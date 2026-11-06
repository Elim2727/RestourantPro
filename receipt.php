<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$orderId) {
    http_response_code(400);
    exit('A valid order number is required.');
}
$statement = $pdo->prepare(
    'SELECT orders.*, customers.full_name, customers.email, payments.method, payments.status AS payment_status
     FROM orders JOIN customers ON customers.id = orders.customer_id
     LEFT JOIN payments ON payments.order_id = orders.id WHERE orders.id = ?'
);
$statement->execute([$orderId]);
$order = $statement->fetch();
if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}
if (current_user()['role'] === 'customer') {
    $statement = $pdo->prepare('SELECT id FROM customers WHERE user_id = ? AND id = ?');
    $statement->execute([current_user()['id'], $order['customer_id']]);
    if (!$statement->fetchColumn()) {
        http_response_code(403);
        exit('You do not have permission to view this receipt.');
    }
} elseif (!in_array(current_user()['role'], ['admin', 'waiter'], true)) {
    http_response_code(403);
    exit('You do not have permission to view this receipt.');
}
$statement = $pdo->prepare('SELECT item_name, quantity, unit_price, line_total FROM order_items WHERE order_id = ?');
$statement->execute([$orderId]);
$items = $statement->fetchAll();
$pageTitle = 'Order receipt';
require __DIR__ . '/includes/header.php';
?>
<section class="receipt">
    <div class="no-print"><button type="button" onclick="window.print()">Print receipt</button></div>
    <h1>RestaurantPro</h1>
    <h2>Receipt for order #<?= e($order['order_number']) ?></h2>
    <p>Customer: <?= e($order['full_name']) ?></p>
    <p>Date: <?= e($order['created_at']) ?> · Order type: <?= e($order['order_type']) ?></p>
    <div class="table-wrap"><table><thead><tr><th>Food</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>
    <?php foreach ($items as $item): ?><tr><td><?= e($item['item_name']) ?></td><td><?= (int) $item['quantity'] ?></td><td>$<?= e(money($item['unit_price'])) ?></td><td>$<?= e(money($item['line_total'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <p><strong>Total: $<?= e(money($order['total_amount'])) ?></strong></p>
    <p>Payment: <?= e(order_status_label($order['payment_status'] ?? 'unpaid')) ?><?= $order['method'] ? ' by ' . e($order['method']) : '' ?></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
