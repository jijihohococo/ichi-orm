<?php

namespace JiJiHoHoCoCo\IchiORM\QueryBuilder;

use PDO;
use Exception;
use ReflectionMethod;
use JiJiHoHoCoCo\IchiORM\Observer\ModelObserver;
use JiJiHoHoCoCo\IchiORM\Observer\ObserverSubject;
use JiJiHoHoCoCo\IchiORM\Pagination\Paginate;
use JiJiHoHoCoCo\IchiORM\Database\NullModel;

class QueryBuilder
{
    private $limitOne = " LIMIT 1";
    private $getID;
    private $table;
    private $fields = [];
    private $where;
    private $whereColumn;
    private $orWhere;
    private $whereIn;
    private $whereNotIn;
    private $operators;
    private $order;
    private $limit;
    private $offset;
    private $groupBy;
    private $joinSQL;
    private $select;
    private $addSelect;
    private $withTrashed;
    private $addTrashed;
    private $className;
    private $toSQL;
    private $numberOfSubQueries;
    private $currentSubQueryNumber;
    private $currentField;
    private $whereSubQuery;
    private $subQuery;
    private $subQueries = [];
    private $selectedFields = [];
    private $havingNumber = null;
    private $havingField;
    private $havingOperator;
    private $havingValue;
    private $whereZero = ' WHERE 0 = 1 ';
    private $andZero = ' AND 0 = 1 ';
    private $groupByString = ' GROUP BY ';
    private $selectQuery;
    protected $observerSubject;
    private $subQueryLimitNumber = 0;
    private $useUnionQuery = [0 => true];
    private $unionQuery = [0 => null];
    private $unionNumber = 0;
    private $currentUnionNumber = 0;
    private $unableUnionQuery = [];
    private $caller = [];
    private $calledClass;
    private $whereKeyCounter = 0;
    private static $lastSQLFields = [];

    private function setLastSQLFields(array $fields)
    {
        self::$lastSQLFields = $fields;
    }

    private function getLastSQLFields()
    {
        $fields = self::$lastSQLFields;
        self::$lastSQLFields = [];
        return $fields;
    }

    private function getModelArrayKeys()
    {
        $className = $this->getCalledClass();
        if ($className === null || !class_exists($className)) {
            return [];
        }
        $model = new $className();
        return get_object_vars($model);
    }

    public function setCalledClass(string $calledClass)
    {
        $this->calledClass = $calledClass;
    }

    public function getCalledClass()
    {
        return $this->calledClass;
    }

    protected function connectDatabase()
    {
        return connectPDO();
    }

    public function setTable(string $table)
    {
        $this->table = $table;
    }

    public function getTable()
    {
        return $this->table === null ? getTableName((string) $this->getCalledClass()) : $this->table;
    }

    public function getID()
    {
        return "id";
    }

    public function autoIncrementId()
    {
        return true;
    }

    public function withTrashed()
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        $query->checkInstance();
        if ($query->currentSubQueryNumber == null) {
            $query->checkUnionQuery();
            $query->boot();
            $query->withTrashed = true;
        } else {
            $currentQuery = $this->showCurrentSubQuery();
            $query->checkSubQueryUnionQuery($currentQuery);
            $query->makeSubQueryTrashTrue($currentQuery);
        }
        return $query;
    }

    private function makeSubQueryTrashTrue($where)
    {
        $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['withTrashed'] = true;
    }

    private function getSelect()
    {
        $select = $this->select;
        $driver = $this->connectDatabase()->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($this->selectQuery !== null) {
            $i = 0;
            foreach ($this->selectQuery as $selectAs => $query) {
                $selectData = $query . ' AS ' . $selectAs;
                $select .= $i == 0 && $select == null ? $selectData : ',' . $selectData;
                $i++;
            }
        }
        $from = ' FROM ' . $this->table . $this->getJoinSQL();
        if ($driver === 'sqlsrv' && $this->limit !== null && $this->offset === null) {
            return "SELECT TOP " . $this->limit . " " . $select . $from;
        }
        return "SELECT " . $select . $from;
    }

    private function makeDelete()
    {
        return "DELETE FROM " . $this->table . $this->getJoinSQL();
    }

    private function makeRestore()
    {
        return "UPDATE " . $this->table . " SET deleted_at=NULL" . $this->getJoinSQL();
    }

    private function getSubQuerySelect($where)
    {
        if (isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber])) {
            $current = $this->{$where}[$this->currentField . $this->currentSubQueryNumber];
            $select = $current['select'];
            if ($current['selectQuery'] !== null) {
                $i = 0;
                foreach ($current['selectQuery'] as $selectAs => $query) {
                    $selectData = '(' . $query . ') AS ' . $selectAs;
                    $select .= $i == 0 && $select == null ? $selectData : ',' . $selectData;
                    $i++;
                }
            }
            return "SELECT " . $select . " FROM " . $current['table'] . $this->getSubQueryJoinSQL($where);
        }
    }

    private function checkInstance()
    {
        try {
            if ($this->className !== null && $this->className !== $this->getCalledClass()) {
                throw new Exception(showDuplicateModelMessage($this->getCalledClass(), $this->className), 1);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    private function checkUnionQuery()
    {
        try {
            if (isset($this->unableUnionQuery[$this->currentUnionNumber]) && $this->unableUnionQuery[$this->currentUnionNumber] !== null) {
                throw new Exception("You are not allowed to use", 1);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    private function checkSubQueryUnionQuery($where)
    {
        try {
            if (
                isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber . 'unableUnionQuery']) &&
                $this->{$where}[$this->currentField . $this->currentSubQueryNumber . 'unableUnionQuery'] == false
            ) {
                throw new Exception("You are not allowed to use", 1);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    private function boot()
    {
        $this->getID = $this->getID();
        $this->table = $this->getTable();
        $calledClass = $this->getCalledClass();
        $this->className = $calledClass !== null && class_exists($calledClass) ? $calledClass : null;
        $this->select = $this->select ?? $this->table . '.*';
        $this->addSelect = $this->addSelect ?? false;
        $this->withTrashed = $this->withTrashed ?? false;
        $this->addTrashed = false;
    }

    public function groupBy(string $groupBy)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        $query->checkInstance();
        if ($query->currentSubQueryNumber == null) {
            $query->checkUnionQuery();
            $query->boot();
            $query->groupBy = $query->groupByString . $groupBy;
        }
        if ($query->currentSubQueryNumber !== null) {
            $currentQuery = $query->showCurrentSubQuery();
            $query->checkSubQueryUnionQuery($currentQuery);
            $query->makeSubQueryGroupBy($currentQuery, $groupBy);
        }
        return $query;
    }

    public function having(string $field, string $operator, $value)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        $query->checkInstance();
        if ($query->currentSubQueryNumber == null) {
            $query->checkUnionQuery();
            $query->boot();
            if ($query->havingNumber == null) {
                $query->havingNumber = 0;
            }
            $query->havingField[$query->havingNumber] = $field;
            $query->havingOperator[$query->havingNumber] = $operator;
            $query->havingValue[$query->havingNumber] = $value;
            $query->havingNumber++;
        }
        if ($query->currentSubQueryNumber !== null) {
            $currentQuery = $query->showCurrentSubQuery();
            $query->checkSubQueryUnionQuery($currentQuery);
            $query->makeSubQueryHaving($currentQuery, $field, $operator, $value);
        }
        return $query;
    }

    private function makeSubQueryHaving($where, $field, $operator, $value)
    {
        $current = $this->{$where}[$this->currentField . $this->currentSubQueryNumber];
        if ($current['havingNumber'] == null) {
            $current['havingNumber'] = 0;
        }
        $current['havingField'][$current['havingNumber']] = $field;
        $current['havingOperator'][$current['havingNumber']] = $operator;
        $current['havingValue'][$current['havingNumber']] = $value;
        $current['havingNumber']++;
    }

    private function makeSubQueryGroupBy($where, $groupBy)
    {
        $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['groupBy'] = $this->groupByString . $groupBy;
    }

    private function getGroupBy()
    {
        return $this->groupBy;
    }

    private function getHaving()
    {
        $string = null;
        if ($this->havingNumber !== null) {
            foreach (range(0, $this->havingNumber - 1) as $key => $value) {
                $result = $this->havingField[$key] . ' ' . $this->havingOperator[$key] . ' ' . $this->havingValue[$key];
                $string .= $key == 0 ? ' HAVING ' . $result : ' AND ' . $result;
            }
        }
        return $string;
    }

    private function getSubQueryHaving($where)
    {
        $string = null;
        if (isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber])) {
            $current = $this->{$where}[$this->currentField . $this->currentSubQueryNumber];
            if ($current['havingNumber'] !== null) {
                foreach (range(0, $current['havingNumber'] - 1) as $key => $value) {
                    $result = $current['havingField'][$key] . ' ' . $current['havingOperator'][$key] . ' ' . $current['havingValue'][$key];
                    $string .= $key == 0 ? ' HAVING ' . $result : ' AND ' . $result;
                }
            }
        }
        return $string;
    }

    private function getSubQueryGroupBy($where)
    {
        if (isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber]['groupBy'])) {
            return $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['groupBy'];
        }
    }

    public function bulkUpdate(array $attributes)
    {
        try {
            $this->caller = getCallerInfo();
            $this->checkInstance();
            if (empty($attributes)) {
                throw new Exception("You need to put non-empty array data", 1);
            }
            $this->boot();
            $instance = $this;
            $arrayKeys = $this->getModelArrayKeys();
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
            $updateString = 'UPDATE ' . $this->table . ' SET ' . substr(implode('', $updatedFields), 0, -2);
            $stmt = $instance->connectDatabase()->prepare($updateString);
            $i = 0;
            foreach ($updatedBindValues as $fields) {
                foreach ($fields as $value) {
                    $i++;
                    $stmt->bindValue($i, $value, getPDOBindDataType($value));
                }
            }
            $stmt->execute();
            $this->disableBooting();
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function insert(array $attributes)
    {
        try {
            $this->caller = getCallerInfo();
            if (empty($attributes)) {
                throw new Exception("You need to put non-empty array data", 1);
            }
            $this->boot();
            $instance = $this;
            $arrayKeys = $this->getModelArrayKeys();
            if (empty($arrayKeys)) {
                throw new Exception("You need to add column data", 1);
            }
            $getID = $instance->getID();
            unset($arrayKeys['deleted_at']);
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
                foreach ($arrayKeys as $key => $value) {
                    if ($key === $getID && $instance->autoIncrementId() == true && !array_key_exists($getID, $attribute)) {
                        continue;
                    }
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
            $stmt = $instance->connectDatabase()->prepare("INSERT INTO " . $this->table . " " . $fields . " VALUES " . $insertedValues);
            bindValues($stmt, $insertBindValues);
            $stmt->execute();
            $this->disableBooting();
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function create(array $attribute)
    {
        try {
            $this->caller = getCallerInfo();
            if (empty($attribute)) {
                throw new Exception("You need to put non-empty array data", 1);
            }
            $this->boot();
            $arrayKeys = $this->getModelArrayKeys();
            if (empty($arrayKeys)) {
                throw new Exception("You need to add column data", 1);
            }
            $getID = $this->getID();
            if ($this->autoIncrementId() == true && !isset($attribute[$getID])) {
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
            $pdo = $this->connectDatabase();
            $stmt = $pdo->prepare("INSERT INTO " . $this->table . " " . $fields . " VALUES " . $insertedValues);
            bindValues($stmt, $insertBindValues);
            $stmt->execute();
            $className = $this->className ?? $this->getCalledClass();
            $this->disableBooting();
            $object = new $className();
            $idData = [];
            if ($this->autoIncrementId() == true && !array_key_exists($getID, $attribute)) {
                $idData[$getID] = $pdo->lastInsertId();
            } elseif (array_key_exists($getID, $insertedData)) {
                $idData[$getID] = $insertedData[$getID];
            }

            $object = mappingModelData(
                $idData,
                $insertedData,
                $object
            );

            $this->makeObserver($className, 'create', $object);

            return $object;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    private function makeObserver(?string $className, string $method, $parameters)
    {
        if ($className !== null && $this->observerSubject !== null && $this->observerSubject->check($className)) {
            $this->observerSubject->use($className, $method, $parameters);
        }
    }

    public function update(array $attribute)
    {
        try {
            $this->caller = getCallerInfo();
            if (empty($attribute)) {
                throw new Exception("You need to put non-empty array data", 1);
            }
            $getID = $this->getID();
            $arrayKeys = $this->getModelArrayKeys();
            if (empty($arrayKeys)) {
                throw new Exception("You need to add column data", 1);
            }

            $updatedBindValues = [];
            $updatedFields = null;
            $updatedData = [];

            $idValue = isset($this->{$getID}) ? $this->{$getID} : null;

            foreach ($attribute as $key => $value) {
                if (array_key_exists($key, $arrayKeys)) {
                    $updatedData[$key] = $value;
                }
            }

            if (array_key_exists('updated_at', $arrayKeys) && !array_key_exists('updated_at', $updatedData)) {
                $updatedData['updated_at'] = now();
            }

            if (empty($updatedData)) {
                throw new Exception("You need to add available column data", 1);
            }

            foreach ($updatedData as $key => $value) {
                $updatedFields .= $key . '=?,';
            }

            $updatedArrayValues = array_values($updatedData);
            $updatedBindValues = array_merge($updatedBindValues, $updatedArrayValues);
            $updatedFields = substr($updatedFields, 0, -1);

            $whereQuery = null;

            if ($idValue !== null && $idValue !== '') {
                $whereQuery = " WHERE " . $getID . " = ?";
                $updatedBindValues[] = $idValue;
            } elseif ($this->where !== null || $this->whereColumn !== null || $this->whereIn !== null || $this->whereNotIn !== null) {
                $whereQuery = $this->getWhere() . $this->getWhereColumn() . $this->getWhereIn() . $this->getWhereNotIn();
                $fields = $this->getFields();
                if (!empty($fields)) {
                    $updatedBindValues = array_merge($updatedBindValues, $fields);
                }
            } else {
                throw new Exception("Model id or where condition is required for update", 1);
            }

            $stmt = $this->connectDatabase()->prepare("UPDATE " . $this->getTable() . " SET " . $updatedFields . $whereQuery);
            bindValues($stmt, $updatedBindValues);
            $stmt->execute();
            $object = mappingModelData([
                $getID => array_key_exists($getID, $updatedData) ? $updatedData[$getID] : $idValue
            ], $updatedData, $this);
            $this->makeObserver((string) get_class($this), 'update', $object);
            return $object;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function find($id)
    {
        try {
            $this->caller = getCallerInfo();
            $this->boot();
            $pdo = $this->connectDatabase();
            $getId = $this->getID();
            $selectSQL = $this->getSelect();
            $whereSQL = " WHERE " . $getId . " = ? ";
            $stmt = $pdo->prepare($this->getFindSQL($selectSQL, $whereSQL));
            bindValues($stmt, [
                0 => $id
            ]);
            $stmt->execute();
            $instance = $stmt->fetchObject($this->className);
            $this->where([$getId, $id]);
            $object = $this->getObject($instance);
            $this->disableBooting();
            return $object;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    private function getObject($instance)
    {
        return $instance == '' ? (new NullModel())->nullExecute() : $instance;
    }

    public function findBy(string $field, $value)
    {
        try {
            $this->caller = getCallerInfo();
            $this->boot();
            $pdo = $this->connectDatabase();
            $selectSQL = $this->getSelect();
            $whereSQL = " WHERE " . $field . " = ? ";
            $stmt = $pdo->prepare($this->getFindSQL($selectSQL, $whereSQL));
            bindValues($stmt, [
                0 => $value
            ]);
            $stmt->execute();
            $instance = $stmt->fetchObject($this->className);
            $this->where([$field, $value]);
            $object = $this->getObject($instance);
            $this->disableBooting();
            return $object;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    private function getFindSQL($selectSQL, $whereSQL)
    {
        $driver = $this->connectDatabase()->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlsrv') {
            return preg_replace(
                '/^SELECT\s+/i',
                'SELECT TOP 1 ',
                $selectSQL
            ) . $whereSQL;
        }
        return $selectSQL . $whereSQL . $this->limitOne;
    }

    public function delete()
    {
        $query = clone $this;
        try {
            $query->caller = getCallerInfo();
            $query->checkInstance();
            if ($query->currentSubQueryNumber !== null) {
                throw new Exception("delete function can't be used in subquery");
            }
            if ($query->currentSubQueryNumber == null) {
                $query->boot();
                $instance = $query;
                $mainSQL = $query->deleteQuery();
                $fields = $query->getFields();
                $stmt = $instance->connectDatabase()->prepare($mainSQL);
                bindValues($stmt, $fields);
                $stmt->execute();
                $query->disableBooting();
                $query->makeObserver((string) get_class($instance), 'delete', $instance);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($query->caller));
        }
    }

    public function forceDelete()
    {
        $query = clone $this;
        try {
            $query->caller = getCallerInfo();
            $query->checkInstance();
            if ($query->currentSubQueryNumber !== null) {
                throw new Exception("force delete function can't be used in subquery");
            }
            if ($query->currentSubQueryNumber == null) {
                $query->boot();
                $instance = $query;
                $mainSQL = $query->forceDeleteQuery();
                $fields = $query->getFields();
                $stmt = $instance->connectDatabase()->prepare($mainSQL);
                bindValues($stmt, $fields);
                $stmt->execute();
                $query->disableBooting();
                $query->makeObserver((string) get_class($instance), 'delete', $instance);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($query->caller));
        }
    }

    public function restore()
    {
        $query = clone $this;
        try {
            $query->caller = getCallerInfo();
            $query->checkInstance();
            if ($query->currentSubQueryNumber !== null) {
                throw new Exception("restore function can't be used in subquery");
            }
            if ($query->currentSubQueryNumber == null) {
                $query->boot();
                $query->withTrashed = true;
                $instance = $query;
                $mainSQL = $query->restoreQuery();
                $fields = $query->getFields();
                $stmt = $instance->connectDatabase()->prepare($mainSQL);
                bindValues($stmt, $fields);
                $stmt->execute();
                $query->disableBooting();
                $query->makeObserver((string) get_class($instance), 'restore', $instance);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($query->caller));
        }
    }

    public function select(array $fields)
    {
        $query = clone $this;
        try {
            $query->caller = getCallerInfo();
            $query->checkInstance();
            if ($query->currentSubQueryNumber == null) {
                $query->checkUnionQuery();
                if ($query->select == null && $query->addSelect == true) {
                    throw new Exception("You must not use addOnlySelect function before", 1);
                }

                $query->boot();
                if ($query->addSelect == false) {
                    $query->select = null;
                } else {
                    $query->select .= ',';
                }

                foreach ($fields as $key => $field) {
                    $query->trackSelectedField((string) $field);
                    $query->select .= $key + 1 == count($fields) ? $field : $field . ',';
                }
            } else {
                $check = $query->showCurrentSubQuery();
                $query->checkSubQueryUnionQuery($check);
                $addSelectCheck = $query->checkSubQueryAddSelect($check);
                if (
                    $query->{$check}[$query->currentField . $query->currentSubQueryNumber]['select'] == null &&
                    $addSelectCheck == true
                ) {
                    throw new Exception("You must not use addOnlySelect function before", 1);
                }

                if ($addSelectCheck == true) {
                    $query->addCommaToSubQuerySelect($check);
                }
                if ($addSelectCheck == false) {
                    $query->makeNullToSubQuerySelect($check);
                }

                foreach ($fields as $key => $field) {
                    $query->{$check}[$query->currentField . $query->currentSubQueryNumber]['select'] .= $key + 1 == count($fields) ? $field : $field . ',';
                }
            }
            return $query;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($query->caller));
        }
    }

    private function makeNullToSubQuerySelect($where)
    {
        $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['select'] = null;
    }

    private function addCommaToSubQuerySelect($where)
    {
        $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['select'] .= ',';
    }

    private function checkSubQueryAddSelect($where)
    {
        return $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['addSelect'];
    }

    public function limit(int $limit)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        $query->checkInstance();
        if ($query->currentSubQueryNumber == null) {
            $query->checkUnionQuery();
            $query->boot();
            $query->limit = $limit;
        }
        if ($query->currentSubQueryNumber !== null) {
            $check = $query->showCurrentSubQuery();
            $query->checkSubQueryUnionQuery($check);
            $query->{$check}[$query->currentField . $query->currentSubQueryNumber]['limit'] = $limit;
            $query->subQueryLimitNumber++;
        }
        return $query;
    }

    public function offset(int $offset)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        $query->checkInstance();
        $driver = $query->connectDatabase()->getAttribute(PDO::ATTR_DRIVER_NAME);
        $offset = $driver === 'sqlsrv' ? ' OFFSET ' . $offset . ' ROWS ' : ' OFFSET ' . $offset;
        if ($query->currentSubQueryNumber == null) {
            $query->checkUnionQuery();
            $query->boot();
            $query->offset = $offset;
        }
        if ($query->currentSubQueryNumber !== null) {
            $check = $query->showCurrentSubQuery();
            $query->checkSubQueryUnionQuery($check);
            $query->{$check}[$query->currentField . $query->currentSubQueryNumber]['offset'] = $offset;
        }
        return $query;
    }

    private function makeSubQueryAttributes($previousField = null, $alias = null)
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
            'table' => $previousField !== null && isset($previousField['table']) ? $previousField['table'] : $this->table,
            'select' => $previousField !== null && isset($previousField['table']) ? $previousField['table'] . '.*' : $this->table . '.*',
            'className' => null,
            'object' => null,
            'havingNumber' => null,
            'havingField' => null,
            'havingOperator' => null,
            'havingValue' => null,
            'selectQuery' => null,
            'alias' => $alias,
        ];
    }

    private function setSubQuery($field, $where, bool $increase = true)
    {
        if ($increase == true) {
            $this->numberOfSubQueries++;
        }
        $previousCheck = $this->currentSubQueryNumber !== null ? $this->showCurrentSubQuery() : null;
        $previousField = $previousCheck !== null ? $this->{$previousCheck}[$this->currentField . $this->currentSubQueryNumber] : null;
        $this->currentSubQueryNumber = $this->numberOfSubQueries;
        $uniqueKey = $field . '__' . $this->whereKeyCounter;
        $this->whereKeyCounter++;
        $this->currentField = $uniqueKey;
        $this->{$where}[$this->currentField . $this->currentSubQueryNumber] = $this->makeSubQueryAttributes($previousField, $field);
    }

    private function setSubWhere($where, $value, $field, $operator, $whereSelect)
    {
        $this->{$where}[$this->currentField . $this->currentSubQueryNumber][$whereSelect][$field] = $value;
        $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['operators'][$field . $whereSelect] = makeOperator($operator);
    }

    private function setSubWhereIn($where, $value, $field, $whereInSelect)
    {
        $this->{$where}[$this->currentField . $this->currentSubQueryNumber][$whereInSelect][$field] = $value;
    }

    private function makeDefaultSubQueryData()
    {
        $this->currentSubQueryNumber = null;
        $this->currentField = null;
    }

    private function showCurrentSubQuery()
    {
        foreach (getSubQueryTypes() as $subQuery) {
            if ($this->checkSubQuery($subQuery)) {
                return $subQuery;
            }
        }
    }

    private function normalizeParameters(array $parameters)
    {
        if (count($parameters) === 1 && is_array($parameters[0])) {
            return $parameters[0];
        }
        return $parameters;
    }

    public function where(...$parameters)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        $query = $query->makeWhereQuery($query->normalizeParameters($parameters), 'where');
        return $query;
    }

    private function makeSubQueryInSubQuery($whereSelect, $value, $field, $check)
    {
        $previousField = $this->currentField;
        $previousSubQueryNumber = $this->currentSubQueryNumber;
        $previousSubQueries = $this->subQueries;
        $query = $this;
        $query->setSubQuery($field, $check);
        $subQueryKey = $query->currentField . $query->currentSubQueryNumber;
        $query->subQueries[$subQueryKey] = $query->currentSubQueryNumber;
        $result = $value($query);
        if ($result instanceof self) {
            $query = $result;
        }

        if ($whereSelect !== 'selectQuery') {
            $query->{$check}[$previousField . $previousSubQueryNumber][$whereSelect] = $query->subQuery;
        }

        if ($whereSelect == 'selectQuery') {
            $query->{$check}[$previousField . $previousSubQueryNumber][$whereSelect][$field] = $query->subQuery;
        }

        $query->subQueries = $previousSubQueries;
        $query->currentField = $previousField;
        $query->currentSubQueryNumber = $previousSubQueryNumber;
        return $query;
    }

    public function from(string $className)
    {
        $query = clone $this;
        try {
            $query->caller = getCallerInfo();
            checkClass($className);
            $query->checkInstance();
            if ($query->currentSubQueryNumber !== null) {
                $currentQuery = $query->showCurrentSubQuery();
                $query->checkSubQueryUnionQuery($currentQuery);
                $query->addTableToSubQuery($currentQuery, $className);
                return $query;
            }
            throw new Exception("You can use 'from' function in only sub queries", 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($query->caller));
        }
    }

    private function getSubQueryClassObject($where, $className)
    {
        if ($this->{$where}[$this->currentField . $this->currentSubQueryNumber]['object'] == null) {
            $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['object'] = new $className();
        }
        return $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['object'];
    }

    private function addTableToSubQuery($where, $className)
    {
        try {
            $obj = $this->getSubQueryClassObject($where, $className);
            $reflectionMethod = new ReflectionMethod($className, 'getTable');
            $reflectionMethod->setAccessible(true);
            $table = $reflectionMethod->invoke($obj);
            if ($this->{$where}[$this->currentField . $this->currentSubQueryNumber]['select'] !== $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['table'] . '.*') {
                throw new Exception("You must use from function before selecting the data", 1);
            }
            $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['table'] = $table;
            $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['select'] = $table . '.*';
            $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['className'] = $className;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function whereColumn(...$parameters)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        $query = $query->makeWhereQuery($query->normalizeParameters($parameters), 'whereColumn');
        return $query;
    }

    public function orWhere(...$parameters)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        $query = $query->makeWhereQuery($query->normalizeParameters($parameters), 'orWhere');
        return $query;
    }

    private function makeWhereQuery(array $parameters, $where)
    {
        $query = $this;
        try {
            $query->checkInstance();
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

                if ($value === null && $operator == '=') {
                    $operator = ' IS ';
                }
                if ($value === null && ($operator == '!=' || $operator == '<>')) {
                    $operator = ' IS NOT ';
                }

                if (!is_callable($value) && $query->currentSubQueryNumber == null) {
                    $query->checkUnionQuery();
                    $query->boot();

                    // Create unique key for same field multiple times
                    $uniqueKey = $field . '__' . $query->whereKeyCounter;
                    $query->whereKeyCounter++;

                    $query->{$where}[$uniqueKey] = $value;
                    $query->operators[$uniqueKey . $where] = makeOperator($operator);

                    if ($value !== null && $where !== 'whereColumn') {
                        $query->fields[] = $value;
                    }
                }

                if (is_callable($value) && $query->currentSubQueryNumber == null) {
                    $query->checkUnionQuery();
                    $query->boot();
                    $query->setSubQuery($field, $where);
                    $query->operators[$query->currentField . $where] = makeOperator($operator);
                    $subQueryKey = $query->currentField . $query->currentSubQueryNumber;
                    $query->subQueries[$subQueryKey] = $query->currentSubQueryNumber;
                    $result = $value($query);
                    if ($result instanceof self) {
                        $query = $result;
                    }
                    $query->makeDefaultSubQueryData();
                }

                if (!is_callable($value) && $query->currentSubQueryNumber !== null) {
                    $currentQuery = $query->showCurrentSubQuery();
                    $query->checkSubQueryUnionQuery($currentQuery);
                    $query->setSubWhere($currentQuery, $value, $field, $operator, $where);
                    if ($value !== null && $where !== 'whereColumn') {
                        $query->fields[] = $value;
                    }
                }

                if (is_callable($value) && $query->currentSubQueryNumber !== null) {
                    $check = $query->showCurrentSubQuery();
                    $query->checkSubQueryUnionQuery($check);
                    $subQueryKey = $query->currentField . $query->currentSubQueryNumber;
                    $query->{$check}[$subQueryKey]['operators'][$query->currentField . $where] = makeOperator($operator);
                    $query = $query->makeSubQueryInSubQuery($where, $value, $field, $check);
                }
            } else {
                throw new Exception("Invalid Argument Parameter", 1);
            }
            return $query;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($query->caller));
        }
    }

    private function makeInQuery($whereIn, $field, $value)
    {
        $query = $this;
        try {
            $query->checkInstance();

            if (!is_array($value) && !is_callable($value) && $value !== null) {
                throw new Exception("You can add only array values or sub query in {$whereIn} function", 1);
            }

            if ((is_array($value) || $value === null) && $query->currentSubQueryNumber == null) {
                $query->checkUnionQuery();
                $query->boot();
                $query->{$whereIn}[$field] = $value;
                if ($value !== null) {
                    $query->fields[] = $value;
                }
            }
            if (is_callable($value) && $query->currentSubQueryNumber == null) {
                $query->checkUnionQuery();
                $query->boot();
                $query->setSubQuery($field, $whereIn, $field);
                $subQueryKey = $query->currentField . $query->currentSubQueryNumber;
                $query->subQueries[$subQueryKey] = $query->currentSubQueryNumber;
                $result = $value($query);
                if ($result instanceof self) {
                    $query = $result;
                }
                $query->makeDefaultSubQueryData();
            }
            if ((is_array($value) || $value === null) && $query->currentSubQueryNumber !== null) {
                $currentQuery = $query->showCurrentSubQuery();
                $query->checkSubQueryUnionQuery($currentQuery);
                $query->setSubWhereIn($currentQuery, $value, $field, $whereIn);
                if ($value !== null) {
                    $query->fields[] = $value;
                }
            }
            if (is_callable($value) && $query->currentSubQueryNumber !== null) {
                $currentQuery = $query->showCurrentSubQuery();
                $query->checkSubQueryUnionQuery($currentQuery);
                $query = $query->makeSubQueryInSubQuery($whereIn, $value, $field, $currentQuery);
            }
            return $query;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($query->caller));
        }
    }

    public function whereIn(string $field, $value)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        $query = $query->makeInQuery('whereIn', $field, $value);
        return $query;
    }

    public function whereNotIn(string $field, $value)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        $query = $query->makeInQuery('whereNotIn', $field, $value);
        return $query;
    }

    private function getLimit()
    {
        $driver = $this->connectDatabase()->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlsrv' || $this->limit === null) {
            return null;
        }
        return ' LIMIT ' . $this->limit;
    }

    private function getOffset()
    {
        $driver = $this->connectDatabase()->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlsrv' && $this->offset !== null && $this->limit !== null) {
            return $this->offset . 'FETCH NEXT ' . $this->limit . ' ROWS ONLY ';
        }
        return $this->offset;
    }

    private function getSubQueryLimit($where)
    {
        if (isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber])) {
            $limit = $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['limit'];
            return $limit;
        }
    }

    private function getSubQueryOffset($where)
    {
        if (isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber])) {
            $offset = $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['offset'];
            return $offset;
        }
    }

    private function getSubQueryLimitNumber()
    {
        return $this->subQueryLimitNumber;
    }

    private function checkTrashed()
    {
        return property_exists($this, 'deleted_at') && $this->withTrashed == false;
    }

    private function checkSubQueryTrashed($where)
    {
        $subClassName = $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['className'];
        $className = $subClassName == null ? $this->getCalledClass() : $subClassName;
        return property_exists($className, 'deleted_at') && $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['withTrashed'] == false;
    }

    private function getSubQueryWhere($where)
    {
        $string = null;
        $i = 0;
        if (isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber])) {
            $current = $this->{$where}[$this->currentField . $this->currentSubQueryNumber];
            if ($current['where'] !== null && is_array($current['where'])) {
                $string = ' WHERE ';
                foreach ($current['where'] as $key => $value) {
                    $operator = $current['operators'][$key . 'where'];
                    if ($value === null) {
                        $string .= $i == 0 ? $key . $operator . 'NULL' : ' AND ' . $key . $operator . 'NULL';
                    } else {
                        $string .= $i == 0 ? $key . $operator . '?' : ' AND ' . $key . $operator . '?';
                    }
                    $i++;
                }
            } elseif ($current['where'] !== null && !is_array($current['where'])) {
                $currentField = getCurrentField($this->subQueries, $this->currentField, $this->currentSubQueryNumber);
                $string = ' WHERE ' . $currentField . $current['operators'][$currentField . 'where'] . ' (' . $current['where'] . ') ';
            }
            if ($this->checkSubQueryTrashed($where)) {
                $isNULL = $current['table'] . '.deleted_at IS NULL';
                $string .= $current['where'] == null ? ' WHERE ' . $isNULL : ' AND ' . $isNULL;
                $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['addTrashed'] = true;
            }
        }
        return $string;
    }

    private function getWhere()
    {
        $string = null;
        $i = 0;
        if ($this->where !== null) {
            $string = ' WHERE ';

            foreach ($this->where as $uniqueKey => $value) {
                // Extract original field name from unique key (remove __counter suffix)
                $field = preg_replace('/__\d+$/', '', $uniqueKey);

                if (isset($this->whereSubQuery[$uniqueKey . 'where'])) {
                    $string .= $i == 0 ? $field . $this->operators[$uniqueKey . 'where'] . $value : ' AND ' . $field . $this->operators[$uniqueKey . 'where'] . $value;
                } else {
                    if ($value === null) {
                        $string .= $i == 0 ? $field . $this->operators[$uniqueKey . 'where'] . 'NULL' : ' AND ' . $field . $this->operators[$uniqueKey . 'where'] . 'NULL';
                    } else {
                        $string .= $i == 0 ? $field . $this->operators[$uniqueKey . 'where'] . '?' : ' AND ' . $field . $this->operators[$uniqueKey . 'where'] . '?';
                    }
                }
                $i++;
            }
        }

        if ($this->checkTrashed()) {
            $isNULL = $this->table . '.deleted_at IS NULL';
            $string .= $this->where == null ? ' WHERE ' . $isNULL : ' AND ' . $isNULL;
            $this->addTrashed = true;
        }
        return $string;
    }

    private function getWhereColumn()
    {
        $string = null;
        $i = 0;
        if ($this->whereColumn !== null) {
            foreach ($this->whereColumn as $uniqueKey => $value) {
                $field = preg_replace('/__\d+$/', '', $uniqueKey);
                $result = $field . $this->operators[$uniqueKey . 'whereColumn'] . $value;
                $string .= $i == 0 && $this->where == null && $this->addTrashed == false ? ' WHERE ' . $result : ' AND ' . $result;
                $i++;
            }
        }
        return $string;
    }

    private function getSubQueryWhereColumn($where)
    {
        $string = null;
        $i = 0;
        $subQueryKey = $this->currentField . $this->currentSubQueryNumber;
        if (!isset($this->{$where}[$subQueryKey])) {
            return $string;
        }
        $current = $this->{$where}[$subQueryKey];
        if ($current['whereColumn'] !== null && is_array($current['whereColumn'])) {
            foreach ($current['whereColumn'] as $key => $value) {
                $operatorKey = $key . 'whereColumn';
                $operator = isset($current['operators'][$operatorKey]) ? $current['operators'][$operatorKey] : '=';
                $result = $key . $operator . $value;
                $string .= $i == 0 && $current['where'] == null && $current['addTrashed'] == false ? ' WHERE ' . $result : ' AND ' . $result;
                $i++;
            }
        }
        if ($current['whereColumn'] !== null && !is_array($current['whereColumn'])) {
            $currentField = getCurrentField($this->subQueries, $this->currentField, $this->currentSubQueryNumber);
            $operatorKey = $this->currentField . $where;
            $operator = isset($current['operators'][$operatorKey]) ? $current['operators'][$operatorKey] : '=';
            $result = $currentField . $operator . ' (' . $current['whereColumn'] . ') ';
            $string .= $current['where'] == null && $current['addTrashed'] == false ? ' WHERE ' . $result : ' AND ' . $result;
        }
        return $string;
    }

    private function getOrWhere()
    {
        $string = null;
        if ($this->orWhere !== null) {
            foreach ($this->orWhere as $uniqueKey => $value) {
                $field = preg_replace('/__\d+$/', '', $uniqueKey);
                if (isset($this->whereSubQuery[$uniqueKey . 'orWhere'])) {
                    $string .= ' OR ' . $field . $this->operators[$uniqueKey . 'orWhere'] . $value;
                } else {
                    $string .= ' OR ' . $field . $this->operators[$uniqueKey . 'orWhere'] . '?';
                }
            }
        }
        return $string;
    }

    private function getSubQueryOrWhere($where)
    {
        $string = null;
        if (isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber])) {
            $current = $this->{$where}[$this->currentField . $this->currentSubQueryNumber];
            if ($current['orWhere'] !== null && is_array($current['orWhere'])) {
                foreach ($current['orWhere'] as $key => $value) {
                    $string .= ' OR ' . $key . $current['operators'][$key . 'orWhere'] . '?';
                }
            }
            if ($current['orWhere'] !== null && !is_array($current['orWhere'])) {
                $currentField = getCurrentField($this->subQueries, $this->currentField, $this->currentSubQueryNumber);
                $string .= ' OR ' . $currentField . $current['operators'][$currentField . 'orWhere'] . ' (' . $current['orWhere'] . ') ';
            }
        }
        return $string;
    }

    private function getWhereInField(string $field)
    {
        $driver = $this->connectDatabase()->getAttribute(PDO::ATTR_DRIVER_NAME);

        switch ($driver) {
            case 'mysql':
                return 'CAST(' . $field . ' AS CHAR)';

            case 'pgsql':
            case 'sqlite':
                return 'CAST(' . $field . ' AS TEXT)';

            case 'sqlsrv':
                return 'CAST(' . $field . ' AS VARCHAR(MAX))';

            default:
                return $field;
        }
    }

    private function getWhereIn()
    {
        $string = null;
        $i = 0;
        if ($this->whereIn !== null) {
            foreach ($this->whereIn as $key => $value) {
                $field = preg_replace('/__\d+$/', '', $key);
                if (is_array($value) && !empty($value)) {
                    $in = addArray($value);
                    $condition = $this->getWhereInField($field) . ' IN (' . $in . ')';
                    $string .= $i == 0 && $this->where == null && $this->whereColumn == null && $this->addTrashed == false ? ' WHERE ' . $condition . ' ' : ' AND ' . $condition . ' ';
                } elseif ($value !== null && !is_array($value)) {
                    $string .= $i == 0 && $this->where == null && $this->whereColumn == null && $this->addTrashed == false ? ' WHERE ' . $field . ' IN ' . $value : ' AND ' . $field . ' IN ' . $value;
                } else {
                    $string .= $i == 0 && $this->where == null && $this->whereColumn == null && $this->addTrashed == false ? $this->whereZero : $this->andZero;
                }
                $i++;
            }
        }
        return $string;
    }

    private function getSubQueryWhereIn($where)
    {
        $string = null;
        $i = 0;
        if (isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber])) {
            $current = $this->{$where}[$this->currentField . $this->currentSubQueryNumber];
            if ($current['whereIn'] !== null && is_array($current['whereIn'])) {
                foreach ($current['whereIn'] as $key => $value) {
                    if (is_array($value) && !empty($value)) {
                        $in = addArray($value);
                        $condition = $this->getWhereInField($key) . ' IN (' . $in . ')';
                        $string .= $i == 0 && $current['where'] == null && $current['whereColumn'] == null && $current['addTrashed'] == false ? ' WHERE ' . $condition . ' ' : ' AND ' . $condition . ' ';
                    } else {
                        $string .= $i == 0 && $current['where'] == null && $current['whereColumn'] == null && $current['addTrashed'] == false ? $this->whereZero : $this->andZero;
                    }
                    $i++;
                }
            } elseif ($current['whereIn'] !== null && !is_array($current['whereIn'])) {
                $currentField = getCurrentField($this->subQueries, $this->currentField, $this->currentSubQueryNumber);
                $string .= $current['where'] == null && $current['whereColumn'] == null && $current['addTrashed'] == false ? ' WHERE ' . $currentField . ' IN ' . $current['whereIn'] . ' ' : ' AND ' . $currentField . ' IN ' . $current['whereIn'] . ' ';
            }
        }
        return $string;
    }

    private function getWhereNotIn()
    {
        $string = null;
        $i = 0;
        if ($this->whereNotIn !== null) {
            foreach ($this->whereNotIn as $key => $value) {
                $field = preg_replace('/__\d+$/', '', $key);
                if (is_array($value) && !empty($value)) {
                    $in = addArray($value);
                    $string .= $i == 0 && $this->where == null && $this->whereColumn == null && $this->whereIn == null && $this->addTrashed == false ?
                        ' WHERE ' . $field . ' NOT IN (' . $in . ') ' : ' AND ' . $field . ' NOT IN (' . $in . ') ';
                } elseif ($value !== null && !is_array($value)) {
                    $string .= $i == 0 && $this->where == null && $this->whereColumn == null && $this->whereIn == null && $this->addTrashed == false ? ' WHERE ' . $field . ' NOT IN ' . $value : ' AND ' . $field . ' NOT IN ' . $value;
                } else {
                    $string .= $i == 0 && $this->where == null && $this->whereColumn == null && $this->whereIn == null && $this->addTrashed == false ? $this->whereZero : $this->andZero;
                }
                $i++;
            }
        }
        return $string;
    }

    private function getSubQueryWhereNotIn($where)
    {
        $string = null;
        $i = 0;
        if (isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber])) {
            $current = $this->{$where}[$this->currentField . $this->currentSubQueryNumber];
            if ($current['whereNotIn'] !== null && is_array($current['whereNotIn'])) {
                foreach ($current['whereNotIn'] as $key => $value) {
                    if (is_array($value) && !empty($value)) {
                        $in = addArray($value);
                        $string .= $i == 0 && $current['where'] == null && $current['whereColumn'] && $current['whereIn'] == null && $current['addTrashed'] == false ?
                            ' WHERE ' . $key . ' NOT IN (' . $in . ') ' : ' AND ' . $key . ' NOT IN (' . $in . ') ';
                    } else {
                        $string .= $i == 0 && $current['where'] == null && $current['whereColumn'] && $current['whereIn'] == null && $current['addTrashed'] == false ? $this->whereZero : $this->andZero;
                    }
                    $i++;
                }
            } elseif ($current['whereNotIn'] !== null && !is_array($current['whereNotIn'])) {
                $currentField = getCurrentField($this->subQueries, $this->currentField, $this->currentSubQueryNumber);
                $string .= $current['where'] == null && $current['whereColumn'] && $current['whereIn'] == null && $current['addTrashed'] == false ? ' WHERE ' . $currentField . ' NOT IN (' . $current['whereNotIn'] . ')' : ' AND ' . $currentField . ' NOT IN (' . $current['whereNotIn'] . ')';
            }
        }
        return $string;
    }

    private function getFields()
    {
        return $this->fields;
    }

    public function orderBy(string $field, string $sort = "ASC")
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        $query->checkInstance();
        if ($query->currentSubQueryNumber == null) {
            $query->checkUnionQuery();
            $query->boot();
            $query->order = " ORDER BY " . $field . " " . $sort;
        }
        if ($query->currentSubQueryNumber !== null) {
            $currentQuery = $query->showCurrentSubQuery();
            $query->checkSubQueryUnionQuery($currentQuery);
            $query->makeSubQueryOrderBy($currentQuery, $field, $sort);
        }
        return $query;
    }

    private function makeSubQueryOrderBy($where, $field, $sort)
    {
        if ($field == null) {
            $object = $this->getSubQueryClassObject($where, $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['className']);
            $field = $object->getID();
        }
        $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['order'] = " ORDER BY " . $field . " " . $sort;
    }

    public function latest(string $field = null)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        $query->checkInstance();
        if ($query->currentSubQueryNumber == null) {
            $query->checkUnionQuery();
            $query->boot();
            $field = $field == null ? $query->getID() : $field;
            $query->order = " ORDER BY " . $query->table . '.' . $field . " DESC";
        }
        if ($query->currentSubQueryNumber !== null) {
            $currentQuery = $query->showCurrentSubQuery();
            $query->checkSubQueryUnionQuery($currentQuery);
            $query->makeSubQueryOrderBy($currentQuery, $field, " DESC");
        }
        return $query;
    }

    private function getOrder()
    {
        return $this->order;
    }

    private function getSubQueryOrder($where)
    {
        if (isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber]['order'])) {
            return $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['order'];
        }
    }

    private function disableBooting()
    {
        $this->getID =
        $this->fields =
        $this->where =
        $this->whereColumn =
        $this->orWhere =
        $this->whereIn =
        $this->whereNotIn =
        $this->operators =
        $this->order =
        $this->limit =
        $this->offset =
        $this->groupBy =
        $this->joinSQL =
        $this->addSelect =
        $this->withTrashed =
        $this->addTrashed =
        $this->className =
        $this->toSQL =
        $this->numberOfSubQueries =
        $this->currentSubQueryNumber =
        $this->currentField =
        $this->whereSubQuery =
        $this->subQuery =
        $this->havingNumber =
        $this->havingField =
        $this->havingOperator =
        $this->havingValue =
        $this->selectQuery = null;
        $this->subQueries = [];
        $this->subQueryLimitNumber = 0;
        $this->whereKeyCounter = 0;

        $this->useUnionQuery = [0 => true];
        $this->unionQuery = [0 => null];
        $this->unionNumber = $this->currentUnionNumber = 0;
        $this->unableUnionQuery = [];
    }

    private function disableForSQL()
    {
        $this->getID =
        $this->table =
        $this->where =
        $this->whereColumn =
        $this->orWhere =
        $this->whereIn =
        $this->whereNotIn =
        $this->operators =
        $this->order =
        $this->limit =
        $this->offset =
        $this->groupBy =
        $this->joinSQL =
        $this->select =
        $this->addSelect =
        $this->withTrashed =
        $this->addTrashed =
        $this->className =
        $this->numberOfSubQueries =
        $this->currentSubQueryNumber =
        $this->currentField =
        $this->whereSubQuery =
        $this->subQuery =
        $this->havingNumber =
        $this->havingField =
        $this->havingOperator =
        $this->havingValue =
        $this->selectQuery = null;
        $this->subQueries = [];
        $this->toSQL = false;
        $this->subQueryLimitNumber = 0;
        $this->whereKeyCounter = 0;

        $this->useUnionQuery = [0 => true];
        $this->unionQuery = [0 => null];
        $this->unionNumber = $this->currentUnionNumber = 0;
        $this->unableUnionQuery = [];
    }

    public function union(callable $value)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        return $query->makeUnionQuery($value, ' UNION ');
    }

    public function unionAll(callable $value)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        return $query->makeUnionQuery($value, ' UNION ALL ');
    }

    private function checkUnion()
    {
        return isset($this->unionQuery[$this->currentUnionNumber]) && $this->unionQuery[$this->currentUnionNumber] !== null && $this->useUnionQuery[$this->currentUnionNumber] == true;
    }

    private function getQuery()
    {
        return $this->checkUnion() ? $this->unionQuery[$this->currentUnionNumber] : $this->getSQL();
    }

    private function deleteQuery()
    {
        if ($this->checkUnion()) {
            throw new Exception("delete function can't be used in union");
        }
        return $this->deleteSQL();
    }

    private function forceDeleteQuery()
    {
        if ($this->checkUnion()) {
            throw new Exception("delete function can't be used in union");
        }
        return $this->forceDeleteSQL();
    }

    private function restoreQuery()
    {
        if ($this->checkUnion()) {
            throw new Exception("restore function can't be used in union");
        }
        return $this->restoreSQL();
    }

    private function formatUnionQuery($previousQuery, $union, $secondQuery)
    {
        $driver = $this->connectDatabase()->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            return '(' . substr($previousQuery, 1, -1) . $union . substr($secondQuery, 1, -1) . ')';
        }

        return substr($previousQuery, 0, -1) . $union . $secondQuery . ')';
    }

    private function makeUnionQuery($value, $union)
    {
        $query = $this;
        try {
            if ($query->currentSubQueryNumber == null) {
                $previousQuery = $query->getQuery();
                $previousFields = $query->getFields();
                $query->disableForSQL();
                $uNumber = $query->currentUnionNumber;
                $query->useUnionQuery[$uNumber] = false;
                $query->unionNumber++;
                $result = $value($query);
                if (!$result instanceof self) {
                    throw new Exception("Unable to build UNION query", 1);
                }
                $query = $result;
                $newUnionQuery = $query->getQuery();
                $newUnionFields = $query->getFields();
                $query->fields = array_merge($previousFields, $newUnionFields);
                $query->useUnionQuery[$uNumber] = true;
                $query->currentUnionNumber = $uNumber;
                $query->unableUnionQuery[$uNumber] = true;
                $query->unionQuery[$uNumber] = $previousQuery . $union . $newUnionQuery;
                $query->boot();
                return $query;
            }
            if ($query->currentSubQueryNumber !== null) {
                $currentQuery = $query->showCurrentSubQuery();
                $currentField = $query->currentField;
                $currentSubQueryNumber = $query->currentSubQueryNumber;
                if (
                    isset($query->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery']) &&
                    $query->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery'] == true
                ) {
                    throw new Exception("You are not allowed to use " . $union, 1);
                }

                $previousUnionQuery = isset($query->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unionQuery']) ?
                    $query->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unionQuery'] : null;
                $previousField = $query->{$currentQuery}[$currentField . $currentSubQueryNumber];
                if ($previousUnionQuery == null) {
                    $query->makeSubQuery($currentQuery);
                    $previousQuery = $query->{$currentQuery}[$currentField];
                    $query->setSubQuery($currentField, $currentQuery, false);
                    $secondField = $query->currentField;
                    $secondSubQueryKey = $secondField . $query->currentSubQueryNumber;
                    $query->subQueries[$secondSubQueryKey] = $currentSubQueryNumber;
                    $query->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery'] = true;
                    $result = $value($query);
                    if ($result instanceof self) {
                        $query = $result;
                    }
                    if (!isset($query->{$currentQuery}[$secondField])) {
                        throw new Exception("Unable to build UNION sub query", 1);
                    }
                    $secondQuery = $query->{$currentQuery}[$secondField];
                    unset($query->{$currentQuery}[$secondField]);
                    $query->currentField = $currentField;
                    $query->currentSubQueryNumber = $currentSubQueryNumber;
                    $query->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unionQuery'] = $query->formatUnionQuery($previousQuery, $union, $secondQuery);
                    $query->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery'] = false;
                    $query->{$currentQuery}[$currentField . $currentSubQueryNumber] = $query->makeSubQueryAttributes($previousField);
                }
                if ($previousUnionQuery !== null) {
                    $query->{$currentQuery}[$query->currentField . $query->currentSubQueryNumber . 'unableUnionQuery'] = true;
                    $query->setSubQuery($currentField, $currentQuery, false);
                    $secondField = $query->currentField;
                    $secondSubQueryKey = $secondField . $query->currentSubQueryNumber;
                    $query->subQueries[$secondSubQueryKey] = $currentSubQueryNumber;
                    $result = $value($query);
                    if ($result instanceof self) {
                        $query = $result;
                    }
                    if (!isset($query->{$currentQuery}[$secondField])) {
                        throw new Exception("Unable to build UNION sub query", 1);
                    }
                    $secondQuery = $query->{$currentQuery}[$secondField];
                    unset($query->{$currentQuery}[$secondField]);
                    $query->currentField = $currentField;
                    $query->currentSubQueryNumber = $currentSubQueryNumber;
                    $query->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unionQuery'] = $query->formatUnionQuery($previousUnionQuery, $union, $secondQuery);
                    $query->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery'] = false;
                    $query->{$currentQuery}[$currentField . $currentSubQueryNumber] = $query->makeSubQueryAttributes($previousField);
                }
            }
            return $query;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($query->caller));
        }
    }

    public function get()
    {
        $query = clone $this;
        try {
            $query->caller = getCallerInfo();
            $query->checkInstance();
            //if ($query->currentSubQueryNumber == null) {
                // $query->boot();
                // $mainSQL = $query->getQuery();
                // if ($query->toSQL == true) {
                //     $query->setLastSQLFields($query->getFields());
                //     $query->disableForSQL();
                //     return $mainSQL;
                // }
                // $class = $query->getCalledClass();
                // $fields = $query->getFields();
                // $stmt = $query->connectDatabase()->prepare($mainSQL);
                // bindValues($stmt, $fields);
                // $stmt->execute();
                // $query->disableBooting();
                // $object = $stmt->fetchAll(PDO::FETCH_CLASS, $class);
                // if ($query->shouldFilterSelectedFields($class)) {
                //     $object = $query->filterSelectedFields($object, $class);
                // }
                // $query->selectedFields = [];
                // $query->select = $query->table = null;
                // if ($query->unionQuery !== null) {
                //     $query->unionQuery = null;
                // }
                // return $object;
            //}
            if ($query->currentSubQueryNumber !== null) {
                $query->makeSubQuery($query->showCurrentSubQuery());
                return $query;
            }
            if ($query->currentUnionNumber !== null && isset($query->useUnionQuery[$query->currentUnionNumber]) && $query->useUnionQuery[$query->currentUnionNumber] === false) {
                //$query->boot();
                return $query;
            }
            $query->boot();
                $mainSQL = $query->getQuery();
                if ($query->toSQL == true) {
                    $query->setLastSQLFields($query->getFields());
                    $query->disableForSQL();
                    return $mainSQL;
                }
                $class = $query->getCalledClass();
                $fields = $query->getFields();
                $stmt = $query->connectDatabase()->prepare($mainSQL);
                bindValues($stmt, $fields);
                $stmt->execute();
                $query->disableBooting();
                $object = $stmt->fetchAll(PDO::FETCH_CLASS, $class);
                if ($query->shouldFilterSelectedFields($class)) {
                    $object = $query->filterSelectedFields($object, $class);
                }
                $query->selectedFields = [];
                $query->select = $query->table = null;
                if ($query->unionQuery !== null) {
                    $query->unionQuery = null;
                }
                return $object;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($query->caller));
        }
    }

    public function new()
    {
        $class = $this->getCalledClass();
        if (!empty($this->selectedFields) && isset($this->selectedFields[$class]) && $this->select !== $this->table . '.*' && $this->select !== null) {
            foreach (get_object_vars($this) as $key => $value) {
                if (!isset($this->selectedFields[$class][$key])) {
                    unset($this->{$key});
                }
            }
        }
        if ($this->select == null && !empty($this->selectedFields) && isset($this->selectedFields[$class])) {
            foreach (get_object_vars($this) as $key => $value) {
                if (isset($this->selectedFields[$class][$key])) {
                    $this->{$key} = $value;
                } else {
                    unset($this->{$key});
                }
            }
        }
    }

    private function shouldFilterSelectedFields(?string $class): bool
    {
        return $class !== null &&
            !empty($this->selectedFields) &&
            isset($this->selectedFields[$class]) &&
            !isset($this->selectedFields[$class]['*']) &&
            $this->select !== null &&
            $this->table !== null &&
            $this->select !== $this->table . '.*';
    }

    private function trackSelectedField(string $field): void
    {
        $class = $this->className ?? $this->getCalledClass();
        if ($class === null) {
            return;
        }

        $normalizedField = trim($field);
        if ($normalizedField === '*') {
            $this->selectedFields[$class]['*'] = '*';
            return;
        }

        if (substr($normalizedField, -2) === '.*') {
            $this->selectedFields[$class]['*'] = '*';
            return;
        }

        $parts = preg_split('/\s+as\s+/i', $normalizedField);
        if (is_array($parts) && count($parts) === 2) {
            $alias = trim($parts[1], "` \t\n\r\0\x0B");
            if ($alias !== '') {
                $this->selectedFields[$class][$alias] = $alias;
            }
            return;
        }

        if (strpos($normalizedField, '(') !== false || strpos($normalizedField, ')') !== false) {
            return;
        }

        if (strpos($normalizedField, '.') !== false) {
            $segments = explode('.', $normalizedField);
            $normalizedField = end($segments);
        }

        $column = trim($normalizedField, "` \t\n\r\0\x0B");
        if ($column !== '') {
            $this->selectedFields[$class][$column] = $column;
        }
    }

    private function filterSelectedFields(array $objects, string $class): array
    {
        $allowedFields = $this->selectedFields[$class] ?? [];
        if (empty($allowedFields)) {
            return $objects;
        }

        foreach ($objects as $object) {
            foreach (get_object_vars($object) as $field => $value) {
                if (!isset($allowedFields[$field])) {
                    unset($object->{$field});
                }
            }
        }

        return $objects;
    }

    private function checkSubQuery($where)
    {
        return isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber]);
    }

    private function makeMainSubQuery($where, $mainSQL)
    {
        $this->subQuery = $mainSQL;
        $currentField = $this->currentField;
        $currentSubQueryNumber = $this->currentSubQueryNumber;
        $subQueryKey = $currentField . $currentSubQueryNumber;
        if ($subQueryKey == array_key_first($this->subQueries)) {
            if (isset($this->{$where}[$subQueryKey]['operators']) && is_array($this->{$where}[$subQueryKey]['operators'])) {
                if (!is_array($this->operators)) {
                    $this->operators = [];
                }

                foreach ($this->{$where}[$subQueryKey]['operators'] as $operatorKey => $operator) {
                    $this->operators[$operatorKey] = $operator;
                }
            }
            if ($where === 'selectQuery') {
                $alias = $this->{$where}[$subQueryKey]['alias'];
                $this->{$where}[$alias] = $mainSQL;
            } else {
                $this->{$where}[$this->currentField] = $mainSQL;
            }
            if ($where == 'where' || $where == 'whereColumn' || $where == 'orWhere') {
                $this->whereSubQuery[$currentField . $where] = 'whereSubQuery';
            }
            $this->subQueries = [];
            $this->makeDefaultSubQueryData();
        }

        if (isset($this->{$where}[$currentField . $currentSubQueryNumber])) {
            unset($this->{$where}[$currentField . $currentSubQueryNumber]);
        }
    }

    private function makeSubQuery($where)
    {
        if (
            isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber . 'unionQuery']) &&
            isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber . 'unableUnionQuery']) &&
            $this->{$where}[$this->currentField . $this->currentSubQueryNumber . 'unableUnionQuery'] == false
        ) {
            $currentField = $this->currentField;
            $currentSubQueryNumber = $this->currentSubQueryNumber;
            $this->{$where}[$this->currentField] = $this->{$where}[$this->currentField . $this->currentSubQueryNumber . 'unionQuery'];
            if ($where == 'where' || $where == 'whereColumn' || $where == 'orWhere') {
                $this->whereSubQuery[$this->currentField . $where] = 'whereSubQuery';
            }
            $this->subQueries = [];
            $this->makeDefaultSubQueryData();
            unset($this->{$where}[$currentField . $currentSubQueryNumber . 'unionQuery']);
            unset($this->{$where}[$currentField . $currentSubQueryNumber . 'unableUnionQuery']);
            if (isset($this->{$where}[$currentField . $currentSubQueryNumber])) {
                unset($this->{$where}[$currentField . $currentSubQueryNumber]);
            }
        } else {
            $mainSQL = $this->getSubQuery($where);
            $this->makeMainSubQuery($where, '(' . $mainSQL . ')');
        }
    }

    private function getSQL()
    {
        return $this->getSelect() .
            $this->getWhere() .
            $this->getWhereColumn() .
            $this->getWhereIn() .
            $this->getWhereNotIn() .
            $this->getOrWhere() .
            $this->getOrder() .
            $this->getGroupBy() .
            $this->getHaving() .
            $this->getLimit() .
            $this->getOffset();
    }

    private function deleteSQL()
    {
        $checkTrash = property_exists($this, 'deleted_at');
        $updateSQL = "UPDATE " . $this->table . " SET deleted_at='" . now() . "'";
        $deleteSQL = $checkTrash ? $updateSQL : $this->makeDelete();
        return $deleteSQL .
            $this->getWhere() .
            $this->getWhereColumn() .
            $this->getWhereIn() .
            $this->getWhereNotIn() .
            $this->getOrWhere() .
            $this->getOrder() .
            $this->getGroupBy() .
            $this->getHaving() .
            $this->getLimit() .
            $this->getOffset();
    }

    private function forceDeleteSQL()
    {
        return $this->makeDelete() .
            $this->getWhere() .
            $this->getWhereColumn() .
            $this->getWhereIn() .
            $this->getWhereNotIn() .
            $this->getOrWhere() .
            $this->getOrder() .
            $this->getGroupBy() .
            $this->getHaving() .
            $this->getLimit() .
            $this->getOffset();
    }

    private function restoreSQL()
    {
        return $this->makeRestore() .
            $this->getWhere() .
            $this->getWhereColumn() .
            $this->getWhereIn() .
            $this->getWhereNotIn() .
            $this->getOrWhere() .
            $this->getOrder() .
            $this->getGroupBy() .
            $this->getHaving() .
            $this->getLimit() .
            $this->getOffset();
    }

    private function getSubQuery($where)
    {
        $driver = $this->connectDatabase()->getAttribute(PDO::ATTR_DRIVER_NAME);
        $limit = $this->getSubQueryLimit($where);
        $offset = $this->getSubQueryOffset($where);
        $result = $this->getSubQuerySelect($where) .
            $this->getSubQueryWhere($where) .
            $this->getSubQueryWhereColumn($where) .
            $this->getSubQueryWhereIn($where) .
            $this->getSubQueryWhereNotIn($where) .
            $this->getSubQueryOrWhere($where) .
            $this->getSubQueryOrder($where) .
            $this->getSubQueryGroupBy($where) .
            $this->getSubQueryHaving($where);
        if ($driver === 'sqlsrv' && $limit !== null && $offset === null) {
            return preg_replace('/^SELECT\s+/i', "SELECT TOP " . $limit . " ", $result);
        }
        if ($driver === 'sqlsrv' && $limit !== null && $offset !== null) {
            return "SELECT * FROM (" . $result . $offset . "FETCH NEXT " . $limit . " ROWS ONLY) AS l" . $this->getSubQueryLimitNumber();
        }
        if ($limit === null) {
            return $result . $offset;
        }
        return "SELECT * FROM (" . $result . " LIMIT " . $limit . $offset . ") AS l" . $this->getSubQueryLimitNumber();
    }

    public function toArray()
    {
        $query = clone $this;
        try {
            $query->caller = getCallerInfo();
            $query->checkInstance();
            if ($query->currentSubQueryNumber !== null) {
                throw new Exception("Please use get() function in sub query to get sub query", 1);
            }
            if ($query->currentUnionNumber !== 0) {
                throw new Exception("Please use toArray function in main query", 1);
            }
            $query->boot();
            $mainSQL = $query->getQuery();
            $fields = $query->getFields();
            $stmt = $query->connectDatabase()->prepare($mainSQL);
            bindValues($stmt, $fields);
            $stmt->execute();
            $query->disableBooting();
            $query->selectedFields = [];
            $query->select = $query->table = null;
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($query->caller));
        }
    }

    public function toSQL()
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        $query->checkInstance();
        if ($query->currentSubQueryNumber !== null) {
            throw new Exception("Don't use toSQL() function in sub query", 1);
        }
        $query->boot();
        $query->toSQL = true;
        return $query;
    }

    public function addSelect(array $fields)
    {
        $query = clone $this;
        try {
            $query->caller = getCallerInfo();
            $query->checkInstance();
            if ($query->currentSubQueryNumber == null) {
                $query->checkUnionQuery();
                if ($query->select == null && $query->addSelect == true) {
                    throw new Exception("You must not use addOnlySelect function before", 1);
                }
                $query->boot();
                $query->addSelect = true;
                return $query->addingSelect($fields);
            }
            throw new Exception("You are not allow to use addSelect function in subquery", 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($query->caller));
        }
    }

    private function addingSelect(array $fields)
    {
        try {
            $query = $this;
            foreach ($fields as $select => $value) {
                if (!is_callable($value)) {
                    throw new Exception("You need to add function in array in addSelect function or addOnlySelect function.", 1);
                }
                $query->setSubQuery($select, 'selectQuery');
                $this->subQueries[$this->currentField . $this->currentSubQueryNumber] = $this->currentSubQueryNumber;
                $result = $value($query);
                if ($result instanceof self) {
                    $query = $result;
                }
                $query->makeDefaultSubQueryData();
                $query->selectedFields[$query->className][$select] = $select;
            }
            return $query;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function addOnlySelect(array $fields)
    {
        $query = clone $this;
        try {
            $query->caller = getCallerInfo();
            $query->checkInstance();
            if ($query->currentSubQueryNumber == null) {
                $query->checkUnionQuery();
                $query->boot();
                if ($query->select !== $query->table . '.*') {
                    throw new Exception("You need to use only addOnlySelect function to select the data", 1);
                }
                $query->select = null;
                $query->addSelect = true;
                return $query->addingSelect($fields);
            }
            if ($query->currentSubQueryNumber !== null) {
                $check = $query->showCurrentSubQuery();
                $query->checkSubQueryUnionQuery($check);
                $subQueryKey = $query->currentField . $query->currentSubQueryNumber;
                if ($query->{$check}[$subQueryKey]['select'] !== $query->{$check}[$subQueryKey]['table'] . '.*') {
                    throw new Exception("You need to use only addOnlySelect function to select the data", 1);
                }

                $query->{$check}[$subQueryKey]['select'] = null;
                $query->{$check}[$subQueryKey]['addSelect'] = true;
                foreach ($fields as $select => $value) {
                    if (!is_callable($value)) {
                        throw new Exception("You need to add function in array in addSelect function or addOnlySelect function.", 1);
                    }
                    $query = $query->makeSubQueryInSubQuery('selectQuery', $value, $select, $check);
                }
            }
            return $query;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($query->caller));
        }
    }

    public function paginate(int $perPage = 10)
    {
        $query = clone $this;
        try {
            $query->caller = getCallerInfo();
            $query->checkInstance();
            if ($query->currentSubQueryNumber !== null) {
                throw new Exception("You can't use paginate() function in sub queries.", 1);
            }
            if ($query->currentUnionNumber !== 0) {
                throw new Exception("Please use paginate function in main query", 1);
            }
            $query->boot();
            $paginate = new Paginate();
            $paginate->setPaginateData($perPage);

            $selectData = $query->getSelect();
            $getWhere = $query->getWhere();
            $getWhereIn = $query->getWhereIn();
            $getWhereNotIn = $query->getWhereNotIn();
            $getOrWhere = $query->getOrWhere();
            $getOrder = $query->getOrder();
            $getGroupBy = $query->getGroupBy();
            $getHaving = $query->getHaving();

            $mainSQL = $query->checkUnion() ? $query->unionQuery[$query->currentUnionNumber] :
                $selectData .
                $getWhere .
                $getWhereIn .
                $getWhereNotIn .
                $getOrWhere .
                $getGroupBy .
                $getHaving;

            $orderSQL = $getOrder !== null ? $getOrder : ' ORDER BY (SELECT NULL)';

            $pdo = $query->connectDatabase();
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

            $sql = $driver === 'sqlsrv'
                ? "SELECT * FROM (" . $mainSQL . ") AS paginate_data" .
                $orderSQL .
                " OFFSET " . $paginate->getStart() .
                " ROWS FETCH NEXT " . $perPage .
                " ROWS ONLY"
                : "SELECT * FROM (" . $mainSQL . ") AS paginate_data" .
                $orderSQL .
                " LIMIT " . $perPage .
                " OFFSET " . $paginate->getStart();
            $fields = $query->getFields();
            $stmt = $pdo->prepare($sql);
            bindValues($stmt, $fields);
            $stmt->execute();

            $countSQL = 'SELECT COUNT(*) FROM (' . $mainSQL . ') AS countData';

            $countStmt = $pdo->prepare($countSQL);
            $countStmt->execute($fields);

            $objectArray = $stmt->fetchAll(PDO::FETCH_CLASS, $query->getCalledClass());
            $class = $query->getCalledClass();
            if ($query->shouldFilterSelectedFields($class)) {
                $objectArray = $query->filterSelectedFields($objectArray, $class);
            }
            $query->selectedFields = [];
            $query->select = $query->table = null;
            $query->disableBooting();

            return $paginate->paginate(
                intval($countStmt->fetchColumn()),
                $objectArray
            );
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($query->caller));
        }
    }

    private function getJoin($sqlArray, $joinSQL)
    {
        foreach ($sqlArray as $table => $related) {
            $this->joinSQL .= $joinSQL . $table . " ON " . $related[1] . " " . $related[2] . " " . $related[0];
        }
    }

    private function getSubQueryJoin($where, $sqlArray, $joinSQL)
    {
        foreach ($sqlArray as $table => $related) {
            $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['joinSQL'] .= $joinSQL . $table . " ON " . $related[1] . " " . $related[2] . " " . $related[0];
        }
    }

    private function parseJoinParameters(array $parameters): array
    {
        $table = $parameters[0];
        $ownField = $parameters[1];
        $third = $parameters[2];
        $fourth = $parameters[3];

        if (in_array($third, databaseOperators(), true)) {
            return [$table, $ownField, $fourth, $third];
        }

        return [$table, $ownField, $third, $fourth];
    }

    private function getJoinSQL()
    {
        return $this->joinSQL;
    }

    private function getSubQueryJoinSQL($where)
    {
        return $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['joinSQL'];
    }

    private function makeSubQueryJoin(array $parameters, string $join)
    {
        [$table, $ownField, $field, $operator] = $this->parseJoinParameters($parameters);
        $sqlArray = [];
        $sqlArray[$table] = [$ownField, $field, $operator];
        $this->getSubQueryJoin($this->showCurrentSubQuery(), $sqlArray, $join);
    }

    private function makeJoin(array $parameters, string $join)
    {
        [$table, $ownField, $field, $operator] = $this->parseJoinParameters($parameters);
        $sqlArray = [];
        $sqlArray[$table] = [$ownField, $field, $operator];
        $this->getJoin($sqlArray, $join);
    }

    public function innerJoin(...$parameters)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        return $query->sqlJoin($query->normalizeParameters($parameters), ' INNER JOIN ');
    }

    public function leftJoin(...$parameters)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        return $query->sqlJoin($query->normalizeParameters($parameters), ' LEFT JOIN ');
    }

    public function rightJoin(...$parameters)
    {
        $query = clone $this;
        $query->caller = getCallerInfo();
        return $query->sqlJoin($query->normalizeParameters($parameters), ' RIGHT JOIN ');
    }

    private function sqlJoin(array $parameters, string $join)
    {
        try {
            $this->checkInstance();
            $countParameters = count($parameters);
            if (
                $countParameters == 4 &&
                is_string($parameters[0]) &&
                is_string($parameters[1]) &&
                is_string($parameters[2]) &&
                is_string($parameters[3])
            ) {
                if ($this->currentSubQueryNumber == null) {
                    $this->boot();
                    $this->makeJoin($parameters, $join);
                }
                if ($this->currentSubQueryNumber !== null) {
                    $this->makeSubQueryJoin($parameters, $join);
                }
                return $this;
            }
            throw new Exception("You need to pass correct parameters");
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function observe(ModelObserver $modelObserver)
    {
        try {
            $this->caller = getCallerInfo();
            checkObserverFunctions($modelObserver);
            if ($this->observerSubject == null) {
                $this->observerSubject = new ObserverSubject();
            }
            $className = (string) $this->getCalledClass();
            $this->observerSubject->attach($className, $modelObserver);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function getObserverSubject()
    {
        return $this->observerSubject;
    }
}
