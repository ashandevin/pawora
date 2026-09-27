<?php
declare(strict_types=1);

/**
 * PAWORA catalog importer.
 *
 * Usage:
 *   php database/catalog/import.php            # validate only (default, writes nothing)
 *   php database/catalog/import.php --apply    # validate, then upsert categories and products
 *   php database/catalog/import.php --apply --category "Dog Food"   # one category at a time
 *
 * Reads categories.sql and products.sql from this folder and upserts them. Products are
 * matched on SKU, so re-running never duplicates a row. Only the rows in products.sql are
 * validated, which is what allows one researched category to be imported at a time. Rows that
 * exist in the database but not in products.sql are reported as "not in file" for manual review
 * and are never deleted, because past orders still reference them.
 */

require_once __DIR__ . '/../../includes/data.php';

const CATALOG_FOLDER = __DIR__;
const PRODUCTS_PER_CATEGORY = 10;
const TARGET_TOTAL = 100;
const PAWORA_BRANDS = ['pawora', 'pawora demo'];

$apply = in_array('--apply', $argv, true);
$categoryFilter = null;
foreach ($argv as $index => $argument) {
    if ($argument === '--category' && isset($argv[$index + 1])) {
        $categoryFilter = trim((string) $argv[$index + 1]);
    }
}

$problems = [];
$warnings = [];

function catalog_fail(array &$problems, string $sku, string $message): void
{
    $problems[] = [$sku, $message];
}

function catalog_warn(array &$warnings, string $sku, string $message): void
{
    $warnings[] = [$sku, $message];
}

function catalog_text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen(utf8_decode($value) ?: $value);
}

function catalog_word_count(string $value): int
{
    $words = preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY);
    return is_array($words) ? count($words) : 0;
}

function catalog_read_sql(string $path, string $label): array
{
    if (!is_readable($path)) {
        fwrite(STDERR, "Cannot read $label ($path). Generate it first.\n");
        exit(1);
    }
    $sql = file_get_contents($path);
    if ($sql === false) {
        fwrite(STDERR, "Cannot read $label.\n");
        exit(1);
    }
    // Comments are stripped before splitting, because a comment may contain a semicolon.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';
    $statements = [];
    foreach (explode(';', $sql) as $statement) {
        $statement = trim($statement);
        // The importer owns the transaction so a failure can roll the whole file back.
        if ($statement === '' || preg_match('/^(START|BEGIN|COMMIT|ROLLBACK)\b/i', $statement)) {
            continue;
        }
        $statements[] = $statement;
    }
    return $statements;
}

// ---------------------------------------------------------------- categories
$categoryStatements = catalog_read_sql(CATALOG_FOLDER . '/categories.sql', 'categories.sql');
$categories = [];
$pdo = db();
$pdo->beginTransaction();
try {
    foreach ($categoryStatements as $statement) {
        $pdo->exec($statement);
    }
    foreach (db_all('SELECT id, name, slug, description, status FROM categories ORDER BY id') as $row) {
        $categories[(string) $row['name']] = $row;
    }
    $pdo->rollBack();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "categories.sql failed: " . $exception->getMessage() . "\n");
    exit(1);
}

if (count($categories) !== 10) {
    catalog_fail($problems, 'categories', 'The catalog must define exactly 10 categories, found ' . count($categories) . '.');
}

// ---------------------------------------------------------------- products
$productStatements = catalog_read_sql(CATALOG_FOLDER . '/products.sql', 'products.sql');

// --category really does restrict the import: each generated statement resolves its category with
// a subselect on categories.name, so the statements for other categories can be skipped exactly.
if ($categoryFilter !== null) {
    $needle = "FROM categories WHERE name = '" . str_replace("'", "''", $categoryFilter) . "'";
    $filtered = array_values(array_filter(
        $productStatements,
        static fn (string $statement): bool => stripos($statement, $needle) !== false
    ));
    if ($filtered === []) {
        fwrite(STDERR, "products.sql has no statements for category \"$categoryFilter\".\n");
        exit(1);
    }
    $productStatements = $filtered;
}

// The file is loaded into a scratch table, so validation only ever covers the rows in the file.
// Rows that are already in the database are reported separately and never silently re-validated,
// which is what makes it possible to import one researched category at a time.
$draft = [];
$pdo->beginTransaction();
try {
    foreach ($categoryStatements as $statement) {
        $pdo->exec($statement);
    }
    $pdo->exec('CREATE TEMPORARY TABLE catalog_import_scratch LIKE products');
    foreach ($productStatements as $statement) {
        $pdo->exec(preg_replace('/\bINSERT\s+INTO\s+products\b/i', 'INSERT INTO catalog_import_scratch', $statement, 1));
    }
    $draft = db_all(
        'SELECT s.*, c.name AS category_name FROM catalog_import_scratch s'
        . ' INNER JOIN categories c ON c.id = s.category_id ORDER BY c.name ASC, s.brand ASC, s.name ASC'
    );
    $pdo->exec('DROP TEMPORARY TABLE catalog_import_scratch');
    $pdo->rollBack();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'products.sql failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

if ($draft === []) {
    fwrite(STDERR, "products.sql produced no products. Nothing to validate or import.\n");
    exit(1);
}

$existingSkus = [];
foreach (db_all('SELECT id, sku, name, image, source_url FROM products') as $row) {
    $existingSkus[(string) $row['sku']] = $row;
}

// ---------------------------------------------------------------- validation
$seenSkus = [];
$seenNames = [];
$perCategory = [];
$realCount = 0;
$paworaCount = 0;

foreach ($draft as $row) {
    $sku = (string) $row['sku'];
    $name = (string) $row['name'];
    $brand = trim((string) ($row['brand'] ?? ''));
    $isPawora = in_array(strtolower($brand), PAWORA_BRANDS, true);
    $categoryName = (string) $row['category_name'];
    $perCategory[$categoryName] = ($perCategory[$categoryName] ?? 0) + 1;

    if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{0,49}$/', $sku)) {
        catalog_fail($problems, $sku, 'SKU must be letters, numbers, hyphens or underscores, up to 50 characters.');
    }
    if (isset($seenSkus[$sku])) {
        catalog_fail($problems, $sku, 'Duplicate SKU in products.sql.');
    }
    $seenSkus[$sku] = true;

    $nameKey = strtolower($categoryName . '|' . $name);
    if (isset($seenNames[$nameKey])) {
        catalog_fail($problems, $sku, 'Another product in ' . $categoryName . ' already uses the name "' . $name . '".');
    }
    $seenNames[$nameKey] = true;

    if (catalog_text_length($brand) < 2 || catalog_text_length($brand) > 80) {
        catalog_fail($problems, $sku, 'Brand must be 2 to 80 characters.');
    }

    $words = catalog_word_count((string) $row['description']);
    if ($words < 20 || $words > 50) {
        catalog_fail($problems, $sku, 'Description must be 20 to 50 words (found ' . $words . ').');
    }

    if (!preg_match('/^[A-Z]{3}$/', (string) $row['currency'])) {
        catalog_fail($problems, $sku, 'Currency must be a three-letter code.');
    }
    if ((float) $row['price'] <= 0) {
        catalog_fail($problems, $sku, 'Selling price must be greater than zero.');
    }
    if ((float) $row['discount'] < 0 || (float) $row['discount'] > 100) {
        catalog_fail($problems, $sku, 'Discount must be between 0 and 100.');
    }
    if ((int) $row['stock_quantity'] < 0) {
        catalog_fail($problems, $sku, 'Stock cannot be negative.');
    }
    if ((int) $row['stock_quantity'] === 0 && (int) $row['low_stock_threshold'] < 1) {
        catalog_warn($warnings, $sku, 'Out-of-stock product with a zero low-stock threshold.');
    }

    $priceType = strtoupper((string) $row['price_type']);
    $priceSource = trim((string) ($row['price_source'] ?? ''));
    if (!in_array($priceType, ['DEMO', 'SOURCE'], true)) {
        catalog_fail($problems, $sku, 'price_type must be DEMO or SOURCE.');
    }
    if ($priceType === 'SOURCE') {
        if ($priceSource === '') {
            catalog_fail($problems, $sku, 'A SOURCE price needs a price_source link or reference.');
        } elseif (!preg_match('#^https?://#i', $priceSource)) {
            catalog_fail($problems, $sku, 'A SOURCE price_source should be the URL of the page the price came from.');
        }
        if ($row['source_price'] === null) {
            catalog_fail($problems, $sku, 'A SOURCE price needs source_price and source_currency.');
        } else {
            $sourceCurrency = strtoupper(trim((string) ($row['source_currency'] ?? '')));
            if (!preg_match('/^[A-Z]{3}$/', $sourceCurrency)) {
                catalog_fail($problems, $sku, 'source_currency must be a three-letter code.');
            }
            if ($sourceCurrency !== strtoupper((string) $row['currency'])) {
                if ($row['source_exchange_rate'] === null) {
                    catalog_fail($problems, $sku, 'Converting ' . $sourceCurrency . ' to ' . $row['currency'] . ' needs source_exchange_rate.');
                } else {
                    $expected = round((float) $row['source_price'] * (float) $row['source_exchange_rate'], 2);
                    $tolerance = max(1.0, $expected * 0.01);
                    if (abs($expected - (float) $row['price']) > $tolerance) {
                        catalog_warn($warnings, $sku, 'Selling price ' . $row['price'] . ' ' . $row['currency']
                            . ' does not match ' . $row['source_price'] . ' ' . $sourceCurrency
                            . ' x ' . $row['source_exchange_rate'] . ' = ' . number_format($expected, 2) . '.');
                    }
                }
            }
            if (empty($row['price_checked_on'])) {
                catalog_warn($warnings, $sku, 'A SOURCE price should record price_checked_on.');
            }
        }
    } elseif ($row['source_price'] !== null) {
        catalog_fail($problems, $sku, 'A DEMO price must not carry a source_price.');
    }

    if (!in_array(strtoupper((string) $row['stock_type']), ['DEMO', 'SOURCE'], true)) {
        catalog_fail($problems, $sku, 'stock_type must be DEMO or SOURCE.');
    }
    if (strtoupper((string) $row['stock_type']) === 'SOURCE' && trim((string) ($row['stock_source'] ?? '')) === '') {
        catalog_fail($problems, $sku, 'Live stock figures need a stock_source reference.');
    }

    if ($row['rating'] !== null) {
        if ((float) $row['rating'] < 0 || (float) $row['rating'] > 5) {
            catalog_fail($problems, $sku, 'Rating must be between 0 and 5.');
        }
        if (!in_array(strtoupper((string) ($row['rating_type'] ?? '')), ['DEMO', 'SOURCE'], true)) {
            catalog_fail($problems, $sku, 'A recorded rating needs rating_type DEMO or SOURCE.');
        }
        if ($row['rating_type'] !== null && strtoupper((string) $row['rating_type']) === 'SOURCE' && $row['source_url'] === null) {
            catalog_fail($problems, $sku, 'A SOURCE rating needs the product source_url it was read from.');
        }
    } elseif ($row['review_count'] !== null) {
        catalog_fail($problems, $sku, 'review_count cannot be set without a rating.');
    }

    $image = trim((string) ($row['image'] ?? ''));
    $imageUrl = trim((string) ($row['image_url'] ?? ''));
    $alt = trim((string) ($row['image_alt'] ?? ''));
    if ($image === '') {
        catalog_fail($problems, $sku, 'Every product needs an image (external URL or assets/images/products file).');
    } elseif (str_starts_with($image, 'http')) {
        if (!preg_match('#^https?://#i', $image)) {
            catalog_fail($problems, $sku, 'Image URL must use http or https.');
        }
    } elseif (!preg_match('#^products/[A-Za-z0-9._-]+$#', $image)) {
        catalog_fail($problems, $sku, 'Local image must be stored as products/<file> inside assets/images/.');
    }
    if ($imageUrl !== '' && !preg_match('#^https?://#i', $imageUrl)) {
        catalog_fail($problems, $sku, 'image_url must be the original external image URL.');
    }
    if ($alt === '') {
        catalog_fail($problems, $sku, 'Every product needs image_alt text for screen readers.');
    } elseif (catalog_text_length($alt) > 160) {
        catalog_fail($problems, $sku, 'image_alt must be 160 characters or fewer.');
    }

    $sourceUrl = trim((string) ($row['source_url'] ?? ''));
    if ($sourceUrl !== '' && !preg_match('#^https?://#i', $sourceUrl)) {
        catalog_fail($problems, $sku, 'source_url must be an http or https link.');
    }
    $imageSourceName = trim((string) ($row['image_source_name'] ?? ''));
    $imageSourceUrl = trim((string) ($row['image_source_url'] ?? ''));
    if ($imageSourceUrl !== '' && !preg_match('#^https?://#i', $imageSourceUrl)) {
        catalog_fail($problems, $sku, 'image_source_url must be an http or https link.');
    }

    if ($isPawora) {
        $paworaCount++;
        if ($sourceUrl !== '') {
            catalog_warn($warnings, $sku, 'PAWORA house-brand products are fictional and should not carry a real product source_url.');
        }
        if ($priceType === 'SOURCE') {
            catalog_fail($problems, $sku, 'PAWORA fictional products must use DEMO prices, not SOURCE prices.');
        }
        if ($row['rating'] !== null && strtoupper((string) ($row['rating_type'] ?? '')) !== 'DEMO') {
            catalog_fail($problems, $sku, 'PAWORA fictional products can only carry DEMO ratings.');
        }
    } else {
        $realCount++;
        if ($sourceUrl === '') {
            catalog_fail($problems, $sku, 'Real branded products need the source_url of the page the details came from.');
        }
        if ($imageSourceName === '' || $imageSourceUrl === '') {
            catalog_fail($problems, $sku, 'Real branded products need image_source_name and image_source_url.');
        }
    }
}

if ($categoryFilter === null) {
    foreach ($categories as $categoryName => $category) {
        $count = $perCategory[$categoryName] ?? 0;
        if ($count !== PRODUCTS_PER_CATEGORY) {
            catalog_warn($warnings, $categoryName, 'Expected ' . PRODUCTS_PER_CATEGORY . ' products, found ' . $count . '.');
        }
    }
}
foreach (array_keys($perCategory) as $categoryName) {
    if (!isset($categories[$categoryName])) {
        catalog_fail($problems, $categoryName, 'products.sql references a category that categories.sql does not define.');
    }
}

if ($categoryFilter !== null) {
    $needle = strtolower($categoryFilter);
    $draft = array_values(array_filter($draft, static fn (array $row): bool => strtolower((string) $row['category_name']) === $needle));
    if ($draft === []) {
        fwrite(STDERR, "No products found for category \"$categoryFilter\".\n");
        exit(1);
    }
}

// ---------------------------------------------------------------- report
echo "PAWORA catalog " . ($apply ? 'IMPORT' : 'DRY RUN') . "\n";
echo str_repeat('=', 60) . "\n";
printf("  categories      : %d\n", count($categories));
printf("  products in file: %d (%d real brands, %d PAWORA)\n", count($draft), $realCount, $paworaCount);
printf("  existing rows   : %d\n", count($existingSkus));
$new = 0;
$updates = 0;
foreach ($draft as $row) {
    if (isset($existingSkus[(string) $row['sku']])) {
        $updates++;
    } else {
        $new++;
    }
}
printf("  would create    : %d\n  would update    : %d\n", $new, $updates);
echo "\n";

$missingFromFile = array_values(array_filter(
    array_keys($existingSkus),
    static fn (string $sku): bool => !isset($seenSkus[$sku])
));
if ($missingFromFile !== []) {
    echo 'In the database but not in products.sql (left untouched, review manually):' . "\n";
    foreach ($missingFromFile as $sku) {
        echo '  - ' . $sku . ' (' . $existingSkus[$sku]['name'] . ")\n";
    }
    echo "\n";
}

if ($warnings !== []) {
    echo 'WARNINGS (' . count($warnings) . ")\n";
    foreach ($warnings as [$sku, $message]) {
        echo '  ! ' . $sku . ': ' . $message . "\n";
    }
    echo "\n";
}

if ($problems !== []) {
    echo 'PROBLEMS (' . count($problems) . ') - fix these before importing' . "\n";
    foreach ($problems as [$sku, $message]) {
        echo '  x ' . $sku . ': ' . $message . "\n";
    }
    echo "\nNothing was written to the database.\n";
    exit(1);
}

echo "Validation passed.\n";

if (!$apply) {
    echo "Dry run only. Re-run with --apply to write the catalog.\n";
    exit(0);
}

$pdo = db();
$applied = 0;
try {
    $pdo->beginTransaction();
    foreach ($categoryStatements as $statement) {
        $pdo->exec($statement);
    }
    $categoryIds = [];
    foreach (db_all('SELECT id, name FROM categories') as $row) {
        $categoryIds[(string) $row['name']] = (int) $row['id'];
    }
    foreach ($productStatements as $statement) {
        $pdo->exec($statement);
    }
    $pdo->commit();
    $applied = count($draft);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "\nImport failed and was rolled back: " . $exception->getMessage() . "\n");
    exit(1);
}

echo "Imported $applied products.\n";
echo "Note: a re-import rewrites products.image from products.sql. If that file holds remote URLs\n";
echo "      again, run: php database/catalog/localize-images.php --apply   to restore the local copies.\n";
