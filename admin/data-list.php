<?php

use secure\Secure;
use crud\ElementsFilters;
use crud\Elements;
use phpformbuilder\Form;

session_start();
include_once '../conf/conf.php';
include_once CLASS_DIR . 'phpformbuilder/Form.php';
include_once ADMIN_DIR . 'secure/class/secure/Secure.php';


// $item    = lowercase compact table name
$item       = $match['params']['item'];

// $p       = page number
$_GET['p']  = @$match['params']['p'];

// used to redirect forms to the current active list
$_SESSION['active_list_url'] = $_SERVER['REQUEST_URI'];

include_once ADMIN_DIR . 'class/crud/Elements.php';
$element   = new Elements($item);
$table     = $element->table;
$desc      = ucfirst($table) . ' list';
$canonical = ADMIN_URL . $item;
$meta_robots = '';
if (!empty($_GET['p'])) {
    $canonical .= '/p' . $_GET['p'];
    $desc      .= ' - page ' . $_GET['p'];
    $meta_robots = '<meta name="robots" content="noindex" />';
}

// lock page
// user must have [restricted|all] READ rights on $table
Secure::lock($table, 'restricted');

$item_class                = $element->item_class;
$item_class_with_namespace = $element->item_class_with_namespace;
ElementsFilters::register($table);

// create the item object
include_once ADMIN_DIR . 'class/crud/' . $item_class . '.php';
$object = new $item_class_with_namespace($element);

// store requested page number
$page_var = $table . '-page';
$_SESSION[$page_var] = $_GET['p'];

// breadcrumb
include_once 'inc/breadcrumb.php';

// sidebar
include_once 'inc/sidebar.php';

// twig loader & templates
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
$template                 = $twig->load($item . '.html');
$template_breadcrumb      = $twig->load('breadcrumb.html');
$template_navbar          = $twig->load('navbar.html');
$template_sidebar         = $twig->load('sidebar.html');
$template_footer          = $twig->load('footer.html');
if (ENABLE_STYLE_SWITCHING) {
    $template_style_switcher  = $twig->load('style-switcher.html');
}
$template_js              = $twig->load('data-lists-js.html');
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
    <title><?php echo SITENAME . ' Admin Dashboard - ' . $desc; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php echo $meta_robots; ?>
    <link rel="canonical" href="<?php echo  $canonical; ?>">
    <meta name="description" content="PHP CRUD Admin Panel - <?php echo $desc; ?> - This professional Full-featured Bootstrap 5 admin dashboard has been built from the Sakila demo database using the PHP CRUD GENERATOR.">
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
            <!-- shows all the user messages -->
            <div id="msg" class="mx-4"><?php echo $msg; ?></div>
            <?php
            echo $template->render(array('object' => $object, 'session' => $_SESSION));
            ?>
        </div> <!-- end content-wrapper -->
    </div> <!-- end container -->
    <div id="loader">
        <div class="spinner"></div>
    </div>
    <?php
    echo $template_footer->render(array('object' => $object));
    if (ENABLE_STYLE_SWITCHING) {
        echo $template_style_switcher->render();
    }
    include_once 'inc/js-includes.php';
    echo @$template_js->render(array('object' => $object));
    ?>

    <!-- Single record view Modal -->

    <div id="single-record-view-modal" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content"></div>
        </div>
    </div>
</body>

</html>
