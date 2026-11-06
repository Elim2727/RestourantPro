<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$customerCount = (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$menuCount = (int) $pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn();
$orderCount = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid'")->fetchColumn();
$recentOrders = $pdo->query(
    'SELECT orders.id, orders.order_number, orders.status, orders.total_amount,
            orders.created_at, customers.full_name
     FROM orders JOIN customers ON customers.id = orders.customer_id
     ORDER BY orders.created_at DESC LIMIT 8'
)->fetchAll();

$pageTitle = 'Admin dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Administrator dashboard</h1>
        <div class="stats">
            <div class="stat-card">Customers<strong><?= $customerCount ?></strong></div>
            <div class="stat-card">Menu items<strong><?= $menuCount ?></strong></div>
            <div class="stat-card">Orders<strong><?= $orderCount ?></strong></div>
            <div class="stat-card">Paid sales<strong>$<?= e(money($sales)) ?></strong></div>
        </div>
        <h2>Recent orders</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th><th>Placed</th></tr></thead>
            <tbody>
            <?php foreach ($recentOrders as $order): ?>
                <tr>
                    <td><a href="<?= e(url('admin/orders.php?id=' . $order['id'])) ?>">#<?= e($order['order_number']) ?></a></td>
                    <td><?= e($order['full_name']) ?></td>
                    <td><span class="badge"><?= e(order_status_label($order['status'])) ?></span></td>
                    <td>$<?= e(money($order['total_amount'])) ?></td>
                    <td><?= e($order['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recentOrders): ?><tr><td colspan="5">No orders have been placed yet.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
