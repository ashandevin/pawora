<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

$errors = [];
$submittedReference = trim((string) ($_GET['reference'] ?? ''));
$petId = (int) ($_GET['pet_id'] ?? 0);
$pet = $petId > 0 ? db_one('SELECT id, name FROM pets WHERE id = ?', [$petId]) : null;
$categories = ['General Question', 'Complaint', 'Technical Issue', 'Pet Inquiry', 'Adoption Issue', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'contact') {
    if (!verify_csrf()) $errors[] = 'Your form session expired. Please try again.';
    $errors = array_merge($errors, length_errors($_POST, ['name' => ['Your name', 120], 'email' => ['Email address', 190], 'subject' => ['Subject', 180], 'message' => ['Message', 5000]]));
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    if ($name === '') $errors[] = 'Please enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (!in_array($category, $categories, true)) $errors[] = 'Choose a support category.';
    if (text_length($subject) < 4) $errors[] = 'Add a short subject.';
    if (text_length($message) < 15) $errors[] = 'Tell us a little more so we can help.';
    if (!$errors) {
        try {
            $reference = generate_reference('SUP');
            $statement = db()->prepare('INSERT INTO contact_messages (name, email, category, subject, message, reference_number) VALUES (?, ?, ?, ?, ?, ?)');
            $statement->execute([$name, $email, $category, $subject, $message, $reference]);
            set_flash('success', 'Your message has been received. Reference: ' . $reference);
            redirect('contact.php');
        } catch (Throwable $exception) {
            $errors[] = 'We could not send your message right now. Please try again.';
        }
    }
}

$user = current_user();
$pageTitle = 'Contact PAWORA';
$activePage = 'contact';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><div class="row align-items-end g-4"><div class="col-lg-8"><span class="eyebrow">We are here to help</span><h1 class="display-6 mt-3 mb-3">Let’s talk about what matters.</h1><p class="section-lead mb-0">Ask a question, share feedback about an adoption or tell us when something needs attention.</p></div></div></div></section>
<section class="section-space-sm"><div class="container"><div class="row g-4 align-items-start"><div class="col-lg-7"><div class="contact-card p-4 p-lg-5"><div class="d-flex justify-content-between align-items-start gap-3 mb-4"><div><span class="eyebrow">Support request</span><h2 class="section-title mt-3 mb-2">How can we help?</h2><p class="text-muted mb-0">We will give you a reference number after your message is received.</p></div><i class="bi bi-chat-square-text text-secondary fs-3" aria-hidden="true"></i></div><?php if ($errors): ?><div class="alert alert-danger" role="alert"><strong>Something needs your attention.</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="post" action="<?= e(app_url('contact.php')) ?>" data-validate><input type="hidden" name="form" value="contact"><?= csrf_field() ?><?php if ($pet): ?><div class="alert alert-info"><i class="bi bi-heart me-2" aria-hidden="true"></i>Your message will include a reference to <strong><?= e($pet['name']) ?></strong>.</div><?php endif; ?><div class="row g-3"><div class="col-md-6"><label class="form-label" for="contact-name">Full name <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" id="contact-name" name="name" value="<?= e($user['name'] ?? '') ?>" required></div><div class="col-md-6"><label class="form-label" for="contact-email">Email <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" type="email" id="contact-email" name="email" value="<?= e($user['email'] ?? '') ?>" required></div><div class="col-md-6"><label class="form-label" for="contact-category">Category <span class="required-mark" aria-hidden="true">*</span></label><select class="form-select" id="contact-category" name="category" required><option value="">Choose a category</option><?php foreach ($categories as $option): ?><option value="<?= e($option) ?>"<?= selected($option, $_POST['category'] ?? ($_GET['category'] ?? '')) ?>><?= e($option) ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label" for="contact-subject">Subject <span class="required-mark" aria-hidden="true">*</span></label><input class="form-control" id="contact-subject" name="subject" value="<?= e($_POST['subject'] ?? ($pet ? 'Question about ' . $pet['name'] : '')) ?>" required></div><div class="col-12"><label class="form-label" for="contact-message">Message <span class="required-mark" aria-hidden="true">*</span></label><textarea class="form-control" id="contact-message" name="message" rows="7" minlength="15" required placeholder="Share the details that will help us respond well."><?= e($_POST['message'] ?? '') ?></textarea></div></div><button class="btn btn-primary btn-lg mt-4" type="submit">Send my message <i class="bi bi-send ms-2" aria-hidden="true"></i></button></form></div></div><div class="col-lg-5"><div class="contact-card mb-4"><h2 class="h5 mb-3">Other ways we can help</h2><div class="contact-method"><i class="bi bi-chat-heart" aria-hidden="true"></i><div><strong>Pawora Assistant</strong><small>Get quick answers about adoption, documents and availability.</small><a class="d-inline-block small mt-1" href="#" data-chatbot-open>Open assistant <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div></div><div class="contact-method"><i class="bi bi-instagram" aria-hidden="true"></i><div><strong>Instagram</strong><small>Follow Pawora stories and updates.</small><a class="d-inline-block small mt-1" href="https://www.instagram.com/" target="_blank" rel="noopener">Visit Instagram</a></div></div><div class="contact-method"><i class="bi bi-tiktok" aria-hidden="true"></i><div><strong>TikTok</strong><small>Meet the pets behind the profiles.</small><a class="d-inline-block small mt-1" href="https://www.tiktok.com/" target="_blank" rel="noopener">Visit TikTok</a></div></div></div><div class="soft-panel p-4"><span class="eyebrow">Please note</span><p class="small text-muted mt-3 mb-0">PAWORA is a coursework prototype and cannot provide real veterinary or emergency care. For urgent animal welfare concerns, contact a qualified local professional.</p></div></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
