<?php

namespace JiJiHoHoCoCo\IchiORM\Cache;

use Redis;

class RedisCache extends CacheAbstract
{

	public function __construct(Redis $redis)
	{
		$this->cachedObject = $redis;
	}


}