<?php

use phpformbuilder\Form;
use phpformbuilder\database\DB;
use phpformbuilder\Validator\Validator;
use common\Utils;

session_start();
include_once '../conf/conf.php';
include_once CLASS_DIR . 'phpformbuilder/Form.php';
include_once CLASS_DIR . 'phpformbuilder/database/DB.php';
include_once CLASS_DIR . 'common/Utils.php';
if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('generator-login-form') === true) {
    include_once CLASS_DIR . 'phpformbuilder/Validator/Validator.php';
    include_once CLASS_DIR . 'phpformbuilder/Validator/Exception.php';
    $validator = new Validator($_POST);
    $required = array('generator-user-email', 'generator-purchase-code');
    foreach ($required as $required) {
        $validator->required()->validate($required);
    }
    $validator->email()->validate('generator-user-email');

    if ($validator->hasErrors()) {
        $_SESSION['errors']['generator-login-form'] = $validator->getAllErrors();
    } else {
        $db = new DB(DEBUG);
        $from = PHPCG_USERDATA_TABLE;
        $columns = array('CLIENT_EMAIL', 'LICENSE_CODE');
        $where = array(
            'CLIENT_EMAIL' => $_POST['generator-user-email'],
            'LICENSE_CODE' => $_POST['generator-purchase-code']
        );

        if ($row = $db->selectRow($from, $columns, $where)) {
            $_SESSION['generator_user_email']    = $row->CLIENT_EMAIL;
            $_SESSION['generator_purchase_code'] = $row->LICENSE_CODE;
            $_SESSION['generator_hash']          = sha1($row->LICENSE_CODE . $row->CLIENT_EMAIL);
            header('Location: generator.php');
            exit;
        } else {
            $_SESSION['msg'] = Utils::alert(LOGIN_ERROR, 'alert-danger');
        }
    }
}
$form = new Form('generator-login-form', 'vertical');
$form->addIcon('generator-user-email', '<i class="' . ICON_USER . ' text-muted"></i>', 'before');
$form->addInput('text', 'generator-user-email', '', '', 'placeholder=' . EMAIL . ', required');
$form->addIcon('generator-purchase-code', '<i class="' . ICON_PASSWORD . ' text-muted"></i>', 'before');
$form->addInput('text', 'generator-purchase-code', '', '', 'placeholder=' . PURCHASE_CODE . ', required');
$form->addBtn('submit', 'submit-btn', 1, SIGN_IN . ' <i class="' . ICON_KEY . ' fa-lg ms-2"></i>', 'class=btn btn-lg btn-primary d-block w-100');
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <title><?php echo SITENAME; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>assets/stylesheets/themes/default/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>assets/stylesheets/themes/<?php echo BOOTSTRAP_THEME; ?>/admin.min.css" type="text/css">
</head>

<body class="bg-slate-700">

    <!-- Page container -->
    <div class="container mt-5 pt-5">
        <div class="row justify-content-md-center">
            <div class="col-md-6 col-lg-4">
                <!-- Simple login form -->
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="icon-object border-slate-400 text-slate-400"><i class="<?php echo ICON_DASHBOARD; ?> fa-3x"></i></div>
                            <h5><?php echo LOGIN_TO_YOUR_ACCOUNT; ?> <small class="d-block mt-1 mb-4 text-slate"><?php echo ENTER_YOUR_CREDENTIALS_BELOW; ?></small></h5>
                            <?php
                            if (isset($_SESSION['msg'])) {
                                echo $_SESSION['msg'];
                            }
                            $form->render(); ?>
                            <p class="text-center"><a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code" target="_blank" title="Where is my Purchase Code?" rel="noopener noreferrer">Where is my Purchase Code?</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- /simple login form -->
    </div>

    <!-- /page container -->
</body>

</html>
