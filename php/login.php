<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Predis\Client;

try {

    // Load .env locally.
    // Render provides environment variables automatically.
    $envFile = __DIR__ . '/../.env';

    if (file_exists($envFile)) {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
    }

    // MySQL configuration
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

    // Get login input
    $email = trim($_POST['email'] ?? '');
    $passwordInput = $_POST['password'] ?? '';

    // Validation
    if ($email === '' || $passwordInput === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Email and password are required.'
        ]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid email address.'
        ]);
        exit;
    }

    // Find user
    $stmt = $pdo->prepare(
        'SELECT id, username, email, password_hash
         FROM users
         WHERE email = :email
         LIMIT 1'
    );

    $stmt->execute([
        ':email' => $email
    ]);

    $user = $stmt->fetch();

    // Verify password
    if (
        !$user ||
        !password_verify($passwordInput, $user['password_hash'])
    ) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password.'
        ]);
        exit;
    }

    // Redis Cloud connection
    $redis = new Client([
        'scheme' => $_ENV['REDIS_SCHEME'] ?? getenv('REDIS_SCHEME') ?: 'redis',
        'host' => $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?: '',
        'port' => (int) ($_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?: 6379),
        'username' => $_ENV['REDIS_USERNAME'] ?? getenv('REDIS_USERNAME') ?: 'default',
        'password' => $_ENV['REDIS_PASSWORD'] ?? getenv('REDIS_PASSWORD') ?: ''
    ]);

    // Generate secure token
    $token = bin2hex(random_bytes(32));

    // Hash token for Redis key
    $sessionKey = 'session:' . hash('sha256', $token);

    // Store session for 1 hour
    $redis->setex(
        $sessionKey,
        3600,
        json_encode([
            'user_id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ])
    );

    // Authentication cookie
    $secure = (
        !empty($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    );

    setcookie('auth_token', $token, [
        'expires' => time() + 3600,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Login successful!'
    ]);

} catch (Throwable $e) {

    error_log($e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Unable to login at this time.'
    ]);
}