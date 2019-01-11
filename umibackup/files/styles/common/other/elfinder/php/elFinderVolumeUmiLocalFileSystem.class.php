<?php

	class elFinderVolumeUmiLocalFileSystem extends elFinderVolumeLocalFileSystem {
    	protected $driverId = 'umi';
		public function fullRoot() {
			return $this->root;
		}

		/**
		* ����������������� ����� (��������� �������� �� ������������� - ������ �������� � ������ ��������)
		*
		* @param mixed $hash
		* @param mixed $name
		* @return string|false
		*/
		public function rename($hash, $name) {
			$path = $this->decode($hash);


			if (!($file = $this->file($hash))) {
				return $this->setError(elFinder::ERROR_FILE_NOT_FOUND);
			}

			$dir = $this->_dirname($path);

			if ($this->attr($path, 'locked')) {
				return $this->setError(elFinder::ERROR_LOCKED, $file['name']);
			}

			if (!$this->nameAccepted($name)) {
				return $this->setError(elFinder::ERROR_INVALID_NAME, $name);
			}
			
			if ($name == $file['name']) {
				return $file;
			}
			
			$newLastDotPosition = strrpos($name, '.');
			$newExt = ($newLastDotPosition) ? substr($name, strrpos($name, '.')) : '';
			
			$lastDotPosition = strrpos($file['name'], '.');
			$ext = ($lastDotPosition) ? substr($file['name'], strrpos($file['name'], '.')) : '';
			
			if ($newExt != $ext && !in_array($newExt, umiFile::$allowedFileTypes)) return $this->setError(elFinder::ERROR_INVALID_NAME, $name); 

			if ($this->_moveWithRename($path, $dir, $name)) {
				$this->rmTmb($path);
				return $this->stat($this->_joinPath($dir, $name));
			}
			return false;
		}

		/**
		* �������� (������ ��������� " copy" ������ "_copy")
		*
		* @param mixed $hash
		* @return false
		*/
		public function duplicate($hash) {
			if (($file = $this->file($hash)) == false) {
				return $this->setError(elFinder::ERROR_FILE_NOT_FOUND);
			}

			$path = $this->decode($hash);
			$dir  = $this->_dirname($path);

			return ($path = $this->doCopy($path, $dir, $this->uniqueName($dir, $file['name'], "_copy"))) == false
				? false
				: $this->stat($path);
		}

		/**
		* �������� ����� � �������������� ��������
		*
		* @param string $dst
		* @param string $name
		* @param mixed $copy
		* @return bool
		*/
		public function mkdir($dst, $name, $copy=false) {
			$path = $this->decode($dst);

			if (($dir = $this->dir($dst)) == false) {
				return $this->setError(elFinder::ERROR_TRGDIR_NOT_FOUND, '#'.$dst);
			}

			if (!$dir['write']) {
				return $this->setError(elFinder::ERROR_PERM_DENIED);
			}

			if (!$this->nameAccepted($name)) {
				return $this->setError(elFinder::ERROR_INVALID_NAME);
			}

			if ($copy && !$this->options['copyOverwrite']) {
				$name = $this->uniqueName($path, $name, '-', false);
			}

			$dst = $this->_joinPath($path, $name);

			if ($this->_fileExists($dst)) {

				if ($copy) {
					if (!$this->options['copyJoin'] && $this->attr($dst, 'write')) {
						foreach ($this->_scandir($dst) as $p) {
							$this->doRm($p);
						}
					}
					return $this->stat($dst);
				}

				return $this->setError(elFinder::ERROR_EXISTS, $name);
			}

			return $this->_mkdirWithRename($path, $name) ? $this->stat($this->_joinPath($path, $name)) : false;
		}

		/**
		* ���������� ������������ �����
		*
		* @param mixed $fp
		* @param string $dst
		* @param mixed $name
		* @param mixed $cmd
		* @return string|false
		*/
		public function save($fp, $dst, $name, $cmd = 'upload') {

			if (($dir = $this->dir($dst, true, true)) == false) {
				return $this->setError(elFinder::ERROR_TRGDIR_NOT_FOUND, '#'.$dst);
			}

			if (!$dir['write']) {
				return $this->setError(elFinder::ERROR_PERM_DENIED);
			}

			if (!$this->nameAccepted($name)) {
				return $this->setError(elFinder::ERROR_INVALID_NAME, $name);
			}

			$dst = $this->decode($dst);

			if (strpos($dst, CURRENT_WORKING_DIR . '/files/') !== false || strpos($dst, CURRENT_WORKING_DIR . '/images/') !== false) {

				$quota_byte = getBytesFromString( mainConfiguration::getInstance()->get('system', 'quota-files-and-images') );
				if ( $quota_byte != 0 ) {
					$all_size = getBusyDiskSize(array('/images', '/files'));
					if ( $all_size >= $quota_byte ) {
						return $this->setError(getLabel('error-files_quota_exceeded'));
					}
				}
			}

			//�������� �����
			$sMethodName = method_exists($this, "_doSave_{$cmd}") ? "_doSave_{$cmd}" : "_doSave_unknown";
			$path = $this->$sMethodName($fp, $dst, $name);

			$result = false;
			if ($path) {
				$result = $this->stat($path);
			}
			return $result;
		}

		/**
		* ����������� ���� � ����� ����� (�����������/��������������)
		*
		* @param  string  $source  source file path
		* @param  string  $target  target dir path
		* @param  string  $name    file name
		* @return bool
		*/
		protected function _moveWithRename($source, &$targetDir, &$name='') {
			$i = 0;
			$bNeedRename = true;

			$old_locale = setlocale(LC_ALL, NULL);
			setlocale(LC_ALL, array("ru_RU.UTF-8", "ru_RU.CP1251", "ru_RU.KOI8-R", "ru_SU.CP1251", "ru_RU", "russian", "ru_SU", "ru"));

			while($bNeedRename) {
				$name = $this->_getNewFilename($name, $i);
				$target = $targetDir . DIRECTORY_SEPARATOR . ($name ? $name : basename($source));
				clearstatcache();
				$bNeedRename = (file_exists($target) || is_dir($target));
				$i++;
			}

			setlocale($old_locale);

			return @rename($source, $target);
		}

		/**
		 * ������� ����� � ���������������
		 *
		 * @param  string  $path  parent dir path
		 * @param string  $name  new directory name
		 * @return bool
		 * @author Dmitry (dio) Levashov
		 */
		protected function _mkdirWithRename($path, &$name) {
			$i = 0;
			$bNeedRename = true;

			$old_locale = setlocale(LC_ALL, NULL);
			setlocale(LC_ALL, array("ru_RU.UTF-8", "ru_RU.CP1251", "ru_RU.KOI8-R", "ru_SU.CP1251", "ru_RU", "russian", "ru_SU", "ru"));

			while($bNeedRename) {
				$name = $this->_getNewFilename($name, $i);
				$target = $path.DIRECTORY_SEPARATOR.$name;
				clearstatcache();
				$bNeedRename = (file_exists($target) || is_dir($target));
				$i++;
			}

			setlocale($old_locale);

			if (@mkdir($target)) {
				@chmod($target, $this->options['dirMode']);
				return true;
			}
			return false;
		}

		/**
		* �������� ���������� �������� ����� � �������� ���������� � ��������
		*
		* @param mixed $sOldName ��� �����
		* @param mixed $i �������� ��������
		* @return string ����� ��� �����
		*/
		protected function _getNewFilename($sOldName, $i) {
			if($sOldName == '') return $sOldName;

			$iLastDotPosition = strrpos($sOldName, '.');
			$sBaseName = ($iLastDotPosition) ? substr($sOldName, 0, strrpos($sOldName, '.')) : $sOldName;
			$sBaseName = $this->_convertFilename($sBaseName);

			$sExt = ($iLastDotPosition) ? substr($sOldName, strrpos($sOldName, '.')) : '';
			$sExt = $this->_convertFilename($sExt);

			if($i == 0) {
				return "{$sBaseName}{$sExt}";
			} else {
				return "{$sBaseName}_{$i}{$sExt}";
			}
		}

		/**
		* ����������� ����� �����
		*
		* @param string $sFileBaseName
		* @return string
		*/
		protected function _convertFilename($sFileBaseName) {
			$arConvertions = array(
				array('a', array('�', '�')), array('b', array('�', '�')), array('v', array('�', '�')),
				array('g', array('�', '�')), array('d', array('�', '�')), array('e', array('�', '�')),
				array('e', array('�', '�')), array('zsh', array('�', '�')), array('z', array('�', '�')),
				array('i', array('�', '�')), array('i', array('�', '�')), array('k', array('�', '�')),
				array('l', array('�', '�')), array('m', array('�', '�')), array('n', array('�', '�')),
				array('o', array('�', '�')), array('p', array('�', '�')), array('r', array('�', '�')),
				array('s', array('�', '�')), array('t', array('�', '�')), array('u', array('�', '�')),
				array('f', array('�', '�')), array('h', array('�', '�')), array('c', array('�', '�')),
				array('ch', array('�', '�')), array('sh', array('�', '�')), array('sh', array('�', '�')),
				array('', array('�', '�')), array('i', array('�', '�')), array('', array('�', '�')),
				array('e', array('�', '�')), array('yu', array('�', '�')), array('ya', array('�', '�')),
				array('_', ' '), array('', '~'), array('', '`'),
				array('', '!'), array('', '@'), array('', '"'),
				array('', "'"), array('', '#'), array('', '�'),
				array('', '$'), array('', ';'), array('', '%'),
				array('', '^'), array('', ':'), array('', '&'),
				array('', '?'), array('', '*'), array('', '+'),
				array('', '='), array('', '|'), array('', "\\"),
				array('', '/'), array('', ','), array('', '<'),
				array('', '>')
			);

			foreach($arConvertions as $arConvPair) {
				$sFileBaseName = str_replace($arConvPair[1], $arConvPair[0], $sFileBaseName);
			}

			return $sFileBaseName;
		}

		/**
		* �������� ��� ���������� ����� ��� ��� ��������
		*
		* @param mixed $dst
		* @param mixed $name
		* @return false
		*/
		protected function _doSave_upload($fp, $dst, $name) {
			$cwd = getcwd();
			chdir(CURRENT_WORKING_DIR);

			$files_index = 0;

			$regedit = regedit::getInstance();
			$controller = cmsController::getInstance();

			$filename = "." . rtrim($dst, "/\\") . DIRECTORY_SEPARATOR . $name;
			if(isset($_FILES['upload'])) {
				foreach($_FILES['upload']['name'] as $i => $f_name) {
					if($f_name == $name) {
						$filename = $_FILES['upload']['tmp_name'][$i];
						$files_index = $i;
					}
				}
			}
			$filesize = (int) filesize($filename);
			if (umiImageFile::getIsImage($name)) {
				$max_img_filesize =	$controller->getModule('data')->getAllowedMaxFileSize('img') * 1024 * 1024;
				if ($max_img_filesize > 0) {
					if ($max_img_filesize < $filesize) {
						chdir($cwd);
						return $this->setError(getLabel('error-max_img_filesize') . ' ' . ($max_img_filesize / 1024 / 1024) . "M");
					}
				}
				if(getRequest('water_mark')) umiImageFile::setWatermarkOn();
				$file = umiImageFile::upload('upload', $files_index, $dst);
			}
			else {
				$upload_max_filesize = $controller->getModule('data')->getAllowedMaxFileSize() * 1024 * 1024;
				if ($upload_max_filesize > 0) {
					if ($upload_max_filesize < $filesize) {
						chdir($cwd);
						return $this->setError(getLabel('error-max_filesize') . ' ' . ($upload_max_filesize / 1024 / 1024) . "M");
					}
				}
				$file = umiFile::upload('upload', $files_index, $dst);
			}

			chdir($cwd);

			if(!$file instanceof umiFile || $file->getIsBroken()) {
				return $this->setError(elFinder::ERROR_UPLOAD);
			} else {
				return CURRENT_WORKING_DIR . $file->getFilePath(true);
			}
		}

		/**
		* �������� ��� ���������� ����� ��� ��� �����������
		*
		* @param mixed $dst
		* @param mixed $name
		*/
		protected function _doSave_copy($fp, $dst, $name) {
			$path = $dst.DIRECTORY_SEPARATOR.$name;

			if (!($target = @fopen($path, 'wb'))) {
				$this->setError(elFinder::ERROR_COPY);
				return false;
			}

			while (!feof($fp)) {
				fwrite($target, fread($fp, 8192));
			}
			fclose($target);
			@chmod($path, $this->options['fileMode']);
			clearstatcache();

			return $path;
		}

		/**
		* ����������� ����� ���������� �����
		*
		* @param mixed $dst
		* @param mixed $name
		* @return false
		*/
		protected function _doSave_unknown($fp, $dst, $name) {
			return $this->setError(elFinder::ERROR_UNKNOWN_CMD);
		}

	}
?>
