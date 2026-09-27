<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pets.php');
}

csrf_valid_or_fail();
$petId = filter_input(INPUT_POST, 'pet_id', FILTER_VALIDATE_INT) ?: 0;
$returnTo = (string) ($_POST['return_to'] ?? 'pets.php');
if (!preg_match('/^[a-z0-9\/-]+\.php(?:\?.*)?$/i', $returnTo)) {
    $returnTo = 'pets.php';
}

if (!is_logged_in()) {
    $_SESSION['return_to'] = $returnTo;
    set_flash('info', 'Sign in to save your favorite pets.');
    redirect('login.php');
}

try {
    $pet = get_pet($petId);
} catch (Throwable $exception) {
    set_flash('error', 'We could not load that pet profile right now.');
    redirect($returnTo);
}
if (!$pet) {
    set_flash('error', 'We could not find that pet profile.');
    redirect($returnTo);
}

try {
    $userId = (int) $_SESSION['user_id'];
    $check = db()->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND pet_id = ? LIMIT 1');
    $check->execute([$userId, $petId]);
    if ($check->fetchColumn()) {
        $delete = db()->prepare('DELETE FROM favorites WHERE user_id = ? AND pet_id = ?');
        $delete->execute([$userId, $petId]);
        set_flash('info', $pet['name'] . ' was removed from your favorites.');
    } else {
        $insert = db()->prepare('INSERT INTO favorites (user_id, pet_id) VALUES (?, ?)');
        $insert->execute([$userId, $petId]);
        set_flash('success', $pet['name'] . ' was added to your favorites.');
    }
} catch (Throwable $exception) {
    set_flash('error', 'We could not update your favorites right now.');
}
redirect($returnTo);
