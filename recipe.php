<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
$userId = require_member();
$recipeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($recipeId === false || $recipeId === null || $recipeId < 1) {
    http_response_code(404);
    exit('Recipe not found.');
}

$recipeQuery = $pdo->prepare(
    'SELECT r.*, u.username, c.name AS category_name,
            EXISTS(
                SELECT 1 FROM favorites f WHERE f.user_id = :user_id AND f.recipe_id = r.id
            ) AS is_favorite
     FROM recipes r
     JOIN users u ON u.id = r.user_id
     JOIN categories c ON c.id = r.category_id
     WHERE r.id = :recipe_id'
);
$recipeQuery->execute(['user_id' => $userId, 'recipe_id' => $recipeId]);
$recipe = $recipeQuery->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    http_response_code(404);
    exit('Recipe not found.');
}

$ingredientQuery = $pdo->prepare(
    'SELECT ingredient_name, amount FROM ingredients WHERE recipe_id = :recipe_id ORDER BY id'
);
$ingredientQuery->execute(['recipe_id' => $recipeId]);
$ingredients = $ingredientQuery->fetchAll(PDO::FETCH_ASSOC);

$commentQuery = $pdo->prepare(
    'SELECT c.id, c.user_id, c.content, c.created_at, c.updated_at, c.is_edited, u.username
     FROM comments c
     JOIN users u ON u.id = c.user_id
     WHERE c.recipe_id = :recipe_id
     ORDER BY c.created_at DESC, c.id DESC'
);
$commentQuery->execute(['recipe_id' => $recipeId]);
$comments = $commentQuery->fetchAll(PDO::FETCH_ASSOC);
$isOwner = (int) $recipe['user_id'] === $userId;

render_header((string) $recipe['title']);
?>
<article class="recipe-detail">
    <?php show_form_error(); ?>
    <section class="panel">
        <a class="text-button" href="index.php">← Back to recipes</a>
        <div class="recipe-card-top" style="margin-top: 20px">
            <span class="category-pill"><?= escape_html($recipe['category_name']) ?></span>
            <button
                class="favorite-button"
                type="button"
                data-favorite-button
                data-recipe-id="<?= escape_html($recipe['id']) ?>"
                data-csrf-token="<?= escape_html(csrf_token()) ?>"
                aria-pressed="<?= $recipe['is_favorite'] ? 'true' : 'false' ?>"
                aria-label="<?= $recipe['is_favorite'] ? 'Remove from favorites' : 'Add to favorites' ?>"
            ><?= $recipe['is_favorite'] ? '♥' : '♡' ?></button>
        </div>
        <h1><?= escape_html($recipe['title']) ?></h1>
        <p class="recipe-description"><?= escape_html($recipe['description'] ?: '') ?></p>
        <div class="detail-meta">
            <span>Shared by <?= escape_html($recipe['username']) ?></span>
            <time datetime="<?= escape_html($recipe['created_at']) ?>">Posted <?= escape_html(date('M j, Y', strtotime($recipe['created_at']))) ?></time>
            <?php if ((bool) $recipe['is_edited']): ?>
                <span class="edited-label">Edited <?= escape_html(date('M j, Y', strtotime($recipe['updated_at']))) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($isOwner): ?>
            <div class="recipe-actions">
                <a class="button button-secondary" href="recipe_form.php?id=<?= escape_html($recipe['id']) ?>">Edit recipe</a>
                <form class="inline-form" action="RecipeController.php" method="post" onsubmit="return confirm('Delete this recipe and its comments?');">
                    <input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>">
                    <input type="hidden" name="action" value="recipe_delete">
                    <input type="hidden" name="recipe_id" value="<?= escape_html($recipe['id']) ?>">
                    <button class="button button-danger" type="submit">Delete recipe</button>
                </form>
            </div>
        <?php endif; ?>

        <hr style="margin: 27px 0; border: 0; border-top: 1px solid #eeede8">
        <h2>Ingredients</h2>
        <?php if ($ingredients === []): ?>
            <p class="muted">No ingredients listed.</p>
        <?php else: ?>
            <ul class="ingredient-list-read">
                <?php foreach ($ingredients as $ingredient): ?>
                    <li>
                        <?= escape_html($ingredient['ingredient_name']) ?>
                        <?php if ($ingredient['amount'] !== null && $ingredient['amount'] !== ''): ?>
                            <span class="muted"> — <?= escape_html($ingredient['amount']) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2 style="margin-top: 30px">Cooking steps</h2>
        <div class="instructions"><?= escape_html($recipe['instructions']) ?></div>
    </section>

    <section class="panel" id="comments">
        <h2>Member comments <span class="muted">(<?= count($comments) ?>)</span></h2>
        <form class="comment-form" action="RecipeController.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>">
            <input type="hidden" name="action" value="comment_create">
            <input type="hidden" name="recipe_id" value="<?= escape_html($recipe['id']) ?>">
            <label class="field" for="new-comment">
                Leave a comment
                <textarea id="new-comment" name="content" maxlength="5000" placeholder="Share how it went or ask a question…" required></textarea>
            </label>
            <div><button class="button" type="submit">Post comment</button></div>
        </form>

        <?php if ($comments !== []): ?>
            <div class="comments" style="margin-top: 22px">
                <?php foreach ($comments as $comment): ?>
                    <article class="comment">
                        <div class="comment-top">
                            <span class="comment-author"><?= escape_html($comment['username']) ?></span>
                            <time class="comment-date" datetime="<?= escape_html($comment['created_at']) ?>"><?= escape_html(date('M j, Y', strtotime($comment['created_at']))) ?></time>
                            <?php if ((bool) $comment['is_edited']): ?>
                                <span class="edited-label">Edited</span>
                            <?php endif; ?>
                        </div>
                        <?php if ((int) $comment['user_id'] === $userId): ?>
                            <form class="comment-form" action="RecipeController.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>">
                                <input type="hidden" name="action" value="comment_update">
                                <input type="hidden" name="recipe_id" value="<?= escape_html($recipe['id']) ?>">
                                <input type="hidden" name="comment_id" value="<?= escape_html($comment['id']) ?>">
                                <textarea name="content" maxlength="5000" aria-label="Edit your comment" required><?= escape_html($comment['content']) ?></textarea>
                                <div class="comment-tools">
                                    <button class="text-button" type="submit">Save edit</button>
                                </div>
                            </form>
                            <form class="inline-form" action="RecipeController.php" method="post" onsubmit="return confirm('Delete your comment?');">
                                <input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>">
                                <input type="hidden" name="action" value="comment_delete">
                                <input type="hidden" name="recipe_id" value="<?= escape_html($recipe['id']) ?>">
                                <input type="hidden" name="comment_id" value="<?= escape_html($comment['id']) ?>">
                                <button class="text-button danger" type="submit">Delete comment</button>
                            </form>
                        <?php else: ?>
                            <p class="comment-content"><?= escape_html($comment['content']) ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="muted" style="margin-top: 20px">No comments yet. Start the conversation.</p>
        <?php endif; ?>
    </section>
</article>
<?php render_footer(); ?>
