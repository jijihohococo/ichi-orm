<?php

namespace JiJiHoHoCoCo\IchiORM\Database;

use JiJiHoHoCoCo\IchiORM\Observer\ModelObserver;
use JiJiHoHoCoCo\IchiORM\QueryBuilder\QueryBuilder;

abstract class Model
{
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
       return self::getQueryBuilder()->getTable();
    }

    protected function getID()
    {
        return self::getQueryBuilder()->getID();
    }

    protected function autoIncrementId()
    {
        return self::getQueryBuilder()->autoIncrementId();
    }

    public static function withTrashed()
    {
        return self::getQueryBuilder()->withTrashed();
    }

    public static function groupBy(string $groupBy)
    {
        return self::getQueryBuilder()->groupBy($groupBy);
    }

    public static function having(string $field, string $operator, $value)
    {
        return self::getQueryBuilder()->having($field, $operator, $value);
    }

    public static function bulkUpdate(array $attributes)
    {
        self::getQueryBuilder()->bulkUpdate($attributes);
    }

    public static function insert(array $attributes)
    {
        self::getQueryBuilder()->insert($attributes);
    }

    public static function create(array $attribute)
    {
        return self::getQueryBuilder()->create($attribute);
    }

    public function update(array $attribute)
    {
        return self::getQueryBuilder()->update($attribute);
    }

    public static function find($id)
    {
        return self::getQueryBuilder()->find($id);
    }

    public static function findBy(string $field, $value)
    {
        return self::getQueryBuilder()->findBy($field, $value);
    }

    public static function delete()
    {
        self::getQueryBuilder()->delete();
    }

    public static function forceDelete()
    {
        self::getQueryBuilder()->forceDelete();
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

    public static function orderBy(string $field, string $sort = "ASC")
    {
        return self::getQueryBuilder()->orderBy($field, $sort);
    }

    public static function latest(string $field = null)
    {
        return self::getQueryBuilder()->latest($field);
    }

    public static function union(callable $value)
    {
        return self::getQueryBuilder()->union($value);
    }

    public static function unionAll(callable $value)
    {
        return self::getQueryBuilder()->unionAll($value);
    }

    public static function get()
    {
        return self::getQueryBuilder()->get();
    }

    public function __construct()
    {
        self::getQueryBuilder()->new();
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
        return self::getQueryBuilder()->refersTo($class, $field, $referField);
    }

    protected function refersMany(string $class, string $field, string $referField = 'id')
    {
        return self::getQueryBuilder()->refersMany($class, $field, $referField);
    }

    public static function observe(ModelObserver $modelObserver)
    {
        self::getQueryBuilder()->observe($modelObserver);
    }
}
