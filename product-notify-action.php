<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('shop.php');
}

csrf_valid_or_fail();
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
$returnTo = (string) ($_POST['return_to'] ?? 'shop.php');
if (!preg_match('/^[a-z0-9\/-]+\.php(?:\?.*)?$/i', $returnTo)) {
    $returnTo = 'shop.php';
}

if (!is_logged_in()) {
    $_SESSION['return_to'] = $returnTo;
    set_flash('info', 'Sign in to join the waiting list.');
    redirect('login.php');
}

$errors = length_errors($_POST, [
    'name' => ['Your name', 120],
    'email' => ['Email address', 190],
    'phone' => ['Phone number', 40],
]);
$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
if ($name === '') {
    $errors[] = 'Please enter your name.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
if ($phone !== '' && (preg_match('/[^0-9+()\-\s]/', $phone) || strlen(preg_replace('/\D/', '', $phone) ?? '') < 7)) {
    $errors[] = 'Please enter a valid phone number.';
}

try {
    $product = db_one('SELECT id, name, stock_quantity, status FROM products WHERE id = ?', [$productId]);
} catch (Throwable $exception) {
    $errors[] = 'We could not load that product right now.';
    $product = null;
}
if (!$product) {
    $errors[] = 'We could not find that product.';
}
if ($errors) {
    set_flash('error', implode(' ', array_slice($errors, 0, 2)));
    redirect($returnTo);
}
if ((int) $product['stock_quantity'] > 0) {
    set_flash('info', $product['name'] . ' is back in stock. Add it to your cart while stock lasts.');
    redirect($returnTo);
}

try {
    $statement = db()->prepare('SELECT 1 FROM product_waiting_list WHERE product_id = ? AND email = ? LIMIT 1');
    $statement->execute([$productId, $email]);
    if ($statement->fetchColumn()) {
        set_flash('info', 'You are already on the waiting list for ' . $product['name'] . '.');
        redirect($returnTo);
    }
    $insert = db()->prepare('INSERT INTO product_waiting_list (user_id, product_id, name, email, phone) VALUES (?, ?, ?, ?, ?)');
    $insert->execute([(int) $_SESSION['user_id'], $productId, $name, $email, $phone]);
    set_flash('success', 'You are on the waiting list. We will email ' . $email . ' when ' . $product['name'] . ' is restocked.');
} catch (Throwable $exception) {
    set_flash('error', 'We could not add you to the waiting list right now.');
}
redirect($returnTo);
