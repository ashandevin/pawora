<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('shop.php');
}

csrf_valid_or_fail();
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
$returnTo = (string) ($_POST['return_to'] ?? 'product-details.php');
if (!preg_match('/^[a-z0-9\/-]+\.php(?:\?.*)?$/i', $returnTo)) {
    $returnTo = 'shop.php';
}

if (!is_logged_in()) {
    $_SESSION['return_to'] = $returnTo;
    set_flash('info', 'Sign in to save products to your wishlist.');
    redirect('login.php');
}

try {
    $product = db_one('SELECT id, name FROM products WHERE id = ?', [$productId]);
    if (!$product) {
        set_flash('error', 'We could not find that product.');
        redirect($returnTo);
    }
    $userId = (int) $_SESSION['user_id'];
    $check = db()->prepare('SELECT 1 FROM product_favorites WHERE user_id = ? AND product_id = ? LIMIT 1');
    $check->execute([$userId, $productId]);
    if ($check->fetchColumn()) {
        $delete = db()->prepare('DELETE FROM product_favorites WHERE user_id = ? AND product_id = ?');
        $delete->execute([$userId, $productId]);
        set_flash('info', $product['name'] . ' was removed from your wishlist.');
    } else {
        $insert = db()->prepare('INSERT INTO product_favorites (user_id, product_id) VALUES (?, ?)');
        $insert->execute([$userId, $productId]);
        set_flash('success', $product['name'] . ' was added to your wishlist.');
    }
} catch (Throwable $exception) {
    set_flash('error', 'We could not update your wishlist right now.');
}
redirect($returnTo);
