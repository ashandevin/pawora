<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function database_error_message(): ?string
{
    return $GLOBALS['pawora_database_error'] ?? null;
}

function db_all(string $sql, array $params = []): array
{
    try {
        $statement = db()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    } catch (Throwable $exception) {
        $GLOBALS['pawora_database_error'] = 'PAWORA is not connected to MySQL yet. Import database/pawora_db.sql and check config/database.php.';
        return [];
    }
}

function db_one(string $sql, array $params = []): ?array
{
    $rows = db_all($sql, $params);
    return $rows[0] ?? null;
}

function db_value(string $sql, array $params = []): mixed
{
    try {
        $statement = db()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchColumn();
    } catch (Throwable $exception) {
        $GLOBALS['pawora_database_error'] = 'PAWORA is not connected to MySQL yet. Import database/pawora_db.sql and check config/database.php.';
        return null;
    }
}

function show_database_notice(): void
{
    if (database_error_message()): ?>
        <div class="container mt-4">
            <div class="alert alert-warning border-0 shadow-sm" role="alert">
                <i class="bi bi-database-exclamation me-2" aria-hidden="true"></i><?= e((string) database_error_message()) ?>
            </div>
        </div>
    <?php endif;
}
