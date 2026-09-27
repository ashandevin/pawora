<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function is_admin(): bool
{
    return strtoupper((string) ($_SESSION['user']['role'] ?? '')) === 'ADMIN';
}

function current_user_is_admin(): bool
{
    if (!is_logged_in()) {
        return false;
    }
    if (is_admin()) {
        return true;
    }
    try {
        $statement = db()->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
        $statement->execute([(int) $_SESSION['user_id']]);
        return $statement->fetchColumn() === 'ADMIN';
    } catch (Throwable $exception) {
        return false;
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_auth(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to continue.');
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_auth();
    $adminId = (int) ($_SESSION['user_id'] ?? 0);
    try {
        $statement = db()->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
        $statement->execute([$adminId]);
        $role = $statement->fetchColumn();
    } catch (Throwable $exception) {
        $_SESSION = [];
        session_regenerate_id(true);
        set_flash('error', 'Your administrator session could not be verified. Please sign in again.');
        redirect('login.php');
    }
    if ($role !== 'ADMIN') {
        $_SESSION = [];
        session_regenerate_id(true);
        set_flash('error', 'You do not have permission to view that page.');
        redirect('login.php');
    }
    $_SESSION['user']['role'] = 'ADMIN';
}

function attempt_login(string $email, string $password): bool
{
    $statement = db()->prepare('SELECT id, name, email, password, phone, address, role FROM users WHERE email = ? LIMIT 1');
    $statement->execute([strtolower(trim($email))]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['user_id'] = (int) $user['id'];
    unset($user['password']);
    $_SESSION['user'] = $user;
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}
