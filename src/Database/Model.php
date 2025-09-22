<?php

namespace JiJiHoHoCoCo\IchiORM\Database;

use PDO;
use Exception;
use JiJiHoHoCoCo\IchiORM\Observer\{ModelObserver, ObserverSubject};
use JiJiHoHoCoCo\IchiORM\Pagination\Paginate;

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
        self::$caller = getCallerInfo();
        self::checkInstance();
        if (self::$currentSubQueryNumber == null) {
            self::checkUnionQuery();
            self::boot();
            self::$withTrashed = true;
        } else {
            $currentQuery = self::showCurrentSubQuery();
            self::checkSubQueryUnionQuery($currentQuery);
            self::makeSubQueryTrashTrue($currentQuery);
        }
        return self::$instance;
    }

    private static function makeSubQueryTrashTrue($where)
    {
        self::${$where}[self::$currentField . self::$currentSubQueryNumber]['withTrashed'] = true;
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

    private static function getSubQuerySelect($where)
    {
        if (isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber])) {
            $current = self::${$where}[self::$currentField . self::$currentSubQueryNumber];
            $select = $current['select'];
            if ($current['selectQuery'] !== null) {
                $i = 0;
                foreach ($current['selectQuery'] as $selectAs => $query) {
                    $selectData = '(' . $query . ') AS ' . $selectAs;
                    $select .= $i == 0 && $select == null ? $selectData : ',' . $selectData;
                    $i++;
                }
            }
            return "SELECT " . $select . " FROM " . $current['table'] . self::getSubQueryJoinSQL($where);
        }
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

    private static function checkUnionQuery()
    {
        try {
            if (isset(self::$unableUnionQuery[self::$currentUnionNumber]) && self::$unableUnionQuery[self::$currentUnionNumber] !== null) {
                throw new Exception("You are not allowed to use", 1);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    private static function checkSubQueryUnionQuery($where)
    {
        try {
            if (
                isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber . 'unableUnionQuery']) &&
                self::${$where}[self::$currentField . self::$currentSubQueryNumber . 'unableUnionQuery'] == false
            ) {
                throw new Exception("You are not allowed to use", 1);
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
        self::$caller = getCallerInfo();
        self::checkInstance();
        if (self::$currentSubQueryNumber == null) {
            self::checkUnionQuery();
            self::boot();
            self::$groupBy = self::$groupByString . $groupBy;
        }
        if (self::$currentSubQueryNumber !== null) {
            $currentQuery = self::showCurrentSubQuery();
            self::checkSubQueryUnionQuery($currentQuery);
            self::makeSubQueryGroupBy($currentQuery, $groupBy);
        }
        return self::$instance;
    }

    public static function having(string $field, string $operator, $value)
    {
        self::$caller = getCallerInfo();
        self::checkInstance();
        if (self::$currentSubQueryNumber == null) {
            self::checkUnionQuery();
            self::boot();
            if (self::$havingNumber == null) {
                self::$havingNumber = 0;
            }
            self::$havingField[self::$havingNumber] = $field;
            self::$havingOperator[self::$havingNumber] = $operator;
            self::$havingValue[self::$havingNumber] = $value;
            self::$havingNumber++;
        }
        if (self::$currentSubQueryNumber !== null) {
            $currentQuery = self::showCurrentSubQuery();
            self::checkSubQueryUnionQuery($currentQuery);
            self::makeSubQueryHaving($currentQuery, $field, $operator, $value);
        }
        return self::$instance;
    }

    private static function makeSubQueryHaving($where, $field, $operator, $value)
    {
        $current = self::${$where}[self::$currentField . self::$currentSubQueryNumber];
        if ($current['havingNumber'] == null) {
            $current['havingNumber'] = 0;
        }
        $current['havingField'][$current['havingNumber']] = $field;
        $current['havingOperator'][$current['havingNumber']] = $operator;
        $current['havingValue'][$current['havingNumber']] = $value;
        $current['havingNumber']++;
    }

    private static function makeSubQueryGroupBy($where, $groupBy)
    {
        self::${$where}[self::$currentField . self::$currentSubQueryNumber]['groupBy'] = self::$groupByString . $groupBy;
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

    private static function getSubQueryHaving($where)
    {
        $string = null;
        if (isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber])) {
            $current = self::${$where}[self::$currentField . self::$currentSubQueryNumber];
            if ($current['havingNumber'] !== null) {
                foreach (range(0, $current['havingNumber'] - 1) as $key => $value) {
                    $result = $current['havingField'][$key] . ' ' . $current['havingOperator'][$key] . ' ' . $current['havingValue'][$key];
                    $string .= $key == 0 ? ' HAVING ' . $result : ' AND ' . $result;
                }
            }
        }
        return $string;
    }

    private static function getSubQueryGroupBy($where)
    {
        if (isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber]['groupBy'])) {
            return self::${$where}[self::$currentField . self::$currentSubQueryNumber]['groupBy'];
        }
    }

    public static function bulkUpdate(array $attributes)
    {
        try {
            self::$caller = getCallerInfo();
            self::checkInstance();
            if (empty($attributes)) {
                throw new Exception("You need to put non-empty array data", 1);
            }
            static::boot();
            $instance = self::$instance;
            $arrayKeys = get_object_vars($instance);
            if (empty($arrayKeys)) {
                throw new Exception("You need to add column data", 1);
            }
            $getID = $instance->getID();
            $updatedFields = [];
            $updatedBindValues = [];
            $i = 0;
            foreach ($attributes as $key => $attribute) {
                if (!is_array($attribute)) {
                    throw new Exception("You need to add the array data", 1);
                }
                if (empty($attribute)) {
                    throw new Exception("You need to put non-empty array data", 1);
                }
                if (!isset($attribute[$getID])) {
                    throw new Exception("You don't have the primary id data to update", 1);
                }
                $i++;
                $j = 0;
                if (property_exists($instance, 'updated_at')) {
                    $attribute['updated_at'] = isset($attribute['updated_at']) ? $attribute['updated_at'] : now();
                }
                foreach ($attribute as $field => $value) {
                    $j++;
                    if (array_key_exists($field, $arrayKeys) && $field !== $getID) {
                        $updatedBindValues[$field][$i . '0'] = $attribute[$getID];
                        $updatedBindValues[$field][$i . $j] = $value;
                        if (!isset($updatedFields[$field])) {
                            $updatedFields[$field] = $field . ' = CASE ';
                        }
                        $updatedFields[$field] .= ' WHEN ' . $getID . ' = ? THEN ?';
                        if ($key + 1 == count($attributes)) {
                            $updatedFields[$field] .= ' ELSE ' . $field . ' END, ';
                        }
                    } elseif (!array_key_exists($field, $arrayKeys) && $field !== $getID) {
                        throw new Exception("You need to put the available column data to update", 1);
                    }
                }
            }
            $updateString = 'UPDATE ' . self::$table . ' SET ' . substr(implode('', $updatedFields), 0, -2);
            $stmt = $instance->connectDatabase()->prepare($updateString);
            $i = 0;
            foreach ($updatedBindValues as $fieldNumber => $fields) {
                foreach ($fields as $key => $value) {
                    $i++;
                    $stmt->bindValue($i, $value, getPDOBindDataType($value));
                }
            }
            $stmt->execute();
            self::disableBooting();
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
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
        try {
            self::$caller = getCallerInfo();
            self::checkBoot();
            if (empty($attribute)) {
                throw new Exception("You need to put non-empty array data", 1);
            }
            $getID = $this->getID();
            $arrayKeys = get_object_vars($this);
            if (empty($arrayKeys)) {
                throw new Exception("You need to add column data", 1);
            }
            unset($arrayKeys[$getID]);
            $updatedBindValues = [];
            $updatedFields = null;
            $insertedData = [];
            foreach ($arrayKeys as $key => $value) {
                $updatedFields .= $key . '=?,';
                if (isset($attribute[$key])) {
                    $insertedData[$key] = $attribute[$key];
                } elseif ($key == 'updated_at') {
                    $insertedData[$key] = isset($attribute[$key]) ? $attribute[$key] : now();
                } else {
                    $insertedData[$key] = $value;
                }
            }
            $insertedArrayValues = array_values($insertedData);
            $updatedBindValues = array_merge($updatedBindValues, $insertedArrayValues);
            $updatedFields = substr($updatedFields, 0, -1);
            $stmt = $this->connectDatabase()->prepare("UPDATE " . $this->getTable() . " SET " . $updatedFields . " WHERE " . $getID . "=" . $this->{$getID});
            bindValues($stmt, $updatedBindValues);
            $stmt->execute();
            $object = mappingModelData([
                $getID => $this->{$getID}
            ], $insertedData, $this);
            self::makeObserver((string) get_class($this), 'update', $object);
            return $object;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
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
        try {
            self::$caller = getCallerInfo();
            self::checkInstance();
            if (self::$currentSubQueryNumber !== null) {
                throw new Exception("restore function can't be used in subquery");
            }
            if (self::$currentSubQueryNumber == null) {
                self::boot();
                self::$withTrashed = true;
                $instance = self::$instance;
                $mainSQL = self::restoreQuery();
                $fields = self::getFields();
                $stmt = $instance->connectDatabase()->prepare($mainSQL);
                bindValues($stmt, $fields);
                $stmt->execute();
                self::disableBooting();
                self::makeObserver((string) get_class($instance), 'restore', $instance);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }


    public static function select(array $fields)
    {
        try {
            self::$caller = getCallerInfo();
            self::checkInstance();
            if (self::$currentSubQueryNumber == null) {
                self::checkUnionQuery();
                // If addSelect was used after using addOnlySelect function
                if (self::$instance !== null && self::$select == null && self::$addSelect == true) {
                    throw new Exception("You must not use addOnlySelect function before", 1);
                }

                self::boot();
                if (self::$addSelect == false) {
                    self::$select = null;
                } else {
                    self::$select .= ',';
                }

                foreach ($fields as $key => $field) {
                    if (strpos($field, '(') == false && strpos($field, ')') == false && !isset(self::$selectedFields[self::$className][$field])) {
                        $selectedField = function () use ($field) {
                            if (strpos($field, '.') !== false) {
                                $getField = explode('.', $field);
                                return $getField[1];
                            } else {
                                return $field;
                            }
                        };
                        $newSelectedField = $selectedField();
                        self::$selectedFields[self::$className][$newSelectedField] = $newSelectedField;
                    }
                    self::$select .= $key + 1 == count($fields) ? $field : $field . ',';
                }
            } else {
                $check = self::showCurrentSubQuery();
                self::checkSubQueryUnionQuery($check);
                $addSelectCheck = self::checkSubQueryAddSelect($check);
                if (
                    self::${$check}[self::$currentField . self::$currentSubQueryNumber]['select'] == null &&
                    $addSelectCheck == true
                ) {
                    throw new Exception("You must not use addOnlySelect function before", 1);
                }

                if ($addSelectCheck == true) {
                    self::addCommaToSubQuerySelect($check);
                }
                if ($addSelectCheck == false) {
                    self::makeNullToSubQuerySelect($check);
                }

                foreach ($fields as $key => $field) {
                    self::${$check}[self::$currentField . self::$currentSubQueryNumber]['select'] .= $key + 1 == count($fields) ? $field : $field . ',';
                }
            }
            return self::$instance;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    private static function makeNullToSubQuerySelect($where)
    {
        self::${$where}[self::$currentField . self::$currentSubQueryNumber]['select'] = null;
    }

    private static function addCommaToSubQuerySelect($where)
    {
        self::${$where}[self::$currentField . self::$currentSubQueryNumber]['select'] .= ',';
    }

    private static function checkSubQueryAddSelect($where)
    {
        return self::${$where}[self::$currentField . self::$currentSubQueryNumber]['addSelect'];
    }

    public static function limit(int $limit)
    {
        self::$caller = getCallerInfo();
        self::checkInstance();
        if (self::$currentSubQueryNumber == null) {
            self::checkUnionQuery();
            self::boot();
            self::$limit = ' LIMIT ' . $limit;
        }
        if (self::$currentSubQueryNumber !== null) {
            $check = self::showCurrentSubQuery();
            self::checkSubQueryUnionQuery($check);
            self::${$check}[self::$currentField . self::$currentSubQueryNumber]['limit'] = $limit;
            self::$subQueryLimitNumber++;
        }
        return self::$instance;
    }

    public static function offset(int $offset)
    {
        self::$caller = getCallerInfo();
        self::checkInstance();
        if (self::$currentSubQueryNumber == null) {
            self::checkUnionQuery();
            self::boot();
            self::$offset = ' OFFSET ' . $offset;
        }
        if (self::$currentSubQueryNumber !== null) {
            $check = self::showCurrentSubQuery();
            self::checkSubQueryUnionQuery($check);
            self::${$check}[self::$currentField . self::$currentSubQueryNumber]['offset'] = $offset;
        }
        return self::$instance;
    }

    private static function makeSubQueryAttributes($previousField = null)
    {
        return [
            'where' => null,
            'whereColumn' => null,
            'orWhere' => null,
            'whereIn' => null,
            'whereNotIn' => null,
            'operators' => null,
            'order' => null,
            'limit' => null,
            'offset' => null,
            'groupBy' => null,
            'joinSQL' => null,
            'addSelect' => false,
            'withTrashed' => false,
            'addTrashed' => false,
            'table' => $previousField !== null && isset($previousField['table']) ? $previousField['table'] : self::$table,
            'select' => $previousField !== null && isset($previousField['table']) ? $previousField['table'] . '.*' : self::$table . '.*',
            'className' => null,
            'object' => null,
            'havingNumber' => null,
            'havingField' => null,
            'havingOperator' => null,
            'havingValue' => null,
            'selectQuery' => null
        ];
    }

    private static function setSubQuery($field, $where, bool $increase = true)
    {
        if ($increase == true) {
            self::$numberOfSubQueries++;
        }
        $previousCheck = self::$currentSubQueryNumber !== null ? self::showCurrentSubQuery() : null;
        $previousField = $previousCheck !== null ? self::${$previousCheck}[self::$currentField . self::$currentSubQueryNumber] : null;
        self::$currentSubQueryNumber = self::$numberOfSubQueries;
        self::$currentField = $field;
        self::${$where}[self::$currentField . self::$currentSubQueryNumber] = self::makeSubQueryAttributes($previousField);
    }

    private static function setSubWhere($where, $value, $field, $operator, $whereSelect)
    {
        self::${$where}[self::$currentField . self::$currentSubQueryNumber][$whereSelect][$field] = $value;
        self::${$where}[self::$currentField . self::$currentSubQueryNumber]['operators'][$field . $whereSelect] = makeOperator($operator);
    }

    private static function setSubWhereIn($where, $value, $field, $whereInSelect)
    {
        self::${$where}[self::$currentField . self::$currentSubQueryNumber][$whereInSelect][$field] = $value;
    }

    private static function makeDefaultSubQueryData()
    {
        self::$currentSubQueryNumber = null;
        self::$currentField = null;
    }

    private static function showCurrentSubQuery()
    {
        foreach (getSubQueryTypes() as $subQuery) {
            if (self::checkSubQuery($subQuery)) {
                return $subQuery;
            }
        }
    }


    public static function where()
    {
        self::$caller = getCallerInfo();
        self::makeWhereQuery(func_get_args(), 'where');
        return self::$instance;
    }

    private static function makeSubQueryInSubQuery($whereSelect, $value, $field, $check)
    {
        // if there is sub query function in sub query //
        $previousField = self::$currentField;
        $previousSubQueryNumber = self::$currentSubQueryNumber;
        $query = self::$instance;
        $query->setSubQuery($field, $check);
        self::$subQueries[$field . self::$currentSubQueryNumber] = self::$currentSubQueryNumber;
        $value($query);

        if ($whereSelect !== 'selectQuery') {
            // put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//
            self::${$check}[$previousField . $previousSubQueryNumber][$whereSelect] = self::$subQuery;
            // put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//
        }

        if ($whereSelect == 'selectQuery') {
            // put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//
            self::${$check}[$previousField . $previousSubQueryNumber][$whereSelect][$field] = self::$subQuery;
            // put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//
        }


        self::$currentField = $previousField;
        self::$currentSubQueryNumber = $previousSubQueryNumber;
    }

    public static function from(string $className)
    {

        try {
            self::$caller = getCallerInfo();

            checkClass($className);

            self::checkInstance();
            if (self::$currentSubQueryNumber !== null) {
                $currentQuery = self::showCurrentSubQuery();
                self::checkSubQueryUnionQuery($currentQuery);
                self::addTableToSubQuery($currentQuery, $className);
                return self::$instance;
            }
            throw new Exception("You can use 'from' function in only sub queries", 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    private static function getSubQueryClassObject($where, $className)
    {
        if (self::${$where}[self::$currentField . self::$currentSubQueryNumber]['object'] == null) {
            self::${$where}[self::$currentField . self::$currentSubQueryNumber]['object'] = new $className();
        }
        return self::${$where}[self::$currentField . self::$currentSubQueryNumber]['object'];
    }

    private static function addTableToSubQuery($where, $className)
    {
        try {
            $obj = self::getSubQueryClassObject($where, $className);
            $table = $obj->getTable();
            if (self::${$where}[self::$currentField . self::$currentSubQueryNumber]['select'] !== self::${$where}[self::$currentField . self::$currentSubQueryNumber]['table'] . '.*') {
                throw new Exception("You must use from function before selecting the data", 1);
            }
            self::${$where}[self::$currentField . self::$currentSubQueryNumber]['table'] = $table;
            self::${$where}[self::$currentField . self::$currentSubQueryNumber]['select'] = $table . '.*';
            self::${$where}[self::$currentField . self::$currentSubQueryNumber]['className'] = $className;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function whereColumn()
    {
        self::$caller = getCallerInfo();
        self::makeWhereQuery(func_get_args(), 'whereColumn');
        return self::$instance;
    }

    public static function orWhere()
    {
        self::$caller = getCallerInfo();
        self::makeWhereQuery(func_get_args(), 'orWhere');
        return self::$instance;
    }
    private static function makeWhereQuery(array $parameters, $where)
    {
        try {
            self::checkInstance();
            $countParameters = count($parameters);
            $value = $operator = $field = null;
            if ($countParameters == 2 || $countParameters == 3) {
                $field = $parameters[0];

                if (!is_string($parameters[0])) {
                    throw new Exception("You must add field name in string", 1);
                }

                if ($countParameters == 3 && !in_array($parameters[1], databaseOperators())) {
                    throw new Exception("You can add only database operators in {$where} function", 1);
                }

                if (isset($parameters[1]) && in_array($parameters[1], databaseOperators()) && (isset($parameters[2]) || $parameters[2] == null)) {
                    $operator = $parameters[1];
                    $value = $parameters[2];
                } elseif ((isset($parameters[1]) && !in_array($parameters[1], databaseOperators()) || !isset($parameters[1])) && !isset($parameters[2])) {
                    $value = $parameters[1];
                    $operator = '=';
                }

                if (is_array($value)) {
                    throw new Exception("You can add single value or sub query function in {$where} function", 1);
                }
                if ($value == null && $operator == '=') {
                    $operator = ' IS ';
                }
                if ($value == null && ($operator == '!=' || $operator == '<>')) {
                    $operator = ' IS NOT ';
                }


                if (!is_callable($value) && self::$currentSubQueryNumber == null) {
                    self::checkUnionQuery();
                    self::boot();
                    self::${$where}[$field] = $value;
                    self::$operators[$field . $where] = makeOperator($operator);
                    if ($value !== null && $where !== 'whereColumn') {
                        self::$fields[] = $value;
                    }
                }
                if (is_callable($value) && self::$currentSubQueryNumber == null) {
                    self::checkUnionQuery();
                    self::boot();
                    self::$operators[$field . $where] = makeOperator($operator);
                    $query = self::$instance;
                    $query->setSubQuery($field, $where);
                    self::$subQueries[$field . self::$currentSubQueryNumber] = self::$currentSubQueryNumber;
                    $value($query);
                    self::makeDefaultSubQueryData();
                }
                if (!is_callable($value) && self::$currentSubQueryNumber !== null) {
                    $currentQuery = self::showCurrentSubQuery();
                    self::checkSubQueryUnionQuery($currentQuery);
                    self::setSubWhere($currentQuery, $value, $field, $operator, $where);
                    if ($value !== null && $where !== 'whereColumn') {
                        self::$fields[] = $value;
                    }
                }
                if (is_callable($value) && self::$currentSubQueryNumber !== null) {
                    $check = self::showCurrentSubQuery();
                    self::checkSubQueryUnionQuery($check);
                    self::${$check}[self::$currentField . self::$currentSubQueryNumber]['operators'][$field . $where] = makeOperator($operator);
                    self::makeSubQueryInSubQuery($where, $value, $field, $check);
                }
            } else {
                throw new Exception("Invalid Argument Parameter", 1);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    private static function makeInQuery($whereIn, $field, $value)
    {

        try {
            self::checkInstance();

            if (!is_array($value) && !is_callable($value) && $value !== null) {
                throw new Exception("You can add only array values or sub query in {$whereIn} function", 1);
            }

            if ((is_array($value) || $value == null) && self::$currentSubQueryNumber == null) {
                self::checkUnionQuery();
                self::boot();
                self::${$whereIn}[$field] = $value;
                if ($value !== null) {
                    self::$fields[] = $value;
                }
            }
            if (is_callable($value) && self::$currentSubQueryNumber == null) {
                self::checkUnionQuery();
                self::boot();
                $query = self::$instance;
                $query->setSubQuery($field, $whereIn, $field);
                self::$subQueries[$field . self::$currentSubQueryNumber] = self::$currentSubQueryNumber;
                $value($query);
                self::makeDefaultSubQueryData();
            }
            if ((is_array($value) || $value == null) && self::$currentSubQueryNumber !== null) {
                $currentQuery = self::showCurrentSubQuery();
                self::checkSubQueryUnionQuery($currentQuery);
                self::setSubWhereIn($currentQuery, $value, $field, $whereIn);
                if ($value !== null) {
                    self::$fields[] = $value;
                }
            }
            if (is_callable($value) && self::$currentSubQueryNumber !== null) {
                $currentQuery = self::showCurrentSubQuery();
                self::checkSubQueryUnionQuery($currentQuery);
                self::makeSubQueryInSubQuery($whereIn, $value, $field, $currentQuery);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function whereIn(string $field, $value)
    {
        self::$caller = getCallerInfo();
        self::makeInQuery('whereIn', $field, $value);
        return self::$instance;
    }

    public static function whereNotIn(string $field, $value)
    {
        self::$caller = getCallerInfo();
        self::makeInQuery('whereNotIn', $field, $value);
        return self::$instance;
    }

    private static function getLimit()
    {
        return self::$limit;
    }

    private static function getOffset()
    {
        return self::$offset;
    }
    private static function getSubQueryLimit($where)
    {
        if (isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber])) {
            $limit = self::${$where}[self::$currentField . self::$currentSubQueryNumber]['limit'];
            return $limit == null ? $limit : ' LIMIT ' . $limit;
        }
    }

    private static function getSubQueryOffset($where)
    {
        if (isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber])) {
            $offset = self::${$where}[self::$currentField . self::$currentSubQueryNumber]['offset'];
            return $offset == null ? $offset : ' OFFSET ' . $offset;
        }
    }

    private static function getSubQueryLimitNumber()
    {
        return self::$subQueryLimitNumber;
    }

    private static function checkTrashed()
    {
        return property_exists(self::$instance, 'deleted_at') && self::$withTrashed == false;
    }

    private static function checkSubQueryTrashed($where)
    {

        $subClassName = self::${$where}[self::$currentField . self::$currentSubQueryNumber]['className'];

        $className = $subClassName == null ? self::$instance : $subClassName;

        return property_exists($className, 'deleted_at') && self::${$where}[self::$currentField . self::$currentSubQueryNumber]['withTrashed'] == false;
    }

    private static function getSubQueryWhere($where)
    {
        $string = null;
        $i = 0;
        if (isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber])) {
            $current = self::${$where}[self::$currentField . self::$currentSubQueryNumber];
            if ($current['where'] !== null && is_array($current['where'])) {
                $string = ' WHERE ';
                foreach ($current['where'] as $key => $value) {
                    $operator = $current['operators'][$key . 'where'];
                    if ($value == null) {
                        $string .= $i == 0 ? $key . $operator . 'NULL' : ' AND ' . $key . $operator . 'NULL';
                    } else {
                        $string .= $i == 0 ? $key . $operator . '?' : ' AND ' . $key . $operator . '?';
                    }
                    $i++;
                }
            } elseif ($current['where'] !== null && !is_array($current['where'])) {
                $currentField = getCurrentField(self::$subQueries, self::$currentField, self::$currentSubQueryNumber);
                $string = ' WHERE ' . $currentField . $current['operators'][$currentField . 'where'] . ' (' . $current['where'] . ') ';
            }
            if (self::checkSubQueryTrashed($where)) {
                $isNULL = $current['table'] . '.deleted_at IS NULL';
                $string .= $current['where'] == null ? ' WHERE ' . $isNULL : ' AND ' . $isNULL;
                self::${$where}[self::$currentField . self::$currentSubQueryNumber]['addTrashed'] = true;
            }
        }
        return $string;
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

    private static function getSubQueryWhereColumn($where)
    {
        $string = null;
        $i = 0;
        if (isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber])) {
            $current = self::${$where}[self::$currentField . self::$currentSubQueryNumber];
            if ($current['whereColumn'] !== null && is_array($current['whereColumn'])) {
                foreach ($current['whereColumn'] as $key => $value) {
                    $result = $key . $current['operators'][$key . 'whereColumn'] . $value;
                    $string .= $i == 0 && $current['where'] == null && $current['addTrashed'] == false ? ' WHERE ' . $result : ' AND ' . $result;
                    $i++;
                }
            }
            if ($current['whereColumn'] !== null && !is_array($current['whereColumn'])) {
                $currentField = getCurrentField(self::$subQueries, self::$currentField, self::$currentSubQueryNumber);
                $result = $currentField . $current['operators'][$currentField . 'whereColumn'] . ' (' . $current['whereColumn'] . ') ';
                $string .= $current['where'] == null && $current['addTrashed'] == false ? ' WHERE ' . $result : ' AND ' . $result;
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

    private static function getSubQueryOrWhere($where)
    {
        $string = null;
        if (isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber])) {
            $current = self::${$where}[self::$currentField . self::$currentSubQueryNumber];
            if ($current['orWhere'] !== null && is_array($current['orWhere'])) {
                foreach ($current['orWhere'] as $key => $value) {
                    $string .= ' OR ' . $key . $current['operators'][$key . 'orWhere'] . '?';
                }
            }
            if ($current['orWhere'] !== null && !is_array($current['orWhere'])) {
                $currentField = getCurrentField(self::$subQueries, self::$currentField, self::$currentSubQueryNumber);
                $string .= ' OR ' . $currentField . $current['operators'][$currentField . 'orWhere'] . ' (' . $current['orWhere'] . ') ';
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

    private static function getSubQueryWhereIn($where)
    {
        $string = null;
        $i = 0;
        if (isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber])) {
            $current = self::${$where}[self::$currentField . self::$currentSubQueryNumber];
            if ($current['whereIn'] !== null && is_array($current['whereIn'])) {
                foreach ($current['whereIn'] as $key => $value) {
                    if (is_array($value) && !empty($value)) {
                        $in = addArray($value);
                        $string .= $i == 0 && $current['where'] == null && $current['whereColumn'] == null && $current['addTrashed'] == false ? ' WHERE ' . $key . ' IN (' . $in . ') ' : ' AND ' . $key . ' IN (' . $in . ') ';
                    } else {
                        $string .= $i == 0 && $current['where'] == null && $current['whereColumn'] == null && $current['addTrashed'] == false ? self::$whereZero : self::$andZero;
                    }
                    $i++;
                }
            } elseif ($current['whereIn'] !== null && !is_array($current['whereIn'])) {
                $currentField = getCurrentField(self::$subQueries, self::$currentField, self::$currentSubQueryNumber);

                $string .= $current['where'] == null && $current['whereColumn'] == null && $current['addTrashed'] == false ? ' WHERE ' . $currentField . ' IN (' . $current['whereIn'] . ')' : ' AND ' . $currentField . ' IN (' . $current['whereIn'] . ')';
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

    private static function getSubQueryWhereNotIn($where)
    {
        $string = null;
        $i = 0;
        if (isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber])) {
            $current = self::${$where}[self::$currentField . self::$currentSubQueryNumber];
            if ($current['whereNotIn'] !== null && is_array($current['whereNotIn'])) {
                foreach ($current['whereNotIn'] as $key => $value) {
                    if (is_array($value) && !empty($value)) {
                        $in = addArray($value);
                        $string .= $i == 0 && $current['where'] == null && $current['whereColumn'] && $current['whereIn'] == null && $current['addTrashed'] == false ?
                            ' WHERE ' . $key . ' NOT IN (' . $in . ') ' : ' AND ' . $key . ' NOT IN (' . $in . ') ';
                    } else {
                        $string .= $i == 0 && $current['where'] == null && $current['whereColumn'] && $current['whereIn'] == null && $current['addTrashed'] == false ? self::$whereZero : self::$andZero;
                    }
                    $i++;
                }
            } elseif ($current['whereNotIn'] !== null && !is_array($current['whereNotIn'])) {
                $currentField = getCurrentField(self::$subQueries, self::$currentField, self::$currentSubQueryNumber);

                $string .= $current['where'] == null && $current['whereColumn'] && $current['whereIn'] == null && $current['addTrashed'] == false ? ' WHERE ' . $currentField . ' NOT IN (' . $current['whereNotIn'] . ')' : ' AND ' . $currentField . ' NOT IN (' . $current['whereNotIn'] . ')';
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
        self::$caller = getCallerInfo();
        self::checkInstance();
        if (self::$currentSubQueryNumber == null) {
            self::checkUnionQuery();
            self::boot();
            self::$order = " ORDER BY " . $field . " " . $sort;
        }
        if (self::$currentSubQueryNumber !== null) {
            $currentQuery = self::showCurrentSubQuery();
            self::checkSubQueryUnionQuery($currentQuery);
            self::makeSubQueryOrderBy($currentQuery, $field, $sort);
        }
        return self::$instance;
    }

    private static function makeSubQueryOrderBy($where, $field, $sort)
    {
        if ($field == null) {
            $object = self::getSubQueryClassObject($where, self::${$where}[self::$currentField . self::$currentSubQueryNumber]['className']);
            $field = $object->getID();
        }
        self::${$where}[self::$currentField . self::$currentSubQueryNumber]['order'] = " ORDER BY " . $field . " " . $sort;
    }

    public static function latest(string $field = null)
    {
        self::$caller = getCallerInfo();
        self::checkInstance();
        if (self::$currentSubQueryNumber == null) {
            self::checkUnionQuery();
            self::boot();
            $field = $field == null ? self::$instance->getID() : $field;
            self::$order = " ORDER BY " . self::$table . '.' . $field . " DESC";
        }
        if (self::$currentSubQueryNumber !== null) {
            $currentQuery = self::showCurrentSubQuery();
            self::checkSubQueryUnionQuery($currentQuery);
            self::makeSubQueryOrderBy($currentQuery, $field, " DESC");
        }
        return self::$instance;
    }

    private static function getOrder()
    {
        return self::$order;
    }

    private static function getSubQueryOrder($where)
    {
        if (isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber]['order'])) {
            return self::${$where}[self::$currentField . self::$currentSubQueryNumber]['order'];
        }
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

    private static function disableForSQL()
    {
        self::$instance =
            self::$getID =
            self::$table =
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
            self::$select =
            self::$addSelect =
            self::$withTrashed =
            self::$addTrashed =
            self::$className =
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
        self::$toSQL = false;
        self::$subQueryLimitNumber = 0;

        self::$useUnionQuery = [0 => true];
        self::$unionQuery = [0 => null];
        self::$unionNumber = self::$currentUnionNumber = 0;
        self::$unableUnionQuery = [];
    }

    public static function union(callable $value)
    {
        self::$caller = getCallerInfo();
        return self::makeUnionQuery($value, ' UNION ');
    }

    public static function unionAll(callable $value)
    {
        self::$caller = getCallerInfo();
        return self::makeUnionQuery($value, ' UNION ALL ');
    }

    private static function checkUnion()
    {
        return isset(self::$unionQuery[self::$currentUnionNumber]) && self::$unionQuery[self::$currentUnionNumber] !== null && self::$useUnionQuery[self::$currentUnionNumber] == true;
    }

    private static function getQuery()
    {
        return self::checkUnion() ? self::$unionQuery[self::$currentUnionNumber] : self::getSQL();
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

    private static function makeUnionQuery($value, $union)
    {
        try {
            if (self::$currentSubQueryNumber == null) {
                $previousQuery = self::getQuery();
                self::disableForSQL();
                $uNumber = self::$currentUnionNumber;
                self::$useUnionQuery[$uNumber] = false;
                self::$unionNumber++;
                $newUnionQuery = $value();
                self::$useUnionQuery[$uNumber] = true;
                self::$currentUnionNumber = $uNumber;
                self::$unableUnionQuery[$uNumber] = true;
                self::boot();
                self::$unionQuery[$uNumber] = $previousQuery . $union . $newUnionQuery;
                return self::$instance;
            }
            if (self::$currentSubQueryNumber !== null) {
                $currentQuery = self::showCurrentSubQuery();
                $currentField = self::$currentField;
                $currentSubQueryNumber = self::$currentSubQueryNumber;
                if (
                    isset(self::${$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery']) &&
                    self::${$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery'] == true
                ) {
                    throw new Exception("You are not allowed to use " . $union, 1);
                }

                $previousUnionQuery = isset(self::${$currentQuery}[$currentField . $currentSubQueryNumber . 'unionQuery']) ?
                    self::${$currentQuery}[$currentField . $currentSubQueryNumber . 'unionQuery'] : null;
                $previousField = self::${$currentQuery}[$currentField . $currentSubQueryNumber];
                if ($previousUnionQuery == null) {
                    self::makeSubQuery($currentQuery);
                    $previousQuery = self::${$currentQuery}[$currentField];
                    $query = self::$instance;
                    $query->setSubQuery($currentField, $currentQuery, false);
                    self::$subQueries[$currentField . $currentSubQueryNumber] = $currentSubQueryNumber;
                    self::${$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery'] = true;
                    $value($query);
                    self::$currentField = $currentField;
                    self::$currentSubQueryNumber = $currentSubQueryNumber;
                    self::${$currentQuery}[$currentField . $currentSubQueryNumber . 'unionQuery'] = substr($previousQuery, 0, -1) . $union . self::${$currentQuery}[$currentField] . ')';
                    self::${$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery'] = false;
                    self::${$currentQuery}[$currentField . $currentSubQueryNumber] = self::makeSubQueryAttributes($previousField);
                }
                if ($previousUnionQuery !== null) {
                    self::${$currentQuery}[self::$currentField . self::$currentSubQueryNumber . 'unableUnionQuery'] = true;
                    unset(self::${$currentQuery}[self::$currentField]);
                    self::$subQueries[$currentField . $currentSubQueryNumber] = $currentSubQueryNumber;
                    $query = self::$instance;
                    $value($query);
                    self::$currentField = $currentField;
                    self::$currentSubQueryNumber = $currentSubQueryNumber;
                    self::${$currentQuery}[$currentField . $currentSubQueryNumber . 'unionQuery'] = substr($previousUnionQuery, 0, -1) . $union . self::${$currentQuery}[$currentField] . ')';
                    self::${$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery'] = false;
                    self::${$currentQuery}[$currentField . $currentSubQueryNumber] = self::makeSubQueryAttributes($previousField);
                }
            }
            return self::$instance;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function get()
    {
        try {
            self::$caller = getCallerInfo();
            self::checkInstance();
            if (self::$currentSubQueryNumber == null) {
                self::boot();
                $mainSQL = self::getQuery();
                if (self::$toSQL == true) {
                    self::disableForSQL();
                    return $mainSQL;
                }
                $class = get_called_class();
                $fields = self::getFields();
                $stmt = self::$instance->connectDatabase()->prepare($mainSQL);
                bindValues($stmt, $fields);
                $stmt->execute();
                self::disableBooting();
                $object = $stmt->fetchAll(PDO::FETCH_CLASS, $class);
                self::$selectedFields = [];
                self::$select = self::$table = null;
                if (self::$unionQuery !== null) {
                    self::$unionQuery = null;
                }
                return $object;
            }
            if (self::$currentSubQueryNumber !== null) {
                self::makeSubQuery(self::showCurrentSubQuery());
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
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

    private static function checkSubQuery($where)
    {
        return isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber]);
    }

    private static function makeMainSubQuery($where, $mainSQL)
    {
        self::$subQuery = $mainSQL;
        $currentField = self::$currentField;
        $currentSubQueryNumber = self::$currentSubQueryNumber;
        if (self::$currentField . self::$currentSubQueryNumber == array_key_first(self::$subQueries)) {
            self::${$where}[self::$currentField] = $mainSQL;
            if ($where == 'where' || $where == 'whereColumn' || $where == 'orWhere') {
                self::$whereSubQuery[self::$currentField . $where] = 'whereSubQuery';
            }
            self::$subQueries = [];
            self::makeDefaultSubQueryData();
        }

        if (isset(self::${$where}[$currentField . $currentSubQueryNumber])) {
            unset(self::${$where}[$currentField . $currentSubQueryNumber]);
        }
    }

    private static function makeSubQuery($where)
    {
        if (
            isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber . 'unionQuery']) &&
            isset(self::${$where}[self::$currentField . self::$currentSubQueryNumber . 'unableUnionQuery']) &&
            self::${$where}[self::$currentField . self::$currentSubQueryNumber . 'unableUnionQuery'] == false
        ) {
            $currentField = self::$currentField;
            $currentSubQueryNumber = self::$currentSubQueryNumber;
            self::${$where}[self::$currentField] = self::${$where}[self::$currentField . self::$currentSubQueryNumber . 'unionQuery'];
            if ($where == 'where' || $where == 'whereColumn' || $where == 'orWhere') {
                self::$whereSubQuery[self::$currentField . $where] = 'whereSubQuery';
            }
            self::$subQueries = [];
            self::makeDefaultSubQueryData();
            unset(self::${$where}[$currentField . $currentSubQueryNumber . 'unionQuery']);
            unset(self::${$where}[$currentField . $currentSubQueryNumber . 'unableUnionQuery']);
            if (isset(self::${$where}[$currentField . $currentSubQueryNumber])) {
                unset(self::${$where}[$currentField . $currentSubQueryNumber]);
            }
        } else {
            $mainSQL = self::getSubQuery($where);
            self::makeMainSubQuery($where, '(' . $mainSQL . ')');
        }
    }

    private static function getSQL()
    {
        return self::getSelect() .
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

    private static function getSubQuery($where)
    {
        $limit = self::getSubQueryLimit($where);
        $offset = self::getSubQueryOffset($where);
        $result = self::getSubQuerySelect($where) .
            self::getSubQueryWhere($where) .
            self::getSubQueryWhereColumn($where) .
            self::getSubQueryWhereIn($where) .
            self::getSubQueryWhereNotIn($where) .
            self::getSubQueryOrWhere($where) .
            self::getSubQueryOrder($where) .
            self::getSubQueryGroupBy($where) .
            self::getSubQueryHaving($where);
        return $limit == null ? $result . $offset : "SELECT * FROM (" . $result . $limit . $offset . ") AS l" . self::getSubQueryLimitNumber();
    }

    public static function toArray()
    {
        try {
            self::$caller = getCallerInfo();
            self::checkInstance();
            if (self::$currentSubQueryNumber !== null) {
                throw new Exception("Please use get() function in sub query to get sub query", 1);
            }
            if (self::$currentUnionNumber !== 0) {
                throw new Exception("Please use toArray function in main query", 1);
            }
            self::boot();
            $mainSQL = self::getQuery();
            $fields = self::getFields();
            $stmt = self::$instance->connectDatabase()->prepare($mainSQL);
            bindValues($stmt, $fields);
            $stmt->execute();
            self::disableBooting();
            self::$selectedFields = [];
            self::$select = self::$table = null;
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function toSQL()
    {
        self::$caller = getCallerInfo();
        self::checkInstance();
        if (self::$currentSubQueryNumber !== null) {
            throw new Exception("Don't use toSQL() function in sub query", 1);
        }
        self::boot();
        self::$toSQL = true;
        return self::$instance;
    }

    public static function addSelect(array $fields)
    {
        try {
            self::$caller = getCallerInfo();
            self::checkInstance();
            if (self::$currentSubQueryNumber == null) {
                self::checkUnionQuery();
                // If addSelect was used after using addOnlySelect function
                if (self::$instance !== null && self::$select == null && self::$addSelect == true) {
                    throw new Exception("You must not use addOnlySelect function before", 1);
                }
                self::boot();
                self::$addSelect = true;
                return self::addingSelect($fields);
            }
            throw new Exception("You are not allow to use addSelect function in subquery", 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    private static function addingSelect(array $fields)
    {
        try {
            $query = self::$instance;
            foreach ($fields as $select => $value) {
                if (!is_callable($value)) {
                    throw new Exception("You need to add function in array in addSelect function or addOnlySelect function.", 1);
                }
                $query->setSubQuery($select, 'selectQuery');
                self::$subQueries[$select . self::$currentSubQueryNumber] = self::$currentSubQueryNumber;
                $value($query);
                self::makeDefaultSubQueryData();
                self::$selectedFields[self::$className][$select] = $select;
            }
            return self::$instance;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function addOnlySelect(array $fields)
    {
        try {
            self::$caller = getCallerInfo();
            self::checkInstance();
            if (self::$currentSubQueryNumber == null) {
                self::checkUnionQuery();
                // If addOnlySelect function was used after using select or addSelect function //
                if (self::$instance !== null && self::$select !== self::$table . '.*') {
                    throw new Exception("You need to use only addOnlySelect function to select the data", 1);
                }
                self::boot();
                self::$select = null;
                self::$addSelect = true;
                return self::addingSelect($fields);
            }
            if (self::$currentSubQueryNumber !== null) {
                $check = self::showCurrentSubQuery();
                self::checkSubQueryUnionQuery($check);
                // If addOnlySelect function was used after using select or addSelect function //
                if (self::${$check}[self::$currentField . self::$currentSubQueryNumber]['select'] !== self::${$check}[self::$currentField . self::$currentSubQueryNumber]['table'] . '.*') {
                    throw new Exception("You need to use only addOnlySelect function to select the data", 1);
                }


                self::${$check}[self::$currentField . self::$currentSubQueryNumber]['select'] = null;
                self::${$check}[self::$currentField . self::$currentSubQueryNumber]['addSelect'] = true;
                foreach ($fields as $select => $value) {
                    if (!is_callable($value)) {
                        throw new Exception("You need to add function in array in addSelect function or addOnlySelect function.", 1);
                    }
                    self::makeSubQueryInSubQuery('selectQuery', $value, $select, $check);
                }
            }
            return self::$instance;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function paginate(int $per_page = 10)
    {
        try {
            self::$caller = getCallerInfo();
            self::checkInstance();
            if (self::$currentSubQueryNumber !== null) {
                throw new Exception("You can't use paginate() function in sub queries.", 1);
            }
            if (self::$currentUnionNumber !== 0) {
                throw new Exception("Please use paginate function in main query", 1);
            }
            self::boot();
            $paginate = new Paginate();
            $paginate->setPaginateData($per_page);

            $selectData = self::getSelect();
            $getWhere = self::getWhere();
            $getWhereIn = self::getWhereIn();
            $getWhereNotIn = self::getWhereNotIn();
            $getOrWhere = self::getOrWhere();
            $getOrder = self::getOrder();
            $getGroupBy = self::getGroupBy();
            $getHaving = self::getHaving();

            $mainSQL = self::checkUnion() ? self::$unionQuery[self::$currentUnionNumber] :
                $selectData .
                $getWhere .
                $getWhereIn .
                $getWhereNotIn .
                $getOrWhere .
                $getOrder .
                $getGroupBy .
                $getHaving;

            $sql = "SELECT * FROM (" . $mainSQL . ") AS paginate_data LIMIT " . $per_page . " OFFSET " . $paginate->getStart();

            $fields = self::getFields();
            $pdo = self::$instance->connectDatabase();
            $stmt = $pdo->prepare($sql);
            bindValues($stmt, $fields);
            $stmt->execute();

            $countSQL = 'SELECT COUNT(*) FROM (' . $mainSQL . ') AS countData';

            $countStmt = $pdo->prepare($countSQL);
            $countStmt->execute($fields);

            $objectArray = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
            self::$selectedFields = [];
            self::$select = self::$table = null;
            self::disableBooting();

            return $paginate->paginate(
                intval($countStmt->fetchColumn()),
                $objectArray
            );
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    private static function getJoin($sqlArray, $joinSQL)
    {
        foreach ($sqlArray as $table => $related) {
            self::$joinSQL .= $joinSQL . $table . " ON " . $related[1] . $related[2] . $related[0];
        }
    }

    private static function getSubQueryJoin($where, $sqlArray, $joinSQL)
    {
        foreach ($sqlArray as $table => $related) {
            self::${$where}[self::$currentField . self::$currentSubQueryNumber]['joinSQL'] .= $joinSQL . $table . " ON " . $related[1] . $related[2] . $related[0];
        }
    }

    private static function getJoinSQL()
    {
        return self::$joinSQL;
    }

    private static function getSubQueryJoinSQL($where)
    {
        return self::${$where}[self::$currentField . self::$currentSubQueryNumber]['joinSQL'];
    }

    private static function makeSubQueryJoin(array $parameters, string $join)
    {
        $table = $parameters[0];
        $ownField = $parameters[1];
        $field = $parameters[2];
        $operator = $parameters[3];
        $sqlArray = [];
        $sqlArray[$table] = [$ownField, $field, $operator];
        self::getSubQueryJoin(self::showCurrentSubQuery(), $sqlArray, $join);
    }

    private static function makeJoin(array $parameters, string $join)
    {
        $table = $parameters[0];
        $ownField = $parameters[1];
        $field = $parameters[2];
        $operator = $parameters[3];
        $sqlArray = [];
        $sqlArray[$table] = [$ownField, $field, $operator];
        self::getJoin($sqlArray, $join);
    }

    public static function innerJoin()
    {
        self::$caller = getCallerInfo();
        return self::sqlJoin(func_get_args(), ' INNER JOIN ');
    }

    public static function leftJoin()
    {
        self::$caller = getCallerInfo();
        return self::sqlJoin(func_get_args(), ' LEFT JOIN ');
    }

    public static function rightJoin()
    {
        self::$caller = getCallerInfo();
        return self::sqlJoin(func_get_args(), ' RIGHT JOIN ');
    }

    private static function sqlJoin(array $parameters, string $join)
    {
        try {
            self::checkInstance();
            $countParameters = count($parameters);
            if (
                $countParameters == 4 &&
                is_string($parameters[0]) &&
                is_string($parameters[1]) &&
                is_string($parameters[2]) &&
                is_string($parameters[3])
            ) {
                if (self::$currentSubQueryNumber == null) {
                    self::boot();
                    self::makeJoin($parameters, $join);
                }
                if (self::$currentSubQueryNumber !== null) {
                    self::makeSubQueryJoin($parameters, $join);
                }
                return self::$instance;
            }
            throw new Exception("You need to pass correct parameters");
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
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
