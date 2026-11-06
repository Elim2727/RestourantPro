<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['waiter']);

$error = '';
$waiterTransitions = [
    'pending' => ['confirmed', 'cancelled'],
    'confirmed' => ['cancelled'],
    'ready' => ['completed'],
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (($_POST['action'] ?? '') === 'status') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? '';
        $statement = $pdo->prepare('SELECT status FROM orders WHERE id = ?');
        $statement->execute([$id]);
        $oldStatus = $statement->fetchColumn();
        if ($id && $oldStatus && in_array($status, $waiterTransitions[$oldStatus] ?? [], true)) {
            $statement = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $statement->execute([$status, $id]);
            flash('Order status updated.');
            redirect('waiter/orders.php');
        }
        $error = 'That order status change is not allowed.';
    } elseif (($_POST['action'] ?? '') === 'create') {
        $customerId = filter_var($_POST['customer_id'] ?? null, FILTER_VALIDATE_INT);
        $orderType = $_POST['order_type'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        $quantities = $_POST['quantity'] ?? [];
        $allowedTypes = ['dine_in', 'takeaway', 'delivery'];
        if (!$customerId || !in_array($orderType, $allowedTypes, true) || strlen($notes) > 1000 || !is_array($quantities)) {
            $error = 'Enter a valid customer, order type and notes.';
        } else {
            $customerCheck = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE id = ?');
            $customerCheck->execute([$customerId]);
            $items = [];
            $total = 0;
            foreach ($quantities as $menuId => $quantityValue) {
                $quantity = filter_var($quantityValue, FILTER_VALIDATE_INT);
                $menuId = filter_var($menuId, FILTER_VALIDATE_INT);
                if (!$quantity || !$menuId) {
                    continue;
                }
                if ($quantity < 1 || $quantity > 99) {
                    $error = 'Each item quantity must be between 1 and 99.';
                    break;
                }
                $statement = $pdo->prepare('SELECT id, name, price FROM menu_items WHERE id = ? AND is_available = 1');
                $statement->execute([$menuId]);
                $item = $statement->fetch();
                if ($item) {
                    $item['quantity'] = $quantity;
                    $item['line_total'] = round((float) $item['price'] * $quantity, 2);
                    $items[] = $item;
                    $total += $item['line_total'];
                }
            }
            if (!(int) $customerCheck->fetchColumn()) {
                $error = 'Choose an existing customer.';
            } elseif (!$items && $error === '') {
                $error = 'Choose a quantity for at least one available menu item.';
            }
            if ($error === '') {
                $pdo->beginTransaction();
                $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $statement = $pdo->prepare(
                    'INSERT INTO orders (order_number, customer_id, created_by, order_type, status, subtotal, total_amount, notes)
                     VALUES (?, ?, ?, ?, "pending", ?, ?, ?)'
                );
                $statement->execute([$orderNumber, $customerId, current_user()['id'], $orderType, $total, $total, $notes ?: null]);
                $orderId = $pdo->lastInsertId();
                $statement = $pdo->prepare(
                    'INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, unit_price, line_total)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                foreach ($items as $item) {
                    $statement->execute([$orderId, $item['id'], $item['name'], $item['quantity'], $item['price'], $item['line_total']]);
                }
                $statement = $pdo->prepare(
                    'INSERT INTO payments (order_id, amount, method, status)
                     VALUES (?, ?, "cash", "pending")'
                );
                $statement->execute([$orderId, $total]);
                $pdo->commit();
                flash('Order created.');
                redirect('waiter/orders.php?id=' . $orderId);
            }
        }
    }
}

$customers = $pdo->query('SELECT id, full_name FROM customers ORDER BY full_name')->fetchAll();
$menuItems = $pdo->query('SELECT id, name, price FROM menu_items WHERE is_available = 1 ORDER BY name')->fetchAll();
$orders = $pdo->query(
    'SELECT orders.*, customers.full_name FROM orders
     JOIN customers ON customers.id = orders.customer_id ORDER BY orders.created_at DESC LIMIT 100'
)->fetchAll();
$selectedOrder = null;
$orderItems = [];
if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $statement = $pdo->prepare('SELECT orders.*, customers.full_name FROM orders JOIN customers ON customers.id = orders.customer_id WHERE orders.id = ?');
        $statement->execute([$id]);
        $selectedOrder = $statement->fetch();
        if ($selectedOrder) {
            $statement = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
            $statement->execute([$id]);
            $orderItems = $statement->fetchAll();
        }
    }
}
$pageTitle = 'Waiter orders';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Orders</h1>
        <?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
        <section class="panel">
            <h2>Create a customer order</h2>
            <?php if (!$customers): ?><p>Register a customer before creating an order.</p><?php endif; ?>
            <?php if (!$menuItems): ?><p>Add available menu items before creating an order.</p><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create">
                <div class="form-row">
                    <div><label for="customer_id">Customer</label><select id="customer_id" name="customer_id" required><option value="">Choose customer</option><?php foreach ($customers as $customer): ?><option value="<?= (int) $customer['id'] ?>"><?= e($customer['full_name']) ?></option><?php endforeach; ?></select></div>
                    <div><label for="order_type">Order type</label><select id="order_type" name="order_type"><option value="dine_in">Dine in</option><option value="takeaway">Takeaway</option><option value="delivery">Delivery</option></select></div>
                </div>
                <h3>Choose food and quantity</h3>
                <div class="table-wrap"><table><thead><tr><th>Food</th><th>Price</th><th>Quantity</th></tr></thead><tbody>
                <?php foreach ($menuItems as $item): ?><tr><td><?= e($item['name']) ?></td><td>$<?= e(money($item['price'])) ?></td><td><input class="quantity-input" type="number" name="quantity[<?= (int) $item['id'] ?>]" min="0" max="99" value="0"></td></tr><?php endforeach; ?>
                </tbody></table></div>
                <label for="notes">Order notes</label><textarea id="notes" name="notes" maxlength="1000"></textarea>
                <p><button type="submit" <?= !$customers || !$menuItems ? 'disabled' : '' ?>>Create order</button></p>
            </form>
        </section>
        <?php if ($selectedOrder): ?><section class="panel">
            <h2>Order #<?= e($selectedOrder['order_number']) ?> for <?= e($selectedOrder['full_name']) ?></h2>
            <ul><?php foreach ($orderItems as $item): ?><li><?= (int) $item['quantity'] ?> × <?= e($item['item_name']) ?> — $<?= e(money($item['line_total'])) ?></li><?php endforeach; ?></ul>
            <p>Total: $<?= e(money($selectedOrder['total_amount'])) ?> · Status: <?= e(order_status_label($selectedOrder['status'])) ?></p>
            <a class="button button-secondary" href="<?= e(url('receipt.php?id=' . $selectedOrder['id'])) ?>">Receipt</a>
        </section><?php endif; ?>
        <h2>Recent orders</h2>
        <div class="table-wrap"><table><thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($orders as $order): ?><tr>
            <td>#<?= e($order['order_number']) ?></td><td><?= e($order['full_name']) ?></td><td><?= e(order_status_label($order['status'])) ?></td><td>$<?= e(money($order['total_amount'])) ?></td>
            <td><div class="table-actions"><a href="<?= e(url('waiter/orders.php?id=' . $order['id'])) ?>">Details</a>
                <?php if (isset($waiterTransitions[$order['status']])): ?><form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                    <select name="status"><?php foreach ($waiterTransitions[$order['status']] as $nextStatus): ?><option value="<?= e($nextStatus) ?>"><?= e(order_status_label($nextStatus)) ?></option><?php endforeach; ?></select><button class="button-small" type="submit">Update</button>
                </form><?php endif; ?>
            </div></td>
        </tr><?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="5">No orders yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
