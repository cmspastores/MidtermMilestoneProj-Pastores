<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
$userId = require_member();

$search = $_GET['q'] ?? '';
$search = is_string($search) ? trim($search) : '';
$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$categoryId = $categoryId !== false && $categoryId !== null && $categoryId > 0
    ? $categoryId
    : null;

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$sql = <<<'SQL'
SELECT r.id, r.title, r.description, r.created_at, r.updated_at,
       u.username, c.name AS category_name,
       EXISTS(
           SELECT 1 FROM favorites f
           WHERE f.recipe_id = r.id AND f.user_id = :favorite_user
       ) AS is_favorite
FROM recipes r
JOIN users u ON u.id = r.user_id
JOIN categories c ON c.id = r.category_id
WHERE (
    :search_title = ''
    OR r.title LIKE :title_pattern
    OR r.description LIKE :description_pattern
    OR r.instructions LIKE :instructions_pattern
    OR EXISTS (
        SELECT 1 FROM ingredients i
        WHERE i.recipe_id = r.id
          AND (i.ingredient_name LIKE :ingredient_pattern OR i.amount LIKE :amount_pattern)
    )
)
AND (:category_id IS NULL OR r.category_id = :category_filter)
ORDER BY r.created_at DESC, r.id DESC
SQL;
$recipeQuery = $pdo->prepare($sql);
$pattern = '%' . $search . '%';
$recipeQuery->execute([
    'favorite_user' => $userId,
    'search_title' => $search,
    'title_pattern' => $pattern,
    'description_pattern' => $pattern,
    'instructions_pattern' => $pattern,
    'ingredient_pattern' => $pattern,
    'amount_pattern' => $pattern,
    'category_id' => $categoryId,
    'category_filter' => $categoryId,
]);
$recipes = $recipeQuery->fetchAll(PDO::FETCH_ASSOC);

render_header('Browse recipes');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Made by the community</p>
        <h1>Find your next favorite</h1>
        <p class="muted">Explore recipes shared by RecipeBook members.</p>
    </div>
    <a class="button" href="recipe_form.php">＋ Share a recipe</a>
</section>

<?php show_form_error(); ?>

<form class="filters" method="get" action="index.php">
    <div class="field">
        <label for="q">Search recipes</label>
        <input
            type="search"
            id="q"
            name="q"
            placeholder="Try a dish or ingredient"
            value="<?= escape_html($search) ?>"
        >
    </div>
    <div class="field">
        <label for="category">Category</label>
        <select id="category" name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?>
                <option
                    value="<?= escape_html($category['id']) ?>"
                    <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>
                ><?= escape_html($category['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="button" type="submit">Search</button>
    <?php if ($search !== '' || $categoryId !== null): ?>
        <a class="button button-secondary" href="index.php">Clear</a>
    <?php endif; ?>
</form>

<?php if ($recipes === []): ?>
    <div class="empty-state">
        <h2>No recipes found</h2>
        <p>Try another search or be the first to share a recipe.</p>
        <a class="button button-secondary" href="recipe_form.php">Share a recipe</a>
    </div>
<?php else: ?>
    <section class="recipe-grid" aria-label="Recipes">
        <?php foreach ($recipes as $recipe): ?>
            <article class="recipe-card" data-recipe-card>
                <div class="recipe-card-top">
                    <span class="category-pill"><?= escape_html($recipe['category_name']) ?></span>
                    <div>
                        <button
                            class="favorite-button"
                            type="button"
                            data-favorite-button
                            data-recipe-id="<?= escape_html($recipe['id']) ?>"
                            data-csrf-token="<?= escape_html(csrf_token()) ?>"
                            aria-pressed="<?= $recipe['is_favorite'] ? 'true' : 'false' ?>"
                            aria-label="<?= $recipe['is_favorite'] ? 'Remove from favorites' : 'Add to favorites' ?>"
                        ><?= $recipe['is_favorite'] ? '♥' : '♡' ?></button>
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
