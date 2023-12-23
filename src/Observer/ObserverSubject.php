<?php

namespace JiJiHoHoCoCo\IchiORM\Observer;
use ReflectionMethod;


class ObserverSubject{

	private $observers = [];

	public function attach(string $className,ModelObserver $modelObserver){
		if($this->check($className)){
			throw new \Exception("Duplicate ".$className." in observers", 1);
		}
		$this->observers[$className][] = $modelObserver;
	}

	public function check(string $className){
		return isset($this->observers[$className]) && is_array($this->observers[$className]) ;
	}

	public function use(string $className,string $method,$parameters){
		foreach($this->observers[$className] as $key => $observer){
			$observerName = get_class($observer);
			if(!method_exists($observer, $method)){
				throw new \Exception("{$observerName} Class doesn't have {$method}", 1);
			}
			$reflectionMethod = new ReflectionMethod($observerName,$method);
			$reflectionMethod->invokeArgs($observer,is_array($parameters) ? $parameters : [$parameters] );
		}
	}

}