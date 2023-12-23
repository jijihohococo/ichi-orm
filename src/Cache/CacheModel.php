<?php

namespace JiJiHoHoCoCo\IchiORM\Cache;

class CacheModel{
	
	private static $redis , $memcached;

	public static function setCacheObject($cacheObject){
		
		if(self::$redis !== NULL || self::$memcached !== NULL ){
			throw new \Exception("You already set cached data", 1);
		}

		if(is_a($cacheObject, 'Redis') ){
			self::$redis = new RedisCache($cacheObject);
		}elseif(is_a($cacheObject, 'Memcached')){
			self::$memcached = new MemcachedCache($cacheObject);
		}else{
			throw new \Exception("Please set redis or memcached object", 1);
		}
	}

	public static function remember(string $key, $data, int $expiredTime = NULL){
		if(self::$redis !== NULL){
			return self::$redis->remember($key, $data, $expiredTime);
		}elseif(self::$memcached !== NULL){
			return self::$memcached->remember($key, $data, $expiredTime == NULL ? 0 : $expiredTime);
		}
		throw new \Exception("You need to set redis or memcached object firstly", 1);
	}

	public static function remove(string $key){
		if(self::$redis !== NULL){
			self::$redis->remove($key);
		}elseif(self::$memcached !== NULL){
			self::$memcached->remove($key);
		}
		throw new \Exception("You need to set redis or memcached object firstly", 1);
	}

	public static function save(string $key,$data,int $expiredTime=NULL){
		if(self::$redis !== NULL){
			self::$redis->set($key,$data,$expiredTime);
		}elseif(self::$memcached !== NULL){
			self::$memcached->set($key,$data,$expiredTime == NULL ? 0 : $expiredTime);
		}
		throw new \Exception("You need to set redis or memcached object firstly", 1);
	}

	public static function get(string $key){
		if(self::$redis !== NULL){
			return self::$redis->get($key);
		}elseif(self::$memcached !== NULL){
			return self::$memcached->get($key);
		}
		throw new \Exception("You need to set redis or memcached object firstly", 1);
	}

	public static function getRedis(){
		if(self::$redis !== NULL){
			return self::$redis->getCacheObject();
		}
		throw new \Exception("You need to set redis object firstly", 1);
	}

	public static function getMemcached(){
		if(self::$memcached !== NULL){
			return self::$memcached->getCacheObject();
		}
		throw new \Exception("You need to set memcached object firstly", 1);
	}
}