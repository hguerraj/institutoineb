<?php
use secure\Secure;
use crud\ElementsFilters;
use crud\Elements;
use phpformbuilder\Form;
use phpformbuilder\Validator\Validator;
use phpformbuilder\database\DB;

session_start();

include_once '../conf/conf.php';
include_once ADMIN_DIR . 'secure/class/secure/Secure.php';
include_once CLASS_DIR . 'phpformbuilder/Form.php';

// $item    = lowercase compact table name
$item = $match['params']['item'];

$params = array();
if ($match['name'] === 'data-forms-edit-delete') {
    $pk_fieldname = $match['params']['pk_fieldname'];
    $pk_value = $match['params']['pk_value'];
    $params[$pk_fieldname] = $pk_value;
} elseif ($match['name'] === 'data-forms-edit-delete-2-primary-keys') {
    $pk_fieldname_1 = $match['params']['pk_fieldname_1'];
    $pk_fieldname_2 = $match['params']['pk_fieldname_2'];
    $pk_value_1 = $match['params']['pk_value_1'];
    $pk_value_2 = $match['params']['pk_value_2'];
    $params[$pk_fieldname_1] = $pk_value_1;
    $params[$pk_fieldname_2] = $pk_value_2;
}

// create|edit|delete
$action = $match['params']['action'];

$element    = new Elements($item);
$table      = $element->table;
$item_class = $element->item_class;

// lock page
if ($action == 'edit' && Secure::canUpdate($table) !== true && Secure::canUpdateRestricted($table) !== true) {
    Secure::logout();
} elseif (($action == 'create' || $action == 'delete') && (Secure::canCreate($table) !== true && Secure::canCreateRestricted($table) !== true)) {
    Secure::logout();
}

// info label
$info_label       = '';
$info_label_class = '';
if ($action == 'create') {
    $info_label       = ADD_NEW;
    $info_label_class = 'primary';
} elseif ($action == 'edit') {
    $info_label       = EDIT;
    $info_label_class = 'warning';
} elseif ($action == 'delete') {
    $info_label       = DELETE_ACTION;
    $info_label_class = 'danger';
}
$desc = $info_label . ' ' . $table;

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
$template_breadcrumb      = $twig->load('breadcrumb.html');
$template_navbar          = $twig->load('navbar.html');
$template_sidebar         = $twig->load('sidebar.html');
$template_footer          = $twig->load('footer.html');
if (ENABLE_STYLE_SWITCHING) {
    $template_style_switcher  = $twig->load('style-switcher.html');
}
$template_js              = $twig->load('data-forms-js.html');

if (!file_exists('inc/forms/' . $item . '-' . $action . '.php')) {
    exit('inc/forms/' . $item . '-' . $action . '.php : ' . ERROR_FILE_NOT_FOUND);
}

include_once 'inc/forms/' . $item . '-' . $action . '.php';
$form->useLoadJs('core');
$form->setMode('development');

$msg = '';
if (isset($_SESSION['msg'])) {
    // catch registered message & reset.
    $msg = $_SESSION['msg'];
    unset($_SESSION['msg']);
}

$back_url = ADMIN_URL . $item;
if (isset($_SESSION['active_list_url'])) {
    $back_url = $_SESSION['active_list_url'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo SITENAME . ' ' . ADMIN . ' - ' . $desc; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="description" content="<?php echo SITENAME; ?> - <?php echo $desc; ?>.">
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
    <div class="admin-form d-flex flex-nowrap">
        <?php
        echo $template_sidebar->render(array('sidebar' => $sidebar));
        ?>
        <div id="content-wrapper">
            <?php
            echo $template_navbar->render(array('session' => $_SESSION));
            echo $template_breadcrumb->render(array('breadcrumb' => $breadcrumb));
            ?>
            <div class="px-4">

                <div id="debug-content">
                    <?php
                    if (DEBUG_DB_QUERIES) {
                        echo $debug_content;
                    }
                    ?>
                </div>

                <!-- shows all the user messages -->

                <div id="msg" class="mx-4"><?php echo $msg; ?></div>

                <div id="toolbar" class="d-flex align-items-center justify-content-between text-bg-light px-3 py-2">
                    <p class="text-semibold m-0"><a href="<?php echo $back_url; ?>"><i class="<?php echo ICON_BACK; ?> prepend"></i></a><?php echo $element->item_label; ?></p>

                    <span class="badge text-bg-<?php echo $info_label_class; ?>"><?php echo $info_label; ?></span>
                </div>

                <?php $form->render(); ?>
            </div>
        </div> <!-- end content-wrapper -->
    </div> <!-- end container -->
    <?php
    echo $template_footer->render(array('object' => ''));
    if (ENABLE_STYLE_SWITCHING) {
        echo $template_style_switcher->render();
    }
    include_once 'inc/js-includes.php';
    $form->printJsCode();
    echo $template_js->render(array('object' => ''));

        // load form javascript if exists
    if (file_exists('inc/forms/' . $item . '.js')) {
        ?>
    <script type="text/javascript" src="<?php echo ADMIN_URL . 'inc/forms/' . $item . '.js'; ?>"></script>
        <?php
    }
    ?>
</body>

</html>
