<?php

use phpformbuilder\Form;

@session_start();
include_once '../../conf/conf.php';
$form = new Form('form-reinstall-phpcg', 'form-inline', 'novalidate');
$form->setAction(GENERATOR_URL . 'generator.php');
$form->addHtml('<div class="modal-body">');
if (DEMO === true) {
    $form->addHtml('<div class="alert alert-info has-icon"><h4 class="mb-0">All the CRUD operations are disabled in this demo.</h4></div>');
}
$form->addHtml(\str_replace('%PHPCG_USERDATA_TABLE%', PHPCG_USERDATA_TABLE, REINSTALL_HELP));

$form->centerContent();
$form->addRadio('do-reinstall', NO, 0, 'checked');
$form->addRadio('do-reinstall', YES, 1);
$form->printRadioGroup('do-reinstall', REINSTALL . '?');
$form->addHtml('</div>');

$form->addHtml('<div class="modal-footer justify-content-center">');
$form->addBtn('button', 'reinstall-phpcg-cancel-btn', 'cancel', '<i class="' . ICON_CANCEL . ' prepend"></i>' . CANCEL, 'class=btn btn-warning', 'reinstall-phpcg-btn-group');
$form->addBtn('button', 'reinstall-phpcg-submit-btn', 'submit', SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-primary, data-ladda-button=true', 'reinstall-phpcg-btn-group');
$form->printBtnGroup('reinstall-phpcg-btn-group');
$form->addHtml('</div>');
?>
<div class="modal-header text-bg-primary">
    <p class="modal-title h1 mb-0 fs-5" id="reinstall-modal-label"><?php echo REINSTALL . ' PHPCG'; ?></p>
    <button type="button" class="btn-close modal-close" aria-label="Close"></button>
</div>

<?php $form->render(); ?>
