<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/cart-helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cart.php');
}

$action = (string) ($_POST['action'] ?? '');
if (!verify_csrf()) {
    set_flash('error', 'Your form session expired. Please try again.');
    redirect(cart_return_path($_POST['return_to'] ?? null));
}

$returnTo = cart_return_path($_POST['return_to'] ?? null);
$productId = (int) ($_POST['product_id'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 1);

switch ($action) {
    case 'add':
        $result = cart_add($productId, max(1, $quantity));
        break;
    case 'update':
        $result = cart_update($productId, $quantity);
        $returnTo = 'cart.php';
        break;
    case 'remove':
        cart_remove($productId);
        $result = ['ok' => true, 'message' => 'Product removed from your cart.'];
        $returnTo = 'cart.php';
        break;
    case 'clear':
        cart_clear();
        $result = ['ok' => true, 'message' => 'Your cart is now empty.'];
        $returnTo = 'cart.php';
        break;
    default:
        $result = ['ok' => false, 'message' => 'That cart action is not supported.'];
}

set_flash($result['ok'] ? 'success' : 'error', $result['message']);
redirect($returnTo);
