<?php

use phpformbuilder\Form;
use phpformbuilder\FormExtended;
use phpformbuilder\database\DB;
use common\Utils;

include_once '../../../conf/conf.php';
include_once GENERATOR_DIR . 'class/generator/Generator.php';

session_start();

// default styles for cards, badges, ...
include_once GENERATOR_DIR . 'inc/default-generator-styles.php';

if (isset($_SESSION['generator'])) {
    $generator   = $_SESSION['generator'];

    $db = new DB(true);

    /*=============================================
    Default Read Single Values                    =
    =============================================*/

    $_SESSION['form-select-fields']['rs_export_btn']    = $generator->list_options['export_btn'];
    $_SESSION['form-select-fields']['rs_open_url_btn']  = $generator->list_options['open_url_btn'];
    $_SESSION['form-select-fields']['rs_table_label']   = $generator->table_label;
    $_SESSION['form-select-fields']['rs_table_label']   = $generator->table_label;

    // columns

    for ($i = 0; $i < $generator->columns_count; $i++) {
        $column_name     = $generator->columns['name'][$i];
        $column_type     = $generator->columns['column_type'][$i];
        $column_relation = $generator->columns['relation'][$i];

        // if one-to-many relation
        if (!empty($column_relation['target_table'])) {
            $target_fields = explode(', ', $column_relation['target_fields']);
            for ($j = 0; $j < 2; $j++) {
                if (isset($target_fields[$j])) {
                    $_SESSION['form-select-fields']['rs_target_column_' . $j . '_' . $column_name] = $target_fields[$j];
                } else {
                    $_SESSION['form-select-fields']['rs_target_column_' . $j . '_' . $column_name] = '';
                }
            }
        }

        // label
        if (isset($generator->columns['fields'][$column_name])) {
            $_SESSION['form-select-fields']['rs_label_' . $column_name] = $generator->columns['fields'][$column_name];
        }

        // value type
        $_SESSION['form-select-fields']['rs_value_type_' . $column_name] = $generator->columns['value_type'][$i];

        // jedit
        $_SESSION['form-select-fields']['rs_jedit_' . $column_name] = $generator->columns['jedit'][$i];

        // special
        if ($generator->columns['value_type'][$i] == 'file') {
            $_SESSION['form-select-fields']['rs_special_file_dir_' . $column_name] = $generator->columns['special'][$i];
            $_SESSION['form-select-fields']['rs_special_file_url_' . $column_name] = $generator->columns['special2'][$i];
            $_SESSION['form-select-fields']['rs_special_file_types_' . $column_name] = $generator->columns['special3'][$i];
        } elseif ($generator->columns['value_type'][$i] == 'image') {
            $_SESSION['form-select-fields']['rs_special_image_dir_' . $column_name] = $generator->columns['special'][$i];
            $_SESSION['form-select-fields']['rs_special_image_url_' . $column_name] = $generator->columns['special2'][$i];
            $_SESSION['form-select-fields']['rs_special_image_thumbnails_' . $column_name] = $generator->columns['special3'][$i];
        } elseif ($generator->columns['value_type'][$i] == 'password') {
            $_SESSION['form-select-fields']['rs_special_password_' . $column_name] = $generator->columns['special'][$i];
        } elseif ($generator->columns['value_type'][$i] == 'date') {
            $_SESSION['form-select-fields']['rs_special_date_' . $column_name] = $generator->columns['special'][$i];
        } elseif ($generator->columns['value_type'][$i] == 'time') {
            $_SESSION['form-select-fields']['rs_special_time_' . $column_name] = $generator->columns['special'][$i];
        }
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
            $_SESSION['form-select-fields']['rs_ext_col_target_table-' . $i]       = $ext_col['active'];
            $_SESSION['form-select-fields']['rs_ext_col_target_fields-' . $i]      = $ext_col['target_fields'];
            $_SESSION['form-select-fields']['rs_ext_col_allow_crud_in_list-' . $i] = $ext_col['allow_crud_in_list'];
            $i++;
        }
    }

    if (isset($_SESSION['errors']['build-list'])) {
        $_SESSION['errors']['form-select-fields'] = $_SESSION['errors']['build-list'];
    }

    $form_select_fields = new FormExtended('form-select-fields', 'horizontal', 'novalidate');

    $options = array(
        'openDomReady'   => '',
        'closeDomReady'  => ''
    );
    $form_select_fields->setOptions($options);

    $form_select_fields->startDiv('slide-div');

    $options = array('elementsWrapper' => '<div class="form-group row justify-content-end mb-3"></div>');
    $form_select_fields->setOptions($options);

    $form_select_fields->startCard(SELECT_OPTIONS_FOR_SINGLE_ELEMENT_LIST, '', $card_active_header_class);

    $form_select_fields->startFieldset('<i class="fas fa-cogs ' . $legend_icon_color . ' prepend"></i><span class="' . $badge_class . ' prepend">' . $generator->table . '</span>' . MAIN_SETTINGS, 'class=mb-5', $legend_attr);
    $form_select_fields->startRowCol($row_class);
    $form_select_fields->setCols(2, 4, 'md');
    $form_select_fields->groupElements('rs_open_url_btn', 'rs_export_btn');
    $form_select_fields->addRadio('rs_open_url_btn', YES, 1);
    $form_select_fields->addRadio('rs_open_url_btn', NO, 0);
    $doc_link = $form_select_fields->getDocLink('https://www.phpcrudgenerator.com/tutorials/how-to-customize-the-bootstrap-admin-data-tables#open-url-button');
    $form_select_fields->printRadioGroup('rs_open_url_btn', OPEN_URL_BUTTON . $doc_link, true, 'required');
    $form_select_fields->addRadio('rs_export_btn', YES, 1);
    $form_select_fields->addRadio('rs_export_btn', NO, 0);
    $form_select_fields->printRadioGroup('rs_export_btn', EXPORT_BUTTON, true, 'required');
    $form_select_fields->endRowCol();

    $form_select_fields->endFieldset();
    $form_select_fields->startFieldset('<i class="fas fa-signature ' . $legend_icon_color . ' prepend"></i><span class="' . $badge_class . ' prepend">' . $generator->table . '</span>' . HUMAN_READABLE_NAMES, 'class=mb-5', $legend_attr);
    $form_select_fields->setCols(3, 3, 'md');
    $form_select_fields->addInput('text', 'rs_table_label', '', $generator->table, 'required');
    for ($i = 0; $i < $generator->columns_count; $i++) {
        if (Utils::pair($i) && $i + 1 < $generator->columns_count) {
            $form_select_fields->groupElements('rs_label_' . $generator->columns['name'][$i], 'rs_label_' . $generator->columns['name'][$i + 1]);
        }
        $form_select_fields->addInput('text', 'rs_label_' . $generator->columns['name'][$i], '', $generator->columns['name'][$i], 'required');
    }
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
    $sets     = array('enum', 'set');


    for ($i = 0; $i < $generator->columns_count; $i++) {
        $uniqid = uniqid();
        $column_name = $generator->columns['name'][$i];

        $column_type = $generator->columns['column_type'][$i];
        $has_relation = false;
        $target_table = '';
        $relation_label = '';
        // if one-to-many relation
        if (!empty($generator->columns['relation'][$i]['target_table'])) {
            $has_relation = true;
            $target_table = $generator->columns['relation'][$i]['target_table'];
            $relation_label = '<br><span class="badge border-secondary-600 text-secondary-600 d-inline-flex align-items-center mt-1"><i class="' . ICON_TRANSMISSION . ' prepend"></i>' . $target_table . '</span>';
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
        $form_select_fields->addHtml('<label class="col-md-2 col-form-label' . $font_size_class . '">' . $column_name . $relation_label . '</label>');
        $form_select_fields->startCol(10, 'md'); // START col

        $form_select_fields->setCols(2, 4, 'md');

        // value type
        // boolean|color|date|datetime|time|image|number|password|set|text|url
        $form_select_fields->groupElements('rs_value_type_' . $column_name, 'rs_jedit_' . $column_name);
        if ($has_relation === true) {
            $form_select_fields->addInput('text', 'rs_value_type_' . $column_name, 'text', TYPE, 'readonly, class=input-sm');
        } elseif (in_array($column_type, $int) || in_array($column_type, $decimal)) {
            // if tinyInt, can be boolean
            if ($column_type == 'tinyint') {
                $form_select_fields->addOption('rs_value_type_' . $column_name, 'number', NUMBER);
                $form_select_fields->addOption('rs_value_type_' . $column_name, 'boolean', BOOLEAN_CONST);
                $form_select_fields->addSelect('rs_value_type_' . $column_name, TYPE, 'data-slimselect=true, data-allow-deselect=false');
            } else {
                $form_select_fields->addInput('text', 'rs_value_type_' . $column_name, 'number', TYPE, 'readonly, class=input-sm');
            }
        } elseif ($column_type == 'boolean') {
            $form_select_fields->addInput('text', 'rs_value_type_' . $column_name, 'boolean', TYPE, 'readonly, class=input-sm');
        } elseif (in_array($column_type, $date)) {
            $form_select_fields->addInput('text', 'rs_value_type_' . $column_name, 'date', TYPE, 'readonly, class=input-sm');
        } elseif (in_array($column_type, $datetime)) {
            $form_select_fields->addInput('text', 'rs_value_type_' . $column_name, 'datetime', TYPE, 'readonly, class=input-sm');
        } elseif (in_array($column_type, $time)) {
            $form_select_fields->addInput('text', 'rs_value_type_' . $column_name, 'time', TYPE, 'readonly, class=input-sm');
        } elseif (in_array($column_type, $string)) {
            if ($column_type == 'char' || $column_type == 'varchar' || $column_type == 'tinytext' || $column_type == 'text' || $column_type == 'mediumtext' || $column_type == 'longtext') {
                $form_select_fields->addOption('rs_value_type_' . $column_name, 'text', TEXT_NUMBER);
                $form_select_fields->addOption('rs_value_type_' . $column_name, 'file', FILE);
                $form_select_fields->addOption('rs_value_type_' . $column_name, 'image', IMAGE);
                $form_select_fields->addOption('rs_value_type_' . $column_name, 'password', PASSWORD);
                $form_select_fields->addOption('rs_value_type_' . $column_name, 'color', COLOR);
                $form_select_fields->addOption('rs_value_type_' . $column_name, 'url', URL);
                $form_select_fields->addSelect('rs_value_type_' . $column_name, TYPE, 'data-slimselect=true, data-allow-deselect=false');
            } else {
                $form_select_fields->addInput('text', 'rs_value_type_' . $column_name, 'text', TYPE, 'readonly, class=input-sm');
            }
        } elseif (in_array($column_type, $sets)) {
            $form_select_fields->addInput('text', 'rs_value_type_' . $column_name, 'set', TYPE, 'readonly, class=input-sm');
        }

        // edit in place
        $form_select_fields->addOption('rs_jedit_' . $column_name, false, DISABLED);
        $form_select_fields->addOption('rs_jedit_' . $column_name, 'text', TEXT_INPUT);
        $form_select_fields->addOption('rs_jedit_' . $column_name, 'textarea', TEXTAREA);
        $form_select_fields->addOption('rs_jedit_' . $column_name, 'boolean', BOOLEAN_CONST);
        $form_select_fields->addOption('rs_jedit_' . $column_name, 'select', SELECT_CONST);
        $form_select_fields->addOption('rs_jedit_' . $column_name, 'date', DATE_CONST);
        $form_select_fields->addSelect('rs_jedit_' . $column_name, EDIT_IN_PLACE, 'data-slimselect=true, data-allow-deselect=false');

        // "select" values
        if (in_array($column_type, $sets)) {
            $form_select_fields->startRowCol('row', 'col-md-offset-8 col-md-4 pt-4 pb-4');

            // show select values from generator data
            $select_values = $generator->getSelectValues($column_name);
            $form_select_fields->addHtml('<p>' . VALUES . ' : <span  id="rs_select-values-' . $column_name . '">' . $select_values . '</span></p>');

            $form_select_fields->endRowCol();

            $form_select_fields->setCols(8, 4, 'md');
            $form_select_fields->addBtn('button', 'rs_jedit_select_modal' . $column_name, '', ADD_EDIT_VALUES, 'class=btn btn-sm btn-success btn-sm mb-4, data-origin=rs_jedit, data-column=' . $column_name);
            $form_select_fields->setCols(2, 4, 'md');
        } else {
            //  Edit in place "select" values
            $form_select_fields->startDependentFields('rs_jedit_' . $column_name, 'select');
            $form_select_fields->startRowCol('row', 'col-md-offset-8 col-md-4 pt-4 pb-4');

            // show select values from generator data
            $select_values = $generator->getSelectValues($column_name);
            $form_select_fields->addHtml('<p>' . VALUES . ' : <span  id="rs_select-values-' . $column_name . '">' . $select_values . '</span></p>');

            $form_select_fields->endRowCol();

            $form_select_fields->setCols(8, 4, 'md');
            $form_select_fields->addBtn('button', 'rs_jedit_select_modal' . $column_name, '', ADD_EDIT_VALUES, 'class=btn btn-sm btn-success btn-sm mb-4, data-origin=rs_jedit, data-column=' . $column_name);
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
            $form_select_fields->addHtml('<div class="rs_special_date_wrapper mb-3">');
            $form_select_fields->addHtml('<span class="form-text text-muted"><a href="#rs-date-format-helper' . $uniqid . '" class="date-format-helper-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="rs-date-format-helper' . $uniqid . '">' . DATE_HELPER . '</a></span>', 'rs_special_date_' . $column_name, 'after');
            $form_select_fields->addInput('text', 'rs_special_date_' . $column_name, '', DATE_DISPLAY_FORMAT . DATE_DISPLAY_TIP, 'placeholder=' . $placeholder . ', data-index=' . $i);
            $form_select_fields->addHtml('</div>');

            // datetime
        } elseif (in_array($column_type, $datetime)) {
            $form_select_fields->groupElements('rs_special_date_' . $column_name, 'rs_special_time_' . $column_name);
            $form_select_fields->startDiv('rs_special_date_wrapper mb-3');
            $form_select_fields->addHtml('<span class="form-text text-muted"><a href="#rs-date-format-helper' . $uniqid . '" class="date-format-helper-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="rs-date-format-helper' . $uniqid . '">' . DATE_HELPER . '</a></span>', 'rs_special_date_' . $column_name, 'after');
            $form_select_fields->addInput('text', 'rs_special_date_' . $column_name, '', DATE_DISPLAY_FORMAT . DATE_DISPLAY_TIP, 'placeholder=dddd dd mmm yyyy, data-index=' . $i);
            $form_select_fields->addHtml('<span class="form-text text-muted"><a href="#rs-time-format-helper' . $uniqid . '" class="time-format-helper-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="rs-time-format-helper' . $uniqid . '">' . DATE_HELPER . '</a></span>', 'rs_special_time_' . $column_name, 'after');
            $form_select_fields->addInput('text', 'rs_special_time_' . $column_name, '', TIME_DISPLAY_FORMAT . TIME_DISPLAY_TIP, 'placeholder=H:i a, data-index=' . $i);
            $form_select_fields->endDiv();

            // time
        } elseif (in_array($column_type, $time)) {
            $form_select_fields->startDiv('rs_special_date_wrapper mb-3');
            $form_select_fields->addHtml('<span class="form-text text-muted"><a href="#rs-time-format-helper' . $uniqid . '" class="time-format-helper-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="rs-time-format-helper' . $uniqid . '">' . DATE_HELPER . '</a></span>', 'rs_special_time_' . $column_name, 'after');
            $form_select_fields->addInput('text', 'rs_special_time_' . $column_name, '', TIME_DISPLAY_FORMAT . TIME_DISPLAY_TIP, 'placeholder=H:i a, data-index=' . $i);
            $form_select_fields->endDiv();
        } elseif (in_array($column_type, $string)) {
            if ($column_type == 'char' || $column_type == 'varchar' || $column_type == 'tinytext' || $column_type == 'text' || $column_type == 'mediumtext' || $column_type == 'longtext') {
                // file path & url
                $form_select_fields->startDependentFields('rs_value_type_' . $column_name, 'file');
                $form_select_fields->addIcon('rs_special_file_dir_' . $column_name, '[ROOT_PATH]/', 'before');
                $form_select_fields->addInput('text', 'rs_special_file_dir_' . $column_name, '', FILE_PATH . FILE_PATH_TIP, 'class=input-sm');
                $form_select_fields->addIcon('rs_special_file_url_' . $column_name, '[ROOT_URL]/', 'before');
                $form_select_fields->addInput('text', 'rs_special_file_url_' . $column_name, '', FILE_URL . FILE_URL_TIP, 'class=input-sm');
                $form_select_fields->addHelper(FILE_AUTHORIZED_HELPER, 'rs_special_file_types_' . $column_name);
                $form_select_fields->addInput('text', 'rs_special_file_types_' . $column_name, '', FILE_AUTHORIZED, 'class=input-sm');
                $form_select_fields->endDependentFields();

                // image path & url
                $form_select_fields->startDependentFields('rs_value_type_' . $column_name, 'image');
                $form_select_fields->addIcon('rs_special_image_dir_' . $column_name, '[ROOT_PATH]/', 'before');
                $form_select_fields->addInput('text', 'rs_special_image_dir_' . $column_name, '', IMAGE_PATH . IMAGE_PATH_TIP, 'class=input-sm');
                $form_select_fields->addIcon('rs_special_image_url_' . $column_name, '[ROOT_URL]/', 'before');
                $form_select_fields->addInput('text', 'rs_special_image_url_' . $column_name, '', IMAGE_URL . IMAGE_URL_TIP, 'class=input-sm');
                $form_select_fields->addRadio('rs_special_image_thumbnails_' . $column_name, NO, 0);
                $form_select_fields->addRadio('rs_special_image_thumbnails_' . $column_name, YES, 1);
                $form_select_fields->printRadioGroup('rs_special_image_thumbnails_' . $column_name, CREATE_IMAGE_THUMBNAILS . CREATE_IMAGE_THUMBNAILS_TIP);
                $form_select_fields->endDependentFields();

                // password constraints
                $form_select_fields->startDependentFields('rs_value_type_' . $column_name, 'password');
                $lower_char = mb_strtolower(LOWERCASE_CHARACTERS, 'UTF-8');
                $char       = mb_strtolower(CHARACTERS, 'UTF-8');

                $form_select_fields->addOption('rs_special_password_' . $column_name, 'min-3', MIN_3);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'min-4', MIN_4);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'min-5', MIN_5);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'min-6', MIN_6);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'min-7', MIN_7);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'min-8', MIN_8);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-min-3', LOWER_UPPER_MIN_3);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-min-4', LOWER_UPPER_MIN_4);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-min-5', LOWER_UPPER_MIN_5);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-min-6', LOWER_UPPER_MIN_6);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-min-7', LOWER_UPPER_MIN_7);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-min-8', LOWER_UPPER_MIN_8);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-number-min-3', LOWER_UPPER_NUMBER_MIN_3);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-number-min-4', LOWER_UPPER_NUMBER_MIN_4);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-number-min-5', LOWER_UPPER_NUMBER_MIN_5);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-number-min-6', LOWER_UPPER_NUMBER_MIN_6);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-number-min-7', LOWER_UPPER_NUMBER_MIN_7);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-number-min-8', LOWER_UPPER_NUMBER_MIN_8);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-number-symbol-min-3', LOWER_UPPER_NUMBER_SYMBOL_MIN_3);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-number-symbol-min-4', LOWER_UPPER_NUMBER_SYMBOL_MIN_4);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-number-symbol-min-5', LOWER_UPPER_NUMBER_SYMBOL_MIN_5);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-number-symbol-min-6', LOWER_UPPER_NUMBER_SYMBOL_MIN_6);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-number-symbol-min-7', LOWER_UPPER_NUMBER_SYMBOL_MIN_7);
                $form_select_fields->addOption('rs_special_password_' . $column_name, 'lower-upper-number-symbol-min-8', LOWER_UPPER_NUMBER_SYMBOL_MIN_8);
                $form_select_fields->addSelect('rs_special_password_' . $column_name, PASSWORD_CONSTRAINT, 'data-slimselect=true, data-allow-deselect=false');
                $form_select_fields->endDependentFields();
            }
        }
        if (in_array($column_type, $date) || in_array($column_type, $datetime)) {
            // date format helper
            $form_select_fields->setCols(2, 10, 'md');
            $form_select_fields->startDiv('collapse', 'rs-date-format-helper' . $uniqid);
            $form_select_fields->addHtml('<table class="table small date-table"> <thead> <tr> <th>Rule</th> <th>Description</th> <th>Result</th> </tr> </thead> <tbody> <tr> <td><code>d</code></td> <td>Date of the month</td> <td>1 – 31</td> </tr> <tr> <td><code>dd</code></td> <td>Date of the month with a leading zero</td> <td>01 – 31</td> </tr> <tr> <td><code>ddd</code></td> <td>Day of the week in short form</td> <td>Sun – Sat</td> </tr> <tr> <td><code>dddd</code></td> <td>Day of the week in full form</td> <td>Sunday – Saturday</td> </tr> </tbody> <tbody> <tr> <td><code>m</code></td> <td>Month of the year</td> <td>1 – 12</td> </tr> <tr> <td><code>mm</code></td> <td>Month of the year with a leading zero</td> <td>01 – 12</td> </tr> <tr> <td><code>mmm</code></td> <td>Month name in short form</td> <td>Jan – Dec</td> </tr> <tr> <td><code>mmmm</code></td> <td>Month name in full form</td> <td>January – December</td> </tr> </tbody> <tbody> <tr> <td><code>yy</code></td> <td>Year in short form <b>*</b></td> <td>00 – 99</td> </tr> <tr> <td><code>yyyy</code></td> <td>Year in full form</td> <td>2000 – 2999</td> </tr> </tbody> </table>');
            $form_select_fields->endDiv();
        }

        // time format helper
        if (in_array($column_type, $time) || in_array($column_type, $datetime)) {
            $form_select_fields->startDiv('collapse', 'rs-time-format-helper' . $uniqid);
            $form_select_fields->addHtml('<table class="table small time-table"> <thead> <tr> <th>Rule</th> <th>Description</th> <th>Result</th> </tr> </thead> <tbody> <tr> <td><code>h</code></td> <td>Hour in 12-hour format</td> <td>1 – 12</td> </tr> <tr> <td><code>hh</code></td> <td>Hour in 12-hour format with a leading zero</td> <td>01 – 12</td> </tr> <tr> <td><code>H</code></td> <td>Hour in 24-hour format</td> <td>0 – 23</td> </tr> <tr> <td><code>HH</code></td> <td>Hour in 24-hour format with a leading zero</td> <td>00 – 23</td> </tr> </tbody> <tbody> <tr> <td><code>i</code></td> <td>Minutes</td> <td>00 – 59</td> </tr> </tbody> <tbody> <tr> <td><code>a</code></td> <td>Day time period</td> <td>a.m. / p.m.</td> </tr> <tr> <td><code>A</code></td> <td>Day time period in uppercase</td> <td>AM / PM</td> </tr> </tbody> </table>');
            $form_select_fields->endDiv();
        }

        if ($has_relation === true) {
            // get fields from target table
            $columns = $db->getColumnsNames($target_table);
            $columns_count = $db->rowCount();

            // none value available for 2nd field only
            $form_select_fields->addOption('rs_target_column_1_' . $column_name, '', NONE);
            if (!empty($columns_count)) {
                foreach ($columns as $field) {
                    $form_select_fields->addOption('rs_target_column_0_' . $column_name, $field, $field);
                    $form_select_fields->addOption('rs_target_column_1_' . $column_name, $field, $field);
                }
            }
            $form_select_fields->addSelect('rs_target_column_0_' . $column_name, DISPLAY_VALUE . ' 1', 'data-slimselect=true, data-allow-deselect=false, required');
            $form_select_fields->addSelect('rs_target_column_1_' . $column_name, DISPLAY_VALUE . ' 2', 'data-slimselect=true');
        }

        // others
        $form_select_fields->setCols(2, 10, 'md');
        $form_select_fields->addCheckbox('rs_others_' . $column_name, '<span class="text-muted">' . SKIP_THIS_FIELD . '</span>', 'skip');
        $form_select_fields->printCheckboxGroup('rs_others_' . $column_name, OPTIONS);

        $form_select_fields->endCol(); // END col
        $form_select_fields->endRow(); // END row
    }

    $form_select_fields->endFieldset();

    // external relations
    if ($external_columns_count > 0) {
        $form_select_fields->startFieldset('<i class="' . ICON_TRANSMISSION . ' ' . $legend_icon_color . ' prepend"></i>' . EXTERNAL_RELATIONS, '', $legend_attr);
        $i = 0;

        /*
        $ext_col = array(
            'target_table'       => '',
            'target_fields'      => array(),
            'table_label'        => '',
            'fields_labels'      => array(),
            'relation'           => '',
            'allow_crud_in_list' => false,
            'allow_in_forms'     => true,
            'forms_fields'       => array(),
            'field_type'         => array(), // 'select-multiple' | 'checkboxes'
            'active'             => false
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
            $form_select_fields->setCols(2, 10, 'md');
            $form_select_fields->addRadio('rs_ext_col_target_table-' . $i, YES, 1);
            $form_select_fields->addRadio('rs_ext_col_target_table-' . $i, NO, 0);
            $form_select_fields->printRadioGroup('rs_ext_col_target_table-' . $i, ENABLE, true, 'required');

            $form_select_fields->startDependentFields('rs_ext_col_target_table-' . $i, 1);
            if (empty($intermediate_table)) {
                $form_select_fields->setCols(5, 7, 'md');
            }
            $columns = $db->getColumnsNames($ext_col['target_table']);
            $columns_count = $db->rowCount();
            if (!empty($columns_count)) {
                foreach ($columns as $field) {
                    $form_select_fields->addOption('rs_ext_col_target_fields-' . $i . '[]', $field, $field);
                }
                $form_select_fields->addSelect('rs_ext_col_target_fields-' . $i . '[]', FIELDS_TO_DISPLAY, 'data-slimselect=true, required, multiple');
                if (empty($intermediate_table)) {
                    $form_select_fields->addRadio('rs_ext_col_allow_crud_in_list-' . $i, YES, 1);
                    $form_select_fields->addRadio('rs_ext_col_allow_crud_in_list-' . $i, NO, 0);
                    $form_select_fields->printRadioGroup('rs_ext_col_allow_crud_in_list-' . $i, ALLOW_CRUD_IN_LIST);
                }
            }
            $form_select_fields->endDependentFields();
            $form_select_fields->endRowCol();
            $form_select_fields->endCol();
            $form_select_fields->endRow();

            // help dropdown
            if ($i + 1 < $external_columns_count) {
                $rc = $row_class;
            } else {
                $rc = $row_last_child_class;
            }

            $form_select_fields->startRowCol($rc); // START col
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
            $form_select_fields->addBtn('button', 'rs_ext_col_helper_btn_' . $i, 1, EXPLAIN_RELATION, 'data-bs-toggle=collapse, data-bs-target=#rs_ext_col_helper2_' . $i . ', aria-expanded=false, aria-controls=rs_ext_col_helper2_' . $i . ', class=btn btn-sm btn-info dropdown-toggle dropdown-light');
            $form_select_fields->startCol(-1, 'xs', 'mb-2 collapse', 'rs_ext_col_helper2_' . $i);
            $form_select_fields->addHtml($helper_text);
            $form_select_fields->endCol();

            $form_select_fields->endRowCol();

            $i++;
        }
        $form_select_fields->endFieldset();
    }

    $form_select_fields->endCard();

    $form_select_fields->endDiv(); // END slide-div
    $form_select_fields->addPlugin('formvalidation', '#form-select-fields', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));

    /* render elements */

    echo $form_select_fields->getHiddenFields() . $form_select_fields->html;

    // The script below updates the form token value with the new generated token
    ?>
<script>
        var run = function() {
            $('input[name="form-select-fields-token"]').val('<?php echo $_SESSION['form-select-fields_token']; ?>');
            enablePrettyCheckbox('#form-select-fields div[data-show-values=\'build_single_element_list\']');
            // scroll to error (invalid feedback)
            if (document.querySelector('div[data-show-values=\'build_single_element_list\'] p.invalid-feedback:not(.fv-plugins-message-container)')) {
                setTimeout(() => {
                    let dims = document.querySelector('div[data-show-values=\'build_single_element_list\'] p.invalid-feedback:not(.fv-plugins-message-container)').getBoundingClientRect();
                    window.scrollTo(window.scrollX, dims.top - 200 + window.scrollY);
                }, 1000);
            } else {
                window.scrollTo(0, 0);
            }
        }
        <?php
        // required for the dependent fields
        $script = $form_select_fields->printJsCode(false, false);
        echo str_replace(['<script>', '</script>'], '', $script);
        ?>
</script>
    <?php
} else {
    echo Utils::alert(SESSION_EXPIRED, 'alert-danger has-icon');
}
