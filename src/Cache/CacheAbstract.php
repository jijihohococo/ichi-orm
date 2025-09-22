<?php

namespace JiJiHoHoCoCo\IchiORM\Cache;

abstract class CacheAbstract
{
    private $getData = false;

    protected $cachedObject;

    public function remember(string $key, $data, int $expiredTime = null)
    {
        if ($this->cachedObject->get($key)) {
            return $this->get($key);
        }
        if (!$this->cachedObject->get($key)) {
            $data = $this->getData($data);
            $this->getData = true;
            $this->set($key, $data, $expiredTime);
            return $data;
        }
    }

    public function set(string $key, $data, int $expiredTime = null)
    {
        $serializedData = serialize($this->getData == false ? $this->getData($data) : $data);
        $this->cachedObject->set($key, $serializedData, $expiredTime);
    }

    public function get(string $key)
    {
        $resultData = $this->cachedObject->get($key);
        return unserialize($resultData);
    }

    private function getData($data)
    {
        $data = is_callable($data) ? $data() : $data;
        $this->getData = true;
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
