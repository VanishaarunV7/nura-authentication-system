<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

try {

    // Load .env locally.
    // On Render, environment variables are provided by Render.
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

    // Get input
    $usernameInput = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwordInput = $_POST['password'] ?? '';

    // Validation
    if ($usernameInput === '' || $email === '' || $passwordInput === '') {
        echo json_encode([
            'success' => false,
            'message' => 'All fields are required.'
        ]);
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $usernameInput)) {
        echo json_encode([
            'success' => false,
            'message' => 'Username must contain 3-50 letters, numbers or underscores.'
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

    if (strlen($passwordInput) < 8) {
        echo json_encode([
            'success' => false,
            'message' => 'Password must contain at least 8 characters.'
        ]);
        exit;
    }

    // Check duplicate username/email
    $check = $pdo->prepare(
        'SELECT id
         FROM users
         WHERE username = :username
            OR email = :email
         LIMIT 1'
    );

    $check->execute([
        ':username' => $usernameInput,
        ':email' => $email
    ]);

    if ($check->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Username or email already exists.'
        ]);
        exit;
    }

    // Hash password
    $passwordHash = password_hash(
        $passwordInput,
        PASSWORD_DEFAULT
    );

    // Insert user
    $stmt = $pdo->prepare(
        'INSERT INTO users
            (username, email, password_hash)
         VALUES
            (:username, :email, :password_hash)'
    );

    $stmt->execute([
        ':username' => $usernameInput,
        ':email' => $email,
        ':password_hash' => $passwordHash
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Redirecting to login...'
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again.'
    ]);

} catch (Throwable $e) {

    error_log($e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong.'
    ]);
}