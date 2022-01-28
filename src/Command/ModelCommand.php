<?php

namespace JiJiHoHoCoCo\IchiORM\Command;

class ModelCommand{

	private $path='app/Models';
	private $observerPath='app/Observers';

	public function setPath(string $path){
		$this->path=$path;
	}

	public function getPath(){
		return $this->path;
	}

	public function setObserverPath(string $observerPath){
		$this->observerPath=$observerPath;
	}

	public function getObserverPath(){
		return $this->observerPath;
	}

	private function makeModelContent(string $defaulFolder,string $createdFile){
		return "<?php

namespace ". str_replace('/', '\\', ucfirst($defaulFolder)).";
use JiJiHoHoCoCo\IchiORM\Database\Model;

class ".$createdFile." extends Model{



}
";
	}

	private function makeObserverContent(string $defaulFolder,string $createdFile){
		
		$variable='$'.strtolower( str_replace('Observer', '', $createdFile) );

		return "<?php

namespace ".str_replace('/', '\\', ucfirst($defaulFolder)).";
use JiJiHoHoCoCo\IchiORM\Observer\ModelObserver;

class ".$createdFile." implements Observer{


	public function create(".$variable."){

	}


	public function update(".$variable."){

	}

	public function delete(".$variable."){

	}

	public function restore(".$variable."){

	}

	public function forceDelete(".$variable."){

	}


}
";
	}

	public function run(string $dir,array $argv){
		try{
			if(count($argv)==3 && ($argv[1]=='make:model' || $argv[1]=='make:observer'  ) ){
				$command=$argv[1];
				$createdOption=$command=='make:model' ? 'Model' : 'Observer';
				$defaulFolder=$command=='make:model' ? $this->getPath() : $this->getObserverPath();
				$baseDir=$dir.'/'.$defaulFolder;
				if(!is_dir($baseDir)){
					$createdFolder=NULL;
					$basefolder=explode('/', $defaulFolder);
					foreach($basefolder as $key => $folder){
						$createdFolder .= $key == 0 ? $dir . '/' . $folder : '/' . $folder;
						if(!is_dir($createdFolder)){
							mkdir($createdFolder);
						}
					}
				}
				$inputFile=explode('/',$argv[2]);
				$count=count($inputFile);
				if($count==1){
					$createdFile=$inputFile[0];
					if(!file_exists($baseDir.'/'.$createdFile.'.php')){
						fopen($baseDir.'/'.$createdFile.'.php', 'w') or die('Unable to create model');
						$createdFileContent=$command=='make:model'? $this->makeModelContent($defaulFolder,$createdFile) : $this->makeObserverContent($defaulFolder,$createdFile);
						file_put_contents($baseDir.'/'.$createdFile.'.php', $createdFileContent);
						echo $createdFile . " ".$createdOption." is created successfully".PHP_EOL;
					}elseif(file_exists($baseDir . '/'.$createdFile.'.php')){
						echo $createdFile . " ".$createdOption." is already created".PHP_EOL;
					}
				}else{
					$createdFile=$inputFile[$count-1];
					unset($inputFile[$count-1]);
					$currentFolder=NULL;
					$newCreatedFolder=NULL;
					foreach($inputFile as $key => $folder){
						$currentFolder .= $key == 0 ? $baseDir . '/' . $folder : '/' . $folder;
						$newCreatedFolder .= $key ==0 ? $defaulFolder . '/' . $folder : '/' . $folder;
						if(!is_dir($currentFolder)){
							mkdir($currentFolder);
						}
					}
					if($currentFolder!==NULL && !file_exists($currentFolder.'/'.$createdFile.'.php') ){
						fopen($currentFolder.'/'.$createdFile.'.php', 'w') or die('Unable to create model');
						$createdFileContent= $command=='make:model' ?  $this->makeModelContent($newCreatedFolder,$createdFile) : $this->makeObserverContent($newCreatedFolder,$createdFile) ;
						file_put_contents($currentFolder.'/'.$createdFile.'.php', $createdFileContent);
						echo $createdFile ." ".$createdOption." is created successfully".PHP_EOL;
					}elseif(file_exists($currentFolder.'/'.$createdFile.'.php')){
						echo $createdFile . " ".$createdOption." is already created".PHP_EOL;
					}
				}
			}
		}catch(Exception $e){
			echo "You can't created Model".PHP_EOL;
		}
	}

}