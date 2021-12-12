<?php

namespace JiJiHoHoCoCo\IchiORM\Database\Connections;

class MySQLConnection extends Connection{

	protected function getDSN(array $config){

		if(!isset($config['dbname']) || !isset($config['host']) ){

			throw new \Exception("You must add database name and host for MySQL Database Connection", 1);
		}

		$dsn=$config['driver'];

		if(isset($config['unix_socket']) && $config['unix_socket']!==NULL ){
			$dsn .=':unix_socket='.$config['unix_socket'].
			';dbname='.$config['dbname'];
		}elseif(isset($config['port']) && $config['port']!==NULL ){
			$dsn .=':host='.$config['host'].
			';port='.$config['port'].
			';dbname='.$config['dbname'];
		}else{
			$dsn .=':host='.$config['host'].
			';dbname='.$config['dbname'];
		}

		return $dsn;
	}

	protected function getExtraOptions(array $config){
		$option=NULL;
		$mode=$charset=$time_zone=FALSE;
		if(isset($config['modes']) && is_array($config['modes']) ){
			$mode=TRUE;
			$option .='set session sql_mode='.implode(',', $config['modes']);
		}elseif(isset($config['strict'])){
			$mode=TRUE;
			$option .=$config['strict']==TRUE ? $this->getStrictMode() :
			"set session sql_mode='NO_ENGINE_SUBSTITUTION'";
		}

		if(isset($config['charset']) && $config['charset']!==NULL && $mode==TRUE ){
			$charset=TRUE;
			$option .=$mode==TRUE ? ', names '.$config['charset'] : 'set names '.$config['charset'];
			$option .=$this->getCollation($config);
		}

		if(isset($config['time_zone']) && $config['time_zone']!==NULL  ){
			$time_zone=TRUE;
			$option .= $mode==FALSE && $charset==FALSE ? 'set time_zone '. $config['time_zone'] : ', time_zone '.$config['time_zone'];
		}

		if(isset($config['isolation_level']) && $config['isolation_level']!==NULL ){
			$isolationLevel ='SESSION TRANSACTION ISOLATION LEVEL '.$config['isolation_level'];
			$option .=$mode==FALSE && $charset==FALSE && $time_zone==FALSE ? 'set '.$isolationLevel : ', '. $isolationLevel;
		}

		return $option;
	}

	private function getStrictMode(){
		return "set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
	}

	private function getCollation(){
		return isset($config['collation']) && $config['collation']!==NULL ? ' collate '. $config['collation'] : NULL;
	}
}