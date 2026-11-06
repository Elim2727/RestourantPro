<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Welcome';
$statement = $pdo->query(
    'SELECT menu_items.*, categories.name AS category_name
     FROM menu_items
     JOIN categories ON categories.id = menu_items.category_id
     WHERE menu_items.is_available = 1
     ORDER BY menu_items.id DESC
     LIMIT 6'
);
$menuItems = $statement->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <h1>Good food, made simple.</h1>
    <p>Welcome to RestaurantPro. Browse our menu, place an order, and enjoy freshly prepared meals.</p>
    <a class="button" href="#menu">Explore the menu</a>
    <a class="button button-secondary" href="<?= e(url('register.php')) ?>">Create an account</a>
</section>

<section class="section" id="menu">
    <div class="section-heading">
        <h2>Menu favourites</h2>
        <a href="<?= e(url('login.php')) ?>">Log in to order</a>
    </div>
    <div class="cards">
        <?php foreach ($menuItems as $item): ?>
            <article class="card">
                <?php if ($item['image_path']): ?>
                    <img class="food-image" src="<?= e(url($item['image_path'])) ?>" alt="<?= e($item['name']) ?>">
                <?php else: ?>
                    <div class="food-placeholder" aria-hidden="true">&#127835;</div>
                <?php endif; ?>
                <h3><?= e($item['name']) ?></h3>
                <p class="muted"><?= e($item['category_name']) ?></p>
                <p><?= e($item['description'] ?: 'Prepared fresh in our kitchen.') ?></p>
                <p class="food-price">$<?= e(money($item['price'])) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$menuItems): ?><p>Our menu is being prepared. Please visit again soon.</p><?php endif; ?>
</section>

<section class="section" id="about">
    <h2>About RestaurantPro</h2>
    <p>A simple restaurant ordering and management website created as a PHP and MySQL learning project.</p>
</section>
<section class="section" id="contact">
    <h2>Contact</h2>
    <p>Email us at <a href="mailto:hello@restaurantpro.local">hello@restaurantpro.local</a> or visit us during opening hours.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
