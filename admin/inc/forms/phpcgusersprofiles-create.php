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
    $validator->required()->validate('r_alumnos');
    $validator->integer()->validate('r_alumnos');
    $validator->min(-9)->validate('r_alumnos');
    $validator->max(9)->validate('r_alumnos');
    $validator->required()->validate('u_alumnos');
    $validator->integer()->validate('u_alumnos');
    $validator->min(-9)->validate('u_alumnos');
    $validator->max(9)->validate('u_alumnos');
    $validator->required()->validate('cd_alumnos');
    $validator->integer()->validate('cd_alumnos');
    $validator->min(-9)->validate('cd_alumnos');
    $validator->max(9)->validate('cd_alumnos');
    $validator->maxLength(255)->validate('cq_alumnos');
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
    $validator->required()->validate('r_cursos');
    $validator->integer()->validate('r_cursos');
    $validator->min(-9)->validate('r_cursos');
    $validator->max(9)->validate('r_cursos');
    $validator->required()->validate('u_cursos');
    $validator->integer()->validate('u_cursos');
    $validator->min(-9)->validate('u_cursos');
    $validator->max(9)->validate('u_cursos');
    $validator->required()->validate('cd_cursos');
    $validator->integer()->validate('cd_cursos');
    $validator->min(-9)->validate('cd_cursos');
    $validator->max(9)->validate('cd_cursos');
    $validator->maxLength(255)->validate('cq_cursos');
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
    $validator->required()->validate('r_grados_secciones');
    $validator->integer()->validate('r_grados_secciones');
    $validator->min(-9)->validate('r_grados_secciones');
    $validator->max(9)->validate('r_grados_secciones');
    $validator->required()->validate('u_grados_secciones');
    $validator->integer()->validate('u_grados_secciones');
    $validator->min(-9)->validate('u_grados_secciones');
    $validator->max(9)->validate('u_grados_secciones');
    $validator->required()->validate('cd_grados_secciones');
    $validator->integer()->validate('cd_grados_secciones');
    $validator->min(-9)->validate('cd_grados_secciones');
    $validator->max(9)->validate('cd_grados_secciones');
    $validator->maxLength(255)->validate('cq_grados_secciones');
    $validator->required()->validate('r_inscripciones');
    $validator->integer()->validate('r_inscripciones');
    $validator->min(-9)->validate('r_inscripciones');
    $validator->max(9)->validate('r_inscripciones');
    $validator->required()->validate('u_inscripciones');
    $validator->integer()->validate('u_inscripciones');
    $validator->min(-9)->validate('u_inscripciones');
    $validator->max(9)->validate('u_inscripciones');
    $validator->required()->validate('cd_inscripciones');
    $validator->integer()->validate('cd_inscripciones');
    $validator->min(-9)->validate('cd_inscripciones');
    $validator->max(9)->validate('cd_inscripciones');
    $validator->maxLength(255)->validate('cq_inscripciones');
    $validator->required()->validate('r_notas');
    $validator->integer()->validate('r_notas');
    $validator->min(-9)->validate('r_notas');
    $validator->max(9)->validate('r_notas');
    $validator->required()->validate('u_notas');
    $validator->integer()->validate('u_notas');
    $validator->min(-9)->validate('u_notas');
    $validator->max(9)->validate('u_notas');
    $validator->required()->validate('cd_notas');
    $validator->integer()->validate('cd_notas');
    $validator->min(-9)->validate('cd_notas');
    $validator->max(9)->validate('cd_notas');
    $validator->maxLength(255)->validate('cq_notas');
    $validator->required()->validate('r_padres_encargados');
    $validator->integer()->validate('r_padres_encargados');
    $validator->min(-9)->validate('r_padres_encargados');
    $validator->max(9)->validate('r_padres_encargados');
    $validator->required()->validate('u_padres_encargados');
    $validator->integer()->validate('u_padres_encargados');
    $validator->min(-9)->validate('u_padres_encargados');
    $validator->max(9)->validate('u_padres_encargados');
    $validator->required()->validate('cd_padres_encargados');
    $validator->integer()->validate('cd_padres_encargados');
    $validator->min(-9)->validate('cd_padres_encargados');
    $validator->max(9)->validate('cd_padres_encargados');
    $validator->maxLength(255)->validate('cq_padres_encargados');
    $validator->required()->validate('r_pagos');
    $validator->integer()->validate('r_pagos');
    $validator->min(-9)->validate('r_pagos');
    $validator->max(9)->validate('r_pagos');
    $validator->required()->validate('u_pagos');
    $validator->integer()->validate('u_pagos');
    $validator->min(-9)->validate('u_pagos');
    $validator->max(9)->validate('u_pagos');
    $validator->required()->validate('cd_pagos');
    $validator->integer()->validate('cd_pagos');
    $validator->min(-9)->validate('cd_pagos');
    $validator->max(9)->validate('cd_pagos');
    $validator->maxLength(255)->validate('cq_pagos');
    $validator->required()->validate('r_profesores');
    $validator->integer()->validate('r_profesores');
    $validator->min(-9)->validate('r_profesores');
    $validator->max(9)->validate('r_profesores');
    $validator->required()->validate('u_profesores');
    $validator->integer()->validate('u_profesores');
    $validator->min(-9)->validate('u_profesores');
    $validator->max(9)->validate('u_profesores');
    $validator->required()->validate('cd_profesores');
    $validator->integer()->validate('cd_profesores');
    $validator->min(-9)->validate('cd_profesores');
    $validator->max(9)->validate('cd_profesores');
    $validator->maxLength(255)->validate('cq_profesores');
    $validator->required()->validate('r_unidades');
    $validator->integer()->validate('r_unidades');
    $validator->min(-9)->validate('r_unidades');
    $validator->max(9)->validate('r_unidades');
    $validator->required()->validate('u_unidades');
    $validator->integer()->validate('u_unidades');
    $validator->min(-9)->validate('u_unidades');
    $validator->max(9)->validate('u_unidades');
    $validator->required()->validate('cd_unidades');
    $validator->integer()->validate('cd_unidades');
    $validator->min(-9)->validate('cd_unidades');
    $validator->max(9)->validate('cd_unidades');
    $validator->maxLength(255)->validate('cq_unidades');
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
        if (is_array($_POST['r_alumnos'])) {
            $json_values = json_encode($_POST['r_alumnos'], JSON_UNESCAPED_UNICODE);
            $values['r_alumnos'] = $json_values;
        } else {
            $values['r_alumnos'] = intval($_POST['r_alumnos']);
        }
        if (is_array($_POST['u_alumnos'])) {
            $json_values = json_encode($_POST['u_alumnos'], JSON_UNESCAPED_UNICODE);
            $values['u_alumnos'] = $json_values;
        } else {
            $values['u_alumnos'] = intval($_POST['u_alumnos']);
        }
        if (is_array($_POST['cd_alumnos'])) {
            $json_values = json_encode($_POST['cd_alumnos'], JSON_UNESCAPED_UNICODE);
            $values['cd_alumnos'] = $json_values;
        } else {
            $values['cd_alumnos'] = intval($_POST['cd_alumnos']);
        }
        $values['cq_alumnos'] = $_POST['cq_alumnos'];
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
        if (is_array($_POST['r_cursos'])) {
            $json_values = json_encode($_POST['r_cursos'], JSON_UNESCAPED_UNICODE);
            $values['r_cursos'] = $json_values;
        } else {
            $values['r_cursos'] = intval($_POST['r_cursos']);
        }
        if (is_array($_POST['u_cursos'])) {
            $json_values = json_encode($_POST['u_cursos'], JSON_UNESCAPED_UNICODE);
            $values['u_cursos'] = $json_values;
        } else {
            $values['u_cursos'] = intval($_POST['u_cursos']);
        }
        if (is_array($_POST['cd_cursos'])) {
            $json_values = json_encode($_POST['cd_cursos'], JSON_UNESCAPED_UNICODE);
            $values['cd_cursos'] = $json_values;
        } else {
            $values['cd_cursos'] = intval($_POST['cd_cursos']);
        }
        $values['cq_cursos'] = $_POST['cq_cursos'];
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
        if (is_array($_POST['r_grados_secciones'])) {
            $json_values = json_encode($_POST['r_grados_secciones'], JSON_UNESCAPED_UNICODE);
            $values['r_grados_secciones'] = $json_values;
        } else {
            $values['r_grados_secciones'] = intval($_POST['r_grados_secciones']);
        }
        if (is_array($_POST['u_grados_secciones'])) {
            $json_values = json_encode($_POST['u_grados_secciones'], JSON_UNESCAPED_UNICODE);
            $values['u_grados_secciones'] = $json_values;
        } else {
            $values['u_grados_secciones'] = intval($_POST['u_grados_secciones']);
        }
        if (is_array($_POST['cd_grados_secciones'])) {
            $json_values = json_encode($_POST['cd_grados_secciones'], JSON_UNESCAPED_UNICODE);
            $values['cd_grados_secciones'] = $json_values;
        } else {
            $values['cd_grados_secciones'] = intval($_POST['cd_grados_secciones']);
        }
        $values['cq_grados_secciones'] = $_POST['cq_grados_secciones'];
        if (is_array($_POST['r_inscripciones'])) {
            $json_values = json_encode($_POST['r_inscripciones'], JSON_UNESCAPED_UNICODE);
            $values['r_inscripciones'] = $json_values;
        } else {
            $values['r_inscripciones'] = intval($_POST['r_inscripciones']);
        }
        if (is_array($_POST['u_inscripciones'])) {
            $json_values = json_encode($_POST['u_inscripciones'], JSON_UNESCAPED_UNICODE);
            $values['u_inscripciones'] = $json_values;
        } else {
            $values['u_inscripciones'] = intval($_POST['u_inscripciones']);
        }
        if (is_array($_POST['cd_inscripciones'])) {
            $json_values = json_encode($_POST['cd_inscripciones'], JSON_UNESCAPED_UNICODE);
            $values['cd_inscripciones'] = $json_values;
        } else {
            $values['cd_inscripciones'] = intval($_POST['cd_inscripciones']);
        }
        $values['cq_inscripciones'] = $_POST['cq_inscripciones'];
        if (is_array($_POST['r_notas'])) {
            $json_values = json_encode($_POST['r_notas'], JSON_UNESCAPED_UNICODE);
            $values['r_notas'] = $json_values;
        } else {
            $values['r_notas'] = intval($_POST['r_notas']);
        }
        if (is_array($_POST['u_notas'])) {
            $json_values = json_encode($_POST['u_notas'], JSON_UNESCAPED_UNICODE);
            $values['u_notas'] = $json_values;
        } else {
            $values['u_notas'] = intval($_POST['u_notas']);
        }
        if (is_array($_POST['cd_notas'])) {
            $json_values = json_encode($_POST['cd_notas'], JSON_UNESCAPED_UNICODE);
            $values['cd_notas'] = $json_values;
        } else {
            $values['cd_notas'] = intval($_POST['cd_notas']);
        }
        $values['cq_notas'] = $_POST['cq_notas'];
        if (is_array($_POST['r_padres_encargados'])) {
            $json_values = json_encode($_POST['r_padres_encargados'], JSON_UNESCAPED_UNICODE);
            $values['r_padres_encargados'] = $json_values;
        } else {
            $values['r_padres_encargados'] = intval($_POST['r_padres_encargados']);
        }
        if (is_array($_POST['u_padres_encargados'])) {
            $json_values = json_encode($_POST['u_padres_encargados'], JSON_UNESCAPED_UNICODE);
            $values['u_padres_encargados'] = $json_values;
        } else {
            $values['u_padres_encargados'] = intval($_POST['u_padres_encargados']);
        }
        if (is_array($_POST['cd_padres_encargados'])) {
            $json_values = json_encode($_POST['cd_padres_encargados'], JSON_UNESCAPED_UNICODE);
            $values['cd_padres_encargados'] = $json_values;
        } else {
            $values['cd_padres_encargados'] = intval($_POST['cd_padres_encargados']);
        }
        $values['cq_padres_encargados'] = $_POST['cq_padres_encargados'];
        if (is_array($_POST['r_pagos'])) {
            $json_values = json_encode($_POST['r_pagos'], JSON_UNESCAPED_UNICODE);
            $values['r_pagos'] = $json_values;
        } else {
            $values['r_pagos'] = intval($_POST['r_pagos']);
        }
        if (is_array($_POST['u_pagos'])) {
            $json_values = json_encode($_POST['u_pagos'], JSON_UNESCAPED_UNICODE);
            $values['u_pagos'] = $json_values;
        } else {
            $values['u_pagos'] = intval($_POST['u_pagos']);
        }
        if (is_array($_POST['cd_pagos'])) {
            $json_values = json_encode($_POST['cd_pagos'], JSON_UNESCAPED_UNICODE);
            $values['cd_pagos'] = $json_values;
        } else {
            $values['cd_pagos'] = intval($_POST['cd_pagos']);
        }
        $values['cq_pagos'] = $_POST['cq_pagos'];
        if (is_array($_POST['r_profesores'])) {
            $json_values = json_encode($_POST['r_profesores'], JSON_UNESCAPED_UNICODE);
            $values['r_profesores'] = $json_values;
        } else {
            $values['r_profesores'] = intval($_POST['r_profesores']);
        }
        if (is_array($_POST['u_profesores'])) {
            $json_values = json_encode($_POST['u_profesores'], JSON_UNESCAPED_UNICODE);
            $values['u_profesores'] = $json_values;
        } else {
            $values['u_profesores'] = intval($_POST['u_profesores']);
        }
        if (is_array($_POST['cd_profesores'])) {
            $json_values = json_encode($_POST['cd_profesores'], JSON_UNESCAPED_UNICODE);
            $values['cd_profesores'] = $json_values;
        } else {
            $values['cd_profesores'] = intval($_POST['cd_profesores']);
        }
        $values['cq_profesores'] = $_POST['cq_profesores'];
        if (is_array($_POST['r_unidades'])) {
            $json_values = json_encode($_POST['r_unidades'], JSON_UNESCAPED_UNICODE);
            $values['r_unidades'] = $json_values;
        } else {
            $values['r_unidades'] = intval($_POST['r_unidades']);
        }
        if (is_array($_POST['u_unidades'])) {
            $json_values = json_encode($_POST['u_unidades'], JSON_UNESCAPED_UNICODE);
            $values['u_unidades'] = $json_values;
        } else {
            $values['u_unidades'] = intval($_POST['u_unidades']);
        }
        if (is_array($_POST['cd_unidades'])) {
            $json_values = json_encode($_POST['cd_unidades'], JSON_UNESCAPED_UNICODE);
            $values['cd_unidades'] = $json_values;
        } else {
            $values['cd_unidades'] = intval($_POST['cd_unidades']);
        }
        $values['cq_unidades'] = $_POST['cq_unidades'];
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

// r_alumnos --
$form->groupElements('r_alumnos', 'u_alumnos');

$form->setCols(2, 4);
$form->addOption('r_alumnos', '2', 'Si');
$form->addOption('r_alumnos', '1', 'Restringido');
$form->addOption('r_alumnos', '0', 'No');
$form->addSelect('r_alumnos', 'Read Alumnos', 'required, data-slimselect=true');

// u_alumnos --
$form->addOption('u_alumnos', '2', 'Si');
$form->addOption('u_alumnos', '1', 'Restringido');
$form->addOption('u_alumnos', '0', 'No');
$form->addSelect('u_alumnos', 'Update Alumnos', 'required, data-slimselect=true');

// cd_alumnos --

$form->setCols(2, 10);
$form->addOption('cd_alumnos', '2', 'Si');
$form->addOption('cd_alumnos', '1', 'Restringido');
$form->addOption('cd_alumnos', '0', 'No');
$form->addSelect('cd_alumnos', 'Create/Delete Alumnos', 'required, data-slimselect=true');

// cq_alumnos --
$form->addInput('text', 'cq_alumnos', '', 'Constraint Query Alumnos<a href="#" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<p>WHERE query para limitar derechos.</p><p>Ejemplo: <br><em>, users WHERE table.users_ID = users.ID AND users.ID = CURRENT_USER_ID</em></p><p><em>CURRENT_USER_ID</em> será reemplazado automáticamente por el usuario conectado\'s ID.</p>" class="append"><span class="badge text-bg-info">?</span></a>', '');

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

// r_cursos --
$form->groupElements('r_cursos', 'u_cursos');

$form->setCols(2, 4);
$form->addOption('r_cursos', '2', 'Si');
$form->addOption('r_cursos', '1', 'Restringido');
$form->addOption('r_cursos', '0', 'No');
$form->addSelect('r_cursos', 'Read Cursos', 'required, data-slimselect=true');

// u_cursos --
$form->addOption('u_cursos', '2', 'Si');
$form->addOption('u_cursos', '1', 'Restringido');
$form->addOption('u_cursos', '0', 'No');
$form->addSelect('u_cursos', 'Update Cursos', 'required, data-slimselect=true');

// cd_cursos --

$form->setCols(2, 10);
$form->addOption('cd_cursos', '2', 'Si');
$form->addOption('cd_cursos', '1', 'Restringido');
$form->addOption('cd_cursos', '0', 'No');
$form->addSelect('cd_cursos', 'Create/Delete Cursos', 'required, data-slimselect=true');

// cq_cursos --
$form->addInput('text', 'cq_cursos', '', 'Constraint Query Cursos<a href="#" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<p>WHERE query para limitar derechos.</p><p>Ejemplo: <br><em>, users WHERE table.users_ID = users.ID AND users.ID = CURRENT_USER_ID</em></p><p><em>CURRENT_USER_ID</em> será reemplazado automáticamente por el usuario conectado\'s ID.</p>" class="append"><span class="badge text-bg-info">?</span></a>', '');

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

// r_grados_secciones --
$form->groupElements('r_grados_secciones', 'u_grados_secciones');

$form->setCols(2, 4);
$form->addOption('r_grados_secciones', '2', 'Si');
$form->addOption('r_grados_secciones', '1', 'Restringido');
$form->addOption('r_grados_secciones', '0', 'No');
$form->addSelect('r_grados_secciones', 'Read Grados Secciones', 'required, data-slimselect=true');

// u_grados_secciones --
$form->addOption('u_grados_secciones', '2', 'Si');
$form->addOption('u_grados_secciones', '1', 'Restringido');
$form->addOption('u_grados_secciones', '0', 'No');
$form->addSelect('u_grados_secciones', 'Update Grados Secciones', 'required, data-slimselect=true');

// cd_grados_secciones --

$form->setCols(2, 10);
$form->addOption('cd_grados_secciones', '2', 'Si');
$form->addOption('cd_grados_secciones', '1', 'Restringido');
$form->addOption('cd_grados_secciones', '0', 'No');
$form->addSelect('cd_grados_secciones', 'Create/Delete Grados Secciones', 'required, data-slimselect=true');

// cq_grados_secciones --
$form->addInput('text', 'cq_grados_secciones', '', 'Constraint Query Grados Secciones<a href="#" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<p>WHERE query para limitar derechos.</p><p>Ejemplo: <br><em>, users WHERE table.users_ID = users.ID AND users.ID = CURRENT_USER_ID</em></p><p><em>CURRENT_USER_ID</em> será reemplazado automáticamente por el usuario conectado\'s ID.</p>" class="append"><span class="badge text-bg-info">?</span></a>', '');

// r_inscripciones --
$form->groupElements('r_inscripciones', 'u_inscripciones');

$form->setCols(2, 4);
$form->addOption('r_inscripciones', '2', 'Si');
$form->addOption('r_inscripciones', '1', 'Restringido');
$form->addOption('r_inscripciones', '0', 'No');
$form->addSelect('r_inscripciones', 'Read Inscripciones', 'required, data-slimselect=true');

// u_inscripciones --
$form->addOption('u_inscripciones', '2', 'Si');
$form->addOption('u_inscripciones', '1', 'Restringido');
$form->addOption('u_inscripciones', '0', 'No');
$form->addSelect('u_inscripciones', 'Update Inscripciones', 'required, data-slimselect=true');

// cd_inscripciones --

$form->setCols(2, 10);
$form->addOption('cd_inscripciones', '2', 'Si');
$form->addOption('cd_inscripciones', '1', 'Restringido');
$form->addOption('cd_inscripciones', '0', 'No');
$form->addSelect('cd_inscripciones', 'Create/Delete Inscripciones', 'required, data-slimselect=true');

// cq_inscripciones --
$form->addInput('text', 'cq_inscripciones', '', 'Constraint Query Inscripciones<a href="#" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<p>WHERE query para limitar derechos.</p><p>Ejemplo: <br><em>, users WHERE table.users_ID = users.ID AND users.ID = CURRENT_USER_ID</em></p><p><em>CURRENT_USER_ID</em> será reemplazado automáticamente por el usuario conectado\'s ID.</p>" class="append"><span class="badge text-bg-info">?</span></a>', '');

// r_notas --
$form->groupElements('r_notas', 'u_notas');

$form->setCols(2, 4);
$form->addOption('r_notas', '2', 'Si');
$form->addOption('r_notas', '1', 'Restringido');
$form->addOption('r_notas', '0', 'No');
$form->addSelect('r_notas', 'Read Notas', 'required, data-slimselect=true');

// u_notas --
$form->addOption('u_notas', '2', 'Si');
$form->addOption('u_notas', '1', 'Restringido');
$form->addOption('u_notas', '0', 'No');
$form->addSelect('u_notas', 'Update Notas', 'required, data-slimselect=true');

// cd_notas --

$form->setCols(2, 10);
$form->addOption('cd_notas', '2', 'Si');
$form->addOption('cd_notas', '1', 'Restringido');
$form->addOption('cd_notas', '0', 'No');
$form->addSelect('cd_notas', 'Create/Delete Notas', 'required, data-slimselect=true');

// cq_notas --
$form->addInput('text', 'cq_notas', '', 'Constraint Query Notas<a href="#" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<p>WHERE query para limitar derechos.</p><p>Ejemplo: <br><em>, users WHERE table.users_ID = users.ID AND users.ID = CURRENT_USER_ID</em></p><p><em>CURRENT_USER_ID</em> será reemplazado automáticamente por el usuario conectado\'s ID.</p>" class="append"><span class="badge text-bg-info">?</span></a>', '');

// r_padres_encargados --
$form->groupElements('r_padres_encargados', 'u_padres_encargados');

$form->setCols(2, 4);
$form->addOption('r_padres_encargados', '2', 'Si');
$form->addOption('r_padres_encargados', '1', 'Restringido');
$form->addOption('r_padres_encargados', '0', 'No');
$form->addSelect('r_padres_encargados', 'Read Padres Encargados', 'required, data-slimselect=true');

// u_padres_encargados --
$form->addOption('u_padres_encargados', '2', 'Si');
$form->addOption('u_padres_encargados', '1', 'Restringido');
$form->addOption('u_padres_encargados', '0', 'No');
$form->addSelect('u_padres_encargados', 'Update Padres Encargados', 'required, data-slimselect=true');

// cd_padres_encargados --

$form->setCols(2, 10);
$form->addOption('cd_padres_encargados', '2', 'Si');
$form->addOption('cd_padres_encargados', '1', 'Restringido');
$form->addOption('cd_padres_encargados', '0', 'No');
$form->addSelect('cd_padres_encargados', 'Create/Delete Padres Encargados', 'required, data-slimselect=true');

// cq_padres_encargados --
$form->addInput('text', 'cq_padres_encargados', '', 'Constraint Query Padres Encargados<a href="#" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<p>WHERE query para limitar derechos.</p><p>Ejemplo: <br><em>, users WHERE table.users_ID = users.ID AND users.ID = CURRENT_USER_ID</em></p><p><em>CURRENT_USER_ID</em> será reemplazado automáticamente por el usuario conectado\'s ID.</p>" class="append"><span class="badge text-bg-info">?</span></a>', '');

// r_pagos --
$form->groupElements('r_pagos', 'u_pagos');

$form->setCols(2, 4);
$form->addOption('r_pagos', '2', 'Si');
$form->addOption('r_pagos', '1', 'Restringido');
$form->addOption('r_pagos', '0', 'No');
$form->addSelect('r_pagos', 'Read Pagos', 'required, data-slimselect=true');

// u_pagos --
$form->addOption('u_pagos', '2', 'Si');
$form->addOption('u_pagos', '1', 'Restringido');
$form->addOption('u_pagos', '0', 'No');
$form->addSelect('u_pagos', 'Update Pagos', 'required, data-slimselect=true');

// cd_pagos --

$form->setCols(2, 10);
$form->addOption('cd_pagos', '2', 'Si');
$form->addOption('cd_pagos', '1', 'Restringido');
$form->addOption('cd_pagos', '0', 'No');
$form->addSelect('cd_pagos', 'Create/Delete Pagos', 'required, data-slimselect=true');

// cq_pagos --
$form->addInput('text', 'cq_pagos', '', 'Constraint Query Pagos<a href="#" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<p>WHERE query para limitar derechos.</p><p>Ejemplo: <br><em>, users WHERE table.users_ID = users.ID AND users.ID = CURRENT_USER_ID</em></p><p><em>CURRENT_USER_ID</em> será reemplazado automáticamente por el usuario conectado\'s ID.</p>" class="append"><span class="badge text-bg-info">?</span></a>', '');

// r_profesores --
$form->groupElements('r_profesores', 'u_profesores');

$form->setCols(2, 4);
$form->addOption('r_profesores', '2', 'Si');
$form->addOption('r_profesores', '1', 'Restringido');
$form->addOption('r_profesores', '0', 'No');
$form->addSelect('r_profesores', 'Read Profesores', 'required, data-slimselect=true');

// u_profesores --
$form->addOption('u_profesores', '2', 'Si');
$form->addOption('u_profesores', '1', 'Restringido');
$form->addOption('u_profesores', '0', 'No');
$form->addSelect('u_profesores', 'Update Profesores', 'required, data-slimselect=true');

// cd_profesores --

$form->setCols(2, 10);
$form->addOption('cd_profesores', '2', 'Si');
$form->addOption('cd_profesores', '1', 'Restringido');
$form->addOption('cd_profesores', '0', 'No');
$form->addSelect('cd_profesores', 'Create/Delete Profesores', 'required, data-slimselect=true');

// cq_profesores --
$form->addInput('text', 'cq_profesores', '', 'Constraint Query Profesores<a href="#" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<p>WHERE query para limitar derechos.</p><p>Ejemplo: <br><em>, users WHERE table.users_ID = users.ID AND users.ID = CURRENT_USER_ID</em></p><p><em>CURRENT_USER_ID</em> será reemplazado automáticamente por el usuario conectado\'s ID.</p>" class="append"><span class="badge text-bg-info">?</span></a>', '');

// r_unidades --
$form->groupElements('r_unidades', 'u_unidades');

$form->setCols(2, 4);
$form->addOption('r_unidades', '2', 'Si');
$form->addOption('r_unidades', '1', 'Restringido');
$form->addOption('r_unidades', '0', 'No');
$form->addSelect('r_unidades', 'Read Unidades', 'required, data-slimselect=true');

// u_unidades --
$form->addOption('u_unidades', '2', 'Si');
$form->addOption('u_unidades', '1', 'Restringido');
$form->addOption('u_unidades', '0', 'No');
$form->addSelect('u_unidades', 'Update Unidades', 'required, data-slimselect=true');

// cd_unidades --

$form->setCols(2, 10);
$form->addOption('cd_unidades', '2', 'Si');
$form->addOption('cd_unidades', '1', 'Restringido');
$form->addOption('cd_unidades', '0', 'No');
$form->addSelect('cd_unidades', 'Create/Delete Unidades', 'required, data-slimselect=true');

// cq_unidades --
$form->addInput('text', 'cq_unidades', '', 'Constraint Query Unidades<a href="#" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="<p>WHERE query para limitar derechos.</p><p>Ejemplo: <br><em>, users WHERE table.users_ID = users.ID AND users.ID = CURRENT_USER_ID</em></p><p><em>CURRENT_USER_ID</em> será reemplazado automáticamente por el usuario conectado\'s ID.</p>" class="append"><span class="badge text-bg-info">?</span></a>', '');

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
