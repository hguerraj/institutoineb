<?php
use phpformbuilder\Form;
use phpformbuilder\FormExtended;
use phpformbuilder\database\DB;

include_once '../../../conf/conf.php';
include_once GENERATOR_DIR . 'class/generator/Generator.php';

session_start();

// default styles for cards, badges, ...
include_once GENERATOR_DIR . 'inc/default-generator-styles.php';

if (isset($_SESSION['generator'])) {
    $generator   = $_SESSION['generator'];

    /* =============================================
    Default Create | Update Values
    ============================================= */

    // columns
    for ($i = 0; $i < $generator->columns_count; $i++) {
        $column_name = $generator->columns['name'][$i];
        $column_type = $generator->columns['column_type'][$i];
        $field_type  = $generator->columns['field_type'][$i];

        // file path & url | image path & url | date display format | password constraint
        if ($field_type == 'file') {
            $_SESSION['form-select-fields']['cu_special_file_dir_' . $column_name]        = $generator->columns['special'][$i];
            $_SESSION['form-select-fields']['cu_special_file_url_' . $column_name]        = $generator->columns['special2'][$i];
            $_SESSION['form-select-fields']['cu_special_file_types_' . $column_name]      = $generator->columns['special3'][$i];
        } elseif ($field_type == 'image') {
            $_SESSION['form-select-fields']['cu_special_image_dir_' . $column_name]        = $generator->columns['special'][$i];
            $_SESSION['form-select-fields']['cu_special_image_url_' . $column_name]        = $generator->columns['special2'][$i];
            $_SESSION['form-select-fields']['cu_special_image_thumbnails_' . $column_name] = $generator->columns['special3'][$i];
            $_SESSION['form-select-fields']['cu_special_image_editor_' . $column_name]     = $generator->columns['special4'][$i];
            $_SESSION['form-select-fields']['cu_special_image_width_' . $column_name]      = $generator->columns['special5'][$i];
            $_SESSION['form-select-fields']['cu_special_image_height_' . $column_name]     = $generator->columns['special6'][$i];
            $_SESSION['form-select-fields']['cu_special_image_crop_' . $column_name]       = $generator->columns['special7'][$i];
        } elseif ($field_type == 'date' || $field_type == 'datetime' || $field_type == 'month') {
            $_SESSION['form-select-fields']['cu_special_date_' . $column_name]            = $generator->columns['special'][$i];
            $_SESSION['form-select-fields']['cu_special_date_now_hidden_' . $column_name] = $generator->columns['special3'][$i];
            if (empty($_SESSION['form-select-fields']['cu_special_date_now_hidden_' . $column_name])) {
                $_SESSION['form-select-fields']['cu_special_date_now_hidden_' . $column_name] = 0;
            }
            if ($field_type == 'datetime') {
                $_SESSION['form-select-fields']['cu_special_time_' . $column_name]        = $generator->columns['special2'][$i];
            }
        } elseif ($field_type == 'time') {
            $_SESSION['form-select-fields']['cu_special_time_' . $column_name] = $generator->columns['special'][$i];
        } elseif ($field_type == 'password') {
            $_SESSION['form-select-fields']['cu_special_password_' . $column_name] = $generator->columns['special'][$i];
        }
        $_SESSION['form-select-fields']['cu_field_type_' . $column_name]      = $generator->columns['field_type'][$i];
        if (isset($generator->columns['ajax_loading'][$i])) {
            $_SESSION['form-select-fields']['cu_ajax_loading_' . $column_name] = $generator->columns['ajax_loading'][$i];
        }
        $_SESSION['form-select-fields']['cu_validation_type_' . $column_name] = $generator->columns['validation_type'][$i];
        $_SESSION['form-select-fields']['cu_help_text_' . $column_name]       = $generator->columns['help_text'][$i];
        $_SESSION['form-select-fields']['cu_tooltip_' . $column_name]         = $generator->columns['tooltip'][$i];
        // options
        $_SESSION['form-select-fields']['cu_options_' . $column_name] = array();
        if ($generator->columns['char_count'][$i] === true) {
            $_SESSION['form-select-fields']['cu_options_' . $column_name][] = 'char_count_' . $column_name;
        }
        if ($generator->columns['tinyMce'][$i] === true) {
            $_SESSION['form-select-fields']['cu_options_' . $column_name][] = 'tinyMce_' . $column_name;
        }
        $_SESSION['form-select-fields']['cu_char_count_max_' . $column_name]  = $generator->columns['char_count_max'][$i];
        $_SESSION['form-select-fields']['cu_field_width_' . $column_name]     = $generator->columns['field_width'][$i];
        $_SESSION['form-select-fields']['cu_field_height_' . $column_name]    = $generator->columns['field_height'][$i];
        // validation
        $column_validation = $generator->columns['validation'][$i];
        $column_validation_count = 0;
        if (is_countable($column_validation)) {
            $column_validation_count = count($column_validation);
        }
        for ($j = 0; $j < $column_validation_count; $j++) {
            $_SESSION['form-select-fields']['cu_validation_function_' . $column_name . '-' . $j] = $column_validation[$j]['function'];
            $_SESSION['form-select-fields']['cu_validation_arguments_' . $column_name . '-' . $j] = $column_validation[$j]['args'];
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
            $_SESSION['form-select-fields']['cu_ext_col_allow_in_forms-' . $i]     = $ext_col['allow_in_forms'];
            $_SESSION['form-select-fields']['cu_ext_col_forms_fields-' . $i]       = $ext_col['forms_fields'];
            $_SESSION['form-select-fields']['cu_ext_col_field_type-' . $i]         = $ext_col['field_type'];
            $i++;
        }
    }

    if (isset($_SESSION['errors']['build-create-edit'])) {
        $_SESSION['errors']['form-select-fields'] = $_SESSION['errors']['build-create-edit'];
    }

    $form_select_fields = new FormExtended('form-select-fields', 'horizontal');

    $options = array(
        'openDomReady'   => '',
        'closeDomReady'  => ''
    );
    $form_select_fields->setOptions($options);

    $form_select_fields->startDiv('slide-div');
    $form_select_fields->startCard(SELECT_FIELDS_TYPES_FOR_CREATE_UPDATE, 'card', 'card-header ' . $card_active_header_class);
    $form_select_fields->startFieldset('<i class="fas fa-database ' . $legend_icon_color . ' prepend"></i>' . $generator->table, '', $legend_attr);
    for ($i = 0; $i < $generator->columns_count; $i++) {
        $uniqid = uniqid();
        if ($i + 1 < $generator->columns_count) {
            $rc = $row_class;
        } else {
            $rc = $row_last_child_class;
        }
        $form_select_fields->setCols(3, 9, 'md');
        $column_name           = $generator->columns['name'][$i];
        $column_type           = $generator->columns['column_type'][$i];
        $column_validation     = $generator->columns['validation'][$i];
        $column_validation_count = 0;
        if (is_countable($column_validation)) {
            $column_validation_count = count($column_validation);
        }
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
        $form_select_fields->startRow($rc);
        $form_select_fields->addHtml('<label class="col-md-2">' . $column_name . $primary_badge . $ai_badge . '</label>');
        $form_select_fields->startCol(10, 'md');

        $form_select_fields->addOption('cu_field_type_' . $column_name, 'boolean', 'boolean');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'checkbox', 'checkbox');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'color', 'color');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'date', 'date');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'datetime', 'datetime');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'email', 'email');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'file', 'file');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'hidden', 'hidden');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'image', 'image');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'month', 'month');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'number', 'number');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'password', 'password');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'radio', 'radio');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'select', 'select');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'text', 'text');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'textarea', 'textarea');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'time', 'time');
        $form_select_fields->addOption('cu_field_type_' . $column_name, 'url', 'url');
        $form_select_fields->addSelect('cu_field_type_' . $column_name, FIELD, 'data-slimselect=true, data-allow-deselect=false, data-index=' . $i);

        if ($column_auto_increment) {
            $form_select_fields->addHtml(AUTO_INCREMENT_HELP);
        }

        // special (file path | image path | date display format | password constraint)

        $form_select_fields->startDependentFields('cu_field_type_' . $column_name, 'file');
        $form_select_fields->addIcon('cu_special_file_dir_' . $column_name, '[ROOT_PATH]/', 'before');
        $form_select_fields->addInput('text', 'cu_special_file_dir_' . $column_name, '', FILE_PATH . FILE_PATH_TIP, 'data-index=' . $i);
        $form_select_fields->addIcon('cu_special_file_url_' . $column_name, '[ROOT_URL]/', 'before');
        $form_select_fields->addInput('text', 'cu_special_file_url_' . $column_name, '', FILE_URL . FILE_URL_TIP, 'data-index=' . $i);
        $form_select_fields->addHelper(FILE_AUTHORIZED_HELPER, 'cu_special_file_types_' . $column_name);
        $form_select_fields->addInput('text', 'cu_special_file_types_' . $column_name, '', FILE_AUTHORIZED, 'data-index=' . $i);
        $form_select_fields->endDependentFields();

        $form_select_fields->startDependentFields('cu_field_type_' . $column_name, 'image');
        $form_select_fields->addIcon('cu_special_image_dir_' . $column_name, '[ROOT_PATH]/', 'before');
        $form_select_fields->addInput('text', 'cu_special_image_dir_' . $column_name, '', IMAGE_PATH . IMAGE_PATH_TIP, 'data-index=' . $i);
        $form_select_fields->addIcon('cu_special_image_url_' . $column_name, '[ROOT_URL]/', 'before');
        $form_select_fields->addInput('text', 'cu_special_image_url_' . $column_name, '', IMAGE_URL . IMAGE_URL_TIP, 'data-index=' . $i);
        $form_select_fields->addRadio('cu_special_image_thumbnails_' . $column_name, NO, 0);
        $form_select_fields->addRadio('cu_special_image_thumbnails_' . $column_name, YES, 1);
        $form_select_fields->printRadioGroup('cu_special_image_thumbnails_' . $column_name, CREATE_IMAGE_THUMBNAILS . CREATE_IMAGE_THUMBNAILS_TIP);
        $form_select_fields->addRadio('cu_special_image_editor_' . $column_name, NO, 0);
        $form_select_fields->addRadio('cu_special_image_editor_' . $column_name, YES, 1);
        $form_select_fields->printRadioGroup('cu_special_image_editor_' . $column_name, IMAGE_EDITOR . IMAGE_EDITOR_TIP);
        $form_select_fields->setCols(3, 3, 'md');
        $form_select_fields->groupElements('cu_special_image_width_' . $column_name, 'cu_special_image_height_' . $column_name);
        $form_select_fields->addHelper(MAX_SIZE_HELPER, 'cu_special_image_width_' . $column_name);
        $form_select_fields->addInput('number', 'cu_special_image_width_' . $column_name, '', MAX_WIDTH);
        $form_select_fields->addHelper(MAX_SIZE_HELPER, 'cu_special_image_height_' . $column_name);
        $form_select_fields->addInput('number', 'cu_special_image_height_' . $column_name, '', MAX_HEIGHT);
        $form_select_fields->setCols(3, 9, 'md');
        $form_select_fields->addRadio('cu_special_image_crop_' . $column_name, NO, 0);
        $form_select_fields->addRadio('cu_special_image_crop_' . $column_name, YES, 1);
        $form_select_fields->printRadioGroup('cu_special_image_crop_' . $column_name, CROP);
        $form_select_fields->endDependentFields();

        $form_select_fields->startDependentFields('cu_field_type_' . $column_name, 'date, datetime, month, time');
        $form_select_fields->addHtml('<span class="form-text text-muted"><a href="#cu-date-format-helper' . $uniqid . '" class="cu-date-format-helper-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="cu-date-format-helper' . $uniqid . '">' . DATE_HELPER . '</a></span>', 'cu_special_date_' . $column_name, 'after');
        $form_select_fields->addInput('text', 'cu_special_date_' . $column_name, '', DATE_DISPLAY_FORMAT . DATE_DISPLAY_TIP, 'placeholder=dddd dd mmm yyyy, data-index=' . $i);
        $form_select_fields->addHtml('<span class="form-text text-muted"><a href="#cu-time-format-helper' . $uniqid . '" class="time-format-helper-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="cu-time-format-helper' . $uniqid . '">' . DATE_HELPER . '</a></span>', 'cu_special_time_' . $column_name, 'after');
        $form_select_fields->addInput('text', 'cu_special_time_' . $column_name, '', TIME_DISPLAY_FORMAT . TIME_DISPLAY_TIP, 'placeholder=H:i a, data-index=' . $i);
        $form_select_fields->addHelper(DATE_NOW_HIDDEN_HELPER, 'cu_special_date_now_hidden_' . $column_name);
        $form_select_fields->addRadio('cu_special_date_now_hidden_' . $column_name, NO, 0);
        $form_select_fields->addRadio('cu_special_date_now_hidden_' . $column_name, YES, 1);
        $form_select_fields->printRadioGroup('cu_special_date_now_hidden_' . $column_name, DATE_NOW_HIDDEN);
        $form_select_fields->endDependentFields();

        $form_select_fields->startDependentFields('cu_field_type_' . $column_name, 'password');
        $lower_char = mb_strtolower(LOWERCASE_CHARACTERS, 'UTF-8');
        $char       = mb_strtolower(CHARACTERS, 'UTF-8');
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-min-3', AT_LEAST . ' 3 ' . $lower_char);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-min-4', AT_LEAST . ' 4 ' . $lower_char);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-min-5', AT_LEAST . ' 5 ' . $lower_char);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-min-6', AT_LEAST . ' 6 ' . $lower_char);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-min-7', AT_LEAST . ' 7 ' . $lower_char);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-min-8', AT_LEAST . ' 8 ' . $lower_char);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-min-3', AT_LEAST . ' 3 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-min-4', AT_LEAST . ' 4 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-min-5', AT_LEAST . ' 5 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-min-6', AT_LEAST . ' 6 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-min-7', AT_LEAST . ' 7 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-min-8', AT_LEAST . ' 8 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-number-min-3', AT_LEAST . ' 3 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE . ' + ' . NUMBERS);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-number-min-4', AT_LEAST . ' 4 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE . ' + ' . NUMBERS);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-number-min-5', AT_LEAST . ' 5 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE . ' + ' . NUMBERS);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-number-min-6', AT_LEAST . ' 6 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE . ' + ' . NUMBERS);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-number-min-7', AT_LEAST . ' 7 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE . ' + ' . NUMBERS);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-number-min-8', AT_LEAST . ' 8 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE . ' + ' . NUMBERS);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-number-symbol-min-3', AT_LEAST . ' 3 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE . ' + ' . NUMBERS . ' + ' . SYMBOLS);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-number-symbol-min-4', AT_LEAST . ' 4 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE . ' + ' . NUMBERS . ' + ' . SYMBOLS);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-number-symbol-min-5', AT_LEAST . ' 5 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE . ' + ' . NUMBERS . ' + ' . SYMBOLS);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-number-symbol-min-6', AT_LEAST . ' 6 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE . ' + ' . NUMBERS . ' + ' . SYMBOLS);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-number-symbol-min-7', AT_LEAST . ' 7 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE . ' + ' . NUMBERS . ' + ' . SYMBOLS);
        $form_select_fields->addOption('cu_special_password_' . $column_name, 'lower-upper-number-symbol-min-8', AT_LEAST . ' 8 ' . $char . ' - ' . LOWERCASE . ' + ' . UPPERCASE . ' + ' . NUMBERS . ' + ' . SYMBOLS);
        $form_select_fields->addSelect('cu_special_password_' . $column_name, PASSWORD_CONSTRAINT, 'data-slimselect=true, data-allow-deselect=false, data-index=' . $i);
        $form_select_fields->endDependentFields();

        // show select values from generator data
        $form_select_fields->startDependentFields('cu_field_type_' . $column_name, 'select, radio, checkbox');
        $form_select_fields->startRow('form-group justify-content-start mb-3');
        $form_select_fields->addHtml('<label class="col-md-3 col-form-label">' . VALUES . '</label>');
        $select_values = $generator->getSelectValues($column_name);
        $form_select_fields->startCol(4, 'md');
        $form_select_fields->addHtml('<p class="p-0"><span id="cu_select-values-' . $column_name . '">' . $select_values . '</span></p>');
        $form_select_fields->endCol();
        $form_select_fields->setOptions(array('buttonWrapper' => ''));
        $form_select_fields->setCols(0, 5, 'md');
        $form_select_fields->addBtn('button', 'cu_select_modal' . $column_name, '', ADD_EDIT_VALUES, 'class=btn btn-xs btn-success, data-origin=create-edit, data-column=' . $column_name);
        $form_select_fields->setOptions(array('buttonWrapper' => '<div class="form-group"></div>'));
        $form_select_fields->endRow();
        $form_select_fields->endDependentFields();

        // Ajax loading for select
        $form_select_fields->startDependentFields('cu_field_type_' . $column_name, 'select');
        $options = array('elementsWrapper' => '<div class="form-group row justify-content-end mb-3"></div>');
        $form_select_fields->setOptions($options);
        $form_select_fields->setCols(3, 9, 'md');
        $form_select_fields->addRadio('cu_ajax_loading_' . $column_name, NO, 0);
        $form_select_fields->addRadio('cu_ajax_loading_' . $column_name, YES, 1);
        $form_select_fields->addHelper(AJAX_LOADING_HELP, 'cu_ajax_loading_' . $column_name);
        $form_select_fields->printRadioGroup('cu_ajax_loading_' . $column_name, AJAX_LOADING);
        $options = array('elementsWrapper' => '<div class="form-group row justify-content-end"></div>');
        $form_select_fields->setOptions($options);
        $form_select_fields->endDependentFields();

        // date format helper
        $form_select_fields->setCols(3, 9, 'md');
        $form_select_fields->startDiv('collapse', 'cu-date-format-helper' . $uniqid);
        $form_select_fields->addHtml('<table class="table small date-table"> <thead> <tr> <th>Rule</th> <th>Description</th> <th>Result</th> </tr> </thead> <tbody> <tr> <td><code>d</code></td> <td>Date of the month</td> <td>1 – 31</td> </tr> <tr> <td><code>dd</code></td> <td>Date of the month with a leading zero</td> <td>01 – 31</td> </tr> <tr> <td><code>ddd</code></td> <td>Day of the week in short form</td> <td>Sun – Sat</td> </tr> <tr> <td><code>dddd</code></td> <td>Day of the week in full form</td> <td>Sunday – Saturday</td> </tr> </tbody> <tbody> <tr> <td><code>m</code></td> <td>Month of the year</td> <td>1 – 12</td> </tr> <tr> <td><code>mm</code></td> <td>Month of the year with a leading zero</td> <td>01 – 12</td> </tr> <tr> <td><code>mmm</code></td> <td>Month name in short form</td> <td>Jan – Dec</td> </tr> <tr> <td><code>mmmm</code></td> <td>Month name in full form</td> <td>January – December</td> </tr> </tbody> <tbody> <tr> <td><code>yy</code></td> <td>Year in short form <b>*</b></td> <td>00 – 99</td> </tr> <tr> <td><code>yyyy</code></td> <td>Year in full form</td> <td>2000 – 2999</td> </tr> </tbody> </table>');
        $form_select_fields->endDiv();

        $form_select_fields->startDiv('collapse', 'cu-time-format-helper' . $uniqid);
        $form_select_fields->addHtml('<table class="table small time-table"> <thead> <tr> <th>Rule</th> <th>Description</th> <th>Result</th> </tr> </thead> <tbody> <tr> <td><code>h</code></td> <td>Hour in 12-hour format</td> <td>1 – 12</td> </tr> <tr> <td><code>hh</code></td> <td>Hour in 12-hour format with a leading zero</td> <td>01 – 12</td> </tr> <tr> <td><code>H</code></td> <td>Hour in 24-hour format</td> <td>0 – 23</td> </tr> <tr> <td><code>HH</code></td> <td>Hour in 24-hour format with a leading zero</td> <td>00 – 23</td> </tr> </tbody> <tbody> <tr> <td><code>i</code></td> <td>Minutes</td> <td>00 – 59</td> </tr> </tbody> <tbody> <tr> <td><code>a</code></td> <td>Day time period</td> <td>a.m. / p.m.</td> </tr> <tr> <td><code>A</code></td> <td>Day time period in uppercase</td> <td>AM / PM</td> </tr> </tbody> </table>');
        $form_select_fields->endDiv();

        // help text; tooltip; options; textarea height; field width
        $form_select_fields->startDependentFields('cu_field_type_' . $column_name, 'hidden', true);
        $form_select_fields->setCols(3, 3, 'md');
        $form_select_fields->groupElements('cu_help_text_' . $column_name, 'cu_tooltip_' . $column_name);
        $form_select_fields->addInput('text', 'cu_help_text_' . $column_name, '', HELP_TEXT);
        $form_select_fields->addHelper(NO_HTML, 'cu_tooltip_' . $column_name);
        $form_select_fields->addInput('text', 'cu_tooltip_' . $column_name, '', TOOLTIP);

        $form_select_fields->startDependentFields('cu_field_type_' . $column_name, 'textarea');
        $form_select_fields->setCols(3, 9, 'md');
        $form_select_fields->addCheckbox('cu_options_' . $column_name, CHAR_COUNT, 'char_count_' . $column_name, 'class=char-count');
        $form_select_fields->addCheckbox('cu_options_' . $column_name, TINYMCE, 'tinyMce_' . $column_name, 'class=tinymce');
        $form_select_fields->printCheckboxGroup('cu_options_' . $column_name, OPTIONS);

        $form_select_fields->setCols(3, 3, 'md');

        $options = array('elementsWrapper' => '<div class="form-group row justify-content-end mb-3"></div>');
        $form_select_fields->setOptions($options);

        $form_select_fields->addOption('cu_field_height_' . $column_name, 'xs', 'x-small');
        $form_select_fields->addOption('cu_field_height_' . $column_name, 'sm', 'small');
        $form_select_fields->addOption('cu_field_height_' . $column_name, 'md', 'medium');
        $form_select_fields->addOption('cu_field_height_' . $column_name, 'lg', 'large');
        $form_select_fields->addOption('cu_field_height_' . $column_name, 'xlg', 'x-large');
        $form_select_fields->addSelect('cu_field_height_' . $column_name, FIELD_HEIGHT, 'data-slimselect=true, data-allow-deselect=false');
        $form_select_fields->endDependentFields();

        $options = array('elementsWrapper' => '<div class="form-group row justify-content-end mb-3"></div>');
        $form_select_fields->setOptions($options);

        $form_select_fields->addOption('cu_field_width_' . $column_name, '100%', '100%', SINGLE);
        $form_select_fields->addOption('cu_field_width_' . $column_name, '66% single', '66% ' . SINGLE, SINGLE);
        $form_select_fields->addOption('cu_field_width_' . $column_name, '50% single', '50% ' . SINGLE, SINGLE);
        $form_select_fields->addOption('cu_field_width_' . $column_name, '33% single', '33% ' . SINGLE, SINGLE);
        $form_select_fields->addOption('cu_field_width_' . $column_name, '66% grouped', '66% ' . GROUPED, GROUPED);
        $form_select_fields->addOption('cu_field_width_' . $column_name, '50% grouped', '50% ' . GROUPED, GROUPED);
        $form_select_fields->addOption('cu_field_width_' . $column_name, '33% grouped', '33% ' . GROUPED, GROUPED);
        $form_select_fields->addSelect('cu_field_width_' . $column_name, FIELD_WIDTH . GROUPED_SINGLE_TIP, 'data-slimselect=true, data-allow-deselect=false');

        $form_select_fields->startDependentFields('cu_options_' . $column_name, 'char_count_' . $column_name);

        $form_select_fields->addInput('text', 'cu_char_count_max_' . $column_name, '', CHAR_COUNT_MAX);

        $options = array('elementsWrapper' => '<div class="form-group row justify-content-end"></div>');
        $form_select_fields->setOptions($options);

        $form_select_fields->endDependentFields();
        $form_select_fields->endDependentFields();

        $form_select_fields->setCols(3, 9, 'md');

        /* =============================================
        Validation
        ============================================= */

        $form_select_fields->startDiv('validation-col px-4 py-2'); // START validation-col

        // validation type
        if ($column_auto_increment === true) {
            $options_muted = array(
                'radioWrapper' => '<div class="form-check justify-content-start text-muted"></div>',
                'inlineRadioLabelClass' => 'form-check-label disabled'
            );
            $options_normal = array(
                'radioWrapper' => '<div class="form-check justify-content-start"></div>',
                'inlineRadioLabelClass' => 'form-check-label'
            );

            $form_select_fields->setOptions($options_muted);
            $form_select_fields->addRadio('cu_validation_type_' . $column_name, NONE, 'none', 'disabled');
            $form_select_fields->addRadio('cu_validation_type_' . $column_name, AUTO, 'auto', 'checked');
            $form_select_fields->addRadio('cu_validation_type_' . $column_name, CUSTOM, 'custom', 'disabled');

            $form_select_fields->printRadioGroup('cu_validation_type_' . $column_name, VALIDATION);

            $form_select_fields->setOptions($options_normal);
        } else {
            $form_select_fields->addRadio('cu_validation_type_' . $column_name, NONE, 'none');
            $form_select_fields->addRadio('cu_validation_type_' . $column_name, AUTO, 'auto');
            $form_select_fields->addRadio('cu_validation_type_' . $column_name, CUSTOM, 'custom');
            $form_select_fields->printRadioGroup('cu_validation_type_' . $column_name, VALIDATION);
        }

        // validation auto
        $form_select_fields->startDependentFields('cu_validation_type_' . $column_name, 'auto');
        $form_select_fields->startDiv('', 'validation-auto-ajax-elements-container-' . $column_name);

        $form_select_fields->endDiv();
        $form_select_fields->endDependentFields();

        // validation custom
        $form_select_fields->startDependentFields('cu_validation_type_' . $column_name, 'custom');
        $form_select_fields->addInput('hidden', 'validation-dynamic-fields-index-' . $column_name, $column_validation_count - 1);

        // Dynamic fields - container + add button
        $form_select_fields->startDiv('', 'validation-custom-ajax-elements-container-' . $column_name);
        for ($j = 0; $j < $column_validation_count; $j++) {
            if (!isset($_SESSION['form-select-fields']['cu_validation_function_' . $column_name . '-' . $j])) {
                // default = required
                $helper_text = $validation_helper_texts['required'];
            } else {
                $function = $_SESSION['form-select-fields']['cu_validation_function_' . $column_name . '-' . $j];
                if (!empty($function)) {
                    $helper_text = $validation_helper_texts[$function];
                }
            }
            $form_select_fields->addCustomValidationFields($column_name, $j, $helper_text);
        }
        $form_select_fields->endDiv();
        $form_select_fields->startRowCol('row', 'col pt-4 pb-2');
        $form_select_fields->addHtml('<button type="button" class="btn btn-success validation-add-element-button btn-sm float-right">' . ADD . '<i class="' . ICON_PLUS . ' icon-sm append"></i></button>');
        $form_select_fields->endRowCol();
        $form_select_fields->endDependentFields();

        $form_select_fields->endDiv(); // END validation-col
        $form_select_fields->endCol(); // END main col
        $form_select_fields->endRow(); // END main row
    }
    $form_select_fields->endFieldset(); // END fieldset

    $external_columns_count = 0;
    if (is_countable($generator->external_columns)) {
        $external_columns_count = count($generator->external_columns);
    }

    // external relations
    if ($external_columns_count > 0) {
        $show_external = false;
        $active_ext_cols = array();
        foreach ($generator->external_columns as $key => $ext_col) {
            if ($ext_col['active'] === true && !empty($ext_col['relation']['intermediate_table'])) {
                $show_external = true;
                $active_ext_cols[] = $ext_col;
            }
        }
        if ($show_external === true) {
            $form_select_fields->startFieldset('<i class="' . ICON_TRANSMISSION . ' ' . $legend_icon_color . ' prepend"></i>' . EXTERNAL_RELATIONS, '', $legend_attr);
            $i = 0;

            /*
            $ext_col = array(
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

            foreach ($active_ext_cols as $key => $ext_col) {
                // var_dump($ext_col);
                $origin_table       = $ext_col['relation']['origin_table'];
                $intermediate_table = $ext_col['relation']['intermediate_table'];
                $target_table       = $ext_col['relation']['target_table'];
                $active_ext_cols_count = 0;
                if (is_countable($active_ext_cols)) {
                    $active_ext_cols_count = count($active_ext_cols);
                }
                if ($i + 1 < $active_ext_cols_count) {
                    $rc = $row_class;
                } else {
                    $rc = $row_last_child_class;
                }

                $form_select_fields->startRow($rc); // START row
                if (!empty($intermediate_table)) {
                    // many to many
                    $form_select_fields->addHtml('<label class="col-md-4 col-form-label">' . $target_table . '<br><small class="text-muted">(' . $origin_table . ' => ' . $intermediate_table . ' => ' . $target_table . ')</small></label>');
                    $form_select_fields->startCol(8, 'md'); // START col

                    $form_select_fields->startRowCol();
                    $form_select_fields->setCols(-1, -1, 'md');
                    $form_select_fields->addRadio('cu_ext_col_allow_in_forms-' . $i, YES, 1);
                    $form_select_fields->addRadio('cu_ext_col_allow_in_forms-' . $i, NO, 0);
                    $find = array('%origin_table%', '%target_table%');
                    $replace = array($origin_table, $target_table);
                    $radio_label = str_replace($find, $replace, ALLOW_RECORDS_MANAGEMENT_IN_FORMS);
                    $form_select_fields->printRadioGroup('cu_ext_col_allow_in_forms-' . $i, $radio_label);

                    $form_select_fields->setCols(2, 10, 'md');
                    $form_select_fields->startDependentFields('cu_ext_col_allow_in_forms-' . $i, 1);
                    if (!isset($db)) {
                        $db = new DB(DEBUG);
                    }
                    $columns = $db->getColumnsNames($ext_col['target_table']);
                    $columns_count = $db->rowCount();
                    if (!empty($columns_count)) {
                        foreach ($columns as $field) {
                            $form_select_fields->addOption('cu_ext_col_forms_fields-' . $i . '[]', $field, $field);
                        }
                        $form_select_fields->addSelect('cu_ext_col_forms_fields-' . $i . '[]', VALUES_TO_DISPLAY, 'data-slimselect=true, multiple, data-maximum-selection-length=2, required');

                        $form_select_fields->addRadio('cu_ext_col_field_type-' . $i, SELECT_MULTIPLE, 'select-multiple');
                        $form_select_fields->addRadio('cu_ext_col_field_type-' . $i, CHECKBOXES, 'checkboxes');
                        $form_select_fields->printRadioGroup('cu_ext_col_field_type-' . $i, FIELD_TYPE);
                    }
                    $form_select_fields->endDependentFields();
                    $form_select_fields->endRowCol();
                    $form_select_fields->endCol();
                    $form_select_fields->endRow();
                }
                $i++;
            }
            $form_select_fields->endFieldset();
        } // end if
    } // end if

    $form_select_fields->endCard(); // END card
    $form_select_fields->endDiv(); // END slide-div

    /* render elements */

    echo $form_select_fields->getHiddenFields() . $form_select_fields->html;

    // The script below updates the form token value with the new generated token
    ?>
<script>
    var run = function() {
        $('input[name="form-select-fields-token"]').val('<?php echo $_SESSION['form-select-fields_token']; ?>');
        enablePrettyCheckbox('#form-select-fields div[data-show-values=\'build_create_edit\']');
        // scroll to error (invalid feedback)
        if (document.querySelector('p.invalid-feedback:not(.fv-plugins-message-container)')) {
            setTimeout(() => {
                let dims = document.querySelector('p.invalid-feedback:not(.fv-plugins-message-container)').getBoundingClientRect();
                window.scrollTo(window.scrollX, dims.top - 50 + window.scrollY);
            }, 1000);
        } else {
            window.scrollTo(0, 0);
        }
    }
    <?php
    // required for the dependent fields
    $script = $form_select_fields->printJsCode(false, false);
    echo str_replace(['<script>', '</script>'], '', $script);
}
?>
</script>
