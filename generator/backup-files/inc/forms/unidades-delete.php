<?php
use phpformbuilder\Form;
use phpformbuilder\Validator\Validator;
use phpformbuilder\database\DB;
use common\Utils;

$debug_content = '';

// get referer pagination
$page_url_qry = '';
if (isset($_SESSION['unidades-page']) && is_numeric($_SESSION['unidades-page'])) {
    $page_url_qry = '/p' . $_SESSION['unidades-page'];
}

/* =============================================
delete if posted
============================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-delete-unidades') === true) {
    $validator = Form::validate('form-delete-unidades', FORMVALIDATION_PHP_LANG);

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-delete-unidades'] = $validator->getAllErrors();
    } else {
        if ($_POST['delete-unidades'] > 0) {
            $db = new DB(DEBUG);
            $db->setDebugMode('register');
            try {
                // begin transaction
                $db->transactionBegin();

                // Delete from origin
                $from = 'notas LEFT JOIN unidades ON notas.Unidad_ID = unidades.ID';
                $where = $_SESSION['unidades_editable_primary_keys'];

                $db->delete($from, $where, DEBUG_DB_QUERIES);

                if (DEBUG_DB_QUERIES) {
                    $debug_content = $db->getDebugContent();
                }

                // Delete from target table
                $where = $_SESSION['unidades_editable_primary_keys'];
                if (DEMO === true || $db->delete('unidades', $where, DEBUG_DB_QUERIES)) {
                    // ALL OK
                    if (!DEBUG_DB_QUERIES) {
                        $db->transactionCommit();
                        $_SESSION['msg'] = Utils::alert(DELETE_SUCCESS_MESSAGE, 'alert-success has-icon');

                        // reset form values
                        Form::clear('form-delete-unidades');

                        // unset the search string
                        if (isset($_SESSION['rp_search_string']['unidades'])) {
                            unset($_SESSION['rp_search_string']['unidades']);
                        }

                        // redirect to list page
                        if (isset($_SESSION['active_list_url'])) {
                            header('Location:' . $_SESSION['active_list_url']);
                        } else {
                            header('Location:' . ADMIN_URL . 'unidades');
                        }

                        // if we don't exit here, $_SESSION['msg'] will be unset
                        exit();
                    } else {
                        $debug_content = $db->getDebugContent();
                        $db->transactionRollback();

                        $_SESSION['msg'] = Utils::alert(DELETE_SUCCESS_MESSAGE . '<br>(' . DEBUG_DB_QUERIES_ENABLED . ')', 'alert-success has-icon');
                    }
                } else {
                    throw new \Exception($db->error());
                }
            } catch (\Exception $e) {
                if (DEBUG_DB_QUERIES) {
                    $debug_content = $db->getDebugContent();
                }
                $msg_content = DB_ERROR;
                if (ENVIRONMENT == 'development') {
                    $msg_content .= '<br>' . $e->getMessage() . '<br>' . $db->getLastSql();
                }
                $_SESSION['msg'] = Utils::alert($msg_content, 'alert-danger has-icon');
            }
        }
    }
} // END if POST

// register editable primary keys, which are NOT posted and will be the query delete filter
// $params come from data-forms.php
// replace 'fieldname' with 'table.fieldname' to avoid ambigous query
$where_params = array_combine(
    array_map(function ($k) {
        return 'unidades.' . $k;
    }, array_keys($params)),
    $params
);
$_SESSION['unidades_editable_primary_keys'] = $where_params;


if (!isset($db)) {
    $db = new DB(DEBUG);
    $db->setDebugMode('register');
}

// select name to display for confirmation
$from = 'unidades';
$columns = array('ID', 'Nombre_Unidad');
$where = $_SESSION['unidades_editable_primary_keys'];
$extras = array('limit' => 1);

$db->select($from, $columns, $where, $extras, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

$count = $db->rowCount();

if ($count > 0) {
    $row = $db->fetch();
    $display_value = $row->ID;
    $display_value .= ' ' . $row->Nombre_Unidad;
} else {
    // this should never happen
    // echo $db->getLastSql();
    header("X-Robots-Tag: noindex", true);
    exit('QRY ERROR');
}

$form = new Form('form-delete-unidades', 'vertical', 'novalidate');

// $params come from data-forms.php
$pk_url_params = http_build_query($params, '', '/');

$form->setAction(ADMIN_URL . 'unidades/delete/' . $pk_url_params);
$form->startFieldset();

// Get the records count from origin table
$notas_record_count = 0;

$from = 'notas LEFT JOIN unidades ON notas.Unidad_ID = unidades.ID';
$columns = array('notas.Unidad_ID' => 'record_count');
$where = $_SESSION['unidades_editable_primary_keys'];

$row = $db->selectCount($from, $columns, $where, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

if (!empty($row)) {
    $notas_record_count = $row->record_count;
}


// origin table
$form->addInput('hidden', 'constrained_tables_notas', true);

// Get the records count from origin table
$alumnos_record_count = 0;

$from = 'alumnos LEFT JOIN unidades ON alumnos.ID = unidades.ID';
$columns = array('alumnos.ID' => 'record_count');
$where = $_SESSION['unidades_editable_primary_keys'];

$row = $db->selectCount($from, $columns, $where, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

if (!empty($row)) {
    $alumnos_record_count = $row->record_count;
}


// origin table
$form->addInput('hidden', 'constrained_tables_alumnos', true);
$form->addHtml('<div class="text-center p-md">');
$form->addRadio('delete-unidades', NO, 0);
$form->addRadio('delete-unidades', YES, 1);
$form->printRadioGroup('delete-unidades', '<span class="me-20">' . DELETE_CONST . ' "' . $display_value . '" ?</span>', true, 'required');
$tables_records_html = '';
$tables_records_html .= '<span class="badge text-bg-warning prepend">notas (' . $notas_record_count . ' ' . RECORDS . ')</span>';
$tables_records_html .= '<span class="badge text-bg-warning prepend">alumnos (' . $alumnos_record_count . ' ' . RECORDS . ')</span>';
$form->addHtml(Utils::alert('<p class="text-semibold">' . MATCHING_RECORDS_WILL_BE_DELETED . ':</p><p>' . $tables_records_html . '</p>', 'alert-warning has-icon'));
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->addHtml('
</div>');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-delete-unidades');
$form->addPlugin('formvalidation', '#form-delete-unidades', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
