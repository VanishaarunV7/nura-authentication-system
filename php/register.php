<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

try {

    // MySQL connection
    $pdo = new PDO(
        'mysql:host=localhost;dbname=nura_auth;charset=utf8mb4',
        'root',
        ''
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get input
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if ($username === '' || $email === '' || $password === '') {

        echo json_encode([
            'success' => false,
            'message' => 'All fields are required.'
        ]);

        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {

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

    if (strlen($password) < 8) {

        echo json_encode([
            'success' => false,
            'message' => 'Password must contain at least 8 characters.'
        ]);

        exit;
    }

    // Check whether username or email already exists
    $check = $pdo->prepare(
        'SELECT id
         FROM users
         WHERE username = :username
         OR email = :email
         LIMIT 1'
    );

    $check->execute([
        ':username' => $username,
        ':email' => $email
    ]);

    if ($check->fetch()) {

        echo json_encode([
            'success' => false,
            'message' => 'Username or email already exists.'
        ]);

        exit;
    }

    // Securely hash password
    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // Insert user into MySQL
    $stmt = $pdo->prepare(
        'INSERT INTO users
        (username, email, password_hash)
        VALUES
        (:username, :email, :password_hash)'
    );

    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password_hash' => $passwordHash
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Redirecting to login...'
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again.'
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong.'
    ]);
}