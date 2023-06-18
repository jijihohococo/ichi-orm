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
		return null;
	}

	private function getSqlSrvDsn(array $config){

		$dsn=$config['driver'] . ':Server='.$config['host'];

		if(isset($config['port'])){
			$dsn .=','.$config['port'];
		}

		$dsn.=';Database='.$config['dbname'];

		if(isset($config['charset'])){
			$dsn .=';CharacterSet='.$config['charset'];
		}

		if(isset($config['readOnly']) && $config['readOnly']==TRUE ){
			$dsn .=';ApplicationIntent=ReadOnly';
		}

		if(isset($config['pooling']) && $config['pooling']==FALSE ){
			$dsn .=';ConnectionPooling=0';
		}

		if(isset($config['application_name'])){
			$dsn .=';APP='.$config['application_name'];
		}

		if(isset($config['encrypt'])){
			$dsn .=';Encrypt='.$config['encrypt'];
		}

		if(isset($config['trust_server_certificate'])){
			$dsn .=';TrustServerCertificate='.$config['trust_server_certificate'];
		}

		if(isset($config['multiple_active_result_sets']) && $config['multiple_active_result_sets']==FALSE ){
			$dsn .=';MultipleActiveResultSets=false';
		}

		if(isset($config['transaction_isolation'])){
			$dsn .=';TransactionIsolation='.$config['transaction_isolation'];
		}

		if(isset($config['multi_subnet_failover'])){
			$dsn .=';MultiSubnetFailover='.$config['multi_subnet_failover'];
		}

		if (isset($config['column_encryption'])) {
			$dsn .=';ColumnEncryption='.$config['column_encryption'];
        }

        if (isset($config['key_store_authentication'])) {
        	$dsn .=';KeyStoreAuthentication='.$config['key_store_authentication'];
        }

        if (isset($config['key_store_principal_id'])) {
        	$dsn .=';KeyStorePrincipalId='.$config['key_store_principal_id'];
        }

        if (isset($config['key_store_secret'])) {
        	$dsn .=';KeyStoreSecret='.$config['key_store_secret'];
        }

        if (isset($config['login_timeout'])) {
        	$dsn .=';LoginTimeout='.$config['login_timeout'];
        }

        return $dsn;

	}

}