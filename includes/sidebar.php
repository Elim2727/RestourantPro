<?php
$role = current_user()['role'] ?? '';
$links = [
    'admin' => [
        ['Dashboard', 'admin/dashboard.php'],
        ['Categories', 'admin/categories.php'],
        ['Menu items', 'admin/menu.php'],
        ['Customers', 'admin/customers.php'],
        ['Orders', 'admin/orders.php'],
        ['Payments', 'admin/payments.php'],
        ['Reports', 'admin/reports.php'],
        ['Staff accounts', 'admin/staff.php'],
    ],
    'waiter' => [
        ['Dashboard', 'waiter/dashboard.php'],
        ['Customers', 'waiter/customers.php'],
        ['Orders', 'waiter/orders.php'],
        ['Payments', 'waiter/payments.php'],
    ],
    'kitchen' => [
        ['Kitchen orders', 'kitchen/orders.php'],
    ],
    'customer' => [
        ['Dashboard', 'customer/dashboard.php'],
        ['Food menu', 'customer/menu.php'],
        ['Shopping cart', 'customer/cart.php'],
        ['My orders', 'customer/my_orders.php'],
    ],
];
?>
<aside class="sidebar">
    <?php foreach ($links[$role] ?? [] as [$label, $path]): ?>
        <a href="<?= e(url($path)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</aside>
