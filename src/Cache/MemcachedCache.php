<?php

namespace JiJiHoHoCoCo\IchiORM\Cache;

use Memcached;
class MemcachedCache extends CacheAbstract{
	
	public function __construct(Memcached $memcached){
		$this->cachedObject = $memcached;
	}


}