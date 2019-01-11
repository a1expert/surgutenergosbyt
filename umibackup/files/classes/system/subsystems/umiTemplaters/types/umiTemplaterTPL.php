<?php
	class umiTemplaterTPL extends umiTemplater {
		/**
		 * Кэш загруженных шаблонов в памяти
		 * @static
		 * @var array
		 */
		protected static $templatesCache = array(); // templates cache
		protected static $callStack;
		protected static $currentCallStackNode;
		protected static $callStackMaxStringLength = 100;

		protected $parseLevel = 0;
		protected static $maxParseLevel = 4;

		protected $executeOnlyAllowedMacroses;

		/**
		 * Стэк с результатами выполнения макросов
		 * @static
		 * @var array
		 */
		protected static $msResultStack = array();

		/**
		 * Короткие алиасы к макросам
		 * Эти макросы выполняются в момент выполнения коротких макросов
		 * @static
		 * @var array
		 */
		protected static $shortAliases = array(
			//'%content%'             => array('macros_content'),
			'%menu%'                => array('macros_menu'),
			'%header%'              => array('macros_header'),
			'%pid%'                 => array('macros_returnPid'),
			'%parent_id%'           => array('macros_returnParentId'),
			'%pre_lang%'            => array('macros_returnPreLang'),
			'%curr_time%'           => array('macros_curr_time'),
			'%domain%'              => array('macros_returnDomain'),
			'%domain_floated%'      => array('macros_returnDomainFloated'),
			'%system_build%'        => array('macros_systemBuild'),
			'%title%'               => array('macros_title'),
			'%keywords%'            => array('macros_keywords'),
			'%describtion%'         => array('macros_describtion'),
			'%description%'         => array('macros_describtion'),
			'%adm_menu%'            => array('macros_adm_menu'),
			'%adm_navibar%'         => array('macros_adm_navibar'),
			'%skin_path%'           => array('macros_skin_path'),
			'%ico_ext%'             => array('macros_ico_ext'),
			'%current_user_id%'     => array('macros_current_user_id'),
			'%current_version_line%'=> array('macros_current_version_line'),
			'%context_help%'        => array('macros_help'),
			'%current_alt_name%'    => array('macros_current_alt_name')
		);

		private function prepareVariableForCallStack($value) {
			if (!is_scalar($value)) {
				return gettype($value);
			} elseif (is_string($value) && strlen($value) > self::$callStackMaxStringLength) {
				return mb_substr($value, 0, self::$callStackMaxStringLength). "...";
			}
			return $value;
		}

		/**
		 * Записывает в стек лог начала парсинга
		 * @param $variables
		 * @param $content
		 * @return DOMElement
		 */
		private function startParseCallReport($variables, $content) {
			if (!self::isEnabledCallStack()) return null;

			$callStack = $this->getCallStack();

			$content = $this->prepareVariableForCallStack($content);
			$callPoint = $callStack->createElement('parse-block');

			$contentNode = $callStack->createElement('parsed-content');
			$contentNode->appendChild($callStack->createCDATASection($content));
			$callPoint->appendChild($contentNode);

			$scopeNode = $callStack->createElement('scope');
			foreach ($variables as $var => $val) {
				$val = $this->prepareVariableForCallStack($val);
				$varNode = $callStack->createElement('var');
				$varNode->setAttribute('name', $var);
				$varNode->setAttribute('type', gettype($val));
				$varNode->appendChild($callStack->createCDATASection($val));
				$scopeNode->appendChild($varNode);
			}
			$callPoint->appendChild($scopeNode);
			$callPoint->setAttribute('start', microtime(true));

			if (!$parentStackNode = self::$currentCallStackNode) {
				$parentStackNode = $callStack->documentElement;
			}
			return self::$currentCallStackNode = $parentStackNode->appendChild($callPoint);
		}

		/**
		 * Записывает в стек лог конца парсинга
		 * @param DOMElement|null $callPoint
		 * @return DOMElement
		 */
		private function stopCallReport($callPoint) {
			if (!self::isEnabledCallStack()) return null;
			if (!$callPoint instanceof DOMElement) return null;

			if ($startTime = $callPoint->getAttribute('start')) {
				$startTime = floatval($startTime);
				$callPoint->setAttribute('call-time', number_format(microtime(true) - $startTime, 5));
			}
			self::$currentCallStackNode = $callPoint->parentNode;
			return $callPoint;
		}


		/**
		 * Записывает в стек лог начала запуска макроса
		 * @param (string) $macros
		 * @return DOMElement
		 */
		private function startMacrosExecuteCallReport($macros) {
			if (!self::isEnabledCallStack()) return null;
			$callStack = $this->getCallStack();

			$callPoint = $callStack->createElement('macros');
			$callPoint->setAttribute('name', $macros);

			$callPoint->setAttribute('start', microtime(true));

			if (!$parentStackNode = self::$currentCallStackNode) {
				$parentStackNode = $callStack->documentElement;
			}
			return self::$currentCallStackNode = $parentStackNode->appendChild($callPoint);
		}

		/**
		 * Установить режим работы, когда запускаются только разрешенные макросы
		 * @param array|null $allowedMacroses список разрешенных макросов
		 * Пример списка array('menu', 'content/get_page_url', ...)
		 */
		public function executeOnlyAllowedMacroses($allowedMacroses) {
			$this->executeOnlyAllowedMacroses = $allowedMacroses;
		}

		/**
		 * Проверяет, можно ли запускать макрос
		 * Если не задан executeOnlyAllowedMacroses, всегда возвращает true
		 * @param $macrosURI
		 * @return bool
		 */
		private function isExecuteMacrosAllowed($macrosURI) {
			if (!is_array($this->executeOnlyAllowedMacroses) || !count($this->executeOnlyAllowedMacroses)) {
				return true;
			}
			return in_array($macrosURI, $this->executeOnlyAllowedMacroses);
		}

		/**
		 * Парсит $content, используя $variables
		 * @param mixed $variables
		 * @param mixed $content
		 * @return string
		 */
		public function parse($variables, $content = null) {
			if (empty($content)) return strval($content);

			if (strpos($content, '%') === false && strpos($content, '[ms_') === false) return $content;

			$callPoint = $this->startParseCallReport($variables, $content);

			// прерываем глубокий рекурсивный парсинг
			if ($this->parseLevel > self::$maxParseLevel) {
				return $content;
			}

			// отключаем XSLT-режим работы макросов
			$oldResultMode = def_module::isXSLTResultMode(false);
			// парсим короткие макросы: переменные из $variables, макросы текущего скопа, глобальные макросы, короткие алиасы
			if ($this->scopeElementId) {
				$content = str_replace('%id%', $this->scopeElementId, $content);
			}

			$oldContent = $content;
			$content = $this->parseShortMacroses($content, $variables);

			// парсим сложные макросы
			$content = $this->parseCompleteMacroses($content, $variables);

			// восстанавливаем старый режим работы макросов
			def_module::isXSLTResultMode($oldResultMode);

			// прерываем парсинг, если контент не изменился за итерацию
			if ($oldContent === $content) {
				$this->stopCallReport($callPoint);
				return $content;
			}

			// заменяем uid макросов на их результат
			if (strpos($content, '[ms_') !== false) {
				$content = str_replace(array_keys(self::$msResultStack), array_values(self::$msResultStack), $content);
			}

			if (strpos($content, '%') !== false) {
				$this->parseLevel++;
				$content = $this->parse($variables, $content);
			}

			$this->stopCallReport($callPoint);

			return $content;
		}

		/**
		 * @static
		 * Загружает все шаблоны из указанного источника и возвращает шаблоны с указанными именами
		 * @param string $templatesSource - источник шаблонов
		 * @return array - список запрошенных шаблонов
		 */
		public static function getTemplates($templatesSource) {
			$result = array();
			$templates = func_get_args();
			unset($templates[0]);
			$allTemplates = self::loadTemplates($templatesSource);

			if (!count($templates)) return $allTemplates;

			foreach ($templates as $name) {
				$result[] = isset($allTemplates[$name]) ? $allTemplates[$name] : "";
			}

			return $result;
		}


		/**
		 * @static
		 * Подключает и возвращает все шаблоны из файла-источника
		 * Использует кэширование загруженных ранее источников
		 *
		 * @param string $templatesSource - файл с шаблонами
		 * @return array - все шаблоны из источника в виде array('tpl_name' => tpl_content, ..)
		 *
		 * @throws publicException - если шаблон не найден
		 */
		public static function loadTemplates($templatesSource) {
			if (empty($templatesSource)) return array();

			$realPath = realpath($templatesSource);
			$hash = md5($realPath);
			if (isset(self::$templatesCache[$hash])) return self::$templatesCache[$hash];


			if (!is_file($realPath)) {
				throw new publicException("Не найден шаблон {$templatesSource}", 2);
			}

			$FORMS = array();

			ob_start();
			include $realPath;
			$templateContent = ob_get_clean();

			if (!count($FORMS) && strlen($templateContent)) {
				$FORMS['common'] = $templateContent;
			}

			return self::$templatesCache[$hash] = $FORMS;
		}


		/**
		 * Парсит короткие макросы вида %macros%
		 * @param string $content
		 * @param array $variables - переменные для парсинга блока
		 * @return mixed
		 */
		protected function parseShortMacroses($content, array $variables) {
			if (strpos($content, '%') === false) return $content;
			// clear comments
			$content = preg_replace("/<!--.*?-->/mu", "", $content);
 
			if(preg_match_all("/%[A-z0-9][A-z0-9_-]{1,}%/m", $content, $matches)) {
				$macroses = array_unique($matches[0]);

				$fromReplace = array();
				$toReplace = array();
				foreach ($macroses as $macros) {
					// fix urlencoded entites (%f0,%67, ...)
					$entity = rtrim($macros, '%');

					if (wa_strlen($entity) == 3 && urldecode($entity) !== $entity) continue;

					$fromReplace[] = $macros;
					$toReplace[] = $this->executeShortMacros($macros, $variables);
				}

				$content = str_replace($fromReplace, $toReplace, $content);
			}

			return $content;
		}

		protected function generateMSResultUID() {
			static $nextNum = 0;
			return '[ms_' . ++$nextNum . ']';
		}

		protected function setMSResult($resultUID, $result) {
			self::$msResultStack[$resultUID] = $result;
		}


		protected function getMSResult($resultUID) {
			return isset(self::$msResultStack[$resultUID]) ? self::$msResultStack[$resultUID] : "";
		}

		/**
		 * Выводит переменные из текущего scope по системному шаблону
		 * @param array $variables - переменные для блока
		 * @return string
		 */
		protected function printScopeVariables(array $variables) {
			if ($scopeObject = $this->getScopeObject()) {
				$scopeFields = $scopeObject->getType()->getAllFields();
				foreach ($scopeFields as $field) {
					$name = $field->getName();
					if (!isset($variables[$name])) {
						$variables[$name] = $scopeObject->getValue($name);
					}
				}
			}

			// parse scope
			if ($resourcesDir = cmsController::getInstance()->getResourcesDirectory()) {
				$templateSrc = $resourcesDir . "/tpls/system/reflection.tpl";
			} else{
				$templateSrc = CURRENT_WORKING_DIR . "/tpls/system/reflection.tpl";
			}
			list($block, $varLine, $macroLine) = $this->getTemplates($templateSrc, "scope_dump_block", "scope_dump_line_variable", "scope_dump_line_macro");
			$assembledLines = "";

			foreach($variables as $name => $value) {
				if($name == "#meta") continue;
				if(is_array($value)) {
					$tmp = str_replace("%name%", $name, $macroLine);
				} else {
					$tmp = $varLine;
					$tmp = str_replace("%name%", $name, $tmp);
					$tmp = str_replace("%type%", gettype($value), $tmp);
					$tmp = str_replace("%value%", htmlspecialchars($value), $tmp);
				}
				$assembledLines .= $tmp;
			}

			if(isset($scopeVariables["#meta"])) {
				$scopeName = isset($scopeVariables["#meta"]["name"]) ? $scopeVariables["#meta"]["name"] : "";
				$scopeFile = isset($scopeVariables["#meta"]["file"]) ? $scopeVariables["#meta"]["file"] : "";
			} else {
				$scopeName = "";
				$scopeFile = "";
			}

			$block = str_replace("%lines%", $assembledLines, $block);
			$block = str_replace("%block_name%", $scopeName, $block);
			$block = str_replace("%block_file%", $scopeFile, $block);
			$block = preg_replace("/%[A-z0-9_]+%/i", "", $block);
			return $block;
		}

		/**
		 * Обрабатывает короткие макросы, возвращает result uid,
		 * либо результат работы макроса, если макрос не может вернуть вложенных макросов
		 * @param string $macros
		 * @param array $variables
		 * @return string
		 */
		protected function executeShortMacros($macros, array $variables) {
			$var = trim($macros, '%');

			$macrosResult = $macros;

			if ($macros == '%template_resources%') {
				return cmsController::getInstance()->getResourcesDirectory(true);
			} elseif ($macros == '%template_name%') {
				if ($template = cmsController::getInstance()->detectCurrentDesignTemplate()) {
					return $template->getName();
				}
				return "";
			} elseif ($macros == '%scope%') {
				return $this->printScopeVariables($variables);
			} elseif (array_key_exists($var, $variables) && !is_array($variables[$var])) {
				// если это переменная из $variables
				$macrosResult = strval($variables[$var]);
			} elseif (array_key_exists($var, cmsController::getInstance()->langs) && !is_array(cmsController::getInstance()->langs[$var])) {
				// макрос из langs
				$macrosResult = cmsController::getInstance()->langs[$var];
			} elseif (isset(self::$shortAliases[$macros])) {
				// если это короткий алиас
				$macrosInfo = self::$shortAliases[$macros];
				if (isset($macrosInfo[0])) {
					$module = $macrosInfo[0];
					$method = isset($macrosInfo[1]) ? $macrosInfo[1] : null;
					$macrosArgs = (isset($macrosInfo[2]) && is_array($macrosInfo[2])) ? $macrosInfo[2] : array();
					return $resultUID = $this->executeCompleteMacros($module, $method, $macrosArgs, $variables);
				}
			} elseif (($scopeObject = $this->getScopeObject()) instanceof umiObject) {
				//  специальные макросы для скопа
				if ($var == 'block-element-id') {
					$macrosResult = $this->scopeElementId > 0 ? $this->scopeElementId : cmsController::getInstance()->getCurrentElementId();
				} elseif ($var == 'block-object-id') {
					$macrosResult = $this->scopeObjectId;
				} elseif ($scopeObject->getPropByName($var) instanceof umiObjectProperty) {
					// если это переменная из скопа
					$val = $scopeObject->getValue($var);
					if(is_object($val)) {
						switch (true) {
							case $val instanceof iUmiDate :
								$macrosResult = $val->getFormattedDate("U");
							break;
							case $val instanceof iUmiFile :
								$macrosResult = $val->getFilePath(true);
							break;
							case $val instanceof iUmiObject:
							case $val instanceof iUmiHierarchyElement:
								$macrosResult = $val->getName();
							break;
						}
					} elseif (is_array($val)) {
						$sz = sizeof($val);
						$macrosResult = "";
						for($i = 0; $i < $sz; $i++) {
							$cval = $val[$i];

							if(is_numeric($cval)) {
								if($obj = umiObjectsCollection::getInstance()->getObject($cval)) {
									$cval = $obj->getName();
								} else continue;
							}

							if($cval instanceof umiHierarchyElement) {
								$cval = $cval->getName();
							}

							$macrosResult .= $cval;
							if($i < ($sz - 1)) $macrosResult .= ", ";
						}
					} else {
						$macrosResult = $val;
					}

				}

			}

			if ($macrosResult === $macros) {
				return $macros;
			}

			// запускаем рекурсивный парсинг вложенных макросов
			$this->parseLevel++;
			$macrosResult = $this->parse($variables, $macrosResult);
			$this->parseLevel--;

			$resultUID = $this->generateMSResultUID();
			$this->setMSResult($resultUID, $macrosResult);

			return $resultUID;
		}

		protected function executeCompleteMacros($module, $method = null, $args = array(), array $variables) {
			$controller = cmsController::getInstance();
			$resultUID = $this->generateMSResultUID();
			$macrosResult = "%" . $module . " " . $method . "(" . implode("," , $args) .")%";

			// заменяем macros uid на реальное значение в аргументах
			$countArgs = count($args);
			for ($i = 0; $i < $countArgs; $i++) {
				if (isset(self::$msResultStack[$args[$i]])) {
					$args[$i] = self::$msResultStack[$args[$i]];
				} elseif (strpos($args[$i], '[ms_') !== false) {
					$args[$i] = str_replace(array_keys(self::$msResultStack), array_values(self::$msResultStack), $args[$i]);
				}
			}

			// если не пришел метод, пытаемся запустить $module как функцию из def_macroses
			if (is_null($method) && is_callable($module)) {
				// TODO: зарефакторить все макросы из def_macroses
				$macrosResult = $module($args);
			} else {
				$moduleInst = null;

				if ($module == "core" || $module == "system" || $module == "custom") {
					$moduleInst = &system_buildin_load($module);
				} elseif (system_is_allowed($module, $method)) {
					$moduleInst = $controller->getModule($module);
				} elseif (defined('DEBUG') && DEBUG) {
					$macrosResult = "You are not allowed to execute {$module}/{$method}";
				} else {
					$macrosResult = "";
				}

				if ($moduleInst) {
					try {
						$macrosResult = $moduleInst->cms_callMethod($method, $args);
					} catch (publicException $e) {
						$macrosResult = $e->getMessage();
					}
				}
			}

			// запускаем рекурсивный парсинг вложенных макросов
			$this->parseLevel++;
			$macrosResult = $this->parse($variables, $macrosResult);
			$this->parseLevel--;

			$this->setMSResult($resultUID, $macrosResult);

			return $resultUID;
		}

		protected function parseCompleteMacroses($content, array $variables) {
			if (strpos($content, '%') === false) return $content;

			if (preg_match_all("/%([A-z0-9]+)\s+([A-z0-9_]+)\s*\(([^%]*)\)%/mu", $content, $matches, PREG_SET_ORDER)) {
				$executed = array();

				foreach ($matches as $macrosInfo) {
					$macros = $macrosInfo[0];

					// фильтруем одинаковые макросы
					if (isset($executed[$macros])) continue;

					$module = $macrosInfo[1];
					$method = $macrosInfo[2];

					if (!$this->isExecuteMacrosAllowed($module . "/" . $method)) continue;

					$args = trim($macrosInfo[3]);
					$args = strlen($args) ? explode(",", $args) : array();

					$countArgs = count($args);
					for ($i = 0; $i < $countArgs; $i++) {
						$args[$i] = trim($args[$i], "'\" ");
					}
					$callPoint = $this->startMacrosExecuteCallReport($macros);
					$resultUID = $this->executeCompleteMacros($module, $method, $args, $variables);
					$this->stopCallReport($callPoint);
					$content = str_replace($macros, $resultUID, $content);
					$executed[$macros] = 1;
				}
			}

			return $content;
		}

		/**
		 * Возвращает стек вызовов для TPL-шаблонизатора
		 * @return DOMDocument
		 */
		private function getCallStack() {
			if (!self::$callStack) {
				self::$callStack = new DOMDocument('1.0', 'utf-8');
				self::$callStack->formatOutput = XML_FORMAT_OUTPUT;
				self::$callStack->appendChild(self::$callStack->createElement('call-stack'));
			}
			return self::$callStack;
		}

		/**
		 * Возвращает стек вызовов для TPL-шаблонизатора в формате xml
		 * @return string
		 */
		public function getCallStackXML() {
			if ($this->isEnabledCallStack()) {
				return $this->getCallStack()->saveXML();
			}

			return $this->disabledCallStackError();
		}
	}

?>