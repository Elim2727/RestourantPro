<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$error = '';
$editItem = null;
if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId) {
        $statement = $pdo->prepare('SELECT * FROM menu_items WHERE id = ?');
        $statement->execute([$editId]);
        $editItem = $statement->fetch();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    if ($action === 'delete' && $id) {
        $statement = $pdo->prepare('SELECT image_path FROM menu_items WHERE id = ?');
        $statement->execute([$id]);
        $oldImage = $statement->fetchColumn();
        $statement = $pdo->prepare('DELETE FROM menu_items WHERE id = ?');
        $statement->execute([$id]);
        if ($oldImage && strpos($oldImage, 'uploads/menu/') === 0) {
            $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldImage);
            if (is_file($file)) {
                unlink($file);
            }
        }
        flash('Menu item deleted. Past orders keep their saved item name and price.');
        redirect('admin/menu.php');
    }

    if ($action === 'save') {
        $name = trim($_POST['name'] ?? '');
        $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
        $description = trim($_POST['description'] ?? '');
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $available = isset($_POST['is_available']) ? 1 : 0;
        $imagePath = $editItem['image_path'] ?? null;

        if ($name === '' || strlen($name) > 150 || !$categoryId || $price === false || $price < 0
            || strlen($description) > 5000) {
            $error = 'Enter a name, category, valid non-negative price and description.';
        } elseif (!is_dir(dirname(__DIR__) . '/uploads/menu') && !mkdir(dirname(__DIR__) . '/uploads/menu', 0755, true)) {
            $error = 'Could not create the menu image upload folder.';
        } else {
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = $_FILES['image'];
                if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 2 * 1024 * 1024) {
                    $error = 'The image upload failed or is larger than 2 MB.';
                } else {
                    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $fileInfo->file($upload['tmp_name']);
                    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    if (!isset($extensions[$mimeType])) {
                        $error = 'Choose a JPEG, PNG or WebP image.';
                    } else {
                        $fileName = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
                        $destination = dirname(__DIR__) . '/uploads/menu/' . $fileName;
                        if (!move_uploaded_file($upload['tmp_name'], $destination)) {
                            $error = 'The image could not be saved. Check folder permissions.';
                        } else {
                            $imagePath = 'uploads/menu/' . $fileName;
                        }
                    }
                }
            }

            if ($error === '') {
                $categoryCheck = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = ?');
                $categoryCheck->execute([$categoryId]);
                if (!(int) $categoryCheck->fetchColumn()) {
                    $error = 'Choose an existing category.';
                } else {
                    if ($id) {
                        $statement = $pdo->prepare(
                            'UPDATE menu_items SET category_id = ?, name = ?, description = ?,
                             price = ?, image_path = ?, is_available = ? WHERE id = ?'
                        );
                        $statement->execute([$categoryId, $name, $description ?: null, $price, $imagePath, $available, $id]);
                    } else {
                        $statement = $pdo->prepare(
                            'INSERT INTO menu_items (category_id, name, description, price, image_path, is_available)
                             VALUES (?, ?, ?, ?, ?, ?)'
                        );
                        $statement->execute([$categoryId, $name, $description ?: null, $price, $imagePath, $available]);
                    }
                    flash('Menu item saved.');
                    redirect('admin/menu.php');
                }
            }
        }
    }
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$items = $pdo->query(
    'SELECT menu_items.*, categories.name AS category_name
     FROM menu_items JOIN categories ON categories.id = menu_items.category_id
     ORDER BY menu_items.name'
)->fetchAll();
$pageTitle = 'Menu management';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Food menu management</h1>
        <?php if (!$categories): ?><div class="notice notice-error">Add a category before creating a menu item.</div><?php endif; ?>
        <section class="panel">
            <h2><?= $editItem ? 'Edit food item' : 'Add food item' ?></h2>
            <?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($editItem['id'] ?? '') ?>">
                <div class="form-row">
                    <div><label for="name">Food name</label><input id="name" name="name" maxlength="150" required value="<?= e($_POST['name'] ?? $editItem['name'] ?? '') ?>"></div>
                    <div><label for="category">Category</label><select id="category" name="category_id" required>
                        <option value="">Choose category</option>
                        <?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) ($editItem['category_id'] ?? $_POST['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?>
                    </select></div>
                </div>
                <div class="form-row">
                    <div><label for="price">Price</label><input id="price" name="price" type="number" min="0" step="0.01" required value="<?= e($_POST['price'] ?? $editItem['price'] ?? '') ?>"></div>
                    <div><label for="image">Food image (JPEG, PNG or WebP; maximum 2 MB)</label><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"></div>
                </div>
                <label for="description">Description</label><textarea id="description" name="description"><?= e($_POST['description'] ?? $editItem['description'] ?? '') ?></textarea>
                <label><input type="checkbox" name="is_available" value="1" <?= !isset($editItem) || (int) ($editItem['is_available'] ?? 1) === 1 ? 'checked' : '' ?>> Available to order</label>
                <p><button type="submit" <?= !$categories ? 'disabled' : '' ?>>Save food item</button>
                <?php if ($editItem): ?><a class="button button-secondary" href="<?= e(url('admin/menu.php')) ?>">Cancel edit</a><?php endif; ?></p>
            </form>
        </section>
        <h2>Food items</h2>
        <div class="table-wrap"><table><thead><tr><th>Food</th><th>Category</th><th>Price</th><th>Availability</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($items as $item): ?><tr>
            <td><?= e($item['name']) ?><?php if ($item['image_path']): ?><br><img class="image-preview" src="<?= e(url($item['image_path'])) ?>" alt=""><?php endif; ?></td>
            <td><?= e($item['category_name']) ?></td><td>$<?= e(money($item['price'])) ?></td>
            <td><?= (int) $item['is_available'] ? 'Available' : 'Unavailable' ?></td>
            <td><div class="table-actions">
                <a class="button button-small button-secondary" href="<?= e(url('admin/menu.php?edit=' . $item['id'])) ?>">Edit</a>
                <form method="post" data-confirm="Delete this food item? Past orders retain their saved item details.">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <button class="button-small button-danger" type="submit">Delete</button>
                </form>
            </div></td>
        </tr><?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="5">No menu items yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
