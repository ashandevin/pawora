<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/data.php';

function cart_max_quantity_per_item(): int
{
    return 99;
}

function cart_shipping_fee(): float
{
    return 350.00;
}

function cart_free_shipping_threshold(): float
{
    return 5000.00;
}

function cart_session_items(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) {
        return [];
    }

    $clean = [];
    foreach ($cart as $productId => $quantity) {
        $productId = (int) $productId;
        $quantity = (int) $quantity;
        if ($productId > 0 && $quantity > 0) {
            $clean[$productId] = min($quantity, cart_max_quantity_per_item());
        }
    }

    return $clean;
}

function cart_items(): array
{
    $cart = cart_session_items();
    if ($cart === []) {
        return [];
    }

    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = db_all(
        'SELECT p.id, p.name, p.sku, p.brand, p.price, p.discount, p.currency, p.price_type, p.stock_quantity, p.low_stock_threshold, p.status, p.image, c.status AS category_status
         FROM products p
         INNER JOIN categories c ON c.id = p.category_id
         WHERE p.id IN (' . $placeholders . ')',
        $ids
    );

    $products = [];
    foreach ($rows as $row) {
        $products[(int) $row['id']] = $row;
    }

    $items = [];
    foreach ($cart as $productId => $quantity) {
        if (!isset($products[$productId])) {
            unset($_SESSION['cart'][$productId]);
            continue;
        }

        $product = $products[$productId];
        $stock = max(0, (int) $product['stock_quantity']);
        $product['quantity'] = (int) $quantity;
        $product['line_total'] = round(product_price($product) * (int) $quantity, 2);
        $product['available'] = strtoupper(trim((string) $product['status'])) === 'ACTIVE'
            && strtoupper(trim((string) $product['category_status'])) === 'ACTIVE'
            && $stock >= (int) $quantity;

        if (strtoupper(trim((string) $product['status'])) === 'INACTIVE') {
            $product['availability_message'] = 'This product is no longer available.';
        } elseif (strtoupper(trim((string) $product['category_status'])) !== 'ACTIVE') {
            $product['availability_message'] = 'This product is no longer available.';
        } elseif ($stock <= 0) {
            $product['availability_message'] = 'Out of stock.';
        } elseif ($stock < (int) $quantity) {
            $product['availability_message'] = 'Only ' . $stock . ' left in stock.';
        } else {
            $product['availability_message'] = '';
        }

        $items[] = $product;
    }

    return $items;
}

function cart_count(): int
{
    $count = 0;
    foreach (cart_session_items() as $quantity) {
        $count += (int) $quantity;
    }
    return $count;
}

function cart_subtotal(array $items): float
{
    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += (float) $item['line_total'];
    }
    return round($subtotal, 2);
}

function cart_shipping(float $subtotal): float
{
    if ($subtotal <= 0 || $subtotal >= cart_free_shipping_threshold()) {
        return 0.0;
    }
    return cart_shipping_fee();
}

function cart_totals(array $items): array
{
    $subtotal = cart_subtotal($items);
    $shipping = cart_shipping($subtotal);
    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => round($subtotal + $shipping, 2),
        'item_count' => array_sum(array_map(static fn (array $item): int => (int) $item['quantity'], $items)),
    ];
}

function cart_is_checkout_ready(array $items): bool
{
    if ($items === []) {
        return false;
    }
    foreach ($items as $item) {
        if (empty($item['available'])) {
            return false;
        }
    }
    return true;
}

function cart_foreign_currency_items(array $items): array
{
    $storeCurrency = store_currency();
    $foreign = [];
    foreach ($items as $item) {
        if (strtoupper(trim((string) ($item['currency'] ?? $storeCurrency))) !== $storeCurrency) {
            $foreign[] = $item;
        }
    }
    return $foreign;
}

function cart_product(int $productId): ?array
{
    if ($productId <= 0) {
        return null;
    }
    return db_one(
        'SELECT p.*, c.status AS category_status
         FROM products p
         INNER JOIN categories c ON c.id = p.category_id
         WHERE p.id = ? LIMIT 1',
        [$productId]
    );
}

function cart_add(int $productId, int $quantity): array
{
    if ($productId <= 0) {
        return ['ok' => false, 'message' => 'Choose a product to add.'];
    }
    if ($quantity < 1) {
        return ['ok' => false, 'message' => 'Quantity must be at least 1.'];
    }
    if ($quantity > cart_max_quantity_per_item()) {
        return ['ok' => false, 'message' => 'You can order up to ' . cart_max_quantity_per_item() . ' units per product.'];
    }

    $product = cart_product($productId);
    if (!$product) {
        return ['ok' => false, 'message' => 'That product could not be found.'];
    }
    if (strtoupper(trim((string) $product['status'])) !== 'ACTIVE' || strtoupper(trim((string) $product['category_status'])) !== 'ACTIVE') {
        return ['ok' => false, 'message' => 'That product is not available for ordering.'];
    }

    $stock = max(0, (int) $product['stock_quantity']);
    if ($stock <= 0) {
        return ['ok' => false, 'message' => 'That product is out of stock.'];
    }

    $cart = cart_session_items();
    $newQuantity = (int) ($cart[$productId] ?? 0) + $quantity;
    if ($newQuantity > $stock) {
        return ['ok' => false, 'message' => 'Only ' . $stock . ' unit' . ($stock === 1 ? '' : 's') . ' available.'];
    }
    if ($newQuantity > cart_max_quantity_per_item()) {
        return ['ok' => false, 'message' => 'You can order up to ' . cart_max_quantity_per_item() . ' units per product.'];
    }

    $_SESSION['cart'][$productId] = $newQuantity;
    return ['ok' => true, 'message' => $product['name'] . ' added to your cart.'];
}

function cart_update(int $productId, int $quantity): array
{
    if ($productId <= 0) {
        return ['ok' => false, 'message' => 'That product could not be found.'];
    }
    if ($quantity <= 0) {
        cart_remove($productId);
        return ['ok' => true, 'message' => 'Product removed from your cart.'];
    }
    if ($quantity > cart_max_quantity_per_item()) {
        return ['ok' => false, 'message' => 'You can order up to ' . cart_max_quantity_per_item() . ' units per product.'];
    }

    $product = cart_product($productId);
    if (!$product || strtoupper(trim((string) $product['status'])) !== 'ACTIVE' || strtoupper(trim((string) $product['category_status'])) !== 'ACTIVE') {
        return ['ok' => false, 'message' => 'That product is not available for ordering.'];
    }

    $stock = max(0, (int) $product['stock_quantity']);
    if ($quantity > $stock) {
        return ['ok' => false, 'message' => 'Only ' . $stock . ' unit' . ($stock === 1 ? '' : 's') . ' available.'];
    }

    $_SESSION['cart'][$productId] = $quantity;
    return ['ok' => true, 'message' => 'Cart updated.'];
}

function cart_remove(int $productId): void
{
    unset($_SESSION['cart'][$productId]);
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

function cart_return_path(?string $path): string
{
    $path = trim((string) $path);
    $parts = explode('?', $path, 2);
    $page = $parts[0];
    $query = $parts[1] ?? '';
    $allowed = ['index.php', 'shop.php', 'product-details.php', 'cart.php'];
    if (!in_array($page, $allowed, true)) {
        return 'cart.php';
    }
    if ($page !== 'product-details.php') {
        return $page;
    }
    if (preg_match('/^id=(\d{1,10})$/', trim($query), $matches)) {
        $productId = (int) $matches[1];
        return $productId > 0 ? 'product-details.php?id=' . $productId : 'cart.php';
    }
    return 'cart.php';
}
