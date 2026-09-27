<?php
declare(strict_types=1);

require_once __DIR__ . '/cart-helpers.php';

function order_checkout_values(array $user = []): array
{
    $stored = $_SESSION['checkout_values'] ?? [];
    unset($_SESSION['checkout_values']);
    if (!is_array($stored)) {
        $stored = [];
    }

    $value = static function (string $key, string $fallback) use ($stored): string {
        if (isset($_POST[$key]) && is_scalar($_POST[$key])) {
            return trim((string) $_POST[$key]);
        }
        if (isset($stored[$key]) && is_scalar($stored[$key])) {
            return trim((string) $stored[$key]);
        }
        return $fallback;
    };

    return [
        'billing_name' => $value('billing_name', trim((string) ($user['name'] ?? ''))),
        'billing_email' => $value('billing_email', trim((string) ($user['email'] ?? ''))),
        'billing_phone' => $value('billing_phone', trim((string) ($user['phone'] ?? ''))),
        'shipping_address' => $value('shipping_address', ''),
        'notes' => $value('notes', ''),
        'payment_method' => $value('payment_method', 'demo_card'),
    ];
}

function order_forget_checkout_values(): void
{
    unset($_SESSION['checkout_values']);
}

function validate_checkout_values(array $values): array
{
    $errors = [];
    $nameLength = text_length((string) ($values['billing_name'] ?? ''));
    if ($nameLength < 2 || $nameLength > 120) {
        $errors[] = 'Enter the billing name between 2 and 120 characters.';
    }
    $email = trim((string) ($values['billing_email'] ?? ''));
    if ($email === '' || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid billing email address.';
    }
    $phone = trim((string) ($values['billing_phone'] ?? ''));
    if ($phone !== '' && !preg_match('/^[0-9+()\s-]{7,40}$/', $phone)) {
        $errors[] = 'Enter a valid billing phone number or leave it blank.';
    }
    $address = trim((string) ($values['shipping_address'] ?? ''));
    $addressLength = text_length($address);
    if ($addressLength < 10 || $addressLength > 1000) {
        $errors[] = 'Enter a delivery address between 10 and 1,000 characters.';
    }
    $notes = trim((string) ($values['notes'] ?? ''));
    if (text_length($notes) > 1000) {
        $errors[] = 'Keep delivery notes under 1,000 characters.';
    }
    if (!in_array((string) ($values['payment_method'] ?? ''), ['demo_card', 'cash_on_delivery'], true)) {
        $errors[] = 'Choose a valid payment method.';
    }
    return $errors;
}

function card_number_is_valid(string $number): bool
{
    $digits = preg_replace('/\D+/', '', $number) ?? '';
    $length = strlen($digits);
    if ($length < 13 || $length > 19) {
        return false;
    }

    $sum = 0;
    $double = false;
    for ($index = $length - 1; $index >= 0; $index--) {
        $digit = (int) $digits[$index];
        if ($double) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        $sum += $digit;
        $double = !$double;
    }
    return $sum % 10 === 0;
}

function card_brand(string $number): string
{
    $digits = preg_replace('/\D+/', '', $number) ?? '';
    return match (true) {
        (bool) preg_match('/^4/', $digits) => 'Visa',
        (bool) preg_match('/^5[1-5]/', $digits) => 'Mastercard',
        (bool) preg_match('/^3[47]/', $digits) => 'American Express',
        (bool) preg_match('/^35/', $digits) => 'JCB',
        (bool) preg_match('/^6(?:011|5)/', $digits) => 'Discover',
        default => 'Card',
    };
}

function card_expiry_is_valid(string $expiry): bool
{
    if (!preg_match('/^(0[1-9]|1[0-2])\s*\/\s*(\d{2}|\d{4})$/', trim($expiry), $matches)) {
        return false;
    }
    $month = (int) $matches[1];
    $year = (int) $matches[2];
    if (strlen($matches[2]) === 2) {
        $year += 2000;
    }
    if ($year < (int) date('Y') || $year > (int) date('Y') + 20) {
        return false;
    }
    $lastDay = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
    $expiryTimestamp = mktime(23, 59, 59, $month, $lastDay, $year);
    return $expiryTimestamp >= time();
}

function validate_demo_card(array $input): array
{
    $errors = [];
    $cardholder = trim((string) ($input['cardholder'] ?? ''));
    $number = trim((string) ($input['card_number'] ?? ''));
    $expiry = trim((string) ($input['card_expiry'] ?? ''));
    $cvv = trim((string) ($input['card_cvv'] ?? ''));

    if (text_length($cardholder) < 2 || text_length($cardholder) > 120) {
        $errors[] = 'Enter the name printed on the demo card.';
    }
    if (!card_number_is_valid($number)) {
        $errors[] = 'Enter a valid demo card number, for example 4242 4242 4242 4242.';
    }
    if (!card_expiry_is_valid($expiry)) {
        $errors[] = 'Enter a valid future expiry date in MM/YY format.';
    }
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        $errors[] = 'Enter the 3 or 4 digit demo card security code.';
    }

    $digits = preg_replace('/\D+/', '', $number) ?? '';
    return [
        'errors' => $errors,
        'brand' => $digits !== '' ? card_brand($digits) : '',
        'last_four' => strlen($digits) >= 4 ? substr($digits, -4) : '',
    ];
}

function order_reference(PDO $pdo, string $prefix = 'PAW', string $table = 'orders'): string
{
    $table = $table === 'payments' ? 'payments' : 'orders';
    $column = $table === 'payments' ? 'reference' : 'order_reference';
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $reference = $prefix . '-' . date('Y') . '-' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $statement = $pdo->prepare('SELECT id FROM ' . $table . ' WHERE ' . $column . ' = ? LIMIT 1');
        $statement->execute([$reference]);
        if (!$statement->fetchColumn()) {
            return $reference;
        }
    }
    throw new RuntimeException('Could not generate a unique order reference.');
}

function order_notify(int $userId, string $title, string $message): void
{
    try {
        notify($userId, $title, $message);
    } catch (Throwable $exception) {
    }
}

function place_order(array $user, array $values, array $card): array
{
    $userId = (int) ($user['id'] ?? 0);
    if ($userId <= 0) {
        return ['errors' => ['You must be signed in to place an order.']];
    }

    $errors = validate_checkout_values($values);
    $method = (string) ($values['payment_method'] ?? '');
    if ($method === 'demo_card') {
        $cardResult = validate_demo_card($card);
        $errors = array_merge($errors, $cardResult['errors']);
        $card = $cardResult;
    } else {
        $card = ['errors' => [], 'brand' => null, 'last_four' => null];
    }
    if ($errors) {
        return ['errors' => $errors];
    }

    $items = cart_items();
    if (cart_foreign_currency_items($items) !== []) {
        return ['errors' => ['One or more items are not sold in ' . store_currency() . '. Review your cart and try again.']];
    }
    if (!cart_is_checkout_ready($items)) {
        return ['errors' => ['Your cart changed while you were checking out. Review the cart and try again.']];
    }

    $pdo = null;
    try {
        $pdo = db();
        $pdo->beginTransaction();

        $lines = [];
        foreach ($items as $item) {
            $statement = $pdo->prepare(
                'SELECT p.id, p.name, p.sku, p.price, p.discount, p.stock_quantity, p.status, c.status AS category_status
                 FROM products p
                 INNER JOIN categories c ON c.id = p.category_id
                 WHERE p.id = ? LIMIT 1 FOR UPDATE'
            );
            $statement->execute([(int) $item['id']]);
            $product = $statement->fetch();
            if (!$product) {
                $pdo->rollBack();
                return ['errors' => [(string) $item['name'] . ' is no longer available. Remove it from your cart to continue.']];
            }
            $stock = (int) $product['stock_quantity'];
            $quantity = (int) $item['quantity'];
            if (strtoupper((string) $product['status']) !== 'ACTIVE' || strtoupper((string) $product['category_status']) !== 'ACTIVE') {
                $pdo->rollBack();
                return ['errors' => [(string) $item['name'] . ' is no longer available for ordering.']];
            }
            if ($stock < $quantity) {
                $pdo->rollBack();
                return ['errors' => ['Only ' . $stock . ' unit' . ($stock === 1 ? '' : 's') . ' of ' . (string) $item['name'] . ' remain' . ($stock === 1 ? 's' : '') . '. Update your cart and try again.']];
            }
            $unitPrice = round(product_price($product), 2);
            $lines[] = [
                'product_id' => (int) $product['id'],
                'name' => (string) $product['name'],
                'sku' => (string) $product['sku'],
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => round($unitPrice * $quantity, 2),
                'new_stock' => $stock - $quantity,
            ];
        }

        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += $line['line_total'];
        }
        $subtotal = round($subtotal, 2);
        $shipping = cart_shipping($subtotal);
        $total = round($subtotal + $shipping, 2);
        $paid = $method === 'demo_card';
        $orderReference = order_reference($pdo);

        $insertOrder = $pdo->prepare(
            'INSERT INTO orders (user_id, order_reference, subtotal, shipping, total, status, payment_status, billing_name, billing_email, billing_phone, shipping_address, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertOrder->execute([
            $userId,
            $orderReference,
            $subtotal,
            $shipping,
            $total,
            $paid ? 'PAID' : 'PENDING',
            $paid ? 'PAID' : 'PENDING',
            trim((string) $values['billing_name']),
            trim((string) $values['billing_email']),
            ($values['billing_phone'] ?? '') !== '' ? trim((string) $values['billing_phone']) : null,
            trim((string) $values['shipping_address']),
            ($values['notes'] ?? '') !== '' ? trim((string) $values['notes']) : null,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $insertItem = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, sku, unit_price, quantity, line_total)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $updateStock = $pdo->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?');
        foreach ($lines as $line) {
            $insertItem->execute([
                $orderId,
                $line['product_id'],
                $line['name'],
                $line['sku'],
                $line['unit_price'],
                $line['quantity'],
                $line['line_total'],
            ]);
            $updateStock->execute([$line['new_stock'], $line['product_id']]);
        }

        $paymentReference = order_reference($pdo, 'PAY', 'payments');
        $insertPayment = $pdo->prepare(
            'INSERT INTO payments (order_id, method, amount, reference, status, card_brand, card_last_four, paid_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertPayment->execute([
            $orderId,
            $method,
            $total,
            $paymentReference,
            $paid ? 'PAID' : 'PENDING',
            $paid ? $card['brand'] : null,
            $paid ? $card['last_four'] : null,
            $paid ? date('Y-m-d H:i:s') : null,
        ]);

        order_notify($userId, 'Order ' . $orderReference . ' received', $paid ? 'Payment of ' . format_money($total) . ' was recorded for order ' . $orderReference . '.' : 'We will confirm your cash on delivery order ' . $orderReference . ' shortly.');

        $pdo->commit();
        cart_clear();
        order_forget_checkout_values();
        return ['errors' => [], 'order_id' => $orderId, 'order_reference' => $orderReference, 'total' => $total];
    } catch (Throwable $exception) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['errors' => ['We could not complete your order right now. Your card was not charged and your cart is unchanged.']];
    }
}

function get_order(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    return db_one(
        'SELECT o.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id
         WHERE o.id = ? LIMIT 1',
        [$id]
    );
}

function get_order_items(int $orderId): array
{
    return db_all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
}

function get_order_payment(int $orderId): ?array
{
    return db_one('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1', [$orderId]);
}

function can_view_order(array $order): bool
{
    if (current_user_is_admin()) {
        return true;
    }
    return is_logged_in() && (int) $order['user_id'] === (int) ($_SESSION['user_id'] ?? 0);
}

function user_orders(int $userId, int $limit = 50): array
{
    $limit = max(1, min(200, $limit));
    return db_all(
        'SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_lines
         FROM orders o
         WHERE o.user_id = ?
         ORDER BY o.created_at DESC, o.id DESC
         LIMIT ' . $limit,
        [$userId]
    );
}

function admin_order_filters(array $filters = []): array
{
    $where = [];
    $params = [];
    $status = strtoupper(trim((string) ($filters['status'] ?? '')));
    if (in_array($status, order_statuses(), true)) {
        $where[] = 'o.status = ?';
        $params[] = $status;
    }
    $search = trim((string) ($filters['search'] ?? ''));
    if ($search !== '') {
        $where[] = '(o.order_reference LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';
        $term = '%' . $search . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }
    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

function admin_order_count(array $filters = []): int
{
    [$whereSql, $params] = admin_order_filters($filters);
    return (int) (db_value('SELECT COUNT(*) FROM orders o INNER JOIN users u ON u.id = o.user_id' . $whereSql, $params) ?? 0);
}

function admin_orders(array $filters = [], int $limit = 50, int $offset = 0): array
{
    [$whereSql, $params] = admin_order_filters($filters);
    $limit = max(1, min(200, $limit));
    $offset = max(0, $offset);
    return db_all(
        'SELECT o.*, u.name AS user_name, u.email AS user_email
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id' . $whereSql . '
         ORDER BY o.created_at DESC, o.id DESC
         LIMIT ' . $limit . ' OFFSET ' . $offset,
        $params
    );
}

function admin_order_counts(): array
{
    $counts = array_fill_keys(order_statuses(), 0);
    $rows = db_all('SELECT status, COUNT(*) AS total FROM orders GROUP BY status');
    foreach ($rows as $row) {
        $counts[(string) $row['status']] = (int) $row['total'];
    }
    $counts['TOTAL'] = array_sum($counts);
    return $counts;
}

function update_order_status(int $orderId, string $status, int $adminId): array
{
    if (!current_user_is_admin()) {
        return ['errors' => ['You do not have permission to update orders.']];
    }
    return apply_order_status_change($orderId, $status, $adminId, true);
}

function order_can_be_cancelled_by_user(array $order): bool
{
    return strtoupper((string) ($order['status'] ?? '')) === 'PENDING'
        && strtoupper((string) ($order['payment_status'] ?? '')) !== 'PAID';
}

function cancel_own_order(int $orderId, int $userId): array
{
    if (!is_logged_in() || (int) ($_SESSION['user_id'] ?? 0) !== $userId) {
        return ['errors' => ['You do not have permission to update this order.']];
    }
    return apply_order_status_change($orderId, 'CANCELLED', $userId, false);
}

function apply_order_status_change(int $orderId, string $status, int $actorId, bool $actorIsAdmin): array
{
    $status = strtoupper(trim($status));
    if (!in_array($status, order_statuses(), true)) {
        return ['errors' => ['Choose a valid order status.']];
    }
    if (!$actorIsAdmin && $status !== 'CANCELLED') {
        return ['errors' => ['You can only cancel an order that has not started yet.']];
    }

    $pdo = null;
    try {
        $pdo = db();
        $pdo->beginTransaction();
        $statement = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1 FOR UPDATE');
        $statement->execute([$orderId]);
        $order = $statement->fetch();
        if (!$order) {
            $pdo->rollBack();
            return ['errors' => ['That order could not be found.']];
        }
        $current = strtoupper((string) $order['status']);
        if (!$actorIsAdmin && ((int) $order['user_id'] !== $actorId || !order_can_be_cancelled_by_user($order))) {
            $pdo->rollBack();
            return ['errors' => ['This order can no longer be cancelled online. Please contact the PAWORA team for help.']];
        }
        if ($current === $status) {
            $pdo->rollBack();
            return ['errors' => ['This order is already marked as ' . status_label($status) . '.']];
        }
        if ($current === 'CANCELLED') {
            $pdo->rollBack();
            return ['errors' => ['Cancelled orders cannot be reopened. Place a new order instead.']];
        }
        if (in_array($status, ['SHIPPED', 'DELIVERED'], true) && strtoupper((string) $order['payment_status']) !== 'PAID') {
            $pdo->rollBack();
            return ['errors' => ['Only orders with a recorded payment can be shipped or delivered.']];
        }

        $paymentStatus = (string) $order['payment_status'];
        if ($status === 'CANCELLED') {
            $items = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
            $items->execute([$orderId]);
            $restock = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?');
            $lockProduct = $pdo->prepare('SELECT id FROM products WHERE id = ? LIMIT 1 FOR UPDATE');
            foreach ($items->fetchAll() as $item) {
                $productId = (int) $item['product_id'];
                if ($productId <= 0) {
                    continue;
                }
                $lockProduct->execute([$productId]);
                $restock->execute([(int) $item['quantity'], $productId]);
            }
            if ($paymentStatus === 'PAID') {
                $paymentStatus = 'REFUNDED';
            }
        }

        $update = $pdo->prepare('UPDATE orders SET status = ?, payment_status = ? WHERE id = ?');
        $update->execute([$status, $paymentStatus, $orderId]);
        if ($status === 'CANCELLED' && $paymentStatus === 'REFUNDED') {
            $updatePayment = $pdo->prepare('UPDATE payments SET status = ? WHERE order_id = ? AND status = ?');
            $updatePayment->execute(['REFUNDED', $orderId, 'PAID']);
        }
        add_admin_log($actorId, $actorIsAdmin ? 'ORDER_STATUS' : 'ORDER_CANCEL', $actorIsAdmin
            ? 'Order ' . $order['order_reference'] . ': ' . status_label($current) . ' to ' . status_label($status)
            : 'Customer cancelled order ' . $order['order_reference'] . ' (' . status_label($current) . ')');
        order_notify((int) $order['user_id'], 'Order ' . $order['order_reference'] . ' updated', 'Your order is now ' . strtolower(status_label($status)) . '.');
        if (!$actorIsAdmin) {
            order_notify((int) $order['user_id'], 'Order ' . $order['order_reference'] . ' cancelled', 'You cancelled this order and the items were returned to our shelves.');
        }
        $pdo->commit();
        return ['errors' => []];
    } catch (Throwable $exception) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['errors' => ['We could not update that order right now.']];
    }
}
