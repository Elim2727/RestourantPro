<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['customer']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $menuId = filter_var($_POST['menu_item_id'] ?? null, FILTER_VALIDATE_INT);
    $statement = $pdo->prepare('SELECT id FROM menu_items WHERE id = ? AND is_available = 1');
    $statement->execute([$menuId]);
    if ($menuId && $statement->fetchColumn()) {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $_SESSION['cart'][$menuId] = min(99, (int) ($_SESSION['cart'][$menuId] ?? 0) + 1);
        flash('Food added to your cart.');
    } else {
        flash('That food item is no longer available.', 'error');
    }
    redirect('customer/menu.php');
}

$items = $pdo->query(
    'SELECT menu_items.*, categories.name AS category_name FROM menu_items
     JOIN categories ON categories.id = menu_items.category_id
     WHERE menu_items.is_available = 1 ORDER BY categories.name, menu_items.name'
)->fetchAll();
$pageTitle = 'Food menu';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Food menu</h1>
        <div class="cards">
        <?php foreach ($items as $item): ?><article class="card">
            <?php if ($item['image_path']): ?><img class="food-image" src="<?= e(url($item['image_path'])) ?>" alt="<?= e($item['name']) ?>"><?php else: ?><div class="food-placeholder" aria-hidden="true">&#127835;</div><?php endif; ?>
            <h2><?= e($item['name']) ?></h2><p class="muted"><?= e($item['category_name']) ?></p><p><?= e($item['description']) ?></p><p class="food-price">$<?= e(money($item['price'])) ?></p>
            <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="menu_item_id" value="<?= (int) $item['id'] ?>"><button type="submit">Add to cart</button></form>
        </article><?php endforeach; ?>
        <?php if (!$items): ?><p class="panel">There are no available menu items right now.</p><?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
