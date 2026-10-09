<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function require_member(): int
{
    $userId = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['user'] ?? null;

    if (
        (!is_int($userId) && !(is_string($userId) && ctype_digit($userId)))
        || !is_string($username)
        || $username === ''
    ) {
        header('Location: login.php');
        exit;
    }

    return (int) $userId;
}

function escape_html(string|int|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function require_valid_csrf(): void
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (
        !is_string($submittedToken)
        || !is_string($sessionToken)
        || $sessionToken === ''
        || !hash_equals($sessionToken, $submittedToken)
    ) {
        http_response_code(400);
        exit('The form expired. Please go back, reload the page, and try again.');
    }
}

function render_header(string $title): void
{
    $username = escape_html($_SESSION['user'] ?? '');
    $token = escape_html(csrf_token());
    $safeTitle = escape_html($title);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f7f5ef">
    <title>{$safeTitle} | RecipeBook</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true">🥬</span>RecipeBook</a>
        <nav class="site-nav" aria-label="Main navigation">
            <a href="index.php">Browse</a>
            <a href="recipe_form.php">Share a recipe</a>
            <a href="favorites.php">Favorites</a>
        </nav>
        <div class="account-nav">
            <span class="account-name">{$username}</span>
            <form action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="{$token}">
                <button class="button button-quiet button-small" type="submit">Log out</button>
            </form>
        </div>
    </header>
    <main class="page">
HTML;
}

function render_footer(): void
{
    echo <<<'HTML'
    </main>
    <script src="app.js" defer></script>
</body>
</html>
HTML;
}

function show_form_error(): void
{
    $error = $_GET['error'] ?? '';
    if (!is_string($error) || $error === '') {
        return;
    }

    $messages = [
        'invalid' => 'Please check the form fields and try again.',
        'missing' => 'That item could not be found.',
        'forbidden' => 'You do not have permission to change that item.',
    ];
    $message = $messages[$error] ?? 'The request could not be completed.';
    echo '<p class="notice notice-error" role="alert">' . escape_html($message) . '</p>';
}
