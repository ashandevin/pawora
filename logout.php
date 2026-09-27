<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    redirect('index.php');
}
logout_user();
header('Location: ' . app_url('index.php'));
exit;
