<?php

namespace JiJiHoHoCoCo\IchiORM\Database\Connections;

class SQLServerConnection extends Connection{

	protected function getDSN(array $config){

		if(!isset($config['dbname']) || !isset($config['host']) ){

			throw new \Exception("You must add database name and host for SQL Server Database Connection", 1);
		}

		$dsn=$config['driver'];


	}

	protected function getExtraOptions(array $config){

	}

}