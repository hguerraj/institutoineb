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

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-edit-alumnos') === true) {
    $validator = Form::validate('form-edit-alumnos', FORMVALIDATION_PHP_LANG);
    $validator->maxLength(100)->validate('Nombre');
    if (isset($_POST['Fecha_Nacimiento_submit'])) {
        $validator->date()->validate('Fecha_Nacimiento_submit');
    } else {
        $validator->date()->validate('Fecha_Nacimiento');
    }
    $validator->maxLength(255)->validate('direccion');
    $validator->maxLength(255)->validate('telefono');
    $validator->maxLength(100)->validate('Email');
    $validator->integer()->validate('Grado_ID');
    $validator->min(-99999999999)->validate('Grado_ID');
    $validator->max(99999999999)->validate('Grado_ID');
    if (isset($_POST['Fecha_Registro_submit'])) {
        $validator->date()->validate('Fecha_Registro_submit');
    } else {
        $validator->date()->validate('Fecha_Registro');
    }

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-edit-alumnos'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');
        $values = array();
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
        if (is_array($_POST['Grado_ID'])) {
            $json_values = json_encode($_POST['Grado_ID'], JSON_UNESCAPED_UNICODE);
            $values['Grado_ID'] = $json_values;
        } else {
            $values['Grado_ID'] = intval($_POST['Grado_ID']);
            if ($values['Grado_ID'] < 1) {
                $values['Grado_ID'] = null;
            }
        }
        $date_value = $_POST['Fecha_Registro'];
        if (isset($_POST['Fecha_Registro_submit'])) {
            $date_value = $_POST['Fecha_Registro_submit'];
        }
        if (trim($date_value) == '') {
            $values['Fecha_Registro'] = null;
        } else {
            $values['Fecha_Registro'] = $date_value;
        }
        $where = $_SESSION['alumnos_editable_primary_keys'];

        // begin transaction
        $db->transactionBegin();

        try {
            // update alumnos
            if (DEMO !== true && !$db->update('alumnos', $values, $where, DEBUG_DB_QUERIES)) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(UPDATE_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-edit-alumnos');

                    // redirect to list page
                    if (isset($_SESSION['active_list_url'])) {
                        header('Location:' . $_SESSION['active_list_url']);
                    } else {
                        header('Location:' . ADMIN_URL . 'alumnos');
                    }

                    // if we don't exit here, $_SESSION['msg'] will be unset
                    exit();
                } else {
                    $debug_content .= $db->getDebugContent();
                    $db->transactionRollback();

                    $_SESSION['msg'] = Utils::alert(UPDATE_SUCCESS_MESSAGE . '<br>(' . DEBUG_DB_QUERIES_ENABLED . ')', 'alert-success has-icon');
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

// register editable primary keys, which are NOT posted and will be the query update filter
// $params come from data-forms.php
// replace 'fieldname' with 'table.fieldname' to avoid ambigous query
$where_params = array_combine(
    array_map(function ($k) {
        return 'alumnos.' . $k;
    }, array_keys($params)),
    $params
);
$_SESSION['alumnos_editable_primary_keys'] = $where_params;

if (!isset($_SESSION['errors']['form-edit-alumnos']) || empty($_SESSION['errors']['form-edit-alumnos'])) { // If no error registered
    $from = 'alumnos  LEFT JOIN grados ON alumnos.Grado_ID=grados.ID';
    $columns = 'alumnos.ID, alumnos.Nombre, alumnos.Fecha_Nacimiento, alumnos.direccion, alumnos.telefono, alumnos.Email, alumnos.Grado_ID, alumnos.Fecha_Registro';

    $where = $_SESSION['alumnos_editable_primary_keys'];

    // if restricted rights
    if (ADMIN_LOCKED === true && Secure::canUpdateRestricted('alumnos')) {
        $where = array_merge($where, Secure::getRestrictionQuery('alumnos'));
    }

    $db = new DB(DEBUG);
    $db->setDebugMode('register');

    $db->select($from, $columns, $where, array(), DEBUG_DB_QUERIES);
    if ($db->rowCount() < 1) {
        if (DEBUG) {
            exit($db->getLastSql() . ' : No Record Found');
        } else {
            exit('No Record Found');
        }
    }
    if (DEBUG_DB_QUERIES) {
        $debug_content .= $db->getDebugContent();
    }
    $row = $db->fetch();
    $_SESSION['form-edit-alumnos']['ID'] = $row->ID;
    $_SESSION['form-edit-alumnos']['Nombre'] = $row->Nombre;
    $Fecha_Nacimiento_ts = strtotime($row->Fecha_Nacimiento);
    // if timestamp is valid and > '1970-01-01'
    if (Utils::isValidTimeStamp($Fecha_Nacimiento_ts) && $Fecha_Nacimiento_ts > -62169984000) {
        $_SESSION['form-edit-alumnos']['Fecha_Nacimiento'] = date('Y-m-d', $Fecha_Nacimiento_ts);
    } else {
        $_SESSION['form-edit-alumnos']['Fecha_Nacimiento'] = null;
    }
    $_SESSION['form-edit-alumnos']['direccion'] = $row->direccion;
    $_SESSION['form-edit-alumnos']['telefono'] = $row->telefono;
    $_SESSION['form-edit-alumnos']['Email'] = $row->Email;
    $_SESSION['form-edit-alumnos']['Grado_ID'] = $row->Grado_ID;
    $Fecha_Registro_ts = strtotime($row->Fecha_Registro);
    // if timestamp is valid and > '1970-01-01'
    if (Utils::isValidTimeStamp($Fecha_Registro_ts) && $Fecha_Registro_ts > -62169984000) {
        $_SESSION['form-edit-alumnos']['Fecha_Registro'] = date('Y-m-d', $Fecha_Registro_ts);
    } else {
        $_SESSION['form-edit-alumnos']['Fecha_Registro'] = null;
    }
}
// $params come from data-forms.php
$pk_url_params = http_build_query($params, '', '/');

$form = new Form('form-edit-alumnos', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'alumnos/edit/' . $pk_url_params);
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

// Grado_ID --
$from = 'grados INNER JOIN carreras ON grados.Carrera_ID = carreras.ID';
$columns = 'grados.ID, grados.Nombre_Grado, carreras.Nombre_Carrera';
$where = array();
$extras = array(
    'select_distinct' => true,
    'order_by' => 'grados.Nombre_Grado'
);

// restrict if relationship table is the users table OR if the relationship table is used in the restriction query
if (ADMIN_LOCKED === true && Secure::canCreateRestricted('alumnos')) {
    $secure_restriction_query = Secure::getRestrictionQuery('alumnos');
    if (!empty($secure_restriction_query)) {
        if ('grados' == USERS_TABLE) {
            $restriction_query = 'grados.ID = ' . $_SESSION['secure_user_ID'];
            $where[] = $restriction_query;
        } elseif (preg_match('/grados\./', $secure_restriction_query[0])) {
            $restriction_query = 'alumnos' . $secure_restriction_query[0];
            $where[] = $restriction_query;
        }
    }
}

// default value if no record exist
$value = '';
$display_value = '';

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
        $display_value = $row->Nombre_Grado;
        $display_value .= ' ' . $row->Nombre_Carrera;
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

// Fecha_Registro --
$form->addPlugin('pickadate', '#Fecha_Registro');
$form->addInput('text', 'Fecha_Registro', '', 'Fecha Registro', 'data-format=dd mmmm yyyy, data-format-submit=yyyy-mm-dd, data-set-default-date=true');
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, data-ladda-button=true, data-style=zoom-in, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success, data-ladda-button=true, data-style=zoom-in', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-edit-alumnos');
$form->addPlugin('formvalidation', '#form-edit-alumnos', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
