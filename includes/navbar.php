<?php $user = current_user(); ?>
<header class="site-header">
    <nav class="navbar container">
        <a class="brand" href="<?= e(url('index.php')) ?>">Restaurant<span>Pro</span></a>
        <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">Menu</button>
        <div class="nav-links">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <a href="<?= e(url('index.php#menu')) ?>">Menu</a>
            <?php if ($user): ?>
                <a href="<?= e(url(dashboard_for_role($user['role']))) ?>">Dashboard</a>
                <span class="nav-user"><?= e($user['username']) ?> (<?= e(ucfirst($user['role'])) ?>)</span>
                <form method="post" action="<?= e(url('logout.php')) ?>" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="button button-small button-outline" type="submit">Log out</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>">Log in</a>
                <a class="button button-small" href="<?= e(url('register.php')) ?>">Register</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
