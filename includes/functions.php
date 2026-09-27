<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function text_length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return (int) mb_strlen($value, 'UTF-8');
    }
    $length = preg_match_all('/./us', $value, $matches);
    return $length === false ? strlen($value) : $length;
}

function clip_text(string $value, int $max): string
{
    if (text_length($value) <= $max) {
        return $value;
    }
    if (function_exists('mb_substr')) {
        return (string) mb_substr($value, 0, $max, 'UTF-8');
    }
    return (string) preg_replace('/^(.{0,' . $max . '}).*$/us', '$1', $value);
}

function ini_size_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    if (!preg_match('/^(\d+(?:\.\d+)?)\s*([KMG]?)B?$/i', $value, $matches)) {
        return 0;
    }
    $number = (float) $matches[1];
    $multipliers = ['' => 1, 'K' => 1024, 'M' => 1048576, 'G' => 1073741824];
    $multiplier = $multipliers[strtoupper($matches[2])] ?? 1;
    return (int) ($number * $multiplier);
}

function upload_limit_bytes(): int
{
    $ini = ini_size_bytes((string) ini_get('upload_max_filesize'));
    if ($ini > 0) {
        return (int) min(MAX_UPLOAD_SIZE, $ini);
    }
    return (int) MAX_UPLOAD_SIZE;
}

function upload_limit_is_server_capped(): bool
{
    return upload_limit_bytes() < MAX_UPLOAD_SIZE;
}

function human_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return rtrim(rtrim(number_format($bytes / 1048576, 1), '0'), '.') . ' MB';
    }
    if ($bytes >= 1024) {
        return rtrim(rtrim(number_format($bytes / 1024, 1), '0'), '.') . ' KB';
    }
    return $bytes . ' bytes';
}

function upload_error_message(int $code): string
{
    $limit = human_bytes(upload_limit_bytes());
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That image is larger than the server accepts. The maximum is ' . $limit . '.',
        UPLOAD_ERR_PARTIAL => 'The image upload was interrupted. Please choose the file again.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'The server could not store the uploaded image temporarily.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension blocked the image upload.',
        default => 'The image upload did not complete. Please choose the file again.',
    };
}

function upload_directory(string $directory): string
{
    return realpath($directory) ?: $directory;
}

function upload_directory_error(string $directory): ?string
{
    $pretty = upload_directory($directory);
    if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
        $pretty = upload_directory($directory);
        return 'The image folder could not be created at ' . $pretty . '. Create it and make it writable, then try again.';
    }
    if (!is_writable($directory)) {
        return 'The image folder is not writable by the web server: ' . $pretty
            . '. Run: chmod 777 ' . $pretty . ' (or chown it to the web user) and try again.';
    }
    return null;
}

function text_limit_error(string $value, int $max, string $label): ?string
{
    $length = text_length(trim($value));
    if ($length <= $max) {
        return null;
    }
    return sprintf('%s is too long (%d characters, maximum %d).', $label, $length, $max);
}

function length_errors(array $input, array $fields): array
{
    $errors = [];
    foreach ($fields as $field => $rule) {
        [$label, $max] = $rule;
        $value = $input[$field] ?? '';
        if (!is_scalar($value)) {
            continue;
        }
        $error = text_limit_error((string) $value, $max, $label);
        if ($error !== null) {
            $errors[] = $error;
        }
    }
    return $errors;
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($flashes) ? $flashes : [];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['_token'] ?? '';
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

function old(string $key, mixed $fallback = ''): mixed
{
    return $_POST[$key] ?? $fallback;
}

function selected(mixed $value, mixed $expected): string
{
    return (string) $value === (string) $expected ? ' selected' : '';
}

function checked(bool $condition): string
{
    return $condition ? ' checked' : '';
}

function format_date(?string $date, string $format = 'M j, Y'): string
{
    if (!$date) {
        return 'Not available';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : 'Not available';
}

function pet_age(?int $months): string
{
    if ($months === null) {
        return 'Age not listed';
    }
    if ($months < 12) {
        return $months . ' month' . ($months === 1 ? '' : 's');
    }
    $years = intdiv($months, 12);
    $remaining = $months % 12;
    $label = $years . ' year' . ($years === 1 ? '' : 's');
    return $remaining > 0 ? $label . ', ' . $remaining . ' month' . ($remaining === 1 ? '' : 's') : $label;
}

function status_label(string $status): string
{
    return match (strtoupper(trim($status))) {
        'AVAILABLE' => 'Available',
        'PENDING' => 'Pending',
        'ADOPTED' => 'Adopted',
        'RESERVED' => 'Reserved',
        'SUBMITTED' => 'Submitted',
        'UNDER REVIEW' => 'Under review',
        'INTERVIEW' => 'Interview',
        'APPROVED' => 'Approved',
        'REJECTED' => 'Rejected',
        'ADOPTION COMPLETED' => 'Adoption completed',
        'ACTIVE' => 'Active',
        'INACTIVE' => 'Inactive',
        'OUT OF STOCK' => 'Out of stock',
        'LOW STOCK' => 'Low stock',
        'PAID' => 'Paid',
        'PROCESSING' => 'Processing',
        'SHIPPED' => 'Shipped',
        'DELIVERED' => 'Delivered',
        'CANCELLED' => 'Cancelled',
        'FAILED' => 'Failed',
        'REFUNDED' => 'Refunded',
        default => ucwords(strtolower(trim($status))),
    };
}

function status_badge(string $status): string
{
    $normalized = strtoupper(str_replace(' ', '-', $status));
    $icon = match ($normalized) {
        'AVAILABLE', 'APPROVED', 'ACTIVE' => 'bi-check-circle-fill',
        'PENDING', 'UNDER-REVIEW', 'INTERVIEW' => 'bi-hourglass-split',
        'ADOPTED', 'REJECTED', 'ADOPTION-COMPLETED' => $normalized === 'REJECTED' ? 'bi-x-circle-fill' : 'bi-heart-fill',
        'RESERVED' => 'bi-bookmark-fill',
        'PAID', 'DELIVERED' => 'bi-check-circle-fill',
        'PROCESSING', 'SHIPPED' => 'bi-truck',
        'CANCELLED' => 'bi-x-octagon-fill',
        'REFUNDED' => 'bi-arrow-counterclockwise',
        'FAILED' => 'bi-x-circle-fill',
        'OUT-OF-STOCK' => 'bi-x-octagon-fill',
        'LOW-STOCK' => 'bi-exclamation-triangle-fill',
        'INACTIVE' => 'bi-eye-slash-fill',
        default => 'bi-info-circle-fill',
    };
    return '<span class="status-badge status-' . e(strtolower($normalized)) . '"><i class="bi ' . e($icon) . '" aria-hidden="true"></i>' . e(status_label($status)) . '</span>';
}

function pet_image_url(?string $image): string
{
    $image = trim((string) $image);
    if ($image === '') {
        return asset_url('images/pet-placeholder.svg');
    }
    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }
    return asset_url('images/' . ltrim($image, '/'));
}

function pet_image_fallback(): string
{
    return asset_url('images/pet-placeholder.svg');
}

function product_image_fallback(): string
{
    return asset_url('images/product-placeholder.svg');
}

function product_image_url(?string $image): string
{
    $image = trim((string) $image);
    if ($image === '') {
        return asset_url('images/product-placeholder.svg');
    }
    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }
    return asset_url('images/' . ltrim($image, '/'));
}

function product_discount_percent(array $product): float
{
    $discount = (float) ($product['discount'] ?? 0);
    return max(0.0, min(100.0, $discount));
}

function product_price(array $product): float
{
    $price = max(0.0, (float) ($product['price'] ?? 0));
    return $price * (1 - (product_discount_percent($product) / 100));
}

function product_effective_status(array $product): string
{
    $status = strtoupper(trim((string) ($product['status'] ?? 'Active')));
    if ($status === 'INACTIVE') {
        return 'Inactive';
    }
    return (int) ($product['stock_quantity'] ?? 0) <= 0 ? 'Out of Stock' : 'Active';
}

function product_stock_label(array $product): string
{
    $stock = max(0, (int) ($product['stock_quantity'] ?? 0));
    $threshold = max(0, (int) ($product['low_stock_threshold'] ?? 0));
    if ($stock === 0) {
        return 'Out of stock';
    }
    if ($stock <= $threshold) {
        return 'Only ' . $stock . ' left';
    }
    return 'In stock';
}

function product_status_badge(array $product): string
{
    $status = strtoupper(trim((string) ($product['status'] ?? 'Active')));
    if ($status === 'INACTIVE') {
        return status_badge('Inactive');
    }
    $stock = max(0, (int) ($product['stock_quantity'] ?? 0));
    $threshold = max(0, (int) ($product['low_stock_threshold'] ?? 0));
    if ($stock > 0 && $stock <= $threshold) {
        return status_badge('Low stock');
    }
    return status_badge(product_effective_status($product));
}

function store_currency(): string
{
    return 'LKR';
}

function format_money(float $amount, string $currency = 'LKR'): string
{
    $currency = strtoupper(trim($currency)) ?: 'LKR';
    $symbols = ['LKR' => 'Rs. ', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => 'Rs. ', 'AUD' => 'A$', 'CAD' => 'C$'];
    $prefix = $symbols[$currency] ?? ($currency . ' ');
    return $prefix . number_format($amount, 2);
}

function product_brand(array $product): string
{
    $brand = trim((string) ($product['brand'] ?? ''));
    if ($brand !== '') {
        return $brand;
    }
    if (preg_match('/^(.+?)\s+[-–—]/u', trim((string) ($product['name'] ?? '')), $matches)) {
        return trim($matches[1]);
    }
    return 'PAWORA';
}

function product_currency(array $product): string
{
    $currency = strtoupper(trim((string) ($product['currency'] ?? 'LKR')));
    return preg_match('/^[A-Z]{3}$/', $currency) ? $currency : 'LKR';
}

function product_price_type(array $product): string
{
    return strtoupper(trim((string) ($product['price_type'] ?? 'DEMO'))) === 'SOURCE' ? 'SOURCE' : 'DEMO';
}

function product_is_demo_price(array $product): bool
{
    return product_price_type($product) === 'DEMO';
}

function product_price_source(array $product): string
{
    return trim((string) ($product['price_source'] ?? ''));
}

function product_price_note(array $product): string
{
    if (product_is_demo_price($product)) {
        return 'Prototype price for demonstration only. Not the official retail price.';
    }
    $source = product_price_source($product);
    return $source !== '' ? 'Price recorded from ' . $source . '.' : 'Price recorded from the product source.';
}

function product_rating(array $product): ?float
{
    if (!array_key_exists('rating', $product) || $product['rating'] === null || $product['rating'] === '') {
        return null;
    }
    $rating = round((float) $product['rating'], 1);
    return ($rating >= 0 && $rating <= 5) ? $rating : null;
}

function product_review_count(array $product): ?int
{
    if (!array_key_exists('review_count', $product) || $product['review_count'] === null || $product['review_count'] === '') {
        return null;
    }
    $count = (int) $product['review_count'];
    return $count > 0 ? $count : null;
}

function product_image_alt(array $product): string
{
    $alt = trim((string) ($product['image_alt'] ?? ''));
    if ($alt !== '') {
        return $alt;
    }
    $brand = product_brand($product);
    $name = trim((string) ($product['name'] ?? ''));
    return $name === '' ? $brand : $brand . ' ' . $name;
}

function product_display_image(array $product): string
{
    $image = trim((string) ($product['image'] ?? ''));
    if ($image !== '') {
        return product_image_url($image);
    }
    $external = trim((string) ($product['image_url'] ?? ''));
    if ($external !== '' && preg_match('#^https?://#i', $external)) {
        return $external;
    }
    return product_image_fallback();
}

function product_image_data_fallback(array $product): string
{
    return product_image_url(trim((string) ($product['image'] ?? '')) !== '' ? (string) $product['image'] : product_image_fallback());
}

function product_source_url(array $product): string
{
    $url = trim((string) ($product['source_url'] ?? ''));
    return preg_match('#^https?://#i', $url) ? $url : '';
}

function product_image_source_name(array $product): string
{
    return trim((string) ($product['image_source_name'] ?? ''));
}

function product_is_store_brand(array $product): bool
{
    return strcasecmp(product_brand($product), 'PAWORA') === 0;
}

function product_rating_stars(?float $rating): string
{
    if ($rating === null) {
        return '';
    }
    $full = (int) floor($rating);
    $half = ($rating - $full) >= 0.5;
    $marks = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full) {
            $marks .= '<i class="bi bi-star-fill" aria-hidden="true"></i>';
        } elseif ($half && $i === $full + 1) {
            $marks .= '<i class="bi bi-star-half" aria-hidden="true"></i>';
        } else {
            $marks .= '<i class="bi bi-star" aria-hidden="true"></i>';
        }
    }
    return $marks;
}

function product_rating_summary(array $product): string
{
    $rating = product_rating($product);
    $reviews = product_review_count($product);
    if ($rating === null && $reviews === null) {
        return '';
    }
    $label = $rating !== null ? number_format($rating, 1) . ' out of 5' : 'Rated';
    if ($reviews !== null) {
        $label .= ' (' . $reviews . ' ' . ($reviews === 1 ? 'review' : 'reviews') . ')';
    }
    return $label;
}

function catalog_brands(bool $includeInactive = false): array
{
    try {
        $sql = "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand <> ''";
        if (!$includeInactive) {
            $sql .= " AND status = 'Active'";
        }
        $sql .= ' ORDER BY brand';
        $brands = [];
        foreach (db_all($sql) as $row) {
            $brands[] = (string) $row['brand'];
        }
        return $brands;
    } catch (Throwable $exception) {
        return [];
    }
}

function is_product_favorite(int $productId): bool
{
    if (!is_logged_in() || $productId <= 0) {
        return false;
    }
    try {
        $statement = db()->prepare('SELECT 1 FROM product_favorites WHERE user_id = ? AND product_id = ? LIMIT 1');
        $statement->execute([(int) $_SESSION['user_id'], $productId]);
        return (bool) $statement->fetchColumn();
    } catch (Throwable $exception) {
        return false;
    }
}

function product_favorite_button_label(int $productId): string
{
    return is_product_favorite($productId) ? 'Saved to wishlist' : 'Add to wishlist';
}

function product_notify_count(int $productId): int
{
    try {
        $statement = db()->prepare('SELECT COUNT(*) FROM product_waiting_list WHERE product_id = ?');
        $statement->execute([$productId]);
        return (int) $statement->fetchColumn();
    } catch (Throwable $exception) {
        return 0;
    }
}

function order_statuses(): array
{
    return ['PENDING', 'PAID', 'PROCESSING', 'SHIPPED', 'DELIVERED', 'CANCELLED'];
}

function payment_statuses(): array
{
    return ['PENDING', 'PAID', 'FAILED', 'REFUNDED'];
}

function payment_method_label(string $method): string
{
    return match (strtolower(trim($method))) {
        'demo_card' => 'Demo card',
        'cash_on_delivery' => 'Cash on delivery',
        default => ucwords(strtolower(trim($method))),
    };
}

function payment_status_badge(?string $status): string
{
    return status_badge($status !== null && $status !== '' ? $status : 'PENDING');
}

function generate_reference(string $prefix): string
{
    return $prefix . '-' . date('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
}

function notify(int $userId, string $title, string $message): void
{
    $statement = db()->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
    $statement->execute([$userId, clip_text($title, 180), $message]);
}

function add_admin_log(int $adminId, string $action, string $description): void
{
    $statement = db()->prepare('INSERT INTO admin_logs (admin_id, action, description) VALUES (?, ?, ?)');
    $statement->execute([$adminId, clip_text($action, 100), $description]);
}

function get_pet(int $id): ?array
{
    $statement = db()->prepare('SELECT * FROM pets WHERE id = ? LIMIT 1');
    $statement->execute([$id]);
    $pet = $statement->fetch();
    return $pet ?: null;
}

function is_favorite(int $petId): bool
{
    if (!is_logged_in()) {
        return false;
    }
    try {
        $statement = db()->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND pet_id = ? LIMIT 1');
        $statement->execute([(int) $_SESSION['user_id'], $petId]);
        return (bool) $statement->fetchColumn();
    } catch (Throwable $exception) {
        return false;
    }
}

function favorite_count(): int
{
    if (!is_logged_in()) {
        return 0;
    }
    try {
        $statement = db()->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ?');
        $statement->execute([(int) $_SESSION['user_id']]);
        return (int) $statement->fetchColumn();
    } catch (Throwable $exception) {
        return 0;
    }
}

function unread_notification_count(): int
{
    if (!is_logged_in()) {
        return 0;
    }
    try {
        $statement = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $statement->execute([(int) $_SESSION['user_id']]);
        return (int) $statement->fetchColumn();
    } catch (Throwable $exception) {
        return 0;
    }
}

function application_statuses(): array
{
    return ['SUBMITTED', 'UNDER REVIEW', 'INTERVIEW', 'APPROVED', 'REJECTED', 'ADOPTION COMPLETED'];
}

function application_progress(string $status): int
{
    return match (strtoupper($status)) {
        'SUBMITTED' => 18,
        'UNDER REVIEW' => 38,
        'INTERVIEW' => 58,
        'APPROVED' => 78,
        'ADOPTION COMPLETED' => 100,
        'REJECTED' => 100,
        default => 10,
    };
}

function flash_type_icon(string $type): string
{
    return match ($type) {
        'success' => 'bi-check-circle-fill',
        'error' => 'bi-exclamation-octagon-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        default => 'bi-info-circle-fill',
    };
}

function csrf_valid_or_fail(): void
{
    if (!verify_csrf()) {
        set_flash('error', 'Your form session expired. Please try again.');
        redirect($_SERVER['PHP_SELF'] ?? 'index.php');
    }
}
