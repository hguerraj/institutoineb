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

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-edit-carreras') === true) {
    $validator = Form::validate('form-edit-carreras', FORMVALIDATION_PHP_LANG);
    $validator->maxLength(100)->validate('Nombre_Carrera');
    $validator->maxLength(255)->validate('descripcion');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-edit-carreras'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');
        $values = array();
        $values['Nombre_Carrera'] = $_POST['Nombre_Carrera'];
        $values['descripcion'] = $_POST['descripcion'];
        $where = $_SESSION['carreras_editable_primary_keys'];

        // begin transaction
        $db->transactionBegin();

        try {
            // update carreras
            if (DEMO !== true && !$db->update('carreras', $values, $where, DEBUG_DB_QUERIES)) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(UPDATE_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-edit-carreras');

                    // redirect to list page
                    if (isset($_SESSION['active_list_url'])) {
                        header('Location:' . $_SESSION['active_list_url']);
                    } else {
                        header('Location:' . ADMIN_URL . 'carreras');
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
        return 'carreras.' . $k;
    }, array_keys($params)),
    $params
);
$_SESSION['carreras_editable_primary_keys'] = $where_params;

if (!isset($_SESSION['errors']['form-edit-carreras']) || empty($_SESSION['errors']['form-edit-carreras'])) { // If no error registered
    $from = 'carreras';
    $columns = '*';

    $where = $_SESSION['carreras_editable_primary_keys'];

    // if restricted rights
    if (ADMIN_LOCKED === true && Secure::canUpdateRestricted('carreras')) {
        $where = array_merge($where, Secure::getRestrictionQuery('carreras'));
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
    $_SESSION['form-edit-carreras']['ID'] = $row->ID;
    $_SESSION['form-edit-carreras']['Nombre_Carrera'] = $row->Nombre_Carrera;
    $_SESSION['form-edit-carreras']['descripcion'] = $row->descripcion;
}
// $params come from data-forms.php
$pk_url_params = http_build_query($params, '', '/');

$form = new Form('form-edit-carreras', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'carreras/edit/' . $pk_url_params);
$form->startFieldset();

// ID --

$form->setCols(2, 10);
$form->addInput('hidden', 'ID', '');

// Nombre_Carrera --

$form->setCols(2, 10);
$form->addInput('text', 'Nombre_Carrera', '', 'Nombre Carrera', '');

// descripcion --
$form->addInput('text', 'descripcion', '', 'Descripcion', '');
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, data-ladda-button=true, data-style=zoom-in, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success, data-ladda-button=true, data-style=zoom-in', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-edit-carreras');
$form->addPlugin('formvalidation', '#form-edit-carreras', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
