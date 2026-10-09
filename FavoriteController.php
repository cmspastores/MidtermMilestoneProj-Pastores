<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
$userId = require_member();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';
if (
    !is_string($csrfToken)
    || !is_string($sessionToken)
    || $sessionToken === ''
    || !hash_equals($sessionToken, $csrfToken)
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Your session expired. Refresh the page and try again.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$recipeId = is_array($payload) ? filter_var($payload['recipe_id'] ?? null, FILTER_VALIDATE_INT) : false;
$favorite = is_array($payload) ? ($payload['favorite'] ?? null) : null;

if (!$recipeId || $recipeId < 1 || !is_bool($favorite)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid favorite request.']);
    exit;
}

$recipeCheck = $pdo->prepare('SELECT id FROM recipes WHERE id = :id');
$recipeCheck->execute(['id' => $recipeId]);
if (!$recipeCheck->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Recipe not found.']);
    exit;
}

if ($favorite) {
    $saveFavorite = $pdo->prepare(
        'INSERT IGNORE INTO favorites (user_id, recipe_id) VALUES (:user_id, :recipe_id)'
    );
    $saveFavorite->execute(['user_id' => $userId, 'recipe_id' => $recipeId]);
} else {
    $removeFavorite = $pdo->prepare(
        'DELETE FROM favorites WHERE user_id = :user_id AND recipe_id = :recipe_id'
    );
    $removeFavorite->execute(['user_id' => $userId, 'recipe_id' => $recipeId]);
}

echo json_encode(['success' => true, 'favorite' => $favorite]);
