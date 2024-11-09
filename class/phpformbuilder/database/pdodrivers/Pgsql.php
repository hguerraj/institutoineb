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

class Pgsql implements PdoInterface
{
    private \PDO $pdo;
    private object $types;
    private array $incompatible_types       = array();
    private array $incompatible_types_list  = array();
    private array $unandled_types           = array();
    private array $decimals                 = array('decimal', 'float', 'double', 'real');
    private array $integers                 = array('smallint', 'int', 'bigint');


    public function __construct($pdo)
    {
        if ($pdo instanceof \PDO) {
            $this->pdo = $pdo;
        } else {
            throw new \Exception('Ooops! $pdo is not an instance of PDO.');
        }

        $this->types = \json_decode(\file_get_contents(__DIR__ . '/pgsql-types.json'));
    }

    /**
     * convert the columns objects returned by the PDO driver to MySQL style standard values
     * @param array $cols
     * @return array
     */
    public function convertColumns($table, $cols)
    {
        $mysql_style_columns = array();

        // A table can have at most one primary key
        // https://www.postgresql.org/docs/15/ddl-constraints.html#DDL-CONSTRAINTS-PRIMARY-KEYS
        $primary_key_column = $this->getPrimaryKey($table);

        if (!$primary_key_column) {
            throw new \Exception('The table "' . $table . '" has no primary column.');
        }

        foreach ($cols as $col) {
            $object = new \stdClass();
            $object->Field  = $col->column_name;
            $object->Type   = $this->convertDataType($col);
            $object->Null   = $col->is_nullable;
            if ($col->column_name === $primary_key_column) {
                $object->Key = 'PRI';
            } else {
                $object->Key    = '';
            }
            $object->Extra  = $this->getExtra($col);

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
        tc.constraint_name, tc.table_name AS table_name, kcu.column_name AS column_name,
        ccu.table_name AS referenced_table_name,
        ccu.column_name AS referenced_column_name
        FROM
        information_schema.table_constraints AS tc
        JOIN information_schema.key_column_usage AS kcu
          ON tc.constraint_name = kcu.constraint_name
        JOIN information_schema.constraint_column_usage AS ccu
          ON ccu.constraint_name = tc.constraint_name
        WHERE constraint_type = \'FOREIGN KEY\';';
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
        // $data_object is the object from JSON (or from scratch if $col->data_type is not handeled)
        $new_type = '';
        if (property_exists($this->types, $col->data_type)) {
            $data_tp = $col->data_type;
            $data_object = $this->types->$data_tp;
        } elseif (\in_array($col->data_type, $this->incompatible_types_list)) {
            // register RAW and LOB fields
            $this->incompatible_types[$col->column_name] = $col->data_type;
            $data_object = new \stdClass();
            $data_object->type = $col->data_type;
        } else {
            // register the unhandeled type
            $this->unandled_types[$col->column_name] = $col->data_type;
            $data_object = new \stdClass();
            $data_object->type = "text";
        }

        $new_type = $data_object->type;

        if (\in_array($data_object->type, $this->decimals) && $col->numeric_precision_radix === 10 && !empty($col->numeric_precision) && !empty($col->numeric_scale)) {
            // decimal precision
            $new_type .= '(' . $col->numeric_precision . ',' . $col->numeric_scale . ')';
        } elseif (\in_array($data_object->type, $this->integers)) {
            // integers length
            if ($col->numeric_precision_radix === 2 && property_exists($data_object, 'range')) {
                $new_type .= '(' . $data_object->range . ')';
            } elseif ($col->numeric_precision_radix === 10 && !empty($col->character_maximum_length)) {
                $new_type .= '(' . $col->character_maximum_length . ')';
            }
        } elseif (($data_object->type == 'char' || $data_object->type == 'varchar') && !empty($col->character_maximum_length)) {
            // character maximum length
            $new_type .= '(' . $col->character_maximum_length . ')';
        } elseif ($data_object->type == 'enum' && !empty($col->udt_name)) {
            // enum
            $enum_values = $this->getEnumValues($col->udt_name);
            if (count($enum_values) > 0) {
                $new_type .= '(\'' . implode("','", $enum_values) . '\')';
            } else {
                $new_type = "text";
            }
        }

        return $new_type;
    }

    private function getEnumValues($col_udt_name)
    {
        $stmt = $this->pdo->prepare('SELECT unnest(enum_range(NULL::' . $col_udt_name . '))');
        $stmt->execute();

        $records = $stmt->fetchAll();

        $return = array();

        // Loop through the query results
        foreach ($records as $element) {
            $return[] = $element['unnest'];
        }

        return $return;
    }

    private function getExtra($col)
    {
        $serials = array('smallserial', 'serial', 'bigserial');
        if (in_array($col->data_type, $serials) || (!is_null($col->column_default) && \strpos($col->column_default, 'nextval') !== false)) {
            return 'auto_increment';
        }

        return '';
    }

    /** Get the primary key column name from a given table
     * http://wiki.postgresql.org/wiki/Retrieve_primary_key_columns
     * @param string $table the table name
     * @return mixed the primary key column | false if no primary column found
     */
    private function getPrimaryKey($table)
    {
        $stmt = $this->pdo->query('SELECT a.attname FROM pg_index i JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey) WHERE i.indrelid=\'' . $table . '\'::regclass AND i.indisprimary');
        $row = $stmt->fetch(\PDO::FETCH_OBJ);

        if (is_object($row)) {
            return $row->attname;
        }

        return false;
    }
}
