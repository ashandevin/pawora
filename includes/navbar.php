<?php
declare(strict_types=1);

require_once __DIR__ . '/cart-helpers.php';

$user = current_user();
$unread = unread_notification_count();
$favoriteTotal = favorite_count();
$cartTotal = cart_count();
?>
<nav class="navbar navbar-expand-lg pawora-navbar sticky-top" aria-label="Primary navigation">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(app_url('index.php')) ?>" aria-label="PAWORA home">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 48 48" role="img"><path d="M24 41.5c-7.4 0-13.4-4.6-13.4-10.3 0-4 3.2-6.6 6.1-6.6 2 0 3.6 1 4.9 2.4 1.5-1.7 3.7-3.2 6.4-3.2 3.7 0 6.6 2.9 6.6 7 0 5.7-4.5 10.7-10.6 10.7Z"/><ellipse cx="13.3" cy="20.2" rx="4.1" ry="5.1" transform="rotate(-27 13.3 20.2)"/><ellipse cx="21.2" cy="13.7" rx="4.1" ry="5.1" transform="rotate(-9 21.2 13.7)"/><ellipse cx="30.5" cy="13.7" rx="4.1" ry="5.1" transform="rotate(9 30.5 13.7)"/><ellipse cx="38.4" cy="20.2" rx="4.1" ry="5.1" transform="rotate(27 38.4 20.2)"/></svg>
            </span>
            <span class="brand-word">PAWORA</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#paworaNav" aria-controls="paworaNav" aria-expanded="false" aria-label="Open navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="paworaNav">
            <ul class="navbar-nav ms-auto mb-3 mb-lg-0 gap-lg-1">
                <li class="nav-item"><a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="<?= e(app_url('index.php')) ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'pets' ? 'active' : '' ?>" href="<?= e(app_url('pets.php')) ?>">Find a Pet</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'adoption' ? 'active' : '' ?>" href="<?= e(app_url('adoption-process.php')) ?>">Adoption</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'shop' ? 'active' : '' ?>" href="<?= e(app_url('shop.php')) ?>">Shop</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'stories' ? 'active' : '' ?>" href="<?= e(app_url('success-stories.php')) ?>">Stories</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'care' ? 'active' : '' ?>" href="<?= e(app_url('pet-care.php')) ?>">Pet Care</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'about' ? 'active' : '' ?>" href="<?= e(app_url('about.php')) ?>">About</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'contact' ? 'active' : '' ?>" href="<?= e(app_url('contact.php')) ?>">Contact</a></li>
            </ul>
            <div class="nav-actions d-flex align-items-center gap-2">
                <a class="nav-icon-link position-relative" href="<?= e(app_url('pets.php')) ?>#pet-search" aria-label="Search pets">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </a>
                <a class="nav-icon-link position-relative" href="<?= e(app_url(is_logged_in() ? 'user/favorites.php' : 'login.php')) ?>" aria-label="Favorite pets">
                    <i class="bi bi-heart" aria-hidden="true"></i>
                    <?php if ($favoriteTotal > 0): ?><span class="nav-count"><?= e((string) $favoriteTotal) ?></span><?php endif; ?>
                </a>
                <a class="nav-icon-link position-relative" href="<?= e(app_url('cart.php')) ?>" aria-label="Shopping cart<?= $cartTotal > 0 ? ', ' . $cartTotal . ' items' : '' ?>">
                    <i class="bi bi-bag" aria-hidden="true"></i>
                    <?php if ($cartTotal > 0): ?><span class="nav-count"><?= e((string) $cartTotal) ?></span><?php endif; ?>
                </a>
                <?php if ($user): ?>
                    <div class="dropdown">
                        <button class="btn btn-dark rounded-pill px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1" aria-hidden="true"></i><span class="d-none d-sm-inline"><?= e(explode(' ', trim($user['name']))[0]) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><span class="dropdown-item-text small text-muted">Signed in as</span></li>
                            <li><a class="dropdown-item" href="<?= e(app_url(($user['role'] ?? '') === 'ADMIN' ? 'admin/dashboard.php' : 'user/dashboard.php')) ?>">Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?= e(app_url('user/orders.php')) ?>">My orders</a></li>
                            <li><a class="dropdown-item" href="<?= e(app_url('cart.php')) ?>">My cart</a></li>
                            <li><a class="dropdown-item" href="<?= e(app_url('user/profile.php')) ?>">My profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><form method="post" action="<?= e(app_url('logout.php')) ?>"><?= csrf_field() ?><button class="dropdown-item" type="submit">Sign out</button></form></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-outline-dark rounded-pill px-3" href="<?= e(app_url('login.php')) ?>">Sign in</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
