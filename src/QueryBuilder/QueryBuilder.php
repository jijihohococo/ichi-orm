<?php

namespace JiJiHoHoCoCo\IchiORM\QueryBuilder;

use PDO;
use Exception;
use JiJiHoHoCoCo\IchiORM\Observer\{ModelObserver, ObserverSubject};
use JiJiHoHoCoCo\IchiORM\Pagination\Paginate;
use JiJiHoHoCoCo\IchiORM\Database\NullModel;

class QueryBuilder
{
    private $limitOne = " LIMIT 1";
    private $instance;
    private $getID;
    private $table;
    private $fields;
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

    public function getTable()
    {
        return getTableName((string) $this->getCalledClass());
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
        $this->caller = getCallerInfo();
        $this->checkInstance();
        if ($this->currentSubQueryNumber == null) {
            $this->checkUnionQuery();
            $this->boot();
            $this->withTrashed = true;
        } else {
            $currentQuery = $this->showCurrentSubQuery();
            $this->checkSubQueryUnionQuery($currentQuery);
            $this->makeSubQueryTrashTrue($currentQuery);
        }
        return $this;
    }

    private function makeSubQueryTrashTrue($where)
    {
        $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['withTrashed'] = true;
    }

    private function getSelect()
    {
        $select = $this->select;
        if ($this->selectQuery !== null) {
            $i = 0;
            foreach ($this->selectQuery as $selectAs => $query) {
                $selectData = $query . ' AS ' . $selectAs;
                $select .= $i == 0 && $select == null ? $selectData : ',' . $selectData;
                $i++;
            }
        }
        return "SELECT " . $select . " FROM " . $this->table . $this->getJoinSQL();
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
        $this->select = $this->table . '.*';
        $this->addSelect = false;
        $this->withTrashed = false;
        $this->subQuery = null;
        $this->addTrashed = false;
    }

    public function groupBy(string $groupBy)
    {
        $this->caller = getCallerInfo();
        $this->checkInstance();
        if ($this->currentSubQueryNumber == null) {
            $this->checkUnionQuery();
            $this->boot();
            $this->groupBy = $this->groupByString . $groupBy;
        }
        if ($this->currentSubQueryNumber !== null) {
            $currentQuery = $this->showCurrentSubQuery();
            $this->checkSubQueryUnionQuery($currentQuery);
            $this->makeSubQueryGroupBy($currentQuery, $groupBy);
        }
        return $this;
    }

    public function having(string $field, string $operator, $value)
    {
        $this->caller = getCallerInfo();
        $this->checkInstance();
        if ($this->currentSubQueryNumber == null) {
            $this->checkUnionQuery();
            $this->boot();
            if ($this->havingNumber == null) {
                $this->havingNumber = 0;
            }
            $this->havingField[$this->havingNumber] = $field;
            $this->havingOperator[$this->havingNumber] = $operator;
            $this->havingValue[$this->havingNumber] = $value;
            $this->havingNumber++;
        }
        if ($this->currentSubQueryNumber !== null) {
            $currentQuery = $this->showCurrentSubQuery();
            $this->checkSubQueryUnionQuery($currentQuery);
            $this->makeSubQueryHaving($currentQuery, $field, $operator, $value);
        }
        return $this;
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
            $updateString = 'UPDATE ' . $this->table . ' SET ' . substr(implode('', $updatedFields), 0, -2);
            $stmt = $instance->connectDatabase()->prepare($updateString);
            $i = 0;
            foreach ($updatedBindValues as $fieldNumber => $fields) {
                foreach ($fields as $key => $value) {
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
            $instance = $this;
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
            $stmt = $pdo->prepare("INSERT INTO " . $this->table . " " . $fields . " VALUES " . $insertedValues);
            bindValues($stmt, $insertBindValues);
            $stmt->execute();
            $object = mappingModelData([
                $getID => $pdo->lastInsertId()
            ], $insertedData, $instance);
            $className = $this->className;
            $this->disableBooting();

            $this->makeObserver($className, 'create', $object);

            return $object;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    private function makeObserver(string $className, string $method, $parameters)
    {
        if ($this->observerSubject !== null && $this->observerSubject->check($className)) {
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
            $stmt = $pdo->prepare($this->getSelect() . " WHERE " . $getId . " = ? " . $this->limitOne);
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
            $stmt = $pdo->prepare($this->getSelect() . " WHERE " . $field . " = ? " . $this->limitOne);
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

    public function delete()
    {
        try {
            $this->caller = getCallerInfo();
            $this->checkInstance();
            if ($this->currentSubQueryNumber !== null) {
                throw new Exception("delete function can't be used in subquery");
            }
            if ($this->currentSubQueryNumber == null) {
                $this->boot();
                $instance = $this;
                $mainSQL = $this->deleteQuery();
                $fields = $this->getFields();
                $stmt = $instance->connectDatabase()->prepare($mainSQL);
                bindValues($stmt, $fields);
                $stmt->execute();
                $this->disableBooting();
                $this->makeObserver((string) get_class($instance), 'delete', $instance);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function forceDelete()
    {
        try {
            $this->caller = getCallerInfo();
            $this->checkInstance();
            if ($this->currentSubQueryNumber !== null) {
                throw new Exception("force delete function can't be used in subquery");
            }
            if ($this->currentSubQueryNumber == null) {
                $this->boot();
                $instance = $this;
                $mainSQL = $this->forceDeleteQuery();
                $fields = $this->getFields();
                $stmt = $instance->connectDatabase()->prepare($mainSQL);
                bindValues($stmt, $fields);
                $stmt->execute();
                $this->disableBooting();
                $this->makeObserver((string) get_class($instance), 'delete', $instance);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function restore()
    {
        try {
            $this->caller = getCallerInfo();
            $this->checkInstance();
            if ($this->currentSubQueryNumber !== null) {
                throw new Exception("restore function can't be used in subquery");
            }
            if ($this->currentSubQueryNumber == null) {
                $this->boot();
                $this->withTrashed = true;
                $instance = $this;
                $mainSQL = $this->restoreQuery();
                $fields = $this->getFields();
                $stmt = $instance->connectDatabase()->prepare($mainSQL);
                bindValues($stmt, $fields);
                $stmt->execute();
                $this->disableBooting();
                $this->makeObserver((string) get_class($instance), 'restore', $instance);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }


    public function select(array $fields)
    {
        try {
            $this->caller = getCallerInfo();
            $this->checkInstance();
            if ($this->currentSubQueryNumber == null) {
                $this->checkUnionQuery();
                // If addSelect was used after using addOnlySelect function
                if ($this !== null && $this->select == null && $this->addSelect == true) {
                    throw new Exception("You must not use addOnlySelect function before", 1);
                }

                $this->boot();
                if ($this->addSelect == false) {
                    $this->select = null;
                } else {
                    $this->select .= ',';
                }

                foreach ($fields as $key => $field) {
                    if (strpos($field, '(') == false && strpos($field, ')') == false && !isset($this->selectedFields[$this->className][$field])) {
                        $selectedField = function () use ($field) {
                            if (strpos($field, '.') !== false) {
                                $getField = explode('.', $field);
                                return $getField[1];
                            } else {
                                return $field;
                            }
                        };
                        $newSelectedField = $selectedField();
                        $this->selectedFields[$this->className][$newSelectedField] = $newSelectedField;
                    }
                    $this->select .= $key + 1 == count($fields) ? $field : $field . ',';
                }
            } else {
                $check = $this->showCurrentSubQuery();
                $this->checkSubQueryUnionQuery($check);
                $addSelectCheck = $this->checkSubQueryAddSelect($check);
                if (
                    $this->{$check}[$this->currentField . $this->currentSubQueryNumber]['select'] == null &&
                    $addSelectCheck == true
                ) {
                    throw new Exception("You must not use addOnlySelect function before", 1);
                }

                if ($addSelectCheck == true) {
                    $this->addCommaToSubQuerySelect($check);
                }
                if ($addSelectCheck == false) {
                    $this->makeNullToSubQuerySelect($check);
                }

                foreach ($fields as $key => $field) {
                    $this->{$check}[$this->currentField . $this->currentSubQueryNumber]['select'] .= $key + 1 == count($fields) ? $field : $field . ',';
                }
            }
            return $this;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
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
        $this->caller = getCallerInfo();
        $this->checkInstance();
        if ($this->currentSubQueryNumber == null) {
            $this->checkUnionQuery();
            $this->boot();
            $this->limit = ' LIMIT ' . $limit;
        }
        if ($this->currentSubQueryNumber !== null) {
            $check = $this->showCurrentSubQuery();
            $this->checkSubQueryUnionQuery($check);
            $this->{$check}[$this->currentField . $this->currentSubQueryNumber]['limit'] = $limit;
            $this->subQueryLimitNumber++;
        }
        return $this;
    }

    public function offset(int $offset)
    {
        $this->caller = getCallerInfo();
        $this->checkInstance();
        if ($this->currentSubQueryNumber == null) {
            $this->checkUnionQuery();
            $this->boot();
            $this->offset = ' OFFSET ' . $offset;
        }
        if ($this->currentSubQueryNumber !== null) {
            $check = $this->showCurrentSubQuery();
            $this->checkSubQueryUnionQuery($check);
            $this->{$check}[$this->currentField . $this->currentSubQueryNumber]['offset'] = $offset;
        }
        return $this;
    }

    private function makeSubQueryAttributes($previousField = null)
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
            'selectQuery' => null
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
        $this->currentField = $field;
        $this->{$where}[$this->currentField . $this->currentSubQueryNumber] = $this->makeSubQueryAttributes($previousField);
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


    public function where(array $parameters)
    {
        $this->caller = getCallerInfo();
        $this->makeWhereQuery($parameters, 'where');
        return $this;
    }

    private function makeSubQueryInSubQuery($whereSelect, $value, $field, $check)
    {
        // if there is sub query function in sub query //
        $previousField = $this->currentField;
        $previousSubQueryNumber = $this->currentSubQueryNumber;
        $query = $this;
        $query->setSubQuery($field, $check);
        $this->subQueries[$field . $this->currentSubQueryNumber] = $this->currentSubQueryNumber;
        $value($query);

        if ($whereSelect !== 'selectQuery') {
            // put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//
            $this->{$check}[$previousField . $previousSubQueryNumber][$whereSelect] = $this->subQuery;
            // put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//
        }

        if ($whereSelect == 'selectQuery') {
            // put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//
            $this->{$check}[$previousField . $previousSubQueryNumber][$whereSelect][$field] = $this->subQuery;
            // put the subquery result in the "where" OR "whereColumn" OR "whereIn" OR "whereNotIn" OR "orWhere" array of previous subquery//
        }


        $this->currentField = $previousField;
        $this->currentSubQueryNumber = $previousSubQueryNumber;
    }

    public function from(string $className)
    {

        try {
            $this->caller = getCallerInfo();

            checkClass($className);

            $this->checkInstance();
            if ($this->currentSubQueryNumber !== null) {
                $currentQuery = $this->showCurrentSubQuery();
                $this->checkSubQueryUnionQuery($currentQuery);
                $this->addTableToSubQuery($currentQuery, $className);
                return $this;
            }
            throw new Exception("You can use 'from' function in only sub queries", 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
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
            $table = $obj->getTable();
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

    public function whereColumn(array $parameters)
    {
        $this->caller = getCallerInfo();
        $this->makeWhereQuery($parameters, 'whereColumn');
        return $this;
    }

    public function orWhere(array $parameters)
    {
        $this->caller = getCallerInfo();
        $this->makeWhereQuery($parameters, 'orWhere');
        return $this;
    }
    private function makeWhereQuery(array $parameters, $where)
    {
        try {
            $this->checkInstance();
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


                if (!is_callable($value) && $this->currentSubQueryNumber == null) {
                    $this->checkUnionQuery();
                    $this->boot();
                    $this->{$where}[$field] = $value;
                    $this->operators[$field . $where] = makeOperator($operator);
                    if ($value !== null && $where !== 'whereColumn') {
                        $this->fields[] = $value;
                    }
                }
                if (is_callable($value) && $this->currentSubQueryNumber == null) {
                    $this->checkUnionQuery();
                    $this->boot();
                    $this->operators[$field . $where] = makeOperator($operator);
                    $query = $this;
                    $query->setSubQuery($field, $where);
                    $this->subQueries[$field . $this->currentSubQueryNumber] = $this->currentSubQueryNumber;
                    $value($query);
                    $this->makeDefaultSubQueryData();
                }
                if (!is_callable($value) && $this->currentSubQueryNumber !== null) {
                    $currentQuery = $this->showCurrentSubQuery();
                    $this->checkSubQueryUnionQuery($currentQuery);
                    $this->setSubWhere($currentQuery, $value, $field, $operator, $where);
                    if ($value !== null && $where !== 'whereColumn') {
                        $this->fields[] = $value;
                    }
                }
                if (is_callable($value) && $this->currentSubQueryNumber !== null) {
                    $check = $this->showCurrentSubQuery();
                    $this->checkSubQueryUnionQuery($check);
                    $this->{$check}[$this->currentField . $this->currentSubQueryNumber]['operators'][$field . $where] = makeOperator($operator);
                    $this->makeSubQueryInSubQuery($where, $value, $field, $check);
                }
            } else {
                throw new Exception("Invalid Argument Parameter", 1);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    private function makeInQuery($whereIn, $field, $value)
    {

        try {
            $this->checkInstance();

            if (!is_array($value) && !is_callable($value) && $value !== null) {
                throw new Exception("You can add only array values or sub query in {$whereIn} function", 1);
            }

            if ((is_array($value) || $value == null) && $this->currentSubQueryNumber == null) {
                $this->checkUnionQuery();
                $this->boot();
                $this->{$whereIn}[$field] = $value;
                if ($value !== null) {
                    $this->fields[] = $value;
                }
            }
            if (is_callable($value) && $this->currentSubQueryNumber == null) {
                $this->checkUnionQuery();
                $this->boot();
                $query = $this;
                $query->setSubQuery($field, $whereIn, $field);
                $this->subQueries[$field . $this->currentSubQueryNumber] = $this->currentSubQueryNumber;
                $value($query);
                $this->makeDefaultSubQueryData();
            }
            if ((is_array($value) || $value == null) && $this->currentSubQueryNumber !== null) {
                $currentQuery = $this->showCurrentSubQuery();
                $this->checkSubQueryUnionQuery($currentQuery);
                $this->setSubWhereIn($currentQuery, $value, $field, $whereIn);
                if ($value !== null) {
                    $this->fields[] = $value;
                }
            }
            if (is_callable($value) && $this->currentSubQueryNumber !== null) {
                $currentQuery = $this->showCurrentSubQuery();
                $this->checkSubQueryUnionQuery($currentQuery);
                $this->makeSubQueryInSubQuery($whereIn, $value, $field, $currentQuery);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function whereIn(string $field, $value)
    {
        $this->caller = getCallerInfo();
        $this->makeInQuery('whereIn', $field, $value);
        return $this;
    }

    public function whereNotIn(string $field, $value)
    {
        $this->caller = getCallerInfo();
        $this->makeInQuery('whereNotIn', $field, $value);
        return $this;
    }

    private function getLimit()
    {
        return $this->limit;
    }

    private function getOffset()
    {
        return $this->offset;
    }
    private function getSubQueryLimit($where)
    {
        if (isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber])) {
            $limit = $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['limit'];
            return $limit == null ? $limit : ' LIMIT ' . $limit;
        }
    }

    private function getSubQueryOffset($where)
    {
        if (isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber])) {
            $offset = $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['offset'];
            return $offset == null ? $offset : ' OFFSET ' . $offset;
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
                    if ($value == null) {
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

            foreach ($this->where as $key => $value) {
                if (isset($this->whereSubQuery[$key . 'where'])) {
                    // WHERE SUBQUERY //

                    $string .= $i == 0 ? $key . $this->operators[$key . 'where'] . $value : ' AND ' . $key . $this->operators[$key . 'where'] . $value;
                } else {
                    // WHERE //
                    if ($value == null) {
                        $string .= $i == 0 ? $key . $this->operators[$key . 'where'] . 'NULL' : ' AND ' . $key . $this->operators[$key . 'where'] . 'NULL';
                    } else {
                        $string .= $i == 0 ?
                            $key . $this->operators[$key . 'where'] . '?' : ' AND ' . $key . $this->operators[$key . 'where'] . '?';
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
            foreach ($this->whereColumn as $key => $value) {
                $result = $key . $this->operators[$key . 'whereColumn'] . $value;
                $string .= $i == 0 && $this->where == null && $this->addTrashed == false ? ' WHERE ' . $result : ' AND ' . $result;
            }
        }
        return $string;
    }

    private function getSubQueryWhereColumn($where)
    {
        $string = null;
        $i = 0;
        if (isset($this->{$where}[$this->currentField . $this->currentSubQueryNumber])) {
            $current = $this->{$where}[$this->currentField . $this->currentSubQueryNumber];
            if ($current['whereColumn'] !== null && is_array($current['whereColumn'])) {
                foreach ($current['whereColumn'] as $key => $value) {
                    $result = $key . $current['operators'][$key . 'whereColumn'] . $value;
                    $string .= $i == 0 && $current['where'] == null && $current['addTrashed'] == false ? ' WHERE ' . $result : ' AND ' . $result;
                    $i++;
                }
            }
            if ($current['whereColumn'] !== null && !is_array($current['whereColumn'])) {
                $currentField = getCurrentField($this->subQueries, $this->currentField, $this->currentSubQueryNumber);
                $result = $currentField . $current['operators'][$currentField . 'whereColumn'] . ' (' . $current['whereColumn'] . ') ';
                $string .= $current['where'] == null && $current['addTrashed'] == false ? ' WHERE ' . $result : ' AND ' . $result;
            }
        }
        return $string;
    }

    private function getOrWhere()
    {
        $string = null;
        if ($this->orWhere !== null) {
            foreach ($this->orWhere as $key => $value) {
                if (isset($this->whereSubQuery[$key . 'orWhere'])) {
                    // OR WHERE SUBQUERY //
                    $string .= ' OR ' . $key . $this->operators[$key . 'orWhere'] . $value;
                } else {
                    // OR WHERE QUERY //

                    $string .= ' OR ' . $key . $this->operators[$key . 'orWhere'] . '?';
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

    private function getWhereIn()
    {
        $string = null;
        $i = 0;
        if ($this->whereIn !== null) {
            foreach ($this->whereIn as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $in = addArray($value);
                    $string .= $i == 0 && $this->where == null && $this->whereColumn == null && $this->addTrashed == false ? ' WHERE ' . $key . ' IN (' . $in . ') ' : ' AND ' . $key . ' IN (' . $in . ') ';
                } elseif ($value !== null && !is_array($value)) {
                    $string .= $i == 0 && $this->where == null && $this->whereColumn == null && $this->addTrashed == false ? ' WHERE ' . $key . ' IN ' . $value : ' AND ' . $key . ' IN ' . $value;
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
                        $string .= $i == 0 && $current['where'] == null && $current['whereColumn'] == null && $current['addTrashed'] == false ? ' WHERE ' . $key . ' IN (' . $in . ') ' : ' AND ' . $key . ' IN (' . $in . ') ';
                    } else {
                        $string .= $i == 0 && $current['where'] == null && $current['whereColumn'] == null && $current['addTrashed'] == false ? $this->whereZero : $this->andZero;
                    }
                    $i++;
                }
            } elseif ($current['whereIn'] !== null && !is_array($current['whereIn'])) {
                $currentField = getCurrentField($this->subQueries, $this->currentField, $this->currentSubQueryNumber);

                $string .= $current['where'] == null && $current['whereColumn'] == null && $current['addTrashed'] == false ? ' WHERE ' . $currentField . ' IN (' . $current['whereIn'] . ')' : ' AND ' . $currentField . ' IN (' . $current['whereIn'] . ')';
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
                if (is_array($value) && !empty($value)) {
                    $in = addArray($value);
                    $string .= $i == 0 && $this->where == null && $this->whereColumn == null && $this->whereIn == null && $this->addTrashed == false ?
                        ' WHERE ' . $key . ' NOT IN (' . $in . ') ' : ' AND ' . $key . ' NOT IN (' . $in . ') ';
                } elseif ($value !== null && !is_array($value)) {
                    $string .= $i == 0 && $this->where == null && $this->whereColumn == null && $this->whereIn == null && $this->addTrashed == false ? ' WHERE ' . $key . ' NOT IN ' . $value : ' AND ' . $key . ' NOT IN ' . $value;
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
        $this->caller = getCallerInfo();
        $this->checkInstance();
        if ($this->currentSubQueryNumber == null) {
            $this->checkUnionQuery();
            $this->boot();
            $this->order = " ORDER BY " . $field . " " . $sort;
        }
        if ($this->currentSubQueryNumber !== null) {
            $currentQuery = $this->showCurrentSubQuery();
            $this->checkSubQueryUnionQuery($currentQuery);
            $this->makeSubQueryOrderBy($currentQuery, $field, $sort);
        }
        return $this;
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
        $this->caller = getCallerInfo();
        $this->checkInstance();
        if ($this->currentSubQueryNumber == null) {
            $this->checkUnionQuery();
            $this->boot();
            $field = $field == null ? $this->getID() : $field;
            $this->order = " ORDER BY " . $this->table . '.' . $field . " DESC";
        }
        if ($this->currentSubQueryNumber !== null) {
            $currentQuery = $this->showCurrentSubQuery();
            $this->checkSubQueryUnionQuery($currentQuery);
            $this->makeSubQueryOrderBy($currentQuery, $field, " DESC");
        }
        return $this;
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

        $this->useUnionQuery = [0 => true];
        $this->unionQuery = [0 => null];
        $this->unionNumber = $this->currentUnionNumber = 0;
        $this->unableUnionQuery = [];
    }

    public function union(callable $value)
    {
        $this->caller = getCallerInfo();
        return $this->makeUnionQuery($value, ' UNION ');
    }

    public function unionAll(callable $value)
    {
        $this->caller = getCallerInfo();
        return $this->makeUnionQuery($value, ' UNION ALL ');
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

    private function makeUnionQuery($value, $union)
    {
        try {
            if ($this->currentSubQueryNumber == null) {
                $previousQuery = $this->getQuery();
                $this->disableForSQL();
                $uNumber = $this->currentUnionNumber;
                $this->useUnionQuery[$uNumber] = false;
                $this->unionNumber++;
                $newUnionQuery = $value();
                $this->useUnionQuery[$uNumber] = true;
                $this->currentUnionNumber = $uNumber;
                $this->unableUnionQuery[$uNumber] = true;
                $this->boot();
                $this->unionQuery[$uNumber] = $previousQuery . $union . $newUnionQuery;
                return $this;
            }
            if ($this->currentSubQueryNumber !== null) {
                $currentQuery = $this->showCurrentSubQuery();
                $currentField = $this->currentField;
                $currentSubQueryNumber = $this->currentSubQueryNumber;
                if (
                    isset($this->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery']) &&
                    $this->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery'] == true
                ) {
                    throw new Exception("You are not allowed to use " . $union, 1);
                }

                $previousUnionQuery = isset($this->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unionQuery']) ?
                    $this->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unionQuery'] : null;
                $previousField = $this->{$currentQuery}[$currentField . $currentSubQueryNumber];
                if ($previousUnionQuery == null) {
                    $this->makeSubQuery($currentQuery);
                    $previousQuery = $this->{$currentQuery}[$currentField];
                    $query = $this;
                    $query->setSubQuery($currentField, $currentQuery, false);
                    $this->subQueries[$currentField . $currentSubQueryNumber] = $currentSubQueryNumber;
                    $this->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery'] = true;
                    $value($query);
                    $this->currentField = $currentField;
                    $this->currentSubQueryNumber = $currentSubQueryNumber;
                    $this->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unionQuery'] = substr($previousQuery, 0, -1) . $union . $this->{$currentQuery}[$currentField] . ')';
                    $this->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery'] = false;
                    $this->{$currentQuery}[$currentField . $currentSubQueryNumber] = $this->makeSubQueryAttributes($previousField);
                }
                if ($previousUnionQuery !== null) {
                    $this->{$currentQuery}[$this->currentField . $this->currentSubQueryNumber . 'unableUnionQuery'] = true;
                    unset($this->{$currentQuery}[$this->currentField]);
                    $this->subQueries[$currentField . $currentSubQueryNumber] = $currentSubQueryNumber;
                    $query = $this;
                    $value($query);
                    $this->currentField = $currentField;
                    $this->currentSubQueryNumber = $currentSubQueryNumber;
                    $this->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unionQuery'] = substr($previousUnionQuery, 0, -1) . $union . $this->{$currentQuery}[$currentField] . ')';
                    $this->{$currentQuery}[$currentField . $currentSubQueryNumber . 'unableUnionQuery'] = false;
                    $this->{$currentQuery}[$currentField . $currentSubQueryNumber] = $this->makeSubQueryAttributes($previousField);
                }
            }
            return $this;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function get()
    {
        try {
            $this->caller = getCallerInfo();
            $this->checkInstance();
            if ($this->currentSubQueryNumber == null) {
                $this->boot();
                $mainSQL = $this->getQuery();
                if ($this->toSQL == true) {
                    $this->disableForSQL();
                    return $mainSQL;
                }
                $class = $this->getCalledClass();
                $fields = $this->getFields();
                $stmt = $this->connectDatabase()->prepare($mainSQL);
                bindValues($stmt, $fields);
                $stmt->execute();
                $this->disableBooting();
                $object = $stmt->fetchAll(PDO::FETCH_CLASS, $class);
                $this->selectedFields = [];
                $this->select = $this->table = null;
                if ($this->unionQuery !== null) {
                    $this->unionQuery = null;
                }
                return $object;
            }
            if ($this->currentSubQueryNumber !== null) {
                $this->makeSubQuery($this->showCurrentSubQuery());
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function new()
    {
        $class = $this->getCalledClass();
        if (!empty($this->selectedFields) && isset($this->selectedFields[$class]) && $this->select !== $this->table . '.*' && $this->select !== null) {
            // FOR ADD SELECT WITH OR WITHOUT SELECT
            foreach (get_object_vars($this) as $key => $value) {
                if (!isset($this->selectedFields[$class][$key])) {
                    unset($this->{$key});
                }
            }
        }
        if ($this->select == null && !empty($this->selectedFields) && isset($this->selectedFields[$class])) {
            // FOR ADD ONLY SELECT
            foreach (get_object_vars($this) as $key => $value) {
                if (isset($this->selectedFields[$class][$key])) {
                    $this->{$key} = $value;
                } else {
                    unset($this->{$key});
                }
            }
        }
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
        if ($this->currentField . $this->currentSubQueryNumber == array_key_first($this->subQueries)) {
            $this->{$where}[$this->currentField] = $mainSQL;
            if ($where == 'where' || $where == 'whereColumn' || $where == 'orWhere') {
                $this->whereSubQuery[$this->currentField . $where] = 'whereSubQuery';
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
        return $limit == null ? $result . $offset : "SELECT * FROM (" . $result . $limit . $offset . ") AS l" . $this->getSubQueryLimitNumber();
    }

    public function toArray()
    {
        try {
            $this->caller = getCallerInfo();
            $this->checkInstance();
            if ($this->currentSubQueryNumber !== null) {
                throw new Exception("Please use get() function in sub query to get sub query", 1);
            }
            if ($this->currentUnionNumber !== 0) {
                throw new Exception("Please use toArray function in main query", 1);
            }
            $this->boot();
            $mainSQL = $this->getQuery();
            $fields = $this->getFields();
            $stmt = $this->connectDatabase()->prepare($mainSQL);
            bindValues($stmt, $fields);
            $stmt->execute();
            $this->disableBooting();
            $this->selectedFields = [];
            $this->select = $this->table = null;
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function toSQL()
    {
        $this->caller = getCallerInfo();
        $this->checkInstance();
        if ($this->currentSubQueryNumber !== null) {
            throw new Exception("Don't use toSQL() function in sub query", 1);
        }
        $this->boot();
        $this->toSQL = true;
        return $this;
    }

    public function addSelect(array $fields)
    {
        try {
            $this->caller = getCallerInfo();
            $this->checkInstance();
            if ($this->currentSubQueryNumber == null) {
                $this->checkUnionQuery();
                // If addSelect was used after using addOnlySelect function
                if ($this->select == null && $this->addSelect == true) {
                    throw new Exception("You must not use addOnlySelect function before", 1);
                }
                $this->boot();
                $this->addSelect = true;
                return $this->addingSelect($fields);
            }
            throw new Exception("You are not allow to use addSelect function in subquery", 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
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
                $this->subQueries[$select . $this->currentSubQueryNumber] = $this->currentSubQueryNumber;
                $value($query);
                $this->makeDefaultSubQueryData();
                $this->selectedFields[$this->className][$select] = $select;
            }
            return $this;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function addOnlySelect(array $fields)
    {
        try {
            $this->caller = getCallerInfo();
            $this->checkInstance();
            if ($this->currentSubQueryNumber == null) {
                $this->checkUnionQuery();
                // If addOnlySelect function was used after using select or addSelect function //
                if ($this->select !== $this->table . '.*') {
                    throw new Exception("You need to use only addOnlySelect function to select the data", 1);
                }
                $this->boot();
                $this->select = null;
                $this->addSelect = true;
                return $this->addingSelect($fields);
            }
            if ($this->currentSubQueryNumber !== null) {
                $check = $this->showCurrentSubQuery();
                $this->checkSubQueryUnionQuery($check);
                // If addOnlySelect function was used after using select or addSelect function //
                if ($this->{$check}[$this->currentField . $this->currentSubQueryNumber]['select'] !== $this->{$check}[$this->currentField . $this->currentSubQueryNumber]['table'] . '.*') {
                    throw new Exception("You need to use only addOnlySelect function to select the data", 1);
                }


                $this->{$check}[$this->currentField . $this->currentSubQueryNumber]['select'] = null;
                $this->{$check}[$this->currentField . $this->currentSubQueryNumber]['addSelect'] = true;
                foreach ($fields as $select => $value) {
                    if (!is_callable($value)) {
                        throw new Exception("You need to add function in array in addSelect function or addOnlySelect function.", 1);
                    }
                    $this->makeSubQueryInSubQuery('selectQuery', $value, $select, $check);
                }
            }
            return $this;
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function paginate(int $per_page = 10)
    {
        try {
            $this->caller = getCallerInfo();
            $this->checkInstance();
            if ($this->currentSubQueryNumber !== null) {
                throw new Exception("You can't use paginate() function in sub queries.", 1);
            }
            if ($this->currentUnionNumber !== 0) {
                throw new Exception("Please use paginate function in main query", 1);
            }
            $this->boot();
            $paginate = new Paginate();
            $paginate->setPaginateData($per_page);

            $selectData = $this->getSelect();
            $getWhere = $this->getWhere();
            $getWhereIn = $this->getWhereIn();
            $getWhereNotIn = $this->getWhereNotIn();
            $getOrWhere = $this->getOrWhere();
            $getOrder = $this->getOrder();
            $getGroupBy = $this->getGroupBy();
            $getHaving = $this->getHaving();

            $mainSQL = $this->checkUnion() ? $this->unionQuery[$this->currentUnionNumber] :
                $selectData .
                $getWhere .
                $getWhereIn .
                $getWhereNotIn .
                $getOrWhere .
                $getOrder .
                $getGroupBy .
                $getHaving;

            $sql = "SELECT * FROM (" . $mainSQL . ") AS paginate_data LIMIT " . $per_page . " OFFSET " . $paginate->getStart();

            $fields = $this->getFields();
            $pdo = $this->connectDatabase();
            $stmt = $pdo->prepare($sql);
            bindValues($stmt, $fields);
            $stmt->execute();

            $countSQL = 'SELECT COUNT(*) FROM (' . $mainSQL . ') AS countData';

            $countStmt = $pdo->prepare($countSQL);
            $countStmt->execute($fields);

            $objectArray = $stmt->fetchAll(PDO::FETCH_CLASS, $this->getCalledClass());
            $this->selectedFields = [];
            $this->select = $this->table = null;
            $this->disableBooting();

            return $paginate->paginate(
                intval($countStmt->fetchColumn()),
                $objectArray
            );
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    private function getJoin($sqlArray, $joinSQL)
    {
        foreach ($sqlArray as $table => $related) {
            $this->joinSQL .= $joinSQL . $table . " ON " . $related[1] . $related[2] . $related[0];
        }
    }

    private function getSubQueryJoin($where, $sqlArray, $joinSQL)
    {
        foreach ($sqlArray as $table => $related) {
            $this->{$where}[$this->currentField . $this->currentSubQueryNumber]['joinSQL'] .= $joinSQL . $table . " ON " . $related[1] . $related[2] . $related[0];
        }
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
        $table = $parameters[0];
        $ownField = $parameters[1];
        $field = $parameters[2];
        $operator = $parameters[3];
        $sqlArray = [];
        $sqlArray[$table] = [$ownField, $field, $operator];
        $this->getSubQueryJoin($this->showCurrentSubQuery(), $sqlArray, $join);
    }

    private function makeJoin(array $parameters, string $join)
    {
        $table = $parameters[0];
        $ownField = $parameters[1];
        $field = $parameters[2];
        $operator = $parameters[3];
        $sqlArray = [];
        $sqlArray[$table] = [$ownField, $field, $operator];
        $this->getJoin($sqlArray, $join);
    }

    public function innerJoin(array $parameters)
    {
        $this->caller = getCallerInfo();
        return $this->sqlJoin($parameters, ' INNER JOIN ');
    }

    public function leftJoin(array $parameters)
    {
        $this->caller = getCallerInfo();
        return $this->sqlJoin($parameters, ' LEFT JOIN ');
    }

    public function rightJoin(array $parameters)
    {
        $this->caller = getCallerInfo();
        return $this->sqlJoin($parameters, ' RIGHT JOIN ');
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

    public function refersTo(string $class, string $field, string $referField = 'id')
    {
        try {
            checkClass($class);
            if (isset($this->{$field})) {
                return $class::findBy($referField, $this->{$field});
            }
            throw new Exception($field . ' is not available', 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }

    public function refersMany(string $class, string $field, string $referField = 'id')
    {
        try {
            checkClass($class);
            if (isset($this->{$referField})) {
                $classObject = new $class();
                return $class::where($classObject->getTable() . '.' . $field, $this->{$referField});
            }
            throw new Exception($referField . ' is not available', 1);
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
            $className = (string) get_called_class();
            $this->observerSubject->attach($className, $modelObserver);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo($this->caller));
        }
    }
}
