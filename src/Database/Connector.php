<?php

namespace JiJiHoHoCoCo\IchiORM\Database;

use JiJiHoHoCoCo\IchiORM\Database\Connections\{MySQLConnection,PostgresSQLConnection};
use PDO;
class Connector{

	private $connections,$pdos=[];
	private static $pdo=NULL;

	private function getPDO(array $config){
		if(!isset($config['driver'])){
			throw new Exception("You need to add database driver", 1);
		}
		$connection=NULL;
		switch ($config['driver']) {

			case 'mysql':
			$connection=new MySQLConnection;
			break;

			case 'pgsql':
			$connection=new PostgresSQLConnection;
			break;

			default:
			throw new Exception("Your database driver is not supported", 1);
			break;

		}

		return $connection->getConnection($config);

		throw new Exception("Your request database driver is not supported", 1);
	}

	private function boot(){
		if(empty($this->connections)){
			$this->connections['mysql']=[
				'driver' => 'mysql'
			];
			$this->connections['pgsql']=[
				'driver' => 'pgsql'
			];
		}
	}

	public function addConnection(string $connection){
		$this->boot();
		if(!isset($this->connections[$connection])){
			$this->connections[$connection]=NULL;
		}
		return $this;
	}

	public function createConnection(string $connection,array $config){
		$this->boot();
		if(array_key_exists($connection, $this->connections)){
			$resultConnection=isset($this->connections[$connection]['driver']) ? $this->connections[$connection]+$config : $config;
			$this->pdos[$connection]=$this->getPDO($resultConnection);
		}else{
			throw new Exception("You are connecting to unavialble database connection", 1);
		}
		
	}

	public function selectConnection(string $connection){
		if(isset($this->pdos[$connection])){
			self::$pdo=$this->pdos[$connection];
		}else{
			throw new Exception("Your database connection is unavailable", 1);
		}
		
	}

	public static function getConnection(){ return self::$pdo; }

	public function executeConnect(string $connection){
		if(isset($this->pdos[$connection])){
			return $this->pdos[$connection];
		}
		throw new Exception("Your database connection is unavailable", 1);
	}
}