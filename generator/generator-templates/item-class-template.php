<?php
// phpcs:disable Generic.WhiteSpace.ScopeIndent
use phpformbuilder\database\DB;
use phpformbuilder\database\pdodrivers\Mysql;
use phpformbuilder\database\pdodrivers\Pgsql;
use crud\ElementsUtilities;

/* To debug:
call $generator->debug($var),
build the list from the generator
then inspect the comments in the item class code.
-------------------------------------------------- */

$generator = $_SESSION['generator'];
echo '<?php' . "\n"; ?>
namespace crud;

use common\Utils;
use phpformbuilder\database\DB;
use phpformbuilder\database\Pagination;
use secure\Secure;

class <?php echo ElementsUtilities::upperCamelCase($generator->table); ?> extends Elements
{

    // item name passed in url
    public $item;

    // item name displayed
    public $item_label;

    // associative array : field => field displayed name
    public $fields;
<?php
    $external_active_tables_count = 0;
if (count($generator->external_columns) > 0) {
    $external_active_tables_count = 0;
    $external_tables_labels = array();
    foreach ($generator->external_columns as $col) {
        if ($col['active']) {
            $external_tables_labels[] = $col['table_label'];
            $external_active_tables_count++;
        }
    }
    ?>

    // external relations
    public $external_tables_count = <?php echo $external_active_tables_count; ?>;
    public $external_fields_count;
    public $external_rows_count;
    public $external_tables_labels = array('<?php echo implode('\', \'', $external_tables_labels); ?>');
    public $external_add_btn = array();
    public $external_fields = array();
<?php
}
?>

    // primary key passed to create|edit|delete
    public $primary_keys; // primary keys fieldnames

    // CREATE rights
    public $can_create = false;

    public $pks = array(); // primary key values for each row
    public $pk_concat_values = array(); // concatenated values of primary key(s) for each row
    public $pk_url_params = array(); // primary key(s) sent to the edit/delete forms URL for each row
    public $update_record_authorized = array();
<?php for ($i=0; $i < $generator->columns_count; $i++) {
    echo '    public $' . ElementsUtilities::sanitizeFieldName($generator->columns['name'][$i]) . ' = array();' . "\n";
} // end for ?>

    public $active_filtered_fields = array();
    public $debug_content = '';
    public $export_data_button;
    public $filters_form;
    public $is_single_view = false;
    public $item_url;
    public $join_query = '';
    public $main_pdo_settings = array();
    public $pagination_html;

    // Array of primary fieldnames => values to select a single record for view
    public $params;

    public $records_count;
    public $select_number_per_page;
    public $sorting;

    public function __construct($element, $params = array())
    {
        $this->table         = $element->table;
        $this->item          = $element->item;
        $this->item_label    = $element->item_label;
        $this->primary_keys  = $element->primary_keys;
        $this->select_data   = $element->select_data;
        $this->fields        = $element->fields;

        $table = $this->table;

        $this->params = $params;

        if (!empty($params)) {
            $this->is_single_view = true;
        }

        $json = file_get_contents(ADMIN_DIR . 'crud-data/' . $this->item . '-filter-data.json');
        $filters_array = json_decode($json, true);
        $this->item_url = $_SERVER['REQUEST_URI'];

        // connect to the database
        $db = new Pagination(DEBUG);
        $db->setDebugMode('register');

<?php
    // generate fields query
    $columns = array(
        'table' => array(),
        'query' => array(),
        'name' => array(),
        'value' => array()
    );
    $secondary_tables    = [];
    $secondary_from_to   = [];
    $primary_keys_values = [];

    // Check if the table has an autoincremented primary key
    $has_auto_increment_primary_key = false;

    for ($i=0; $i < $generator->columns_count; $i++) {
        if ($generator->columns['primary'][$i] && $generator->columns['auto_increment'][$i]) {
            $has_auto_increment_primary_key = true;
        }
    }

    $target_tables = array();
    $done_queries  = array();

    for ($i=0; $i < $generator->columns_count; $i++) {
        $relation = $generator->columns['relation'][$i];

        if (empty($relation['target_table']) && !in_array($generator->table . '.' . $generator->columns['name'][$i], $done_queries)) {
            if (!in_array($generator->table . '.' . $generator->columns['name'][$i], $done_queries)) {
                $columns['table'][] = $generator->table;
                $columns['query'][] = $generator->table . '.' . $generator->columns['name'][$i];
                $columns['name'][]  = $generator->columns['name'][$i];
                $columns['value'][] = '$row->' . $columns['name'][$i];
                if ($generator->columns['primary'][$i]) {
                    $pk_field = $generator->columns['name'][$i];
                    $primary_keys_values[$pk_field] = '$row->' . $columns['name'][$i];
                }
                $done_queries[] = $generator->table . '.' . $generator->columns['name'][$i];
            }
        } else {
            /* =============================================
            join if any relation
            ============================================= */

            $target_table = $relation['target_table'];
            if (!empty($relation['target_table_alias'])) {
                $target_table = $relation['target_table_alias'];
            }

            // relation query can use several fields
            $target_fields = explode(', ', $relation['target_fields']);
            $target_fields_display_values = explode(', ', $relation['target_fields_display_values']);

            $columns['table'][] = $target_table;

            $query = array();
            foreach ($target_fields as $target_field) {
                if (!in_array($target_table . '.' . $target_field, $done_queries)) {
                    $short_alias = ElementsUtilities::shortenAlias($target_table . '_' . $target_field);
                    $query[] = $target_table . '.' . $target_field . ' AS ' . $short_alias;
                    if ($generator->columns['primary'][$i] || !$has_auto_increment_primary_key) {
                        $pk_field = $generator->columns['name'][$i];
                        $primary_keys_values[$pk_field] = '$row->' . $short_alias;
                    }
                    $done_queries[] = $target_table . '.' . $target_field;
                }
            }
            foreach ($target_fields_display_values as $target_field_dv) {
                if (!strpos($target_field_dv, '.')) {
                    $short_alias_dv = ElementsUtilities::shortenAlias($target_table . '_' . $target_field_dv);
                    if (!in_array($target_table . '.' . $target_field_dv, $done_queries)) {
                        $query[] = $target_table . '.' . $target_field_dv . ' AS ' . $short_alias_dv;
                        $done_queries[] = $target_table . '.' . $target_field_dv;
                    }
                } else {
                    // if the target field comes from a secondary relation. E.g: city.country.country
                    $split = explode('.', $target_field_dv);
                    $secondary_target_table = $split[0];
                    $secondary_target_field_dv = $split[1];
                    $short_alias_dv2 = ElementsUtilities::shortenAlias($target_table . '_' . $secondary_target_table . '_' . $secondary_target_field_dv);
                    if (!in_array($target_table . '.' . $secondary_target_table . '.' . $secondary_target_field_dv, $done_queries)) {
                        $query[] = $secondary_target_table . '.' . $secondary_target_field_dv . ' AS ' . $short_alias_dv2;
                        $done_queries[] = $target_table . '.' . $secondary_target_table . '.' . $secondary_target_field_dv;
                    }

                    // register the secondary relation
                    if (in_array($target_table, $generator->relations['all_db_related_tables'])) {
                        foreach ($generator->relations['from_to'] as $ft) {
                            if ($ft['origin_table'] === $target_table && empty($ft['intermediate_table']) && $ft['target_table'] === $secondary_target_table) {
                                $secondary_tables[] = $ft['origin_table'];
                                $secondary_from_to[] = $ft;
                            }
                        }
                    }
                }
            }
            $columns['query'][] = implode(', ', $query);

            $columns['name'][]  = $generator->columns['name'][$i];

            $value = array();

            $target_fields_values = array();

            // display the target_fields_display_values if not empty, else target_fields
            foreach ($target_fields as $target_field) {
                if (empty($target_fields_display_values[0])) {
                    $target_fields_values[] = $target_field;
                } else {
                    $target_fields_values[] = implode(', ', $target_fields_display_values);
                }
            }
            foreach ($target_fields_values as $target_field_value) {
                if (!strpos($target_field_value, ', ')) {
                    $short_alias = ElementsUtilities::shortenAlias($target_table . '_' . str_replace('.', '_', $target_field_value));
                    $value[] = '$row->' . $short_alias;
                } else {
                    // if the target field comes from a secondary relation. E.g: city.country.country
                    $values = explode(', ', $target_field_value);
                    $short_alias = ElementsUtilities::shortenAlias($target_table . '_' . str_replace('.', '_', $values[0]));
                    // $value[] = '$row->' . $short_alias;
                    $short_alias2 = ElementsUtilities::shortenAlias($target_table . '_' . str_replace('.', '_', $values[1]));
                    $value[] = '$row->' . $short_alias . ' . \' \' . $row->' . $short_alias2;
                }
            }
            $columns['value'][] = implode(' . \'[|]\' . ', $value);
        }
    }

    // create join queries
    $from_to = null;
    if (isset($generator->relations['from_to'])) {
        $from_to = $generator->relations['from_to'];
    }
    if (!empty($secondary_from_to)) {
        $from_to = array_merge($from_to, $secondary_from_to);
    }
    $join_query  = array();
    // LEFT JOIN city ON address.city_city_id=city.city_id LEFT JOIN country ON city.country_country_id=country.country_id AND city.country_country_id=country.country_id
    if (is_array($from_to)) {
        foreach ($from_to as $ft) {
            if ($ft['origin_table'] == $generator->table || in_array($ft['origin_table'], $secondary_tables)) {
                if (empty($ft['intermediate_table'])) {
                    /* one-to one */
                    if (empty($ft['target_table_alias'])) {
                        $join_query[] = ' LEFT JOIN ' . $ft['target_table'] . ' ON ' . $ft['origin_table'] . '.' . $ft['origin_column'] . '=' . $ft['target_table'] . '.' . $ft['target_column'];
                    } else {
                        $join_query[] = ' LEFT JOIN ' . $ft['target_table'] . ' ' . $ft['target_table_alias'] . ' ON ' . $ft['origin_table'] . '.' . $ft['origin_column'] . '=' . $ft['target_table_alias'] . '.' . $ft['target_column'];
                    }
                } else {
                    /* many-to-many */
                }
            }
        }
    }

    // register column_count including relation fields
    $columns_count = count($columns['table']);
    $columns_imploded = implode(', ', array_unique($columns['query']));
    $join_query    = implode('', array_unique($join_query));
    if (!empty($join_query)) { ?>
        $this->join_query = '<?php echo $join_query; ?>';<?php
    } ?>

        $columns = '<?php echo $columns_imploded ?>';
        $where = array();

        // restricted rights query
        if (Secure::canReadRestricted($table)) {
            $where = array_merge($where, Secure::getRestrictionQuery($table));
        }

        // filters
        $filters = new ElementsFilters($table, $filters_array, $this->join_query);
        $this->active_filtered_fields = $filters->getActiveFilteredFields();
        $where_filters = $filters->getWhere();
        $where = array_merge($where, $where_filters);

        // search
        $where_search = array();
        if (isset($_POST['search_field']) && isset($_POST['search_string'])) {
            $searchVals = explode(' + ', $_POST['search_string']);
            $search_string = $searchVals[0];
            $_SESSION['rp_search_field'][$table] = $_POST['search_field'];
            $_SESSION['rp_search_string'][$table] = $search_string;
            if (sizeof($searchVals) > 1) {
                $_SESSION['rp_search_string_2'][$table] = $searchVals[1];
            } else {
                unset($_SESSION['rp_search_string_2'][$table]);
            }
        }

        if (isset($_SESSION['rp_search_string'][$table]) && !empty($_SESSION['rp_search_string'][$table])) {
            $sf = $_SESSION['rp_search_field'][$table];
            $search_field = $table . '.' . $sf;
            $search_field2 = '';
            $search_string_sqlvalue = $db->safe('%' . $_SESSION['rp_search_string'][$table] . '%');
            if (isset($_SESSION['rp_search_string_2'][$table])) {
                $search_string_2_sqlvalue = $db->safe('%' . $_SESSION['rp_search_string_2'][$table] . '%');
            }
            if (file_exists(ADMIN_DIR . 'crud-data/' . $this->item . '-select-data.json')) {
                $json = file_get_contents(ADMIN_DIR . 'crud-data/' . $this->item . '-select-data.json');
                $selects_array = json_decode($json, true);
                if (isset($selects_array[$sf]) && $selects_array[$sf]['from'] == 'from_table') {
                    $search_field = $selects_array[$sf]['from_table'] . '.' . $selects_array[$sf]['from_field_1'];
                    if (!empty($selects_array[$sf]['from_field_2'])) {
                        $search_field2 = $selects_array[$sf]['from_table'] . '.' . $selects_array[$sf]['from_field_2'];
                    }
                }
            }
            $where_search[] = 'LOWER(' . $search_field . ') LIKE LOWER(' . $search_string_sqlvalue . ')';
            if (!empty($search_field2) && isset($search_string_2_sqlvalue) && ($search_string_2_sqlvalue != "'%%'")) {
                $where_search[] = 'LOWER(' . $search_field2 . ') LIKE LOWER(' . $search_string_2_sqlvalue . ')';
            }
            $where = array_merge($where, $where_search);
        }

        $this->filters_form = $filters->returnForm($this->item_url);

        // Get join queries from active filters
        $active_filters_join_queries = $filters->buildElementJoinQuery();

        if (isset($_POST['search_field'])) {
            $pagination_url = str_replace(ADMIN_URL . 'search/', ADMIN_URL, $_SERVER['REQUEST_URI']);
        } else {
            $pagination_url = $_SERVER['REQUEST_URI'];
        }
        if (isset($_POST['npp']) && is_numeric($_POST['npp'])) {
            $_SESSION['npp'] = $_POST['npp'];
        } elseif (!isset($_SESSION['npp'])) {
            $_SESSION['npp'] = 20;
        }
        if ($this->is_single_view) {
            // if single record view
            $active_filters_join_queries = $filters->buildElementJoinQuery();
            $pagination_url = '';
            // replace 'fieldname' with 'table.fieldname' to avoid ambigous query
            $where_params = array_combine(
                array_map(function ($k) {
                    return $this->table . '.' . $k;
                }, array_keys($this->params)),
                $this->params
            );
            $where = array_merge($where, $where_params);
        }

        // order query
        $this->sorting = ElementsUtilities::getSorting($table, '<?php echo $generator->list_options['order_by']; ?>', '<?php echo $generator->list_options['order_direction']; ?>');

        $npp = $_SESSION['npp'];
        if (!empty($where_search) && PAGINE_SEARCH_RESULTS === false) {
            $npp = 1000000;
        }

        if (empty($where)) {
            $where = null;
        }

        // $this->main_pdo_settings are the PDO settings without the pagination LIMIT.
        $this->main_pdo_settings = array(
            'function' => 'select',
            'from'    => '<?php echo $generator->table ?>' . $active_filters_join_queries,
            'values'   => $columns,
            'where'    => $where,
            'extras'   => array('order_by' => $this->sorting),
            'debug'    => DEBUG_DB_QUERIES
        );

        $this->pagination_html = $db->pagine($this->main_pdo_settings, $npp, 'p', $pagination_url, 5, true, '/', '');

        if (DEBUG_DB_QUERIES) {
            $this->debug_content .= '<p class="debug-title text-bg-info">"' . $this->table . '" queries</p>' . $db->getDebugContent();
        }

        $update_authorized = false;
        if (Secure::canUpdate($this->table)) {
            // user can update ALL the records
            $update_authorized = true;
        }

        $this->records_count = $db->rowCount();
        if (!empty($this->records_count)) {
            while ($row = $db->fetch()) {
                $primary_keys_array = array(
<?php
    $nb = count($primary_keys_values);
    $i = 0;
    $pk_url_params_array = array();
    foreach ($primary_keys_values as $pk_field => $value) {
        $end_line = ',' . "\n";
        if ($i+1 === $nb) {
            $end_line = "\n";
        }
        // replace null values by litteral string because http_build_query removes null values from the URL parameters
        // https://www.php.net/manual/fr/function.http-build-query.php#60523
        if (is_null($value)) {
            $value = 'null';
        }
        echo '                    \'' . ElementsUtilities::sanitizeFieldName($pk_field) . '\' => ' . $value . $end_line;
        $i++;
    }

?>
                );
                $this->pks[] = $primary_keys_array;
                $pk_concatenated_values = <?php echo implode(' . \'~\' . ', $primary_keys_values) ?>;
                $this->pk_concat_values[] = $pk_concatenated_values;
                $this->update_record_authorized[$pk_concatenated_values] = $update_authorized;
                $this->pk_url_params[] = http_build_query($primary_keys_array, '', '/');
<?php
// get the USERS_TABLE constant from secure
if (file_exists(ADMIN_DIR . 'secure/conf/conf.php')) {
    include_once ADMIN_DIR . 'secure/conf/conf.php';
}

for ($i=0; $i < $columns_count; $i++) {
    $colval = $columns['value'][$i];
    if (\strtolower($generator->table) == \strtolower(USERS_TABLE) . '_profiles' && $columns['name'][$i] != 'ID') {
        // convert the users_profiles values (0, 1, 2) to text (yes, no, restricted)
        $colval = 'ElementsUtilities::getUserProfileValue($row->' . $columns['name'][$i] . ')';
    }
    if ($generator->columns['select_from'][$i] == 'from_table' && $generator->columns['select_multiple'][$i] > 0) {
        ?>
                $json = false;
                if (!is_null(<?php echo $columns['value'][$i]; ?>)) {
                    $test_if_json = json_decode(<?php echo $columns['value'][$i]; ?>);
                    if (json_last_error() == JSON_ERROR_NONE && is_array($test_if_json)) {
                        $json = $test_if_json;
                    }
                }
                if ($json) {
                    $this-><?php echo ElementsUtilities::sanitizeFieldName($columns['name'][$i]); ?>[] = implode(', ', $json);
                } else {
                    $this-><?php echo ElementsUtilities::sanitizeFieldName($columns['name'][$i]); ?>[] = <?php echo $colval; ?>;
                }
<?php
    } else {
        echo '                $this->' . ElementsUtilities::sanitizeFieldName($columns['name'][$i]) . '[] = ' . $colval . ';' . "\n";
    }
} // end for
?>
            }
        }

        // Autocomplete doesn't need the followings settings
        if (!isset($_POST['is_autocomplete'])) {
            if (!$this->is_single_view) {
                // CREATE/DELETE rights
                if (Secure::canCreate($table) || Secure::canCreateRestricted($table)) {
                    $this->can_create = true;
                }

                // restricted UPDATE rights
                if (Secure::canUpdateRestricted($table)) {
                    $where = array_merge(
                        Secure::getRestrictionQuery($table),
                        $where_filters,
                        $where_search
                    );

                    $pdo_settings = array(
                        'function' => 'select',
                        'from'    => '<?php echo $generator->table ?>' . $active_filters_join_queries,
                        'values'   => $columns,
                        'where'    => $where,
                        'extras'   => array('order_by' => $this->sorting),
                        'debug'    => DEBUG_DB_QUERIES
                    );

                    // get authorized update primary keys
                    $db->pagine($pdo_settings, $npp, 'p', $pagination_url, 5, true, '/', '');
                    if (DEBUG_DB_QUERIES) {
                        $this->debug_content .= '<p class="debug-title text-bg-info">"' . $this->table . '" - get authorized update primary keys</p>' . $db->getDebugContent();
                    }
                    $records_count = $db->rowCount();
                    if (!empty($records_count)) {
                        while ($row = $db->fetch()) {
                            $this->update_record_authorized[<?php echo implode(' . \'~\' . ', $primary_keys_values) ?>] = true;
                        }
                    }
                }
            }
<?php

    /* =============================================
    External relations
    ============================================= */

if (count($generator->external_columns) > 0 && array_search(true, array_column($generator->external_columns, 'active')) !== false) {
?>

            /* external relations */

            for ($i=0; $i < count($this->pks); $i++) {
                $this->external_rows_count[$i] = array();
                $this->external_fields[$i] = array();
                $this->external_add_btn[$i] = array();
<?php
    foreach ($generator->external_columns as $key => $ext_col) {
        if ($ext_col['active']) {
            $origin_table              = $ext_col['relation']['origin_table'];
            $target_table              = $ext_col['relation']['target_table'];
            $intermediate_table        = $ext_col['relation']['intermediate_table'];
            $action_btns_target_table  = $ext_col['relation']['target_table'];

            // find the current table in the relationship
            $current_table = $origin_table;
            $current_table_pk_fieldname = $ext_col['relation']['origin_column'];
            if ($generator->table === $target_table) {
                $current_table = $target_table;
                $current_table_pk_fieldname = $ext_col['relation']['target_column'];
            }

            if (!empty($intermediate_table)) {
                // many to many
                $relation_table = $target_table;
?>

                // <?php echo $origin_table; ?> => <?php echo $intermediate_table; ?> => <?php echo $target_table; ?>

<?php
            } else {
                // one to many with the current table as target
                $relation_table = $origin_table;
?>
                // <?php echo $origin_table; ?> => <?php echo $target_table; ?>

<?php
            }

            /* get the primary key of the relation table
            to build the add + edit/delete links
            -------------------------------------------------- */

            $db = new DB(true);

            if (!empty($intermediate_table)) {
                // many to many

                // the 'add' button links to the intermediate table
                $intermediate_table_pk_columns = array();
                $columns = $db->getColumns($intermediate_table);
                $pdo_driver_object = 'phpformbuilder\\database\\pdodrivers\\' . ucfirst(PDO_DRIVER);
                $pdo_driver = new $pdo_driver_object($db->getPdo());
                $columns = $pdo_driver->convertColumns($intermediate_table, $columns);
                if (!empty($columns)) {
                    // Check if the table has an autoincremented primary key
                    $has_auto_increment_primary_key = false;

                    foreach ($columns as $column) {
                        if ($column->Key == 'PRI' && $column->Extra == 'auto_increment') {
                            $has_auto_increment_primary_key = true;
                        }
                    }

                    foreach ($columns as $column) {
                        if (isset($column->Field) && $column->Key == 'PRI' || (!$has_auto_increment_primary_key && $column->Null == 'NO' && count($intermediate_table_pk_columns) < 2)) {
                            $intermediate_table_pk_columns[] = $column->Field;
                        }
                    }
                }
                if (empty($intermediate_table_pk_columns)) {
                    $intermediate_table_pk_columns = array('unknown_primary_key');
                }

                // the 'edit/delete' button links to the target table
                $target_table_pk_columns = array();
                $columns = $db->getColumns($target_table);
                $pdo_driver_object = 'phpformbuilder\\database\\pdodrivers\\' . ucfirst(PDO_DRIVER);
                $pdo_driver = new $pdo_driver_object($db->getPdo());
                $columns = $pdo_driver->convertColumns($target_table, $columns);
                if (!empty($columns)) {
                    foreach ($columns as $column) {
                        if (isset($column->Field) && $column->Key == 'PRI') {
                            $target_table_pk_columns[]  = $column->Field;
                        }
                    }
                }
                if (empty($target_table_pk_columns)) {
                    $target_table_pk_columns = array('unknown_primary_key');
                }

                // if many to many AND if the READ LIST links lead to the intermediate relation table
                if (!isset($ext_col['action_btns_target_table']) || $ext_col['action_btns_target_table'] === $intermediate_table) {
                    $action_btns_target_table = $intermediate_table;
                    $upperCamelCase_intermediate_table = ElementsUtilities::upperCamelCase($intermediate_table);
                    $relation_item = mb_strtolower($upperCamelCase_intermediate_table);
                } else {
                    // if many to many AND if the READ LIST links lead to the target relation table
                    $upperCamelCase_target_table = ElementsUtilities::upperCamelCase($target_table);
                    $relation_item = mb_strtolower($upperCamelCase_target_table);
                }
            } else {
                // one to many with the current table as target
                $origin_table_pk_columns = array();
                $columns = $db->getColumns($origin_table);
                $pdo_driver_object = 'phpformbuilder\\database\\pdodrivers\\' . ucfirst(PDO_DRIVER);
                $pdo_driver = new $pdo_driver_object($db->getPdo());
                $columns = $pdo_driver->convertColumns($origin_table, $columns);
                if (!empty($columns)) {
                    foreach ($columns as $column) {
                        if (isset($column->Field) && $column->Key == 'PRI') {
                            $origin_table_pk_columns[]  = $column->Field;
                        }
                    }
                }
                if (empty($origin_table_pk_columns)) {
                    $origin_table_pk_columns = array('unknown_primary_key');
                }
                $upperCamelCase_origin_table = ElementsUtilities::upperCamelCase($origin_table);
                $relation_item = mb_strtolower($upperCamelCase_origin_table);
            }

            $fields_query = '';
            // SELECT payment.payment_id AS payment_payment_id, payment.payment_id, customer.amount, customer.payment_date FROM customer INNER JOIN payment
            // change the primary key to alias in the query
            if (!empty($intermediate_table)) {
                // remove pk from the target fields for the query
                $to_remove = $intermediate_table_pk_columns;
                $ext_cols_target_fields_without_pk = array_diff($ext_col['target_fields'], $to_remove);
                foreach ($intermediate_table_pk_columns as $column) {
                    $fields_query .= $intermediate_table . '.' . $column . ' AS ' . $intermediate_table . '_' . $column . ', ';
                }
                if (count($ext_cols_target_fields_without_pk) > 0) {
                    $fields_query .= $relation_table . '.' . implode(', ' . $target_table . '.', $ext_cols_target_fields_without_pk) . ', ';
                }
                $target_table_pk_columns_count = count($target_table_pk_columns);
                for ($i=0; $i < $target_table_pk_columns_count; $i++) {
                    $fields_query .= $relation_table . '.' . $target_table_pk_columns[$i] . ' AS target_table_pk_' . $i;
                }
            } else {
                $to_remove = $origin_table_pk_columns;
                $ext_cols_target_fields_without_pk = array_diff($ext_col['target_fields'], $to_remove);
                if ($origin_table != $target_table) {
                    foreach ($origin_table_pk_columns as $column) {
                        $fields_query .= $origin_table . '.' . $column . ' AS ' . $origin_table . '_' . $column . ', ';
                    }
                    $fields_query = rtrim($fields_query, ', ');
                    if (count($ext_cols_target_fields_without_pk) > 0) {
                        $fields_query .= ', ';
                        $fields_query .= $relation_table . '.' . implode(', ' . $relation_table . '.', $ext_cols_target_fields_without_pk);
                    }
                } else {
                    // self-reference table => we use aliases
                    // t2       = origin
                    // t1       = target
                    // relation = origin (t2)
                    foreach ($origin_table_pk_columns as $column) {
                        $fields_query .= 't2.' . $column . ' AS ' . $origin_table . '_' . $column . ', ';
                    }
                    $fields_query = rtrim($fields_query, ', ');
                    if (count($ext_cols_target_fields_without_pk) > 0) {
                        $fields_query .= ', ';
                        $fields_query .= 't2.' . implode(', t1' . $target_table . '.', $ext_cols_target_fields_without_pk);
                    }
                }
            }

            if (!empty($intermediate_table)) {
                // many to many
                $join_query = ' INNER JOIN ' . $intermediate_table . ' ON ' . $intermediate_table . '.' . $ext_col['relation']['intermediate_column_1'] . '=' . $origin_table . '.' . $ext_col['relation']['origin_column'];

                $join_query .= ' INNER JOIN ' . $target_table . ' ON ' . $intermediate_table . '.' . $ext_col['relation']['intermediate_column_2'] . '=' . $target_table . '.' . $ext_col['relation']['target_column'];
            } else {
                // one to many with the current table as target
                if ($origin_table != $target_table) {
                    $join_query = ' INNER JOIN ' . $origin_table . ' ON ' . $origin_table . '.' . $ext_col['relation']['origin_column'] . '=' . $target_table . '.' . $ext_col['relation']['target_column'];
                } else {
                    // self-reference table => we use aliases
                    $join_query = ' INNER JOIN ' . $origin_table . ' t2 ON t2.' . $ext_col['relation']['origin_column'] . '=t1.' . $ext_col['relation']['target_column'];

                // ie:
                // $qry = 'SELECT t1.brand_id AS brand_brand_id, t1.brand_name FROM brand AS t1 INNER JOIN brand t2 ON t1.is_sub_brand=t2.brand_id WHERE t1.' . $this->primary_key . ' = ' . $this->pk[$i];
                }
            }

            /*
            $generator->external_columns = array(
                'target_table'  => '',
                'target_fields' => array(),
                'table_label'   => '',
                'fields_labels' => array(),
                'relation'      => array(
                    'origin_table' // origin_table
                    'origin_column'
                    'intermediate_table'
                    'intermediate_column_1' // refers to origin_table
                    'intermediate_column_2' // refers to target_table
                    'target_table'
                    'target_column'
                ),
                'action_btns_target_table'
                'allow_crud_in_list' => false
                'active'             => false
            );

               'relation'      => array(
               'origin_table' => 'orders',
               'origin_column' => 'ID',
               'intermediate_table' => 'products_orders',
               'intermediate_column_1' => 'orders_ID',
               'intermediate_column_2' => 'products_ID',
               'target_table' => 'products',
               'target_column' => 'ID'
               );

               SELECT
             products.product_name,
             products.ID
               FROM
             orders INNER JOIN
             products_orders
               ON products_orders.orders_ID = orders.ID INNER JOIN
             products
               ON products_orders.products_ID = products.ID
               WHERE
             orders.ID = 1
            */

            $from  = $generator->table;
            $where_table = $generator->table;
            if (empty($intermediate_table) && $origin_table == $target_table) {
                // if one to many self-reference table
                $from .= ' AS t1';
                $where_table = 't1';
            }
?>
                $from = '<?php echo $from . $join_query; ?>';
                $values = '<?php echo $fields_query; ?>';
                $where = array();
                foreach ($this->pks[$i] as $key => $value) {
                    $where[] = '<?php echo $where_table; ?>.' . $key . ' = ' . $value;
                }
                $db->select($from, $values, $where, array('order_by' => $this->sorting), DEBUG_DB_QUERIES);
                if (DEBUG_DB_QUERIES) {
                    if ($i === 0) {
                        $this->debug_content .= '<p class="debug-title text-bg-info">"<?php echo $relation_table; ?>" queries <small>(External relation)</small></p>' . $db->getDebugContent();
                    } else {
                        $this->debug_content .= $db->getDebugContent();
                    }
                }
                $records_count = $db->rowCount();
                $this->external_rows_count[$i][] = $records_count;
                $ext_fields = array(
                    'table' => '<?php echo $relation_table; ?>',
                    'table_label' => '<?php echo $ext_col['table_label']; ?>',
                    'uniqid' => 'f-' . uniqid(),
                    'fields' => array(
<?php
            for ($i=0; $i < count($ext_col['target_fields']); $i++) {
?>
                        '<?php echo $ext_col['target_fields'][$i] . '\' => array()';
                        if ($i + 1 < count($ext_col['target_fields'])) {
                            echo ',';
                        }
?>

<?php
            } // end for
?>
                    ),
                    'fieldnames' => array(
<?php
                for ($i=0; $i < count($ext_col['target_fields']); $i++) {
?>
                        '<?php echo $ext_col['target_fields'][$i] . '\' => \'' . $ext_col['target_fields'][$i] . '\'';
                        if ($i + 1 < count($ext_col['target_fields'])) {
                            echo ',';
                        }
                        echo "\n";
                } // end for
?>
                    )
<?php

            // add url query parameter with primary key for the "add new" button
            $url_query_parameters = '';
            if (empty($ext_col['relation']['intermediate_table'])) {
                // if relation is one to many
                $url_query_parameters = '?' . $ext_col['relation']['origin_column'] . '=\' . $this->pks[$i][\'' . $current_table_pk_fieldname . '\'] . \'';
            } else {
                if (!isset($ext_col['action_btns_target_table']) || $ext_col['action_btns_target_table'] === $intermediate_table) {
                    $url_query_parameters = '?' . $ext_col['relation']['intermediate_column_1'] . '=\' . $this->pks[$i][\'' . $current_table_pk_fieldname . '\'] . \'';
                } else {
                    $url_query_parameters = '?' . $ext_col['relation']['origin_column'] . '=\' . $this->pks[$i][\'' . $current_table_pk_fieldname . '\'] . \'';
                }
            }
?>
                );

                // get user custom fieldnames
                $ext_fieldnames = ElementsUtilities::getFieldNames($ext_fields['table']);
                if ($ext_fieldnames !== false) {
                    foreach ($ext_fields['fieldnames'] as $key => $value) {
                        if (isset($ext_fieldnames[$key])) {
                            $ext_fields['fieldnames'][$key] = $ext_fieldnames[$key];
                        }
                    }
                }

                if (!$this->is_single_view) {
                    // add button
                    $add_btn = '';
<?php
            if ($ext_col['allow_crud_in_list']) {
?>
                    if (Secure::canCreate('<?php echo $action_btns_target_table; ?>')) {
                        if (!empty($records_count)) {
                            // add button for nested table
                            $add_btn = '<div class="d-flex flex-row-reverse mb-2">';
                            $add_btn .= ' <a href="' . ADMIN_URL . '<?php echo $relation_item; ?>/create<?php echo $url_query_parameters; ?>" class="btn btn-xs btn-primary" data-bs-title="<?php echo ADD_NEW; ?>" data-bs-toggle="tooltip"><span class="fas fa-plus-circle prepend"></span><?php echo ADD_NEW; ?> <?php echo $ext_col['table_label']; ?></a>';
                            $add_btn .= '</div>';
                        } else {
                            // add button for empty cell
                            $add_btn = '<div class="d-flex justify-content-center">';
                            $add_btn .= ' <a href="' . ADMIN_URL . '<?php echo $relation_item; ?>/create<?php echo $url_query_parameters; ?>" class="btn btn-xs btn-outline-secondary" data-bs-title="<?php echo ADD_NEW; ?>" data-bs-toggle="tooltip"><span class="fas fa-plus-circle prepend"></span><?php echo ADD_NEW; ?></a>';
                            $add_btn .= '</div>';
                        }
                    }
<?php
            } // end if
?>
                    $this->external_add_btn[$i][] = $add_btn;
                }

                if (!empty($records_count)) {
                    while ($row = $db->fetch()) {
<?php
            $ext_col_count = count($ext_col['target_fields']);
            for ($i=0; $i < $ext_col_count; $i++) {
                // replace the primary key with the alias used in query
                $row_field = $ext_col['target_fields'][$i];
                if (!empty($intermediate_table)) {
                    if (in_array($row_field, $intermediate_table_pk_columns)) {
                        $row_field = $intermediate_table . '_' . $row_field;
                    }
                } elseif (in_array($row_field, $origin_table_pk_columns)) {
                    $row_field = $origin_table . '_' . $row_field;
                }
?>
                        $json = false;
                        if (!is_null($row-><?php echo $row_field; ?>)) {
                            $test_if_json = json_decode($row-><?php echo $row_field; ?>);
                            if (json_last_error() == JSON_ERROR_NONE && is_array($test_if_json)) {
                                $json = $test_if_json;
                            }
                        }
                        if ($json) {
                            $ext_fields['fields']['<?php echo $ext_col['target_fields'][$i]; ?>'][] = implode(', ', $json);
                        } else {
                            $ext_fields['fields']['<?php echo $ext_col['target_fields'][$i]; ?>'][] = $row-><?php echo $row_field; ?>;
                        }
<?php
            } // end for
            if ($ext_col['allow_crud_in_list']) {
                $relation_table_pk_columns = array();
                if (!empty($ext_col['relation']['intermediate_table'])) {
                    if (!isset($ext_col['action_btns_target_table']) || $ext_col['action_btns_target_table'] === $intermediate_table) {
                        foreach ($intermediate_table_pk_columns as $column) {
                            $relation_table_pk_columns[] = array(
                                'colname' => $column,
                                'row_field' => $intermediate_table . '_' . $column
                            );
                        }
                    } else {
                        $target_table_pk_columns_count = count($target_table_pk_columns);
                        for ($i=0; $i < $target_table_pk_columns_count; $i++) {
                            $relation_table_pk_columns[] = array(
                                'colname' => $target_table_pk_columns[$i],
                                'row_field' => 'target_table_pk_' . $i
                            );
                        }
                    }
                } else {
                    foreach ($origin_table_pk_columns as $column) {
                        $relation_table_pk_columns[] = array(
                            'colname' => $column,
                            'row_field' => $origin_table . '_' . $column
                        );
                    }
                }
?>
                        if (!$this->is_single_view) {
                            // edit/delete buttons
                            if (Secure::canUpdate('<?php echo $action_btns_target_table; ?>') || Secure::canCreate('<?php echo $action_btns_target_table; ?>')) {
                                $action_btns = '<div class="btn-group">';
                                $relation_table_pk_columns = array(
<?php
    $nb = count($relation_table_pk_columns);
    $i = 0;
    $end_line = ',' . "\n";
    foreach ($relation_table_pk_columns as $column) {
        if ($i+1 === $nb) {
            $end_line = "\n";
        }
        echo '                                \'' . $column['colname'] . '\' => $row->' . $column['row_field'] . $end_line;
        $i++;
    }
?>
                                );
                                $url_params = http_build_query($relation_table_pk_columns, '', '/');
                                if (Secure::canUpdate('<?php echo $action_btns_target_table; ?>')) {
                                    $action_btns .= '<a href="' . ADMIN_URL . '<?php echo $relation_item; ?>/edit/' . $url_params . '" class="btn btn-xs btn-warning" data-bs-title="' . addslashes(EDIT) . '" rel="noindex" data-bs-toggle="tooltip"><span class="fas fa-pencil-alt"></span></a>';
                                }
                                if (Secure::canCreate('<?php echo $action_btns_target_table; ?>')) {
                                    $action_btns .= '<a href="' . ADMIN_URL . '<?php echo $relation_item; ?>/delete/' . $url_params . '" class="btn btn-xs btn-danger" data-bs-title="' . addslashes(DELETE_CONST) . '" rel="noindex" data-bs-toggle="tooltip"><span class="fas fa-times-circle"></span></a>';
                                }
                                $action_btns .= '</div>';
                                $ext_fields['fieldnames']['action'] = ACTION_CONST;
                                $ext_fields['fields']['action'][] = $action_btns;
                            } // end if
                        } // end if !$this->is_single_view
<?php
            } // end if
?>
                    } // end while
                } // end if
                $this->external_fields[$i][] = $ext_fields;
<?php
        } // end if active
    } // end foreach
?>
            } // end for
            $this->external_fields_count = count($this->external_fields);
<?php
}
?>
        } // end if

        if (!$this->is_single_view) {
            // Export data button
            $this->export_data_button = ElementsUtilities::exportDataButtons($table, $this->main_pdo_settings);

            // number/page
            $numbers_array = array(5, 10, 20, 50, 100, 200, 10000);
            $this->select_number_per_page = ElementsUtilities::selectNumberPerPage($numbers_array, $_SESSION['npp'], $this->item_url);
        }
    }
}
