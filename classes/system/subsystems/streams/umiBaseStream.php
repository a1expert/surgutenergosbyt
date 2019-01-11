<?php
	abstract class umiBaseStream implements iUmiBaseStream {
	    public static $allowTimeMark = true;
	    public static $allowExtendedOptions = true;
		protected
				$position = 0,
				$length = 0,
				$data = "",
				$expire = 0,
				$transform = "",
				$path, $params = array(),
				$isJson = false;

		protected	$scheme;
		protected static $callLog = array();

		private		$start_time = false;


		public function __construct() {
			$this->start_time = microtime(true);
		}

		public function stream_flush() {
			return true;
		}


		public function stream_tell() {
			return $this->position;
		}


		public function stream_eof() {
			return $this->position >= $this->length;
		}


		public function stream_seek($offset, $whence) {
			switch($whence) {
				case SEEK_SET: {
					if($this->isValidOffset($offset)) {
						$this->position = $offset;
						return true;
					} else {
						return false;
					}
				}


				case SEEK_CUR: {
					if($offset >= 0) {
						$this->position += $offset;
						return true;
					} else {
						return false;
					}
				}


				case SEEK_END: {
					if($this->isValidOffset($this->position + $offset)) {
						$this->position = $this->length + $offset;
						return true;
					} else {
						return false;
					}
				}


				default: {
					return false;
				}
			}
		}


		public function url_stat() {
			return Array();
		}


		public function stream_stat() {
			return Array();
		}


		public function stream_close() {
			return true;
		}


		public function stream_read($count) {
			$result = bytes_substr($this->data, $this->position, $count);
			$this->position += $count;
			return $result;
		}


		public function stream_write($inputData) {
			$inputDataLength = bytes_strlen($inputData);

		    $dataLeft = bytes_substr($this->data, 0, $this->position);
			$dataRight = bytes_substr($this->data, $this->position + $inputDataLength);

			$this->data = $dataLeft . $inputData . $dataRight;

			$this->position += $inputData;
			return $inputDataLength;
		}


		public function getProtocol() {
			return $this->scheme . "://";
		}

		static public function registerStream($scheme) {
			$config = mainConfiguration::getInstance();
			$filepath = $config->includeParam('system.kernel.streams') . "{$scheme}/{$scheme}Stream.php";
			if(file_exists($filepath)) {
				require $filepath;
				if(!stream_wrapper_register($scheme, "{$scheme}Stream")) {
					throw new coreException("Failed to register stream \"{$scheme}\"");
				}
			} else {
				throw new coreException("Can't locate file \"{$filepath}\"");
			}
		}


		public static function protectParams($param) {
			return str_replace("/", "&#2F;", $param);
		}


		public static function unprotectParams($param) {
			return str_replace("&#2F;", "/", $param);
		}

		public static function getCalledStreams() {
			$lines_arr = Array();
			$total_time = 0;
			$dbConnection = ConnectionPool::getInstance()->getConnection();
			$queriesCount = $dbConnection->getQueriesCount();
			foreach(self::$callLog as $callInfo) {
				list($url, $time) = $callInfo;
				$total_time += $time;
				$lines_arr[] = array(
					'attribute:generation-time'	=> $time,
					'node:url'					=> $url
				);
			}
			$block_arr = array('nodes:call' => $lines_arr);

			$dom = new DOMDocument('1.0', 'utf-8');
			$dom->formatOutput = XML_FORMAT_OUTPUT;
			$rootNode = $dom->createElement("streams-call");
			$rootNode->setAttribute('total-time', $total_time);
			if (isset($_GET['show-something'])) {
				$rootNode->setAttribute('queries-count', $queriesCount);
			}
			$dom->appendChild($rootNode);

			$xmlTranslator = new xmlTranslator($dom);
			$xmlTranslator->translateToXml($rootNode, $block_arr);

			return $dom->saveXml();
		}

		public static function reportCallTime($path, $time) {
			foreach(self::$callLog as &$callInfo) {
				$callInfoPath = explode("?", $callInfo[0]);
				if($callInfoPath[0] == $path) {
					$callInfo[1] = $time;
				}
			}
		}

		/**
		 * Добавляет запись в лог вызовов.
		 * @param array $line информация о вызове array(callName, executionTime)
		 */
		public static function addLineCallLog(array $line) {
			self::$callLog [] = $line;
		}

		protected function isValidOffset($offset) {
			return ($offset >= 0) && ($offset < $this->length);
		}


		protected function translateToXml() {
			$args = func_get_args();
			$res = $args[0];

			if($this->isJson) {
				return $this->translateToJSON($res);
			}

			if(isset($res['plain:result'])) {
				return $res['plain:result'];
			}

			$dom = new DOMDocument("1.0", "utf-8");
			$dom->formatOutput = XML_FORMAT_OUTPUT;

			$rootNode = $dom->createElement("udata");
			$dom->appendChild($rootNode);

			$rootNode->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');

			$xslTranslator = new xmlTranslator($dom);
			$xslTranslator->translateToXml($rootNode, $res);

			$executionTime = number_format(microtime(true) - $this->start_time, 6);
			$rootNode->setAttribute('generation-time', $executionTime);
			self::reportCallTime($this->getProtocol() . $this->path, $executionTime);

			if($this->transform) {
				return $this->applyXslTransformation($dom, $this->transform);
			}

			return $dom->saveXml();
		}


		protected function applyXslTransformation(DOMDocument $dom, $xslFilePath) {
			$config = mainConfiguration::getInstance();
			if ($resourcesDir = cmsController::getInstance()->getResourcesDirectory()) {
				$templatePath = $resourcesDir . "/xslt/";
			} else {
				$templatePath = $config->includeParam('templates.xsl');
			}

			$filePath = $templatePath . $xslFilePath;
			if(is_file($filePath) == false) {
				throw new publicException("Udata trasform xsl-template was not found \"{$filePath}\"");
			}

			$xsltDom = DomDocument::load($filePath, DOM_LOAD_OPTIONS);
			if ( $xsltDom === false && !defined("DEBUG") && function_exists("libxml_get_last_error") ) {
				throw new libXMLErrorException(libxml_get_last_error());
			}

			$xslt = new xsltProcessor;
			$xslt->registerPHPFunctions();
			$xslt->importStyleSheet($xsltDom);
			return $xslt->transformToXML($dom);
		}


		protected function parsePath($path) {

			$protocol = $this->getProtocol();
			$path = substr($path, strlen($protocol));

			$parsed_url = parse_url($path);
			$realPath = $parsed_url['path'];
			if(substr($realPath, -5) == '.json') {
				$realPath = substr($realPath, 0, strlen($realPath) - 5);
				$this->isJson = true;
			} else $this->isJson = false;

			$this->path = $realPath;

			self::$callLog[] = array($protocol . $path, false);

			if($params = getArrayKey($parsed_url, 'query')) {
				parse_str($params, $params_arr);
				$this->params = $params_arr;

				$_REQUEST = array_merge($_REQUEST, $params_arr);

				if(isset($params_arr['expire'])) {
					$this->expire = getArrayKey($params_arr, 'expire');
				}

				if(isset($params_arr['transform'])) {
					$this->transform = getArrayKey($params_arr, 'transform');
				}

			}

			if(!$this->expire) {
				$config = mainConfiguration::getInstance();

				if ($config->get('cache', 'streams.cache-enabled')) {
					$expire = (int) $config->get('cache', 'streams.cache-lifetime');
					if ($expire > 0) {
						$this->expire = $expire;

					}
				}
			}
			
			if (self::$allowExtendedOptions) {

				$extendedGroups = array();
				if (isset($this->params['extGroups']) && !empty($this->params['extGroups'])) {
					$extendedGroups = explode(',', $this->params['extGroups']);
					$extendedGroups = array_unique(array_map('trim', $extendedGroups));
				}

				$extendedProps = array();
				if (isset($this->params['extProps']) && !empty($this->params['extProps'])) {
					$extendedProps = explode(',', $this->params['extProps']);
					$extendedProps = array_unique(array_map('trim', $extendedProps));
				}

				def_module::setMacrosExtendedResult($extendedProps, $extendedGroups);
			
			}

			return $this->path;
		}


		protected function normalizeString($str) {
			$str = urldecode($str);

			if(!preg_match("/[\x{0000}-\x{FFFF}]+/u", $str)) {
				$str = iconv("CP1251", "UTF-8//IGNORE", $str);
			}

			return $str;
		}

		protected function setData($data) {
		    if(!$data) {
		        return false;
		    }

		    $this->data = $data;
		    $this->length = bytes_strlen($data);
		    return true;
		}

		protected function setDataError($errorCode) {
			$data = Array(
				'error' => Array(
					'attribute:code' => $errorCode,
					'node:message' => getLabel('error-' . $errorCode)
				)
			);
			$data = self::translateToXml($data);
			$this->setData($data);
			return true;
		}


		protected function translateToJSON($data) {
			$translator = new jsonTranslator;
			return $translator->translateToJson($data);
		}

		/**
		 * Удаляет из запроса протокола хеш, добавляемый в
		 * cmsController::executeStream().
		 * Необходим для более корректного кеширования
		 * протоколов.
		 * @param string $path запрос протокола
		 * @return mixed
		 */
		protected function removeHash($path) {
			if (!is_string($path)) {
				return $path;
			}
			$cleanPath = preg_replace('/([\?|\&]umiHash=\S{32})/', '', $path);
			if (is_null($cleanPath)) {
				return $path;
			}
			return $cleanPath;
		}
	};
?>