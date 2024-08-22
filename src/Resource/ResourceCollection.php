<?php

namespace JiJiHoHoCoCo\IchiORM\Resource;

use JiJiHoHoCoCo\IchiORM\Database\Model;
use Exception;

abstract class ResourceCollection
{
	private static $caller = [];

	abstract protected function getSelectedResource($model);

	public function singleCollection($model)
	{
		try {
			self::$caller = getCallerInfo();
			$newArray = [];
			if (!$model instanceof Model) {
				throw new Exception("You need to extend JiJiHoHoCoCo\IchiORM\Database\Model abstract class.");
			}
			if ($model instanceof Model) {
				foreach ($this->getSelectedResource($model) as $resourceKey => $newData) {
					$model->{$resourceKey} = $newData;
					$newArray[$resourceKey] = $model->{$resourceKey};
				}
				return $newArray;
			}
		} catch (Exception $e) {
			return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
		}
	}

	public function collection($objects)
	{
		try {
			self::$caller = getCallerInfo();
			$newArray = [];
			foreach ($objects as $key => $object) {
				if (!$object instanceof Model) {
					throw new Exception("You need to extend JiJiHoHoCoCo\IchiORM\Database\Model abstract class.");
				}
				if ($object instanceof Model) {
					foreach ($this->getSelectedResource($object) as $resourceKey => $newData) {
						$object->{$resourceKey} = $newData;
						$newArray[$key][$resourceKey] = $object->{$resourceKey};
					}
				}
			}
			return $newArray;
		} catch (Exception $e) {
			return showErrorPage($e->getMessage() . showCallerInfo(self::$caller));
		}
	}
}