<?php
use crud\ElementsFilters;
use phpformbuilder\database\DB;

include_once '../../conf/conf.php';
include_once '../class/generator/Generator.php';

@session_start();
if (isset($_SESSION['generator'])) {
    include_once ADMIN_DIR . 'secure/class/secure/Secure.php';

    $generator     = $_SESSION['generator'];
    $index         = $_POST['index'];
    $filter_parsed = $generator->parseQuery($_POST['from']);
    $from_table    = $filter_parsed['from_table'];
    $join_tables   = $filter_parsed['join_tables'];
    $join_queries  = $filter_parsed['join_queries'];
    $filters = array(
        'filter_mode'     => $_POST['filter_mode'],
        'filter_A'        => $_POST['filter_A'],
        'select_label'    => $_POST['select_label'],
        'select_name'     => $index,
        'option_text'     => $_POST['option_text'],
        'fields'          => $_POST['fields'],
        'field_to_filter' => $_POST['field_to_filter'],
        'from'            => $_POST['from'],
        'from_table'      => $from_table,
        'join_tables'     => $join_tables,
        'join_queries'    => $join_queries,
        'type'            => $_POST['type']
    );
    $filters_array = array($filters);
    $filters   = new ElementsFilters($from_table, $filters_array);
    $filters->register($from_table);
    $qry = '';

    $filters_form = $filters->returnForm('#', false);
    if (!$filters->hasError()) {
        $header_class = 'text-bg-success-300';
        $status = STATUS . ': <span class="badge text-bg-success">' . SUCCESS . '</span>' . "\n";
    } else {
        $header_class = 'text-bg-danger-300';
        $status = STATUS . ': <span class="badge text-bg-danger">' . ERROR . '</span>' . "\n";
    }
    $out = '';
    $out .= '<h4>' . QUERY . ': </h4>' . "\n";
    $out .= '<pre><code>' . $filters->getLastSql() . '</code></pre>' . "\n";
    $out .= '<hr>' . "\n";
    $out .= '<h4>' . GENERATED_FILTER . ': </h4>' . "\n";
    $out .= $filters_form;
}
?>
<div class="modal-header <?php echo $header_class; ?>">
    <h1 class="modal-title fs-5"><?php echo $status; ?></h1>
    <button type="button" class="btn-close modal-close" aria-label="Close"></button>
</div>
<div class="modal-body">
    <?php
    echo $out;
    ?>
</div>