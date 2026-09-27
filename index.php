<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'feedback') {
    $errors = [];
    if (!verify_csrf()) $errors[] = 'Your form session expired. Please try again.';
    $errors = array_merge($errors, length_errors($_POST, ['comment' => ['Your feedback', 2000]]));
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT) ?: 0;
    $comment = trim((string) ($_POST['comment'] ?? ''));
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Choose a rating from 1 to 5 stars.';
    }
    if (text_length($comment) < 10) {
        $errors[] = 'Tell us a little more about your experience.';
    }
    if ($errors) {
        set_flash('error', implode(' ', $errors));
    } else {
        try {
            $statement = db()->prepare('INSERT INTO feedback (user_id, rating, comment) VALUES (?, ?, ?)');
            $statement->execute([is_logged_in() ? (int) $_SESSION['user_id'] : null, $rating, $comment]);
            set_flash('success', 'Thank you. Your feedback is with the PAWORA team.');
        } catch (Throwable $exception) {
            set_flash('error', 'We could not save your feedback right now. Please try again.');
        }
    }
    redirect('index.php#feedback');
}

$featuredPets = db_all('SELECT p.*, CASE WHEN f.id IS NULL THEN 0 ELSE 1 END AS is_favorite FROM pets p LEFT JOIN favorites f ON f.pet_id = p.id AND f.user_id = ? WHERE p.featured = 1 ORDER BY p.updated_at DESC LIMIT 6', [is_logged_in() ? (int) $_SESSION['user_id'] : null]);
$featuredProducts = db_all("SELECT p.*, c.name AS category_name FROM products p INNER JOIN categories c ON c.id = p.category_id WHERE p.status <> 'Inactive' AND c.status = 'Active' ORDER BY p.stock_quantity = 0 ASC, p.updated_at DESC LIMIT 3");
$stories = db_all('SELECT * FROM success_stories ORDER BY adoption_date DESC LIMIT 3');
$pageTitle = 'Everything They Need. A Home They Deserve.';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7 hero-copy reveal">
                <span class="eyebrow">A calmer way to adopt</span>
                <h1 class="display-title mt-3 mb-3">Find your new<br><span class="text-primary">best friend.</span></h1>
                <p>Give a loving pet the second chance they deserve. Discover pets waiting for a safe and caring forever home.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary btn-lg" href="<?= e(app_url('pets.php')) ?>">Find a Pet <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                    <a class="btn btn-outline-dark btn-lg" href="<?= e(app_url('shop.php')) ?>">Shop essentials</a>
                    <a class="btn btn-outline-dark btn-lg" href="<?= e(app_url('adoption-process.php')) ?>">How Adoption Works</a>
                </div>
                <div class="trust-row" aria-label="PAWORA trust principles">
                    <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i>Verified profiles</span>
                    <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i>Responsible adoption</span>
                    <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i>Transparent process</span>
                    <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i>Support after adoption</span>
                </div>
            </div>
            <div class="col-lg-5 hero-visual reveal">
                <div class="hero-image-wrap">
                    <img class="hero-image" src="https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&w=1200&q=88" data-fallback="<?= e(pet_image_fallback()) ?>" alt="Golden retriever resting in a bright home" width="900" height="1100" fetchpriority="high">
                    <div class="hero-note"><i class="bi bi-shield-check" aria-hidden="true"></i><div><small>Thoughtful matching</small><strong>Welfare comes first</strong></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container" aria-labelledby="pet-search-title">
    <div class="search-panel">
        <h2 id="pet-search-title" class="visually-hidden">Search available pets</h2>
        <form action="<?= e(app_url('pets.php')) ?>" method="get" class="row g-3 align-items-end" data-validate>
            <div class="col-6 col-lg-2"><label class="form-label" for="home-name">Pet name</label><input class="form-control" id="home-name" name="q" placeholder="Luna"></div>
            <div class="col-6 col-lg-2"><label class="form-label" for="home-species">Species</label><select class="form-select" id="home-species" name="species"><option value="">Any species</option><option value="Dog">Dog</option><option value="Cat">Cat</option><option value="Rabbit">Rabbit</option><option value="Bird">Bird</option><option value="Other">Other</option></select></div>
            <div class="col-6 col-lg-2"><label class="form-label" for="home-breed">Breed</label><input class="form-control" id="home-breed" name="breed" placeholder="Golden Retriever"></div>
            <div class="col-6 col-lg-2"><label class="form-label" for="home-age">Age</label><select class="form-select" id="home-age" name="age"><option value="">Any age</option><option value="puppy">Under 1 year</option><option value="young">1–3 years</option><option value="adult">4–7 years</option><option value="senior">8+ years</option></select></div>
            <div class="col-6 col-lg-2"><label class="form-label" for="home-gender">Gender</label><select class="form-select" id="home-gender" name="gender"><option value="">Any gender</option><option value="Female">Female</option><option value="Male">Male</option></select></div>
            <div class="col-6 col-lg-2"><label class="form-label" for="home-location">Location</label><input class="form-control" id="home-location" name="location" placeholder="Colombo"></div>
            <div class="col-12 d-lg-flex justify-content-end"><button class="btn btn-primary w-100" type="submit">Find My Pet <i class="bi bi-search ms-2" aria-hidden="true"></i></button></div>
        </form>
    </div>
</section>

<?php show_database_notice(); ?>

<section class="section-space" id="featured-pets" aria-labelledby="featured-title">
    <div class="container">
        <div class="section-heading-row">
            <div><span class="eyebrow">Meet your match</span><h2 class="section-title mt-3 mb-2" id="featured-title">A few friends are waiting.</h2><p class="section-lead">Browse a few of the pets currently looking for thoughtful, committed adopters.</p></div>
            <a class="btn btn-outline-primary" href="<?= e(app_url('pets.php')) ?>">View all pets <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a>
        </div>
        <?php if ($featuredPets): ?>
            <div class="row g-4">
                <?php foreach ($featuredPets as $pet): ?>
                    <div class="col-12 col-sm-6 col-lg-4"><?php require __DIR__ . '/includes/pet-card.php'; ?></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="bi bi-heart" aria-hidden="true"></i></div><h3>Our profiles are getting ready.</h3><p class="text-muted mb-0">Import the sample database to see the PAWORA pet collection here.</p></div>
        <?php endif; ?>
    </div>
</section>

<section class="section-space pt-0" id="shop-preview" aria-labelledby="shop-preview-title">
    <div class="container">
        <div class="section-heading-row">
            <div><span class="eyebrow">Everyday care</span><h2 class="section-title mt-3 mb-2" id="shop-preview-title">Small essentials. Better days.</h2><p class="section-lead">Thoughtful food, play and care products for the pets already part of your family.</p></div>
            <a class="btn btn-outline-primary" href="<?= e(app_url('shop.php')) ?>">Explore the shop <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a>
        </div>
        <?php if ($featuredProducts): ?>
            <div class="row g-4"><?php foreach ($featuredProducts as $product): ?><div class="col-12 col-sm-6 col-lg-4"><?php require __DIR__ . '/includes/product-card.php'; ?></div><?php endforeach; ?></div>
        <?php else: ?>
            <div class="empty-state"><div class="empty-state-icon"><i class="bi bi-bag-heart" aria-hidden="true"></i></div><h3>Care essentials are getting ready.</h3><p class="text-muted mb-0">Import the updated PAWORA database to see the shop collection here.</p></div>
        <?php endif; ?>
    </div>
</section>

<section class="section-space pt-0" aria-labelledby="process-title">
    <div class="container">
        <div class="soft-panel p-4 p-lg-5">
            <div class="row align-items-end g-4 mb-4"><div class="col-lg-7"><span class="eyebrow">A clear path forward</span><h2 class="section-title mt-3 mb-2" id="process-title">Adoption, without the guesswork.</h2><p class="section-lead mb-0">You’ll always know what is happening, what comes next and who to contact.</p></div><div class="col-lg-5 text-lg-end"><a class="btn btn-dark" href="<?= e(app_url('adoption-process.php')) ?>">Read the adoption guide <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a></div></div>
            <div class="row g-3">
                <?php foreach ([['01', 'Discover', 'Browse real profiles and save the pets that speak to you.'], ['02', 'Learn', 'Understand each pet’s needs, history and care requirements.'], ['03', 'Apply', 'Share your home and lifestyle through a thoughtful application.'], ['04', 'Meet', 'Talk with our team and find the right fit for everyone.'], ['05', 'Adopt', 'Complete the process and start your next chapter together.']] as [$number, $title, $copy]): ?>
                    <div class="col-6 col-lg"><div class="process-card h-100"><div class="process-number"><?= e($number) ?></div><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></div></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="section-space pt-0" aria-labelledby="stories-title">
    <div class="container">
        <div class="section-heading-row"><div><span class="eyebrow">Proof of care</span><h2 class="section-title mt-3 mb-2" id="stories-title">Happy beginnings.</h2><p class="section-lead mb-0">Every adoption story reminds us why a patient, informed choice matters.</p></div><a class="btn btn-outline-primary" href="<?= e(app_url('success-stories.php')) ?>">Read more stories <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a></div>
        <div class="row g-4">
            <?php foreach ($stories as $story): ?>
                <div class="col-md-4"><article class="story-card"><img class="story-image" src="<?= e(pet_image_url($story['image'])) ?>" data-fallback="<?= e(pet_image_fallback()) ?>" alt="<?= e($story['pet_name']) ?>'s adoption story" loading="lazy"><div class="story-card-body"><span class="eyebrow">Success story</span><h3 class="mt-3 mb-2"><?= e($story['pet_name']) ?>’s next chapter</h3><p><?= e($story['story']) ?></p><p class="story-date mt-3 mb-0"><i class="bi bi-calendar3 me-1" aria-hidden="true"></i>Adopted <?= e(format_date($story['adoption_date'])) ?></p></div></article></div>
            <?php endforeach; ?>
            <?php if (!$stories): ?><div class="col-12"><div class="empty-state"><h3>Stories are on their way.</h3><p class="text-muted mb-0">Add approved stories to the database to see them here.</p></div></div><?php endif; ?>
        </div>
    </div>
</section>

<section class="section-space pt-0" id="feedback" aria-labelledby="feedback-title">
    <div class="container">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-7"><div class="h-100 p-4 p-lg-5 soft-panel"><span class="eyebrow">Your voice matters</span><h2 class="section-title mt-3 mb-2" id="feedback-title">How can we make adoption easier?</h2><p class="section-lead">Tell us what is working, what is confusing or what would make PAWORA more helpful for you and your future pet.</p><div class="d-flex gap-3 mt-4"><div class="trust-icon mb-0"><i class="bi bi-chat-square-heart" aria-hidden="true"></i></div><div><strong class="d-block">Small feedback, real care.</strong><span class="text-muted small">Approved feedback may appear on our public pages after review.</span></div></div></div></div>
            <div class="col-lg-5"><form class="h-100 p-4 p-lg-5 bg-white border rounded-4 shadow-sm" method="post" action="<?= e(app_url('index.php#feedback')) ?>" data-validate><input type="hidden" name="form" value="feedback"><?= csrf_field() ?><h3 class="h5 mb-4">Share your experience</h3><div class="mb-3"><label class="form-label" for="feedback-rating">Your rating <span class="required-mark" aria-hidden="true">*</span></label><div class="rating-input" role="radiogroup" aria-label="Rating out of five stars"><input class="form-check-input" type="radio" name="rating" id="rating-5" value="5" required><label for="rating-5" aria-label="5 stars">★</label><input class="form-check-input" type="radio" name="rating" id="rating-4" value="4"><label for="rating-4" aria-label="4 stars">★</label><input class="form-check-input" type="radio" name="rating" id="rating-3" value="3"><label for="rating-3" aria-label="3 stars">★</label><input class="form-check-input" type="radio" name="rating" id="rating-2" value="2"><label for="rating-2" aria-label="2 stars">★</label><input class="form-check-input" type="radio" name="rating" id="rating-1" value="1"><label for="rating-1" aria-label="1 star">★</label></div><small class="form-hint">1 = needs improvement · 5 = excellent</small></div><div class="mb-4"><label class="form-label" for="feedback-comment">Your comment <span class="required-mark" aria-hidden="true">*</span></label><textarea class="form-control" id="feedback-comment" name="comment" rows="5" minlength="10" required placeholder="What would you like to share?"></textarea></div><button class="btn btn-primary w-100" type="submit">Send feedback <i class="bi bi-send ms-2" aria-hidden="true"></i></button></form></div>
        </div>
    </div>
</section>

<section class="section-space pt-0" aria-labelledby="social-title">
    <div class="container"><div class="cta-band p-4 p-lg-5"><div class="row align-items-center g-4"><div class="col-lg-8"><span class="eyebrow text-secondary">Follow the journey</span><h2 class="mt-3 mb-2" id="social-title">More paws, more stories.</h2><p class="mb-0">Meet the animals behind the profiles and see the small moments that make a big difference.</p></div><div class="col-lg-4 d-flex flex-wrap justify-content-lg-end gap-2"><a class="btn btn-secondary" href="https://www.instagram.com/" target="_blank" rel="noopener">Follow on Instagram <i class="bi bi-instagram ms-2" aria-hidden="true"></i></a><a class="btn btn-outline-light" href="https://www.tiktok.com/" target="_blank" rel="noopener">TikTok <i class="bi bi-tiktok ms-2" aria-hidden="true"></i></a></div></div></div></div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
