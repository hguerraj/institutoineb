<?php
use phpformbuilder\database\DB;
use phpformbuilder\Form;

include_once '../../class/phpformbuilder/database/DB.php';
include_once '../../class/phpformbuilder/Form.php';

session_start();

// Security check
if (file_exists('../install.lock') || !isset($_POST['hash']) || !isset($_SESSION['hash']) || $_POST['hash'] !== $_SESSION['hash']) {
    echo 'The request has been blocked by the security settings.';
    exit;
}

$output = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db_driver  = $_POST['db_driver'];
    $db_host    = $_POST['db_host'];
    $db_port    = $_POST['db_port'];
    $db_name    = $_POST['db_name'];
    $db_user    = $_POST['db_user'];
    $db_pass    = $_POST['db_pass'];

    $pdo_drivers = \PDO::getAvailableDrivers();

    if (!in_array($db_driver, $pdo_drivers)) {
        $output = Form::buildAlert('Invalid PDO driver', 'bs5', 'danger');
    } else {
        $db = new DB(true, $db_driver, $db_host, $db_name, $db_user, $db_pass, $db_port);
        if ($db->isConnected()) {
            $output = Form::buildAlert('PHP CRUD Generator has successfully connected to your database.', 'bs5', 'success');
        } else {
            $output = Form::buildAlert('Failed to connect to the database.<br>' . utf8_decode($db->error()), 'bs5', 'warning');
        }
    }
}

echo $output;
