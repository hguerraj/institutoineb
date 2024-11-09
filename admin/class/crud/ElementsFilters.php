<?php

namespace crud;

use secure\Secure;
use phpformbuilder\Form;
use phpformbuilder\database\DB;
use common\Utils;

class ElementsFilters
{
    public $table                       = '';
    public $filters                     = array();
    public $filters_count               = 0;
    public $active_filters              = array();
    public $active_filtered_fields      = array();
    public $active_filters_from_tables  = array();
    public $active_filters_count        = 0;
    public $active_filters_select_names = array();
    public $ajax_filters                = array();

    private $debug;
    private $has_error = false; // true if an exception has been thrown
    private $join_query;
    private $last_sql = '';
    private $where_restriction = array();

    /**
     * register filters and list
     * @param string $table    name of the table to filter (ex : clients)
     * @param array  $filters ; example :
     *                        $filters = array(
     *                        array(
     *                        'ajax'            =>  false,
     *                        'daterange'       =>  false,
     *                        'filter_mode'     =>  'simple',
     *                        'filter_A'        =>   'ID',
     *                        'select_label'    =>  'sous-menu',
     *                        'select_name'     =>  'dropdown_ID',
     *                        'option_text'     =>  'nav_nom + dropdown.nom',
     *                        'fields'          =>  'dropdown.ID, dropdown.nom, nav.nom AS nav_nom',
     *                        'field_to_filter' =>  'dropdown.ID',
     *                        'from'            =>  'pages, dropdown Left Join nav On dropdown.nav_ID = nav.ID',
     *                        'type'            =>  'text'
     *                        ),
     *                        array(
     *                        ...
     *                        )
     *                        );
     * @param string $join_query    JOIN query from the Element
     * @param boolean $debug        if true $db will debug all the queries sent by this class.
     *                              else $db will only throw the errors if the global DEBUG constant is enabled.
     */
    public function __construct($table, $filters, $join_query = '', $debug = false)
    {
        $this->debug         = $debug;
        $this->filters       = $filters;
        $this->filters_count = count($this->filters);
        $this->table         = $table;

        // ajax - reset filters on each page load
        $_SESSION['filters_ajax'] = array();

        for ($i = 0; $i < $this->filters_count; $i++) {
            $filter = $this->filters[$i];
            // ajax filter default value
            if (!isset($filter['ajax'])) {
                $filter['ajax'] = false;
                $this->filters[$i] = $filter;
            }
            // daterange default value
            if (!isset($filter['daterange'])) {
                $filter['daterange'] = false;
                $this->filters[$i] = $filter;
            }
            $filter_var = $this->table . '_filter_' . $filter['select_name'];
            // get the active filters from session to build the queries
            $this->active_filters[$i] = array();
            if (isset($_SESSION['filters-list'][$filter_var]) && $_SESSION['filters-list'][$filter_var] !== '') {
                $this->active_filters[$i]            = $filter;
                $this->active_filters_from_tables[]  = $filter['from_table'];
                $this->active_filters_select_names[] = $filter['select_name'];
                // remove the table name from the field to filter
                // ie: actor.name => name
                $field = $filter['field_to_filter'];
                if ($pos = strpos($field, '.')) {
                    $field = substr($field, $pos + 1);
                }
                $this->active_filtered_fields[] = $field;
                $this->active_filters_count++;
            }
        }

        // join-query will be used only with filters[filter_mode = 'simple']
        // Advanced filters's join query is entered in SQL FROM settings's advanced parameters
        $this->join_query = str_replace('`', '', $join_query);
        $this->where_restriction = Secure::getRestrictionQuery($this->table);
    }

    /**
     * create html form with selects of elements to filter
     * @param  string $form_action ex : page.php
     * @param  boolean $use_restrictions false only if request comes from generator filter test.
     * @return string form html
     */
    public function returnForm($form_action, $use_restrictions = true)
    {
        $log                  = array();
        $output               = '';
        $datetime_field_types = explode(',', DATETIME_FIELD_TYPES);
        if ($this->filters_count > 0) {
            $output .= '<div class="px-3">' . "\n";
            $attr = 'novalidate';
            if (AUTO_ENABLE_FILTERS === true) {
                $attr .= ', class=auto-enable-filters';
            }
            $form = new Form('filters-list', 'vertical', $attr);
            $form->setOptions(['elementsWrapper' => '<div class="row gx-0 mb-3"></div>']);
            if (isset($_POST['cancel_filters'])) {
                Form::clear('filters-list');
            }
            $form->setCols(-1, -1);
            $form->setAction($form_action);
            $form->startFieldset();
            $db = new DB(DEBUG);
            $where_restrict = array();
            // if limited READ rights
            if ($use_restrictions && Secure::canReadRestricted($this->table)) {
                $where_restrict = $this->where_restriction;
            }
            for ($i = 0; $i < $this->filters_count; $i++) {
                $filter          = $this->filters[$i];
                $field_var       = $this->table . '_filter_' . $filter['select_name'];
                $select_name     = $filter['select_name'];

                $where_filter = $this->getWhere($filter['field_to_filter']);

                // merge the join_query from the current filter and the active ones
                if (count($this->active_filters_select_names) > 0) {
                    $current_join_query = $this->mergeJoinQueries($filter);
                } else {
                    $current_join_query = implode(' ', $filter['join_queries']);
                }

                /*====================================================================================================================
                we retrieve all the filters, except the one of the current field (we filter the dropdown list according to the other fields,
                but not the field itself. e.g. if a category is chosen, we still display them all in the list.
                ====================================================================================================================== */

                $option_text = array();
                $option_value = array();
                $where = array_merge($where_filter, $where_restrict);
                $pdo_select_settings = array(
                    'from'  => $filter['from_table'] . $current_join_query,
                    'values' => $filter['fields'],
                    'where'  => $where,
                    'extras' => array(
                        'select_distinct' => true,
                        'order_by' => $this->getAliases($filter['fields']) . ' ASC'
                    )
                );
                if ($this->debug === true) {
                    $log[] = array(
                        '$select_name          => ' . $select_name,
                        'from                  => ' . $filter['from'],
                        'join_query            => ' . $this->join_query,
                        'where_restrict        => ' . var_export($where_restrict, true),
                        'where_filter          => ' . var_export($where_filter, true),
                        '$pdo_select_settings  => ' . var_export($pdo_select_settings, true)
                    );
                }
                if ($filter['ajax'] === true) {
                    $_SESSION['filters_ajax'][$field_var] = array(
                        'table'                => $this->table,
                        'field_to_filter'      => $filter['field_to_filter'],
                        'option_text'          => $filter['option_text'],
                        'pdo_select_settings'  => $pdo_select_settings
                    );
                    $form->addOption($field_var, '', '', '', 'label=' . SELECT_CONST . ', data-placeholder=true');
                    // if an ajax field is active we get the display value from a new query
                    if (isset($_SESSION['filters-list'][$field_var]) && $_SESSION['filters-list'][$field_var] !== '') {
                        // var_dump($filter);
                        $where_option = array();
                        $field_to_filter = $filter['field_to_filter'];
                        // if json values => $_SESSION['filters-list'][$field_var] = ~value~
                        if (preg_match('`^~([^~]+)~$`', $_SESSION['filters-list'][$field_var], $out)) {
                            $where_option[] = 'JSON_VALID(' . $field_to_filter . ') AND JSON_CONTAINS(' . $field_to_filter . ', \'["' . $out[1] . '"]\')';
                        } else {
                            $where_option[] = $field_to_filter . ' = ' . $db->safe($_SESSION['filters-list'][$field_var]);
                        }
                        $from = $filter['from'] . $current_join_query;
                        $columns = $filter['fields'];
                        $where_option = array_merge($where, $where_option);
                        if ($row = $db->selectRow($from, $columns, $where_option, $this->debug)) {
                            $this->last_sql = $db->getLastSql();
                            if (preg_match('`[\s]?\+[\s]?`', $filter['option_text'])) {
                                $field_text = preg_split('`[\s]?\+[\s]?`', $filter['option_text']);
                                $field_text[0] = preg_replace('`[^.]+\.`', '', $field_text[0]);
                                $field_text[1] = preg_replace('`[^.]+\.`', '', $field_text[1]);
                            } else {
                                $field_text = preg_replace('`[^.]+\.`', '', $filter['option_text']);
                            }
                            $field_value = preg_replace('`[^.]+\.`', '', $filter['field_to_filter']);
                            $test_if_json = json_decode($row->$field_value);
                            if (json_last_error() == JSON_ERROR_NONE && is_array($test_if_json)) {
                                foreach ($test_if_json as $value) {
                                    if ('~' . $value . '~' === $_SESSION['filters-list'][$field_var]) {
                                        $option_text = $value;
                                        $option_value = '~' . $value . '~';
                                    }
                                }
                            } else {
                                if (is_array($field_text)) {
                                    $f0 = $field_text[0];
                                    $f1 = $field_text[1];
                                    $option_text = $row->$f0 . '/' . $row->$f1;
                                } else {
                                    $option_text = $row->$field_text;
                                }
                                $option_value = $row->$field_value;
                            }
                            // var_dump($option_text);
                            $form->addOption($field_var, $option_value, $option_text, '', 'selected');
                        }
                    }
                    $form->addSelect($field_var, ucwords($filter['select_label']), 'class=ajax-filter form-select-sm, data-allow-deselect=true, data-allow-deselect-option=true, data-placeholder=' . SELECT_CONST . ' ...');
                } elseif ($filter['daterange'] === true && in_array($filter['type'], $datetime_field_types)) {
                    if (!$db->select($pdo_select_settings['from'], $pdo_select_settings['values'], $pdo_select_settings['where'], $pdo_select_settings['extras'], $this->debug)) {
                        $this->last_sql = $db->getLastSql();
                        $output .= '<p class="text-center text-danger"><strong><em>' . ucwords($filter['select_label']) . '</em></strong>: ' . QUERY_FAILED . ':<br>' . $this->last_sql . '</p>';
                    } else {
                        $this->last_sql = $db->getLastSql();
                        $values_count = $db->rowCount();
                        if (!empty($values_count)) {
                            $field_value = preg_replace('`[^.]+\.`', '', $filter['field_to_filter']);
                            $all_dates   = array();
                            while ($row = $db->fetch()) {
                                $all_dates[] = $row->$field_value;
                            }

                            // remove NULL values
                            $all_dates = array_filter(
                                $all_dates,
                                function ($value) {
                                    return !is_null($value) && $value !== '';
                                }
                            );

                            sort($all_dates);

                            $date_min = new \DateTime($all_dates[0]);
                            $date_min = $date_min->format('Y-m-d');
                            if (isset($all_dates[$values_count - 1])) {
                                $date_max = new \DateTime($all_dates[$values_count - 1]);
                                $date_max->add(new \DateInterval('P1D'));
                                $date_max = $date_max->format('Y-m-d');
                            } else {
                                // if only 1 record
                                $date_max = new \DateTime('now');
                                $date_max->add(new \DateInterval('P1D'));
                                $date_max = $date_max->format('Y-m-d');
                            }

                            $form->addInput('text', $field_var, '', ucwords($filter['select_label']), 'class=litepick form-control-sm, placeholder=' . DISPLAY_ALL . ', autocomplete=off, data-min-date=' . $date_min . ', data-max-date=' . $date_max);
                        }
                    }
                } else {
                    if (!$db->select($pdo_select_settings['from'], $pdo_select_settings['values'], $pdo_select_settings['where'], $pdo_select_settings['extras'], $this->debug)) {
                        $this->last_sql = $db->getLastSql();
                        $this->has_error = true;
                        $output .= '<p class="text-center text-danger"><strong><em>' . ucwords($filter['select_label']) . '</em></strong>: ' . QUERY_FAILED . ':<br>' . $this->last_sql . '</p>';
                    } else {
                        $this->last_sql = $db->getLastSql();
                        $values_count = $db->rowCount();
                        if (!empty($values_count)) {
                            try {
                                if (preg_match('`[\s]?\+[\s]?`', $filter['option_text'])) {
                                    $field_text = preg_split('`[\s]?\+[\s]?`', $filter['option_text']);
                                    $field_text[0] = preg_replace('`[^.]+\.`', '', $field_text[0]);
                                    $field_text[1] = preg_replace('`[^.]+\.`', '', $field_text[1]);
                                } else {
                                    $field_text = preg_replace('`[^.]+\.`', '', $filter['option_text']);
                                }
                                $field_value = preg_replace('`[^.]+\.`', '', $filter['field_to_filter']);
                                $used_values = array();
                                while ($row = $db->fetch()) {
                                    if (!isset($row->$field_value) && !is_null($row->$field_value)) {
                                        $this->has_error = true;
                                        throw new \Exception('The field "' . $field_value . '" is missing in the SQL query (1).', 1);
                                    }
                                    $test_if_json = json_decode($row->$field_value);
                                    if (json_last_error() == JSON_ERROR_NONE && is_array($test_if_json)) {
                                        foreach ($test_if_json as $value) {
                                            if (!in_array($value, $used_values)) {
                                                $option_text[] = $value;
                                                $option_value[] = '~' . $value . '~';
                                                $used_values[] = $value;
                                            }
                                        }
                                    } else {
                                        if (is_array($field_text)) {
                                            $f0 = $field_text[0];
                                            $f1 = $field_text[1];
                                            if (!isset($row->$f0)) {
                                                $this->has_error = true;
                                                throw new \Exception('The field "' . $f0 . '" is missing in the SQL query (2).', 1);
                                            } elseif (!isset($row->$f1)) {
                                                $this->has_error = true;
                                                throw new \Exception('The field "' . $f1 . '" is missing in the SQL query (3).', 1);
                                            }
                                            $option_text[]  = $row->$f0 . '/' . $row->$f1;
                                            $option_value[] = $row->$field_value;
                                        } else {
                                            if (!isset($row->$field_text) && !is_null($row->$field_value)) {
                                                $this->has_error = true;
                                                throw new \Exception('The field "' . $field_text . '" is missing in the SQL query (4).', 1);
                                            }
                                            $timestamp = strtotime($row->$field_value);
                                            if ($timestamp) {
                                                // Remove milliseconds from Timestamp
                                                $datetime = date('Y-m-d H:i:s', $timestamp);
                                                if (!in_array($datetime, $used_values)) {
                                                    $option_text[]  = $datetime;
                                                    $option_value[] = $datetime;
                                                    $used_values[]  = $datetime;
                                                }
                                            } else {
                                                $option_text[]  = $row->$field_text;
                                                $option_value[] = $row->$field_value;
                                            }
                                        }
                                    }
                                }
                            } catch (\Throwable $e) {
                                echo '<p class="alert alert-danger">' . $e->getMessage() . '</p>';
                            }

                            $values_count = count($option_value);

                            for ($j = -1; $j < $values_count; $j++) {
                                if ($j == -1) {
                                    $form->addOption($field_var, '', '', '', 'label=' . SELECT_CONST . ', data-placeholder=true');
                                } elseif ($filter['type'] == 'text' || $filter['type'] == 'datetime' || $filter['type'] === 'timestamp') {
                                    $op_txt = $option_text[$j];
                                    if (empty($op_txt)) {
                                        $op_txt = '-';
                                    }
                                    $form->addOption($field_var, $option_value[$j], $op_txt);
                                }
                            }
                            if ($filter['type'] == 'boolean') {
                                if (in_array('1', $option_value)) {
                                    $form->addOption($field_var, '1', YES);
                                }
                                if (in_array('0', $option_value)) {
                                    $form->addOption($field_var, '0', NO);
                                }
                            }
                            $form->addSelect($field_var, ucwords($filter['select_label']), 'class=form-select-sm, data-slimselect=true, data-allow-deselect=true, data-allow-deselect-option=true, data-placeholder=' . SELECT_CONST . ' ...');
                        }
                    }
                }
            }

            // reset then add buttons
            $form->centerContent();
            $form->setCols(0, 12);
            $form->addHtml('<span class="d-block mb-4"></span>');
            $form->addBtn('submit', 'cancel_filters', 1, '<i class="' . ICON_RESET . ' prepend"></i>' . RESET, 'class=btn btn-sm btn-warning', 'btns');
            $form->addBtn('submit', 'submit_filters', 1, FILTER . '<i class="' . ICON_FILTER . ' append"></i>', 'class=btn btn-sm btn-primary', 'btns');
            $form->printBtnGroup('btns');
            $form->endFieldset();
            $output .= $form->render(false, false);
            $output .= '</div>' . "\n";
        }

        if ($this->debug && !empty($log)) {
            $content = array();
            foreach ($log as $array) {
                $content[] = implode('<br>', $array);
            }
            $this->userMessage('<span class="badge badge-primary me-2">LOG OUTPUT</span>returnForm()', 'panel-default m-5', 'close', implode('<br><br>', $content));
        }

        return $output;
    }

    /**
     * build filtered elements
     * @param  string $current_select
     * @return string the filtered elements query
     */
    public function buildElementJoinQuery()
    {
        $log = array();
        $joins = array(
            'join_queries' => array(),
            'from_table'   => array()
        );
        $final_join_queries   = array();

        foreach ($this->active_filters as $active_filter) {
            if (!empty($active_filter)) {
                $active_filter_join_queries_count = count($active_filter['join_queries']);
                for ($i = 0; $i < $active_filter_join_queries_count; $i++) {
                    if (!empty($active_filter['join_queries'])) {
                        $joins['join_queries'][] = $active_filter['join_queries'][$i];
                        $joins['origin_table'][]   = $active_filter['from_table'];
                    }
                }
            }
        }

        // if the table joined is the element table we have to invert the joined table

        /* WRONG
        FROM actor
        INNER JOIN film_actor ON film_actor.film_id = film.film_id =>INNER JOIN film ON film.film_id = film_actor.film_id
        INNER JOIN actor ON film_actor.actor_id =actor.actor_id

        OK
        FROM actor
        INNER JOIN film_actor ON film_actor.actor_id =actor.actor_id
        INNER JOIN film ON film.film_id = film_actor.film_id
        */

        $join_type           = array();
        $joined_table        = array();
        $joined_table_alias  = array();
        $left_equal_table    = array();
        $left_equal_field    = array();
        $right_equal_table   = array();
        $right_equal_field   = array();

        $joins_count = count($joins['join_queries']);
        for ($i = 0; $i < $joins_count; $i++) {
            if (preg_match('`(LEFT|INNER|RIGHT) JOIN ([a-zA-Z0-9_]+) ON ([a-zA-Z0-9_]+).([a-zA-Z0-9_]+)(?:[\s]*=[\s]*)([a-zA-Z0-9_]+).([a-zA-Z0-9_]+)`', $joins['join_queries'][$i], $out)) {
                $join_type[]         = $out[1];
                if (!isset($out[7])) {
                    // if no table alias
                    $joined_table[]       = $out[2];
                    $joined_table_alias[] = '';
                    $left_equal_table[]   = $out[3];
                    $left_equal_field[]   = $out[4];
                    $right_equal_table[]  = $out[5];
                    $right_equal_field[]  = $out[6];
                } else {
                    $joined_table[]       = $out[2];
                    $joined_table_alias[] = $out[3];
                    $left_equal_table[]   = $out[4];
                    $left_equal_field[]   = $out[5];
                    $right_equal_table[]  = $out[6];
                    $right_equal_field[]  = $out[7];
                }
            } else {
                // ERROR
                $log[] = array('Malformed JOIN query');
            }
        }

        // add the Element join queries to the filter joins
        // $this->join_query = ' LEFT JOIN `types` ON `articles`.`types_ID`=`types`.`ID` LEFT JOIN `marques` ON `articles`.`marques_ID`=`marques`.`ID`';
        /*
            $out = array(
                0 =>
                  array (size=2)
                    0 => string 'LEFT JOIN types ON articles.types_ID=types.ID' (length=45)
                    1 => string 'LEFT JOIN marques ON articles.marques_ID=marques.ID' (length=51)
                1 =>
                  array (size=2)
                    0 => string 'LEFT' (length=4)
                    1 => string 'LEFT' (length=4)
                2 =>
                  array (size=2)
                    0 => string 'types' (length=5)
                    1 => string 'marques' (length=7)
                3 =>
                  array (size=2)
                    0 => string 'articles' (length=8)
                    1 => string 'articles' (length=8)
                4 =>
                  array (size=2)
                    0 => string 'types_ID' (length=8)
                    1 => string 'marques_ID' (length=10)
                5 =>
                  array (size=2)
                    0 => string 'types' (length=5)
                    1 => string 'marques' (length=7)
                6 =>
                  array (size=2)
                    0 => string 'ID' (length=2)
                    1 => string 'ID' (length=2)
            }
            */
        if ($element_joins_count = preg_match_all('`(LEFT|INNER|RIGHT) JOIN ([a-zA-Z0-9_]+)\s?([a-zA-Z0-9_]+)? ON ([a-zA-Z0-9_]+).([a-zA-Z0-9_]+)(?:[\s]*=[\s]*)([a-zA-Z0-9_]+).([a-zA-Z0-9_]+)`', $this->join_query, $out)) {
            for ($i = 0; $i < $element_joins_count; $i++) {
                $join_type[]         = $out[1][$i];
                $joined_table[]       = $out[2][$i];
                if (!isset($out[7])) {
                    // if no table alias
                    $joined_table_alias[] = '';
                    $left_equal_table[]   = $out[3][$i];
                    $left_equal_field[]   = $out[4][$i];
                    $right_equal_table[]  = $out[5][$i];
                    $right_equal_field[]  = $out[6][$i];
                } else {
                    $joined_table_alias[] = $out[3][$i];
                    $left_equal_table[]   = $out[4][$i];
                    $left_equal_field[]   = $out[5][$i];
                    $right_equal_table[]  = $out[6][$i];
                    $right_equal_field[]  = $out[7][$i];
                }
            }
        }

        // each left table MUST BE UNIQUE AND MUST NOT BE THE MAIN QUERY TABLE (ie $this->table)
        $used_left_tables   = array($this->table);
        $final_join_queries = array();
        $joins_count        = count($join_type);

        // 1st loop: register joins which use $this->table
        for ($i = 0; $i < $joins_count; $i++) {
            if ($left_equal_table[$i] == $this->table || $right_equal_table[$i] == $this->table) {
                $case = 'SKIP';
                if ($right_equal_table[$i] == $this->table && !in_array($left_equal_table[$i], $used_left_tables)) {
                    $invert_query = false;
                    $used_left_tables[] = $left_equal_table[$i];
                    $case = '[ORIGINAL JOIN] - ';
                } elseif ($left_equal_table[$i] == $this->table && !in_array($right_equal_table[$i], $used_left_tables)) {
                    $invert_query = true;
                    $used_left_tables[] = $right_equal_table[$i];
                    $case = '[INVERTED JOIN] - ';
                }

                if ($case != 'SKIP') {
                    $new_join_query = $this->formatJoinQuery($join_type[$i], $joined_table[$i], $joined_table_alias[$i], $left_equal_table[$i], $left_equal_field[$i], $right_equal_table[$i], $right_equal_field[$i], $invert_query);

                    $final_join_queries[] = $new_join_query;

                    if ($this->debug === true) {
                        $log[] = array($case . $this->table . '<br>' . $new_join_query . '<br>--------------<br>');
                    }
                }
            }
        }

        // 2nd loop: register joins which don't use $this->table
        for ($i = 0; $i < $joins_count; $i++) {
            if ($left_equal_table[$i] != $this->table && $right_equal_table[$i] != $this->table) {
                $case = 'SKIP';
                if (!in_array($left_equal_table[$i], $used_left_tables)) {
                    $invert_query = false;
                    $used_left_tables[] = $left_equal_table[$i];
                    $case = '[ORIGINAL JOIN] - ';
                } elseif (!in_array($right_equal_table[$i], $used_left_tables)) {
                    $invert_query = true;
                    $used_left_tables[] = $right_equal_table[$i];
                    $case = '[INVERTED JOIN] - ';
                }

                if ($case != 'SKIP') {
                    $new_join_query = $this->formatJoinQuery($join_type[$i], $joined_table[$i], $joined_table_alias[$i], $left_equal_table[$i], $left_equal_field[$i], $right_equal_table[$i], $right_equal_field[$i], $invert_query);

                    $final_join_queries[] = $new_join_query;

                    if ($this->debug === true) {
                        $log[] = array($case . $this->table . '<br>' . $new_join_query . '<br>--------------<br>');
                    }
                }
            }
        }

        $final_join_queries = array_filter(array_unique($final_join_queries));

        if ($this->debug === true) {
            if (!empty($final_join_queries)) {
                $log[] = array_merge(array('FINAL JOIN QUERY: '), $final_join_queries);
            } else {
                $log[] = array('ELEMENT JOIN QUERY IS EMPTY');
            }

            if (!empty($log)) {
                $content = array();
                foreach ($log as $array) {
                    $content[] = implode('<br>', $array);
                }
                $this->userMessage('<span class="badge badge-primary me-2">LOG OUTPUT</span>buildElementJoinQuery()', 'panel-default m-5', 'close', implode('<br><br>', $content));
            }
        }

        return implode(' ', $final_join_queries);
    }

    public function getActiveFilteredFields()
    {
        return $this->active_filtered_fields;
    }

    public function getLastSql()
    {
        return $this->last_sql;
    }

    /**
     * build filtered elements
     * @param  string $current_select
     * @return array the filtered elements queries in an array
     */
    public function getWhere($current_field = '')
    {
        $db = new DB(DEBUG);
        $qry_where = array();
        $i = 0;
        foreach ($this->filters as $filter) {
            $select_name     = $filter['select_name'];
            $field_to_filter = $filter['field_to_filter'];
            $daterange       = $filter['daterange'];
            $type            = $filter['type'];

            $field_var = $this->table . '_filter_' . $select_name;
            if (!isset($_SESSION['filters-list'][$field_var]) || (empty($_SESSION['filters-list'][$field_var]) && $_SESSION['filters-list'][$field_var] !== (int) 0 && $_SESSION['filters-list'][$field_var] !== (string) '0')) {
                $_SESSION['filters-list'][$field_var] = '';
            }
            // echo $field_var . ' => ' . $_SESSION['filters-list'][$field_var] . '<br>';
            // echo $field_to_filter . ' => ' . $current_field . '<br>';
            if ($_SESSION['filters-list'][$field_var] != '' && $field_to_filter != $current_field) {
                $datetime_field_types = explode(',', DATETIME_FIELD_TYPES);

                // if json values => $_SESSION['filters-list'][$field_var] = ~value~
                if (preg_match('`^~([^~]+)~$`', $_SESSION['filters-list'][$field_var], $out)) {
                    $qry_where[] = 'JSON_VALID(' . $db->safe($field_to_filter) . ') AND JSON_CONTAINS(' . $db->safe($field_to_filter) . ', \'["' . $out[1] . '"]\')';
                } elseif (in_array($type, $datetime_field_types)) {
                    if (boolval($daterange)) {
                        $range_dates = explode(' - ', $_SESSION['filters-list'][$field_var]);
                        if (count($range_dates) === 2) {
                            $qry_where[$field_to_filter . ' >= '] = $range_dates[0];
                            $qry_where[$field_to_filter . ' <= '] = $range_dates[1];
                        }
                    } elseif ($type === 'timestamp') { //  && PDO_DRIVER === 'firebird'
                        // Firebird uses milliseconds and won't find any record if we don't set a date range with 0 to 999 ms
                        $qry_where[$field_to_filter . ' >= '] = $_SESSION['filters-list'][$field_var] . '.000';
                        $qry_where[$field_to_filter . ' <= '] = $_SESSION['filters-list'][$field_var] . '.999';
                    } else {
                        $qry_where[$field_to_filter] = $_SESSION['filters-list'][$field_var];
                    }
                } else {
                    $qry_where[$field_to_filter] = $_SESSION['filters-list'][$field_var];
                }
            }
            $i++;
        }

        return $qry_where;
    }

    /**
     * @return bool true if an exception has been thrown
     */
    public function hasError()
    {
        return $this->has_error;
    }

    /**
     * build the JOIN query
     * @param  syting  $join_type         LEFT|INNER
     * @param  syting  $left_equal_table
     * @param  syting  $left_equal_field
     * @param  syting  $right_equal_table
     * @param  syting  $right_equal_field
     * @param  boolean $invert            invert left/right terms if true
     * @return string                     the join query for SQL
     */
    private function formatJoinQuery($join_type, $joined_table, $joined_table_alias, $left_equal_table, $left_equal_field, $right_equal_table, $right_equal_field, $invert = false)
    {
        if (!$invert) {
            $join_qry = ' ' . $join_type . ' JOIN ' . $left_equal_table;
            if (!empty($joined_table_alias)) {
                $join_qry .= $joined_table_alias . ' ';
            }
            $join_qry .= ' ON ';
            if (!empty($joined_table_alias) && $left_equal_table === $joined_table) {
                $join_qry .= $joined_table_alias;
            } else {
                $join_qry .= $left_equal_table;
            }
            $join_qry .= '.' . $left_equal_field .  ' = ';
            if (!empty($joined_table_alias) && $right_equal_field === $joined_table) {
                $join_qry .= $joined_table_alias;
            } else {
                $join_qry .= $right_equal_table;
            }
            $join_qry .= '.' . $right_equal_field;
        } else {
            $join_qry = ' ' . $join_type . ' JOIN ';
            if (!empty($joined_table_alias) && $right_equal_table === $joined_table_alias) {
                $join_qry .= $joined_table . ' ' . $joined_table_alias;
            } else {
                $join_qry .= $right_equal_table;
            }
            $join_qry .= ' ON ';
            if (!empty($joined_table_alias) && $right_equal_table === $joined_table) {
                $join_qry .= $joined_table_alias;
            } else {
                $join_qry .= $right_equal_table;
            }
            $join_qry .= '.' . $right_equal_field .  ' = ';
            if (!empty($joined_table_alias) && $left_equal_field === $joined_table) {
                $join_qry .= $joined_table_alias;
            } else {
                $join_qry .= $left_equal_table;
            }
            $join_qry .= '.' . $left_equal_field;
        }

        return $join_qry;
    }

    private function getAliases($fields)
    {
        $fields_array = explode(',', $fields);
        $output = array();
        foreach ($fields_array as $field) {
            $trimmed = trim($field);
            if (preg_match('`^[a-zA-Z0-9._ \']+ AS ([a-zA-Z 0-9_]+)$`', $trimmed, $out)) {
                $output[] = $out[1];
            } else {
                $output[] = $trimmed;
            }
        }

        return implode(', ', $output);
    }

    /**
     * merge and order the join queries from the current filter and the active ones
     * @param array the current filter joins
     * @return sql the final JOIN query for the current filter
     */
    private function mergeJoinQueries($filter)
    {
        $log = array();
        $joins = array(
            'join_queries' => array(),
            'from_table'   => array()
        );
        $final_join_queries   = array();
        if (!empty($filter)) {
            $filter_join_queries_count = count($filter['join_queries']);
            for ($i = 0; $i < $filter_join_queries_count; $i++) {
                if (!empty($filter['join_queries'])) {
                    $joins['join_queries'][] = $filter['join_queries'][$i];
                    $joins['origin_table'][]   = $filter['from_table'];
                }
            }
        }
        foreach ($this->active_filters as $active_filter) {
            if (!empty($active_filter) && $active_filter['select_name'] != $filter['select_name']) {
                $active_filter_join_queries_count = count($active_filter['join_queries']);
                for ($i = 0; $i < $active_filter_join_queries_count; $i++) {
                    if (!empty($active_filter['join_queries'])) {
                        $joins['join_queries'][] = $active_filter['join_queries'][$i];
                        $joins['origin_table'][]   = $active_filter['from_table'];
                    }
                }
            }
        }

        // if the table joined is the filter table we have to invert the joined table
        // => $from_table is replaced with left equal table
        // ie: actor query with films filtered

        /* WRONG
        FROM actor
        INNER JOIN film_actor ON film_actor.film_id = film.film_id =>INNER JOIN film ON film.film_id = film_actor.film_id
        INNER JOIN actor ON film_actor.actor_id =actor.actor_id

        OK
        FROM actor
        INNER JOIN film_actor ON film_actor.actor_id =actor.actor_id
        INNER JOIN film ON film.film_id = film_actor.film_id
        */

        $join_type           = array();
        $joined_table        = array();
        $joined_table_alias  = array();
        $left_equal_table    = array();
        $left_equal_field    = array();
        $right_equal_table   = array();
        $right_equal_field   = array();

        $joins_count = count($joins['join_queries']);

        if ($this->debug === true) {
            $log[] = array(
                '<br><br>
                /* =============================================<br>
                    FILTER ' . $filter['from_table'] . '<br>
                ============================================= */<br>
                '
            );
        }
        for ($i = 0; $i < $joins_count; $i++) {
            if (preg_match('`(LEFT|INNER|RIGHT) JOIN ([a-zA-Z0-9_]+) ON ([a-zA-Z0-9_]+).([a-zA-Z0-9_]+)(?:[\s]*=[\s]*)([a-zA-Z0-9_]+).([a-zA-Z0-9_]+)`', $joins['join_queries'][$i], $out)) {
                $join_type[]         = $out[1];
                if (!isset($out[7])) {
                    // if no table alias
                    $joined_table[]       = $out[2];
                    $joined_table_alias[] = '';
                    $left_equal_table[]   = $out[3];
                    $left_equal_field[]   = $out[4];
                    $right_equal_table[]  = $out[5];
                    $right_equal_field[]  = $out[6];
                } else {
                    $joined_table[]       = $out[2];
                    $joined_table_alias[] = $out[3];
                    $left_equal_table[]   = $out[4];
                    $left_equal_field[]   = $out[5];
                    $right_equal_table[]  = $out[6];
                    $right_equal_field[]  = $out[7];
                }
            } else {
                // ERROR
                $log[] = array('Malformed JOIN query');
            }
        }

        // each left table MUST BE UNIQUE AND MUST NOT BE THE MAIN QUERY TABLE (ie $filter['from_table'])
        $used_left_tables   = array($filter['from_table']);
        $final_join_queries = array();
        $joins_count        = count($join_type);

        // 1st loop: register joins which use $filter['from_table']
        for ($i = 0; $i < $joins_count; $i++) {
            if ($left_equal_table[$i] == $filter['from_table'] || $right_equal_table[$i] == $filter['from_table']) {
                $case = 'SKIP';
                if ($right_equal_table[$i] == $filter['from_table'] && !in_array($left_equal_table[$i], $used_left_tables)) {
                    $invert_query = false;
                    $used_left_tables[] = $left_equal_table[$i];
                    $case = '[ORIGINAL JOIN] - ';
                } elseif ($left_equal_table[$i] == $filter['from_table'] && !in_array($right_equal_table[$i], $used_left_tables)) {
                    $invert_query = true;
                    $used_left_tables[] = $right_equal_table[$i];
                    $case = '[INVERTED JOIN] - ';
                }

                if ($case != 'SKIP') {
                    $new_join_query = $this->formatJoinQuery($join_type[$i], $joined_table[$i], $joined_table_alias[$i], $left_equal_table[$i], $left_equal_field[$i], $right_equal_table[$i], $right_equal_field[$i], $invert_query);

                    $final_join_queries[] = $new_join_query;

                    if ($this->debug === true) {
                        $log[] = array($case . $filter['from_table'] . '<br>' . $new_join_query . '<br>--------------<br>');
                    }
                }
            }
        }

        // 2nd loop: register joins which don't use $filter['from_table']
        for ($i = 0; $i < $joins_count; $i++) {
            if ($left_equal_table[$i] != $filter['from_table'] && $right_equal_table[$i] != $filter['from_table']) {
                $case = 'SKIP';
                if (!in_array($left_equal_table[$i], $used_left_tables)) {
                    $invert_query = false;
                    $used_left_tables[] = $left_equal_table[$i];
                    $case = '[ORIGINAL JOIN] - ';
                } elseif (!in_array($right_equal_table[$i], $used_left_tables)) {
                    $invert_query = true;
                    $used_left_tables[] = $right_equal_table[$i];
                    $case = '[INVERTED JOIN] - ';
                }

                if ($case != 'SKIP') {
                    $new_join_query = $this->formatJoinQuery($join_type[$i], $joined_table[$i], $joined_table_alias[$i], $left_equal_table[$i], $left_equal_field[$i], $right_equal_table[$i], $right_equal_field[$i], $invert_query);

                    $final_join_queries[] = $new_join_query;

                    if ($this->debug === true) {
                        $log[] = array($case . $filter['from_table'] . '<br>' . $new_join_query . '<br>--------------<br>');
                    }
                }
            }
        }

        $final_join_queries = array_filter(array_unique($final_join_queries));

        if ($this->debug === true) {
            if (!empty($final_join_queries)) {
                $log[] = array_merge(array('FINAL JOIN QUERIES:'), $final_join_queries);
            } else {
                $log[] = array('EMPTY JOIN QUERY');
            }

            if (!empty($log)) {
                $content = array();
                foreach ($log as $array) {
                    $content[] = implode('<br>', $array);
                }
                $this->userMessage('<span class="badge badge-primary me-2">LOG OUTPUT</span>mergeJoinQueries()', 'panel-default m-5', 'close', implode('<br><br>', $content));
            }
        }

        return implode(' ', $final_join_queries);
    }

    /**
     * used in generator to test filter query
     * @return string     sql query
     */
    public function showQuery()
    {
        $filter      = $this->filters[0];
        $select_name = $filter['select_name'];
        $qry_where   = $this->getWhere($select_name);
        $qry         = 'SELECT DISTINCT ' . $filter['fields'] . ' FROM ' . $filter['from'] . $qry_where;

        return $qry;
    }

    /**
     * Register filtered elements in session
     * @param  string $list name of the list to filter (ex : clients)
     * @return void
     */
    public static function register($list)
    {
        if (isset($_SESSION['filters-list'])) {
            if (isset($_POST['cancel_filters'])) {
                foreach ($_SESSION['filters-list'] as $key => $value) {
                    if (preg_match('`' . $list . '_filter_`', $key)) {
                        unset($_SESSION['filters-list'][$key]);
                    }
                }
            } else {
                foreach ($_POST as $key => $value) {
                    if (preg_match('`_filter_`', $key)) {
                        $_SESSION['filters-list'][$key] = $value;
                    }
                }
            }
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
        if (!empty($content)) {
            // panel
            echo Utils::alertCard($title, $classname, '', $heading_elements, $content);
        } else {
            // alert
            echo Utils::alert($title, $classname);
        }
    }
}
