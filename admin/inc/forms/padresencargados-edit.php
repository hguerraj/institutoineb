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

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-edit-padres-encargados') === true) {
    $validator = Form::validate('form-edit-padres-encargados', FORMVALIDATION_PHP_LANG);
    $validator->maxLength(100)->validate('Nombre');
    $validator->maxLength(255)->validate('telefono');
    $validator->maxLength(100)->validate('Email');
    $validator->maxLength(255)->validate('direccion');
    $validator->maxLength(255)->validate('relacion_alumno');
    $validator->integer()->validate('Alumno_ID');
    $validator->min(-99999999999)->validate('Alumno_ID');
    $validator->max(99999999999)->validate('Alumno_ID');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-edit-padres-encargados'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');
        $values = array();
        $values['Nombre'] = $_POST['Nombre'];
        $values['telefono'] = $_POST['telefono'];
        $values['Email'] = $_POST['Email'];
        $values['direccion'] = $_POST['direccion'];
        $values['relacion_alumno'] = $_POST['relacion_alumno'];
        if (is_array($_POST['Alumno_ID'])) {
            $json_values = json_encode($_POST['Alumno_ID'], JSON_UNESCAPED_UNICODE);
            $values['Alumno_ID'] = $json_values;
        } else {
            $values['Alumno_ID'] = intval($_POST['Alumno_ID']);
            if ($values['Alumno_ID'] < 1) {
                $values['Alumno_ID'] = null;
            }
        }
        $where = $_SESSION['padres_encargados_editable_primary_keys'];

        // begin transaction
        $db->transactionBegin();

        try {
            // update padres_encargados
            if (DEMO !== true && !$db->update('padres_encargados', $values, $where, DEBUG_DB_QUERIES)) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(UPDATE_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-edit-padres-encargados');

                    // redirect to list page
                    if (isset($_SESSION['active_list_url'])) {
                        header('Location:' . $_SESSION['active_list_url']);
                    } else {
                        header('Location:' . ADMIN_URL . 'padresencargados');
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
        return 'padres_encargados.' . $k;
    }, array_keys($params)),
    $params
);
$_SESSION['padres_encargados_editable_primary_keys'] = $where_params;

if (!isset($_SESSION['errors']['form-edit-padres-encargados']) || empty($_SESSION['errors']['form-edit-padres-encargados'])) { // If no error registered
    $from = 'padres_encargados  LEFT JOIN alumnos ON padres_encargados.Alumno_ID=alumnos.ID';
    $columns = 'padres_encargados.ID, padres_encargados.Nombre, padres_encargados.telefono, padres_encargados.Email, padres_encargados.direccion, padres_encargados.relacion_alumno, padres_encargados.Alumno_ID';

    $where = $_SESSION['padres_encargados_editable_primary_keys'];

    // if restricted rights
    if (ADMIN_LOCKED === true && Secure::canUpdateRestricted('padres_encargados')) {
        $where = array_merge($where, Secure::getRestrictionQuery('padres_encargados'));
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
    $_SESSION['form-edit-padres-encargados']['ID'] = $row->ID;
    $_SESSION['form-edit-padres-encargados']['Nombre'] = $row->Nombre;
    $_SESSION['form-edit-padres-encargados']['telefono'] = $row->telefono;
    $_SESSION['form-edit-padres-encargados']['Email'] = $row->Email;
    $_SESSION['form-edit-padres-encargados']['direccion'] = $row->direccion;
    $_SESSION['form-edit-padres-encargados']['relacion_alumno'] = $row->relacion_alumno;
    $_SESSION['form-edit-padres-encargados']['Alumno_ID'] = $row->Alumno_ID;
}
// $params come from data-forms.php
$pk_url_params = http_build_query($params, '', '/');

$form = new Form('form-edit-padres-encargados', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'padresencargados/edit/' . $pk_url_params);
$form->startFieldset();

// ID --

$form->setCols(2, 10);
$form->addInput('hidden', 'ID', '');

// Nombre --

$form->setCols(2, 10);
$form->addInput('text', 'Nombre', '', 'Nombre', '');

// telefono --
$form->addInput('text', 'telefono', '', 'Telefono', '');

// Email --
$form->addInput('text', 'Email', '', 'Email', '');

// direccion --
$form->addInput('text', 'direccion', '', 'Direccion', '');

// relacion_alumno --
$form->addInput('text', 'relacion_alumno', '', 'Relacion Alumno', '');

// Alumno_ID --
$from = 'alumnos';
$columns = 'alumnos.ID, alumnos.Nombre';
$where = array();
$extras = array(
    'select_distinct' => true,
    'order_by' => 'alumnos.ID'
);

// restrict if relationship table is the users table OR if the relationship table is used in the restriction query
if (ADMIN_LOCKED === true && Secure::canCreateRestricted('padres_encargados')) {
    $secure_restriction_query = Secure::getRestrictionQuery('padres_encargados');
    if (!empty($secure_restriction_query)) {
        if ('alumnos' == USERS_TABLE) {
            $restriction_query = 'alumnos.ID = ' . $_SESSION['secure_user_ID'];
            $where[] = $restriction_query;
        } elseif (preg_match('/alumnos\./', $secure_restriction_query[0])) {
            $restriction_query = 'padres_encargados' . $secure_restriction_query[0];
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
            $form->addOption('Alumno_ID', '', '-');
    while ($row = $db->fetch()) {
        $value = $row->ID;
        $display_value = $row->ID;
        $display_value .= ' ' . $row->Nombre;
        if ($db_count > 0) {
            $form->addOption('Alumno_ID', $value, $display_value);
        }
    }
}

if ($db_count > 0) {
    $form->addSelect('Alumno_ID', 'Alumno Id', 'data-slimselect=true');
} else {
    // for display purpose
    $form->addInput('text', 'Alumno_ID-display', $display_value, 'Alumno Id', 'readonly');

    // for send purpose
    $form->addInput('hidden', 'Alumno_ID', $value);
}
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, data-ladda-button=true, data-style=zoom-in, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success, data-ladda-button=true, data-style=zoom-in', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-edit-padres-encargados');
$form->addPlugin('formvalidation', '#form-edit-padres-encargados', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
