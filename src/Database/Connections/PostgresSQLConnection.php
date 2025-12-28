<?php

namespace JiJiHoHoCoCo\IchiORM\Database\Connections;

use Exception;

class PostgresSQLConnection extends Connection
{
    protected function getDSN(array $config)
    {

        if (!isset($config['dbname']) || !isset($config['host'])) {
            throw new Exception("You must add database name and host for Postgres SQL Database Connection", 1);
        }

        $dsn = "{$config['driver']}:";

        if (isset($config['host'])) {
            $dsn .= "host={$config['host']};";
        }

        $dsn .= "dbname={$config['dbname']}";
        if (isset($config['port'])) {
            $dsn .= ";port={$config['port']}";
        }

        return $this->getSSLOptions($config, $dsn);
    }

    protected function getExtraOptions(array $config)
    {
        $option = null;
        $charset = $time_zone = $application_name = false;
        if (isset($config['charset']) && $config['charset'] !== null) {
            $charset = true;
            $option .= "set names '{$config['charset']}'";
        }

        if (isset($config['time_zone']) && $config['time_zone'] !== null) {
            $time_zone = true;
            $sql = "timezone = '{$config['time_zone']}'";
            $option .= $charset == true ? ", {$sql}" : "set {$sql}";
        }

        if (isset($config['application_name']) && $config['application_name'] !== null) {
            $application_name = true;
            $sql = "application_name = '{$config['application_name']}'";
            $option .= $charset == true && $time_zone == true ? ", {$sql}" : "set {$sql}";
        }

        if (isset($config['synchronous_commit']) && $config['synchronous_commit'] !== null) {
            $sql = "set synchronous_commit = '{$config['synchronous_commit']}'";
            $option .= $charset == true && $time_zone == true && $application_name == true ? ", {$sql}" : $sql;
        }

        return $option;
    }

    private function getSSLOptions(array $config, $dsn)
    {
        foreach (['sslmode', 'sslcert', 'sslkey', 'sslrootcert'] as $option) {
            if (isset($config[$option])) {
                $dsn .= ';' . $option . '=' . $config[$option];
            }
        }
        return $dsn;
    }
}
