<?php
declare(strict_types=1);
?>
</main>
<footer class="pawora-footer mt-5">
    <div class="container">
        <div class="row g-4 py-5">
            <div class="col-lg-4">
                <a class="footer-brand d-inline-flex align-items-center gap-2 mb-3" href="<?= e(app_url('index.php')) ?>">
                    <span class="brand-mark brand-mark-light" aria-hidden="true">
                        <svg viewBox="0 0 48 48"><path d="M24 41.5c-7.4 0-13.4-4.6-13.4-10.3 0-4 3.2-6.6 6.1-6.6 2 0 3.6 1 4.9 2.4 1.5-1.7 3.7-3.2 6.4-3.2 3.7 0 6.6 2.9 6.6 7 0 5.7-4.5 10.7-10.6 10.7Z"/><ellipse cx="13.3" cy="20.2" rx="4.1" ry="5.1" transform="rotate(-27 13.3 20.2)"/><ellipse cx="21.2" cy="13.7" rx="4.1" ry="5.1" transform="rotate(-9 21.2 13.7)"/><ellipse cx="30.5" cy="13.7" rx="4.1" ry="5.1" transform="rotate(9 30.5 13.7)"/><ellipse cx="38.4" cy="20.2" rx="4.1" ry="5.1" transform="rotate(27 38.4 20.2)"/></svg>
                    </span>
                    <span>PAWORA</span>
                </a>
                <p class="footer-copy mb-3">A clearer, kinder path to a forever home. Every decision starts with the pet’s wellbeing.</p>
                <div class="d-flex gap-2">
                    <a class="social-link" href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="PAWORA on Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                    <a class="social-link" href="https://www.tiktok.com/" target="_blank" rel="noopener" aria-label="PAWORA on TikTok"><i class="bi bi-tiktok" aria-hidden="true"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h2 class="footer-heading">Explore</h2>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?= e(app_url('pets.php')) ?>">Find a Pet</a></li>
                    <li><a href="<?= e(app_url('shop.php')) ?>">Pet care shop</a></li>
                    <li><a href="<?= e(app_url('adoption-process.php')) ?>">How it works</a></li>
                    <li><a href="<?= e(app_url('success-stories.php')) ?>">Stories</a></li>
                    <li><a href="<?= e(app_url('pet-care.php')) ?>">Pet care</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h2 class="footer-heading">Support</h2>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?= e(app_url('faq.php')) ?>">FAQs</a></li>
                    <li><a href="<?= e(app_url('contact.php')) ?>">Contact us</a></li>
                    <li><a href="<?= e(app_url('user/applications.php')) ?>">Track an application</a></li>
                    <li><a href="<?= e(app_url('login.php')) ?>">Your account</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <div class="footer-note p-4 rounded-4">
                    <span class="eyebrow text-secondary">A note from PAWORA</span>
                    <p class="mb-0 mt-2">Adoption is a lifelong commitment. We encourage every applicant to choose with patience, clarity and care.</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2 py-4">
            <small>© <?= e(date('Y')) ?> PAWORA. Demonstration coursework project.</small>
            <small>Built for responsible adoption.</small>
        </div>
    </div>
</footer>
<?php require __DIR__ . '/chatbot.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= e(asset_url('js/script.js')) ?>"></script>
<script src="<?= e(asset_url('js/validation.js')) ?>"></script>
<script src="<?= e(asset_url('js/chatbot.js')) ?>"></script>
</body>
</html>
