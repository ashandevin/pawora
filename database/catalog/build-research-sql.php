<?php
declare(strict_types=1);

/**
 * Turns a research worksheet (database/catalog/research/<name>.json) into products.sql.
 *
 * Usage:
 *   php database/catalog/build-research-sql.php dog-food
 *
 * The research file holds the verified facts; this script only derives values from them, so the
 * worksheet stays the single place a fact is recorded:
 *   - SOURCE rows: selling price = source_price x the dated rate in the worksheet, rounded to 5.
 *   - DEMO rows: selling price = the worksheet's demo_price.
 * The INSERT shape is identical to the one build-products-sql.php writes, so import.php reads
 * either file. Nothing is written to the database here.
 */

require_once __DIR__ . '/../../includes/data.php';

const RESEARCH_FOLDER = __DIR__ . '/research';
const DEFAULT_DATASET = 'dog-food';

$dataset = DEFAULT_DATASET;
foreach ($argv as $index => $argument) {
    if ($argument === '--dataset' && isset($argv[$index + 1])) {
        $dataset = trim((string) $argv[$index + 1]);
        continue;
    }
    if (str_starts_with($argument, '--dataset=')) {
        $dataset = trim(substr($argument, 10));
        continue;
    }
    // A bare first argument is the dataset name, so `... php build-research-sql.php cat-food` works.
    if ($index > 0 && !str_starts_with($argument, '-')) {
        $dataset = trim($argument);
    }
}

$path = RESEARCH_FOLDER . '/' . preg_replace('/[^A-Za-z0-9_-]/', '', $dataset) . '.json';
if (!is_readable($path)) {
    fwrite(STDERR, "Cannot read the research file ($path).\n");
    exit(1);
}
$worksheet = json_decode((string) file_get_contents($path), true);
if (!is_array($worksheet) || !isset($worksheet['products']) || !is_array($worksheet['products'])) {
    fwrite(STDERR, "$path does not look like a research worksheet (no products array).\n");
    exit(1);
}

$categoryName = trim((string) ($worksheet['category'] ?? ''));
if ($categoryName === '') {
    fwrite(STDERR, "The worksheet has no category.\n");
    exit(1);
}
$rates = is_array($worksheet['exchange_rates'] ?? null) ? $worksheet['exchange_rates'] : [];

$columns = [
    'category_id', 'brand', 'name', 'description', 'price', 'currency', 'price_type', 'price_source',
    'source_price', 'source_currency', 'source_exchange_rate', 'price_checked_on', 'discount', 'sku',
    'stock_quantity', 'low_stock_threshold', 'stock_type', 'stock_source', 'rating', 'review_count',
    'rating_type', 'image', 'image_url', 'image_alt', 'source_url', 'image_source_name', 'image_source_url', 'status',
];
$updatable = array_values(array_diff($columns, ['category_id', 'sku']));

// Only these columns are numbers in the schema. Everything else is quoted, because a bare
// identifier such as 023100143538 would be read back by MariaDB as the number 23100143538.
$numericColumns = [
    'price', 'discount', 'stock_quantity', 'low_stock_threshold',
    'rating', 'review_count', 'source_price', 'source_exchange_rate',
];

$quote = static function (mixed $value): string {
    if ($value === null) {
        return 'NULL';
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    return "'" . str_replace(["\\", "'"], ["\\\\", "''"], (string) $value) . "'";
};
$format = static function (mixed $value) use ($quote): string {
    if ($value === null) {
        return 'NULL';
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    return $quote($value);
};

// Selling prices are rounded to the nearest 5 units of the store currency, so converted prices
// look like shop prices while staying within the importer's 1 percent tolerance.
$roundTo = static fn (float $value): float => round($value / 5) * 5;

$rows = [];
$skipped = [];
foreach ($worksheet['products'] as $entry) {
    $sku = strtoupper(trim((string) ($entry['sku'] ?? '')));
    if ($sku === '') {
        $skipped[] = $entry['name'] ?? '(no name)';
        continue;
    }
    $priceType = strtoupper((string) ($entry['price_type'] ?? 'DEMO'));
    $sourcePrice = $entry['source_price'] ?? null;
    $sourceCurrency = strtoupper(trim((string) ($entry['source_currency'] ?? '')));
    $rate = null;

    if ($priceType === 'SOURCE') {
        if ($sourcePrice === null || $sourceCurrency === '') {
            fwrite(STDERR, "$sku is SOURCE priced but has no source_price/source_currency.\n");
            exit(1);
        }
        $converted = (float) $sourcePrice;
        if ($sourceCurrency !== 'LKR') {
            if (!isset($rates[$sourceCurrency]) || !is_numeric($rates[$sourceCurrency])) {
                fwrite(STDERR, "$sku needs a $sourceCurrency rate in the worksheet.\n");
                exit(1);
            }
            $rate = (float) $rates[$sourceCurrency];
            $converted *= $rate;
        }
        $price = $roundTo($converted);
    } else {
        if (!isset($entry['demo_price']) || !is_numeric($entry['demo_price'])) {
            fwrite(STDERR, "$sku is DEMO priced but has no demo_price.\n");
            exit(1);
        }
        $price = (float) $entry['demo_price'];
    }

    $rows[] = [
        'category_id' => null, // resolved from the category name at import time
        'brand' => trim((string) ($entry['brand'] ?? '')),
        'name' => trim((string) ($entry['name'] ?? '')),
        'description' => trim((string) ($entry['description'] ?? '')),
        'price' => number_format($price, 2, '.', ''),
        'currency' => 'LKR',
        'price_type' => $priceType,
        'price_source' => $priceType === 'SOURCE' ? trim((string) ($entry['price_source'] ?? '')) : null,
        'source_price' => $priceType === 'SOURCE' ? number_format((float) $sourcePrice, 2, '.', '') : null,
        'source_currency' => $priceType === 'SOURCE' ? $sourceCurrency : null,
        'source_exchange_rate' => $rate === null ? null : rtrim(rtrim(number_format($rate, 6, '.', ''), '0'), '.'),
        'price_checked_on' => $priceType === 'SOURCE' ? (string) ($entry['price_checked_on'] ?? '') : null,
        'discount' => number_format((float) ($entry['discount'] ?? 0), 2, '.', ''),
        'sku' => $sku,
        'stock_quantity' => (int) ($entry['stock_quantity'] ?? 0),
        'low_stock_threshold' => (int) ($entry['low_stock_threshold'] ?? 0),
        'stock_type' => strtoupper((string) ($entry['stock_type'] ?? 'DEMO')),
        'stock_source' => $entry['stock_source'] ?? null,
        'rating' => $entry['rating'] ?? null,
        'review_count' => $entry['review_count'] ?? null,
        'rating_type' => $entry['rating_type'] ?? null,
        'image' => trim((string) ($entry['image'] ?? '')),
        'image_url' => trim((string) ($entry['image_url'] ?? $entry['image'] ?? '')),
        'image_alt' => trim((string) ($entry['image_alt'] ?? '')),
        'source_url' => trim((string) ($entry['source_url'] ?? '')),
        'image_source_name' => trim((string) ($entry['image_source_name'] ?? '')),
        'image_source_url' => trim((string) ($entry['image_source_url'] ?? '')),
        'status' => trim((string) ($entry['status'] ?? 'Active')),
    ];
}

if ($rows === []) {
    fwrite(STDERR, "The worksheet produced no usable rows.\n");
    exit(1);
}

$header = "-- PAWORA product catalog data.\n"
    . "-- Generated by database/catalog/build-research-sql.php from database/catalog/research/{$dataset}.json\n"
    . "-- and read by database/catalog/import.php. Every value is escaped and rows upsert on SKU.\n"
    . '-- Category: ' . $categoryName . "\n"
    . '-- Rows: ' . count($rows) . "\n"
    . isset($rates['provider'], $rates['updated_utc'])
        ? '-- Converted prices use ' . $rates['provider'] . ' rates dated ' . $rates['updated_utc'] . ".\n"
        : ''
    . "-- category_id is resolved from the category names in categories.sql at import time.\n\n"
    . "START TRANSACTION;\n\n";

$out = $header;
foreach ($rows as $row) {
    $values = [];
    foreach ($columns as $column) {
        if ($column === 'category_id') {
            $values[] = '(SELECT id FROM categories WHERE name = ' . $quote($categoryName) . ' LIMIT 1)';
            continue;
        }
        $value = $row[$column] ?? null;
        $values[] = in_array($column, $numericColumns, true) && $value !== null
            ? (string) $value
            : $format($value);
    }
    $out .= 'INSERT INTO products (' . implode(', ', $columns) . ") VALUES (\n    "
        . implode(",\n    ", $values) . "\n)\nON DUPLICATE KEY UPDATE\n    "
        . implode(",\n    ", array_map(static fn (string $column): string => $column . ' = VALUES(' . $column . ')', $updatable))
        . ";\n";
}
$out .= "\nCOMMIT;\n";

$target = __DIR__ . '/products.sql';
file_put_contents($target, $out);

echo 'wrote database/catalog/products.sql from research/' . $dataset . ".json\n";
printf("  category : %s\n  rows     : %d\n", $categoryName, count($rows));
if ($skipped !== []) {
    echo '  skipped  : ' . implode(', ', $skipped) . "\n";
}
echo "\nReview before importing:\n";
printf("  %-24s %-11s %-8s %10s  %s\n", 'SKU', 'BRAND', 'PRICE', 'LKR', 'PRICE TYPE');
foreach ($rows as $row) {
    printf("  %-24s %-11s %-8s %10s  %s\n", $row['sku'], substr($row['brand'], 0, 11), $row['currency'],
        number_format((float) $row['price'], 2), $row['price_type']);
}
echo "\nNext: php database/catalog/import.php --category \"" . $categoryName . "\"   # dry run, writes nothing\n";
