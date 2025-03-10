<?php
namespace common;

class Utils
{
    /* Components */

    public static function alert($content, $class)
    {
        if (DEMO === true) {
            $content = '<span class="badge text-bg-warning me-2">DEMO</span>' . $content;
        }
        $alert = '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert"><span class="text-semibold">' . $content . '</span><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>' . "\n";

        return $alert;
    }

    /**
     * @param  string $title            card title
     * @param  string $classname        Boootstrap card class (bg-success, bg-primary, bg-warning, bg-danger)
     * @param  string $heading_elements separated comma list : collapse, close
     */
    public static function startCard($title, $classname, $header_classname = '', $heading_elements = '', $has_body = true)
    {
        $start_card = '<div class="card ' . $classname . ' mb-4">' . "\n";
        if (!empty($title)) {
            $start_card .= '    <div class="card-header ' . $header_classname . ' align-items-center">' . $title . "\n";
            if (!empty($heading_elements)) {
                $heading_elements = array_map('trim', explode(',', $heading_elements));
                $start_card .= '        <div class="heading-elements">' . "\n";
                if (in_array('collapse', $heading_elements)) {
                    $random_id = 'a' . uniqid();
                    $start_card .= '               <a class="dropdown-toggle fs-5 text-dark" data-bs-toggle="collapse" href="#' . $random_id . '" role="button" aria-expanded="false" aria-controls="' . $random_id . '"></a>' . "\n";
                }
                if (in_array('close', $heading_elements)) {
                    $start_card .= '               <button type="button" class="btn-close" aria-label="Close"></button>' . "\n";
                }
                $start_card .= '           </ul>' . "\n";
                $start_card .= '        </div>' . "\n";
            }
            $start_card .= '    </div>' . "\n";
        }
        if ($has_body === true) {
            $collapsed = '';
            if (preg_match('`card-collapsed`', $classname)) {
                $collapsed = ' collapse';
            }
            $start_card .= '    <div class="card-body' . $collapsed . '"';
            if (isset($random_id)) {
                $start_card .= ' id="' . $random_id . '"';
            }
            $start_card .= '>' . "\n";
        }

        return $start_card;
    }

    public static function endCard($has_body = true)
    {
        $end_card = '';
        if ($has_body === true) {
            $end_card .= '    </div>' . "\n";
        }
        $end_card .= '</div>' . "\n";

        return $end_card;
    }

    /**
     * @param  string $title            panel title
     * @param  string $classname        Boootstrap panel class (bg-success, bg-primary, bg-warning, bg-danger)
     * @param  string $heading_elements separated comma list : collapse, reload, close
     * @param  string $content          panel body html content
     */
    public static function alertCard($title, $classname, $header_classname = '', $heading_elements = '', $content = '')
    {
        if (!empty($content)) {
            $has_body = true;
        } else {
            $has_body = false;
        }
        $card = self::startCard($title, $classname, $header_classname, $heading_elements, $has_body);
        $card .= $content;
        $card .= self::endCard($has_body);

        return $card;
    }

    /* Numbers */

    public static function formatNumber($nbre)
    {
        $nbre = number_format($nbre, 2, '.', ' ');

        /* on supprime '.00' à la fin si nécessaire */

        if (preg_match('`([0-9\s]+)\.00$`', $nbre, $out)) {
            $nbre = $out[1];
        }

        return $nbre;
    }

    public static function multiple($i, $nb)
    {
        $calcul = (round($i / $nb) - ($i / $nb));
        if ($calcul == "0") {
            return true; // $i est multiple de $nb
        } else {
            return false; // $i n'est pas multiple de $nb
        }
    }

    public static function pair($nb)
    {
        $calcul = (round($nb / 2) - ($nb / 2));
        if ($calcul == "0") {
            return true; // $nb est pair
        } else {
            return false; // $nb n'est pas impair
        }
    }

    /* Dates */

    /**
     * isValidTimeStamp
     * from https://stackoverflow.com/questions/2524680/check-whether-the-string-is-a-unix-timestamp
     *
     * @param  mixed $timestamp
     * @return void
     */
    public static function isValidTimeStamp($timestamp)
    {
        return ((intval($timestamp) <= PHP_INT_MAX) && (intval($timestamp) >= ~PHP_INT_MAX));
    }

    /*[UNUSED] public static function pickerdateToMysqlDate($date, $pickerdate_format, $fieldtype)
    {
        $find    = array('a.m.', 'p.m.');
        $replace = array('AM', 'PM');
        $date = str_replace($find, $replace, $date);

        try {
            if (class_exists('IntlDateFormatter')) {
                // ICU values
                $find    = array('dddd', 'ddd', 'mmmm', 'mmm', 'mm', 'm', 'i', 'A');
                $replace = array('eeee', 'eee', 'MMMM', 'MMM', 'MM', 'M', 'm', 'a');

                $icu_format = str_replace($find, $replace, $pickerdate_format);

                // If hour is in 24h format and the format uses AM/PM we
                if (strpos($icu_format, 'H') > 0 && strpos($icu_format, 'a') > 0) {
                    $icu_format = str_replace('H', 'h', $icu_format);
                }

                $fmt = new \IntlDateFormatter(
                    str_replace('_', '-', DATETIMEPICKERS_LANG),
                    \IntlDateFormatter::FULL,
                    \IntlDateFormatter::FULL,
                    date_default_timezone_get(),
                    \IntlDateFormatter::GREGORIAN,
                    $icu_format
                );
                $ts = $fmt->parse($date);

                switch ($fieldtype) {
                    case 'date':
                        $mysql_date = date('Y-m-d', $ts);
                        break;
                    case 'month':
                        $mysql_date = date('m', $ts);
                        break;
                    case 'datetime':
                        $mysql_date = date('Y-m-d H:i:s', $ts);
                        break;
                    case 'time':
                        $mysql_date = date('H:i:s', $ts);
                        break;
                    default:
                        $mysql_date = date('Y-m-d', $ts);
                        break;
                }

                return $mysql_date;
            } else {
                return $date;
            }
        } catch (\Exception $e) {
            // echo  $e->getMessage();
            return WRONG_DATE_FORMAT;
        }
    } */

    public static function dateUsToFr($date)
    {
        sscanf($date, "%4s-%2s-%2s", $y, $mo, $d);

        return $d.'-'.$mo.'-'.$y;
    }

    public static function dateTimeToDate($dateTime)
    {
        $retour = preg_replace('/([0-9]{4}-[0-9]{2}-[0-9]{2}) [0-9]{2}:[0-9]{2}:[0-9]{2}/', '$1', $dateTime);

        return $retour;
    }

    public static function dateTimeToTime($dateTime)
    {
        $retour = preg_replace('/[0-9]{4}-[0-9]{2}-[0-9]{2} ([0-9]{2}:[0-9]{2}:[0-9]{2})/', '$1', $dateTime);

        return $retour;
    }

    public static function addYear()
    {
        $format = 'Y-m-d';
        $aujourdhui = date($format);
        $tab = explode('-', $aujourdhui);
        $tab[0] = $tab[0]+1;
        $dansUnAn = $tab[0] . '-' . $tab[1] . '-' . $tab[2];

        return $dansUnAn;
    }

    public static function translateMonth($number)
    {
        // pour les répertoires des factures, pas de multilingue
        $tabMois = array(1 => 'janvier', 2 => 'fevrier', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'aout', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'decembre');

        return $tabMois[(int) $number]; // (int) supprime les zéros initiaux
    }

    /* Strings */

    public static function sanitize($var)
    {
        $ancienNom = trim($var);
        $charset = 'utf-8';
        $nouveauNom = htmlentities($ancienNom, ENT_NOQUOTES, $charset);
        $nouveauNom = preg_replace('#\&([A-za-z])(?:acute|cedil|circ|grave|ring|tilde|uml)\;#', '\1', $nouveauNom);
        $nouveauNom = preg_replace('#\&([A-za-z]{2})(?:lig)\;#', '\1', $nouveauNom);    // pour les ligatures e.g. '&oelig;'
        $nouveauNom = preg_replace('#\&[^;]+\;#', '', $nouveauNom);    // supprime les autres caractères
        $nouveauNom = preg_replace('`[ &~"#{( \'\[|\\^@)\]=}$¤*µ%,;:!?/§.]+`', '-', $nouveauNom);
        while (preg_match('`--`', $nouveauNom)) {
            $nouveauNom = preg_replace('`--`', '-', $nouveauNom);
        }
        $nouveauNom = trim(strtolower($nouveauNom), '-');

        return $nouveauNom;
    }

    public static function formatName($var) // for files, keep extension dot
    {
        $ancienNom = trim($var);
        $charset = 'utf-8';
        $nouveauNom = htmlentities($ancienNom, ENT_NOQUOTES, $charset);
        $nouveauNom = preg_replace('#\&([A-za-z])(?:acute|cedil|circ|grave|ring|tilde|uml)\;#', '\1', $nouveauNom);
        $nouveauNom = preg_replace('#\&([A-za-z]{2})(?:lig)\;#', '\1', $nouveauNom);    // pour les ligatures e.g. '&oelig;'
        $nouveauNom = preg_replace('#\&[^;]+\;#', '', $nouveauNom);    // supprime les autres caractères
        $nouveauNom = preg_replace('`[ &~"#{( \'\[|\\^@)\]=}$¤*µ%,;:!?/§]+`', '-', $nouveauNom);
        while (preg_match('`--`', $nouveauNom)) {
            $nouveauNom = preg_replace('`--`', '-', $nouveauNom);
        }
        $nouveauNom = strtolower($nouveauNom);

        return $nouveauNom;
    }

    public static function hexaEncode($chaine) // encode email addresses
    {
        $longueur=strlen($chaine);
        $retour = '';
        for ($i=0; $i<$longueur; $i++) {
            $retour .= '&#x' . bin2hex(substr($chaine, $i, 1)) . ';';
        }

        return $retour;
    }

    /**
     * transform a lowercase string with underscores or hyphens to camelCase
     * @param  [type] $str
     * @param  array  $noStrip non-spacing additional characters
     * @return upperCamelCased string
     */
    public static function camelCase($str, array $noStrip = array())
    {
        $str = self::sanitize($str);
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-zA-Z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);

        return lcfirst($str);
    }

    public static function replaceAccents($str, $charset = 'utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères

        return $str;
    }

    public static function truncateString($chaine, $nbreDeCaracteres)
    {
        if (strlen($chaine) <= $nbreDeCaracteres) {
            return $chaine;
        }
        $texte = wordwrap($chaine, $nbreDeCaracteres, ' coupure '); //on ajoute le mot 'coupure' après le dernier mot inclus jusqu'à n caractères,
        $pos = strpos($texte, ' coupure'); //on récupère la position du mot 'coupure'
        $texte = substr($texte, 0, $pos); //on coupe ce qui est après

        return $texte . ' ...';
    }

    /* Data */

    public static function replaceWithBooleen($value)
    {
        if (!empty($value)) {
            return '<i class="' . ICON_CHECKMARK . ' icon-lg text-success"></i>';
        } else {
            return '<i class="' . ICON_CANCEL . ' icon-md text-danger"></i>';
        }
    }

    /* Attributes & classes */

    /**
    * Returns linearised attributes.
    * @param string $attr The element attributes
    * @return string Linearised attributes
    *                Exemple : size=30, required=required => size="30" required="required"
    */
    public static function getAttributes($attr)
    {
        if (empty($attr)) {
            return '';
        } else {
            $attr = preg_replace('`\s*=\s*`', '="', $attr) .  '"'; // adding quotes
            $attr = preg_replace_callback('`(.){1},\s*`', array('self', 'replaceCallback'), $attr);

            return ' ' . $attr;
        }
    }

    /**
    * Add new class to $attr.(see options).
    *
    * @param string $newclassname The new class
    * @param string $attr The element attributes
    * @return string $attr including new class.
    */
    public static function addClass($newclassname, $attr)
    {
        if (preg_match('`class="([^"]+)"`', $attr, $out)) {
            // if $attr already contains a class we keep it and add newclassname
            $new_class =  'class="' . $out[1] . ' ' . $newclassname . '"';

            return preg_replace('`class="([^"]+)"`', $new_class, $attr);
        } else {
            // if $attr contains no class we add elementClass
            return $attr . ' class="' . $newclassname . '"';
        }
    }

    /**
    * Used for getAttributes regex.
    */
    protected static function replaceCallback($motif)
    {
        /* if there's no antislash before the comma */
        if (preg_match('`[^\\\]`', $motif[1])) {
            return $motif[1] . '" ';
        } else {
            return ',';
        }
    }

    /* Arrays */

    /**
     * Look for a value in an associative array inside an indexed array
     * @param  array  $indexed_array
     * @param  string $key           key to look for
     * @param  string $value         value to match key
     * @return array                 indexes found in indexed array
     */
    public static function findInIndexedArray($indexed_array, $key, $value)
    {
        $index_array = array();
        foreach ($indexed_array as $index => $assoc_array) {
            if ($assoc_array[$key] === $value) {
                $index_array[] = $index;
            }
        }

        return $index_array;
    }
}
