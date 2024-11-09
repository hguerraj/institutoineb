<?php
// phpcs:disable Generic.WhiteSpace.ScopeIndent
use generator\TemplatesUtilities;
use crud\ElementsUtilities;

include_once GENERATOR_DIR . 'class/generator/TemplatesUtilities.php';
include_once ADMIN_DIR . 'class/crud/ElementsUtilities.php';

$generator = $_SESSION['generator'];
echo '<?php' . "\n";

$has_multiple_primary_keys = false;
if (count($generator->primary_keys) > 1) {
    $has_multiple_primary_keys = true;
}
?>
use secure\Secure;
use phpformbuilder\database\DB;
use common\Utils;

header("X-Robots-Tag: noindex", true);

include_once '../../../conf/conf.php';
include_once ADMIN_DIR . 'secure/class/secure/Secure.php';

session_start();

// user must have [restricted|all] CREATE/DELETE rights on $table
if ((Secure::canCreate('<?php echo $generator->table; ?>') || Secure::canCreateRestricted('<?php echo $generator->table; ?>')) && $_SERVER["REQUEST_METHOD"] == "POST") {
    /* =============================================
        delete if posted
    ============================================= */

    if (isset($_POST['records']) && is_array($_POST['records'])) {
        $db = new DB(DEBUG);
        $db->throwExceptions = true;
        try {
            // begin transaction
            $db->transactionBegin();
            foreach ($_POST['records'] as $record) {
<?php
if (!$has_multiple_primary_keys) {
?>
                $key_value = explode('=', $record);
                $record_pk_value = urldecode($key_value[1]);
<?php
} else {
?>
                $keys_values = explode('/', $record);
                $record_pks = array();
                foreach ($keys_values as $kv) {
                    $key_value = explode('=', $kv);
                    $pk_field = '<?php echo $generator->table ?>.' . $key_value[0];
                    $pk_value = urldecode($key_value[1]);
                    $record_pks[$pk_field] = $pk_value;
                }
<?php
}
?>
<?php
    // $_POST['records'] examples: 'actor_id=1' or 'actor_id=1/film_id=140' if multiple primary keys
    /* constrained_from_to_relations:
        array(
            'origin_table'
            'origin_column'
            'intermediate_table'
            'intermediate_column_1' // refers to origin_table
            'intermediate_column_2' // refers to target_table
            'target_table'
            'target_column',
            'cascade_delete_from_intermediate' // true will automatically delete all matching records according to foreign keys constraints. Default: true
            'cascade_delete_from_origin' // true will automatically delete all matching records according to foreign keys constraints. Default: true
        )*/

    // Cascade delete - automatically delete all matching records according to foreign keys constraints (true|false)
    //
    // Current table is always the target.
    //
    // If External relation with intermediate table:
    //      origin_table ID <- [fk-origin + fk-target] -> target_table ID
    //      => We'll delete from [intermediate_table] THEN origin_table THEN target_table
    // else:
    //      fk-origin -> target_table ID
    //      => We'll delete from origin_table THEN target_table


/*
CAS 1

    target       = souscategories
    intermediate = articles
    origin       = categories

CAS 2

    target       = souscategories
    intermediate = null
    origin       = articles
*/

$done_tables = array();

if (isset($generator->relations['from_to'])) {
    foreach ($generator->relations['from_to'] as $from_to) {
        if ($from_to['target_table'] == $generator->table) {
            if (!empty($from_to['intermediate_table'])) {
                // Delete from intermediate
                if ($from_to['cascade_delete_from_intermediate'] > 0 && !empty($from_to['intermediate_table']) && !in_array($from_to['intermediate_table'], $done_tables)) {
                    if ($from_to['cascade_delete_from_origin'] > 0 && !in_array($from_to['origin_table'], $done_tables)) {
?>

                // Get records to delete from origin table before intermediate are deleted
                $<?php echo $from_to['origin_table']; ?>_records = array();

                $from = '<?php echo $from_to['origin_table']; ?>
                INNER JOIN <?php echo $from_to['intermediate_table']; ?> ON <?php echo $from_to['intermediate_table']; ?>.<?php echo $from_to['intermediate_column_1']; ?> =
                    <?php echo $from_to['origin_table']; ?>.<?php echo $from_to['origin_column']; ?>
                INNER JOIN <?php echo $from_to['target_table']; ?> ON <?php echo $from_to['intermediate_table']; ?>.<?php echo $from_to['intermediate_column_2']; ?> =
                    <?php echo $from_to['target_table']; ?>.<?php echo $from_to['target_column']; ?>';
                $columns = '<?php echo $from_to['origin_table']; ?>.<?php echo $from_to['origin_column']; ?>';
<?php
// $_POST['records'] examples: 'actor_id=1' or 'actor_id=1/film_id=140' if multiple primary keys
if (!$has_multiple_primary_keys) {
    ?>
                $where = array('<?php echo $from_to['target_table']; ?>.<?php echo $generator->primary_keys[0]; ?>' => $record_pk_value);
    <?php
} else {
?>
                $where = $record_pks;
<?php
}
?>

                $db->select($from, $columns, $where);
                $db_count = $db->rowCount();
                if (!empty($db_count)) {
                    while ($row = $db->fetch()) {
                        $<?php echo $from_to['origin_table']; ?>_records[] = $row-><?php echo $from_to['origin_column']; ?>;
                    }
                }
<?php
                    } // END if ($from_to['cascade_delete_from_origin'] > 0)
?>

                // Delete from intermediate
<?php
if (!$has_multiple_primary_keys) {
?>
                $from = '<?php echo $from_to['intermediate_table']; ?> LEFT JOIN <?php echo $from_to['target_table']; ?> ON <?php echo $from_to['intermediate_table']; ?>.<?php echo $from_to['intermediate_column_2']; ?> = <?php echo $from_to['target_table']; ?>.<?php echo $from_to['target_column']; ?>';
                $where = ['<?php echo $from_to['target_table']; ?>.<?php echo $generator->primary_keys[0]; ?>' => $record_pk_value];
<?php
} else {
?>
                $from = '<?php echo $from_to['intermediate_table']; ?> LEFT JOIN <?php echo $from_to['target_table']; ?> ON (<?php echo $from_to['intermediate_table']; ?>.<?php echo $from_to['intermediate_column_2']; ?> = <?php echo $from_to['target_table']; ?>.<?php echo $from_to['target_column']; ?>)';
                $where = $record_pks;
<?php
}
?>
                if (DEMO !== true) {
                    $db->delete($from, $where, DEBUG_DB_QUERIES);
                }

                if (DEBUG_DB_QUERIES) {
                    $debug_content = $db->getDebugContent();
                }

<?php

                    // Delete from origin
                    if ($from_to['cascade_delete_from_origin'] > 0 && !in_array($from_to['origin_table'], $done_tables)) {
                        $origin_filtered_column = $from_to['origin_table'] . '.' . $from_to['origin_column'];
?>
                foreach ($<?php echo $from_to['origin_table']; ?>_records as $value) {
                    $where  = array('<?php echo $origin_filtered_column; ?>' => $value);
                    if (!DEMO && !$db->delete('<?php echo $from_to['origin_table']; ?>', $where, DEBUG_DB_QUERIES)) {
                        throw new \Exception($db->error());
                    }
                }

<?php
                        $done_tables[] = $from_to['origin_table'];
                    }   // END if ($from_to['cascade_delete_from_origin'] > 0) {
                    $done_tables[] = $from_to['intermediate_table'];
                } // END if ($from_to['cascade_delete_from_intermediate'] > 0)
            } else { // If NO intermediate table
/* =============================================
    DELETE customers FROM customers LEFT JOIN orders  ON (customers.id = orders.customer_id) WHERE orders.customer_id IS NULL;
============================================= */

                // Delete from origin
                if ($from_to['cascade_delete_from_origin'] > 0 && !in_array($from_to['origin_table'], $done_tables)) {
                    $origin_filtered_column = $from_to['origin_column'];
                    $origin_filtered_column_value = $from_to['origin_table'] . '_' . $from_to['origin_column'];
?>

                // Delete from origin
<?php
if (!$has_multiple_primary_keys) {
?>
                $from = '<?php echo $from_to['origin_table']; ?> LEFT JOIN <?php echo $from_to['target_table']; ?> ON <?php echo $from_to['origin_table']; ?>.<?php echo $from_to['origin_column']; ?> = <?php echo $from_to['target_table']; ?>.<?php echo $from_to['target_column']; ?>';
                $where = array('<?php echo $from_to['target_table']; ?>.<?php echo $generator->primary_keys[0]; ?>' => $record_pk_value);
<?php
} else {
?>
                $from = '<?php echo $from_to['origin_table']; ?> LEFT JOIN <?php echo $from_to['target_table']; ?> ON (<?php echo $from_to['origin_table']; ?>.<?php echo $from_to['origin_column']; ?> = <?php echo $from_to['target_table']; ?>.<?php echo $from_to['target_column']; ?>)';
                $where = $record_pks;
<?php
}
?>
                if (DEMO !== true) {
                    $db->delete($from, $where, DEBUG_DB_QUERIES);
                }

                if (DEBUG_DB_QUERIES) {
                    $debug_content = $db->getDebugContent();
                }
<?php
                    $done_tables[] = $from_to['origin_table'];
                } // END if ($from_to['cascade_delete_from_origin'] > 0) {
            }
        }
    }
}
?>

                // Delete from target table
<?php
// $_POST['records'] examples: 'actor_id=1' or 'actor_id=1/film_id=140' if multiple primary keys
if (!$has_multiple_primary_keys) {
?>
                $where = array('<?php echo $generator->table; ?>.<?php echo $generator->primary_keys[0]; ?>' => $record_pk_value);
<?php
} else {
?>
                $where = $record_pks;
<?php
}
?>
                if (!DEMO && !$db->delete('<?php echo $generator->table; ?>', $where, DEBUG_DB_QUERIES)) {
                    throw new \Exception(FAILED_TO_DELETE);
                }
            } // end foreach
            // ALL OK
            // revert if DEBUG_DB_QUERIES is enabled
            $msg_debug = '';
            if (DEBUG_DB_QUERIES) {
                $db->transactionRollback();
                // gives an id to catch it with the Javascript callback
                $msg_debug .= '<br><strong id="debug-db-queries-is-enabled">' . DEBUG_DB_QUERIES_ENABLED . '</strong>';
            } else {
                $db->transactionCommit();
            }
            $msg = Utils::alert(count($_POST['records']) . BULK_DELETE_SUCCESS_MESSAGE . $msg_debug, 'alert-success has-icon');
        } catch (\Exception $e) {
            $db->transactionRollback();
            if (ENVIRONMENT == 'development' && !$db->show_errors) {
                $msg_content = DB_ERROR;
                if (ENVIRONMENT == 'development') {
                    $msg_content .= '<br>' . $e->getMessage() . '<br>' . $db->getLastSql();
                }
                $msg = Utils::alert($msg_content, 'alert-danger has-icon');
            }
        }
    } // END if (isset($_POST['records']))

    if (isset($msg)) {
        echo $msg;
    }
} // END if Secure
