<?php

use secure\Secure;
use crud\Elements;

header("X-Robots-Tag: noindex", true);

session_start();
include_once '../conf/conf.php';
include_once ADMIN_DIR . 'secure/class/secure/Secure.php';

// $item = lowercase compact table name
$item = $match['params']['item'];

$params = array();
if ($match['name'] === 'data-print-view') {
    $pk_fieldname = $match['params']['pk_fieldname'];
    $pk_value = $match['params']['pk_value'];
    $params[$pk_fieldname] = $pk_value;
    $export_title_value = $pk_value;
} elseif ($match['name'] === 'data-print-view-2-primary-keys') {
    $pk_fieldname_1 = $match['params']['pk_fieldname_1'];
    $pk_fieldname_2 = $match['params']['pk_fieldname_2'];
    $pk_value_1 = $match['params']['pk_value_1'];
    $pk_value_2 = $match['params']['pk_value_2'];
    $params[$pk_fieldname_1] = $pk_value_1;
    $params[$pk_fieldname_2] = $pk_value_2;
    $export_title_value = $pk_value_1 . '-' . $pk_value_2;
} else {
    exit('Primary key value not found');
}

include_once ADMIN_DIR . 'class/crud/Elements.php';
$element   = new Elements($item);
$table     = $element->table;

// lock page
// user must have [restricted|all] READ rights on $table
Secure::lock($table, 'restricted');

$item_class                = $element->item_class;
$item_class_with_namespace = $element->item_class_with_namespace;
// ElementsFilters::register($table);

// create the item object
include_once ADMIN_DIR . 'class/crud/' . $item_class . '.php';
$object = new $item_class_with_namespace($element, $params);

// twig loader & templates
require_once ROOT . 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig   = new \Twig\Environment($loader, array(
    'debug' => DEBUG,
));
include_once ROOT . 'vendor/twig/twig/src/Extension/CrudTwigExtension.php';
$twig->addExtension(new \Twig\Extension\CrudTwigExtension());
if (ENVIRONMENT == 'development') {
    $twig->addExtension(new \Twig\Extension\DebugExtension());
    $twig->enableDebug();
}
$template = $twig->load('single-record-views/' . $item . '.html');
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex" />
    <meta name="description" content="">
    <meta name="author" content="Gilles Migliori">
    <link rel="icon" href="/favicon.ico">

    <!-- https://datatables.net/download/ -->
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/jq-3.6.0/jszip-2.5.0/dt-1.12.1/b-2.2.3/b-colvis-2.2.3/b-html5-2.2.3/b-print-2.2.3/datatables.min.css" />

    <style>
        body {
            margin: 0;
            padding: 0 5vw;
            font-size: 12pt;
        }

        @media only screen {
            * {
                box-sizing: border-box;
            }

            html,
            body {
                width: 100vw;
                height: 100vh;
            }

            body {
                margin: 0;
                padding: 0 5vw;
                font-size: 12px;
            }

            th {
                text-align: left;
            }

            table td .dt-buttons {
                display: none;
            }

            .dt-buttons {
                padding: 2rem;
                text-align: center;
            }

            .btn {
                border-radius: 0 !important;
            }
        }
    </style>
</head>

<body>
    <?php echo $template->renderBlock('print_view', array('object' => $object, 'session' => $_SESSION)); ?>

    <!-- https://datatables.net/download/ -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/v/bs5/jq-3.6.0/jszip-2.5.0/dt-1.12.1/b-2.2.3/b-colvis-2.2.3/b-html5-2.2.3/b-print-2.2.3/datatables.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#single-record-export-table').DataTable({
                dom: 'Bfrtip',
                paging: false,
                ordering: false,
                searching: false,
                info: false,
                scrollX: '90vw',
                scrollY: 'calc(90vh - 110px)',
                buttons: [{
                    extend: 'excel',
                    filename: '<?php echo $table . '-' . $export_title_value . '-export-' . date('Y-m-d'); ?>',
                    className: 'btn-success'
                }, 'spacer', {
                    extend: 'csv',
                    filename: '<?php echo $table . '-' . $export_title_value . '-export-' . date('Y-m-d'); ?>',
                    className: 'btn-warning'
                }, 'spacer', {
                    extend: 'pdf',
                    filename: '<?php echo $table . '-' . $export_title_value . '-export-' . date('Y-m-d'); ?>',
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
