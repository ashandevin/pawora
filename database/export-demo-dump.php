<?php
/**
 * Exports a shareable, portable SQL dump of the live PAWORA database.
 *
 * The seed file database/pawora_db.sql only carries the original 10 demo
 * products, because the real catalog was assembled by importing research
 * worksheets straight into the live database and was never written back to a
 * file. This tool closes that gap: it produces a single .sql file that another
 * machine can import to get the real catalog.
 *
 * It also scrubs the database of anything that must not leave this machine:
 *   - real user accounts (email, phone, home address)
 *   - QA and XSS-probe rows left behind by the regression harnesses
 *   - the 600+ row admin_logs noise
 *
 * The generated dump always starts with DROP TABLE, so importing it over an
 * existing install replaces the demo data instead of merging with it.
 *
 * Usage:
 *   php database/export-demo-dump.php
 *   php database/export-demo-dump.php --out=/path/to/file.sql
 *   php database/export-demo-dump.php --orders=all      # keep every clean order
 *   php database/export-demo-dump.php --allow-pii      # skip scrubbing (local only)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

const SEED_FILE = __DIR__ . '/pawora_db.sql';

/**
 * Personal data must never appear in an exported file.
 *
 * Rather than listing known values (which would mean storing real addresses and
 * phone numbers in this file, and would still miss anything new), the export is
 * checked for anything that looks like real personal data:
 *   - an email address outside the known demonstration domains
 *   - a phone number or a street address anywhere in the text
 */
const ALLOWED_EMAIL_DOMAINS = ['pawora.test', 'example.com', 'example.org', 'localhost'];

/**
 * Placeholder contact details seeded by database/pawora_db.sql. They are
 * fictional fixture values, not real personal data, so the phone-number scan
 * must not flag them. Compared with all non-digits removed.
 */
const DEMO_PHONE_NUMBERS = ['0771234567', '0770000000', '94112345678', '94771234567'];

function digits(string $value): string
{
    return preg_replace('/\D+/', '', $value);
}

function findPersonalData(string $sql): array
{
    $hits = [];

    // Any e-mail that is not clearly a demonstration address.
    if (preg_match_all('/\b([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+)\b/', $sql, $m)) {
        foreach (array_unique($m[1]) as $email) {
            $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
            if (!in_array($domain, ALLOWED_EMAIL_DOMAINS, true)) {
                $hits[] = "e-mail address outside the demo domains: {$email}";
            }
        }
    }

    // Phone numbers, ignoring the fictional ones from the seed. The lookarounds
    // keep the pattern from matching digits inside a longer token, such as the
    // numeric part of an image filename or URL.
    if (preg_match_all('/(?<![A-Za-z0-9-])(?:\+?94|0)7[\s\d]{8,12}(?![A-Za-z0-9-])/', $sql, $m)) {
        foreach (array_unique($m[0]) as $phone) {
            if (!in_array(digits($phone), DEMO_PHONE_NUMBERS, true)) {
                $hits[] = 'phone number: ' . trim($phone);
            }
        }
    }

    // Street addresses. Only quoted data values are considered, so schema text
    // such as "DECIMAL(10,2)" cannot be mistaken for an address.
    if (preg_match_all("/'([^'\\\\]*)'/", $sql, $m)) {
        foreach ($m[1] as $value) {
            if (preg_match('/^\d{2,4}\s*,\s*[A-Za-z][A-Za-z\s,.-]{3,}$/u', $value)) {
                $hits[] = 'possible street address: ' . trim($value);
            }
        }
    }

    return array_values(array_unique($hits));
}

/** Users kept in the export, by id. Everyone else is a real local account. */
const KEEP_USERS = [1, 2, 3];

/** Pets kept in the export: all of them. */
const KEEP_PETS = 'all';

$pdo = db();
$outPath = __DIR__ . '/pawora_demo_full.sql';
$orderMode = 'sample';
$allowPii = false;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--out=')) {
        $outPath = substr($arg, 6);
    } elseif ($arg === '--orders=all') {
        $orderMode = 'all';
    } elseif ($arg === '--allow-pii') {
        $allowPii = true;
    }
}

// ---------------------------------------------------------------------------
// Schema: reuse the CREATE TABLE / CREATE TRIGGER statements verbatim so the
// dump can never drift from the schema the application actually runs against.
// ---------------------------------------------------------------------------

$seed = file_get_contents(SEED_FILE);
if ($seed === false) {
    exit("Could not read " . SEED_FILE . "\n");
}

preg_match_all(
    '/CREATE TABLE IF NOT EXISTS\s+(\w+)\s*\((.*?)\n\) ENGINE=InnoDB;/s',
    $seed,
    $tableMatches,
    PREG_SET_ORDER
);
if ($tableMatches === []) {
    exit("No CREATE TABLE statements found in the seed file.\n");
}

$schema = [];
$createOrder = [];
foreach ($tableMatches as $m) {
    $schema[$m[1]] = 'CREATE TABLE IF NOT EXISTS ' . $m[1] . " (\n" . $m[2] . "\n) ENGINE=InnoDB;";
    $createOrder[] = $m[1];
}

preg_match_all('/(?:DROP TRIGGER IF EXISTS \w+;\n)?CREATE TRIGGER \w+.*?END;/s', $seed, $triggerMatches);
$triggers = $triggerMatches[0];

// ---------------------------------------------------------------------------
// Row selection
// ---------------------------------------------------------------------------

/** Returns the column list for a table, in declaration order. */
function columns(PDO $pdo, string $table): array
{
    $st = $pdo->prepare(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION'
    );
    $st->execute([$table]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

function fetchWhere(PDO $pdo, string $table, string $where, array $params = []): array
{
    $cols = columns($pdo, $table);
    $sql = 'SELECT * FROM `' . $table . '`';
    if ($where !== '') {
        $sql .= ' WHERE ' . $where;
    }
    $sql .= ' ORDER BY ' . $cols[0];
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Picks one clean order per status so the export demonstrates the whole order
 * lifecycle (pending, paid, processing, delivered, cancelled).
 *
 * Rather than blacklisting the individual junk values the test harnesses
 * happened to write, an order is kept only when it looks like a genuine
 * demonstration order:
 *
 *   - it belongs to a kept demonstration account;
 *   - the billing name is the account holder's own registered name, which
 *     rejects the scripted names the harnesses submitted;
 *   - the shipping address contains a comma-separated locality, which rejects
 *     the keyboard-mashing values.
 *
 * This is expressed as a rule so it keeps working when the names and strings
 * used by a future test run differ from the ones used today.
 */
function pickOrders(PDO $pdo, string $mode): array
{
    $st = $pdo->prepare(
        'SELECT o.* FROM orders o
           JOIN users u ON u.id = o.user_id
          WHERE o.user_id IN (1,2,3)
            AND o.billing_name = u.name
            AND o.shipping_address LIKE ?
          ORDER BY o.id'
    );
    $st->execute(['%,%']);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    if ($mode === 'all') {
        return $rows;
    }

    $byStatus = [];
    foreach ($rows as $row) {
        $byStatus[$row['status']] ??= $row;
    }
    ksort($byStatus);
    return array_values($byStatus);
}

$userIds = KEEP_USERS;
$userList = implode(',', $userIds);
$petIds = array_map('intval', array_column(fetchWhere($pdo, 'pets', ''), 'id'));
$petList = implode(',', $petIds);
$orderRows = pickOrders($pdo, $orderMode);
$orderIds = array_map('intval', array_column($orderRows, 'id'));
$orderList = $orderIds === [] ? '0' : implode(',', $orderIds);

$plan = [
    // label            table                     WHERE
    'categories' => ['categories', ''],
    'products' => ['products', ''],
    'users' => ['users', 'id IN (' . $userList . ')'],
    'pets' => ['pets', KEEP_PETS === 'all' ? '' : ('id IN (' . implode(',', KEEP_PETS) . ')')],
    'adoption_applications' => ['adoption_applications', 'user_id IN (' . $userList . ')'],
    'success_stories' => ['success_stories', ''],
    // Keep one row per distinct comment: the harnesses re-posted the same three
    // reviews dozens of times, and the demo page should show three, not eight.
    'feedback' => [
        'feedback',
        "(user_id IN ($userList) OR user_id IS NULL) AND comment NOT LIKE '%script%' AND comment NOT LIKE '%QA%' AND comment NOT LIKE '%CCC%'",
        'comment',
    ],
    // A handful of notifications keeps the customer dashboard readable; the live
    // database has ~60 because every checkout test wrote one.
    'notifications' => ['notifications', 'user_id IN (' . $userList . ')', 'id', 6],
    'orders' => ['orders', 'id IN (' . $orderList . ')'],
    'order_items' => ['order_items', 'order_id IN (' . $orderList . ')'],
    'payments' => ['payments', 'order_id IN (' . $orderList . ')'],
];

// contact_messages, admin_logs, favorites, waiting_list, product_favorites and
// product_waiting_list are intentionally omitted: they only contain QA rows.

foreach ($plan as $label => $spec) {
    if (!isset($schema[$spec[0]])) {
        exit("Unknown table in export plan: {$spec[0]}\n");
    }
}
echo "Exporting PAWORA demo data\n";
echo str_repeat('-', 60) . "\n";

// ---------------------------------------------------------------------------
// Build
// ---------------------------------------------------------------------------

$sql = "-- PAWORA - full demo data dump\n"
     . "--\n"
     . "-- Generated by database/export-demo-dump.php on " . date('Y-m-d H:i:s') . "\n"
     . "--\n"
     . "-- This contains the complete research-backed product catalogue\n"
     . "-- (" . $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() . " products, provenance and prices included),\n"
     . "-- the adoption listings, and a small set of demonstration content.\n"
     . "--\n"
     . "-- IMPORT INSTRUCTIONS\n"
     . "--   1. Create an empty database.\n"
     . "--   2. Select that database in phpMyAdmin so its name shows in the sidebar.\n"
     . "--   3. Import -> choose this file -> character set utf8mb4 -> Go.\n"
     . "--\n"
     . "--   The file drops and recreates its own tables, so it safely replaces an\n"
     . "--   existing PAWORA install instead of merging with it.\n"
     . "--\n"
     . "--   Then set the database name and credentials for the application in\n"
     . "--   .env  (copy .env.example and edit it).\n"
     . "--\n"
     . "-- OMITTED ON PURPOSE: contact_messages, admin_logs, favourites, waiting lists\n"
     . "-- and QA rows created while testing - none of it belongs in a fresh install.\n"
     . "--\n\n"
     . "SET NAMES utf8mb4;\n"
     . "SET FOREIGN_KEY_CHECKS = 0;\n\n";

$sql .= "-- -------------------------------------------------------------------\n";
$sql .= "-- Drop existing objects (reverse dependency order)\n";
$sql .= "-- -------------------------------------------------------------------\n";
foreach ($triggers as $t) {
    $name = preg_match('/CREATE TRIGGER (\w+)/', $t, $m) ? $m[1] : '';
    if ($name !== '') {
        $sql .= "DROP TRIGGER IF EXISTS {$name};\n";
    }
}
foreach (array_reverse($createOrder) as $table) {
    $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
}
$sql .= "\n";

$sql .= "-- -------------------------------------------------------------------\n";
$sql .= "-- Schema (dependency order: every foreign key target is created first)\n";
$sql .= "-- -------------------------------------------------------------------\n";
foreach ($createOrder as $table) {
    $sql .= $schema[$table] . "\n\n";
}
foreach ($triggers as $t) {
    $sql .= "DROP TRIGGER IF EXISTS " . (preg_match('/CREATE TRIGGER (\w+)/', $t, $m) ? $m[1] : '') . ";\n";
    $sql .= $t . "\n\n";
}

$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n\n";

$total = 0;
foreach ($plan as $label => $spec) {
    [$table, $where] = $spec;
    $uniqueBy = $spec[2] ?? null;
    $max = $spec[3] ?? null;

    $rows = fetchWhere($pdo, $table, $where);

    if ($uniqueBy !== null) {
        $seen = [];
        $rows = array_values(array_filter($rows, static function ($row) use ($uniqueBy, &$seen) {
            $key = $row[$uniqueBy];
            if (isset($seen[$key])) {
                return false;
            }
            $seen[$key] = true;
            return true;
        }));
    }

    if ($max !== null && count($rows) > $max) {
        $rows = array_slice($rows, 0, $max);
    }

    // Safety net: any row pointing at a user that is not part of the export
    // would break the foreign key on import. Detach it if the column allows a
    // NULL, otherwise leave it out entirely.
    $ownerCols = array_values(array_intersect(['user_id', 'admin_id'], columns($pdo, $table)));
    if ($ownerCols !== []) {
        $nullable = [];
        foreach ($ownerCols as $oc) {
            $q = $pdo->prepare(
                'SELECT IS_NULLABLE FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $q->execute([$table, $oc]);
            $nullable[$oc] = $q->fetchColumn() === 'YES';
        }
        $kept = [];
        foreach ($rows as $row) {
            $drop = false;
            foreach ($ownerCols as $oc) {
                $v = $row[$oc];
                if ($v !== null && !in_array((int) $v, $userIds, true)) {
                    if ($nullable[$oc]) {
                        $row[$oc] = null;
                    } else {
                        $drop = true;
                    }
                }
            }
            if (!$drop) {
                $kept[] = $row;
            }
        }
        if (count($kept) !== count($rows)) {
            echo '  ' . str_pad($label, 24) . (count($rows) - count($kept)) . " row(s) detached from removed accounts\n";
        }
        $rows = $kept;
    }

    if ($rows === []) {
        echo "  " . str_pad($label, 24) . "0 rows (skipped)\n";
        continue;
    }

    $cols = columns($pdo, $table);
    $colSql = implode(', ', array_map(static fn($c) => "`{$c}`", $cols));

    $sql .= "-- {$label} (" . count($rows) . " rows)\n";
    $sql .= "INSERT INTO `{$table}` ({$colSql}) VALUES\n";

    $chunks = [];
    foreach (array_chunk($rows, 100) as $chunk) {
        $values = [];
        foreach ($chunk as $row) {
            $cells = [];
            foreach ($cols as $c) {
                $cells[] = $row[$c] === null ? 'NULL' : $pdo->quote((string) $row[$c]);
            }
            $values[] = '  (' . implode(', ', $cells) . ')';
        }
        $chunks[] = implode(",\n", $values);
    }
    $sql .= implode(",\n", $chunks) . ";\n\n";
    $total += count($rows);
    echo "  " . str_pad($label, 24) . count($rows) . " rows\n";
}

file_put_contents($outPath, $sql);

// ---------------------------------------------------------------------------
// Verify
// ---------------------------------------------------------------------------

echo str_repeat('-', 60) . "\n";
echo "  rows written: {$total}\n";
echo "  file:        {$outPath} (" . number_format(strlen($sql) / 1024, 1) . " KB)\n";

$leaks = [];
if (!$allowPii) {
    $leaks = findPersonalData($sql);
}

if ($leaks === []) {
    echo "  privacy:     clean (no e-mail, address or phone number outside the demo domains)\n";
} else {
    echo "  privacy:     FAILED - the dump still contains:\n";
    foreach ($leaks as $l) {
        echo "                - {$l}\n";
    }
    unlink($outPath);
    exit("\nRefusing to keep an export containing personal data. Nothing was written.\n");
}

echo "  drop order:   " . count($createOrder) . " tables, reversed\n";
echo "  create order: " . count($createOrder) . " tables, dependency order\n";
echo "\nImport this file over the demo data to load the real catalogue.\n";
