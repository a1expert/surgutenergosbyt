#!/usr/local/bin/php
<?php
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
	
	include(dirname(__FILE__) . '/standalone.php');
	
	/**
	* Класс для мониторинга удаленных, измененных и добавленых файлов в системе
	*/
	class FilesMonitor {
		
		protected $initialDir, $logFile, $excludes = array();
		
		public function __construct($initialDir, $logFile, array $excludes) {
			$this->initialDir = $initialDir;
			$this->logFile = $logFile;
			
			foreach ($excludes as &$exclude) {
				$exclude = str_replace('~', $this->initialDir, $exclude);
			}
			unset($exclude);
			
			$this->excludes = $excludes;
		}
		
		protected function isExcluded($path) {
			foreach ($this->excludes as $exclude) {
				if (preg_match('|' . $exclude . '|', $path)) {
					return true;
				} 
			}
			return false;
		}
		
		protected function flushLog($message) {
			echo "{$message}\n";
		}
		
		/**
		* Записать в файл md5 сканируемых файлов и получить список новых файлов
		* @param string $dir начальная директория сканирования
		* @param array $filesToCompare массив существовавших файлов до сканирования
		* @return string лог добавленных файлов
		*/
		public function getMD5($dir, $filesToCompare = array()){
			$log = '';			
			if ($handle = opendir($dir)) {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." && $entry != "..") {
						$entryPath = $dir . "/" . $entry;
						if ($this->isExcluded($entryPath)) {
							$this->flushLog("{$entryPath} был исключен из проверки\n");
							continue;
						}
						if (is_dir($entryPath)) {
							$log .= $this->getMD5($entryPath, $filesToCompare);
						} else {
							if (!empty($filesToCompare) && !in_array($entryPath, $filesToCompare)) {
								$log .= "Файл {$entryPath} был создан<br/>";
								$this->flushLog("Файл {$entryPath} был создан\n");
							} 
							file_put_contents($this->logFile, "{$entryPath};" . md5_file($entryPath) . "\n", FILE_APPEND);
						}
					}
				}
				closedir($handle);
			}
			
			return $log;
		}
		
		/**
		* Сравнить md5 из файла с реальными md5 файлов и получить лог
		* @return array первый элемент массива - string - лог удаленных и измененных файлов,
		* второй элемент массива - array - массив файлов, которые существуют и в логе, и в системе
		*/
		public function checkMD5() {
			$log = "";
			$files = array();
			if ($handle = fopen($this->logFile, "r")) {
			    while (($line = fgets($handle)) !== false) {
			        $fileInfo = explode(';', $line);
			        $filePath = trim($fileInfo[0]);
			        $md5 = trim($fileInfo[1]);
			        if (file_exists($filePath)) {
	        			if ($md5 != md5_file($filePath)) {
	        				$log .= "Файл {$filePath} был изменен<br/>";
	        				$this->flushLog("Файл {$filePath} был изменен\n");
						}
						$files[] = $filePath;
					} else {
						$log .= "Файл {$filePath} был удален<br/>";
						$this->flushLog("Файл {$filePath} был удален\n");
					}
			    }
			    fclose($handle);
			}
			return array($log, $files);
		}
		
		/**
		* Проверить файловую систему
		*/
		public function checkFileSystem() {
			
			$logFile = $this->logFile;
			$initialDir = $this->initialDir;

			if(file_exists($logFile)) {
				
				$info = $this->checkMD5();
				$log = $info[0];
				$filesToCompare = $info[1];
				unlink($logFile);
				
				$log .= $this->getMD5($initialDir, $filesToCompare);
				
				if (strlen($log)) {
					$regedit = regedit::getInstance();

					$emailTo = $regedit->getVal("//settings/admin_email");
					$email = regedit::getInstance()->getVal("//settings/email_from");
					$fio = regedit::getInstance()->getVal("//settings/fio_from");

					$registrationMail = new umiMail();
					$registrationMail->addRecipient($emailTo);
					$registrationMail->setFrom($email, $fio);
					$registrationMail->setSubject('Изменения в файловой системе');
					$registrationMail->setContent($log);
					$registrationMail->commit();
					$registrationMail->send();
					
					$this->flushLog("Письмо с изменениями было отправлено на e-mail {$emailTo}\n");
				} else {
					$this->flushLog("Изменений не было\n");
				}
			} else {
				$this->getMD5($initialDir);
				$this->flushLog("По директоиии {$initialDir} был составлен лог\n");
			}
			
		}

	}
	
	if (isset($_SERVER['HTTP_HOST'])) exit();
	
	 $excludes = array(
		'^~/developerTools',
		'^~/sys-temp',
		'^~/images',
		'^~/files',
		'^~/filemonitor.php',
		'\.svn$'
	);
	
	$logFile = CURRENT_WORKING_DIR . '/sys-temp/filemonitor.log';
	
	$initialDir = CURRENT_WORKING_DIR;
	
	$filesMonitor = new FilesMonitor($initialDir, $logFile, $excludes);
	$filesMonitor->checkFileSystem();

?>