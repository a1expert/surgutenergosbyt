<?php

	class umiTemplaterXSLT extends umiTemplater implements IFullResult {
		private $domXSL;
		private $domXML;
		private $additionalVariables;

		/**
		 * Выполняет траснформацию шаблона, используя $variables для формирования XML с данными
		 * @param mixed $variables
		 * @param mixed $content
		 * @return string
		 */
		public function parse($variables, $content = null) {
			if (is_null($this->domXSL)) {
				$this->domXSL = $this->prepareXSL($this->templatesSource);
			}

			$domXML = ($this->domXML instanceof DOMDocument) ? $this->domXML : $this->prepareXML($variables);

			$xslt = new xsltProcessor;
			$xslt->registerPHPFunctions();

			if (defined("UMI_XSLT_PROFILER_LOG") && version_compare(PHP_VERSION,'5.3.0','>=')) {
				$xslt->setProfiling(UMI_XSLT_PROFILER_LOG);
			}

			if ( !defined("DEBUG") ) {
				set_error_handler(array(__CLASS__, "errorHandler"));
				$oldErrorHandler = error_reporting(~E_STRICT);
			}

			$xslt->importStyleSheet($this->domXSL);

			// System variables
			if ($currentTemplate = cmsController::getInstance()->detectCurrentDesignTemplate()) {
				$sysVars = array(
					'template-name' => $currentTemplate->getName(),
					'template-resources' => $currentTemplate->getResourcesDirectory(true)
				);
				$this->addRequestParams($xslt, $sysVars);
			}

			// Additional variables
			if (is_array($this->additionalVariables)) {
				$this->addRequestParams($xslt, $this->additionalVariables);
			}

			$this->addRequestParams($xslt, $_COOKIE);
			$this->addRequestParams($xslt, $_REQUEST);
			$this->addRequestParams($xslt, $_SERVER, "_");

			$res =  $xslt->transformToXML($domXML);

			if(!defined("DEBUG")) {
				error_reporting($oldErrorHandler);
				return restore_error_handler();
			}

			return $res;
		}

		/**
		* Добавляет массив дополнительных параметров для использования в шаблоне
		* @param array $vars
		*/
		public function setAdditionalVariables(array $vars) {
			$this->additionalVariables = $vars;
		}

		/**
		 * @static
		 * Для XSLT-шаблонизатора метод возвращает
		 * массив в виде num => templateURI (file://path/to/template#fragment)
		 *
		 * @param string $templatesSource - источник шаблонов
		 * @return array
		 */
		public static function getTemplates($templatesSource) {
			$args = func_get_args();
			unset($args[0]);
			$result = array();
			foreach ($args as $arg) {
				$result[] = 'file://' . $templatesSource . "#" . $arg;
			}
			return $result;
		}

		/**
		 * @static
		 * Не актуален для XSLT-шаблонизатора,
		 * возвращает пустой массив для соблюдения интерфейса
		 *
		 * @param $templatesSource
		 * @return array
		 */
		public static function loadTemplates($templatesSource) {
			return array();
		}

		/**
		 * @deprecated
		 * Оставлено для обратной совместимости
		 *
		 * @param DOMDocument $xmlDocument
		 */
		public function setXmlDocument(DOMDocument $xmlDocument) {
			$this->domXML = $xmlDocument;
		}

		protected function prepareXML($variables) {
			if ($variables instanceof DOMDocument) return $variables;

			$domXML = new DOMDocument("1.0", "utf-8");
			$domXML->formatOutput = XML_FORMAT_OUTPUT;
			$rootNode = $domXML->appendChild($domXML->createElement("result"));
			$rootNode->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');

			$translator = new xmlTranslator($domXML);
			$translator->translateToXml($rootNode, $variables);

			return $domXML;
		}

		/**
		 * Подготавливает шаблон
		 *
		 * @param mixed $template - DOMDocument, либо путь к шаблону
		 * @return DOMDocument
		 * @throws coreException
		 */
		protected function prepareXSL($templatesSource) {
			if (!is_file($templatesSource)) {
				throw new coreException('Not found template "' . $templatesSource . '".', 10004);
			}

			$domXSL = new DOMDocument('1.0', 'utf-8');
			$domXSL->resolveExternals = true;
			$domXSL->substituteEntities = true;
			$domXSL->load($templatesSource, DOM_LOAD_OPTIONS);

			return $domXSL;
		}

		/**
		 * Передает массив параметров в шаблон
		 *
		 * @param xsltProcessor $xslt
		 * @param $array - массив параметров, передаваемых в шаблон
		 * @param string $prefix - префикс для имен параметров
		 */
		protected function addRequestParams(xsltProcessor $xslt, $array, $prefix = "") {
			foreach($array as $key => $val) {
				$key = strtolower($key);
				if(!is_array($val)) {
					// Fix to prevent warning on some strings
					if(strpos($val, "'") !== false && strpos($val, "\"") !== false) {
						$val = str_replace("'", "\\\"", $val);
					}
					$key = str_replace(array(':'), array(''), $key);
					$xslt->setParameter("", $prefix . $key, $val);
				} else {
					$this->addRequestParams($xslt, $val, $prefix . $key . ".");
				}
			}
		}

		/**
		 * Обработчик ошибок для XSLT
		 * Бросает исключение, для обработки ошибки через обработчик исключений
		 *
		 * @throws libXMLErrorException
		 */
		public static function errorHandler($errno, $errstr, $errfile, $errline, $e) {
			if( defined("DEBUG") ||
				!function_exists("libxml_get_last_error") ||
				$errline != 0 ||
				$errno != 2
			) return;

			throw new libXMLErrorException(libxml_get_last_error());
		}
	}
?>