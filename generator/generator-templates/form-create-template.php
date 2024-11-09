<?php
// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable Generic.WhiteSpace.ScopeIndent
use phpformbuilder\database\DB;
use phpformbuilder\database\pdodrivers\Mysql;
use phpformbuilder\database\pdodrivers\Pgsql;
use generator\TemplatesUtilities;
use common\Utils;
use crud\ElementsUtilities;

include_once GENERATOR_DIR . 'class/generator/TemplatesUtilities.php';
include_once ADMIN_DIR . 'class/crud/ElementsUtilities.php';

/* To debug:
call $generator->debug($var),
build the forms from the generator
then inspect the comments in the form code.
-------------------------------------------------- */

$generator = $_SESSION['generator'];
$form_id = 'form-create-' . str_replace('_', '-', $generator->table);
$has_fileuploader = false;
if (in_array('image', $generator->columns['field_type']) || in_array('file', $generator->columns['field_type'])) {
    $has_fileuploader = true;
}


/* External fields
-------------------------------------------------- */

/*
$generator->external_columns = array(
    'target_table'       => '',
    'target_fields'      => array(),
    'table_label'        => '',
    'fields_labels'      => array(),
    'relation'           => '',
    'allow_crud_in_list' => false,
    'allow_in_forms'     => true,
    'forms_fields'       => array(),
    'field_type'         => array(), // 'select-multiple' | 'checkboxes'
    'active'             => false
);

// relation = $generator->relations['from_to'][$i]
*/

$show_external = false;
$active_ext_cols = array();
foreach ($generator->external_columns as $key => $ext_col) {
    if ($ext_col['active'] === true && !empty($ext_col['relation']['intermediate_table']) && $ext_col['allow_in_forms'] === true) {
        $show_external = true;
        $active_ext_cols[] = $ext_col;
    }
}

echo '<?php' . "\n";
?>
use phpformbuilder\Form;
<?php if ($has_fileuploader === true) { ?>
use fileuploader\server\FileUploader;
<?php } ?>
use phpformbuilder\Validator\Validator;
use phpformbuilder\database\DB;
use common\Utils;
use secure\Secure;

include_once ADMIN_DIR . 'secure/class/secure/Secure.php';
<?php if ($has_fileuploader === true) { ?>
include_once CLASS_DIR . 'phpformbuilder/plugins/fileuploader/server/class.fileuploader.php';
<?php } ?>

$debug_content = '';

/* =============================================
    validation if posted
============================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST" && Form::testToken('<?php echo $form_id; ?>') === true) {
    $validator = Form::validate('<?php echo $form_id; ?>', FORMVALIDATION_PHP_LANG);
<?php

/* =============================================
Create validation statements
============================================= */

for ($i=0; $i < $generator->columns_count; $i++) {
    $column_name       = $generator->columns['name'][$i];
    $column_type       = $generator->columns['column_type'][$i];
    $column_validation = $generator->columns['validation'][$i];
    $field_type        = $generator->columns['field_type'][$i];

    if (!empty($column_validation) && is_array($column_validation) && (!in_array($column_name, $generator->auto_increment_keys))) {
        $validation = $column_validation;

        for ($j=0; $j < count($validation); $j++) {
            $validation_function = $validation[$j]['function'];
            $validation_args     = $validation[$j]['args'];
            if ($validation_function == 'date' || $validation_function == 'minDate' || $validation_function == 'maxDate') {
?>
    if (isset($_POST['<?php echo $column_name; ?>_submit'])) {
        $validator-><?php echo $validation_function; ?>(<?php echo $validation_args; ?>)->validate('<?php echo $column_name; ?>_submit');
    } else {
        $validator-><?php echo $validation_function; ?>(<?php echo $validation_args; ?>)->validate('<?php echo $column_name; ?>');
    }
<?php
            } elseif ($field_type == 'password') {
?>
    $validator-><?php echo $validation_function; ?>(<?php echo $validation_args; ?>)->validate('<?php echo $column_name; ?>');
<?php
            } elseif ($generator->columns['select_multiple'][$i] > 0 || $field_type == 'checkbox') {
                // Array values
                if ($validation_function == 'required') {
                    // validate only 1st entry
?>
    $validator-><?php echo $validation_function; ?>(<?php echo $validation_args; ?>)->validate('<?php echo $column_name; ?>.0');
<?php
                } elseif ($validation_function == 'maxLength') {
                    // validate json encoded value
?>
    $json_value = json_encode($_POST['<?php echo $column_name; ?>']);
    $validator-><?php echo $validation_function; ?>(<?php echo $validation_args; ?>)->validate($json_value, JSON_UNESCAPED_UNICODE);
<?php
                } else {
                    // validate each entry
                    // used for all validation functions except required and maxLength
?>
    if (is_array($_POST['<?php echo $column_name; ?>']) || $_POST['<?php echo $column_name; ?>'] instanceof Countable) {
        $count = count($_POST['<?php echo $column_name; ?>']);
        for ($i=0; $i < $count; $i++) {
            $validator-><?php echo $validation_function; ?>(<?php echo $validation_args; ?>)->validate('<?php echo $column_name; ?>.' . $i);
        }
    }
<?php
                }
            } else {
?>
    $validator-><?php echo $validation_function; ?>(<?php echo $validation_args; ?>)->validate('<?php echo $column_name; ?>');
<?php
            }
        }
    }
}
?>

    // check for errors
    if ($validator->hasErrors()) {
        $_SESSION['errors']['<?php echo $form_id; ?>'] = $validator->getAllErrors();
    } else {
        require_once CLASS_DIR . 'phpformbuilder/database/db-connect.php';
        require_once CLASS_DIR . 'phpformbuilder/database/DB.php';
        $db = new DB(DEBUG);
        $db->setDebugMode('register');

        // begin transaction
        $db->transactionBegin();

        $values = array();
<?php

/* =============================================
Create update query
============================================= */

/* field_types : boolean|checkbox|color|date|datetime|email|file|hidden|image|month|number|password|radio|select|text|textarea|time|url

column types:

tinyint | smallint | mediumint | int | bigint | decimal | float | double | real
date | datetime | timestamp | time | year
char | varchar | tinytext | text | mediumtext | longtext
enum | set | json

*/

$column_types_integers = array('tinyint', 'smallint', 'mediumint', 'int', 'bigint');
$column_types_floats = array('decimal', 'float', 'double', 'real');

$auto_increment_column_name  = '';
$has_auto_increment_function = false;

for ($i=0; $i < $generator->columns_count; $i++) {
    $column_name              = $generator->columns['name'][$i];
    $column_type              = $generator->columns['column_type'][$i];
    $column_auto_increment    = $generator->columns['auto_increment'][$i];
    $auto_increment_function  = $generator->columns['auto_increment_function'][$i];
    $column_type              = $generator->columns['column_type'][$i];
    $field_type               = $generator->columns['field_type'][$i];
    $special3                 = $generator->columns['special3'][$i];

    $has_relation = false;
    if (!empty($generator->columns['relation'][$i]['target_table'])) {
        $has_relation = true;
    }

    if ($column_auto_increment) {
        $auto_increment_column_name = $column_name;
        if (PDO_DRIVER === 'firebird' && !empty($auto_increment_function)) {
            $has_auto_increment_function = true;
?>
        $sql = 'SELECT NEXT VALUE FOR <?php echo $auto_increment_function; ?> FROM rdb$database';
        if ($next_<?php echo \strtolower($column_name); ?> = $db->queryValue($sql)) {
            $values['<?php echo $column_name; ?>'] = $next_<?php echo \strtolower($column_name); ?>;
        }
<?php
        } elseif (PDO_DRIVER === 'oci') {
            // ommit the auto-incremented column, the value will be generated by a sequence trigger or an IDENTITY column.
        } else {
?>
        $values['<?php echo $column_name; ?>'] = null;
<?php
        }
    } elseif ($field_type == 'color' || $field_type == 'email' || $field_type == 'hidden' || $field_type == 'text' || $field_type == 'textarea' || $field_type == 'url') {
?>
        $values['<?php echo $column_name; ?>'] = $_POST['<?php echo $column_name; ?>'];
<?php
    } elseif ($field_type == 'boolean') {
?>
        if (isset($_POST['<?php echo $column_name; ?>'])) {
            $values['<?php echo $column_name; ?>'] = intval($_POST['<?php echo $column_name; ?>']);
        }
<?php
    } elseif ($field_type == 'radio' || $field_type == 'number') {
        if (in_array($column_type, $column_types_integers)) {
?>
        if (isset($_POST['<?php echo $column_name; ?>'])) {
            $values['<?php echo $column_name; ?>'] = intval($_POST['<?php echo $column_name; ?>']);
        }
<?php
        } elseif (in_array($column_type, $column_types_floats)) {
?>
        if (isset($_POST['<?php echo $column_name; ?>'])) {
            $values['<?php echo $column_name; ?>'] = floatval($_POST['<?php echo $column_name; ?>']);
        }
<?php
        } else {
?>
        if (isset($_POST['<?php echo $column_name; ?>'])) {
            $values['<?php echo $column_name; ?>'] = $_POST['<?php echo $column_name; ?>'];
        }
<?php
        }
    } elseif ($field_type == 'file') {
?>
        if (!empty($_POST['<?php echo $column_name; ?>']) && $_POST['<?php echo $column_name; ?>'] != '[]') {
            $posted_file = FileUploader::getPostedFiles($_POST['<?php echo $column_name; ?>']);
            $values['<?php echo $column_name; ?>'] = $posted_file[0]['file'];
        } else {
            $values['<?php echo $column_name; ?>'] = '';
        }
<?php
    } elseif ($field_type == 'image') {
?>
        if (!empty($_POST['<?php echo $column_name; ?>']) && $_POST['<?php echo $column_name; ?>'] != '[]') {
            $posted_img = FileUploader::getPostedFiles($_POST['<?php echo $column_name; ?>']);
            $values['<?php echo $column_name; ?>'] = $posted_img[0]['file'];
        } else {
            $values['<?php echo $column_name; ?>'] = '';
        }
<?php
    } elseif ($field_type == 'password') {
?>
        if (!empty($_POST['<?php echo $column_name; ?>'])) {
            $password = Secure::encrypt($_POST['<?php echo $column_name; ?>']);
            $values['<?php echo $column_name; ?>'] = $password;
        }
<?php
    } elseif ($field_type == 'select') {
?>
        if (is_array($_POST['<?php echo $column_name; ?>'])) {
<?php
        if ($column_type == 'enum' || $column_type == 'set') {
?>
            $array_values = implode(',', $_POST['<?php echo $column_name; ?>']);
            $values['<?php echo $column_name; ?>'] = $array_values;
<?php
        } else {
    ?>
            $json_values = json_encode($_POST['<?php echo $column_name; ?>'], JSON_UNESCAPED_UNICODE);
            $values['<?php echo $column_name; ?>'] = $json_values;
<?php
        }
?>
        } else {
<?php
        if (in_array($column_type, $column_types_integers)) {
            if ($has_relation) {
?>
            $values['<?php echo $column_name; ?>'] = intval($_POST['<?php echo $column_name; ?>']);
            if ($values['<?php echo $column_name; ?>'] < 1) {
                $values['<?php echo $column_name; ?>'] = null;
            }
<?php
            } else {
?>
            $values['<?php echo $column_name; ?>'] = intval($_POST['<?php echo $column_name; ?>']);
<?php
            }
        } elseif (in_array($column_type, $column_types_floats)) {
?>
            $values['<?php echo $column_name; ?>'] = floatval($_POST['<?php echo $column_name; ?>']);
<?php
        } else {
?>
            $values['<?php echo $column_name; ?>'] = $_POST['<?php echo $column_name; ?>'];
<?php
        }
?>
        }
<?php
    } elseif ($field_type == 'checkbox') {
        if ($column_type == 'enum' || $column_type == 'set') {
?>
            $array_values = implode(',', $_POST['<?php echo $column_name; ?>']);
            $values['<?php echo $column_name; ?>'] = $array_values;
<?php
        } else {
    ?>
            $json_values = json_encode($_POST['<?php echo $column_name; ?>'], JSON_UNESCAPED_UNICODE);
            $values['<?php echo $column_name; ?>'] = $json_values;
<?php
        }
    } elseif ($field_type == 'date' || $field_type == 'month') {
?>
        $date_value = $_POST['<?php echo $column_name; ?>'];
        if (isset($_POST['<?php echo $column_name; ?>_submit'])) {
            $date_value = $_POST['<?php echo $column_name; ?>_submit'];
        }
        if (trim($date_value) == '') {
            $values['<?php echo $column_name; ?>'] = null;
        } else {
            $values['<?php echo $column_name; ?>'] = $date_value;
        }
<?php
    } elseif ($field_type == 'datetime') {
        if (intval($special3) > 0) {
            // date_now_hidden
?>
        $values['<?php echo $column_name; ?>'] = $_POST['<?php echo $column_name; ?>'];
<?php
        } else {
?>
        $value_date = $_POST['<?php echo $column_name; ?>'];
        $value_time = $_POST['<?php echo $column_name; ?>-time'];
        if (isset($_POST['<?php echo $column_name; ?>_submit'])) {
            $value_date = $_POST['<?php echo $column_name; ?>_submit'];
        }
        if (isset($_POST['<?php echo $column_name; ?>-time_submit'])) {
            $value_time = $_POST['<?php echo $column_name; ?>-time_submit'];
        }
        if (trim($value_date . ' ' . $value_time) == '') {
            $values['<?php echo $column_name; ?>'] = null;
        } else {
            $values['<?php echo $column_name; ?>'] = $value_date . ' ' . $value_time;
        }
<?php
        }
    } elseif ($field_type == 'time') {
?>
        $value_time = $_POST['<?php echo $column_name; ?>'];
        if (isset($_POST['<?php echo $column_name; ?>_submit'])) {
            $value_time = $_POST['<?php echo $column_name; ?>_submit'];
        }
        if (trim($value) == '') {
            $values['<?php echo $column_name; ?>'] = null;
        } else {
            $values['<?php echo $column_name; ?>'] = $value_time;
        }
<?php
    }
} // END for

/* =============================================
DB UPDATE
============================================= */

?>
        try {
            // insert into <?php echo $generator->table; ?>

            if (DEMO !== true && $db->insert('<?php echo $generator->table; ?>', $values, DEBUG_DB_QUERIES) === false) {
                $error = $db->error();
                throw new \Exception($error);
            } else {
<?php

/* External fields
-------------------------------------------------- */

/*
$generator->external_columns = array(
    'target_table'       => '',
    'target_fields'      => array(),
    'table_label'        => '',
    'fields_labels'      => array(),
    'relation'           => '',
    'allow_crud_in_list' => false,
    'allow_in_forms'     => true,
    'forms_fields'       => array(),
    'field_type'         => array(), // 'select-multiple' | 'checkboxes'
    'active'             => false
);

// relation = $generator->relations['from_to'][$i]

$ext_col['relation'] = array(
    'origin_table'
    'origin_column'
    'intermediate_table'
    'intermediate_column_1' // refers to origin_table
    'intermediate_column_2' // refers to target_table
    'target_table'
    'target_column',
    'cascade_delete_from_intermediate' // true will automatically delete all matching records according to foreign keys constraints. Default: true
    'cascade_delete_from_origin' // true will automatically delete all matching records according to foreign keys constraints. Default: true
)
*/

if ($show_external === true) {
    foreach ($active_ext_cols as $key => $ext_col) {
        $origin_table           = $ext_col['relation']['origin_table'];
        $origin_column          = $ext_col['relation']['origin_column'];
        $intermediate_table     = $ext_col['relation']['intermediate_table'];
        $relation_origin_column = $ext_col['relation']['intermediate_column_1'];
        $relation_target_column = $ext_col['relation']['intermediate_column_2'];
        $target_table           = $ext_col['relation']['target_table'];
        $target_column          = $ext_col['relation']['target_column'];
        $table_label            = $ext_col['table_label'];
        // (products => products_categories => categories)

        // many to many
        // the posted records will be added  to the intermediate table.
        // get the primary keys of the intermediate table
        // case 1: the intermediate table HAS an auto-incremented primary key.
        // case 2: the intermediate table DOESN't HAVE an auto-incremented primary key.
        // in both cases the WHERE clause is:
        //  $intermediate_table.$relation_origin_column = $origin_table.$origin_column
        $intermediate_table_auto_incremented_pk_column = null;

        $db = new DB();
        $columns = $db->getColumns($intermediate_table);
        $pdo_driver_object = 'phpformbuilder\\database\\pdodrivers\\' . ucfirst(PDO_DRIVER);
        $pdo_driver = new $pdo_driver_object($db->getPdo());
        $columns = $pdo_driver->convertColumns($intermediate_table, $columns);
        if ($columns) {
            foreach ($columns as $col) {
                // last row is table comments, skip it.
                if (isset($col->Field) && $col->Key == 'PRI' && $col->Extra == 'auto_increment') {
                    $intermediate_table_auto_incremented_pk_column  = $col->Field;
                }
            }
        }
        if (PDO_DRIVER === 'firebird' && $has_auto_increment_function) {
?>
                $<?php echo $generator->table; ?>_last_insert_ID = $next_<?php echo \strtolower($auto_increment_column_name); ?>;
<?php
        } elseif (PDO_DRIVER === 'oci') {
?>
                $<?php echo $generator->table; ?>_last_insert_ID = $db->getMaximumValue('<?php echo $generator->table; ?>', '<?php echo $auto_increment_column_name; ?>', DEBUG_DB_QUERIES);
<?php
        } else {
?>
                $<?php echo $generator->table; ?>_last_insert_ID = $db->getLastInsertID();
<?php
        }
?>
                // insert records in <?php echo $intermediate_table ?>

                foreach ($_POST['ext_<?php echo $target_table; ?>'] as $value) {
                    $values = array();
<?php
                    if (!is_null($intermediate_table_auto_incremented_pk_column)) {
?>
                    $values['<?php echo $intermediate_table_auto_incremented_pk_column; ?>'] = null;
<?php
                    }
?>
                    $values['<?php echo $relation_origin_column; ?>'] = $<?php echo $generator->table; ?>_last_insert_ID;
                    $values['<?php echo $relation_target_column; ?>'] = $value;
                    if (DEMO !== true && $db->insert('<?php echo $intermediate_table; ?>', $values, DEBUG_DB_QUERIES) === false) {
                        $error = $db->error();
                        throw new \Exception($error);
                    }
                }

<?php
    } // end foreach
} // end if
?>
                // ALL OK
                if (!DEBUG_DB_QUERIES) {
                    $db->transactionCommit();

                    $_SESSION['msg'] = Utils::alert(INSERT_SUCCESS_MESSAGE, 'alert-success has-icon');

                    // reset form values
                    Form::clear('<?php echo $form_id; ?>');

                    // redirect to list page
                    if (isset($_SESSION['active_list_url'])) {
                        header('Location:' . $_SESSION['active_list_url']);
                    } else {
                        header('Location:' . ADMIN_URL . '<?php echo $generator->item; ?>');
                    }

                    // if we don't exit here, $_SESSION['msg'] will be unset
                    exit();
                } else {
                    $debug_content .= $db->getDebugContent();
                    $db->transactionRollback();

                    $_SESSION['msg'] = Utils::alert(INSERT_SUCCESS_MESSAGE . '<br>(' . DEBUG_DB_QUERIES_ENABLED . ')', 'alert-success has-icon');
                }
            }
        } catch (\Exception $e) {
            $db->transactionRollback();
            $msg_content = DB_ERROR;
            if (DEBUG) {
                $msg_content .= '<br>' . $e->getMessage() . '<br>' . $db->getLastSql();
            }
            $_SESSION['msg'] = Utils::alert($msg_content, 'alert-danger has-icon');
        }
    } // END else
} // END if POST
<?php

/* =============================================
form Update
============================================= */

?>

$form = new Form('<?php echo $form_id; ?>', 'horizontal', 'novalidate');
$form->setAction(ADMIN_URL . '<?php echo $generator->item; ?>/create');
$form->startFieldset();
<?php

// get grouped fields & fields width
$current_group = array();
$is_grouped = array();
for ($i=0; $i < $generator->columns_count; $i++) {
    // SKIP primary keys
    // if (!in_array($generator->columns['name'][$i], $generator->auto_increment_keys)) {
        $is_grouped[$i] = false;
        $flex_option[$i] = 'end';
        if (strpos($generator->columns['field_width'][$i], 'grouped') !== false || ($generator->columns['field_type'][$i] == 'datetime' && intval($generator->columns['special3'][$i]) < 1)) {
            $is_grouped[$i] = true;
        }
        $w = $generator->columns['field_width'][$i];
        $field_width[$i] = 10;
        $field_percent_width[$i] = 100;
        if ($w == '66% single' || $w == '66% grouped') {
            $field_width[$i] = 6;
            $field_percent_width[$i] = 66.66;
            if ($w == '66% single') {
                $flex_option[$i] = 'start';
            }
        } elseif ($w == '50% single' || $w == '50% grouped') {
            $field_width[$i] = 4;
            $field_percent_width[$i] = 50;
            if ($w == '50% single') {
                $flex_option[$i] = 'start';
            }
        } elseif ($w == '33% single' || $w == '33% grouped') {
            $field_width[$i] = 2;
            $field_percent_width[$i] = 33.33;
            if ($w == '33% single') {
                $flex_option[$i] = 'start';
            }
        }
    // }
}

// the loop must be restarted because of group fields.
for ($i=0; $i < $generator->columns_count; $i++) {
    // if (!in_array($generator->columns['name'][$i], $generator->auto_increment_keys)) {
        $field_type           = $generator->columns['field_type'][$i];
        $ajax_loading = false;
        if (isset($generator->columns['ajax_loading'][$i])) {
            $ajax_loading = $generator->columns['ajax_loading'][$i];
        }
        $name                 = $generator->columns['name'][$i];
        $label                = $generator->columns['fields'][$name];
        $special              = $generator->columns['special'][$i];
        $special2             = $generator->columns['special2'][$i];
        $special3             = $generator->columns['special3'][$i];
        $special4             = $generator->columns['special4'][$i];
        $special5             = $generator->columns['special5'][$i];
        $special6             = $generator->columns['special6'][$i];
        $special7             = $generator->columns['special7'][$i];
        $select_from          = $generator->columns['select_from'][$i];
        $select_from_table    = $generator->columns['select_from_table'][$i];
        $select_from_value    = $generator->columns['select_from_value'][$i];
        $select_from_field_1  = $generator->columns['select_from_field_1'][$i];
        $select_from_field_2  = $generator->columns['select_from_field_2'][$i];
        $select_custom_values = $generator->columns['select_custom_values'][$i];
        $select_multiple      = $generator->columns['select_multiple'][$i];
        $help_text            = $generator->columns['help_text'][$i];
        $tooltip              = $generator->columns['tooltip'][$i];
        $required             = $generator->columns['required'][$i];
        $char_count           = $generator->columns['char_count'][$i];
        $char_count_max       = $generator->columns['char_count_max'][$i];
        $tinyMce              = $generator->columns['tinyMce'][$i];
        $grouped              = $is_grouped[$i];
        $width                = $field_width[$i];
        $height               = $generator->columns['field_height'][$i];
        $flex                 = $flex_option[$i];


        /* field_types : input|password|textarea|select|radio|boolean|checkbox|file|image|date|hidden */

        // attributes
        $attr = [];
        if ($required) {
            $attr[] = 'required';
        }

        // multiple
        if ($field_type == 'select' && $select_multiple > 0) {
            $name .= '[]';
        }
?>

// <?php echo $name; ?> --
<?php

        // group
        if (empty($current_group) && $is_grouped[$i]) {
            $percent_width = 0;
            for ($j=$i; $j < ($i + 4); $j++) {
                if (isset($is_grouped[$j]) && $is_grouped[$j]) {
                    $percent_width += $field_percent_width[$j];
                    if ($percent_width <= 100) {
                        // include to current group & remove from others incoming groups
                        $current_group[] = '\'' . $generator->columns['name'][$j] . '\'';
                        if ($generator->columns['field_type'][$j] == 'datetime') {
                            $current_group[] = '\'' . $generator->columns['name'][$j] . '-time\'';
                        }
                        $is_grouped[$j] = false;
                    }
                }
            }
?>
$form->groupElements(<?php echo implode(', ', $current_group) ?>);
<?php
        }
        if (($i == 0 && $flex == 'start') || ($i > 0 && $flex != $flex_option[$i - 1])) {
?>
$options = array(
    'elementsWrapper' => '<div class="form-group row justify-content-<?php echo $flex; ?>"></div>'
);
$form->setOptions($options);
<?php
        }
        // reset group
        $percent_width = 0;
        $current_group = array();

        // layout
        if ($i < 1 || $width != $field_width[$i - 1] || in_array($generator->columns['name'][$i - 1], $generator->auto_increment_keys)) {
?>

$form->setCols(2, <?php echo $width; ?>);
<?php
        }

        // help text
        if (!empty($help_text)) {
?>
$form->addHelper('<?php echo addslashes($help_text); ?>', '<?php echo $name; ?>', 'after');
<?php
        }

        // label & tooltip
        $label = ucwords(addslashes($label));
        if (!empty($tooltip)) {
            $label .= '<a href="#" data-bs-toggle="tooltip" data-bs-html="true" data-bs-title="' . str_replace('"', '\'', addslashes($tooltip)) . '" class="append"><span class="badge text-bg-info">?</span></a>';
        }

        // char count
        if ($char_count && !$tinyMce) {
?>
$form->addPlugin('word-character-count', '#<?php echo $name; ?>', 'default', array('maxCharacters' => <?php echo $char_count_max; ?>));
<?php

        // char count + tinyMce
        } elseif ($char_count && $tinyMce) {
            $attr[] = 'class=tinyMce';
?>
$form->addPlugin('tinymce', '#<?php echo $name; ?>', 'word_char_count', array('maxCharacters' => <?php echo $char_count_max; ?>));
<?php

        // tinyMce
        } elseif ($tinyMce) {
            $attr[] = 'class=tinyMce';
?>
$form->addPlugin('tinymce', '#<?php echo $name; ?>');
<?php
        }

        // color|email|number|url
        if ($field_type == 'text' || $field_type == 'color' || $field_type == 'email' || $field_type == 'url') {
?>
$form->addInput('<?php echo $field_type; ?>', '<?php echo $name; ?>', '', '<?php echo $label; ?>', '<?php echo implode(', ', $attr) ?>');
<?php
        } elseif ($field_type == 'number') {
            // if number with decimals, $special is the numbe of decimals
            if (!empty($special)) {
                $attr[] = 'data-decimals=' . $special;
                $attr[] = 'step=' . pow(10, -intval($special) - 1);
            }
?>
$form->addInput('<?php echo $field_type; ?>', '<?php echo $name; ?>', '', '<?php echo $label; ?>', '<?php echo implode(', ', $attr) ?>');
<?php
        } elseif ($field_type == 'password') {
?>
$form->addPlugin('passfield', '#<?php echo $name; ?>', '<?php echo $special; ?>');
$form->addInput('password', '<?php echo $name; ?>', '', '<?php echo $label; ?>', '<?php echo implode(', ', $attr) ?>');
<?php
        } elseif ($field_type == 'textarea') {
            $attr[] = 'cols=10';
            if ($height !== '') {
                $available_heights = array(
                    'xs' => '1',
                    'sm' => '2',
                    'md' => '8',
                    'lg' => '12',
                    'xlg' => '16'
                );
                if (isset($available_heights[$height])) {
                    $attr[] = 'rows=' . $available_heights[$height];
                } else {
                    $attr[] = 'rows=8';
                }
            } else {
                $attr[] = 'rows=10';
            }
?>
$form->addTextarea('<?php echo $name; ?>', '', '<?php echo $label; ?>', '<?php echo implode(', ', $attr) ?>');
<?php
        } elseif ($field_type == 'select' || $field_type == 'radio' || $field_type == 'checkbox') {
            $indent = '';
            $join_query = '';
            if ($field_type == 'select') {
                if ($ajax_loading) {
                    $attr[] = 'class=ajax-select';
                    $attr[] = 'data-minimum-input-length=2';
                    $attr[] = 'data-placeholder=' . SELECT_CONST . ' ...';
                } else {
                    $attr[] = 'data-slimselect=true';
                }
                if ($select_multiple) {
                    $attr[] = 'multiple';
                }
            }

            /* START if ($select_from == 'from_table')
            -------------------------------------------------- */

            if ($select_from == 'from_table') {
                $indent = '    ';
                $fields_query = $select_from_table . '.' . $select_from_value;
                $order_by = $select_from_table . '.' . $select_from_value;
                $option_text_for_ajax = $select_from_table . '.' . $select_from_value;
                if ($select_from_field_1 != $select_from_value) {
                    if (!strpos($select_from_field_1, '.')) {
                        $fields_query .= ', ' . $select_from_table . '.' . $select_from_field_1;
                        $order_by = $select_from_table . '.' . $select_from_field_1;
                        $option_text_for_ajax = $select_from_table . '.' . $select_from_field_1;
                    } else {
                        // if the target field comes from a secondary relation. E.g: city.country.country
                        $split = explode('.', $select_from_field_1);
                        $secondary_target_table = $split[0];
                        $secondary_target_field = $split[1];
                        $fields_query .= ', ' . $secondary_target_table . '.' . $secondary_target_field;
                        $order_by = $secondary_target_table . '.' . $secondary_target_field;
                        $option_text_for_ajax = $secondary_target_table . '.' . $secondary_target_field;
                    }
                }

                if (!empty($select_from_field_2)) {
                    if (!strpos($select_from_field_2, '.')) {
                        $fields_query .= ', ' . $select_from_table . '.' . $select_from_field_2;
                        $option_text_for_ajax .= ' + ' . $select_from_table . '.' . $select_from_field_2;
                    } else {
                        // if the target field comes from a secondary relation. E.g: city.country.country
                        $split = explode('.', $select_from_field_2);
                        $secondary_target_table = $split[0];
                        $secondary_target_field = $split[1];
                        $fields_query .= ', ' . $secondary_target_table . '.' . $secondary_target_field;
                        $option_text_for_ajax .= ' + ' . $secondary_target_table . '.' . $secondary_target_field;
                    }
                }

                if (strpos($select_from_field_1, '.') || strpos($select_from_field_2, '.') && in_array($secondary_target_table, $generator->relations['all_db_related_tables'])) {
                    // INNER JOIN country ON city.country_country_id = country.country_id'
                    foreach ($generator->relations['from_to'] as $ft) {
                        if ($ft['origin_table'] === $select_from_table && empty($ft['intermediate_table']) && $ft['target_table'] === $secondary_target_table) {
                            $join_query = ' INNER JOIN ' . $secondary_target_table . ' ON ' . $select_from_table . '.' . $ft['origin_column'] . ' = ' . $ft['target_table'] . '.' . $ft['target_column'];
                        }
                    }
                }
?>
$from = '<?php echo $select_from_table . $join_query; ?>';
$columns = '<?php echo $fields_query; ?>';
$where = array();
$extras = array(
    'select_distinct' => true,
    'order_by' => '<?php echo $order_by; ?>'
);

// restrict if relationship table is the users table OR if the relationship table is used in the restriction query
if (ADMIN_LOCKED === true && Secure::canCreateRestricted('<?php echo $generator->table; ?>')) {
    $secure_restriction_query = Secure::getRestrictionQuery('<?php echo $generator->table; ?>');
    if (!empty($secure_restriction_query)) {
        if ('<?php echo $select_from_table; ?>' == USERS_TABLE) {
            $restriction_query = '<?php echo $select_from_table . '.' . $select_from_value ?> = ' . $_SESSION['secure_user_ID'];
            $where[] = $restriction_query;
        } elseif (preg_match('/<?php echo $select_from_table; ?>\./', $secure_restriction_query[0])) {
            $restriction_query = '<?php echo $generator->table; ?>' . $secure_restriction_query[0];
            $where[] = $restriction_query;
        }
    }
}
<?php
                if ($ajax_loading) {
?>

$pdo_select_settings = array(
    'from'  => $from,
    'values' => $columns,
    'where'  => $where,
    'extras' => $extras
);

$_SESSION['select_ajax']['<?php echo $name; ?>']['table'] = '<?php echo $select_from_table; ?>';
$_SESSION['select_ajax']['<?php echo $name; ?>']['field_value'] = '<?php echo $select_from_table . '.' . $select_from_value ?>';
$_SESSION['select_ajax']['<?php echo $name; ?>']['option_text'] = '<?php echo $option_text_for_ajax ?>';
$_SESSION['select_ajax']['<?php echo $name; ?>']['pdo_select_settings'] = $pdo_select_settings;

// set the selected value if it has been sent in URL query parameters
if (isset($_GET['<?php echo $name; ?>'])) {
    $_SESSION['<?php echo $form_id; ?>']['<?php echo $name; ?>'] = addslashes($_GET['<?php echo $name; ?>']);
}

// set an empty option for the placeholder
$form->addOption('<?php echo $name; ?>', '', '');

// set an option to select the current value
if (!empty($_SESSION['<?php echo $form_id; ?>']['<?php echo $name; ?>'])) {
    $current_value_from = '<?php echo $select_from_table . $join_query; ?>';
    $current_value_columns = '<?php echo $fields_query; ?>';
    $current_value_where = array('<?php echo $select_from_table . '.' . $select_from_value ?>' => $_SESSION['<?php echo $form_id; ?>']['<?php echo $name; ?>']);
    $current_value_extras = array(
        'select_distinct' => true,
        'limit' => 1
    );

    $db = new DB(DEBUG);
    $db->setDebugMode('register');

    $db->select($current_value_from, $current_value_columns, $current_value_where, $current_value_extras, DEBUG_DB_QUERIES);

    if (DEBUG_DB_QUERIES) {
        $debug_content .= $db->getDebugContent();
    }

    $db_count = $db->rowCount();
    if (!empty($db_count)) {
        $row = $db->fetch();
        $value = $row-><?php echo $select_from_value; ?>;
<?php
                    if ($select_from_field_1 != $select_from_value) {
                        if (!strpos($select_from_field_1, '.')) {
?>
        $display_value = $row-><?php echo $select_from_field_1; ?>;
<?php
                        } else {
                            $split = explode('.', $select_from_field_1);
                            $secondary_target_table = $split[0];
                            $secondary_target_field = $split[1];
?>
        $display_value = $row-><?php echo $secondary_target_field; ?>;
<?php
                        }
                    } else {
?>
        $display_value = $row-><?php echo $select_from_value; ?>;
<?php
                    }
                    if (!empty($select_from_field_2)) {
                        if (!strpos($select_from_field_2, '.')) {
 ?>
        $display_value .= ' ' . $row-><?php echo $select_from_field_2; ?>;
<?php
                        } else {
                            $split = explode('.', $select_from_field_2);
                            $secondary_target_table = $split[0];
                            $secondary_target_field = $split[1];
?>
        $display_value .= ' ' . $row-><?php echo $secondary_target_field; ?>;
<?php
                        }
                    }
?>
        $form->addOption('<?php echo $name; ?>', $value, $display_value);
    }
}

$form->addSelect('<?php echo $name; ?>', '<?php echo $label; ?>', '<?php echo implode(', ', $attr) ?>');
<?php
                } else { // if NO ajax_loading
?>

// default value if no record exist
$value = '';
$display_value = '';


// set the selected value if it has been sent in URL query parameters
if (isset($_GET['<?php echo $name; ?>'])) {
    $_SESSION['<?php echo $form_id; ?>']['<?php echo $name; ?>'] = addslashes($_GET['<?php echo $name; ?>']);
}

$db = new DB(DEBUG);
$db->setDebugMode('register');

$db->select($from, $columns, $where, $extras, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content .= $db->getDebugContent();
}

$db_count = $db->rowCount();
if (!empty($db_count)) {
<?php
                    if ($required !== true) {
                        if ($field_type == 'select') {
?>
        <?php echo $indent; ?>$form->addOption('<?php echo $name; ?>', '', '-');
<?php
                        } elseif ($field_type == 'radio') {
?>
        <?php echo $indent; ?>$form->addRadio('<?php echo $name; ?>', '-', '');
<?php
                        } elseif ($field_type == 'checkbox') {
?>
        <?php echo $indent; ?>$form->addCheckbox('<?php echo $name; ?>', '-', '');
<?php
                        }
                    }
?>
    while ($row = $db->fetch()) {
        $value = $row-><?php echo $select_from_value; ?>;
<?php
                    if ($select_from_field_1 != $select_from_value) {
                        if (!strpos($select_from_field_1, '.')) {
?>
        $display_value = $row-><?php echo $select_from_field_1; ?>;
<?php
                        } else {
                            $split = explode('.', $select_from_field_1);
                            $secondary_target_table = $split[0];
                            $secondary_target_field = $split[1];
?>
        $display_value = $row-><?php echo $secondary_target_field; ?>;
<?php
                        }
                    } else {
?>
        $display_value = $row-><?php echo $select_from_value; ?>;
<?php
                    }
                    if (!empty($select_from_field_2)) {
                        if (!strpos($select_from_field_2, '.')) {
?>
        $display_value .= ' ' . $row-><?php echo $select_from_field_2; ?>;
<?php
                        } else {
                            $split = explode('.', $select_from_field_2);
                            $secondary_target_table = $split[0];
                            $secondary_target_field = $split[1];
?>
        $display_value .= ' ' . $row-><?php echo $secondary_target_field; ?>;
<?php
                        }
                    }
                    $min_count_for_field = 1;
                    if ($required !== true) {
                        $min_count_for_field = 0;
                    }
                    if ($select_from == 'from_table' && !$ajax_loading) {
?>
        if ($db_count > <?php echo $min_count_for_field; ?>) {
<?php
                    }
                    if ($field_type == 'select') {
?>
        <?php echo $indent; ?>$form->addOption('<?php echo $name; ?>', $value, $display_value);
<?php
                    } elseif ($field_type == 'radio') {
?>
        <?php echo $indent; ?>$form->addRadio('<?php echo $name; ?>', $display_value, $value);
<?php
                    } elseif ($field_type == 'checkbox') {
?>
        <?php echo $indent; ?>$form->addCheckbox('<?php echo $name; ?>', $display_value, $value);
<?php
                    }
?>
        }
    }
}
<?php
/* END if (!$ajax_loading) {
-------------------------------------------------- */
                }
/* END if ($select_from == 'from_table')
-------------------------------------------------- */
            } elseif ($select_from == 'custom_values') {
                foreach ($select_custom_values as $custom_label => $custom_value) {
                    if ($field_type == 'select') {
?>
$form->addOption('<?php echo $name; ?>', '<?php echo $custom_value; ?>', '<?php echo $custom_label; ?>');
<?php
                    } elseif ($field_type == 'radio') {
?>
$form->addRadio('<?php echo $name; ?>', '<?php echo $custom_label; ?>', '<?php echo $custom_value; ?>');
<?php
                    } elseif ($field_type == 'checkbox') {
?>
$form->addCheckbox('<?php echo $name; ?>', '<?php echo $custom_label; ?>', '<?php echo $custom_value; ?>');
<?php
                    }
                }
            }
            if (!$ajax_loading) {
                if ($select_from == 'from_table') {
?>

if ($db_count > <?php echo $min_count_for_field; ?>) {
<?php
                }
                if ($field_type == 'select') {
?>
<?php echo $indent; ?>$form->addSelect('<?php echo $name; ?>', '<?php echo $label; ?>', '<?php echo implode(', ', $attr) ?>');
<?php
                } elseif ($field_type == 'radio') {
?>
<?php echo $indent; ?>$form->printRadioGroup('<?php echo $name; ?>', '<?php echo $label; ?>', true, '<?php echo implode(', ', $attr) ?>');
<?php
                } elseif ($field_type == 'checkbox') {
?>
<?php echo $indent; ?>$form->printCheckboxGroup('<?php echo $name; ?>', '<?php echo $label; ?>', true, '<?php echo implode(', ', $attr) ?>');
<?php
                }
                if ($select_from == 'from_table') {
                    $attr[] = 'readonly';
?>
} else {
    // for display purpose
    $form->addInput('text', '<?php echo $name; ?>-display', $display_value, '<?php echo $label; ?>', 'readonly');

    // for send purpose
    $form->addInput('hidden', '<?php echo $name; ?>', $value);
}
<?php
                }
/* END if (!$ajax_loading) {
-------------------------------------------------- */
            }
        } elseif ($field_type == 'boolean') {
?>
$form->addRadio('<?php echo $name; ?>', NO, 0);
$form->addRadio('<?php echo $name; ?>', YES, 1);
$form->printRadioGroup('<?php echo $name; ?>', '<?php echo $label; ?>', true, '<?php echo implode(', ', $attr) ?>');
<?php
        } elseif ($field_type == 'file') {
            // default allowed extensions
            $extensions = '[\'doc\', \'docx\', \'xls\', \'xlsx\', \'pdf\', \'txt\']';
            if (preg_match_all('`([^,]+)(?:,)*(?:\s)*`', $special3, $out)) {
                $extensions = '[\'' . implode('\', \'', $out[1]) . '\']';
            }
?>
// get current file if exists
$current_file = '';
if (!empty($_SESSION['<?php echo $form_id; ?>']['<?php echo $name; ?>'])) {
    if (isset($_POST['<?php echo $name; ?>']) && !empty($_POST['<?php echo $name; ?>'])) {
        // get filename from POST data (JSON)
        $posted_file = FileUploader::getPostedFiles($_POST['<?php echo $name; ?>']);
        $current_file_name = $posted_file[0]['file'];
    } else {
        // get filename from Database (text)
        $current_file_name = $_SESSION['<?php echo $form_id; ?>']['<?php echo $name; ?>'];
    }
    $current_file_path = ROOT . '<?php echo $special; ?>';
    if (file_exists($current_file_path . $current_file_name)) {
        $current_file_size = filesize($current_file_path . $current_file_name);
        $current_file_type = mime_content_type($current_file_path . $current_file_name);
        $current_file = array(
            'name' => $current_file_name,
            'size' => $current_file_size,
            'type' => $current_file_type,
            'file' => BASE_URL . '<?php echo $special; ?>' . $current_file_name,
            'data' => array(
                'listProps' => array(
                'file' => $current_file_name
                )
            )
        );
    }
}
$fileUpload_config = array(
'upload_dir'    => '../../../../../../<?php echo $special; ?>', // the directory to upload the files. relative to [plugins dir]/fileuploader/[xml]/php/[uploader]
'limit'         => 1, // max. number of files
'file_max_size' => 5, // each file's maximal size in MB {null, Number}
'extensions'    => <?php echo $extensions; ?>,
'debug'         => true
);
$form->addFileUpload('<?php echo $name; ?>', '', '<?php echo $label; ?>', '', $fileUpload_config, $current_file);
<?php
        } elseif ($field_type == 'image') {
            $thumbnails = 'false';
            $editor     = 'false';
            $width      = '9999';
            $height     = '9999';
            $crop       = 'false';
            if ($special3 > 0) {
                $thumbnails = 'true';
            }
            if ($special4 > 0) {
                $editor = 'true';
            }
            if ($special5 > 0) {
                $width = $special5;
            }
            if ($special6 > 0) {
                $height = $special6;
            }
            if ($special7 > 0) {
                $crop = 'true';
            }
?>
// get current image if exists
$current_file = '';
if (!empty($_SESSION['<?php echo $form_id; ?>']['<?php echo $name; ?>'])) {
    if (isset($_POST['<?php echo $name; ?>']) && !empty($_POST['<?php echo $name; ?>'])) {
        // get filename from POST data (JSON)
        $posted_file = FileUploader::getPostedFiles($_POST['<?php echo $name; ?>']);
        $current_file_name = $posted_file[0]['file'];
    } else {
        // get filename from Database (text)
        $current_file_name = $_SESSION['<?php echo $form_id; ?>']['<?php echo $name; ?>'];
    }
    $current_file_path = ROOT . '<?php echo $special; ?>';
    if (file_exists($current_file_path . $current_file_name)) {
        $current_file_size = filesize($current_file_path . $current_file_name);
        $current_file_type = mime_content_type($current_file_path . $current_file_name);
        $current_file = array(
            'name' => $current_file_name,
            'size' => $current_file_size,
            'type' => $current_file_type,
            'file' => BASE_URL . '<?php echo $special; ?>' . $current_file_name,
            'data' => array(
                'listProps' => array(
                'file' => $current_file_name
                )
            )
        );
    }
}
$fileUpload_config = array(
    'xml'           => 'image-upload', // the thumbs directories must exist
    'uploader'      => 'ajax_upload_file.php', // the uploader file in phpformbuilder/plugins/fileuploader/[xml]/php
    'upload_dir'    => '../../../../../../<?php echo $special; ?>', // the directory to upload the files. relative to [plugins dir]/fileuploader/[xml]/php/[uploader]
    'limit'         => 1, // max. number of files
    'file_max_size' => 5, // each file's maximal size in MB {null, Number}
    'extensions'    => ['jpg', 'jpeg', 'png'],
    'thumbnails'    => <?php echo $thumbnails; ?>,
    'editor'        => <?php echo $editor; ?>,
    'width'         => <?php echo $width; ?>,
    'height'        => <?php echo $height; ?>,
    'crop'          => <?php echo $crop; ?>,
    'debug'         => true
);
$form->addFileUpload('<?php echo $name; ?>', '', '<?php echo $label; ?>', '', $fileUpload_config, $current_file);
<?php
        } elseif ($field_type == 'date') {
            if (intval($special3) > 0) {
?>
$form->addInput('hidden', '<?php echo $name; ?>', date('Y-m-d'));
<?php
            } else {
                $attr[] = 'data-format=' . $special . ', data-format-submit=yyyy-mm-dd, data-set-default-date=true';
?>
$form->addPlugin('pickadate', '#<?php echo $name; ?>');
$form->addInput('text', '<?php echo $name; ?>', '', '<?php echo $label; ?>', '<?php echo implode(', ', $attr) ?>');
<?php
            }
        } elseif ($field_type == 'datetime') {
            if (intval($special3) > 0) {
?>
$form->addInput('hidden', '<?php echo $name; ?>', date('Y-m-d H:i'));
<?php
            } else {
                $date_attr          = $attr;
                $time_attr          = $attr;
                $material_time_attr = $attr;

                $date_format = 'yyyy mmmm dddd';
                $time_format = 'H:i a';
                $material_time_format = 'HH:i';
                if (!empty($special)) {
                    $date_format = $special;
                }
                $twelve_hour = 'false';
                if (!empty($special2)) {
                    $time_format = $special2;
                    if (strpos($special2, 'h') !== false) {
                        $twelve_hour = 'true';
                        $material_time_format = 'hh:i A';
                    }
                }

                $date_attr[] = 'data-format=' . $date_format;
                $date_attr[] = 'data-format-submit=yyyy-mm-dd';
                $date_attr[] = 'data-set-default-date=true';

                $time_attr[] = 'data-format=' . $time_format;
                $time_attr[] = 'data-format-submit=HH:i';
                $time_attr[] = 'data-interval=15';

                $material_time_attr[] = 'data-format=hh:i A';
                $material_time_attr[] = 'data-format-submit=HH:i';
                $material_time_attr[] = 'data-twelve-hour=' . $twelve_hour;
                $material_time_attr[] = 'data-interval=15';
?>
$form->addPlugin('pickadate', '#<?php echo $name; ?>'); // date field
$form->addPlugin('pickadate', '#<?php echo $name; ?>-time', 'pickatime'); // time field
<?php
                // set date & time fields width
                if ($width == 4) {
                    $date_width = 2;
                    $time_width = 2;
                } elseif ($width == 10) {
                    $date_width = 6;
                    $time_width = 4;
                }
?>

$form->setCols(2, <?php echo $date_width; ?>);
$form->addInput('text', '<?php echo $name; ?>', '', '<?php echo $label; ?>', '<?php echo implode(', ', $date_attr) ?>');
$form->setCols(0, <?php echo $time_width; ?>);
<?php
                // time placeholder
                $time_attr[] = 'placeholder=' . TIME_PLACEHOLDER;
                $material_time_attr[] = 'placeholder=' . TIME_PLACEHOLDER;
?>
if (DATETIMEPICKERS_STYLE === 'material') {
    $form->addInput('text', '<?php echo $name; ?>-time', '', '', '<?php echo implode(', ', $material_time_attr) ?>');
} else {
    $form->addInput('text', '<?php echo $name; ?>-time', '', '', '<?php echo implode(', ', $time_attr) ?>');
}
$form->setCols(2, <?php echo $width; ?>);
<?php
            }
        } elseif ($field_type == 'time') {
            if (intval($special3) > 0) {
?>
$form->addInput('hidden', '<?php echo $name; ?>', date('HH:i'));
<?php
            } else {
                $time_attr          = $attr;
                $material_time_attr = $attr;
                $time_format = 'H:i a';
                $material_time_format = 'HH:i';
                $twelve_hour = 'false';
                if (!empty($special2) && strpos($special2, 'h') !== false) {
                    $twelve_hour = 'true';
                    $material_time_format = 'hh:i A';
                }
                $time_attr[] = 'data-format=' . $time_format;
                $time_attr[] = 'data-format-submit=HH:i';
                $time_attr[] = 'data-interval=15';

                $material_time_attr[] = 'data-format=hh:i A';
                $material_time_attr[] = 'data-format-submit=HH:i';
                $material_time_attr[] = 'data-twelve-hour=' . $twelve_hour;
                $material_time_attr[] = 'data-interval=15';
?>
$form->addPlugin('pickadate', '#<?php echo $name; ?>', 'pickatime'); // time field
if (DATETIMEPICKERS_STYLE === 'material') {
    $form->addInput('text', '<?php echo $name; ?>', '', '<?php echo $label; ?>', '<?php echo implode(', ', $material_time_attr) ?>');
} else {
    $form->addInput('text', '<?php echo $name; ?>', '', '<?php echo $label; ?>', '<?php echo implode(', ', $time_attr) ?>');
}
<?php
            }
        } elseif ($field_type == 'month') {
            if (intval($special3) > 0) {
?>
$form->addInput('hidden', '<?php echo $name; ?>', date('m'));
<?php
            } else {
                $attr[] = 'data-slimselect=true';
?>
$form->addOption('<?php echo $name; ?>', JANUARY, JANUARY);
$form->addOption('<?php echo $name; ?>', FEBRUARY, FEBRUARY);
$form->addOption('<?php echo $name; ?>', MARCH, MARCH);
$form->addOption('<?php echo $name; ?>', APRIL, APRIL);
$form->addOption('<?php echo $name; ?>', MAY, MAY);
$form->addOption('<?php echo $name; ?>', JUNE, JUNE);
$form->addOption('<?php echo $name; ?>', JULY, JULY);
$form->addOption('<?php echo $name; ?>', AUGUST, AUGUST);
$form->addOption('<?php echo $name; ?>', SEPTEMBER, SEPTEMBER);
$form->addOption('<?php echo $name; ?>', OCTOBER, OCTOBER);
$form->addOption('<?php echo $name; ?>', NOVEMBER, NOVEMBER);
$form->addOption('<?php echo $name; ?>', DECEMBER, DECEMBER);
$form->addSelect('<?php echo $name; ?>', '<?php echo $label; ?>', '<?php echo implode(', ', $attr) ?>');
<?php
            }
        } elseif ($field_type == 'hidden') {
?>
$form->addInput('hidden', '<?php echo $name; ?>', '');
<?php
        }
    // }
}

/* External fields
-------------------------------------------------- */

/*
$generator->external_columns = array(
    'target_table'       => '',
    'target_fields'      => array(),
    'table_label'        => '',
    'fields_labels'      => array(),
    'relation'           => '',
    'allow_crud_in_list' => false,
    'allow_in_forms'     => true,
    'forms_fields'       => array(),
    'field_type'         => array(), // 'select-multiple' | 'checkboxes'
    'active'             => false
);

// relation = $generator->relations['from_to'][$i]
*/

if ($show_external === true) {
    foreach ($active_ext_cols as $key => $ext_col) {
        $origin_table       = $ext_col['relation']['origin_table'];
        $intermediate_table = $ext_col['relation']['intermediate_table'];
        $target_table       = $ext_col['relation']['target_table'];
        $target_column      = $ext_col['relation']['target_column'];
        $table_label        = $ext_col['table_label'];

        $fields_query = implode(', ', $ext_col['forms_fields']);

        // add primary key in query if necessary
        if (!in_array($target_column, $ext_col['forms_fields'])) {
            $fields_query .= ', ' . $target_column;
        }
        $row_value = '$row->' . $target_column;
        $row_display_value    = '$row->' . implode(' . \' - \' . $row->', $ext_col['forms_fields']);
?>

// external relation: <?php echo $origin_table; ?> => <?php echo $intermediate_table; ?> => <?php echo $target_table; ?>;
$from = '<?php echo $target_table; ?>';
$columns = '<?php echo $fields_query; ?>';
$where = false;
$extras = array(
    'select_distinct' => true
);

$db = new DB();
$db->select($from, $columns, $where, $extras, DEBUG_DB_QUERIES);

if (DEBUG_DB_QUERIES) {
    $debug_content .= $db->getDebugContent();
}

$db_count = $db->rowCount();
if (!empty($db_count)) {
    $values = array();
    $display_values = array();
    while ($row = $db->fetch()) {
        $values[] = <?php echo $row_value; ?>;
        $display_values[] = <?php echo $row_display_value; ?>;
    }
    for ($i=0; $i < $db_count; $i++) {
<?php
if ($ext_col['field_type'] == 'select-multiple') {
?>
        $form->addOption('ext_<?php echo $target_table; ?>[]', $values[$i], $display_values[$i]);
<?php
} else {
?>
        $form->addCheckbox('ext_<?php echo $target_table; ?>', $display_values[$i], $values[$i]);
<?php
}
?>
    }
<?php
if ($ext_col['field_type'] == 'select-multiple') {
?>
    $form->addSelect('ext_<?php echo $target_table; ?>[]', '<?php echo $table_label; ?>', 'data-slimselect=true, multiple, data-close-on-select=false');
<?php
} else {
?>
    $form->printCheckboxGroup('ext_<?php echo $target_table; ?>', '<?php echo $table_label; ?>');
<?php
}
?>
}
<?php
    } // end foreach
} // end if

    // layout
if ($generator->columns['field_width'][$i - 1] < 10) {
?>
$form->setCols(2, <?php echo $width; ?>);
<?php
}
?>
$form->addBtn('button', 'cancel', 0, '<i class="' . ICON_BACK . ' prepend"></i>' . CANCEL, 'class=btn btn-warning, data-ladda-button=true, data-style=zoom-in, onclick=history.go(-1)', 'btn-group');
$form->addBtn('submit', 'submit-btn', 1, SUBMIT . '<i class="' . ICON_CHECKMARK . ' append"></i>', 'class=btn btn-success, data-ladda-button=true, data-style=zoom-in', 'btn-group');
$form->setCols(0, 12);
$form->centerContent();
$form->printBtnGroup('btn-group');
$form->endFieldset();
$form->addPlugin('pretty-checkbox', '#<?php echo $form_id; ?>');
$form->addPlugin('formvalidation', '#<?php echo $form_id; ?>', 'default', array('language' => FORMVALIDATION_JAVASCRIPT_LANG));
