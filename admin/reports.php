<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$dailySales = $pdo->query(
    "SELECT COALESCE(SUM(amount), 0) FROM payments
     WHERE status = 'paid' AND DATE(created_at) = CURDATE()"
)->fetchColumn();
$monthlySales = $pdo->query(
    "SELECT COALESCE(SUM(amount), 0) FROM payments
     WHERE status = 'paid' AND YEAR(created_at) = YEAR(CURDATE())
       AND MONTH(created_at) = MONTH(CURDATE())"
)->fetchColumn();
$totalOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$popularItems = $pdo->query(
    'SELECT order_items.item_name, SUM(order_items.quantity) AS quantity_sold,
            SUM(order_items.line_total) AS sales_total
     FROM order_items JOIN orders ON orders.id = order_items.order_id
     WHERE orders.status != "cancelled"
     GROUP BY order_items.item_name ORDER BY quantity_sold DESC LIMIT 10'
)->fetchAll();
$pageTitle = 'Sales reports';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Simple sales reports</h1>
        <p class="muted">Sales are based on payments marked as paid. Order totals include all orders except cancelled ones.</p>
        <div class="stats">
            <div class="stat-card">Paid sales today<strong>$<?= e(money($dailySales)) ?></strong></div>
            <div class="stat-card">Paid sales this month<strong>$<?= e(money($monthlySales)) ?></strong></div>
            <div class="stat-card">Non-cancelled orders<strong><?= $totalOrders ?></strong></div>
        </div>
        <h2>Popular food items</h2>
        <div class="table-wrap"><table><thead><tr><th>Food</th><th>Quantity sold</th><th>Sales value</th></tr></thead><tbody>
        <?php foreach ($popularItems as $item): ?><tr><td><?= e($item['item_name']) ?></td><td><?= (int) $item['quantity_sold'] ?></td><td>$<?= e(money($item['sales_total'])) ?></td></tr><?php endforeach; ?>
        <?php if (!$popularItems): ?><tr><td colspan="3">No order items to report yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
