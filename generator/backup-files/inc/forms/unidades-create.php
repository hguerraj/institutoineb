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

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-create-unidades') === true) {
    $validator = Form::validate('form-create-unidades', FORMVALIDATION_PHP_LANG);
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
        $_SESSION['errors']['form-create-unidades'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');

        // begin transaction
        $db->transactionBegin();

        $values = array();
        $values['ID'] = null;
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
        try {
            // insert into unidades
            if (DEMO !== true && $db->insert('unidades', $values, DEBUG_DB_QUERIES) === false) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(INSERT_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-create-unidades');

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

$form = new Form('form-create-unidades', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'unidades/create');
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


// set the selected value if it has been sent in URL query parameters
if (isset($_GET['Curso_ID'])) {
    $_SESSION['form-create-unidades']['Curso_ID'] = addslashes($_GET['Curso_ID']);
}

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
$form->addPlugin('pretty-checkbox', '#form-create-unidades');
$form->addPlugin('formvalidation', '#form-create-unidades', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
