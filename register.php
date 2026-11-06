<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect(dashboard_for_role(current_user()['role']));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullName === '' || strlen($fullName) > 120 || $username === '' || strlen($username) > 50
        || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254
        || strlen($phone) > 30 || strlen($address) > 255 || strlen($password) < 8) {
        $error = 'Enter a valid name, username, email and contact details. Passwords must be at least 8 characters.';
    } else {
        try {
            $pdo->beginTransaction();
            $statement = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash, role)
                 VALUES (?, ?, ?, "customer")'
            );
            $statement->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $userId = $pdo->lastInsertId();

            $statement = $pdo->prepare(
                'INSERT INTO customers (user_id, full_name, email, phone, address)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $statement->execute([$userId, $fullName, $email, $phone ?: null, $address ?: null]);
            $pdo->commit();
            flash('Your account was created. You can now log in.');
            redirect('login.php');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception->getCode() === '23000') {
                $error = 'That username or email is already registered. Please choose another.';
            } else {
                throw $exception;
            }
        }
    }
}

$pageTitle = 'Customer registration';
require __DIR__ . '/includes/header.php';
?>
<section class="panel form-card">
    <h1>Create a customer account</h1>
    <?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="full_name">Full name</label>
        <input id="full_name" name="full_name" maxlength="120" required value="<?= e($_POST['full_name'] ?? '') ?>">
        <div class="form-row">
            <div><label for="username">Username</label><input id="username" name="username" maxlength="50" required value="<?= e($_POST['username'] ?? '') ?>"></div>
            <div><label for="email">Email</label><input id="email" name="email" type="email" maxlength="254" required value="<?= e($_POST['email'] ?? '') ?>"></div>
        </div>
        <div class="form-row">
            <div><label for="phone">Phone</label><input id="phone" name="phone" maxlength="30" value="<?= e($_POST['phone'] ?? '') ?>"></div>
            <div><label for="address">Address</label><input id="address" name="address" maxlength="255" value="<?= e($_POST['address'] ?? '') ?>"></div>
        </div>
        <label for="password">Password (at least 8 characters)</label>
        <input id="password" name="password" type="password" minlength="8" required autocomplete="new-password">
        <p><button type="submit">Register</button></p>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
