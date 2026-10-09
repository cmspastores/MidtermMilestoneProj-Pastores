<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
$userId = require_member();

$recipeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$recipe = null;
$ingredients = [];
$editing = $recipeId !== false && $recipeId !== null && $recipeId > 0;

if ($editing) {
    $findRecipe = $pdo->prepare(
        'SELECT id, category_id, title, description, instructions FROM recipes WHERE id = :id AND user_id = :user_id'
    );
    $findRecipe->execute(['id' => $recipeId, 'user_id' => $userId]);
    $recipe = $findRecipe->fetch(PDO::FETCH_ASSOC);

    if (!$recipe) {
        http_response_code(404);
        exit('Recipe not found.');
    }

    $findIngredients = $pdo->prepare(
        'SELECT ingredient_name, amount FROM ingredients WHERE recipe_id = :recipe_id ORDER BY id'
    );
    $findIngredients->execute(['recipe_id' => $recipeId]);
    $ingredients = $findIngredients->fetchAll(PDO::FETCH_ASSOC);
}

if ($ingredients === []) {
    $ingredients = [['ingredient_name' => '', 'amount' => '']];
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
render_header($editing ? 'Edit recipe' : 'Share a recipe');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Your kitchen, your story</p>
        <h1><?= $editing ? 'Edit your recipe' : 'Share a recipe' ?></h1>
        <p class="muted">Add the details so other members can make it too.</p>
    </div>
</section>

<?php show_form_error(); ?>

<form class="panel form-panel" action="RecipeController.php" method="post">
    <input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $editing ? 'recipe_update' : 'recipe_create' ?>">
    <?php if ($editing): ?>
        <input type="hidden" name="recipe_id" value="<?= escape_html($recipe['id']) ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="field span-2">
            <label for="title">Recipe title</label>
            <input
                type="text"
                id="title"
                name="title"
                maxlength="200"
                value="<?= escape_html($recipe['title'] ?? '') ?>"
                placeholder="e.g. Sunday tomato soup"
                required
            >
        </div>

        <div class="field span-2">
            <label for="description">Short description</label>
            <textarea
                id="description"
                name="description"
                maxlength="1000"
                placeholder="What makes this recipe special?"
                style="min-height: 82px"
            ><?= escape_html($recipe['description'] ?? '') ?></textarea>
        </div>

        <div class="field span-2">
            <label for="category">Category</label>
            <select id="category" name="category_id" required>
                <option value="">Choose a category</option>
                <?php foreach ($categories as $category): ?>
                    <option
                        value="<?= escape_html($category['id']) ?>"
                        <?= isset($recipe['category_id']) && (int) $recipe['category_id'] === (int) $category['id'] ? 'selected' : '' ?>
                    ><?= escape_html($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field span-2">
            <label>Ingredients</label>
            <div class="ingredient-list" data-ingredient-list>
                <?php foreach ($ingredients as $ingredient): ?>
                    <div class="ingredient-row">
                        <input
                            type="text"
                            name="ingredient_name[]"
                            maxlength="200"
                            value="<?= escape_html($ingredient['ingredient_name']) ?>"
                            placeholder="Ingredient"
                            aria-label="Ingredient name"
                            required
                        >
                        <input
                            type="text"
                            name="amount[]"
                            maxlength="100"
                            value="<?= escape_html($ingredient['amount']) ?>"
                            placeholder="Amount"
                            aria-label="Ingredient amount"
                        >
                        <button class="remove-ingredient" type="button" data-remove-ingredient aria-label="Remove ingredient">×</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="button button-secondary button-small" type="button" data-add-ingredient>＋ Add ingredient</button>
            <p class="field-hint">Add as many ingredients as your recipe needs.</p>
        </div>

        <div class="field span-2">
            <label for="instructions">Cooking steps</label>
            <textarea
                id="instructions"
                name="instructions"
                maxlength="20000"
                placeholder="Write the steps in order, one per line."
                required
            ><?= escape_html($recipe['instructions'] ?? '') ?></textarea>
        </div>
    </div>

    <template id="ingredient-template">
        <div class="ingredient-row">
            <input type="text" name="ingredient_name[]" maxlength="200" placeholder="Ingredient" aria-label="Ingredient name" required>
            <input type="text" name="amount[]" maxlength="100" placeholder="Amount" aria-label="Ingredient amount">
            <button class="remove-ingredient" type="button" data-remove-ingredient aria-label="Remove ingredient">×</button>
        </div>
    </template>

    <div class="form-actions">
        <button class="button" type="submit"><?= $editing ? 'Save changes' : 'Publish recipe' ?></button>
        <a class="button button-secondary" href="<?= $editing ? 'recipe.php?id=' . escape_html($recipe['id']) : 'index.php' ?>">Cancel</a>
    </div>
</form>
<?php render_footer(); ?>
