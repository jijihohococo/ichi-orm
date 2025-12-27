<?php

namespace JiJiHoHoCoCo\IchiORM\Database\Connections;

use PDO,

Exception;

class SQLServerConnection extends Connection
{
    protected function getDSN(array $config)
    {

        if (!isset($config['dbname']) || !isset($config['host'])) {
            throw new Exception("You must add database name and host for SQL Server Database Connection", 1);
        }

        switch ($config['driver']) {
            case 'sqlsrv':
                return $this->getSqlSrvDsn($config);
                break;
        }

        throw new Exception("The database driver is unavailable");
    }

    protected function getExtraOptions(array $config)
    {
        $options = [];

        if (isset($config['charset'])) {
            if (!defined('\\PDO::SQLSRV_ATTR_ENCODING')) {
                throw new Exception('pdo_sqlsrv extension is required to set charset options');
            }

            $charset = strtolower($config['charset']);
            if (in_array($charset, ['utf8', 'utf-8'], true)) {
                $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
            } elseif ($charset == 'binary') {
                $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_BINARY;
            } elseif ($charset == 'system') {
                $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_SYSTEM;
            } else {
                throw new Exception("Unsupported SQL Server charset: {$config['charset']}");
            }
        }

        return $options ?: null;
    }

    private function getSqlSrvDsn(array $config)
    {

        $dsn = $config['driver'] . ':Server=' . $config['host'];

        if (isset($config['port'])) {
            $dsn .= ',' . $config['port'];
        }

        $dsn .= ';Database=' . $config['dbname'];

        if (isset($config['readOnly']) && $config['readOnly'] == true) {
            $dsn .= ';ApplicationIntent=ReadOnly';
        }

        if (isset($config['pooling']) && $config['pooling'] == false) {
            $dsn .= ';ConnectionPooling=0';
        }

        if (isset($config['application_name'])) {
            $dsn .= ';APP=' . $config['application_name'];
        }

        $encrypt = isset($config['encrypt']) && $config['encrypt'] ? 'true' : 'false';
        $dsn .= ';Encrypt=' . $encrypt;

        $trust = isset($config['trust_server_certificate']) && $config['trust_server_certificate'] ? 'true' : 'false';
        $dsn .= ';TrustServerCertificate=' . $trust;

        if (isset($config['multiple_active_result_sets']) && $config['multiple_active_result_sets'] == false) {
            $dsn .= ';MultipleActiveResultSets=false';
        }

        if (isset($config['transaction_isolation'])) {
            $dsn .= ';TransactionIsolation=' . $config['transaction_isolation'];
        }

        if (isset($config['multi_subnet_failover'])) {
            $dsn .= ';MultiSubnetFailover=' . $config['multi_subnet_failover'];
        }

        if (isset($config['column_encryption'])) {
            $dsn .= ';ColumnEncryption=' . $config['column_encryption'];
        }

        if (isset($config['key_store_authentication'])) {
            $dsn .= ';KeyStoreAuthentication=' . $config['key_store_authentication'];
        }

        if (isset($config['key_store_principal_id'])) {
            $dsn .= ';KeyStorePrincipalId=' . $config['key_store_principal_id'];
        }

        if (isset($config['key_store_secret'])) {
            $dsn .= ';KeyStoreSecret=' . $config['key_store_secret'];
        }

        if (isset($config['login_timeout'])) {
            $dsn .= ';LoginTimeout=' . $config['login_timeout'];
        }

        return $dsn;
    }
}
