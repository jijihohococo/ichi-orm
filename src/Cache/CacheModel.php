<?php

namespace JiJiHoHoCoCo\IchiORM\Cache;

use Exception;

class CacheModel
{
    private static $redis, $memcached;
    private static $caller = [];

    public static function setCacheObject($cacheObject)
    {

        try {
            self::$caller = getCallerInfo();

            if (self::$redis !== null || self::$memcached !== null) {
                throw new Exception("You already set cached data", 1);
            }
            if (!is_a($cacheObject, 'Redis') && !is_a($cacheObject, 'Memcached')) {
                throw new Exception("Please set redis or memcached object", 1);
            }
            if (is_a($cacheObject, 'Redis')) {
                self::$redis = new RedisCache($cacheObject);
            }
            if (is_a($cacheObject, 'Memcached')) {
                self::$memcached = new MemcachedCache($cacheObject);
            }
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function remember(string $key, $data, int $expiredTime = null)
    {
        try {
            self::$caller = getCallerInfo();
            if (self::$redis !== null) {
                return self::$redis->remember($key, $data, $expiredTime);
            }
            if (self::$memcached !== null) {
                return self::$memcached->remember($key, $data, $expiredTime == null ? 0 : $expiredTime);
            }
            throw new Exception("You need to set redis or memcached object firstly", 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function remove(string $key)
    {
        try {
            self::$caller = getCallerInfo();
            if (self::$redis !== null) {
                self::$redis->remove($key);
            }
            if (self::$memcached !== null) {
                self::$memcached->remove($key);
            }
            throw new Exception("You need to set redis or memcached object firstly", 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function save(string $key, $data, int $expiredTime = null)
    {
        try {
            self::$caller = getCallerInfo();
            if (self::$redis !== null) {
                self::$redis->set($key, $data, $expiredTime);
            }
            if (self::$memcached !== null) {
                self::$memcached->set($key, $data, $expiredTime == null ? 0 : $expiredTime);
            }
            throw new Exception("You need to set redis or memcached object firstly", 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function get(string $key)
    {
        try {
            self::$caller = getCallerInfo();
            if (self::$redis !== null) {
                return self::$redis->get($key);
            }
            if (self::$memcached !== null) {
                return self::$memcached->get($key);
            }
            throw new Exception("You need to set redis or memcached object firstly", 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function getRedis()
    {
        try {
            self::$caller = getCallerInfo();
            if (self::$redis !== null) {
                return self::$redis->getCacheObject();
            }
            throw new Exception("You need to set redis object firstly", 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }

    public static function getMemcached()
    {
        try {
            self::$caller = getCallerInfo();
            if (self::$memcached !== null) {
                return self::$memcached->getCacheObject();
            }
            throw new Exception("You need to set memcached object firstly", 1);
        } catch (Exception $e) {
            return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
        }
    }
}
