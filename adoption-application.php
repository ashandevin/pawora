<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';
require_auth();

$petId = (int) ($_GET['pet_id'] ?? $_POST['pet_id'] ?? 0);
$pet = $petId > 0 ? db_one('SELECT * FROM pets WHERE id = ?', [$petId]) : null;
$user = current_user();
$errors = [];
$values = [
    'full_name' => trim((string) ($_POST['full_name'] ?? $user['name'] ?? '')),
    'email' => trim((string) ($_POST['email'] ?? $user['email'] ?? '')),
    'phone' => trim((string) ($_POST['phone'] ?? $user['phone'] ?? '')),
    'address' => trim((string) ($_POST['address'] ?? $user['address'] ?? '')),
    'home_type' => trim((string) ($_POST['home_type'] ?? '')),
    'living_situation' => trim((string) ($_POST['living_situation'] ?? '')),
    'previous_pet_experience' => trim((string) ($_POST['previous_pet_experience'] ?? '')),
    'family_members' => trim((string) ($_POST['family_members'] ?? '')),
    'working_hours' => trim((string) ($_POST['working_hours'] ?? '')),
    'experience_with_animals' => trim((string) ($_POST['experience_with_animals'] ?? '')),
    'reason' => trim((string) ($_POST['reason'] ?? '')),
    'additional_information' => trim((string) ($_POST['additional_information'] ?? '')),
];

if (!$pet) {
    set_flash('error', 'Choose a pet from the collection before applying.');
    redirect('pets.php');
}
if ($pet['status'] !== 'Available') {
    set_flash('info', $pet['status'] === 'Adopted' ? $pet['name'] . ' has already been adopted. You can join the waiting list instead.' : $pet['name'] . ' is not currently accepting new applications.');
    redirect('pet-details.php?id=' . (int) $pet['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'adoption_application') {
    if (!verify_csrf()) $errors[] = 'Your form session expired. Please try again.';
    $errors = array_merge($errors, length_errors($_POST, [
        'full_name' => ['Full name', 120],
        'email' => ['Email address', 190],
        'phone' => ['Phone number', 40],
        'address' => ['Home address', 2000],
        'home_type' => ['Home type', 80],
        'living_situation' => ['Living situation', 120],
        'previous_pet_experience' => ['Previous pet experience', 120],
        'working_hours' => ['Working hours', 120],
        'experience_with_animals' => ['Experience with animals', 120],
        'reason' => ['Reason for adopting', 2000],
        'additional_information' => ['Additional information', 2000],
    ]));
    if ($values['full_name'] === '' || text_length($values['full_name']) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($values['phone'] === '' || preg_match('/[^0-9+()\-\s]/', $values['phone']) || strlen(preg_replace('/\D/', '', $values['phone']) ?? '') < 7) $errors[] = 'Please enter a valid phone number.';
    if (text_length($values['address']) < 8) $errors[] = 'Please enter your home address.';
    foreach (['home_type', 'living_situation', 'previous_pet_experience', 'working_hours', 'experience_with_animals'] as $field) {
        if ($values[$field] === '') $errors[] = 'Please complete every lifestyle field.';
    }
    if (!ctype_digit($values['family_members']) || (int) $values['family_members'] < 1 || (int) $values['family_members'] > 30) $errors[] = 'Enter the number of people in your household.';
    if (text_length($values['reason']) < 20) $errors[] = 'Please tell us a little more about why you want to adopt.';
    if (empty($_POST['terms'])) $errors[] = 'Please accept the adoption terms to continue.';
    if (empty($_POST['privacy'])) $errors[] = 'Please accept the privacy agreement to continue.';

    if (!$errors) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $lockedPet = $pdo->prepare('SELECT id, name, status FROM pets WHERE id = ? FOR UPDATE');
            $lockedPet->execute([(int) $pet['id']]);
            $lockedPetRow = $lockedPet->fetch();
            if (!$lockedPetRow || $lockedPetRow['status'] !== 'Available') {
                $pdo->rollBack();
                $errors[] = 'This pet is no longer accepting applications. Please choose another profile.';
            } else {
                $existing = $pdo->prepare('SELECT 1 FROM adoption_applications WHERE user_id = ? AND pet_id = ? AND status NOT IN (?, ?) LIMIT 1');
                $existing->execute([(int) $user['id'], (int) $pet['id'], 'REJECTED', 'ADOPTION COMPLETED']);
                if ($existing->fetchColumn()) {
                    $pdo->rollBack();
                    $errors[] = 'You already have an active application for this pet.';
                } else {
                    $reference = generate_reference('PAW');
                    $insert = $pdo->prepare('INSERT INTO adoption_applications (user_id, pet_id, application_reference, full_name, email, phone, address, home_type, living_situation, previous_pet_experience, family_members, working_hours, experience_with_animals, reason, additional_information) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $insert->execute([(int) $user['id'], (int) $pet['id'], $reference, $values['full_name'], $values['email'], $values['phone'], $values['address'], $values['home_type'], $values['living_situation'], $values['previous_pet_experience'], (int) $values['family_members'], $values['working_hours'], $values['experience_with_animals'], $values['reason'], $values['additional_information']]);
                    $applicationId = (int) $pdo->lastInsertId();
                    $update = $pdo->prepare('UPDATE pets SET status = ? WHERE id = ? AND status = ?');
                    $update->execute(['Pending', (int) $pet['id'], 'Available']);
                    if ($update->rowCount() !== 1) {
                        $pdo->rollBack();
                        $errors[] = 'This pet is no longer accepting applications. Please choose another profile.';
                    } else {
                        notify((int) $user['id'], 'Your adoption application has been received', 'We received your application for ' . $pet['name'] . '. The PAWORA team will review it shortly.');
                        $pdo->commit();
                        $_SESSION['application_confirmation'] = $reference;
                        redirect('application-confirmation.php?reference=' . urlencode($reference) . '&id=' . $applicationId);
                    }
                }
            }
        } catch (Throwable $exception) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'We could not submit your application right now. Please try again.';
        }
    }
}

$pageTitle = 'Adoption application';
$activePage = 'adoption';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><nav aria-label="Breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= e(app_url('pets.php')) ?>">Find a Pet</a></li><li class="breadcrumb-item"><a href="<?= e(app_url('pet-details.php?id=' . (int) $pet['id'])) ?>"><?= e($pet['name']) ?></a></li><li class="breadcrumb-item active" aria-current="page">Application</li></ol></nav><div class="row align-items-end g-4 mt-1"><div class="col-lg-8"><span class="eyebrow">A thoughtful next step</span><h1 class="display-6 mt-3 mb-3">Tell us about your home.</h1><p class="section-lead mb-0">There are no trick questions. Share enough for our team to understand whether <?= e($pet['name']) ?> could be a good match.</p></div></div></div></section>
<section class="section-space-sm"><div class="container"><div class="application-shell"><div class="application-steps" aria-label="Application steps"><div class="application-step is-current" data-step-indicator><span class="application-step-number">01</span><span>About you</span></div><div class="application-step" data-step-indicator><span class="application-step-number">02</span><span>Pet</span></div><div class="application-step" data-step-indicator><span class="application-step-number">03</span><span>Lifestyle</span></div><div class="application-step" data-step-indicator><span class="application-step-number">04</span><span>Your why</span></div><div class="application-step" data-step-indicator><span class="application-step-number">05</span><span>Confirm</span></div></div><?php if ($errors): ?><div class="alert alert-danger mb-4" role="alert"><strong>Please review the highlighted details.</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form class="form-panel" method="post" action="<?= e(app_url('adoption-application.php?pet_id=' . (int) $pet['id'])) ?>" data-application-form data-validate><input type="hidden" name="form" value="adoption_application"><?= csrf_field() ?><input type="hidden" name="pet_id" value="<?= e((string) $pet['id']) ?>"><section data-application-step><span class="eyebrow">Step 01</span><h2 class="mt-3 mb-2" tabindex="-1">Your personal information</h2><p class="text-muted mb-4">Use the best contact details so we can keep you informed.</p><div class="row g-3"><div class="col-md-6"><label class="form-label" for="application-name">Full name <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" id="application-name" name="full_name" value="<?= e($values['full_name']) ?>" required></div><div class="col-md-6"><label class="form-label" for="application-email">Email <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" type="email" id="application-email" name="email" value="<?= e($values['email']) ?>" required></div><div class="col-md-6"><label class="form-label" for="application-phone">Phone <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" id="application-phone" name="phone" value="<?= e($values['phone']) ?>" required></div><div class="col-12"><label class="form-label" for="application-address">Home address <span class="required-mark" aria-hidden="true">*</span></label><textarea class="form-control" id="application-address" name="address" rows="3" required><?= e($values['address']) ?></textarea></div></div><div class="d-flex justify-content-end mt-4"><button class="btn btn-primary" type="button" data-next-step>Continue to pet <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></button></div></section><section data-application-step><span class="eyebrow">Step 02</span><h2 class="mt-3 mb-2" tabindex="-1">Your selected pet</h2><p class="text-muted mb-4">Take one more moment to make sure this is the profile you want to discuss.</p><div class="d-flex align-items-center gap-3 p-3 bg-light rounded-4"><img src="<?= e(pet_image_url($pet['image'])) ?>" data-fallback="<?= e(pet_image_fallback()) ?>" alt="<?= e($pet['name']) ?>" class="rounded-3" width="100" height="100" style="object-fit:cover"><div><h3 class="h5 mb-1"><?= e($pet['name']) ?></h3><p class="small text-muted mb-1"><?= e($pet['breed']) ?> · <?= e(pet_age((int) $pet['age_months'])) ?> · <?= e($pet['location']) ?></p><a class="small" href="<?= e(app_url('pet-details.php?id=' . (int) $pet['id'])) ?>">Review full profile <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div></div><div class="d-flex justify-content-between mt-4"><button class="btn btn-outline-secondary" type="button" data-previous-step><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back</button><button class="btn btn-primary" type="button" data-next-step>Continue to lifestyle <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></button></div></section><section data-application-step><span class="eyebrow">Step 03</span><h2 class="mt-3 mb-2" tabindex="-1">Your lifestyle</h2><p class="text-muted mb-4">These details help us understand the everyday environment <?= e($pet['name']) ?> would experience.</p><div class="row g-3"><div class="col-md-6"><label class="form-label" for="application-home">Home type <span class="required-mark" aria-hidden="true">*</span></label><select class="form-select" id="application-home" name="home_type" required><option value="">Choose one</option><option value="Apartment"<?= selected($values['home_type'], 'Apartment') ?>>Apartment</option><option value="House"<?= selected($values['home_type'], 'House') ?>>House</option><option value="Townhouse"<?= selected($values['home_type'], 'Townhouse') ?>>Townhouse</option><option value="Other"<?= selected($values['home_type'], 'Other') ?>>Other</option></select></div><div class="col-md-6"><label class="form-label" for="application-living">Living situation <span class="required-mark" aria-hidden="true">*</span></label><select class="form-select" id="application-living" name="living_situation" required><option value="">Choose one</option><option value="Living alone"<?= selected($values['living_situation'], 'Living alone') ?>>Living alone</option><option value="Living with family"<?= selected($values['living_situation'], 'Living with family') ?>>Living with family</option><option value="Shared home"<?= selected($values['living_situation'], 'Shared home') ?>>Shared home</option></select></div><div class="col-md-6"><label class="form-label" for="application-experience">Previous pet experience <span class="required-mark" aria-hidden="true">*</span></label><select class="form-select" id="application-experience" name="previous_pet_experience" required><option value="">Choose one</option><option value="First-time guardian"<?= selected($values['previous_pet_experience'], 'First-time guardian') ?>>First-time guardian</option><option value="Some experience"<?= selected($values['previous_pet_experience'], 'Some experience') ?>>Some experience</option><option value="Experienced"<?= selected($values['previous_pet_experience'], 'Experienced') ?>>Experienced</option></select></div><div class="col-md-6"><label class="form-label" for="application-family">People in your household <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" type="number" min="1" max="30" id="application-family" name="family_members" value="<?= e($values['family_members']) ?>" required></div><div class="col-md-6"><label class="form-label" for="application-working">Working hours / routine <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" id="application-working" name="working_hours" value="<?= e($values['working_hours']) ?>" placeholder="For example, weekdays 9am–5pm" required></div><div class="col-md-6"><label class="form-label" for="application-animals">Experience with animals <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" id="application-animals" name="experience_with_animals" value="<?= e($values['experience_with_animals']) ?>" placeholder="Tell us what you are comfortable with" required></div></div><div class="d-flex justify-content-between mt-4"><button class="btn btn-outline-secondary" type="button" data-previous-step><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back</button><button class="btn btn-primary" type="button" data-next-step>Continue to your why <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></button></div></section><section data-application-step><span class="eyebrow">Step 04</span><h2 class="mt-3 mb-2" tabindex="-1">Why <?= e($pet['name']) ?>?</h2><p class="text-muted mb-4">A clear, honest answer helps us understand the relationship you are hoping to build.</p><div class="mb-3"><label class="form-label" for="application-reason">Why do you want to adopt this pet? <span class="required-mark" aria-hidden="true">*</span></label><textarea class="form-control" id="application-reason" name="reason" rows="6" minlength="20" required placeholder="Tell us what drew you to this profile and what a good home looks like for you."><?= e($values['reason']) ?></textarea></div><div><label class="form-label" for="application-additional">Anything else we should know? <span class="form-hint d-inline">(optional)</span></label><textarea class="form-control" id="application-additional" name="additional_information" rows="4" placeholder="A daily routine, questions, or anything that would help us support you."><?= e($values['additional_information']) ?></textarea></div><div class="d-flex justify-content-between mt-4"><button class="btn btn-outline-secondary" type="button" data-previous-step><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back</button><button class="btn btn-primary" type="button" data-next-step>Review application <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></button></div></section><section data-application-step><span class="eyebrow">Step 05</span><h2 class="mt-3 mb-2" tabindex="-1">Review and send</h2><p class="text-muted mb-4">Please confirm that the information is accurate and that you understand the adoption process.</p><div class="soft-panel p-3 mb-3"><div class="d-flex justify-content-between gap-2"><strong>Applying for</strong><span><?= e($pet['name']) ?> · <?= e($pet['breed']) ?></span></div><div class="d-flex justify-content-between gap-2 mt-2"><strong>Contact email</strong><span><?= e($values['email']) ?></span></div></div><div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="terms" name="terms" value="1" required><label class="form-check-label" for="terms">I understand that adoption is a long-term responsibility and agree to follow through with the agreed care plan.</label><div class="invalid-feedback">Please accept the adoption terms.</div></div><div class="form-check mb-4"><input class="form-check-input" type="checkbox" id="privacy" name="privacy" value="1" required><label class="form-check-label" for="privacy">I agree that PAWORA may use these details to review my application and contact me about this adoption.</label><div class="invalid-feedback">Please accept the privacy agreement.</div></div><div class="d-flex justify-content-between"><button class="btn btn-outline-secondary" type="button" data-previous-step><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back</button><button class="btn btn-primary btn-lg" type="submit" data-submit-application>Send My Application <i class="bi bi-send ms-2" aria-hidden="true"></i></button></div></section></form></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
