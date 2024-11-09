<?php
namespace crud;

use common\Utils;
use phpformbuilder\database\DB;
use phpformbuilder\database\Pagination;
use secure\Secure;

class PhpcgUsersProfiles extends Elements
{

    // item name passed in url
    public $item;

    // item name displayed
    public $item_label;

    // associative array : field => field displayed name
    public $fields;

    // primary key passed to create|edit|delete
    public $primary_keys; // primary keys fieldnames

    // CREATE rights
    public $can_create = false;

    public $pks = array(); // primary key values for each row
    public $pk_concat_values = array(); // concatenated values of primary key(s) for each row
    public $pk_url_params = array(); // primary key(s) sent to the edit/delete forms URL for each row
    public $update_record_authorized = array();
    public $id = array();
    public $profile_name = array();
    public $r_alumnos = array();
    public $u_alumnos = array();
    public $cd_alumnos = array();
    public $cq_alumnos = array();
    public $r_carreras = array();
    public $u_carreras = array();
    public $cd_carreras = array();
    public $cq_carreras = array();
    public $r_cursos = array();
    public $u_cursos = array();
    public $cd_cursos = array();
    public $cq_cursos = array();
    public $r_grados = array();
    public $u_grados = array();
    public $cd_grados = array();
    public $cq_grados = array();
    public $r_grados_secciones = array();
    public $u_grados_secciones = array();
    public $cd_grados_secciones = array();
    public $cq_grados_secciones = array();
    public $r_inscripciones = array();
    public $u_inscripciones = array();
    public $cd_inscripciones = array();
    public $cq_inscripciones = array();
    public $r_notas = array();
    public $u_notas = array();
    public $cd_notas = array();
    public $cq_notas = array();
    public $r_padres_encargados = array();
    public $u_padres_encargados = array();
    public $cd_padres_encargados = array();
    public $cq_padres_encargados = array();
    public $r_pagos = array();
    public $u_pagos = array();
    public $cd_pagos = array();
    public $cq_pagos = array();
    public $r_profesores = array();
    public $u_profesores = array();
    public $cd_profesores = array();
    public $cq_profesores = array();
    public $r_unidades = array();
    public $u_unidades = array();
    public $cd_unidades = array();
    public $cq_unidades = array();
    public $r_phpcg_users = array();
    public $u_phpcg_users = array();
    public $cd_phpcg_users = array();
    public $cq_phpcg_users = array();
    public $r_phpcg_users_profiles = array();
    public $u_phpcg_users_profiles = array();
    public $cd_phpcg_users_profiles = array();
    public $cq_phpcg_users_profiles = array();

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


        $columns = 'phpcg_users_profiles.id, phpcg_users_profiles.profile_name, phpcg_users_profiles.r_alumnos, phpcg_users_profiles.u_alumnos, phpcg_users_profiles.cd_alumnos, phpcg_users_profiles.cq_alumnos, phpcg_users_profiles.r_carreras, phpcg_users_profiles.u_carreras, phpcg_users_profiles.cd_carreras, phpcg_users_profiles.cq_carreras, phpcg_users_profiles.r_cursos, phpcg_users_profiles.u_cursos, phpcg_users_profiles.cd_cursos, phpcg_users_profiles.cq_cursos, phpcg_users_profiles.r_grados, phpcg_users_profiles.u_grados, phpcg_users_profiles.cd_grados, phpcg_users_profiles.cq_grados, phpcg_users_profiles.r_grados_secciones, phpcg_users_profiles.u_grados_secciones, phpcg_users_profiles.cd_grados_secciones, phpcg_users_profiles.cq_grados_secciones, phpcg_users_profiles.r_inscripciones, phpcg_users_profiles.u_inscripciones, phpcg_users_profiles.cd_inscripciones, phpcg_users_profiles.cq_inscripciones, phpcg_users_profiles.r_notas, phpcg_users_profiles.u_notas, phpcg_users_profiles.cd_notas, phpcg_users_profiles.cq_notas, phpcg_users_profiles.r_padres_encargados, phpcg_users_profiles.u_padres_encargados, phpcg_users_profiles.cd_padres_encargados, phpcg_users_profiles.cq_padres_encargados, phpcg_users_profiles.r_pagos, phpcg_users_profiles.u_pagos, phpcg_users_profiles.cd_pagos, phpcg_users_profiles.cq_pagos, phpcg_users_profiles.r_profesores, phpcg_users_profiles.u_profesores, phpcg_users_profiles.cd_profesores, phpcg_users_profiles.cq_profesores, phpcg_users_profiles.r_unidades, phpcg_users_profiles.u_unidades, phpcg_users_profiles.cd_unidades, phpcg_users_profiles.cq_unidades, phpcg_users_profiles.r_phpcg_users, phpcg_users_profiles.u_phpcg_users, phpcg_users_profiles.cd_phpcg_users, phpcg_users_profiles.cq_phpcg_users, phpcg_users_profiles.r_phpcg_users_profiles, phpcg_users_profiles.u_phpcg_users_profiles, phpcg_users_profiles.cd_phpcg_users_profiles, phpcg_users_profiles.cq_phpcg_users_profiles';
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
        $this->sorting = ElementsUtilities::getSorting($table, 'id', 'ASC');

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
            'from'    => 'phpcg_users_profiles' . $active_filters_join_queries,
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
                    'id' => $row->id
                );
                $this->pks[] = $primary_keys_array;
                $pk_concatenated_values = $row->id;
                $this->pk_concat_values[] = $pk_concatenated_values;
                $this->update_record_authorized[$pk_concatenated_values] = $update_authorized;
                $this->pk_url_params[] = http_build_query($primary_keys_array, '', '/');
                $this->id[] = ElementsUtilities::getUserProfileValue($row->id);
                $this->profile_name[] = ElementsUtilities::getUserProfileValue($row->profile_name);
                $this->r_alumnos[] = ElementsUtilities::getUserProfileValue($row->r_alumnos);
                $this->u_alumnos[] = ElementsUtilities::getUserProfileValue($row->u_alumnos);
                $this->cd_alumnos[] = ElementsUtilities::getUserProfileValue($row->cd_alumnos);
                $this->cq_alumnos[] = ElementsUtilities::getUserProfileValue($row->cq_alumnos);
                $this->r_carreras[] = ElementsUtilities::getUserProfileValue($row->r_carreras);
                $this->u_carreras[] = ElementsUtilities::getUserProfileValue($row->u_carreras);
                $this->cd_carreras[] = ElementsUtilities::getUserProfileValue($row->cd_carreras);
                $this->cq_carreras[] = ElementsUtilities::getUserProfileValue($row->cq_carreras);
                $this->r_cursos[] = ElementsUtilities::getUserProfileValue($row->r_cursos);
                $this->u_cursos[] = ElementsUtilities::getUserProfileValue($row->u_cursos);
                $this->cd_cursos[] = ElementsUtilities::getUserProfileValue($row->cd_cursos);
                $this->cq_cursos[] = ElementsUtilities::getUserProfileValue($row->cq_cursos);
                $this->r_grados[] = ElementsUtilities::getUserProfileValue($row->r_grados);
                $this->u_grados[] = ElementsUtilities::getUserProfileValue($row->u_grados);
                $this->cd_grados[] = ElementsUtilities::getUserProfileValue($row->cd_grados);
                $this->cq_grados[] = ElementsUtilities::getUserProfileValue($row->cq_grados);
                $this->r_grados_secciones[] = ElementsUtilities::getUserProfileValue($row->r_grados_secciones);
                $this->u_grados_secciones[] = ElementsUtilities::getUserProfileValue($row->u_grados_secciones);
                $this->cd_grados_secciones[] = ElementsUtilities::getUserProfileValue($row->cd_grados_secciones);
                $this->cq_grados_secciones[] = ElementsUtilities::getUserProfileValue($row->cq_grados_secciones);
                $this->r_inscripciones[] = ElementsUtilities::getUserProfileValue($row->r_inscripciones);
                $this->u_inscripciones[] = ElementsUtilities::getUserProfileValue($row->u_inscripciones);
                $this->cd_inscripciones[] = ElementsUtilities::getUserProfileValue($row->cd_inscripciones);
                $this->cq_inscripciones[] = ElementsUtilities::getUserProfileValue($row->cq_inscripciones);
                $this->r_notas[] = ElementsUtilities::getUserProfileValue($row->r_notas);
                $this->u_notas[] = ElementsUtilities::getUserProfileValue($row->u_notas);
                $this->cd_notas[] = ElementsUtilities::getUserProfileValue($row->cd_notas);
                $this->cq_notas[] = ElementsUtilities::getUserProfileValue($row->cq_notas);
                $this->r_padres_encargados[] = ElementsUtilities::getUserProfileValue($row->r_padres_encargados);
                $this->u_padres_encargados[] = ElementsUtilities::getUserProfileValue($row->u_padres_encargados);
                $this->cd_padres_encargados[] = ElementsUtilities::getUserProfileValue($row->cd_padres_encargados);
                $this->cq_padres_encargados[] = ElementsUtilities::getUserProfileValue($row->cq_padres_encargados);
                $this->r_pagos[] = ElementsUtilities::getUserProfileValue($row->r_pagos);
                $this->u_pagos[] = ElementsUtilities::getUserProfileValue($row->u_pagos);
                $this->cd_pagos[] = ElementsUtilities::getUserProfileValue($row->cd_pagos);
                $this->cq_pagos[] = ElementsUtilities::getUserProfileValue($row->cq_pagos);
                $this->r_profesores[] = ElementsUtilities::getUserProfileValue($row->r_profesores);
                $this->u_profesores[] = ElementsUtilities::getUserProfileValue($row->u_profesores);
                $this->cd_profesores[] = ElementsUtilities::getUserProfileValue($row->cd_profesores);
                $this->cq_profesores[] = ElementsUtilities::getUserProfileValue($row->cq_profesores);
                $this->r_unidades[] = ElementsUtilities::getUserProfileValue($row->r_unidades);
                $this->u_unidades[] = ElementsUtilities::getUserProfileValue($row->u_unidades);
                $this->cd_unidades[] = ElementsUtilities::getUserProfileValue($row->cd_unidades);
                $this->cq_unidades[] = ElementsUtilities::getUserProfileValue($row->cq_unidades);
                $this->r_phpcg_users[] = ElementsUtilities::getUserProfileValue($row->r_phpcg_users);
                $this->u_phpcg_users[] = ElementsUtilities::getUserProfileValue($row->u_phpcg_users);
                $this->cd_phpcg_users[] = ElementsUtilities::getUserProfileValue($row->cd_phpcg_users);
                $this->cq_phpcg_users[] = ElementsUtilities::getUserProfileValue($row->cq_phpcg_users);
                $this->r_phpcg_users_profiles[] = ElementsUtilities::getUserProfileValue($row->r_phpcg_users_profiles);
                $this->u_phpcg_users_profiles[] = ElementsUtilities::getUserProfileValue($row->u_phpcg_users_profiles);
                $this->cd_phpcg_users_profiles[] = ElementsUtilities::getUserProfileValue($row->cd_phpcg_users_profiles);
                $this->cq_phpcg_users_profiles[] = ElementsUtilities::getUserProfileValue($row->cq_phpcg_users_profiles);
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
                        'from'    => 'phpcg_users_profiles' . $active_filters_join_queries,
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
                            $this->update_record_authorized[$row->id] = true;
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
