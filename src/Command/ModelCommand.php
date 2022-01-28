<?php

namespace JiJiHoHoCoCo\IchiORM\Command;

class ModelCommand{

	private $path='app/Models';

	public function setPath(string $path){
		$this->path=$path;
	}

	public function getPath(){
		return $this->path;
	}

	private function makeContent(string $defaulFolder,string $createdFile){
		return "<?php

namespace ". str_replace('/', '\\', ucfirst($defaulFolder)).";
use JiJiHoHoCoCo\IchiORM\Database\Model;

class ".$createdFile." extends Model{



}
";
	}

	public function run(string $dir,array $argv){
		try{
			if(count($argv)==3 && $argv[1]=='make:model' ){
				$defaulFolder=$this->getPath();
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
						$createdFileContent=$this->makeContent($defaulFolder,$createdFile);
						file_put_contents($baseDir.'/'.$createdFile.'.php', $createdFileContent);
						echo $createdFile . " Model is created successfully".PHP_EOL;
					}elseif(file_exists($baseDir . '/'.$createdFile.'.php')){
						echo $createdFile . " Model is already created".PHP_EOL;
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
						$createdFileContent=$this->makeContent($newCreatedFolder,$createdFile);
						file_put_contents($currentFolder.'/'.$createdFile.'.php', $createdFileContent);
						echo $createdFile ." Model is created successfully".PHP_EOL;
					}elseif(file_exists($currentFolder.'/'.$createdFile.'.php')){
						echo $createdFile . " Model is already created".PHP_EOL;
					}
				}
			}
		}catch(Exception $e){
			echo "You can't created Model".PHP_EOL;
		}
	}

}