<?php
use fileuploader\server\FileUploader;
use phpformbuilder\Form;
use phpformbuilder\FormExtended;
use phpformbuilder\Validator\Validator;

if (!file_exists('../../../conf/conf.php')) {
    exit('<p class="alert alert-danger has-icon mt-5">Configuration file (conf/conf.php) not found</p>');
}
include_once '../../../conf/conf.php';

if (!file_exists(ROOT . 'conf/user-conf.json')) {
    exit('<p class="alert alert-danger has-icon mt-5">User Configuration file (conf/user-conf.json) not found</p>');
}
session_start();

// lock access on production server
if (ENVIRONMENT !== 'localhost' && GENERATOR_LOCKED === true) {
    include_once 'inc/protect.php';
}

include_once CLASS_DIR . 'phpformbuilder/Form.php';
include_once CLASS_DIR . 'phpformbuilder/FormExtended.php';

// include the fileuploader
include_once CLASS_DIR . 'phpformbuilder/plugins/fileuploader/server/class.fileuploader.php';

$userConf = json_decode(file_get_contents(ROOT . 'conf/user-conf.json'));

/*=============================================
=             Set default values              =
=============================================*/

$default_values = array(
    'admin_action_buttons_position'        => 'left',
    'admin_filtered_columns_class'         => 'text-bg-secondary-200',
    'admin_logo'                           => 'logo-height-100.png',
    'auto_enable_filters'                  => false,
    'bootstrap_theme'                      => 'default',
    'collapse_inactive_sidebar_categories' => true,
    'data_tables_scrollbar'                => true,
    'datetimepickers_lang'                 => 'en_EN',
    'datetimepickers_style'                => 'default',
    'debug'                                => true,
    'debug_db_queries'                     => false,
    'default_buttons_class'                => 'text-bg-secondary-400',
    'default_table_heading_class'          => 'bg-gray-dark',
    'enable_style_switching'               => true,
    'formvalidation_javascript_lang'       => 'en_US',
    'formvalidation_php_lang'              => 'en',
    'generator_locked'                     => false,
    'lang'                                 => 'en',
    'locale_default'                       => 'en-GB',
    'navbar_style'                         => 'light',
    'pagine_search_results'                => true,
    'sidebar_style'                        => 'dark',
    'sitename'                             => 'PHP CRUD GENERATOR',
    'timezone'                             => 'UTC',
    'users_password_constraint'            => 'lower-upper-number-min-6'
);

foreach ($default_values as $key => $value) {
    if (!isset($userConf->$key)) {
        $userConf->$key = $value;
    }
}

/* =============================================
    validation if posted
============================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('configuration-form') === true) {
    // create validator & auto-validate required fields
    $validator = Form::validate('configuration-form');

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['configuration-form'] = $validator->getAllErrors();
    } else {
        $userConf->admin_action_buttons_position = $_POST['admin_action_buttons_position'];
        $userConf->admin_filtered_columns_class  = $_POST['admin_filtered_columns_class'];
        if (isset($_POST['admin_logo']) && !empty($_POST['admin_logo'])) {
            $posted_file        = FileUploader::getPostedFiles($_POST['admin_logo']);
            $current_file_name  = $posted_file[0]['file'];
            $userConf->admin_logo = $current_file_name;
        }
        $userConf->auto_enable_filters           = false;
        if ($_POST['auto_enable_filters'] > 0) {
            $userConf->auto_enable_filters = true;
        }
        $userConf->bootstrap_theme = $_POST['bootstrap_theme'];
        $userConf->collapse_inactive_sidebar_categories = false;
        if (isset($_POST['collapse_inactive_sidebar_categories'])) {
            $userConf->collapse_inactive_sidebar_categories = true;
        }
        $userConf->data_tables_scrollbar = false;
        if ($_POST['data_tables_scrollbar'] > 0) {
            $userConf->data_tables_scrollbar = true;
        }
        if ($userConf->datetimepickers_style == 'default') {
            $userConf->datetimepickers_lang = $_POST['datetimepickers_lang'];
        } else {
            $userConf->datetimepickers_lang = $_POST['datetimepickers_material_lang'];
        }
        $userConf->datetimepickers_style = $_POST['datetimepickers_style'];
        $userConf->debug = true;
        if (!isset($_POST['debug'])) {
            $userConf->debug = false;
        }
        $userConf->debug_db_queries = true;
        if (!isset($_POST['debug_db_queries'])) {
            $userConf->debug_db_queries = false;
        }
        $userConf->default_table_heading_class = true;
        if (!isset($_POST['default_table_heading_class'])) {
            $userConf->default_table_heading_class = false;
        }
        $userConf->default_buttons_class           = $_POST['default_buttons_class'];
        $userConf->default_table_heading_class     = $_POST['default_table_heading_class'];
        $userConf->formvalidation_javascript_lang  = $_POST['formvalidation_javascript_lang'];
        $userConf->formvalidation_php_lang         = $_POST['formvalidation_php_lang'];
        $userConf->generator_locked = true;
        if (!isset($_POST['generator_locked'])) {
            $userConf->generator_locked = false;
        }
        if ($_POST['lang'] != 'other') {
            $userConf->lang = $_POST['lang'];
        } else {
            $userConf->lang = $_POST['lang-other'];
        }
        if (isset($_POST['locale_default'])) {
            $userConf->locale_default = $_POST['locale_default'];
        }
        $userConf->navbar_style = $_POST['navbar_style'];
        $userConf->pagine_search_results = false;
        if ($_POST['pagine_search_results'] > 0) {
            $userConf->pagine_search_results = true;
        }
        $userConf->sidebar_style              = $_POST['sidebar_style'];
        $userConf->sitename                   = $_POST['sitename'];
        $userConf->timezone                   = $_POST['timezone'];
        $userConf->users_password_constraint  = $_POST['users_password_constraint'];

        $user_conf = json_encode($userConf);
        if (DEMO !== true) {
            if (!file_put_contents(ROOT . 'conf/user-conf.json', $user_conf)) {
                $form_message = Form::buildAlert(ERROR_CANT_WRITE_FILE . ': ' . ROOT . 'conf/user-conf.json', 'bs5', 'danger has-icon');
            } else {
                $form_message = Form::buildAlert(CONFIGURATION_SUCCESS_MESSAGE, 'bs5', 'success has-icon');
                Form::clear('configuration-form');
            }
        } else {
            $form_message = Form::buildAlert(CONFIGURATION_SUCCESS_MESSAGE . ' (Disabled in DEMO)', 'bs5', 'success has-icon');
            Form::clear('configuration-form');
        }
    }
}
$form = new FormExtended('configuration-form', 'horizontal', 'novalidate');
$form->setMode('development');

// enable Ajax loading
$form->setOptions(['ajax' => true]);

if (!isset($_SESSION['errors']['configuration-form']) || empty($_SESSION['errors']['configuration-form'])) { // If no error registered
    $_SESSION['configuration-form']['admin_action_buttons_position']         = $userConf->admin_action_buttons_position;
    $_SESSION['configuration-form']['admin_filtered_columns_class']          = $userConf->admin_filtered_columns_class;
    $_SESSION['configuration-form']['admin_logo']                            = $userConf->admin_logo;
    $_SESSION['configuration-form']['auto_enable_filters']                   = $userConf->auto_enable_filters;
    $_SESSION['configuration-form']['bootstrap_theme']                       = $userConf->bootstrap_theme;
    $_SESSION['configuration-form']['collapse_inactive_sidebar_categories']  = $userConf->collapse_inactive_sidebar_categories;
    $_SESSION['configuration-form']['data_tables_scrollbar']                 = $userConf->data_tables_scrollbar;
    if ($userConf->datetimepickers_style == 'default') {
        $_SESSION['configuration-form']['datetimepickers_lang'] = $userConf->datetimepickers_lang;
        $_SESSION['datetimepickers_material_lang'] = '';
    } else {
        $_SESSION['configuration-form']['datetimepickers_lang'] = '';
        $_SESSION['datetimepickers_material_lang'] = $userConf->datetimepickers_lang;
    }
    $_SESSION['configuration-form']['datetimepickers_style']           = $userConf->datetimepickers_style;
    $_SESSION['configuration-form']['debug']                           = $userConf->debug;
    $_SESSION['configuration-form']['debug_db_queries']                = $userConf->debug_db_queries;
    $_SESSION['configuration-form']['default_buttons_class']           = $userConf->default_buttons_class;
    $_SESSION['configuration-form']['default_table_heading_class']     = $userConf->default_table_heading_class;
    $_SESSION['configuration-form']['enable_style_switching']          = $userConf->enable_style_switching;
    $_SESSION['configuration-form']['formvalidation_javascript_lang']  = $userConf->formvalidation_javascript_lang;
    $_SESSION['configuration-form']['formvalidation_php_lang']         = $userConf->formvalidation_php_lang;
    $_SESSION['configuration-form']['generator_locked']                = $userConf->generator_locked;
    $available_languages = array('en', 'es', 'fr', 'it');
    if (in_array($userConf->lang, $available_languages)) {
        $_SESSION['configuration-form']['lang'] = $userConf->lang;
    } else {
        $_SESSION['configuration-form']['lang'] = 'other';
        $_SESSION['configuration-form']['lang-other'] = $userConf->lang;
    }
    $_SESSION['configuration-form']['locale_default']             = $userConf->locale_default;
    $_SESSION['configuration-form']['navbar_style']               = $userConf->navbar_style;
    $_SESSION['configuration-form']['pagine_search_results']      = $userConf->pagine_search_results;
    $_SESSION['configuration-form']['sidebar_style']              = $userConf->sidebar_style;
    $_SESSION['configuration-form']['sitename']                   = $userConf->sitename;
    $_SESSION['configuration-form']['timezone']                   = $userConf->timezone;
    $_SESSION['configuration-form']['users_password_constraint']  = $userConf->users_password_constraint;
}

$form->startFieldset(PROJECT, 'class=mb-5', 'class=text-bg-info');

// site name
$form->addHelper(SITE_NAME_HELPER, 'sitename');
$form->addInput('text', 'sitename', '', SITE_NAME_TXT, 'required');

// admin logo

// reload the previously posted file if the form was posted with errors
$current_file = '';
if (isset($_POST['admin_logo']) && !empty($_POST['admin_logo'])) {
    $posted_file = FileUploader::getPostedFiles($_POST['admin_logo']);
    $current_file_name = $posted_file[0]['file'];
} else {
    $current_file_name = $userConf->admin_logo;
}
$current_file_path = ADMIN_DIR . 'assets/images/';
if (!empty($current_file_name) && file_exists($current_file_path . $current_file_name)) {
    $current_file_size = filesize($current_file_path . $current_file_name);
    $file_info = new finfo();
    $current_file_type = $file_info->file($current_file_path . $current_file_name, FILEINFO_MIME_TYPE);
    // $current_file_type = mime_content_type($current_file_path . $current_file_name);
    $current_file = array(
        'name' => $current_file_name,
        'size' => $current_file_size,
        'type' => $current_file_type,
        'file' => ADMIN_URL . '/assets/images/' . $current_file_name, // url of the file
        'data' => array(
            'listProps' => array(
                'file' => $current_file_name
            )
        )
    );
}

if ($_SERVER['HTTP_HOST'] !== 'www.phpcrudgenerator.com') {
    $fileUpload_config = array(
        'xml'           => 'image-upload',                          // the uploader's config in phpformbuilder/plugins-config/fileuploader.xml
        'uploader'      => 'ajax_upload_file.php',              // the uploader file in phpformbuilder/plugins/fileuploader/[xml]/php
        'upload_dir'    => ADMIN_DIR . 'assets/images/',   // the directory to upload the files. relative to [plugins dir]/fileuploader/image-upload/php/ajax_upload_file.php
        'limit'         => 1,                                       // max. number of files
        'file_max_size' => 5,                                       // each file's maximal size in MB {null, Number}
        'extensions'    => ['jpg', 'jpeg', 'png', 'gif'],           // allowed extensions
        'thumbnails'    => false,                                    // the thumbs directories must exist. thumbs config. is done in phpformbuilder/plugins/fileuploader/image-upload/php/ajax_upload_file.php
        'editor'        => true,                                    // allows the user to crop/rotate the uploaded image
        'width'         => 1000,                                     // the uploaded image maximum width
        'height'        => 100,                                     // the uploaded image maximum height
        'crop'          => false,
        'debug'         => true                                     // log the result in the browser's console and shows an error text on the page if the uploader fails to parse the json result.
    );
    $form->addHelper(ADMIN_LOGO_HELPER, 'admin_logo');
    if (is_array($current_file)) {
        $form->addFileUpload('admin_logo', '', ADMIN_LOGO_TXT, '', $fileUpload_config, $current_file);
    } else {
        $form->addFileUpload('admin_logo', '', ADMIN_LOGO_TXT, '', $fileUpload_config);
    }
}

$form->endFieldset();
$form->startFieldset(SECURITY, 'class=mb-5', 'class=text-bg-info');

// generator_locked
$form->addHelper(LOCK_THE_GENERATOR_HELPER, 'generator_locked');
$form->addCheckbox('generator_locked', '', true, 'data-toggle=true, data-on-label=' . YES . ', data-off-label=' . NO . ', data-on-icon=' . ICON_CHECKMARK . ', data-off-icon=' . ICON_CANCEL . ', data-on-color=success-o');
$form->printCheckboxGroup('generator_locked', LOCK_THE_GENERATOR_TXT);

$form->endFieldset();
$form->startFieldset(DEBUGGING, 'class=mb-5', 'class=text-bg-info');

$form->setOptions(array('helperWrapper' => '<span class="form-text ps-3 d-block"></span>'));

// debug
$form->addHelper(DEBUG_SETTINGS_HELPER, 'debug');
$form->addCheckbox('debug', '', true, 'data-toggle=true, data-on-label=' . YES . ', data-off-label=' . NO . ', data-on-icon=' . ICON_CHECKMARK . ', data-off-icon=' . ICON_CANCEL . ', data-on-color=success-o');
$form->printCheckboxGroup('debug', DEBUG_SETTINGS_TXT, false);

// debug_db_queries
$form->addHelper(DEBUG_DB_QUERIES_SETTINGS_HELPER, 'debug_db_queries');
$form->addCheckbox('debug_db_queries', '', true, 'data-toggle=true, data-on-label=' . YES . ', data-off-label=' . NO . ', data-on-icon=' . ICON_CHECKMARK . ', data-off-icon=' . ICON_CANCEL . ', data-on-color=success-o');
$form->printCheckboxGroup('debug_db_queries', DEBUG_DB_QUERIES_SETTINGS_TXT, false);

$form->setOptions(array('helperWrapper' => '<span class="form-text"></span>'));

$form->endFieldset();
$form->startFieldset(STYLES, 'class=mb-5', 'class=text-bg-info');

// Bootstrap theme
$form->addBootstrapThemeSelect();

// navbar_style
$form->addNavStyleSelect('navbar_style', NAVBAR_STYLE_TXT);

// sidebar_style
$form->addNavStyleSelect('sidebar_style', SIDEBAR_STYLE_TXT);

// admin_filtered_columns_class
$form->addHelper(ADMIN_FILTERED_COLUMNS_CLASS_HELPER, 'admin_filtered_columns_class');
$form->addInput('text', 'admin_filtered_columns_class', '', ADMIN_FILTERED_COLUMNS_CLASS_TXT);

// datetimepickers style
$form->addOption('datetimepickers_style', 'default', DEFAULT_CONST);
$form->addOption('datetimepickers_style', 'material', 'Material Design');
$form->addSelect('datetimepickers_style', 'Date &amp; Time pickers style', 'data-slimselect=true, data-allow-deselect=false, data-show-search=false');

// default_buttons_class
$form->addHelper(DEFAULT_BUTTONS_CLASS_HELPER, 'default_buttons_class');
$form->addInput('text', 'default_buttons_class', '', DEFAULT_BUTTONS_CLASS_TXT);

// default_table_heading_class
$form->addHelper(DEFAULT_TABLE_HEADING_CLASS_HELPER, 'default_table_heading_class');
$form->addInput('text', 'default_table_heading_class', '', DEFAULT_TABLE_HEADING_CLASS_TXT);

$form->endFieldset();
$form->startFieldset(USER_INTERFACE, 'class=mb-5', 'class=text-bg-info');

// admin_action_buttons_position
$form->addRadio('admin_action_buttons_position', ON_THE_LEFT, 'left');
$form->addRadio('admin_action_buttons_position', ON_THE_RIGHT, 'right');
$form->printRadioGroup('admin_action_buttons_position', ADMIN_ACTION_BUTTONS_POSITION_TXT, true);

// enable_style_switching
$form->setOptions(array('helperWrapper' => '<span class="form-text ps-3 d-block"></span>'));
$form->addHelper(ENABLE_STYLE_SWITCHING_HELPER, 'enable_style_switching');
$form->addCheckbox('enable_style_switching', '', true, 'data-toggle=true, data-on-label=' . YES . ', data-off-label=' . NO . ', data-on-icon=' . ICON_CHECKMARK . ', data-off-icon=' . ICON_CANCEL . ', data-on-color=success-o');
$form->printCheckboxGroup('enable_style_switching', '<span class="ms-2">' . ENABLE_STYLE_SWITCHING_TXT . '</span>', false);
$form->setOptions(array('helperWrapper' => '<span class="form-text"></span>'));

// auto_enable_filters
$form->addRadio('auto_enable_filters', ON_FILTER_BUTTON_CLICK_TXT, false);
$form->addRadio('auto_enable_filters', ON_SELECT_TXT, true);
$form->printRadioGroup('auto_enable_filters', ENABLE_FILTERS_TXT, true);

// collapse_inactive_sidebar_categories
$form->addCheckbox('collapse_inactive_sidebar_categories', '', true, 'data-toggle=true, data-on-label=' . YES . ', data-off-label=' . NO . ', data-on-icon=' . ICON_CHECKMARK . ', data-off-icon=' . ICON_CANCEL . ', data-on-color=success-o');
$form->printCheckboxGroup('collapse_inactive_sidebar_categories', COLLAPSE_INACTIVE_SIDEBAR_CATEGORIES_TXT, false);

// data_tables_scrollbar
$form->addRadio('data_tables_scrollbar', WITH_A_VERTICAL_SCROLL_BAR, true);
$form->addRadio('data_tables_scrollbar', WITHOUT_A_SCROLLBAR, false);
$form->printRadioGroup('data_tables_scrollbar', DISPLAY_OF_DATA_TABLES, true);

// pagine_search_results
$form->addRadio('pagine_search_results', IN_A_PAGINATED_LIST, true);
$form->addRadio('pagine_search_results', ALL_ON_THE_SAME_PAGE, false);
$form->printRadioGroup('pagine_search_results', SHOW_SEARCH_RESULTS, true);

// users_password_constraint
$password_contraints = array('lower-upper-min-', 'lower-upper-number-min-', 'lower-upper-number-symbol-min-');

foreach ($password_contraints as $constraint) {
    # code...
    for ($i = 3; $i < 9; $i++) {
        $form->addOption('users_password_constraint', $constraint . $i, $constraint . $i);
    }
}
$form->addHelper(USERS_PASSWORD_CONSTRAINT_HELPER, 'users_password_constraint');
$form->addSelect('users_password_constraint', USERS_PASSWORD_CONSTRAINT_TXT, 'data-slimselect=true, data-allow-deselect=false, data-show-search=false');

$form->endFieldset();
$form->startFieldset(LANGUAGE_SETTINGS_TXT, '', 'class=text-bg-info');

// lang
$form->addOption('lang', 'en', 'English');
$form->addOption('lang', 'es', 'Spanish');
$form->addOption('lang', 'it', 'Italian');
$form->addOption('lang', 'fr', 'French');
$form->addOption('lang', 'cs', 'Czech');
$form->addOption('lang', 'other', 'Other');
$form->addSelect('lang', LANGUAGE_TXT, 'data-slimselect=true, data-allow-deselect=false, data-show-search=false, required');

$form->startDependentFields('lang', 'other');
$form->addHelper(LANGUAGE_OTHER_HELPER, 'lang-other');
$form->addInput('text', 'lang-other', '', LANGUAGE_OTHER_TXT, 'required');
$form->endDependentFields();

// locale_default
if (class_exists('Locale')) {
    $locale = ResourceBundle::getLocales('');
    foreach ($locale as $value) {
        $value = str_replace('_', '-', $value);
        $form->addOption('locale_default', $value, $value);
    }
    $form->addHelper(DATE_TIME_TRANSLATIONS_FOR_LISTS_HELPER, 'locale_default');
    $form->addSelect('locale_default', DATE_TIME_TRANSLATIONS_FOR_LISTS_TXT, 'data-slimselect=true, data-allow-deselect=false');
} else {
    $form->addHtml(NO_LOCALE);
}

// timezone
$timezones = DateTimeZone::listIdentifiers();
$timezones_count = count($timezones);
for ($i = 0; $i < $timezones_count; $i++) {
    $form->addOption('timezone', $timezones[$i], $timezones[$i]);
}
$form->addSelect('timezone', 'Timezone', 'data-slimselect=true, data-allow-deselect=false');

// datetimepickers lang
$form->startDependentFields('datetimepickers_style', 'default');
$files = array_diff(scandir(CLASS_DIR . 'phpformbuilder/plugins/pickadate/lib/compressed/translations/'), array('.', '..'));
$form->addOption('datetimepickers_lang', 'en_EN', 'en_EN');
foreach ($files as $file) {
    $file = str_replace('.js', '', $file);
    $form->addOption('datetimepickers_lang', $file, $file);
}
$form->addHelper(DATETIMEPICKERS_LANG_HELPER, 'datetimepickers_lang');
$form->addSelect('datetimepickers_lang', DATETIMEPICKERS_LANG_TXT, 'data-slimselect=true, data-allow-deselect=false');
$form->endDependentFields();

// datetimepickers material lang
$form->startDependentFields('datetimepickers_style', 'material');
$files = array_diff(scandir(CLASS_DIR . 'phpformbuilder/plugins/material-datepicker/dist/i18n/'), array('.', '..'));
foreach ($files as $file) {
    $file = str_replace('.js', '', $file);
    $form->addOption('datetimepickers_material_lang', $file, $file);
}
$form->addHelper(DATETIMEPICKERS_MATERIAL_LANG_HELPER, 'datetimepickers_material_lang');
$form->addSelect('datetimepickers_material_lang', DATETIMEPICKERS_LANG_TXT, 'data-slimselect=true, data-allow-deselect=false, data-show-search=false');
$form->endDependentFields();

// formvalidation JavaScript lang
$files = array_diff(scandir(CLASS_DIR . 'phpformbuilder/plugins/formvalidation/js/locales'), array('.', '..'));
$form->addOption('formvalidation_javascript_lang', 'en_US', 'en_US');
foreach ($files as $file) {
    if (!strpos('.min.js', $file)) {
        $file = str_replace('.js', '', $file);
        $form->addOption('formvalidation_javascript_lang', $file, $file);
    }
}
$form->addHelper(FORMVALIDATION_JAVASCRIPT_LANG_HELPER, 'formvalidation_javascript_lang');
$form->addSelect('formvalidation_javascript_lang', FORMVALIDATION_JAVASCRIPT_LANG_TXT, 'data-slimselect=true, data-allow-deselect=false');

// formvalidation PHP lang
$validation_php_langs = array('de', 'en', 'es', 'fr', 'pt_br');
foreach ($validation_php_langs as $lang) {
    $form->addOption('formvalidation_php_lang', $lang, $lang);
}
$form->addHelper(FORMVALIDATION_PHP_LANG_HELPER, 'formvalidation_php_lang');
$form->addSelect('formvalidation_php_lang', FORMVALIDATION_PHP_LANG_TXT, 'data-slimselect=true, data-allow-deselect=false, data-show-search=false');

$form->endFieldset();

$form->centerContent();

$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success, data-ladda-button=true, data-style=zoom-in');

$form->addPlugin('formvalidation', '#configuration-form', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));

if (isset($form_message)) {
    echo $form_message;
}
$form->render();
?>
<script>
    enablePrettyCheckbox('#configuration-form');
    window.scrollTo(0, 0);
</script>
