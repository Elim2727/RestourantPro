<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['customer']);

$statement = $pdo->prepare(
    'SELECT customers.id FROM customers WHERE customers.user_id = ?'
);
$statement->execute([current_user()['id']]);
$customerId = $statement->fetchColumn();
$orderCount = 0;
$latestOrders = [];
if ($customerId) {
    $statement = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE customer_id = ?');
    $statement->execute([$customerId]);
    $orderCount = (int) $statement->fetchColumn();
    $statement = $pdo->prepare('SELECT id, order_number, status, total_amount, created_at FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5');
    $statement->execute([$customerId]);
    $latestOrders = $statement->fetchAll();
}
$pageTitle = 'Customer dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Welcome, <?= e(current_user()['username']) ?></h1>
        <?php if (!$customerId): ?><div class="notice notice-error">Your customer profile is missing. Please contact restaurant staff.</div><?php else: ?>
            <div class="stats"><div class="stat-card">Your orders<strong><?= $orderCount ?></strong></div></div>
            <p><a class="button" href="<?= e(url('customer/menu.php')) ?>">Browse food menu</a></p>
            <h2>Recent orders</h2>
            <div class="table-wrap"><table><thead><tr><th>Order</th><th>Status</th><th>Total</th><th>Date</th></tr></thead><tbody>
            <?php foreach ($latestOrders as $order): ?><tr><td><a href="<?= e(url('customer/my_orders.php?id=' . $order['id'])) ?>">#<?= e($order['order_number']) ?></a></td><td><?= e(order_status_label($order['status'])) ?></td><td>$<?= e(money($order['total_amount'])) ?></td><td><?= e($order['created_at']) ?></td></tr><?php endforeach; ?>
            <?php if (!$latestOrders): ?><tr><td colspan="4">You have not placed an order yet.</td></tr><?php endif; ?>
            </tbody></table></div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
