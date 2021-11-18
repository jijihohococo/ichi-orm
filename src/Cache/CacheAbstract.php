<?php

namespace JiJiHoHoCoCo\IchiORM\Cache;

abstract class CacheAbstract{

	public function remember(string $key,$data,int $expiredTime=NULL){
		if(!$this->cachedObject->get($key)){
			$data=is_callable($data) ? $data() : $data;
			$this->cachedObject->set($key,serialize($data),$expiredTime);
			return $data;
		}else{
			$resultData=$this->cachedObject->get($key);
			return unserialize($resultData);
		}
	}

}
