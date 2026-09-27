<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'Pet adoption with care';
$activePage = $activePage ?? '';
$bodyClass = $bodyClass ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="PAWORA helps people find loving, responsible forever homes for pets who need them.">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset_url('images/pawora-mark.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('css/style.css')) ?>" rel="stylesheet">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main-content">Skip to main content</a>
<?php require __DIR__ . '/navbar.php'; ?>
<main id="main-content">
    <?php foreach (take_flashes() as $flash): ?>
        <div class="container flash-wrap" aria-live="polite">
            <div class="alert alert-<?= e($flash['type'] === 'error' ? 'danger' : $flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi <?= e(flash_type_icon($flash['type'])) ?> me-2" aria-hidden="true"></i>
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close message"></button>
            </div>
        </div>
    <?php endforeach; ?>
