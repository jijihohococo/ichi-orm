<?php

namespace JiJiHoHoCoCo\IchiORM\Database;
use PDO;
use JiJiHoHoCoCo\IchiORM\Observer\{ModelObserver,ObserverSubject};
use JiJiHoHoCoCo\IchiORM\Pagination\Paginate;
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
	private static $selectQuery;
	protected static $observerSubject;
	private static $subQueryLimitNumber=0;
	private static $useUnionQuery=[ 0 => TRUE ];
	private static $unionQuery=[ 0 => NULL ];
	private static $unionNumber , $currentUnionNumber = 0;
	private static $unableUnionQuery=[];

	protected function connectDatabase(){
		return connectPDO();
	}

	protected function getTable(){
		return getTableName((string)get_called_class());
	}

	protected function getID(){
		return "id";
	}

	protected function autoIncrementId(){
		return TRUE;
	}

	public static function withTrashed(){
		self::checkInstance();
		if(self::$currentSubQueryNumber==NULL){
			self::checkUnionQuery();
			self::boot();
			self::$withTrashed=TRUE;
		}else{
			$currentQuery=self::showCurrentSubQuery();
			self::checkSubQueryUnionQuery($currentQuery);
			self::makeSubQueryTrashTrue($currentQuery);
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

	private static function checkInstance(){
		if(self::$className!==NULL && self::$className!==get_called_class()){
			throw new \Exception(showDuplicateModelMessage(get_called_class(),self::$className), 1);
		}
	}

	private static function checkUnionQuery(){
		if(isset(self::$unableUnionQuery[self::$currentUnionNumber]) && self::$unableUnionQuery[self::$currentUnionNumber]!==NULL ){
			throw new \Exception("You are not allowed to use", 1);
		}
	}

	private static function checkSubQueryUnionQuery($where){
		if(isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber.'unableUnionQuery']) && 
			self::${$where}[self::$currentField.self::$currentSubQueryNumber.'unableUnionQuery']==FALSE ){
			throw new \Exception("You are not allowed to use", 1);
	}
}

private static function checkBoot(){
	if(self::$instance!==NULL){
		throw new \Exception("CRUD functions and querying are different", 1);
	}
}

private static function boot(){
	$calledClass=get_called_class();

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

public static function groupBy(string $groupBy){
	self::checkInstance();
	if(self::$currentSubQueryNumber==NULL){
		self::checkUnionQuery();
		self::boot();
		self::$groupBy=self::$groupByString . $groupBy;
	}else{
		$currentQuery=self::showCurrentSubQuery();
		self::checkSubQueryUnionQuery($currentQuery);
		self::makeSubQueryGroupBy($currentQuery,$groupBy);
	}
	return self::$instance;
}

public static function having(string $field,string $operator,$value){
	self::checkInstance();
	if(self::$currentSubQueryNumber==NULL){
		self::checkUnionQuery();
		self::boot();
		if(self::$havingNumber==NULL){
			self::$havingNumber=0;
		}
		self::$havingField[self::$havingNumber]=$field;
		self::$havingOperator[self::$havingNumber]=$operator;
		self::$havingValue[self::$havingNumber]=$value;
		self::$havingNumber++;
	}else{
		$currentQuery=self::showCurrentSubQuery();
		self::checkSubQueryUnionQuery($currentQuery);
		self::makeSubQueryHaving($currentQuery,$field,$operator,$value);
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
	self::checkInstance();
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
	self::checkBoot();
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
	self::checkBoot();
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
	if($instance->autoIncrementId()==TRUE){
		unset($arrayKeys[$getID]);
	}
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
	$className=self::$className;
	self::disableBooting();

	self::makeObserver($className,'create',$object);

	return $object;
}

public static function makeObserver(string $className , string $method , $parameters){
	if(self::$observerSubject!==NULL && self::$observerSubject->check($className) ){
		self::$observerSubject->use($className,$method,$parameters);
	}
}

public function update(array $attribute){
	self::checkBoot();
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
	self::makeObserver((string)get_class($this),'update',$object);
	return $object;

}

public static function find($id){
	self::checkBoot();
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

public static function findBy(string $field,$value){
	self::checkBoot();
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
	self::checkBoot();
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
	self::makeObserver((string)get_class($this),'delete',$this);
}

public function forceDelete(){
	self::checkBoot();
	$id=$this->getID();
	$table=$this->getTable();
	$stmt=$this->connectDatabase()->prepare("DELETE FROM ".$table." WHERE ".$id.'='.$this->{$id});
	self::makeObserver((string)get_class($this),'forceDelete',$this);
	$stmt->execute();
}

public function restore(){
	self::checkBoot();
	$id=$this->getID();
	$pdo=$this->connectDatabase();
	$table=$this->getTable();
	if(property_exists($this, 'deleted_at')){
		$stmt=$pdo->prepare("UPDATE ".$table." SET deleted_at=NULL WHERE ".$id."=".$this->{$id});
		$stmt->execute();
	}
	self::makeObserver((string)get_class($this),'restore',$this);
}


public static function select(array  $fields){
	self::checkInstance();
	if(self::$currentSubQueryNumber==NULL){
		self::checkUnionQuery();
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
		self::checkSubQueryUnionQuery($check);
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

public static function limit(int $limit){
	self::checkInstance();
	if(self::$currentSubQueryNumber==NULL){
		self::checkUnionQuery();
		self::boot();
		self::$limit=' LIMIT '.$limit;
	}else{
		$check=self::showCurrentSubQuery();
		self::checkSubQueryUnionQuery($check);
		self::${$check}[self::$currentField.self::$currentSubQueryNumber]['limit']=$limit;
		self::$subQueryLimitNumber++;
	}
	return self::$instance;
}

private static function makeSubQueryAttributes($previousField=NULL){
	return [
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
		'table' => $previousField!==NULL && isset($previousField['table']) ? $previousField['table'] : self::$table,
		'select'=>  $previousField!==NULL && isset($previousField['table']) ? $previousField['table'].'.*' :  self::$table.'.*',
		'className' => NULL,
		'object' => NULL,
		'havingNumber' => NULL ,
		'havingField' => NULL ,
		'havingOperator' => NULL ,
		'havingValue' => NULL ,
		'selectQuery' => NULL
	];
}

private static function setSubQuery($field,$where,bool $increase=TRUE){
	if($increase==TRUE){
		self::$numberOfSubQueries++;
	}
	$previousCheck=self::$currentSubQueryNumber!==NULL ? self::showCurrentSubQuery() : NULL;
	$previousField=$previousCheck!==NULL ? self::${$previousCheck}[self::$currentField.self::$currentSubQueryNumber] : NULL  ;
	self::$currentSubQueryNumber=self::$numberOfSubQueries;
	self::$currentField=$field;
	self::${$where}[self::$currentField.self::$currentSubQueryNumber]=self::makeSubQueryAttributes($previousField);
}

private static function setSubWhere($where,$value,$field,$operator,$whereSelect){
	self::${$where}[self::$currentField.self::$currentSubQueryNumber][$whereSelect][$field]=$value;
	self::${$where}[self::$currentField.self::$currentSubQueryNumber]['operators'][$field.$whereSelect]=makeOperator($operator);
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

public static function from(string $className){
	
	checkClass($className);

	self::checkInstance();
	if(self::$currentSubQueryNumber!==NULL){
		$currentQuery=self::showCurrentSubQuery();
		self::checkSubQueryUnionQuery($currentQuery);
		self::addTableToSubQuery($currentQuery,$className);
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
	self::checkInstance();
	$countParameters=count($parameters);
	$value=$operator=$field=NULL;
	if($countParameters==2 || $countParameters==3 ){
		$field=$parameters[0];

		if(!is_string($parameters[0])){
			throw new \Exception("You must add field name in string", 1);
		}

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
			self::checkUnionQuery();
			self::boot();
			self::${$where}[$field]=$value;
			self::$operators[$field.$where]=makeOperator($operator);
			if($value!==NULL && $where!=='whereColumn' ){
				self::$fields[]=$value;
			}
		}elseif(is_callable($value) && self::$currentSubQueryNumber==NULL ){
			self::checkUnionQuery();
			self::boot();
			self::$operators[$field.$where]=makeOperator($operator);
			$query=self::$instance;
			$query->setSubQuery($field,$where);
			self::$subQueries[$field.self::$currentSubQueryNumber]=self::$currentSubQueryNumber;
			$value($query);
			self::makeDefaultSubQueryData();
		}elseif(!is_callable($value) && self::$currentSubQueryNumber!==NULL ){
			$currentQuery=self::showCurrentSubQuery();
			self::checkSubQueryUnionQuery($currentQuery);
			self::setSubWhere($currentQuery,$value,$field,$operator,$where);
			if($value!==NULL && $where!=='whereColumn' ){
				self::$fields[]=$value;
			}
		}elseif(is_callable($value) && self::$currentSubQueryNumber!==NULL ){
			$check=self::showCurrentSubQuery();
			self::checkSubQueryUnionQuery($check);
			self::${$check}[self::$currentField.self::$currentSubQueryNumber]['operators'][$field.$where]=makeOperator($operator);
			self::makeSubQueryInSubQuery($where,$value,$field,$check);
		}
	}else{
		throw new \Exception("Invalid Argument Parameter", 1);
	}
}

private static function makeInQuery($whereIn,$field,$value){

	self::checkInstance();

	if(!is_array($value) && !is_callable($value) && $value!==NULL ){
		throw new \Exception("You can add only array values or sub query in {$whereIn} function", 1);
	}

	if((is_array($value) || $value==NULL) && self::$currentSubQueryNumber==NULL){
		self::checkUnionQuery();
		self::boot();
		self::${$whereIn}[$field]=$value;
		if($value!==NULL){
			self::$fields[]=$value;
		}
	}elseif(is_callable($value) && self::$currentSubQueryNumber==NULL ){
		self::checkUnionQuery();
		self::boot();
		$query=self::$instance;
		$query->setSubQuery($field,$whereIn,$field);
		self::$subQueries[$field.self::$currentSubQueryNumber]=self::$currentSubQueryNumber;
		$value($query);
		self::makeDefaultSubQueryData();
	}elseif((is_array($value) || $value==NULL) && self::$currentSubQueryNumber!==NULL ){
		$currentQuery=self::showCurrentSubQuery();
		self::checkSubQueryUnionQuery($currentQuery);
		self::setSubWhereIn($currentQuery,$value,$field,$whereIn);
		if($value!==NULL){
			self::$fields[]=$value;
		}
	}elseif(is_callable($value) && self::$currentSubQueryNumber!==NULL ){
		$currentQuery=self::showCurrentSubQuery();
		self::checkSubQueryUnionQuery($currentQuery);
		self::makeSubQueryInSubQuery($whereIn,$value,$field,$currentQuery);
	}
}

public static function whereIn(string $field,$value){
	self::makeInQuery('whereIn',$field,$value);
	return self::$instance;
}

public static function whereNotIn(string $field,$value){
	self::makeInQuery('whereNotIn',$field,$value);
	return self::$instance;
}

private static function getLimit(){ return self::$limit; }

private static function getSubQueryLimit($where){
	if(isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber])){
		$limit= self::${$where}[self::$currentField.self::$currentSubQueryNumber]['limit'];
		return $limit==NULL ? $limit : ' LIMIT '.$limit;
	}
}

private static function getSubQueryLimitNumber(){
	return self::$subQueryLimitNumber;
}

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

public static function orderBy(string $field,string $sort="ASC"){
	self::checkInstance();
	if(self::$currentSubQueryNumber==NULL){
		self::checkUnionQuery();
		self::boot();
		self::$order=" ORDER BY ".$field . " ".  $sort;
	}else{
		$currentQuery=self::showCurrentSubQuery();
		self::checkSubQueryUnionQuery($currentQuery);
		self::makeSubQueryOrderBy($currentQuery,$field,$sort);
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

public static function latest(string $field=null){
	self::checkInstance();
	if(self::$currentSubQueryNumber==NULL){
		self::checkUnionQuery();
		self::boot();
		$field=$field==null ? self::$instance->getID() : $field;
		self::$order=" ORDER BY " . self::$table . '.'  . $field . " DESC";
	}else{
		$currentQuery=self::showCurrentSubQuery();
		self::checkSubQueryUnionQuery($currentQuery);
		self::makeSubQueryOrderBy($currentQuery,$field," DESC");
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
	self::$subQueryLimitNumber=0;

	self::$useUnionQuery=[ 0 => TRUE ];
	self::$unionQuery=[ 0 => NULL ];
	self::$unionNumber = self::$currentUnionNumber = 0;
	self::$unableUnionQuery=[];
}

private static function disableForSQL(){
	self::$instance=self::$getID=self::$table=self::$where=self::$whereColumn=self::$orWhere=self::$whereIn=self::$whereNotIn=self::$operators=self::$order=self::$limit=self::$groupBy=self::$joinSQL=self::$select=self::$addSelect=self::$withTrashed=self::$addTrashed=self::$className=self::$numberOfSubQueries=self::$currentSubQueryNumber=self::$currentField=self::$whereSubQuery=self::$subQuery=self::$havingNumber=self::$havingField=self::$havingOperator=self::$havingValue=self::$selectQuery=NULL;
	self::$subQueries=[];
	self::$toSQL=FALSE;
	self::$subQueryLimitNumber=0;

	self::$useUnionQuery=[ 0 => TRUE ];
	self::$unionQuery=[ 0 => NULL ];
	self::$unionNumber = self::$currentUnionNumber = 0;
	self::$unableUnionQuery=[];
}

public static function union(callable $value){
	return self::makeUnionQuery($value,' UNION ');
}

public static function unionAll(callable $value){
	return self::makeUnionQuery($value,' UNION ALL ');
}

private static function checkUnion(){
	return  isset(self::$unionQuery[self::$currentUnionNumber]) && self::$unionQuery[self::$currentUnionNumber]!==NULL && self::$useUnionQuery[self::$currentUnionNumber]==TRUE;
}

private static function getQuery(){
	return self::checkUnion() ? self::$unionQuery[self::$currentUnionNumber] : self::getSQL();
}

private static function makeUnionQuery($value,$union){
	if(self::$currentSubQueryNumber==NULL){
		$previousQuery=self::getQuery();
		self::disableForSQL();
		$uNumber=self::$currentUnionNumber;
		self::$useUnionQuery[$uNumber]=FALSE;
		self::$unionNumber++;
		$newUnionQuery=$value();
		self::$useUnionQuery[$uNumber]=TRUE;
		self::$currentUnionNumber=$uNumber;
		self::$unableUnionQuery[$uNumber]=TRUE;
		self::boot();
		self::$unionQuery[$uNumber]=$previousQuery . $union . $newUnionQuery;
		return self::$instance;
	}else{
		$currentQuery=self::showCurrentSubQuery();
		$currentField=self::$currentField;
		$currentSubQueryNumber=self::$currentSubQueryNumber;
		if(isset(self::${$currentQuery}[$currentField.$currentSubQueryNumber.'unableUnionQuery']) &&
			self::${$currentQuery}[$currentField.$currentSubQueryNumber.'unableUnionQuery']==TRUE
		){
			throw new \Exception("You are not allowed to use ".$union, 1);
	}

	$previousUnionQuery=isset(self::${$currentQuery}[$currentField.$currentSubQueryNumber.'unionQuery']) ?
	self::${$currentQuery}[$currentField.$currentSubQueryNumber.'unionQuery'] : NULL;
	$previousField=self::${$currentQuery}[$currentField.$currentSubQueryNumber];
	if($previousUnionQuery==NULL){
		self::makeSubQuery( $currentQuery );
		$previousQuery = self::${$currentQuery}[$currentField];
		$query=self::$instance;
		$query->setSubQuery($currentField,$currentQuery,FALSE);
		self::$subQueries[$currentField.$currentSubQueryNumber]=$currentSubQueryNumber;
		self::${$currentQuery}[$currentField.$currentSubQueryNumber.'unableUnionQuery']=TRUE;
		$value($query);
		self::$currentField=$currentField;
		self::$currentSubQueryNumber=$currentSubQueryNumber;
		self::${$currentQuery}[$currentField.$currentSubQueryNumber.'unionQuery']=substr($previousQuery,0,-1) . $union .  self::${$currentQuery}[$currentField] . ')';
		self::${$currentQuery}[$currentField.$currentSubQueryNumber.'unableUnionQuery']=FALSE;
		self::${$currentQuery}[$currentField.$currentSubQueryNumber]=self::makeSubQueryAttributes($previousField);

	}else{
		self::${$currentQuery}[self::$currentField.self::$currentSubQueryNumber.'unableUnionQuery']=TRUE;
		unset(self::${$currentQuery}[self::$currentField]);
		self::$subQueries[$currentField.$currentSubQueryNumber]=$currentSubQueryNumber;
		$query=self::$instance;
		$value($query);
		self::$currentField=$currentField;
		self::$currentSubQueryNumber=$currentSubQueryNumber;
		self::${$currentQuery}[$currentField.$currentSubQueryNumber.'unionQuery']=substr($previousUnionQuery,0,-1) . $union .  self::${$currentQuery}[$currentField] . ')';
		self::${$currentQuery}[$currentField.$currentSubQueryNumber.'unableUnionQuery']=FALSE;
		self::${$currentQuery}[$currentField.$currentSubQueryNumber]=self::makeSubQueryAttributes($previousField);
	}
}
return self::$instance;
}

public static function get(){
	self::checkInstance();
	if(self::$currentSubQueryNumber==NULL){
		self::boot();
		$mainSQL=self::getQuery();
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

private static function makeMainSubQuery($where,$mainSQL){
	self::$subQuery=$mainSQL;
	$currentField=self::$currentField;
	$currentSubQueryNumber=self::$currentSubQueryNumber;
	if(self::$currentField . self::$currentSubQueryNumber==array_key_first(self::$subQueries)){
		self::${$where}[self::$currentField]=$mainSQL;
		if($where=='where' || $where=='whereColumn' || $where=='orWhere' ){
			self::$whereSubQuery[self::$currentField.$where]='whereSubQuery';
		}
		self::$subQueries=[];
		self::makeDefaultSubQueryData();
	}

	if(isset(self::${$where}[$currentField.$currentSubQueryNumber])){
		unset(self::${$where}[$currentField.$currentSubQueryNumber]);
	}
}

private static function makeSubQuery($where){
	if(isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber.'unionQuery']) &&
		isset(self::${$where}[self::$currentField.self::$currentSubQueryNumber.'unableUnionQuery']) &&
		self::${$where}[self::$currentField.self::$currentSubQueryNumber.'unableUnionQuery']==FALSE ){
		$currentField=self::$currentField;
	$currentSubQueryNumber=self::$currentSubQueryNumber;
	self::${$where}[self::$currentField]=self::${$where}[self::$currentField.self::$currentSubQueryNumber.'unionQuery'];
	if($where=='where' || $where=='whereColumn' || $where=='orWhere' ){
		self::$whereSubQuery[self::$currentField.$where]='whereSubQuery';
	}
	self::$subQueries=[];
	self::makeDefaultSubQueryData();
	unset(self::${$where}[$currentField.$currentSubQueryNumber.'unionQuery']);
	unset(self::${$where}[$currentField.$currentSubQueryNumber.'unableUnionQuery']);
	if(isset(self::${$where}[$currentField.$currentSubQueryNumber])){
		unset(self::${$where}[$currentField.$currentSubQueryNumber]);
	}
}else{
	$mainSQL=self::getSubQuery($where);
	self::makeMainSubQuery($where,'('.$mainSQL.')');
}

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
	$limit=self::getSubQueryLimit($where);
	$result=self::getSubQuerySelect($where).
	self::getSubQueryWhere($where).
	self::getSubQueryWhereColumn($where).
	self::getSubQueryWhereIn($where).
	self::getSubQueryWhereNotIn($where).
	self::getSubQueryOrWhere($where).
	self::getSubQueryOrder($where).
	self::getSubQueryGroupBy($where).
	self::getSubQueryHaving($where);
	return $limit==NULL ? $result : "SELECT * FROM (".$result.$limit.") AS l".self::getSubQueryLimitNumber();
}

public static function toArray(){
	self::checkInstance();
	if(self::$currentSubQueryNumber!==NULL){
		throw new \Exception("Please use get() function in sub query to get sub query", 1);
	}
	if(self::$currentUnionNumber!==0){
		throw new \Exception("Please use toArray function in main query", 1);
	}
	self::boot();
	$mainSQL=self::getQuery();
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
	self::checkInstance();
	if(self::$currentSubQueryNumber!==NULL){
		throw new \Exception("Don't use toSQL() function in sub query", 1);
	}
	self::boot();
	self::$toSQL=TRUE;
	return self::$instance;
}

public static function addSelect(array $fields){
	self::checkInstance();
	if(self::$currentSubQueryNumber==NULL){
		self::checkUnionQuery();
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
	self::checkInstance();
	if(self::$currentSubQueryNumber==NULL){
		self::checkUnionQuery();
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
		self::checkSubQueryUnionQuery($check);
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

public static function paginate(int $per_page=10){
	self::checkInstance();
	if(self::$currentSubQueryNumber!==NULL){
		throw new \Exception("You can't use paginate() function in sub queries.", 1);
	}
	if(self::$currentUnionNumber!==0){
		throw new \Exception("Please use paginate function in main query", 1);
	}
	self::boot();
	$paginate=new Paginate;
	$paginate->setPaginateData($per_page);




	$selectData=self::getSelect();
	$getWhere=self::getWhere();
	$getWhereIn=self::getWhereIn();
	$getWhereNotIn=self::getWhereNotIn();
	$getOrWhere=self::getOrWhere();
	$getOrder=self::getOrder();
	$getGroupBy=self::getGroupBy();
	$getHaving=self::getHaving();

	$mainSQL=self::checkUnion() ? self::$unionQuery[self::$currentUnionNumber] :
	$selectData .
	$getWhere.
	$getWhereIn.
	$getWhereNotIn.
	$getOrWhere.
	$getOrder.
	$getGroupBy.
	$getHaving;

	$sql="SELECT * FROM (" . $mainSQL . ") AS paginate_data LIMIT ".$per_page." OFFSET ".$paginate->getStart();

		$fields=self::getFields();
		$pdo=self::$instance->connectDatabase();
		$stmt=$pdo->prepare($sql);
		bindValues($stmt,$fields);
		$stmt->execute();

		$countSQL='SELECT COUNT(*) FROM ('.$mainSQL.') AS countData';

			$countStmt=$pdo->prepare($countSQL);
			$countStmt->execute( $fields );

			$objectArray=$stmt->fetchAll(PDO::FETCH_CLASS,get_called_class());
			self::$selectedFields=[];
			self::$select=self::$table=NULL;
			self::disableBooting();

			return $paginate->paginate(
				intval($countStmt->fetchColumn()),
				$objectArray
			);
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

		public static function innerJoin(string $table,string $field,string $operator,string $ownField){
			self::checkInstance();
			$join=' INNER JOIN ';
			if(self::$currentSubQueryNumber==NULL){
				self::boot();
				self::makeJoin($table,$field,$operator,$ownField,$join);
			}else{
				self::makeSubQueryJoin($table,$field,$operator,$ownField,$join);
			}
			return self::$instance;
		}

		public function leftJoin(string $table,string $field,string $operator,string $ownField){
			self::checkInstance();
			$join=' LEFT JOIN ';
			if(self::$currentSubQueryNumber==NULL){
				self::boot();
				self::makeJoin($table,$field,$operator,$ownField,$join);
			}else{
				self::makeSubQueryJoin($table,$field,$operator,$ownField,$join);
			}
			return self::$instance;
		}

		public function rightJoin(string $table,string $field,string $operator,string $ownField){
			self::checkInstance();
			$join=' RIGHT JOIN ';
			if(self::$currentSubQueryNumber==NULL){
				self::boot();
				self::makeJoin($table,$field,$operator,$ownField,$join);
			}else{
				self::makeSubQueryJoin($table,$field,$operator,$ownField,$join);
			}
			return self::$instance;
		}

		protected function refersTo(string $class,string $field,string $referField='id'){
			checkClass($class);
			self::checkBoot();
			if(isset($this->{$field})){
				return $class::findBy($referField,$this->{$field});
			}
			throw new \Exception($field .' is not available', 1);
		}

		protected function refersMany(string $class,string $field,string $referField='id'){
			checkClass($class);
			self::checkBoot();
			if(isset($this->{$referField})){
				$classObject=new $class;
				return $class::where($classObject->getTable() . '.'.$field,$this->{$referField}); 
			}
			throw new \Exception($referField .' is not available', 1);

		}

		public static function observe(ModelObserver $modelObserver){
			self::checkBoot();
			checkObserverFunctions($modelObserver);
			if(self::$observerSubject==NULL){
				self::$observerSubject=new ObserverSubject;
			}
			$className=(string)get_called_class();
			self::$observerSubject->attach($className , $modelObserver);
		}
	}