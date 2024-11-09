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

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-edit-unidades') === true) {
    $validator = Form::validate('form-edit-unidades', FORMVALIDATION_PHP_LANG);
    $validator->integer()->validate('Curso_ID');
    $validator->min(-99999999999)->validate('Curso_ID');
    $validator->max(99999999999)->validate('Curso_ID');
    $validator->maxLength(100)->validate('Nombre_Unidad');
    $validator->maxLength(255)->validate('descripcion');
    $validator->float()->validate('numero_unidad');
    $validator->integer()->validate('numero_unidad');
    $validator->min(-99999999999)->validate('numero_unidad');
    $validator->max(99999999999)->validate('numero_unidad');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-edit-unidades'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');
        $values = array();
        if (is_array($_POST['Curso_ID'])) {
            $json_values = json_encode($_POST['Curso_ID'], JSON_UNESCAPED_UNICODE);
            $values['Curso_ID'] = $json_values;
        } else {
            $values['Curso_ID'] = intval($_POST['Curso_ID']);
            if ($values['Curso_ID'] < 1) {
                $values['Curso_ID'] = null;
            }
        }
        $values['Nombre_Unidad'] = $_POST['Nombre_Unidad'];
        $values['descripcion'] = $_POST['descripcion'];
        if (isset($_POST['numero_unidad'])) {
            $values['numero_unidad'] = intval($_POST['numero_unidad']);
        }
        $where = $_SESSION['unidades_editable_primary_keys'];

        // begin transaction
        $db->transactionBegin();

        try {
            // update unidades
            if (DEMO !== true && !$db->update('unidades', $values, $where, DEBUG_DB_QUERIES)) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(UPDATE_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-edit-unidades');

                    // redirect to list page
                    if (isset($_SESSION['active_list_url'])) {
                        header('Location:' . $_SESSION['active_list_url']);
                    } else {
                        header('Location:' . ADMIN_URL . 'unidades');
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
        return 'unidades.' . $k;
    }, array_keys($params)),
    $params
);
$_SESSION['unidades_editable_primary_keys'] = $where_params;

if (!isset($_SESSION['errors']['form-edit-unidades']) || empty($_SESSION['errors']['form-edit-unidades'])) { // If no error registered
    $from = 'unidades  LEFT JOIN cursos ON unidades.Curso_ID=cursos.ID';
    $columns = 'unidades.ID, unidades.Curso_ID, unidades.Nombre_Unidad, unidades.descripcion, unidades.numero_unidad';

    $where = $_SESSION['unidades_editable_primary_keys'];

    // if restricted rights
    if (ADMIN_LOCKED === true && Secure::canUpdateRestricted('unidades')) {
        $where = array_merge($where, Secure::getRestrictionQuery('unidades'));
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
    $_SESSION['form-edit-unidades']['ID'] = $row->ID;
    $_SESSION['form-edit-unidades']['Curso_ID'] = $row->Curso_ID;
    $_SESSION['form-edit-unidades']['Nombre_Unidad'] = $row->Nombre_Unidad;
    $_SESSION['form-edit-unidades']['descripcion'] = $row->descripcion;
    $_SESSION['form-edit-unidades']['numero_unidad'] = $row->numero_unidad;
}
// $params come from data-forms.php
$pk_url_params = http_build_query($params, '', '/');

$form = new Form('form-edit-unidades', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'unidades/edit/' . $pk_url_params);
$form->startFieldset();

// ID --

$form->setCols(2, 10);
$form->addInput('hidden', 'ID', '');

// Curso_ID --

$form->setCols(2, 10);
$from = 'cursos';
$columns = 'cursos.ID, cursos.Nombre_Curso';
$where = array();
$extras = array(
    'select_distinct' => true,
    'order_by' => 'cursos.ID'
);

// restrict if relationship table is the users table OR if the relationship table is used in the restriction query
if (ADMIN_LOCKED === true && Secure::canCreateRestricted('unidades')) {
    $secure_restriction_query = Secure::getRestrictionQuery('unidades');
    if (!empty($secure_restriction_query)) {
        if ('cursos' == USERS_TABLE) {
            $restriction_query = 'cursos.ID = ' . $_SESSION['secure_user_ID'];
            $where[] = $restriction_query;
        } elseif (preg_match('/cursos\./', $secure_restriction_query[0])) {
            $restriction_query = 'unidades' . $secure_restriction_query[0];
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
            $form->addOption('Curso_ID', '', '-');
    while ($row = $db->fetch()) {
        $value = $row->ID;
        $display_value = $row->ID;
        $display_value .= ' ' . $row->Nombre_Curso;
        if ($db_count > 0) {
            $form->addOption('Curso_ID', $value, $display_value);
        }
    }
}

if ($db_count > 0) {
    $form->addSelect('Curso_ID', 'Curso Id', 'data-slimselect=true');
} else {
    // for display purpose
    $form->addInput('text', 'Curso_ID-display', $display_value, 'Curso Id', 'readonly');

    // for send purpose
    $form->addInput('hidden', 'Curso_ID', $value);
}

// Nombre_Unidad --
$form->addInput('text', 'Nombre_Unidad', '', 'Nombre Unidad', '');

// descripcion --
$form->addInput('text', 'descripcion', '', 'Descripcion', '');

// numero_unidad --
$form->addInput('number', 'numero_unidad', '', 'Numero Unidad', '');
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, data-ladda-button=true, data-style=zoom-in, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success, data-ladda-button=true, data-style=zoom-in', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-edit-unidades');
$form->addPlugin('formvalidation', '#form-edit-unidades', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
