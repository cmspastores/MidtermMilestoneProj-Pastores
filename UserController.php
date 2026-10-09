<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$action = $_POST['action'] ?? '';
if (!is_string($action)) {
    http_response_code(400);
    exit('Invalid action.');
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!is_string($email) || !is_string($password)) {
    http_response_code(400);
    exit('Invalid form data.');
}

$email = trim($email);

if ($action === 'Register') {
    $username = $_POST['username'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    if (
        !is_string($username)
        || !is_string($passwordConfirmation)
        || trim($username) === ''
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($password) < 8
        || strlen($password) > 72
        || $password !== $passwordConfirmation
    ) {
        header('Location: Register.php?error=invalid_input');
        exit;
    }

    $username = trim($username);
    $existingUser = $pdo->prepare(
        'SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1'
    );
    $existingUser->execute([
        'email' => $email,
        'username' => $username,
    ]);

    if ($existingUser->fetch()) {
        header('Location: Register.php?error=account_exists');
        exit;
    }

    $createUser = $pdo->prepare(
        'INSERT INTO users (username, email, password) VALUES (:username, :email, :password)'
    );
    $createUser->execute([
        'username' => $username,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['user'] = $username;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    header('Location: index.php');
    exit;
}

if ($action === 'Login') {
    $findUser = $pdo->prepare(
        'SELECT id, username, email, password FROM users WHERE email = :email LIMIT 1'
    );
    $findUser->execute(['email' => $email]);
    $user = $findUser->fetch(PDO::FETCH_ASSOC);

    if (
        !$user
        || !is_string($user['password'])
        || !password_verify($password, $user['password'])
    ) {
        header('Location: login.php?error=invalid_credentials');
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = $user['username'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    header('Location: index.php');
    exit;
}

http_response_code(400);
exit('Invalid action.');
