<?php
namespace crud;

use common\Utils;
use phpformbuilder\database\DB;
use phpformbuilder\database\Pagination;
use secure\Secure;

class Alumnos extends Elements
{

    // item name passed in url
    public $item;

    // item name displayed
    public $item_label;

    // associative array : field => field displayed name
    public $fields;

    // external relations
    public $external_tables_count = 0;
    public $external_fields_count;
    public $external_rows_count;
    public $external_tables_labels = array('');
    public $external_add_btn = array();
    public $external_fields = array();

    // primary key passed to create|edit|delete
    public $primary_keys; // primary keys fieldnames

    // CREATE rights
    public $can_create = false;

    public $pks = array(); // primary key values for each row
    public $pk_concat_values = array(); // concatenated values of primary key(s) for each row
    public $pk_url_params = array(); // primary key(s) sent to the edit/delete forms URL for each row
    public $update_record_authorized = array();
    public $ID = array();
    public $Nombre = array();
    public $Fecha_Nacimiento = array();
    public $direccion = array();
    public $telefono = array();
    public $Email = array();
    public $Grado_ID = array();
    public $Fecha_Registro = array();

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

        $this->join_query = ' LEFT JOIN grados ON alumnos.Grado_ID=grados.ID';
        $columns = 'alumnos.ID, alumnos.Nombre, alumnos.Fecha_Nacimiento, alumnos.direccion, alumnos.telefono, alumnos.Email, grados.ID AS gra_ID, alumnos.Fecha_Registro';
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
        $this->sorting = ElementsUtilities::getSorting($table, 'ID', 'ASC');

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
            'from'    => 'alumnos' . $active_filters_join_queries,
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
                    'ID' => $row->ID
                );
                $this->pks[] = $primary_keys_array;
                $pk_concatenated_values = $row->ID;
                $this->pk_concat_values[] = $pk_concatenated_values;
                $this->update_record_authorized[$pk_concatenated_values] = $update_authorized;
                $this->pk_url_params[] = http_build_query($primary_keys_array, '', '/');
                $this->ID[] = $row->ID;
                $this->Nombre[] = $row->Nombre;
                $this->Fecha_Nacimiento[] = $row->Fecha_Nacimiento;
                $this->direccion[] = $row->direccion;
                $this->telefono[] = $row->telefono;
                $this->Email[] = $row->Email;
                $this->Grado_ID[] = $row->gra_ID;
                $this->Fecha_Registro[] = $row->Fecha_Registro;
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
                        'from'    => 'alumnos' . $active_filters_join_queries,
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
                            $this->update_record_authorized[$row->ID] = true;
                        }
                    }
                }
            }
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
