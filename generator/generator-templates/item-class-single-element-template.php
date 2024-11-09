<?php
// phpcs:disable Generic.WhiteSpace.ScopeIndent
use crud\ElementsUtilities;

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

    // item name dispolayed
    public $item_label;

    // associative array : field => field displayed name
    public $fields;
<?php
    $external_active_tables_count = 0;
if (count($generator->external_columns) > 0) {
    $external_active_tables_count = 0;
    foreach ($generator->external_columns as $col) {
        if ($col['active'] == true) {
            $external_active_tables_count++;
        }
    }
?>

    // external relations
    public $external_tables_count = <?php echo $external_active_tables_count; ?>;
    public $external_fields_count;
    public $external_rows_count;
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
    echo '    public $' . $generator->columns['name'][$i] . ' = array();' . "\n";
} // end for ?>

    public $debug_content = '';
    public $export_data_button;
    public $join_query = '';
    public $main_pdo_settings = array();
    public $records_count;
    public $item_url;

    public function __construct($element)
    {
        $this->table             = $element->table;
        $this->item              = $element->item;
        $this->item_label        = $element->item_label;
        $this->primary_keys      = $element->primary_keys;
        $this->select_data       = $element->select_data;
        $this->fields            = $element->fields;

        $table = $this->table;

        $this->item_url         = $_SERVER['REQUEST_URI'];
<?php
    // generate fields query
    $columns = array(
        'table' => array(),
        'query' => array(),
        'name' => array(),
        'value' => array()
    );
    for ($i=0; $i < $generator->columns_count; $i++) {
        $relation = $generator->columns['relation'][$i];

        if (empty($relation['target_table'])) {
            $columns['table'][] = $generator->table;
            $columns['query'][] = $generator->table . '.' . $generator->columns['name'][$i];
            $columns['name'][]  = $generator->columns['name'][$i];
            $columns['value'][] = '$row->' . $columns['name'][$i];
        } else {
            /* =============================================
            join if any relation
            ============================================= */

            $target_table  = $relation['target_table'];

            // relation query can use several fields
            $target_fields = explode(', ', $relation['target_fields']);

            $columns['table'][] = $target_table;

            $query = array();
            foreach ($target_fields as $target_field) {
                $query[] = $target_table . '.' . $target_field . ' AS ' . $target_table . '_' . $target_field;

                // register primary_key_alias if target_field is primary_key
                if ($target_table == $generator->table && $target_field == $this->primary_key) {
                    $this->primary_key_alias = $target_field;
                }
            }
            $columns['query'][] = implode(', ', $query);

            $columns['name'][]  = $generator->columns['name'][$i];

            $value = array();
            foreach ($target_fields as $target_field) {
                $value[] = '$row->' . $target_table . '_' . $target_field;
            }
            $columns['value'][] = implode(' . \' \' . ', $value);
        }
    }

    // create join queries
    $from_to = $generator->relations['from_to'];
    $join_query = array();
    if (is_array($from_to)) {
        foreach ($from_to as $ft) {
            if ($ft['origin_table'] == $generator->table) {
                if (empty($ft['intermediate_table'])) {
                    /* one-to one */

                    $join_query[] = ' LEFT JOIN ' . $ft['target_table'] . '
    ON ' . $ft['origin_table'] . '.' . $ft['origin_column'] . '=' . $ft['target_table'] . '.' . $ft['target_column'];
                        $used_tables[] = $ft['target_table'];
                } else {
                    /* many-to-many */
                }
            }
        }
    }

    // register column_count including relation fields
    $columns_count = count($columns['table']);
    $columns_query = implode(', ', $columns['query']);
    $join_query    = implode('', $join_query);
    if (!empty($join_query)) {
?>
        $this->join_query        = '<?php echo $join_query; ?>';
        $columns                 = '<?php echo $columns_query ?>';
<?php
    } else {
?>
        $columns                 = '<?php echo $columns_query ?>';
<?php
    }
?>
        $where = array();

        // restricted rights query
        if (Secure::canReadRestricted($table)) {
            $where = array_merge($where, Secure::getRestrictionQuery($table));
        }

        // connect to the database
        $db = new DB(DEBUG);
        $db->setDebugMode('register');

        if (empty($where)) {
            $where = null;
        }

        // main pdo settings required for the export_data button
        $this->main_pdo_settings = array(
            'function' => 'select',
            'from'    => '<?php echo $generator->table; ?>' . $this->join_query,
            'values'   => $columns,
            'where'    => $where,
            'extras'   => array(
                'limit' => 1
            ),
            'debug'    => DEBUG_DB_QUERIES
        );

        $db->select('<?php echo $generator->table; ?>' . $this->join_query, $columns, $where, array('limit' => 1), DEBUG_DB_QUERIES);

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
    $primary_keys_values = [];

    for ($i=0; $i < $generator->columns_count; $i++) {
        if ($generator->columns['primary'][$i]) {
            $pk_field = $generator->columns['name'][$i];
            $primary_keys_values[$pk_field] = '$row->' . $columns['name'][$i];
        }
    }

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
for ($i=0; $i < $columns_count; $i++) {
    echo '                $this->' . $columns['name'][$i] . '[]= ' . $columns['value'][$i] . ';' . "\n";
} // end for
?>
            }
        }

        // restricted UPDATE rights
        // (no need to restrict if read rights are already restricted : only allowed records are displayed)
        if (Secure::canUpdateRestricted($table) === true && Secure::canReadRestricted($table) !== true) {
            $where = Secure::getRestrictionQuery($table);

            // get authorized update primary keys
            $db->select('<?php echo $generator->table; ?>' . $this->join_query, $columns, $where, array(), DEBUG_DB_QUERIES);

            if (DEBUG_DB_QUERIES) {
                $this->debug_content .= '<p class="debug-title text-bg-info">"' . $this->table . '" - get authorized update primary keys</p>' . $db->getDebugContent();
            }
            $records_count = $db->rowCount();
            if (!empty($records_count)) {
                while ($row = $db->fetch()) {
                    $this->update_record_authorized[<?php echo implode(' . \'~\' . ', $primary_keys_values) ?>] = true;
                }
            }
        } elseif (Secure::canUpdate($table) === true || Secure::canUpdateRestricted($table) === true) {
            // user can update ALL records
            foreach ($this->pks as $key => $value) {
                $this->authorized_update_pk[] = reset($value);
            }
        }
<?php

    /* =============================================
    External relations
    ============================================= */

if (count($generator->external_columns) > 0) {
?>

        /* external relations */

        for ($i=0; $i < count($this->pks); $i++) {
            $this->external_rows_count[$i] = array();
            $this->external_fields[$i] = array();
<?php
    foreach ($generator->external_columns as $key => $ext_col) {
        if ($ext_col['active'] === true) {
            if (!empty($ext_col['relation']['intermediate_table'])) {
                // many to many
?>
            // <?php echo $ext_col['relation']['origin_table']; ?> => <?php echo $ext_col['relation']['intermediate_table']; ?> => <?php echo $ext_col['relation']['target_table']; ?>

<?php
            } else {
                // one to many with the current table as target
?>
                // <?php echo $ext_col['relation']['origin_table']; ?> => <?php echo $ext_col['relation']['target_table']; ?>

<?php
            }
                $target_table = $ext_col['target_table'];
                $target_fields_query = $ext_col['target_table'] . '.' . implode(', ' . $ext_col['target_table'] . '.', $ext_col['target_fields']);
            if (!empty($ext_col['relation']['intermediate_table'])) {
                // many to many
                $join_query = ' INNER JOIN ' . $ext_col['relation']['intermediate_table'] . '
            ON ' . $ext_col['relation']['intermediate_table'] . '.' . $ext_col['relation']['intermediate_column_1'] . '=' . $ext_col['relation']['origin_table'] . '.' . $ext_col['relation']['origin_column'];

                $join_query .= ' INNER JOIN ' . $ext_col['target_table'] . '
            ON ' . $ext_col['relation']['intermediate_table'] . '.' . $ext_col['relation']['intermediate_column_2'] . '=' . $ext_col['relation']['target_table'] . '.' . $ext_col['relation']['target_column'];
            } else {
                // one to many with the current table as target
                $join_query = ' INNER JOIN ' . $ext_col['relation']['origin_table'] . '
            ON ' . $ext_col['relation']['origin_table'] . '.' . $ext_col['relation']['origin_column'] . '=' . $ext_col['relation']['target_table'] . '.' . $ext_col['relation']['target_column'];
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
                    'active'        => false
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
?>
            $where = array();
            foreach ($this->pks as $pk) {
                $value = reset($pk);
                $key = key($pk);
                $where['<?php echo $generator->table; ?>.' . $key] = $value;
            }

            $db->select('<?php echo $generator->table . $join_query; ?>', '<?php echo $target_fields_query; ?>', $where, array(), DEBUG_DB_QUERIES);

            if (DEBUG_DB_QUERIES) {
                $this->debug_content .= '<p class="debug-title text-bg-info">"<?php echo $ext_col['relation']['origin_table']; ?>" queries</p>' . $db->getDebugContent();
            }

            $this->external_rows_count[$i][] = $db->rowCount();
            $ext_fields = array(
                'table'       => '<?php echo $target_table; ?>',
                'table_label' => '<?php echo $target_table; ?>',
                'uniqid'      => 'f-' . uniqid(),
                'fields'      => array(
<?php
    for ($i=0; $i < count($ext_col['target_fields']); $i++) {
?>
                    '<?php echo $ext_col['target_fields'][$i]; ?>' => array()<?php if ($i + 1 < count($ext_col['target_fields'])) {
                        echo ',';
                     }
                    ?>

<?php
    } // end for
?>
                )
            );
            if (!empty($this->external_rows_count[$i])) {
                while ($row = $db->fetch()) {
<?php
    for ($i=0; $i < count($ext_col['target_fields']); $i++) {
?>
                    $ext_fields['fields']['<?php echo $ext_col['target_fields'][$i]; ?>'][]  = $row-><?php echo $ext_col['target_fields'][$i]; ?>;
<?php
    } // end for
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

        // Export data button
        $this->export_data_button = ElementsUtilities::exportDataButtons($table, $this->main_pdo_settings, true);
    }
}
