<?php

namespace ryzerbe\core\provider;

use function str_contains;

class MySQLProvider implements RyZerProvider{
    public static function checkInsert(string $insert): bool{
        $filters = [
            "§",
            "$",
            ".",
            ",",
            "-",
            "(",
            ")",
            '"',
            "'",
            ";",
            "'",
            "%",
            "DELETE",
            "INSERT",
            "DROP",
            "SELECT",
            "*"
        ];
        foreach($filters as $filter) {
            if(str_contains($insert, $filter)) return false;
        }
        return true;
    }
}