<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

$scriptFolder = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$appFolder = preg_replace('#/(admin|waiter|kitchen|customer)$#', '', $scriptFolder);
define('APP_URL', rtrim($appFolder, '/'));

function url($path = '')
{
    return APP_URL . '/' . ltrim($path, '/');
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function check_csrf()
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals(csrf_token(), $submittedToken)) {
        http_response_code(400);
        exit('The form expired. Please go back, refresh the page, and try again.');
    }
}

function flash($message, $type = 'success')
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function show_flash()
{
    if (!empty($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] ?? 'success';
        echo '<div class="notice notice-' . e($type) . '">' . e($_SESSION['flash_message']) . '</div>';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    }
}

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function require_login()
{
    if (!current_user()) {
        flash('Please log in to continue.', 'error');
        redirect('login.php');
    }
}

function require_role($roles)
{
    require_login();

    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
}

function dashboard_for_role($role)
{
    $dashboards = [
        'admin' => 'admin/dashboard.php',
        'waiter' => 'waiter/dashboard.php',
        'kitchen' => 'kitchen/dashboard.php',
        'customer' => 'customer/dashboard.php',
    ];

    return $dashboards[$role] ?? 'index.php';
}

function order_status_label($status)
{
    return ucwords(str_replace('_', ' ', $status));
}

function money($amount)
{
    return number_format((float) $amount, 2);
}
