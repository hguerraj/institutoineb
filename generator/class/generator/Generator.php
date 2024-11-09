<?php

namespace generator;

use phpformbuilder\database\DB;
use phpformbuilder\database\pdodrivers\Firebird;
use phpformbuilder\database\pdodrivers\Mysql;
use phpformbuilder\database\pdodrivers\Pgsql;
use phpformbuilder\Form;
use common\Utils;
use crud\ElementsUtilities;

/**
 * Php CRUD Generator Class
 *
 * @version available in conf/conf.php
 * @author Gilles Migliori - gilles.migliori@gmail.com
 *
 */

class Generator
{
    public $action = '';
    public $authentication_module_enabled = ADMIN_LOCKED;
    public $database;

    /* current database table relations
    $this->relations = array(
        'db' = array(
            'table'                   => $row->table_name,
            'column'                  => $row->column_name,
            'referenced_table'        => $row->referenced_table_name,
            'referenced_table_alias'  => $row->referenced_table_name,
            'referenced_column'       => $row->referenced_column_name
        ),
        'all_db_related_tables' = array(), // = tables + referenced_tables
        'from_to' = array(
            'origin_table'
            'origin_column'
            'intermediate_table'
            'intermediate_column_1' // refers to origin_table
            'intermediate_column_2' // refers to target_table
            'target_table'
            'target_table_alias'
            'target_column',
            'cascade_delete_from_intermediate' // true will automatically delete all matching records according to foreign keys constraints. Default: true
            'cascade_delete_from_origin' // true will automatically delete all matching records according to foreign keys constraints. Default: true
        ),
        'from_to_origin_tables' = array(), // = All from_to['origin_table']
        ,
        'from_to_target_tables' = array(), // = All from_to['target_table']
    );
    */

    public $relations = array();
    public $tables    = array();
    public $table;
    public $default_table_icon = 'fas fa-pencil-alt';
    public $table_icon;
    public $table_label;

    // item = lowercase table name without '-' and '_'
    public $item;
    public $columns_count;
    public $diff_files_form = '';

    /* columns properties extracted from db
        $this->db_columns = array(
            'name',
            'type',
            'null',
            'key',
            'extra'
        );
    */
    public $db_columns = array();

    /* translated properties corresponding to generator needs
        $this->columns = array(
            'name',
            'column_type', // e.g: varchar
            'column_type_full', // e.g: varchar(255)
            'field_type',
            'ajax_loading',

            'relation' = array(
                'target_table',
                'target_table_alias',
                'target_fields',
                'target_fields_display_values' // comma separated
            ),

            'validation_type'
            'value_type',
            'validation',
            'primary',
            'auto_increment',
            'auto_increment_function',

            'fields' = array(
                $columns_name => $columns_label
            )

            'jedit'
            'special' // file path | image path | date display format | password constraints | number of decimals
            'special2' // file url | image url | time display format if datetime or timestamp
            'special3' // file types | image thumbnails (bool) | date_now_hidden (bool)
            'special4' // image editor
            'special5' // image width
            'special6' // image height
            'special7' // image crop
            'sorting'
            'nested'
            'skip'
            'select_from' // from_table | custom_values
            'select_from_table'
            'select_from_value'
            'select_from_field_1'
            'select_from_field_2'
            'select_custom_values'
            'select_multiple'

            'help_text'
            'tooltip'
            'required'
            'char_count'
            'char_count_max'
            'tinyMce'
            'field_width'
            'field_height'
        );

        columns types = $this->valid_db_types ( = database columns)

        field types list : ( = create update fields)
            boolean|checkbox|color|date|datetime|email|file|hidden|image|month|number|password|radio|select|text|textarea|time|url

        value types list : ( = read paginated display values)
            array|boolean|color|date|datetime|time|file|html|image|number|password|set|text|url

        NOTE:
        -----
        Changing value_type in the read paginated list changes automatically the corresponding field_type in generator columns properties.
        The reverse is not true: when we change the field_type in the create/update form it doesn't update the read paginated's value_type

        fields width :
            '100%'         = col(2, 10)
            '66% grouped'  = col(2, 6)
            '66% single'
            '50% grouped'  = col(2, 4)
            '50% single'
            '33% grouped'  = col(2, 2)
            '33% single'
    */
    public $columns = array();

    /*
    $this->external_columns = array(
        'target_table'                  => '',
        'target_fields'                 => array(),
        'target_fields_display_values'  => array(),
        'table_label'                   => '',
        'fields_labels'                 => array(),
        'relation'                      => '',
        'action_btns_target_table'      => '',
        'nested'                        => false,
        'allow_crud_in_list'            => false,
        'allow_in_forms'                => true,
        'forms_fields'                  => array(),
        'field_type'                    => array(), // 'select-multiple' | 'checkboxes'
        'active'                        => false
    );

    // relation = $this->relations['from_to'][$i]
    */
    public $external_columns = array();

    /**
     * $this->list_options = array(
     *    'list_type', // build_paginated_list|build_single_element_list
     *    'open_url_btn',
     *    'export_btn',
     *    'bulk_delete',
     *    'view_record',
     *    'default_search_field',
     *    'order_by',
     *    'order_direction',
     *    'filters' => array()
     * );
     */
    public $list_options = array();
    public $primary_keys = array();
    public $auto_increment_keys = array();
    public $field_delete_confirm_1;
    public $field_delete_confirm_2;

    /**
     * action sent by POST
     *    'build_paginated_list'
     *    'build_single_element_list'
     *    'build_create_edit'
     *    'build_delete'
     */
    public $debug;

    /**
     * $simulate_and_debug:
     * if true, will simulate DB reset structure when posted,
     * and record results in generator/class/generator/reload-table-data-debug.log
     */
    private $simulate_and_debug = false;

    /**
     * Handles the following standard MySQL column types:
     *
     * tinyint | smallint | mediumint | int | bigint | decimal | float | double | real
     * date | datetime | timestamp | time | year
     * char | varchar | tinytext | text | mediumtext | longtext
     * enum | set | json
     */
    private $valid_db_types = array('tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'decimal', 'float', 'double', 'real', 'date', 'datetime', 'timestamp', 'time', 'year', 'char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'enum', 'set', 'json');

    /**
     * get databases from root
     * @return null
     */
    public function __construct($debug = false)
    {
        $this->debug = $debug;
        $this->checkRequiredFiles();
        $this->logMessage('<strong>construct</strong>');
    }

    /**
     * register POST data
     * set action
     * @return null
     */
    public function init()
    {
        $this->connectDb();
        // enable|disable admin authentication module (Secure)
        if (isset($_POST['lock-unlock-admin']) && Form::testToken('lock-unlock-admin') === true && DEMO !== true) {
            if (isset($_POST['lock-admin']) && ($_POST['lock-admin'] > 0)) {
                if ($this->lockAdminPanel() === true) {
                    $this->userMessage(ADMIN_AUTHENTICATION_MODULE_ENABLED, 'alert-success has-icon');
                    $this->authentication_module_enabled = true;
                }
            } elseif (isset($_POST['unlock-admin']) && ($_POST['unlock-admin'] > 0)) {
                if ($this->unlockAdminPanel() === true) {
                    $this->userMessage(ADMIN_AUTHENTICATION_MODULE_DISABLED, 'alert-success has-icon');
                    $this->authentication_module_enabled = false;
                }
            }
        } elseif (isset($_POST['form-remove-authentication-module']) && Form::testToken('form-remove-authentication-module') === true && DEMO !== true) {
            if (!empty($this->database) && $_POST['remove'] > 0) {
                $this->removeAuthentificationModule();
            } elseif (empty($this->database)) {
                $this->userMessage(SELECT_DATABASE, 'alert-warning has-icon');
            }
        } elseif (isset($_POST['form-reinstall-phpcg']) && Form::testToken('form-reinstall-phpcg') === true && DEMO !== true && isset($_POST['do-reinstall']) && $_POST['do-reinstall'] > 0) {
            if ($this->uninstall()) {
                header('Location:../install/do-install.php');
            }
        }
        $this->action = '';
        if (isset($_POST['form-select-table']) && Form::testToken('form-select-table') === true) {
            if (empty($this->database)) {
                $this->userMessage(SELECT_DATABASE, 'alert-warning has-icon');

                return false;
            } else {
                $this->table = $_POST['table'];
                $this->table_icon = $this->getIcon($_POST['table']);
                $this->table_label = $this->getLabel($_POST['table']);
                $upperCamelCaseTable = ElementsUtilities::upperCamelCase($this->table);
                $this->item = mb_strtolower($upperCamelCaseTable);
                $this->reset('columns');
                $this->logMessage('<strong>init</strong> => Generator table (from POST) = ' . $this->table);
            }
        }

        if (!empty($this->database)) {
            // delete relation file if exists (will be regenerated on page reload)
            if (isset($_POST['reset-relations']) && $_POST['reset-relations'] > 0 && Form::testToken('form-reset-relations') === true) {
                if (DEMO !== true) {
                    $this->resetRelations();
                } else {
                    $this->userMessage('Feature disabled in DEMO', 'alert-danger has-icon');
                }
            }
            $this->getRelations();

            // delete table files if exist (will be regenerated on page reload)
            if (isset($_POST['reset-table']) && $_POST['reset-table'] > 0 && DEMO !== true) {
                if (DEMO !== true) {
                    $this->table = $_POST['table-to-reset'];
                    $this->table_icon = $this->getIcon($this->table);
                    $this->table_label = $this->getLabel($this->table);
                    $upperCamelCaseTable = ElementsUtilities::upperCamelCase($this->table);
                    $this->item = mb_strtolower($upperCamelCaseTable);
                    $this->reset('columns');
                    $this->logMessage('<strong>init</strong> => Generator table (from table-to-reset POST) = ' . $this->table);
                    // reset table (structure + data) in all cases
                    $this->deleteTableData();

                    if ($_POST['reset-data'] < 1) {
                        // reload old data
                        $this->reloadTableData();
                    }
                } else {
                    $this->userMessage('Feature disabled in DEMO', 'alert-danger has-icon');
                }
            }
        }
        // set action :
        //      build_create_edit
        //      build_paginated_list
        //      build_single_element_list
        //      build_delete
        if (isset($_POST['action']) && isset($_POST['form-select-fields']) && Form::testToken('form-select-fields') === true && DEMO !== true) {
            if ($_POST['action'] == 'build_read') {
                $this->action = $_POST['list_type'];
            } else {
                $this->action = $_POST['action'];
            }
        }
    }

    /**
     * get tables from current database
     * @return null
     */
    public function getTables()
    {
        if (empty($this->tables)) {
            $db = $this->connectDb();
            $tbls = $db->getTables();
            $registration_table_key = array_search(PHPCG_USERDATA_TABLE, $tbls);
            if ($registration_table_key !== false) {
                unset($tbls[$registration_table_key]);
            }
            $this->tables = array_values($tbls);

            // delete tables if they're in admin/crud-data/db-data.json but not in real database
            if (file_exists(ADMIN_DIR . 'crud-data/db-data.json')) {
                $do_refresh_relations = false;
                $json    = file_get_contents(ADMIN_DIR . 'crud-data/db-data.json');
                $db_data = json_decode($json, true);
                if (!empty($db_data)) {
                    foreach ($db_data as $table => $data) {
                        if (!in_array($table, $this->tables)) {
                            $this->deleteTableData($table);
                            $do_refresh_relations = true;
                        }
                    }
                }
                if ($do_refresh_relations === true) {
                    $this->resetRelations();
                    $this->registerRelations();
                }
            }

            // test invalid characters
            $tables_with_invalid_chars = array();
            $columns_with_invalid_chars = array();
            $tables_count = 0;
            if (is_countable($this->tables)) {
                $tables_count = count($this->tables);
            }
            if ($tables_count > 0) {
                foreach ($this->tables as $table) {
                    if (!preg_match('`^[a-zA-Z0-9_]+$`', $table) || preg_match('`^[0-9]`', $table)) {
                        $tables_with_invalid_chars[] = $table;
                    }
                    $cls = $db->getColumnsNames($table);
                    foreach ($cls as $column_name) {
                        if (!preg_match('`^[a-zA-Z0-9_]+$`', $column_name) || preg_match('`^[0-9]`', $column_name)) {
                            $columns_with_invalid_chars[] = $table . '.' . $column_name;
                        }
                    }
                }
                if (!empty($tables_with_invalid_chars)) {
                    $find = array('%target_name%', '%target_values%');
                    $replace = array('tables', implode('<br>', $tables_with_invalid_chars));
                    $error_msg = str_replace($find, $replace, INVALID_CHARS_ERROR);
                    $this->userMessage($error_msg, 'alert-warning has-icon');
                }
                if (!empty($columns_with_invalid_chars)) {
                    $find = array('%target_name%', '%target_values%');
                    $replace = array('columns', implode('<br>', $columns_with_invalid_chars));
                    $error_msg = str_replace($find, $replace, INVALID_CHARS_ERROR);
                    $this->userMessage($error_msg, 'alert-danger has-icon');
                }
            }
            $this->logMessage('<strong>getTables</strong> => Get tables from DB');
        } else {
            $this->logMessage('<strong>getTables</strong> => Tables already registered');
        }

        // select default table
        if (empty($this->table)) {
            $this->table         = $this->tables[0];
            $this->table_icon    = $this->getIcon($this->tables[0]);
            $this->table_label   = $this->getLabel($this->tables[0]);
            $upperCamelCaseTable = ElementsUtilities::upperCamelCase($this->table);
            $this->item          = mb_strtolower($upperCamelCaseTable);
            $this->logMessage('<strong>getTables</strong> => generator default table = ' . $this->table);
        }
    }

    /**
     * get columns from current table
     * @return null
     */
    public function getDbColumns()
    {
        if (empty($this->db_columns)) {
            $this->db_columns = array(
                'name' => array(),
                'type' => array(),
                'null' => array(),
                'key' => array(),
                'extra' => array()
            );
            $db = $this->connectDb();
            $cols = $db->getColumns($this->table);
            $pdo_driver = $this->getPdoDriver($db);
            $cols = $pdo_driver->convertColumns($this->table, $cols);

            $incompatible_types = $pdo_driver->getIncompatibleTypes();
            if (!empty($incompatible_types)) {
                $it_list = array();
                foreach ($incompatible_types as $field_name => $field_type) {
                    $it_list[] = $field_name . ' (<em>' . $field_type . '</em>)';
                }
                $this->userMessage('<p class="h4">' . INCOMPATIBLE_FIELD_TYPES . '</p><hr><p class="mb-0">' . INCOMPATIBLE_FIELD_TYPES_TEXT . '<br>' . implode(', ', $it_list) . '</p>', 'alert-danger has-icon');
            }

            $unhandled_types = $pdo_driver->getUnhandeledTypes();
            if (!empty($unhandled_types)) {
                $ut_list = array();
                foreach ($unhandled_types as $field_name => $field_type) {
                    $ut_list[] = $field_name . ' (<em>' . $field_type . '</em>)';
                }
                $this->userMessage('<p class="h4">' . UNHANDLED_FIELD_TYPES . '</p><hr><p class="mb-0">' . UNHANDLED_FIELD_TYPES_TEXT . '<br>' . implode(', ', $ut_list) . '</p>', 'alert-info has-icon');
            }

            $this->columns_count = $db->rowCount();
            if (!empty($this->columns_count)) {
                foreach ($cols as $col) {
                    $this->db_columns['name'][]  = $col->Field;
                    $this->db_columns['type'][]  = trim(str_replace('unsigned', '', $col->Type));

                    // allow null for auto_increment
                    if (\strpos($col->Extra, 'auto_increment') !== false) {
                        $this->db_columns['null'][]  = 'YES';
                    } else {
                        $this->db_columns['null'][]  = $col->Null;
                    }
                    $this->db_columns['key'][]   = $col->Key;
                    $this->db_columns['extra'][] = $col->Extra;
                }
                if (!in_array('PRI', $this->db_columns['key'])) {
                    $this->userMessage($this->table . ': ' . NO_PRIMARY_KEY, 'alert-danger has-icon');
                }
            }
            $this->logMessage('<strong>getDbColumns</strong>');
        } else {
            $this->logMessage('<strong>getDbColumns</strong> => Columns already registered');
        }
    }

    public function registerColumnsProperties()
    {
        if (empty($this->columns)) {
            // get columns properties from json if file exists
            $fp = 'database/' . \strtolower($this->database . '/' . $this->table) . '.json';
            if (file_exists(GENERATOR_DIR . $fp)) {
                $json                         = file_get_contents(GENERATOR_DIR . $fp);
                $json_data                    = json_decode($json, true);
                $this->columns                = $json_data['columns'];
                $this->external_columns       = $json_data['external_columns'];
                $this->list_options           = $json_data['list_options'];
                $this->field_delete_confirm_1 = $json_data['field_delete_confirm_1'];
                $this->field_delete_confirm_2 = $json_data['field_delete_confirm_2'];

                // get primary key
                $keys = array_keys($this->columns['primary'], 'true');
                $this->primary_keys = array();
                foreach ($keys as $key) {
                    $this->primary_keys[] = $this->columns['name'][$key];
                }

                // get auto-incremented keys
                $ai_keys = array_keys($this->columns['auto_increment'], 'true');
                $this->auto_increment_keys = array();
                foreach ($ai_keys as $key) {
                    $this->auto_increment_keys[] = $this->columns['name'][$key];
                }

                if (!array_key_exists('bulk_delete', $this->list_options)) {
                    $this->list_options['bulk_delete'] = false;
                }
                if (!array_key_exists('view_record', $this->list_options)) {
                    $this->list_options['view_record'] = true;
                }
                if (!array_key_exists('default_search_field', $this->list_options)) {
                    $this->list_options['default_search_field'] = '';
                }
                if (!array_key_exists('order_by', $this->list_options) || empty($this->list_options['order_by'])) {
                    $this->list_options['order_by'] = implode(', ', $this->primary_keys);
                    $this->list_options['order_direction'] = 'ASC';
                }
                if (!array_key_exists('field_height', $this->columns)) {
                    $this->list_options['field_height'] = array();
                    $cols_count = 0;
                    if (is_countable($this->columns['name'])) {
                        $cols_count = count($this->columns['name']);
                    }
                    for ($i = 0; $i < $cols_count; $i++) {
                        $this->columns['field_height'][] = '';
                    }
                }
                if (!array_key_exists('ajax_loading', $this->columns)) {
                    $this->list_options['ajax_loading'] = array();
                    $cols_count = 0;
                    if (is_countable($this->columns['name'])) {
                        $cols_count = count($this->columns['name']);
                    }
                    for ($i = 0; $i < $cols_count; $i++) {
                        $this->columns['ajax_loading'][] = false;
                    }
                }

                $this->logMessage('<strong>registerColumnsProperties</strong> => From JSON : ' . \strtolower($this->database . '/' . $this->table) . '.json');
            } else {
                // get columns properties from database field types
                $db_columns_data = $this->getColumnsDataFromDb();
                // var_dump($db_columns_data);

                // default fields for forms
                // $text = array( 'char', 'varchar' );
                $number = array(
                    'tinyint',
                    'smallint',
                    'mediumint',
                    'int',
                    'bigint',
                    'decimal',
                    'float',
                    'double',
                    'real',
                    'year'
                );
                $textarea = array(
                    'tinytext',
                    'text',
                    'mediumtext',
                    'longtext'
                );
                $select = array(
                    'enum',
                    'set',
                    'json'
                );
                $boolean = array(
                    'boolean'
                );
                $date = array(
                    'date'
                );
                $datetime = array(
                    'datetime',
                    'timestamp'
                );
                $time = array(
                    'time'
                );
                for ($i = 0; $i < $this->columns_count; $i++) {
                    // default values
                    $columns_name = $this->db_columns['name'][$i];
                    $columns_label = $this->toReadable($this->db_columns['name'][$i]);

                    $this->columns['name'][]        = $columns_name;
                    $this->columns['column_type'][] = $db_columns_data['column_type'][$i];

                    $select_from         = '';
                    $select_from_table   = '';
                    $select_from_value   = '';
                    $select_from_field_1 = '';
                    $select_from_field_2 = '';
                    $select_custom_values = '';

                    $field_type = 'text';

                    // default empty relation
                    $relation = array(
                        'target_table'                  => '',
                        'target_table_alias'            => '',
                        'target_fields'                 => '',
                        'target_fields_display_values'  => '' // comma separated
                    );

                    $has_relation = false;

                    // if current table has relations
                    if ($this->relations !== null && is_array($this->relations['from_to_origin_tables']) && in_array($this->table, $this->relations['from_to_origin_tables'])) {
                        // find the corresponding index in $this->relations['from_to']
                        $index_array = Utils::findInIndexedArray($this->relations['from_to'], 'origin_table', $this->table);
                        foreach ($index_array as $index) {
                            if ($columns_name == $this->relations['from_to'][$index]['origin_column'] && empty($this->relations['from_to'][$index]['intermediate_table'])) {
                                // if one-to-one or one-to-many relation
                                $has_relation = true;
                                $relation = array(
                                    'target_table'                  => $this->relations['from_to'][$index]['target_table'],
                                    'target_table_alias'            => $this->relations['from_to'][$index]['target_table_alias'],
                                    'target_fields'                 => $this->relations['from_to'][$index]['target_column'],
                                    'target_fields_display_values'  => $this->relations['from_to'][$index]['target_column'] // default display value is the foreign key
                                );

                                // default select values = relation field
                                $select_from         = 'from_table';
                                $select_from_table   = $this->relations['from_to'][$index]['target_table'];
                                $select_from_value   = $this->relations['from_to'][$index]['target_column'];
                                $select_from_field_1 = $this->relations['from_to'][$index]['target_column'];
                                $select_from_field_2 = '';
                            }
                        }
                    }

                    $value_type = 'text';
                    $special    = '';
                    $special2   = '';
                    $special3   = '';
                    $special4   = '';
                    $special5   = '';
                    $special6   = '';
                    $special7   = '';
                    $column_type = $db_columns_data['column_type'][$i];
                    $column_type_full = $db_columns_data['column_type_full'][$i];
                    if ($has_relation === true) {
                        $field_type = 'select';
                        $value_type = 'text';
                    } elseif ($db_columns_data['auto_increment'][$i]) {
                        $field_type = 'hidden';
                    } elseif (in_array($column_type, $number)) {
                        $field_type = 'number';
                        $value_type = 'number';
                        $with_decimals = array(
                            'decimal',
                            'float',
                            'double',
                            'real'
                        );
                        if (in_array($column_type, $with_decimals) && preg_match('`([a-z]+)\(([0-9]+),([0-9]+)\)`', $column_type_full, $out)) {
                            $special = $out[3]; // number of decimals
                        }
                    } elseif (in_array($column_type, $textarea)) {
                        $field_type = 'textarea';
                    } elseif (in_array($column_type, $select)) {
                        $field_type = 'select';
                    } elseif (in_array($column_type, $boolean)) {
                        $field_type = 'boolean';
                        $value_type = 'boolean';
                    } elseif (in_array($column_type, $date)) {
                        $field_type = 'date';
                        $value_type = 'date';
                        $special = 'dd mmmm yyyy';
                    } elseif (in_array($column_type, $datetime)) {
                        $field_type = 'datetime';
                        $value_type = 'datetime';
                        $special = 'dd mmmm yyyy';
                        $special2 = 'H:i a';
                    } elseif (in_array($column_type, $time)) {
                        $field_type = 'time';
                        $value_type = 'time';
                        $special = 'H:i a';
                    }

                    // set select default values
                    if ($field_type == 'select' && !empty($db_columns_data['select_values'][$i])) {
                        $select_from         = 'custom_values';
                        $select_custom_values = $db_columns_data['select_values'][$i];
                    }

                    $this->columns['field_type'][]      = $field_type;
                    $this->columns['ajax_loading'][]    = false;
                    $this->columns['relation'][]        = $relation;
                    $this->columns['validation_type'][] = 'auto';
                    $this->columns['value_type'][]      = $value_type;
                    $this->columns['validation'][]      = $db_columns_data['validation'][$i];
                    $this->columns['primary'][]         = $db_columns_data['primary'][$i];
                    $this->columns['auto_increment'][]  = $db_columns_data['auto_increment'][$i];
                    $this->columns['auto_increment_function'][]  = $db_columns_data['auto_increment_function'][$i];

                    $this->columns['fields'][$columns_name]  = $columns_label;

                    $this->columns['jedit'][]                = '';
                    $this->columns['special'][]              = $special;
                    $this->columns['special2'][]             = $special2;
                    $this->columns['special3'][]             = $special3;
                    $this->columns['special4'][]             = $special4;
                    $this->columns['special5'][]             = $special5;
                    $this->columns['special6'][]             = $special6;
                    $this->columns['special7'][]             = $special7;
                    $this->columns['sorting'][]              = false;
                    $this->columns['nested'][]               = false;
                    $this->columns['skip'][]                 = false;
                    $this->columns['select_from'][]          = $select_from;
                    $this->columns['select_from_table'][]    = $select_from_table;
                    $this->columns['select_from_value'][]    = $select_from_value;
                    $this->columns['select_from_field_1'][]  = $select_from_field_1;
                    $this->columns['select_from_field_2'][]  = $select_from_field_2;
                    $this->columns['select_custom_values'][] = $select_custom_values;
                    $this->columns['select_multiple'][]      = false;

                    $this->columns['help_text'][]  = '';
                    $this->columns['tooltip'][]    = '';
                    $required = false;
                    if ($this->db_columns['null'][$i] == 'NO') {
                        $required = true;
                    }
                    $this->columns['required'][]       = $required;
                    $this->columns['char_count'][]     = false;
                    $this->columns['char_count_max'][] = '';
                    $this->columns['tinyMce'][]        = false;
                    $this->columns['field_width'][]    = '100%';
                    $this->columns['field_height'][]   = '';
                }

                // get primary key
                $keys = array_keys($this->columns['primary'], 'true');
                $this->primary_keys = array();
                foreach ($keys as $key) {
                    $this->primary_keys[] = $this->columns['name'][$key];
                }

                // get auto-incremented keys
                $ai_keys = array_keys($this->columns['auto_increment'], 'true');
                $this->auto_increment_keys = array();
                foreach ($ai_keys as $key) {
                    $this->auto_increment_keys[] = $this->columns['name'][$key];
                }

                /*  add external relations to columns list:
                    -   many to many
                    -   one to many with the current table as target
                */

                // reset
                $this->external_columns = array();

                // if current table has relations
                if ($this->relations !== null && is_array($this->relations['from_to_origin_tables']) && in_array($this->table, $this->relations['from_to_origin_tables'])) {
                    // find the corresponding index in $this->relations['from_to']
                    $index_array = Utils::findInIndexedArray($this->relations['from_to'], 'origin_table', $this->table);
                    foreach ($index_array as $index) {
                        // if many-to-many relation
                        if (!empty($this->relations['from_to'][$index]['intermediate_table'])) {
                            // add external column data
                            $external_columns_name    = $this->relations['from_to'][$index]['target_table'];
                            $external_columns_labels  = array($this->getLabel($this->relations['from_to'][$index]['target_table']));
                            $this->external_columns[] = array(
                                'target_table'       => $this->relations['from_to'][$index]['target_table'],
                                'target_fields'      => array(
                                    $this->relations['from_to'][$index]['target_column']
                                ),
                                'target_fields_display_values' => array(
                                    $this->relations['from_to'][$index]['target_column'] // default targeted field is the foreign key
                                ),
                                'table_label'               => $external_columns_name,
                                'fields_labels'             => $external_columns_labels,
                                'relation'                  => $this->relations['from_to'][$index],
                                'action_btns_target_table'  => $this->relations['from_to'][$index]['target_table'],
                                'nested'                    => false,
                                'allow_crud_in_list'        => false,
                                'allow_in_forms'            => true,
                                'forms_fields'              => array(),
                                'field_type'                => 'select-multiple',
                                'active'                    => false
                            );
                        }
                    }
                }
                if ($this->relations !== null && is_array($this->relations['from_to_target_tables']) && in_array($this->table, $this->relations['from_to_target_tables'])) {
                    // find the corresponding index in $this->relations['from_to']
                    $index_array = Utils::findInIndexedArray($this->relations['from_to'], 'target_table', $this->table);
                    foreach ($index_array as $index) {
                        // if one-to-many relation
                        if (empty($this->relations['from_to'][$index]['intermediate_table'])) {
                            // add external column data
                            $external_columns_name    = $this->relations['from_to'][$index]['origin_table'];
                            $external_columns_labels  = array($this->getLabel($this->relations['from_to'][$index]['origin_table']));
                            $this->external_columns[] = array(
                                'target_table'       => $this->relations['from_to'][$index]['origin_table'],
                                'target_fields'      => array(
                                    $this->relations['from_to'][$index]['origin_table']
                                ),
                                'target_fields_display_values' => array(
                                    $this->relations['from_to'][$index]['origin_table'] // default targeted field is the foreign key
                                ),
                                'table_label'               => $external_columns_name,
                                'fields_labels'             => $external_columns_labels,
                                'relation'                  => $this->relations['from_to'][$index],
                                'action_btns_target_table'  => '',
                                'nested'                    => false,
                                'allow_crud_in_list'        => false,
                                'allow_in_forms'            => true,
                                'forms_fields'              => array(),
                                'field_type'                => 'select-multiple',
                                'active'                    => false
                            );
                        }
                    }
                }

                // default values
                $this->list_options = array(
                    'list_type'            => 'build_paginated_list',
                    'open_url_btn'         => 0,
                    'export_btn'           => 1,
                    'bulk_delete'          => 0,
                    'view_record'          => 1,
                    'default_search_field' => '',
                    'order_by'             => implode(', ', $this->primary_keys),
                    'order_direction'      => 'ASC',
                    'filters'              => array()
                );

                $json_data = array(
                    'list_options'           => $this->list_options,
                    'columns'                => $this->columns,
                    'external_columns'       => $this->external_columns,
                    'field_delete_confirm_1' => $this->field_delete_confirm_1,
                    'field_delete_confirm_2' => $this->field_delete_confirm_2
                );

                // register table & columns properties in json file
                $json = json_encode($json_data, JSON_UNESCAPED_UNICODE);
                $this->registerJson($this->table . '.json', $json);
                $this->logMessage('<strong>registerColumnsProperties</strong> => From DB');
            }
        } elseif ($this->action == 'build_paginated_list') {
            // edit columns properties from POST
            for ($i = 0; $i < $this->columns_count; $i++) {
                $column_name                           = $this->columns['name'][$i];
                $column_label                          = $_POST['rp_label_' . $column_name];
                $value_type                            = 'rp_value_type_' . $column_name;
                $jedit                                 = 'rp_jedit_' . $column_name;
                $this->columns['fields'][$column_name] = $column_label;
                $with_decimals = array(
                    'decimal',
                    'float',
                    'double',
                    'real'
                );
                // don't reset if $special is the number of decimals
                if (!in_array($this->columns['column_type'][$i], $with_decimals)) {
                    $this->columns['special'][$i] = ''; // file path | image path | date display format | password constraints | number of decimals
                }
                $this->columns['special2'][$i]         = ''; // file url | image url | time display format if datetime or timestamp
                $this->columns['special3'][$i]         = ''; // file types | image thumbnails (bool) | date_now_hidden (bool)
                $this->columns['special4'][$i]         = ''; // img editor
                $this->columns['special5'][$i]         = ''; // img width
                $this->columns['special6'][$i]         = ''; // img height
                $this->columns['special7'][$i]         = ''; // img crop
                $others                                = 'rp_others_' . $column_name;
                $this->columns['value_type'][$i]       = $_POST[$value_type];
                if (isset($_POST[$jedit])) {
                    $this->columns['jedit'][$i] = $_POST[$jedit];
                } else {
                    // if jedit field has been disabled
                    $this->columns['jedit'][$i] = '';
                }

                // update field_type according to value_type
                // (value_type = read paginated display value)
                // (field_type = create update field)
                if ($this->columns['value_type'][$i] == 'set') {
                    $this->columns['field_type'][$i] = 'select';
                } elseif ($this->columns['auto_increment'][$i] === true) {
                    $this->columns['field_type'][$i] = 'hidden';
                } elseif ($this->columns['value_type'][$i] == 'html') {
                    $this->columns['field_type'][$i] = 'textarea';
                } elseif (!empty($this->columns['relation'][$i]['target_table'])) {
                    $this->columns['field_type'][$i] = 'select';
                } else {
                    $this->columns['field_type'][$i] = $this->columns['value_type'][$i];
                }

                // relation fields  - target values to display
                if (isset($_POST['rp_target_column_0_' . $column_name])) {
                    $this->columns['relation'][$i]['target_fields_display_values'] = $_POST['rp_target_column_0_' . $column_name];
                    if (!empty($_POST['rp_target_column_1_' . $column_name])) {
                        $this->columns['relation'][$i]['target_fields_display_values'] .= ', ' . $_POST['rp_target_column_1_' . $column_name];
                    }

                    // register chosen columns in select values
                    $this->columns['select_from_field_1'][$i] = $_POST['rp_target_column_0_' . $column_name];
                    $this->columns['select_from_field_2'][$i] = $_POST['rp_target_column_1_' . $column_name];
                }

                // special (image path | date display format | password constraint)
                if ($this->columns['value_type'][$i] == 'file') {
                    $this->columns['special'][$i]  = $_POST['rp_special_file_dir_' . $column_name];
                    $this->columns['special2'][$i] = $_POST['rp_special_file_url_' . $column_name];
                    $this->columns['special3'][$i] = $_POST['rp_special_file_types_' . $column_name];
                } elseif ($this->columns['value_type'][$i] == 'image') {
                    $this->columns['special'][$i]  = $_POST['rp_special_image_dir_' . $column_name];
                    $this->columns['special2'][$i] = $_POST['rp_special_image_url_' . $column_name];
                    $this->columns['special3'][$i] = $_POST['rp_special_image_thumbnails_' . $column_name];
                } elseif ($this->columns['value_type'][$i] == 'html') {
                    $this->columns['tinyMce'][$i]  = true;
                    $this->columns['field_height'][$i]  = 'xlg';
                } elseif ($this->columns['value_type'][$i] == 'date' || $this->columns['value_type'][$i] == 'month') {
                    if (!empty($_POST['rp_special_date_' . $column_name])) {
                        $this->columns['special'][$i] = $_POST['rp_special_date_' . $column_name];
                    } else {
                        $this->columns['special'][$i] = 'dd mmmm yyyy';
                    }
                } elseif ($this->columns['value_type'][$i] == 'datetime') {
                    if (!empty($_POST['rp_special_date_' . $column_name])) {
                        $this->columns['special'][$i] = $_POST['rp_special_date_' . $column_name];
                    } else {
                        $this->columns['special'][$i] = 'dd mmmm yyyy';
                    }
                    if (!empty($_POST['rp_special_time_' . $column_name])) {
                        $this->columns['special2'][$i] = $_POST['rp_special_time_' . $column_name];
                    } else {
                        $this->columns['special2'][$i] = 'H:i a';
                    }
                } elseif ($this->columns['value_type'][$i] == 'time') {
                    if (!empty($_POST['rp_special_time_' . $column_name])) {
                        $this->columns['special'][$i] = $_POST['rp_special_time_' . $column_name];
                    } else {
                        $this->columns['special'][$i] = 'H:i a';
                    }
                } elseif ($this->columns['value_type'][$i] == 'password') {
                    $this->columns['special'][$i] = $_POST['rp_special_password_' . $column_name];
                }

                $this->columns['sorting'][$i] = false;
                $this->columns['nested'][$i]  = false;
                $this->columns['skip'][$i]    = false;
                if ($_POST[$others] == 'sorting') {
                    $this->columns['sorting'][$i] = true;
                } elseif ($_POST[$others] == 'nested') {
                    $this->columns['nested'][$i] = true;
                } elseif ($_POST[$others] == 'skip') {
                    $this->columns['skip'][$i] = true;
                }
            }

            $this->table_label = $_POST['rp_table_label'];

            $rp_open_url_btn = 0;
            if (isset($_POST['rp_open_url_btn'])) {
                $rp_open_url_btn = $_POST['rp_open_url_btn'];
            }

            $rp_export_btn = 0;
            if (isset($_POST['rp_export_btn'])) {
                $rp_export_btn = $_POST['rp_export_btn'];
            }

            $rp_bulk_delete = 0;
            if (isset($_POST['rp_bulk_delete'])) {
                $rp_bulk_delete = $_POST['rp_bulk_delete'];
            }

            $rp_view_record = 0;
            if (isset($_POST['rp_view_record'])) {
                $rp_view_record = $_POST['rp_view_record'];
            }

            // store main list values
            $this->list_options = array(
                'list_type'            => $_POST['list_type'],
                'open_url_btn'         => $rp_open_url_btn,
                'export_btn'           => $rp_export_btn,
                'bulk_delete'          => $rp_bulk_delete,
                'view_record'          => $rp_view_record,
                'default_search_field' => $_POST['rp_default_search_field'],
                'order_by'             => $_POST['rp_order_by'],
                'order_direction'      => $_POST['rp_order_direction'],
                'filters'              => array()
            );

            // unset the session value to refresh the admin list order
            if (isset($_SESSION['sorting_' . $this->table])) {
                unset($_SESSION['sorting_' . $this->table]);
                unset($_SESSION['direction_' . $this->table]);
            }

            // filters

            /*
                Simple filter example

            array(
                'select_label'    => 'Name',
                'select_name'     => 'mock-data',
                'option_text'     => 'mock_data.last_name + mock_data.first_name',
                'fields'          => 'mock_data.last_name, mock_data.first_name',
                'field_to_filter' => 'mock_data.last_name',
                'from'            => 'mock_data',
                'type'            => 'text'
            )

                Advanced filter example

            array(
                'select_label'    => 'Secondary nav',
                'select_name'     => 'dropdown_ID',
                'option_text'     => 'nav_name + dropdown.name',
                'fields'          => 'dropdown.ID, dropdown.name, nav.name AS nav_name',
                'field_to_filter' => 'dropdown.ID',
                'from'            => 'dropdown Left Join nav On dropdown.nav_ID = nav.ID',
                'type'            => 'text'
            )
            */

            if (isset($_POST['filters-dynamic-fields-index'])) {
                for ($i = 0; $i <= $_POST['filters-dynamic-fields-index']; $i++) {
                    $filter_mode = $_POST['filter-mode-' . $i];
                    $filter_ajax = false;
                    if (isset($_POST['filter-ajax-' . $i])) {
                        $filter_ajax = boolval($_POST['filter-ajax-' . $i]);
                    }

                    // simple
                    if ($filter_mode == 'simple') {
                        $filter_A         = $_POST['filter_field_A-' . $i];
                        $field_index_A    = array_search($filter_A, $this->columns['name']);
                        $column_name      = $this->columns['name'][$field_index_A];
                        $select_label     = $this->columns['fields'][$column_name];
                        $select_name      = $filter_A;
                        $option_text      = $this->table . '.' . $filter_A;
                        $fields           = $this->table . '.' . $filter_A;
                        $field_to_filter  = $this->table . '.' . $filter_A;
                        $daterange        = false;
                        $from             = $this->table;
                        $from_table       = $this->table;
                        $join_tables      = array();
                        $join_queries     = array();
                        $type             = 'text';
                        if ($this->columns['column_type'][$field_index_A] == 'boolean') {
                            $type = 'boolean';
                        } else {
                            $datetime_field_types = explode(',', DATETIME_FIELD_TYPES);
                            if (in_array($this->columns['column_type'][$field_index_A], $datetime_field_types)) {
                                $type = $this->columns['column_type'][$field_index_A];
                                $daterange = boolval($_POST['filter-daterange-' . $i]);
                                if ($daterange === true) {
                                    $filter_ajax = false;
                                }
                            }
                        }
                    } elseif ($filter_mode == 'advanced') {
                        $filter_A         = '';
                        $select_label     = $_POST['filter_select_label-' . $i];
                        $select_name      = mb_strtolower(ElementsUtilities::upperCamelCase($this->table)) . '-' . $i;
                        $option_text      = $_POST['filter_option_text-' . $i];
                        $fields           = $_POST['filter_fields-' . $i];
                        $field_to_filter  = $_POST['filter_field_to_filter-' . $i];
                        $from             = $_POST['filter_from-' . $i];
                        $filter_parsed    = $this->parseQuery($from);
                        $daterange        = false;
                        $from_table       = $filter_parsed['from_table'];
                        $join_tables      = $filter_parsed['join_tables'];
                        $join_queries     = $filter_parsed['join_queries'];
                        $type             = 'text';
                        if (isset($_POST['filter_type-' . $i])) {
                            $type        = $_POST['filter_type-' . $i];
                        }
                    }

                    $filter_data = array(
                        // generator simple data
                        'filter_mode'     => $filter_mode,
                        'filter_A'        => $filter_A,

                        // admin data
                        'ajax'             => $filter_ajax,
                        'select_label'     => $select_label,
                        'select_name'      => $select_name,
                        'option_text'      => $option_text,
                        'fields'           => $fields,
                        'field_to_filter'  => $field_to_filter,
                        'daterange'        => $daterange,
                        'from'             => $from,
                        'from_table'       => $from_table,
                        'join_tables'      => $join_tables,
                        'join_queries'     => $join_queries,
                        'type'             => $type
                    );
                    $this->list_options['filters'][] = $filter_data;
                }
            }

            // external relations
            $external_columns_count = 0;
            if (is_countable($this->external_columns)) {
                $external_columns_count = count($this->external_columns);
            }
            if ($external_columns_count > 0) {
                foreach ($this->external_columns as $key => $ext_col) {
                    $this->external_columns[$key]['active'] = false;
                    if ($_POST['rp_ext_col_target_table-' . $key] > 0) {
                        $this->external_columns[$key]['active'] = true;
                    }
                    $this->external_columns[$key]['target_fields']                 = array();
                    $this->external_columns[$key]['target_fields_display_values']  = array();
                    $this->external_columns[$key]['fields_labels']                 = array();
                    $fields = 'rp_ext_col_target_fields-' . $key;
                    if (isset($_POST[$fields])) {
                        foreach ($_POST[$fields] as $fieldname) {
                            $this->external_columns[$key]['target_fields'][] = $fieldname;
                            $this->external_columns[$key]['fields_labels'][] = $this->getLabel($this->external_columns[$key]['target_table'], $fieldname);
                        }
                    }
                    if (isset($_POST['rp_ext_col_action_btns_target_table-' . $key])) {
                        $this->external_columns[$key]['action_btns_target_table'] = $_POST['rp_ext_col_action_btns_target_table-' . $key];
                    }
                    if (isset($_POST['rp_ext_col_nested_table-' . $key])) {
                        $this->external_columns[$key]['nested'] = $_POST['rp_ext_col_nested_table-' . $key];
                    }
                    $this->external_columns[$key]['allow_crud_in_list'] = false;
                    if (isset($_POST['rp_ext_col_allow_crud_in_list-' . $key]) && $_POST['rp_ext_col_allow_crud_in_list-' . $key] > 0) {
                        $this->external_columns[$key]['allow_crud_in_list'] = true;
                    }
                    $this->external_columns[$key]['table_label'] = $this->getLabel($this->external_columns[$key]['target_table']);
                }
            }

            $json_data = array(
                'list_options'           => $this->list_options,
                'columns'                => $this->columns,
                'external_columns'       => $this->external_columns,
                'field_delete_confirm_1' => $this->field_delete_confirm_1,
                'field_delete_confirm_2' => $this->field_delete_confirm_2
            );

            // register table & columns properties in json file
            $json = json_encode($json_data, JSON_UNESCAPED_UNICODE);
            $this->registerJson($this->table . '.json', $json);
            $this->logMessage('<strong>registerColumnsProperties</strong> => From build_paginated_list POST');
        } elseif ($this->action == 'build_single_element_list') {
            // edit columns properties from POST
            for ($i = 0; $i < $this->columns_count; $i++) {
                $column_name                           = $this->columns['name'][$i];
                $column_label                          = $_POST['rs_label_' . $column_name];
                $value_type                            = 'rs_value_type_' . $column_name;
                $jedit                                 = 'rs_jedit_' . $column_name;
                $this->columns['fields'][$column_name] = $column_label;
                $with_decimals = array(
                    'decimal',
                    'float',
                    'double',
                    'real'
                );
                if (!in_array($this->columns['column_type'][$i], $with_decimals)) {
                    // don't reset if $special is the number of decimals
                    $this->columns['special'][$i] = ''; // file path | image path | date display format | password constraints | number of decimals
                }
                $this->columns['special2'][$i]         = ''; // img url | time display format if datetime or timestamp
                $this->columns['special3'][$i]         = ''; // img thumbnails
                $this->columns['special4'][$i]         = ''; // img editor
                $this->columns['special5'][$i]         = ''; // img width
                $this->columns['special6'][$i]         = ''; // img height
                $this->columns['special7'][$i]         = ''; // img crop
                $others                                = 'rs_others_' . $column_name;
                $this->columns['value_type'][$i]       = $_POST[$value_type];
                if (isset($_POST[$jedit])) {
                    $this->columns['jedit'][$i] = $_POST[$jedit];
                } else {
                    // if jedit field has been disabled
                    $this->columns['jedit'][$i] = '';
                }

                // update field_type according to value_type
                // (value_type = read paginated display value)
                // (field_type = create update field)
                if ($this->columns['value_type'][$i] == 'set') {
                    $this->columns['field_type'][$i] = 'select';
                } else {
                    $this->columns['field_type'][$i] = $this->columns['value_type'][$i];
                }

                // relation fields  - target values to display
                if (isset($_POST['rs_target_column_0_' . $column_name])) {
                    $this->columns['relation'][$i]['target_fields_display_values'] = $_POST['rs_target_column_0_' . $column_name];
                    if (!empty($_POST['rs_target_column_1_' . $column_name])) {
                        $this->columns['relation'][$i]['target_fields_display_values'] .= ', ' . $_POST['rs_target_column_1_' . $column_name];
                    }

                    // register chosen columns in select values
                    $this->columns['select_from_field_1'][$i] = $_POST['rs_target_column_0_' . $column_name];
                    $this->columns['select_from_field_2'][$i] = $_POST['rs_target_column_1_' . $column_name];
                }

                // special (image path | date display format | password constraint)
                if ($this->columns['value_type'][$i] == 'file') {
                    $this->columns['special'][$i]  = $_POST['rs_special_file_dir_' . $column_name];
                    $this->columns['special2'][$i] = $_POST['rs_special_file_url_' . $column_name];
                    $this->columns['special3'][$i] = $_POST['rs_special_file_types_' . $column_name];
                } elseif ($this->columns['value_type'][$i] == 'image') {
                    $this->columns['special'][$i]  = $_POST['rs_special_image_dir_' . $column_name];
                    $this->columns['special2'][$i] = $_POST['rs_special_image_url_' . $column_name];
                    $this->columns['special3'][$i] = $_POST['rs_special_image_thumbnails_' . $column_name];
                } elseif ($this->columns['value_type'][$i] == 'date' || $this->columns['value_type'][$i] == 'month') {
                    if (!empty($_POST['rs_special_date_' . $column_name])) {
                        $this->columns['special'][$i] = $_POST['rs_special_date_' . $column_name];
                    } else {
                        $this->columns['special'][$i] = 'dd mmmm yyyy';
                    }
                } elseif ($this->columns['value_type'][$i] == 'datetime') {
                    if (!empty($_POST['rs_special_date_' . $column_name])) {
                        $this->columns['special'][$i] = $_POST['rs_special_date_' . $column_name];
                    } else {
                        $this->columns['special'][$i] = 'dd mmmm yyyy';
                    }
                    if (!empty($_POST['rs_special_time_' . $column_name])) {
                        $this->columns['special2'][$i] = $_POST['rs_special_time_' . $column_name];
                    } else {
                        $this->columns['special2'][$i] = 'H:i a';
                    }
                } elseif ($this->columns['value_type'][$i] == 'time') {
                    if (!empty($_POST['rs_special_time_' . $column_name])) {
                        $this->columns['special'][$i] = $_POST['rs_special_time_' . $column_name];
                    } else {
                        $this->columns['special'][$i] = 'H:i a';
                    }
                } elseif ($this->columns['value_type'][$i] == 'password') {
                    $this->columns['special'][$i] = $_POST['rs_special_password_' . $column_name];
                }

                $this->columns['sorting'][$i] = false;
                $this->columns['nested'][$i]  = false;
                $this->columns['skip'][$i]    = false;
                if (isset($_POST[$others]) && in_array('skip', $_POST[$others])) {
                    $this->columns['skip'][$i] = true;
                }
            }

            $this->table_label = $_POST['rs_table_label'];

            // store main list values
            $this->list_options = array(
                'list_type'            => $_POST['list_type'],
                'open_url_btn'         => $_POST['rs_open_url_btn'],
                'export_btn'           => $_POST['rs_export_btn'],
                'bulk_delete'          => '',
                'view_record'          => '',
                'default_search_field' => '',
                'order_by'             => '',
                'order_direction'      => '',
                'filters'              => array()
            );

            // external relations
            $external_columns_count = 0;
            if (is_countable($this->external_columns)) {
                $external_columns_count = count($this->external_columns);
            }
            if ($external_columns_count > 0) {
                foreach ($this->external_columns as $key => $ext_col) {
                    $this->external_columns[$key]['active'] = false;
                    if ($_POST['rs_ext_col_target_table-' . $key] > 0) {
                        $this->external_columns[$key]['active'] = true;
                    }
                    $this->external_columns[$key]['target_fields']                 = array();
                    $this->external_columns[$key]['target_fields_display_values']  = array();
                    $this->external_columns[$key]['fields_labels']                 = array();
                    $fields = 'rs_ext_col_target_fields-' . $key;
                    if (isset($_POST[$fields])) {
                        foreach ($_POST[$fields] as $fieldname) {
                            $this->external_columns[$key]['target_fields_display_values'][] = $fieldname;
                            $this->external_columns[$key]['fields_labels'][] = $this->getLabel($this->external_columns[$key]['target_table'], $fieldname);
                        }
                    }
                    if (isset($_POST['rs_ext_col_action_btns_target_table-' . $key])) {
                        $this->external_columns[$key]['action_btns_target_table'][] = $_POST['rs_ext_col_action_btns_target_table-' . $key];
                    }
                    if (isset($_POST['rs_ext_col_allow_crud_in_list-' . $key])) {
                        $this->external_columns[$key]['allow_crud_in_list'][] = $_POST['rs_ext_col_allow_crud_in_list-' . $key];
                    }
                    $this->external_columns[$key]['table_label'] = $this->getLabel($this->external_columns[$key]['target_table']);
                }
            }

            $json_data = array(
                'list_options'           => $this->list_options,
                'columns'                => $this->columns,
                'external_columns'       => $this->external_columns,
                'field_delete_confirm_1' => $this->field_delete_confirm_1,
                'field_delete_confirm_2' => $this->field_delete_confirm_2
            );

            // register table & columns properties in json file
            $json = json_encode($json_data, JSON_UNESCAPED_UNICODE);
            $this->registerJson($this->table . '.json', $json);
            $this->logMessage('<strong>registerColumnsProperties</strong> => From build_single_element_list POST');
        } elseif ($this->action == 'build_create_edit') {
            // update columns properties from POST
            for ($i = 0; $i < $this->columns_count; $i++) {
                $column_name = $this->columns['name'][$i];
                $field_type   = $_POST['cu_field_type_' . $column_name];
                $special      = '';
                $special2     = '';
                $special3     = '';
                $special4     = '';
                $special5     = '';
                $special6     = '';
                $special7     = '';

                // special (image path | date display format | password constraint)
                if ($field_type == 'file') {
                    $special  = $_POST['cu_special_file_dir_' . $column_name];
                    $special2 = $_POST['cu_special_file_url_' . $column_name];
                    $special3 = $_POST['cu_special_file_types_' . $column_name];
                } elseif ($field_type == 'image') {
                    $special  = $_POST['cu_special_image_dir_' . $column_name];
                    $special2 = $_POST['cu_special_image_url_' . $column_name];
                    $special3 = $_POST['cu_special_image_thumbnails_' . $column_name];
                    $special4 = $_POST['cu_special_image_editor_' . $column_name];
                    $special5 = $_POST['cu_special_image_width_' . $column_name];
                    $special6 = $_POST['cu_special_image_height_' . $column_name];
                    $special7 = $_POST['cu_special_image_crop_' . $column_name];
                } elseif ($field_type == 'date' || $field_type == 'month') {
                    if (!empty($_POST['cu_special_date_' . $column_name])) {
                        $special = $_POST['cu_special_date_' . $column_name];
                    } else {
                        $special = 'dd mmmm yyyy';
                    }
                    $special3 = $_POST['cu_special_date_now_hidden_' . $column_name];
                } elseif ($field_type == 'datetime') {
                    if (!empty($_POST['cu_special_date_' . $column_name])) {
                        $special = $_POST['cu_special_date_' . $column_name];
                    } else {
                        $special = 'dd mmmm yyyy';
                    }
                    $special3 = $_POST['cu_special_date_now_hidden_' . $column_name];
                    if (!empty($_POST['cu_special_time_' . $column_name])) {
                        $special2 = $_POST['cu_special_time_' . $column_name];
                    } else {
                        $special2 = 'H:i a';
                    }
                } elseif ($field_type == 'time') {
                    if (!empty($_POST['cu_special_time_' . $column_name])) {
                        $special = $_POST['cu_special_time_' . $column_name];
                    } else {
                        $special = 'H:i a';
                    }
                    $special3 = $_POST['cu_special_date_now_hidden_' . $column_name];
                } elseif ($field_type == 'password') {
                    $special = $_POST['cu_special_password_' . $column_name];
                }

                /* select values are already registered with ajax */

                $ajax_loading   = false;
                $help_text      = '';
                $tooltip        = '';
                $char_count     = false;
                $tinyMce        = false;
                $char_count_max = '';
                $field_width    = '100%';
                $field_height   = '';
                if (isset($_POST['cu_ajax_loading_' . $column_name])) {
                    $ajax_loading = $_POST['cu_ajax_loading_' . $column_name];
                }
                if (isset($_POST['cu_help_text_' . $column_name])) {
                    $help_text = $_POST['cu_help_text_' . $column_name];
                }
                if (isset($_POST['cu_tooltip_' . $column_name])) {
                    $tooltip = $_POST['cu_tooltip_' . $column_name];
                }
                if (isset($_POST['cu_field_width_' . $column_name])) {
                    $field_width = $_POST['cu_field_width_' . $column_name];
                }
                if (isset($_POST['cu_field_height_' . $column_name])) {
                    $field_height = $_POST['cu_field_height_' . $column_name];
                }

                if (isset($_POST['cu_options_' . $column_name])) {
                    if (in_array('char_count_' . $column_name, $_POST['cu_options_' . $column_name])) {
                        $char_count = true;
                        $char_count_max = $_POST['cu_char_count_max_' . $column_name];
                    }
                    if (in_array('tinyMce_' . $column_name, $_POST['cu_options_' . $column_name])) {
                        $tinyMce = true;
                    }
                }

                // validation
                $validation  = array();
                $v_functions = array();
                $v_arguments = array();
                $validation_type = $_POST['cu_validation_type_' . $column_name];
                if ($validation_type == 'auto' || $validation_type == 'custom') {
                    if ($validation_type == 'auto') {
                        $function_field_name  = 'cu_auto_validation_function_';
                        $arguments_field_name = 'cu_auto_validation_arguments_';
                    } elseif ($validation_type == 'custom') {
                        $function_field_name  = 'cu_validation_function_';
                        $arguments_field_name = 'cu_validation_arguments_';
                    }
                    foreach ($_POST as $name => $value) {
                        if (preg_match('`' . $function_field_name . $column_name . '-`', $name)) {
                            $v_functions[] = $value;
                        } elseif (preg_match('`' . $arguments_field_name . $column_name . '-`', $name)) {
                            $v_arguments[] = $value;
                        }
                    }
                    for ($j = 0; $j < count($v_functions); $j++) {
                        $validation[] = array(
                            'function' => $v_functions[$j],
                            'args' => $v_arguments[$j]
                        );
                    }
                }
                $required       = false;
                if (in_array('required', $v_functions)) {
                    $required = true;
                }
                $this->columns['validation_type'][$i] = $validation_type;
                $this->columns['field_type'][$i]      = $field_type;
                $this->columns['ajax_loading'][$i]    = $ajax_loading;
                $with_decimals = array(
                    'decimal',
                    'float',
                    'double',
                    'real'
                );
                // don't reset if $special is the number of decimals
                if (!in_array($this->columns['column_type'][$i], $with_decimals)) {
                    $this->columns['special'][$i]         = $special;
                }
                $this->columns['special2'][$i]        = $special2;
                $this->columns['special3'][$i]        = $special3;
                $this->columns['special4'][$i]        = $special4;
                $this->columns['special5'][$i]        = $special5;
                $this->columns['special6'][$i]        = $special6;
                $this->columns['special7'][$i]        = $special7;
                $this->columns['help_text'][$i]       = $help_text;
                $this->columns['tooltip'][$i]         = $tooltip;
                $this->columns['required'][$i]        = $required;
                $this->columns['char_count'][$i]      = $char_count;
                $this->columns['tinyMce'][$i]         = $tinyMce;
                $this->columns['char_count_max'][$i]  = $char_count_max;
                $this->columns['field_width'][$i]     = $field_width;
                $this->columns['field_height'][$i]    = $field_height;
                $this->columns['validation'][$i]      = $validation;

                // Generate warning message if any select|radio|checkbox without values
                if ($field_type == 'select' | $field_type == 'radio' | $field_type == 'checkbox') {
                    $select_from          = $this->columns['select_from'][$i];
                    $select_from_table    = $this->columns['select_from_table'][$i];
                    $select_from_value    = $this->columns['select_from_value'][$i];
                    $select_custom_values = $this->columns['select_custom_values'][$i];
                    if (($select_from == 'from_table' && (empty($select_from_table) || empty($select_from_value))) || ($select_from == 'custom_values' && empty($select_custom_values)) || empty($select_from)) {
                        $this->userMessage(NO_VALUE_SELECTED . ' <em>' . $this->columns['name'][$i] . '</em>', 'alert-warning has-icon');
                    }
                }
            }
            $group_started = false;
            $group_width   = 0;
            $group_length  = 0;
            for ($i = 0; $i < $this->columns_count; $i++) {
                // Generate warning message if single grouped field
                if ($this->columns['field_type'][$i] != 'hidden') {
                    if (preg_match('`grouped`', $this->columns['field_width'][$i])) {
                        $field_width = preg_replace('`% [a-z]+`', '', $this->columns['field_width'][$i]); // 33|50|66
                        // start new group
                        if ($group_started === false) {
                            $group_started = true;
                            $group_width   = $field_width;
                            $group_length  = 1;
                        } else {
                            if ($group_width + $field_width <= 100) {
                                // continue group
                                $group_width += $field_width;
                                $group_length += 1;
                            } else {
                                if ($group_length == 1) {
                                    $find = array('%field1%', '%field2%');
                                    $replace = array($this->columns['name'][$i - 1], $this->columns['name'][$i]);
                                    $group_warning = str_replace($find, $replace, GROUP_WIDTH_WARNING);
                                    $this->userMessage($group_warning, 'alert-warning has-icon');
                                }
                                // end group & start new one
                                $group_width   = $field_width;
                                $group_length  = 1;
                            }
                        }
                    } else {
                        if ($group_started === true && $group_length == 1) {
                            $group_warning = str_replace('%field%', ' <em>' . $this->columns['name'][$i] . '</em>', GROUP_WARNING);
                            $this->userMessage($group_warning, 'alert-warning has-icon');
                        }

                        // end group
                        $group_started = false;
                        $group_width   = 0;
                        $group_length  = 0;
                    }
                }
            }

            // external relations
            $external_columns_count = 0;
            if (is_countable($this->external_columns)) {
                $external_columns_count = count($this->external_columns);
            }
            if ($external_columns_count > 0) {
                foreach ($this->external_columns as $key => $ext_col) {
                    if ($ext_col['active'] === true && !empty($ext_col['relation']['intermediate_table'])) {
                        $this->external_columns[$key]['allow_in_forms'] = false;
                        if (isset($_POST['cu_ext_col_allow_in_forms-' . $key]) && $_POST['cu_ext_col_allow_in_forms-' . $key] > 0) {
                            $this->external_columns[$key]['allow_in_forms'] = true;
                            if (isset($_POST['cu_ext_col_forms_fields-' . $key])) {
                                $this->external_columns[$key]['forms_fields']   = $_POST['cu_ext_col_forms_fields-' . $key];
                            }
                            if (isset($_POST['cu_ext_col_field_type-' . $key])) {
                                $this->external_columns[$key]['field_type']     = $_POST['cu_ext_col_field_type-' . $key];
                            }
                        }
                    }
                }
            }

            $json_data = array(
                'list_options'           => $this->list_options,
                'columns'                => $this->columns,
                'external_columns'       => $this->external_columns,
                'field_delete_confirm_1' => $this->field_delete_confirm_1,
                'field_delete_confirm_2' => $this->field_delete_confirm_2
            );

            // register table & columns properties in json file
            $json = json_encode($json_data, JSON_UNESCAPED_UNICODE);
            $this->registerJson($this->table . '.json', $json);
            $this->logMessage('<strong>registerColumnsProperties</strong> => From build_create_edit POST');
        } elseif ($this->action == 'build_delete') {
            $this->field_delete_confirm_1 = $_POST['field_delete_confirm_1'];
            $this->field_delete_confirm_2 = $_POST['field_delete_confirm_2'];
            $json_data = array(
                'list_options'           => $this->list_options,
                'columns'                => $this->columns,
                'external_columns'       => $this->external_columns,
                'field_delete_confirm_1' => $this->field_delete_confirm_1,
                'field_delete_confirm_2' => $this->field_delete_confirm_2
            );

            // register table & columns properties in json file
            $json = json_encode($json_data, JSON_UNESCAPED_UNICODE);
            $this->registerJson($this->table . '.json', $json);
            $this->logMessage('<strong>registerColumnsProperties</strong> => From build_delete POST');
        } else {
            $this->logMessage('<strong>registerColumnsProperties</strong> => Already registered');
        }
    }

    public function registerSelectValues($column, $select_from, $select_from_table, $select_from_value, $select_from_field_1, $select_from_field_2, $select_custom_values, $select_multiple)
    {
        $fp = \strtolower('database/' . $this->database . '/' . $this->table . '.json');
        if (!file_exists(GENERATOR_DIR . $fp)) {
            exit('json file not found');
        }
        $json          = file_get_contents(GENERATOR_DIR . $fp);
        $json_data     = json_decode($json, true);

        $index = array_search($column, $json_data['columns']['name']);
        $this->columns['select_from'][$index]          = $select_from;
        $this->columns['select_from_table'][$index]    = $select_from_table;
        $this->columns['select_from_value'][$index]    = $select_from_value;
        $this->columns['select_from_field_1'][$index]  = $select_from_field_1;
        $this->columns['select_from_field_2'][$index]  = $select_from_field_2;
        $this->columns['select_custom_values'][$index] = $select_custom_values;
        $this->columns['select_multiple'][$index]      = $select_multiple;

        $json_data = array(
            'list_options'           => $this->list_options,
            'columns'                => $this->columns,
            'external_columns'       => $this->external_columns,
            'field_delete_confirm_1' => $this->field_delete_confirm_1,
            'field_delete_confirm_2' => $this->field_delete_confirm_2
        );

        // register table & columns properties in json file
        $json = json_encode($json_data, JSON_UNESCAPED_UNICODE);
        $this->registerJson($this->table . '.json', $json);
    }

    public function getSelectValues($column)
    {
        $index = array_search($column, $this->columns['name']);
        if (empty($this->columns['select_from'][$index])) {
            return NONE;
        } else {
            if ($this->columns['select_from'][$index] == 'from_table') {
                $values = $this->columns['select_from_table'][$index] . '.' . $this->columns['select_from_field_1'][$index];
                if (!empty($this->columns['select_from_field_2'][$index])) {
                    $values .= ', ' . $this->columns['select_from_table'][$index] . '.' . $this->columns['select_from_field_2'][$index];
                }

                return $values;
            } else {
                return CUSTOM_VALUES;
            }
        }
    }

    /**
     * remove table from admin sidenav
     * @return Boolean
     */
    private function unregisterNavTable($table)
    {
        $dir_path  = array();
        $file_name = array();


        // nav data (admin/crud-data/nav-data.json)
        $json_data = array();
        if (file_exists(ADMIN_DIR . 'crud-data/nav-data.json')) {
            $json               = file_get_contents(ADMIN_DIR . 'crud-data/nav-data.json');
            $json_data          = json_decode($json, true);

            if (empty($json_data)) {
                $this->logMessage('<strong>--- unregisterNavTable: </strong> nav data is empty');

                return false;
            }

            // try to find the table in nav categories
            foreach ($json_data as $navcat => $data) {
                // "navcat-0": { "name": "Inventory", "tables": ["customer"], "is_disabled": [false] }
                $tbls      = $data['tables'];
                $is_disabled = $data['is_disabled'];
                if (is_array($tbls) && in_array($table, $tbls)) {
                    $key = array_search($table, $tbls);
                    unset($tbls[$key]);
                    unset($is_disabled[$key]);
                    $json_data[$navcat]['tables']      = array_values($tbls);
                    $json_data[$navcat]['is_disabled'] = array_values($is_disabled);
                    $dir              = ADMIN_DIR . 'crud-data/';
                    $file             = 'nav-data.json';
                    $dir_path[]       = $dir;
                    $file_name[]      = $file;
                    $this->registerAdminFile($dir, $file, json_encode($json_data, JSON_UNESCAPED_UNICODE));

                    $this->logMessage('<strong>--- unregisterNavTable: </strong> ' . $table);

                    return true;
                }
            }

            // if the table hasn't been found in previous loop
            $this->logMessage('<strong>--- unregisterNavTable: </strong> ' . $table . ' table not found in nav data');

            return false;
        }
    }

    private function registerNavData()
    {
        $dir_path  = array();
        $file_name = array();

        // nav data (admin/crud-data/nav-data.json)
        $json_data = array();
        if (file_exists(ADMIN_DIR . 'crud-data/nav-data.json')) {
            $json               = file_get_contents(ADMIN_DIR . 'crud-data/nav-data.json');
            $json_data          = json_decode($json, true);
        }

        if (empty($json_data)) {
            $json_data = array(
                'navcat-0' => array(
                    'name'        => EDITABLE_CONTENT,
                    'tables'      => array(),
                    'is_disabled' => array()
                )
            );
        }

        // try to find the current table in nav categories
        $current_cat = '';
        foreach ($json_data as $navcat => $data) {
            $tbls = $data['tables'];
            if (is_array($tbls) && in_array($this->table, $tbls)) {
                $key = array_search($this->table, $tbls);
                $current_cat = $tbls[$key];
            }
            $last_cat = $navcat;
        }

        // if the current table is not already in nav categories, we add it to the last one.
        if (empty($current_cat)) {
            $json_data[$last_cat]['tables'][]      = $this->table;
            $json_data[$last_cat]['is_disabled'][] = false;
            $dir              = ADMIN_DIR . 'crud-data/';
            $file             = 'nav-data.json';
            $dir_path[]       = $dir;
            $file_name[]      = $file;
            $this->registerAdminFile($dir, $file, json_encode($json_data, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * build files depending on current action
     * @return [type] [description]
     */
    public function runBuild()
    {
        if (DEMO !== true) {
            if ($this->action == 'build_create_edit') {
                $this->buildCreateUpdate();
            } elseif ($this->action == 'build_paginated_list') {
                $this->buildPaginatedList();
            } elseif ($this->action == 'build_single_element_list') {
                $this->buildSingleElementList();
            } elseif ($this->action == 'build_delete') {
                $this->buildDelete();
            }
        } elseif ($this->action == 'build_create_edit' || $this->action == 'build_paginated_list' || $this->action == 'build_single_element_list' || $this->action == 'build_delete') {
            $this->userMessage('Feature disabled in DEMO', 'alert-danger has-icon');
        }
    }

    private function buildCreateUpdate()
    {
        $dir_path  = array();
        $file_name = array();

        $itm = $this->item;

        // form create (/admin/inc/forms/[lowertable]-create.php)
        ob_start();
        include GENERATOR_DIR . 'generator-templates/form-create-template.php';
        $output_form_create = ob_get_contents();
        ob_end_clean();
        $dir = ADMIN_DIR . 'inc/forms/';
        $file = $itm . '-create.php';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $output_form_create);

        // form edit (/admin/inc/forms/[lowertable]-edit.php)
        ob_start();
        include GENERATOR_DIR . 'generator-templates/form-edit-template.php';
        $output_form_edit = ob_get_contents();
        ob_end_clean();
        $dir = ADMIN_DIR . 'inc/forms/';
        $file = $itm . '-edit.php';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $output_form_edit);

        // select-data (admin/crud-data/[table]-select-data.json)
        $select_data = array();
        for ($i = 0; $i < $this->columns_count; $i++) {
            $name               = $this->columns['name'][$i];
            $this->getSelectValues($name);
            $select_data[$name] = array(
                'from'          => $this->columns['select_from'][$i],
                'from_table'    => $this->columns['select_from_table'][$i],
                'from_value'    => $this->columns['select_from_value'][$i],
                'from_field_1'  => $this->columns['select_from_field_1'][$i],
                'from_field_2'  => $this->columns['select_from_field_2'][$i],
                'custom_values' => $this->columns['select_custom_values'][$i],
                'multiple'      => $this->columns['select_multiple'][$i]
            );
        }

        $json = json_encode($select_data, JSON_UNESCAPED_UNICODE);
        $dir = ADMIN_DIR . 'crud-data/';
        $file = $itm . '-select-data.json';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $json);

        // edit admin/inc/forms/[userstable]profiles[-create|-edit].php
        // for specific customization
        include_once ADMIN_DIR . 'secure/conf/conf.php';
        if ($this->table == USERS_TABLE . '_profiles') {
            include_once ADMIN_DIR . 'secure/install/users-profiles-form-edit-customization.php';
        }

        // delete form css & js combined files to regenerate them if plugins have changed
        if (file_exists(CLASS_DIR . 'phpformbuilder/plugins/min/css/bs4-form-create-' . $itm . '.min.css')) {
            unlink(CLASS_DIR . 'phpformbuilder/plugins/min/css/bs4-form-create-' . $itm . '.min.css');
        }

        if (file_exists(CLASS_DIR . 'phpformbuilder/plugins/min/js/bs4-form-create-' . $itm . '.min.js')) {
            unlink(CLASS_DIR . 'phpformbuilder/plugins/min/js/bs4-form-create-' . $itm . '.min.js');
        }

        $list_url = ADMIN_URL . $itm;
        $title = '<span class="badge bg-success-600 fs-6 me-4 mb-0">' . $this->table . '</span> ' . FORMS_GENERATED . '<a href="' . $list_url . '" class="text-bg-success-200 text-decoration-none px-2 py-1 ms-4" target="_blank">' . OPEN_ADMIN_PAGE . '<i class="fas fa-external-link-alt append"></i></a>';
        $msg_body = '<p class="text-semibold">' . CREATED_UPDATED_FILES . ' : </p>' . "\n";
        $msg_body .= '<ul class="list-square">';
        $dir_path_count = 0;
        if (is_countable($dir_path)) {
            $dir_path_count = count($dir_path);
        }
        for ($i = 0; $i < $dir_path_count; $i++) {
            $msg_body .= '<li>' . $dir_path[$i] . $file_name[$i] . '</li>';
        }
        $msg_body .= '</ul>';

        $this->userMessage($title, 'bg-success card-collapsed has-icon', 'collapse, close', $msg_body);
    }

    private function buildPaginatedList()
    {
        $this->registerNavData();

        // main class (/admin/class/crud/[Table].php)
        ob_start();
        include GENERATOR_DIR . 'generator-templates/item-class-template.php';
        $output_item_class = ob_get_contents();
        ob_end_clean();
        $dir = ADMIN_DIR . 'class/crud/';
        $upperCamelCaseTable = ElementsUtilities::upperCamelCase($this->table);
        $itm = $this->item;
        $file = $upperCamelCaseTable . '.php';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $output_item_class);

        // list template (/admin/templates/[lowertable].html)
        ob_start();
        include GENERATOR_DIR . 'generator-templates/item-list-template.php';
        $output_item_template = ob_get_contents();
        ob_end_clean();
        $dir = ADMIN_DIR . 'templates/';
        $file = $itm . '.html';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $output_item_template);

        // single record view (/admin/templates/single-record-views/[lowertable].html)
        if (isset($_POST['rp_view_record']) && $_POST['rp_view_record'] > 0) {
            ob_start();
            include GENERATOR_DIR . 'generator-templates/item-single-record-view-template.php';
            $output_item_single_record_view_template = ob_get_contents();
            ob_end_clean();
            $dir = ADMIN_DIR . 'templates/single-record-views/';
            $file = $itm . '.html';
            $dir_path[]  = $dir;
            $file_name[] = $file;
            $this->registerAdminFile($dir, $file, $output_item_single_record_view_template);
        }

        // form bulk delete (/admin/inc/forms/[lowertable]-bulk-delete.php)
        if (isset($_POST['rp_bulk_delete']) && $_POST['rp_bulk_delete'] > 0) {
            $bulk_delete_output = $this->buildBulkDelete();
            $dir_path = array_merge($dir_path, $bulk_delete_output['dir']);
            $file_name = array_merge($file_name, $bulk_delete_output['file']);
        }

        // db-data (admin/crud-data/db-data.json)
        if (file_exists(ADMIN_DIR . 'crud-data/db-data.json')) {
            $json    = file_get_contents(ADMIN_DIR . 'crud-data/db-data.json');
            $db_data = json_decode($json, true);
        } else {
            $db_data = array();
        }

        // create / edit table data (even if exists)
        $tbl              = $this->table;
        $tbl_label        = $this->table_label;
        $class_name       = $upperCamelCaseTable;
        $prim_keys        = $this->primary_keys;
        $ai_keys          = $this->auto_increment_keys;
        $f_del_confirm_1  = $this->field_delete_confirm_1;
        $f_del_confirm_2  = $this->field_delete_confirm_2;
        $tbl_icon         = $this->table_icon;
        $fields           = $this->columns['fields'];

        $db_data[$tbl] = array(
            'item'                   => $itm,
            'table_label'            => $tbl_label,
            'class_name'             => $class_name,
            'primary_keys'           => $prim_keys,
            'auto_increment_keys'    => $ai_keys,
            'field_delete_confirm_1' => $f_del_confirm_1,
            'field_delete_confirm_2' => $f_del_confirm_2,
            'icon'                   => $tbl_icon,
            'fields'                 => $fields
        );
        $json = json_encode($db_data, JSON_UNESCAPED_UNICODE);
        $dir = ADMIN_DIR . 'crud-data/';
        $file = 'db-data.json';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $json);

        // filter-data (admin/data/[table]-filter-data.json)
        $filter_data = $this->list_options['filters'];
        $json = json_encode($filter_data, JSON_UNESCAPED_UNICODE);
        $dir = ADMIN_DIR . 'crud-data/';
        $file = $itm . '-filter-data.json';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $json);

        // select-data (admin/crud-data/[table]-select-data.json)
        $select_data = array();
        for ($i = 0; $i < $this->columns_count; $i++) {
            $name               = $this->columns['name'][$i];
            $select_data[$name] = array(
                'from'          => $this->columns['select_from'][$i],
                'from_table'    => $this->columns['select_from_table'][$i],
                'from_value'    => $this->columns['select_from_value'][$i],
                'from_field_1'  => $this->columns['select_from_field_1'][$i],
                'from_field_2'  => $this->columns['select_from_field_2'][$i],
                'custom_values' => $this->columns['select_custom_values'][$i],
                'multiple'      => $this->columns['select_multiple'][$i]
            );
        }

        $json = json_encode($select_data, JSON_UNESCAPED_UNICODE);
        $dir = ADMIN_DIR . 'crud-data/';
        $file = $itm . '-select-data.json';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $json);
        $list_url = ADMIN_URL . $itm;
        $title = '<span class="badge bg-success-600 fs-6 me-4 mb-0">' . $this->table . '</span> ' . LIST_GENERATED . '<a href="' . $list_url . '" class="text-bg-success-200 text-decoration-none px-2 py-1 ms-4" target="_blank">' . OPEN_ADMIN_PAGE . '<i class="fas fa-external-link-alt append"></i></a>';
        $msg_body = '<p class="text-semibold">' . CREATED_UPDATED_FILES . ' : </p>' . "\n";
        $msg_body .= '<ul class="list-square">';
        $dir_path_count = 0;
        if (is_countable($dir_path)) {
            $dir_path_count = count($dir_path);
        }
        for ($i = 0; $i < $dir_path_count; $i++) {
            $msg_body .= '<li>' . str_replace('\\', '/', $dir_path[$i]) . $file_name[$i] . '</li>';
        }
        $msg_body .= '</ul>';

        $this->userMessage($title, 'bg-success card-collapsed has-icon', 'collapse, close', $msg_body);
        $this->logMessage('<strong>buildPaginatedList</strong>');
    }

    private function buildSingleElementList()
    {
        $this->registerNavData();

        // main class (/admin/class/crud/[Table].php)
        ob_start();
        include_once GENERATOR_DIR . 'generator-templates/item-class-single-element-template.php';
        $output_item_class = ob_get_contents();
        ob_end_clean();
        $dir                 = ADMIN_DIR . 'class/crud/';
        $upperCamelCaseTable = ElementsUtilities::upperCamelCase($this->table);
        $itm                = $this->item;
        $file                = $upperCamelCaseTable . '.php';
        $dir_path[]          = $dir;
        $file_name[]         = $file;
        $this->registerAdminFile($dir, $file, $output_item_class);

        // list template (/admin/templates/[lowertable].html)
        ob_start();
        include_once GENERATOR_DIR . 'generator-templates/item-list-single-element-template.php';
        $output_item_template = ob_get_contents();
        ob_end_clean();
        $dir = ADMIN_DIR . 'templates/';
        $file = $itm . '.html';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $output_item_template);

        // db-data (admin/crud-data/db-data.json)
        if (file_exists(ADMIN_DIR . 'crud-data/db-data.json')) {
            $json    = file_get_contents(ADMIN_DIR . 'crud-data/db-data.json');
            $db_data = json_decode($json, true);
        } else {
            $db_data = array();
        }

        // create / edit table data (even if exists)
        $tbl              = $this->table;
        $tbl_label        = $this->table_label;
        $class_name       = $upperCamelCaseTable;
        $prim_keys        = $this->primary_keys;
        $ai_keys          = $this->auto_increment_keys;
        $f_del_confirm_1  = $this->field_delete_confirm_1;
        $f_del_confirm_2  = $this->field_delete_confirm_2;
        $tbl_icon         = $this->table_icon;
        $fields           = $this->columns['fields'];

        $db_data[$tbl] = array(
            'item'                   => $itm,
            'table_label'            => $tbl_label,
            'class_name'             => $class_name,
            'primary_keys'           => $prim_keys,
            'auto_increment_keys'    => $ai_keys,
            'field_delete_confirm_1' => $f_del_confirm_1,
            'field_delete_confirm_2' => $f_del_confirm_2,
            'icon'                   => $tbl_icon,
            'fields'                 => $fields
        );
        $json = json_encode($db_data, JSON_UNESCAPED_UNICODE);
        $dir = ADMIN_DIR . 'crud-data/';
        $file = 'db-data.json';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $json);

        // filter-data (admin/data/[table]-filter-data.json)
        $filter_data = $this->list_options['filters'];
        $json = json_encode($filter_data, JSON_UNESCAPED_UNICODE);
        $dir = ADMIN_DIR . 'crud-data/';
        $file = $itm . '-filter-data.json';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $json);

        // select-data (admin/crud-data/[table]-select-data.json)
        $select_data = array();
        for ($i = 0; $i < $this->columns_count; $i++) {
            $name               = $this->columns['name'][$i];
            $select_data[$name] = array(
                'from'          => $this->columns['select_from'][$i],
                'from_table'    => $this->columns['select_from_table'][$i],
                'from_value'    => $this->columns['select_from_value'][$i],
                'from_field_1'  => $this->columns['select_from_field_1'][$i],
                'from_field_2'  => $this->columns['select_from_field_2'][$i],
                'custom_values' => $this->columns['select_custom_values'][$i],
                'multiple'      => $this->columns['select_multiple'][$i]
            );
        }

        $json = json_encode($select_data, JSON_UNESCAPED_UNICODE);
        $dir = ADMIN_DIR . 'crud-data/';
        $file = $itm . '-select-data.json';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $json);
        $list_url = ADMIN_URL . $itm;
        $title = '<span class="badge bg-success-600 fs-6 me-4 mb-0">' . $this->table . '</span> ' . LIST_GENERATED . '<a href="' . $list_url . '" class="text-bg-success-200 text-decoration-none px-2 py-1 ms-4" target="_blank">' . OPEN_ADMIN_PAGE . '<i class="fas fa-external-link-alt append"></i></a>';
        $msg_body = '<p class="text-semibold">' . CREATED_UPDATED_FILES . ' : </p>' . "\n";
        $msg_body .= '<ul class="list-square">';
        $dir_path_count = 0;
        if (is_countable($dir_path)) {
            $dir_path_count = count($dir_path);
        }
        for ($i = 0; $i < $dir_path_count; $i++) {
            $msg_body .= '<li>' . str_replace('\\', '/', $dir_path[$i]) . $file_name[$i] . '</li>';
        }
        $msg_body .= '</ul>';

        $this->userMessage($title, 'bg-success card-collapsed has-icon', 'collapse, close', $msg_body);
        $this->logMessage('<strong>buildSingleElementList</strong>');
    }

    private function buildDelete()
    {
        $dir_path  = array();
        $file_name = array();

        $itm = $this->item;

        // register cascade_delete relations
        $this->getRelations();
        if (isset($_POST['from_to_indexes'])) {
            foreach ($_POST['from_to_indexes'] as $index) {
                $cascade_delete_origin_field = 'constrained_tables_' . $this->relations['from_to'][$index]['origin_table'];
                $this->relations['from_to'][$index]['cascade_delete_from_origin'] = $_POST[$cascade_delete_origin_field];
                if (!empty($this->relations['from_to'][$index]['intermediate_table'])) {
                    $cascade_delete_intermediate_field = 'constrained_tables_' . $this->relations['from_to'][$index]['intermediate_table'];
                    $this->relations['from_to'][$index]['cascade_delete_from_intermediate'] = $_POST[$cascade_delete_intermediate_field];
                }
            }
        }

        // form delete (/admin/inc/forms/[lowertable]-delete.php)
        ob_start();
        include_once GENERATOR_DIR . 'generator-templates/form-delete-template.php';
        $output_form_delete = ob_get_contents();
        ob_end_clean();
        $dir = ADMIN_DIR . 'inc/forms/';
        $file = $itm . '-delete.php';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $output_form_delete);

        // register table & columns properties in json file
        $json_data = json_encode($this->relations, JSON_UNESCAPED_UNICODE);
        $this->registerJson($this->database . '-relations.json', $json_data);

        $list_url = ADMIN_URL . $itm;
        $title = '<span class="badge bg-success-600 fs-6 me-4 mb-0">' . $this->table . '</span> ' . FORMS_GENERATED . '<a href="' . $list_url . '" class="text-bg-success-200 text-decoration-none px-2 py-1 ms-4" target="_blank">' . OPEN_ADMIN_PAGE . '<i class="fas fa-external-link-alt append"></i></a>';
        $msg_body = '<p class="text-semibold">' . CREATED_UPDATED_FILES . ' : </p>' . "\n";
        $msg_body .= '<ul class="list-square">';
        $dir_path_count = 0;
        if (is_countable($dir_path)) {
            $dir_path_count = count($dir_path);
        }
        for ($i = 0; $i < $dir_path_count; $i++) {
            $msg_body .= '<li>' . $dir_path[$i] . $file_name[$i] . '</li>';
        }
        $msg_body .= '</ul>';

        $this->userMessage($title, 'bg-success card-collapsed has-icon', 'collapse, close', $msg_body);
        $this->logMessage('<strong>buildDelete</strong>');
    }

    private function buildBulkDelete()
    {
        $dir_path  = array();
        $file_name = array();

        $itm = $this->item;

        // register cascade_delete relations
        // there's no difference between bulk_constrained_tables_ and constrained_tables_
        // as the radio buttons change simultaneously in the READ form & the DELETE form
        $this->getRelations();
        if (isset($_POST['bulk_from_to_indexes'])) {
            foreach ($_POST['bulk_from_to_indexes'] as $index) {
                $cascade_delete_origin_field = 'bulk_constrained_tables_' . $this->relations['from_to'][$index]['origin_table'];
                $this->relations['from_to'][$index]['cascade_delete_from_origin'] = $_POST[$cascade_delete_origin_field];
                if (!empty($this->relations['from_to'][$index]['intermediate_table'])) {
                    $cascade_delete_intermediate_field = 'bulk_constrained_tables_' . $this->relations['from_to'][$index]['intermediate_table'];
                    $this->relations['from_to'][$index]['cascade_delete_from_intermediate'] = $_POST[$cascade_delete_intermediate_field];
                }
            }
        }

        // form delete (/admin/inc/forms/[lowertable]-bulk-delete.php)
        ob_start();
        include_once GENERATOR_DIR . 'generator-templates/bulk-delete-template.php';
        $output_form_delete = ob_get_contents();
        ob_end_clean();
        $dir = ADMIN_DIR . 'inc/forms/';
        $file = $itm . '-bulk-delete.php';
        $dir_path[]  = $dir;
        $file_name[] = $file;
        $this->registerAdminFile($dir, $file, $output_form_delete);

        // register table & columns properties in json file
        $json_data = json_encode($this->relations, JSON_UNESCAPED_UNICODE);
        $this->registerJson($this->database . '-relations.json', $json_data);

        $this->logMessage('<strong>buildBulkDelete</strong>');

        // return dir & created files
        return array(
            'dir'  => $dir_path,
            'file' => $file_name
        );
    }

    /**
     * Create form with all backup files in a dropdown select
     */
    public function createDiffFileList()
    {
        $files_to_diff   = $this->scanDirectories(BACKUP_DIR);
        $files_to_diff_count = 0;
        if (is_countable($files_to_diff)) {
            $files_to_diff_count = count($files_to_diff);
        }
        if ($files_to_diff_count > 0) {
            $this->diff_files_form = new Form('diff-files-form', 'vertical');
            $this->diff_files_form->setAction(GENERATOR_URL . 'inc/tabs-contents/diff-files.php');
            $this->diff_files_form->useLoadJs('core');
            $this->diff_files_form->setMode('development');
            $options = array(
                'verticalLabelClass' => 'form-label pe-0'
            );
            $this->diff_files_form->setOptions($options);
            foreach ($files_to_diff as $value) {
                $optgroup = 'Root';
                $value = str_replace(BACKUP_DIR, '', $value);
                $file_name = basename($value);
                $file_dir = ltrim(str_replace($file_name, '', $value), '/');
                if (!empty($file_dir) && $file_dir !== $optgroup) {
                    $optgroup = $file_dir;
                }
                $this->diff_files_form->addOption('file-to-diff', ltrim($value, '/'), $file_name, $optgroup);
            }
            $this->diff_files_form->addSelect('file-to-diff', DIFF_FILES, 'data-slimselect=true, data-allow-deselect=false');
            $this->diff_files_form->addBtn('submit', 'submit', 1, COMPARE . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-lg btn-primary my-5');
        }
    }

    /**
     * call this function from the generator templates to write debug comments into the generated admin classes/forms
     * @param mixed $var
     * @return void
     */
    public function debug($var)
    {
        echo "/* DEBUG **********\n";
        print_r($var);
        echo "\nEND DEBUG *******/\n";
    }

    /**
     * reset descending data from database
     * @param  string $level database|tables|table
     * @return null
     */
    public function reset($level)
    {
        if ($level == 'tables') {
            $this->tables     = array();
            $this->table      = '';
            $this->item       = '';
            $this->columns    = array();
            $this->db_columns = array();
        } elseif ($level == 'columns') {
            $this->columns    = array();
            $this->db_columns = array();
        }
        $this->logMessage('<strong>reset</strong> => ' . $level);
    }

    /**
     * replace chars from string to make readable name
     * @param  string $string
     * @return string
     */
    public function toReadable($string)
    {
        $find = array('`-`', '`_`');
        $replace = array(' ', ' ');

        return ucfirst(strtolower(preg_replace($find, $replace, $string)));
    }

    /**
     * get database columns data corresponding to generator needs
     * @return
     * array(
     *     type =>
     *     validation =>
     *     primary => true|false
     *     auto_increment => true|false
     * )
     */
    private function getColumnsDataFromDb()
    {
        $columns_data = array(
            'column_type',
            'column_type_full',
            'select_values',
            'validation',
            'primary',
            'auto_increment',
            'auto_increment_function'
        );
        for ($i = 0; $i < $this->columns_count; $i++) {
            $columns_data['column_type_full'][] = $this->db_columns['type'][$i];
            // get type before parenthesis
            $pos = strpos($this->db_columns['type'][$i], '(');
            if ($pos === false) {
                $column_type = $this->db_columns['type'][$i];
            } else {
                $type = substr($this->db_columns['type'][$i], 0, $pos);
                if (in_array($type, $this->valid_db_types)) {
                    // detect if boolean from records values
                    if ($this->db_columns['type'][$i] == 'tinyint') {
                        $db = $this->connectDb();
                        $db->selectRow($this->table, $this->db_columns['name'][$i], array($this->db_columns['name'][$i] . ' >' => 1));
                        $db_count = $db->rowCount();
                        if (!empty($db_count)) {
                            $type = 'tinyint';
                        } else {
                            $type = 'boolean';
                        }
                    }
                    $column_type = $type;
                } else {
                    // default if type not found
                    $column_type = 'varchar';
                }
            }
            $columns_data['column_type'][] = $column_type;

            // get select values if enum|set
            if ($column_type == 'enum' || $column_type == 'set') {
                // Remove "set(" at start and ");" at end.
                $set  = substr($this->db_columns['type'][$i], 5, strlen($this->db_columns['type'][$i]) - 7);

                // Split into an array.
                $array_values = explode(',', str_replace("'", '', $set));

                // convert to associative array
                $assoc_array = array();
                foreach ($array_values as $value) {
                    if (!empty($value)) {
                        $assoc_array[$value] = $value;
                    }
                }
                $columns_data['select_values'][] = $assoc_array;
            } else {
                $columns_data['select_values'][] = '';
            }
            $columns_data['validation'][] = $this->getValidation($column_type, $i);
            if ($this->db_columns['key'][$i] == 'PRI') {
                $columns_data['primary'][]    = true;
                $this->field_delete_confirm_1 = $this->db_columns['name'][$i];
                $this->field_delete_confirm_2 = '';
            } else {
                $columns_data['primary'][] = false;
            }
            if (\strpos($this->db_columns['extra'][$i], 'auto_increment') !== false) {
                $columns_data['auto_increment'][] = true;
                if (preg_match('`%(.*)%$`', $this->db_columns['extra'][$i], $out)) {
                    $columns_data['auto_increment_function'][] = $out[1];
                } else {
                    $columns_data['auto_increment_function'][] = '';
                }
            } else {
                $columns_data['auto_increment'][] = false;
                $columns_data['auto_increment_function'][] = '';
            }
        }
        $this->logMessage('<strong>getColumnsDataFromDb</strong>');

        return $columns_data;
    }

    /**
     * deduct validation from db column type
     * @param  string $column_type    column type before parenthesis
     * @param  number $i              column index
     * @return array  $validation
     */
    public function getValidation($column_type, $i)
    {
        $db_column_type = $this->db_columns['type'][$i];
        $db_column_null = $this->db_columns['null'][$i];
        $validation = array();
        // no validation if column can be null
        // if ($db_column_null !== 'YES' || $this->db_columns['extra'][0] == 'auto_increment') {
        $int        = array('tinyint', 'smallint', 'mediumint', 'int', 'bigint');
        $decimal    = array('decimal', 'numeric', 'float', 'double', 'real');
        $boolean    = array('boolean');
        $date_time  = array('date', 'datetime', 'timestamp', 'time', 'year');
        $string     = array('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext');
        $sets       = array('enum', 'set');
        if ($db_column_null == 'NO') {
            $validation[] = array(
                'function' => 'required',
                'args' => ''
            );
        }
        if (in_array($column_type, $int)) {
            // validate integer
            $validation[] = array(
                'function' => 'integer',
                'args' => ''
            );
            preg_match('`([a-z]+)\(([0-9]+)\)`', $db_column_type, $out);

            // calculate unsigned min max values
            if (preg_match('`unsigned`', $db_column_type)) {
                $min = '0';
                // convert number of values to '9'. ex : 2 => 99
                $max = $this->getMaxNumber($out[2]);
                if ($column_type == 'tinyint' && $out[2] == 3) {
                    $max = '255';
                } elseif ($column_type == 'smallint' && $out[2] == 5) {
                    $max = '65535';
                } elseif ($column_type == 'mediumint' && $out[2] == 8) {
                    $max = '16777215';
                } elseif ($column_type == 'int' && $out[2] == 10) {
                    $max = '4294967295';
                } elseif ($column_type == 'bigint' && $out[2] == 20) {
                    $max = '18446744073709551615';
                }
            } elseif (is_array($out) && count($out) > 1) {
                // convert number of values to '9'. ex : 2 => 99
                $min = - ($this->getMaxNumber($out[2]));
                $max = $this->getMaxNumber($out[2]);
                if ($column_type == 'tinyint' && $out[2] == 3) {
                    $min = '-128';
                    $max = '127';
                } elseif ($column_type == 'smallint' && $out[2] == 5) {
                    $min = '-32768';
                    $max = '32767';
                } elseif ($column_type == 'mediumint' && $out[2] == 8) {
                    $min = '-8388608';
                    $max = '8388607';
                } elseif ($column_type == 'int' && $out[2] == 10) {
                    $min =  '-2147483648';
                    $max = '2147483647';
                } elseif ($column_type == 'bigint' && $out[2] == 20) {
                    $min = '-9223372036854775808';
                    $max = '9223372036854775807';
                }
            } else {
                if ($column_type == 'tinyint') {
                    $min = '-128';
                    $max = '127';
                } elseif ($column_type == 'smallint') {
                    $min = '-32768';
                    $max = '32767';
                } elseif ($column_type == 'mediumint') {
                    $min = '-8388608';
                    $max = '8388607';
                } elseif ($column_type == 'int') {
                    $min =  '-2147483648';
                    $max = '2147483647';
                } elseif ($column_type == 'bigint') {
                    $min = '-9223372036854775808';
                    $max = '9223372036854775807';
                }
            }
            $validation[] = array(
                'function' => 'min',
                'args' => $min
            );
            $validation[] = array(
                'function' => 'max',
                'args' => $max
            );
        } elseif (in_array($column_type, $decimal) && preg_match('`([a-z]+)\(([0-9]+),([0-9]+)\)`', $db_column_type, $out)) {
            // validate decimal
            $validation[] = array(
                'function' => 'float',
                'args' => ''
            );
            $max = 999999999999999999999999999999999999999999;
            if (preg_match('`([a-z]+)\(([0-9]+),([0-9]+)\)`', $db_column_type, $out)) {
                // calculate min / max values
                $max = $this->getMaxNumber($out[2], $out[3]);
            }
            if (preg_match('`unsigned`', $db_column_type)) {
                $min = 0;
            } else {
                $min = -(int)$max;
            }
            $validation[] = array(
                'function' => 'min',
                'args' => $min
            );
            $validation[] = array(
                'function' => 'max',
                'args' => $max
            );
        } elseif (in_array($column_type, $boolean)) {
            $validation[] = array(
                'function' => 'min',
                'args' => '0'
            );
            $validation[] = array(
                'function' => 'max',
                'args' => '1'
            );
        } elseif (in_array($column_type, $date_time)) {
            $validation[] = array(
                'function' => 'date',
                'args' => ''
            );
        } elseif (in_array($column_type, $string)) {
            if (preg_match('`([a-z]+)\(([0-9]+)\)`', $db_column_type, $out)) {
                $validation[] = array(
                    'function' => 'maxLength',
                    'args' => $out[2]
                );
            }
        } elseif (in_array($column_type, $sets)) {
            // Remove "[enum|set](" at start and ");" at end.
            if ($column_type == 'enum') {
                $out  = substr($db_column_type, 6, strlen($db_column_type) - 8);
            } else {
                $out  = substr($db_column_type, 5, strlen($db_column_type) - 7);
            }
            $validation[] = array(
                'function' => 'oneOf',
                'args' => "'" . str_replace('\',\'', ',', $out) . "'"
            );
        }
        // }

        return $validation;
    }

    /**
     * convert int or decimal '9'.
     * 2 returns 99
     * 3.2 returns 999.99
     * @param  int $int
     * @return int $decimal
     */
    private function getMaxNumber($int, $decimal = '')
    {
        $units = '';
        for ($i = 0; $i < $int; $i++) {
            $units .= '9';
        }
        $dec = '';
        if (!empty($decimal)) {
            $dec = '.';
            for ($i = 0; $i < $decimal; $i++) {
                $dec .= '9';
            }
        }

        return $units . $dec;
    }

    /** delete relations json file */
    public function resetRelations()
    {
        $fp = \strtolower('database/' . $this->database . '/' . $this->database . '-relations.json');
        if (file_exists(GENERATOR_DIR . $fp) && !@unlink(GENERATOR_DIR . $fp)) {
            $this->userMessage(FAILED_TO_DELETE . ' ' . GENERATOR_DIR . $fp, 'alert-danger has-icon');
        }
    }

    /**
     * if $this->simulate_and_debug === true, will JUST copy table data in backup dir (delete nothing)
     *
     * copy table data in backup dir
     * then delete table data
     * files :
     *     admin/crud-data/db-data.json (delete data)
     *     item-filter-data.json (delete file)
     *     item-select-data.json (delete file)
     * @return void
     */
    private function deleteTableData($table = '')
    {
        if (empty($table)) {
            $table = $this->table;
            $itm  = $this->item;
        } else {
            $upperCamelCaseTable = ElementsUtilities::upperCamelCase($table);
            $itm = mb_strtolower($upperCamelCaseTable);
        }
        if ($this->simulate_and_debug !== true) {
            $msg = array();

            $this->logMessage('<strong>deleteTableData</strong>');

            // Generate backup file then delete generator table.json
            $path = GENERATOR_DIR . 'database/' . \strtolower($this->database);
            $file = \strtolower($table) . '.json';
            if (file_exists($path . '/' . $file)) {
                // Generate backup file
                $backup_path = str_replace(GENERATOR_DIR, BACKUP_DIR, $path);
                if (!is_dir($backup_path) && !mkdir($backup_path, 0775)) {
                    $this->userMessage(ERROR_CANT_CREATE_DIR . ' ' . $backup_path, 'alert-danger has-icon');

                    return false;
                }
                if (copy($path . '/' . $file, $backup_path . '/' . $file) === false) {
                    $this->userMessage(ERROR_CANT_WRITE_FILE . ' ' . $backup_path . '/' . $file, 'alert-danger has-icon');
                }

                if ($this->simulate_and_debug !== true) {
                    // Delete file
                    if (!@unlink($path . '/' . $file)) {
                        $msg[] = FAILED_TO_DELETE . ' ' . $path . '/' . $file;
                    } else {
                        $this->logMessage('<strong>--- unlink </strong>' . $file);
                    }
                }
            }

            // remove table from db-data (admin/crud-data/db-data.json)
            $path = ADMIN_DIR . 'crud-data';
            $file = 'db-data.json';
            if (file_exists($path . '/' . $file)) {
                // Generate backup file
                $backup_path = str_replace(ADMIN_DIR, BACKUP_DIR, $path);
                if (!is_dir($backup_path) && !mkdir($backup_path, 0775)) {
                    $this->userMessage(ERROR_CANT_CREATE_DIR . ' ' . $backup_path, 'alert-danger has-icon');

                    return false;
                }
                if (copy($path . '/' . $file, $backup_path . '/' . $file) === false) {
                    $this->userMessage(ERROR_CANT_WRITE_FILE . ' ' . $backup_path . '/' . $file, 'alert-danger has-icon');
                }

                if ($this->simulate_and_debug !== true) {
                    // delete table data
                    $json    = file_get_contents($path . '/' . $file);
                    $db_data = json_decode($json, true);
                    if (isset($db_data[$table])) {
                        unset($db_data[$table]);
                        $json = json_encode($db_data, JSON_UNESCAPED_UNICODE);
                        $this->registerAdminFile($path . '/', $file, $json);
                    }
                }
            }

            // backup & delete admin item-filter-data.json
            $path = ADMIN_DIR . 'crud-data';
            $file = \strtolower($itm) . '-filter-data.json';
            if (file_exists($path . '/' . $file)) {
                // Generate backup file
                $backup_path = str_replace(ADMIN_DIR, BACKUP_DIR, $path);
                if (!is_dir($backup_path) && !mkdir($backup_path, 0775)) {
                    $this->userMessage(ERROR_CANT_CREATE_DIR . ' ' . $backup_path, 'alert-danger has-icon');

                    return false;
                }
                if (copy($path . '/' . $file, $backup_path . '/' . $file) === false) {
                    $this->userMessage(ERROR_CANT_WRITE_FILE . ' ' . $backup_path . '/' . $file, 'alert-danger has-icon');
                }

                if ($this->simulate_and_debug !== true) {
                    if (!@unlink($path . '/' . $file)) {
                        $msg[] = FAILED_TO_DELETE . ' ' . $path . '/' . $file;
                    } else {
                        $this->logMessage('<strong>--- unlink </strong>' . $file);
                    }
                }
            }

            // backup & delete admin item-select-data.json
            $path = ADMIN_DIR . 'crud-data';
            $file = \strtolower($itm) . '-select-data.json';
            if (file_exists($path . '/' . $file)) {
                // Generate backup file
                $backup_path = str_replace(ADMIN_DIR, BACKUP_DIR, $path);
                if (!is_dir($backup_path) && !mkdir($backup_path, 0775)) {
                    $this->userMessage(ERROR_CANT_CREATE_DIR . ' ' . $backup_path, 'alert-danger has-icon');

                    return false;
                }
                if (copy($path . '/' . $file, $backup_path . '/' . $file) === false) {
                    $this->userMessage(ERROR_CANT_WRITE_FILE . ' ' . $backup_path . '/' . $file, 'alert-danger has-icon');
                }

                if ($this->simulate_and_debug !== true) {
                    if (!@unlink($path . '/' . $file)) {
                        $msg[] = FAILED_TO_DELETE . ' ' . $path . '/' . $file;
                    } else {
                        $this->logMessage('<strong>--- unlink </strong>' . $file);
                    }
                }
            }

            if ($this->simulate_and_debug !== true) {
                // remove table from sidenav
                $this->unregisterNavTable($table);
            }

            $msg_count = 0;
            if (is_countable($msg)) {
                $msg_count = count($msg);
            }
            if ($msg_count > 0) {
                $msg = implode('<br>', $msg);

                // error message
                $this->userMessage($msg, 'alert-danger has-icon');
            } else {
                // all OK
                $this->userMessage(str_replace('%table%', $table, TABLE_HAS_BEEN_REMOVED), 'alert-success has-icon');
            }
        }
    }

    /**
     * if $this->simulate_and_debug === true, will just simulate and record results in class/generator/reload-table-data-debug.log
     * regenerate table data from database
     * then restore content from backup files :
     *     GENERATOR_DIR . 'database/' . $this->database . '/' . $this->table . '.json'
     *     ADMIN_DIR . 'crud-data/db-data.json'
     *     ADMIN_DIR . 'crud-data/' . $this->item . '-filter-data.json'
     *     ADMIN_DIR . 'crud-data/' . $this->item . '-select-data.json'
     *
     * @return [type] [description]
     */
    private function reloadTableData()
    {
        $this->logMessage('<strong>reloadTableData</strong>');

        // register columns from database in $this->table . '.json'
        $this->columns = array();
        $this->db_columns = array();
        $this->getDbColumns();
        $this->registerColumnsProperties();

        // restore content from backup files
        $backup_path = BACKUP_DIR . 'database/' . \strtolower($this->database);
        $path = str_replace(BACKUP_DIR, GENERATOR_DIR, $backup_path);
        $file = \strtolower($this->table) . '.json';
        if (file_exists($backup_path . '/' . $file) && file_exists($path . '/' . $file)) {
            // Restore backup content from table.json in generator file
            $json             = file_get_contents($backup_path . '/' . $file);
            $json_backup_data = json_decode($json, true);

            // array_intersect_key returns keys => values from array_1 if key exists in array_2
            // recursiveArrayIntersectKey = same function using recursive
            $this->list_options = self::recursiveArrayIntersectKey($json_backup_data['list_options'], $this->list_options);

            // restore columns data only for columns with same name & same column_type
            $columns_data_to_restore = array(
                'field_type',
                'ajax_loading',
                'relation',
                'validation_type',
                'value_type',
                'validation',
                'fields',
                'jedit',
                'special',
                'special2',
                'special3',
                'special4',
                'special5',
                'special6',
                'special7',
                'sorting',
                'nested',
                'skip',
                'select_from',
                'select_from_table',
                'select_from_value',
                'select_from_field_1',
                'select_from_field_2',
                'select_custom_values',
                'select_multiple',
                'help_text',
                'tooltip',
                'required',
                'char_count',
                'char_count_max',
                'tinyMce',
                'field_width',
                'field_height'
            );
            $count = 0;
            if (is_countable($this->columns['name'])) {
                $count = count($this->columns['name']);
            }
            if ($this->simulate_and_debug === true) {
                $content = array();
                $content[] = "\n\n" . '==================================== ' . "\n" . $file . "\n" . '====================================';
                file_put_contents('class/generator/reload-table-data-debug.log', implode("\n", $content) . "\n\n");
            }
            for ($i = 0; $i < $count; $i++) {
                $key = array_search($this->columns['name'][$i], $json_backup_data['columns']['name']);
                if ($key !== false && $this->columns['column_type'][$i] == $json_backup_data['columns']['column_type'][$key]) {
                    if ($this->simulate_and_debug === true) {
                        $content = array();
                        $content[] = 'FOUND FROM BACKUP column name: ' . $this->columns['name'][$i] . "\n" . '------------------------------------';
                        file_put_contents('class/generator/reload-table-data-debug.log', implode("\n", $content) . "\n\n", FILE_APPEND);
                    }
                    foreach ($columns_data_to_restore as $c_data) {
                        // $this->columns[$c_data] = self::recursiveArrayIntersectKey($json_backup_data['columns'][$c_data], $this->columns[$c_data]);
                        if (isset($json_backup_data['columns'][$c_data][$key])) {
                            $this->columns[$c_data][$i] = $json_backup_data['columns'][$c_data][$key];
                            if ($this->simulate_and_debug === true) {
                                $content = array();
                                $content[] = '      FOUND FROM BACKUP $c_data: ' . $c_data;
                                if (is_array($json_backup_data['columns'][$c_data][$key])) {
                                    $content[] = '  $this->columns[$c_data][$i] =  ' . var_export($json_backup_data['columns'][$c_data][$key], true);
                                } else {
                                    $content[] = '  $this->columns[$c_data][$i] =  ' . $json_backup_data['columns'][$c_data][$key];
                                }
                                file_put_contents('class/generator/reload-table-data-debug.log', implode("\n", $content) . "\n\n", FILE_APPEND);
                            }
                        }
                    }
                }
            }
            // $this->external_columns     = self::recursiveArrayIntersectKey($json_backup_data['external_columns'], $this->external_columns);
            if (in_array($json_backup_data['field_delete_confirm_1'], $this->columns['name'])) {
                $this->field_delete_confirm_1 = $json_backup_data['field_delete_confirm_1'];
                $this->field_delete_confirm_2 = $json_backup_data['field_delete_confirm_2'];
                if ($this->simulate_and_debug === true) {
                    $content = array();
                    $content[] = '      FOUND FROM BACKUP field_delete_confirm_1: ' . $this->field_delete_confirm_1;
                    $content[] = '      FOUND FROM BACKUP field_delete_confirm_2: ' . $this->field_delete_confirm_2;
                    file_put_contents('class/generator/reload-table-data-debug.log', implode("\n", $content) . "\n\n", FILE_APPEND);
                }
            } else {
                // default is primary key if old field_delete_confirm doesn't exist anymore
                $key = array_search(true, $this->columns['primary']);
                if ($key !== false) {
                    $this->field_delete_confirm_1 = $this->columns['name'][$key];
                } else {
                    $this->field_delete_confirm_1 = $this->columns['name'][0];
                }
                $this->field_delete_confirm_2 = '';
            }

            if ($this->simulate_and_debug !== true) {
                $json_data = array(
                    'list_options'           => $this->list_options,
                    'columns'                => $this->columns,
                    'external_columns'       => $this->external_columns,
                    'field_delete_confirm_1' => $this->field_delete_confirm_1,
                    'field_delete_confirm_2' => $this->field_delete_confirm_2
                );

                // register table & columns properties in json file
                $json = json_encode($json_data, JSON_UNESCAPED_UNICODE);
                $this->registerJson($this->table . '.json', $json);
            }
        }

        $backup_path = BACKUP_DIR . 'crud-data';
        $path = str_replace(BACKUP_DIR, ADMIN_DIR, $backup_path);
        $file = 'db-data.json';

        if ($this->simulate_and_debug === true) {
            $content = array();
            $content[] = "\n\n" . '==================================== ' . "\n" . $file . "\n" . '====================================';
            file_put_contents('class/generator/reload-table-data-debug.log', implode("\n", $content) . "\n\n", FILE_APPEND);
        }

        if (file_exists($backup_path . '/' . $file) && file_exists($path . '/' . $file)) {
            // Restore backup content in admin file
            $json             = file_get_contents($backup_path . '/' . $file);
            $json_backup_data = json_decode($json, true);

            // $json            = file_get_contents($path . '/' . $file);
            // $admin_json_data = json_decode($json, true);

            // $json_data = self::recursiveArrayIntersectKey($json_backup_data, $admin_json_data);
            $json_data = $json_backup_data;
            $tbl = $this->table;
            if (isset($json_backup_data[$tbl]['fields'])) {
                foreach ($this->columns['fields'] as $column_name => $column_label) {
                    if (isset($json_backup_data[$tbl]['fields'][$column_name])) {
                        $this->columns['fields'][$column_name] = $json_backup_data[$tbl]['fields'][$column_name];
                        if ($this->simulate_and_debug === true) {
                            $content = array();
                            $content[] = '      FOUND FROM BACKUP $column_name: ' . $column_name;
                            file_put_contents('class/generator/reload-table-data-debug.log', implode("\n", $content) . "\n\n", FILE_APPEND);
                        }
                    }
                }
            }

            $tbl_icon             = $this->table_icon;
            $tbl_label            = $this->table_label;
            $upperCamelCaseTable  = ElementsUtilities::upperCamelCase($this->table);
            $class_name           = $upperCamelCaseTable;
            $itm                  = $this->item;
            $prim_keys            = $this->primary_keys;
            $ai_keys              = $this->auto_increment_keys;
            $f_del_confirm_1      = $this->field_delete_confirm_1;
            $f_del_confirm_2      = $this->field_delete_confirm_2;
            $fields               = $this->columns['fields'];

            if ($this->simulate_and_debug !== true) {
                $json_data[$tbl] = array(
                    'item'                   => $itm,
                    'table_label'            => $tbl_label,
                    'class_name'             => $class_name,
                    'primary_keys'           => $prim_keys,
                    'auto_increment_keys'    => $ai_keys,
                    'field_delete_confirm_1' => $f_del_confirm_1,
                    'field_delete_confirm_2' => $f_del_confirm_2,
                    'icon'                   => $tbl_icon,
                    'fields'                 => $fields
                );

                $data = json_encode($json_data, JSON_UNESCAPED_UNICODE);

                $this->registerAdminFile($path . '/', $file, $data, false);
            }
        }

        // filter-data (admin/data/[table]-filter-data.json)
        $backup_path = BACKUP_DIR . 'crud-data';
        $file = $this->item . '-filter-data.json';

        if ($this->simulate_and_debug === true) {
            $content = array();
            $content[] = "\n\n" . '==================================== ' . "\n" . $file . "\n" . '====================================';
            file_put_contents('class/generator/reload-table-data-debug.log', implode("\n", $content) . "\n\n", FILE_APPEND);
        }

        if (file_exists($backup_path . '/' . $file)) {
            $json             = file_get_contents($backup_path . '/' . $file);
            $json_backup_data = json_decode($json, true);

            foreach ($json_backup_data as $jbd) {
                // if filter field still exists
                if (in_array($jbd['filter_A'], $this->columns['name'])) {
                    $this->list_options['filters'][] = $jbd;
                }
                if ($this->simulate_and_debug === true) {
                    $content = array();
                    $content[] = '      FOUND FROM BACKUP filter_A: ' . $jbd['filter_A'];
                    file_put_contents('class/generator/reload-table-data-debug.log', implode("\n", $content) . "\n\n", FILE_APPEND);
                }
            }
        }
        $filter_data = $this->list_options['filters'];
        $json = json_encode($filter_data, JSON_UNESCAPED_UNICODE);
        $dir = ADMIN_DIR . 'crud-data/';
        if ($this->simulate_and_debug !== true) {
            $this->registerAdminFile($dir, $file, $json);
        }

        // select-data (admin/crud-data/[table]-select-data.json)
        $select_data = array();
        for ($i = 0; $i < $this->columns_count; $i++) {
            $name               = $this->columns['name'][$i];
            $this->getSelectValues($name);
            $select_data[$name] = array(
                'from'          => $this->columns['select_from'][$i],
                'from_table'    => $this->columns['select_from_table'][$i],
                'from_value'    => $this->columns['select_from_value'][$i],
                'from_field_1'  => $this->columns['select_from_field_1'][$i],
                'from_field_2'  => $this->columns['select_from_field_2'][$i],
                'custom_values' => $this->columns['select_custom_values'][$i],
                'multiple'      => $this->columns['select_multiple'][$i]
            );
        }

        if ($this->simulate_and_debug !== true) {
            $json = json_encode($select_data, JSON_UNESCAPED_UNICODE);
            $dir = ADMIN_DIR . 'crud-data/';
            $file = $this->item . '-select-data.json';
            $dir_path[]  = $dir;
            $file_name[] = $file;
            $this->registerAdminFile($dir, $file, $json);
        }
    }

    /**
     * returns keys => values from array_1 if key exists in array_2
     * recursive => compare array values inside parent array
     *
     * $array_1 = json_backup_data
     * $array_2 = db_data
     *
     *
     * @param  array  $array_1
     * @param  array  $array_2
     * @return Array
     */
    private static function recursiveArrayIntersectKey(array $array_1, array $array_2, $vd = false)
    {
        $array_1 = array_intersect_key($array_1, $array_2);
        foreach ($array_1 as $key => &$value) {
            if (is_array($value) && is_array($array_2[$key])) {
                $value = self::recursiveArrayIntersectKey($value, $array_2[$key], false);
            }
        }
        if ($vd) {
            var_dump($array_1);
        }
        return $array_1;
    }

    /**
     * Auto-detect relations between tables
     * called :
     *     - on first database post
     *     - on relation reset post (json file has beed deleted)
     *     - if a table has been deleted
     * register relations in generator/database/[current_db]/[current_db]_relations.json
     * @return [type] [description]
     */
    public function registerRelations()
    {
        try {
            $db = $this->connectDb();
            $db->transactionBegin();
            $pdo_driver = $this->getPdoDriver($db);
            $qry = $pdo_driver->getRelationsQuery($this->database);
            $db->getPdo()->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
            $db->query($qry);
            $db_count = $db->rowCount();

            /* output example

                table_name         column_name       referenced_table_name     referenced_column_name
                orders             customers_ID      customers                 ID
                products_orders    orders_ID         orders                    ID
                products_orders    products_ID       products                  ID
            */

            // reset
            $this->relations = array(
                'db'                    => array(),
                'all_db_related_tables' => array(),
                'from_to'               => array(),
                'from_to_origin_tables' => array(),
                'from_to_target_tables' => array()
            );

            // for each relation we register the referenced_table_name associated to the table_name
            // if a table is referenced twice from the same table_name we register an alias
            $referenced_tables = array();

            if (!empty($db_count)) {
                while ($row = $db->fetch()) {
                    $tname = $row->table_name;
                    if (!isset($referenced_tables[$tname])) {
                        $referenced_tables[$tname] = array();
                    }
                    $ref_table_alias = '';
                    if (in_array($row->referenced_table_name, $referenced_tables[$tname])) {
                        $alias_index = 1;
                        $ref_table_alias = 't' . $alias_index;
                        while (in_array($ref_table_alias, $referenced_tables[$tname])) {
                            $alias_index++;
                            $ref_table_alias = 't' . $alias_index;
                        }
                    } else {
                        $referenced_tables[$tname][] = $row->referenced_table_name;
                    }
                    $table[]                    = $row->table_name;
                    $column[]                   = $row->column_name;
                    $referenced_table[]         = $row->referenced_table_name;
                    $referenced_table_alias[]   = $ref_table_alias;
                    $referenced_column[]        = $row->referenced_column_name;

                    $this->relations['db'][] = array(
                        'table'             => $row->table_name,
                        'column'            => $row->column_name,
                        'referenced_table'  => $row->referenced_table_name,
                        'referenced_table_alias'  => $ref_table_alias,
                        'referenced_column' => $row->referenced_column_name
                    );

                    $this->relations['all_db_related_tables'][] = $row->table_name;
                    $this->relations['all_db_related_tables'][] = $row->referenced_table_name;
                }
                $this->relations['all_db_related_tables'] = array_unique($this->relations['all_db_related_tables']);

                /* Get structured from_to relations from db relations */

                $relation = array();

                // one-to-one && one-to many
                $relations_count = 0;
                if (is_countable($this->relations['db'])) {
                    $relations_count = count($this->relations['db']);
                }
                for ($i = 0; $i < $relations_count; $i++) {
                    $relation_db                                  = $this->relations['db'][$i];
                    $relation['origin_table']                     = $relation_db['table'];
                    $relation['origin_column']                    = $relation_db['column'];
                    $relation['intermediate_table']               = '';
                    $relation['intermediate_column_1']            = '';
                    $relation['intermediate_column_2']            = '';
                    $relation['target_table']                     = $relation_db['referenced_table'];
                    $relation['target_table_alias']               = $relation_db['referenced_table_alias'];
                    $relation['target_column']                    = $relation_db['referenced_column'];
                    $relation['cascade_delete_from_intermediate'] = true; // default
                    $relation['cascade_delete_from_origin']       = true; // default

                    $this->relations['from_to'][] = $relation;
                    $this->relations['from_to_origin_tables'][] = $relation['origin_table'];
                    $this->relations['from_to_target_tables'][] = $relation['target_table'];
                }

                // many-to many ( = with intermediate tables)
                // 2 referenced tables must have same origin to be registered as many-to many relation
                $tested_origins = array();
                for ($i = 0; $i < $relations_count; $i++) {
                    $relation_db = $this->relations['db'][$i];
                    if (!in_array($relation_db['table'], $tested_origins)) {
                        $tested_origins[] = $relation_db['table'];

                        // look for same origin in all followings
                        for ($j = $i + 1; $j < $relations_count; $j++) {
                            $rel_db_row = $this->relations['db'][$j];
                            if ($rel_db_row['table'] == $relation_db['table']) {
                                // same origin tables => recording as many-to many relation
                                $relation['origin_table']                     = $relation_db['referenced_table'];
                                $relation['origin_column']                    = $relation_db['referenced_column'];
                                $relation['intermediate_table']               = $relation_db['table'];
                                $relation['intermediate_column_1']            = $relation_db['column'];
                                $relation['intermediate_column_2']            = $rel_db_row['column'];
                                $relation['target_table']                     = $rel_db_row['referenced_table'];
                                $relation['target_table_alias']                     = $rel_db_row['referenced_table_alias'];
                                $relation['target_column']                    = $rel_db_row['referenced_column'];
                                $relation['cascade_delete_from_intermediate'] = true; // default
                                $relation['cascade_delete_from_origin']       = true; // default

                                $this->relations['from_to'][] = $relation;
                                $this->relations['from_to_origin_tables'][] = $relation['origin_table'];
                                $this->relations['from_to_target_tables'][] = $relation['target_table'];

                                if (!empty($relation['intermediate_column_1'])) {
                                    // Register reverse relation (ex : products => orders | orders => products)
                                    $relation['origin_table']                     = $rel_db_row['referenced_table'];
                                    $relation['origin_column']                    = $rel_db_row['referenced_column'];
                                    $relation['intermediate_table']               = $relation_db['table'];
                                    $relation['intermediate_column_1']            = $rel_db_row['column'];
                                    $relation['intermediate_column_2']            = $relation_db['column'];
                                    $relation['target_table']                     = $relation_db['referenced_table'];
                                    $relation['target_table_alias']                     = $relation_db['referenced_table_alias'];
                                    $relation['target_column']                    = $relation_db['referenced_column'];
                                    $relation['cascade_delete_from_intermediate'] = true; // default
                                    $relation['cascade_delete_from_origin']       = true; // default

                                    $this->relations['from_to'][] = $relation;
                                    $this->relations['from_to_origin_tables'][] = $relation['origin_table'];
                                    $this->relations['from_to_target_tables'][] = $relation['target_table'];
                                }
                            }
                        }
                    }
                }

                $this->relations['from_to_origin_tables'] = array_unique($this->relations['from_to_origin_tables']);
                $this->relations['from_to_target_tables'] = array_unique($this->relations['from_to_target_tables']);

                // register table & columns properties in json file
                $json_data = json_encode($this->relations, JSON_UNESCAPED_UNICODE);
                $this->registerJson($this->database . '-relations.json', $json_data);
                $this->logMessage('<strong>registerRelations</strong>');
                if (isset($_SESSION['msg']) && strpos($_SESSION['msg'], DB_RELATIONS_REFRESHED) === false) {
                    $this->userMessage(DB_RELATIONS_REFRESHED, 'alert-success has-icon');
                }
            } else {
                $json_data = '';
                $this->registerJson($this->database . '-relations.json', $json_data);
                $this->logMessage('<strong>registerRelations (No relation found)</strong>');
            }
            $db->transactionCommit();
        } catch (\Exception $e) {
            $db->transactionRollback();
            exit($e->getMessage());
        }
    }

    private function getRelations()
    {
        $fp = \strtolower('database/' . $this->database . '/' . $this->database . '-relations.json');
        // create file if doesn't exist
        if (!file_exists(GENERATOR_DIR . $fp)) {
            $this->registerRelations();
        }

        // get relations
        if (file_exists(GENERATOR_DIR . $fp)) {
            $json            = file_get_contents(GENERATOR_DIR . $fp);
            $this->relations = json_decode($json, true);
        } else {
            $this->userMessage(GENERATOR_DIR . $fp . ': ' . ERROR_FILE_NOT_FOUND, 'alert-warning has-icon');
        }
    }

    /**
     * find label in table json file if exists
     * @param  string $table
     * @param  string $column
     * @return string         label from json file or from toReadable function if not found
     */
    public function getLabel($table, $column = '')
    {
        $label     = '';
        $json_data = array();
        if (file_exists(ADMIN_DIR . 'crud-data/db-data.json')) {
            $json      = file_get_contents(ADMIN_DIR . 'crud-data/db-data.json');
            $json_data = json_decode($json, true);
        }
        if (isset($json_data[$table])) {
            if (empty($column)) {
                $label = $json_data[$table]['table_label'];
            } else {
                $label = $json_data[$table]['fields'][$column];
            }
        } else {
            if (empty($column)) {
                $label = $this->toReadable($table);
            } else {
                $label = $this->toReadable($column);
            }
        }

        return $label;
    }

    /**
     * find icon in table json file if exists
     * @param  string $table
     * @return string         icon from json file or default icon if not found
     */
    public function getIcon($table)
    {
        $icon     = '';
        $json_data = array();
        if (file_exists(ADMIN_DIR . 'crud-data/db-data.json')) {
            $json      = file_get_contents(ADMIN_DIR . 'crud-data/db-data.json');
            $json_data = json_decode($json, true);
        }
        if (isset($json_data[$table])) {
            $icon = $json_data[$table]['icon'];
        } else {
            $icon = $this->default_table_icon;
        }

        return $icon;
    }

    public function registerJson($file_name, $json_data)
    {
        $fp = 'database/' . \strtolower($this->database);
        $file_name = \strtolower($file_name);
        if (!is_dir(GENERATOR_DIR . $fp) && !mkdir(GENERATOR_DIR . $fp, 0775)) {
            $this->userMessage(ERROR_CANT_CREATE_DIR . ' ' . GENERATOR_DIR . $fp, 'alert-danger has-icon');

            return false;
        }
        if (file_put_contents(GENERATOR_DIR . $fp . '/' . $file_name, $json_data) === false) {
            $this->userMessage(ERROR_CANT_WRITE_FILE . ' ' . GENERATOR_DIR . $fp . '/' . $file_name, 'alert-danger has-icon');

            return false;
        }
        $this->logMessage('<strong>registerJson</strong> => ' . $file_name);
    }

    public function registerAdminFile($dir_path, $file_name, $data, $backup = true)
    {
        if (!is_dir($dir_path) && !mkdir($dir_path, 0775)) {
            $this->userMessage(ERROR_CANT_CREATE_DIR . ' ' . $dir_path, 'alert-danger has-icon');

            return false;
        }

        // Generate backup file
        if (file_exists($dir_path . $file_name) && $backup === true) {
            $backup_dir = str_replace(ADMIN_DIR, BACKUP_DIR, $dir_path);
            if (copy($dir_path . $file_name, $backup_dir . $file_name) === false) {
                $this->userMessage(ERROR_CANT_WRITE_FILE . ' ' . $backup_dir . $file_name, 'alert-danger has-icon');
            }
        }

        // Register new content
        if (file_put_contents($dir_path . $file_name, $data) === false) {
            $this->userMessage(ERROR_CANT_WRITE_FILE . ' ' . $dir_path . $file_name, 'alert-danger has-icon');

            return false;
        }
        $this->logMessage('<strong>registerAdminFile</strong> => ' . $dir_path . $file_name);
    }

    public function lockAdminPanel()
    {
        $userConf = json_decode(file_get_contents(ROOT . 'conf/user-conf.json'));
        $userConf->admin_locked = true;
        $user_conf = json_encode($userConf);
        if (DEMO !== true) {
            if (!file_put_contents(ROOT . 'conf/user-conf.json', $user_conf)) {
                $this->userMessage(ERROR_CANT_WRITE_FILE . ': ' . ROOT . 'conf/user-conf.json', 'alert-danger has-icon');

                return false;
            }
            $this->authentication_module_enabled = true;

            return true;
        }

        return true;
    }

    /**
     * parse a sql "from" query - used to parse filters "from"
     * @param  string $from - the complete "from" query with tables & joins
     * @return array       An array with the tables used in from and the join queries
     */
    public function parseQuery($from)
    {
        $qry         = preg_replace('`( LEFT JOIN| INNER JOIN| RIGHT JOIN)`', '%$1', $from);
        $out         = preg_split('`%`', $qry);
        $from_table  = trim($out[0]);
        array_splice($out, 0, 1);
        $join_queries = $out;

        $join_tables = array();
        foreach ($join_queries as $qry) {
            preg_match('`JOIN ([a-zA-Z_]+) ON`', $qry, $table_out);
            $join_tables[] = $table_out[1];
        }

        $parsed = array(
            'from_table'   => $from_table,
            'join_tables'  => $join_tables,
            'join_queries' => $join_queries
        );

        return $parsed;
    }

    private function checkRequiredFiles()
    {
        $files_to_create = array(
            ADMIN_DIR . 'crud-data/nav-data.json',
            ADMIN_DIR . 'crud-data/db-data.json'
        );
        $directories_to_create = array(
            BACKUP_DIR . 'class',
            BACKUP_DIR . 'class/crud',
            BACKUP_DIR . 'crud-data',
            BACKUP_DIR . 'database',
            BACKUP_DIR . 'inc',
            BACKUP_DIR . 'inc/forms',
            BACKUP_DIR . 'templates'
        );

        foreach ($files_to_create as $file) {
            if (!file_exists($file)) {
                if (!touch($file)) {
                    $this->userMessage(ERROR_CANT_WRITE_FILE . ' ' . $file, 'alert-danger has-icon');
                } else {
                    $this->logMessage('<strong>checkRequiredFiles</strong> => CREATE ' . $file);
                }
            }
        }

        foreach ($directories_to_create as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0755)) {
                    $this->userMessage(ERROR_CANT_WRITE_FILE . ' ' . $dir, 'alert-danger has-icon');
                } else {
                    $this->logMessage('<strong>checkRequiredFiles</strong> => CREATE ' . $dir);
                }
            }
        }
    }

    private function connectDb()
    {
        if ($db = new DB(true)) {
            if (preg_match('`([a-zA-Z0-9_]+)\.(?:[a-zA-Z]+)$`', DB_NAME, $out)) {
                // for firebird ; e.g.: C:/Users/folder/DATABASES/firebird/PHPCG_TEST.FDB
                $this->database = \strtolower($out[1]);
            } else {
                $this->database = DB_NAME;
            }
            // register if no database error
            $_SESSION['generator'] = $this;
        } else {
            $this->userMessage(FAILED_TO_CONNECT_DB, 'alert-danger has-icon');
        }

        return $db;
    }

    private function getPdoDriver(DB $db)
    {
        // Create the PDO driver object according to PDO_DRIVER
        $pdo_driver_object = 'phpformbuilder\\database\\pdodrivers\\' . ucfirst(PDO_DRIVER);

        return new $pdo_driver_object($db->getPdo());
    }

    private function unlockAdminPanel()
    {
        $userConf = json_decode(file_get_contents(ROOT . 'conf/user-conf.json'));
        $userConf->admin_locked = false;
        $user_conf = json_encode($userConf);
        if (DEMO !== true) {
            if (!file_put_contents(ROOT . 'conf/user-conf.json', $user_conf)) {
                $this->userMessage(ERROR_CANT_WRITE_FILE . ': ' . ROOT . 'conf/user-conf.json', 'alert-danger has-icon');

                return false;
            }

            return true;
        }

        return true;
    }

    private function removeAuthentificationModule()
    {
        // USERS_TABLE
        include_once ADMIN_DIR . 'secure/conf/conf.php';
        $users_classname = ElementsUtilities::upperCamelCase($this->table);

        $db_lc = \strtolower($this->database);
        $users_table_lc = \strtolower(USERS_TABLE);
        $files_to_delete = array(
            GENERATOR_DIR . 'database/' . $db_lc . '/' . $users_table_lc . '.json',
            GENERATOR_DIR . 'database/' . $db_lc . '/' . $users_table_lc . '_profiles.json',
            ADMIN_DIR . 'class/crud/' . $users_classname . '.php',
            ADMIN_DIR . 'class/crud/' . $users_classname . 'Profiles.php',
            ADMIN_DIR . 'crud-data/' . $users_table_lc . '-filter-data.json',
            ADMIN_DIR . 'crud-data/' . $users_table_lc . '-select-data.json',
            ADMIN_DIR . 'crud-data/' . $users_table_lc . 'profiles-filter-data.json',
            ADMIN_DIR . 'crud-data/' . $users_table_lc . 'profiles-select-data.json',
            ADMIN_DIR . 'inc/forms/' . $users_table_lc . '-create.php',
            ADMIN_DIR . 'inc/forms/' . $users_table_lc . '-edit.php',
            ADMIN_DIR . 'inc/forms/' . $users_table_lc . 'profiles-create.php',
            ADMIN_DIR . 'inc/forms/' . $users_table_lc . 'profiles-edit.php',
            ADMIN_DIR . 'secure/install/install.lock'
        );

        /* store tables custom names to reuse on next authentication module installation */

        // users
        $users_json = file_get_contents(GENERATOR_DIR . 'database/' . $db_lc . '/' . $users_table_lc . '.json');
        $users_json_data = json_decode($users_json, true);
        $_SESSION['users_columns_json_data'] = $users_json_data['columns']['fields']; // [field_name => displayed name]

        // profiles
        $users_profiles_json = file_get_contents(GENERATOR_DIR . 'database/' . $db_lc . '/' . $users_table_lc . '_profiles.json');
        $users_profiles_json_data = json_decode($users_profiles_json, true);
        $_SESSION['users_profiles_columns_json_data'] = $users_profiles_json_data['columns']['fields']; // [field_name => displayed name]

        $unlink_success = true;
        foreach ($files_to_delete as $file) {
            if (file_exists($file) && @unlink($file) === false) {
                $unlink_success = false;
                $this->userMessage(FAILED_TO_DELETE . ' ' . $file, 'alert-danger has-icon');
            }
        }

        if ($unlink_success === true) {
            // nav data (admin/data/nav-data.json)
            $nav_data = array();
            if (file_exists(ADMIN_DIR . 'crud-data/nav-data.json')) {
                $json     = file_get_contents(ADMIN_DIR . 'crud-data/nav-data.json');
                $nav_data = json_decode($json, true);
                foreach ($nav_data as $navcat) {
                    if (in_array(USERS_TABLE, $navcat['tables'])) {
                        $key = array_search(USERS_TABLE, $navcat['tables']);
                        unset($navcat['tables'][$key]);
                        unset($navcat['is_disabled'][$key]);
                    }
                    if (in_array(USERS_TABLE . '_profiles', $navcat['tables'])) {
                        $key = array_search(USERS_TABLE . '_profiles', $navcat['tables']);
                        unset($navcat['tables'][$key]);
                        unset($navcat['is_disabled'][$key]);
                    }
                }
                $dir              = ADMIN_DIR . 'crud-data/';
                $file             = 'nav-data.json';
                $this->registerAdminFile($dir, $file, json_encode($nav_data, JSON_UNESCAPED_UNICODE));
            }

            // db-data (admin/data/db-data.json)
            if (file_exists(ADMIN_DIR . 'crud-data/db-data.json')) {
                $json    = file_get_contents(ADMIN_DIR . 'crud-data/db-data.json');
                $db_data = json_decode($json, true);
                unset($db_data[USERS_TABLE]);
                unset($db_data[USERS_TABLE . '_profiles']);
                $dir = ADMIN_DIR . 'crud-data/';
                $file = 'db-data.json';
                $this->registerAdminFile($dir, $file, $json);
            }

            $db = new DB(DEBUG);
            $qry = 'DROP TABLE ' . USERS_TABLE;
            $db->execute($qry);
            $qry = 'DROP TABLE ' . USERS_TABLE . '_profiles';
            $db->execute($qry);

            $this->resetRelations();
            $this->registerRelations();
            $this->reset('tables');
            $_SESSION['generator'] = $this;

            // reload page to refresh authentication module
            header("Refresh:0");
            exit();
        }

        $this->logMessage('<strong>removeAuthentificationModule</strong>');
    }

    private function uninstall()
    {
        $core_files = array(
            '.htaccess',
            'Elements.php',
            'ElementsFilters.php',
            'ElementsUtilities.php',
            'breadcrumb.html',
            'data-forms-js.html',
            'data-home-js.html',
            'data-lists-js.html',
            'footer.html',
            'home.html',
            'navbar.html',
            'sidebar.html',
            'style-switcher.html'
        );
        $files_to_delete = array();

        $dirs_to_scan = array(
            ADMIN_DIR . 'class/crud',
            ADMIN_DIR . 'crud-data',
            ADMIN_DIR . 'inc/forms',
            ADMIN_DIR . 'templates',
            ADMIN_DIR . 'templates/single-record-views',
            GENERATOR_DIR . 'backup-files/class/crud',
            GENERATOR_DIR . 'backup-files/class/crud-data',
            GENERATOR_DIR . 'backup-files/class/database',
            GENERATOR_DIR . 'backup-files/class/inc',
            GENERATOR_DIR . 'backup-files/class/templates',
            GENERATOR_DIR . 'database/' . \strtolower($this->database)
        );

        try {
            foreach ($dirs_to_scan as $dir) {
                if (is_dir($dir)) {
                    $files = $this->scanDirectories($dir);
                    if (!empty($files)) {
                        foreach ($files as $f) {
                            // extract the filename then check if it's in the core_files
                            if (preg_match('`([a-zA-Z0-9_-]+\.[a-z]{3,4})$`', $f, $out) && !in_array($out[1], $core_files)) {
                                $files_to_delete[] = $f;
                            }
                        }
                    }
                }
            }

            $files_to_delete[] = ADMIN_DIR . 'crud-data/db-data.json';
            $files_to_delete[] = ADMIN_DIR . 'crud-data/nav-data.json';
            $files_to_delete[] = ROOT . 'install/install.lock';

            \unlink(GENERATOR_DIR . 'database/' . \strtolower($this->database));

            foreach ($files_to_delete as $f) {
                if (\file_exists($f) && \is_file($f)) {
                    \unlink($f);
                }
            }

            $db = $this->connectDb();
            $db->transactionBegin();
            $sql = 'DROP TABLE ' . PHPCG_USERDATA_TABLE;
            $db->execute($sql);
            $db->transactionCommit();

            $db_connect_template = GENERATOR_DIR . 'generator-templates/db-connect.txt';
            $fp                  = file_get_contents($db_connect_template);
            file_put_contents(CLASS_DIR . 'phpformbuilder/database/db-connect.php', $fp);

            $_SESSION['uninstalled-from-generator-msg'] = '<div class="alert alert-success alert-dismissible has-icon fade show">' . UNINSTALL_SUCCESS_MESSAGE . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';

            return true;
        } catch (\Exception $e) {
            $db->transactionRollback();
            $this->userMessage($e->getMessage(), 'alert-danger has-icon');

            return false;
        }
    }

    /**
     * replace some content in given file
     * @param  string $find
     * @param  string $replace
     * @param  string $file_path
     * @return Boolean
     */
    /* private function replaceInFile($find, $replace, $file_path)
    {
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $content = str_replace($find, $replace, $content);
            if (file_put_contents($file_path, $content) === false) {
                $this->userMessage(ERROR_CANT_WRITE_FILE . ' ' . $file_path, 'alert-danger has-icon');

                return false;
            } else {
                return true;
            }
        } else {
            $this->userMessage(ERROR_FILE_NOT_FOUND . ' ' . $file_path, 'alert-danger has-icon');

            return false;
        }
    } */

    /**
     * recursive scan of directory
     * get all files paths in dir & all subdirs.
     * http://php.net/manual/fr/function.scandir.php
     * @param  string $root_dir
     * @param  string $all_data
     * @return  Array indexed array (non-multidimensional) with all files paths
     */
    private function scanDirectories($root_dir, $all_data = array())
    {

        // set filenames invisible if you want
        $invisible_file_names = array(".", "..", ".htaccess", ".htpasswd");

        // run through content of root directory
        $dir_content = scandir($root_dir);
        foreach ($dir_content as $content) {
            // filter all files not accessible
            $path = $root_dir . '/' . $content;
            if (!in_array($content, $invisible_file_names)) {
                // if content is file & readable, add to array
                if (is_file($path) && is_readable($path)) {
                    // save file name with path
                    $all_data[] = $path;

                    // if content is a directory and readable, add path and name
                } elseif (is_dir($path) && is_readable($path)) {
                    // recursive callback to open new directory
                    $all_data = $this->scanDirectories($path, $all_data);
                }
            }
        }
        return $all_data;
    }

    private function logMessage($msg)
    {
        if ($this->debug === true) {
            if (!isset($_SESSION['log-msg'])) {
                $_SESSION['log-msg'] = '';
            }
            $_SESSION['log-msg'] .= '<p>' . $msg . '</p>';
        }
    }

    /**
     * register output message for user
     * alert if no content
     * panel if content
     * @param  string $title            alert|panel title
     * @param  string $classname        Boootstrap alert|panel class (bg-success, bg-primary, bg-warning, bg-danger)
     * @param  string $heading_elements [panels] separated comma list : collapse, reload, close
     * @param  string $content          [panels] panel body html content
     * @return void
     */
    private function userMessage($title, $classname, $heading_elements = 'close', $content = '')
    {
        if (!isset($_SESSION['msg'])) {
            $_SESSION['msg'] = '';
        }
        if (!empty($content)) {
            // panel
            $_SESSION['msg'] .= Utils::alertCard($title, $classname, '', $heading_elements, $content);
        } else {
            // alert
            $_SESSION['msg'] .= Utils::alert($title, $classname);
        }
    }
}
