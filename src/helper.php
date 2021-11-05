<?php

use JiJiHoHoCoCo\IchiORM\Database\{Connector,NullModel};

if(!function_exists('connectPDO')){
	function connectPDO(){
		return Connector::getConnection();
	}
}

if(!function_exists('jsonResponse')){
	function jsonResponse(array $data){
		header('Content-type:application/json');
		echo json_encode($data);
	}
}

if(!function_exists('pageCheck')){
	function pageCheck(){
		return isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page']>1;
	}
}

if(!function_exists('addArray')){
	function addArray($value){
		return implode(',', array_fill(0, count($value), '?'));
	}
}

if(!function_exists('now')){
	function now(){
		return date('Y-m-d H:i:s');
	}
}

if(!function_exists('getDomainName')){
	function getDomainName(){
		$http=!empty($_SERVER['HTTPS']) ? 'https://' : 'http://' ;
		return $http . $_SERVER['HTTP_HOST'] . parse_url( $_SERVER["REQUEST_URI"] , PHP_URL_PATH);
	}
}

if(!function_exists('getTableName')){
	function getTableName($string){
		$resultString=NULL;
		$stringArray=explode('\\',$string);
		$endOfString=end($stringArray);
		$stringSplit=str_split($endOfString);
		foreach($stringSplit as $key => $stringData){
			$resultString .=isset($stringSplit[$key+1]) && ctype_upper($stringSplit[$key+1]) ? strtolower($stringData) . '_' : strtolower($stringData);
		}
		return $resultString . 's';
	}
}

if(!function_exists('getCurrentField')){
	function getCurrentField($subQueries,$currentField,$currentSubQueryNumber){
		return substr_replace(array_keys($subQueries)[$subQueries[$currentField.$currentSubQueryNumber]], NULL, -strlen($currentSubQueryNumber+1) );
	}
}

if(!function_exists('mappingModelData')){
	function mappingModelData(array $primaryData,array $attributes,$object){
		$dataArray=$primaryData+$attributes;
		foreach ($dataArray as $key => $value) {
			$object->{$key}=$value;
		}
		return $object;
	}
}


if(!function_exists('getFirstObject')){
	function getFirstObject(array $objectArray){
		return isset($objectArray[0]) ? $objectArray[0] : (new NullModel)->nullExecute();
	}
}

if(!function_exists('bindValues')){
	function bindValues($stmt,$fields){
		if(is_array($fields)){
			foreach($fields as $key => $field){
				if(!is_array($field)){
					$stmt->bindValue($key+1,$field, getPDOBindDataType($field));
				}elseif(is_array($field)){
					return bindValues($stmt,$field);
				}
			}
		}
	}
}

if(!function_exists('getPDOBindDataType')){
	function getPDOBindDataType($field){
		$type=gettype($field);
		
		switch ($type) {
			case 'integer':
			return \PDO::PARAM_INT;
			break;

			case 'boolean':
			return \PDO::PARAM_BOOL;
			break;

			case 'NULL':
			return \PDO::PARAM_NULL;

			case 'resource':
			case 'resource (closed)':
			return \PDO::PARAM_LOB;
			
			default:
			return \PDO::PARAM_STR;
			break;
		}
	}
}

if(!function_exists('databaseOperators')){
	function databaseOperators(){
		return [
			'=',
			'<>',
			'!=',
			'>',
			'<',
			'>=',
			'<=',
			'!<',
			'!>',
			'like',
			'LIKE',
			'not like',
			'NOT LIKE'
		];
	}
}

if(!function_exists('getSubQueryTypes')){
	function getSubQueryTypes(){
		return ['where','whereColumn','whereIn','whereNotIn','orWhere','selectQuery'];
	}
}

if(!function_exists('unsetTimeData')){
	function unsetTimeData(array $data){
		if(isset($data['created_at'])){
			unset($data,'created_at');
		}

		if(isset($data['updated_at'])){
			unset($data,'updated_at');
		}

		if(isset($data['deleted_at'])){
			unset($data,'deleted_at');
		}
	}
}