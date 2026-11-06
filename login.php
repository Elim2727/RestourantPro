<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect(dashboard_for_role(current_user()['role']));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $statement = $pdo->prepare(
        'SELECT id, username, email, password_hash, role, is_active
         FROM users WHERE username = ? OR email = ? LIMIT 1'
    );
    $statement->execute([$login, $login]);
    $user = $statement->fetch();

    if ($user && (int) $user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        redirect(dashboard_for_role($user['role']));
    }
    $error = 'The username/email or password is incorrect, or the account is inactive.';
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<section class="panel form-card">
    <h1>Log in</h1>
    <?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="login">Username or email</label>
        <input id="login" name="login" required autocomplete="username" value="<?= e($_POST['login'] ?? '') ?>">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
        <p><button type="submit">Log in</button></p>
    </form>
    <p>Customer? <a href="<?= e(url('register.php')) ?>">Create an account</a>.</p>
    <p class="muted">Administrator? If this is a fresh installation, first create the admin using <a href="<?= e(url('setup_admin.php')) ?>">the one-time setup page</a>.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
