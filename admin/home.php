<?php
use secure\Secure;

session_start();
include_once '../conf/conf.php';
include_once ADMIN_DIR . 'secure/class/secure/Secure.php';

// lock page
Secure::lock();

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
}
$twig->addGlobal('SERVER', $_SERVER);
$template                 = $twig->load('home.html');
$template_breadcrumb      = $twig->load('breadcrumb.html');
$template_navbar          = $twig->load('navbar.html');
$template_sidebar         = $twig->load('sidebar.html');
$template_footer          = $twig->load('footer.html');
if (ENABLE_STYLE_SWITCHING) {
    $template_style_switcher  = $twig->load('style-switcher.html');
}
$template_js              = $twig->load('data-home-js.html');
$subtitle = ' Admin Dashboard - Home';
$desc = ' - Admin Dashboard';
if (DEMO === true) {
    $subtitle = ' - Sakila Database Demo';
    $desc = ' - Bootstrap admin dashboard with CRUD functionnalities. This admin panel is built with the MySQL Sakila Database for demo purposes';
}
$msg = '';
if (isset($_SESSION['msg'])) {
    // catch registered message & reset.
    $msg = $_SESSION['msg'];
    unset($_SESSION['msg']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo SITENAME . $subtitle; ?></title>
    <meta name="description" content="<?php echo SITENAME; ?>  - <?php echo $desc; ?>">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://<?php echo $_SERVER['HTTP_HOST']; ?>/admin/home" rel="canonical">
    <meta name="theme-color" content="#ffffff">
    <?php include_once 'inc/css-includes.php'; ?>
</head>

<body>
    <?php
    if (DEMO) {
        include_once '../inc/navbar-main.php';
    }
    ?>
    <div class="d-flex flex-nowrap">
        <?php echo $template_sidebar->render(array('sidebar' => $sidebar)); ?>
        <div id="content-wrapper">
            <?php
            echo $template_navbar->render(array('session' => $_SESSION));
            echo $template_breadcrumb->render(array('breadcrumb' => $breadcrumb));
            ?>
            <!-- shows all the user messages -->
            <div id="msg" class="mx-4"><?php echo $msg; ?></div>
            <?php
            echo $template->render(array());
            ?>
        </div> <!-- end content-wrapper -->
    </div> <!-- end container -->
    <div id="loader">
        <div class="spinner"></div>
    </div>

    <?php
    echo $template_footer->render(array('object' => ''));
    if (ENABLE_STYLE_SWITCHING) {
        echo $template_style_switcher->render();
    }
    include_once 'inc/js-includes.php';
    echo $template_js->render(array('object' => ''));
    ?>
</body>

</html>
