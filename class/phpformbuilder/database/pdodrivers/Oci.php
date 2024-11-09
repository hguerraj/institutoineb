<?php

namespace phpformbuilder\database\pdodrivers;

use PDOException;

class Oci implements PdoInterface
{
    private \PDO $pdo;
    private array $incompatible_types = array();
    private array $incompatible_types_list = array('RAW', 'LONG RAW', 'CLOB', 'NCLOB', 'BLOB', 'BFILE');
    private array $unandled_types     = array();
    private array $decimals           = array('dec', 'decimal', 'float', 'double');

    public function __construct($pdo)
    {
        if ($pdo instanceof \PDO) {
            $this->pdo = $pdo;
        } else {
            throw new \Exception('Ooops! $pdo is not an instance of PDO.');
        }

        $this->types = \json_decode(\file_get_contents(__DIR__ . '/oci-types.json'));
    }

    /**
     * No need to convert the MySQL columns, this function just returns them as is.
     * @param array $cols
     * @return array
     */
    public function convertColumns($table, $cols)
    {
        $mysql_style_columns = array();

        $primary_key_columns = $this->getPrimaryKeys($table);

        if (!$primary_key_columns) {
            throw new \Exception('The table "' . $table . '" has no primary column.');
        }

        $output = $this->getAutoIncrementColumns($table);
        $this->auto_increment_columns = $output['auto_increment_columns'];
        $this->auto_increment_columns_gen_id = $output['auto_increment_columns_gen_id'];
        $this->enum = $this->getEnumFieldsValues($table);
        foreach ($cols as $col) {
            $object = new \stdClass();
            $object->Field  = $col->{'COLUMN_NAME'};
            $object->Type   = $this->convertDataType($col);
            $obj_null = 'YES';
            if ($col->NULLABLE === 'N') {
                $obj_null = 'NO';
            }
            $object->Null   = $obj_null;

            $object->Key = '';
            if (in_array($col->{'COLUMN_NAME'}, $primary_key_columns)) {
                $object->Key = 'PRI';
            }

            $object->Extra  = '';
            if ($col->IDENTITY_COLUMN == 'YES') {
                $object->Extra = 'auto_increment';
            } elseif (in_array($object->Field, $this->auto_increment_columns)) {
                $object->Extra = 'auto_increment gen_id%' . $this->auto_increment_columns_gen_id[$object->Field] . '%';
            }

            $mysql_style_columns[] = $object;
        }

        return $mysql_style_columns;
    }

    /**
     * Get the appropriate query to retrieve the database relations
     * The query must return the results in 4 columns:
     * table_name, column_name, referenced_table_name, referenced_column_name
     * @param mixed $database the database name
     * @return string the query
     */
    public function getRelationsQuery($database)
    {
        return 'SELECT a.table_name AS table_name, a.column_name AS column_name, a.constraint_name, b.table_name AS referenced_table_name, b.column_name AS referenced_column_name FROM all_cons_columns a JOIN user_constraints c ON a.owner = c.owner AND a.constraint_name = c.constraint_name join all_cons_columns b on c.owner = b.owner and c.r_constraint_name = b.constraint_name WHERE c.constraint_type = \'R\'';
    }

    public function getIncompatibleTypes()
    {
        return $this->incompatible_types;
    }

    public function getUnhandeledTypes()
    {
        return $this->unandled_types;
    }

    private function convertDataType($col)
    {
        // $new_type is the output string
        // $data_object is the object from JSON (or from scratch if $col->DATA_TYPE is not handeled)
        $new_type = '';
        if (\property_exists($this->types, $col->DATA_TYPE)) {
            $data_tp = $col->DATA_TYPE;
            $data_object = $this->types->$data_tp;
        } elseif (\in_array($col->DATA_TYPE, $this->incompatible_types_list)) {
            // register RAW and LOB fields
            $this->incompatible_types[$col->COLUMN_NAME] = $col->DATA_TYPE;
            $data_object = new \stdClass();
            $data_object->type = $col->DATA_TYPE;
        } else {
            // register the unhandeled type
            $this->unandled_types[$col->COLUMN_NAME] = $col->DATA_TYPE;
            $data_object = new \stdClass();
            $data_object->type = "text";
        }

        if ($data_object->type === 'NUMBER') {
            $new_type = $this->getNumberType($col);
        } else {
            $new_type = $data_object->type;
        }

        if (\array_key_exists($col->COLUMN_NAME, $this->enum)) {
            $fieldname = $col->COLUMN_NAME;
            $new_type = 'enum' . $this->enum[$fieldname];
        } elseif (\in_array($new_type, $this->decimals)) {
            // decimal precision
            $new_type .= $this->getDecimalPrecision($col);
        } elseif (($new_type == 'char' || $new_type == 'varchar') && !empty($col->DATA_LENGTH)) {
            // character maximum length
            $new_type .= '(' . $col->DATA_LENGTH . ')';
        }

        return $new_type;
    }

    private function getAutoIncrementColumns($table)
    {
        // Columns with IDENTITY_TYPE are auto-detected.
        // This function detects the columns that use a generator & trigger.
        $output = array(
            'auto_increment_columns' => array(),
            'auto_increment_columns_gen_id' => array()
        );
        $stmt = $this->pdo->query('SELECT TRIGGER_BODY FROM ALL_TRIGGERS WHERE TABLE_NAME = \'' . \strtoupper($table) . '\' AND TRIGGER_TYPE = \'BEFORE EACH ROW\' AND TRIGGERING_EVENT = \'INSERT\'');

        $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        if ($rows) {
            foreach ($rows as $str) {
                if (preg_match('`SELECT ([a-zA-Z0-9_]+).nextval INTO :NEW.([a-zA-Z0-9_]+)`mi', $str, $out)) {
                    $field = \strtoupper($out[2]);
                    $output['auto_increment_columns'][] = $field;
                    $output['auto_increment_columns_gen_id'][$field] = \strtoupper($out[1]);
                }
            }
        }

        return $output;
    }

    private function getDecimalPrecision($col)
    {
        $precision  = $col->DATA_PRECISION;
        $scale      = $col->DATA_SCALE;
        $output     = '';

        if (empty($precision)) {
            $output     = '';
        } elseif (empty($scale)) {
            $output = '(' . $precision . ')';
        } else {
            $output = '(' . $precision . ', ' . $scale . ')';
        }

        return $output;
    }

    private function getEnumFieldsValues($table)
    {
        $stmt = $this->pdo->prepare('SELECT SEARCH_CONDITION
        FROM user_constraints
        WHERE table_name = \'' . $table . '\'
        AND constraint_type = \'C\'');
        $stmt->execute();

        $records = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $return = array();

        if ($records) {
            // Loop through the query results
            foreach ($records as $rec) {
                // e.g.: rating in ('G','PG','PG-13','R','NC-17')
                if (preg_match('`([a-zA-Z_]+)\sIN\s(\([^)]+\))`i', $rec, $out)) {
                    $return[\strtoupper($out[1])] = $out[2];
                }
            }
        }

        return $return;
    }

    /**
     * https://www.convert-in.com/docs/ora2sql/types-mapping.htm
     * https://cloud.google.com/solutions/migrating-oracle-users-to-mysql-data-users-tables#oracle_to_mysql_data_type_conversion
     * @param mixed $col
     * @return $output - the column type
     */
    private function getNumberType($col)
    {
        $precision  = $col->DATA_PRECISION;
        $scale      = $col->DATA_SCALE;
        $output     = '';

        if (empty($precision)) {
            $output     = 'int';
        } elseif (empty($scale)) {
            if ($precision <= 1 && $precision < 3) {
                $output = 'tinyint';
            } elseif ($precision <= 3 && $precision < 5) {
                $output = 'smallint';
            } elseif ($precision <= 5 && $precision < 9) {
                $output = 'int';
            } elseif ($precision <= 9 && $precision < 19) {
                $output = 'bigint';
            } elseif ($precision <= 19 && $precision < 38) {
                $output = 'decimal(' . $precision . ')';
            }
        } else {
            $output = 'decimal(' . $precision . ', ' . $scale . ')';
        }

        return $output;
    }

    /** Get the primary key column name from a given table
     * @param string $table the table name
     * @return mixed the primary key column | false if no primary column found
     */
    private function getPrimaryKeys($table)
    {
        $stmt = $this->pdo->query('SELECT DISTINCT
        COLUMN_NAME
    FROM DBA_IND_COLUMNS WHERE TABLE_NAME = \'' . \strtoupper($table) . '\' AND INDEX_NAME = (SELECT CONSTRAINT_NAME
            FROM user_constraints
            WHERE table_name = \'' . \strtoupper($table) . '\'
            AND constraint_type = \'P\')');
        $pks = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        if ($pks) {
            return $pks;
        }

        return false;
    }
}
