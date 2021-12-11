<?php

namespace JiJiHoHoCoCo\IchiORM\Database\Connections;

use PDO;
class SQLServerConnection extends Connection{

	protected function getDSN(array $config){

		if(!isset($config['dbname']) || !isset($config['host']) ){

			throw new \Exception("You must add database name and host for SQL Server Database Connection", 1);
		}
		
		switch ($config['driver']) {
			case 'sqlsrv':
			return $this->getSqlSrvDsn($config);
			break;
		}

	}

	protected function getExtraOptions(array $config){

	}

	private function getSqlSrvDsn(array $config){

		$dsn=$config['driver'] . ':';

	}

}