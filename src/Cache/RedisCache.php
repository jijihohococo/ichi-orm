<?php

namespace JiJiHoHoCoCo\IchiORM\Cache;

use Redis;
class RedisCache extends CacheAbstract{

	protected $cachedObject;
	
	public function __construct(Redis $redis){
		$this->cachedObject=$redis;
	}


}