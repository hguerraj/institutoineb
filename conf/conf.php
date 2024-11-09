<?php
// phpcs:disable PSR1.Files.SideEffects
/* =============================================
    CORE DEFINITIONS - DON'T MODIFY ANYTHING
    USE THE 'GENERAL SETTINGS' form
    in the Generator
============================================= */

define('AUTHOR', 'Hector Guerra');
define('VERSION', '2.3.1');

// path to conf folder
$root_path = dirname(dirname(__FILE__));

// reliable path to conf folder with symlinks resolved
$info = new \SplFileInfo($root_path);
$real_root_path = $info->getRealPath();

// sanitize root directory separator
$root = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $real_root_path) . DIRECTORY_SEPARATOR;

// ROOT is the path leading to the PHPCG folders (admin, generator, ...)
define('ROOT', $root);

$userConf = json_decode(file_get_contents(ROOT . 'conf/user-conf.json'));

/* Project BASE URL
-------------------------------------------------- */

$base_urls = getBaseUrls();

// Uncomment the following line to display the 2 returned values
// echo 'BASE_URL: ' . $base_urls['base_url'] . '<br>ROOT_RELATIVE_URL: ' . $base_urls['root_relative_url'] . '<br>';

// BASE_URL MUST lead to your project root url.
// This url MUST end with a slash.
// ie: define('BASE_URL', 'http://localhost/my-project/');
define('BASE_URL', $base_urls['base_url']);

// ROOT_RELATIVE_URL is the ROOT RELATIVE URL leading to you admin folder.
// This url MUST end with a slash.
define('ROOT_RELATIVE_URL', $base_urls['root_relative_url']);

define('ADMIN_DIR', ROOT . 'admin/');
define('CLASS_DIR', ROOT . 'class/');
define('GENERATOR_DIR', ROOT . 'generator/');

// backup dir must exist on your server
define('BACKUP_DIR', GENERATOR_DIR . 'backup-files/');

define('ADMIN_URL', BASE_URL . 'admin/');
define('ADMINLOGINPAGE', ADMIN_URL . 'login');
define('ADMINREDIRECTPAGE', ADMIN_URL . 'home');
define('ASSETS_URL', BASE_URL . 'assets/');
define('CLASS_URL', BASE_URL . 'class/');
define('GENERATOR_URL', BASE_URL . 'generator/');

/* Database debugging (bool) - hide/show the database errors */

define('DEBUG', $userConf->debug);

/* Database queries debugging (bool)
    if true:
        - Show the details of all the database queries
        - All the insert/update/delete queries are simulated (not executed)
*/

define('DEBUG_DB_QUERIES', $userConf->debug_db_queries);

/* Translation */

// the translation file MUST exist in /admin/i18n/
define('LANG', $userConf->lang);

if (!file_exists(ADMIN_DIR . 'i18n/' . LANG . '.php')) {
    exit('Language file doesn\'t exist: ' . ADMIN_DIR . 'i18n/' . LANG . '.php - Please <a href="https://www.phpcrudgenerator.com/documentation/index#i18n">read the documentation</a> and add your language to i18n');
} else {
    include_once ADMIN_DIR . 'i18n/' . LANG . '.php';
}

// Detect if we're on https://www.phpcrudgenerator.com's demo
$demo = false;
if (preg_match('`^www.phpcrudgenerator.com`', $_SERVER['HTTP_HOST'])) {
    $demo = true;
}

define('DEMO', $demo);

/*
    localhost :
        shows queries on database errors
    production :
        hide queries
*/

$environment = 'production';

if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1') {
    // localhost settings
    $environment = 'development';
}
define('ENVIRONMENT', $environment);

// datetime filedtypes for daterange filters
define('DATETIME_FIELD_TYPES', 'date,datetime,timestamp');

/* =============================================
    ICONS
============================================= */

define('ICON_ADDRESS', 'fas fa-location-dot');
define('ICON_ARROW_DOWN', 'fas fa-angle-down');
define('ICON_ARROW_LEFT', 'fas fa-angle-left');
define('ICON_ARROW_RIGHT', 'fas fa-angle-right');
define('ICON_ARROW_UP', 'fas fa-angle-up');
define('ICON_ARROW_RIGHT_CIRCLE', 'fas fa-circle-right');
define('ICON_BACK', 'fas fa-left-long');
define('ICON_CALENDAR', 'fas fa-calendar');
define('ICON_CANCEL', 'fas fa-xmark');
define('ICON_CHECKMARK', 'fas fa-check');
define('ICON_CITY', 'fas fa-building');
define('ICON_COMPANY', 'fas fa-id-card');
define('ICON_CONTACT', 'fas fa-phone');
define('ICON_COUNTRY', 'fas fa-flag');
define('ICON_DATABASE', 'fas fa-database');
define('ICON_DASHBOARD', 'fas fa-power-off');
define('ICON_DELETE', 'fas fa-circle-xmark');
define('ICON_EDIT', 'fas fa-pencil');
define('ICON_ENVELOP', 'fas fa-envelope');
define('ICON_FILTER', 'fas fa-arrow-down-wide-short');
define('ICON_FULLSCREEN', 'fas fa-expand');
define('ICON_HOME', 'fas fa-house-chimney');
define('ICON_HOUR_GLASS', 'fas fa-hourglass-half');
define('ICON_INFO', 'fas fa-circle-info');
define('ICON_KEY', 'fas fa-key');
define('ICON_LINK', 'fas fa-link');
define('ICON_LIST', 'fas fa-list');
define('ICON_LOCK', 'fas fa-lock');
define('ICON_LOGOUT', 'fas fa-power-off');
define('ICON_LOGIN', 'fas fa-circle-user');
define('ICON_NAVBAR', ' fas fa-bars');
define('ICON_NEW_TAB', 'fas fa-up-right-from-square');
define('ICON_PASSWORD', 'fas fa-eye-slash');
define('ICON_PLUS', 'fas fa-circle-plus');
define('ICON_QUESTION', 'fas fa-question');
define('ICON_REFRESH', 'fas fa-rotate');
define('ICON_RESET', 'fas fa-rotate-left');
define('ICON_SEARCH', 'fas fa-magnifying-glass');
define('ICON_SPINNER', 'fas fa-spinner');
define('ICON_STOP', 'fas fa-circle-stop');
define('ICON_STYLES', 'fas fa-palette');
define('ICON_TRANSMISSION', 'fas fa-right-left');
define('ICON_UNLOCK', 'fas fa-unlock');
define('ICON_UPLOAD', 'fas fa-upload');
define('ICON_USER', 'fas fa-user');
define('ICON_USER_PLUS', 'fas fa-user-plus');
define('ICON_VIEW', 'fas fa-eye');
define('ICON_ZIP_CODE', 'fas fa-location-arrow');

/* database connection */

include_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';

/* =============================================
    USER CONFIG
============================================= */

// Lock Generator with login page
define('GENERATOR_LOCKED', $userConf->generator_locked);

// set timezone
define('TIMEZONE', $userConf->timezone);

date_default_timezone_set(TIMEZONE);

/* Admin panel settings
-------------------------------------------------- */

// Lock/Unlock admin panel with User Authentification
define('ADMIN_LOCKED', $userConf->admin_locked);

// Bootstrap theme
$bootstrap_theme = $userConf->bootstrap_theme;
if (isset($_COOKIE['bootstrap_theme']) && ctype_lower($_COOKIE['bootstrap_theme'])) {
    $bootstrap_theme = $_COOKIE['bootstrap_theme'];
}

// The original Bootstrap theme is the one saved from the generator to the user conf.
// The Bootstrap theme is the one saved in the browser cookie by the user from the admin.
define('ORIGINAL_BOOTSTRAP_THEME', $userConf->bootstrap_theme);
define('BOOTSTRAP_THEME', $bootstrap_theme);

// Admin panel main title
define('SITENAME', $userConf->sitename);

// date & time translations for lists
if (class_exists('Locale')) {
    Locale::setDefault($userConf->locale_default);
}

define('DATETIMEPICKERS_STYLE', $userConf->datetimepickers_style);
define('DATETIMEPICKERS_LANG', $userConf->datetimepickers_lang);

// form validation translations
define('FORMVALIDATION_JAVASCRIPT_LANG', $userConf->formvalidation_javascript_lang);
define('FORMVALIDATION_PHP_LANG', $userConf->formvalidation_php_lang);

// Admin panel action buttons
define('ADMIN_ACTION_BUTTONS_POSITION', $userConf->admin_action_buttons_position);

// Admin panel enable style switching
define('ENABLE_STYLE_SWITCHING', $userConf->enable_style_switching);

// Admin panel auto-enable filters
define('AUTO_ENABLE_FILTERS', $userConf->auto_enable_filters);

// admin filtered columns CSS class
define('ADMIN_FILTERED_COLUMNS_CLASS', $userConf->admin_filtered_columns_class);

// Admin panel logo
define('ADMIN_LOGO', $userConf->admin_logo);

// password contraint for users acounts
// available contraints in
// /class/phpformbuilder/plugins-config/passfield.xml
define('USERS_PASSWORD_CONSTRAINT', $userConf->users_password_constraint);

// Display lists contained in the screen with a vertical scroll bar
define('DATA_TABLES_SCROLLBAR', $userConf->data_tables_scrollbar);

// Search results settings
define('PAGINE_SEARCH_RESULTS', $userConf->pagine_search_results);

// Sidebar settings
define('COLLAPSE_INACTIVE_SIDEBAR_CATEGORIES', $userConf->collapse_inactive_sidebar_categories);

// user_data table
define('PHPCG_USERDATA_TABLE', $userConf->phpcg_userdata_table);

/* Admin panel skin
-------------------------------------------------- */

$available_styles = array('primary', 'secondary', 'success', 'info', 'warning', 'danger', 'light', 'dark');

$navbar_style = $userConf->navbar_style;
if (isset($_COOKIE['navbar_style']) && in_array($_COOKIE['navbar_style'], $available_styles)) {
    $navbar_style = $_COOKIE['navbar_style'];
}
// The original navbar style is the one saved from the generator to the user conf.
// The navbar style is the one saved in the browser cookie by the user from the admin.
define('ORIGINAL_NAVBAR_STYLE', $userConf->navbar_style);
define('NAVBAR_STYLE', $navbar_style);

$sidebar_style = $userConf->sidebar_style;
if (isset($_COOKIE['sidebar_style']) && in_array($_COOKIE['sidebar_style'], $available_styles)) {
    $sidebar_style = $_COOKIE['sidebar_style'];
}
// The original sidebar style is the one saved from the generator to the user conf.
// The sidebar style is the one saved in the browser cookie by the user from the admin.
define('ORIGINAL_SIDEBAR_STYLE', $userConf->sidebar_style);
define('SIDEBAR_STYLE', $sidebar_style);

define('DEFAULT_BUTTONS_CLASS', $userConf->default_buttons_class);
define('DEFAULT_TABLE_HEADING_CLASS', $userConf->default_table_heading_class);

/* =============================================
    Auto-detect base url
============================================= */

function getBaseUrls()
{
    // reliable document_root (https://gist.github.com/jpsirois/424055)
    $script_name     = str_replace(DIRECTORY_SEPARATOR, '/', $_SERVER['SCRIPT_NAME']);
    $script_filename = str_replace(DIRECTORY_SEPARATOR, '/', $_SERVER['SCRIPT_FILENAME']);
    $root_path       = str_replace($script_name, '', $script_filename);
    $conf_path       = rtrim(dirname(strtolower(dirname(__FILE__))), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    // reliable document_root with symlinks resolved
    $info = new \SplFileInfo($root_path);
    $real_root_path = strtolower($info->getRealPath());

    // defined root_relative url used in admin routing
    // ie: /my-dir/
    $root_relative_url = '/' . ltrim(
        str_replace(array($real_root_path, DIRECTORY_SEPARATOR), array('', '/'), $conf_path),
        '/'
    );
    // sanitize directory separator
    $base_url = (((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $root_relative_url;

    return array(
        'root_relative_url' => $root_relative_url,
        'base_url'          => $base_url
    );
}

/* =============================================
    Register the default autoloader implementation in the php engine.
============================================= */

function autoload($class)
{
    /* Define the paths to the directories holding class files */

    $paths = array(
        CLASS_DIR,
        ADMIN_DIR . 'class/',
        ADMIN_DIR . 'secure/'
    );
    foreach ($paths as $path) {
        $file = $path . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file) === true) {
            require_once $file;
            break;
        }
    }
}
spl_autoload_register('autoload');
