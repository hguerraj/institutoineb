<?php session_start();
header("X-Robots-Tag: noindex", true);
if ( isset($_GET['dataTable']) && isset($_GET['dataField']) && isset($_GET['dataDirection']) && preg_match('([0-9a-zA-Z_-]+)', $_GET['dataTable']) && preg_match('([0-9a-zA-Z_-]+)', $_GET['dataField']) && preg_match('(ASC|DESC)', $_GET['dataDirection'])) {
    $table                           = $_GET['dataTable'];
    $field                           = $_GET['dataField'];
    $direction                       = $_GET['dataDirection'];
    if (strpos($field, '.') === false) {
        $_SESSION['sorting_' . $table]   = $table . '.' . $field;
    } else {
        $_SESSION['sorting_' . $table]   = $field;
    }
    $_SESSION['direction_' . $table] = $direction;
}
