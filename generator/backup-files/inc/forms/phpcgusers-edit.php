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

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-edit-phpcg-users') === true) {
    $validator = Form::validate('form-edit-phpcg-users', FORMVALIDATION_PHP_LANG);
    $validator->required()->validate('profiles_id');
    $validator->integer()->validate('profiles_id');
    $validator->min(-99999999999)->validate('profiles_id');
    $validator->max(99999999999)->validate('profiles_id');
    $validator->required()->validate('name');
    $validator->maxLength(50)->validate('name');
    $validator->required()->validate('firstname');
    $validator->maxLength(50)->validate('firstname');
    $validator->maxLength(50)->validate('address');
    $validator->maxLength(50)->validate('city');
    $validator->maxLength(20)->validate('zip_code');
    $validator->required()->validate('email');
    $validator->maxLength(50)->validate('email');
    $validator->maxLength(20)->validate('phone');
    $validator->maxLength(20)->validate('mobile_phone');
    if (!empty($_POST['password'])) {
        $validator->maxLength(255)->validate('password');
    } // end password optional validation
    $validator->required()->validate('active');
    $validator->integer()->validate('active');
    $validator->min(-9)->validate('active');
    $validator->max(9)->validate('active');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-edit-phpcg-users'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');
        $values = array();
        if (is_array($_POST['profiles_id'])) {
            $json_values = json_encode($_POST['profiles_id'], JSON_UNESCAPED_UNICODE);
            $values['profiles_id'] = $json_values;
        } else {
            $values['profiles_id'] = intval($_POST['profiles_id']);
            if ($values['profiles_id'] < 1) {
                $values['profiles_id'] = null;
            }
        }
        $values['name'] = $_POST['name'];
        $values['firstname'] = $_POST['firstname'];
        $values['address'] = $_POST['address'];
        $values['city'] = $_POST['city'];
        $values['zip_code'] = $_POST['zip_code'];
        $values['email'] = $_POST['email'];
        $values['phone'] = $_POST['phone'];
        $values['mobile_phone'] = $_POST['mobile_phone'];
        if (!empty($_POST['password'])) {
            $password = Secure::encrypt($_POST['password']);
            $values['password'] = $password;
        }
        if (isset($_POST['active'])) {
            $values['active'] = intval($_POST['active']);
        }
        $where = $_SESSION['phpcg_users_editable_primary_keys'];

        // begin transaction
        $db->transactionBegin();

        try {
            // update phpcg_users
            if (DEMO !== true && !$db->update('phpcg_users', $values, $where, DEBUG_DB_QUERIES)) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(UPDATE_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-edit-phpcg-users');

                    // redirect to list page
                    if (isset($_SESSION['active_list_url'])) {
                        header('Location:' . $_SESSION['active_list_url']);
                    } else {
                        header('Location:' . ADMIN_URL . 'phpcgusers');
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
        return 'phpcg_users.' . $k;
    }, array_keys($params)),
    $params
);
$_SESSION['phpcg_users_editable_primary_keys'] = $where_params;

if (!isset($_SESSION['errors']['form-edit-phpcg-users']) || empty($_SESSION['errors']['form-edit-phpcg-users'])) { // If no error registered
    $from = 'phpcg_users  LEFT JOIN phpcg_users_profiles ON phpcg_users.profiles_id=phpcg_users_profiles.id';
    $columns = 'phpcg_users.id, phpcg_users.profiles_id, phpcg_users.name, phpcg_users.firstname, phpcg_users.address, phpcg_users.city, phpcg_users.zip_code, phpcg_users.email, phpcg_users.phone, phpcg_users.mobile_phone, phpcg_users.password, phpcg_users.active';

    $where = $_SESSION['phpcg_users_editable_primary_keys'];

    // if restricted rights
    if (ADMIN_LOCKED === true && Secure::canUpdateRestricted('phpcg_users')) {
        $where = array_merge($where, Secure::getRestrictionQuery('phpcg_users'));
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
    $_SESSION['form-edit-phpcg-users']['id'] = $row->id;
    $_SESSION['form-edit-phpcg-users']['profiles_id'] = $row->profiles_id;
    $_SESSION['form-edit-phpcg-users']['name'] = $row->name;
    $_SESSION['form-edit-phpcg-users']['firstname'] = $row->firstname;
    $_SESSION['form-edit-phpcg-users']['address'] = $row->address;
    $_SESSION['form-edit-phpcg-users']['city'] = $row->city;
    $_SESSION['form-edit-phpcg-users']['zip_code'] = $row->zip_code;
    $_SESSION['form-edit-phpcg-users']['email'] = $row->email;
    $_SESSION['form-edit-phpcg-users']['phone'] = $row->phone;
    $_SESSION['form-edit-phpcg-users']['mobile_phone'] = $row->mobile_phone;
    $_SESSION['form-edit-phpcg-users']['password'] = '';
    $_SESSION['form-edit-phpcg-users']['active'] = $row->active;
}
// $params come from data-forms.php
$pk_url_params = http_build_query($params, '', '/');

$form = new Form('form-edit-phpcg-users', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'phpcgusers/edit/' . $pk_url_params);
$form->startFieldset();

// id --

$form->setCols(2, 10);
$form->addInput('hidden', 'id', '');

// profiles_id --

$form->setCols(2, 10);
$from = 'phpcg_users_profiles';
$columns = 'phpcg_users_profiles.id, phpcg_users_profiles.profile_name';
$where = array();
$extras = array(
    'select_distinct' => true,
    'order_by' => 'phpcg_users_profiles.profile_name'
);

// restrict if relationship table is the users table OR if the relationship table is used in the restriction query
if (ADMIN_LOCKED === true && Secure::canCreateRestricted('phpcg_users')) {
    $secure_restriction_query = Secure::getRestrictionQuery('phpcg_users');
    if (!empty($secure_restriction_query)) {
        if ('phpcg_users_profiles' == USERS_TABLE) {
            $restriction_query = 'phpcg_users_profiles.id = ' . $_SESSION['secure_user_ID'];
            $where[] = $restriction_query;
        } elseif (preg_match('/phpcg_users_profiles\./', $secure_restriction_query[0])) {
            $restriction_query = 'phpcg_users' . $secure_restriction_query[0];
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
    while ($row = $db->fetch()) {
        $value = $row->id;
        $display_value = $row->profile_name;
        if ($db_count > 1) {
            $form->addOption('profiles_id', $value, $display_value);
        }
    }
}

if ($db_count > 1) {
    $form->addSelect('profiles_id', 'Perfil', 'required, data-slimselect=true');
} else {
    // for display purpose
    $form->addInput('text', 'profiles_id-display', $display_value, 'Perfil', 'readonly');

    // for send purpose
    $form->addInput('hidden', 'profiles_id', $value);
}

// name --
$form->addInput('text', 'name', '', 'Name', 'required');

// firstname --
$form->addInput('text', 'firstname', '', 'Firstname', 'required');

// address --
$form->addInput('text', 'address', '', 'Address', '');

// city --
$form->addInput('text', 'city', '', 'City', '');

// zip_code --
$form->addInput('text', 'zip_code', '', 'Zip Code', '');

// email --
$form->addInput('text', 'email', '', 'Email', 'required');

// phone --
$form->addInput('text', 'phone', '', 'Phone', '');

// mobile_phone --
$form->addInput('text', 'mobile_phone', '', 'Mobile Phone', '');

// password --
$form->addHelper('Como mínimo 6 caracteres - Minúscula + Mayúscula + Números', 'password', 'after');
$form->addPlugin('passfield', '#password', 'lower-upper-number-min-6');
$form->addHelper(' - ' . PASSWORD_EDIT_HELPER, 'password');
$form->addInput('password', 'password', '', 'Password', '');

// active --
$form->addRadio('active', NO, 0);
$form->addRadio('active', YES, 1);
$form->printRadioGroup('active', 'Active', true, 'required');
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, data-ladda-button=true, data-style=zoom-in, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success, data-ladda-button=true, data-style=zoom-in', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-edit-phpcg-users');
$form->addPlugin('formvalidation', '#form-edit-phpcg-users', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
