<?php

namespace JiJiHoHoCoCo\IchiORM\Cache;

abstract class CacheAbstract
{

	private $getData = FALSE;

	protected $cachedObject;

	public function remember(string $key, $data, int $expiredTime = NULL)
	{
		if ($this->cachedObject->get($key)) {
			return $this->get($key);
		}
		if (!$this->cachedObject->get($key)) {
			$data = $this->getData($data);
			$this->getData = TRUE;
			$this->set($key, $data, $expiredTime);
			return $data;
		}
	}

	public function set(string $key, $data, int $expiredTime = NULL)
	{
		$this->cachedObject->set($key, serialize($this->getData == FALSE ? $this->getData($data) : $data), $expiredTime);
	}

	public function get(string $key)
	{
		$resultData = $this->cachedObject->get($key);
		return unserialize($resultData);
	}

	private function getData($data)
	{
		$data = is_callable($data) ? $data() : $data;
		$this->getData = TRUE;
		return $data;
	}

	public function remove(string $key)
	{
		$this->cachedObject->delete($key);
	}

	public function getCacheObject()
	{
		return $this->cachedObject;
	}

}
