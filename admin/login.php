<?php
use secure\Secure;
use phpformbuilder\Form;
use phpformbuilder\FormExtended;
use phpformbuilder\Validator\Validator;

session_start();
include_once '../conf/conf.php';
include_once ADMIN_DIR . 'secure/conf/conf.php';
include_once ADMIN_DIR . 'secure/class/secure/Secure.php';
include_once CLASS_DIR . 'phpformbuilder/Form.php';
include_once CLASS_DIR . 'phpformbuilder/database/DB.php';
if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('login-form') === true) {
    $validator = Form::validate('login-form', FORMVALIDATION_PHP_LANG);
    $validator->email()->validate('user-email');

    if ($validator->hasErrors()) {
        $_SESSION['errors']['login-form'] = $validator->getAllErrors();
    } else {
        if (!ADMIN_LOCKED) {
            $_SESSION['msg'] = '<div class="alert alert-warning"><p>' . ADMIN_AUTHENTICATION_MODULE_IS_DISABLED . '</p><p><a href="' . ADMINREDIRECTPAGE . '">' . ADMINREDIRECTPAGE . '</a></p></div>';
        } else {
            Secure::testUser();
        }
    }
}
$form = new FormExtended('login-form', 'vertical', 'novalidate');
$form->setAction(ADMIN_URL . 'login');
$form->addIcon('user-email', '<i class="' . ICON_USER . '"></i>', 'before');
$form->addInput('text', 'user-email', '', '', 'placeholder=' . EMAIL . ', required');
$form->addIcon('user-password', '<i class="' . ICON_PASSWORD . '"></i>', 'before');
$form->addInput('password', 'user-password', '', '', 'placeholder=' . PASSWORD . ', required');
$form->addBtn('submit', 'submit-btn', 1, SIGN_IN . ' <i class="' . ICON_KEY . ' fa-lg append"></i>', 'class=btn btn-outline-danger btn-lg mt-5 mb-3, data-ladda-button=true, data-style=zoom-in');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title><?php echo ADMIN . ' ' . SITENAME; ?></title>
    <meta name="description" content="<?php echo ADMIN . ' ' . SITENAME; ?> Login page">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://<?php echo $_SERVER['HTTP_HOST']; ?>/admin/login" rel="canonical">
    <?php
    include_once 'inc/css-includes.php';
    $form->printIncludes('css');
    ?>
    <link rel="stylesheet" href="assets/stylesheets/style.css" type="text/css" media="screen">
</head>
<body class="bg-secondary-700">

    <!-- Page container -->
    <div class="container mt-5 pt-5">
        <div class="row justify-content-md-center">
            <div class="col-md-6 col-lg-4">
                <!-- Simple login form -->
                <div class="card">
                    <div class="card-body p-5 text-bg-secondary-800">
                        <div class="text-center">
                            <img src="assets/images/logo-height-100-whitea.png" alt="500">    
                            <h1 class="h5"><?php echo LOGIN_TO_YOUR_ACCOUNT; ?> <small class="d-block mt-1 mb-4"><?php echo ENTER_YOUR_CREDENTIALS_BELOW; ?></small></h1>
                            <?php
                            if (isset($_SESSION['msg'])) {
                                echo $_SESSION['msg'];
                                unset($_SESSION['msg']);
                            }
                            $form->render(); ?>
                            <!-- <a href="login_password_recover.html"><?php echo FORGOT_PASSWORD; ?> ?</a> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- /simple login form -->
    </div>

    <!-- /page container -->
    <?php
    include_once 'inc/js-includes.php';
    $form->printIncludes('js');
    $form->printJsCode();
    ?>
</body>
</html>
