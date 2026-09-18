<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Predis\Client;
use MongoDB\Client as MongoClient;

try {
    // Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // MySQL connection
    $pdo = new PDO(
        'mysql:host=localhost;dbname=nura_auth;charset=utf8mb4',
        'root',
        ''
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Redis connection
    $redis = new Client([
        'scheme'   => 'redis',
        'host'     => $_ENV['REDIS_HOST'],
        'port'     => (int) $_ENV['REDIS_PORT'],
        'password' => $_ENV['REDIS_PASSWORD']
    ]);

    $redis->connect();

    // Get authentication token
    $token = $_COOKIE['auth_token'] ?? '';

    if ($token === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Please login first.'
        ]);
        exit;
    }

    // Find session in Redis
    $sessionKey = 'session:' . hash('sha256', $token);
    $sessionData = $redis->get($sessionKey);

    if (!$sessionData) {
        echo json_encode([
            'success' => false,
            'message' => 'Session expired. Please login again.'
        ]);
        exit;
    }

    $session = json_decode($sessionData, true);

    if (!$session || empty($session['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid session.'
        ]);
        exit;
    }

    $userId = (int) $session['user_id'];

    // MongoDB connection
    $mongoClient = new MongoClient($_ENV['MONGODB_URI']);
    $database = $mongoClient->selectDatabase($_ENV['MONGODB_DATABASE']);

    $profilesCollection = $database->profiles;

    /*
    |--------------------------------------------------------------------------
    | GET - Load profile
    |--------------------------------------------------------------------------
    */

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Get user from MySQL
        $stmt = $pdo->prepare(
            'SELECT id, username, email
             FROM users
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            ':id' => $userId
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode([
                'success' => false,
                'message' => 'User not found.'
            ]);
            exit;
        }

        // Get additional profile information from MongoDB
        $profile = $profilesCollection->findOne([
            'user_id' => $userId
        ]);

        $profileData = null;

        if ($profile) {
            $profileData = [
                'fullName' => $profile['fullName'] ?? '',
                'age' => $profile['age'] ?? '',
                'bio' => $profile['bio'] ?? '',
                'interests' => $profile['interests'] ?? ''
            ];
        }

        echo json_encode([
            'success' => true,
            'user' => [
                'username' => $user['username'],
                'email' => $user['email']
            ],
            'profile' => $profileData
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | POST - Logout
    |--------------------------------------------------------------------------
    */

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $action = $_POST['action'] ?? '';

        if ($action === 'logout') {

            // Delete Redis session
            $redis->del([$sessionKey]);

            // Remove authentication cookie
            setcookie('auth_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Logged out successfully.'
            ]);

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | POST - Update profile
        |--------------------------------------------------------------------------
        */

        $fullName = trim($_POST['fullName'] ?? '');
        $age = trim($_POST['age'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $interests = trim($_POST['interests'] ?? '');

        // Validate full name
        if ($fullName === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Full name is required.'
            ]);
            exit;
        }

        // Validate age
        if ($age !== '') {

            if (!filter_var($age, FILTER_VALIDATE_INT)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Age must be a valid number.'
                ]);
                exit;
            }

            $age = (int) $age;

            if ($age < 1 || $age > 120) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Age must be between 1 and 120.'
                ]);
                exit;
            }
        } else {
            $age = null;
        }

        // Save/update profile in MongoDB
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

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully.'
        ]);

        exit;
    }

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

} catch (Exception $e) {

    // Temporary debugging message
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}