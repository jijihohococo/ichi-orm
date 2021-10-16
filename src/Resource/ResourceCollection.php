<?php

namespace JiJiHoHoCoCo\IchiORM\Resource;
use JiJiHoHoCoCo\IchiORM\Database\Model;
abstract class ResourceCollection{
	
	abstract protected static function getSelectedResource(Model $model);

	public static function singleCollection(Model $model){
		$newArray=[];
		foreach(static::getSelectedResource($model) as $resourceKey
			=> $newData){
			$model->{$resourceKey}=$newData;
		$newArray[$resourceKey]=$model->{$resourceKey};
	}
	
	return $newArray;
}

public static function collection($objects){
	$newArray=[];
	foreach($objects as $key => $object){
		foreach(static::getSelectedResource($object) as $resourceKey => $newData){
			$object->{$resourceKey}=$newData;
			$newArray[$key][$resourceKey]=$object->{$resourceKey};
		}
	}
	return $newArray;
}
}