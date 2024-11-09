<?php

session_start();

include_once '../conf/conf.php';
include_once CLASS_DIR . 'phpformbuilder/Form.php';
include_once ADMIN_DIR . 'secure/class/secure/Secure.php';

// breadcrumb
include_once 'inc/breadcrumb.php';

// sidebar
include_once 'inc/sidebar.php';

require_once ROOT . 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig   = new \Twig\Environment($loader, array(
    'debug' => DEBUG,
));
include_once ROOT . 'vendor/twig/twig/src/Extension/CrudTwigExtension.php';
$twig->addExtension(new \Twig\Extension\CrudTwigExtension());
if (ENVIRONMENT == 'development') {
    $twig->addExtension(new \Twig\Extension\DebugExtension());
    $twig->enableDebug();
}
$template_breadcrumb  = $twig->load('breadcrumb.html');
$template_navbar      = $twig->load('navbar.html');
$template_sidebar     = $twig->load('sidebar.html');
$template_footer      = $twig->load('footer.html');
$template_js          = $twig->load('data-lists-js.html');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo SITENAME . ' Admin Dashboard - 404 error - Page not found'; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex" />
    <meta name="description" content="PHP CRUD Admin Panel - 404 error - Page not found">
    <meta name="theme-color" content="#ffffff">
    <?php
    include_once 'inc/css-includes.php';
    ?>
</head>

<body>
    <?php
    if (DEMO) {
        include_once '../inc/navbar-main.php';
    }
    ?>
    <div class="d-flex flex-nowrap">
        <?php
        echo $template_sidebar->render(array('sidebar' => $sidebar));
        ?>
        <div id="content-wrapper">
            <?php
            echo $template_navbar->render(array('session' => $_SESSION));
            echo $template_breadcrumb->render(array('breadcrumb' => $breadcrumb));
            ?>
            <div class="px-4">

                <div class="content-wrapper">
                    <div class="row">
                        <div class="col">
                            <p class="h2">Sorry <small class="text-muted">(404 error)</small></p>
                            <p>We looked everywhere but couldn't find the page you're looking for.</p>
                            <p>Perhaps you are trying to access a list or form that you have not yet created?<br>Or you have created the form but not the list? (to open the forms the associated list must be created)</p>
                            <p><a href="https:d/www.phpcrudgenerator.com/help-center#error-404" class="btn btn-lg btn-info" title="Go Back Home"><i class="fas fa-circle-question me-2 prepend"></i>Lead me to the help center</a></p>
                            <p><a href="<?php echo ADMIN_URL; ?>" class="btn btn-lg btn-primary" title="Go Back Home"><i class="fas fa-house prepend"></i>Bring me Home</a></p>
                        </div> <!-- end col -->
                    </div> <!-- end row -->
                </div> <!-- end content-wrapper -->
            </div>
        </div> <!-- end content-wrapper -->
    </div> <!-- end container -->
    <?php
    echo $template_footer->render(array('object' => ''));
    include_once 'inc/js-includes.php';
    echo $template_js->render(array('object' => ''));
    ?>
</body>

</html>
