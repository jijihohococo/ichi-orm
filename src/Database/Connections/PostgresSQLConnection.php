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

        $dsn = $config['driver'] . ':';

        $dsn .= isset($config['host']) ? 'host=' . $config['host'] . ';' : '';

        $dsn .= 'dbname=' . $config['dbname'];

        if (isset($config['port'])) {
            $dsn .= ';port=' . $config['port'];
        }

        return $this->getSSLOptions($config, $dsn);
    }

    protected function getExtraOptions(array $config)
    {
        $option = null;
        $charset = $time_zone = $application_name = $synchronous_commit = false;
        if (isset($config['charset']) && $config['charset'] !== null) {
            $charset = true;
            $option .= 'set names ' . $config['charset'];
        }

        if (isset($config['time_zone']) && $config['time_zone'] !== null) {
            $time_zone = true;
            $option .= $charset == true ? ", time_zone = '{$config['time_zone']}'" : "set time_zone = '{$config['time_zone']}'";
        }

        if (isset($config['application_name']) && $config['application_name'] !== null) {
            $application_name = true;
            $option .= $charset == true && $time_zone == true ? ', application_name to ' . $config['application_name'] : 'set application_name to ' . $config['application_name'];
        }

        if (isset($config['synchronous_commit']) && $config['synchronous_commit'] !== null) {
            $synchronous_commit = true;
            $option .= $charset == true && $time_zone == true && $application_name == true ? ', set synchronous_commit to ' . $config['synchronous_commit'] : 'set synchronous_commit to ' . $config['synchronous_commit'];
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
