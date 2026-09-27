<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

if (is_logged_in()) {
    redirect('user/dashboard.php');
}

$errors = [];
$name = trim((string) ($_POST['name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$phone = trim((string) ($_POST['phone'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirmation'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) $errors[] = 'Your form session expired. Please try again.';
    $errors = array_merge($errors, length_errors($_POST, ['name' => ['Full name', 120], 'email' => ['Email address', 190], 'phone' => ['Phone number', 40], 'address' => ['Address', 2000]]));
    if ($name === '' || text_length($name) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($phone !== '' && (preg_match('/[^0-9+()\-\s]/', $phone) || strlen(preg_replace('/\D/', '', $phone) ?? '') < 7)) $errors[] = 'Please enter a valid phone number.';
    if (text_length($password) < 8) $errors[] = 'Your password must be at least 8 characters long.';
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) $errors[] = 'Your password needs at least one letter and one number.';
    if ($password !== $passwordConfirm) $errors[] = 'The passwords do not match.';
    if (!$errors) {
        try {
            $check = db()->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            if ($check->fetchColumn()) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $statement = db()->prepare('INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)');
                $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone ?: null, $address ?: null]);
                $_SESSION['pending_login_email'] = $email;
                set_flash('success', 'Your PAWORA account is ready. Sign in to continue.');
                redirect('login.php');
            }
        } catch (Throwable $exception) {
            $errors[] = 'We could not create your account right now. Please try again.';
        }
    }
}

$pageTitle = 'Create your account';
$activePage = '';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout"><div class="container"><div class="row g-4 align-items-stretch justify-content-center"><div class="col-lg-6"><div class="auth-art"><img src="https://images.unsplash.com/photo-1601758125948-8b9c7f3e0d7f?auto=format&fit=crop&w=1000&q=85" data-fallback="<?= e(pet_image_fallback()) ?>" alt="A calm dog looking toward the camera" loading="lazy"><div class="auth-art-copy"><span class="eyebrow text-secondary">A better way to begin</span><h2 class="mt-3">Your next chapter starts with a profile.</h2><p>Save pets, send thoughtful applications and keep every update in one calm place.</p></div></div></div><div class="col-lg-5"><div class="auth-card"><span class="eyebrow">Join PAWORA</span><h1 class="section-title mt-3 mb-2">Create your account.</h1><p class="text-muted mb-4">It only takes a minute to make responsible adoption easier to manage.</p><?php if ($errors): ?><div class="alert alert-danger" role="alert"><strong>Please check the form.</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="post" action="<?= e(app_url('register.php')) ?>" data-validate novalidate><?= csrf_field() ?><div class="mb-3"><label class="form-label" for="register-name">Full name <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" id="register-name" name="name" value="<?= e($name) ?>" autocomplete="name" minlength="2" required><div class="invalid-feedback">Please enter your full name.</div></div><div class="mb-3"><label class="form-label" for="register-email">Email <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" type="email" id="register-email" name="email" value="<?= e($email) ?>" autocomplete="email" required><div class="invalid-feedback">Please enter a valid email.</div></div><div class="row g-3 mb-3"><div class="col-sm-6"><label class="form-label" for="register-phone">Phone</label><input class="form-control" id="register-phone" name="phone" value="<?= e($phone) ?>" autocomplete="tel" placeholder="+94 …"></div><div class="col-sm-6"><label class="form-label" for="register-address">Address</label><input class="form-control" id="register-address" name="address" value="<?= e($address) ?>" autocomplete="street-address"></div></div><div class="mb-3"><label class="form-label" for="register-password">Password <span class="required-mark" aria-hidden="true">*</span></label><div class="input-group"><input class="form-control" type="password" id="register-password" name="password" minlength="8" autocomplete="new-password" required><button class="btn btn-outline-secondary" type="button" data-password-toggle="register-password" aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button></div><div class="form-hint">8+ characters with at least one letter and one number.</div></div><div class="mb-4"><label class="form-label" for="register-password-confirmation">Confirm password <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" type="password" id="register-password-confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required><div class="invalid-feedback">The passwords do not match.</div></div><button class="btn btn-primary btn-lg w-100" type="submit">Create my account <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></button></form><p class="small text-muted text-center mt-4 mb-0">Already have an account? <a href="<?= e(app_url('login.php')) ?>">Sign in</a></p></div></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
