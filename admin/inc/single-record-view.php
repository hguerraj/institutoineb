<?php

use secure\Secure;
use crud\Elements;

header("X-Robots-Tag: noindex", true);

session_start();
include_once '../conf/conf.php';
include_once ADMIN_DIR . 'secure/class/secure/Secure.php';

// $item = lowercase compact table name
$item = $match['params']['item'];

$params = array();
if ($match['name'] === 'data-view') {
    $pk_fieldname = $match['params']['pk_fieldname'];
    $pk_value = $match['params']['pk_value'];
    $params[$pk_fieldname] = $pk_value;
} elseif ($match['name'] === 'data-view-2-primary-keys') {
    $pk_fieldname_1 = $match['params']['pk_fieldname_1'];
    $pk_fieldname_2 = $match['params']['pk_fieldname_2'];
    $pk_value_1 = $match['params']['pk_value_1'];
    $pk_value_2 = $match['params']['pk_value_2'];
    $params[$pk_fieldname_1] = $pk_value_1;
    $params[$pk_fieldname_2] = $pk_value_2;
}

include_once ADMIN_DIR . 'class/crud/Elements.php';
$element   = new Elements($item);
$table     = $element->table;

// lock page
// user must have [restricted|all] READ rights on $table
Secure::lock($table, 'restricted');

$item_class                = $element->item_class;
$item_class_with_namespace = $element->item_class_with_namespace;
// ElementsFilters::register($table);

// create the item object
include_once ADMIN_DIR . 'class/crud/' . $item_class . '.php';
$object = new $item_class_with_namespace($element, $params);

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
$template = $twig->load('single-record-views/' . $item . '.html');
echo $template->render(array('object' => $object, 'session' => $_SESSION));
