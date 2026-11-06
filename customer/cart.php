<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['customer']);

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'remove') {
        $id = filter_var($_POST['menu_item_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id) {
            unset($_SESSION['cart'][$id]);
        }
        flash('Food removed from cart.');
        redirect('customer/cart.php');
    }
    if ($action === 'update') {
        foreach (($_POST['quantity'] ?? []) as $id => $quantityValue) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            $quantity = filter_var($quantityValue, FILTER_VALIDATE_INT);
            if (!$id || $quantity === false || $quantity < 0 || $quantity > 99) {
                $error = 'Choose a quantity between 0 and 99.';
                break;
            }
            if ($quantity === 0) {
                unset($_SESSION['cart'][$id]);
            } else {
                $_SESSION['cart'][$id] = $quantity;
            }
        }
        if ($error === '') {
            flash('Cart quantities updated.');
            redirect('customer/cart.php');
        }
    }
    if ($action === 'checkout') {
        $orderType = $_POST['order_type'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        if (!in_array($orderType, ['dine_in', 'takeaway', 'delivery'], true) || strlen($notes) > 1000 || !$_SESSION['cart']) {
            $error = 'Add food to your cart and choose a valid order type.';
        } else {
            $statement = $pdo->prepare('SELECT id FROM customers WHERE user_id = ?');
            $statement->execute([current_user()['id']]);
            $customerId = $statement->fetchColumn();
            if (!$customerId) {
                $error = 'Your customer profile is missing. Please contact restaurant staff.';
            } else {
                $items = [];
                $total = 0;
                foreach ($_SESSION['cart'] as $menuId => $quantity) {
                    $statement = $pdo->prepare('SELECT id, name, price FROM menu_items WHERE id = ? AND is_available = 1');
                    $statement->execute([$menuId]);
                    $item = $statement->fetch();
                    if ($item) {
                        $item['quantity'] = (int) $quantity;
                        $item['line_total'] = round((float) $item['price'] * $item['quantity'], 2);
                        $items[] = $item;
                        $total += $item['line_total'];
                    }
                }
                if (!$items) {
                    $error = 'The items in your cart are no longer available.';
                } else {
                    $pdo->beginTransaction();
                    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                    $statement = $pdo->prepare(
                        'INSERT INTO orders (order_number, customer_id, order_type, status, subtotal, total_amount, notes)
                         VALUES (?, ?, ?, "pending", ?, ?, ?)'
                    );
                    $statement->execute([$orderNumber, $customerId, $orderType, $total, $total, $notes ?: null]);
                    $orderId = $pdo->lastInsertId();
                    $statement = $pdo->prepare(
                        'INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, unit_price, line_total)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    foreach ($items as $item) {
                        $statement->execute([$orderId, $item['id'], $item['name'], $item['quantity'], $item['price'], $item['line_total']]);
                    }
                    $statement = $pdo->prepare('INSERT INTO payments (order_id, amount, method, status) VALUES (?, ?, "cash", "pending")');
                    $statement->execute([$orderId, $total]);
                    $pdo->commit();
                    $_SESSION['cart'] = [];
                    flash('Your order has been placed. Payment is pending until restaurant staff records it.');
                    redirect('customer/my_orders.php?id=' . $orderId);
                }
            }
        }
    }
}

$items = [];
$total = 0;
if ($_SESSION['cart']) {
    $statement = $pdo->prepare(
        'SELECT id, name, price, is_available FROM menu_items WHERE id = ?'
    );
    foreach ($_SESSION['cart'] as $id => $quantity) {
        $statement->execute([$id]);
        $item = $statement->fetch();
        if ($item) {
            $item['quantity'] = (int) $quantity;
            $item['line_total'] = round((float) $item['price'] * $item['quantity'], 2);
            $items[] = $item;
            $total += $item['line_total'];
        } else {
            unset($_SESSION['cart'][$id]);
        }
    }
}
$pageTitle = 'Shopping cart';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Your shopping cart</h1>
        <?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
        <?php if (!$items): ?><p class="panel">Your cart is empty. <a href="<?= e(url('customer/menu.php')) ?>">Browse the food menu</a>.</p><?php else: ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update">
                <div class="table-wrap"><table><thead><tr><th>Food</th><th>Price</th><th>Quantity</th><th>Total</th><th></th></tr></thead><tbody>
                <?php foreach ($items as $item): ?><tr>
                    <td><?= e($item['name']) ?><?= !(int) $item['is_available'] ? ' (no longer available)' : '' ?></td><td>$<?= e(money($item['price'])) ?></td>
                    <td><input class="quantity-input" type="number" min="0" max="99" name="quantity[<?= (int) $item['id'] ?>]" value="<?= (int) $item['quantity'] ?>"></td>
                    <td>$<?= e(money($item['line_total'])) ?></td>
                    <td><button class="button-small button-danger" type="submit" form="remove-<?= (int) $item['id'] ?>">Remove</button></td>
                </tr><?php endforeach; ?>
                </tbody></table></div>
                <p class="text-right"><strong>Cart total: $<?= e(money($total)) ?></strong></p><button type="submit">Update quantities</button>
            </form>
            <?php foreach ($items as $item): ?><form id="remove-<?= (int) $item['id'] ?>" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="menu_item_id" value="<?= (int) $item['id'] ?>"></form><?php endforeach; ?>
            <section class="panel">
                <h2>Place your order</h2>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="checkout">
                    <label for="order_type">Order type</label><select id="order_type" name="order_type"><option value="dine_in">Dine in</option><option value="takeaway">Takeaway</option><option value="delivery">Delivery</option></select>
                    <label for="notes">Notes or delivery details</label><textarea id="notes" name="notes" maxlength="1000"></textarea>
                    <p><button type="submit">Confirm order</button></p>
                </form>
            </section>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
