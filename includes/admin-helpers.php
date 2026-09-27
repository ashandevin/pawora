<?php
declare(strict_types=1);

require_once __DIR__ . '/data.php';

function pet_form_values(array $pet = []): array
{
    return [
        'name' => trim((string) ($_POST['name'] ?? $pet['name'] ?? '')),
        'species' => (string) ($_POST['species'] ?? $pet['species'] ?? 'Dog'),
        'breed' => trim((string) ($_POST['breed'] ?? $pet['breed'] ?? '')),
        'age_months' => (string) ($_POST['age_months'] ?? ($pet['age_months'] ?? '')),
        'gender' => (string) ($_POST['gender'] ?? $pet['gender'] ?? 'Female'),
        'size' => (string) ($_POST['size'] ?? $pet['size'] ?? 'Medium'),
        'location' => trim((string) ($_POST['location'] ?? $pet['location'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? $pet['description'] ?? '')),
        'personality' => trim((string) ($_POST['personality'] ?? $pet['personality'] ?? '')),
        'health_status' => trim((string) ($_POST['health_status'] ?? $pet['health_status'] ?? '')),
        'vaccinated' => array_key_exists('vaccinated', $_POST) ? (int) filter_var($_POST['vaccinated'], FILTER_VALIDATE_BOOLEAN) : (int) ($pet['vaccinated'] ?? 0),
        'image' => (string) ($pet['image'] ?? ''),
        'image_url' => trim((string) ($_POST['image_url'] ?? '')),
        'status' => (string) ($_POST['status'] ?? $pet['status'] ?? 'Available'),
        'featured' => array_key_exists('featured', $_POST) ? (int) filter_var($_POST['featured'], FILTER_VALIDATE_BOOLEAN) : (int) ($pet['featured'] ?? 0),
    ];
}

function validate_pet_values(array $values): array
{
    $errors = [];
    $errors = array_merge($errors, length_errors($values, [
        'name' => ['Pet name', 100],
        'breed' => ['Breed', 120],
        'location' => ['Location', 100],
        'description' => ['Description', 5000],
        'personality' => ['Personality', 2000],
        'health_status' => ['Health status', 255],
        'image' => ['Image path', 500],
    ]));
    if (text_length($values['name']) < 2) $errors[] = 'Enter a pet name.';
    if (!in_array($values['species'], ['Dog', 'Cat', 'Rabbit', 'Bird', 'Other'], true)) $errors[] = 'Choose a valid species.';
    if ($values['breed'] === '') $errors[] = 'Enter a breed or mixed breed.';
    if (!ctype_digit($values['age_months']) || (int) $values['age_months'] < 1 || (int) $values['age_months'] > 360) $errors[] = 'Enter an age between 1 and 360 months.';
    if (!in_array($values['gender'], ['Male', 'Female'], true)) $errors[] = 'Choose a valid gender.';
    if (!in_array($values['size'], ['Small', 'Medium', 'Large'], true)) $errors[] = 'Choose a valid size.';
    if ($values['location'] === '') $errors[] = 'Enter a location.';
    if (text_length($values['description']) < 20) $errors[] = 'Add a description of at least 20 characters.';
    if (!in_array($values['status'], ['Available', 'Pending', 'Reserved', 'Adopted'], true)) $errors[] = 'Choose a valid availability status.';
    if ($values['image_url'] !== '' && !filter_var($values['image_url'], FILTER_VALIDATE_URL)) $errors[] = 'Use a valid image URL or upload an image.';
    return $errors;
}

function pet_image_path(?string $image): ?string
{
    $image = trim((string) $image);
    if (!preg_match('#^pets/([A-Za-z0-9._-]+)$#', $image, $matches)) {
        return null;
    }
    return __DIR__ . '/../assets/images/pets/' . $matches[1];
}

function remove_local_pet_image(?string $image): void
{
    $path = pet_image_path($image);
    if ($path === null || !is_file($path)) {
        return;
    }
    // The same local file can be referenced by more than one pet, so only unlink it once no
    // row still points at it. If the check cannot run we keep the file rather than risk
    // breaking another pet's image.
    try {
        if ((int) db_value('SELECT COUNT(*) FROM pets WHERE image = ?', [(string) $image]) > 0) {
            return;
        }
    } catch (Throwable $exception) {
        return;
    }
    @unlink($path);
}

function upload_pet_image(array $values, array $existing, array &$errors): string
{
    $existingImage = (string) ($existing['image'] ?? '');
    if (!isset($_FILES['image']) || !is_array($_FILES['image']) || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingImage;
    }
    $file = $_FILES['image'];
    if (is_array($file['error'] ?? null)) {
        $errors[] = 'Please choose a single image.';
        return $existingImage;
    }
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $errors[] = upload_error_message($uploadError);
        return $existingImage;
    }
    if (!isset($file['tmp_name']) || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'The uploaded image could not be verified.';
        return $existingImage;
    }
    if (($file['size'] ?? 0) < 1) {
        $errors[] = 'The selected image is empty. Please choose another file.';
        return $existingImage;
    }
    if (($file['size'] ?? 0) > upload_limit_bytes()) {
        $errors[] = 'Pet images must be smaller than ' . human_bytes(upload_limit_bytes()) . '.';
        return $existingImage;
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
        return $existingImage;
    }
    $directory = upload_directory(__DIR__ . '/../assets/images/pets');
    $directoryError = upload_directory_error($directory);
    if ($directoryError !== null) {
        $errors[] = $directoryError;
        return $existingImage;
    }
    $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $target = $directory . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        $errors[] = 'We could not save the uploaded image to ' . $target
            . '. Check the folder permissions and the server upload limits, then try again.';
        return $existingImage;
    }
    @chmod($target, 0644);
    return 'pets/' . $filename;
}

function save_pet(?int $id = null): array
{
    $existing = $id ? get_pet($id) : [];
    $values = pet_form_values($existing);
    $errors = validate_pet_values($values);
    $image = $values['image_url'] !== '' ? $values['image_url'] : (string) ($existing['image'] ?? '');
    if (!$errors && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $image = upload_pet_image($values, $existing, $errors);
    }
    if ($errors) return ['errors' => $errors, 'values' => $values];
    $previousImage = (string) ($existing['image'] ?? '');
    try {
        if ($id) {
            $statement = db()->prepare('UPDATE pets SET name = ?, species = ?, breed = ?, age_months = ?, gender = ?, size = ?, location = ?, description = ?, personality = ?, health_status = ?, vaccinated = ?, image = ?, status = ?, featured = ? WHERE id = ?');
            $statement->execute([$values['name'], $values['species'], $values['breed'], (int) $values['age_months'], $values['gender'], $values['size'], $values['location'], $values['description'], $values['personality'], $values['health_status'], $values['vaccinated'], $image, $values['status'], $values['featured'], $id]);
            add_admin_log((int) $_SESSION['user_id'], 'UPDATE', 'Updated pet profile: ' . $values['name']);
            if ($previousImage !== $image) {
                remove_local_pet_image($previousImage);
            }
        } else {
            $statement = db()->prepare('INSERT INTO pets (name, species, breed, age_months, gender, size, location, description, personality, health_status, vaccinated, image, status, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $statement->execute([$values['name'], $values['species'], $values['breed'], (int) $values['age_months'], $values['gender'], $values['size'], $values['location'], $values['description'], $values['personality'], $values['health_status'], $values['vaccinated'], $image, $values['status'], $values['featured']]);
            $id = (int) db()->lastInsertId();
            add_admin_log((int) $_SESSION['user_id'], 'CREATE', 'Created pet profile: ' . $values['name']);
        }
        return ['errors' => [], 'id' => $id];
    } catch (Throwable $exception) {
        if ($image !== $previousImage) {
            remove_local_pet_image($image);
        }
        return ['errors' => ['We could not save this pet right now.'], 'values' => $values];
    }
}
