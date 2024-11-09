<?php

namespace phpformbuilder\database\pdodrivers;

use PDOException;

/**
*
* LIMITATION:
* -----------
* This class handles the following standard MySQL column types:
*
* tinyint | smallint | mediumint | int | bigint | decimal | float | double | real
* date | datetime | timestamp | time | year
* char | varchar | tinytext | text | mediumtext | longtext
* enum | set | json

* The pgsql data_type of each column is converted to the appropriate standard MySQL column type.
* If it is not covered, it is considered as a " text " column.
*
*/

class Firebird implements PdoInterface
{
    private \PDO $pdo;
    private object $types;
    private array $incompatible_types             = array();
    private array $incompatible_types_list        = array('BLOB');
    private array $unandled_types                 = array();
    private array $decimals                       = array('decimal', 'float', 'double', 'real');
    private array $integers                       = array('smallint', 'int', 'bigint');
    private array $enum                           = array();
    private array $auto_increment_columns         = array();

    // functions names (e.g.: "id_generator") to get the next autoincrement value with "select gen_id(id_generator, 1) FROM rdb$database"
    private array $auto_increment_columns_gen_id  = array();


    public function __construct($pdo)
    {
        if ($pdo instanceof \PDO) {
            $this->pdo = $pdo;
        } else {
            throw new \Exception('Ooops! $pdo is not an instance of PDO.');
        }

        $this->types = \json_decode(\file_get_contents(__DIR__ . '/firebird-types.json'));
    }

    /**
     * convert the columns objects returned by the PDO driver to MySQL style standard values
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
            $object->Field  = $col->{'FIELD_NAME'};
            $object->Type   = $this->convertDataType($col);
            $obj_null = 'YES';
            if ($col->NULL_FLAG) {
                $obj_null = 'NO';
            }
            $object->Null   = $obj_null;

            $object->Key = '';
            if (in_array($col->{'FIELD_NAME'}, $primary_key_columns)) {
                $object->Key = 'PRI';
            }

            $object->Extra  = '';
            if ($col->IDENTITY_TYPE == 'DEFAULT' || $col->IDENTITY_TYPE == 'ALWAYS') {
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
        return 'SELECT
        TRIM(PK.RDB$RELATION_NAME) AS referenced_table_name
       ,TRIM(ISP.RDB$FIELD_NAME) AS referenced_column_name
       ,TRIM(FK.RDB$RELATION_NAME) AS table_name
       ,TRIM(ISF.RDB$FIELD_NAME) AS column_name
       FROM
        RDB$RELATION_CONSTRAINTS PK
       ,RDB$RELATION_CONSTRAINTS FK
       ,RDB$REF_CONSTRAINTS RC
       ,RDB$INDEX_SEGMENTS ISP
       ,RDB$INDEX_SEGMENTS ISF
       WHERE
        FK.RDB$CONSTRAINT_NAME = RC.RDB$CONSTRAINT_NAME
       AND PK.RDB$CONSTRAINT_NAME = RC.RDB$CONST_NAME_UQ
       AND ISP.RDB$INDEX_NAME = PK.RDB$INDEX_NAME
       AND ISF.RDB$INDEX_NAME = FK.RDB$INDEX_NAME
       AND ISP.RDB$FIELD_POSITION = ISF.RDB$FIELD_POSITION
       ORDER BY PK.RDB$RELATION_NAME';
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
        // $data_object is the object from JSON (or from scratch if $col->FIELD_TYPE is not handeled)
        $new_type = '';
        if (\property_exists($this->types, $col->FIELD_TYPE)) {
            $data_tp = $col->FIELD_TYPE;
            $data_object = $this->types->$data_tp;
        } elseif (\in_array($col->FIELD_TYPE, $this->incompatible_types_list)) {
            // register RAW and LOB fields
            $this->incompatible_types[$col->FIELD_NAME] = $col->FIELD_TYPE;
            $data_object = new \stdClass();
            $data_object->type = $col->FIELD_TYPE;
        } else {
            // register the unhandeled type
            $this->unandled_types[$col->FIELD_NAME] = $col->FIELD_TYPE;
            $data_object = new \stdClass();
            $data_object->type = "text";
        }

        if (!\in_array($data_object->type, $this->integers)) {
            $new_type = $data_object->type;
        } else {
            $data_subtype = \strval($col->FIELD_SUB_TYPE);
            $new_type = $data_object->subtypes->$data_subtype;
        }

        if (\array_key_exists($col->FIELD_NAME, $this->enum)) {
            $fieldname = $col->FIELD_NAME;
            $new_type = 'enum' . $this->enum[$fieldname];
        } elseif (\in_array($new_type, $this->decimals) && !empty($col->FIELD_PRECISION) && !empty($col->FIELD_SCALE)) {
            // decimal precision
            $new_type .= '(' . $col->FIELD_PRECISION . ',' . -$col->FIELD_SCALE . ')';
        } elseif (\in_array($new_type, $this->integers)) {
            // integers length
            if (property_exists($data_object, 'range')) {
                $new_type .= '(' . $data_object->range . ')';
            } elseif (!empty($col->FIELD_LENGTH)) {
                $new_type .= '(' . $col->FIELD_LENGTH . ')';
            }
        } elseif (($new_type == 'char' || $new_type == 'varchar') && !empty($col->FIELD_LENGTH)) {
            // character maximum length
            $new_type .= '(' . $col->FIELD_LENGTH . ')';
        }

        return $new_type;
    }

    private function getEnumFieldsValues($table)
    {
        $stmt = $this->pdo->prepare('SELECT r.RDB$TRIGGER_SOURCE
        FROM RDB$TRIGGERS r
        INNER JOIN RDB$CHECK_CONSTRAINTS ON r.RDB$TRIGGER_NAME = RDB$CHECK_CONSTRAINTS.RDB$TRIGGER_NAME
        INNER JOIN RDB$RELATION_CONSTRAINTS ON RDB$CHECK_CONSTRAINTS.RDB$CONSTRAINT_NAME = RDB$RELATION_CONSTRAINTS.RDB$CONSTRAINT_NAME
        WHERE RDB$RELATION_CONSTRAINTS.RDB$CONSTRAINT_TYPE = \'CHECK\' AND r.RDB$RELATION_NAME = \'' . $table . '\'');
        $stmt->execute();

        $records = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $return = array();

        if ($records) {
            // Loop through the query results
            foreach ($records as $rec) {
                // e.g.: CHECK(rating in ('G','PG','PG-13','R','NC-17'))
                if (preg_match('`CHECK\(([a-zA-Z_]+)\sIN\s(\([^)]+\))\)`i', $rec, $out)) {
                    $return[\strtoupper($out[1])] = $out[2];
                }
            }
        }

        return $return;
    }

    private function getAutoIncrementColumns($table)
    {
        // Columns with IDENTITY_TYPE are auto-detected.
        // This function detects the columns that use a generator & trigger.
        $output = array(
            'auto_increment_columns' => array(),
            'auto_increment_columns_gen_id' => array()
        );
        $stmt = $this->pdo->query('SELECT cast(RDB$TRIGGER_BLR as blob character set utf8) AS STRVAL FROM RDB$TRIGGERS WHERE RDB$SYSTEM_FLAG = 0 AND RDB$TRIGGER_TYPE=1 AND RDB$RELATION_NAME=\'' . $table . '\'');

        $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        if ($rows) {
            foreach ($rows as $str) {
                if (preg_match('`blr_field,(?:\s?[0-9]+,\s?[0-9]+),\s?([^\s]+),`', $str, $out_field) && preg_match('`blr_gen_id,(?:\s?[0-9]+),\s?([^\s]+),`', $str, $out_gen_id)) {
                    $field = str_replace(array('\'', ','), '', $out_field[1]);
                    $output['auto_increment_columns'][] = $field;
                    $output['auto_increment_columns_gen_id'][$field] = str_replace(array('\'', ','), '', $out_gen_id[1]);
                }
            }
        }

        return $output;
    }

    /** Get the primary key column name from a given table
     * @param string $table the table name
     * @return mixed the primary key column | false if no primary column found
     */
    private function getPrimaryKeys($table)
    {
        $stmt = $this->pdo->query('SELECT TRIM(SG.RDB$FIELD_NAME) AS FIELD_NAME FROM RDB$INDICES IX
        LEFT JOIN RDB$INDEX_SEGMENTS SG ON IX.RDB$INDEX_NAME = SG.RDB$INDEX_NAME
        LEFT JOIN RDB$RELATION_CONSTRAINTS RC ON RC.RDB$INDEX_NAME = IX.RDB$INDEX_NAME
        WHERE RC.RDB$RELATION_NAME = \'' . strtoupper($table) . '\' AND RC.RDB$CONSTRAINT_TYPE = \'PRIMARY KEY\'');
        $pks = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        if ($pks) {
            return $pks;
        }

        return false;
    }
}
