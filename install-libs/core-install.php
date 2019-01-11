<?php
	class umiDistrReader {
		protected $distrFilePath, $fh;

		public		$signature = "ucp", $author, $comment, $timestamp, $totalSize;
		protected	$version = "1.0.0";


		public function __construct($distrFilePath) {
			if(!is_file($distrFilePath)) {
				throw new Exception("Distributive file \"{$distrFilePath}\" doesn't exists", E_USER_ERROR);
			}

			$this->distrFilePath = $distrFilePath;
			$this->readHeader();
		}


		public function __destruct() {
			if(is_resource($this->fh)) {
				fclose($this->fh);
			}
		}


		protected function readHeader() {
			if(!is_readable($this->distrFilePath)) {
				throw new Exception("Distributive file \"{$this->distrFilePath}\" is not readable", E_USER_ERROR);
			}

			$this->fh = $f = fopen($this->distrFilePath, "r");

			fseek($f, 0);
			if(stream_get_line($f, 5, "\0") != $this->signature) {
				throw new Exception("Distributive file corrupted: wrong signature", E_USER_ERROR);
				return false;
			}

			fseek($f, 5);
			if(version_compare($needle_version = stream_get_line($f, 5, "\0"), $this->version, "<=") != 1) {
				throw new Exception("You need installer at least version {$needle_version} to read this distribute file", E_USER_ERROR);
				return false;
			}

			fseek($f, 10);
			$this->timestamp = (int) stream_get_line($f, 15, "\0");

			fseek($f, 25);
			$this->totalSize = (int) stream_get_line($f, 25, "\0");

			fseek($f, 50);
			$this->author = (string) stream_get_line($f, 25, "\0");

			fseek($f, 75);
			$this->comment = (string) stream_get_line($f, 330, "\0");

			fseek($f, 331);
		}


		public function getNextResource($pos = false) {
			$f = $this->fh;

			if($pos !== false) {
				fseek($f, $pos);
			}

			$p = ftell($f);

			$blockSize = (int) stream_get_line($f, 25, "\0");

			fseek($f, $p + 25);
			$blockData = (string) stream_get_line($f, $blockSize);

			if(strlen($blockData) == $blockSize) {
				$obj = unserialize(base64_decode($blockData));
				return $obj;
			} else {
				return false;
			}
		}


		public function getCurrentPos() {
			return ftell($this->fh);
		}
	};

	abstract class umiDistrInstallItem {
		abstract public function __construct($filePath = false);

		abstract public function pack();
		abstract public static function unpack($data);

		abstract public function restore();

		abstract public function getDescription();
	};

	class umiDistrFolder extends umiDistrInstallItem {
		protected $filePath, $permissions;

		public function __construct($filePath = false) {
			if($filePath !== false) {
				$this->filePath = $filePath;
				$this->permissions = fileperms($filePath) & 0x1FF;
			}
		}

		public function pack() {
			return base64_encode(serialize($this));
		}

		public static function unpack($data) {
			return base64_decode(unserialize($data));
		}

		public function restore() {
			$this->filePath = str_replace("\\", "/", $this->filePath);
			
			if(!file_exists($this->filePath)) {
				$pathinfo = pathinfo($this->filePath);

				if(is_dir($pathinfo['dirname'])) {
					if(is_writable(dirname($this->filePath))) {
						mkdir($this->filePath);
					} else {
						throw new Exception("Folder {$this->filePath} is not writable");
					}
				}
			}

			if(is_dir($this->filePath)) {
				if(function_exists("posix_getuid")) {
					@chmod($this->filePath, $this->permissions);
					return true;
				}
			} else {
				return false;
			}
		}

		public function getDescription() {
			$this->filePath = str_replace("\\", "/", $this->filePath);
			return $this->filePath;
		}
	};

	class umiDistrFile extends umiDistrInstallItem {
		protected $filePath, $permissions, $content;

		public function __construct($filePath = false) {
			if($filePath !== false) {
				$this->filePath = $filePath;
				$this->permissions = fileperms($filePath) & 0x1FF;
				$this->content = file_get_contents($filePath);
			}
		}

		public function pack() {
			return base64_encode(serialize($this));
		}

		public static function unpack($data) {
			return base64_decode(unserialize($data));
		}

		public function restore() {
			$this->filePath = str_replace("\\", "/", $this->filePath);

			if(!is_file($this->filePath)) {
				$dirname = dirname($this->filePath);
				if(!is_dir($dirname)) {
					throw new Exception("Directory {$dirname} doesn't exists");
				}
				
				if(!is_writable($dirname)) {
					throw new Exception("Directory {$dirname} is not writable");
				}
				
				file_put_contents($this->filePath, $this->content);
			}

			if(is_file($this->filePath)) {
				if(!is_writable($this->filePath)) {
					throw new Exception("File {$this->filePath} is not writable");
				}

				if(function_exists("posix_getuid")) {
					@chmod($this->filePath, $this->permissions);
				}
				
				file_put_contents($this->filePath, $this->content);
				return true;
			} else {
				throw new Exception("Failed to create file (\"{$this->filePath}\")", E_USER_ERROR);
				return false;
			}
		}


		public function getDescription() {
			$this->filePath = str_replace("\\", "/", $this->filePath);
			return $this->filePath;
		}
	};

	class umiDistrMySql extends umiDistrInstallItem {
		protected $tableName, $permissions, $sqls = Array();

		public function __construct($tableName = false) {
			if($tableName !== false) {
				$this->tableName = $tableName;
				$this->readTableDefinition();
				$this->readData();
			}
		}

		public function pack() {
			return base64_encode(serialize($this));
		}

		public static function unpack($data) {
			return base64_decode(unserialize($data));
		}

		public function restore() {
			$sql = "DROP TABLE IF EXISTS {$this->tableName}";
			mysql_query($sql);
			
			if($this->tableName == 'cms3_object_content') {
				$sql = "SHOW TABLES LIKE 'cms3_object_content%'";
				$result = mysql_query($sql);
				while(list($tn) = mysql_fetch_row($result)) {
					mysql_query("DROP TABLE {$tn}");
				}
			}

			$sz = sizeof($this->sqls);
			for($i = 0; $i < $sz; $i++) {
				$sql = $this->sqls[$i];
				
				if($i == 0) {
					$sql = str_replace("\"", "`", $sql);
					$sql .= " ENGINE=InnoDb";
				}

				mysql_query($sql);
				if($err = mysql_error()) {
					throw new Exception("Error while creating table {$this->tableName}: " . $err);
				}
			}
		}


		protected function readTableDefinition() {
			$sql = "SHOW CREATE TABLE {$this->tableName}";
			$result = mysql_query($sql);
			list(, $cont) = mysql_fetch_row($result);
			$this->sqls[] = $cont;
		}


		protected function readData() {
			$sql = "SELECT * FROM {$this->tableName}";
			$result = mysql_query($sql);
			while($row = mysql_fetch_assoc($result)) {
				$this->sqls[] = $this->generateInsertRow($row);
			}
		}


		protected function generateInsertRow($row) {
			$sql = "INSERT INTO {$this->tableName} (";

			$fields = array_keys($row);
			$sz = sizeof($fields);
			for($i = 0; $i < $sz; $i++) {
				$sql .= $fields[$i];

				if($i < ($sz - 1)) {
					$sql .= ", ";
				}
			}
			unset($fields);

			$sql .= ") VALUES(";



			$values = array_values($row);
			$sz = sizeof($values);
			for($i = 0; $i < $sz; $i++) {
				$sql .= "'" . mysql_escape_string($values[$i]) . "'";

				if($i < ($sz - 1)) {
					$sql .= ", ";
				}
			}
			unset($values);


			$sql .= ")";

			return $sql;
		}

		public function getDescription() {
			return $this->tableName;
		}
	};
?>