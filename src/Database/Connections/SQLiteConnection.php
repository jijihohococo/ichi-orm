<?php

namespace JiJiHoHoCoCo\IchiORM\Database\Connections;

use Exception;

class SQLiteConnection extends Connection
{


	protected function getDSN(array $config)
	{
		try {
			if (!isset($config['dbname'])) {

				throw new Exception("You must add database name and host for SQL Lite Database Connection", 1);
			}

			$dsn = $config['driver'] . ':' . $config['dbname'];

			return $dsn;
		} catch (Exception $e) {
			return showErrorPage($e->getMessage());
		}
	}

	protected function getExtraOptions(array $config)
	{
		return null;
	}


}