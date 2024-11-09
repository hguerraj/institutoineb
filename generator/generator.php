<?php
// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable Generic.WhiteSpace.ScopeIndent
use phpformbuilder\Form;
use phpformbuilder\FormExtended;
use phpformbuilder\database\DB;
use generator\Generator;
use common\Utils;

if (file_exists('conf/conf.php')) {
    include_once 'conf/conf.php';
} elseif (file_exists('../conf/conf.php')) {
    include_once '../conf/conf.php';
} else {
    exit('Configuration file not found (4)');
}
include_once GENERATOR_DIR . 'class/generator/Generator.php';
session_start();

// lock access on production server
if (ENVIRONMENT !== 'localhost' && GENERATOR_LOCKED === true) {
    include_once 'inc/protect.php';
}

// phpcrudgenerator.com navbar (include path from index router)
if (file_exists('inc/navbar-main.php')) {
    define('IS_PHPCRUDGENERATOR_COM', true);
}

// default styles for cards, badges, ...
include_once 'inc/default-generator-styles.php';

if (!isset($_SESSION['generator'])) {
    $generator = new Generator(DEBUG);
} else {
    $generator = $_SESSION['generator'];
}
$generator->init();

$db = new DB(true);

if (!empty($generator->database)) {
    $form_reset_relations = new Form('form-reset-relations', 'inline');
    $form_reset_relations->useLoadJs('core');
    $form_reset_relations->setMode('development');

    $form_reset_relations->setOptions(array('buttonWrapper' => ''));

    // required just to transmit generator url to jQuery for the updater
    $form_reset_relations->addInput('hidden', 'generator-url', GENERATOR_URL);

    $form_reset_relations->setAction($_SERVER["REQUEST_URI"]);
    $form_reset_relations->addInput('hidden', 'reset-relations', 1);
    $form_reset_relations->addBtn('submit', 'submit', 1, REFRESH_DB_RELATIONS . '<i class="' . ICON_TRANSMISSION . ' append"></i>', 'class=dropdown-item d-flex justify-content-between, data-bs-toggle=tooltip, data-toggle-loader=true, title=' . REFRESH_DB_RELATIONS_HELPER);
    $generator->getTables();

    if (isset($_POST['reset-table']) && $_POST['reset-table'] > 0 && DEMO !== true) {
        // select the posted table, which has been set as the new generator table
        $_SESSION['form-select-table']['table'] = $generator->table;
    }

    $form_select_table = new Form('form-select-table', 'inline', 'class=w-100');
    $form_select_table->useLoadJs('core');
    $options = array(
        'elementsWrapper' => '<div class="d-flex flex-fill align-items-center mb-3"></div>'
    );
    $form_select_table->setOptions($options);
    $form_select_table->setMode('development');
    $form_select_table->setAction($_SERVER["REQUEST_URI"]);
    $addon = '<button class="btn btn-primary ms-2" type="submit">' . LOAD . '<i class="' . ICON_ARROW_RIGHT_CIRCLE . ' append"></i></button>';
    $form_select_table->addAddon('table', $addon, 'after');
    foreach ($generator->tables as $table) {
        $form_select_table->addOption('table', $table, $table);
    }
    $form_select_table->addSelect('table', 'table: ', 'data-slimselect=true, data-allow-deselect=false, class=w-auto');

    $form_reset_table = new Form('form_reset_table', 'inline');
    $form_reset_table->useLoadJs('core');
    $form_reset_table->setMode('development');
    $form_reset_table->setAction($_SERVER["REQUEST_URI"]);
    $form_reset_table->addInput('hidden', 'reset-table', 1);
    $form_reset_table->addInput('hidden', 'table-to-reset', $generator->table);
    $form_reset_table->addInput('hidden', 'reset-data', 0);
    $form_reset_table->setOptions(array('buttonWrapper' => '<div></div>'));
    $form_reset_table->addBtn('button', 'btn-reset-table', 1, REFRESH . ' "<em>' . $generator->table . '</em>" ' . STRUCTURE . '<i class="' . ICON_RESET . ' append"></i>', 'class=btn btn-sm btn-warning, data-bs-toggle=tooltip, data-bs-title=' . REFRESH_TABLE_HELPER);
}
if (!empty($generator->table)) {
    $generator->getDbColumns();
    $generator->registerColumnsProperties();

    if (!isset($_POST['form-select-fields']) && isset($_SESSION['form-select-fields'])) {
        // reset the required fields if the form is not posted.
        // when the table changes, if we don't reset, the new required fields will be added to the previous table's required fields.
        unset($_SESSION['form-select-fields']);
    }
    // reset errors
    unset($_SESSION['errors']['form-select-fields']);
    unset($_SESSION['errors']['build-list']);
    unset($_SESSION['errors']['build-create-edit']);

    if (isset($_POST['action']) && isset($_POST['form-select-fields']) && Form::testToken('form-select-fields') === true && DEMO !== true) {
        $validator = FormExtended::validate('form-select-fields', FORMVALIDATION_PHP_LANG);
        // check for errors
        if ($validator->hasErrors()) {
            $errors = $validator->getAllErrors();
            // set the errors on the appropriate tab
            if ($_POST['action'] === 'build_read') {
                $_SESSION['errors']['build-list'] = $errors;
            } elseif ($_POST['action'] === 'build_create_edit') {
                $_SESSION['errors']['build-create-edit'] = $errors;
            }
        } else {
            $generator->runBuild();
        }
    }

    // get values from generator
    if (!isset($_POST['form-select-fields'])) {
        $_SESSION['form-select-fields']['action'] = 'build_read';
    }

    if (isset($_SESSION['errors']['build-list'])) {
        $_SESSION['errors']['form-select-fields'] = $_SESSION['errors']['build-list'];
    }

    // Create the form before registering session values
    // to overwrite posted values with the generator ones
    // if the form has been posted
    $form_select_fields = new FormExtended('form-select-fields', 'horizontal', 'novalidate');
    $form_select_fields->useLoadJs('core');
    $form_select_fields->setMode('development');
    $form_select_fields->setAction($_SERVER['REQUEST_URI']);
    $options = array(
        'elementsClass' => 'form-control form-control-sm'
    );
    $form_select_fields->setOptions($options);

    /* =============================================
    Default List Values
    ============================================= */

    $_SESSION['form-select-fields']['list_type']        = $generator->list_options['list_type'];
    $_SESSION['form-select-fields']['rp_export_btn']    = $generator->list_options['export_btn'];
    $_SESSION['form-select-fields']['rp_open_url_btn']  = $generator->list_options['open_url_btn'];
    $_SESSION['form-select-fields']['rp_table_label']   = $generator->table_label;
    $_SESSION['form-select-fields']['rp_table_label']   = $generator->table_label;

    if ($generator->list_options['list_type'] !== 'build_single_element_list') {
        $_SESSION['form-select-fields']['rp_default_search_field'] = $generator->list_options['default_search_field'];
        $_SESSION['form-select-fields']['rp_bulk_delete']          = $generator->list_options['bulk_delete'];
        $_SESSION['form-select-fields']['rp_view_record']          = $generator->list_options['view_record'];
        $_SESSION['form-select-fields']['rp_order_by']             = $generator->list_options['order_by'];
        $_SESSION['form-select-fields']['rp_order_direction']      = $generator->list_options['order_direction'];
    }

    // columns
    for ($i = 0; $i < $generator->columns_count; $i++) {
        $column_name     = $generator->columns['name'][$i];
        $column_type     = $generator->columns['column_type'][$i];
        $column_relation = $generator->columns['relation'][$i];

        // if one-to-many relation
        if (!empty($column_relation['target_table'])) {
            if (empty($column_relation['target_fields_display_values'])) {
                $_SESSION['form-select-fields']['rp_target_column_0_' . $column_name] = $column_relation['target_fields'];
                $_SESSION['form-select-fields']['rp_target_column_1_' . $column_name] = '';
            } else {
                $target_fields_display_values = explode(', ', $column_relation['target_fields_display_values']);
                for ($j = 0; $j < 2; $j++) {
                    if (isset($target_fields_display_values[$j])) {
                        $_SESSION['form-select-fields']['rp_target_column_' . $j . '_' . $column_name] = $target_fields_display_values[$j];
                    } else {
                        $_SESSION['form-select-fields']['rp_target_column_' . $j . '_' . $column_name] = '';
                    }
                }
            }
        }

        // label
        if (isset($generator->columns['fields'][$column_name])) {
            $_SESSION['form-select-fields']['rp_label_' . $column_name] = $generator->columns['fields'][$column_name];
        }

        // value type
        $_SESSION['form-select-fields']['rp_value_type_' . $column_name] = $generator->columns['value_type'][$i];

        // jedit
        $_SESSION['form-select-fields']['rp_jedit_' . $column_name] = $generator->columns['jedit'][$i];

        // special
        if ($generator->columns['value_type'][$i] == 'file') {
            $_SESSION['form-select-fields']['rp_special_file_dir_' . $column_name] = $generator->columns['special'][$i];
            $_SESSION['form-select-fields']['rp_special_file_url_' . $column_name] = $generator->columns['special2'][$i];
            $_SESSION['form-select-fields']['rp_special_file_types_' . $column_name] = $generator->columns['special3'][$i];
        } elseif ($generator->columns['value_type'][$i] == 'image') {
            $_SESSION['form-select-fields']['rp_special_image_dir_' . $column_name] = $generator->columns['special'][$i];
            $_SESSION['form-select-fields']['rp_special_image_url_' . $column_name] = $generator->columns['special2'][$i];
            $_SESSION['form-select-fields']['rp_special_image_thumbnails_' . $column_name] = $generator->columns['special3'][$i];
        } elseif ($generator->columns['value_type'][$i] == 'password') {
            $_SESSION['form-select-fields']['rp_special_password_' . $column_name] = $generator->columns['special'][$i];
        } elseif ($generator->columns['value_type'][$i] == 'date') {
            $_SESSION['form-select-fields']['rp_special_date_' . $column_name] = $generator->columns['special'][$i];
        } elseif ($generator->columns['value_type'][$i] == 'time') {
            $_SESSION['form-select-fields']['rp_special_time_' . $column_name] = $generator->columns['special'][$i];
        }

        // others
        $others = '';
        if ($generator->columns['sorting'][$i]) {
            $others = 'sorting';
        } elseif ($generator->columns['nested'][$i]) {
            $others = 'nested';
        } elseif ($generator->columns['skip'][$i]) {
            $others = 'skip';
        }
        $_SESSION['form-select-fields']['rp_others_' . $column_name] = $others;
    }

    $external_columns_count = 0;
    if (is_countable($generator->external_columns)) {
        $external_columns_count = count($generator->external_columns);
    }

    // external relations
    if ($external_columns_count > 0) {
        $i = 0;
        foreach ($generator->external_columns as $key => $ext_col) {
            if (!isset($ext_col['allow_crud_in_list'])) {
                $ext_col['allow_crud_in_list'] = false;
            }
            if (!isset($ext_col['allow_in_forms'])) {
                $ext_col['allow_in_forms'] = true;
            }
            if (!isset($ext_col['forms_fields'])) {
                $ext_col['forms_fields'] = array();
            }
            if (!isset($ext_col['field_type'])) {
                $ext_col['field_type'] = 'select-multiple';
            }
            if (!isset($ext_col['nested'])) {
                $ext_col['nested'] = false;
            }
            $_SESSION['form-select-fields']['rp_ext_col_target_table-' . $i]       = $ext_col['active'];
            $_SESSION['form-select-fields']['rp_ext_col_target_fields-' . $i]      = $ext_col['target_fields'];
            $_SESSION['form-select-fields']['rp_ext_col_allow_crud_in_list-' . $i] = $ext_col['allow_crud_in_list'];
            $_SESSION['form-select-fields']['rp_ext_col_nested_table-' . $i]       = $ext_col['nested'];
            $i++;
        }
    }

    /*=============================================
    =    Cascade delete + Bulk cascade delete     =
    =============================================*/

    // Look for other tables with foreign keys pointing to current one
    $constrained_from_to_relations = array();

    /* constrained_from_to_relations:
        array(
            'origin_table'
            'origin_column'
            'intermediate_table'
            'intermediate_column_1' // refers to origin_table
            'intermediate_column_2' // refers to target_table
            'target_table'
            'target_column',
            'cascade_delete' // true will automatically delete all matching records according to foreign keys constraints. Default: true
        )*/

    // Cascade delete - automatically delete all matching records according to foreign keys constraints (true|false)
    //
    // Current table is always the target.
    //
    // If External relation with intermediate table:
    //      origin_table ID <- [fk-origin + fk-target] -> target_table ID
    //      => We'll delete from [intermediate_table] THEN origin_table THEN target_table
    // else:
    //      fk-origin -> target_table ID
    //      => We'll delete from origin_table THEN target_table

    if (!isset($_SESSION['form-select-fields']['constrained_tables'])) {
        $_SESSION['form-select-fields']['constrained_tables'] = array();
    }
    if ($generator->relations !== null && is_array($generator->relations['from_to_target_tables']) && in_array($generator->table, $generator->relations['from_to_target_tables'])) {
        $index = 0;
        $constrained_from_to_relations_indexes = array();
        foreach ($generator->relations['from_to'] as $from_to) {
            if ($from_to['target_table'] == $generator->table) {
                $constrained_from_to_relations[] = $from_to;
                $constrained_from_to_relations_indexes[] = $index;
                if (!empty($from_to['intermediate_table'])) {
                    $field_name = 'constrained_tables_' . $from_to['intermediate_table'];
                    $_SESSION['form-select-fields'][$field_name] = $from_to['cascade_delete_from_intermediate'];
                    // bulk delete value
                    $bulk_field_name = 'bulk_constrained_tables_' . $from_to['intermediate_table'];
                    $_SESSION['form-select-fields'][$bulk_field_name] = $from_to['cascade_delete_from_intermediate'];
                }
                $field_name = 'constrained_tables_' . $from_to['origin_table'];
                $_SESSION['form-select-fields'][$field_name] = $from_to['cascade_delete_from_origin'];
                // bulk delete value
                $bulk_field_name = 'bulk_constrained_tables_' . $from_to['origin_table'];
                $_SESSION['form-select-fields'][$bulk_field_name] = $from_to['cascade_delete_from_origin'];

                $_SESSION['form-select-fields']['constrained_tables'][] = $from_to['origin_table'];
            }
            $index++;
        }
    }

    // filters
    if (is_countable($generator->list_options['filters'])) {
        for ($i = 0; $i < count($generator->list_options['filters']); $i++) {
            $filter = $generator->list_options['filters'][$i];
            $_SESSION['form-select-fields']['filter-mode-' . $i]            = $filter['filter_mode'];
            $_SESSION['form-select-fields']['filter_field_A-' . $i]         = $filter['filter_A'];
            $_SESSION['form-select-fields']['filter_select_label-' . $i]    = $filter['select_label'];
            $_SESSION['form-select-fields']['filter_option_text-' . $i]     = $filter['option_text'];
            $_SESSION['form-select-fields']['filter_fields-' . $i]          = $filter['fields'];
            $_SESSION['form-select-fields']['filter_field_to_filter-' . $i] = $filter['field_to_filter'];
            $_SESSION['form-select-fields']['filter_from-' . $i]            = $filter['from'];
            $_SESSION['form-select-fields']['filter_type-' . $i]            = $filter['type'];

            // default values if not set
            $_SESSION['form-select-fields']['filter-ajax-' . $i]      = false;
            $_SESSION['form-select-fields']['filter-daterange-' . $i] = false;

            // else
            if (isset($filter['ajax'])) {
                $_SESSION['form-select-fields']['filter-ajax-' . $i] = $filter['ajax'];
            }
            if (isset($filter['daterange'])) {
                $_SESSION['form-select-fields']['filter-daterange-' . $i] = $filter['daterange'];
            }
        }
    }

    // START 1st row
    $form_select_fields->startRowCol('row', 'col mb-4');

    // START 1st card
    $form_select_fields->startCard(SELECT_ACTION, '', $card_header_class);
    $form_select_fields->addInput('hidden', 'action');
    $form_select_fields->addHtml('<div class="row row-cols-1 row-cols-md-3 g-4 mb-3">
        <div class="col">
            <a href="#" class="choose-action-radio card text-bg-primary-500 text-decoration-none h-100" id="build_read">
                <div class="card-body d-flex flex-column justify-content-center">
                    <p class="h6 card-title text-center my-4"><span class="rounded-circle text-bg-primary-200"><i class="' . ICON_CHECKMARK . '"></i></span>' . BUILD . ' Read List</p>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="#" class="choose-action-radio card text-bg-secondary-700 text-decoration-none h-100" id="build_create_edit">
                <div class="card-body d-flex flex-column justify-content-center">
                    <p class="h6 card-title text-center my-4"><span class="rounded-circle text-bg-primary-200"><i class="' . ICON_CHECKMARK . '"></i></span>' . BUILD . ' Create / Update Forms</p>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="#" class="choose-action-radio card text-bg-secondary-700 text-decoration-none h-100" id="build_delete">
                <div class="card-body d-flex flex-column justify-content-center">
                    <p class="h6 card-title text-center my-4"><span class="rounded-circle text-bg-primary-200"><i class="' . ICON_CHECKMARK . '"></i></span>' . BUILD . ' Delete Form</p>
                </div>
            </a>
        </div>
    </div>');

    $form_select_fields->startDependentFields('action', 'build_read');
    $form_select_fields->optionsJustifyCenter();

    $paginated_checked     = ' checked';
    $single_record_checked = '';

    if ($generator->list_options['list_type'] == 'build_single_element_list') {
        $paginated_checked = '';
        $single_record_checked = ' checked';
    }

    $form_select_fields->addRadio('list_type', PAGINATED_LIST, 'build_paginated_list', $paginated_checked);
    $form_select_fields->addRadio('list_type', SINGLE_RECORD, 'build_single_element_list', $single_record_checked);
    $form_select_fields->printRadioGroup('list_type', CHOOSE_LIST_TYPE . SINGLE_RECORD_TIP, true, 'class=pb-4');
    $form_select_fields->endDependentFields();
    $form_select_fields->optionsRevert();
    // END 1st card
    $form_select_fields->endCard();

    // END 1st row
    $form_select_fields->endRowCol();

    /*__________ READ PAGINATED _________________*/

    $form_select_fields->startDependentFields('action', 'build_read');
    $form_select_fields->startDependentFields('list_type', 'build_paginated_list');
    $form_select_fields->startDiv('slide-div');

    $form_select_fields->startCard(SELECT_OPTIONS_FOR_PAGINATED_LIST, '', $card_active_header_class);

    $form_select_fields->startFieldset('<i class="fas fa-cogs ' . $legend_icon_color . ' prepend"></i><span class="' . $badge_class . ' prepend">' . $generator->table . '</span>' . MAIN_SETTINGS, 'class=mb-5', $legend_attr);

    /* $options = array(
        'horizontalLabelCol'       => 'col-md-4 col-lg-2',
        'horizontalElementCol'     => 'col-md-4 col-lg-4'
    );
    $form_select_fields->setOptions($options); */
    $form_select_fields->setCols(4, 2, 'md');
    $form_select_fields->groupElements('rp_open_url_btn', 'rp_export_btn');

    $form_select_fields->addCheckbox('rp_open_url_btn', '', 1, 'data-toggle=true, data-on-label=' . YES . ', data-off-label=' . NO . ', data-on-icon=' . ICON_CHECKMARK . ', data-off-icon=' . ICON_CANCEL . ', data-on-color=success-o');
    $doc_link = $form_select_fields->getDocLink('https://www.phpcrudgenerator.com/tutorials/how-to-customize-the-bootstrap-admin-data-tables#open-url-button');
    $form_select_fields->printCheckboxGroup('rp_open_url_btn', OPEN_URL_BUTTON . $doc_link, false);

    $form_select_fields->addCheckbox('rp_export_btn', '', 1, 'data-toggle=true, data-on-label=' . YES . ', data-off-label=' . NO . ', data-on-icon=' . ICON_CHECKMARK . ', data-off-icon=' . ICON_CANCEL . ', data-on-color=success-o');
    $form_select_fields->printCheckboxGroup('rp_export_btn', EXPORT_BUTTON, false);

    $form_select_fields->groupElements('rp_bulk_delete', 'rp_view_record');

    $form_select_fields->addCheckbox('rp_bulk_delete', '', 1, 'data-toggle=true, data-on-label=' . YES . ', data-off-label=' . NO . ', data-on-icon=' . ICON_CHECKMARK . ', data-off-icon=' . ICON_CANCEL . ', data-on-color=success-o');
    $form_select_fields->printCheckboxGroup('rp_bulk_delete', BULK_DELETE_BUTTON . BULK_DELETE_BUTTON_TIP, false);

    $form_select_fields->addCheckbox('rp_view_record', '', 1, 'data-toggle=true, data-on-label=' . YES . ', data-off-label=' . NO . ', data-on-icon=' . ICON_CHECKMARK . ', data-off-icon=' . ICON_CANCEL . ', data-on-color=success-o');
    $form_select_fields->printCheckboxGroup('rp_view_record', VIEW_RECORD_BUTTON . VIEW_RECORD_BUTTON_TIP, false);

    $form_select_fields->startDependentFields('rp_bulk_delete', 1);

    $index = 0;
    $done_tables = array();
    if (!empty($constrained_from_to_relations)) {
        $doc_link = $form_select_fields->getDocLink('https://www.phpcrudgenerator.com/tutorials/generate-bootstrap-admin-list-view#cascade-delete-option');
        $form_select_fields->startFieldset(CASCADE_DELETE_OPTIONS . $doc_link, 'class=px-3 py-2');
        $form_select_fields->setCols(4, 8, 'md');
        foreach ($constrained_from_to_relations as $from_to) {
            $form_select_fields->addInput('hidden', 'bulk_from_to_indexes[]', $constrained_from_to_relations_indexes[$index]);
            // if intermediate table
            if (!empty($from_to['intermediate_table']) && !in_array($from_to['intermediate_table'], $done_tables)) {
                $form_select_fields->addRadio('bulk_constrained_tables_' . $from_to['intermediate_table'], NO, 0);
                $form_select_fields->addRadio('bulk_constrained_tables_' . $from_to['intermediate_table'], YES, 1);
                $form_select_fields->printRadioGroup('bulk_constrained_tables_' . $from_to['intermediate_table'], DELETE_RECORDS_FROM . ' "' . $from_to['intermediate_table'] . '"', true, 'required');
                $done_tables[] = $from_to['intermediate_table'];
            }
            if (!in_array($from_to['origin_table'], $done_tables)) {
                $form_select_fields->addRadio('bulk_constrained_tables_' . $from_to['origin_table'], NO, 0);
                $form_select_fields->addRadio('bulk_constrained_tables_' . $from_to['origin_table'], YES, 1);
                $form_select_fields->printRadioGroup('bulk_constrained_tables_' . $from_to['origin_table'], DELETE_RECORDS_FROM . ' "' . $from_to['origin_table'] . '"', true, 'required');
                $done_tables[] = $from_to['origin_table'];
            }
            $index++;
        }
        $form_select_fields->endFieldset();
    }
    $form_select_fields->endDependentFields();

    $form_select_fields->setCols(4, 8, 'md');

    for ($i = 0; $i < $generator->columns_count; $i++) {
        $form_select_fields->addOption('rp_default_search_field', $generator->columns['name'][$i], $generator->columns['name'][$i]);
    }

    $form_select_fields->addSelect('rp_default_search_field', DEFAULT_SEARCH_FIELD, 'data-slimselect=true, data-allow-deselect=false');

    $form_select_fields->setCols(4, 6, 'md');
    $form_select_fields->groupElements('rp_order_by', 'rp_order_direction');
    for ($i = 0; $i < $generator->columns_count; $i++) {
        $form_select_fields->addOption('rp_order_by', $generator->columns['name'][$i], $generator->columns['name'][$i]);
    }
    $form_select_fields->addSelect('rp_order_by', ORDER_BY, 'data-slimselect=true, data-allow-deselect=false');
    $form_select_fields->setCols(0, -1, 'md');
    $form_select_fields->addOption('rp_order_direction', 'ASC', 'ASC');
    $form_select_fields->addOption('rp_order_direction', 'DESC', 'DESC');
    $form_select_fields->addSelect('rp_order_direction', '', 'data-slimselect=true, data-show-search=false, data-allow-deselect=false');

    $form_select_fields->endFieldset();
    $form_select_fields->startFieldset('<i class="fas fa-signature ' . $legend_icon_color . ' prepend"></i><span class="' . $badge_class . ' prepend">' . $generator->table . '</span>' . HUMAN_READABLE_NAMES, 'class=mb-5', $legend_attr);

    $form_select_fields->setCols(3, 3, 'md');
    $form_select_fields->addInput('text', 'rp_table_label', '', $generator->table, 'required');
    for ($i = 0; $i < $generator->columns_count; $i++) {
        if (Utils::pair($i) && $i + 1 < $generator->columns_count) {
            $form_select_fields->groupElements('rp_label_' . $generator->columns['name'][$i], 'rp_label_' . $generator->columns['name'][$i + 1]);
        }
        $form_select_fields->addInput('text', 'rp_label_' . $generator->columns['name'][$i], '', $generator->columns['name'][$i], 'required');
    }
    $form_select_fields->setCols(2, 4, 'md');

    // filters
    if (is_countable($generator->list_options['filters'])) {
        $list_options_filters_count = count($generator->list_options['filters']);
    } else {
        $list_options_filters_count = 0;
    }
    $form_select_fields->addInput('hidden', 'filters-dynamic-fields-index', $list_options_filters_count - 1);

    // Dynamic fields for filters - container + add button

    $form_select_fields->endFieldset();
    $form_select_fields->startFieldset('<i class="fas fa-filter ' . $legend_icon_color . ' prepend"></i><span class="' . $badge_class . ' prepend">' . $generator->table . '</span>' . FILTER_DROPDOWNS, '', $legend_attr);

    $form_select_fields->startCol(12, 'xs', 'py-3 text-center');
    $form_select_fields->addHtml('<button type="button" data-bs-toggle="collapse" data-bs-target="#filter-help" aria-expanded="false" aria-controls="filter-help" class="btn btn-sm btn-info dropdown-toggle dropdown-light">' . NEED_HELP . ' ?</button>');
    $form_select_fields->endCol();

    $form_select_fields->startCol(12, 'xs', 'mb-2 collapse', 'filter-help');
    $form_select_fields->addHtml(FILTER_HELP);
    $form_select_fields->endCol();

    $form_select_fields->startCol(12, 'xs'); // START col
    $form_select_fields->addHtml(FILTER_HELP_3);
    $form_select_fields->startDiv('', 'filters-ajax-elements-container'); // START ajax
    for ($i = 0; $i < $list_options_filters_count; $i++) {
        $form_select_fields->addFilterFields($generator->table, $generator->db_columns['name'], $generator->db_columns['type'], $i);
    }
    $form_select_fields->endDiv(); // END ajax

    $form_select_fields->startRowCol('row justify-content-end', 'col pt-4 pb-4');
    $form_select_fields->addHtml('<button type="button" class="btn btn-sm btn-primary filters-add-element-button float-right">' . ADD_FILTER . '<i class="' . ICON_PLUS . ' append"></i></button>');
    $form_select_fields->endRowCol();

    $form_select_fields->endCol(); // END col
    $form_select_fields->endFieldset();

    // values
    $form_select_fields->startFieldset('<i class="fas fa-database ' . $legend_icon_color . ' prepend"></i><span class="' . $badge_class . ' prepend">' . $generator->table . '</span>' . $generator->table . ' ' . FIELDS, '', $legend_attr);

    // values types arrays
    $int      = array('tinyint', 'smallint', 'mediumint', 'int', 'bigint');
    $decimal  = array('decimal', 'numeric', 'float', 'double', 'real');
    $boolean  = array('boolean');
    $date     = array('date', 'year');
    $datetime = array('datetime', 'timestamp');
    $time     = array('time');
    $string   = array('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext');
    $sets     = array('enum', 'set', 'json');


    for ($i = 0; $i < $generator->columns_count; $i++) {
        $uniqid = uniqid();
        $column_name = $generator->columns['name'][$i];

        $column_type = $generator->columns['column_type'][$i];
        $has_relation = false;
        $target_table = '';
        $relation_label = '';
        $column_primary        = $generator->columns['primary'][$i];
        $column_auto_increment = $generator->columns['auto_increment'][$i];
        $primary_badge = '';
        if ($column_primary) {
            $primary_badge = '<br><small class="badge text-bg-warning d-inline-flex align-items-center"><i class="' . ICON_KEY . ' text-warning-800 prepend"></i>primary</small>';
        }
        $ai_badge = '';
        if ($column_auto_increment) {
            $ai_badge = '<br><small class="badge border border-secondary-600 text-secondary-600 d-inline-flex align-items-center mt-1"><i class="' . ICON_PLUS . ' prepend"></i>auto-increment</small>';
        }
        // if one-to-many relation
        if (!empty($generator->columns['relation'][$i]['target_table'])) {
            $has_relation = true;
            $target_table = $generator->columns['relation'][$i]['target_table'];
            $relation_label = '<br><span class="badge border border-secondary-600 text-secondary-600 d-inline-flex align-items-center mt-1"><i class="' . ICON_TRANSMISSION . ' prepend"></i>' . $target_table . '</span>';
        }
        if ($i + 1 < $generator->columns_count) {
            $rc = $row_class;
        } else {
            $rc = $row_last_child_class;
        }
        $form_select_fields->startRow($rc); // START row
        $font_size_class = '';
        if (strlen($column_name) > 24) {
            $font_size_class = ' text-size-mini';
        } elseif (strlen($column_name) > 18) {
            $font_size_class = ' text-size-small';
        }
        $form_select_fields->addHtml('<label class="col-md-2 col-form-label' . $font_size_class . '">' . $column_name . $primary_badge . $ai_badge . $relation_label . '</label>');
        $form_select_fields->startCol(10, 'md'); // START col

        $form_select_fields->startDiv('skippable'); // START wrapper to hide skipped fields

        $form_select_fields->setCols(2, 4, 'md');

        // value type
        // boolean|color|date|datetime|time|file|image|number|password|set|text|url
        $form_select_fields->groupElements('rp_value_type_' . $column_name, 'rp_jedit_' . $column_name);
        if ($has_relation === true) {
            $form_select_fields->addInput('text', 'rp_value_type_' . $column_name, 'text', TYPE, 'readonly, class=input-sm');
        } elseif (in_array($column_type, $int) || in_array($column_type, $decimal)) {
            // if tinyInt, can be boolean
            if ($column_type == 'tinyint') {
                $form_select_fields->addOption('rp_value_type_' . $column_name, 'number', NUMBER);
                $form_select_fields->addOption('rp_value_type_' . $column_name, 'boolean', BOOLEAN_CONST);
                $form_select_fields->addSelect('rp_value_type_' . $column_name, TYPE, 'data-slimselect=true, data-allow-deselect=false');
            } else {
                $form_select_fields->addInput('text', 'rp_value_type_' . $column_name, 'number', TYPE, 'readonly, class=input-sm');
            }
        } elseif ($column_type == 'boolean') {
            $form_select_fields->addInput('text', 'rp_value_type_' . $column_name, 'boolean', TYPE, 'readonly, class=input-sm');
        } elseif (in_array($column_type, $date)) {
            $form_select_fields->addInput('text', 'rp_value_type_' . $column_name, 'date', TYPE, 'readonly, class=input-sm');
        } elseif (in_array($column_type, $datetime)) {
            $form_select_fields->addInput('text', 'rp_value_type_' . $column_name, 'datetime', TYPE, 'readonly, class=input-sm');
        } elseif (in_array($column_type, $time)) {
            $form_select_fields->addInput('text', 'rp_value_type_' . $column_name, 'time', TYPE, 'readonly, class=input-sm');
        } elseif (in_array($column_type, $string)) {
            if ($column_type == 'char' || $column_type == 'varchar' || $column_type == 'tinytext' || $column_type == 'text' || $column_type == 'mediumtext' || $column_type == 'longtext') {
                $form_select_fields->addOption('rp_value_type_' . $column_name, 'text', TEXT_NUMBER);
                $form_select_fields->addOption('rp_value_type_' . $column_name, 'html', HTML);
                $form_select_fields->addOption('rp_value_type_' . $column_name, 'select', SELECT_CONST);
                $form_select_fields->addOption('rp_value_type_' . $column_name, 'array', ARRAY_VALUE_TYPE);
                $form_select_fields->addOption('rp_value_type_' . $column_name, 'file', FILE);
                $form_select_fields->addOption('rp_value_type_' . $column_name, 'image', IMAGE);
                $form_select_fields->addOption('rp_value_type_' . $column_name, 'password', PASSWORD);
                $form_select_fields->addOption('rp_value_type_' . $column_name, 'color', COLOR);
                $form_select_fields->addOption('rp_value_type_' . $column_name, 'url', URL);
                $form_select_fields->addSelect('rp_value_type_' . $column_name, TYPE, 'data-slimselect=true, data-allow-deselect=false');
            } else {
                $form_select_fields->addInput('text', 'rp_value_type_' . $column_name, 'text', TYPE, 'readonly, class=input-sm');
            }
        } elseif (in_array($column_type, $sets)) {
            $form_select_fields->addInput('text', 'rp_value_type_' . $column_name, 'set', TYPE, 'readonly, class=input-sm');
        } else {
            // incompatible type
            $form_select_fields->addInput('text', 'rp_value_type_' . $column_name, 'incompatible_type', TYPE, 'readonly, class=input-sm text-bg-danger');
        }

        // edit in place
        $form_select_fields->addOption('rp_jedit_' . $column_name, false, DISABLED);
        $form_select_fields->addOption('rp_jedit_' . $column_name, 'text', TEXT_INPUT);
        $form_select_fields->addOption('rp_jedit_' . $column_name, 'textarea', TEXTAREA);
        $form_select_fields->addOption('rp_jedit_' . $column_name, 'boolean', BOOLEAN_CONST);
        $form_select_fields->addOption('rp_jedit_' . $column_name, 'select', SELECT_CONST);
        $form_select_fields->addOption('rp_jedit_' . $column_name, 'date', DATE_CONST);
        $form_select_fields->addSelect('rp_jedit_' . $column_name, EDIT_IN_PLACE, 'data-slimselect=true, data-allow-deselect=false');

        // "select" values
        if (in_array($column_type, $sets)) {
            $form_select_fields->startRowCol('row', 'col-md-offset-8 col-md-4 pt-4 pb-4');

            // show select values from generator data
            $select_values = $generator->getSelectValues($column_name);
            $form_select_fields->addHtml('<p>' . VALUES . ' : <span  id="rp_select-values-' . $column_name . '">' . $select_values . '</span></p>');

            $form_select_fields->endRowCol();

            $form_select_fields->setCols(8, 4, 'md');
            $form_select_fields->addBtn('button', 'rp_jedit_select_modal' . $column_name, '', ADD_EDIT_VALUES, 'class=btn btn-sm btn-success btn-sm mb-4, data-origin=rp_jedit, data-column=' . $column_name);
            $form_select_fields->setCols(2, 4, 'md');
        } else {
            //  Edit in place "select" values
            $form_select_fields->startDependentFields('rp_jedit_' . $column_name, 'select');
            $form_select_fields->startRowCol('row', 'col-md-offset-8 col-md-4 pt-4 pb-4');

            // show select values from generator data
            $select_values = $generator->getSelectValues($column_name);
            $form_select_fields->addHtml('<p>' . VALUES . ' : <span  id="rp_select-values-' . $column_name . '">' . $select_values . '</span></p>');

            $form_select_fields->endRowCol();

            $form_select_fields->setCols(8, 4, 'md');
            $form_select_fields->addBtn('button', 'rp_jedit_select_modal' . $column_name, '', ADD_EDIT_VALUES, 'class=btn btn-sm btn-success btn-sm mb-4, data-origin=rp_jedit, data-column=' . $column_name);
            $form_select_fields->setCols(2, 4, 'md');
            $form_select_fields->endDependentFields();
        }

        $form_select_fields->setCols(2, 10, 'md');

        // date
        if (in_array($column_type, $date)) {
            $placeholder = 'dddd dd mmm yyyy';
            if ($column_type == 'year') {
                $placeholder = 'yyyy';
            }
            $form_select_fields->startDiv('rp_special_date_wrapper mb-3');
            $form_select_fields->addHtml('<span class="form-text text-muted"><a href="#rp-date-format-helper' . $uniqid . '" class="date-format-helper-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="rp-date-format-helper' . $uniqid . '">' . DATE_HELPER . '</a></span>', 'rp_special_date_' . $column_name, 'after');
            $form_select_fields->addInput('text', 'rp_special_date_' . $column_name, '', DATE_DISPLAY_FORMAT . DATE_DISPLAY_TIP, 'placeholder=' . $placeholder . ', data-index=' . $i);
            $form_select_fields->endDiv();

            // datetime
        } elseif (in_array($column_type, $datetime)) {
            $form_select_fields->groupElements('rp_special_date_' . $column_name, 'rp_special_time_' . $column_name);
            $form_select_fields->startDiv('rp_special_date_wrapper mb-3');
            $form_select_fields->addHtml('<span class="form-text text-muted"><a href="#rp-date-format-helper' . $uniqid . '" class="date-format-helper-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="rp-date-format-helper' . $uniqid . '">' . DATE_HELPER . '</a></span>', 'rp_special_date_' . $column_name, 'after');
            $form_select_fields->addInput('text', 'rp_special_date_' . $column_name, '', DATE_DISPLAY_FORMAT . DATE_DISPLAY_TIP, 'placeholder=dddd dd mmm yyyy, data-index=' . $i);
            $form_select_fields->addHtml('<span class="form-text text-muted"><a href="#rp-time-format-helper' . $uniqid . '" class="time-format-helper-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="rp-time-format-helper' . $uniqid . '">' . DATE_HELPER . '</a></span>', 'rp_special_time_' . $column_name, 'after');
            $form_select_fields->addInput('text', 'rp_special_time_' . $column_name, '', TIME_DISPLAY_FORMAT . TIME_DISPLAY_TIP, 'placeholder=H:i a, data-index=' . $i);
            $form_select_fields->endDiv();

            // time
        } elseif (in_array($column_type, $time)) {
            $form_select_fields->startDiv('rp_special_date_wrapper mb-3');
            $form_select_fields->addHtml('<span class="form-text text-muted"><a href="#rp-time-format-helper' . $uniqid . '" class="time-format-helper-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="rp-time-format-helper' . $uniqid . '">' . DATE_HELPER . '</a></span>', 'rp_special_time_' . $column_name, 'after');
            $form_select_fields->addInput('text', 'rp_special_time_' . $column_name, '', TIME_DISPLAY_FORMAT . TIME_DISPLAY_TIP, 'placeholder=H:i a, data-index=' . $i);
            $form_select_fields->endDiv();
        } elseif (in_array($column_type, $string)) {
            if ($column_type == 'char' || $column_type == 'varchar' || $column_type == 'tinytext' || $column_type == 'text' || $column_type == 'mediumtext' || $column_type == 'longtext') {
                // file path & url
                $form_select_fields->startDependentFields('rp_value_type_' . $column_name, 'file');
                $form_select_fields->addAddon('rp_special_file_dir_' . $column_name, '[ROOT_PATH]/', 'before');
                $form_select_fields->addInput('text', 'rp_special_file_dir_' . $column_name, '', FILE_PATH . FILE_PATH_TIP, 'class=input-sm');
                $form_select_fields->addAddon('rp_special_file_url_' . $column_name, '[ROOT_URL]/', 'before');
                $form_select_fields->addInput('text', 'rp_special_file_url_' . $column_name, '', FILE_URL . FILE_URL_TIP, 'class=input-sm');
                $form_select_fields->addHelper(FILE_AUTHORIZED_HELPER, 'rp_special_file_types_' . $column_name);
                $form_select_fields->addInput('text', 'rp_special_file_types_' . $column_name, '', FILE_AUTHORIZED, 'class=input-sm');
                $form_select_fields->endDependentFields();

                // image path & url
                $form_select_fields->startDependentFields('rp_value_type_' . $column_name, 'image');
                $form_select_fields->addAddon('rp_special_image_dir_' . $column_name, '[ROOT_PATH]/', 'before');
                $form_select_fields->addInput('text', 'rp_special_image_dir_' . $column_name, '', IMAGE_PATH . IMAGE_PATH_TIP, 'class=input-sm');
                $form_select_fields->addAddon('rp_special_image_url_' . $column_name, '[ROOT_URL]/', 'before');
                $form_select_fields->addInput('text', 'rp_special_image_url_' . $column_name, '', IMAGE_URL . IMAGE_URL_TIP, 'class=input-sm');
                $form_select_fields->addRadio('rp_special_image_thumbnails_' . $column_name, NO, 0);
                $form_select_fields->addRadio('rp_special_image_thumbnails_' . $column_name, YES, 1);
                $form_select_fields->printRadioGroup('rp_special_image_thumbnails_' . $column_name, CREATE_IMAGE_THUMBNAILS . CREATE_IMAGE_THUMBNAILS_TIP);
                $form_select_fields->endDependentFields();

                // password constraints
                $form_select_fields->startDependentFields('rp_value_type_' . $column_name, 'password');
                $lower_char = mb_strtolower(LOWERCASE_CHARACTERS, 'UTF-8');
                $char       = mb_strtolower(CHARACTERS, 'UTF-8');

                $form_select_fields->addOption('rp_special_password_' . $column_name, 'min-3', MIN_3);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'min-4', MIN_4);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'min-5', MIN_5);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'min-6', MIN_6);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'min-7', MIN_7);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'min-8', MIN_8);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-min-3', LOWER_UPPER_MIN_3);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-min-4', LOWER_UPPER_MIN_4);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-min-5', LOWER_UPPER_MIN_5);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-min-6', LOWER_UPPER_MIN_6);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-min-7', LOWER_UPPER_MIN_7);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-min-8', LOWER_UPPER_MIN_8);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-number-min-3', LOWER_UPPER_NUMBER_MIN_3);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-number-min-4', LOWER_UPPER_NUMBER_MIN_4);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-number-min-5', LOWER_UPPER_NUMBER_MIN_5);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-number-min-6', LOWER_UPPER_NUMBER_MIN_6);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-number-min-7', LOWER_UPPER_NUMBER_MIN_7);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-number-min-8', LOWER_UPPER_NUMBER_MIN_8);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-number-symbol-min-3', LOWER_UPPER_NUMBER_SYMBOL_MIN_3);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-number-symbol-min-4', LOWER_UPPER_NUMBER_SYMBOL_MIN_4);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-number-symbol-min-5', LOWER_UPPER_NUMBER_SYMBOL_MIN_5);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-number-symbol-min-6', LOWER_UPPER_NUMBER_SYMBOL_MIN_6);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-number-symbol-min-7', LOWER_UPPER_NUMBER_SYMBOL_MIN_7);
                $form_select_fields->addOption('rp_special_password_' . $column_name, 'lower-upper-number-symbol-min-8', LOWER_UPPER_NUMBER_SYMBOL_MIN_8);
                $form_select_fields->addSelect('rp_special_password_' . $column_name, PASSWORD_CONSTRAINT, 'data-slimselect=true, data-allow-deselect=false');
                $form_select_fields->endDependentFields();
            }
        }
        if (in_array($column_type, $date) || in_array($column_type, $datetime)) {
            // date format helper
            $form_select_fields->setCols(2, 10, 'md');
            $form_select_fields->startDiv('collapse', 'rp-date-format-helper' . $uniqid);
            $form_select_fields->addHtml('<table class="table small date-table"> <thead> <tr> <th>Rule</th> <th>Description</th> <th>Result</th> </tr> </thead> <tbody> <tr> <td><code>d</code></td> <td>Date of the month</td> <td>1 – 31</td> </tr> <tr> <td><code>dd</code></td> <td>Date of the month with a leading zero</td> <td>01 – 31</td> </tr> <tr> <td><code>ddd</code></td> <td>Day of the week in short form</td> <td>Sun – Sat</td> </tr> <tr> <td><code>dddd</code></td> <td>Day of the week in full form</td> <td>Sunday – Saturday</td> </tr> </tbody> <tbody> <tr> <td><code>m</code></td> <td>Month of the year</td> <td>1 – 12</td> </tr> <tr> <td><code>mm</code></td> <td>Month of the year with a leading zero</td> <td>01 – 12</td> </tr> <tr> <td><code>mmm</code></td> <td>Month name in short form</td> <td>Jan – Dec</td> </tr> <tr> <td><code>mmmm</code></td> <td>Month name in full form</td> <td>January – December</td> </tr> </tbody> <tbody> <tr> <td><code>yy</code></td> <td>Year in short form <b>*</b></td> <td>00 – 99</td> </tr> <tr> <td><code>yyyy</code></td> <td>Year in full form</td> <td>2000 – 2999</td> </tr> </tbody> </table>');
            $form_select_fields->endDiv('</div>');
        }

        // time format helper
        if (in_array($column_type, $time) || in_array($column_type, $datetime)) {
            $form_select_fields->startDiv('collapse', 'rp-time-format-helper' . $uniqid);
            $form_select_fields->addHtml('<table class="table small time-table"> <thead> <tr> <th>Rule</th> <th>Description</th> <th>Result</th> </tr> </thead> <tbody> <tr> <td><code>h</code></td> <td>Hour in 12-hour format</td> <td>1 – 12</td> </tr> <tr> <td><code>hh</code></td> <td>Hour in 12-hour format with a leading zero</td> <td>01 – 12</td> </tr> <tr> <td><code>H</code></td> <td>Hour in 24-hour format</td> <td>0 – 23</td> </tr> <tr> <td><code>HH</code></td> <td>Hour in 24-hour format with a leading zero</td> <td>00 – 23</td> </tr> </tbody> <tbody> <tr> <td><code>i</code></td> <td>Minutes</td> <td>00 – 59</td> </tr> </tbody> <tbody> <tr> <td><code>a</code></td> <td>Day time period</td> <td>a.m. / p.m.</td> </tr> <tr> <td><code>A</code></td> <td>Day time period in uppercase</td> <td>AM / PM</td> </tr> </tbody> </table>');
            $form_select_fields->endDiv();
        }

        if ($has_relation === true) {
            // get fields from target table
            $columns = $db->getColumnsNames($target_table);
            $columns_count = $db->rowCount();

            // none value available for 2nd field only
            $form_select_fields->addOption('rp_target_column_1_' . $column_name, '', NONE);
            if (!empty($columns_count)) {
                foreach ($columns as $field) {
                    $form_select_fields->addOption('rp_target_column_0_' . $column_name, $field, $field);
                    $form_select_fields->addOption('rp_target_column_1_' . $column_name, $field, $field);
                }
            }

            // look for another target table if the first target table has a relation to another.
            // e.g: address.city_id => city.id => city.country_id => country.id, country.name, ...
            if (in_array($target_table, $generator->relations['all_db_related_tables'])) {
                foreach ($generator->relations['from_to'] as $ft) {
                    if ($ft['origin_table'] === $target_table && empty($ft['intermediate_table'])) {
                        $second_target_table = $ft['target_table'];
                        $columns = $db->getColumnsNames($second_target_table);
                        foreach ($columns as $field) {
                            $field_prefixed = $second_target_table . '.' . $field;
                            $form_select_fields->addOption('rp_target_column_0_' . $column_name, $field_prefixed, $field_prefixed);
                            $form_select_fields->addOption('rp_target_column_1_' . $column_name, $field_prefixed, $field_prefixed);
                        }
                    }
                }
            }

            $form_select_fields->addSelect('rp_target_column_0_' . $column_name, DISPLAY_VALUE . ' 1', 'data-slimselect=true, data-allow-deselect=false');
            $form_select_fields->addSelect('rp_target_column_1_' . $column_name, DISPLAY_VALUE . ' 2', 'data-slimselect=true');
        }

        $form_select_fields->endDiv(); // END wrapper to hide skipped fields

        // others
        $form_select_fields->setCols(2, 10, 'md');
        $form_select_fields->addRadio('rp_others_' . $column_name, NONE, '');
        $form_select_fields->addRadio('rp_others_' . $column_name, ENABLE_SORTING, 'sorting');
        $form_select_fields->addRadio('rp_others_' . $column_name, NESTED_TABLE, 'nested');
        $form_select_fields->addRadio('rp_others_' . $column_name, '<span class="text-muted">' . SKIP_THIS_FIELD . '</span>', 'skip');
        $form_select_fields->printRadioGroup('rp_others_' . $column_name, OPTIONS);

        $form_select_fields->endCol(); // END col
        $form_select_fields->endRow(); // END row
    }

    $form_select_fields->endFieldset();

    // external relations
    if ($external_columns_count > 0) {
        $form_select_fields->startFieldset('<i class="' . ICON_TRANSMISSION . ' ' . $legend_icon_color . ' prepend"></i><span class="' . $badge_class . ' prepend">' . $generator->table . '</span>' . EXTERNAL_RELATIONS, '', $legend_attr);
        $i = 0;

        /*
        $ext_col             = array(
            'target_table'       => array(),
            'target_fields'      => array(),
            'name'               => array(),
            'label'              => array(),
            'allow_crud_in_list' => array(),
            'allow_in_forms'     => array(),
            'forms_fields'       => array(),
            'field_type'         => array(), // 'select-multiple' | 'checkboxes'
            'active'             => array()
        );
         */

        foreach ($generator->external_columns as $key => $ext_col) {
            // var_dump($ext_col);
            $origin_table       = $ext_col['relation']['origin_table'];
            $intermediate_table = $ext_col['relation']['intermediate_table'];
            $target_table       = $ext_col['relation']['target_table'];

            $form_select_fields->startRow(); // START row
            if (!empty($intermediate_table)) {
                // many to many
                $form_select_fields->addHtml('<label class="col-md-4 col-form-label">' . $target_table . '<br><small class="text-muted">(' . $origin_table . ' => ' . $intermediate_table . ' => ' . $target_table . ')</small></label>');
            } else {
                // one to many with the current table as target
                $form_select_fields->addHtml('<label class="col-md-4 col-form-label">' . $origin_table . '<br><small class="text-muted">(' . $origin_table . ' => ' . $target_table . ')</small></label>');
            }
            $form_select_fields->startCol(8, 'md'); // START col

            $form_select_fields->startRowCol();
            $form_select_fields->setCols(4, 8, 'md');
            $form_select_fields->addRadio('rp_ext_col_target_table-' . $i, NO, 0);
            $form_select_fields->addRadio('rp_ext_col_target_table-' . $i, YES, 1);
            $form_select_fields->printRadioGroup('rp_ext_col_target_table-' . $i, ENABLE, true, 'required');

            $form_select_fields->startDependentFields('rp_ext_col_target_table-' . $i, 1);
            $columns = $db->getColumnsNames($ext_col['target_table']);
            $columns_count = $db->rowCount();
            if (!empty($columns_count)) {
                foreach ($columns as $field) {
                    $form_select_fields->addOption('rp_ext_col_target_fields-' . $i . '[]', $field, $field);
                }
                $form_select_fields->addSelect('rp_ext_col_target_fields-' . $i . '[]', FIELDS_TO_DISPLAY, 'data-slimselect=true, data-close-on-select=false,multiple, required');

                $form_select_fields->addRadio('rp_ext_col_allow_crud_in_list-' . $i, NO, 0);
                $form_select_fields->addRadio('rp_ext_col_allow_crud_in_list-' . $i, YES, 1);
                $form_select_fields->printRadioGroup('rp_ext_col_allow_crud_in_list-' . $i, ALLOW_CRUD_IN_LIST, true, 'required');
                if (!empty($intermediate_table)) {
                    // many-to-many - choose if the action buttons lead to the intermediate or the target table
                    $form_select_fields->startDependentFields('rp_ext_col_allow_crud_in_list-' . $i, 1);
                    $form_select_fields->addRadio('rp_ext_col_action_btns_target_table-' . $i, $intermediate_table, $intermediate_table);
                    $form_select_fields->addRadio('rp_ext_col_action_btns_target_table-' . $i, $target_table, $target_table);
                    $form_select_fields->printRadioGroup('rp_ext_col_action_btns_target_table-' . $i, ACTION_BUTTONS_TARGET, true, 'required');
                    $form_select_fields->endDependentFields();
                }
               // nested table
                $form_select_fields->addRadio('rp_ext_col_nested_table-' . $i, NO, 0);
                $form_select_fields->addRadio('rp_ext_col_nested_table-' . $i, YES, 1);
                $form_select_fields->printRadioGroup('rp_ext_col_nested_table-' . $i, NESTED_TABLE);
            }
            $form_select_fields->endDependentFields();
            $form_select_fields->endCol();
            $form_select_fields->endRow();
            $form_select_fields->endRowCol();

            // help dropdown
            if ($i + 1 < $external_columns_count) {
                $rc = $row_class;
            } else {
                $rc = $row_last_child_class;
            }

            $form_select_fields->startRowCol($rc); // START row
            $form_select_fields->setCols(0, 12, 'justify-content-end', 'md');

            if (!empty($intermediate_table)) {
                // many to many
                $find = array('%origin_table%', '%intermediate_table%', '%target_table%');
                $replace = array($origin_table, $intermediate_table, $target_table);
                $helper_text = str_replace($find, $replace, EXPLAIN_RELATION_MANY_TO_MANY);
            } else {
                // one to many with the current table as target
                $find = array('%origin_table%', '%target_table%');
                $replace = array($origin_table, $target_table);
                $helper_text = str_replace($find, $replace, EXPLAIN_RELATION_ONE_TO_MANY);
            }
            $form_select_fields->addBtn('button', 'rp_ext_col_helper_btn_' . $i, 1, EXPLAIN_RELATION, 'data-bs-toggle=collapse, data-bs-target=#rp_ext_col_helper_' . $i . ', aria-expanded=false, aria-controls=rp_ext_col_helper_' . $i . ', class=btn btn-sm btn-info mt-2 mt-md-0 dropdown-toggle dropdown-light');
            $form_select_fields->startCol(-1, 'xs', 'mb-2 collapse', 'rp_ext_col_helper_' . $i);
            $form_select_fields->addHtml($helper_text);
            $form_select_fields->endCol();

            $form_select_fields->endRowCol();
            $i++;
        }
        $form_select_fields->endFieldset();
    }

    $form_select_fields->endCard();

    $form_select_fields->endDiv(); // END slide-div
    $form_select_fields->endDependentFields(); // END build_paginated_list
    $form_select_fields->endDependentFields(); // END build_read

    /*__________ CREATE | UPDATE _________________*/

    $form_select_fields->startDependentFields('action', 'build_create_edit');
    $form_select_fields->endDependentFields();

    /*__________ READ SINGLE _________________*/

    $form_select_fields->startDependentFields('action', 'build_read');
    $form_select_fields->startDependentFields('list_type', 'build_single_element_list');
    $form_select_fields->endDependentFields(); // END build_single_element_list
    $form_select_fields->endDependentFields(); // END build_read

    /*__________ DELETE _________________*/

    $form_select_fields->startDependentFields('action', 'build_delete');
    $form_select_fields->startDiv('slide-div');
    $form_select_fields->startCard(SELECT_OPTIONS_FOR_DELETE_FORM, 'card', 'card-header ' . $card_active_header_class);
    $form_select_fields->setCols(4, 8, 'md');

    foreach ($generator->columns['name'] as $column_name) {
        $form_select_fields->addOption('field_delete_confirm_1', $column_name, $column_name);
    }
    $form_select_fields->addSelect('field_delete_confirm_1', FIELD_DELETE_CONFIRM, 'data-slimselect=true, data-allow-deselect=false');

    $form_select_fields->addOption('field_delete_confirm_2', '', NONE);
    foreach ($generator->columns['name'] as $column_name) {
        $form_select_fields->addOption('field_delete_confirm_2', $column_name, $column_name);
    }
    $form_select_fields->addSelect('field_delete_confirm_2', FIELD_DELETE_CONFIRM . ' (n°2)', 'data-slimselect=true');
    $form_select_fields->addHtml(FIELD_DELETE_CONFIRM_HELP);


    $index = 0;
    $done_tables = array();
    foreach ($constrained_from_to_relations as $from_to) {
        $form_select_fields->addInput('hidden', 'from_to_indexes[]', $constrained_from_to_relations_indexes[$index]);
        // if intermediate table
        if (!empty($from_to['intermediate_table']) && !in_array($from_to['intermediate_table'], $done_tables)) {
            $form_select_fields->addRadio('constrained_tables_' . $from_to['intermediate_table'], NO, 0);
            $form_select_fields->addRadio('constrained_tables_' . $from_to['intermediate_table'], YES, 1);
            $form_select_fields->printRadioGroup('constrained_tables_' . $from_to['intermediate_table'], DELETE_RECORDS_FROM . ' "' . $from_to['intermediate_table'] . '"');
            $done_tables[] = $from_to['intermediate_table'];
        }
        if (!in_array($from_to['origin_table'], $done_tables)) {
            $form_select_fields->addRadio('constrained_tables_' . $from_to['origin_table'], NO, 0);
            $form_select_fields->addRadio('constrained_tables_' . $from_to['origin_table'], YES, 1);
            $form_select_fields->printRadioGroup('constrained_tables_' . $from_to['origin_table'], DELETE_RECORDS_FROM . ' "' . $from_to['origin_table'] . '"');
            $done_tables[] = $from_to['origin_table'];
        }
        $index++;
    }

    $form_select_fields->endCard(); // END card
    $form_select_fields->endDiv(); // END slide-div
    $form_select_fields->endDependentFields();

    $form_select_fields->addHtml('<p>&nbsp;</p>');
    $form_select_fields->setCols(0, 12, 'md');
    $form_select_fields->centerContent();
    $form_select_fields->addBtn('button', 'form-select-fields-submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-lg btn-success, data-ladda-button=true, data-style=zoom-in');

    // Custom radio & checkbox css
    $options = [
        'icon'        => 'fas fa-check me-2 text-success',
        'plain'       => 'plain',
        'size'        => 'bigger',
        'animations'  => 'smooth'
    ];
    $form_select_fields->addPlugin('pretty-checkbox', '#form-select-fields', 'default', $options);
    $form_select_fields->addPlugin('formvalidation', '#form-select-fields', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
}

/* ===========================================================
Get admin current state (locked|unlocked)
Get current Secure state ($secure_installed = true|false|)
Generate corresponding heading elements
=========================================================== */
$secure_installed = false;
if (file_exists(ADMIN_DIR . 'secure/install/install.lock')) {
    $secure_installed = true;

    $form_lock_unlock_admin = new Form('lock-unlock-admin', 'horizontal', '');
    $form_lock_unlock_admin->useLoadJs('core');
    $form_lock_unlock_admin->setMode('development');
    $form_lock_unlock_admin->setAction($_SERVER["REQUEST_URI"]);
    if ($generator->authentication_module_enabled !== true) {
        $form_lock_unlock_admin->addInput('hidden', 'lock-admin', 1);
    } else {
        $form_lock_unlock_admin->addInput('hidden', 'unlock-admin', 1);
    }
}

// open the appropriate pill
$pill_crud_aria_selected = 'true';
$pill_crud_active = ' active';
$pill_authentication_aria_selected = 'false';
$pill_authentication_active = '';
if (isset($_POST['lock-unlock-admin'])) {
    $pill_crud_aria_selected = 'false';
    $pill_crud_active = '';
    $pill_authentication_aria_selected = 'true';
    $pill_authentication_active = ' active';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP CRUD - Admin Panel Generator</title>
    <meta name="description" content="This CRUD Generator connects to your database and analyses the tables, fields, and relational structure. It allows you to generate in a few clicks a complete and professional admin dashboard">
    <link href="https://www.phpcrudgenerator.com/crud-generator-demo" rel="canonical">
    <link rel="preload" href="<?php echo ADMIN_URL; ?>assets/stylesheets/pace-theme-minimal.min.css" as="style" onload="this.rel='stylesheet'">
    <link rel="preload" href="<?php echo ADMIN_URL; ?>assets/stylesheets/themes/default/bootstrap.min.css" as="style" onload="this.rel='stylesheet'">
    <link rel="preload" href="<?php echo GENERATOR_URL; ?>generator-assets/stylesheets/generator.min.css" as="style" onload="this.rel='stylesheet'">
    <noscript>
        <link type="text/css" media="screen" rel="stylesheet" href="<?php echo ADMIN_URL; ?>assets/stylesheets/pace-theme-minimal.min.css">
        <link type="text/css" media="screen" rel="stylesheet" href="<?php echo ADMIN_URL; ?>assets/stylesheets/themes/default/bootstrap.min.css">
        <link type="text/css" media="screen" rel="stylesheet" href="<?php echo GENERATOR_URL; ?>generator-assets/stylesheets/generator.min.css">
    </noscript>
    <script> (function(w){ "use strict"; if (!w.loadCSS){ w.loadCSS=function(){}} var rp=loadCSS.relpreload={}; rp.support=(function(){ var ret; try{ ret=w.document.createElement("link").relList.supports("preload")} catch (e){ ret=!1} return function(){ return ret}})(); rp.bindMediaToggle=function(link){ var finalMedia=link.media || "all"; function enableStylesheet(){ link.media=finalMedia} if (link.addEventListener){ link.addEventListener("load", enableStylesheet)} else if (link.attachEvent){ link.attachEvent("onload", enableStylesheet)} setTimeout(function(){ link.rel="stylesheet"; link.media="only x"}); setTimeout(enableStylesheet, 3000)}; rp.poly=function(){ if (rp.support()){ return} var links=w.document.getElementsByTagName("link"); for (var i=0; i < links.length; i++){ var link=links[i]; if (link.rel==="preload" && link.getAttribute("as")==="style" && !link.getAttribute("data-loadcss")){ link.setAttribute("data-loadcss", !0); rp.bindMediaToggle(link)}}}; if (!rp.support()){ rp.poly(); var run=w.setInterval(rp.poly, 500); if (w.addEventListener){ w.addEventListener("load", function(){ rp.poly(); w.clearInterval(run)})} else if (w.attachEvent){ w.attachEvent("onload", function(){ rp.poly(); w.clearInterval(run)})}} if (typeof exports !=="undefined"){ exports.loadCSS=loadCSS} else{ w.loadCSS=loadCSS}}(typeof global !=="undefined" ? global : this)) </script>
    <style></style>
</head>
<body>
    <?php

    // hidden form to lock|unlock admin
    if (isset($form_lock_unlock_admin)) {
        $form_lock_unlock_admin->render();
    }
    ?>
    <?php
    // phpcrudgenerator.com navbar for demo website
    if (defined('IS_PHPCRUDGENERATOR_COM')) {
        include_once 'inc/navbar-main.php';
    }
    ?>
    <header class="align-items-center d-flex justify-content-between p-3 text-bg-secondary-800">
        <h1 class="h5 text-start mb-0 opacity-50"><i class="fas fa-wrench fa-lg me-3"></i>CRUD Generator</h1>
        <p id="header-db-name" class="h2 mb-0"><?php echo DATABASE_CONST . ' <em>' . $generator->database . '</em>'; ?></p>
        <p class="mb-0 opacity-50"><?php include_once 'update/index.php'; ?></p>
    </header>

    <nav class="navbar navbar-expand-md text-bg-info-200 shadow-sm py-2" aria-label="Generator main menu">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-navbar-nav" aria-controls="main-navbar-nav" aria-expanded="false" aria-label="Toggle navigation"> <span class="navbar-toggler-icon text-info-800"></span>
            </button>
            <div class="collapse navbar-collapse" id="main-navbar-nav">
                <ul class="navbar-nav nav-pills me-auto" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link px-3<?php echo $pill_crud_active ?>" id="pills-crud-tab" data-bs-toggle="tab" data-bs-target="#pills-crud" href="#" role="tab" aria-controls="pills-crud" aria-selected="<?php echo $pill_crud_aria_selected; ?>"><span class="text-nowrap"><?php echo CRUD_GENERATOR; ?></span> <i class="fa-solid fa-wand-magic-sparkles append"></i></a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link px-3" id="pills-configuration-tab" data-bs-toggle="tab" data-bs-target="#pills-configuration" href="#" role="tab" data-form="configurationForm" aria-controls="pills-configuration" aria-selected="false"><span class="text-nowrap"><?php echo CONFIGURATION; ?></span> <i class="fa-solid fa-sliders append"></i></a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link px-3" id="pills-admin-navbar-tab" data-bs-toggle="tab" data-bs-target="#pills-admin-navbar" href="#" role="tab" aria-controls="pills-admin-navbar" aria-selected="false"><span class="text-nowrap"><?php echo ADMIN_NAVBAR; ?></span> <i class="fa-solid fa-sitemap append"></i></a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link px-3" id="pills-compare-merge-tab" data-bs-toggle="tab" data-bs-target="#pills-compare-merge" href="#" role="tab" aria-controls="pills-compare-merge" aria-selected="false"><span class="text-nowrap"><?php echo COMPARE . '/' . MERGE; ?></span> <i class="fa-solid fa-code-compare append"></i></a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link px-3<?php echo $pill_authentication_active; ?>" id="pills-authentication-tab" data-bs-toggle="tab" data-bs-target="#pills-authentication" href="#" role="tab" aria-controls="pills-authentication" aria-selected="<?php echo $pill_authentication_aria_selected; ?>"><span class="text-nowrap"><?php echo AUTHENTICATION_MODULE; ?></span> <i class="fa-solid fa-lock append"></i></a>
                    </li>
                </ul>
                <ul class="navbar-nav nav-pills ms-auto" role="tablist">
                    <?php
                    if (ENVIRONMENT !== 'localhost' && GENERATOR_LOCKED) {
                        // Logout button
                    ?>
                    <li class="nav-item" role="presentation">
                        <a href="logout.php" class="btn btn-outline-secondary btn-sm ms-auto px-3 shadow-none" title="<?php echo LOGOUT; ?>"><?php echo LOGOUT; ?><i class="<?php echo ICON_LOGOUT; ?> append"></i></a>
                    </li>
                    <?php
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php
    if (!empty($generator->database)) {
        ?>
    <nav id="pills-crud-nav" class="d-flex justify-content-end p-2 mb-4" aria-label="CRUD tools menu">
        <button type="button" class="btn btn-sm btn-light me-2 modal-trigger" data-target="database-relations-modal">
            <?php echo DISPLAY . ' ' . $generator->database . ' ' . RELATIONS; ?><i class="<?php echo ICON_VIEW; ?> append"></i>
        </button>
        <div class="dropdown">
            <button class="btn btn-sm btn-warning dropdown-toggle me-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="<?php echo ICON_REFRESH; ?> prepend"></i><?php echo REFRESH ?>
            </button>
            <ul class="dropdown-menu">
                <li><?php $form_reset_relations->render(); ?></li>
                <li><a href="#" id="reload-db-structure-link" class="dropdown-item d-flex justify-content-between" data-bs-toggle="tooltip" data-bs-title="<?php echo REFRESH_DB_STRUCTURE_HELPER; ?>" data-toggle-loader="true"><?php echo REFRESH_DB_STRUCTURE ?><i class="<?php echo ICON_DATABASE; ?> append"></i></a></li>
            </ul>
        </div>
        <button type="button" name="btn-reinstall" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" data-bs-title="<?php echo REINSTALL_TIP; ?>">
            <?php echo REINSTALL; ?><i class="<?php echo ICON_DELETE; ?> append"></i>
        </button>
    </nav>
        <?php
    }
    ?>
        <?php
        if (isset($_SESSION['msg'])) {
            echo '<div id="user-msg-container" class="container py-4">';
            if (!strpos($_SESSION['msg'], LOGIN_ERROR)) {
                echo $_SESSION['msg'];
            }
            echo '</div>';
            unset($_SESSION['msg']);
        }
        ?>

    <div class="container tab-content">

        <!-- pills-crud -->
        <div class="tab-pane<?php echo $pill_crud_active; ?>" id="pills-crud" role="tabpanel" aria-labelledby="pills-crud-tab" tabindex="0">
            <?php
        if (DEMO === true) {
            ?>
            <div class="container">
                <div class="row justify-content-md-center">
                    <div class="col-md-10">
                        <div class="alert alert-info has-icon shadow-sm">
                            <p class="h2 alert-heading">PHP CRUD generator - Create your Bootstrap Admin Panel from here</p>
                            <p class="font-weight-bold">You are here in the PHP CRUD generator, which allows you to generate your <a href="https://www.phpcrudgenerator.com/admin/home">Bootstrap admin panel</a> from any MySQL/MariaDB, Oracle, PostgreSQL, Firebird database in a few clicks.</p>
                            <hr>
                            <ol>
                                <li class="font-weight-bold mb-2">Select the table that you want to add in your admin panel.</li>
                                <li class="font-weight-bold mb-2">To create your CRUD pages, click one of the 3 big buttons:
                                    <div class="d-lg-flex my-1">
                                        <a href="#form-select-fields" class="btn btn-sm d-block d-lg-flex text-bg-primary text-decoration-none me-lg-3 mb-2">Build READ List</a>
                                        <a href="#form-select-fields" class="btn btn-sm d-block d-lg-flex text-bg-primary text-decoration-none me-lg-3 mb-2">Build Create / Update Forms</a>
                                        <a href="#form-select-fields" class="btn btn-sm d-block d-lg-flex text-bg-primary text-decoration-none mb-2">Build Delete Form</a>
                                    </div>
                                </li>
                                <li class="font-weight-bold">Choose your options for each field then confirm at the bottom of the page</li>
                            </ol>
                            <p class="d-block d-lg-flex justify-content-between py-3">
                                <button class="btn btn-lg w-100 mb-2 mb-lg-0 text-bg-danger" title="Switch to the CRUD Generator" disabled><i class="<?php echo ICON_ARROW_LEFT; ?> prepend"></i>Switch to the CRUD Generator</button>
                                <a href="/admin/home" class="btn btn-lg w-100 text-bg-danger text-decoration-none" title="Switch to the Bootstrap Admin Dashboard">Switch to the Admin Dashboard<i class="<?php echo ICON_ARROW_RIGHT; ?> append"></i></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        if (isset($form_select_table)) {
            ?>
            <div class="row justify-content-md-center">
                <div class="col-md-10 mt-4 mb-5">
                    <div class="card">
                        <div class="card-header <?php echo $card_header_class; ?>"><?php echo CHOOSE_TABLE; ?></div>
                        <div class="card-body">
                            <div class="row justify-content-md-center align-items-end">
                                <div class="col-12">
                                    <div class="card-text d-flex justify-content-center mb-4 mb-lg-0">
                                        <?php $form_select_table->render(); ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="card-text d-flex justify-content-end">
                                        <?php $form_reset_table->render(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        if (DEMO === true) {
            ?>
            <div class="alert alert-info has-icon">
                <p class="mb-0">All the CRUD operations are disabled in this demo.</p>
            </div>
            <?php
        }
        if (isset($form_select_fields)) {
            $form_select_fields->render();
        }
            ?>
        </div>

        <!-- pills-configuration -->

        <div class="tab-pane" id="pills-configuration" role="tabpanel" aria-labelledby="pills-configuration-tab" tabindex="0">
            <div class="container">
                <h2 class="h1 text-center my-4">PHP CRUD Generator - <?php echo CONFIGURATION; ?></h2>
                <?php
                if (DEMO === true) {
                ?>
                <div class="alert alert-info has-icon">
                    <p class="mb-0 h4">The <em>Configuration</em> form is disabled in this demo.</p>
                </div>
                <?php
                }
                ?>
                <div id="ajax-configuration-form-container"></div>
            </div>
        </div>

        <!-- pills-admin-navbar -->

        <div class="tab-pane" id="pills-admin-navbar" role="tabpanel" aria-labelledby="pills-admin-navbar-tab" tabindex="0"></div>

        <!-- pills-compare-merge -->

        <div class="tab-pane" id="pills-compare-merge" role="tabpanel" aria-labelledby="pills-compare-merge-tab" tabindex="0">
            <div class="container">
                <h2 class="h1 text-center my-4">PHP CRUD Generator - <?php echo DIFF_FILES; ?></h2>
                <?php
                if (DEMO === true) {
                ?>
                <div class="alert alert-info has-icon">
                    <p class="mb-0 h4">The <em>file comparison tool</em> is disabled in this demo.</p>
                </div>
                <?php
                }
                ?>
                <div id="ajax-diff-files-form-container"></div>
            </div>
        </div>

        <!-- pills-authentication -->

        <div class="tab-pane<?php echo $pill_authentication_active; ?>" id="pills-authentication" role="tabpanel" aria-labelledby="pills-authentication-tab" tabindex="0">
            <div class="container">
                <div class="row justify-content-md-center mb-4">

                    <h2 class="h1 text-center my-4">PHP CRUD Generator - <?php echo AUTHENTICATION_MODULE; ?></h2>

                    <?php
                    if ($secure_installed !== true) {
                        // Install auth. module button
                        ?>
                    <div id="authentication-module-installer">
                        <div class="alert alert-info has-icon mb-4">
                            <p><?php echo ADMIN_AUTHENTICATION_MODULE_HELPER; ?></p>
                            <hr>
                            <a href="https://www.phpcrudgenerator.com/documentation/index#admin-user-authentication-module" class="text-info-800" target="_blank" rel="noreferer noopener"><?php echo DOC; ?></a>
                        </div>
                        <p id="auth-not-installed" class="lead text-center fs-2 mb-5"><i class="<?php echo ICON_LOGIN; ?> text-danger-600 prepend"></i><?php echo ADMIN_AUTHENTICATION_MODULE_IS_NOT_INSTALLED; ?></p>
                        <div class="d-flex justify-content-center p-md-4">
                            <button type="button" id="install-authentication-module-btn" data-admin-url="<?php echo ADMIN_URL; ?>" class="btn btn-lg btn-primary"><?php echo INSTALL_ADMIN_AUTHENTICATION_MODULE; ?><i class="fas fa-magic append"></i><</button>
                        </div>
                    </div>
                    <?php
                    } else {
                        if ($generator->authentication_module_enabled !== true) {
                            // Lock admin button
                        ?>

                    <p class="lead text-center fs-2 mb-5"><i class="<?php echo ICON_UNLOCK; ?> text-danger-600 prepend"></i><?php echo ADMIN_AUTHENTICATION_MODULE_IS_DISABLED; ?></p>

                    <div class="d-flex justify-content-around p-md-4">
                        <a class="btn btn-lg btn-danger" href="#" id="remove-authentication-module"><?php echo REMOVE; ?><i class="<?php echo ICON_DELETE; ?> text-danger-700 append"></i></a>
                        <a class="btn btn-lg btn-primary" href="#" id="lock-admin-link"><?php echo ENABLE; ?><i class="<?php echo ICON_CHECKMARK; ?> text-success-700 append"></i></a>
                    </div>
                    <?php
                        } else {
                            // Unlock admin button
                        ?>

                    <p class="lead text-center fs-2 mb-5"><i class="<?php echo ICON_LOCK; ?> text-success-600 prepend"></i><?php echo ADMIN_AUTHENTICATION_MODULE_IS_ENABLED; ?></p>

                    <div class="d-flex justify-content-around p-md-4">
                        <a class="btn btn-lg btn-warning" href="#" id="lock-admin-link"><i class="<?php echo ICON_UNLOCK; ?> text-danger-600 prepend"></i><?php echo DISABLE; ?></a>
                        <a class="btn btn-lg btn-primary" href="<?php echo ADMIN_URL . 'login' ?>" target="_blank" rel="noreferer noopener"><?php echo OPEN_ADMIN_PAGE; ?><i class="<?php echo ICON_NEW_TAB; ?> text-primary-800 append"></i></a>
                    </div>
                    <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    if ($generator->debug === true) {
    ?>
    <div id="debug">
        <button id="btn-debug" type="button" class="btn btn-xs btn-danger">DEBUG</button>
        <?php
            if (isset($_SESSION['log-msg'])) {
                echo $_SESSION['log-msg'];
                unset($_SESSION['log-msg']);
            } else {
                echo '<p>No debug message registered</p>';
            } ?>
    </div>
    <?php
    }
    ?>

    <!-- Database relations modal -->

    <div id="database-relations-modal" class="modal d-none" tabindex="-1" aria-labelledby="relationship-modal-label" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header text-bg-info">
                <p class="modal-title h1 mb-0 fs-5" id="relationship-modal-label"><?php echo $generator->database . ' ' . RELATIONS; ?></p>
                <button type="button" class="btn-close modal-close" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php

                // Relations table
                if (!empty($generator->relations)) {
                ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-4">
                        <thead>
                            <tr class="bt-0">
                                <th><?php echo TABLE; ?></th>
                                <th><?php echo COLUMN; ?></th>
                                <th><?php echo REFERENCED_TABLE; ?></th>
                                <th><?php echo REFERENCED_COLUMN; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach ($generator->relations['db'] as $r) {
                                ?>
                            <tr>
                                <td><?php echo $r['table']; ?></td>
                                <td><?php echo $r['column']; ?></td>
                                <td><?php echo $r['referenced_table']; ?></td>
                                <td><?php echo $r['referenced_column']; ?></td>
                            </tr>
                            <?php
                                } ?>
                        </tbody>
                    </table>
                </div>
                <?php
                } else {
                    echo '<p>' . NO_RELATIONSHIP_FOUND . '</p>';
                }
                ?>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-light modal-close"><i class="<?php echo ICON_CANCEL; ?> prepend"></i> <?php echo CLOSE; ?></button>
            </div>
        </div>
    </div>

    <!-- Ajax modal -->

    <div id="ajax-modal" class="modal d-none" tabindex="-1" aria-hidden="true">
        <div class="modal-content"></div>
    </div>

    <div id="ajax-update-validation-helper"></div>
    <div id="loader" class="d-none bg-white" style="--bs-bg-opacity: .75;z-index: 2;">
        <div class="spinner"></div>
    </div>
    <script async defer src="<?php echo ADMIN_URL; ?>assets/javascripts/plugins/pace.min.js"></script>
    <script src="<?php echo ADMIN_URL; ?>assets/javascripts/loadjs.min.js"></script>
    <script src="<?php echo ADMIN_URL; ?>assets/javascripts/jquery-3.5.1.min.js"></script>
    <script>
        var adminUrl = '<?php echo ADMIN_URL; ?>';
        var generatorUrl = '<?php echo GENERATOR_URL; ?>';
    </script>
    <script>
        const classUrl = '<?php echo CLASS_URL; ?>';
    </script>
    <script src="<?php echo CLASS_URL; ?>phpformbuilder/plugins/ajax-data-loader/ajax-data-loader.min.js"></script>
    <script type="module" src="<?php echo GENERATOR_URL; ?>generator-assets/javascripts/generator.js"></script>
    <?php
    if (isset($form_select_table)) {
        $form_select_table->printJsCode();
    }
    if (isset($form_select_fields)) {
        $form_select_fields->printJsCode();
    }
    if (!empty($generator->diff_files_form)) {
        $generator->diff_files_form->printJsCode();
    }
    ?>
    </body>

</html>
