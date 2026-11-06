<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['waiter']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
    if ($orderId) {
        $statement = $pdo->prepare(
            "SELECT orders.total_amount, orders.status, payments.status AS payment_status
             FROM orders LEFT JOIN payments ON payments.order_id = orders.id WHERE orders.id = ?"
        );
        $statement->execute([$orderId]);
        $order = $statement->fetch();
        if ($order && $order['status'] !== 'cancelled' && $order['payment_status'] !== 'paid') {
            $statement = $pdo->prepare(
                'INSERT INTO payments (order_id, amount, method, status, recorded_by)
                 VALUES (?, ?, "cash", "paid", ?)
                 ON DUPLICATE KEY UPDATE amount = VALUES(amount), method = "cash",
                     status = "paid", recorded_by = VALUES(recorded_by)'
            );
            $statement->execute([$orderId, $order['total_amount'], current_user()['id']]);
            flash('Cash payment recorded.');
        } else {
            flash('This order cannot be recorded as paid.', 'error');
        }
    }
    redirect('waiter/payments.php');
}
$payments = $pdo->query(
    'SELECT orders.id AS order_id, orders.order_number, orders.total_amount,
            customers.full_name, payments.method, payments.status AS payment_status
     FROM orders JOIN customers ON customers.id = orders.customer_id
     LEFT JOIN payments ON payments.order_id = orders.id
     ORDER BY orders.created_at DESC LIMIT 200'
)->fetchAll();
$pageTitle = 'Cash payments';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Cash payments</h1>
        <div class="table-wrap"><table><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($payments as $payment): ?><tr>
            <td><a href="<?= e(url('receipt.php?id=' . $payment['order_id'])) ?>">#<?= e($payment['order_number']) ?></a></td><td><?= e($payment['full_name']) ?></td>
            <td>$<?= e(money($payment['total_amount'])) ?></td><td><?= e(order_status_label($payment['payment_status'] ?? 'unpaid')) ?></td>
            <td><?php if ($payment['payment_status'] !== 'paid'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="order_id" value="<?= (int) $payment['order_id'] ?>"><button class="button-small" type="submit">Record cash</button></form><?php else: ?>Paid<?php endif; ?></td>
        </tr><?php endforeach; ?>
        <?php if (!$payments): ?><tr><td colspan="5">No orders yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
