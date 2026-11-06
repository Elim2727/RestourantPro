<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$error = '';
$editCustomer = null;
if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId) {
        $statement = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
        $statement->execute([$editId]);
        $editCustomer = $statement->fetch();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($action === 'delete' && $id) {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE customer_id = ?');
        $statement->execute([$id]);
        if ((int) $statement->fetchColumn() > 0) {
            $error = 'This customer has order history and cannot be deleted.';
        } else {
            $pdo->beginTransaction();
            $statement = $pdo->prepare('SELECT user_id FROM customers WHERE id = ?');
            $statement->execute([$id]);
            $userId = $statement->fetchColumn();
            $statement = $pdo->prepare('DELETE FROM customers WHERE id = ?');
            $statement->execute([$id]);
            if ($userId) {
                $statement = $pdo->prepare('DELETE FROM users WHERE id = ?');
                $statement->execute([$userId]);
            }
            $pdo->commit();
            flash('Customer deleted.');
            redirect('admin/customers.php');
        }
    } elseif ($action === 'save') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($name === '' || strlen($name) > 120 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
            || strlen($email) > 254 || strlen($phone) > 30 || strlen($address) > 255) {
            $error = 'Enter a name and valid contact details.';
        } else {
            if ($id) {
                $statement = $pdo->prepare('UPDATE customers SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?');
                $statement->execute([$name, $email ?: null, $phone ?: null, $address ?: null, $id]);
            } else {
                $statement = $pdo->prepare('INSERT INTO customers (full_name, email, phone, address) VALUES (?, ?, ?, ?)');
                $statement->execute([$name, $email ?: null, $phone ?: null, $address ?: null]);
            }
            flash('Customer saved.');
            redirect('admin/customers.php');
        }
    }
}

$customers = $pdo->query(
    'SELECT customers.*, COUNT(orders.id) AS order_count
     FROM customers LEFT JOIN orders ON orders.customer_id = customers.id
     GROUP BY customers.id ORDER BY customers.full_name'
)->fetchAll();
$pageTitle = 'Customer management';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Customer management</h1>
        <section class="panel">
            <h2><?= $editCustomer ? 'Edit customer' : 'Add customer' ?></h2>
            <?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= e($editCustomer['id'] ?? '') ?>">
                <label for="full_name">Full name</label><input id="full_name" name="full_name" maxlength="120" required value="<?= e($_POST['full_name'] ?? $editCustomer['full_name'] ?? '') ?>">
                <div class="form-row">
                    <div><label for="email">Email</label><input id="email" name="email" type="email" maxlength="254" value="<?= e($_POST['email'] ?? $editCustomer['email'] ?? '') ?>"></div>
                    <div><label for="phone">Phone</label><input id="phone" name="phone" maxlength="30" value="<?= e($_POST['phone'] ?? $editCustomer['phone'] ?? '') ?>"></div>
                </div>
                <label for="address">Address</label><input id="address" name="address" maxlength="255" value="<?= e($_POST['address'] ?? $editCustomer['address'] ?? '') ?>">
                <p><button type="submit">Save customer</button>
                <?php if ($editCustomer): ?><a class="button button-secondary" href="<?= e(url('admin/customers.php')) ?>">Cancel</a><?php endif; ?></p>
            </form>
        </section>
        <h2>Customers</h2>
        <div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($customers as $customer): ?><tr>
            <td><?= e($customer['full_name']) ?></td><td><?= e($customer['email']) ?></td><td><?= e($customer['phone']) ?></td><td><?= (int) $customer['order_count'] ?></td>
            <td><div class="table-actions">
                <a class="button button-small button-secondary" href="<?= e(url('admin/customers.php?edit=' . $customer['id'])) ?>">Edit</a>
                <?php if (!(int) $customer['order_count']): ?><form method="post" data-confirm="Delete this customer and their login account, if any?">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $customer['id'] ?>">
                    <button class="button-small button-danger" type="submit">Delete</button>
                </form><?php endif; ?>
            </div></td>
        </tr><?php endforeach; ?>
        <?php if (!$customers): ?><tr><td colspan="5">No customers registered yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
