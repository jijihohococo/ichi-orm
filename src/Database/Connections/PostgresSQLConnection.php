<?php

namespace JiJiHoHoCoCo\IchiORM\Database\Connections;

class PostgresSQLConnection extends Connection{

	protected function getDSN(array $config){

		if(!isset($config['dbname']) || !isset($config['host']) ){

			throw new \Exception("You must add database name and host for Postgres SQL Database Connection", 1);
		}

		$dsn=$config['driver'] . ':';

		$dsn.=isset($config['host']) ? 'host=' $config['host'] . ';' : ''; 

		$dsn .='dbname='.$config['dbname'];

		if(isset($config['port'])){
			$dsn .=';port='.$config['port'];
		}

		return $this->getSSLOptions($config,$dsn);
		
	}

	protected function getExtraOptions(array $config){
		$option=NULL;
		$charset=$time_zone=$application_name=$synchronous_commit=FALSE;
		if(isset($config['charset']) && $config['charset']!==NULL ){
			$charset=TRUE;
			$option .='set names '.$config['charset'];
		}

		if(isset($config['time_zone']) && $config['time_zone']!==NULL ){
			$time_zone=TRUE;
			$option .=$charset==TRUE ? ', time zone '.$config['time_zone'] : 'set time zone '.$config['time_zone'];
		}

		if(isset($config['application_name']) && $config['application_name']!==NULL ){
			$application_name=TRUE;
			$option .=$charset==TRUE && $time_zone==TRUE ? ', application_name to '.$config['application_name'] : 'set application_name to '.$config['application_name'];
		}

		if(isset($config['synchronous_commit']) && $config['synchronous_commit']!==NULL ){
			$synchronous_commit=TRUE;
			$option .=$charset==TRUE && $time_zone==TRUE && $application_name==TRUE ? ', set synchronous_commit to '.$config['synchronous_commit'] : 'set synchronous_commit to '.$config['synchronous_commit'];
		}

		return $option;

	}

	private function getSSLOptions(array $config,$dsn){
		foreach(['sslmode', 'sslcert', 'sslkey', 'sslrootcert'] as $option){
			if(isset($config[$option])){
				$dsn .=';'.$option .'=' . $config[$option];
			}
		}
		return $dsn;
	}
}