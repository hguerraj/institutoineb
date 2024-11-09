<?php
namespace phpformbuilder\database\pdodrivers;

interface PdoInterface
{
    public function __construct($pdo);
    public function convertColumns($table, $cols);
    public function getRelationsQuery($database);
    public function getIncompatibleTypes();
    public function getUnhandeledTypes();
}
