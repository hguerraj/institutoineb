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

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-edit-cursos') === true) {
    $validator = Form::validate('form-edit-cursos', FORMVALIDATION_PHP_LANG);
    $validator->maxLength(100)->validate('Nombre_Curso');
    $validator->maxLength(255)->validate('descripcion');
    $validator->integer()->validate('Grado_ID');
    $validator->min(-99999999999)->validate('Grado_ID');
    $validator->max(99999999999)->validate('Grado_ID');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-edit-cursos'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');
        $values = array();
        $values['Nombre_Curso'] = $_POST['Nombre_Curso'];
        $values['descripcion'] = $_POST['descripcion'];
        if (is_array($_POST['Grado_ID'])) {
            $json_values = json_encode($_POST['Grado_ID'], JSON_UNESCAPED_UNICODE);
            $values['Grado_ID'] = $json_values;
        } else {
            $values['Grado_ID'] = intval($_POST['Grado_ID']);
            if ($values['Grado_ID'] < 1) {
                $values['Grado_ID'] = null;
            }
        }
        $where = $_SESSION['cursos_editable_primary_keys'];

        // begin transaction
        $db->transactionBegin();

        try {
            // update cursos
            if (DEMO !== true && !$db->update('cursos', $values, $where, DEBUG_DB_QUERIES)) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(UPDATE_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-edit-cursos');

                    // redirect to list page
                    if (isset($_SESSION['active_list_url'])) {
                        header('Location:' . $_SESSION['active_list_url']);
                    } else {
                        header('Location:' . ADMIN_URL . 'cursos');
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
        return 'cursos.' . $k;
    }, array_keys($params)),
    $params
);
$_SESSION['cursos_editable_primary_keys'] = $where_params;

if (!isset($_SESSION['errors']['form-edit-cursos']) || empty($_SESSION['errors']['form-edit-cursos'])) { // If no error registered
    $from = 'cursos  LEFT JOIN grados ON cursos.Grado_ID=grados.ID';
    $columns = 'cursos.ID, cursos.Nombre_Curso, cursos.descripcion, cursos.Grado_ID';

    $where = $_SESSION['cursos_editable_primary_keys'];

    // if restricted rights
    if (ADMIN_LOCKED === true && Secure::canUpdateRestricted('cursos')) {
        $where = array_merge($where, Secure::getRestrictionQuery('cursos'));
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
    $_SESSION['form-edit-cursos']['ID'] = $row->ID;
    $_SESSION['form-edit-cursos']['Nombre_Curso'] = $row->Nombre_Curso;
    $_SESSION['form-edit-cursos']['descripcion'] = $row->descripcion;
    $_SESSION['form-edit-cursos']['Grado_ID'] = $row->Grado_ID;
}
// $params come from data-forms.php
$pk_url_params = http_build_query($params, '', '/');

$form = new Form('form-edit-cursos', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'cursos/edit/' . $pk_url_params);
$form->startFieldset();

// ID --

$form->setCols(2, 10);
$form->addInput('hidden', 'ID', '');

// Nombre_Curso --

$form->setCols(2, 10);
$form->addInput('text', 'Nombre_Curso', '', 'Nombre Curso', '');

// descripcion --
$form->addInput('text', 'descripcion', '', 'Descripcion', '');

// Grado_ID --
$from = 'grados INNER JOIN carreras ON grados.Carrera_ID = carreras.ID';
$columns = 'grados.ID, grados.Nombre_Grado, carreras.Nombre_Carrera';
$where = array();
$extras = array(
    'select_distinct' => true,
    'order_by' => 'grados.Nombre_Grado'
);

// restrict if relationship table is the users table OR if the relationship table is used in the restriction query
if (ADMIN_LOCKED === true && Secure::canCreateRestricted('cursos')) {
    $secure_restriction_query = Secure::getRestrictionQuery('cursos');
    if (!empty($secure_restriction_query)) {
        if ('grados' == USERS_TABLE) {
            $restriction_query = 'grados.ID = ' . $_SESSION['secure_user_ID'];
            $where[] = $restriction_query;
        } elseif (preg_match('/grados\./', $secure_restriction_query[0])) {
            $restriction_query = 'cursos' . $secure_restriction_query[0];
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
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, data-ladda-button=true, data-style=zoom-in, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success, data-ladda-button=true, data-style=zoom-in', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-edit-cursos');
$form->addPlugin('formvalidation', '#form-edit-cursos', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
