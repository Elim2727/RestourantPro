<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$statuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';
    if ($id && in_array($status, $statuses, true)) {
        $statement = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $statement->execute([$status, $id]);
        flash('Order status updated.');
    } else {
        flash('Choose a valid order status.', 'error');
    }
    redirect('admin/orders.php' . ($id ? '?id=' . $id : ''));
}

$selectedOrder = null;
$orderItems = [];
if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $statement = $pdo->prepare(
            'SELECT orders.*, customers.full_name, customers.email, users.username AS staff_name
             FROM orders JOIN customers ON customers.id = orders.customer_id
             LEFT JOIN users ON users.id = orders.created_by WHERE orders.id = ?'
        );
        $statement->execute([$id]);
        $selectedOrder = $statement->fetch();
        if ($selectedOrder) {
            $statement = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
            $statement->execute([$id]);
            $orderItems = $statement->fetchAll();
        }
    }
}
$orders = $pdo->query(
    'SELECT orders.id, orders.order_number, orders.status, orders.order_type,
            orders.total_amount, orders.created_at, customers.full_name
     FROM orders JOIN customers ON customers.id = orders.customer_id
     ORDER BY orders.created_at DESC LIMIT 100'
)->fetchAll();
$pageTitle = 'Order management';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Order management</h1>
        <?php if ($selectedOrder): ?>
            <section class="panel">
                <h2>Order #<?= e($selectedOrder['order_number']) ?></h2>
                <p>Customer: <?= e($selectedOrder['full_name']) ?> · <?= e($selectedOrder['order_type']) ?> · <?= e($selectedOrder['created_at']) ?></p>
                <p>Notes: <?= e($selectedOrder['notes'] ?: 'None') ?></p>
                <div class="table-wrap"><table><thead><tr><th>Item</th><th>Quantity</th><th>Price</th><th>Line total</th></tr></thead><tbody>
                <?php foreach ($orderItems as $item): ?><tr><td><?= e($item['item_name']) ?></td><td><?= (int) $item['quantity'] ?></td><td>$<?= e(money($item['unit_price'])) ?></td><td>$<?= e(money($item['line_total'])) ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
                <p><strong>Total: $<?= e(money($selectedOrder['total_amount'])) ?></strong></p>
                <form method="post" class="table-actions">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $selectedOrder['id'] ?>">
                    <label for="status">Status</label><select id="status" name="status"><?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $selectedOrder['status'] === $status ? 'selected' : '' ?>><?= e(order_status_label($status)) ?></option><?php endforeach; ?></select>
                    <button type="submit">Update status</button>
                </form>
                <p><a class="button button-secondary" href="<?= e(url('receipt.php?id=' . $selectedOrder['id'])) ?>">View receipt</a></p>
            </section>
        <?php elseif (isset($_GET['id'])): ?><div class="notice notice-error">Order not found.</div><?php endif; ?>
        <h2>All orders</h2>
        <div class="table-wrap"><table><thead><tr><th>Order</th><th>Customer</th><th>Type</th><th>Status</th><th>Total</th><th>Date</th><th></th></tr></thead><tbody>
        <?php foreach ($orders as $order): ?><tr>
            <td>#<?= e($order['order_number']) ?></td><td><?= e($order['full_name']) ?></td><td><?= e($order['order_type']) ?></td><td><?= e(order_status_label($order['status'])) ?></td>
            <td>$<?= e(money($order['total_amount'])) ?></td><td><?= e($order['created_at']) ?></td><td><a href="<?= e(url('admin/orders.php?id=' . $order['id'])) ?>">Details</a></td>
        </tr><?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="7">No orders yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
