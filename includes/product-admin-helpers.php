<?php
declare(strict_types=1);

require_once __DIR__ . '/data.php';

function product_admin_input(string $key, mixed $fallback = ''): mixed
{
    $value = array_key_exists($key, $_POST) ? $_POST[$key] : $fallback;
    return is_scalar($value) ? $value : '';
}

function product_admin_form_values(array $product = []): array
{
    $status = strtoupper(trim((string) product_admin_input('status', $product['status'] ?? 'Active')));
    $priceType = strtoupper(trim((string) product_admin_input('price_type', $product['price_type'] ?? 'DEMO')));
    $stockType = strtoupper(trim((string) product_admin_input('stock_type', $product['stock_type'] ?? 'DEMO')));
    $ratingType = strtoupper(trim((string) product_admin_input('rating_type', $product['rating_type'] ?? 'DEMO')));

    return [
        'category_id' => trim((string) product_admin_input('category_id', $product['category_id'] ?? '')),
        'brand' => trim((string) product_admin_input('brand', $product['brand'] ?? '')),
        'name' => trim((string) product_admin_input('name', $product['name'] ?? '')),
        'description' => trim((string) product_admin_input('description', $product['description'] ?? '')),
        'price' => trim((string) product_admin_input('price', $product['price'] ?? '')),
        'currency' => strtoupper(trim((string) product_admin_input('currency', $product['currency'] ?? 'LKR'))),
        'price_type' => $priceType === 'SOURCE' ? 'SOURCE' : 'DEMO',
        'price_source' => trim((string) product_admin_input('price_source', $product['price_source'] ?? '')),
        'source_price' => trim((string) product_admin_input('source_price', $product['source_price'] ?? '')),
        'source_currency' => strtoupper(trim((string) product_admin_input('source_currency', $product['source_currency'] ?? ''))),
        'source_exchange_rate' => trim((string) product_admin_input('source_exchange_rate', $product['source_exchange_rate'] ?? '')),
        'price_checked_on' => trim((string) product_admin_input('price_checked_on', $product['price_checked_on'] ?? '')),
        'discount' => trim((string) product_admin_input('discount', $product['discount'] ?? '0')),
        'sku' => strtoupper(trim((string) product_admin_input('sku', $product['sku'] ?? ''))),
        'stock_quantity' => trim((string) product_admin_input('stock_quantity', $product['stock_quantity'] ?? '0')),
        'low_stock_threshold' => trim((string) product_admin_input('low_stock_threshold', $product['low_stock_threshold'] ?? '5')),
        'stock_type' => $stockType === 'SOURCE' ? 'SOURCE' : 'DEMO',
        'stock_source' => trim((string) product_admin_input('stock_source', $product['stock_source'] ?? '')),
        'rating' => trim((string) product_admin_input('rating', $product['rating'] ?? '')),
        'review_count' => trim((string) product_admin_input('review_count', $product['review_count'] ?? '')),
        'rating_type' => in_array($ratingType, ['DEMO', 'SOURCE'], true) ? $ratingType : 'DEMO',
        'image' => trim((string) ($product['image'] ?? '')),
        'image_url' => trim((string) product_admin_input('image_url', '')),
        'image_alt' => trim((string) product_admin_input('image_alt', $product['image_alt'] ?? '')),
        'source_url' => trim((string) product_admin_input('source_url', $product['source_url'] ?? '')),
        'image_source_name' => trim((string) product_admin_input('image_source_name', $product['image_source_name'] ?? '')),
        'image_source_url' => trim((string) product_admin_input('image_source_url', $product['image_source_url'] ?? '')),
        'remove_image' => (int) (bool) product_admin_input('remove_image', 0),
        'status' => match ($status) {
            'INACTIVE' => 'Inactive',
            'ACTIVE', 'OUT OF STOCK' => 'Active',
            default => '',
        },
    ];
}

function product_admin_decimal(string $value): string
{
    if (!preg_match('/^(\d{1,8})(?:\.(\d{1,2}))?$/', $value, $matches)) {
        return '';
    }
    return $matches[1] . '.' . str_pad($matches[2] ?? '', 2, '0');
}

function product_admin_rate(string $value): string
{
    if (!preg_match('/^(\d{1,10})(?:\.(\d{1,4}))?$/', $value, $matches)) {
        return '';
    }
    return $matches[1] . '.' . str_pad($matches[2] ?? '', 4, '0');
}

function product_admin_optional_https_url(string $value, int $maxLength = 500): bool
{
    if ($value === '') {
        return true;
    }
    return text_length($value) <= $maxLength
        && filter_var($value, FILTER_VALIDATE_URL) !== false
        && (bool) preg_match('#^https?://#i', $value);
}

function product_admin_default_image_alt(string $brand, string $name): string
{
    $label = trim($brand . ' ' . $name);
    $alt = $label === '' ? 'Pet product' : $label . ' product photo';
    return clip_text($alt, 160);
}

function validate_product_values(array $values): array
{
    $errors = [];
    $nameLength = text_length($values['name']);
    if ($nameLength < 2 || $nameLength > 160) {
        $errors[] = 'Enter a product name between 2 and 160 characters.';
    }
    $brandLength = text_length($values['brand']);
    if ($brandLength < 2 || $brandLength > 80) {
        $errors[] = 'Enter a brand name between 2 and 80 characters.';
    }
    if (!in_array($values['status'], ['Active', 'Inactive'], true)) {
        $errors[] = 'Choose a valid product visibility.';
    }
    if (!preg_match('/^[A-Z]{3}$/', $values['currency'])) {
        $errors[] = 'Use a three-letter currency code, for example LKR or USD.';
    }
    if (!preg_match('/^\d{1,10}$/', $values['stock_quantity']) || (int) $values['stock_quantity'] > 4294967295) {
        $errors[] = 'Enter a valid stock quantity.';
    }
    if (!preg_match('/^\d{1,10}$/', $values['low_stock_threshold']) || (int) $values['low_stock_threshold'] > 4294967295) {
        $errors[] = 'Enter a valid low-stock threshold.';
    }
    $price = product_admin_decimal($values['price']);
    if ($price === '') {
        $errors[] = 'Enter a valid price with no more than two decimal places.';
    }
    $discount = product_admin_decimal($values['discount']);
    if ($discount === '' || (float) $discount < 0 || (float) $discount > 100) {
        $errors[] = 'Enter a discount between 0 and 100.';
    }
    if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{0,49}$/', $values['sku'])) {
        $errors[] = 'Use a SKU with letters, numbers, hyphens or underscores, up to 50 characters.';
    }
    if (!preg_match('/^\d+$/', $values['category_id']) || (int) $values['category_id'] < 1) {
        $errors[] = 'Choose a product category.';
    }
    $descriptionLength = text_length($values['description']);
    if ($descriptionLength < 20 || $descriptionLength > 10000) {
        $errors[] = 'Add a product description between 20 and 10,000 characters.';
    }
    if ($values['image_url'] !== '' && $values['remove_image'] === 0) {
        if (text_length($values['image_url']) > 500 || !filter_var($values['image_url'], FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $values['image_url'])) {
            $errors[] = 'Use a valid JPG, PNG or WEBP upload or an HTTP/HTTPS image URL.';
        }
    }
    if (text_length($values['image_alt']) > 160) {
        $errors[] = 'Keep the image alt text under 160 characters.';
    }
    foreach (['source_url' => 'product source page', 'image_source_url' => 'image source page'] as $field => $label) {
        if (!product_admin_optional_https_url($values[$field])) {
            $errors[] = 'The ' . $label . ' must be a valid HTTP/HTTPS link.';
        }
    }
    if (text_length($values['image_source_name']) > 120) {
        $errors[] = 'Keep the image source name under 120 characters.';
    }
    if ($values['price_source'] !== '' && !product_admin_optional_https_url($values['price_source'])) {
        $errors[] = 'The price source must be a valid HTTP/HTTPS link or a short text reference.';
    }
    if ($values['price_type'] === 'SOURCE' && $values['price_source'] === '') {
        $errors[] = 'A source price needs a source link or reference so the price stays traceable.';
    }
    if ($values['source_price'] !== '' && product_admin_decimal($values['source_price']) === '') {
        $errors[] = 'Enter the original source price with no more than two decimal places.';
    }
    if ($values['source_price'] !== '' && !preg_match('/^[A-Z]{3}$/', $values['source_currency'])) {
        $errors[] = 'A source price needs the three-letter currency it was quoted in.';
    }
    $rate = $values['source_exchange_rate'];
    if ($rate !== '' && product_admin_rate($rate) === '') {
        $errors[] = 'Enter the exchange rate with up to four decimal places, for example 305.2500.';
    }
    if ($rate !== '' && (float) $rate <= 0) {
        $errors[] = 'The exchange rate must be greater than zero.';
    }
    if ($values['source_currency'] !== '' && $values['source_currency'] !== $values['currency'] && $rate === '') {
        $errors[] = 'Converting a source price needs the exchange rate used for the displayed price.';
    }
    if ($values['price_checked_on'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $values['price_checked_on'])) {
        $errors[] = 'Enter the price check date as YYYY-MM-DD.';
    }
    if ($values['stock_type'] === 'SOURCE' && $values['stock_source'] === '') {
        $errors[] = 'Live stock figures need a source reference, such as the supplier page you checked.';
    }
    if (text_length($values['stock_source']) > 160) {
        $errors[] = 'Keep the stock source reference under 160 characters.';
    }
    $rating = $values['rating'];
    if ($rating !== '' && !preg_match('/^\d(?:\.\d)?$/', $rating)) {
        $errors[] = 'Enter a rating between 0 and 5 with one decimal place.';
    }
    if ($rating !== '' && (float) $rating > 5) {
        $errors[] = 'Enter a rating between 0 and 5.';
    }
    if ($values['review_count'] !== '' && !preg_match('/^\d{1,9}$/', $values['review_count'])) {
        $errors[] = 'Enter a review count as a whole number.';
    }
    if ($rating === '' && $values['review_count'] !== '') {
        $errors[] = 'A review count needs a matching rating value.';
    }
    if ($rating !== '' && $values['rating_type'] === '') {
        $errors[] = 'Mark whether the rating comes from a source or is demo data.';
    }
    return $errors;
}

function get_product_admin(int $id): ?array
{
    try {
        $statement = db()->prepare('SELECT p.*, c.name AS category_name, c.status AS category_status FROM products p INNER JOIN categories c ON c.id = p.category_id WHERE p.id = ? LIMIT 1');
        $statement->execute([$id]);
        $product = $statement->fetch();
        return $product ?: null;
    } catch (Throwable $exception) {
        $GLOBALS['pawora_database_error'] = 'PAWORA is not connected to MySQL yet. Import database/pawora_db.sql and check config/database.php.';
        return null;
    }
}

function product_admin_image_path(?string $image): ?string
{
    $image = trim((string) $image);
    if (!preg_match('#^products/([A-Za-z0-9._-]+)$#', $image, $matches)) {
        return null;
    }
    return __DIR__ . '/../assets/images/products/' . $matches[1];
}

function product_admin_image_is_shared(string $image, ?int $exceptId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM products WHERE image = ?';
    $params = [$image];
    if ($exceptId !== null) {
        $sql .= ' AND id <> ?';
        $params[] = $exceptId;
    }
    try {
        return (int) db_value($sql, $params) > 0;
    } catch (Throwable $exception) {
        return true;
    }
}

function remove_local_product_image(?string $image, ?int $exceptId = null): void
{
    $path = product_admin_image_path($image);
    if ($path === null || !is_file($path)) {
        return;
    }
    // A local path can be shared - the placeholder is used by every house product - so only
    // unlink a file once no other product row still points at it. If the check cannot run we
    // keep the file rather than risk breaking another product's image.
    if (product_admin_image_is_shared((string) $image, $exceptId)) {
        return;
    }
    @unlink($path);
}

function upload_product_image(array &$errors): ?string
{
    if (!isset($_FILES['image']) || !is_array($_FILES['image']) || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES['image'];
    if (is_array($file['error'] ?? null)) {
        $errors[] = 'Please choose a single product image.';
        return null;
    }
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $errors[] = upload_error_message($uploadError);
        return null;
    }
    if (!isset($file['tmp_name']) || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'The uploaded product image could not be verified.';
        return null;
    }
    if (($file['size'] ?? 0) < 1) {
        $errors[] = 'The selected product image is empty. Please choose another file.';
        return null;
    }
    if (($file['size'] ?? 0) > upload_limit_bytes()) {
        $errors[] = 'Product images must be smaller than ' . human_bytes(upload_limit_bytes()) . '.';
        return null;
    }
    try {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $dimensions = @getimagesize($file['tmp_name']);
    } catch (Throwable $exception) {
        $mime = false;
        $dimensions = false;
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime]) || !is_array($dimensions) || ($dimensions[0] ?? 0) < 1 || ($dimensions[1] ?? 0) < 1) {
        $errors[] = 'Upload a valid JPG, PNG or WEBP image. Photos stored as HEIC/HEIF on a phone must be saved as JPG first.';
        return null;
    }
    $directory = upload_directory(__DIR__ . '/../assets/images/products');
    $directoryError = upload_directory_error($directory);
    if ($directoryError !== null) {
        $errors[] = $directoryError;
        return null;
    }
    try {
        $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    } catch (Throwable $exception) {
        $errors[] = 'We could not generate a secure product image name.';
        return null;
    }
    $target = $directory . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        $errors[] = 'We could not save the uploaded product image to ' . $target
            . '. Check the folder permissions and the server upload limits, then try again.';
        return null;
    }
    @chmod($target, 0644);
    return 'products/' . $filename;
}

function save_product(?int $id = null): array
{
    $pdo = null;
    $newImage = null;
    $existing = [];
    $values = product_admin_form_values();
    try {
        $pdo = db();
        $pdo->beginTransaction();

        if ($id !== null) {
            $product = $pdo->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
            $product->execute([$id]);
            $existing = $product->fetch() ?: [];
            if ($existing === []) {
                $pdo->rollBack();
                return ['errors' => ['That product could not be found.'], 'values' => product_admin_form_values()];
            }
        }

        $values = product_admin_form_values($existing);
        if ($values['image_alt'] === '') {
            $values['image_alt'] = product_admin_default_image_alt($values['brand'], $values['name']);
        }
        $errors = validate_product_values($values);
        $oldImage = (string) ($existing['image'] ?? '');
        $oldImageUrl = (string) ($existing['image_url'] ?? '');
        $image = $oldImage;
        $imageUrl = $oldImageUrl;
        // image_url doubles as the attribution link for a locally stored photo, so the form always
        // resubmits it unchanged. Only treat it as a replacement when the value really changed,
        // otherwise every unrelated edit would swap the local pack shot for a remote hot link.
        $imageUrlChanged = $values['image_url'] !== '' && $values['image_url'] !== $oldImageUrl;
        if (!$errors && $values['remove_image'] === 1) {
            $image = '';
            $imageUrl = '';
        } elseif (!$errors && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $newImage = upload_product_image($errors);
            if ($newImage !== null) {
                $image = $newImage;
                $imageUrl = '';
            }
        } elseif (!$errors && $imageUrlChanged) {
            $image = $values['image_url'];
            $imageUrl = $values['image_url'];
        }
        if ($errors) {
            $pdo->rollBack();
            if ($newImage !== null) {
                remove_local_product_image($newImage);
            }
            return ['errors' => $errors, 'values' => $values];
        }

        $category = $pdo->prepare('SELECT id FROM categories WHERE id = ? FOR UPDATE');
        $category->execute([(int) $values['category_id']]);
        if (!$category->fetchColumn()) {
            $pdo->rollBack();
            if ($newImage !== null) {
                remove_local_product_image($newImage);
            }
            return ['errors' => ['Choose a valid product category.'], 'values' => $values];
        }

        $sourcePrice = $values['source_price'] !== '' ? product_admin_decimal($values['source_price']) : null;
        $exchangeRate = $values['source_exchange_rate'] !== '' ? product_admin_rate($values['source_exchange_rate']) : null;
        $ratingValue = $values['rating'] !== '' ? (string) (float) $values['rating'] : null;
        $reviewCount = $values['review_count'] !== '' ? (int) $values['review_count'] : null;
        $priceCheckedOn = $values['price_checked_on'] !== '' ? $values['price_checked_on'] : null;
        $nullIfEmpty = static function (string $value): ?string {
            return $value === '' ? null : $value;
        };

        $productColumns = [
            'category_id', 'brand', 'name', 'description', 'price', 'currency', 'price_type', 'price_source',
            'source_price', 'source_currency', 'source_exchange_rate', 'price_checked_on', 'discount', 'sku',
            'stock_quantity', 'low_stock_threshold', 'stock_type', 'stock_source', 'rating', 'review_count',
            'rating_type', 'image', 'image_url', 'image_alt', 'source_url', 'image_source_name', 'image_source_url', 'status',
        ];
        $productValues = [
            (int) $values['category_id'],
            $nullIfEmpty($values['brand']),
            $values['name'],
            $values['description'],
            product_admin_decimal($values['price']),
            $values['currency'],
            $values['price_type'],
            $nullIfEmpty($values['price_source']),
            $sourcePrice,
            $nullIfEmpty($values['source_currency']),
            $exchangeRate,
            $priceCheckedOn,
            product_admin_decimal($values['discount']),
            $values['sku'],
            (int) $values['stock_quantity'],
            (int) $values['low_stock_threshold'],
            $values['stock_type'],
            $nullIfEmpty($values['stock_source']),
            $ratingValue,
            $reviewCount,
            $values['rating'] !== '' ? $values['rating_type'] : null,
            $image !== '' ? $image : null,
            $imageUrl !== '' ? $imageUrl : null,
            $nullIfEmpty($values['image_alt']),
            $nullIfEmpty($values['source_url']),
            $nullIfEmpty($values['image_source_name']),
            $nullIfEmpty($values['image_source_url']),
            $values['status'],
        ];

        if ($id !== null) {
            $assignments = implode(', ', array_map(static fn (string $column): string => $column . ' = ?', $productColumns));
            $statement = $pdo->prepare('UPDATE products SET ' . $assignments . ' WHERE id = ?');
            $statement->execute(array_merge($productValues, [$id]));
            add_admin_log((int) $_SESSION['user_id'], 'UPDATE', 'Updated product: ' . $values['name'] . ' (' . $values['sku'] . ')');
        } else {
            $placeholders = implode(', ', array_fill(0, count($productColumns), '?'));
            $statement = $pdo->prepare('INSERT INTO products (' . implode(', ', $productColumns) . ') VALUES (' . $placeholders . ')');
            $statement->execute($productValues);
            $id = (int) $pdo->lastInsertId();
            add_admin_log((int) $_SESSION['user_id'], 'CREATE', 'Created product: ' . $values['name'] . ' (' . $values['sku'] . ')');
        }
        $pdo->commit();
        if ($oldImage !== '' && $oldImage !== $image) {
            remove_local_product_image($oldImage);
        }
        return ['errors' => [], 'id' => $id];
    } catch (Throwable $exception) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($newImage !== null) {
            remove_local_product_image($newImage);
        }
        $message = ($exception instanceof PDOException && (string) $exception->getCode() === '23000')
            ? 'That SKU is already assigned to another product.'
            : 'We could not save this product right now.';
        return ['errors' => [$message], 'values' => $values];
    }
}

function set_product_visibility(int $id, string $status): array
{
    $status = $status === 'Inactive' ? 'Inactive' : 'Active';
    try {
        $pdo = db();
        $pdo->beginTransaction();
        $product = $pdo->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
        $product->execute([$id]);
        $row = $product->fetch();
        if (!$row) {
            $pdo->rollBack();
            return ['errors' => ['That product could not be found.']];
        }
        $update = $pdo->prepare('UPDATE products SET status = ? WHERE id = ?');
        $update->execute([$status, $id]);
        add_admin_log((int) $_SESSION['user_id'], 'PRODUCT_STATUS', ($status === 'Inactive' ? 'Deactivated product ' : 'Activated product ') . $row['name'] . ' (' . $row['sku'] . ')');
        $pdo->commit();
        return ['errors' => []];
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['errors' => ['We could not update product visibility right now.']];
    }
}

function delete_product(int $id): array
{
    try {
        $pdo = db();
        $pdo->beginTransaction();
        $product = $pdo->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
        $product->execute([$id]);
        $row = $product->fetch();
        if (!$row) {
            $pdo->rollBack();
            return ['errors' => ['That product could not be found.']];
        }
        $delete = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $delete->execute([$id]);
        add_admin_log((int) $_SESSION['user_id'], 'DELETE', 'Deleted product: ' . $row['name'] . ' (' . $row['sku'] . ')');
        $pdo->commit();
        remove_local_product_image((string) $row['image']);
        return ['errors' => []];
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['errors' => ['This product could not be deleted. Deactivate it instead if it is referenced elsewhere.']];
    }
}

function adjust_product_stock(int $id, int $delta): array
{
    if ($delta === 0 || $delta < -4294967295 || $delta > 4294967295) {
        return ['errors' => ['Enter a stock adjustment other than zero.']];
    }
    try {
        $pdo = db();
        $pdo->beginTransaction();
        $product = $pdo->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
        $product->execute([$id]);
        $row = $product->fetch();
        if (!$row) {
            $pdo->rollBack();
            return ['errors' => ['That product could not be found.']];
        }
        $oldStock = (int) $row['stock_quantity'];
        $newStock = $oldStock + $delta;
        if ($newStock < 0 || $newStock > 4294967295) {
            $pdo->rollBack();
            return ['errors' => ['The adjustment would put stock below zero or above the supported limit.']];
        }
        $update = $pdo->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?');
        $update->execute([$newStock, $id]);
        add_admin_log((int) $_SESSION['user_id'], 'STOCK_ADJUSTMENT', 'Adjusted stock for ' . $row['name'] . ' (' . $row['sku'] . '): ' . $oldStock . ' to ' . $newStock);
        $pdo->commit();
        return ['errors' => [], 'old_stock' => $oldStock, 'new_stock' => $newStock];
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['errors' => ['We could not adjust stock right now.']];
    }
}

function category_admin_form_values(array $category = []): array
{
    $status = strtoupper(trim((string) product_admin_input('status', $category['status'] ?? 'Active')));

    return [
        'name' => trim((string) product_admin_input('name', $category['name'] ?? '')),
        'status' => match ($status) {
            'INACTIVE' => 'Inactive',
            'ACTIVE' => 'Active',
            default => '',
        },
    ];
}

function category_admin_slug(string $name): string
{
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    return substr($slug, 0, 120);
}

function save_category(?int $id = null): array
{
    $pdo = null;
    $existing = [];
    $values = category_admin_form_values();
    try {
        $pdo = db();
        $pdo->beginTransaction();
        if ($id !== null) {
            $statement = $pdo->prepare('SELECT * FROM categories WHERE id = ? FOR UPDATE');
            $statement->execute([$id]);
            $existing = $statement->fetch() ?: [];
            if ($existing === []) {
                $pdo->rollBack();
                return ['errors' => ['That category could not be found.'], 'values' => category_admin_form_values()];
            }
        }

        $values = category_admin_form_values($existing);
        $errors = [];
        if (text_length($values['name']) < 2 || text_length($values['name']) > 100) {
            $errors[] = 'Enter a category name between 2 and 100 characters.';
        }
        if (!in_array($values['status'], ['Active', 'Inactive'], true)) {
            $errors[] = 'Choose a valid category visibility.';
        }
        $slug = category_admin_slug($values['name']);
        if ($slug === '') {
            $errors[] = 'Enter a category name that can be converted into a URL slug.';
        }
        if ($errors) {
            $pdo->rollBack();
            return ['errors' => $errors, 'values' => $values];
        }

        $duplicate = $pdo->prepare('SELECT id FROM categories WHERE (name = ? OR slug = ?) AND id <> ? LIMIT 1 FOR UPDATE');
        $duplicate->execute([$values['name'], $slug, $id ?? 0]);
        if ($duplicate->fetchColumn()) {
            $pdo->rollBack();
            return ['errors' => ['A category with that name or slug already exists.'], 'values' => $values];
        }
        if ($id !== null) {
            $statement = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, status = ? WHERE id = ?');
            $statement->execute([$values['name'], $slug, $values['status'], $id]);
            add_admin_log((int) $_SESSION['user_id'], 'UPDATE', 'Updated product category: ' . $values['name']);
        } else {
            $statement = $pdo->prepare('INSERT INTO categories (name, slug, status) VALUES (?, ?, ?)');
            $statement->execute([$values['name'], $slug, $values['status']]);
            $id = (int) $pdo->lastInsertId();
            add_admin_log((int) $_SESSION['user_id'], 'CREATE', 'Created product category: ' . $values['name']);
        }
        $pdo->commit();
        return ['errors' => [], 'id' => $id];
    } catch (Throwable $exception) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = ($exception instanceof PDOException && (string) $exception->getCode() === '23000') ? 'A category with that name or slug already exists.' : 'We could not save this category right now.';
        return ['errors' => [$message], 'values' => $values];
    }
}

function delete_category(int $id): array
{
    try {
        $pdo = db();
        $pdo->beginTransaction();
        $category = $pdo->prepare('SELECT * FROM categories WHERE id = ? FOR UPDATE');
        $category->execute([$id]);
        $row = $category->fetch();
        if (!$row) {
            $pdo->rollBack();
            return ['errors' => ['That category could not be found.']];
        }
        $count = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $count->execute([$id]);
        if ((int) $count->fetchColumn() > 0) {
            $pdo->rollBack();
            return ['errors' => ['This category contains products. Deactivate it instead of deleting it.']];
        }
        $delete = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $delete->execute([$id]);
        add_admin_log((int) $_SESSION['user_id'], 'DELETE', 'Deleted product category: ' . $row['name']);
        $pdo->commit();
        return ['errors' => []];
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['errors' => ['This category could not be deleted.']];
    }
}
