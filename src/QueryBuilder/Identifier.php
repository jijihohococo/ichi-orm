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

    public static function having(string $field): string
    {
        $column = '[a-zA-Z_][a-zA-Z0-9_]*';
        $qualifiedColumn = "{$column}(\\.{$column})?";

        $columnPattern = "/^{$qualifiedColumn}$/";

        $aggregatePattern =
            "/^(COUNT|SUM|AVG|MIN|MAX)\\(({$qualifiedColumn}|\\*)\\)$/i";

        if (preg_match($columnPattern, $field)) {
            return $field;
        }

        if (preg_match($aggregatePattern, $field)) {
            return $field;
        }

        throw new InvalidArgumentException(
            "Invalid having identifier: {$field}"
        );
    }
}
