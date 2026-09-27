<?php
declare(strict_types=1);

$confirmModalTitle = $confirmModalTitle ?? 'Are you sure?';
$confirmModalCancel = $confirmModalCancel ?? 'Go back';
$confirmModalSubmit = $confirmModalSubmit ?? $confirmModalTitle;
?>
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow"><div class="modal-header"><h2 class="modal-title fs-5" id="confirmActionTitle" data-confirm-title><?= e($confirmModalTitle) ?></h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><p class="mb-0" data-confirm-message>Are you sure?</p></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e($confirmModalCancel) ?></button><button type="button" class="btn btn-danger" data-confirm-submit><?= e($confirmModalSubmit) ?></button></div></div></div>
