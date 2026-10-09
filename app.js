document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-favorite-button]');
    if (!button || button.disabled) {
        return;
    }

    const status = button.parentElement.querySelector('.favorite-status');
    button.disabled = true;
    if (status) {
        status.textContent = 'Saving…';
    }

    try {
        const response = await fetch('FavoriteController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': button.dataset.csrfToken
            },
            body: JSON.stringify({
                recipe_id: button.dataset.recipeId,
                favorite: button.getAttribute('aria-pressed') !== 'true'
            })
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Could not update favorite.');
        }

        button.setAttribute('aria-pressed', String(result.favorite));
        button.setAttribute('aria-label', result.favorite ? 'Remove from favorites' : 'Add to favorites');
        button.textContent = result.favorite ? '♥' : '♡';
        if (status) {
            status.textContent = result.favorite ? 'Saved to favorites' : 'Removed from favorites';
        }

        if (!result.favorite && button.dataset.removeOnUnfavorite === 'true') {
            button.closest('[data-recipe-card]').remove();
            const grid = document.querySelector('[data-recipe-grid]');
            if (grid && grid.children.length === 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'empty-state';

                const heading = document.createElement('h2');
                heading.textContent = 'Your collection is empty';

                const message = document.createElement('p');
                message.textContent = 'Tap the heart on a recipe to save it here.';

                const link = document.createElement('a');
                link.className = 'button button-secondary';
                link.href = 'index.php';
                link.textContent = 'Explore recipes';

                emptyState.append(heading, message, link);
                grid.replaceWith(emptyState);
            }
        }
    } catch (error) {
        if (status) {
            status.textContent = error.message;
        }
    } finally {
        button.disabled = false;
    }
});

document.addEventListener('click', (event) => {
    const addButton = event.target.closest('[data-add-ingredient]');
    if (addButton) {
        const list = document.querySelector('[data-ingredient-list]');
        const template = document.querySelector('#ingredient-template');
        list.append(template.content.cloneNode(true));
        list.lastElementChild.querySelector('input').focus();
        return;
    }

    const removeButton = event.target.closest('[data-remove-ingredient]');
    if (!removeButton) {
        return;
    }

    const list = removeButton.closest('[data-ingredient-list]');
    const row = removeButton.closest('.ingredient-row');
    if (list.children.length > 1) {
        row.remove();
    } else {
        row.querySelectorAll('input').forEach((input) => {
            input.value = '';
        });
    }
});
