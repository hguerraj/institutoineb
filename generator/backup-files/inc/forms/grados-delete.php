<?php
use phpformbuilder\Form;
use phpformbuilder\Validator\Validator;
use phpformbuilder\database\DB;
use common\Utils;

$debug_content = '';

// get referer pagination
$page_url_qry = '';
if (isset($_SESSION['grados-page']) && is_numeric($_SESSION['grados-page'])) {
    $page_url_qry = '/p' . $_SESSION['grados-page'];
}

/* =============================================
delete if posted
============================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-delete-grados') === true) {
    $validator = Form::validate('form-delete-grados', FORMVALIDATION_PHP_LANG);

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-delete-grados'] = $validator->getAllErrors();
    } else {
        if ($_POST['delete-grados'] > 0) {
            $db = new DB(DEBUG);
            $db->setDebugMode('register');
            try {
                // begin transaction
                $db->transactionBegin();

                // Delete from origin
                $from = 'alumnos LEFT JOIN grados ON alumnos.Grado_ID = grados.ID';
                $where = $_SESSION['grados_editable_primary_keys'];

                $db->delete($from, $where, DEBUG_DB_QUERIES);

                if (DEBUG_DB_QUERIES) {
                    $debug_content = $db->getDebugContent();
                }

                // Delete from origin
                $from = 'cursos LEFT JOIN grados ON cursos.Grado_ID = grados.ID';
                $where = $_SESSION['grados_editable_primary_keys'];

                $db->delete($from, $where, DEBUG_DB_QUERIES);

                if (DEBUG_DB_QUERIES) {
                    $debug_content = $db->getDebugContent();
                }

                // Delete from origin
                $from = 'grados_secciones LEFT JOIN grados ON grados_secciones.Grado_ID = grados.ID';
                $where = $_SESSION['grados_editable_primary_keys'];

                $db->delete($from, $where, DEBUG_DB_QUERIES);

                if (DEBUG_DB_QUERIES) {
                    $debug_content = $db->getDebugContent();
                }

                // Delete from origin
                $from = 'inscripciones LEFT JOIN grados ON inscripciones.Grado_ID = grados.ID';
                $where = $_SESSION['grados_editable_primary_keys'];

                $db->delete($from, $where, DEBUG_DB_QUERIES);

                if (DEBUG_DB_QUERIES) {
                    $debug_content = $db->getDebugContent();
                }

                // Delete from origin
                $from = 'profesores LEFT JOIN grados ON profesores.Grado_ID = grados.ID';
                $where = $_SESSION['grados_editable_primary_keys'];

                $db->delete($from, $where, DEBUG_DB_QUERIES);

                if (DEBUG_DB_QUERIES) {
                    $debug_content = $db->getDebugContent();
                }

                // Delete from target table
                $where = $_SESSION['grados_editable_primary_keys'];
                if (DEMO === true || $db->delete('grados', $where, DEBUG_DB_QUERIES)) {
                    // ALL OK
                    if (!DEBUG_DB_QUERIES) {
                        $db->transactionCommit();
                        $_SESSION['msg'] = Utils::alert(DELETE_SUCCESS_MESSAGE, 'alert-success has-icon');

                        // reset form values
                        Form::clear('form-delete-grados');

                        // unset the search string
                        if (isset($_SESSION['rp_search_string']['grados'])) {
                            unset($_SESSION['rp_search_string']['grados']);
                        }

                        // redirect to list page
                        if (isset($_SESSION['active_list_url'])) {
                            header('Location:' . $_SESSION['active_list_url']);
                        } else {
                            header('Location:' . ADMIN_URL . 'grados');
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
        return 'grados.' . $k;
    }, array_keys($params)),
    $params
);
$_SESSION['grados_editable_primary_keys'] = $where_params;


if (!isset($db)) {
    $db = new DB(DEBUG);
    $db->setDebugMode('register');
}

// select name to display for confirmation
$from = 'grados';
$columns = array('ID');
$where = $_SESSION['grados_editable_primary_keys'];
$extras = array('limit' => 1);

$db->select($from, $columns, $where, $extras, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

$count = $db->rowCount();

if ($count > 0) {
    $row = $db->fetch();
    $display_value = $row->ID;
} else {
    // this should never happen
    // echo $db->getLastSql();
    header("X-Robots-Tag: noindex", true);
    exit('QRY ERROR');
}

$form = new Form('form-delete-grados', 'vertical', 'novalidate');

// $params come from data-forms.php
$pk_url_params = http_build_query($params, '', '/');

$form->setAction(ADMIN_URL . 'grados/delete/' . $pk_url_params);
$form->startFieldset();

// Get the records count from origin table
$alumnos_record_count = 0;

$from = 'alumnos LEFT JOIN grados ON alumnos.Grado_ID = grados.ID';
$columns = array('alumnos.Grado_ID' => 'record_count');
$where = $_SESSION['grados_editable_primary_keys'];

$row = $db->selectCount($from, $columns, $where, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

if (!empty($row)) {
    $alumnos_record_count = $row->record_count;
}


// origin table
$form->addInput('hidden', 'constrained_tables_alumnos', true);

// Get the records count from origin table
$cursos_record_count = 0;

$from = 'cursos LEFT JOIN grados ON cursos.Grado_ID = grados.ID';
$columns = array('cursos.Grado_ID' => 'record_count');
$where = $_SESSION['grados_editable_primary_keys'];

$row = $db->selectCount($from, $columns, $where, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

if (!empty($row)) {
    $cursos_record_count = $row->record_count;
}


// origin table
$form->addInput('hidden', 'constrained_tables_cursos', true);

// Get the records count from origin table
$grados_secciones_record_count = 0;

$from = 'grados_secciones LEFT JOIN grados ON grados_secciones.Grado_ID = grados.ID';
$columns = array('grados_secciones.Grado_ID' => 'record_count');
$where = $_SESSION['grados_editable_primary_keys'];

$row = $db->selectCount($from, $columns, $where, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

if (!empty($row)) {
    $grados_secciones_record_count = $row->record_count;
}


// origin table
$form->addInput('hidden', 'constrained_tables_grados_secciones', true);

// Get the records count from origin table
$inscripciones_record_count = 0;

$from = 'inscripciones LEFT JOIN grados ON inscripciones.Grado_ID = grados.ID';
$columns = array('inscripciones.Grado_ID' => 'record_count');
$where = $_SESSION['grados_editable_primary_keys'];

$row = $db->selectCount($from, $columns, $where, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

if (!empty($row)) {
    $inscripciones_record_count = $row->record_count;
}


// origin table
$form->addInput('hidden', 'constrained_tables_inscripciones', true);

// Get the records count from origin table
$profesores_record_count = 0;

$from = 'profesores LEFT JOIN grados ON profesores.Grado_ID = grados.ID';
$columns = array('profesores.Grado_ID' => 'record_count');
$where = $_SESSION['grados_editable_primary_keys'];

$row = $db->selectCount($from, $columns, $where, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

if (!empty($row)) {
    $profesores_record_count = $row->record_count;
}


// origin table
$form->addInput('hidden', 'constrained_tables_profesores', true);

// Get the records count from origin table
$usuarios_del_sistema_record_count = 0;

$from = 'usuarios_del_sistema LEFT JOIN grados ON usuarios_del_sistema.ID = grados.ID';
$columns = array('usuarios_del_sistema.ID' => 'record_count');
$where = $_SESSION['grados_editable_primary_keys'];

$row = $db->selectCount($from, $columns, $where, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

if (!empty($row)) {
    $usuarios_del_sistema_record_count = $row->record_count;
}


// origin table
$form->addInput('hidden', 'constrained_tables_usuarios_del_sistema', true);
$form->addHtml('<div class="text-center p-md">');
$form->addRadio('delete-grados', NO, 0);
$form->addRadio('delete-grados', YES, 1);
$form->printRadioGroup('delete-grados', '<span class="me-20">' . DELETE_CONST . ' "' . $display_value . '" ?</span>', true, 'required');
$tables_records_html = '';
$tables_records_html .= '<span class="badge text-bg-warning prepend">alumnos (' . $alumnos_record_count . ' ' . RECORDS . ')</span>';
$tables_records_html .= '<span class="badge text-bg-warning prepend">cursos (' . $cursos_record_count . ' ' . RECORDS . ')</span>';
$tables_records_html .= '<span class="badge text-bg-warning prepend">grados_secciones (' . $grados_secciones_record_count . ' ' . RECORDS . ')</span>';
$tables_records_html .= '<span class="badge text-bg-warning prepend">inscripciones (' . $inscripciones_record_count . ' ' . RECORDS . ')</span>';
$tables_records_html .= '<span class="badge text-bg-warning prepend">profesores (' . $profesores_record_count . ' ' . RECORDS . ')</span>';
$tables_records_html .= '<span class="badge text-bg-warning prepend">usuarios_del_sistema (' . $usuarios_del_sistema_record_count . ' ' . RECORDS . ')</span>';
$form->addHtml(Utils::alert('<p class="text-semibold">' . MATCHING_RECORDS_WILL_BE_DELETED . ':</p><p>' . $tables_records_html . '</p>', 'alert-warning has-icon'));
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->addHtml('
</div>');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-delete-grados');
$form->addPlugin('formvalidation', '#form-delete-grados', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
