<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        if ($username === '' || strlen($username) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 254 || strlen($password) < 8 || !in_array($role, ['waiter', 'kitchen'], true)) {
            $error = 'Enter a username, valid email, password of at least 8 characters and a staff role.';
        } else {
            try {
                $statement = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
                $statement->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                flash('Staff account created.');
                redirect('admin/staff.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'That username or email is already in use.';
                } else {
                    throw $exception;
                }
            }
        }
    } elseif ($action === 'toggle') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id && (int) $id !== (int) current_user()['id']) {
            $statement = $pdo->prepare(
                'UPDATE users SET is_active = IF(is_active = 1, 0, 1)
                 WHERE id = ? AND role IN ("waiter", "kitchen")'
            );
            $statement->execute([$id]);
            flash('Staff account status updated.');
            redirect('admin/staff.php');
        }
        $error = 'That staff account could not be updated.';
    }
}

$staff = $pdo->query(
    "SELECT id, username, email, role, is_active FROM users
     WHERE role IN ('waiter', 'kitchen') ORDER BY role, username"
)->fetchAll();
$pageTitle = 'Staff accounts';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <section class="dashboard-content">
        <h1>Restaurant staff</h1>
        <section class="panel form-card">
            <h2>Add waiter or kitchen staff</h2>
            <?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add">
                <label for="username">Username</label><input id="username" name="username" maxlength="50" required>
                <label for="email">Email</label><input id="email" name="email" type="email" maxlength="254" required>
                <label for="password">Temporary password (at least 8 characters)</label><input id="password" name="password" type="password" minlength="8" required autocomplete="new-password">
                <label for="role">Role</label><select id="role" name="role"><option value="waiter">Waiter</option><option value="kitchen">Kitchen staff</option></select>
                <p><button type="submit">Create account</button></p>
            </form>
        </section>
        <h2>Staff accounts</h2>
        <div class="table-wrap"><table><thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($staff as $member): ?><tr>
            <td><?= e($member['username']) ?></td><td><?= e($member['email']) ?></td><td><?= e(ucfirst($member['role'])) ?></td><td><?= (int) $member['is_active'] ? 'Active' : 'Inactive' ?></td>
            <td><form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                <button class="button-small <?= (int) $member['is_active'] ? 'button-danger' : 'button-secondary' ?>" type="submit"><?= (int) $member['is_active'] ? 'Deactivate' : 'Activate' ?></button>
            </form></td>
        </tr><?php endforeach; ?>
        <?php if (!$staff): ?><tr><td colspan="5">No staff accounts yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
