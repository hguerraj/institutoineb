<?php
namespace phpformbuilder\database;

class Pagination extends DB
{
    public $pagine;
    private $active_class       = 'active';
    private $disabled_class     = 'disabled';
    private $pagination_class   = 'pagination pagination-flat';
    private $first_markup       = '<i class="fas fa-angle-double-left"></i>';
    private $previous_markup    = '<i class="fas fa-angle-left"></i>';
    private $next_markup        = '<i class="fas fa-angle-right"></i>';
    private $last_markup        = '<i class="fas fa-angle-double-right"></i>';
    private $rewrite_transition;
    private $rewrite_extension;

    /**
     * Build pagination
     * @param array  $pdo_settings PDO settings to which the "LIMIT..." will be added. i.e:
     * $pdo_settings = array(
     *     'function' => 'select', // The function in the parent DB class.
     *                                Values: 'select' or 'query'
     *     'from'     => $from,   // refer to DB->select() arguments
     *     'values'   => $columns, // refer to DB->select() arguments
     *     'where'    => $where,   // refer to DB->select() arguments
     *     'extras'   => $extras, // refer to DB->select() arguments
     *     'debug'    => false     // refer to DB->select() arguments
     * );
     * or:
     * $pdo_settings = array(
     *     'function' => 'query', // The function in the parent DB class.
     *                               Values: 'select' or 'query'
     *     'sql'          => 'SELECT ...', // refer to DB->query() arguments
     *     'placeholders' => false,        // refer to DB->query() arguments
     *     'debug'        => false         // refer to DB->query() arguments
     * );
     * documentation: https://www.phpformbuilder.pro/documentation/db-help.php#select
     * @param string  $mpp         Max number of lines per page
     * @param string  $querystring Querystring element indicating the page number
     * @param string  $url         URL of the page
     * @param integer $long        Max number of pages before and after the current page
     */
    public function pagine($pdo_settings, $mpp, $querystring, $url, $long = 5, $rewrite_links = true, $rewrite_transition = '-', $rewrite_extension = '.html')
    {
    // Pour construire les liens, regarde si $url contient déjà un ?
        $t   = $this->rewrite_transition = $rewrite_transition;
        $ext = $this->rewrite_extension = $rewrite_extension;
        $url = $this->removePreviousQuerystring($url, $querystring, $rewrite_links);
        if ($rewrite_links !== true) {
            if (strpos($url, "?")) {
                $t = '&amp;';
            } else {
                $t = '?';
            }
        }
        $this->getRecords($pdo_settings);
        $nbre = parent::rowCount();  // Total number of records returned
        if (!empty($nbre)) {
            $_SESSION['result_rs'] = $nbre;    // Calculation of the number of pages
            $nbpage = ceil($nbre/$mpp);    // The current page is
            $p = @$_GET[$querystring];
            if (!$p) {
                $p = 1;
            }
            if ($p>$nbpage) {
                $p = $nbpage;    // Length of the page list
            }
            $deb = max(1, $p-$long);
            $fin = min($nbpage, $p+$long);    // Building the list of pages
            $this->pagine = "";
            if ($nbpage > 1) {
                for ($i = $deb; $i <= $fin; $i++) { // Current page ?
                    if ($i == $p) {
                        $this->pagine .= '<li class="page-item ' . $this->active_class . '"><a class="page-link" href="#">' . $i . '</a></li>' . "\n";    // Page 1 > link without query
                    } elseif ($i == 1) {
                        if ($rewrite_links === true) {
                            $this->pagine .= '<li class="page-item"><a class="page-link" href="' . $url . $ext . '">' . $i . '</a></li>' . "\n";   // Other page -> link with query
                        } else {
                            $this->pagine .= '<li class="page-item"><a class="page-link" href="' . $url . '">' . $i . '</a></li>' . "\n";   // Other page -> link with query
                        }
                    } else {
                        if ($rewrite_links === true) {
                            $this->pagine .= '<li class="page-item"><a class="page-link" href="' . $url . $t . $querystring . $i . $ext . '">' . $i . '</a></li>' . "\n";
                        } else {
                            $this->pagine .= '<li class="page-item"><a class="page-link" href="' . $url . $t . $querystring . '=' . $i . '">' . $i . '</a></li>' . "\n";
                        }
                    }
                }
                if ($this->pagine) {
                    $this->pagine = '<li class="page-item ' . $this->disabled_class . '"><a class="page-link" href="#">Page</a></li>' . $this->pagine . "\n";
                }
                if ($this->pagine && ($p > 1)) { //PREVIOUS
                    if ($p == 2) {
                        if ($rewrite_links === true) {
                            $this->pagine = '<li class="page-item"><a class="page-link" href="' . $url . $ext . '">' . $this->previous_markup . '</a></li>' . $this->pagine . "\n";
                        } else {
                            $this->pagine = '<li class="page-item"><a class="page-link" href="' . $url . '">' . $this->previous_markup . '</a></td>' . $this->pagine . "\n";
                        }
                    } else { //PREVIOUS
                        if ($rewrite_links === true) {
                            $this->pagine = '<li class="page-item"><a class="page-link" href="' . $url . $t . $querystring . ($p-1) . $ext . '">' . $this->previous_markup . '</a></li>' . $this->pagine . "\n";
                        } else {
                            $this->pagine = '<li class="page-item"><a class="page-link" href="' . $url . $t . $querystring . '=' . ($p-1)  . '">' . $this->previous_markup . '</a></li>' . $this->pagine . "\n";
                        }
                    }
                    if ($p>1) { // FIRST
                        if ($rewrite_links === true) {
                            $this->pagine = '<li class="page-item"><a class="page-link" href="' . $url . $ext . '">' . $this->first_markup . '</a></li>' . $this->pagine . "\n";
                        } else {
                            $this->pagine = '<li class="page-item"><a class="page-link" href="' . $url . '">' . $this->first_markup . '</a></li>' . $this->pagine . "\n";
                        }
                    }
                }
                if ($this->pagine && ($p<$nbpage)) { // NEXT, LAST
                    if ($rewrite_links === true) {
                        $this->pagine .= '<li class="page-item"><a class="page-link" href="' . $url . $t . $querystring . ($p+1) . $ext . '">' . $this->next_markup . '</a></li>' . "\n"; // NEXT
                    } else {
                        $this->pagine .= '<li class="page-item"><a class="page-link" href="' . $url . $t . $querystring . '=' . ($p+1)  . '">' . $this->next_markup . '</a></li>' . "\n";
                    }
                    if ($p<$nbpage) { // LAST
                        if ($rewrite_links === true) {
                            $this->pagine .= '<li class="page-item"><a class="page-link" href="' . $url . $t . $querystring . ($nbpage) . $ext . '">' . $this->last_markup . '</a></li>' . "\n";
                        } else {
                            $this->pagine .= '<li class="page-item"><a class="page-link" href="' . $url . $t . $querystring . '=' . ($nbpage)  . '">' . $this->last_markup . '</a></li>' . "\n";
                        }
                    }
                }    // Modification of the request
                $pdo_settings_with_limit = $this->addRequestLimit($pdo_settings, $p, $mpp);
                $this->getRecords($pdo_settings_with_limit); // new set of records with LIMIT clause
                $current_page_number = parent::rowCount(); // display 'results n to m on x // start = $start // end = $end // total = $number
                $start = $mpp*($p-1) +1;    // no. per page x current page.
                $end = $start+$current_page_number-1;
            } else {    // if there is only one page
                $this->pagine = '' . "\n";
                $start = 1;    // no. per page x current page.
                $end = $nbre;
            }

            // CRUD admin i18n
            if (defined('PAGINATION_RESULTS')) {
                $this->results = '<p class="text-end text-semibold mb-0">' . PAGINATION_RESULTS . ' ' . $start . ' ' . PAGINATION_TO . ' ' . $end . ' ' . PAGINATION_OF . ' ' . $nbre . '</p>' . "\n";
            } else {
                $this->results = '<p class="text-end text-semibold mb-0">résultats ' . $start . ' à ' . $end . ' sur ' . $nbre . '</p>' . "\n";
            }
            $htmlPagination = '';
            if (!empty($this->results)) {
                $htmlPagination .= '<ul class="' . $this->pagination_class . '">' . "\n";
                $htmlPagination .= $this->pagine . "\n";
                $htmlPagination .= '</ul>' . "\n";
            }
            $htmlPagination .= '<div class="pt-1 pr-3">' . "\n";
            $htmlPagination .= $this->results;
            $htmlPagination .= '</div>' . "\n";

            return $htmlPagination;
        }
    }

    /**
     * Sets form layout options to match your framework
     *
     * @param array $user_options (Optional) An associative array containing the
     *                            options names as keys and values as data.
     * @return $this
     */
    public function setOptions($user_options = array())
    {
        $options = array('active_class', 'disabled_class', 'first_markup', 'pagination_class', 'previous_markup', 'next_markup', 'last_markup', 'rewrite_transition', 'rewrite_extension');
        foreach ($user_options as $key => $value) {
            if (in_array($key, $options)) {
                $this->$key = $value;
            }
        }
    }

    private function addRequestLimit($pdo_settings, $p, $mpp)
    {
        if ($pdo_settings['function'] === 'select') {
            $pdo_settings['extras']['limit'] = (($p-1) *$mpp)  . ',' . $mpp;
        } else {
            $suppr = 'LIMIT';
            $find = strstr($pdo_settings['sql'], $suppr);
            $pdo_settings['sql'] = str_replace($find, '', $pdo_settings['sql']);    // if empty delete the "LIMIT" clause
            $pdo_settings['sql'] .= ' LIMIT ' . (($p-1) *$mpp)  . ',' . $mpp;
        }

        return $pdo_settings;
    }

    private function getRecords($pdo_settings)
    {
        if ($pdo_settings['function'] === 'select') {
            parent::select(
                $pdo_settings['from'],
                $pdo_settings['values'],
                $pdo_settings['where'],
                $pdo_settings['extras'],
                $pdo_settings['debug']
            );
        } else {
            parent::query(
                $pdo_settings['sql'],
                $pdo_settings['placeholders'],
                $pdo_settings['debug']
            );
        }
    }

    private function removePreviousQuerystring($url, $querystring, $rewrite_links)
    {
        if ($rewrite_links === true) {
            $find = array('`' . $this->rewrite_transition . $querystring . '[0-9]+`', '`' . $this->rewrite_extension . '`');
            $replace = array('', '');
        } else {
            $find = array('`\?' . $querystring . '=([0-9]+)&(amp;)?`', '`\?' . $querystring . '=([0-9]+)`', '`&(amp;)?' . $querystring . '=([0-9]+)`');
            $replace = array('?', '', '');
        }
        $url = preg_replace($find, $replace, $url);

        return $url;
    }
}
