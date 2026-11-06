<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
if ($adminCount > 0) {
    flash('An administrator account already exists. Please log in.');
    redirect('login.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || strlen($username) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 254 || strlen($password) < 8) {
        $error = 'Enter a username, valid email and password with at least 8 characters.';
    } else {
        try {
            $statement = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash, role)
                 VALUES (?, ?, ?, "admin")'
            );
            $statement->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
            flash('Administrator created. Please log in.');
            redirect('login.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'That username or email is already in use.';
            } else {
                throw $exception;
            }
        }
    }
}

$pageTitle = 'Create first administrator';
require __DIR__ . '/includes/header.php';
?>
<section class="panel form-card">
    <h1>First administrator setup</h1>
    <p>This setup form is available only until an administrator account is created. Choose your own password.</p>
    <?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="username">Username</label><input id="username" name="username" maxlength="50" required>
        <label for="email">Email</label><input id="email" name="email" type="email" maxlength="254" required>
        <label for="password">Password (at least 8 characters)</label><input id="password" name="password" type="password" minlength="8" required autocomplete="new-password">
        <p><button type="submit">Create administrator</button></p>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
