<?php
use phpformbuilder\database\DB;
use common\Utils;

include_once '../../conf/conf.php';
preg_match('`([a-zA-Z0-9_]+):([a-zA-Z0-9_-]+):([a-zA-Z0-9_-]+)=([0-9]+)(:([a-zA-Z0-9_]+):([a-zA-Z0-9_]+):([a-zA-Z0-9_%]+))?`', $_POST['id'], $out);
$table = $out[1];
$champ = $out[2];
$pk_name = $out[3];
$pk_value = $out[4];
$relation_table = '';
$relation_pk    = '';
$relation_fields = '';
if (isset($out[5])) {
    $relation_table = $out[6];
    $relation_pk    = $out[7];
    $relation_fields = $out[8];
}
if (isset($_POST['value_submit'])) { // pickadate
    $new_value = $_POST['value_submit'];
} else {
    $new_value = $_POST['value'];
}
$display = $_POST['value'];

if (DEMO !== true) {
    $db = new DB(DEBUG);
    $db->setDebugMode('register');

    $values = array(
        $champ => $new_value
    );

    $where = array(
        $pk_name => $pk_value
    );

    if ($db->update($table, $values, $where, DEBUG_DB_QUERIES)) {
        // success
        if (empty($relation_table)) {
            echo $display;
        } else {
            $from = $relation_table;
            $relation_fields = explode('%', $relation_fields);
            $columns = $relation_fields;
            $where = array($relation_pk => $new_value);

            if ($row = $db->selectRow($from, $columns, $where)) {
                $results = array();
                foreach ($relation_fields as $f) {
                    $results[] = $row->$f;
                }
                echo implode(' ', $results);
            } else {
                echo $display;
            }
        }
    } else {
        echo QUERY_FAILED;
    }
    if (DEBUG_DB_QUERIES) {
        ?>
    <script>
        $('#debug-ajax-content').css('opacity', '0').html('<?php echo addslashes(str_replace(array("\r", "\n"), "<br>", $db->getDebugContent())); ?>').animate({'opacity': '1'}, {duration: 600});
    </script>
        <?php
    }
} else {
    echo addslashes($display);
    ?>
    <script>alert('live edit disabled in demo');</script>
    <?php
}
