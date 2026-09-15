<?php

namespace JiJiHoHoCoCo\IchiORM\QueryBuilder;

use InvalidArgumentException;

final class Identifier
{
    public static function column(string $column): string
    {
        if (
            !preg_match(
                '/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/',
                $column
            )
        ) {
            throw new InvalidArgumentException(
                "Invalid column identifier: {$column}"
            );
        }
        return $column;
    }

    public static function table(string $table): string
    {
        if (
            !preg_match(
                '/^[a-zA-Z_][a-zA-Z0-9_]*$/',
                $table
            )
        ) {
            throw new InvalidArgumentException(
                "Invalid table identifier: {$table}"
            );
        }

        return $table;
    }
}
