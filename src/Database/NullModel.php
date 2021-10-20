<?php

namespace JiJiHoHoCoCo\IchiORM\Database;
class NullModel extends Model{

	public function nullExecute(){
		return $this;
	}

	public function __call( string $name , array $arguments){
		return $this;
	}

	public function __get($attribute){
		return null;
	}

}