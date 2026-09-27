<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/data.php';

$rows = db_all('SELECT id, name, slug, description, status FROM categories ORDER BY id');
$quote = static fn (string $value): string => "'" . str_replace("'", "''", $value) . "'";
$out = "-- PAWORA product catalog: the ten approved store categories.\n"
    . "-- Read by database/catalog/import.php. Safe to re-import: rows are upserted by name.\n"
    . "-- Exactly ten categories, in the order the storefront shows them.\n\n"
    . "START TRANSACTION;\n\n";
foreach ($rows as $row) {
    $out .= '-- ' . $row['name'] . "\n"
        . 'INSERT INTO categories (name, slug, description, status) VALUES (' . "\n"
        . '    ' . $quote((string) $row['name']) . ',' . "\n"
        . '    ' . $quote((string) $row['slug']) . ',' . "\n"
        . '    ' . $quote((string) $row['description']) . ',' . "\n"
        . '    ' . $quote((string) $row['status']) . "\n"
        . ")\nON DUPLICATE KEY UPDATE\n"
        . "    description = VALUES(description),\n"
        . "    name = VALUES(name),\n"
        . "    slug = VALUES(slug),\n"
        . "    status = VALUES(status);\n\n";
}
$out .= "COMMIT;\n";
file_put_contents(__DIR__ . '/categories.sql', $out);
echo 'wrote database/catalog/categories.sql (' . count($rows) . " categories)\n";
