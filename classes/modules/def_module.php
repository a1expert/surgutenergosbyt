<?php
abstract class def_module {
	public static
		$templates_cache = array(), $noRedirectOnPanic = false, $defaultTemplateName = 'default', $xsltResultMode = null;

	/**
	 * Список макросов с выводом расширенных полей для XSLT
	 * @var array
	 */
	protected static $macrosesXsltExtendedResult = array(array(), array());
	public $max_pages = 10, $isSelectionFiltered = false;
	public $pid, $FORMS_CACHE = array(), $FORMS = array(), $per_page = 20;

	public $dataType, $actionType, $currentEditedElementId = false;
	public $__classes = array(), $libsCalled = array();
	public $common_tabs = null, $config_tabs = null;
	protected $errors = array();
	protected $errorPage = '';

	protected $cmsController;
	protected $regedit;
	protected $umiHierarchyTypesCollection;
	protected $umiObjectsCollection;
	protected $umiObjectTypesCollection;
	protected $domainsCollection;
	protected $mainConfiguration;
	protected $umiFieldsCollection;
	protected $umiFieldTypesCollection;
	protected $umiLinksHelper;
	protected $umiTypesHelper;
	protected $umiPropertiesHelper;

	protected function __implement($class_name) {
		$this->__classes[] = $class_name;

		$cm = get_class_methods($class_name);

		if(is_null($cm)) return;
		$fn = "onInit";
		if(in_array($fn, $cm)) $this->$fn();

		// invoke onImplement public method :
		$fn = "onImplement";
		if (in_array($fn, $cm) && class_exists('ReflectionClass')
		&& class_exists('ReflectionMethod') && class_exists('ReflectionException')) {
			try {
				$oRfClass = new ReflectionClass($class_name);
				$oRfMethod = $oRfClass->getMethod($fn);
				if ($oRfMethod instanceof ReflectionMethod) {
					if ($oRfMethod->isPublic()) {
						eval('$res = ' . $class_name . '::' . $fn . '();');
					}
				}
			} catch (ReflectionException $e) {}
		}

	}

	/**
	 * Устанавливает список дополнительных полей и групп для результатов парсинга макросов
	 * @param array $extProps имена полей
	 * @param array $extGroups имена групп
	 */
	public static function setMacrosExtendedResult($extProps = array(), $extGroups = array()) {
		self::$macrosesXsltExtendedResult = array($extProps, $extGroups);
	}

	/**
	 * Возвращает список доп полей для результата макроса
	 * @return array массив имен полей
	 */
	public static function getMacrosExtendedProps() {
		return self::$macrosesXsltExtendedResult[0];
	}

	/**
	 * Возвращает список доп групп для результата макроса
	 * @return array массив имен групп
	 */
	public static function getMacrosExtendedGroups() {
		return self::$macrosesXsltExtendedResult[1];
	}

	public function __admin() {
		if($this->cmsController->getCurrentMode() == "admin" && !class_exists("__" . get_class($this))) {
			$this->__loadLib("__admin.php");
			$this->__implement("__" . get_class($this));
		}
	}

	public function __call($method, $args) {
		foreach($this->__classes as $className) {
			$classMethods = get_class_methods($className);
			if(is_null($classMethods)) continue;

			if(in_array($method, $classMethods)) {
				$params = "";
				if(is_array($args)) {
					$sz = sizeof($args);
					for($i = 0; $i < $sz; $i++) {
						$params .= '$args[' . $i . ']';
						if($i != $sz-1) $params .= ", ";
					}
				}
				$result = false;
				eval('$result = ' . $className . '::' . $method . '(' . $params . ');');
				return $result;
			}
		}

		$cmsController = $this->cmsController;
		$cmsController->langs[get_class($this)][$method] = "Ошибка";

		/** @var content $contentModule */
		$contentModule = $cmsController->getModule("content");
		if (!$contentModule) {
			return '';
		}

		if ($cmsController->getCurrentMode() == "admin") {
			return "Вызов несуществующего метода.";
		}

		if ($cmsController->getCurrentModule() == get_class($this) && $cmsController->getCurrentMethod() == $method) {
			return $contentModule->gen404();
		} else {
			return "";
		}
	}

	public function __construct() {
        $this->cmsController = cmsController::getInstance();
        $this->regedit = regedit::getInstance();
        $this->umiHierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
        $this->umiObjectsCollection = umiObjectsCollection::getInstance();
        $this->umiObjectTypesCollection = umiObjectTypesCollection::getInstance();
        $this->domainsCollection = domainsCollection::getInstance();
        $this->mainConfiguration = mainConfiguration::getInstance();
        $this->umiFieldsCollection = umiFieldsCollection::getInstance();
        $this->umiFieldTypesCollection = umiFieldTypesCollection::getInstance();
        $this->umiLinksHelper = umiLinksHelper::getInstance();
		$this->umiTypesHelper = umiTypesHelper::getInstance();
		$this->umiPropertiesHelper = umiPropertiesHelper::getInstance();
		$this->lang = $this->cmsController->getCurrentLang()->getPrefix();
		$this->init();
	}

	public function getSortedModulesList() {
		$priorityList = Array(
			// user modules (priority < 100)
			'events'		=> 1,
				'content'		=> 2,
			'news'			=> 3,
				'menu'		=> 4,
			'forum'			=> 5,
				'blogs20'		=> 6,
			'vote'			=> 7,
				'comments'		=> 8,
			'photoalbum'	=> 9,
				'webforms'		=> 10,
			'dispatches'	=> 11,
				'faq'			=> 12,
			'eshop'			=> 13,
			'emarket'		=> 14,
				'catalog'		=> 15,
			'users'			=> 16,
				'banners'		=> 17,
			'seo'			=> 18,
				'stat'			=> 19,
			'social_networks'	=> 20,
				'exchange'		=> 21,
			// administrative modules (priority > 100)
			'data'			=> 101,
			'config'		=> 102,
			'backup'		=> 103,
			'autoupdate'	=> 104,
			'webo'			=> 105,
			'search'		=> 106,
			'filemanager'	=> 107
		);

		$sysModules = array('config', 'trash' ,'search', 'autoupdate');
		$utilModules = array('data','backup', 'webo', 'filemanager');

		$modulesList = $this->regedit->getList('//modules');
		$permissions = permissionsCollection::getInstance();

		$result = array();
		foreach($modulesList as $module) {
			list( $module ) = $module;
			if ( $permissions->isAllowedModule(false, $module) == false ) {
				continue;
			}
			$priority = isset($priorityList[$module]) ? $priorityList[$module] : 99;
			$result[$module] = $priority;
		}

		$isTrashAllowed = $permissions->isAllowedMethod( $permissions->getUserId(), "data", "trash" );
		if ( system_get_skinName() == "mac" && $isTrashAllowed != false ) {
			$result['trash'] = 999;
		}
		natsort($result);

		foreach($result as $module => $priority) {
			if(in_array($module, $sysModules))
				$type = 'system';
			else if(in_array($module, $utilModules))
				$type = 'util';
			else $type = null;

			$moduleInfo = array();
			$moduleInfo['name'] = $module;
			$moduleInfo['label'] = getLabel("module-" . $module);
			$moduleInfo['type'] = $type;

			$result[$module] = $moduleInfo;
		}

		return $result;
	}

	public function getCommonTabs() {
		$cmsController = $this->cmsController;
		$currentModule = $cmsController->getCurrentModule();
		$selfModule = get_class($this);

		if (($currentModule != $selfModule) && ($currentModule != false && $selfModule != 'users')) return false;
		if (!$this->common_tabs instanceof adminModuleTabs) {
			$this->common_tabs = new adminModuleTabs("common");
		}
		return $this->common_tabs;
	}

	public function getConfigTabs() {
		if ($this->cmsController->getCurrentModule() != get_class($this)) return false;

		if (!$this->config_tabs instanceof adminModuleTabs) {
			$this->config_tabs = new adminModuleTabs("config");
		}
		return $this->config_tabs;
	}

	public function cms_callMethod($method_name, $args) {
		if(!$method_name) return;

		$aArguments = array();
		if(USE_REFLECTION_EXT && class_exists('ReflectionMethod')) {
			try {
				$oReflection   = new ReflectionMethod($this, $method_name);
				$iNeedArgCount = max($oReflection->getNumberOfRequiredParameters(), count($args));
				if($iNeedArgCount) $aArguments = array_fill(0, $iNeedArgCount, 0);
			} catch(Exception $e) {}
		}

		for($i=0; $i<count($args); $i++) $aArguments[$i] = $args[$i];

		if(count($aArguments) && !(empty($args[0]) && sizeof($args) == 1)) {
			return call_user_func_array(array($this, $method_name), $aArguments);
		} else {
			return $this->$method_name();
		}
	}

	//инициализация модуля
	public function init() {
		// подключаем кастомы из ресурсов шаблона
		// TODO: refactoring
		if ($resourcesDir = $this->cmsController->getResourcesDirectory()) {
			$includesFile = realpath($resourcesDir . '/classes/modules') . '/' . get_class($this) . '/class.php';
			if (file_exists($includesFile)) {
				require_once $includesFile;
				$className = get_class($this) . '_custom';
				if (!in_array($className, $this->__classes)) {
					$this->__implement($className);
					new $className($this);
				}
			}
		}

		$includesFile = CURRENT_WORKING_DIR . '/classes/modules/' . get_class($this) . '/includes.php';
		if (file_exists($includesFile) && !defined('SKIP_MODULES_INCLUDES')) {
			require_once $includesFile;
		}
	}

	/**
	 * Производит загрузку общих файлов расширений.
	 * Загрузка производится из директории [имя_модуля]/ext с учётом префикса файлов.
	 */
	public function loadCommonExtension() {
		$extensionFiles = CURRENT_WORKING_DIR . '/classes/modules/' . get_class($this) . '/ext/';
		if (file_exists($extensionFiles)) {
			$files = glob($extensionFiles . '/includes_*.php');
			if (is_array($files)) {
				foreach ($files as $filename) {
					require_once $filename;
				}
			}
			$files = glob($extensionFiles . '/common_*.php');
			if (is_array($files)) {
				foreach ($files as $filename) {
					$fileName = str_replace($extensionFiles . '/', '', $filename);
					$className = str_replace('.php', '', $fileName);
					$this->__loadLib($fileName, $extensionFiles);
					$this->__implement($className);
				}
 			}
		}
	}

	/**
	 * Производит загрузку админских файлов расширений.
	 * Загрузка производится из директории [имя_модуля]/ext с учётом префикса файлов.
	 */
	public function loadAdminExtension() {
		$extensionFiles = CURRENT_WORKING_DIR . '/classes/modules/' . get_class($this) . '/ext/';
		if (file_exists($extensionFiles)) {
			if ($this->cmsController->getCurrentMode() == "admin") {
				$files = glob($extensionFiles . '/admin_*.php');
				if (is_array($files)) {
					foreach ($files as $filename) {
						$fileName = str_replace($extensionFiles . '/', '', $filename);
						$className = str_replace('.php', '', $fileName);
						$this->__loadLib($fileName, $extensionFiles);
						$this->__implement($className);
					}
 				}
			}
		}
	}

	/**
	 * Производит загрузку сайтовых файлов расширений и подключение событий.
	 * Загрузка производится из директории [имя_модуля]/ext с учётом префикса файлов.
	 */
	public function loadSiteExtension() {
		$extensionFiles = CURRENT_WORKING_DIR . '/classes/modules/' . get_class($this) . '/ext/';

		if (file_exists($extensionFiles)) {
			if ($this->cmsController->getCurrentMode() != "admin") {
				$files = glob($extensionFiles . '/site_*.php');
				if (is_array($files)) {
					foreach ($files as $filename) {
						$fileName = str_replace($extensionFiles . '/', '', $filename);
						$className = str_replace('.php', '', $fileName);
						$this->__loadLib($fileName, $extensionFiles);
						$this->__implement($className);
					}
				}
				$files = glob($extensionFiles . '/__events_*.php');
				if (is_array($files)) {
					foreach ($files as $filename) {
						$fileName = str_replace($extensionFiles . '/', '', $filename);
						$className = str_replace('.php', '', $fileName);
						$this->__loadLib($fileName, $extensionFiles);
						$this->__implement($className);
					}
				}
			}
		}
	}

	/**
	 * Проверяет валидность HTTP_REFERER
	 * @internal
	 *
	 * @return bool
	 */
	public static function checkHTTPReferer() {
		preg_match('|^http(?:s)?:\/\/(?:www\.)?([^/:]+)|ui', getServer('HTTP_REFERER'), $matches);
		if (!isset($matches[1]) || count($matches[1])!=1) {
			return false;
		}
		$domainsCollection = domainsCollection::getInstance();
		if ($domainsCollection->getDomainId($matches[1])===false && $domainsCollection->getDomainId('www.'.$matches[1])===false) {
			return false;
		}
		return true;
	}

	public static function install($INFO) {
		$xpath = '//modules/' . $INFO['name'];
		$regedit = regedit::getInstance();

		$regedit->setVar($xpath, $INFO['name']);

		if(is_array($INFO)) {
				foreach($INFO as $var => $module_param) {
						$val = $module_param;
						$regedit->setVar($xpath . "/" . $var, $val);
				}
		}
	}

	public function uninstall() {
		$className = get_class($this);
		$regedit = $this->regedit;
		$defaultModuleAdmin = $regedit->getVal('//settings/default_module_admin');

		if ($defaultModuleAdmin == $className) {
			$regedit->setVal('//settings/default_module_admin', 'content');
		}

		$regedit->getKey('//modules/' . $className);
		$regedit->delVar('//modules/' . $className);

	}

	/**
	* @desc Redirect to $url and terminate current execution
	* @param $url String Url of new location
	* @return void
	*/
	public function redirect($url, $ignoreErrorParam = true) {
		if(getRequest('redirect_disallow')) return;
		if(!$url) $url = $this->pre_lang . "/";
		if($ignoreErrorParam && (isset($this) && $this instanceof def_module)) $url = $this->removeErrorParam($url);

        umiHierarchy::getInstance()->__destruct();
		outputBuffer::current()->redirect($url);
	}

	public function requireSlashEnding() {
		if(getRequest('is_app_user') !== null) {
			return;
		}

		if(getRequest('xmlMode') == "force" || sizeof($_POST) > 0) {
			return;
		}

		if (getRequest('jsonMode') == "force" || sizeof($_POST) > 0) {
			return;
		}

		$uri = getServer('REQUEST_URI');

		if($uri == '/') {
			return;
		}
		$uriInfo = parse_url($uri);

		if (cmsController::getInstance()->getCurrentMode() == "admin") {
			if(substr($uriInfo['path'], -1, 1) != "/") {
				$uri = $uriInfo['path'] . "/";
				if(isset($uriInfo['query']) && $uriInfo['query']) {
					$uri .= "?" . $uriInfo['query'];
				}
				self::redirect($uri);
			}
			return;
		}

		if ($urlSuffix = mainConfiguration::getInstance()->get('seo', 'url-suffix')){
			$pos = strrpos($uriInfo['path'], $urlSuffix);
			if ($pos === false || !($pos + strlen($urlSuffix) == strlen($uriInfo['path']))) {
				if ($uriInfo['path'] == '/') {
					return;
				} else {
					$uri = rtrim($uriInfo['path'], '/') . $urlSuffix;
				}
				if(isset($uriInfo['query']) && $uriInfo['query']) {
					$uri .= "?" . $uriInfo['query'];
				}
				self::redirect($uri);
			}
		}
	}

	/**
	* @desc Подключает дополнительные файлы. Введена, чтобы подключать дополнительные методы в админке.
	* @param $lib String - Filename of libfile
	* @param $path String - Path to directory, where lib file is located
	* @param remember Boolean If true, do not flush cache after next use of this method.
	* @return void
	*/
	protected function __loadLib($lib, $path = "", $remember = false) {
		$lib_path = ($path) ? $path . $lib : "classes/modules/" . get_class($this) . "/" . $lib;
		$path = ($path) ? $lib_path : CURRENT_WORKING_DIR . '/' . $lib_path;

		if (isset($this->FORMS_CACHE[$lib_path])) {
			$FORMS = $this->FORMS_CACHE[$lib_path];
		}
		else {
			if (file_exists($path)) require_once $path;
		}

		if($remember) {
			$this->FORMS = $FORMS;
			$this->FORMS_CACHE[$lib_path] = $FORMS;
		}
		return true;
	}

	public function setHeader($header) {
		$cmsControllerInstance = $this->cmsController;
		$cmsControllerInstance->currentHeader = $header;
	}

	protected function setTitle($title = "", $mode = 0) {
		$cmsControllerInstance = $this->cmsController;
		if($title) {
			if($mode)
				$cmsControllerInstance->currentTitle = $this->regedit->getVal('//domains/' . $_REQUEST['domain'] . '/title_pref_' . $_REQUEST['lang']) . $title;
			else
				$cmsControllerInstance->currentTitle = $title;
		}
		else
			$cmsControllerInstance->currentTitle = $this->cmsController->currentHeader;

	}

	protected function setH1($h1) {
		$this->setHeader($h1);
	}

	public function flush($output = "", $ctype = false) {
		if($ctype !== false) {
			header("Content-type: " . $ctype);
		}

		echo $output;
		exit();
	}

	/**
	 * @deprecated
	 * Больше не успользуется
	 */
	public static function loadTemplatesMeta($filepath = "") {
		$arguments = func_get_args();
		$templates = call_user_func_array(array('def_module', "loadTemplates"), $arguments);

		for($i=1; $i < count($arguments); $i++) {
			$templates[$i-1] = $templates[$i-1] ? array("#template" => $templates[$i-1], "#meta" => array("name" => $arguments[$i], "file" => $filepath)) : $templates[$i-1];
		}

		return $templates;
	}

	/**
	 * @static
	 * Загружает шаблоны, используя шаблонизатор в зависимости от режима работы макросов, возвращает запрошенные блоки
	 *
	 * @param string $filePath - путь к источнику шаблонов
	 * @return array
	 */
	public static function loadTemplates($filePath = "") {
		$args = func_get_args();

		$templater = self::isXSLTResultMode() ? 'umiTemplaterXSLT' : 'umiTemplaterTPL';

		if (!self::isXSLTResultMode() && !is_file($filePath)) {
			$cmsController = cmsController::getInstance();
			// получаем полный путь к tpl-шаблону
			$defaultLang = langsCollection::getInstance()->getDefaultLang();
			$currentLang = $cmsController->getCurrentLang();
			$resourcesDir = $cmsController->getResourcesDirectory();

			$langPrefix = '';
			if ($defaultLang && $currentLang && ($defaultLang->getId() != $currentLang->getId())) {
				$langPrefix = $currentLang->getPrefix();
			}

			if (substr($filePath, -4) === '.tpl') {
				$filePath = substr($filePath, 0, -4);
			}

			$files = array();
			if ( system_is_mobile() ) {
				$pathArray = explode('/', $filePath);
				$mobileFilePath = '/mobile/' . array_pop($pathArray);
				$mobileFilePath = implode('/', $pathArray) . $mobileFilePath;
				if ( strlen($langPrefix) ) {
					$files[] = $mobileFilePath . '.' . $langPrefix;
				}
				$files[] = $mobileFilePath;
			}

			if ( strlen($langPrefix) ) {
				$files[] = $filePath . '.' . $langPrefix;
			}
			$files[] = $filePath;

			$dir = rtrim(($resourcesDir ? $resourcesDir : CURRENT_WORKING_DIR), '/') . '/tpls/';

			foreach($files as $filePath) {
				$filePath = $dir . $filePath. '.tpl';
				if ( is_file($filePath) ) {
					break;
				}
			}

			$args[0] = $filePath;
		}

		$result = call_user_func_array(array(
			$templater, 'getTemplates'
		), $args);

		return $result;
	}

	/**
	 * @static
	 * Загружает шаблоны для формирования писем
	 * Сначала пытаемся загрузить XSLT-шаблон, если шаблон не найден, пытаемся загрузить TPL-шаблон
	 *
	 * @param string $filepath
	 * @return array - массив шаблонов
	 */
	public static function loadTemplatesForMail($filePath = "") {
		if (substr($filePath, -4) === '.tpl') {
			$filePath = substr($filePath, 0, -4);
		}
		// fix for mail / mails paths for xslt
		$xslFilePath = $filePath;
		if (strpos($xslFilePath, "mail") !== false) {
			$xslFilePath = str_replace(array("mail/", "mails/"), array('', ''), $xslFilePath);
		}

		if ($resourcesDir = cmsController::getInstance()->getResourcesDirectory()) {
			$xslSourcePath = $resourcesDir . "/xslt/mail/" . $xslFilePath . ".xsl";
			$tplSourcePath = $resourcesDir . "/tpls/" . $filePath . ".tpl";
		} else {
			$xslSourcePath = CURRENT_WORKING_DIR . "/xsltTpls/mail/" . $xslFilePath . ".xsl";
			$tplSourcePath = CURRENT_WORKING_DIR . "/tpls/" . $filePath . ".tpl";
		}

		$templaterClass = null;
		if (is_file($xslSourcePath)) {
			$templaterClass = 'umiTemplaterXSLT';
			$sourcePath = $xslSourcePath;
		} elseif (is_file($tplSourcePath)) {
			$templaterClass = 'umiTemplaterTPL';
			$sourcePath = $tplSourcePath;
		} else {
			throw new coreException("Невозможно подключить шаблон \"{$filePath}\" для отправки письма", 2);
		}

		$args = func_get_args();
		$args[0] = $sourcePath;

		$result = call_user_func_array(array(
			$templaterClass, 'getTemplates'
		), $args);

		return $result;
	}

	/**
	 * @static
	 * Обрабатывает TPL - макросы в контенте, используя TPL-шаблонизатор
	 *
	 * @param string $content
	 * @param mixed $scopeElementId - id страницы в качестве области видимости блока
	 * @param mixed $scopeObjectId - id объекта в качестве области видимости блока
	 * @param array $parseVariables - переменные, для парсинга контента
	 * @return string
	 */
	public static function parseTPLMacroses($content, $scopeElementId = false, $scopeObjectId = false, $parseVariables = array()) {
		if (strpos($content, '%') === false) return $content;

		$tplTemplater = umiTemplater::create('TPL');
		$tplTemplater->setScope($scopeElementId, $scopeObjectId);
		return $tplTemplater->parse($parseVariables, $content);
	}

	/**
	 * @static
	 * Выполняет разбор шаблона, используя необходимый шаблонизатор в зависимости от режима работы макросов
	 *
	 * @param mixed $template - шаблон для разбора
	 * @param array $arr - массив переменнх
	 * @param bool|int $parseElementPropsId - установить id страницы в качестве области видимости блока
	 * @param bool|int $parseObjectPropsId  - установить id объекта в качестве области видимости блока
	 * @param null|bool $xsltResultMode - принудительно устанавливает режим работы макросов перед разбором
	 * и восстанавливает предыдущий режим работы в конце работы
	 * @return mixed - результат разбора шаблона
	 */
	public static function parseTemplate($template, $arr, $parseElementPropsId = false, $parseObjectPropsId = false, $xsltResultMode = null) {
		if (!is_array($arr)) $arr = array();

		$oldResultMode = null;
		if (is_bool($xsltResultMode)) {
			$oldResultMode = self::isXSLTResultMode($xsltResultMode);
		}
		if (self::isXSLTResultMode()) {
			$result = array();
			$extProps = self::getMacrosExtendedProps();
			$extGroups = self::getMacrosExtendedGroups();
			if ((!empty($extProps) || !empty($extGroups)) && ($parseElementPropsId || $parseObjectPropsId)) {
				if ($parseElementPropsId) {
					$entity = umiHierarchy::getInstance()->getElement($parseElementPropsId);
					if ($entity) $entity = $entity->getObject();
				} else {
					$entity = umiObjectsCollection::getInstance()->getObject($parseObjectPropsId);
				}
				/**
 				 * @var umiObject $entity
				 */
				if ($entity) {
					$extPropsInfo = array();
					foreach ($extProps as $fieldName) {
						if ($fieldName == 'name' && !isset($arr['attribute:name'], $arr['@name'])) {
							$arr['@name'] = $entity->getName();
						} elseif ($extProp = $entity->getPropByName($fieldName)) {
							$extPropsInfo[] = $extProp;
						}
					}
					if (count($extPropsInfo)) {
						if (!isset($arr['extended'])) $arr['extended'] = array();
						$arr['extended']['properties'] = array('+property' => $extPropsInfo);
					}

					$extGroupsInfo = array();
					foreach ($extGroups as $groupName) {
						if ($group = $entity->getType()->getFieldsGroupByName($groupName)) {
							$groupWrapper = translatorWrapper::get($group);
							$extGroupsInfo[] = $groupWrapper->translateProperties($group, $entity);
						}
					}

					if (count($extGroupsInfo)) {
						if (!isset($arr['extended'])) $arr['extended'] = array();
						$arr['extended']['groups'] = array('+group' => $extGroupsInfo);
					}
				}
			}
			$keysCache = &xmlTranslator::$keysCache;
			foreach($arr as $key => $val) {
				if (is_null($val) || $val === false || $val === "") continue;
				if (is_array($val)) {
					$val = self::parseTemplate($template, $val);
				}
				if (!isset($keysCache[$key])) {
					$keysCache[$key] = xmlTranslator::getKey($key);
				}
				list($subKey, $realKey) = $keysCache[$key];
				if($subKey === 'subnodes') {
					$result[$realKey] = array(
						'nodes:item' => $val
					);
					continue;
				}

				$result[$key] = $val;
			}
			return $result;
		} else {
			$templater = umiTemplater::create('TPL');
			$variables = array();
			foreach($arr as $m => $v) {
				$m = self::getRealKey($m);

				if(is_array($v)) {
					$res = "";
					$v = array_values($v);
					$sz = sizeof($v);
					for($i = 0; $i < $sz; $i++) {
						$str = $v[$i];

						$listClassFirst = ($i == 0) ? "first" : "";
						$listClassLast = ($i == $sz-1) ? "last" : "";
						$listClassOdd = (($i+1) % 2 == 0) ? "odd" : "";
						$listClassEven = $listClassOdd ? "" : "even";
						$listPosition = ($i + 1);
						$listComma = $listClassLast ? '' : ', ';

						$from = Array(
							'%list-class-first%', '%list-class-last%', '%list-class-odd%', '%list-class-even%', '%list-position%',
							'%list-comma%'
						);
						$to = Array(
							$listClassFirst, $listClassLast, $listClassOdd, $listClassEven, $listPosition, $listComma
						);
						$res .= str_replace($from, $to, $str);
					}
					$v = $res;
				}
				if(!is_object($v)) {
					$variables[$m] = $v;
				}
			}
			$arr = $variables;
		}
		$templater->setScope($parseElementPropsId, $parseObjectPropsId);

		$result = $templater->parse($arr, $template);
		$result = $templater->replaceCommentsAfterParse($result);

		if (!is_null($oldResultMode)) {
			 self::isXSLTResultMode($oldResultMode);
		}

		return $result;
	}

	/**
	 * Сокращенная запись loadTemplate/parseTemplate
	 *
	 * @param string $template Файл шаблона
	 * @param string $block Блок вывода
	 * @param array $blockArray Значения
	 * @param bool|int $elementId Элемент
	 *
	 * @return mixed
	 */
	public static function renderTemplate($template, $block, $blockArray = array(), $elementId = false) {
		list($tpl) = def_module::loadTemplates($template, $block);
		return def_module::parseTemplate($tpl, $blockArray, $elementId);
	}

	/**
	 * @static
	 * Выполняет разбор шаблона для отправки письма
	 * Если в template пришел URI шаблона, для обработки используется umiTemplaterXSTL
	 *
	 * @param string $template - шаблон для разбора
	 * @param array $arr - массив переменнх
	 * @param bool|int $parseElementPropsId - установить id страницы в качестве области видимости блока
	 * @param bool|int $parseObjectPropsId  - установить id объекта в качестве области видимости блока
	 * @return mixed - результат разбора шаблона
	 */
	public static function parseTemplateForMail($template, $arr, $parseElementPropsId = false, $parseObjectPropsId = false) {
		if (strpos($template, 'file://') === 0) {
			// Используем xslt-шаблонизатор
			$templateURL = @parse_url($template);
			if (!is_array($templateURL)) {
				throw new publicException('Невозможно обработать шаблон "' . $template . '"');
			}
			$templateSource = $templateURL['path'];
			$templateFragment = (isset($templateURL['fragment']) && strlen($templateURL['fragment'])) ? $templateURL['fragment'] : 'result';

			$templater = umiTemplater::create('XSLT', $templateSource);
			return $templater->parse(array(
				$templateFragment => $arr
			));
		} else {
			// Используем tpl-шаблонизатор
			return def_module::parseTemplate($template, $arr, $parseElementPropsId, $parseObjectPropsId, false);
		}
	}
	/**
	 * @deprecated
	 * Используйте def_module::parseTemplateForMail
	 */
	public static function parseContent($template, $arr, $parseElementPropsId = false, $parseObjectPropsId = false) {
		return self::parseTemplateForMail($template, $arr, $parseElementPropsId, $parseObjectPropsId);
	}

	static public function getRealKey($key, $reverse = false) {
		$shortKeys = array('@', '#', '+', '%', '*');

		if(in_array(substr($key, 0, 1), $shortKeys)) {
			return substr($key, 1);
		}

		if($pos = strpos($key, ":")) {
			++$pos;
		} else {
			$pos = 0;
		}

		return $reverse ? substr($key, 0, $pos - 1) : substr($key, $pos);
	}

	public function formatMessage($message, $b_split_long_mode = 0) {
		static $bb_from;
		static $bb_to;

		$oldResultTMode = $this->isXSLTResultMode(false);

		try {
			list($quote_begin, $quote_end) = $this->loadTemplates('quote/default', 'quote_begin', 'quote_end');
		} catch (publicException $e) {
			$quote_begin = "<div class='quote'>";
			$quote_end = "</div>";
		}

		if (self::isXSLTResultMode()) {
			$quote_begin = "<div class='quote'>";
			$quote_end = "</div>";
		}

		if (!(is_array($bb_from) && is_array($bb_to) && count($bb_from) === count($bb_to))) {
			try {
				list($bb_from, $bb_to) = $this->loadTemplates('bb/default', 'bb_from', 'bb_to');
				if (!(is_array($bb_from) && is_array($bb_to) && count($bb_from) === count($bb_to) && count($bb_to))) {
					$bb_from = Array("[b]", "[i]", "[/b]", "[/i]",
						"[quote]", "[/quote]", "[u]", "[/u]", "\r\n"
					);

					$bb_to   = Array("<strong>", "<em>", "</strong>", "</em>",
						$quote_begin, $quote_end, "<u>", "</u>", "<br />"
					);
				}
			} catch (publicException $e) {
				$bb_from = Array("[b]", "[i]", "[/b]", "[/i]",
					"[quote]", "[/quote]", "[u]", "[/u]", "\r\n"
				);

				$bb_to   = Array("<strong>", "<em>", "</strong>", "</em>",
					$quote_begin, $quote_end, "<u>", "</u>", "<br />"
				);
			}
		}

		$openQuoteCount = substr_count(wa_strtolower($message), "[quote]");
		$closeQuoteCount = substr_count(wa_strtolower($message), "[/quote]");

		if($openQuoteCount > $closeQuoteCount) {
			$message .= str_repeat("[/quote]", $openQuoteCount - $closeQuoteCount);
		}
		if($openQuoteCount < $closeQuoteCount) {
			$message = str_repeat("[quote]", $closeQuoteCount - $openQuoteCount) . $message;
		}

		$message = preg_replace("`((http)+(s)?:(//)|(www\.))((\w|\.|\-|_)+)(/)?([/|#|?|&|=|\w|\.|\-|_]+)?`i", "[url]http\\3://\\5\\6\\8\\9[/url]", $message);

		$message = str_ireplace($bb_from, $bb_to, $message);
		$message = str_ireplace("</h4>", "</h4><p>", $message);
		$message = str_ireplace("</div>", "</p></div>", $message);

		$message = str_replace(".[/url]", "[/url].", $message);
		$message = str_replace(",[/url]", "[/url],", $message);

		$message = str_replace(Array("[url][url]", "[/url][/url]"), Array("[url]", "[/url]"), $message);

		// split long words
		if ($b_split_long_mode === 0) { // default
			$arr_matches = array();
			$b_succ = preg_match_all("/[^\s^<^>]{70,}/u", $message, $arr_matches);
			if ($b_succ && isset($arr_matches[0]) && is_array($arr_matches[0])) {
				foreach ($arr_matches[0] as $str) {
					$s = "";
					if (strpos($str, "[url]") === false) {
						for ($i = 0; $i<wa_strlen($str); $i++) $s .= wa_substr($str, $i, 1).(($i % 30) === 0 ? " " : "");
						$message = str_replace($str, $s, $message);
					}
				}
			}
		} elseif ($b_split_long_mode === 1) {
			// TODU abcdef...asdf
		}

		if (preg_match_all("/\[url\]([^А-я^\r^\n^\t]*)\[\/url\]/U", $message, $matches, PREG_SET_ORDER)) {
			for ($i=0; $i<count($matches); $i++) {
				$s_url = $matches[$i][1];
				$i_length = strlen($s_url);
				if ($i_length>40) {
					$i_cutpart = ceil(($i_length-40)/2);
					$i_center = ceil($i_length/2);

					$s_url = substr_replace($s_url, "...", $i_center-$i_cutpart, $i_cutpart*2);
				}
				$message = str_replace($matches[$i][0], "<a href='/go-out.php?url=".$matches[$i][1]."' target='_blank' title='Ссылка откроется в новом окне'>".$s_url."</a>", $message);
			}
		}

		$message = str_replace("&", "&amp;", $message);

		$message = str_ireplace("[QUOTE][QUOTE]", "", $message);

		if(preg_match_all("/\[smile:([^\]]+)\]/im", $message, $out)) {
			foreach($out[1] as $smile_path) {
				$s = $smile_path;
				$smile_path = "images/forum/smiles/" . $smile_path . ".gif";
				if(file_exists($smile_path)) {
					$message = str_replace("[smile:" . $s . "]", "<img src='/{$smile_path}' />", $message);
				}
			}
		}

		$message = preg_replace("/<p>(<br \/>)+/", "<p>", $message);
		$message = nl2br($message);
		$message = str_replace("<<br />br /><br />", "", $message);
		$message = str_replace("<p<br />>", "<p>", $message);

		$message = str_replace("&amp;quot;", "\"", $message);
		$message = str_replace("&amp;quote;", "\"", $message);
		$message = html_entity_decode($message);
		$message = str_replace("%", "&#37;", $message);

		$message = $this->parseTPLMacroses($message);

		$this->isXSLTResultMode($oldResultTMode);
		return $message;
	}

	public function autoDetectAttributes() {
		if($element_id = $this->cmsController->getCurrentElementId()) {
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if(!$element) return false;

			if($h1 = $element->getValue("h1")) {
				$this->setHeader($h1);
			} else {
				$this->setHeader($element->getName());
			}

			if($title = $element->getValue("title")) {
				$this->setTitle($title);
			}

		}
	}

	public function autoDetectOrders(umiSelection $sel, $object_type_id) {
		if(array_key_exists("order_filter", $_REQUEST)) {
			$sel->setOrderFilter();

			$type = $this->umiObjectTypesCollection->getType($object_type_id);

			$order_filter = getRequest('order_filter');
			foreach($order_filter as $field_name => $direction) {
				if($direction === "asc") $direction = true;
				if($direction === "desc") $direction = false;

				if($field_name == "name") {
					$sel->setOrderByName((bool) $direction);
					continue;
				}

				if($field_name == "ord") {
					$sel->setOrderByOrd((bool) $direction);
					continue;
				}

				if($type) {
					if($field_id = $type->getFieldId($field_name)) {
						$sel->setOrderByProperty($field_id, (bool) $direction);
					} else {
						continue;
					}
				}
			}
		} else {
			return false;
		}
	}

	public function autoDetectFilters(umiSelection $sel, $object_type_id) {
		if(is_null(getRequest('search-all-text')) == false) {
			$searchStrings = getRequest('search-all-text');
			if(is_array($searchStrings)) {
				foreach($searchStrings as $searchString) {
					if($searchString) {
						$sel->searchText($searchString);
					}
				}
			}
		}

		if(array_key_exists("fields_filter", $_REQUEST)) {
			$cmsController = $this->cmsController;
			$data_module = $cmsController->getModule("data");
			if(!$data_module) {
				throw new publicException("Need data module installed to use dynamic filters");
			}
			$sel->setPropertyFilter();

			$type = $this->umiObjectTypesCollection->getType($object_type_id);

			$order_filter = getRequest('fields_filter');
			if(!is_array($order_filter)) {
				return false;
			}

			foreach($order_filter as $field_name => $value) {
				if($field_name == "name") {
					$data_module->applyFilterName($sel, $value);
					continue;
				}

				if($field_id = $type->getFieldId($field_name)) {
					$this->isSelectionFiltered = true;
					$field = $this->umiFieldsCollection->getField($field_id);

					$field_type_id = $field->getFieldTypeId();
					$field_type = $this->umiFieldTypesCollection->getFieldType($field_type_id);

					$data_type = $field_type->getDataType();

					switch($data_type) {
						case "text": {
							$data_module->applyFilterText($sel, $field, $value);
							break;
						}

						case "wysiwyg": {
							$data_module->applyFilterText($sel, $field, $value);
							break;
						}

						case "string": {
							$data_module->applyFilterText($sel, $field, $value);
							break;
						}

						case "tags": {
							$tmp = array_extract_values($value);
							if(empty($tmp)) {
								break;
							}
						}
						case "boolean": {
							$data_module->applyFilterBoolean($sel, $field, $value);
							break;
						}

						case "int": {
							$data_module->applyFilterInt($sel, $field, $value);
							break;
						}

						case "symlink":
						case "relation": {
							$data_module->applyFilterRelation($sel, $field, $value);
							break;
						}

						case "float": {
							$data_module->applyFilterFloat($sel, $field, $value);
							break;
						}

						case "price": {
							$emarket = $cmsController->getModule('emarket');
							if($emarket instanceof def_module) {
								$defaultCurrency = $emarket->getDefaultCurrency();
								$currentCurrency = $emarket->getCurrentCurrency();
								$prices = $emarket->formatCurrencyPrice($value, $defaultCurrency, $currentCurrency);
								foreach($value as $index => $void) {
									$value[$index] = getArrayKey($prices, $index);
								}
							}

							$data_module->applyFilterPrice($sel, $field, $value);
							break;
						}

						case "file":
						case "img_file":
						case "swf_file":
						case "boolean": {
							$data_module->applyFilterInt($sel, $field, $value);
							break;
						}

						case "date": {
							$data_module->applyFilterDate($sel, $field, $value);
							break;
						}

						default: {
							break;
						}
					}
				} else {
					continue;
				}
			}
		} else {
			return false;
		}
	}

	public function analyzeRequiredPath($pathOrId, $returnCurrentIfVoid = true) {

        $umiHierarchy = umiHierarchy::getInstance();

		if(is_numeric($pathOrId)) {
			return ($umiHierarchy->isExists((int) $pathOrId)) ? (int) $pathOrId : false;
		} else {
			$pathOrId = trim($pathOrId);

			if($pathOrId) {
				if(strpos($pathOrId, " ") === false) {
					return $umiHierarchy->getIdByPath($pathOrId);
				} else {
					$paths_arr = explode(" ", $pathOrId);

					$ids = Array();

					foreach($paths_arr as $subpath) {
						$id = $this->analyzeRequiredPath($subpath, false);

						if($id === false) {
							continue;
						} else {
							$ids[] = $id;
						}
					}

					if(sizeof($ids) > 0) {
						return $ids;
					} else {
						return false;
					}
				}
			} else {
				if($returnCurrentIfVoid) {
					return $this->cmsController->getCurrentElementId();
				} else {
					return false;
				}
			}
		}
	}

	public function checkPostIsEmpty($bRedirect = true) {
		$bResult = !is_array($_POST) || (is_array($_POST) && !count($_POST));
		if ($bResult && $bRedirect) {
			$url = preg_replace("/(\r)|(\n)/", "", $_REQUEST['pre_lang'])."/admin/";
			header("Location: ".$url);
			exit();
		} else {
			return $bResult;
		}
	}

	public static function setEventPoint(umiEventPoint $eventPoint) {
		umiEventsController::getInstance()->callEvent($eventPoint);
	}

	/**
	 * @deprecated
	 * @return bool
	 */
	public function breakMe() {
		return false;
	}

	/**
	 * Methods for user errors notifications thru pages
	 */

	/**
	 * Call this function to register error page url, which will be called after errors.
	 * @param unknown_type $errorUrl
	 */
	public function errorRegisterFailPage($errorUrl) {
		$this->cmsController->errorUrl = $errorUrl;
	}

	/**
	 * Add new error message and call errorPanic(), is second argument is true
	 * @param unknown_type $errorMessage
	 * @param unknown_type $causePanic
	 * @param unknown_type $errorCode
	 * @param unknown_type $errorStrCode
	 */
	public function errorNewMessage($errorMessage, $causePanic = true, $errorCode = false, $errorStrCode = false) {
		$controller = $this->cmsController;
		$requestId = 'errors_' . $controller->getRequestId();
		if(!isset($_SESSION[$requestId])) {
			$_SESSION[$requestId] = Array();
		}

		$errorMessage = $controller->getCurrentTemplater()->putLangs($errorMessage);

		$_SESSION[$requestId][] = Array("message" => $errorMessage,
						"code" => $errorCode,
						"strcode" => $errorStrCode);

		if($causePanic) {
			$this->errorPanic();
		}
	}

	//Forces redirect to error page, if at least one error message registrated
	public function errorPanic() {
		if(is_null(getRequest('_err')) == false) {
			return false;
		}

		if(self::$noRedirectOnPanic) {
			$requestId = 'errors_' . $this->cmsController->getRequestId();
			if(!isset($_SESSION[$requestId])) {
				$_SESSION[$requestId] = Array();
			}
			$errorMessage = "";
			foreach($_SESSION[$requestId] as $i => $errorInfo) {
				unset($_SESSION[$requestId][$i]);
				$errorMessage .= $errorInfo['message'];
			}
			throw new errorPanicException($errorMessage);
		}

		if($errorUrl = $this->cmsController->errorUrl) {
			// validate url
			$errorUrl = preg_replace("/_err=\d+/is", '', $errorUrl);
			while (strpos($errorUrl, '&&') !== false || strpos($errorUrl, '??') !== false || strpos($errorUrl, '?&') !== false) {
				$errorUrl = str_replace('&&', '&', $errorUrl);
				$errorUrl = str_replace('??', '?', $errorUrl);
				$errorUrl = str_replace('?&', '?', $errorUrl);
			}
			if (strlen($errorUrl) && (substr($errorUrl, -1) === '?' || substr($errorUrl, -1) === '&')) $errorUrl = substr($errorUrl, 0, strlen($errorUrl)-1);
			// detect param concat
			$sUrlConcat = (strpos($errorUrl, '?') === false ? '?' : '&');
			//
			$errorUrl .= $sUrlConcat . "_err=" . $this->cmsController->getRequestId();
			$this->redirect($errorUrl, false);
		} else {
			throw new privateException("Can't find error redirect string");
		}
	}

	public function importDataTypes() {
		$sDTXmlPath = dirname(__FILE__)."/".get_class($this)."/types.xml";
		$oDTImporter = new umiModuleDataImporter();
		$bSucc = $oDTImporter->loadXmlFile($sDTXmlPath);
		if ($bSucc) {
			$oDTImporter->import();
			return "data types imported ok";
		} else {
			return "can not import data from file '".$sDTXmlPath."'";
		}
	}

	public function exportDataTypes() {
		$sDTXmlPath = dirname(__FILE__)."/".get_class($this)."/types.xml";
		$oDTExporter = new umiModuleDataExporter(get_class($this));
		$sDTXmlData = $oDTExporter->getXml();
		$vSucc = file_put_contents($sDTXmlPath, $sDTXmlData);
		if ($vSucc === false) {
			return "can not write to file '".$sDTXmlPath."'";
		} else {
			@chmod($sDTXmlPath, 0777);
			return $vSucc." bytes exported to the file '".$sDTXmlPath."' successfully";
		}
	}

	public function guessDomain() {
		$res = false;

		for($i = 0; ($param = getRequest("param" . $i)) || $i <= 3; $i++) {
			if(is_numeric($param)) {
				$element = umiHierarchy::getInstance()->getElement($param);
				if($element instanceof umiHierarchyElement) {
					$domain_id = $element->getDomainId();
					if($domain_id) $res = $domain_id;
				} else {
					continue;
				}
			} else {
				continue;
			}
		}

		$domain = $this->domainsCollection->getDomain($res);
		if($domain instanceof iDomain) {
			return $domain->getHost();
		} else {
			return false;
		}
	}

	public static function pushEditable($module, $method, $id) {
		umiTemplater::pushEditable($module, $method, $id);
	}

	/**
	* @desc Checks for method existance
	* @param String $_sMethodName Name of the method
	* @return Boolean
	*/
	public function isMethodExists($_sMethodName) {//$this->__classes

		if(class_exists('ReflectionClass')) {

			$oReflection = new ReflectionClass($this);
			if($oReflection->hasMethod($_sMethodName)) {
				return true;
			}

			foreach($this->__classes as $classname) {
				$oReflection = new ReflectionClass($classname);
				if($oReflection->hasMethod($_sMethodName)) {
					return true;
				}
			}

			if ($_sMethodName == 'tagsDomainCloud') {

			}
			return false;
		} else {
			$aMethods = get_class_methods($this);
			if(in_array($_sMethodName, $aMethods)) {
				return true;
			}

			foreach($this->__classes as $classname) {
				$aMethods = get_class_methods($classname);
				if(in_array($_sMethodName, $aMethods)) {
					return true;
				}
			}

			return false;
		}
	}

	public function flushAsXML($methodName) {
		static $c = 0;
		if($c++ == 0) {
			$xml = true;
			if (getRequest('jsonMode') == 'force') {
				$xml = false;
			}
			$buffer = outputBuffer::current();
			$buffer->contentType('text/' . ($xml ? 'xml' : 'javascript'));
			$buffer->charset('utf-8');
			$buffer->clear();
			$data = $this->cmsController->executeStream("udata://" . get_class($this) . "/" . $methodName . ($xml ? '' : '.json'));
			$buffer->push($data);
			$buffer->end();
		}
	}

	public function ifNotXmlMode() {
		if(getRequest('xmlMode') != 'force') {
			$this->setData(array('message' => 'This method returns result only by direct xml call'));
			return true;
		}
	}

	public function removeErrorParam($url) { return preg_replace("/_err=\d+/", "", $url); }

	public function getObjectEditLink($objectId, $type = false) { return false; }

	public static function validateTemplate(&$templateName) {
		if(!$templateName && $templateName == 'default' && self::$defaultTemplateName != 'default') {
			$templateName = self::$defaultTemplateName;
		}
	}

	public function templatesMode($mode) {
		$isXslt = self::isXSLTResultMode();
		if($mode == 'xslt' && !$isXslt) {
			throw new xsltOnlyException;
		}

		if($mode == 'tpl' && $isXslt) {
			throw new tplOnlyException;
		}
	}

	/**
	 * Устанавливает/возвращает режим работы макросов
	 * @param bool|null $newValue - если передан, то переопределяет режим работы
	 * @static
	 * @return bool - возвращает режим работы, если передан новый режим, возвращает прошлый режим работы макросов
	 */
	public static function isXSLTResultMode($newValue = null) {
		if (is_null(self::$xsltResultMode)) {
			self::$xsltResultMode = cmsController::getInstance()->getCurrentTemplater() instanceof IFullResult;
		}

		if (!is_null($newValue)) {
			$oldValue = self::$xsltResultMode;
			self::$xsltResultMode = (bool) $newValue;
			return $oldValue;
		}

		return self::$xsltResultMode;
	}

	public function validateEntityByTypes($entity, $types, $checkParentType = false) {
		if($entity instanceof iUmiHierarchyElement) {
			$module = $entity->getModule();
			$method = $entity->getMethod();
		} else if($entity instanceof iUmiObject) {
			/**
			* @var umiObjectType
			*/
			$objectType = selector::get('object-type')->id($entity->getTypeId());
			if($checkParentType) {
				$objectType = selector::get('object-type')->id($objectType->getParentId());
			}
			if($hierarchyTypeId = $objectType->getHierarchyTypeId()) {
				$hierarchyType = selector::get('hierarchy-type')->id($hierarchyTypeId);
				$module = $hierarchyType->getModule();
				$method = $hierarchyType->getMethod();
			} else {
				$module = null;
				$method = null;
			}
		} else {
			throw new publicException("Page or object must be given");
		}

		if(is_null($module) && is_null($method) && is_null($types)) {
			return true;
		}

		if($module == 'content' && $method == '') {
			$method = 'page';
		}

		if(getArrayKey($types, 'module')) {
			$types = array($types);
		}

		foreach($types as $type) {
			$typeModule = getArrayKey($type, 'module');
			$typeMethod = getArrayKey($type, 'method');

			if($typeModule == 'content' && $typeMethod == '') {
				$typeMethod = 'page';
			}

			if($typeModule == $module) {
				if(is_null($typeMethod)) return;
				if($typeMethod == $method) return;
			}
		}
		throw new publicException(getLabel('error-common-type-mismatch'));
	}

	public function is_demo() {
		return defined('CURRENT_VERSION_LINE') && CURRENT_VERSION_LINE == 'demo';
	}



/* New errors system */

	public function errorAddErrors($errors) {
		$result = array();
		if ($errors instanceof Exception) {
			$error = array(
				'message' => $errors->getMessage(),
				'code' => $errors->getCode(),
				);
			return array_push($this->errors, $error);
		} elseif (is_array($errors)) {
			if (array_key_exists('message', $errors)) {
				$error = array_intersect_key($errors, array('message'=>'', 'code'=>''));
				return array_push($this->errors, $error);
			} else {
				foreach ($errors as $error) {
					$result[] = $this->errorAddErrors($error);
				}
				return $result;
			}
		} elseif (is_string($errors)) {
			return array_push($this->errors, array('message' => $errors));
		}
		return false;
	}

	protected function errorSetErrors () {

	}

	public function errorGetErrors() {
		return $this->errors;
	}

	public function errorHasErrors() {
		return (!empty($this->errors));
	}

	public function errorSetErrorPage($errorPage) {
		$errorPage = preg_replace('#http://[^/]+#', '', trim($errorPage));
		// validate url
		$errorPage = preg_replace("/_err=\d+/is", '', $errorPage);
		while (strpos($errorPage, '&&') !== false || strpos($errorPage, '??') !== false || strpos($errorPage, '?&') !== false) {
			$errorPage = str_replace('&&', '&', $errorPage);
			$errorPage = str_replace('??', '?', $errorPage);
			$errorPage = str_replace('?&', '?', $errorPage);
		}
		if (strlen($errorPage) && (substr($errorPage, -1) === '?' || substr($errorPage, -1) === '&')) {
			$errorPage = substr($errorPage, 0, strlen($errorPage)-1);
		}
        $this->errorPage = $errorPage;
        return true;
	}

	public function errorGetErrorPage() {
		return $this->errorPage;
	}

	public function errorThrow($mode = false) {
		if (!$this->errorHasErrors()) {
			return false;
		}

		if(self::$noRedirectOnPanic) {
			$errorMessage = '';
			foreach ($this->errors as $error) {
				$errorMessage .= getLabel($error['message']) . ' ';
			}
			$this->errors = array();
			throw new errorPanicException($errorMessage);
		}

		switch ($mode) {
			case 'public' : {
				$this->errorThrowPublic();
				break;
			}

			case 'admin' : {
				$this->errorThrowAdmin();
				break;
			}

			case 'xml' : {
				$errors = array();
				foreach ($this->errors as $error) {
					$errors[] = getLabel($error['message']);
				}
				$this->errors = array();
				throw new wrongValueException('<br/>' . implode("<br/><br/>", $errors));
			}
		}
	}

	/**
	 * Сортирует массив с объектам, по порядку идентификаторов
	 * в массиве с идентификаторами объектов.
	 * Возращает результат сортировки.
	 * @param array $sortedIds массив с идентификаторами объектов
	 * @param array $objects массив с объектами
	 * @return array
	 */
	public static function sortObjects($sortedIds, $objects) {
		if (count($sortedIds) == 0 || count($objects) == 0) {
			$objects;
		}

		$flippedSortedIds = array_flip($sortedIds);
		$sortedObjects = array();

		foreach ($objects as $object) {
			$objectId = null;

			switch ($object) {
				case ($object instanceof umiEntinty): {
					$objectId = $object->getId();
					break;
				}
				case (is_array($object) && isset($object['id'])): {
					$objectId = $object['id'];
					break;
				}
			}
			switch ($objectId) {
				case is_null($objectId): {
					$sortedObjects[] = $object;
					break;
				}
				case isset($flippedSortedIds[$objectId]): {
					$sortedObjects[$flippedSortedIds[$objectId]] = $object;
					break;
				}
				default: {
					$sortedObjects[] = $object;
				}
			}
		}
		ksort($sortedObjects);
		return $sortedObjects;
	}

	/**
	 * Формирует объекты страниц (umiHierarchyElement) по идентификаторам
	 * @param array $elementsIds массив с идентификаторами страниц
	 * @param bool $needProps нужно ли дополнительно загрузить свойства страниц
	 * @param bool|int $hierarchyTypeId ид иерархического типа данных, к которому принадлежат страницы
	 * @return void
	 */
	protected function loadElements($elementsIds, $needProps = false, $hierarchyTypeId = false) {
		if (!$elementsIds) {
			return;
		}

		if (!is_array($elementsIds)) {
			$elementsIds = array($elementsIds);
		}

		if (count($elementsIds) == 0) {
			return;
		}

		$umiHierarchy = umiHierarchy::getInstance();
		$this->umiLinksHelper->loadLinkPartForPages($elementsIds);

		$permittedIds = array();
		foreach($elementsIds as $id) {
			if ($umiHierarchy->isAllowed($id) && !$umiHierarchy->isLoaded($id)) {
				$permittedIds[] = $id;
			}
		}
		$elementsIds = $permittedIds;

		if (count($elementsIds) == 0) {
			return;
		}

		$elementsIds = implode(',', $elementsIds);
		$connection = ConnectionPool::getInstance()->getConnection();
		$sql = <<<SQL
SELECT
  h.id,
  h.rel,
  h.type_id,
  h.lang_id,
  h.domain_id,
  h.tpl_id,
  h.obj_id,
  h.ord,
  h.alt_name,
  h.is_active,
  h.is_visible,
  h.is_deleted,
  h.updatetime,
  h.is_default,
  o.name,
  o.type_id AS object_type_id
FROM cms3_hierarchy h, cms3_objects o
WHERE h.id IN ($elementsIds) AND o.id = h.obj_id;
SQL;
		$result = $connection->queryResult($sql);
		$result->setFetchType(IQueryResult::FETCH_ROW);

		if ($result->length() == 0) {
			return;
		}

		$objectsIds = array();
		foreach($result as $row) {
			$elementId = array_shift($row);
			$element = $umiHierarchy->getElement($elementId, false, false, $row);
			if ($element instanceof umiHierarchyElement) {
				$objectsIds[] = $element->getObjectId();
			}
		}

		if ($needProps && $hierarchyTypeId && count($objectsIds) > 0) {
			umiObjectProperty::loadPropsData($objectsIds, $hierarchyTypeId);
		}

		return true;
	}

	private function errorThrowPublic() {
		foreach ($this->errors as &$error) {
			$error['message'] = '%' . $error['message'] . '%';
		}
		$this->errorRedirect();
	}

	private function errorThrowAdmin () {
		foreach ($this->errors as &$error) {
			$error['message'] = getLabel($error['message']);
		}
		$this->errorRedirect();
	}

	private function errorRedirect () {
		$requestId = 'errors_' . $this->cmsController->getRequestId();
		$_SESSION[$requestId] = $this->errors;
		if($errorUrl = $this->errorPage) {
			// detect param concat
			$sUrlConcat = (strpos($errorUrl, '?') === false ? '?' : '&');
			//
			$errorUrl .= $sUrlConcat . "_err=" . $this->cmsController->getRequestId();
			$this->errors = array();
			$this->redirect($errorUrl, false);
		} else {
			$this->errors = array();
			throw new privateException("Can't find error redirect string");
		}
	}
};

?>
