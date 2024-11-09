<?php

namespace phpformbuilder\database\pdodrivers;

use PDOException;

class Mysql implements PdoInterface
{
    private \PDO $pdo;

    // types from generator/class/Generator.php
    private $valid_db_types = array('tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'decimal', 'float', 'double', 'real', 'date', 'datetime', 'timestamp', 'time', 'year', 'char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'enum', 'set', 'json');

    private array $incompatible_types = array();
    private array $incompatible_types_list = array('binary', 'varbinary', 'blob');
    private array $unandled_types     = array();

    public function __construct($pdo)
    {
        if ($pdo instanceof \PDO) {
            $this->pdo = $pdo;
        } else {
            throw new \Exception('Ooops! $pdo is not an instance of PDO.');
        }
    }

    /**
     * No need to convert the MySQL columns, this function just returns them as is.
     * @param array $cols
     * @return array
     */
    public function convertColumns($table, $cols)
    {
        foreach ($cols as $col) {
            $pos = strpos($col->Type, '(');
            if ($pos === false) {
                $column_type_short = $col->Type;
            } else {
                $column_type_short = trim(substr($col->Type, 0, $pos));
            }
            if (\in_array($column_type_short, $this->incompatible_types_list)) {
                // register RAW and LOB fields
                $this->incompatible_types[$col->Field] = $col->Type;
            } elseif (!\in_array($column_type_short, $this->valid_db_types)) {
                // convert to text if the type is not handled
                $col->Type = 'text';
            }
        }

        return $cols;
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
        return 'SELECT `TABLE_NAME` AS table_name, `COLUMN_NAME` AS column_name, `REFERENCED_TABLE_NAME` AS referenced_table_name, `REFERENCED_COLUMN_NAME` AS referenced_column_name FROM `information_schema`.`KEY_COLUMN_USAGE` WHERE `CONSTRAINT_SCHEMA` = \'' . $database . '\' AND `REFERENCED_TABLE_SCHEMA` IS NOT NULL AND `REFERENCED_TABLE_NAME` IS NOT NULL AND `REFERENCED_COLUMN_NAME` IS NOT NULL';
    }

    public function getIncompatibleTypes()
    {
        return $this->incompatible_types;
    }

    public function getUnhandeledTypes()
    {
        return $this->unandled_types;
    }
}
