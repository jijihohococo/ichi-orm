<?php

namespace JiJiHoHoCoCo\IchiORM\Database;

use Exception;
use JiJiHoHoCoCo\IchiORM\Observer\{ModelObserver, ObserverSubject};
use JiJiHoHoCoCo\IchiORM\QueryBuilder\QueryBuilder;

abstract class Model
{
    private static $limitOne = " LIMIT 1";

    private static $instance;
    private static $getID;
    private static $table;
    private static $fields;
    private static $where;
    private static $whereColumn;
    private static $orWhere;
    private static $whereIn;
    private static $whereNotIn;
    private static $operators;
    private static $order;
    private static $limit;
    private static $offset;
    private static $groupBy;
    private static $joinSQL;
    private static $select;
    private static $addSelect;
    private static $withTrashed;
    private static $addTrashed;
    private static $className;
    private static $toSQL;
    private static $numberOfSubQueries;
    private static $currentSubQueryNumber;
    private static $currentField;
    private static $whereSubQuery;
    private static $subQuery;
    private static $subQueries = [];
    private static $selectedFields = [];
    private static $havingNumber = null;
    private static $havingField;
    private static $havingOperator;
    private static $havingValue;
    private static $whereZero = ' WHERE 0 = 1 ';
    private static $andZero = ' AND 0 = 1 ';
    private static $groupByString = ' GROUP BY ';
    private static $selectQuery;
    protected static $observerSubject;
    private static $subQueryLimitNumber = 0;
    private static $useUnionQuery = [0 => true];
    private static $unionQuery = [0 => null];
    private static $unionNumber = 0;
    private static $currentUnionNumber = 0;
    private static $unableUnionQuery = [];
    private static $caller = [];
    private static $queryBuilder;

    private static function getQueryBuilder()
    {
        if (self::$queryBuilder == null) {
            self::$queryBuilder = new QueryBuilder();
            self::$queryBuilder->setCalledClass(get_called_class());
        }
        return clone self::$queryBuilder;
    }

    protected function connectDatabase()
    {
        return connectPDO();
    }

    protected function getTable()
    {
        return getTableName((string) get_called_class());
    }

    protected function getID()
    {
        return "id";
    }

    protected function autoIncrementId()
    {
        return true;
    }

    public static function withTrashed()
    {
        return self::getQueryBuilder()->withTrashed();
    }

    private static function getSelect()
    {
        $select = self::$select;
        if (self::$selectQuery !== null) {
            $i = 0;
            foreach (self::$selectQuery as $selectAs => $query) {
                $selectData = $query . ' AS ' . $selectAs;
                $select .= $i == 0 && $select == null ? $selectData : ',' . $selectData;
                $i++;
            }
        }
        return "SELECT " . $select . " FROM " . self::$table . self::getJoinSQL();
    }

    private static function makeDelete()
    {
        return "DELETE FROM " . self::$table . self::getJoinSQL();
    }

    private static function makeRestore()
    {
        return "UPDATE " . self::$table . " SET deleted_at=NULL" . self::getJoinSQL();
    }

    private static function checkInstance()
    {
        try {
            if (self::$className !== null && self::$className !== get_called_class()) {
                throw new Exception(showDuplicateModelMessage(get_called_class(), self::$className), 1);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    private static function checkBoot()
    {
        try {
            if (self::$instance !== null) {
                throw new Exception("CRUD functions and querying are different", 1);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    private static function boot()
    {
        $calledClass = get_called_class();

        if (self::$instance == null) {
            self::$where = null;
            self::$whereColumn = null;
            self::$orWhere = null;
            self::$whereIn = null;
            self::$whereNotIn = null;
            self::$operators = null;
            self::$order = null;
            self::$limit = null;
            self::$offset = null;
            self::$groupBy = null;
            self::$joinSQL = null;
            self::$instance = new $calledClass();
            self::$className = $calledClass;
            self::$getID = self::$instance->getID();
            self::$table = self::$instance->getTable();
            self::$select = self::$table . '.*';
            self::$addSelect = false;
            self::$withTrashed = false;

            self::$subQuery = null;
            self::$addTrashed = false;
        }
    }

    public static function groupBy(string $groupBy)
    {
        return self::getQueryBuilder()->groupBy($groupBy);
    }

    public static function having(string $field, string $operator, $value)
    {
        return self::getQueryBuilder()->having($field, $operator, $value);
    }

    private static function getGroupBy()
    {
        return self::$groupBy;
    }

    private static function getHaving()
    {
        $string = null;
        if (self::$havingNumber !== null) {
            foreach (range(0, self::$havingNumber - 1) as $key => $value) {
                $result = self::$havingField[$key] . ' ' . self::$havingOperator[$key] . ' ' . self::$havingValue[$key];
                $string .= $key == 0 ? ' HAVING ' . $result : ' AND ' . $result;
            }
        }
        return $string;
    }

    public static function bulkUpdate(array $attributes)
    {
        self::getQueryBuilder()->bulkUpdate($attributes);
    }

    public static function insert(array $attributes)
    {
        try {
            self::$caller = getCallerInfo();
            self::checkBoot();
            if (empty($attributes)) {
                throw new Exception("You need to put non-empty array data", 1);
            }
            self::boot();
            $instance = self::$instance;
            $arrayKeys = get_object_vars($instance);
            if (empty($arrayKeys)) {
                throw new Exception("You need to add column data", 1);
            }
            $getID = $instance->getID();
            if ($instance->autoIncrementId() == true) {
                unset($arrayKeys[$getID]);
            }
            unset($arrayKeys['deleted_at']);
            unset($arrayKeys['updated_at']);
            $insertedValues = '';
            $insertBindValues = [];
            $insertedFields = [];
            foreach ($attributes as $attribute) {
                if (!is_array($attribute)) {
                    throw new Exception("You need to add the array data", 1);
                }
                if (empty($attribute)) {
                    throw new Exception("You need to put non-empty array data", 1);
                }
                $insertedData = [];
                unset($attribute[$getID]);
                unset($attribute['deleted_at']);
                foreach ($arrayKeys as $key => $value) {
                    if (!isset($insertedFields[$key . ','])) {
                        $insertedFields[$key . ','] = null;
                    }
                    if (isset($attribute[$key])) {
                        $insertedData[$key] = $attribute[$key];
                    } elseif ($key == 'created_at' || $key == 'updated_at') {
                        $insertedData[$key] = isset($attribute[$key]) ? $attribute[$key] : now();
                    } else {
                        $insertedData[$key] = $value;
                    }
                }
                $insertedArrayValues = array_values($insertedData);
                $insertedValues .= "(" . addArray($insertedArrayValues) . "),";
                $insertBindValues = array_merge($insertBindValues, $insertedArrayValues);
            }
            $insertedValues = substr($insertedValues, 0, -1);
            $fields = '(' . substr(implode('', array_keys($insertedFields)), 0, -1) . ')';
            $stmt = $instance->connectDatabase()->prepare("INSERT INTO " . self::$table . " " . $fields . " VALUES " . $insertedValues);
            bindValues($stmt, $insertBindValues);
            $stmt->execute();
            self::disableBooting();
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function create(array $attribute)
    {
        try {
            self::$caller = getCallerInfo();
            self::checkBoot();
            if (empty($attribute)) {
                throw new Exception("You need to put non-empty array data", 1);
            }
            self::boot();
            $instance = self::$instance;
            $arrayKeys = get_object_vars($instance);
            if (empty($arrayKeys)) {
                throw new Exception("You need to add column data", 1);
            }
            $getID = $instance->getID();
            if ($instance->autoIncrementId() == true) {
                unset($arrayKeys[$getID]);
            }
            unset($arrayKeys['deleted_at']);
            $insertBindValues = [];
            $insertedFields = [];
            $insertedData = [];
            foreach ($arrayKeys as $key => $value) {
                if (!isset($insertedFields[$key . ','])) {
                    $insertedFields[$key . ','] = null;
                }
                if (isset($attribute[$key])) {
                    $insertedData[$key] = $attribute[$key];
                } elseif ($key == 'created_at' || $key == 'updated_at') {
                    $insertedData[$key] = isset($attribute[$key]) ? $attribute[$key] : now();
                } else {
                    $insertedData[$key] = $value;
                }
            }
            $insertedArrayValues = array_values($insertedData);
            $insertedValues = substr("(" . addArray($insertedArrayValues) . "),", 0, -1);
            $insertBindValues = array_merge($insertBindValues, $insertedArrayValues);
            $fields = '(' . substr(implode('', array_keys($insertedFields)), 0, -1) . ')';
            $pdo = $instance->connectDatabase();
            $stmt = $pdo->prepare("INSERT INTO " . self::$table . " " . $fields . " VALUES " . $insertedValues);
            bindValues($stmt, $insertBindValues);
            $stmt->execute();
            $object = mappingModelData([
                $getID => $pdo->lastInsertId()
            ], $insertedData, $instance);
            $className = self::$className;
            self::disableBooting();

            self::makeObserver($className, 'create', $object);

            return $object;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    private static function makeObserver(string $className, string $method, $parameters)
    {
        if (self::$observerSubject !== null && self::$observerSubject->check($className)) {
            self::$observerSubject->use($className, $method, $parameters);
        }
    }

    public function update(array $attribute)
    {
        return self::getQueryBuilder()->update($attribute);
    }

    public static function find($id)
    {
        try {
            self::$caller = getCallerInfo();
            self::checkBoot();
            self::boot();
            $pdo = self::$instance->connectDatabase();
            $getId = self::$instance->getID();
            $stmt = $pdo->prepare(self::getSelect() . " WHERE " . $getId . " = ? " . self::$limitOne);
            bindValues($stmt, [
                0 => $id
            ]);
            $stmt->execute();
            $instance = $stmt->fetchObject(self::$className);
            self::where($getId, $id);
            $object = self::getObject($instance);
            self::disableBooting();
            return $object;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    private static function getObject($instance)
    {
        return $instance == '' ? (new NullModel())->nullExecute() : $instance;
    }

    public static function findBy(string $field, $value)
    {
        try {
            self::$caller = getCallerInfo();
            self::checkBoot();
            self::boot();
            $pdo = self::$instance->connectDatabase();
            $stmt = $pdo->prepare(self::getSelect() . " WHERE " . $field . " = ? " . self::$limitOne);
            bindValues($stmt, [
                0 => $value
            ]);
            $stmt->execute();
            $instance = $stmt->fetchObject(self::$className);
            self::where($field, $value);
            $object = self::getObject($instance);
            self::disableBooting();
            return $object;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function delete()
    {
        try {
            self::$caller = getCallerInfo();
            self::checkInstance();
            if (self::$currentSubQueryNumber !== null) {
                throw new Exception("delete function can't be used in subquery");
            }
            if (self::$currentSubQueryNumber == null) {
                self::boot();
                $instance = self::$instance;
                $mainSQL = self::deleteQuery();
                $fields = self::getFields();
                $stmt = $instance->connectDatabase()->prepare($mainSQL);
                bindValues($stmt, $fields);
                $stmt->execute();
                self::disableBooting();
                self::makeObserver((string) get_class($instance), 'delete', $instance);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function forceDelete()
    {
        try {
            self::$caller = getCallerInfo();
            self::checkInstance();
            if (self::$currentSubQueryNumber !== null) {
                throw new Exception("force delete function can't be used in subquery");
            }
            if (self::$currentSubQueryNumber == null) {
                self::boot();
                $instance = self::$instance;
                $mainSQL = self::forceDeleteQuery();
                $fields = self::getFields();
                $stmt = $instance->connectDatabase()->prepare($mainSQL);
                bindValues($stmt, $fields);
                $stmt->execute();
                self::disableBooting();
                self::makeObserver((string) get_class($instance), 'delete', $instance);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public function restore()
    {
        self::getQueryBuilder()->restore();
    }


    public static function select(array $fields)
    {
        return self::getQueryBuilder()->select($fields);
    }

    public static function limit(int $limit)
    {
        return self::getQueryBuilder()->limit($limit);
    }

    public static function offset(int $offset)
    {
        return self::getQueryBuilder()->offset($offset);
    }

    public static function where()
    {
        return self::getQueryBuilder()->where(func_get_args());
    }

    public static function from(string $className)
    {
        return self::getQueryBuilder()->from($className);
    }

    public static function whereColumn()
    {
        return self::getQueryBuilder()->whereColumn(func_get_args());
    }

    public static function orWhere()
    {
        return self::getQueryBuilder()->orWhere(func_get_args());
    }

    public static function whereIn(string $field, $value)
    {
        return self::getQueryBuilder()->whereIn($field, $value);
    }

    public static function whereNotIn(string $field, $value)
    {
        return self::getQueryBuilder()->whereNotIn($field, $value);
    }

    private static function getLimit()
    {
        return self::$limit;
    }

    private static function getOffset()
    {
        return self::$offset;
    }

    private static function checkTrashed()
    {
        return property_exists(self::$instance, 'deleted_at') && self::$withTrashed == false;
    }

    private static function getWhere()
    {
        $string = null;
        $i = 0;
        if (self::$where !== null) {
            $string = ' WHERE ';

            foreach (self::$where as $key => $value) {
                if (isset(self::$whereSubQuery[$key . 'where'])) {
                    // WHERE SUBQUERY //

                    $string .= $i == 0 ? $key . self::$operators[$key . 'where'] . $value : ' AND ' . $key . self::$operators[$key . 'where'] . $value;
                } else {
                    // WHERE //
                    if ($value == null) {
                        $string .= $i == 0 ? $key . self::$operators[$key . 'where'] . 'NULL' : ' AND ' . $key . self::$operators[$key . 'where'] . 'NULL';
                    } else {
                        $string .= $i == 0 ?
                            $key . self::$operators[$key . 'where'] . '?' : ' AND ' . $key . self::$operators[$key . 'where'] . '?';
                    }
                }
                $i++;
            }
        }

        if (self::checkTrashed()) {
            $isNULL = self::$table . '.deleted_at IS NULL';
            $string .= self::$where == null ? ' WHERE ' . $isNULL : ' AND ' . $isNULL;
            self::$addTrashed = true;
        }
        return $string;
    }

    private static function getWhereColumn()
    {
        $string = null;
        $i = 0;
        if (self::$whereColumn !== null) {
            foreach (self::$whereColumn as $key => $value) {
                $result = $key . self::$operators[$key . 'whereColumn'] . $value;
                $string .= $i == 0 && self::$where == null && self::$addTrashed == false ? ' WHERE ' . $result : ' AND ' . $result;
            }
        }
        return $string;
    }

    private static function getOrWhere()
    {
        $string = null;
        if (self::$orWhere !== null) {
            foreach (self::$orWhere as $key => $value) {
                if (isset(self::$whereSubQuery[$key . 'orWhere'])) {
                    // OR WHERE SUBQUERY //
                    $string .= ' OR ' . $key . self::$operators[$key . 'orWhere'] . $value;
                } else {
                    // OR WHERE QUERY //

                    $string .= ' OR ' . $key . self::$operators[$key . 'orWhere'] . '?';
                }
            }
        }
        return $string;
    }

    private static function getWhereIn()
    {
        $string = null;
        $i = 0;
        if (self::$whereIn !== null) {
            foreach (self::$whereIn as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $in = addArray($value);
                    $string .= $i == 0 && self::$where == null && self::$whereColumn == null && self::$addTrashed == false ? ' WHERE ' . $key . ' IN (' . $in . ') ' : ' AND ' . $key . ' IN (' . $in . ') ';
                } elseif ($value !== null && !is_array($value)) {
                    $string .= $i == 0 && self::$where == null && self::$whereColumn == null && self::$addTrashed == false ? ' WHERE ' . $key . ' IN ' . $value : ' AND ' . $key . ' IN ' . $value;
                } else {
                    $string .= $i == 0 && self::$where == null && self::$whereColumn == null && self::$addTrashed == false ? self::$whereZero : self::$andZero;
                }
                $i++;
            }
        }
        return $string;
    }

    private static function getWhereNotIn()
    {
        $string = null;
        $i = 0;
        if (self::$whereNotIn !== null) {
            foreach (self::$whereNotIn as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $in = addArray($value);
                    $string .= $i == 0 && self::$where == null && self::$whereColumn == null && self::$whereIn == null && self::$addTrashed == false ?
                        ' WHERE ' . $key . ' NOT IN (' . $in . ') ' : ' AND ' . $key . ' NOT IN (' . $in . ') ';
                } elseif ($value !== null && !is_array($value)) {
                    $string .= $i == 0 && self::$where == null && self::$whereColumn == null && self::$whereIn == null && self::$addTrashed == false ? ' WHERE ' . $key . ' NOT IN ' . $value : ' AND ' . $key . ' NOT IN ' . $value;
                } else {
                    $string .= $i == 0 && self::$where == null && self::$whereColumn == null && self::$whereIn == null && self::$addTrashed == false ? self::$whereZero : self::$andZero;
                }
                $i++;
            }
        }
        return $string;
    }

    private static function getFields()
    {
        return self::$fields;
    }

    public static function orderBy(string $field, string $sort = "ASC")
    {
        return self::getQueryBuilder()->orderBy($field, $sort);
    }

    public static function latest(string $field = null)
    {
        return self::getQueryBuilder()->latest($field);
    }

    private static function getOrder()
    {
        return self::$order;
    }

    private static function disableBooting()
    {
        self::$instance =
            self::$getID =
            self::$fields =
            self::$where =
            self::$whereColumn =
            self::$orWhere =
            self::$whereIn =
            self::$whereNotIn =
            self::$operators =
            self::$order =
            self::$limit =
            self::$offset =
            self::$groupBy =
            self::$joinSQL =
            self::$addSelect =
            self::$withTrashed =
            self::$addTrashed =
            self::$className =
            self::$toSQL =
            self::$numberOfSubQueries =
            self::$currentSubQueryNumber =
            self::$currentField =
            self::$whereSubQuery =
            self::$subQuery =
            self::$havingNumber =
            self::$havingField =
            self::$havingOperator =
            self::$havingValue =
            self::$selectQuery = null;
        self::$subQueries = [];
        self::$subQueryLimitNumber = 0;

        self::$useUnionQuery = [0 => true];
        self::$unionQuery = [0 => null];
        self::$unionNumber = self::$currentUnionNumber = 0;
        self::$unableUnionQuery = [];
    }

    public static function union(callable $value)
    {
        return self::getQueryBuilder()->union($value);
    }

    public static function unionAll(callable $value)
    {
        return self::getQueryBuilder()->unionAll($value);
    }

    private static function checkUnion()
    {
        return isset(self::$unionQuery[self::$currentUnionNumber]) && self::$unionQuery[self::$currentUnionNumber] !== null && self::$useUnionQuery[self::$currentUnionNumber] == true;
    }

    private static function deleteQuery()
    {
        if (self::checkUnion()) {
            throw new Exception("delete function can't be used in union");
        }
        return self::deleteSQL();
    }

    private static function forceDeleteQuery()
    {
        if (self::checkUnion()) {
            throw new Exception("delete function can't be used in union");
        }
        return self::forceDeleteSQL();
    }

    private static function restoreQuery()
    {
        if (self::checkUnion()) {
            throw new Exception("restore function can't be used in union");
        }
        return self::restoreSQL();
    }

    public static function get()
    {
        return self::getQueryBuilder()->get();
    }

    public function __construct()
    {
        $class = get_called_class();
        if (!empty(self::$selectedFields) && isset(self::$selectedFields[$class]) && self::$select !== self::$table . '.*' && self::$select !== null) {
            // FOR ADD SELECT WITH OR WITHOUT SELECT
            foreach (get_object_vars($this) as $key => $value) {
                if (!isset(self::$selectedFields[$class][$key])) {
                    unset($this->{$key});
                }
            }
        }
        if (self::$select == null && !empty(self::$selectedFields) && isset(self::$selectedFields[$class])) {
            // FOR ADD ONLY SELECT
            foreach (get_object_vars($this) as $key => $value) {
                if (isset(self::$selectedFields[$class][$key])) {
                    $this->{$key} = $value;
                } else {
                    unset($this->{$key});
                }
            }
        }
    }

    private static function deleteSQL()
    {
        $checkTrash = property_exists(static::class, 'deleted_at');
        $updateSQL = "UPDATE " . self::$table . " SET deleted_at='" . now() . "'";
        $deleteSQL = $checkTrash ? $updateSQL : self::makeDelete();
        return $deleteSQL .
            self::getWhere() .
            self::getWhereColumn() .
            self::getWhereIn() .
            self::getWhereNotIn() .
            self::getOrWhere() .
            self::getOrder() .
            self::getGroupBy() .
            self::getHaving() .
            self::getLimit() .
            self::getOffset();
    }

    private static function forceDeleteSQL()
    {
        return self::makeDelete() .
            self::getWhere() .
            self::getWhereColumn() .
            self::getWhereIn() .
            self::getWhereNotIn() .
            self::getOrWhere() .
            self::getOrder() .
            self::getGroupBy() .
            self::getHaving() .
            self::getLimit() .
            self::getOffset();
    }

    private static function restoreSQL()
    {
        return self::makeRestore() .
            self::getWhere() .
            self::getWhereColumn() .
            self::getWhereIn() .
            self::getWhereNotIn() .
            self::getOrWhere() .
            self::getOrder() .
            self::getGroupBy() .
            self::getHaving() .
            self::getLimit() .
            self::getOffset();
    }

    public static function toArray()
    {
        return self::getQueryBuilder()->toArray();
    }

    public static function toSQL()
    {
        return self::getQueryBuilder()->toSQL();
    }

    public static function addSelect(array $fields)
    {
        return self::getQueryBuilder()->addSelect($fields);
    }

    public static function addOnlySelect(array $fields)
    {
        return self::getQueryBuilder()->addOnlySelect($fields);
    }

    public static function paginate(int $per_page = 10)
    {
        return self::getQueryBuilder()->paginate($per_page);
    }

    private static function getJoinSQL()
    {
        return self::$joinSQL;
    }

    public static function innerJoin()
    {
        return self::getQueryBuilder()->innerJoin(func_get_args());
    }

    public static function leftJoin()
    {
        return self::getQueryBuilder()->leftJoin(func_get_args());
    }

    public static function rightJoin()
    {
        return self::getQueryBuilder()->rightJoin(func_get_args());
    }

    protected function refersTo(string $class, string $field, string $referField = 'id')
    {
        try {
            checkClass($class);
            self::checkBoot();
            if (isset($this->{$field})) {
                return $class::findBy($referField, $this->{$field});
            }
            throw new Exception($field . ' is not available', 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    protected function refersMany(string $class, string $field, string $referField = 'id')
    {
        try {
            checkClass($class);
            self::checkBoot();
            if (isset($this->{$referField})) {
                $classObject = new $class();
                return $class::where($classObject->getTable() . '.' . $field, $this->{$referField});
            }
            throw new Exception($referField . ' is not available', 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function observe(ModelObserver $modelObserver)
    {
        try {
            self::$caller = getCallerInfo();
            self::checkBoot();
            checkObserverFunctions($modelObserver);
            if (self::$observerSubject == null) {
                self::$observerSubject = new ObserverSubject();
            }
            $className = (string) get_called_class();
            self::$observerSubject->attach($className, $modelObserver);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }
}
