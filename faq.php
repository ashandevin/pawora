<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';
$pageTitle = 'Frequently Asked Questions';
$activePage = 'care';
require __DIR__ . '/includes/header.php';
$faqs = [
    ['How does adoption work?', 'Start by browsing the pet collection and reading each profile. When you are ready, sign in or create an account, submit an application and follow the status shown in your dashboard. Our team will contact you with the next step.'],
    ['What documents are required?', 'The exact checklist can vary, but you may need photo identification, proof of address and a home conversation. We will explain any additional requirements clearly before an adoption is finalised.'],
    ['How long does adoption take?', 'There is no single deadline. A straightforward application may move quickly, while a pet needing extra support or a home visit can take longer. We prefer to communicate honestly rather than promise a date.'],
    ['Can I adopt more than one pet?', 'It may be possible, but we consider the welfare of each animal and the household’s capacity. Please discuss your plan with our team before applying for more than one pet.'],
    ['Can I visit a pet before applying?', 'Yes, visits or conversations can be part of the process when appropriate. Contact us with the pet name and your availability so we can guide the arrangement.'],
    ['What happens after I submit an application?', 'You will receive a reference number and a confirmation screen. Your application then moves through review, interview, approval and completion, with updates available in your dashboard.'],
    ['What if the pet is already adopted?', 'The profile will show its current status. If a waiting list is available, you can join it from the profile and we will keep your contact details with that pet record.'],
    ['How do I get support after adoption?', 'Use the Contact page or Pawora Assistant for general guidance. For urgent health concerns, contact a qualified veterinary professional or local emergency service.'],
];
?>
<section class="page-hero"><div class="container"><div class="row align-items-end g-4"><div class="col-lg-8"><span class="eyebrow">Clear answers</span><h1 class="display-6 mt-3 mb-3">Questions are part of caring.</h1><p class="section-lead mb-0">Find helpful answers about adoption, applications, visits and life after adoption.</p></div><div class="col-lg-4 text-lg-end"><a class="btn btn-primary" href="<?= e(app_url('contact.php')) ?>">Ask a question <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a></div></div></div></section>
<section class="section-space-sm"><div class="container"><div class="application-shell"><div class="accordion faq-accordion" id="paworaFaq"><?php foreach ($faqs as $index => [$question, $answer]): ?><div class="accordion-item"><h3 class="accordion-header" id="faq-heading-<?= e((string) $index) ?>"><button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq-item-<?= e((string) $index) ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="faq-item-<?= e((string) $index) ?>"><?= e($question) ?></button></h3><div id="faq-item-<?= e((string) $index) ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="faq-heading-<?= e((string) $index) ?>" data-bs-parent="#paworaFaq"><div class="accordion-body"><?= e($answer) ?></div></div></div><?php endforeach; ?></div><div class="soft-panel p-4 p-lg-5 mt-5 text-center"><span class="eyebrow">Still unsure?</span><h2 class="section-title mt-3 mb-2">Ask Pawora directly.</h2><p class="section-lead mx-auto">Our support team can help with general questions, pet inquiries and adoption issues.</p><a class="btn btn-dark mt-3" href="<?= e(app_url('contact.php')) ?>">Contact support <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
