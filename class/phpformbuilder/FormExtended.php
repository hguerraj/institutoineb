<?php
namespace phpformbuilder;

use phpformbuilder\Validator\Validator;
use phpformbuilder\database\Mysql;
use common\Utils;

class FormExtended extends Form
{

    /* =============================================
    CRUD GENERATOR
    ============================================= */

    /**
     * register options to revert them with $this->optionsRevert() after $this->optionsJustifyCenter()
     * @var array
     */
    private $savedOptions = array();

    public function startRowCol($row_class = 'row', $col_class = 'col')
    {
        $this->addHtml('<div class="' . $row_class . '">');
        $this->addHtml('<div class="' . $col_class . '">');
    }

    public function endRowCol()
    {
        $this->addHtml('</div>');
        $this->addHtml('</div>');
    }

    public function startCard($title, $classname = '', $header_classname = '', $heading_elements = '', $has_body = true)
    {
        $start_card = Utils::startCard($title, $classname, $header_classname, $heading_elements, $has_body);
        $this->addHtml($start_card);
    }

    public function endCard($has_body = true)
    {
        $end_card = Utils::endCard($has_body);
        $this->addHtml($end_card);
    }

    public function addBootstrapThemeSelect()
    {
        $bs5_available_themes = array(
            'default'    => 'Bootstrap default theme',
            'cerulean'   => 'A calm blue sky',
            'cosmo'      => 'An ode to Metro',
            'cyborg'     => 'Jet black and electric blue',
            'darkly'     => 'Flatly in night mode',
            'flatly'     => 'Flat & modern',
            'journal'    => 'Crisp like a new sheet of paper',
            'litera'     => 'The medium is the message',
            'lumen'      => 'Light & shadow',
            'lux'        => 'A touch of class',
            'materia'    => 'Material is the metaphor',
            'minty'      => 'A fresh feel',
            'morph'      => 'A neumorphic layer',
            'pulse'      => 'A trace of purple',
            'quartz'     => 'A glassmorphic layer',
            'sandstone'  => 'A touch of warmth',
            'simplex'    => 'Mini & minimalmist',
            'slate'      => 'Shades of gunmetal gray',
            'solar'      => 'A spin on solarized',
            'spacelab'   => 'Silvery and sleek',
            'superhero'  => 'The brave and the blue',
            'united'     => 'Ubuntu orange and unique font',
            'yeti'       => 'A friendly foundation',
            'zephyr'     => 'Breezy and beautiful'
        );

        foreach ($bs5_available_themes as $key => $value) {
            $palette = '<div style\=\'padding-left:2rem\' class\=\'d-inline-flex\'><span class\=\'primary\'></span><span class\=\'success\'></span><span class\=\'info\'></span><span class\=\'warning\'></span><span class\=\'danger\'></span><span class\=\'light\'></span><span class\=\'dark\'></span></div>';
            $this->addOption('bootstrap_theme', $key, '', '', 'data-html=<div class\=\'palette palette-' . $key . ' d-flex flex-nowrap justify-content-between\'><span>' . ucfirst($key) . '<small class\=\'text-muted\'> - ' . $value . '</small></span>' . $palette . '</div>');
        }

        $this->addSelect('bootstrap_theme', 'Bootstrap theme', 'data-slimselect=true, data-allow-deselect=false, data-show-search=false, required');
    }

    public function addNavStyleSelect($select_name, $label)
    {
        $available_styles = array('primary', 'secondary', 'success', 'info', 'warning', 'danger', 'light', 'dark');

        foreach ($available_styles as $st) {
            $this->addOption($select_name, $st, '', '', 'data-html=<div class\=\'palette palette-' . BOOTSTRAP_THEME . ' d-flex flex-nowrap justify-content-between\'><span>' . ucfirst($st) . '</span><div style\=\'padding-left:2rem\' class\=\'d-inline-flex\'><span class\=\'' . $st . '\'></span></div></div>');
        }
        $this->addSelect($select_name, $label, 'data-slimselect=true, data-allow-deselect=false, data-show-search=false, required');
    }

    public function addValidationAutoFields($column_name, $index, $function, $value, $validation_helper_text = '')
    {
        $input_function = 'cu_auto_validation_function_' . $column_name . '-' . $index;
        $input_arguments  = 'cu_auto_validation_arguments_' . $column_name . '-' . $index;
        $this->groupElements($input_function, $input_arguments);
        $this->addHtml('<div class="row"><div class="col mb-2">');
        $options = array(
               'horizontalLabelCol'       => 'col-md-4 mb-md-2 mb-lg-0 col-lg-2',
               'horizontalElementCol'     => 'col-md-8 mb-md-2 mb-lg-0 col-lg-3'
        );
        $this->setOptions($options);
        $this->addInput('text', $input_function, $function, FUNCTION_CONST, 'class=form-control-sm, data-index=' . $index . ', data-column-name=' . $column_name . ', readonly');
        if (!empty($validation_helper_text)) {
            $this->addHelper($validation_helper_text, $input_arguments);
        }
        $options = array(
               'horizontalLabelCol'       => 'col-md-4 mb-md-2 mb-lg-0 col-lg-2',
               'horizontalElementCol'     => 'col-md-8 mb-md-2 mb-lg-0 col-lg-5'
        );
        $this->setOptions($options);
        $this->addInput('text', $input_arguments, $value, ARGUMENTS, 'class=form-control-sm, readonly');
        $this->addHtml('</div></div>');
    }

    public function addCustomValidationFields($column_name, $index, $validation_helper_text = '')
    {
        $select_name = 'cu_validation_function_' . $column_name . '-' . $index;
        $input_name  = 'cu_validation_arguments_' . $column_name . '-' . $index;
        $div_attr    = ' class="row validation-dynamic" data-index="' . $index . '"';
        $this->groupElements($select_name, $input_name, 'validation_remove-' . $index);
        $this->addHtml('<div' . $div_attr . '><div class="col">');
        $options = array(
               'horizontalLabelCol'       => 'col-md-4 mb-md-2 mb-lg-0 col-lg-2',
               'horizontalElementCol'     => 'col-md-8 mb-md-2 mb-lg-0 col-lg-3'
        );
        $this->setOptions($options);
        $this->addOption($select_name, 'required', 'required');
        $this->addOption($select_name, 'email', 'email');
        $this->addOption($select_name, 'float', 'float');
        $this->addOption($select_name, 'integer', 'integer');
        $this->addOption($select_name, 'min', 'min');
        $this->addOption($select_name, 'max', 'max');
        $this->addOption($select_name, 'between', 'between');
        $this->addOption($select_name, 'minLength', 'minLength');
        $this->addOption($select_name, 'maxLength', 'maxLength');
        $this->addOption($select_name, 'length', 'length');
        $this->addOption($select_name, 'startsWith', 'startsWith');
        $this->addOption($select_name, 'notStartsWith', 'notStartsWith');
        $this->addOption($select_name, 'endsWith', 'endsWith');
        $this->addOption($select_name, 'notEndsWith', 'notEndsWith');
        $this->addOption($select_name, 'ip', 'ip');
        $this->addOption($select_name, 'url', 'url');
        $this->addOption($select_name, 'date', 'date');
        $this->addOption($select_name, 'minDate', 'minDate');
        $this->addOption($select_name, 'maxDate', 'maxDate');
        $this->addOption($select_name, 'ccnum', 'ccnum');
        $this->addOption($select_name, 'oneOf', 'oneOf');
        $this->addOption($select_name, 'hasLowercase', 'hasLowercase');
        $this->addOption($select_name, 'hasUppercase', 'hasUppercase');
        $this->addOption($select_name, 'hasNumber', 'hasNumber');
        $this->addOption($select_name, 'hasSymbol', 'hasSymbol');
        $this->addOption($select_name, 'hasPattern', 'hasPattern');
        $this->addSelect($select_name, FUNCTION_CONST, 'data-slimselect=true, data-index=' . $index . ', data-column-name=' . $column_name . ', data-show-search=false');
        if (!empty($validation_helper_text)) {
            $this->addHelper($validation_helper_text, $input_name);
        }
        $options = array(
               'horizontalLabelCol'       => 'col-md-4 mb-md-2 col-lg-2',
               'horizontalElementCol'     => 'col-md-8 mb-md-2 col-lg-4'
        );
        $this->setOptions($options);
        $this->addInput('text', $input_name, '', ARGUMENTS);

        // remove button
        $options = array(
               'horizontalElementCol'     => 'col-12 mb-md-4 col-lg-1 mb-lg-0'
        );
        $this->setOptions($options);
        $this->addBtn('button', 'validation_remove-' . $index, $index, '<i class="fas fa-circle-xmark"></i>', 'class=btn btn-sm btn-danger validation-remove-element-button, aria-label=Close, data-index=' . $index);
        $this->addHtml('</div></div>');
    }

    public function addFilterFields($table, $columns, $columns_types, $index)
    {
        $this->setCols(2, 8);
        $this->groupElements('filter-mode-' . $index, 'filter_remove-' . $index);

        $clazz = 'primary-200';
        if (Utils::pair($index)) {
            $clazz = 'primary-100';
        }

        // filter mode
        $this->addHtml('<div id="filters-ajax-elements-' . $index . '" class="row filters-dynamic text-bg-' . $clazz . ' shadow-sm" data-index="' . $index . '"><div class="col-12 py-2">');
        $this->addRadio('filter-mode-' . $index, SIMPLE, 'simple', 'checked');
        $this->addRadio('filter-mode-' . $index, ADVANCED, 'advanced');
        $this->printRadioGroup('filter-mode-' . $index, 'Mode');

        // remove button
        $this->setOptions(['horizontalLabelCol' => '', 'horizontalElementCol' => 'col text-end']);
        $this->addBtn('button', 'filter_remove-' . $index, $index, '', 'class=btn-close ms-auto filters-remove-element-button, data-bs-toggle=tooltip, data-bs-title=' . REMOVE_THIS_FILTER . ', aria-label=Close, data-index=' . $index);
        $this->setOptions(['horizontalElementCol' => 'col-sm-8']);

        // ajax
        $this->setCols(2, 10);
        $this->addRadio('filter-ajax-' . $index, NO, false, 'checked');
        $this->addRadio('filter-ajax-' . $index, YES, true);
        $this->addHelper(AJAX_LOADING_HELP, 'filter-ajax-' . $index);
        $this->printRadioGroup('filter-ajax-' . $index, AJAX_LOADING);

        // simple filters
        $this->startDependentFields('filter-mode-' . $index, 'simple');
        $columns_count = count($columns);
        $datetime_field_types = explode(',', DATETIME_FIELD_TYPES);
        $table_datefields = array();
        for ($i=0; $i < $columns_count; $i++) {
            $column_name = $columns[$i];
            $column_type = $columns_types[$i];
            $this->addOption('filter_field_A-' . $index, $column_name, $table . '.' . $column_name);
            if (in_array($column_type, $datetime_field_types)) {
                $table_datefields[] = $column_name;
            }
        }
        $this->setCols(2, 10);
        $this->addHtml('<span class="form-text text-muted">' . FILTER_HELP_1 . '</span>', 'filter_field_A-' . $index, 'after');
        $this->addSelect('filter_field_A-' . $index, FIELDS_TO_FILTER, 'data-slimselect=true, data-show-search=false');

        // dependent field for date fields
        if (!empty($table_datefields)) {
            $this->startDependentFields('filter_field_A-' . $index, implode(', ', $table_datefields));
            $this->addRadio('filter-daterange-' . $index, NO, false, 'checked');
            $this->addRadio('filter-daterange-' . $index, YES, true);
            $this->addHelper(FILTER_BY_DATE_RANGE_HELPER, 'filter-daterange-' . $index);
            $this->printRadioGroup('filter-daterange-' . $index, FILTER_BY_DATE_RANGE);
            $this->endDependentFields();
        }

        $this->endDependentFields();

        // advanced filters
        $this->startDependentFields('filter-mode-' . $index, 'advanced');
        $this->setCols(2, 4);
        $this->groupElements('filter_select_label-' . $index, 'filter_option_text-' . $index);
        $this->addInput('text', 'filter_select_label-' . $index, '', LABEL);
        $this->addInput('text', 'filter_option_text-' . $index, '', VALUE_S);

        $this->groupElements('filter_fields-' . $index, 'filter_field_to_filter-' . $index);
        $this->addInput('text', 'filter_fields-' . $index, '', FIELDS);
        $this->addInput('text', 'filter_field_to_filter-' . $index, '', FIELDS_TO_FILTER);

        $this->setCols(2, 10);
        $this->addInput('text', 'filter_from-' . $index, '', SQL_FROM);
        if (!isset($_SESSION['form-select-fields']['filter_type-' . $index])) {
            $_SESSION['form-select-fields']['filter_type-' . $index] = 'text';
        }
        $this->setCols(2, 6);
        $this->groupElements('filter_type-' . $index, 'filter_test-' . $index);
        $this->addRadio('filter_type-' . $index, TEXT, 'text');
        $this->addRadio('filter_type-' . $index, BOOLEAN_CONST, 'boolean');
        $this->printRadioGroup('filter_type-' . $index, VALUES_TYPE);
        $this->setCols(0, 4);
        $this->addBtn('button', 'filter_test-' . $index, $index, TEST, 'class=btn btn-sm btn-success pull-right');
        $this->endDependentFields();

        $this->addHtml('</div></div>');
    }

    public function getDocLink($url)
    {
        return '<a href="' . $url . '" class="text-info ms-2" data-bs-toggle="tooltip" data-bs-title="' . DOC . '" target="_blank"><i class="' . ICON_INFO . ' fa-xl"></i></a>';
    }

    public function getHiddenFields()
    {
        return $this->hidden_fields;
    }

    public function optionsJustifyCenter()
    {
        $this->savedOptions = array(
            'elementsWrapper'       => $this->options['elementsWrapper'],
            'horizontalLabelCol'    => $this->options['horizontalLabelCol'],
            'horizontalElementCol'  => $this->options['horizontalElementCol']
        );
        $options = array(
            'elementsWrapper'       => '<div class ="row align-items-center justify-content-center"></div>',
            'horizontalLabelCol'    => 'col flex-grow-0 ms-5 text-nowrap',
            'horizontalElementCol'  => 'col flex-grow-0 text-nowrap'
        );
        $this->setOptions($options);
    }

    public function optionsRevert()
    {
        $this->options                 = array_merge($this->options, $this->savedOptions);
        $this->elements_end_wrapper    = $this->getElementWrapper($this->options['elementsWrapper'], 'end');
        $this->elements_start_wrapper  = $this->getElementWrapper($this->options['elementsWrapper'], 'start');
    }

    /**
     * create Validator object and auto-validate all required fields
     * @param  string $form_ID the form ID
     * @return object          the Validator
     */
    public static function validate($form_ID, $lang = 'en')
    {
        include_once dirname(__FILE__) . '/Validator/Validator.php';
        include_once dirname(__FILE__) . '/Validator/Exception.php';
        $validator = new Validator($_POST, $lang);
        $required = $_SESSION[$form_ID]['required_fields'];
        if ($form_ID === 'form-select-fields' && isset($_POST['action']) && isset($_POST['form-select-fields'])) {
            // remove from the validator all the fields that are not relevant with the posted action
            $required_prefixes = array(
                'build_create_edit' => 'cu_',
                'build_paginated_list' => 'rp_',
                'build_single_element_list' => 'rs_',
                'build_delete' => 'field_delete_confirm_'
            );
            if ($_POST['action'] == 'build_read') {
                $action = $_POST['list_type'];
            } else {
                $action = $_POST['action'];
            }
            $required_prefix = $required_prefixes[$action];
            foreach ($_SESSION[$form_ID]['required_fields'] as $key => $req) {
                if (strpos($req, $required_prefix) === false) {
                    unset($_SESSION[$form_ID]['required_fields'][$key]);
                }
            }
            $required = $_SESSION[$form_ID]['required_fields'];
        }
        foreach ($required as $field) {
            $do_validate = true;

            // if dependent field, test parent posted value & validate only if parent posted value matches condition
            if (!empty($_SESSION[$form_ID]['required_fields_conditions'][$field])) {
                $parent_field = $_SESSION[$form_ID]['required_fields_conditions'][$field]['parent_field'];
                $show_values  = preg_split('`,(\s)?`', $_SESSION[$form_ID]['required_fields_conditions'][$field]['show_values']);
                $inverse      = $_SESSION[$form_ID]['required_fields_conditions'][$field]['inverse'];

                if (!isset($_POST[$parent_field])) {
                    // if parent field is not posted (nested dependent fields)
                    $do_validate = false;
                } elseif (!in_array($_POST[$parent_field], $show_values) && !$inverse) {
                    // if posted parent value doesn't match show values
                    $do_validate = false;
                } elseif (in_array($_POST[$parent_field], $show_values) && $inverse) {
                    // if posted parent value does match show values but dependent is inverted
                    $do_validate = false;
                }
            }
            if ($do_validate) {
                if (isset($_POST[$field]) && is_array($_POST[$field])) {
                    $field = $field . '.0';
                }
                $validator->required()->validate($field);
            }
        }

        return $validator;
    }

    /* =============================================
        Complete contact form
    ============================================= */

    public function createContactForm($user_icon, $email_icon, $phone_icon, $send_icon)
    {
        $this->startFieldset('Please fill in this form to contact us');
        $this->addHtml('<p class="text-warning">All fields are required</p>');
        $this->groupElements('user-name', 'user-first-name');
        $this->setCols(0, 6);
        $this->addIcon('user-name', $user_icon, 'before');
        $this->addInput('text', 'user-name', '', '', 'class=input-group-field, placeholder=Name, required');
        $this->addIcon('user-first-name', $user_icon, 'before');
        $this->addInput('text', 'user-first-name', '', '', 'class=input-group-field, placeholder=First Name, required');
        $this->setCols(0, 12);
        $this->addIcon('user-email', $email_icon, 'before');
        $this->addInput('email', 'user-email', '', '', 'class=input-group-field, placeholder=Email, required');
        $this->addIcon('user-phone', $phone_icon, 'before');
        $this->addInput('text', 'user-phone', '', '', 'class=input-group-field, placeholder=Phone, required');
        if ($this->framework == 'material') {
            $this->addTextarea('message', '', 'Your message', 'cols=30, rows=4, required');
        } else {
            $this->addTextarea('message', '', '', 'cols=30, rows=4, required, placeholder=Message');
        }
        $this->addPlugin('word-character-count', '#message', 'default', array('%maxAuthorized%' => 100));
        $this->addCheckbox('newsletter', 'Suscribe to Newsletter', 1, 'checked=checked');
        $this->printCheckboxGroup('newsletter', '');
        $this->setCols(3, 9);
        $this->addInput('text', 'captcha', '', 'Type the following characters :', 'size=15');
        $this->addPlugin('captcha', '#captcha');
        $this->setCols(0, 12);
        $this->centerContent();
        $this->addBtn('submit', 'submit-btn', 1, 'Send ' . $send_icon, 'class=btn btn-lg btn-success success button');
        $this->endFieldset();

        // Custom radio & checkbox css
        if ($this->framework != 'material') {
            $this->addPlugin('nice-check', 'form', 'default', ['%skin%' => 'green']);
        }

        // jQuery validation
        $this->addPlugin('formvalidation', '#' . $this->form_ID);

        return $this;
    }

    /* Contact form validation */

    public static function validateContactForm($form_name)
    {
        // create validator & auto-validate required fields
        $validator = self::validate($form_name);

        // additional validation
        $validator->maxLength(100)->validate('message');
        $validator->email()->validate('user-email');
        $validator->captcha('captcha')->validate('captcha');

        // check for errors

        if ($validator->hasErrors()) {
            $_SESSION['errors'][$form_name] = $validator->getAllErrors();

            return false;
        } else {
            return true;
        }
    }

    /* Contact form e-mail sending */

    public static function sendContactEmail($email_config, $form_ID)
    {

        // get hostname
        $email_config['filter_values'] =  $form_ID . ', captcha, submit-btn, captchaHash';
        $sent_message = self::sendMail($email_config);
        self::clear($form_ID);

        return $sent_message;
    }

    /* =============================================
        Fields shorcuts and groups for users
    ============================================= */

    public function addAddress($i = '')
    {
        $index = $this->getIndex($i);
        $index_text = $this->getIndexText($i);
        $this->setCols(3, 9, 'md');
        $this->addTextarea('address' . $index, '', 'Address' . $index_text, 'required');
        $this->groupElements('zip_code' . $index, 'city' . $index);
        $this->setCols(3, 4, 'md');
        $this->addInput('text', 'zip_code' . $index, '', 'Zip Code' . $index_text, 'required');
        $this->setCols(2, 3, 'md');
        $this->addInput('text', 'city' . $index, '', 'City' . $index_text, 'required');
        $this->setCols(3, 9, 'md');
        $this->addCountrySelect('country' . $index, 'Country' . $index_text, 'class=no-autoinit, data-width=100%, required');

        return $this;
    }

    public function addBirth($i = '')
    {
        $index = $this->getIndex($i);
        $index_text = $this->getIndexText($i);
        $this->setCols(3, 4, 'md');
        $this->groupElements('birth_date' . $index, 'birth_zip_code' . $index);
        $this->addInput('text', 'birth_date' . $index, '', 'Birth Date' . $index_text, 'placeholder=click to open calendar');
        if ($this->framework == 'material') {
            $date_plugin = 'material-datepicker';
        } else {
            $date_plugin = 'pickadate';
        }
        $this->addPlugin($date_plugin, '#birth_date' . $index);
        $this->setCols(2, 3, 'md');
        $this->addInput('text', 'birth_zip_code' . $index, '', 'Birth Zip Code' . $index_text);
        $this->setCols(3, 4, 'md');
        $this->groupElements('birth_city' . $index, 'birth_country' . $index);
        $this->addInput('text', 'birth_city' . $index, '', 'Birth  City' . $index_text);
        $this->setCols(2, 3, 'md');
        $this->addCountrySelect('birth_country' . $index, 'Birth Country' . $index_text, 'class=no-autoinit, data-width=100%');

        return $this;
    }

    public function addCivilitySelect($i = '')
    {
        $index = $this->getIndex($i);
        $index_text = $this->getIndexText($i);
        $this->addOption('civility' . $index, 'M.', 'M.');
        $this->addOption('civility' . $index, 'M<sup>rs</sup>', 'Mrs');
        $this->addOption('civility' . $index, 'M<sup>s</sup>', 'Ms');
        $this->addSelect('civility' . $index, 'Civility' . $index_text, 'data-slimselect=true, data-show-search=false, class=no-autoinit, required');

        return $this;
    }

    public function addContact($i = '')
    {
        $index = $this->getIndex($i);
        $index_text = $this->getIndexText($i);
        $this->groupElements('phone' . $index, 'mobile_phone' . $index);
        $this->setCols(3, 4, 'md');
        $this->addInput('text', 'phone' . $index, '', 'Phone' . $index_text);
        $this->setCols(2, 3, 'md');
        $this->addInput('text', 'mobile_phone' . $index, '', 'Mobile' . $index_text, 'required');
        $this->setCols(3, 9, 'md');
        $this->addInput('email', 'email_professional' . $index, '', 'BuisnessE-mail' . $index_text, 'required');
        $this->addInput('email', 'email_private' . $index, '', 'Personal E-mail' . $index_text);

        return $this;
    }

    public function addIdentity($i = '')
    {
        $index = $this->getIndex($i);
        $index_text = $this->getIndexText($i);
        $this->groupElements('civility' . $index, 'name' . $index);
        $this->setCols(3, 2, 'md');
        $this->addCivilitySelect($i);
        $this->setCols(2, 5, 'md');
        $this->addInput('text', 'name' . $index, '', 'Name' . $index_text, 'required');
        $this->setCols(3, 9, 'md');
        $this->startDependentFields('civility' . $index, 'Mrs');
        $this->addInput('text', 'maiden_name' . $index, '', 'Maiden Name' . $index_text);
        $this->endDependentFields();
        $this->groupElements('firstnames' . $index, 'citizenship' . $index);
        $this->setCols(3, 4, 'md');
        $this->addInput('text', 'firstnames' . $index, '', 'Firstnames' . $index_text, 'required');
        $this->setCols(2, 3, 'md');
        $this->addInput('text', 'citizenship' . $index, '', 'Citizenship' . $index_text);

        return $this;
    }

    /* Submit buttons */

    public function addBackSubmit()
    {
        $this->setCols(0, 12);
        $this->addHtml('<p>&nbsp;</p>');
        $this->addBtn('submit', 'back-btn', 1, 'Back', 'class=btn btn-warning button warning', 'submit_group');
        $this->addBtn('submit', 'submit-btn', 1, 'Submit', 'class=btn btn-success button success', 'submit_group');
        $this->printBtnGroup('submit_group');

        return $this;
    }

    public function addCancelSubmit()
    {
        $this->setCols(3, 9);
        $this->addHtml('<p>&nbsp;</p>');
        $this->addBtn('button', 'cancel-btn', 1, 'Cancel', 'class=btn btn-default button warning', 'submit_group');
        $this->addBtn('submit', 'submit-btn', 1, 'Submit', 'class=btn btn-success button primary', 'submit_group');
        $this->printBtnGroup('submit_group');

        return $this;
    }

    private function getIndex($i)
    {
        if ($i !== '') {
            return '-' . $i;
        }

        return false;
    }
    private function getIndexText($i)
    {
        if ($i !== '') {
            return ' ' . $i;
        }

        return false;
    }
}
