<?php

namespace ryzerbe\core\provider;

class MySQLProvider implements RyZerProvider{

    /**
     * OLD FUNCTION OF RUSHEROASE
     * @param string $insert
     * @return bool
     */
    public static function checkInsert(string $insert): bool{
        $filters = [
            "§",
            "$".
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

        $result = $insert;
        foreach($filters as $filter){
            $result = str_replace($filter, "", $insert);
        }

        return $insert === $result;
    }
}