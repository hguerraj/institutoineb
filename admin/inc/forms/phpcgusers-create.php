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

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-create-phpcg-users') === true) {
    $validator = Form::validate('form-create-phpcg-users', FORMVALIDATION_PHP_LANG);
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
    $validator->required()->validate('password');
    $validator->hasLowercase()->validate('password');
    $validator->hasUppercase()->validate('password');
    $validator->hasNumber()->validate('password');
    $validator->minLength(6)->validate('password');
    $validator->maxLength(255)->validate('password');
    $validator->required()->validate('active');
    $validator->integer()->validate('active');
    $validator->min(-9)->validate('active');
    $validator->max(9)->validate('active');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-create-phpcg-users'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');

        // begin transaction
        $db->transactionBegin();

        $values = array();
        $values['id'] = null;
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
        try {
            // insert into phpcg_users
            if (DEMO !== true && $db->insert('phpcg_users', $values, DEBUG_DB_QUERIES) === false) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(INSERT_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-create-phpcg-users');

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

$form = new Form('form-create-phpcg-users', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'phpcgusers/create');
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


// set the selected value if it has been sent in URL query parameters
if (isset($_GET['profiles_id'])) {
    $_SESSION['form-create-phpcg-users']['profiles_id'] = addslashes($_GET['profiles_id']);
}

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
$form->addInput('password', 'password', '', 'Password', 'required');

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
$form->addPlugin('pretty-checkbox', '#form-create-phpcg-users');
$form->addPlugin('formvalidation', '#form-create-phpcg-users', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
