<?php
// phpcs:disable PSR1.Files.SideEffects

use phpformbuilder\database\DB;
use phpformbuilder\Form;

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include_once '../conf/conf.php';

$output = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // create validator & auto-validate required fields
    $validator = Form::validate('db-connection-test');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['db-connection-test'] = $validator->getAllErrors();
    } else {
        $db_driver  = $_POST['db_driver'];
        $db_host    = $_POST['db_host'];
        $db_port    = $_POST['db_port'];
        $db_user    = $_POST['db_user'];
        $db_pass    = $_POST['db_pass'];
        $db_name    = $_POST['db_name'];
        $db_table   = $_POST['db_table'];

        $env = 'production (remote server)';
        if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1') {
            $env = 'localhost (local server)';
        }
        // SELECT * FROM information_schema.columns WHERE table_schema = 'phpcg_test' AND table_name = 'actor';
        $output .= '<div class="p-3 mb-5 border d-flex flex-column text-center text-bg-light align-items-center">';
        $output .= '<h2 class="fw-light mb-4">Test results</h2>';
        $output .= '<p><strong class="me-2">Detected environment:</strong> ' . $env . '</p>';

        $db = new DB(true, $db_driver, $db_host, $db_name, $db_user, $db_pass, $db_port);
        if ($db->isConnected()) {
            $output .= '<h4 class="text-bg-success px-3 py-2">Success! Database is connected.</h4>';
            $columns = $db->getColumnsNames($db_table);
            $output .= '<p><strong class="me-2">Query:</strong> <code>' . $db->getLastSql() . '</code></p>';
            if (!$columns) {
                $output .= '<p class="text-bg-warning px-3 py-2">No columns found in ' . $db_table . '<br><small class="fw-light text-muted">Or this table doesn\'t exist.</small></p>';
            } else {
                $output .= '<p class="text-bg-success px-3 py-2">Found ' . count($columns) . ' columns in ' . $db_table . '</p>';
                $output .= implode(', ', $columns);
            }
        } else {
            $output .= '<h4 class="text-bg-danger px-3 py-2">Failed to connect to the database.</h4>';
            $output .= $db->error();
        }
        $output .= '</div>';
    }
}

$form = new Form('db-connection-test', 'horizontal', 'novalidate');
$form->setMode('development');

$pdo_drivers = \PDO::getAvailableDrivers();

foreach ($pdo_drivers as $driver) {
    $form->addOption('db_driver', $driver, $driver);
}

$form->addSelect('db_driver', 'Choose your database driver', 'data-slimselect=true');
$form->addInput('text', 'db_host', '', 'Database host', 'required');
$form->addHelper('Leave blank to use the default port', 'db_port');
$form->addInput('text', 'db_port', '', 'Database port', '');
$form->addInput('text', 'db_user', '', 'Database user', 'required');
$form->addInput('text', 'db_pass', '', 'Database pass', 'required');
$form->addInput('text', 'db_name', '', 'Database name', 'required');
$form->addInput('text', 'db_table', '', 'Database table', 'required');

$form->centerContent();

$form->addBtn('submit', 'submit_btn', 1, 'Test connection', 'class=btn btn-lg btn-primary mt-5');

$form->addPlugin('formvalidation', '#db-connection-test');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Database connection test</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" />
    <?php $form->printIncludes('css'); ?>
</head>

<body>
    <div class="container">
        <h1 class="text-center fw-light my-5">Database connection test</h1>
        <?php
        echo $output;
        $form->render();
        ?>
    </div>
    <?php
    $form->printIncludes('js');
    $form->printJsCode();
    ?>
</body>

</html>
