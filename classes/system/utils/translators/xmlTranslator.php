<?php
	class xmlTranslator implements iXmlTranslator {
		public static $showHiddenFieldGroups = false;
		public static $showUnsecureFields = false;
		public static $socialNetworkMode = false;
		public static $keysCache = array();
		public static $translateCache = array();

		protected $domDocument = false;
		protected $currentPageTranslated = false;
		protected static $shortKeys = array(
			'@' => 'attribute',
			'#' => 'node',
			'+'	=> 'nodes',
			'%' => 'xlink',
			'*' => 'comment'
		);

		public function __construct(DOMDocument $domDocument) {
			$this->domDocument = $domDocument;
		}


		public function translateToXml(DOMElement $rootNode, $userData) {
			return $this->chooseTranslator($rootNode, $userData);
		}

		/**
		 * Выбор способа обработки данных
		 * @param DOMElement $rootNode
		 * @param mixed $userData
		 * @param bool $is_full
		 * @return void
		 * @throws coreException
		 */
		public function chooseTranslator(DOMElement $rootNode, $userData, $is_full = false) {
			switch(gettype($userData)) {
				case "array": {
					$this->translateArray($rootNode, $userData);
					break;
				}
				case "object": {
					if (!$userData instanceof umiEntinty && !$userData instanceof umiObjectProxy) {
						$wrapper = translatorWrapper::get($userData);
						$wrapper->isFull = $is_full;
						$this->chooseTranslator($rootNode, $wrapper->translate($userData));
						break;
					}
					$cache = &self::$translateCache;
					$key = get_class($userData) . '#' . $userData->getId() . '#' . ((int) $is_full);

					if (!isset($cache[$key])) {
						$wrapper = translatorWrapper::get($userData);
						$wrapper->isFull = $is_full;
						$cache[$key] = $wrapper->translate($userData);
					}
					$this->chooseTranslator($rootNode, $cache[$key]);
					break;
				}
				default: {
					$this->translateBasic($rootNode, $userData);
					break;
				}
			}
		}

		/**
		 * Разрешена ли обработка макросов
		 * @return bool
		 */
		public static function isParseTPLMacrosesAllowed() {
			static $allowed;
			if (is_bool($allowed)) return $allowed;

			$allowed = true;
			if (cmsController::getInstance()->getCurrentMode() == "admin") {
				$allowed = false;
			} elseif (defined('XML_MACROSES_DISABLE') && XML_MACROSES_DISABLE) {
				$allowedList = mainConfiguration::getInstance()->get('kernel', 'xml-macroses.allowed');
				$allowed = (is_array($allowedList) && count($allowedList));
			}
			return $allowed;
		}

		/**
		 * Получить список разрешенных для обработки макросов
		 * @return null|String
		 */
		public static function getAllowedTplMacroses() {
			static $cache = false;

			if ($cache !== false) {
				return $cache;
 			}

			if (defined('XML_MACROSES_DISABLE') && XML_MACROSES_DISABLE) {
				$cache = mainConfiguration::getInstance()->get('kernel', 'xml-macroses.allowed');
			} else {
				$cache = null;
			}

			return $cache;
		}

		/**
		 * Обработка макросов
		 * @param mixed $userData
		 * @param bool $scopeElementId
		 * @param bool $scopeObjectId
		 * @return string
		 * @throws coreException
		 */
		public static function executeMacroses($userData, $scopeElementId = false, $scopeObjectId = false) {
			if (!self::isParseTPLMacrosesAllowed()) {
				return $userData;
			}

			if (strpos($userData, '%') === false) {
				return $userData;
			}

			$tplTemplater = umiTemplater::create('TPL');
			$tplTemplater->executeOnlyAllowedMacroses(self::getAllowedTplMacroses());
			$tplTemplater->setScope($scopeElementId, $scopeObjectId);
			return $tplTemplater->parse(array(), $userData);
		}

		/**
		 * Получить правую часть ключа
		 * @param $key
		 * @return string
		 */
		public static function getRealKey($key) {
			$keysCache = &self::$keysCache;
			if (!isset($keysCache[$key])) {
				$keysCache[$key] = self::getKey($key);
			}

			list($subKey, $realKey) = $keysCache[$key];
			return $realKey;
		}

		/**
		 * Получить левую часть ключа
		 * @param $key
		 * @return string
		 */
		public static function getSubKey($key) {
			$keysCache = &self::$keysCache;
			if (!isset($keysCache[$key])) {
				$keysCache[$key] = self::getKey($key);
			}

			list($subKey, $realKey) = $keysCache[$key];
			return $subKey;
		}

		/**
		 * Получить массив правой и левой частей ключа
		 * @param  string $key
		 * @return string
		 */
		public static function getKey($key) {
			if (isset(self::$shortKeys[$key[0]])) {
				return array(self::$shortKeys[$key[0]], substr($key, 1));
			}
			return strpos($key, ":") ? explode(":", $key, 2) : array(false, $key);
		}
		
		/**
		 * Очищает кэш у всех экземпляров класса
		 * @return void
		 */
		public static function clearCache()
		{
			self::$keysCache = array();
			self::$translateCache = array();
		}
		
		/**
		 * Конвертация скалярных значений
		 * @param DOMElement $rootNode
		 * @param mixed $userData
		 * @return void
		 */
		protected function translateBasic(DOMElement $rootNode, $userData) {
			$dom = $this->domDocument;

			$userData = self::executeMacroses($userData);

			$element = $dom->createTextNode($userData);
			$rootNode->appendChild($element);
		}

		/**
		 * Конвертация массива
		 * @param DOMElement $rootNode
		 * @param array $userData
		 * @return void
		 * @throws coreException
		 */
		protected function translateArray(DOMElement $rootNode, $userData) {
			$keysCache = &self::$keysCache;
			$dom = $this->domDocument;
			foreach($userData as $key => $val) {
				if (!isset($keysCache[$key])) {
					$keysCache[$key] = self::getKey($key);
				}
				list($subKey, $realKey) = $keysCache[$key];
				switch($subKey) {
					case 'attr':
					case 'attribute': {
						if ($val === "" || $val === null || is_array($val)) {
							break;
						}
						$val = (self::$socialNetworkMode)? htmlspecialchars($val) : $val;
						$rootNode->setAttribute($realKey, $val);
 						break;
 					}
					case 'nodes':
					case 'list': {
						if (is_array($val)) {
							foreach($val as $cval) {
								$element = $dom->createElement($realKey);
								$this->chooseTranslator($element, $cval);
								$rootNode->appendChild($element);
							}
 						}
 						break;
 					}
					case 'node': {
						if (self::$socialNetworkMode) {
							$node = $dom->createCDATASection((string) $val);
						} else {
							$node = $dom->createTextNode((string) $val);
						}
						$rootNode->appendChild($node);
						break;
					}
					case 'void': {
						break;
					}
					case 'full': {
						$element = $realKey ? $dom->createElement($realKey) : $rootNode;
						$this->chooseTranslator($element, $val, true);
						if ($realKey) {
							$rootNode->appendChild($element);
						}
						break;
					}
					case 'xml': {
						$val = html_entity_decode($val, ENT_COMPAT, "utf-8");
						$val = str_replace('&', '&amp;', $val);
						$sxe = @secure_load_simple_xml($val);
						if ($sxe !== false) {
							if ($dom_sxe = dom_import_simplexml($sxe)) {
								$dom_sxe = $dom->importNode($dom_sxe, true);
								$rootNode->appendChild($dom_sxe);
							}
						} else {
							$rootNode->appendChild($dom->createTextNode($val));
						}
						break;
					}
					case 'xlink': {
						$rootNode->setAttribute("xlink:" . $realKey, $val);
						break;
					}
					case 'comment': {
						$rootNode->appendChild(new DOMComment(' ' . $val . ' '));
						break;
					}
					case 'subnodes': {
						$res = array();
						$res[$realKey] = array('nodes:item' => $val);
						$val = $res;
						unset($res);
					}
					default: {
						if ($realKey === 0) {
							throw new coreException("Can't translate to xml key {$realKey} with value {$val}");
 						}
						$element = $dom->createElement($realKey);
						$this->chooseTranslator($element, $val);
						$rootNode->appendChild($element);
					}
				}
			}
		}

		/**
		 * @deprecated
		 */
		protected function isKeyANull($key) {
			return $this->getSubKey($key) == "void";
		}

		/**
		 * @deprecated
		 */
		protected function isKeyAFull($key) {
			return $this->getSubKey($key) == "full";
		}

		/**
		 * @deprecated
		 */
		protected function isKeyAnAttribute($key) {
			$subKey = $this->getSubKey($key);
			return ($subKey == "attr" || $subKey == "attribute");
		}

		/**
		 * @deprecated
		 */
		protected function isKeyANode($key) {
			return ($this->getSubKey($key) == "node");
		}

		/**
		 * @deprecated
		 */
		protected function isKeyNodes($key) {
			return ($this->getSubKey($key) == "nodes"  || $this->getSubKey($key) == "list");
		}

		protected function isKeySubnodes($key) {
			return ($this->getSubKey($key) == "subnodes");
		}

		/**
		 * @deprecated
		 */
		protected function isKeyXml($key) {
			return ($this->getSubKey($key) == "xml");
		}

		/**
		 * @deprecated
		 */
		protected function isKeyXLink($key) {
			return ($this->getSubKey($key) == "xlink");
		}

		/**
		 * @deprecated
		 */
		protected function isKeyComment($key) {
			return ($this->getSubKey($key) == "comment");
		}
	};
?>
