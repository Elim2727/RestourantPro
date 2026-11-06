<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'RestaurantPro';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | RestaurantPro</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <script src="<?= e(url('assets/js/script.js')) ?>" defer></script>
</head>
<body>
<?php require __DIR__ . '/navbar.php'; ?>
<main class="container">
    <?php show_flash(); ?>
