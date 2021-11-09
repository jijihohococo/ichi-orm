<?php

namespace JiJiHoHoCoCo\IchiORM\Database;
use PDO;
abstract class Model{

	private static $limitOne=" LIMIT 1";

	private static $instance,$getID,$table,$fields,$where,$whereColumn,$orWhere,$whereIn,$whereNotIn,$operators,$order,$limit,$groupBy,$joinSQL,$select,$addSelect,$withTrashed,$addTrashed,$className,$toSQL;
	private static $numberOfSubQueries,$currentSubQueryNumber,$currentField,$whereSubQuery;
	private static $subQuery;
	private static $subQueries,$selectedFields=[];
	private static $havingNumber=NULL;
	private static $havingField,$havingOperator,$havingValue;
	private static $whereZero=' WHERE 0 = 1 ';
	private static $andZero=' AND 0 = 1 ';
	private static $groupByString=' GROUP BY ';
	private static $unionQuery;
	private static $selectQuery;

	protected function connectDatabase(){
		return connectPDO();
	}

	protected function getTable(){
		return getTableName((string)get_called_class());
	}

	protected function getID(){
		return "id";
	}

	public static function withTrashed(){
		if(self::$currentSubQueryNumber==NULL){
			self::boot();
			self::$withTrashed=TRUE;
		}else{
			self::makeSubQueryTrashTrue(self::showCurrentSubQuery());
		}
		return self::$instance;
	}

	private static function makeSubQueryTrashTrue($where){
		self::${$where}[self::$currentField.self::$currentSubQueryNumber]['withTrashed']=TRUE;
	}

	private static function getSelect(){
		$select=self::$select;
		if(self::$selectQuery!==NULL){
			$i=0;
			foreach(self::$selectQuery as $selectAs => $query ){
				$selectData=$query.' AS '.$selectAs;
				$select .= $i==0 && $select==NULL ? $selectData : ','.$selectData;
				$i++;
			}
		}
		return "SELECT ".$select." FROM ".self::$table.self::getJoinSQL();
	}

	private static function getSubQuerySelect($where){
		if(isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber])){
			$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];

			// $select=$current['select']!==NULL ? str_replace(self::$table.'.*', $current['table'].'.*', $current['select']) : NULL;
			$select=$current['select'];
			if($current['selectQuery']!==NULL){
				$i=0;
				foreach($current['selectQuery'] as $selectAs => $query){
					$selectData='('.$query.') AS '.$selectAs;
					$select .=$i==0 && $select==NULL ? $selectData : ','.$selectData;
					$i++;
				}
			}
			return "SELECT ".$select." FROM ".$current['table'].self::getSubQueryJoinSQL($where);
		}
	}

	private static function countData(){
		$table=self::$table;
		return "SELECT COUNT(".$table.".".self::$getID.") FROM ".$table. self::getJoinSQL();
	}

	private static function boot(){
		$calledClass=get_called_class();
		if(self::$className!==NULL && self::$className!==$calledClass){
			throw new \Exception("You are not allowed to use multiple query to execute at once. Please use sub queries", 1);
		}

		if(self::$instance==NULL){
			self::$where=NULL;
			self::$whereColumn=NULL;
			self::$orWhere=NULL;
			self::$whereIn=NULL;
			self::$whereNotIn=NULL;
			self::$operators=NULL;
			self::$order=NULL;
			self::$limit=NULL;
			self::$groupBy=NULL;
			self::$joinSQL=NULL;
			self::$instance=new static;
			self::$className=$calledClass;
			self::$getID=self::$instance->getID();
			self::$table=self::$instance->getTable();
			self::$select=self::$table.'.*';
			self::$addSelect=FALSE;
			self::$withTrashed=FALSE;

			self::$subQuery=NULL;
			self::$addTrashed=FALSE;
		}
	}

	public static function groupBy($groupBy){
		if(self::$currentSubQueryNumber==NULL){
			self::boot();
			self::$groupBy=self::$groupByString . $groupBy;
		}else{
			self::makeSubQueryGroupBy(self::showCurrentSubQuery(),$groupBy);
		}
		return self::$instance;
	}

	public static function having($field,$operator,$value){
		if(self::$currentSubQueryNumber==NULL){
			self::boot();
			if(self::$havingNumber==NULL){
				self::$havingNumber=0;
			}
			self::$havingField[self::$havingNumber]=$field;
			self::$havingOperator[self::$havingNumber]=$operator;
			self::$havingValue[self::$havingNumber]=$value;
			self::$havingNumber++;
		}else{
			self::makeSubQueryHaving(self::showCurrentSubQuery(),$field,$operator,$value);
		}
		return self::$instance;
	}

	private static function makeSubQueryHaving($where,$field,$operator,$value){
		$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
		if($current['havingNumber']==NULL){
			$current['havingNumber']=0;
		}
		$current['havingField'][$current['havingNumber']]=$field;
		$current['havingOperator'][$current['havingNumber']]=$operator;
		$current['havingValue'][$current['havingNumber']]=$value;
		$current['havingNumber']++;
	}

	private static function makeSubQueryGroupBy($where,$groupBy){
		self::${$where}[self::$currentField.self::$currentSubQueryNumber]['groupBy']=self::$groupByString . $groupBy;
	}

	private static function getGroupBy(){ return self::$groupBy; }

	private static function getHaving(){
		$string=NULL;
		if(self::$havingNumber!==NULL){

			foreach (range(0, self::$havingNumber-1) as $key => $value) {
				$result=self::$havingField[$key].' ' . self::$havingOperator[$key] . ' ' . self::$havingValue[$key];
				$string .=$key==0 ? ' HAVING ' . $result : ' AND ' . $result;
			}
		}
		return $string;
	}

	private static function getSubQueryHaving($where){
		$string=NULL;
		if(isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber])){
			$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
			if($current['havingNumber']!==NULL){
				foreach (range(0, $current['havingNumber']-1) as $key => $value) {
					$result=$currentField['havingField'][$key] . ' ' . $currentField['havingOperator'][$key] . ' ' . $currentField['havingValue'][$key];
					$string .=$key==0 ? ' HAVING ' . $result : ' AND ' . $result;
				}
			}
		}
		return $string;
	}

	private static function getSubQueryGroupBy($where){
		if(isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber]['groupBy'])){
			return self::${$where}[self::$currentField.self::$currentSubQueryNumber]['groupBy'];
		}
	}

	public static function bulkUpdate(array $attributes){
			if(empty($attributes)){
				throw new \Exception("You need to put non-empty array data", 1);
			}
			static::boot();
			$instance=self::$instance;
			$arrayKeys=get_object_vars( $instance );
			if(empty($arrayKeys)){
				throw new \Exception("You need to add column data", 1);
			}
			$getID=$instance->getID();
			$updatedFields=[];
			$updatedIds=[];
			$updatedBindValues=[];
			$i=0;
			foreach ($attributes as $key => $attribute) {
				if(!is_array($attribute)){
					throw new \Exception("You need to add the array data", 1);

				}
				if(empty($attribute)){
					throw new \Exception("You need to put non-empty array data", 1);
				}
				if(!isset($attribute[$getID])){
					throw new \Exception("You don't have the primary id data to update", 1);
				}
				$i++;
				$j=0;
				if( property_exists($instance,'updated_at') ){
					$attribute['updated_at']=now();
				}
				foreach ($attribute as $field => $value) {
					$j++;
					if(array_key_exists($field,$arrayKeys) && $field!==$getID ){
						$updatedIds[$i.'0']=$attribute[$getID];
						$updatedBindValues[$field][$i.'0']=$attribute[$getID];
						$updatedBindValues[$field][$i.$j]=$value;
						if(!isset($updatedFields[$field])){
							$updatedFields[$field]=$field . ' = CASE ';
						}
						$updatedFields[$field] .=' WHEN ' . $getID . ' = ? THEN ?';
						if($key+1==count($attributes)){
							$updatedFields[$field] .=' END, ';
						}
					}elseif(!array_key_exists($field,$arrayKeys) && $field!==$getID ){
						throw new \Exception("You need to put the available column data to update", 1);
					}
				}
			}
			$updateString='UPDATE '.self::$table. ' SET '. substr(implode('', $updatedFields),0,-2);
			$stmt=$instance->connectDatabase()->prepare($updateString);
			$i=0;
			foreach($updatedBindValues as $fieldNumber => $fields){
				foreach($fields as $key => $value){
					$i++;
					$stmt->bindValue($i,$value,getPDOBindDataType($value));
				}
			}
			$stmt->execute();
			self::disableBooting();
	}

	public static function insert(array $attributes){
		if(empty($attributes)){
			throw new \Exception("You need to put non-empty array data", 1);
		}
		self::boot();
		$instance=self::$instance;
		$arrayKeys=get_object_vars( $instance );
		if(empty($arrayKeys)){
			throw new \Exception("You need to add column data", 1);
		}
		$getID=$instance->getID();
		unset($arrayKeys[$getID]);
		unset($arrayKeys['deleted_at']);
		unset($arrayKeys['updated_at']);
		$insertedValues='';
		$insertBindValues=[];
		$insertedFields=[];
		foreach($attributes as $attribute){
			if(!is_array($attribute)){
				throw new \Exception("You need to add the array data", 1);
			}
			if(empty($attribute)){
				throw new \Exception("You need to put non-empty array data", 1);
			}
			$insertedData=[];
			unset($attribute[$getID]);
			unset($attribute['created_at']);
			unset($attribute['deleted_at']);
			unset($attribute['updated_at']);
			foreach ($arrayKeys as $key => $value) {
				if(!isset($insertedFields[$key.','])){
					$insertedFields[$key.',']=NULL;
				}
				if(isset($attribute[$key])){
					$insertedData[$key]=$attribute[$key];
				}elseif($key=='created_at'){
					$insertedData[$key]=now();
				}else{
					$insertedData[$key]=$value;
				}
			}
			$insertedArrayValues=array_values($insertedData);
			$insertedValues .= "(".addArray( $insertedArrayValues )."),";
			$insertBindValues= array_merge($insertBindValues,$insertedArrayValues);
		}
		$insertedValues=substr($insertedValues, 0,-1);
		$fields='('.substr(implode('',array_keys($insertedFields)), 0, -1).')';
		$stmt=$instance->connectDatabase()->prepare("INSERT INTO ".self::$table." ".$fields." VALUES ". $insertedValues );
		bindValues($stmt,$insertBindValues);
		$stmt->execute();
		self::disableBooting();
	}

	public static function create(array $attribute){
		if(empty($attribute)){
			throw new \Exception("You need to put non-empty array data", 1);
		}
		self::boot();
		$instance=self::$instance;
		$arrayKeys=get_object_vars($instance);
		if(empty($arrayKeys)){
			throw new \Exception("You need to add column data", 1);
		}
		$getID=$instance->getID();
		unset($arrayKeys[$getID]);
		unset($arrayKeys['deleted_at']);
		unset($arrayKeys['updated_at']);
		$insertBindValues=[];
		$insertedFields=[];
		$insertedData=[];
		foreach ($arrayKeys as $key => $value) {
			if(!isset($insertedFields[$key.','])){
				$insertedFields[$key.',']=NULL;
			}
			if(isset($attribute[$key])){
				$insertedData[$key]=$attribute[$key];
			}elseif($key=='created_at'){
				$insertedData[$key]=now();
			}else{
				$insertedData[$key]=$value;
			}
		}
		$insertedArrayValues=array_values($insertedData);
		$insertedValues = substr("(".addArray( $insertedArrayValues )."),",0,-1);
		$insertBindValues= array_merge($insertBindValues,$insertedArrayValues);
		$fields='('.substr(implode('',array_keys($insertedFields)), 0, -1).')';
		$pdo=$instance->connectDatabase();
		$stmt=$pdo->prepare("INSERT INTO ".self::$table." ".$fields." VALUES ". $insertedValues );
		bindValues($stmt,$insertBindValues);
		$stmt->execute();
		$object= mappingModelData([
			$getID => $pdo->lastInsertId()
		], $insertedData , $instance );
		self::disableBooting();
		return $object;
	}

	public function update(array $attribute){
		if(empty($attribute)){
			throw new \Exception("You need to put non-empty array data", 1);
		}
		$getID=$this->getID();
		$arrayKeys=get_object_vars($this);
		if(empty($arrayKeys)){
			throw new \Exception("You need to add column data", 1);
		}
		unset($arrayKeys[$getID]);
		$updatedBindValues=[];
		$updatedFields=NULL;
		$insertedData=[];
		foreach ($arrayKeys as $key => $value) {
			$updatedFields .= $key . '=?,';
			if(isset($attribute[$key])){
				$insertedData[$key]=$attribute[$key];
			}elseif($key=='updated_at'){
				$insertedData[$key]=now();
			}else{
				$insertedData[$key]=$value;
			}
		}
		$insertedArrayValues=array_values($insertedData);
		$updatedBindValues= array_merge($updatedBindValues,$insertedArrayValues);
		$updatedFields=substr($updatedFields, 0,-1);
		$stmt=$this->connectDatabase()->prepare("UPDATE ".$this->getTable()." SET ".$updatedFields. " WHERE ".$getID."=".$this->{$getID} );
		bindValues($stmt,$updatedBindValues);
		$stmt->execute();
		$object= mappingModelData([
			$getID => $this->{$getID}
		], $insertedData , $this );
		return $object;
		
	}

	public static function find($id){
		self::boot();
		$pdo=self::$instance->connectDatabase();
		$getId=self::$instance->getID();
		$stmt=$pdo->prepare(self::getSelect() . " WHERE ".$getId ." = ? ".self::$limitOne);
		bindValues($stmt,[
			0 => $id
		]);
		$stmt->execute();
		$instance=$stmt->fetchObject(self::$className);
		self::disableBooting();
		return self::getObject($instance);
	}

	private static function getObject($instance){
		return $instance=='' ? (new NullModel)->nullExecute() : $instance;
	}

	public static function findBy($field,$value){
		self::boot();
		$pdo=self::$instance->connectDatabase();
		$stmt=$pdo->prepare(self::getSelect() . " WHERE ".$field." = ? ".self::$limitOne);
		bindValues($stmt,[
			0 => $value
		]);
		$stmt->execute();
		$instance=$stmt->fetchObject(self::$className);
		self::disableBooting();
		return self::getObject($instance);
	}

	public function delete(){
		$id=$this->getID();
		$table=$this->getTable();
		$pdo=$this->connectDatabase();
		if( property_exists($this, 'deleted_at') ){
			$stmt=$pdo->prepare("UPDATE ".$table." SET deleted_at='".now()."' WHERE ".$id."=".$this->{$id} );
			$stmt->execute();
		}else{
			$stmt=$pdo->prepare("DELETE FROM ".$table." WHERE ".$id."=".$this->{$id});
			$stmt->execute();
		}
	}

	public function forceDelete(){
		$id=$this->getID();
		$table=$this->getTable();
		$stmt=$this->connectDatabase()->prepare("DELETE FROM ".$table." WHERE ".$id.'='.$this->{$id});
		$stmt->execute();
	}

	public function restore(){
		$id=$this->getID();
		$pdo=$this->connectDatabase();
		$table=$this->getTable();
		if(property_exists($this, 'deleted_at')){
			$stmt=$pdo->prepare("UPDATE ".$table." SET deleted_at=NULL WHERE ".$id."=".$this->{$id});
			$stmt->execute();
		}
	}


	public static function select(array  $fields){
		if(self::$currentSubQueryNumber==NULL){

			// If addSelect was used after using addOnlySelect function
			if(self::$instance!==NULL && self::$select==NULL && self::$addSelect==TRUE ){
				throw new \Exception("You must not use addOnlySelect function before", 1);
			}

			self::boot();
			if(self::$addSelect==FALSE){
				self::$select=NULL;
			}else{
				self::$select.=',';
			}
			
			foreach($fields as $key => $field){
				if( strpos($field,'(')==FALSE && strpos($field,')')==FALSE && !isset(self::$selectedFields[self::$className][$field]) ){
					$selectedField=function() use ($field){
						if(strpos($field, '.')!==FALSE){
							$getField=explode('.', $field);
							return $getField[1];
						}else{
							return $field;
						}
					};
					$newSelectedField=$selectedField();
					self::$selectedFields[self::$className][$newSelectedField]=$newSelectedField;
				}
				self::$select  .= $key+1==count($fields) ? $field : $field . ',';
			}
		}else{

			$check=self::showCurrentSubQuery();
			$addSelectCheck=self::checkSubQueryAddSelect($check);
			if(self::${$check}[self::$currentField.self::$currentSubQueryNumber]['select']==NULL &&
				$addSelectCheck==TRUE
			){
				throw new \Exception("You must not use addOnlySelect function before", 1);
		}

		if($addSelectCheck==TRUE){
			self::addCommaToSubQuerySelect($check);
		}else{
			self::makeNullToSubQuerySelect($check);
		}

		foreach($fields as $key => $field){
			self::${$check}[self::$currentField.self::$currentSubQueryNumber]['select'] .= $key+1==count($fields) ? $field : $field . ',';
		}
	}
	return self::$instance;
}

private static function makeNullToSubQuerySelect($where){
	self::${$where}[self::$currentField.self::$currentSubQueryNumber]['select']=NULL;
}

private static function addCommaToSubQuerySelect($where){
	self::${$where}[self::$currentField.self::$currentSubQueryNumber]['select'].=',';
}

private static function checkSubQueryAddSelect($where){
	return self::${$where}[self::$currentField.self::$currentSubQueryNumber]['addSelect'];
}

public static function limit($limit){
	self::boot();
	self::$limit=' LIMIT '.$limit;
	return self::$instance;
}

public static function setSubQuery($field,$where){
	self::$numberOfSubQueries++;
	self::$currentSubQueryNumber=self::$numberOfSubQueries;
	self::$currentField=$field;
	self::${$where}[self::$currentField.self::$currentSubQueryNumber]=[
		'where'=>NULL,
		'whereColumn'=>NULL,
		'orWhere'=>NULL,
		'whereIn'=>NULL,
		'whereNotIn'=>NULL,
		'operators'=>NULL,
		'order'=>NULL,
		'limit'=>NULL,
		'groupBy'=>NULL,
		'joinSQL'=>NULL,
		'addSelect'=>FALSE,
		'withTrashed'=>FALSE,
		'addTrashed' => FALSE,
		'table' => self::$table,
		'select'=> self::$table.'.*',
		'className' => NULL,
		'object' => NULL,
		'havingNumber' => NULL ,
		'havingField' => NULL ,
		'havingOperator' => NULL ,
		'havingValue' => NULL ,
		'selectQuery' => NULL
	];
}

private static function setSubWhere($where,$value,$field,$operator,$whereSelect){
	self::${$where}[self::$currentField.self::$currentSubQueryNumber][$whereSelect][$field]=$value;
	self::${$where}[self::$currentField.self::$currentSubQueryNumber]['operators'][$field.$whereSelect]=$operator;
}

private static function setSubWhereIn($where,$value,$field,$whereInSelect){
	self::${$where}[self::$currentField.self::$currentSubQueryNumber][$whereInSelect][$field]=$value;
}

private static function makeDefaultSubQueryData(){
	self::$currentSubQueryNumber=NULL;
	self::$currentField=NULL;
		//self::$numberOfSubQueries=NULL;
}

private static function showCurrentSubQuery(){
	foreach(getSubQueryTypes() as $subQuery){
		if(self::checkSubQuery($subQuery)){
			return $subQuery;
		}
	}
}


public static function where(){
	self::makeWhereQuery(func_get_args(),'where');
	return self::$instance;
}

private static function makeSubQueryInSubQuery($whereSelect,$value,$field,$check){
		// if 	there is sub query function in sub query //
	$previousField=self::$currentField;
	$previousSubQueryNumber=self::$currentSubQueryNumber;
	$query=self::$instance;
	$query->setSubQuery($field,$check);
	self::$subQueries[$field.self::$currentSubQueryNumber]=self::$currentSubQueryNumber;
	$value($query);

	if($whereSelect!=='selectQuery'){
			// put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//
		self::${$check}[$previousField.$previousSubQueryNumber][$whereSelect]=self::$subQuery;
			// put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//
	}

	if($whereSelect=='selectQuery'){
			// put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//
		self::${$check}[$previousField.$previousSubQueryNumber][$whereSelect][$field]=self::$subQuery;
			// put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//
	}


	self::$currentField=$previousField;
	self::$currentSubQueryNumber=$previousSubQueryNumber;
}

public static function from($className){
	if(self::$currentSubQueryNumber!==NULL){
		self::addTableToSubQuery(self::showCurrentSubQuery(),$className);
		return self::$instance;
	}
	throw new \Exception("You can use 'from' function in only sub queries", 1);
}

private static function getSubQueryClassObject($where,$className){
	if(self::${$where}[self::$currentField.self::$currentSubQueryNumber]['object']==NULL){
		self::${$where}[self::$currentField.self::$currentSubQueryNumber]['object']=new $className;
	}
	return self::${$where}[self::$currentField.self::$currentSubQueryNumber]['object'];
}

private static function addTableToSubQuery($where,$className){
	$obj=self::getSubQueryClassObject($where,$className);
	$table=$obj->getTable();
	if(self::${$where}[self::$currentField.self::$currentSubQueryNumber]['select']!==self::${$where}[self::$currentField.self::$currentSubQueryNumber]['table'].'.*'){
		throw new \Exception("You must use from function before selecting the data", 1);
	}
	self::${$where}[self::$currentField.self::$currentSubQueryNumber]['table']=$table;
	self::${$where}[self::$currentField.self::$currentSubQueryNumber]['select']=$table.'.*';
	self::${$where}[self::$currentField.self::$currentSubQueryNumber]['className']=$className;
}

public static function whereColumn(){
	self::makeWhereQuery(func_get_args(),'whereColumn');
	return self::$instance;
}

public static function orWhere(){
	self::makeWhereQuery(func_get_args(),'orWhere');
	return self::$instance;
}

private static function makeWhereQuery(array $parameters,$where){
	$countParameters=count($parameters);
	$value=$operator=$field=NULL;
	if($countParameters==2 || $countParameters==3 ){
		$field=$parameters[0];

		if($countParameters==3 && !in_array($parameters[1],databaseOperators()) ){
			throw new \Exception("You can add only database operators in {$where} function", 1);
		}
		
		if(isset($parameters[1]) && in_array($parameters[1], databaseOperators()) && (isset($parameters[2]) || $parameters[2]==NULL) ){
			$operator=$parameters[1];
			$value=$parameters[2];
		}elseif((isset($parameters[1]) && !in_array($parameters[1],databaseOperators()) || !isset($parameters[1]) ) && !isset($parameters[2]) ){
			$value=$parameters[1];
			$operator='=';
		}

		if(is_array($value)){
			throw new \Exception("You can add single value or sub query function in {$where} function", 1);
		}elseif($value==NULL && $operator=='=' ){
			$operator=' IS ';
		}elseif($value==NULL && ($operator=='!=' || $operator=='<>') ){
			$operator=' IS NOT ';
		}


		if(!is_callable($value) && self::$currentSubQueryNumber==NULL){
			self::boot();
			self::${$where}[$field]=$value;
			self::$operators[$field.$where]=$operator;
			if($value!==NULL && $where!=='whereColumn' ){
				self::$fields[]=$value;
			}
		}elseif(is_callable($value) && self::$currentSubQueryNumber==NULL ){
			self::boot();
			self::$operators[$field.$where]=$operator;
			$query=self::$instance;
			$query->setSubQuery($field,$where);
			self::$subQueries[$field.self::$currentSubQueryNumber]=self::$currentSubQueryNumber;
			$value($query);
			self::makeDefaultSubQueryData();
		}elseif(!is_callable($value) && self::$currentSubQueryNumber!==NULL ){
			self::setSubWhere(self::showCurrentSubQuery(),$value,$field,$operator,$where);
			if($value!==NULL && $where!=='whereColumn' ){
				self::$fields[]=$value;
			}
		}elseif(is_callable($value) && self::$currentSubQueryNumber!==NULL ){
			$check=self::showCurrentSubQuery();
			self::${$check}[self::$currentField.self::$currentSubQueryNumber]['operators'][$field.$where]=$operator;
			self::makeSubQueryInSubQuery($where,$value,$field,$check);
		}
	}else{
		throw new \Exception("Invalid Argument Parameter", 1);
	}
}

private static function makeInQuery($whereIn,$field,$value){

	if(!is_array($value) && !is_callable($value) && $value!==NULL ){
		throw new \Exception("You can add only array values or sub query in {$whereIn} function", 1);
	}

	if((is_array($value) || $value==NULL) && self::$currentSubQueryNumber==NULL){
		self::boot();
		self::${$whereIn}[$field]=$value;
		if($value!==NULL){
			self::$fields[]=$value;
		}
	}elseif(is_callable($value) && self::$currentSubQueryNumber==NULL ){
		self::boot();
		$query=self::$instance;
		$query->setSubQuery($field,$whereIn,$field);
		self::$subQueries[$field.self::$currentSubQueryNumber]=self::$currentSubQueryNumber;
		$value($query);
		self::makeDefaultSubQueryData();
	}elseif((is_array($value) || $value==NULL) && self::$currentSubQueryNumber!==NULL ){
		self::setSubWhereIn(self::showCurrentSubQuery(),$value,$field,$whereIn);
		if($value!==NULL){
			self::$fields[]=$value;
		}
	}elseif(is_callable($value) && self::$currentSubQueryNumber!==NULL ){
		self::makeSubQueryInSubQuery($whereIn,$value,$field,self::showCurrentSubQuery());
	}
}

public static function whereIn($field,$value){
	self::makeInQuery('whereIn',$field,$value);
	return self::$instance;
}

public static function whereNotIn($field,$value){
	self::makeInQuery('whereNotIn',$field,$value);
	return self::$instance;
}

private static function getLimit(){ return self::$limit; }

private static function checkTrashed(){ return property_exists(self::$instance, 'deleted_at') && self::$withTrashed==FALSE; }

private static function checkSubQueryTrashed($where){

	$subClassName=self::${$where}[self::$currentField.self::$currentSubQueryNumber]['className'];

	$className=$subClassName==NULL ? self::$instance : $subClassName;

	return property_exists($className,'deleted_at') && self::${$where}[self::$currentField.self::$currentSubQueryNumber]['withTrashed']==FALSE;
}

private static function getSubQueryWhere($where){
	$string=NULL;
	$i=0;
	if(isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber])){
		$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
		if($current['where']!==NULL && is_array($current['where']) ){
			$string=' WHERE ';
			//$string=$current['select']==NULL ? NULL : ' WHERE ';
			foreach($current['where'] as $key => $value){
				$operator=$current['operators'][$key.'where'];
				if($value==NULL){
					$string .=$i==0 ? $key . $operator . 'NULL' : ' AND '. $key . $operator . 'NULL';
				}else{
					$string .=$i==0 ? $key . $operator . '?' : ' AND '. $key . $operator . '?';
				}
				$i++;
			}
		}elseif($current['where']!==NULL && !is_array($current['where'])){
			$currentField=getCurrentField(self::$subQueries,self::$currentField,self::$currentSubQueryNumber);
			$string = ' WHERE ' . $currentField . $current['operators'][$currentField.'where'] . ' (' . $current['where'] . ') ';
		}
		if(self::checkSubQueryTrashed($where)){
			$isNULL=$current['table'].'.deleted_at IS NULL';
			$string.=$current['where']==NULL ? ' WHERE ' . $isNULL : ' AND '.$isNULL;
			self::${$where}[self::$currentField.self::$currentSubQueryNumber]['addTrashed']=TRUE;
		}
	}
	return $string;
}

private static function getWhere(){
	$string=NULL;
	$i=0;
	if(self::$where!==NULL){
		$string=' WHERE ';

		foreach(self::$where  as $key => $value){
			if(isset(self::$whereSubQuery[$key.'where'])){
					// WHERE SUBQUERY //

				$string.= $i==0 ? $key . self::$operators[$key.'where'] . $value : ' AND ' . $key . self::$operators[$key.'where'] . $value;
			}else{
					// WHERE //
				if($value==NULL){
					$string .=$i==0 ? $key . self::$operators[$key.'where'] . 'NULL' : ' AND ' . $key . self::$operators[$key.'where'] . 'NULL';
				}else{
					$string .= $i==0  ?
					$key . self::$operators[$key.'where'] . '?' :
					' AND ' . $key . self::$operators[$key.'where'] . '?';
				}
			}
			$i++;
		}
	}

	if(self::checkTrashed()){
		$isNULL=self::$table.'.deleted_at IS NULL';
		$string.=self::$where==NULL ? ' WHERE '. $isNULL : ' AND '.$isNULL;
		self::$addTrashed=TRUE;
	}
	return $string;
}

private static function getWhereColumn(){
	$string=NULL;
	$i=0;
	if(self::$whereColumn!==NULL){
		foreach(self::$whereColumn as $key => $value){
			$result=$key. self::$operators[$key.'whereColumn'] . $value;
			$string .= $i==0 && self::$where==NULL && self::$addTrashed==FALSE ? ' WHERE ' . $result  : ' AND '. $result;
		}
	}
	return $string;
}

private static function getSubQueryWhereColumn($where){
	$string=NULL;
	$i=0;
	if(isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber])){
		$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
		if($current['whereColumn']!==NULL && is_array($current['whereColumn'])){
			foreach($current['whereColumn'] as $key => $value){
				$result=$key. $current['operators'][$key.'whereColumn'] . $value;
				$string .= $i==0 && $current['where']==NULL && $current['addTrashed']==FALSE ? ' WHERE ' . $result  : ' AND '. $result;
				$i++;
			}
		}elseif($current['whereColumn']!==NULL && !is_array($current['whereColumn'])){
			$currentField=getCurrentField(self::$subQueries,self::$currentField,self::$currentSubQueryNumber);
			$result=$currentField . $current['operators'][$currentField.'whereColumn'] . ' ('. $current['whereColumn'] . ') ';
			$string .=$current['where']==NULL && $current['addTrashed']==FALSE ? ' WHERE ' . $result : ' AND ' . $result;
		}
	}
	return $string;
}

private static function getOrWhere(){
	$string=NULL;
	if(self::$orWhere!==NULL){
		foreach(self::$orWhere as $key => $value){
			if(isset(self::$whereSubQuery[$key.'orWhere'])){
					// OR WHERE SUBQUERY //
				$string .=' OR '. $key . self::$operators[$key.'orWhere'] . $value;
			}else{
					// OR WHERE QUERY //

				$string .= ' OR ' . $key . self::$operators[$key.'orWhere'] . '?';
			}
		}
	}
	return $string;
}

private static function getSubQueryOrWhere($where){
	$string=NULL;
	if(isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber])){
		$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
		if($current['orWhere']!==NULL && is_array($current['orWhere'])){
			foreach($current['orWhere'] as $key => $value){

				$string .= ' OR ' . $key . $current['operators'][$key.'orWhere'] . '?';
			}
		}elseif($current['orWhere']!==NULL && !is_array($current['orWhere'])){
			$currentField=getCurrentField(self::$subQueries,self::$currentField,self::$currentSubQueryNumber);
			$string .= ' OR ' . $currentField . $current['operators'][$currentField.'orWhere'] . ' (' . $current['orWhere'] . ') ';
		}
	}
	return $string;
}

private static function getWhereIn(){
	$string=NULL;
	$i=0;
	if(self::$whereIn!==NULL){ 
		foreach(self::$whereIn as $key => $value){
			if(is_array($value) && !empty($value) ){
				$in  = addArray($value);
				$string .=  $i==0 && self::$where==NULL && self::$whereColumn==NULL && self::$addTrashed==FALSE ? ' WHERE '.$key.' IN (' . $in . ') ' : ' AND '.$key.' IN (' . $in . ') ';

			}elseif($value!==NULL && !is_array($value)){
				$string .=  $i==0 && self::$where==NULL && self::$whereColumn==NULL && self::$addTrashed==FALSE ? ' WHERE '.$key.' IN ' . $value : ' AND '.$key.' IN ' . $value;
			}else{
				$string .= $i==0 && self::$where==NULL && self::$whereColumn==NULL && self::$addTrashed==FALSE ? self::$whereZero : self::$andZero;
			}
			$i++;
		}

	}
	return $string;
}

private static function getSubQueryWhereIn($where){
	$string=NULL;
	$i=0;
	if(isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber])){
		$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
		if($current['whereIn']!==NULL && is_array($current['whereIn'])){ 
			foreach($current['whereIn'] as $key => $value){
				if(is_array($value) && !empty($value) ){
					$in  = addArray($value);
					$string .=  $i==0 && $current['where']==NULL && $current['whereColumn']==NULL && $current['addTrashed']==FALSE ? ' WHERE '.$key.' IN (' . $in . ') ' : ' AND '.$key.' IN (' . $in . ') ';

				}else{
					$string .= $i==0 && $current['where']==NULL && $current['whereColumn']==NULL && $current['addTrashed']==FALSE ? self::$whereZero : self::$andZero ;
				}
				$i++;
			}

		}elseif($current['whereIn']!==NULL && !is_array($current['whereIn'])){
			$currentField=getCurrentField(self::$subQueries,self::$currentField,self::$currentSubQueryNumber);

			$string.=$current['where']==NULL && $current['whereColumn']==NULL && $current['addTrashed']==FALSE ? ' WHERE '.$currentField.' IN (' .  $current['whereIn'] . ')' : ' AND '.$currentField.' IN (' . $current['whereIn'] . ')';
		}
	}
	return $string;
}

private static function getWhereNotIn(){
	$string=NULL;
	$i=0;
	if(self::$whereNotIn!==NULL){
		foreach(self::$whereNotIn as $key => $value){
			if(is_array($value) && !empty($value) ){
				$in=addArray($value);
				$string .=$i==0 && self::$where==NULL && self::$whereColumn==NULL && self::$whereIn==NULL && self::$addTrashed==FALSE ?
				' WHERE '.$key.' NOT IN (' . $in . ') ' : ' AND '.$key.' NOT IN ('.$in.') ';

			}elseif($value!==NULL && !is_array($value)){
				$string .=$i==0 && self::$where==NULL && self::$whereColumn==NULL && self::$whereIn==NULL && self::$addTrashed==FALSE ? ' WHERE '.$key. ' NOT IN ' . $value : ' AND ' . $key . ' NOT IN ' .  $value;

			}else{
				$string .=$i==0 && self::$where==NULL && self::$whereColumn==NULL && self::$whereIn==NULL && self::$addTrashed==FALSE ? self::$whereZero : self::$andZero;
			}
			$i++;
		}
	}
	return $string;
}

private static function getSubQueryWhereNotIn($where){
	$string=NULL;
	$i=0;
	if(isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber])){
		$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
		if($current['whereNotIn']!==NULL && is_array($current['whereNotIn'])){
			foreach($current['whereNotIn'] as $key => $value){
				if(is_array($value) && !empty($value) ){
					$in=addArray($value);
					$string .=$i==0 && $current['where']==NULL && $current['whereColumn'] && $current['whereIn']==NULL && $current['addTrashed']==FALSE ?
					' WHERE '.$key.' NOT IN (' . $in . ') ' : ' AND '.$key.' NOT IN ('.$in.') ';

				}else{
					$string .=$i==0 && $current['where']==NULL && $current['whereColumn'] && $current['whereIn']==NULL && $current['addTrashed']==FALSE ? self::$whereZero : self::$andZero;
				}
				$i++;
			}
		}elseif($current['whereNotIn']!==NULL && !is_array($current['whereNotIn'])){
			$currentField=getCurrentField(self::$subQueries,self::$currentField,self::$currentSubQueryNumber);

			$string.=$current['where']==NULL && $current['whereColumn'] && $current['whereIn']==NULL && $current['addTrashed']==FALSE ? ' WHERE '.$currentField.' NOT IN (' .  $current['whereNotIn'] . ')' : ' AND '.$currentField.' NOT IN (' . $current['whereNotIn'] . ')';
		}
	}
	return $string;
}

private static function getFields(){ return self::$fields; }

public static function orderBy($field,$sort="ASC"){
	if(self::$currentSubQueryNumber==NULL){
		self::boot();
		self::$order=" ORDER BY ".$field . " ".  $sort;
	}else{
		self::makeSubQueryOrderBy(self::showCurrentSubQuery(),$field,$sort);
	}
	return self::$instance;
}

private static function makeSubQueryOrderBy($where,$field,$sort){
	if($field==NULL){
		$object=self::getSubQueryClassObject($where, 
			self::${$where}[self::$currentField.self::$currentSubQueryNumber]['className']
		);
		$field=$object->getID();
	}
	self::${$where}[self::$currentField.self::$currentSubQueryNumber]['order']=" ORDER BY ".$field . " ". $sort;
}

public static function latest($field=null){
	if(self::$currentSubQueryNumber==NULL){
		$field=$field==null ? self::$instance->getID() : $field;
		self::boot();
		self::$order=" ORDER BY " . self::$table . '.'  . $field . " DESC";
	}else{
		self::makeSubQueryOrderBy(self::showCurrentSubQuery(),$field," DESC");
	}
	return self::$instance;
}

private static function getOrder(){ return self::$order; }

private static function getSubQueryOrder($where){
	if(isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber]['order'])){
		return self::${$where}[self::$currentField.self::$currentSubQueryNumber]['order'];
	}
}

private static function disableBooting(){
	self::$instance=self::$getID=self::$fields=self::$where=self::$whereColumn=self::$orWhere=self::$whereIn=self::$whereNotIn=self::$operators=self::$order=self::$limit=self::$groupBy=self::$joinSQL=self::$addSelect=self::$withTrashed=self::$addTrashed=self::$className=self::$toSQL=self::$numberOfSubQueries=self::$currentSubQueryNumber=self::$currentField=self::$whereSubQuery=self::$subQuery=self::$havingNumber=self::$havingField=self::$havingOperator=self::$havingValue=self::$selectQuery=NULL;
	self::$subQueries=[];
}

private static function disableForSQL(){
	self::$instance=self::$getID=self::$table=self::$where=self::$whereColumn=self::$orWhere=self::$whereIn=self::$whereNotIn=self::$operators=self::$order=self::$limit=self::$groupBy=self::$joinSQL=self::$select=self::$addSelect=self::$withTrashed=self::$addTrashed=self::$className=self::$numberOfSubQueries=self::$currentSubQueryNumber=self::$currentField=self::$whereSubQuery=self::$subQuery=self::$havingNumber=self::$havingField=self::$havingOperator=self::$havingValue=self::$selectQuery=NULL;
	self::$subQueries=[];
	self::$toSQL=FALSE;
}

public static function union($value){
	return self::makeUnionQuery($value,' UNION ');
}

public static function unionAll($value){
	return self::makeUnionQuery($value,' UNION ALL ');
}

private static function makeUnionQuery($value,$union){
	$previousQuery=self::$unionQuery==NULL ? self::getSQL() : self::$unionQuery;
	self::disableForSQL();
	$newUnionQuery=$value();
	self::boot();
	self::$unionQuery=$previousQuery . $union . $newUnionQuery;
	return self::$instance;
}

public static function get(){
	if(self::$currentSubQueryNumber==NULL){
		self::boot();
		$mainSQL=self::$unionQuery==NULL ? self::getSQL() : self::$unionQuery ;
		if(self::$toSQL==TRUE){
			self::disableForSQL();
			return $mainSQL;
		}
		$class=get_called_class();
		$fields=self::getFields();
		$stmt=self::$instance->connectDatabase()->prepare($mainSQL);
		bindValues($stmt,$fields);
		$stmt->execute();
		self::disableBooting();
		$object=$stmt->fetchAll(PDO::FETCH_CLASS,$class);
		self::$selectedFields=[];
		self::$select=self::$table=NULL;
		if(self::$unionQuery!==NULL){
			self::$unionQuery=NULL;
		}
		return $object;
	}else{
		self::makeSubQuery(self::showCurrentSubQuery());
	}
}

public function __construct(){
	$class=get_called_class();
	$selectedValues=[];
	if(!empty(self::$selectedFields) && isset(self::$selectedFields[$class]) && self::$select!==self::$table . '.*' && self::$select!==NULL ){
			// FOR ADD SELECT WITH OR WITHOUT SELECT
		foreach (get_object_vars($this) as $key => $value) {
			if(isset(self::$selectedFields[$class][$key])){
				$selectedValues[$key]=$value;
			}
			unset($this->{$key});
		}
		$id=$this->getID();
		if($id=='id'){
			unset($this->{$id});
		}
		foreach(self::$selectedFields[$class] as $key => $value){
			if(isset($selectedValues[$key])){
				$this->{$key}=$selectedValues[$key];
			}else{
				$this->{$key}=$value;
			}
		}
	}elseif(self::$select==NULL && !empty(self::$selectedFields) && isset(self::$selectedFields[$class]) ){
			// FOR ADD ONLY SELECT 
		foreach(get_object_vars($this) as $key => $value){
			if(isset(self::$selectedFields[$class][$key])){
				$this->{$key}=$value;
			}else{
				unset($this->{$key});
			}
		}
	}
}

private static function checkSubQuery($where){
	return isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber]);
}

private static function makeSubQuery($where){
	$mainSQL=self::getSubQuery($where);
	self::$subQuery=$mainSQL;
	$currentField=self::$currentField;
	$currentSubQueryNumber=self::$currentSubQueryNumber;
	if(self::$currentField . self::$currentSubQueryNumber==array_key_first(self::$subQueries)){
		$mainSQL='('.$mainSQL.')';
		self::${$where}[self::$currentField]=$mainSQL;
		if($where=='where' || $where=='whereColumn' || $where=='orWhere' ){
			self::$whereSubQuery[self::$currentField.$where]='whereSubQuery';
		}
		self::$subQueries=[];
		self::makeDefaultSubQueryData();
	}
	unset(self::${$where}[$currentField.$currentSubQueryNumber]);
}

private static function getSQL(){
	return self::getSelect().
	self::getWhere().
	self::getWhereColumn().
	self::getWhereIn().
	self::getWhereNotIn().
	self::getOrWhere().
	self::getOrder().
	self::getGroupBy().
	self::getHaving().
	self::getLimit();
}

private static function getSubQuery($where){
	return  self::getSubQuerySelect($where).
	self::getSubQueryWhere($where).
	self::getSubQueryWhereColumn($where).
	self::getSubQueryWhereIn($where).
	self::getSubQueryWhereNotIn($where).
	self::getSubQueryOrWhere($where).
	self::getSubQueryOrder($where).
	self::getSubQueryGroupBy($where).
	self::getSubQueryHaving($where);
}

public static function toArray(){
	self::boot();
	$mainSQL=self::getSQL();
	$fields=self::getFields();
	$stmt=self::$instance->connectDatabase()->prepare($mainSQL);
	bindValues($stmt,$fields);
	$stmt->execute();
	self::disableBooting();
	self::$selectedFields=[];
	self::$select=self::$table=NULL;
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public static function toSQL(){
	self::boot();
	self::$toSQL=TRUE;
	return self::$instance;
}

public static function addSelect(array $fields){

	if(self::$currentSubQueryNumber==NULL){
			// If addSelect was used after using addOnlySelect function
		if(self::$instance!==NULL && self::$select==NULL && self::$addSelect==TRUE ){
			throw new \Exception("You must not use addOnlySelect function before", 1);
		}
		self::boot();
		self::$addSelect=TRUE;
		return self::addingSelect($fields);
	}
	throw new \Exception("You are not allow to use addSelect function in subquery", 1);
}

private static function addingSelect(array $fields){
	$query=self::$instance;
	foreach($fields as $select => $value){
		if(!is_callable($value)){
			throw new \Exception("You need to add function in array in addSelect function or addOnlySelect function.", 1);
		}
		$query->setSubQuery($select,'selectQuery');
		self::$subQueries[$select.self::$currentSubQueryNumber]=self::$currentSubQueryNumber;
		$value($query);
		self::makeDefaultSubQueryData();
		self::$selectedFields[self::$className][$select]=$select;
	}
	return self::$instance;
}

public static function addOnlySelect(array $fields){
	if(self::$currentSubQueryNumber==NULL){
			// If addOnlySelect function was used after using select or addSelect function //
		if(self::$instance!==NULL && self::$select!==self::$table.'.*'){
			throw new \Exception("You need to use only addOnlySelect function to select the data", 1);
		}
		self::boot();
		self::$select=NULL;
		self::$addSelect=TRUE;
		return self::addingSelect($fields);
	}else{
		$check=self::showCurrentSubQuery();
			// If addOnlySelect function was used after using select or addSelect function //
		if(self::${$check}[self::$currentField.self::$currentSubQueryNumber]['select']!==self::${$check}[self::$currentField.self::$currentSubQueryNumber]['table'] . '.*'
	){
			throw new \Exception("You need to use only addOnlySelect function to select the data", 1);
	}


	self::${$check}[self::$currentField.self::$currentSubQueryNumber]['select']=NULL;
	self::${$check}[self::$currentField.self::$currentSubQueryNumber]['addSelect']=TRUE;
	foreach ($fields as $select => $value) {
		if(!is_callable($value)){
			throw new \Exception("You need to add function in array in addSelect function or addOnlySelect function.", 1);
		}
		self::makeSubQueryInSubQuery('selectQuery',$value,$select,$check);
	}
}
return self::$instance;
}

public static function paginate($per_page=10){
	self::boot();
	$pageCheck=pageCheck();
	$current_page=$pageCheck ? intval($_GET['page']) : 1;
	$start=($current_page>1) ? ($per_page*($current_page-1)) : 0 ;




	$selectData=self::getSelect();
	$getWhere=self::getWhere();
	$getWhereIn=self::getWhereIn();
	$getWhereNotIn=self::getWhereNotIn();
	$getOrWhere=self::getOrWhere();
	$getOrder=self::getOrder();
	$countData=self::countData();
	$getGroupBy=self::getGroupBy();

	$mainSQL=$selectData .
	$getWhere.
	$getWhereIn.
	$getWhereNotIn.
	$getOrWhere.
	$getOrder.
	$getGroupBy;

	$sql=$mainSQL . " LIMIT ".$per_page." OFFSET ".$start;

	$fields=self::getFields();
	$pdo=self::$instance->connectDatabase();
	$stmt=$pdo->prepare($sql);
	bindValues($stmt,$fields);
	$stmt->execute();

	$countSQL=$countData.
	$getWhere.
	$getWhereIn.
	$getWhereNotIn.
	$getOrWhere;

	$countStmt=$pdo->prepare($countSQL);
	$countStmt->execute( $fields );

	$objectArray=$stmt->fetchAll(PDO::FETCH_CLASS,get_called_class());
	self::$selectedFields=[];
	self::$select=self::$table=NULL;

	$total=intval($countStmt->fetchColumn());
	$total_pages=ceil($total/$per_page);
	$next_page=$current_page+1;
	$previous_page=$pageCheck && $_GET['page']-1>=1 ? $_GET['page']-1 : NULL ;
	$from=$start+1;

	$domainName=getDomainName();
	$totalPerPage=count($objectArray);
	$to=($from+$totalPerPage)-1;

	self::disableBooting();
	return [ 
		'current_page' => $current_page,
		'data'=> $objectArray,
		'first_page_url'=> $domainName.'?page=1',
		'from' => $from > $total_pages ? NULL : $from,
		'last_page' => $total_pages,
		'last_page_url' => $domainName . '?page='.$total_pages,
		'next_page_url' => $next_page<=$total_pages ? $domainName . '?page='.$next_page : NULL,
		'path' => $domainName,
		'per_page' => $per_page,
		'prev_page_url' => $previous_page!==NULL ? $domainName . '?page='.$previous_page : NULL,
		'to' => $to<=0 || $to>$total ? NULL: $to,
		'total' => $totalPerPage
	];
}

private static function getJoin($sqlArray,$joinSQL){
	foreach($sqlArray as $table => $related){
		self::$joinSQL .=$joinSQL . $table . " ON ".$related[1].$related[2].$related[0];
	}
}

private static function getSubQueryJoin($where,$sqlArray,$joinSQL){
	foreach($sqlArray as $table => $related){
		self::${$where}[self::$currentField.self::$currentSubQueryNumber]['joinSQL'] .=$joinSQL . $table . " ON ".$related[1].$related[2].$related[0];
	}
}

private static function getJoinSQL(){
	return self::$joinSQL;
}

private static function getSubQueryJoinSQL($where){
	return self::${$where}[self::$currentField.self::$currentSubQueryNumber]['joinSQL'];
}

private static function makeSubQueryJoin($table,$field,$operator,$ownField,$join){
	$sqlArray=[];
	$sqlArray[$table]=[$ownField,$field,$operator];
	self::getSubQueryJoin(self::showCurrentSubQuery(),$sqlArray,$join);
}

private static function makeJoin($table,$field,$operator,$ownField,$join){
	$sqlArray=[];
	$sqlArray[$table]=[$ownField,$field,$operator];
	self::getJoin($sqlArray,$join);
}

public static function innerJoin($table,$field,$operator,$ownField){
	$join=' INNER JOIN ';
	if(self::$currentSubQueryNumber==NULL){
		self::boot();
		self::makeJoin($table,$field,$operator,$ownField,$join);
	}else{
		self::makeSubQueryJoin($table,$field,$operator,$ownField,$join);
	}
	return self::$instance;
}

public function leftJoin($table,$field,$operator,$ownField){
	$join=' LEFT JOIN ';
	if(self::$currentSubQueryNumber==NULL){
		self::boot();
		self::makeJoin($table,$field,$operator,$ownField,$join);
	}else{
		self::makeSubQueryJoin($table,$field,$operator,$ownField,$join);
	}
	return self::$instance;
}

public function rightJoin($table,$field,$operator,$ownField){
	$join=' RIGHT JOIN ';
	if(self::$currentSubQueryNumber==NULL){
		self::boot();
		self::makeJoin($table,$field,$operator,$ownField,$join);
	}else{
		self::makeSubQueryJoin($table,$field,$operator,$ownField,$join);
	}
	return self::$instance;
}

public function refersTo($class,$field,$referField='id'){
	if(isset($this->{$field})){
		return $class::findBy($referField,$this->{$field});
	}
	throw new \Exception($field .' is not available', 1);
}

public function refersMany($class,$field,$referField='id'){
	if(isset($this->{$referField})){
		$classObject=new $class;
		return $class::where($classObject->getTable() . '.'.$field,$this->{$referField}); 
	}
	throw new \Exception($referField .' is not available', 1);
	
}
}