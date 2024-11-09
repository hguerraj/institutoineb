<?php
// phpcs:disable PSR1.Files.SideEffects
use phpformbuilder\Form;
use phpformbuilder\Validator\Validator;
use phpformbuilder\database\DB;
use fileuploader\server\FileUploader;

header("X-Robots-Tag: noindex", true);

session_start();

$_SESSION['hash'] = bin2hex(random_bytes(5));

$msg = '';
if (isset($_SESSION['uninstalled-from-generator-msg'])) {
    $msg = $_SESSION['uninstalled-from-generator-msg'];
    if (session_destroy()) {
        session_start();
    }
}

$_SESSION['msg']    = $msg;
$already_registered = false;
$has_blocking_error = false;
$just_unregistered  = false;
$detected_server = 'production';
if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1') {
    $detected_server = 'localhost';
}

/* =============================================
    Internal functions for installation
============================================= */

function registerAlertMessage($html_content, $alert_type)
{
    $_SESSION['msg'] = '<div class="alert alert-' . $alert_type . ' alert-dismissible has-icon fade show">' . $html_content . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

/**
 * get the database connection infos from connection file
 * @return array $db_info of Boolean false on failure
 * NOTE: the database constants MUST NOT be used in this file because the connection file may be rewritten.
 */
function getDbInfo()
{
    $fp = file_get_contents(CLASS_DIR . 'phpformbuilder/database/db-connect.php');
    $reg = '`define\(\'(?:PDO_DRIVER|DB_HOST|DB_NAME|DB_USER|DB_PASS|DB_PORT)\',[\s]?\'([^\']*)\'\);`';
    preg_match_all($reg, $fp, $out);
    $results = $out[1];
    if (isset($results[10])) {
        $db_info = array(
            'pdo_driver' => $results[0],
            'localhost' => array(
                'host' => $results[1],
                'name' => $results[2],
                'user' => $results[3],
                'pass' => $results[4],
                'port' => $results[5]
           ),
            'production' => array(
                'host' => $results[6],
                'name' => $results[7],
                'user' => $results[8],
                'pass' => $results[9],
                'port' => $results[10]
           )
        );

        return $db_info;
    } else {
        registerAlertMessage('<p class="mb-0">Unable to parse ' . CLASS_DIR . 'phpformbuilder/database/db-connect.php' . '<br><br>Please restore the package\'s original file then retry</p>', 'danger');

        return false;
    }
}

function getHttpResponseCode($url)
{
    $headers = get_headers($url);
    return substr($headers[0], 9, 3);
}

function registerDbInfo()
{
    $error_msg = '';
    // Register the connection settings in class/phpformbuilder/database/db-connect.php
    $db_connect_template = GENERATOR_DIR . 'generator-templates/db-connect.txt';
    $fp                  = file_get_contents($db_connect_template);
    $find                = array();
    $replace             = array();
    if ($_POST['db-target'] == 'localhost') {
        if ($_POST['localhost-db_pdo_driver'] == 'firebird') {
            array_push($find, 'localhost-db_name');
            array_push($replace, \strtoupper($_POST['localhost-db_name_firebird']));
        } else {
            array_push($find, 'localhost-db_name');
            $localhost_db_name_value = $_POST['localhost-db_name'];
            if ($_POST['localhost-db_pdo_driver'] == 'oci') {
                $localhost_db_name_value = \strtoupper($localhost_db_name_value);
            }
            array_push($replace, $localhost_db_name_value);
        }
        array_push($find, 'db_pdo_driver', 'localhost-db_host', 'localhost-db_user', 'localhost-db_pass', 'localhost-db_port');
        $localhost_db_user_value = $_POST['localhost-db_user'];
        if ($_POST['localhost-db_pdo_driver'] == 'oci') {
            $localhost_db_user_value = \strtoupper($localhost_db_user_value);
        }
        array_push($replace, $_POST['localhost-db_pdo_driver'], $_POST['localhost-db_host'], $localhost_db_user_value, $_POST['localhost-db_pass'], $_POST['localhost-db_port']);
    } elseif ($_POST['db-target'] == 'production') {
        if ($_POST['production-db_pdo_driver'] == 'firebird') {
            array_push($find, 'production-db_name');
            array_push($replace, \strtoupper($_POST['production-db_name_firebird']));
        } else {
            array_push($find, 'production-db_name');
            $production_db_name_value = $_POST['production-db_name'];
            if ($_POST['production-db_pdo_driver'] == 'oci') {
                $production_db_name_value = \strtoupper($production_db_name_value);
            }
            array_push($replace, $production_db_name_value);
        }
        array_push($find, 'db_pdo_driver', 'production-db_host', 'production-db_user', 'production-db_pass', 'production-db_port');
        $production_db_user_value = $_POST['production-db_user'];
        if ($_POST['production-db_pdo_driver'] == 'oci') {
            $production_db_user_value = \strtoupper($production_db_user_value);
        }
        array_push($replace, $_POST['production-db_pdo_driver'], $_POST['production-db_host'], $production_db_user_value, $_POST['production-db_pass'], $_POST['production-db_port']);
    }
    $db_connect_content = str_replace($find, $replace, $fp);

    if (!file_put_contents(CLASS_DIR . 'phpformbuilder/database/db-connect.php', $db_connect_content)) {
        $error_msg = 'Unable to write content in <code>' . CLASS_DIR . 'phpformbuilder/database/db-connect.php</code><br>Please check the write permissions of this folder (chmod >= 0755)';
    } else {
        // Check connection to database
        $db_info = getDbInfo();
        $server = 'production'; // default
        if (ENVIRONMENT == 'development') {
            $server = 'localhost';
        }
        $pdo_driver = $db_info['pdo_driver'];
        $db_host = $db_info[$server]['host'];
        $db_name = $db_info[$server]['name'];
        $db_user = $db_info[$server]['user'];
        $db_pass = $db_info[$server]['pass'];
        $db_port = $db_info[$server]['port'];
        $db = new DB(false, $pdo_driver, $db_host, $db_name, $db_user, $db_pass, $db_port);
        if (!$db->isConnected()) {
            $error_msg = 'Unable to connect to the database (' . utf8_encode($db->error()) . '<br>Please check your connection settings<br><hr>';
            $error_msg .= '<span style="display:inline-block;width:120px">ENVIRONMENT:</span> ' . ENVIRONMENT;
            if (ENVIRONMENT == 'development') {
                $error_msg .= ' (localhost)';
            }
            $error_msg .= '<br><span style="display:inline-block;width:120px">PDO DRIVER:</span> ' . $pdo_driver;
            $error_msg .= '<br><span style="display:inline-block;width:120px">DB HOST:</span> ' . $db_host;
            $error_msg .= '<br><span style="display:inline-block;width:120px">DB NAME:</span> ' . $db_name;
            $error_msg .= '<br><span style="display:inline-block;width:120px">DB USER:</span> ' . $db_user;
            $error_msg .= '<br><span style="display:inline-block;width:120px">DB PASS:</span> ' . $db_pass;
        } else {
            try {
                $from = PHPCG_USERDATA_TABLE;
                $columns = array('*');

                if ($db->selectRow($from, $columns) !== false) {
                    $error_msg = 'The table "' . PHPCG_USERDATA_TABLE . '" already exists.<br>You must remove it from your database, then relaunch the installation.';
                    // revert the content of db-connect.php to the default.
                    file_put_contents(CLASS_DIR . 'phpformbuilder/database/db-connect.php', $fp);
                }
            } catch (\Throwable $th) {
                // nothing here, if the select query fails it means that the PHPCG_USERDATA_TABLE doesn't exist so it's ok.
            }
        }
    }
    if (!empty($error_msg)) {
        registerAlertMessage($error_msg, 'danger');

        return false;
    }
    registerAlertMessage('Database connection successful', 'success');

    return true;
}

function revertDbInfo()
{
    $db_connect_template = GENERATOR_DIR . 'generator-templates/db-connect.txt';
    $fp                  = file_get_contents($db_connect_template);
    if (!file_put_contents(CLASS_DIR . 'phpformbuilder/database/db-connect.php', $fp)) {
        $error_msg = 'Unable to write content in <code>' . CLASS_DIR . 'phpformbuilder/database/db-connect.php</code><br>Please check the write permissions of this folder (chmod >= 0755)';
        registerAlertMessage($error_msg, 'danger');

        return false;
    }

    return true;
}

function updateConf($constant, $value, $conf_file = 'conf/user-conf.json')
{
    $user_conf = json_decode(file_get_contents(ROOT . $conf_file), true);
    $user_conf[$constant] = $value;
    $user_conf = json_encode($user_conf);
    if (!file_put_contents(ROOT . $conf_file, $user_conf)) {
        registerAlertMessage('<p>Failed to write the configuration data in ' . ROOT . $conf_file . '</p>', 'danger');

        return false;
    }

    return true;
}

function checkWritePermissions($files)
{
    $errors = [];
    foreach ($files as $filename) {
        if (file_exists($filename) && !is_writable($filename)) {
            // test only existing files/folders, as the generator may not be uploaded on production server
            $errors[] = str_replace('../', '', $filename);
        }
    }
    if (!empty($errors)) {
        return 'The following file(s)/folder(s) must be writable:<br><br>' . implode('<br>', $errors) . '<br><br>You\'ve got to increase your CHMOD.';
    }

    return true;
}

function getMimeType($file)
{
    $realpath = realpath($file);
    // Try a couple of different ways to get the mime-type of a file, in order of preference
    if ($realpath && function_exists('finfo_file') && function_exists('finfo_open') && defined('FILEINFO_MIME_TYPE')) {
        // As of PHP 5.3, this is how you get the mime-type of a file; it uses the Fileinfo
        // PECL extension
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $realpath);
    } elseif (function_exists('mime_content_type')) {
        // Before this was deprecated in PHP 5.3, this was how you got the mime-type of a file
        return mime_content_type($file);
    } else {
        // Worst-case scenario has happened, use the file extension to infer the mime-type
        $mimeTypes = array(
            'gif' => 'image/gif',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'xbm' => 'image/x-xbitmap',
        );
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (isset($mimeTypes[$ext])) {
            return $mimeTypes[$ext];
        }
    }
    return false;
}

if (!file_exists("install.lock")) {
    if (!file_exists('../conf/conf.php')) {
        $error_msg = '<p style="color: #721c24;background-color: #f8d7da;padding: .75rem 1.25rem;border-radius: .25rem;">../conf/conf.php<br><br><strong>Configuration file not found (2)</strong></p>';

        exit($error_msg);
    } else {
        // if the license table name is posted we must register it in conf/user-conf.json before loading conf/conf.php
        if ($_SERVER["REQUEST_METHOD"] == 'POST' && !isset($_POST['back-btn']) && isset($_POST['step-2-db-info']) && preg_match('`[a-z_]+`', $_POST['phpcg_userdata_table'])) {
            /* Validate the posted phpcg_userdata_table (we can't use the Validator here, not the updateConf function)
                    -------------------------------------------------- */

            $conf_file = '../conf/user-conf.json';
            $user_conf = json_decode(file_get_contents($conf_file), true);
            $user_conf['phpcg_userdata_table'] = $_POST['phpcg_userdata_table'];
            $user_conf = json_encode($user_conf);
            if (!file_put_contents($conf_file, $user_conf)) {
                $error_msg = '<p style="color: #721c24;background-color: #f8d7da;padding: .75rem 1.25rem;border-radius: .25rem;"><strong>Unable to write content in <code>../conf/user-conf.json</code><br>Please check the write permissions of this file (chmod >= 0755) or make sure that this file is present on your server.</p>';
                exit($error_msg);
            }
        }

        include_once '../conf/conf.php';
        include_once CLASS_DIR . 'phpformbuilder/Form.php';
        include_once CLASS_DIR . 'phpformbuilder/Validator/Token.php';

        if (!file_exists(CLASS_DIR . 'phpformbuilder/database/db-connect.php')) {
            $error_msg       = '<p><em style="color: crimson;">' . CLASS_DIR . 'phpformbuilder' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'db-connect.php' . '</em></p><p><strong>The database connection file has not been found on your server. Please copy it at the right place then refresh this page to run the installer.</strong></p>';
            exit($error_msg);
        }


        /* check if package is already installed
        -------------------------------------------------- */

        $db_info = getDbInfo();

        if ($db_info && !isset($_POST['step-3-global-settings'])) {
            $server = false;
            if (($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1') && $db_info['localhost']['name'] !== 'localhost-db_name') {
                $server = 'localhost';
            } elseif ($db_info['production']['name'] !== 'production-db_name') {
                $server = 'production';
            }

            if ($server) {
                $pdo_driver = $db_info['pdo_driver'];
                $db_host = $db_info[$server]['host'];
                $db_name = $db_info[$server]['name'];
                $db_user = $db_info[$server]['user'];
                $db_pass = $db_info[$server]['pass'];
                $db_port = $db_info[$server]['port'];

                $db = new DB(false, $pdo_driver, $db_host, $db_name, $db_user, $db_pass, $db_port);
                if (!$db->isConnected()) {
                    registerAlertMessage('<p class="mb-0">Unable to connect to database.<br>Please check your connection settings in <em>' . str_replace(DIRECTORY_SEPARATOR, '/', CLASS_DIR) . 'phpformbuilder/database/db-connect.php</em><br>Or restore the original file.</p>', 'danger');
                } else {
                    $tables = $db->GetTables();
                    if (is_array($tables) && in_array(PHPCG_USERDATA_TABLE, $tables)) {
                        $already_registered = true;
                    } else {
                        $has_blocking_error = true;
                        registerAlertMessage('<p class="mb-0">Table ' . PHPCG_USERDATA_TABLE . ' not found</p><p>Replace ' . str_replace(DIRECTORY_SEPARATOR, '/', CLASS_DIR) . 'phpformbuilder/database/db-connect.php original file to reset your installation</p>', 'danger');
                    }
                }
            }
        }


        if ($already_registered) {
            //

            /* =============================================
                Already registered
            ============================================= */

            if ($_SERVER["REQUEST_METHOD"] == 'POST' && isset($_POST['unregister-form'])) {
                $validator = Form::validate('unregister-form');

                if ($validator->hasErrors()) {
                    $_SESSION['errors']['unregister-form'] = $validator->getAllErrors();
                } else {
                    //

                    /* Uninstall
                    -------------------------------------------------- */

                    $purchase_verification = aplVerifyEnvatoPurchase(trim($_POST['user-purchase-code']));
                    if (!empty($purchase_verification)) { //protected script can't connect to your licensing server
                        $error_msg = 'Connection to remote server can\'t be established';
                    }
                    if (empty($error_msg)) {
                        include_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
                        $db = new DB(false);
                        if (!$db->isConnected()) {
                            $error_msg = 'Unable to connect to the database:<br>'. $db->error() . '<br>Please check your connection settings<br><hr>';
                        } else {
                            $pdo = $db->getPdo();
                            $pdo->setAttribute(
                                \PDO::ATTR_ERRMODE,
                                \PDO::ERRMODE_EXCEPTION
                            );
                            $token_notifications_array = aplUninstallToken($pdo);
                            if ($token_notifications_array['notification_case'] !== "notification_license_ok") {
                                $error_msg = 'Unfortunately, uninstaller failed because of this reason: ' . $token_notifications_array['notification_text'];
                            } else {
                                // reset db connection
                                $db_connect_template = GENERATOR_DIR . 'generator-templates/db-connect.txt';
                                $fp                  = file_get_contents($db_connect_template);
                                if (!file_put_contents(CLASS_DIR . 'phpformbuilder/database/db-connect.php', $fp)) {
                                    $error_msg = 'Unable to write content in <code>' . CLASS_DIR . 'phpformbuilder/database/db-connect.php</code><br>Please check the write permissions of this folder (chmod >= 0755)';
                                } else {
                                    // All ok - uninstall successful
                                    $just_unregistered = true;
                                    registerAlertMessage('<p>The PHP CRUD Generator license has been successfully removed.</p>', 'success');
                                }
                            }
                        }
                    }
                    if (!empty($error_msg)) {
                        registerAlertMessage($error_msg, 'danger');
                    }
                }
            }

            if (!$just_unregistered) {
                $form = new Form('unregister-form', 'horizontal', 'novalidate');
                $form->addHtml('<p class="h3 my-4 text-success-700">PHP CRUD Generator is already registered on this domain.</p>');
                $form->startFieldset('Fill-in the form below to unregister your copy');
                $form->addHtml('<p class="mb-5"></p>');
                $form->addInput('text', 'user-purchase-code', '', 'Enter your purchase code', 'class=mb-5, required');
                $form->centerContent();
                $form->addBtn('submit', 'submit-btn', 1, 'Unregister', 'class=btn btn-lg btn-warning, data-ladda-button=true, data-style=zoom-in');
            }
        } else {
            //

            /* =============================================
                Not yet registered
            ============================================= */

            $current_step = 1; // default if nothing posted
            if ($_SERVER["REQUEST_METHOD"] == 'GET' || (isset($_POST['back-btn']) && $_POST['back-btn'] == 1)) {
                //

                // Check if wr're in a subfolder & register the warning message if true
                $path = parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH);
                $subdirs = explode('/', $path);
                $first_subdir = $subdirs[1];
                $admin_htaccess_content = file_get_contents(ADMIN_DIR . '/.htaccess');
                if ($first_subdir !== 'install' && isset($subdirs[2]) && strpos($admin_htaccess_content, '/' . $first_subdir . '/admin/index.php') === false) {
                    $alert_msg = '<h4 class="text-danger">WARNING - Your <var>install</var> folder is not at the root of your project but inside the <var>' . $first_subdir . '</var> folder.</h4>';
                    $alert_msg .= '<p>If you install PHP CRUD Generator in a subfolder you must edit the <var>admin/.htaccess</var>:</p>';
                    $alert_msg .= '<ol class="mb-0">';
                    $alert_msg .= '<li>Open <var>' . $first_subdir . '/admin/.htaccess</var> in your code editor</li>';
                    $alert_msg .= '<li>Make the following change:<br><pre><code>// replace the following original code:' . "\n";
                    $alert_msg .= 'RewriteRule . /admin/index.php [QSA,L]' . "\n\n";
                    $alert_msg .= '// with this code:' . "\n";
                    $alert_msg .= 'RewriteRule . /' . $first_subdir . '/admin/index.php' . "\n";
                    $alert_msg .= '</code></pre></li></ol>';
                    registerAlertMessage($alert_msg, 'warning');
                }

                // Check if FollowSymLinks is authorized by Apache (if not, the admin assets will return an error 500)
                // and if the admin folder and files are present

                $response_code = getHttpResponseCode(BASE_URL . 'admin/assets/stylesheets/themes/default/bootstrap.min.css');
                if ($response_code === '500') {
                    registerAlertMessage('<p>The <em>FollowSymLinks</em> option is not allowed in your Apache server configuration.<br>Please go <a href="https://www.phpcrudgenerator.com/help-center#followsymlinks-not-allowed" target="_blank">here in the help center</a> to solve this issue.</p>', 'danger');
                } elseif ($response_code === '404') {
                    registerAlertMessage('<p><em>' . BASE_URL . 'admin/assets/stylesheets/themes/default/bootstrap.min.css' . '</em></p><p><strong>The file above has not been found on your server. Please copy it at the right place or check if you have any redirection that prevent its access.<br>Then refresh this page to run the installer.</strong></p>', 'danger');
                }

                /* Check server settings
                -------------------------------------------------- */

                $files_to_check = array(
                    '../admin/assets/images',
                    '../admin/class/crud',
                    '../admin/crud-data',
                    '../admin/inc/forms',
                    '../admin/secure/conf/conf.php',
                    '../admin/secure/install',
                    '../admin/templates',
                    '../class/phpformbuilder/database/db-connect.php',
                    '../class/phpformbuilder/plugins/min',
                    '../conf',
                    '../conf/conf.php',
                    '../conf/user-conf.json',
                    '../generator/backup-files/class',
                    '../generator/backup-files/crud-data',
                    '../generator/backup-files/database',
                    '../generator/backup-files/inc',
                    '../generator/backup-files/templates',
                    '../generator/database',
                    '../generator/update/cache',
                    '../generator/update/temp',
                    '../install'
                );

                $user_server = array(
                    'php_version' => array(
                        'label' => 'PHP Version',
                        'value' => phpversion(),
                        'ok'    => false,
                        'error_msg' => 'Your PHP version must be 7.4 or newer.'
                   ),
                    'file_perms' => array(
                        'label' => 'Files/folders write permissions',
                        'value' => '',
                        'ok'    => false,
                        'error_msg' => ''
                   ),
                    'allow_url_fopen' => array(
                        'label' => 'PHP allow_url_fopen',
                        'value' => '',
                        'ok'    => false,
                        'error_msg' => 'PHP allow_url_fopen is disabled in your php.ini and is required by PHP CRUD Generator.<br>Open your php.ini and turn on the allow_url_fopen directive.'
                   ),
                    'pdo_extension' => array(
                        'label' => 'PHP PDO extension',
                        'value' => '',
                        'ok'    => false,
                        'error_msg' => 'PHP PDO extension is required and is not installed/enabled on your server.'
                   ),
                    'curl_extension' => array(
                        'label' => 'PHP CURL extension',
                        'value' => '',
                        'ok'    => false,
                        'error_msg' => 'PHP CURL extension is required and is not installed/enabled on your server.'
                   ),
                    'dom_extension' => array(
                        'label' => 'PHP dom extension',
                        'value' => '',
                        'ok'    => false,
                        'error_msg' => 'PHP DOM extension is required and is not installed/enabled on your server.'
                   ),
                    'gd_extension' => array(
                        'label' => 'PHP GD extension',
                        'value' => '',
                        'ok'    => 'warning',
                        'error_msg' => 'PHP GD extension is required for images upload and is not enabled on your server.'
                   ),
                    'mb_string_extension' => array(
                        'label' => 'PHP mb_string extension',
                        'value' => '',
                        'ok'    => false,
                        'error_msg' => 'PHP mb_string extension is required and is not installed/enabled on your server.'
                   ),
                    'zip_extension' => array(
                        'label' => 'PHP ZIP extension',
                        'value' => '',
                        'ok'    => 'warning',
                        'error_msg' => 'PHP ZIP extension is required for auto-update system and is not enabled on your server.<br>You will not be able to install the future PHPCG versions with the auto-update system.'
                   ),
                    'intl_extension' => array(
                        'label' => 'PHP intl extension',
                        'value' => '',
                        'ok'    => 'warning',
                        'error_msg' => 'PHP intl extension is suitable but not required.<br>Intl extension is used for automatic date &amp; time translation in the admin panel READ lists.<br>If not present, dates &amp; times will be in English.'
                   )
                );

                // PHP Version
                if (version_compare($user_server['php_version']['value'], '7.4', '>=')) {
                    $user_server['php_version']['ok'] = true;
                }

                // Write permissions
                $permissions = checkWritePermissions($files_to_check);
                if ($permissions) {
                    $user_server['file_perms']['ok'] = true;
                } else {
                    $user_server['file_perms']['error_msg'] = $permissions;
                }

                if (ini_get('allow_url_fopen')) {
                    $user_server['allow_url_fopen']['ok'] = true;
                }

                $php_extensions = get_loaded_extensions();

                // PHP GD extension
                if (in_array('PDO', $php_extensions)) {
                    $user_server['pdo_extension']['ok'] = true;
                }

                // PHP CURL extension
                if (in_array('curl', $php_extensions)) {
                    $user_server['curl_extension']['ok'] = true;
                }

                // PHP DOM extension
                if (in_array('dom', $php_extensions)) {
                    $user_server['dom_extension']['ok'] = true;
                }

                // PHP GD extension
                if (in_array('gd', $php_extensions)) {
                    $user_server['gd_extension']['ok'] = true;
                }

                // PHP mb_string extension
                if (in_array('mbstring', $php_extensions)) {
                    $user_server['mb_string_extension']['ok'] = true;
                }

                // PHP ZIP extension
                if (in_array('zip', $php_extensions)) {
                    $user_server['zip_extension']['ok'] = true;
                }

                // PHP intl extension
                if (in_array('intl', $php_extensions)) {
                    $user_server['intl_extension']['ok'] = true;
                }
            } elseif ($_SERVER["REQUEST_METHOD"] == 'POST') {
                //

                // default step for POST
                $current_step = 2;
                if (isset($_POST['back-btn'])) {
                    $current_step = $_POST['back-btn'];
                    // If back from step 3 we delete the USER_TABLE and revert the db-connect.php to the original version
                    include_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
                    $db = new DB(true);
                    $sql = 'DROP TABLE ' . PHPCG_USERDATA_TABLE;
                    $db->execute($sql);
                    revertDbInfo();
                } elseif (isset($_POST['step-2-db-info'])) {
                    //

                    /* Validate step 2
                    -------------------------------------------------- */

                    $validator = Form::validate('step-2-db-info');

                    // additional validation
                    $validator->email()->validate('user-email');
                    if (!preg_match('`^[a-z_]+$`', $_POST['phpcg_userdata_table'])) {
                        $validator->maxLength(-1, 'Your license table name contains some invalid characters. Only lowercase alphabetic characters and underscores are allowed.')->validate('phpcg_userdata_table');
                    }

                    if ($validator->hasErrors()) {
                        $current_step = 2;
                        $_SESSION['errors']['step-2-db-info'] = $validator->getAllErrors();
                    } else {
                        //

                        /* Register DB connection(s)
                        -------------------------------------------------- */

                        if (registerDbInfo()) {
                            $current_step = 3;
                        }
                    }
                } elseif (isset($_POST['step-3-global-settings'])) {
                    //

                    /* Validate step 3
                    -------------------------------------------------- */

                    $current_step = 3;

                    $validator = Form::validate('step-3-global-settings');

                    if ($validator->hasErrors()) {
                        $_SESSION['errors']['step-3-global-settings'] = $validator->getAllErrors();
                    } else {
                        //

                        /* Register CONF
                        -------------------------------------------------- */

                        $has_conf_error = false;
                        $has_db_error   = false;
                        if (!updateConf('sitename', $_POST['user-sitename'])) {
                            $has_conf_error = true;
                        }
                        if (!updateConf('lang', $_POST['user-language'])) {
                            $has_conf_error = true;
                        }
                        if (isset($_POST['user-logo']) && !empty($_POST['user-logo'])) {
                            include_once CLASS_DIR . 'phpformbuilder/plugins/fileuploader/server/class.fileuploader.php';

                            $posted_img = FileUploader::getPostedFiles($_POST['user-logo']);
                            if (!updateConf('admin_logo', $posted_img[0]['file'])) {
                                $has_conf_error = true;
                            }
                        }
                        if (!$has_conf_error) {
                            // create lock file
                            if (!$has_db_error && file_put_contents('install.lock', '') === false) {
                                registerAlertMessage('<p>Unable to write the <var>install.lock</var> file</p>', 'danger');
                            }

                            $current_step = 4;
                        }
                    }
                }
            } // End if ($_SERVER["REQUEST_METHOD"] == 'POST')

            if ($current_step == 2) {
                //

                /* ==================================================
                    Step 2
                ================================================== */

                /* Get db connection infos from phpformbuilder/database/db-connect.php
                -------------------------------------------------- */

                if ($db_info) {
                    $form = new Form('step-2-db-info', 'horizontal', 'novalidate');
                    $val = $detected_server; // localhost | production
                    if (isset($_POST['db-target']) && ($_POST['db-target'] == 'localhost' || $_POST['db-target'] == 'production')) {
                        $val = $_POST['db-target'];
                    }
                    $form->addInput('hidden', 'db-target', $val);
                    $server_txt = str_replace('production', 'production server', $val);
                    $form->addHtml('<label class="control-label mb-3">Choose your installation server (<em>' . ucfirst($server_txt) . '</em> detected)</label>');
                    // choose-action-radio card text-decoration-none h-100 text-bg-primary-500 active
                    $form->addHtml('<div class="row row-cols-1 row-cols-md-2 g-4 mb-3">
                        <div class="col">
                            <a href="#" class="choose-db-target-radio card text-decoration-none h-100 text-bg-primary-500" id="localhost">
                                <div class="card-body d-flex flex-column justify-content-center">
                                    <p class="card-title h6 text-center my-4"><span class="rounded-circle text-bg-primary-200"><i class="' . ICON_CHECKMARK . '"></i></span>Localhost</p>
                                </div>
                            </a>
                        </div>
                            <div class="col">
                            <a href="#" class="choose-db-target-radio card text-bg-secondary-700 text-decoration-none h-100" id="production">
                                <div class="card-body d-flex flex-column justify-content-center">
                                    <p class="card-title h6 text-center my-4"><span class="rounded-circle text-bg-primary-200"><i class="' . ICON_CHECKMARK . '"></i></span>Production Server</p>
                                </div>
                            </a>
                        </div>
                    </div>');

                    $db_pdo_driver = str_replace('db_pdo_driver', 'mysql', $db_info['pdo_driver']);

                    $localhost_host = str_replace('localhost-db_host', '', $db_info['localhost']['host']);
                    $localhost_name = str_replace('localhost-db_name', '', $db_info['localhost']['name']);
                    $localhost_user = str_replace('localhost-db_user', '', $db_info['localhost']['user']);
                    $localhost_pass = str_replace('localhost-db_pass', '', $db_info['localhost']['pass']);
                    $localhost_port = str_replace('localhost-db_port', '', $db_info['localhost']['port']);

                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step-2-db-info']) && $_POST['db-target'] === 'localhost') {
                        $db_pdo_driver = $_POST['localhost-db_pdo_driver'];
                        $localhost_host = $_POST['localhost-db_host'];
                        if ($db_pdo_driver == 'firebird') {
                            $localhost_name = $_POST['localhost-db_name_firebird'];
                        } else {
                            $localhost_name = $_POST['localhost-db_name'];
                        }
                        $localhost_user = $_POST['localhost-db_user'];
                        $localhost_pass = $_POST['localhost-db_pass'];
                        $localhost_port = $_POST['localhost-db_port'];
                    }

                    $form->startDependentFields('db-target', 'localhost');

                    $form->startFieldset('Database PDO driver', 'class=mb-4');
                    $pdo_drivers = \PDO::getAvailableDrivers();
                    foreach ($pdo_drivers as $driver) {
                        $selected = '';
                        if ($driver === $db_pdo_driver) {
                            $selected = 'selected';
                        }
                        $form->addOption('localhost-db_pdo_driver', $driver, $driver, '', $selected);
                    }
                    $form->addSelect('localhost-db_pdo_driver', 'Select your database driver', 'data-slimselect=true, data-allow-deselect=false, data-show-search=false,required');
                    $form->endFieldset();

                    $form->startFieldset('Database Localhost connection', 'class=mb-4');
                    $form->addHelper('e.g: localhost, or 127.0.0.1', 'localhost-db_host');
                    $form->addInput('text', 'localhost-db_host', $localhost_host, 'Host', 'required');

                    // all databases except firebird
                    $form->startDependentFields('localhost-db_pdo_driver', 'firebird', true);
                    $form->addHelper('The name of your database', 'localhost-db_name');
                    $form->addInput('text', 'localhost-db_name', $localhost_name, 'Database', 'required');
                    $form->endDependentFields();

                    // firebird database
                    $form->startDependentFields('localhost-db_pdo_driver', 'firebird');
                    $form->addHelper('The complete path leading to your firebird database.fdb file.<br>e.g: /path/to/database.fdb', 'localhost-db_name_firebird');
                    $form->addInput('text', 'localhost-db_name_firebird', $localhost_name, 'Database path', 'required');
                    $form->endDependentFields();

                    $form->addHelper('Leave blank to use the default port', 'localhost-db_port');
                    $form->addInput('text', 'localhost-db_port', $localhost_port, 'Port', '');
                    $form->addInput('text', 'localhost-db_user', $localhost_user, 'User', 'required');
                    $form->addInput('password', 'localhost-db_pass', $localhost_pass, 'Password', 'required');
                    $options = array(
                            'horizontalOffsetCol'      => '',
                            'horizontalElementCol'     => 'col-12',
                    );
                    $form->setOptions($options);
                    $form->addHtml('<div id="localhost-connection-test-output"></div>');
                    $form->addBtn('button', 'test-connection-localhost', 1, 'Test connection<i class="fa-solid fa-plug ms-2"></i>', 'class=btn btn-primary ms-auto d-block test-connection-btn, data-target=localhost');
                    $options = array(
                        'horizontalLabelCol'       => 'col-sm-4',
                        'horizontalOffsetCol'      => 'col-sm-offset-4',
                        'horizontalElementCol'     => 'col-sm-8',
                    );
                    $form->setOptions($options);
                    $form->endFieldset();
                    $form->endDependentFields();

                    $production_host = str_replace('production-db_host', '', $db_info['production']['host']);
                    $production_name = str_replace('production-db_name', '', $db_info['production']['name']);
                    $production_user = str_replace('production-db_user', '', $db_info['production']['user']);
                    $production_pass = str_replace('production-db_pass', '', $db_info['production']['pass']);
                    $production_port = str_replace('production-db_port', '', $db_info['production']['port']);

                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step-2-db-info']) && $_POST['db-target'] === 'production') {
                        $db_pdo_driver = $_POST['production-db_pdo_driver'];
                        $production_host = $_POST['production-db_host'];
                        if ($db_pdo_driver == 'firebird') {
                            $production_name = $_POST['production-db_name_firebird'];
                        } else {
                            $production_name = $_POST['production-db_name'];
                        }
                        $production_user = $_POST['production-db_user'];
                        $production_pass = $_POST['production-db_pass'];
                        $production_port = $_POST['production-db_port'];
                    }

                    $form->startDependentFields('db-target', 'production');

                    $form->startFieldset('Database PDO driver', 'class=mb-4');
                    $pdo_drivers = \PDO::getAvailableDrivers();
                    foreach ($pdo_drivers as $driver) {
                        $selected = '';
                        if ($driver === $db_pdo_driver) {
                            $selected = 'selected';
                        }
                        $form->addOption('production-db_pdo_driver', $driver, $driver, '', $selected);
                    }
                    $form->addSelect('production-db_pdo_driver', 'Select your database driver', 'data-slimselect=true, data-allow-deselect=false, data-show-search=false,required');
                    $form->endFieldset();

                    $form->startFieldset('Database Production server connection', 'class=mb-4');
                    $form->addHelper('e.g: localhost, or IP address of the server', 'production-db_host');
                    $form->addInput('text', 'production-db_host', $production_host, 'Host', 'required');

                    // all databases except firebird
                    $form->startDependentFields('production-db_pdo_driver', 'firebird', true);
                    $form->addHelper('The name of your database', 'production-db_name');
                    $form->addInput('text', 'production-db_name', $production_name, 'Database', 'required');
                    $form->endDependentFields();

                    // firebird database
                    $form->startDependentFields('production-db_pdo_driver', 'firebird');
                    $form->addHelper('The complete path leading to your firebird database.fdb file.<br>e.g: /path/to/database.fdb', 'production-db_name_firebird');
                    $form->addInput('text', 'production-db_name_firebird', $production_name, 'Database path', 'required');
                    $form->endDependentFields();

                    $form->addHelper('Leave blank to use the default port', 'production-db_port');
                    $form->addInput('text', 'production-db_port', $production_port, 'Port', '');
                    $form->addInput('text', 'production-db_user', $production_user, 'User', 'required');
                    $form->addInput('password', 'production-db_pass', $production_pass, 'Password', 'required');
                    $options = array(
                            'horizontalOffsetCol'      => '',
                            'horizontalElementCol'     => 'col-12',
                    );
                    $form->setOptions($options);
                    $form->addHtml('<div id="production-connection-test-output"></div>');
                    $form->addBtn('button', 'test-connection-production', 1, 'Test connection <i class="fa-solid fa-plug ms-2"></i>', 'class=btn btn-primary ms-auto d-block test-connection-btn, data-target=production');
                    $options = array(
                        'horizontalLabelCol'       => 'col-sm-4',
                        'horizontalOffsetCol'      => 'col-sm-offset-4',
                        'horizontalElementCol'     => 'col-sm-8',
                    );
                    $form->setOptions($options);
                    $form->endFieldset();
                    $form->endDependentFields();

                    $form->startFieldset('User Info', 'class=mb-4');
                    $form->addInput('email', 'user-email', '', 'Your email', 'required');
                    $form->addHelper('<a href="https://market.mrcode.ir" target="_blank" style="color:red;">MrCode Market</a>', 'user-purchase-code');
                    $form->addInput('text', 'user-purchase-code', '', 'Enter RANDOM license', 'required');
                    $user_data_table_value = 'user_data';
                    if (isset($_POST['phpcg_userdata_table'])) {
                        $user_data_table_value = $_POST['phpcg_userdata_table'];
                    }
                    $form->addHelper('This table will be created in your database. This table is used internally to check your license.<br>There\'s no need to change the default name, except if you prefer to add a prefix or already have a table with the same name.<br>Only lowercase characters and underscores are accepted.', 'phpcg_userdata_table');
                    $form->addInput('text', 'phpcg_userdata_table', $user_data_table_value, 'Name of the license data table', 'required');
                    $form->endFieldset();

                    $form->centerContent();
                    $form->setCols(-1, -1);
                    $form->addBtn('submit', 'back-btn', 1, '<i class="' . ICON_ARROW_LEFT . ' prepend"></i> Back', 'class=btn btn-lg btn-warning', 'btns');
                    $form->addBtn('submit', 'submit-btn', 2, 'Next <i class="' . ICON_ARROW_RIGHT . ' append" aria-hidden="true"></i>', 'class=btn btn-lg btn-primary, data-ladda-button=true, data-style=zoom-in', 'btns');
                    $form->printBtnGroup('btns');
                }
            } elseif ($current_step == 3) {
                //

                /* ==================================================
                    Step 3
                ================================================== */

                // get the db info to display db name
                $db_info = getDbInfo();
                $form = new Form('step-3-global-settings', 'horizontal', 'novalidate');
                $form->startFieldset('Global Settings', 'class=mb-4');
                $form->addHelper('Enter the sitename to be displayed in your admin panel', 'sitename');
                $form->addInput('text', 'user-sitename', 'PHP CRUD GENERATOR', 'Your project name', 'required');

                if (!isset($_SESSION['step-3-global-settings']['user-language'])) {
                    $_SESSION['step-3-global-settings']['user-language'] = 'en';
                }

                $json = file_get_contents(GENERATOR_DIR . 'inc/languages.json');
                $countries = json_decode($json);

                foreach ($countries as $c) {
                    $form->addOption('user-language', $c->code, $c->name);
                }
                $form->addHtml('<div id="select-language-callback"></div>', 'user-language', 'after');
                $form->addSelect('user-language', 'Admin panel language', 'class=select2, required');

                $current_file = ''; // default empty
                $current_file_name = 'logo-height-100.png'; // default PHPCG logo

                $current_file_path = ADMIN_DIR . 'assets/images/';
                if (isset($_POST['user-logo'])) {
                    $current_file_name = $_POST['user-logo'];
                }

                if (file_exists($current_file_path . $current_file_name)) {
                    $current_file_size = filesize($current_file_path . $current_file_name);
                    $current_file_type = getMimeType($current_file_path . $current_file_name);
                    $current_file = array(
                        'name' => $current_file_name,
                        'size' => $current_file_size,
                        'type' => $current_file_type,
                        'file' => ADMIN_URL . 'assets/images/' . $current_file_name, // url of the file
                        'data' => array(
                            'listProps' => array(
                            'file' => $current_file_name
                           )
                       )
                    );
                }
                $fileUpload_config = array(
                    'xml'           => 'image-upload', // the thumbs directories must exist
                    'uploader'      => 'ajax_upload_file.php', // the uploader file in phpformbuilder/plugins/fileuploader/[xml]/php
                    'upload_dir'    => '../../../../../../admin/assets/images/', // the directory to upload the files. relative to [plugins dir]/fileuploader/image-upload/php/ajax_upload_file.php
                    'limit'         => 1, // max. number of files
                    'file_max_size' => 2, // each file's maximal size in MB {null, Number}
                    'extensions'    => ['jpg', 'jpeg', 'png'],
                    'thumbnails'    => false,
                    'editor'        => false,
                    'width'         => 200,
                    'height'        => 100,
                    'crop'          => false,
                    'debug'         => true
                );

                $form->addHelper('Image will be resized to max. width 200px, max. height 100px.<br>Accepted File Types : Accepted File Types : .jp[e]g, .png, .gif', 'user-logo', 'after');
                $form->addFileUpload('user-logo', '', 'Your image/logo', '', $fileUpload_config, $current_file);

                $form->centerContent();
                $form->setCols(-1, -1);
                $form->addBtn('submit', 'back-btn', 2, '<i class="' . ICON_ARROW_LEFT . ' prepend"></i> Back', 'class=btn btn-lg btn-warning', 'btns');
                $form->addBtn('submit', 'submit-btn', 3, 'Next <i class="' . ICON_ARROW_RIGHT . ' append" aria-hidden="true"></i>', 'class=btn btn-lg btn-primary, data-ladda-button=true, data-style=zoom-in', 'btns');
                $form->printBtnGroup('btns');
                $form->addHtml('<p id="wait-msg" class="text-danger text-center d-none">Please wait until the task ends...</p>');
                $form->endFieldset();

                $form->addPlugin('nice-check', 'form', 'default', ['%skin%' => 'blue']);
            }
        } // End Not yet registered
    }
} else {
    exit('Installer is locked. Delete the .lock file from the install folder to allow the access.');
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex" />
    <title>PHP CRUD Generator Installer</title>
    <meta name="description" content="">
    <link rel="preload" href="<?php echo ADMIN_URL; ?>assets/stylesheets/pace-theme-minimal.min.css" as="style" onload="this.rel='stylesheet'">
    <link rel="preload" href="<?php echo ADMIN_URL; ?>assets/stylesheets/themes/default/bootstrap.min.css" as="style" onload="this.rel='stylesheet'">
    <link rel="preload" href="<?php echo GENERATOR_URL; ?>generator-assets/stylesheets/generator.min.css" as="style" onload="this.rel='stylesheet'">
    <noscript>
        <link type="text/css" media="screen" rel="stylesheet" href="<?php echo ADMIN_URL; ?>assets/stylesheets/pace-theme-minimal.min.css">
        <link type="text/css" media="screen" rel="stylesheet" href="<?php echo ADMIN_URL; ?>assets/stylesheets/themes/default/bootstrap.min.css">
        <link type="text/css" media="screen" rel="stylesheet" href="<?php echo GENERATOR_URL; ?>generator-assets/stylesheets/generator.min.css">
    </noscript>
    <script> (function(w){ "use strict"; if (!w.loadCSS){ w.loadCSS=function(){}} var rp=loadCSS.relpreload={}; rp.support=(function(){ var ret; try{ ret=w.document.createElement("link").relList.supports("preload")} catch (e){ ret=!1} return function(){ return ret}})(); rp.bindMediaToggle=function(link){ var finalMedia=link.media || "all"; function enableStylesheet(){ link.media=finalMedia} if (link.addEventListener){ link.addEventListener("load", enableStylesheet)} else if (link.attachEvent){ link.attachEvent("onload", enableStylesheet)} setTimeout(function(){ link.rel="stylesheet"; link.media="only x"}); setTimeout(enableStylesheet, 3000)}; rp.poly=function(){ if (rp.support()){ return} var links=w.document.getElementsByTagName("link"); for (var i=0; i < links.length; i++){ var link=links[i]; if (link.rel==="preload" && link.getAttribute("as")==="style" && !link.getAttribute("data-loadcss")){ link.setAttribute("data-loadcss", !0); rp.bindMediaToggle(link)}}}; if (!rp.support()){ rp.poly(); var run=w.setInterval(rp.poly, 500); if (w.addEventListener){ w.addEventListener("load", function(){ rp.poly(); w.clearInterval(run)})} else if (w.attachEvent){ w.attachEvent("onload", function(){ rp.poly(); w.clearInterval(run)})}} if (typeof exports !=="undefined"){ exports.loadCSS=loadCSS} else{ w.loadCSS=loadCSS}}(typeof global !=="undefined" ? global : this)) </script>
    <?php
    if (isset($form)) {
        $form->printIncludes('css', false, true, false);
    }
    ?>
    <style type="text/css">
        .bs-wizard {margin-top: 40px;}

        /* step Wizard - https://codepen.io/migli/pen/JBYVJB */

        .bs-wizard > .bs-wizard-step {padding: 0; position: relative;}
        .bs-wizard > .bs-wizard-step .bs-wizard-stepnum {color: #595959; font-size: 16px; margin-bottom: 5px;}
        .bs-wizard > .bs-wizard-step .bs-wizard-info {color: #999; font-size: 14px;}
        .bs-wizard > .bs-wizard-step > .bs-wizard-dot {position: absolute; width: 30px; height: 30px; display: block; background: #3f51b5; top: 47px; left: 50%; margin-top: -15px; margin-left: -15px; border-radius: 50%;}
        .bs-wizard > .bs-wizard-step > .bs-wizard-dot:after {content: ' '; width: 14px; height: 14px; background: #ABB6F5; border-radius: 50px; position: absolute; top: 8px; left: 8px; }
        .bs-wizard > .bs-wizard-step > .progress {position: relative; border-radius: 0px; height: 8px; box-shadow: none; margin: 20px 0;}
        .bs-wizard > .bs-wizard-step > .progress > .progress-bar {width:0px; height: 8px; line-height: 20px; box-shadow: none; background: #3f51b5;}
        .bs-wizard > .bs-wizard-step.complete > .progress > .progress-bar {width:100%;}
        .bs-wizard > .bs-wizard-step.active > .progress > .progress-bar {width:50%;}
        .bs-wizard > .bs-wizard-step:first-child.active > .progress > .progress-bar {width:0%;}
        .bs-wizard > .bs-wizard-step:last-child.active > .progress > .progress-bar {width: 100%;}
        .bs-wizard > .bs-wizard-step.disabled > .bs-wizard-dot {background-color: #f5f5f5;}
        .bs-wizard > .bs-wizard-step.disabled > .bs-wizard-dot:after {opacity: 0;}
        .bs-wizard > .bs-wizard-step:first-child  > .progress {left: 50%; width: 50%;}
        .bs-wizard > .bs-wizard-step:last-child  > .progress {width: 50%;}
        .bs-wizard > .bs-wizard-step.disabled a.bs-wizard-dot{ pointer-events: none; }
        .bs-wizard .progress { overflow: hidden; background-color: #f5f5f5; }
    </style>
</head>

<body>
    <header class="align-items-center d-flex justify-content-between p-3 mb-5 text-bg-secondary-800">
        <h1 class="h5 text-start mb-0 opacity-50"><i class="fas fa-wrench fa-lg me-3"></i>PHP CRUD Generator installer | Activated <a href="https://market.mrcode.ir" target="_blank" style="color:red;">MrCode Market</a></h1>
    </header>
    <div class="container">

        <?php
        if (isset($_SESSION['msg'])) {
            echo $_SESSION['msg'];
            unset($_SESSION['msg']);

            // if url has uppercase characters
            if (preg_match('`[A-Z]`', $_SERVER['REQUEST_URI'])) {
                echo '<div class="alert alert-danger alert-dismissible has-icon fade show"><p>Your url has some uppercase characters.</p><p class="mb-0">You should rename your files / folders without uppercase letters to avoid the display problems you are currently experiencing and any future issues..</p><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }
        if (isset($current_step)) {
            $class_step = array();
            $class_step[1] = 'active';
            $class_step[2] = 'disabled';
            $class_step[3] = 'disabled';
            $class_step[4] = 'disabled';

            if ($current_step > 1) {
                $class_step[1] = 'complete';
            }
            if ($current_step > 2) {
                $class_step[2] = 'complete';
            }
            if ($current_step > 3) {
                $class_step[3] = 'complete';
            }
            $class_step[$current_step] = 'active';
            ?>
        <div class="row bs-wizard mb-4">

            <div class="col-3 bs-wizard-step <?php echo $class_step[1]; ?>">
                <div class="text-center bs-wizard-stepnum">Step 1</div>
                <div class="progress">
                    <div class="progress-bar"></div>
                </div>
                <a href="#" class="bs-wizard-dot"></a>
                <div class="bs-wizard-info text-center">Server compatibility</div>
            </div>

            <div class="col-3 bs-wizard-step <?php echo $class_step[2]; ?>">
                <div class="text-center bs-wizard-stepnum">Step 2</div>
                <div class="progress">
                    <div class="progress-bar"></div>
                </div>
                <a href="#" class="bs-wizard-dot"></a>
                <div class="bs-wizard-info text-center">Database & Registration</div>
            </div>

            <div class="col-3 bs-wizard-step <?php echo $class_step[3]; ?>">
                <div class="text-center bs-wizard-stepnum">Step 3</div>
                <div class="progress">
                    <div class="progress-bar"></div>
                </div>
                <a href="#" class="bs-wizard-dot"></a>
                <div class="bs-wizard-info text-center">Custom settings</div>
            </div>

            <div class="col-3 bs-wizard-step <?php echo $class_step[4]; ?>">
                <div class="text-center bs-wizard-stepnum">Step 4</div>
                <div class="progress">
                    <div class="progress-bar"></div>
                </div>
                <a href="#" class="bs-wizard-dot"></a>
                <div class="bs-wizard-info text-center">Done</div>
            </div>
        </div>
            <?php
        }
        ?>
        <div class="row justify-content-md-center">
            <div class="col-md-11 col-lg-10 mb-4">
                <div class="card card-default">
                    <div class="card-header">
                        <?php
                        if (!isset($current_step) && ($already_registered || $just_unregistered)) {
                            ?>
                        PHPCG Installation - Unregister
                            <?php
                        } else {
                            ?>
                        PHPCG Installation - Step <?php echo $current_step ?> / 4
                        <div class="heading-elements">
                            <span class="badge bg-dark-400">Step <?php echo $current_step ?> / 4</span>
                        </div>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($form)) {
                            $form->render();
                        } elseif ($just_unregistered) {
                            ?>
                        <p class="lead text-center my-5">You can now reinstall on this server or any other.</p>
                        <p class="lead text-center my-5"><a href="do-install.php" class="btn btn-lg btn-primary">Reload to open the installer<i class="<?php echo ICON_REFRESH; ?> ms-2"></i></a></p>
                            <?php
                        } elseif ($current_step == 1) {
                            ?>
                        <h3 class="text-center mb-5">Server settings</h3>
                        <table class="table table-striped mb-5">
                            <tbody>
                                <?php
                                foreach ($user_server as $key => $array_values) {
                                    $value_ok = '<i class="' . ICON_DELETE . ' text-danger mx-2"></i>';
                                    $value_error_msg = $array_values['error_msg'];
                                    if ($array_values['ok']) {
                                        $value_ok = '<i class="' . ICON_CHECKMARK . ' text-success mx-2"></i>';
                                        $value_error_msg = '';
                                    } elseif ($array_values['ok'] == 'warning') {
                                        $value_ok = '<i class="' . ICON_DELETE . ' text-warning mx-2"></i>';
                                    } else {
                                        $has_blocking_error = true;
                                    }
                                    ?>
                                    <tr>
                                        <th scope="row"><?php echo $array_values['label']; ?></th>
                                        <td><?php echo $array_values['value']; ?></td>
                                        <td><?php echo $value_ok . $value_error_msg; ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>

                        <h3 class="text-center mb-5">Demo database (<em>Sakila</em> database)<br><small class="text-secondary">(You can skip this information if you do not intend to install the demo database)</small></h3>

                        <p>The <a href="https://dev.mysql.com/doc/sakila/en/sakila-structure.html" target="_blank" rel="noopener"><strong>Sakila database</strong></a> is available for each Relational Database Management System (RDBMS) in the <code>install/demo-database/</code> directory.</p>
                        <p>The <strong>Sakila database</strong> is a demonstration database, using a complete and extensive schema.</p>
                        <p>If you want to learn about PHP CRUD Generator before using it with your own database:</p>
                        <ol>
                            <li class="mb-1">Install the <em>Sakila</em> database using the provided SQL files.</li>
                            <li class="mb-1">Then restart the installation of PHP CRUD Generator, and enter the details of your <em>Sakila</em> database in step 2.</li>
                            <li class="mb-1">Once installed, run the CRUD Generator, test and generate the different parts of the admin dashboard.</li>
                            <li class="mb-1">When you want to reinstall to work on your own database, simply click on the "Reset" button of the CRUD Generator.This will delete all files related to the <em>Sakila</em> database, and start a new blank installation.</li>
                        </ol>
                        <p class="mb-5"><a href=""></a></p>

                            <?php
                            $disabled = '';
                            if ($has_blocking_error) {
                                $disabled = ' disabled';
                            }
                            ?>
                        <form action="do-install.php" method="POST">
                            <div class="text-center my-5">
                                <?php
                                if ($disabled) {
                                    ?>
                                    <p class="text-danger pb-2 mb-4">You must solve the issue(s) before proceeding with the installation.</p>
                                    <?php
                                }
                                ?>
                                <button type="submit" class="btn btn-lg btn-primary" value="1"<?php echo $disabled; ?>>Next <i class="<?php echo ICON_ARROW_RIGHT; ?> append" aria-hidden="true"></i></button>
                            </div>
                        </form>
                            <?php
                        } elseif ($current_step == 4) {
                            ?>
                        <h3 class="text-center mb-5"><strong>PHPCG</strong> Installation Successful</h3>
                        <p class="lead text-center"><strong>If for some reason you have someday to uninstall/reinstall:</strong></p>
                        <div class="d-flex justify-content-center">
                            <ol class="text-left">
                                <li>Delete <code>install/install.lock</code></li>
                                <li>Open <code>install/do-install.php</code> to unregister your license</li>
                                <li>Refresh your page to reinstall</li>
                            </ol>
                        </div>
                        <p class="lead text-center my-5"><a href="<?php echo GENERATOR_URL ?>generator.php" class="btn btn-lg btn-primary" target="_blank" rel="noopener noreferrer">Open the CRUD Generator Now<i class="<?php echo ICON_NEW_TAB; ?> ms-2"></i></a></p>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?php echo ADMIN_URL; ?>assets/javascripts/jquery-3.5.1.min.js"></script>
    <script src="<?php echo ADMIN_URL; ?>assets/javascripts/bootstrap/dist/bootstrap-bundle.min.js"></script>
    <script async defer src="<?php echo ADMIN_URL; ?>assets/javascripts/plugins/pace.min.js"></script>
    <?php
    if (isset($form)) {
        $form->printIncludes('js', false, true, false);
        $form->printJsCode();
    }
    ?>
    <script>
        $(document).ready(function() {
            if ($('input[name="db-target"]')[0]) {
                // if step form
                var $dbInput      = $('input[name="db-target"]'),
                    dbTargetValue = $dbInput.val();

                if(dbTargetValue == "undefined") {
                    dbTargetValue = 'localhost';
                    $dbInput.val(dbTargetValue);
                }

                $('.choose-db-target-radio').on('click', function() {
                    dbTargetValue = $(this).attr('id');
                    $dbInput.val(dbTargetValue);
                    const evt = new Event('change', { bubbles: true });
                    document.querySelector('input[name="db-target"]').dispatchEvent(evt);
                    $('.choose-db-target-radio').addClass('text-bg-secondary-700').removeClass('text-bg-primary-500 active');
                    $('#' + dbTargetValue).removeClass('text-bg-secondary-700').addClass('text-bg-primary-500 active');
                });

                $('#' + dbTargetValue).trigger('click');

                $('select[name="user-language"]').on('change', function() {
                    var target = $('#select-language-callback'),
                        selectedLanguage = $(this).val();
                    $.ajax({
                        url: 'select-language-callback.php',
                        type: 'POST',
                        data: {
                            'language': selectedLanguage
                        }
                    }).done(function(data) {
                        target.html(data);
                    }).fail(function(data, statut, error) {
                        console.log(error);
                    });
                });
            }

            // Test database connection
            if ($('.test-connection-btn')[0]) {
                $('.test-connection-btn').on('click', function() {
                    const serverTarget = $(this).attr('data-target');
                    const $target = $('#' + serverTarget + '-connection-test-output');
                    const pdoDriver = $('#' + serverTarget + '-db_pdo_driver option:selected').text();
                    const dbHost = $('#' + serverTarget + '-db_host').val();
                    let dbName = $('#' + serverTarget + '-db_name').val();
                    if (pdoDriver === 'firebird') {
                        dbName = $('#' + serverTarget + '-db_name_firebird').val();
                    }
                    const dbPort = $('#' + serverTarget + '-db_port').val();
                    const dbUser = $('#' + serverTarget + '-db_user').val();
                    const dbPass = $('#' + serverTarget + '-db_pass').val();
                    if (!pdoDriver) {
                        alert('You must first select a PDO driver');
                    } else if (!dbHost) {
                        alert('You must first enter your database host');
                    } else if (!dbName) {
                        alert('You must first enter your database name');
                    } else if (!dbUser) {
                        alert('You must first enter your database user name');
                    } else if (!dbPass) {
                        alert('You must first enter your database password');
                    } else {
                        // ok
                        $.ajax({
                            url: 'ajax/ajax-db-connection-test.php',
                            type: 'POST',
                            data: {
                                'db_driver': pdoDriver,
                                'db_host': dbHost,
                                'db_port': dbPort,
                                'db_name': dbName,
                                'db_user': dbUser,
                                'db_pass': dbPass,
                                'hash': '<?php echo $_SESSION['hash']; ?>'
                            }
                        }).done(function(data) {
                            $target.html(data);
                        }).fail(function(data, statut, error) {
                            console.log(error);
                        });
                    }
                });
            }

            if ($('#step-3-global-settings')[0]) {
                $('#step-3-global-settings button[name="submit-btn"]').on('click', function() {
                    $('#wait-msg').removeClass('d-none');
                });
            }
        });
    </script>
</body>

</html>
