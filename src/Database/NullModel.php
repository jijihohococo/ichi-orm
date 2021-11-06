<?php

namespace JiJiHoHoCoCo\IchiORM\Database;
class NullModel{

	public function nullExecute(){
		return $this;
	}

	public function __call( string $name , array $arguments){
		if($name=='get'){
			return [];
		}
		return $this;
	}

	public function __get($attribute){
		return null;
	}

}