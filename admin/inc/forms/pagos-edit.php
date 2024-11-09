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

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-edit-pagos') === true) {
    $validator = Form::validate('form-edit-pagos', FORMVALIDATION_PHP_LANG);
    $validator->integer()->validate('Alumno_ID');
    $validator->min(-99999999999)->validate('Alumno_ID');
    $validator->max(99999999999)->validate('Alumno_ID');
    $validator->maxLength(50)->validate('Tipo_Pago');
    $validator->float()->validate('Monto');
    $validator->min(-9999999999)->validate('Monto');
    $validator->max(9999999999.99)->validate('Monto');
    if (isset($_POST['Fecha_Pago_submit'])) {
        $validator->date()->validate('Fecha_Pago_submit');
    } else {
        $validator->date()->validate('Fecha_Pago');
    }
    if (isset($_POST['anio_submit'])) {
        $validator->date()->validate('anio_submit');
    } else {
        $validator->date()->validate('anio');
    }
    $validator->float()->validate('Mes');
    $validator->integer()->validate('Mes');
    $validator->min(-99999999999)->validate('Mes');
    $validator->max(99999999999)->validate('Mes');
    $validator->maxLength(50)->validate('Estado_Pago');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-edit-pagos'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');
        $values = array();
        if (is_array($_POST['Alumno_ID'])) {
            $json_values = json_encode($_POST['Alumno_ID'], JSON_UNESCAPED_UNICODE);
            $values['Alumno_ID'] = $json_values;
        } else {
            $values['Alumno_ID'] = intval($_POST['Alumno_ID']);
            if ($values['Alumno_ID'] < 1) {
                $values['Alumno_ID'] = null;
            }
        }
        $values['Tipo_Pago'] = $_POST['Tipo_Pago'];
        if (isset($_POST['Monto'])) {
            $values['Monto'] = floatval($_POST['Monto']);
        }
        $date_value = $_POST['Fecha_Pago'];
        if (isset($_POST['Fecha_Pago_submit'])) {
            $date_value = $_POST['Fecha_Pago_submit'];
        }
        if (trim($date_value) == '') {
            $values['Fecha_Pago'] = null;
        } else {
            $values['Fecha_Pago'] = $date_value;
        }
        $date_value = $_POST['anio'];
        if (isset($_POST['anio_submit'])) {
            $date_value = $_POST['anio_submit'];
        }
        if (trim($date_value) == '') {
            $values['anio'] = null;
        } else {
            $values['anio'] = $date_value;
        }
        if (isset($_POST['Mes'])) {
            $values['Mes'] = intval($_POST['Mes']);
        }
        $values['Estado_Pago'] = $_POST['Estado_Pago'];
        $where = $_SESSION['pagos_editable_primary_keys'];

        // begin transaction
        $db->transactionBegin();

        try {
            // update pagos
            if (DEMO !== true && !$db->update('pagos', $values, $where, DEBUG_DB_QUERIES)) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(UPDATE_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-edit-pagos');

                    // redirect to list page
                    if (isset($_SESSION['active_list_url'])) {
                        header('Location:' . $_SESSION['active_list_url']);
                    } else {
                        header('Location:' . ADMIN_URL . 'pagos');
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
        return 'pagos.' . $k;
    }, array_keys($params)),
    $params
);
$_SESSION['pagos_editable_primary_keys'] = $where_params;

if (!isset($_SESSION['errors']['form-edit-pagos']) || empty($_SESSION['errors']['form-edit-pagos'])) { // If no error registered
    $from = 'pagos  LEFT JOIN alumnos ON pagos.Alumno_ID=alumnos.ID';
    $columns = 'pagos.ID, pagos.Alumno_ID, pagos.Tipo_Pago, pagos.Monto, pagos.Fecha_Pago, pagos.anio, pagos.Mes, pagos.Estado_Pago';

    $where = $_SESSION['pagos_editable_primary_keys'];

    // if restricted rights
    if (ADMIN_LOCKED === true && Secure::canUpdateRestricted('pagos')) {
        $where = array_merge($where, Secure::getRestrictionQuery('pagos'));
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
    $_SESSION['form-edit-pagos']['ID'] = $row->ID;
    $_SESSION['form-edit-pagos']['Alumno_ID'] = $row->Alumno_ID;
    $_SESSION['form-edit-pagos']['Tipo_Pago'] = $row->Tipo_Pago;
    $_SESSION['form-edit-pagos']['Monto'] = $row->Monto;
    $Fecha_Pago_ts = strtotime($row->Fecha_Pago);
    // if timestamp is valid and > '1970-01-01'
    if (Utils::isValidTimeStamp($Fecha_Pago_ts) && $Fecha_Pago_ts > -62169984000) {
        $_SESSION['form-edit-pagos']['Fecha_Pago'] = date('Y-m-d', $Fecha_Pago_ts);
    } else {
        $_SESSION['form-edit-pagos']['Fecha_Pago'] = null;
    }
    $anio_ts = strtotime($row->anio);
    // if timestamp is valid and > '1970-01-01'
    if (Utils::isValidTimeStamp($anio_ts) && $anio_ts > -62169984000) {
        $_SESSION['form-edit-pagos']['anio'] = date('Y', $anio_ts);
    } else {
        $_SESSION['form-edit-pagos']['anio'] = null;
    }
    $_SESSION['form-edit-pagos']['Mes'] = $row->Mes;
    $_SESSION['form-edit-pagos']['Estado_Pago'] = $row->Estado_Pago;
}
// $params come from data-forms.php
$pk_url_params = http_build_query($params, '', '/');

$form = new Form('form-edit-pagos', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'pagos/edit/' . $pk_url_params);
$form->startFieldset();

// ID --

$form->setCols(2, 10);
$form->addInput('hidden', 'ID', '');

// Alumno_ID --

$form->setCols(2, 10);
$from = 'alumnos';
$columns = 'alumnos.ID, alumnos.Nombre';
$where = array();
$extras = array(
    'select_distinct' => true,
    'order_by' => 'alumnos.ID'
);

// restrict if relationship table is the users table OR if the relationship table is used in the restriction query
if (ADMIN_LOCKED === true && Secure::canCreateRestricted('pagos')) {
    $secure_restriction_query = Secure::getRestrictionQuery('pagos');
    if (!empty($secure_restriction_query)) {
        if ('alumnos' == USERS_TABLE) {
            $restriction_query = 'alumnos.ID = ' . $_SESSION['secure_user_ID'];
            $where[] = $restriction_query;
        } elseif (preg_match('/alumnos\./', $secure_restriction_query[0])) {
            $restriction_query = 'pagos' . $secure_restriction_query[0];
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

// Tipo_Pago --
$form->addInput('text', 'Tipo_Pago', '', 'Tipo Pago', '');

// Monto --
$form->addInput('number', 'Monto', '', 'Monto', 'data-decimals=2, step=0.001');

// Fecha_Pago --
$form->addPlugin('pickadate', '#Fecha_Pago');
$form->addInput('text', 'Fecha_Pago', '', 'Fecha Pago', 'data-format=dd mmmm yyyy, data-format-submit=yyyy-mm-dd, data-set-default-date=true');

// anio --
$form->addPlugin('pickadate', '#anio');
$form->addInput('text', 'anio', '', 'Anio', 'data-format=dd mmmm yyyy, data-format-submit=yyyy-mm-dd, data-set-default-date=true');

// Mes --
$form->addInput('number', 'Mes', '', 'Mes', '');

// Estado_Pago --
$form->addInput('text', 'Estado_Pago', '', 'Estado Pago', '');
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, data-ladda-button=true, data-style=zoom-in, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success, data-ladda-button=true, data-style=zoom-in', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-edit-pagos');
$form->addPlugin('formvalidation', '#form-edit-pagos', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
