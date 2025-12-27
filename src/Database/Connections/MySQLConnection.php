<?php

namespace JiJiHoHoCoCo\IchiORM\Database\Connections;

use Exception;

class MySQLConnection extends Connection
{
    protected function getDSN(array $config)
    {
        if (!isset($config['dbname']) || !isset($config['host'])) {
            throw new Exception("You must add database name and host for MySQL Database Connection", 1);
        }

        $dsn = $config['driver'];

        if (isset($config['unix_socket']) && $config['unix_socket'] !== null) {
            $dsn .= ':unix_socket=' . $config['unix_socket'] .
                ';dbname=' . $config['dbname'];
        } elseif (isset($config['port']) && $config['port'] !== null) {
            $dsn .= ':host=' . $config['host'] .
                ';port=' . $config['port'] .
                ';dbname=' . $config['dbname'];
        } else {
            $dsn .= ':host=' . $config['host'] .
                ';dbname=' . $config['dbname'];
        }

        return $dsn;
    }

    protected function getExtraOptions(array $config)
    {
        $option = null;
        $mode = $charset = $time_zone = false;
        if (isset($config['modes']) && is_array($config['modes'])) {
            $mode = true;
            $option .= "set session sql_mode='" . implode(',', $config['modes']) . "'";
        } elseif (isset($config['strict'])) {
            $mode = true;
            $option .= $config['strict'] == true ? $this->getStrictMode() :
                "set session sql_mode='NO_ENGINE_SUBSTITUTION'";
        }

        if (isset($config['charset']) && $config['charset'] !== null && $mode == true) {
            $charset = true;
            $option .= $mode == true ? ', names ' . $config['charset'] : 'set names ' . $config['charset'];
            $option .= $this->getCollation($config);
        }

        if (isset($config['time_zone']) && $config['time_zone'] !== null) {
            $time_zone = true;
            $sql = "time_zone = '{$config['time_zone']}'";
            $option .= $mode == false && $charset == false ? 'set ' . $sql : ", " . $sql;
        }

        if (isset($config['isolation_level']) && $config['isolation_level'] !== null) {
            $isolationLevelValue = str_replace(' ', '-', $config['isolation_level']);
            $sql = "transaction_isolation = '" . $isolationLevelValue . "'";
            $option .= $mode == false && $charset == false && $time_zone == false ? 'set ' . $sql : ", " . $sql;
        }

        return $option;
    }

    private function getStrictMode()
    {
        return "set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
    }

    private function getCollation(array $config)
    {
        return isset($config['collation']) && $config['collation'] !== null ? ' collate ' . $config['collation'] : null;
    }
}
