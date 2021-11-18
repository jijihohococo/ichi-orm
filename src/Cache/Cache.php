<?php

namespace JiJiHoHoCoCo\IchiORM\Cache;

use Redis,Memcached;
class Cache{
	
	private static $redis , $memcached;

	public static function setCacheObect($cacheObject){
		
		if(self::$redis!==NULL || self::$memcached!==NULL ){
			throw new \Exception("You already set cached data", 1);
		}

		if($cacheObject instanceof Redis){
			self::$redis=new RedisCache($cacheObject);
		}elseif($cacheObject instanceof Memcached){
			self::$memcached=new MemcachedCache($cacheObject);
		}else{
			throw new \Exception("Error Processing Request", 1);
		}
	}

	public static function remember(string $key,$data,int $expiredTime=NULL){
		if(self::$redis!==NULL){
			self::$redis->remember($key,$data,$expiredTime);
		}
		if(self::$memcached!==NULL){
			self::$memcached->remember($key,$data,$expiredTime==NULL ? 0 : $expiredTime);
		}
	}
}