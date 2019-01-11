<?php
/**
 * TODO Write all PHPDoc's
 *
 */
	class umiLogger implements iUmiLogger {

		protected	$logDir = "./logs/",
					$log = "",
					$is_saved = false,
					$is_global_env_pushed = false,
					$start_time = false;

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param unknown_type $logDir
		 */
		public function __construct($logDir = "./logs/") {
			$this->runTimer();
			$this->logDir = $logDir;
			$this->checkDirectory();
		}

		/**
		 * TODO PHPDoc
		 * (non-PHPdoc)
		 * @see iUmiLogger::pushGlobalEnviroment()
		 */
		public function pushGlobalEnviroment() {
			if ($this->is_global_env_pushed == false) {
				$this->collectGlobalEnviroment();
				$this->is_global_env_pushed = true;
				return true;
			} else {
				return false;
			}
		}

		/**
		 * TODO PHPDoc
		 * (non-PHPdoc)
		 * @see iUmiLogger::push()
		 */
		public function push($mess, $enableTimer = true) {
			if ($enableTimer == true) {
				$mess = "[" . sprintf("%1.7f", $this->getTimer()) . "]\t" . $mess;
			}
			$this->log .= $mess . "\n";
		}

		/**
		 * TODO PHPDoc
		 * (non-PHPdoc)
		 * @see iUmiLogger::log()
		 */
		public function log() {
			$this->pushGlobalEnviroment();
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		public function __destruct() {
			if($this->is_saved == false) {
				$this->save();
			}
		}

		/**
		 * TODO PHPDoc
		 * (non-PHPdoc)
		 * @see iUmiLogger::save()
		 */
		public function save() {
			$store_dirpath = $this->prepareStoreDir();

			$filename = date("Y-m-d_H_i_s");

			$filepath = $store_dirpath . "/" . $filename . ".log";
			if (file_put_contents($filepath, $this->get())) {
				return $filepath;
			} else {
				throw new Exception("Can't save log in \"{$filepath}\"");
			}

			$this->is_saved = true;
		}

		/**
		 * TODO PHPDoc
		 * (non-PHPdoc)
		 * @see iUmiLogger::get()
		 */
		public function get() {
			return $this->log;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @throws Exception
		 */
		protected function checkDirectory() {
			$dirpath = $this->logDir;

			if (is_dir($dirpath)) {
				if (is_writable($dirpath)) {
					return true;
				} else {
					throw new Exception("Directory \"{$dirpath}\" must be writable");
				}
			} else {
				throw new Exception("Directory \"{$dirpath}\" doesn't exists");
			}
		}

		/**
		 * Enter description here ...
		 * @throws Exception
		 */
		protected function prepareStoreDir() {
			$dirpath = $this->logDir;
			$remote_addr = $_SERVER['REMOTE_ADDR'];
			$storepath = $dirpath . $remote_addr;

			if (file_exists($storepath)) {
				if (is_writable($storepath)) {
					return $storepath;
				} else {
					throw new Exception("Directory \"{$storepath}\" must be writable");
				}
			}

			if (mkdir($storepath)) {
				return $storepath . '/';
			} else {
				throw new Exception("Can't create directory \"{$storepath}\"");
			}
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		protected function collectGlobalEnviroment() {
			$this->collectGlobalArray('_COOKIE');
			$this->collectGlobalArray('_SESSION');
			$this->collectGlobalArray('_POST');
			$this->collectGlobalArray('_GET');
			$this->collectGlobalArray('_FILES');

			if (function_exists('apache_request_headers')) {
				$this->collectArray("Request headers", apache_request_headers());
			}

			if (function_exists('apache_response_headers')) {
				$this->collectArray("Response headers", apache_response_headers());
			}
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param unknown_type $varname
		 */
		protected function collectGlobalArray($varname) {
			global $$varname;

			if (isset($$varname)) {
				$this->collectArray($varname, $$varname);
			}
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param unknown_type $name
		 * @param unknown_type $arr
		 */
		protected function collectArray($name, $arr) {
			if (!is_array($arr)) {
				return false;
			}

			if (sizeof($arr) == 0) {
				return true;
			}

			$msg = "[{$name}]\n";
			foreach ($arr as $i => $v) {
				$msg .= "\t[" . $i . "]\n\t" . "(" . gettype($v) . ") ";

				if (is_array($v)) {
					$v = $this->serializeArray($v);
				}
				$msg .= $v . "\n\n";
			}
			$this->push($msg, false);
			return true;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param unknown_type $arr
		 */
		protected function serializeArray($arr) {
			$res = "[";

			$sz = sizeof($arr);
			$c = 0;
			foreach ($arr as $i => $v) {
				if (is_array($v)) {
					$v = $this->serializeArray($v);
				}

				$res .= "'" . $v . "'";
				if(++$c < $sz) {
					$res .= ", ";
				}
			}

			$res .= "]";
			return $res;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		protected function runTimer() {
			$this->start_time = microtime(true);
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		protected function getTimer() {
			$time = microtime(true) - $this->start_time;
			return round($time, 7);
		}
	};
?>