<?php
use phpformbuilder\Form;
use phpformbuilder\Validator\Validator;
use phpformbuilder\database\DB;
use common\Utils;
use secure\Secure;

include_once ADMIN_DIR . 'secure/class/secure/Secure.php';

$debug_content = '';

/* =============================================
    validation if posted
============================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-create-profesores') === true) {
    $validator = Form::validate('form-create-profesores', FORMVALIDATION_PHP_LANG);
    $validator->maxLength(100)->validate('Nombre');
    if (isset($_POST['Fecha_Nacimiento_submit'])) {
        $validator->date()->validate('Fecha_Nacimiento_submit');
    } else {
        $validator->date()->validate('Fecha_Nacimiento');
    }
    $validator->maxLength(255)->validate('direccion');
    $validator->maxLength(255)->validate('telefono');
    $validator->maxLength(100)->validate('Email');
    $validator->maxLength(255)->validate('Horario');
    $validator->integer()->validate('Grado_ID');
    $validator->min(-99999999999)->validate('Grado_ID');
    $validator->max(99999999999)->validate('Grado_ID');
    $validator->float()->validate('Usuario_ID');
    $validator->integer()->validate('Usuario_ID');
    $validator->min(-99999999999)->validate('Usuario_ID');
    $validator->max(99999999999)->validate('Usuario_ID');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-create-profesores'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');

        // begin transaction
        $db->transactionBegin();

        $values = array();
        $values['ID'] = null;
        $values['Nombre'] = $_POST['Nombre'];
        $date_value = $_POST['Fecha_Nacimiento'];
        if (isset($_POST['Fecha_Nacimiento_submit'])) {
            $date_value = $_POST['Fecha_Nacimiento_submit'];
        }
        if (trim($date_value) == '') {
            $values['Fecha_Nacimiento'] = null;
        } else {
            $values['Fecha_Nacimiento'] = $date_value;
        }
        $values['direccion'] = $_POST['direccion'];
        $values['telefono'] = $_POST['telefono'];
        $values['Email'] = $_POST['Email'];
        $values['Asignaturas'] = $_POST['Asignaturas'];
        $values['Horario'] = $_POST['Horario'];
        if (is_array($_POST['Grado_ID'])) {
            $json_values = json_encode($_POST['Grado_ID'], JSON_UNESCAPED_UNICODE);
            $values['Grado_ID'] = $json_values;
        } else {
            $values['Grado_ID'] = intval($_POST['Grado_ID']);
            if ($values['Grado_ID'] < 1) {
                $values['Grado_ID'] = null;
            }
        }
        if (isset($_POST['Usuario_ID'])) {
            $values['Usuario_ID'] = intval($_POST['Usuario_ID']);
        }
        try {
            // insert into profesores
            if (DEMO !== true && $db->insert('profesores', $values, DEBUG_DB_QUERIES) === false) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(INSERT_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-create-profesores');

                    // redirect to list page
                    if (isset($_SESSION['active_list_url'])) {
                        header('Location:' . $_SESSION['active_list_url']);
                    } else {
                        header('Location:' . ADMIN_URL . 'profesores');
                    }

                    // if we don't exit here, $_SESSION['msg'] will be unset
                    exit();
                } else {
                    $debug_content .= $db->getDebugContent();
                    $db->transactionRollback();

                    $_SESSION['msg'] = Utils::alert(INSERT_SUCCESS_MESSAGE . '<br>(' . DEBUG_DB_QUERIES_ENABLED . ')', 'alert-success has-icon');
                }
            }
        } catch (\Exception $e) {
            $db->transactionRollback();
            $msg_content = DB_ERROR;
            if (DEBUG) {
                $msg_content .= '<br>' . $e->getMessage() . '<br>' . $db->getLastSql();
            }
            $_SESSION['msg'] = Utils::alert($msg_content, 'alert-danger has-icon');
        }
    } // END else
} // END if POST

$form = new Form('form-create-profesores', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'profesores/create');
$form->startFieldset();

// ID --

$form->setCols(2, 10);
$form->addInput('hidden', 'ID', '');

// Nombre --

$form->setCols(2, 10);
$form->addInput('text', 'Nombre', '', 'Nombre', '');

// Fecha_Nacimiento --
$form->addPlugin('pickadate', '#Fecha_Nacimiento');
$form->addInput('text', 'Fecha_Nacimiento', '', 'Fecha Nacimiento', 'data-format=dd mmmm yyyy, data-format-submit=yyyy-mm-dd, data-set-default-date=true');

// direccion --
$form->addInput('text', 'direccion', '', 'Direccion', '');

// telefono --
$form->addInput('text', 'telefono', '', 'Telefono', '');

// Email --
$form->addInput('text', 'Email', '', 'Email', '');

// Asignaturas --
$form->addInput('text', 'Asignaturas', '', 'Asignaturas', '');

// Horario --
$form->addInput('text', 'Horario', '', 'Horario', '');

// Grado_ID --
$from = 'grados';
$columns = 'grados.ID, grados.descripcion';
$where = array();
$extras = array(
    'select_distinct' => true,
    'order_by' => 'grados.ID'
);

// restrict if relationship table is the users table OR if the relationship table is used in the restriction query
if (ADMIN_LOCKED === true && Secure::canCreateRestricted('profesores')) {
    $secure_restriction_query = Secure::getRestrictionQuery('profesores');
    if (!empty($secure_restriction_query)) {
        if ('grados' == USERS_TABLE) {
            $restriction_query = 'grados.ID = ' . $_SESSION['secure_user_ID'];
            $where[] = $restriction_query;
        } elseif (preg_match('/grados\./', $secure_restriction_query[0])) {
            $restriction_query = 'profesores' . $secure_restriction_query[0];
            $where[] = $restriction_query;
        }
    }
}

// default value if no record exist
$value = '';
$display_value = '';


// set the selected value if it has been sent in URL query parameters
if (isset($_GET['Grado_ID'])) {
    $_SESSION['form-create-profesores']['Grado_ID'] = addslashes($_GET['Grado_ID']);
}

$db = new DB(DEBUG);
$db->setDebugMode('register');

$db->select($from, $columns, $where, $extras, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content .= $db->getDebugContent();
}

$db_count = $db->rowCount();
if (!empty($db_count)) {
            $form->addOption('Grado_ID', '', '-');
    while ($row = $db->fetch()) {
        $value = $row->ID;
        $display_value = $row->ID;
        $display_value .= ' ' . $row->descripcion;
        if ($db_count > 0) {
            $form->addOption('Grado_ID', $value, $display_value);
        }
    }
}

if ($db_count > 0) {
    $form->addSelect('Grado_ID', 'Grado Id', 'data-slimselect=true');
} else {
    // for display purpose
    $form->addInput('text', 'Grado_ID-display', $display_value, 'Grado Id', 'readonly');

    // for send purpose
    $form->addInput('hidden', 'Grado_ID', $value);
}

// Usuario_ID --
$form->addInput('number', 'Usuario_ID', '', 'Usuario Id', '');
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, data-ladda-button=true, data-style=zoom-in, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success, data-ladda-button=true, data-style=zoom-in', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-create-profesores');
$form->addPlugin('formvalidation', '#form-create-profesores', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
