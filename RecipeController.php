<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
$userId = require_member();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

require_valid_csrf();

$action = $_POST['action'] ?? '';
if (!is_string($action)) {
    http_response_code(400);
    exit('Invalid action.');
}

$recipeId = filter_input(INPUT_POST, 'recipe_id', FILTER_VALIDATE_INT);
$recipeId = $recipeId !== false && $recipeId !== null && $recipeId > 0 ? $recipeId : null;

function redirect_recipe_form_error(bool $editing): void
{
    $target = $editing && isset($_POST['recipe_id'])
        ? 'recipe_form.php?id=' . rawurlencode((string) $_POST['recipe_id'])
        : 'recipe_form.php';
    header('Location: ' . $target . (str_contains($target, '?') ? '&' : '?') . 'error=invalid');
    exit;
}

if ($action === 'recipe_create' || $action === 'recipe_update') {
    $editing = $action === 'recipe_update';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $instructions = $_POST['instructions'] ?? '';
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $ingredientNames = $_POST['ingredient_name'] ?? [];
    $amounts = $_POST['amount'] ?? [];

    if (
        !is_string($title)
        || !is_string($description)
        || !is_string($instructions)
        || !is_array($ingredientNames)
        || !is_array($amounts)
        || trim($title) === ''
        || strlen($title) > 200
        || strlen($description) > 1000
        || trim($instructions) === ''
        || strlen($instructions) > 20000
        || $categoryId === false
        || $categoryId === null
        || $categoryId < 1
        || ($editing && $recipeId === null)
    ) {
        redirect_recipe_form_error($editing);
    }

    $ingredientRows = [];
    foreach ($ingredientNames as $index => $name) {
        $amount = $amounts[$index] ?? '';
        if (!is_string($name) || !is_string($amount)) {
            redirect_recipe_form_error($editing);
        }
        $name = trim($name);
        $amount = trim($amount);
        if ($name === '' && $amount === '') {
            continue;
        }
        if ($name === '' || strlen($name) > 200 || strlen($amount) > 100) {
            redirect_recipe_form_error($editing);
        }
        $ingredientRows[] = ['name' => $name, 'amount' => $amount];
    }

    if ($ingredientRows === []) {
        redirect_recipe_form_error($editing);
    }

    $categoryCheck = $pdo->prepare('SELECT id FROM categories WHERE id = :id');
    $categoryCheck->execute(['id' => $categoryId]);
    if (!$categoryCheck->fetchColumn()) {
        redirect_recipe_form_error($editing);
    }

    $pdo->beginTransaction();
    try {
        if ($editing) {
            $updateRecipe = $pdo->prepare(
                'UPDATE recipes
                 SET category_id = :category_id, title = :title, description = :description, instructions = :instructions,
                     is_edited = 1
                 WHERE id = :id AND user_id = :user_id'
            );
            $updateRecipe->execute([
                'category_id' => $categoryId,
                'title' => trim($title),
                'description' => trim($description) === '' ? null : trim($description),
                'instructions' => trim($instructions),
                'id' => $recipeId,
                'user_id' => $userId,
            ]);

            if ($updateRecipe->rowCount() === 0) {
                $ownerCheck = $pdo->prepare('SELECT id FROM recipes WHERE id = :id AND user_id = :user_id');
                $ownerCheck->execute(['id' => $recipeId, 'user_id' => $userId]);
                if (!$ownerCheck->fetchColumn()) {
                    $pdo->rollBack();
                    header('Location: recipe.php?id=' . $recipeId . '&error=forbidden');
                    exit;
                }
            }

            $deleteIngredients = $pdo->prepare('DELETE FROM ingredients WHERE recipe_id = :recipe_id');
            $deleteIngredients->execute(['recipe_id' => $recipeId]);
            $savedRecipeId = $recipeId;
        } else {
            $createRecipe = $pdo->prepare(
                'INSERT INTO recipes (user_id, category_id, title, description, instructions)
                 VALUES (:user_id, :category_id, :title, :description, :instructions)'
            );
            $createRecipe->execute([
                'user_id' => $userId,
                'category_id' => $categoryId,
                'title' => trim($title),
                'description' => trim($description) === '' ? null : trim($description),
                'instructions' => trim($instructions),
            ]);
            $savedRecipeId = (int) $pdo->lastInsertId();
        }

        $insertIngredient = $pdo->prepare(
            'INSERT INTO ingredients (recipe_id, ingredient_name, amount)
             VALUES (:recipe_id, :ingredient_name, :amount)'
        );
        foreach ($ingredientRows as $ingredient) {
            $insertIngredient->execute([
                'recipe_id' => $savedRecipeId,
                'ingredient_name' => $ingredient['name'],
                'amount' => $ingredient['amount'] === '' ? null : $ingredient['amount'],
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    header('Location: recipe.php?id=' . $savedRecipeId);
    exit;
}

if ($action === 'recipe_delete') {
    if ($recipeId === null) {
        http_response_code(400);
        exit('Invalid recipe.');
    }
    $deleteRecipe = $pdo->prepare('DELETE FROM recipes WHERE id = :id AND user_id = :user_id');
    $deleteRecipe->execute(['id' => $recipeId, 'user_id' => $userId]);
    if ($deleteRecipe->rowCount() === 0) {
        header('Location: recipe.php?id=' . $recipeId . '&error=forbidden');
        exit;
    }
    header('Location: index.php');
    exit;
}

if ($action === 'comment_create' || $action === 'comment_update') {
    $content = $_POST['content'] ?? '';
    if (!is_string($content) || trim($content) === '' || strlen($content) > 5000) {
        header('Location: recipe.php?id=' . (int) $recipeId . '&error=invalid#comments');
        exit;
    }

    if ($action === 'comment_create') {
        if ($recipeId === null) {
            http_response_code(400);
            exit('Invalid recipe.');
        }
        $createComment = $pdo->prepare(
            'INSERT INTO comments (recipe_id, user_id, content) VALUES (:recipe_id, :user_id, :content)'
        );
        $createComment->execute([
            'recipe_id' => $recipeId,
            'user_id' => $userId,
            'content' => trim($content),
        ]);
    } else {
        $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
        if ($recipeId === null || !$commentId || $commentId < 1) {
            http_response_code(400);
            exit('Invalid comment.');
        }
        $updateComment = $pdo->prepare(
            'UPDATE comments SET content = :content, is_edited = 1
             WHERE id = :id AND recipe_id = :recipe_id AND user_id = :user_id'
        );
        $updateComment->execute([
            'content' => trim($content),
            'id' => $commentId,
            'recipe_id' => $recipeId,
            'user_id' => $userId,
        ]);
        if ($updateComment->rowCount() === 0) {
            $commentCheck = $pdo->prepare(
                'SELECT id FROM comments WHERE id = :id AND recipe_id = :recipe_id AND user_id = :user_id'
            );
            $commentCheck->execute([
                'id' => $commentId,
                'recipe_id' => $recipeId,
                'user_id' => $userId,
            ]);
            if (!$commentCheck->fetchColumn()) {
                header('Location: recipe.php?id=' . $recipeId . '&error=forbidden#comments');
                exit;
            }
        }
    }

    header('Location: recipe.php?id=' . $recipeId . '#comments');
    exit;
}

if ($action === 'comment_delete') {
    $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    if ($recipeId === null || !$commentId || $commentId < 1) {
        http_response_code(400);
        exit('Invalid comment.');
    }
    $deleteComment = $pdo->prepare(
        'DELETE FROM comments WHERE id = :id AND recipe_id = :recipe_id AND user_id = :user_id'
    );
    $deleteComment->execute([
        'id' => $commentId,
        'recipe_id' => $recipeId,
        'user_id' => $userId,
    ]);
    if ($deleteComment->rowCount() === 0) {
        header('Location: recipe.php?id=' . $recipeId . '&error=forbidden#comments');
        exit;
    }
    header('Location: recipe.php?id=' . $recipeId . '#comments');
    exit;
}

http_response_code(400);
exit('Invalid action.');
