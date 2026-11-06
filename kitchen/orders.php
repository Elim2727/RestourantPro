<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['kitchen']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';
    $allowed = ['preparing', 'ready'];
    $statement = $pdo->prepare('SELECT status FROM orders WHERE id = ?');
    $statement->execute([$id]);
    $oldStatus = $statement->fetchColumn();
    $valid = ($oldStatus === 'pending' && $status === 'preparing')
        || ($oldStatus === 'confirmed' && $status === 'preparing')
        || ($oldStatus === 'preparing' && $status === 'ready');
    if ($id && in_array($status, $allowed, true) && $valid) {
        $statement = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $statement->execute([$status, $id]);
        flash('Kitchen order status updated.');
    } else {
        flash('That order status change is not allowed.', 'error');
    }
    redirect('kitchen/orders.php');
}

$orders = $pdo->query(
    "SELECT orders.id, orders.order_number, orders.status, orders.order_type, orders.notes, orders.created_at,
            customers.full_name
     FROM orders JOIN customers ON customers.id = orders.customer_id
     WHERE orders.status IN ('pending', 'confirmed', 'preparing')
     ORDER BY orders.created_at ASC"
)->fetchAll();
$itemQuery = $pdo->prepare('SELECT item_name, quantity FROM order_items WHERE order_id = ? ORDER BY id');
$pageTitle = 'Kitchen orders';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Kitchen order board</h1>
        <p>Work through the oldest orders first, and mark food as ready when cooking is finished.</p>
        <div class="cards">
        <?php foreach ($orders as $order): ?>
            <?php $itemQuery->execute([$order['id']]); $items = $itemQuery->fetchAll(); ?>
            <article class="card">
                <h2>Order #<?= e($order['order_number']) ?></h2>
                <p><?= e($order['created_at']) ?> · <?= e($order['order_type']) ?></p>
                <p>Customer: <?= e($order['full_name']) ?></p>
                <ul><?php foreach ($items as $item): ?><li><?= (int) $item['quantity'] ?> × <?= e($item['item_name']) ?></li><?php endforeach; ?></ul>
                <?php if ($order['notes']): ?><p>Notes: <?= e($order['notes']) ?></p><?php endif; ?>
                <p><span class="badge"><?= e(order_status_label($order['status'])) ?></span></p>
                <?php if (in_array($order['status'], ['pending', 'confirmed'], true)): ?>
                    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="status" value="preparing"><button type="submit">Start preparing</button></form>
                <?php elseif ($order['status'] === 'preparing'): ?>
                    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="status" value="ready"><button type="submit">Mark ready</button></form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (!$orders): ?><p class="panel">There are no orders waiting for the kitchen.</p><?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
