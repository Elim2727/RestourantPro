<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['waiter']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($name === '' || strlen($name) > 120 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
        || strlen($email) > 254 || strlen($phone) > 30) {
        $error = 'Enter a name and valid contact details.';
    } else {
        $statement = $pdo->prepare('INSERT INTO customers (full_name, email, phone) VALUES (?, ?, ?)');
        $statement->execute([$name, $email ?: null, $phone ?: null]);
        flash('Customer registered.');
        redirect('waiter/customers.php');
    }
}
$customers = $pdo->query('SELECT id, full_name, email, phone FROM customers ORDER BY full_name')->fetchAll();
$pageTitle = 'Register customers';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Register customers</h1>
        <section class="panel">
            <h2>New customer</h2>
            <?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label for="full_name">Full name</label><input id="full_name" name="full_name" maxlength="120" required>
                <div class="form-row"><div><label for="email">Email</label><input id="email" name="email" type="email" maxlength="254"></div><div><label for="phone">Phone</label><input id="phone" name="phone" maxlength="30"></div></div>
                <p><button type="submit">Register customer</button></p>
            </form>
        </section>
        <h2>Customers</h2>
        <div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Phone</th></tr></thead><tbody>
        <?php foreach ($customers as $customer): ?><tr><td><?= e($customer['full_name']) ?></td><td><?= e($customer['email']) ?></td><td><?= e($customer['phone']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
