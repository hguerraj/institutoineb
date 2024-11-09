<?php
use phpformbuilder\Form;
use phpformbuilder\Validator\Validator;
use phpformbuilder\database\DB;
use common\Utils;

$debug_content = '';

// get referer pagination
$page_url_qry = '';
if (isset($_SESSION['phpcg_users-page']) && is_numeric($_SESSION['phpcg_users-page'])) {
    $page_url_qry = '/p' . $_SESSION['phpcg_users-page'];
}

/* =============================================
delete if posted
============================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-delete-phpcg-users') === true) {
    $validator = Form::validate('form-delete-phpcg-users', FORMVALIDATION_PHP_LANG);

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-delete-phpcg-users'] = $validator->getAllErrors();
    } else {
        if ($_POST['delete-phpcg-users'] > 0) {
            $db = new DB(DEBUG);
            $db->setDebugMode('register');
            try {
                // begin transaction
                $db->transactionBegin();

                // Delete from target table
                $where = $_SESSION['phpcg_users_editable_primary_keys'];
                if (DEMO === true || $db->delete('phpcg_users', $where, DEBUG_DB_QUERIES)) {
                    // ALL OK
                    if (!DEBUG_DB_QUERIES) {
                        $db->transactionCommit();
                        $_SESSION['msg'] = Utils::alert(DELETE_SUCCESS_MESSAGE, 'alert-success has-icon');

                        // reset form values
                        Form::clear('form-delete-phpcg-users');

                        // unset the search string
                        if (isset($_SESSION['rp_search_string']['phpcg_users'])) {
                            unset($_SESSION['rp_search_string']['phpcg_users']);
                        }

                        // redirect to list page
                        if (isset($_SESSION['active_list_url'])) {
                            header('Location:' . $_SESSION['active_list_url']);
                        } else {
                            header('Location:' . ADMIN_URL . 'phpcgusers');
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
        return 'phpcg_users.' . $k;
    }, array_keys($params)),
    $params
);
$_SESSION['phpcg_users_editable_primary_keys'] = $where_params;


if (!isset($db)) {
    $db = new DB(DEBUG);
    $db->setDebugMode('register');
}

// select name to display for confirmation
$from = 'phpcg_users';
$columns = array('id');
$where = $_SESSION['phpcg_users_editable_primary_keys'];
$extras = array('limit' => 1);

$db->select($from, $columns, $where, $extras, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

$count = $db->rowCount();

if ($count > 0) {
    $row = $db->fetch();
    $display_value = $row->id;
} else {
    // this should never happen
    // echo $db->getLastSql();
    header("X-Robots-Tag: noindex", true);
    exit('QRY ERROR');
}

$form = new Form('form-delete-phpcg-users', 'vertical', 'novalidate');

// $params come from data-forms.php
$pk_url_params = http_build_query($params, '', '/');

$form->setAction(ADMIN_URL . 'phpcgusers/delete/' . $pk_url_params);
$form->startFieldset();
$form->addHtml('<div class="text-center p-md">');
$form->addRadio('delete-phpcg-users', NO, 0);
$form->addRadio('delete-phpcg-users', YES, 1);
$form->printRadioGroup('delete-phpcg-users', '<span class="me-20">' . DELETE_CONST . ' "' . $display_value . '" ?</span>', true, 'required');
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->addHtml('
</div>');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-delete-phpcg-users');
$form->addPlugin('formvalidation', '#form-delete-phpcg-users', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
