<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$error = '';
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId) {
        $statement = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $statement->execute([$editId]);
        $editCategory = $statement->fetch();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    if ($action === 'delete' && $id) {
        try {
            $statement = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $statement->execute([$id]);
            flash('Category deleted.');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                flash('This category still has menu items. Move or delete those items first.', 'error');
            } else {
                throw $exception;
            }
        }
        redirect('admin/categories.php');
    }

    if ($action === 'save') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($name === '' || strlen($name) > 100 || strlen($description) > 500) {
            $error = 'Enter a category name (up to 100 characters) and a description up to 500 characters.';
        } else {
            try {
                if ($id) {
                    $statement = $pdo->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?');
                    $statement->execute([$name, $description ?: null, $id]);
                } else {
                    $statement = $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
                    $statement->execute([$name, $description ?: null]);
                }
                flash('Category saved.');
                redirect('admin/categories.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'A category with that name already exists.';
                } else {
                    throw $exception;
                }
            }
        }
    }
}

$categories = $pdo->query(
    'SELECT categories.*, COUNT(menu_items.id) AS item_count
     FROM categories LEFT JOIN menu_items ON menu_items.category_id = categories.id
     GROUP BY categories.id ORDER BY categories.name'
)->fetchAll();
$pageTitle = 'Category management';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Category management</h1>
        <section class="panel">
            <h2><?= $editCategory ? 'Edit category' : 'Add category' ?></h2>
            <?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= e($editCategory['id'] ?? '') ?>">
                <label for="name">Name</label>
                <input id="name" name="name" maxlength="100" required value="<?= e($_POST['name'] ?? $editCategory['name'] ?? '') ?>">
                <label for="description">Description</label>
                <textarea id="description" name="description" maxlength="500"><?= e($_POST['description'] ?? $editCategory['description'] ?? '') ?></textarea>
                <p><button type="submit">Save category</button>
                <?php if ($editCategory): ?><a class="button button-secondary" href="<?= e(url('admin/categories.php')) ?>">Cancel edit</a><?php endif; ?></p>
            </form>
        </section>
        <h2>Categories</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>Name</th><th>Description</th><th>Menu items</th><th>Actions</th></tr></thead>
            <tbody><?php foreach ($categories as $category): ?><tr>
                <td><?= e($category['name']) ?></td><td><?= e($category['description']) ?></td><td><?= (int) $category['item_count'] ?></td>
                <td><div class="table-actions">
                    <a class="button button-small button-secondary" href="<?= e(url('admin/categories.php?edit=' . $category['id'])) ?>">Edit</a>
                    <form method="post" data-confirm="Delete this category? It must not have menu items.">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                        <button class="button-small button-danger" type="submit">Delete</button>
                    </form>
                </div></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
