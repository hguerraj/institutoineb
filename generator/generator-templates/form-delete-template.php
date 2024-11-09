<?php
// phpcs:disable Generic.WhiteSpace.ScopeIndent
use generator\TemplatesUtilities;
use crud\ElementsUtilities;

include_once GENERATOR_DIR . 'class/generator/TemplatesUtilities.php';
include_once ADMIN_DIR . 'class/crud/ElementsUtilities.php';

$generator = $_SESSION['generator'];
$form_id = 'form-delete-' . str_replace('_', '-', $generator->table);
$radio_fieldname = 'delete-' . str_replace('_', '-', $generator->table);
echo '<?php' . "\n";
?>
use phpformbuilder\Form;
use phpformbuilder\Validator\Validator;
use phpformbuilder\database\DB;
use common\Utils;

$debug_content = '';

// get referer pagination
$page_url_qry = '';
if (isset($_SESSION['<?php echo $generator->table; ?>-page']) && is_numeric($_SESSION['<?php echo $generator->table; ?>-page'])) {
    $page_url_qry = '/p' . $_SESSION['<?php echo $generator->table; ?>-page'];
}

/* =============================================
delete if posted
============================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('<?php echo $form_id; ?>') === true) {
    $validator = Form::validate('<?php echo $form_id; ?>', FORMVALIDATION_PHP_LANG);

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['<?php echo $form_id; ?>'] = $validator->getAllErrors();
    } else {
        if ($_POST['<?php echo $radio_fieldname; ?>'] > 0) {
            $db = new DB(DEBUG);
            $db->setDebugMode('register');
            try {
                // begin transaction
                $db->transactionBegin();
<?php

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
                $columns = array('<?php echo $from_to['origin_table']; ?>.<?php echo $from_to['origin_column']; ?>');
                $where = $_SESSION['<?php echo $generator->table; ?>_editable_primary_keys'];

                $db->select($from, $columns, $where, array(), DEBUG_DB_QUERIES);

                if (DEBUG_DB_QUERIES) {
                    $debug_content = $db->getDebugContent();
                }

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
                $from = '<?php echo $from_to['intermediate_table']; ?> LEFT JOIN <?php echo $from_to['target_table']; ?> ON <?php echo $from_to['intermediate_table']; ?>.<?php echo $from_to['intermediate_column_2']; ?> = <?php echo $from_to['target_table']; ?>.<?php echo $from_to['target_column']; ?>';
                $where = $_SESSION['<?php echo $generator->table; ?>_editable_primary_keys'];

                $db->delete($from, $where, DEBUG_DB_QUERIES);

                if (DEBUG_DB_QUERIES) {
                    $debug_content = $db->getDebugContent();
                }

                // Delete from origin
<?php
                    if ($from_to['cascade_delete_from_origin'] > 0 && !in_array($from_to['origin_table'], $done_tables)) {
                        $origin_filtered_column = $from_to['origin_table'] . '.' . $from_to['origin_column'];
?>
                foreach ($<?php echo $from_to['origin_table']; ?>_records as $value) {
                    $where = array('<?php echo $origin_filtered_column; ?>' => $value);
                    $db->delete('<?php echo $from_to['origin_table']; ?>', $where, DEBUG_DB_QUERIES);

                    if (DEBUG_DB_QUERIES) {
                        $debug_content = $db->getDebugContent();
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
                $from = '<?php echo $from_to['origin_table']; ?> LEFT JOIN <?php echo $from_to['target_table']; ?> ON <?php echo $from_to['origin_table']; ?>.<?php echo $from_to['origin_column']; ?> = <?php echo $from_to['target_table']; ?>.<?php echo $from_to['target_column']; ?>';
                $where = $_SESSION['<?php echo $generator->table; ?>_editable_primary_keys'];

                $db->delete($from, $where, DEBUG_DB_QUERIES);

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
                $where = $_SESSION['<?php echo $generator->table; ?>_editable_primary_keys'];
                if (DEMO === true || $db->delete('<?php echo $generator->table; ?>', $where, DEBUG_DB_QUERIES)) {
                    // ALL OK
                    if (!DEBUG_DB_QUERIES) {
                        $db->transactionCommit();
                        $_SESSION['msg'] = Utils::alert(DELETE_SUCCESS_MESSAGE, 'alert-success has-icon');

                        // reset form values
                        Form::clear('<?php echo $form_id; ?>');

                        // unset the search string
                        if (isset($_SESSION['rp_search_string']['<?php echo $generator->table; ?>'])) {
                            unset($_SESSION['rp_search_string']['<?php echo $generator->table; ?>']);
                        }

                        // redirect to list page
                        if (isset($_SESSION['active_list_url'])) {
                            header('Location:' . $_SESSION['active_list_url']);
                        } else {
                            header('Location:' . ADMIN_URL . '<?php echo $generator->item; ?>');
                        }

                        // if we don't exit here, $_SESSION['msg'] will be unset
                        exit();
                    } else {
                        $debug_content = $db->getDebugContent();
                        $db->transactionRollback();

                        $_SESSION['msg'] = Utils::alert(DELETE_SUCCESS_MESSAGE . '<br>(' . DEBUG_DB_QUERIES_ENABLED . ')', 'alert-success has-icon');
                    }
                } else {
                    throw new \Exception($db->error());
                }
            } catch (\Exception $e) {
                if (DEBUG_DB_QUERIES) {
                    $debug_content = $db->getDebugContent();
                }
                $msg_content = DB_ERROR;
                if (ENVIRONMENT == 'development') {
                    $msg_content .= '<br>' . $e->getMessage() . '<br>' . $db->getLastSql();
                }
                $_SESSION['msg'] = Utils::alert($msg_content, 'alert-danger has-icon');
            }
        }
    }
} // END if POST

// register editable primary keys, which are NOT posted and will be the query delete filter
// $params come from data-forms.php
// replace 'fieldname' with 'table.fieldname' to avoid ambigous query
$where_params = array_combine(
    array_map(function ($k) {
        return '<?php echo $generator->table; ?>.' . $k;
    }, array_keys($params)),
    $params
);
$_SESSION['<?php echo $generator->table; ?>_editable_primary_keys'] = $where_params;

<?php

/* =============================================
form Delete
============================================= */

?>

if (!isset($db)) {
    $db = new DB(DEBUG);
    $db->setDebugMode('register');
}

// select name to display for confirmation
<?php
$select_fields = array();
$select_fields[] = $generator->field_delete_confirm_1;
if (!empty($generator->field_delete_confirm_2)) {
    $select_fields[] = $generator->field_delete_confirm_2;
}
?>
$from = '<?php echo $generator->table ?>';
$columns = array('<?php echo implode('\', \'', $select_fields); ?>');
$where = $_SESSION['<?php echo $generator->table; ?>_editable_primary_keys'];
$extras = array('limit' => 1);

$db->select($from, $columns, $where, $extras, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

$count = $db->rowCount();

if ($count > 0) {
    $row = $db->fetch();
    $display_value = $row-><?php echo $generator->field_delete_confirm_1 ?>;
<?php if (!empty($generator->field_delete_confirm_2)) { ?>
    $display_value .= ' ' . $row-><?php echo $generator->field_delete_confirm_2 ?>;
<?php } ?>
} else {
    // this should never happen
    // echo $db->getLastSql();
    header("X-Robots-Tag: noindex", true);
    exit('QRY ERROR');
}

$form = new Form('<?php echo $form_id; ?>', 'vertical', 'novalidate');

// $params come from data-forms.php
$pk_url_params = http_build_query($params, '', '/');

$form->setAction(ADMIN_URL . '<?php echo $generator->item ?>/delete/' . $pk_url_params);
$form->startFieldset();
<?php
$matching_records_tables = array();
if (isset($generator->relations['from_to'])) {
    foreach ($generator->relations['from_to'] as $from_to) {
        if ($from_to['target_table'] == $generator->table) {
            if ($from_to['cascade_delete_from_intermediate'] > 0 && !empty($from_to['intermediate_table']) && !in_array($from_to['intermediate_table'], $matching_records_tables)) {
                $record_count_alias = 'record_count';
                if (PDO_DRIVER === 'firebird' || PDO_DRIVER === 'oci') {
                    $record_count_alias = 'RECORD_COUNT';
                }
?>
// Get records count from intermediate table
$<?php echo $from_to['intermediate_table']; ?>_record_count = 0;

$from = '<?php echo $from_to['intermediate_table']; ?> LEFT JOIN <?php echo $from_to['target_table']; ?> ON <?php echo $from_to['intermediate_table']; ?>.<?php echo $from_to['intermediate_column_2']; ?> = <?php echo $from_to['target_table']; ?>.<?php echo $from_to['target_column']; ?>';
$columns = array('<?php echo $from_to['intermediate_table']; ?>.<?php echo $from_to['intermediate_column_2']; ?>' => '<?php echo $record_count_alias; ?>');
$where = $_SESSION['<?php echo $generator->table; ?>_editable_primary_keys'];

$row = $db->selectCount($from, $columns, $where, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

if (!empty($row)) {
    $<?php echo $from_to['intermediate_table']; ?>_record_count = $row-><?php echo $record_count_alias; ?>;
}
<?php
$matching_records_tables[] = $from_to['intermediate_table'];
?>

// intermediate table
$form->addInput('hidden', '<?php echo 'constrained_tables_' . $from_to['intermediate_table']; ?>', true);
<?php
            }
            if ($from_to['cascade_delete_from_origin'] > 0 && !in_array($from_to['origin_table'], $matching_records_tables)) {
                $record_count_alias = 'record_count';
                if (PDO_DRIVER === 'firebird' || PDO_DRIVER === 'oci') {
                    $record_count_alias = 'RECORD_COUNT';
                }
?>

// Get the records count from origin table
$<?php echo $from_to['origin_table']; ?>_record_count = 0;

$from = '<?php echo $from_to['origin_table']; ?> LEFT JOIN <?php echo $from_to['target_table']; ?> ON <?php echo $from_to['origin_table']; ?>.<?php echo $from_to['origin_column']; ?> = <?php echo $from_to['target_table']; ?>.<?php echo $from_to['target_column']; ?>';
$columns = array('<?php echo $from_to['origin_table']; ?>.<?php echo $from_to['origin_column']; ?>' => '<?php echo $record_count_alias; ?>');
$where = $_SESSION['<?php echo $generator->table; ?>_editable_primary_keys'];

$row = $db->selectCount($from, $columns, $where, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content = $db->getDebugContent();
}

if (!empty($row)) {
    $<?php echo $from_to['origin_table']; ?>_record_count = $row-><?php echo $record_count_alias; ?>;
}

<?php
                $matching_records_tables[] = $from_to['origin_table'];
?>

// origin table
$form->addInput('hidden', '<?php echo 'constrained_tables_' . $from_to['origin_table']; ?>', true);
<?php
            }
        }
    }
}
?>
$form->addHtml('<div class="text-center p-md">');
$form->addRadio('<?php echo $radio_fieldname; ?>', NO, 0);
$form->addRadio('<?php echo $radio_fieldname; ?>', YES, 1);
$form->printRadioGroup('<?php echo $radio_fieldname; ?>', '<span class="me-20">' . DELETE_CONST . ' "' . $display_value . '" ?</span>', true, 'required');
<?php
    if (!empty($matching_records_tables)) {
?>
$tables_records_html = '';
<?php
        for ($i = 0; $i < count($matching_records_tables); $i++) {
?>
$tables_records_html .= '<span class="badge text-bg-warning prepend"><?php echo $matching_records_tables[$i] ?> (' . $<?php echo $matching_records_tables[$i]; ?>_record_count . ' ' . RECORDS . ')</span>';
<?php
        }
?>
$form->addHtml(Utils::alert('<p class="text-semibold">' . MATCHING_RECORDS_WILL_BE_DELETED . ':</p><p>' . $tables_records_html . '</p>', 'alert-warning has-icon'));
<?php
    }
?>
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->addHtml('
</div>');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#<?php echo $form_id; ?>');
$form->addPlugin('formvalidation', '#<?php echo $form_id; ?>', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
