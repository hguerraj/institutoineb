<?php
use phpformbuilder\Form;

@session_start();
include_once '../../conf/conf.php';
$form_remove_authentication_module = new Form('form-remove-authentication-module', 'horizontal', 'novalidate');
$form_remove_authentication_module->setAction(GENERATOR_URL . 'generator.php');
$form_remove_authentication_module->addHtml('<div class="modal-body">');
if (DEMO === true) {
    $form_remove_authentication_module->addHtml('<div class="alert alert-info has-icon"><h4 class="mb-0">All the CRUD operations are disabled in this demo.</h4></div>');
}
$form_remove_authentication_module->addHtml(str_replace('%USERS_TABLE%', USERS_TABLE, REMOVE_ADMIN_AUTHENTICATION_MODULE_HELPER));
$form_remove_authentication_module->addRadio('remove', NO, 0, 'checked');
$form_remove_authentication_module->addRadio('remove', YES, 1);
$form_remove_authentication_module->printRadioGroup('remove', REMOVE . ' ' . ADMIN_AUTHENTICATION_MODULE . ' ?');

$form_remove_authentication_module->addHtml('<div class="modal-footer justify-content-center">');
$form_remove_authentication_module->addBtn('button', 'remove-authentication-module-cancel-btn', 'cancel', '<i class="' . ICON_CANCEL . ' prepend"></i>' . CANCEL, 'class=btn btn-warning', 'remove-authentication-module-btn-group');
$form_remove_authentication_module->addBtn('submit', 'remove-authentication-module-submit-btn', 'submit', SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-primary, data-ladda-button=true, data-style=zoom-in', 'remove-authentication-module-btn-group');
$form_remove_authentication_module->printBtnGroup('remove-authentication-module-btn-group');
$form_remove_authentication_module->addHtml('</div>');
?>

<div class="modal-header text-bg-primary">
    <p class="modal-title h1 mb-0 fs-5"><?php echo REMOVE . ' ' . ADMIN_AUTHENTICATION_MODULE . ' ?' ?></p>
    <button type="button" class="btn-close modal-close" aria-label="Close"></button>
</div>

<?php $form_remove_authentication_module->render(); ?>
