<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Predis\Client;
use MongoDB\Client as MongoClient;

function respond(
    bool $success,
    string $message = '',
    array $extra = [],
    int $status = 200
): never {

    http_response_code($status);

    echo json_encode(
        array_merge(
            [
                'success' => $success,
                'message' => $message
            ],
            $extra
        )
    );

    exit;
}

try {

    // Load .env locally.
    // Render provides environment variables automatically.
    $envFile = __DIR__ . '/../.env';

    if (file_exists($envFile)) {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
    }

    // =========================================================
    // MySQL configuration
    // =========================================================

    $host = $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?: 'localhost';
    $port = $_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?: '3306';
    $database = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?: 'nura_auth';
    $username = $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?: 'root';
    $password = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    // Aiven MySQL SSL
    $sslCa = $_ENV['MYSQL_SSL_CA'] ?? getenv('MYSQL_SSL_CA') ?: '';

    if ($sslCa !== '' && file_exists($sslCa)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        $options
    );

    // =========================================================
    // Redis connection
    // =========================================================

    $redis = new Client([
        'scheme'   => $_ENV['REDIS_SCHEME'] ?? getenv('REDIS_SCHEME') ?: 'redis',
        'host'     => $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?: '',
        'port'     => (int) ($_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?: 6379),
        'username' => $_ENV['REDIS_USERNAME'] ?? getenv('REDIS_USERNAME') ?: 'default',
        'password' => $_ENV['REDIS_PASSWORD'] ?? getenv('REDIS_PASSWORD') ?: ''
    ]);

    // =========================================================
    // Authenticate using Redis session
    // =========================================================

    $token = $_COOKIE['auth_token'] ?? '';

    if ($token === '') {
        respond(false, 'Please login first.', [], 401);
    }

    $sessionKey = 'session:' . hash('sha256', $token);

    $sessionData = $redis->get($sessionKey);

    if (!$sessionData) {
        respond(
            false,
            'Session expired. Please login again.',
            [],
            401
        );
    }

    $session = json_decode($sessionData, true);

    if (
        !is_array($session) ||
        empty($session['user_id'])
    ) {
        respond(false, 'Invalid session.', [], 401);
    }

    $userId = (int) $session['user_id'];

    // =========================================================
    // MongoDB connection
    // =========================================================

    $mongoUri = $_ENV['MONGODB_URI'] ?? getenv('MONGODB_URI') ?: '';
    $mongoDatabase = $_ENV['MONGODB_DATABASE'] ?? getenv('MONGODB_DATABASE') ?: '';

    $mongoClient = new MongoClient($mongoUri);

    $databaseMongo = $mongoClient->selectDatabase(
        $mongoDatabase
    );

    $profilesCollection = $databaseMongo->profiles;

    // =========================================================
    // GET - Load profile
    // =========================================================

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $stmt = $pdo->prepare(
            'SELECT id, username, email
             FROM users
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            ':id' => $userId
        ]);

        $user = $stmt->fetch();

        if (!$user) {
            respond(false, 'User not found.', [], 404);
        }

        $profile = $profilesCollection->findOne([
            'user_id' => $userId
        ]);

        $profileData = null;

        if ($profile !== null) {

            $profileData = [
                'fullName' => $profile['fullName'] ?? '',
                'age' => $profile['age'] ?? '',
                'bio' => $profile['bio'] ?? '',
                'interests' => $profile['interests'] ?? ''
            ];
        }

        respond(
            true,
            '',
            [
                'user' => [
                    'username' => $user['username'],
                    'email' => $user['email']
                ],
                'profile' => $profileData
            ]
        );
    }

    // =========================================================
    // Only POST allowed below
    // =========================================================

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(false, 'Invalid request method.', [], 405);
    }

    $action = $_POST['action'] ?? '';

    // =========================================================
    // POST - Logout
    // =========================================================

    if ($action === 'logout') {

        $redis->del([$sessionKey]);

        $secure = (
            !empty($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] !== 'off'
        );

        setcookie('auth_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        respond(
            true,
            'Logged out successfully.'
        );
    }

    // =========================================================
    // POST - Update profile
    // =========================================================

    $fullName = trim((string) ($_POST['fullName'] ?? ''));
    $ageInput = trim((string) ($_POST['age'] ?? ''));
    $bio = trim((string) ($_POST['bio'] ?? ''));
    $interests = trim((string) ($_POST['interests'] ?? ''));

    if (
        $fullName === '' ||
        strlen($fullName) > 100
    ) {
        respond(
            false,
            'Full name is required and must be at most 100 characters.',
            [],
            422
        );
    }

    if ($ageInput !== '') {

        if (!ctype_digit($ageInput)) {
            respond(
                false,
                'Age must be a valid number.',
                [],
                422
            );
        }

        $age = (int) $ageInput;

        if ($age < 1 || $age > 120) {
            respond(
                false,
                'Age must be between 1 and 120.',
                [],
                422
            );
        }

    } else {

        $age = null;
    }

    if (strlen($bio) > 500) {
        respond(
            false,
            'Bio must be at most 500 characters.',
            [],
            422
        );
    }

    if (strlen($interests) > 255) {
        respond(
            false,
            'Interests must be at most 255 characters.',
            [],
            422
        );
    }

    // =========================================================
    // Save profile in MongoDB
    // =========================================================

    $profilesCollection->updateOne(
        ['user_id' => $userId],
        [
            '$set' => [
                'user_id' => $userId,
                'fullName' => $fullName,
                'age' => $age,
                'bio' => $bio,
                'interests' => $interests,
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ]
        ],
        ['upsert' => true]
    );

    respond(
        true,
        'Profile updated successfully.'
    );

} catch (Throwable $e) {

    error_log($e->getMessage());

    respond(
        false,
        'Profile service is temporarily unavailable.',
        [],
        500
    );
}