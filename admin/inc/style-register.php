<?php
use common\Utils;

include_once '../../conf/conf.php';

$available_styles = array('primary', 'secondary', 'success', 'info', 'warning', 'danger', 'light', 'dark');

if (!isset($_POST['bootstrap_theme']) || !ctype_lower($_POST['bootstrap_theme']) || !isset($_POST['navbar_style']) || !in_array($_POST['navbar_style'], $available_styles) || !isset($_POST['sidebar_style']) || !in_array($_POST['sidebar_style'], $available_styles)) {
    echo Utils::alert(STYLES_PREFERENCES_FAILURE, 'alert-danger has-icon');
} else {
    setcookie('bootstrap_theme', $_POST['bootstrap_theme'], array ('path' => '/'));
    setcookie('navbar_style', $_POST['navbar_style'], array ('path' => '/'));
    setcookie('sidebar_style', $_POST['sidebar_style'], array ('path' => '/'));
    echo Utils::alert(STYLES_PREFERENCES_REGISTERED, 'alert-success has-icon');
}
