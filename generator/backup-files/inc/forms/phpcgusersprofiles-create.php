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

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('form-create-phpcg-users-profiles') === true) {
    $validator = Form::validate('form-create-phpcg-users-profiles', FORMVALIDATION_PHP_LANG);
    $validator->required()->validate('profile_name');
    $validator->maxLength(100)->validate('profile_name');
    $validator->required()->validate('r_carreras');
    $validator->integer()->validate('r_carreras');
    $validator->min(-9)->validate('r_carreras');
    $validator->max(9)->validate('r_carreras');
    $validator->required()->validate('u_carreras');
    $validator->integer()->validate('u_carreras');
    $validator->min(-9)->validate('u_carreras');
    $validator->max(9)->validate('u_carreras');
    $validator->required()->validate('cd_carreras');
    $validator->integer()->validate('cd_carreras');
    $validator->min(-9)->validate('cd_carreras');
    $validator->max(9)->validate('cd_carreras');
    $validator->maxLength(255)->validate('cq_carreras');
    $validator->required()->validate('r_grados');
    $validator->integer()->validate('r_grados');
    $validator->min(-9)->validate('r_grados');
    $validator->max(9)->validate('r_grados');
    $validator->required()->validate('u_grados');
    $validator->integer()->validate('u_grados');
    $validator->min(-9)->validate('u_grados');
    $validator->max(9)->validate('u_grados');
    $validator->required()->validate('cd_grados');
    $validator->integer()->validate('cd_grados');
    $validator->min(-9)->validate('cd_grados');
    $validator->max(9)->validate('cd_grados');
    $validator->maxLength(255)->validate('cq_grados');
    $validator->required()->validate('r_phpcg_users');
    $validator->integer()->validate('r_phpcg_users');
    $validator->min(-9)->validate('r_phpcg_users');
    $validator->max(9)->validate('r_phpcg_users');
    $validator->required()->validate('u_phpcg_users');
    $validator->integer()->validate('u_phpcg_users');
    $validator->min(-9)->validate('u_phpcg_users');
    $validator->max(9)->validate('u_phpcg_users');
    $validator->required()->validate('cd_phpcg_users');
    $validator->integer()->validate('cd_phpcg_users');
    $validator->min(-9)->validate('cd_phpcg_users');
    $validator->max(9)->validate('cd_phpcg_users');
    $validator->maxLength(255)->validate('cq_phpcg_users');
    $validator->required()->validate('r_phpcg_users_profiles');
    $validator->integer()->validate('r_phpcg_users_profiles');
    $validator->min(-9)->validate('r_phpcg_users_profiles');
    $validator->max(9)->validate('r_phpcg_users_profiles');
    $validator->required()->validate('u_phpcg_users_profiles');
    $validator->integer()->validate('u_phpcg_users_profiles');
    $validator->min(-9)->validate('u_phpcg_users_profiles');
    $validator->max(9)->validate('u_phpcg_users_profiles');
    $validator->required()->validate('cd_phpcg_users_profiles');
    $validator->integer()->validate('cd_phpcg_users_profiles');
    $validator->min(-9)->validate('cd_phpcg_users_profiles');
    $validator->max(9)->validate('cd_phpcg_users_profiles');
    $validator->maxLength(255)->validate('cq_phpcg_users_profiles');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['form-create-phpcg-users-profiles'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');

        // begin transaction
        $db->transactionBegin();

        $values = array();
        $values['id'] = null;
        $values['profile_name'] = $_POST['profile_name'];
        if (is_array($_POST['r_carreras'])) {
            $json_values = json_encode($_POST['r_carreras'], JSON_UNESCAPED_UNICODE);
            $values['r_carreras'] = $json_values;
        } else {
            $values['r_carreras'] = intval($_POST['r_carreras']);
        }
        if (is_array($_POST['u_carreras'])) {
            $json_values = json_encode($_POST['u_carreras'], JSON_UNESCAPED_UNICODE);
            $values['u_carreras'] = $json_values;
        } else {
            $values['u_carreras'] = intval($_POST['u_carreras']);
        }
        if (is_array($_POST['cd_carreras'])) {
            $json_values = json_encode($_POST['cd_carreras'], JSON_UNESCAPED_UNICODE);
            $values['cd_carreras'] = $json_values;
        } else {
            $values['cd_carreras'] = intval($_POST['cd_carreras']);
        }
        $values['cq_carreras'] = $_POST['cq_carreras'];
        if (is_array($_POST['r_grados'])) {
            $json_values = json_encode($_POST['r_grados'], JSON_UNESCAPED_UNICODE);
            $values['r_grados'] = $json_values;
        } else {
            $values['r_grados'] = intval($_POST['r_grados']);
        }
        if (is_array($_POST['u_grados'])) {
            $json_values = json_encode($_POST['u_grados'], JSON_UNESCAPED_UNICODE);
            $values['u_grados'] = $json_values;
        } else {
            $values['u_grados'] = intval($_POST['u_grados']);
        }
        if (is_array($_POST['cd_grados'])) {
            $json_values = json_encode($_POST['cd_grados'], JSON_UNESCAPED_UNICODE);
            $values['cd_grados'] = $json_values;
        } else {
            $values['cd_grados'] = intval($_POST['cd_grados']);
        }
        $values['cq_grados'] = $_POST['cq_grados'];
        if (is_array($_POST['r_phpcg_users'])) {
            $json_values = json_encode($_POST['r_phpcg_users'], JSON_UNESCAPED_UNICODE);
            $values['r_phpcg_users'] = $json_values;
        } else {
            $values['r_phpcg_users'] = intval($_POST['r_phpcg_users']);
        }
        if (is_array($_POST['u_phpcg_users'])) {
            $json_values = json_encode($_POST['u_phpcg_users'], JSON_UNESCAPED_UNICODE);
            $values['u_phpcg_users'] = $json_values;
        } else {
            $values['u_phpcg_users'] = intval($_POST['u_phpcg_users']);
        }
        if (is_array($_POST['cd_phpcg_users'])) {
            $json_values = json_encode($_POST['cd_phpcg_users'], JSON_UNESCAPED_UNICODE);
            $values['cd_phpcg_users'] = $json_values;
        } else {
            $values['cd_phpcg_users'] = intval($_POST['cd_phpcg_users']);
        }
        $values['cq_phpcg_users'] = $_POST['cq_phpcg_users'];
        if (is_array($_POST['r_phpcg_users_profiles'])) {
            $json_values = json_encode($_POST['r_phpcg_users_profiles'], JSON_UNESCAPED_UNICODE);
            $values['r_phpcg_users_profiles'] = $json_values;
        } else {
            $values['r_phpcg_users_profiles'] = intval($_POST['r_phpcg_users_profiles']);
        }
        if (is_array($_POST['u_phpcg_users_profiles'])) {
            $json_values = json_encode($_POST['u_phpcg_users_profiles'], JSON_UNESCAPED_UNICODE);
            $values['u_phpcg_users_profiles'] = $json_values;
        } else {
            $values['u_phpcg_users_profiles'] = intval($_POST['u_phpcg_users_profiles']);
        }
        if (is_array($_POST['cd_phpcg_users_profiles'])) {
            $json_values = json_encode($_POST['cd_phpcg_users_profiles'], JSON_UNESCAPED_UNICODE);
            $values['cd_phpcg_users_profiles'] = $json_values;
        } else {
            $values['cd_phpcg_users_profiles'] = intval($_POST['cd_phpcg_users_profiles']);
        }
        $values['cq_phpcg_users_profiles'] = $_POST['cq_phpcg_users_profiles'];
        try {
            // insert into phpcg_users_profiles
            if (DEMO !== true && $db->insert('phpcg_users_profiles', $values, DEBUG_DB_QUERIES) === false) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(INSERT_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('form-create-phpcg-users-profiles');

                    // redirect to list page
                    if (isset($_SESSION['active_list_url'])) {
                        header('Location:' . $_SESSION['active_list_url']);
                    } else {
                        header('Location:' . ADMIN_URL . 'phpcgusersprofiles');
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

$form = new Form('form-create-phpcg-users-profiles', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . 'phpcgusersprofiles/create');

$form->addHtml(USERS_PROFILES_HELPER);


$form->startFieldset();

// id --

$form->setCols(2, 10);
$form->addInput('hidden', 'id', '');

// profile_name --

$form->setCols(2, 10);
$form->addInput('text', 'profile_name', '', 'Profile Name', 'required');

// r_carreras --
$form->groupElements('r_carreras', 'u_carreras');

$form->setCols(2, 4);
$form->addOption('r_carreras', '2', 'Si');
$form->addOption('r_carreras', '1', 'Restringido');
$form->addOption('r_carreras', '0', 'No');
$form->addSelect('r_carreras', 'Read Carreras', 'required, data-slimselect=true');

// u_carreras --
$form->addOption('u_carreras', '2', 'Si');
$form->addOption('u_carreras', '1', 'Restringido');
$form->addOption('u_carreras', '0', 'No');
$form->addSelect('u_carreras', 'Update Carreras', 'required, data-slimselect=true');

// cd_carreras --

$form->setCols(2, 10);
$form->addOption('cd_carreras', '2', 'Si');
$form->addOption('cd_carreras', '1', 'Restringido');
$form->addOption('cd_carreras', '0', 'No');
$form->addSelect('cd_carreras', 'Create/Delete Carreras', 'required, data-slimselect=true');

// cq_carreras --
$form->addInput('text', 'cq_carreras', '', 'Constraint Query Carreras<a href="#" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<p>WHERE query para limitar derechos.</p><p>Ejemplo: <br><em>, users WHERE table.users_ID = users.ID AND users.ID = CURRENT_USER_ID</em></p><p><em>CURRENT_USER_ID</em> será reemplazado automáticamente por el usuario conectado\'s ID.</p>" class="append"><span class="badge text-bg-info">?</span></a>', '');

// r_grados --
$form->groupElements('r_grados', 'u_grados');

$form->setCols(2, 4);
$form->addOption('r_grados', '2', 'Si');
$form->addOption('r_grados', '1', 'Restringido');
$form->addOption('r_grados', '0', 'No');
$form->addSelect('r_grados', 'Read Grados', 'required, data-slimselect=true');

// u_grados --
$form->addOption('u_grados', '2', 'Si');
$form->addOption('u_grados', '1', 'Restringido');
$form->addOption('u_grados', '0', 'No');
$form->addSelect('u_grados', 'Update Grados', 'required, data-slimselect=true');

// cd_grados --

$form->setCols(2, 10);
$form->addOption('cd_grados', '2', 'Si');
$form->addOption('cd_grados', '1', 'Restringido');
$form->addOption('cd_grados', '0', 'No');
$form->addSelect('cd_grados', 'Create/Delete Grados', 'required, data-slimselect=true');

// cq_grados --
$form->addInput('text', 'cq_grados', '', 'Constraint Query Grados<a href="#" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<p>WHERE query para limitar derechos.</p><p>Ejemplo: <br><em>, users WHERE table.users_ID = users.ID AND users.ID = CURRENT_USER_ID</em></p><p><em>CURRENT_USER_ID</em> será reemplazado automáticamente por el usuario conectado\'s ID.</p>" class="append"><span class="badge text-bg-info">?</span></a>', '');

// r_phpcg_users --
$form->groupElements('r_phpcg_users', 'u_phpcg_users');

$form->setCols(2, 4);
$form->addOption('r_phpcg_users', '2', 'Si');
$form->addOption('r_phpcg_users', '1', 'Restringido');
$form->addOption('r_phpcg_users', '0', 'No');
$form->addSelect('r_phpcg_users', 'Read Phpcg Users', 'required, data-slimselect=true');

// u_phpcg_users --
$form->addOption('u_phpcg_users', '2', 'Si');
$form->addOption('u_phpcg_users', '1', 'Restringido');
$form->addOption('u_phpcg_users', '0', 'No');
$form->addSelect('u_phpcg_users', 'Update Phpcg Users', 'required, data-slimselect=true');

// cd_phpcg_users --

$form->setCols(2, 10);
$form->addOption('cd_phpcg_users', '2', 'Si');
$form->addOption('cd_phpcg_users', '0', 'No');
$form->addSelect('cd_phpcg_users', 'Create/Delete Phpcg Users', 'required, data-slimselect=true');

// cq_phpcg_users --
$form->addHelper('Derechos de CREAR/ELIMINAR usuarios no pueden ser limitados - esto no tendría sentido', 'cq_phpcg_users', 'after');
$form->addInput('text', 'cq_phpcg_users', '', 'Constraint Query Phpcg Users', '');

// r_phpcg_users_profiles --
$form->groupElements('r_phpcg_users_profiles', 'u_phpcg_users_profiles');

$form->setCols(2, 4);
$form->addOption('r_phpcg_users_profiles', '2', 'Si');
$form->addOption('r_phpcg_users_profiles', '1', 'Restringido');
$form->addOption('r_phpcg_users_profiles', '0', 'No');
$form->addSelect('r_phpcg_users_profiles', 'Read Phpcg Users Profiles', 'required, data-slimselect=true');

// u_phpcg_users_profiles --
$form->addOption('u_phpcg_users_profiles', '2', 'Si');
$form->addOption('u_phpcg_users_profiles', '1', 'Restringido');
$form->addOption('u_phpcg_users_profiles', '0', 'No');
$form->addSelect('u_phpcg_users_profiles', 'Update Phpcg Users Profiles', 'required, data-slimselect=true');

// cd_phpcg_users_profiles --

$form->setCols(2, 10);
$form->addOption('cd_phpcg_users_profiles', '2', 'Si');
$form->addOption('cd_phpcg_users_profiles', '1', 'Restringido');
$form->addOption('cd_phpcg_users_profiles', '0', 'No');
$form->addSelect('cd_phpcg_users_profiles', 'Create/Delete Phpcg Users Profiles', 'required, data-slimselect=true');

// cq_phpcg_users_profiles --
$form->addInput('text', 'cq_phpcg_users_profiles', '', 'Constraint Query Phpcg Users Profiles<a href="#" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<p>WHERE query para limitar derechos.</p><p>Ejemplo: <br><em>, users WHERE table.users_ID = users.ID AND users.ID = CURRENT_USER_ID</em></p><p><em>CURRENT_USER_ID</em> será reemplazado automáticamente por el usuario conectado\'s ID.</p>" class="append"><span class="badge text-bg-info">?</span></a>', '');
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, data-ladda-button=true, data-style=zoom-in, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success, data-ladda-button=true, data-style=zoom-in', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#form-create-phpcg-users-profiles');
$form->addPlugin('formvalidation', '#form-create-phpcg-users-profiles', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
