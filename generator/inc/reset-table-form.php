<?php

use phpformbuilder\Form;

@session_start();
include_once '../../conf/conf.php';
$form = new Form('reset-table-choices', 'form-inline', 'novalidate');

$form->addHtml('<div class="modal-body">');
if (DEMO === true) {
    $form->addHtml('<div class="alert alert-info has-icon"><h4 class="mb-0">All the CRUD operations are disabled in this demo.</h4></div>');
}
$form->addRadio('reset-data-choices', STRUCTURE_ONLY . '<span class="small text-danger-300 append">*</span>', 0);
$form->addRadio('reset-data-choices', STRUCTURE_AND_DATA . '<span class="small text-danger-400 append">**</span>', 1);
$form->printRadioGroup('reset-data-choices', '', false);
$form->addHtml(RESET_DATA_CHOICES_HELP_1);
$form->addHtml(RESET_DATA_CHOICES_HELP_2);
$form->addHtml(RESET_DATA_CHOICES_HELP_3);
$form->addHtml('</div>');

$form->addHtml('<div class="modal-footer justify-content-center">');
$form->addBtn('button', 'reset-table-choices-cancel-btn', 'cancel', '<i class="' . ICON_CANCEL . ' prepend"></i>' . CANCEL, 'class=btn btn-warning', 'reset-table-choices-btn-group');
$form->addBtn('button', 'reset-table-choices-submit-btn', 'submit', SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-primary', 'reset-table-choices-btn-group');
$form->printBtnGroup('reset-table-choices-btn-group');
$form->addHtml('</div>');
?>
<div class="modal-header text-bg-primary">
    <p class="modal-title h1 mb-0 fs-5" id="relationship-modal-label"><?php echo RESET_TABLE_DATA . ' ' . $_POST['table']; ?></p>
    <button type="button" class="btn-close modal-close" aria-label="Close"></button>
</div>

<?php echo $form->html; ?>
