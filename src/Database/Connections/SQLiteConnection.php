<?php

namespace JiJiHoHoCoCo\IchiORM\Database\Connections;

use Exception;

class SQLiteConnection extends Connection
{
    protected function getDSN(array $config)
    {
        if (!isset($config['dbname'])) {
            throw new Exception("You must add database name and host for SQL Lite Database Connection", 1);
        }

        $dsn = $config['driver'] . ':' . $config['dbname'];

        return $dsn;
    }

    protected function getExtraOptions(array $config)
    {
        return null;
    }
}
