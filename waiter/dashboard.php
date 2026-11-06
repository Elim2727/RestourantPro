<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['waiter']);

$todayOrders = $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()')->fetchColumn();
$customers = $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$orders = $pdo->query(
    'SELECT orders.id, orders.order_number, orders.status, orders.total_amount,
            orders.created_at, customers.full_name
     FROM orders JOIN customers ON customers.id = orders.customer_id
     ORDER BY orders.created_at DESC LIMIT 10'
)->fetchAll();
$pageTitle = 'Waiter dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Waiter dashboard</h1>
        <div class="stats"><div class="stat-card">Orders today<strong><?= (int) $todayOrders ?></strong></div><div class="stat-card">Customers<strong><?= (int) $customers ?></strong></div></div>
        <p><a class="button" href="<?= e(url('waiter/orders.php')) ?>">Create an order</a> <a class="button button-secondary" href="<?= e(url('waiter/customers.php')) ?>">Register a customer</a></p>
        <h2>Latest orders</h2>
        <div class="table-wrap"><table><thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th><th>Details</th></tr></thead><tbody>
        <?php foreach ($orders as $order): ?><tr><td>#<?= e($order['order_number']) ?></td><td><?= e($order['full_name']) ?></td><td><?= e(order_status_label($order['status'])) ?></td><td>$<?= e(money($order['total_amount'])) ?></td><td><a href="<?= e(url('waiter/orders.php?id=' . $order['id'])) ?>">Open</a></td></tr><?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="5">No orders yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
