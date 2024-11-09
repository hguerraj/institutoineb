<?php
use phpformbuilder\database\DB;

session_start();
$error = false;
$out = array(
    'results' => array(),
    'pagination' => array(
        'more' => false
    )
);
if (
    !isset($_SESSION['filters_ajax']) ||
    !isset($_POST['selectname']) ||
    !isset($_POST['search']) ||
    !isset($_POST['page'])) {
    $error = true;
}
include_once '../../conf/conf.php';
include_once CLASS_DIR . 'phpformbuilder/Form.php';

$selectname   = $_POST['selectname'];
$search       = $_POST['search'];
$page         = $_POST['page'];

if (
    !isset($_SESSION['filters_ajax'][$selectname]) ||
    !isset($_SESSION['filters_ajax'][$selectname]['table']) ||
    !isset($_SESSION['filters_ajax'][$selectname]['field_to_filter']) ||
    !isset($_SESSION['filters_ajax'][$selectname]['option_text']) ||
    !isset($_SESSION['filters_ajax'][$selectname]['pdo_select_settings'])) {
    $error = true;
}
// var_dump($_SESSION['filters_ajax'][$selectname]);
// var_dump($error);
if (!$error) {
    $table               = $_SESSION['filters_ajax'][$selectname]['table'];
    $pdo_select_settings = $_SESSION['filters_ajax'][$selectname]['pdo_select_settings'];
    $filter              = array(
        'field_to_filter' => $_SESSION['filters_ajax'][$selectname]['field_to_filter'],
        'option_text'     => $_SESSION['filters_ajax'][$selectname]['option_text']
    );
    $db = new DB();
    /*
    $pdo_select_settings = array(
        'from'  => $filter['from_table'] . $current_join_query,
        'values' => $filter['fields'],
        'where'  => $where,
        'extras' => array(
            'order_by' => $this->getAliases($filter['fields']) . ' ASC'
        )
    );
    */
    $field_value = preg_replace('`[^.]+\.`', '', $filter['field_to_filter']);

    if (preg_match('`[\s]?\+[\s]?`', $filter['option_text'])) {
        /* if the option text combines 2 values
           ie: actor.firstname + actor.name
        -------------------------------------------------- */

        // $field_text_fields = ['actor.firstname', 'actor.name']
        $field_text_fields = preg_split('`[\s]?\+[\s]?`', $filter['option_text']);

        // $field_text = ['firstname', 'name']
        $field_text = array(
            preg_replace('`[^.]+\.`', '', $field_text_fields[0]),
            preg_replace('`[^.]+\.`', '', $field_text_fields[1])
        );
        $where = '(LOWER(' . $field_text_fields[0] . ') LIKE LOWER(' . $db->safe('%' . $search . '%') . ') OR LOWER(' . $field_text_fields[1] . ') LIKE LOWER(' . $db->safe('%' . $search . '%') . '))';
    } else {
        $field_text_fields = $filter['option_text'];
        $field_text        = preg_replace('`[^.]+\.`', '', $filter['option_text']);
        $where = 'LOWER(' . $field_text_fields . ') LIKE LOWER(' . $db->safe('%' . $search . '%') . ')';
    }
    $pdo_select_settings['where'][] = $where;

    $number_of_results = 10;
    // we get one more result than $number_of_results
    // it allows to know if some more results exist beyond the query
    $limit = (($page - 1) * $number_of_results)  . ',' . ($number_of_results + 1);
    $pdo_select_settings['extras']['limit'] = $limit;

    $db->setDebugMode('register');
    $db->select($table, $pdo_select_settings['values'], $pdo_select_settings['where'], $pdo_select_settings['extras'], DEBUG_DB_QUERIES);

    $values_count = $db->rowCount();
    if (!empty($values_count)) {
        if ($values_count > $number_of_results) {
            $out['pagination'] = array(
                'more' => true
            );
        }
        $used_values = array();
        while ($row = $db->fetch()) {
            $test_if_json = json_decode($row->$field_value);
            if (json_last_error() == JSON_ERROR_NONE && is_array($test_if_json)) {
                foreach ($test_if_json as $value) {
                    if (!in_array($value, $used_values)) {
                        $out['results'][] = array(
                            'id' => '~' . $value . '~',
                            'text' => $value
                        );
                        $used_values [] = $value;
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
                $out['results'][] = array(
                    'id' => $option_value,
                    'text' => $option_text
                );
            }
        }
    }
    $out['debugAjaxContent'] = $db->getDebugContent();
}

echo json_encode($out);

/* {
  "results": [
    {
      "id": 1,
      "text": "Option 1"
    },
    {
      "id": 2,
      "text": "Option 2"
    }
  ],
  "pagination": {
    "more": true
  }
} */
