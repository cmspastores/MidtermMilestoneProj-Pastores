<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
$userId = require_member();

$recipeQuery = $pdo->prepare(
    'SELECT r.id, r.title, r.description, r.created_at, u.username, c.name AS category_name
     FROM favorites f
     JOIN recipes r ON r.id = f.recipe_id
     JOIN users u ON u.id = r.user_id
     JOIN categories c ON c.id = r.category_id
     WHERE f.user_id = :user_id
     ORDER BY f.created_at DESC, f.recipe_id DESC'
);
$recipeQuery->execute(['user_id' => $userId]);
$recipes = $recipeQuery->fetchAll(PDO::FETCH_ASSOC);
render_header('Your favorites');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Saved for later</p>
        <h1>Your favorites</h1>
        <p class="muted">Recipes you’ve saved to your personal collection.</p>
    </div>
    <a class="button button-secondary" href="index.php">Browse recipes</a>
</section>

<?php if ($recipes === []): ?>
    <div class="empty-state">
        <h2>Your collection is empty</h2>
        <p>Tap the heart on a recipe to save it here.</p>
        <a class="button button-secondary" href="index.php">Explore recipes</a>
    </div>
<?php else: ?>
    <section class="recipe-grid" data-recipe-grid aria-label="Favorite recipes">
        <?php foreach ($recipes as $recipe): ?>
            <article class="recipe-card" data-recipe-card>
                <div class="recipe-card-top">
                    <span class="category-pill"><?= escape_html($recipe['category_name']) ?></span>
                    <div>
                        <button
                            class="favorite-button"
                            type="button"
                            data-favorite-button
                            data-remove-on-unfavorite="true"
                            data-recipe-id="<?= escape_html($recipe['id']) ?>"
                            data-csrf-token="<?= escape_html(csrf_token()) ?>"
                            aria-pressed="true"
                            aria-label="Remove from favorites"
                        >♥</button>
                        <span class="favorite-status" aria-live="polite"></span>
                    </div>
                </div>
                <h2><a href="recipe.php?id=<?= escape_html($recipe['id']) ?>"><?= escape_html($recipe['title']) ?></a></h2>
                <p class="description"><?= escape_html($recipe['description'] ?: 'A delicious recipe shared with the community.') ?></p>
                <div class="recipe-meta">
                    <span>By <?= escape_html($recipe['username']) ?></span>
                    <time datetime="<?= escape_html($recipe['created_at']) ?>"><?= escape_html(date('M j, Y', strtotime($recipe['created_at']))) ?></time>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?php render_footer(); ?>
