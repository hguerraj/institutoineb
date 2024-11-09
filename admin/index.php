<?php
use altorouter\AltoRouter;

header("Content-Type: text/html");

include_once '../conf/conf.php';

include __DIR__ . '/class/altorouter/AltoRouter.php';
$router = new AltoRouter();
$router->setBasePath(ROOT_RELATIVE_URL . basename(__DIR__));

// router MatchTypes
$router->addMatchTypes(array('pk_fieldname' => '[0-9a-zA-Z_-]+'));
$router->addMatchTypes(array('pk_value' => '[^\/]+'));

$json    = file_get_contents('crud-data/db-data.json');
$db_data = json_decode($json, true);
$editable_tables = array();

// get table names & url alias
// '-' not allowed in table names - would break edit in place links
if (!is_null($db_data)) {
    foreach ($db_data as $key => $value) {
        $editable_tables[$key] = $value['item'];
    }
}

// Main routes that non-customers see
$router->map('GET', '/home', 'home.php', 'home');

$router->map('GET|POST', '/login', 'login.php', 'login');

$router->map('GET', '/logout', 'logout.php', 'logout');

// Lists
$router->map('GET|POST', '/[' . implode('|', $editable_tables) . ':item]', 'data-list.php', 'data-list');

// Paginated Lists
$router->map('GET|POST', '/[' . implode('|', $editable_tables) . ':item]/p[i:p]', 'data-list.php', 'data-paginated-list');

// Single record view. Ie: actor/view/actor_id=2
$router->map('GET', '/[' . implode('|', $editable_tables) . ':item]/view/[pk_fieldname:pk_fieldname]=[pk_value:pk_value]', 'inc/single-record-view.php', 'data-view');

// Single record view with 2 primary keys
$router->map('GET', '/[' . implode('|', $editable_tables) . ':item]/view/[pk_fieldname:pk_fieldname_1]=[pk_value:pk_value_1]/[pk_fieldname:pk_fieldname_2]=[pk_value:pk_value_2]', 'inc/single-record-view.php', 'data-view-2-primary-keys');

// Single record view. Ie: actor/view/actor_id=2
$router->map('GET', '/[' . implode('|', $editable_tables) . ':item]/print-view/[pk_fieldname:pk_fieldname]=[pk_value:pk_value]', 'inc/single-record-print-view.php', 'data-print-view');

// Single record view with 2 primary keys
$router->map('GET', '/[' . implode('|', $editable_tables) . ':item]/print-view/[pk_fieldname:pk_fieldname_1]=[pk_value:pk_value_1]/[pk_fieldname:pk_fieldname_2]=[pk_value:pk_value_2]', 'inc/single-record-print-view.php', 'data-print-view-2-primary-keys');

// Ajax Search Lists
$router->map('POST', '/search/[' . implode('|', $editable_tables) . ':item]', 'inc/data-list-search.php', 'data-list-search');

// Create
$router->map('GET|POST', '/[' . implode('|', $editable_tables) . ':item]/[create:action]', 'data-forms.php', 'data-forms-create');

// Update|Delete
$router->map('GET|POST', '/[' . implode('|', $editable_tables) . ':item]/[edit|delete:action]/[pk_fieldname:pk_fieldname]=[pk_value:pk_value]', 'data-forms.php', 'data-forms-edit-delete');

// Update|Delete with 2 primary keys
$router->map('GET|POST', '/[' . implode('|', $editable_tables) . ':item]/[edit|delete:action]/[pk_fieldname:pk_fieldname_1]=[pk_value:pk_value_1]/[pk_fieldname:pk_fieldname_2]=[pk_value:pk_value_2]', 'data-forms.php', 'data-forms-edit-delete-2-primary-keys');

// Register URL query string parameters in $_GET since Altorouter ROUTE doesn't deal with these.
$parts = parse_url($_SERVER['REQUEST_URI']);
if (isset($parts['query'])) {
    parse_str($parts['query'], $_GET);
}

/* Match the current request */
$match = $router->match();
if ($match) {
    require_once $match['target'];
} else {
    header("HTTP/1.0 404 Not Found");
    require_once '404.php';
}
