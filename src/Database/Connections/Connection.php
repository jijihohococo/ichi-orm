<?php

namespace JiJiHoHoCoCo\IchiORM\Database\Connections;

use JiJiHoHoCoCo\IchiORM\Database\Connections\DetectableDatabaseError;
use PDO,

Exception;

abstract class Connection
{
    use DetectableDatabaseError;

    private $defaultOptions = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    abstract protected function getDSN(array $config);

    abstract protected function getExtraOptions(array $config);

    public function getConnection(array $config)
    {
        if (strpos(get_class($this), 'SQLServerConnection') !== false) {
            unset($this->defaultOptions[PDO::ATTR_EMULATE_PREPARES]);
        }
        try {
            return $this->connect($config, $this->defaultOptions);
        } catch (Exception $e) {
            if ($this->checkDetectableErrors($e->getMessage())) {
                return $this->connect($config, $this->defaultOptions);
            }
            throw new Exception($e->getMessage());
        }
    }

    private function connect(array $config, array $option)
    {
        if (!isset($config['user_name']) || !isset($config['user_password'])) {
            throw new Exception("You need to set your database user name and password", 1);
        }
        if (isset($config['options'])) {
            $option = $config['options'] + $option;
        }
        $extraOptions = $this->getExtraOptions($config);

        if (is_array($extraOptions)) {
            $option = $extraOptions + $option; // driver-specific PDO options
        }

        $sqlSrvBinary = false;
        if ($extraOptions !== null && 
        isset($extraOptions[PDO::SQLSRV_ATTR_ENCODING]) &&
        $extraOptions[PDO::SQLSRV_ATTR_ENCODING] === PDO::SQLSRV_ENCODING_BINARY) {
            unset($option[PDO::SQLSRV_ATTR_ENCODING]);
            $sqlSrvBinary = true;
        }

        $pdo = new PDO(
            $this->getDSN($config),
            $config['user_name'],
            $config['user_password'],
            $option
        );

        if (is_string($extraOptions)) {
            $pdo->prepare($extraOptions)->execute();
        }
        if ($sqlSrvBinary === true) {
            $pdo->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_BINARY);
        }

        return $pdo;
    }
}
