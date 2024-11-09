<?php

use secure\Secure;
use crud\ElementsUtilities;
use export\ExportData;
use export\ExportDataExcel;
use export\ExportDataCSV;
use export\ExportDataTSV;
use phpformbuilder\database\DB;
use phpformbuilder\database\Pagination;

header("X-Robots-Tag: noindex", true);

session_start();
include_once '../../conf/conf.php';
include_once ADMIN_DIR . 'secure/class/secure/Secure.php';
include_once ADMIN_DIR . 'class/export/ExportData.php';

// lock page
Secure::lock();

if (!isset($_GET['table']) || !isset($_GET['npp']) || !is_numeric($_GET['npp']) || (isset($_GET['p']) && !is_numeric($_GET['p']))) {
    exit('error 1');
}
$npp = $_GET['npp'];
if ($_GET['npp'] < 0) {
    $npp = 100000;
}
$table = addslashes($_GET['table']);
if (!isset($_SESSION['export'][$table]['pdo_settings'])) {
    exit('error 2');
}

// get the item name
$upperCamelCaseTable = ElementsUtilities::upperCamelCase($table);
$item = mb_strtolower($upperCamelCaseTable);

$db = new Pagination();
$columns = $db->getColumnsNames($table);

// get the foreign fields displayed values (a foreign field can display 2 combined values from the relational table)
$select_data = json_decode(file_get_contents('../crud-data/' . $item . '-select-data.json'));
$select_data_tables = array();
foreach ($columns as $column_name) {
    // default
    $outputRows[$column_name] = array(
        'field_1'    => $column_name,
        'field_2'    => ''
    );
    // if 2 combined values
    $column_select_data = $select_data->$column_name;
    if ($column_select_data->from === 'from_table' && strpos($_SESSION['export'][$table]['pdo_settings']['from'], $column_select_data->from_table) !== false) {
        $outputRows[$column_name] = array(
            'field_1'    => $column_select_data->from_field_1,
            'field_2'    => $column_select_data->from_field_2
        );
        if (!in_array($column_select_data->from_table, $select_data_tables)) {
            $select_data_tables[] = $column_select_data->from_table;
        }
    }
}
/* Example:
$_SESSION['export'][$table]['pdo_settings'] = array(
    'function' => 'select',
    'from'    => 'actor' . $active_filters_join_queries,
    'values'   => $columns,
    'where'    => $where,
    'extras'   => array('order_by' => $this->sorting),
    'debug'    => DEBUG_DB_QUERIES
);
*/

$pdo_settings_keys = array('function', 'from', 'values', 'where', 'extras', 'debug');

foreach ($pdo_settings_keys as $key) {
    if (!array_key_exists($key, $_SESSION['export'][$table]['pdo_settings'])) {
        exit('Missing key <em>' . $key . '</em> in $pdo_settings_keys(admin/inc/export-data.php)');
    }
}

$table_alias = array();

if ($element_joins_count = preg_match_all('`(LEFT|INNER|RIGHT) JOIN ([a-zA-Z0-9_]+)\s?([a-zA-Z0-9_]+)? ON ([a-zA-Z0-9_]+).([a-zA-Z0-9_]+)(?:[\s]*=[\s]*)([a-zA-Z0-9_]+).([a-zA-Z0-9_]+)`', $_SESSION['export'][$table]['pdo_settings']['from'], $out)) {
    for ($i = 0; $i < $element_joins_count; $i++) {
        if (!empty($out[3][$i])) {
            // if table alias
            $original_field_name = $out[7][$i];
            $field_alias = $out[4][$i] . '_' . $out[5][$i];
            $table_alias[$original_field_name] = $out[3][$i];
            // 'original_language_id' => string 't1'
        }
    }
}

// get the columns names from the SELECT query and replace aliases
$fields_query = $_SESSION['export'][$table]['pdo_settings']['values'];
$fields_array = explode(',', $fields_query);
if (empty($fields_array)) {
    exit('failed to parse the query (1)');
}

if (!is_null($_SESSION['export'][$table]['pdo_settings']['values'])) {
    $values_array = explode(', ', $_SESSION['export'][$table]['pdo_settings']['values']);
    foreach ($values_array as $field_query) {
        // e.g.:t1.language_id AS t1_language_id
        if (preg_match('/([^.]+)\.([^\s]+)( AS ([a-zA-Z0-9_-]+))?/', trim($field_query), $out)) {
            if (!isset($out[2])) {
                exit('failed to parse the query (1) - ' . $field_query);
            }
            if (isset($out[4])) {
                foreach ($outputRows as $cname => $fields) {
                    if (isset($table_alias[$cname])) {
                        if ($fields['field_1'] === $out[2]) {
                            $outputRows[$cname]['field_1'] = ElementsUtilities::shortenAlias($table_alias[$cname] . '_' . $outputRows[$cname]['field_1']);
                        }
                        if ($fields['field_2'] === $out[2]) {
                            $outputRows[$cname]['field_2'] = ElementsUtilities::shortenAlias($table_alias[$cname] . '_' . $outputRows[$cname]['field_2']);
                        }
                    } else {
                        if ($fields['field_1'] === $out[2]) {
                            $outputRows[$cname]['field_1'] = ElementsUtilities::shortenAlias($out[1] . '_' . $outputRows[$cname]['field_1']);
                        } elseif ($fields['field_2'] === $out[2]) {
                            $outputRows[$cname]['field_2'] = ElementsUtilities::shortenAlias($out[1] . '_' . $outputRows[$cname]['field_2']);
                        } elseif ($fields['field_1'] === $out[1] . '.' . $out[2]) {
                            // if we're in a secondary relation. e.g: store => address.postal_code
                            $outputRows[$cname]['field_1'] = $out[4];
                        } elseif ($fields['field_2'] === $out[1] . '.' . $out[2]) {
                            $outputRows[$cname]['field_2'] = $out[4];
                        }
                    }
                }
            }
        } else {
            exit('failed to parse the query (2) - ' . $field_query);
        }
    }
}

$db->pagine($_SESSION['export'][$table]['pdo_settings'], $npp, 'p', '', 1, false, '/', '');
$nbre = $db->rowCount();

// var_dump($_SESSION['export'][$table]['pdo_settings']);
// var_dump($outputRows);
// var_dump($columns);

if (!empty($nbre)) {
    $output = array(
        'thead' => $columns,
        'rows'  => array()
    );
    $i = 0;
    while ($row = $db->fetch()) {
        foreach ($columns as $column_name) {
            $fieldname = $outputRows[$column_name]['field_1'];
            $out = $row->$fieldname;
            if (!empty($outputRows[$column_name]['field_2'])) {
                $fieldname_2 = $outputRows[$column_name]['field_2'];
                $out .= ' ' . $row->$fieldname_2;
            }
            $output['rows'][$i][] = $out;
        }
        $i++;
    }
    $thead = '<thead><tr><th>' . implode('</th><th>', $output['thead']) . '</th></tr></thead>';
    $tbody = '<tbody>' . array_reduce($output['rows'], function ($a, $b) {
        return $a .= '<tr><td>' . implode('</td><td>', $b) . '</td></tr>';
    }) . '</tbody>';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex" />
    <title><?php echo ucfirst($table) . ' - ' . date('Y-m-d'); ?></title>
    <meta name="description" content="">
    <meta name="author" content="Gilles Migliori">
    <link rel="icon" href="/favicon.ico">

    <!-- https://datatables.net/download/ -->
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/jq-3.6.0/jszip-2.5.0/dt-1.12.1/b-2.2.3/b-colvis-2.2.3/b-html5-2.2.3/b-print-2.2.3/datatables.min.css" />

    <style> body{ margin: 0; padding: 0 5vw; font-size: 12pt;} @media only screen{ *{ box-sizing: border-box;} html, body{ width: 100vw; height: 100vh;} body{ margin: 0; padding: 0 5vw; font-size: 12px;} th{ text-align: left;} table td .dt-buttons{ display: none;} .dt-buttons{ padding: 2rem; text-align: center;} .btn{ border-radius: 0 !important;}} </style>
</head>

<body>
    <?php echo '<table id="export-table" class="display compact" style="width:100%">' . $thead . $tbody . '</table>'; ?>
    <!-- https://datatables.net/download/ -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/v/bs5/jq-3.6.0/jszip-2.5.0/dt-1.12.1/b-2.2.3/b-colvis-2.2.3/b-html5-2.2.3/b-print-2.2.3/datatables.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#export-table').DataTable({
                dom: 'Bfrtip',
                paging: false,
                ordering: false,
                searching: false,
                info: false,
                scrollX: '90vw',
                scrollY: 'calc(90vh - 110px)',
                buttons: [{
                    extend: 'excel',
                    filename: '<?php echo $table . '-export-' . date('Y-m-d'); ?>',
                    className: 'btn-success'
                }, 'spacer', {
                    extend: 'csv',
                    filename: '<?php echo $table . '-export-' . date('Y-m-d'); ?>',
                    className: 'btn-warning'
                }, 'spacer', {
                    extend: 'pdf',
                    filename: '<?php echo $table . '-export-' . date('Y-m-d'); ?>',
                    className: 'btn-danger'
                }, 'spacer', {
                    extend: 'print',
                    className: 'btn-info',
                    exportOptions: {
                        stripHtml: false
                    }
                }],
                createdRow: function(row) {
                    $(row).find('td table')
                        .DataTable({
                            dom: 'Bfrtip',
                            paging: false,
                            ordering: false,
                            searching: false,
                            buttons: []
                        })
                }
            });
        });
    </script>
</body>

</html>
