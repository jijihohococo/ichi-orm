<?php

namespace JiJiHoHoCoCo\IchiORM\Command;

use Exception;

class ModelCommand
{

	private $path = 'app/Models';
	private $observerPath = 'app/Observers';
	private $resourcePath = 'app/Resources';

	private $modelCommandLine = 'make:model';
	private $observerCommandLine = 'make:observer';
	private $resourceCommandLine = 'make:resource';

	private $green = "\033[0;32m";
	private $red = "\033[01;31m";
	private $end = " \033[0m";

	private $createdFile;

	public function setPath(string $path)
	{
		$this->path = $path;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function setObserverPath(string $observerPath)
	{
		$this->observerPath = $observerPath;
	}

	public function getObserverPath()
	{
		return $this->observerPath;
	}

	public function setResourcePath(string $resourcePath)
	{
		$this->resourcePath = $resourcePath;
	}

	public function getResourcePath()
	{
		return $this->resourcePath;
	}

	private function getNamespace(string $defaulFolder)
	{
		return str_replace('/', '\\', ucfirst($defaulFolder));
	}

	private function makeModelContent(string $defaulFolder, string $createdFile)
	{
		return "<?php

namespace " . $this->getNamespace($defaulFolder) . ";
use JiJiHoHoCoCo\IchiORM\Database\Model;

class " . $createdFile . " extends Model{



}
";
	}

	private function makeObserverContent(string $defaulFolder, string $createdFile)
	{

		$variable = '$' . strtolower(str_replace('Observer', '', $createdFile));

		return "<?php

namespace " . $this->getNamespace($defaulFolder) . ";
use JiJiHoHoCoCo\IchiORM\Observer\ModelObserver;

class " . $createdFile . " implements Observer{


	public function create(" . $variable . "){

	}


	public function update(" . $variable . "){

	}

	public function delete(" . $variable . "){

	}

	public function restore(" . $variable . "){

	}

	public function forceDelete(" . $variable . "){

	}


}
";
	}

	private function makeResourceContent(string $defaulFolder, string $createdFile)
	{
		$variable = '$data';
		return "<?php

namespace " . $this->getNamespace($defaulFolder) . ";
use JiJiHoHoCoCo\IchiORM\Resource\ResourceCollection;

class " . $createdFile . " extends ResourceCollection{

	public function getSelectedResource(" . $variable . "){


	}

}";
	}

	private function checkOption(string $command)
	{
		switch ($command) {
			case $this->modelCommandLine:
				return 'Model';
				break;

			case $this->observerCommandLine:
				return 'Observer';
				break;

			case $this->resourceCommandLine:
				return 'Resource';
				break;
		}
	}

	private function checkPath(string $command)
	{
		switch ($command) {
			case $this->modelCommandLine:
				return $this->getPath();
				break;

			case $this->observerCommandLine:
				return $this->getObserverPath();
				break;

			case $this->resourceCommandLine:
				return $this->getResourcePath();
				break;
		}
	}

	private function checkContent(string $command, string $defaulFolder, string $createdFile)
	{
		switch ($command) {
			case $this->modelCommandLine:
				return $this->makeModelContent($defaulFolder, $createdFile);
				break;

			case $this->observerCommandLine:
				return $this->makeObserverContent($defaulFolder, $createdFile);
				break;

			case $this->resourceCommandLine:
				return $this->makeResourceContent($defaulFolder, $createdFile);
				break;
		}
	}

	public function successMessage(string $message)
	{
		return $this->green . $message . $this->end . PHP_EOL;
	}

	public function errorMessage(string $message)
	{
		return $this->red . $message . $this->end . PHP_EOL;
	}

	private function alreadyHave(string $createdFile, string $createdOption)
	{
		echo $this->errorMessage($createdFile . " " . $createdOption . " is already created");
		exit();
	}

	private function success(string $createdFile, string $createdOption)
	{
		echo $this->successMessage($createdFile . " " . $createdOption . " is created successfully");
		exit();
	}

	private function wrongCommand()
	{
		echo $this->errorMessage("You type wrong command");
		exit();
	}

	private function createError(string $createdFile, string $createdOption)
	{
		echo $this->errorMessage("You can't create " . $createdFile . " file " . $createdOption);
		exit();
	}

	public function run(string $dir, array $argv)
	{

		if (count($argv) == 3 && ($argv[1] == $this->modelCommandLine || $argv[1] == $this->observerCommandLine || $argv[1] == $this->resourceCommandLine)) {
			$command = $argv[1];
			$createdOption = $this->checkOption($command);
			$defaulFolder = $this->checkPath($command);
			$baseDir = $dir . '/' . $defaulFolder;
			if (substr($argv[2], -1) == '/') {
				return $this->wrongCommand();
			}
			try {
				if (!is_dir($baseDir)) {
					$createdFolder = NULL;
					$basefolder = explode('/', $defaulFolder);
					foreach ($basefolder as $key => $folder) {
						$createdFolder .= $key == 0 ? $dir . '/' . $folder : '/' . $folder;
						if (!is_dir($createdFolder)) {
							mkdir($createdFolder);
						}
					}
				}
				$inputFile = explode('/', $argv[2]);
				$count = count($inputFile);

				if ($count == 1 && $inputFile[0] !== NULL && !file_exists($baseDir . '/' . $inputFile[0] . '.php')) {
					$this->createdFile = $inputFile[0];
					fopen($baseDir . '/' . $this->createdFile . '.php', 'w') or die('Unable to create ' . $createdOption);
					$createdFileContent = $this->checkContent($command, $defaulFolder, $this->createdFile);
					file_put_contents($baseDir . '/' . $this->createdFile . '.php', $createdFileContent, LOCK_EX);
					return $this->success($this->createdFile, $createdOption);

				} 
				if ($count == 1 && $inputFile[0] !== NULL && file_exists($baseDir . '/' . $inputFile[0] . '.php')) {
					$this->createdFile = $inputFile[0];

					return $this->alreadyHave($this->createdFile, $createdOption);

				} 
				if ($count > 1 && file_exists($baseDir . '/' . implode('/', $inputFile) . '.php')) {
					$this->createdFile = implode('/', $inputFile);
					return $this->alreadyHave($this->createdFile, $createdOption);

				} 
				if ($count > 1 && !file_exists($baseDir . '/' . implode('/', $inputFile) . '.php')) {
					$this->createdFile = $inputFile[$count - 1];
					unset($inputFile[$count - 1]);
					$currentFolder = NULL;
					$newCreatedFolder = NULL;
					foreach ($inputFile as $key => $folder) {
						$currentFolder .= $key == 0 ? $baseDir . '/' . $folder : '/' . $folder;
						$newCreatedFolder .= $key == 0 ? $defaulFolder . '/' . $folder : '/' . $folder;
						if (!is_dir($currentFolder)) {
							mkdir($currentFolder);
						}
					}

					fopen($currentFolder . '/' . $this->createdFile . '.php', 'w') or die('Unable to create ' . $createdOption);
					$createdFileContent = $this->checkContent($command, $newCreatedFolder, $this->createdFile);
					file_put_contents($currentFolder . '/' . $this->createdFile . '.php', $createdFileContent, LOCK_EX);
					return $this->success($this->createdFile, $createdOption);
				}
			} catch (Exception $e) {

				return $this->createError($this->createdFile, $createdOption);

			}

		}
	}

}