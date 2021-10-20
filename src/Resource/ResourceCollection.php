<?php

namespace JiJiHoHoCoCo\IchiORM\Resource;
use JiJiHoHoCoCo\IchiORM\Database\Model;
abstract class ResourceCollection{
	
	abstract protected function getSelectedResource(Model $model);

	public function singleCollection(Model $model){
		$newArray=[];
		foreach($this->getSelectedResource($model) as $resourceKey
			=> $newData){
			$model->{$resourceKey}=$newData;
		$newArray[$resourceKey]=$model->{$resourceKey};
	}
	
	return $newArray;
}

public function collection($objects){
	$newArray=[];
	foreach($objects as $key => $object){
		foreach($this->getSelectedResource($object) as $resourceKey => $newData){
			$object->{$resourceKey}=$newData;
			$newArray[$key][$resourceKey]=$object->{$resourceKey};
		}
	}
	return $newArray;
}
}