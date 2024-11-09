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

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-create-grados') === true) {
    $validator = Form::validate('form-create-grados', FORMVALIDATION_PHP_LANG);
    $validator->maxLength(50)->validate('Nombre_Grado');
    $validator->maxLength(255)->validate('descripcion');
    $validator->integer()->validate('Carrera_ID');
    $validator->min(-99999999999)->validate('Carrera_ID');
    $validator->max(99999999999)->validate('Carrera_ID');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-create-grados'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');

        // begin transaction
        $db->transactionBegin();

        $values = array();
        $values['ID'] = null;
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
        try {
            // insert into grados
            if (DEMO !== true && $db->insert('grados', $values, DEBUG_DB_QUERIES) === false) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(INSERT_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-create-grados');

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

$form = new Form('form-create-grados', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'grados/create');
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


// set the selected value if it has been sent in URL query parameters
if (isset($_GET['Carrera_ID'])) {
    $_SESSION['form-create-grados']['Carrera_ID'] = addslashes($_GET['Carrera_ID']);
}

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
$form->addPlugin('pretty-checkbox', '#form-create-grados');
$form->addPlugin('formvalidation', '#form-create-grados', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
