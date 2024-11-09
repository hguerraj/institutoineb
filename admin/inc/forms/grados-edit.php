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

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-edit-grados') === true) {
    $validator = Form::validate('form-edit-grados', FORMVALIDATION_PHP_LANG);
    $validator->maxLength(50)->validate('Nombre_Grado');
    $validator->maxLength(255)->validate('descripcion');
    $validator->integer()->validate('Carrera_ID');
    $validator->min(-99999999999)->validate('Carrera_ID');
    $validator->max(99999999999)->validate('Carrera_ID');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-edit-grados'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');
        $values = array();
        $values['Nombre_Grado'] = $_POST['Nombre_Grado'];
        $values['descripcion'] = $_POST['descripcion'];
        if (is_array($_POST['Carrera_ID'])) {
            $json_values = json_encode($_POST['Carrera_ID'], JSON_UNESCAPED_UNICODE);
            $values['Carrera_ID'] = $json_values;
        } else {
            $values['Carrera_ID'] = intval($_POST['Carrera_ID']);
            if ($values['Carrera_ID'] < 1) {
                $values['Carrera_ID'] = null;
            }
        }
        $where = $_SESSION['grados_editable_primary_keys'];

        // begin transaction
        $db->transactionBegin();

        try {
            // update grados
            if (DEMO !== true && !$db->update('grados', $values, $where, DEBUG_DB_QUERIES)) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(UPDATE_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-edit-grados');

                    // redirect to list page
                    if (isset($_SESSION['active_list_url'])) {
                        header('Location:' . $_SESSION['active_list_url']);
                    } else {
                        header('Location:' . ADMIN_URL . 'grados');
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
        return 'grados.' . $k;
    }, array_keys($params)),
    $params
);
$_SESSION['grados_editable_primary_keys'] = $where_params;

if (!isset($_SESSION['errors']['form-edit-grados']) || empty($_SESSION['errors']['form-edit-grados'])) { // If no error registered
    $from = 'grados  LEFT JOIN carreras ON grados.Carrera_ID=carreras.ID';
    $columns = 'grados.ID, grados.Nombre_Grado, grados.descripcion, grados.Carrera_ID';

    $where = $_SESSION['grados_editable_primary_keys'];

    // if restricted rights
    if (ADMIN_LOCKED === true && Secure::canUpdateRestricted('grados')) {
        $where = array_merge($where, Secure::getRestrictionQuery('grados'));
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
    $_SESSION['form-edit-grados']['ID'] = $row->ID;
    $_SESSION['form-edit-grados']['Nombre_Grado'] = $row->Nombre_Grado;
    $_SESSION['form-edit-grados']['descripcion'] = $row->descripcion;
    $_SESSION['form-edit-grados']['Carrera_ID'] = $row->Carrera_ID;
}
// $params come from data-forms.php
$pk_url_params = http_build_query($params, '', '/');

$form = new Form('form-edit-grados', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'grados/edit/' . $pk_url_params);
$form->startFieldset();

// ID --

$form->setCols(2, 10);
$form->addInput('hidden', 'ID', '');

// Nombre_Grado --

$form->setCols(2, 10);
$form->addInput('text', 'Nombre_Grado', '', 'Nombre Grado', '');

// descripcion --
$form->addInput('text', 'descripcion', '', 'Descripcion', '');

// Carrera_ID --
$from = 'carreras';
$columns = 'carreras.ID, carreras.Nombre_Carrera';
$where = array();
$extras = array(
    'select_distinct' => true,
    'order_by' => 'carreras.ID'
);

// restrict if relationship table is the users table OR if the relationship table is used in the restriction query
if (ADMIN_LOCKED === true && Secure::canCreateRestricted('grados')) {
    $secure_restriction_query = Secure::getRestrictionQuery('grados');
    if (!empty($secure_restriction_query)) {
        if ('carreras' == USERS_TABLE) {
            $restriction_query = 'carreras.ID = ' . $_SESSION['secure_user_ID'];
            $where[] = $restriction_query;
        } elseif (preg_match('/carreras\./', $secure_restriction_query[0])) {
            $restriction_query = 'grados' . $secure_restriction_query[0];
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
            $form->addOption('Carrera_ID', '', '-');
    while ($row = $db->fetch()) {
        $value = $row->ID;
        $display_value = $row->ID;
        $display_value .= ' ' . $row->Nombre_Carrera;
        if ($db_count > 0) {
            $form->addOption('Carrera_ID', $value, $display_value);
        }
    }
}

if ($db_count > 0) {
    $form->addSelect('Carrera_ID', 'Carrera Id', 'data-slimselect=true');
} else {
    // for display purpose
    $form->addInput('text', 'Carrera_ID-display', $display_value, 'Carrera Id', 'readonly');

    // for send purpose
    $form->addInput('hidden', 'Carrera_ID', $value);
}
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, data-ladda-button=true, data-style=zoom-in, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success, data-ladda-button=true, data-style=zoom-in', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-edit-grados');
$form->addPlugin('formvalidation', '#form-edit-grados', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
