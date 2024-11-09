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

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-create-notas') === true) {
    $validator = Form::validate('form-create-notas', FORMVALIDATION_PHP_LANG);
    $validator->integer()->validate('Alumno_ID');
    $validator->min(-99999999999)->validate('Alumno_ID');
    $validator->max(99999999999)->validate('Alumno_ID');
    $validator->integer()->validate('Unidad_ID');
    $validator->min(-99999999999)->validate('Unidad_ID');
    $validator->max(99999999999)->validate('Unidad_ID');
    $validator->float()->validate('calificacion');
    $validator->min(-99999)->validate('calificacion');
    $validator->max(99999.99)->validate('calificacion');
    $validator->maxLength(50)->validate('Periodo_Academico');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-create-notas'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');

        // begin transaction
        $db->transactionBegin();

        $values = array();
        $values['ID'] = null;
        if (is_array($_POST['Alumno_ID'])) {
            $json_values = json_encode($_POST['Alumno_ID'], JSON_UNESCAPED_UNICODE);
            $values['Alumno_ID'] = $json_values;
        } else {
            $values['Alumno_ID'] = intval($_POST['Alumno_ID']);
            if ($values['Alumno_ID'] < 1) {
                $values['Alumno_ID'] = null;
            }
        }
        if (is_array($_POST['Unidad_ID'])) {
            $json_values = json_encode($_POST['Unidad_ID'], JSON_UNESCAPED_UNICODE);
            $values['Unidad_ID'] = $json_values;
        } else {
            $values['Unidad_ID'] = intval($_POST['Unidad_ID']);
            if ($values['Unidad_ID'] < 1) {
                $values['Unidad_ID'] = null;
            }
        }
        if (isset($_POST['calificacion'])) {
            $values['calificacion'] = floatval($_POST['calificacion']);
        }
        $values['Periodo_Academico'] = $_POST['Periodo_Academico'];
        try {
            // insert into notas
            if (DEMO !== true && $db->insert('notas', $values, DEBUG_DB_QUERIES) === false) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(INSERT_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-create-notas');

                    // redirect to list page
                    if (isset($_SESSION['active_list_url'])) {
                        header('Location:' . $_SESSION['active_list_url']);
                    } else {
                        header('Location:' . ADMIN_URL . 'notas');
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

$form = new Form('form-create-notas', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'notas/create');
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
if (ADMIN_LOCKED === true && Secure::canCreateRestricted('notas')) {
    $secure_restriction_query = Secure::getRestrictionQuery('notas');
    if (!empty($secure_restriction_query)) {
        if ('alumnos' == USERS_TABLE) {
            $restriction_query = 'alumnos.ID = ' . $_SESSION['secure_user_ID'];
            $where[] = $restriction_query;
        } elseif (preg_match('/alumnos\./', $secure_restriction_query[0])) {
            $restriction_query = 'notas' . $secure_restriction_query[0];
            $where[] = $restriction_query;
        }
    }
}

// default value if no record exist
$value = '';
$display_value = '';


// set the selected value if it has been sent in URL query parameters
if (isset($_GET['Alumno_ID'])) {
    $_SESSION['form-create-notas']['Alumno_ID'] = addslashes($_GET['Alumno_ID']);
}

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

// Unidad_ID --
$from = 'unidades';
$columns = 'unidades.ID, unidades.Nombre_Unidad';
$where = array();
$extras = array(
    'select_distinct' => true,
    'order_by' => 'unidades.ID'
);

// restrict if relationship table is the users table OR if the relationship table is used in the restriction query
if (ADMIN_LOCKED === true && Secure::canCreateRestricted('notas')) {
    $secure_restriction_query = Secure::getRestrictionQuery('notas');
    if (!empty($secure_restriction_query)) {
        if ('unidades' == USERS_TABLE) {
            $restriction_query = 'unidades.ID = ' . $_SESSION['secure_user_ID'];
            $where[] = $restriction_query;
        } elseif (preg_match('/unidades\./', $secure_restriction_query[0])) {
            $restriction_query = 'notas' . $secure_restriction_query[0];
            $where[] = $restriction_query;
        }
    }
}

// default value if no record exist
$value = '';
$display_value = '';


// set the selected value if it has been sent in URL query parameters
if (isset($_GET['Unidad_ID'])) {
    $_SESSION['form-create-notas']['Unidad_ID'] = addslashes($_GET['Unidad_ID']);
}

$db = new DB(DEBUG);
$db->setDebugMode('register');

$db->select($from, $columns, $where, $extras, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content .= $db->getDebugContent();
}

$db_count = $db->rowCount();
if (!empty($db_count)) {
            $form->addOption('Unidad_ID', '', '-');
    while ($row = $db->fetch()) {
        $value = $row->ID;
        $display_value = $row->ID;
        $display_value .= ' ' . $row->Nombre_Unidad;
        if ($db_count > 0) {
            $form->addOption('Unidad_ID', $value, $display_value);
        }
    }
}

if ($db_count > 0) {
    $form->addSelect('Unidad_ID', 'Unidad Id', 'data-slimselect=true');
} else {
    // for display purpose
    $form->addInput('text', 'Unidad_ID-display', $display_value, 'Unidad Id', 'readonly');

    // for send purpose
    $form->addInput('hidden', 'Unidad_ID', $value);
}

// calificacion --
$form->addInput('number', 'calificacion', '', 'Calificacion', 'data-decimals=2, step=0.001');

// Periodo_Academico --
$form->addInput('text', 'Periodo_Academico', '', 'Periodo Academico', '');
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, data-ladda-button=true, data-style=zoom-in, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success, data-ladda-button=true, data-style=zoom-in', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-create-notas');
$form->addPlugin('formvalidation', '#form-create-notas', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
