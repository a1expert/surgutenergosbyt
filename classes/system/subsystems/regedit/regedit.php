<?php

class regedit extends singleton implements iRegedit {
	protected $cacheFilePath, $cache = Array(), $cacheSaved = false;

	/**
	 * @param null $c
	 *
	 * @return regedit
	 */
	public static function getInstance($c = NULL) {
		return parent::getInstance(__CLASS__);
	}


	public function getKey($path, $rightOffset = 0, $cacheOnly = false) {
		static $cache = array();
		$path = trim($path, "/");

		if (isset($this->cache['keys'][$path])) {
			return $this->cache['keys'][$path];
		}

		if ($cacheOnly) {
			return $this->cache['keys'][$path] = false;
		}

		$keyId = 0; $previousPaths = array();
		foreach(explode("/", $path) as $key) {
			$key = l_mysql_real_escape_string($key);
			$previousPaths[] = $key;
			$currentKey = implode('/', $previousPaths);

			if(isset($cache[$currentKey])) {
				$keyId = $cache[$currentKey];
				continue;
			}

			$sql = "SELECT id FROM cms_reg WHERE rel = '$keyId' AND var = '{$key}'";
			$result = l_mysql_query($sql, true);
			if (mysql_num_rows($result)) {
				list($keyId) = mysql_fetch_row($result);
				$cache[$currentKey] = $keyId;
			} else {
				return $this->cache['keys'][$path] = false;
			}
		}
		return $this->cache['keys'][$path] = (int) $keyId;
	}

	/**
	 * Получить значение
	 *
	 * @param string $path
	 *
	 * @return mixed
	 */
	public function getVal($path, $cacheKey = false) {
		$keyId = $this->getKey($path, $cacheKey);

		if(isset($this->cache['values'][$path])) {
			return $this->cache['values'][$path];
		}

		if($keyId) {
			if(isset($this->cache['values'][$keyId])) {
				return $this->cache['values'][$keyId];
			}
			$this->cacheSaved = false;

			$sql = "SELECT val FROM cms_reg WHERE id = '{$keyId}'";
			$result = l_mysql_query($sql, true);

			list($value) = mysql_fetch_row($result);
			return $this->cache['values'][$keyId] = $value;
		} else {
			return $this->cache['values'][$path] = false;
		}
	}

	public function setVar($path, $value) {
		return $this->setVal($path, $value);
	}

	public function setVal($path, $value) {
		if(defined('CURRENT_VERSION_LINE') && CURRENT_VERSION_LINE == 'demo') {
			return false;
		}

		$this->resetCache();
		$keyId = $this->getKey($path);
		if($keyId == false) {
			$keyId = $this->createKey($path);
		}

		$value = l_mysql_real_escape_string($value);
		$sql = "UPDATE cms_reg SET val = '{$value}' WHERE id = '{$keyId}'";
		l_mysql_query($sql);

		$this->resetCache();

		return true;
	}

	public function delVar($path) {
		if(defined('CURRENT_VERSION_LINE') && CURRENT_VERSION_LINE == 'demo') {
			return false;
		}

		$keyId = $this->getKey($path);
		if($keyId) {
			$sql = "DELETE FROM cms_reg WHERE rel = '{$keyId}' OR id = '{$keyId}'";
			l_mysql_query($sql, true);
			$this->resetCache();
			return true;
		} else {
			return false;
		}
	}

	public function getList($path) {
		if(isset($this->cache['lists'][$path])) {
			return $this->cache['lists'][$path];
		}

		$keyId = $this->getKey($path);

		if($path == "//") {
			$keyId = 0;
		}

		if($keyId || $path == "//") {
			if(isset($this->cache['lists'][$keyId])) {
				return $this->cache['lists'][$keyId];
			}
			$this->cacheSaved = false;

			$sql = "SELECT id, var, val FROM cms_reg WHERE rel = '{$keyId}' ORDER BY id ASC";
			$result = l_mysql_query($sql, true);

			$values = Array();
			while(list(, $var, $val) = mysql_fetch_array($result)) {
				$values[] = Array($var, $val);
			}
			return $this->cache['lists'][$keyId] = $values;
		} else {
			return $this->cache['lists'][$path] = false;
		}
	}

	final public static function checkSomething($a, $b, $return=false) {
		$trialLifetime = 3600*24*30;

		if(preg_match('/\.?localhost$/', $_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_ADDR'] === '127.0.0.1' || $_SERVER['SERVER_ADDR'] === '::1')) {
			return true;
		}

		$isCommerceEnc = self::getInstance()->getVal("//modules/autoupdate/system_edition") == 'commerce_enc';

		foreach ($b as $versionLine => $c3) {
			$isValid = (substr($a, 12, strlen($a) - 12) == $c3);

			if ($isValid === true) {
				if (!defined('CURRENT_VERSION_LINE')) {
					define("CURRENT_VERSION_LINE", $versionLine);
				}

				if ($versionLine == "trial" || $isCommerceEnc) {
					if (file_exists(SYS_CACHE_RUNTIME . "trash")) {
						unlink(SYS_CACHE_RUNTIME . "trash");
					}

					$createTime = filectime(__FILE__);
					$currentTime = time();

					if (($currentTime - $createTime) > $trialLifetime) {
						if ($return) {
							return false;
						} else {
							include CURRENT_WORKING_DIR . "/errors/trial_expired.html";
							exit();
						}
					}
				}

				return true;
			}
		}

		return false;
	}


	final public function checkSelfKeycode() {
		$keycode = $this->getVal("//settings/keycode");

		if (strlen($keycode)==0) {
			return false;
		}

		$codename = $this->getVal("//settings/system_edition");

		$pro = array('commerce', 'business', 'corporate', 'commerce_enc', 'business_enc', 'corporate_enc');
		$internalCodeName = in_array($codename, $pro) ? 'pro' : $codename;

		$b = array($internalCodeName => umiTemplater::getSomething($internalCodeName));

		return self::checkSomething($keycode, $b, true);
	}

	public function getDaysLeft() {
		return 30 - floor((time() - filectime(__FILE__)) / (3600*24));
	}


	protected function __construct() {
		$config = mainConfiguration::getInstance();
		$this->cacheFilePath = $config->includeParam('system.runtime-cache') . 'registry';
		$this->loadCache();
	}

	public function __destruct() {
		if(!$this->cacheSaved) {
			$this->saveCache();
		}
	}

	protected function loadCache() {
		$cacheFrontend = cacheFrontend::getInstance();

		if($cacheFrontend->getIsConnected()) {
				if($cache = $cacheFrontend->loadSql("registry")) {
					$this->cache = unserialize($cache);
					$this->cacheSaved = true;
					return;
				}
		}

		if(file_exists($this->cacheFilePath)) {
			$cache = unserialize(file_get_contents($this->cacheFilePath));
			if(is_array($cache)) {
				$this->cacheSaved = true;
				$this->cache = $cache;
			}
		}
	}

	protected function saveCache() {
		if(is_array($this->cache)) {
			if(is_dir(dirname($this->cacheFilePath))) {
				file_put_contents($this->cacheFilePath, serialize($this->cache));
			}
			if(cacheFrontend::getInstance()->getIsConnected()) {
				cacheFrontend::getInstance()->saveSql("registry", serialize($this->cache));
			}
		}
		$this->cacheSaved = true;
	}

	protected function createKey($path) {
		$path = trim($path, "/");
		$subKeyPath = "//";

		$relId = 0;
		$keyId = null;

		foreach(explode("/", $path) as $key) {
			$key = l_mysql_real_escape_string($key);
			$subKeyPath .= $key . "/";

			if($keyId = $this->getKey($subKeyPath)) {
				$relId = $keyId;
			} else {
				$sql = "INSERT INTO cms_reg (rel, var, val) VALUES ('{$relId}', '{$key}', '')";
				l_mysql_query($sql, true);
				$relId = $keyId = (int) l_mysql_insert_id();
				$this->resetCache();
			}
		}
		return $keyId;
	}

	protected function resetCache($keys = false) {
		if(is_array($keys)) {
			foreach($keys as $key) {
				if(isset($this->cache[$key])) {
					unset($this->cache[$key]);
				}
			}
		} else {
			$this->cache = Array();
		}

		$this->saveCache();
	}
};

?>