<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/order-helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('checkout.php');
}
require_auth();

$user = current_user() ?? [];
$values = order_checkout_values($user);
$card = [
    'cardholder' => trim((string) ($_POST['cardholder'] ?? '')),
    'card_number' => trim((string) ($_POST['card_number'] ?? '')),
    'card_expiry' => trim((string) ($_POST['card_expiry'] ?? '')),
    'card_cvv' => trim((string) ($_POST['card_cvv'] ?? '')),
];

if (!verify_csrf()) {
    $_SESSION['checkout_values'] = $values;
    set_flash('error', 'Your form session expired. Please try again.');
    redirect('checkout.php');
}

$_SESSION['checkout_values'] = $values;
$result = place_order($user, $values, $card);
if ($result['errors']) {
    set_flash('error', implode(' ', $result['errors']));
    redirect('checkout.php');
}

set_flash('success', 'Order ' . $result['order_reference'] . ' was placed successfully.');
redirect('order-confirmation.php?id=' . (int) $result['order_id']);
