<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['customer']);

$statement = $pdo->prepare('SELECT id FROM customers WHERE user_id = ?');
$statement->execute([current_user()['id']]);
$customerId = $statement->fetchColumn();
$selectedOrder = null;
$orderItems = [];
$orders = [];
if ($customerId) {
    $statement = $pdo->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
    $statement->execute([$customerId]);
    $orders = $statement->fetchAll();
    if (isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if ($id) {
            $statement = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND customer_id = ?');
            $statement->execute([$id, $customerId]);
            $selectedOrder = $statement->fetch();
            if ($selectedOrder) {
                $statement = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
                $statement->execute([$id]);
                $orderItems = $statement->fetchAll();
            }
        }
    }
}
$pageTitle = 'My orders';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>My orders</h1>
        <?php if ($selectedOrder): ?><section class="panel">
            <h2>Order #<?= e($selectedOrder['order_number']) ?></h2>
            <p>Placed <?= e($selectedOrder['created_at']) ?> · <?= e(order_status_label($selectedOrder['status'])) ?></p>
            <div class="table-wrap"><table><thead><tr><th>Food</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead><tbody>
            <?php foreach ($orderItems as $item): ?><tr><td><?= e($item['item_name']) ?></td><td><?= (int) $item['quantity'] ?></td><td>$<?= e(money($item['unit_price'])) ?></td><td>$<?= e(money($item['line_total'])) ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
            <p><strong>Total: $<?= e(money($selectedOrder['total_amount'])) ?></strong></p>
            <p><a class="button button-secondary" href="<?= e(url('receipt.php?id=' . $selectedOrder['id'])) ?>">View receipt</a></p>
        </section><?php elseif (isset($_GET['id'])): ?><div class="notice notice-error">Order not found.</div><?php endif; ?>
        <h2>Order history</h2>
        <div class="table-wrap"><table><thead><tr><th>Order</th><th>Type</th><th>Status</th><th>Total</th><th>Date</th><th>Details</th></tr></thead><tbody>
        <?php foreach ($orders as $order): ?><tr><td>#<?= e($order['order_number']) ?></td><td><?= e($order['order_type']) ?></td><td><?= e(order_status_label($order['status'])) ?></td><td>$<?= e(money($order['total_amount'])) ?></td><td><?= e($order['created_at']) ?></td><td><a href="<?= e(url('customer/my_orders.php?id=' . $order['id'])) ?>">View</a></td></tr><?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="6">You have not placed an order yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
