<?php

namespace JiJiHoHoCoCo\IchiORM\Cache;

abstract class CacheAbstract{

	private $getData=FALSE;

	public function remember(string $key,$data,int $expiredTime=NULL){
		if(!$this->cachedObject->get($key)){
			$data=$this->getData($data);
			$this->getData=TRUE;
			$this->set($key,$data,$expiredTime);
			return $data;
		}else{
			$resultData=$this->cachedObject->get($key);
			return unserialize($resultData);
		}
	}

	public function set(string $key,$data,int $expiredTime=NULL){
		$this->cachedObject->set($key,serialize( $this->getData==FALSE ? $this->getData($data) : $data ),$expiredTime);
	}

	private function getData($data){
		$data=is_callable($data) ? $data() : $data;
		return $data;
	}

	public function remove(string $key){
		$this->cachedObject->delete($key);
	}

}
