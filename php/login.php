<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Predis\Client;

try {

    // Load environment variables from .env
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // -----------------------------
    // MySQL connection
    // -----------------------------
    $pdo = new PDO(
        'mysql:host=localhost;dbname=nura_auth;charset=utf8mb4',
        'root',
        ''
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // -----------------------------
    // Get login input
    // -----------------------------
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // -----------------------------
    // Validation
    // -----------------------------
    if ($email === '' || $password === '') {

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

    // -----------------------------
    // Find user in MySQL
    // -----------------------------
    $stmt = $pdo->prepare(
        'SELECT id, username, email, password_hash
         FROM users
         WHERE email = :email
         LIMIT 1'
    );

    $stmt->execute([
        ':email' => $email
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // -----------------------------
    // Verify password
    // -----------------------------
    if (
        !$user ||
        !password_verify($password, $user['password_hash'])
    ) {

        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password.'
        ]);

        exit;
    }

    // -----------------------------
    // Redis Cloud connection
    // -----------------------------
    $redis = new Client([
        'scheme'   => 'redis',
        'host'     => $_ENV['REDIS_HOST'],
        'port'     => (int) $_ENV['REDIS_PORT'],
        'password' => $_ENV['REDIS_PASSWORD']
    ]);

    // Test Redis connection
    $redis->connect();

    // -----------------------------
    // Generate secure session token
    // -----------------------------
    $token = bin2hex(random_bytes(32));

    // Hash token before storing in Redis
    $sessionKey = 'session:' . hash('sha256', $token);

    // -----------------------------
    // Store session in Redis
    // -----------------------------
    $redis->setex(
        $sessionKey,
        3600,
        json_encode([
            'user_id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ])
    );

    // -----------------------------
    // Secure authentication cookie
    // -----------------------------
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

    // -----------------------------
    // Login success
    // -----------------------------
    echo json_encode([
        'success' => true,
        'message' => 'Login successful!'
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}