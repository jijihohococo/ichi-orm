<?php

namespace JiJiHoHoCoCo\IchiORM\Database;
use PDO;
abstract class Model{

	private static $limitOne=" LIMIT 1";

	private static $pdo,$instance,$id,$table,$fields,$where,$whereColumn,$orWhere,$whereIn,$whereNotIn,$operators,$order,$limit,$groupBy,$joinSQL,$select,$addSelect,$withTrashed,$addTrashed,$className,$toSQL;
	private static $numberOfSubQueries,$currentSubQueryNumber,$currentField,$whereSubQuery;
	private static $subQuery;
	private static $subQueries=[];
	private static $havingNumber=NULL;
	private static $havingField,$havingOperator,$havingValue;
	private const WHERE_ZERO=' WHERE 0 = 1 ';
	private const AND_ZERO=' AND 0 = 1 ';
	private const GROUP_BY=' GROUP BY ';

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
		return "SELECT ".self::$select." FROM ".self::$table.self::getJoinSQL();
	}

	private static function getSubQuerySelect($where){
		$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
		return "SELECT ".str_replace(self::$table.'.*', $current['table'].'.*', $current['select'])." FROM ".$current['table'].self::getSubQueryJoinSQL($where);
	}

	private static function countData(){
		$table=self::$table;
		return "SELECT COUNT(".$table.".".self::$id.") FROM ".$table. self::getJoinSQL();
	}

	public static function connect(PDO $pdo){
		self::$pdo=$pdo;
		self::boot();
		return self::$instance;
	}

	private static function boot(){
		if(self::$instance==NULL){
			self::$fields=NULL;
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
			self::$className=get_called_class();
			self::$id=self::$instance->getID();
			self::$table=self::$instance->getTable();
			self::$select=self::$table.'.*';
			self::$addSelect=FALSE;
			self::$withTrashed=FALSE;
			self::$toSQL=FALSE;
			self::$subQuery=NULL;
			self::$addTrashed=FALSE;
			if(self::$pdo==NULL){
				self::$pdo=connectPDO();
			}
		}
	}

	public static function groupBy($groupBy){
		if(self::$currentSubQueryNumber==NULL){
			self::boot();
			self::$groupBy=self::GROUP_BY . $groupBy;
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
		self::${$where}[self::$currentField.self::$currentSubQueryNumber]['groupBy']=self::GROUP_BY . $groupBy;
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
		$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
		if($current['havingNumber']!==NULL){
			foreach (range(0, $current['havingNumber']-1) as $key => $value) {
				$result=$currentField['havingField'][$key] . ' ' . $currentField['havingOperator'][$key] . ' ' . $currentField['havingValue'][$key];
				$string .=$key==0 ? ' HAVING ' . $result : ' AND ' . $result;
			}
		}
		return $string;
	}

	private static function getSubQueryGroupBy($where){
		return self::${$where}[self::$currentField.self::$currentSubQueryNumber]['groupBy'];
	}

	private function checkAndPutData($field,$data){
		return $this->checkProperty($field) ? [ $field => $data ] : [];
	}

	public static function create(array $data){
		self::boot();
		$instance=self::$instance;
		
		$createdData=$data+$instance->checkAndPutData('created_at',now());

		$fields=array_keys($createdData);
		$insertedData=array_values($createdData);

		$stmt=self::$pdo->prepare("INSERT INTO ".self::$table." (". implode(',', $fields ) .")  VALUES (". addArray($fields).")");
		self::bindValues($stmt,$insertedData);
		$stmt->execute();
		$id=self::$instance->getID();

		$object= mappingModelData([
			$id => self::$pdo->lastInsertId()
		], array_combine($fields,$insertedData) , $instance );
		self::disableBooting();
		return $object;
	}

	public function update(array $data){
		$createdData=$data+$this->checkAndPutData('updated_at',now());
		$fields=array_keys($createdData);
		$updatedData=array_values($createdData);
		$id=$this->getID();
		$stmt=connectPDO()->prepare("UPDATE ".$this->getTable()." SET ".implode("=?,", $fields)."=? WHERE ".$id."=".$this->{$id});
		self::bindValues($stmt,$updatedData);
		$stmt->execute();
		return mappingModelData([
			$id => $this->{$id}
		], array_combine($fields,$updatedData) , $this );
	}

	public static function find($id){
		self::boot();
		$pdo=self::$pdo;
		$getId=self::$instance->getID();
		$stmt=$pdo->prepare(self::getSelect() . " WHERE ".$getId ." = ? ".self::$limitOne);
		self::bindValues($stmt,[
			0 => $id
		]);
		$stmt->execute();
		$instance=$stmt->fetchObject(self::$className);
		self::disableBooting();
		return $instance;
	}

	public static function findBy($field,$value){
		self::boot();
		$pdo=self::$pdo;
		$stmt=$pdo->prepare(self::getSelect() . " WHERE ".$field." = ? ".self::$limitOne);
		self::bindValues($stmt,[
			0 => $value
		]);
		$stmt->execute();
		$instance=$stmt->fetchObject(self::$className);
		self::disableBooting();
		return $instance;
	}

	public function delete(){
		$id=$this->getID();
		$table=$this->getTable();
		$pdo=connectPDO();
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
		$stmt=connectPDO()->prepare("DELETE FROM ".$table." WHERE ".$id.'='.$this->{$id});
		$stmt->execute();
	}

	public function restore(){
		$id=$this->getID();
		$pdo=connectPDO();
		$table=$this->getTable();
		if(property_exists($this, 'deleted_at')){
			$stmt=$pdo->prepare("UPDATE ".$table." SET deleted_at=NULL WHERE ".$id."=".$this->{$id});
			$stmt->execute();
		}
	}

	private static function getWhereTypes(){
		return ['where','whereColumn','whereIn','whereNotIn','orWhere'];
	}


	public static function select(array  $fields){
		if(self::$currentSubQueryNumber==NULL){
			self::boot();
			if(self::$addSelect==FALSE){
				self::$select=NULL;
			}else{
				self::$select.=',';
			}
			
			foreach($fields as $key => $field){
				self::$select  .= $key+1==count($fields) ? $field : $field . ',';
			}
		}else{

			$check=self::showCurrentSubQuery();

			if(self::checkSubQueryAddSelect($check)==TRUE){
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

	private static function makeSubQueryAddSelect($where){
		self::${$where}[self::$currentField.self::$currentSubQueryNumber]['addSelect']=TRUE;
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
			'havingValue' => NULL
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
		foreach(self::getWhereTypes() as $where){
			if(self::checkSubQuery($where)){
				return $where;
			}
		}
	}

	private static function databaseOperators(){
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
			'LIKE'
		];
	}


	public static function where(){
		self::makeWhereQuery(func_get_args(),'where');
		return self::$instance;
	}

	private static function makeSubQueryInSubQuery($whereSelect,$value,$field){
		// if 	there is sub query function in sub query //
		$previousField=self::$currentField;
		$previousSubQueryNumber=self::$currentSubQueryNumber;
		$query=self::$instance;
		$check=self::showCurrentSubQuery();
		$query->setSubQuery($field,$check);
		self::$subQueries[$field.self::$currentSubQueryNumber]=self::$currentSubQueryNumber;
		$value($query);


			// put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//
		self::${$check}[$previousField.$previousSubQueryNumber][$whereSelect]=self::$subQuery;
			// put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//


		self::$currentField=$previousField;
		self::$currentSubQueryNumber=$previousSubQueryNumber;
	}

	public static function from($className){
		if(self::$currentSubQueryNumber!==NULL){
			self::addTableToSubQuery(self::showCurrentSubQuery(),$className);
			return self::$instance;
		}
		throw new \Exception("You can use 'from' function in sub queries", 1);
	}

	private static function getSubQueryClassObject($where,$className){
		if(self::${$where}[self::$currentField.self::$currentSubQueryNumber]['object']==NULL){
			self::${$where}[self::$currentField.self::$currentSubQueryNumber]['object']=new $className;
		}
		return self::${$where}[self::$currentField.self::$currentSubQueryNumber]['object'];
	}

	private static function addTableToSubQuery($where,$className){
		$obj=self::getSubQueryClassObject($where,$className);
		self::${$where}[self::$currentField.self::$currentSubQueryNumber]['table']=$obj->getTable();
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
		if($countParameters==2 || $countParameters==3 ){
			$field=$parameters[0];
			if(isset($parameters[1]) && in_array($parameters[1], self::databaseOperators()) && isset($parameters[2]) ){
				$operator=$parameters[1];
				$value=$parameters[2];
			}elseif(isset($parameters[1]) && !in_array($parameters[1],self::databaseOperators()) ){
				$value=$parameters[1];
				$operator='=';
			}



			if(!is_callable($value) && self::$currentSubQueryNumber==NULL){
				self::boot();
				self::${$where}[$field]=$value;
				self::$operators[$field.$where]=$operator;
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
			}elseif(is_callable($value) && self::$currentSubQueryNumber!==NULL ){
				$check=self::showCurrentSubQuery();
				self::${$check}[self::$currentField.self::$currentSubQueryNumber]['operators'][$field.$where]=$operator;
				self::makeSubQueryInSubQuery($where,$value,$field);
			}
		}else{
			throw new \Exception("Invalid Argument Parameter", 1);
		}
	}

	private static function makeInQuery($whereIn,$field,$value){
		if(is_array($value) && self::$currentSubQueryNumber==NULL){
			self::boot();
			self::${$whereIn}[$field]=$value;
		}elseif(is_callable($value) && self::$currentSubQueryNumber==NULL ){
			self::boot();
			$query=self::$instance;
			$query->setSubQuery($field,$whereIn,$field);
			self::$subQueries[$field.self::$currentSubQueryNumber]=self::$currentSubQueryNumber;
			$value($query);
			self::makeDefaultSubQueryData();
		}elseif(!is_callable($value) && self::$currentSubQueryNumber!==NULL ){
			self::setSubWhereIn(self::showCurrentSubQuery(),$value,$field,$whereIn);
		}elseif(is_callable($value) && self::$currentSubQueryNumber!==NULL ){
			self::makeSubQueryInSubQuery($whereIn,$value,$field);
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

	private function checkProperty($field){
		return property_exists(self::$instance??$this,$field);
	}

	private static function checkSubQueryTrashed($where){
		
		$subClassName=self::${$where}[self::$currentField.self::$currentSubQueryNumber]['className'];
		
		$className=$subClassName==NULL ? self::$instance : $subClassName;

		return property_exists($className,'deleted_at') && self::${$where}[self::$currentField.self::$currentSubQueryNumber]['withTrashed']==FALSE;
	}

	private static function getSubQueryWhere($where){
		$string=NULL;
		$i=0;
		$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
		if($current['where']!==NULL && is_array($current['where']) ){
			$string=' WHERE ';
			//$string=$current['select']==NULL ? NULL : ' WHERE ';
			foreach($current['where'] as $key => $value){
				$operator=$current['operators'][$key.'where'];
				self::$fields[]=$value;
				$string .=$i==0 ? $key . $operator . '?' : ' AND '. $key . $operator . '?';
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
					self::$fields[]=$value;
					$string .= $i==0  ?
					$key . self::$operators[$key.'where'] . '?' :
					' AND ' . $key . self::$operators[$key.'where'] . '?';
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
		$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
		if($current['whereColumn']!==NULL && is_array($current['whereColumn'])){
			foreach($current['whereColumn'] as $key => $value){
				$result=$key. $current['operators'][$key.'whereColumn'] . $value;
				$string .= $i==0 && $current['where']==NULL && $current['addTrashed']==FALSE ? ' WHERE ' . $result  : ' AND '. $result;
			}
		}elseif($current['whereColumn']!==NULL && !is_array($current['whereColumn'])){
			$currentField=getCurrentField(self::$subQueries,self::$currentField,self::$currentSubQueryNumber);
			$result=$currentField . $current['operators'][$currentField.'whereColumn'] . ' ('. $current['whereColumn'] . ') ';
			$string .=$current['where']==NULL && $current['addTrashed']==FALSE ? ' WHERE ' . $result : ' AND ' . $result;
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
		$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
		if($current['orWhere']!==NULL && is_array($current['orWhere'])){
			foreach($current['orWhere'] as $key => $value){
				self::$fields[]=$value;
				$string .= ' OR ' . $key . $current['operators'][$key.'orWhere'] . '?';
			}
		}elseif($current['orWhere']!==NULL && !is_array($current['orWhere'])){
			$currentField=getCurrentField(self::$subQueries,self::$currentField,self::$currentSubQueryNumber);
			$string .= ' OR ' . $currentField . $current['operators'][$currentField.'orWhere'] . ' (' . $current['orWhere'] . ') ';
		}
		return $string;
	}

	private static function addValuesFromArrayToBind(array $value){
		foreach($value as $v){
			if(is_array($v)){
				self::addValuesFromArrayToBind($v);
			}else{
				self::$fields[]=$v;
			}
		}
	}

	private static function getWhereIn(){
		$string=NULL;
		$i=0;
		if(self::$whereIn!==NULL){ 
			foreach(self::$whereIn as $key => $value){
				if(is_array($value) && !empty($value) ){
					$in  = addArray($value);
					$string .=  $i==0 && self::$where==NULL && self::$whereColumn==NULL && self::$addTrashed==FALSE ? ' WHERE '.$key.' IN (' . $in . ') ' : ' AND '.$key.' IN (' . $in . ') ';
					self::addValuesFromArrayToBind($value);
				}elseif($value!==NULL && !is_array($value)){
					$string .=  $i==0 && self::$where==NULL && self::$whereColumn==NULL && self::$addTrashed==FALSE ? ' WHERE '.$key.' IN ' . $value : ' AND '.$key.' IN ' . $value;
				}else{
					$string .= $i==0 && self::$where==NULL && self::$whereColumn==NULL && self::$addTrashed==FALSE ? self::WHERE_ZERO: self::AND_ZERO;
				}
				$i++;
			}
			
		}
		return $string;
	}

	private static function getSubQueryWhereIn($where){
		$string=NULL;
		$i=0;
		$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
		if($current['whereIn']!==NULL && is_array($current['whereIn'])){ 
			foreach($current['whereIn'] as $key => $value){
				if(is_array($value) && !empty($value) ){
					$in  = addArray($value);
					$string .=  $i==0 && $current['where']==NULL && $current['whereColumn']==NULL && $current['addTrashed']==FALSE ? ' WHERE '.$key.' IN (' . $in . ') ' : ' AND '.$key.' IN (' . $in . ') ';
					self::addValuesFromArrayToBind($value);
				}else{
					$string .= $i==0 && $current['where']==NULL && $current['whereColumn']==NULL && $current['addTrashed']==FALSE ? self::WHERE_ZERO : self::AND_ZERO ;
				}
				$i++;
			}
			
		}elseif($current['whereIn']!==NULL && !is_array($current['whereIn'])){
			$currentField=getCurrentField(self::$subQueries,self::$currentField,self::$currentSubQueryNumber);

			$string.=$current['where']==NULL && $current['whereColumn']==NULL && $current['addTrashed']==FALSE ? ' WHERE '.$currentField.' IN (' .  $current['whereIn'] . ')' : ' AND '.$currentField.' IN (' . $current['whereIn'] . ')';
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
					self::addValuesFromArrayToBind($value);
				}elseif($value!==NULL && !is_array($value)){
					$string .=$i==0 && self::$where==NULL && self::$whereColumn==NULL && self::$whereIn==NULL && self::$addTrashed==FALSE ? ' WHERE '.$key. ' NOT IN ' . $value : ' AND ' . $key . ' NOT IN ' .  $value;

				}else{
					$string .=$i==0 && self::$where==NULL && self::$whereColumn==NULL && self::$whereIn==NULL && self::$addTrashed==FALSE ? self::WHERE_ZERO : self::AND_ZERO;
				}
				$i++;
			}
		}
		return $string;
	}

	private static function getSubQueryWhereNotIn($where){
		$string=NULL;
		$i=0;
		$current=self::${$where}[self::$currentField.self::$currentSubQueryNumber];
		if($current['whereNotIn']!==NULL && is_array($current['whereNotIn'])){
			foreach($current['whereNotIn'] as $key => $value){
				if(is_array($value) && !empty($value) ){
					$in=addArray($value);
					$string .=$i==0 && $current['where']==NULL && $current['whereColumn'] && $current['whereIn']==NULL && $current['addTrashed']==FALSE ?
					' WHERE '.$key.' NOT IN (' . $in . ') ' : ' AND '.$key.' NOT IN ('.$in.') ';
					self::addValuesFromArrayToBind($value);
				}else{
					$string .=$i==0 && $current['where']==NULL && $current['whereColumn'] && $current['whereIn']==NULL && $current['addTrashed']==FALSE ? self::WHERE_ZERO : self::AND_ZERO;
				}
				$i++;
			}
		}elseif($current['whereNotIn']!==NULL && !is_array($current['whereNotIn'])){
			$currentField=getCurrentField(self::$subQueries,self::$currentField,self::$currentSubQueryNumber);

			$string.=$current['where']==NULL && $current['whereColumn'] && $current['whereIn']==NULL && $current['addTrashed']==FALSE ? ' WHERE '.$currentField.' NOT IN (' .  $current['whereNotIn'] . ')' : ' AND '.$currentField.' NOT IN (' . $current['whereNotIn'] . ')';
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
		return self::${$where}[self::$currentField.self::$currentSubQueryNumber]['order'];
	}

	private static function disableBooting(){
		self::$pdo=self::$instance=self::$id=self::$table=self::$fields=self::$where=self::$whereColumn=self::$orWhere=self::$whereIn=self::$whereNotIn=self::$operators=self::$order=self::$limit=self::$groupBy=self::$joinSQL=self::$select=self::$addSelect=self::$withTrashed=self::$addTrashed=self::$className=self::$toSQL=self::$numberOfSubQueries=self::$currentSubQueryNumber=self::$currentField=self::$whereSubQuery=self::$subQuery=self::$havingNumber=self::$havingField=self::$havingOperator=self::$havingValue=NULL;
		self::$subQueries=[];
	}

	public static function get(){
		if(self::$currentSubQueryNumber==NULL){
			self::boot();
			$mainSQL=self::getSQL();
			if(self::$toSQL==TRUE){
				self::disableBooting();
				return $mainSQL;
			}
			$fields=self::getFields();
			$stmt=self::$pdo->prepare($mainSQL);
			self::bindValues($stmt,$fields);
			$stmt->execute();
			self::disableBooting();
			return  $stmt->fetchAll(PDO::FETCH_CLASS,get_called_class());
		}else{
			self::makeSubQuery(self::showCurrentSubQuery());
		}
	}

	private static function bindValues($stmt,$fields){
		if(is_array($fields)){
			foreach($fields as $key => $field){
				$stmt->bindValue($key+1,$field, self::getPDOBindDataType($field));
			}
		}
	}
	private static function getPDOBindDataType($field){
		$type=gettype($field);
		
		switch ($type) {
			case 'integer':
			return PDO::PARAM_INT;
			break;

			case 'boolean':
			return PDO::PARAM_BOOL;
			break;

			case 'NULL':
			return PDO::PARAM_NULL;

			case 'resource':
			return PDO::PARAM_LOB;
			
			default:
			return PDO::PARAM_STR;
			break;
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

			self::${$where}[self::$currentField]='('.$mainSQL.')';
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
		if(self::$toSQL==TRUE){
			self::disableBooting();
			return $mainSQL;
		}
		$fields=self::getFields();
		$stmt=self::$pdo->prepare($mainSQL);
		self::bindValues($stmt,$fields);
		$stmt->execute();
		self::disableBooting();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public static function toSQL(){
		self::boot();
		self::$toSQL=TRUE;
		return self::$instance;
	}

	public static function addSelect(array $fields){
		if(self::$currentSubQueryNumber==NULL){
			self::boot();
			self::$addSelect=TRUE;
		}else{
			self::makeSubQueryAddSelect(self::showCurrentSubQuery());
		}
		foreach($fields as $select => $query){
			self::select(['('.$query.') AS ' . $select]);
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
		$stmt=self::$pdo->prepare($sql);
		self::bindValues($stmt,$fields);
		$stmt->execute();
		
		$countSQL=$countData.
		$getWhere.
		$getWhereIn.
		$getWhereNotIn.
		$getOrWhere;

		$countStmt=self::$pdo->prepare($countSQL);
		$countStmt->execute( self::getFields() );

		$objectArray=$stmt->fetchAll(PDO::FETCH_CLASS,get_called_class());

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
		$result=$referField=='id' ? $class::find($this->{$field}) : $class::findBy($referField,$this->{$field});
		return $result!==FALSE ? $result : (new NullModel)->nullExecute();
	}

	public function refersMany($class,$field,$referField='id'){
		$class=$class::connect(connectPDO());
		return $class->where($class->getTable() . '.'.$field,$this->{$referField});
	}
}