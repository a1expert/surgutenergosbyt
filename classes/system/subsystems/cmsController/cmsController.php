<?php
	/**
	 * Контроллер приложения UMI.CMS.
	 */
	class cmsController extends singleton implements iSingleton, iCmsController {

		public static $IGNORE_MICROCACHE = false;

		public $isContentMode = false;

		public $parsedContent = false;
		public $currentTitle = false;
		public $currentHeader = false;
		public $currentMetaKeywords = false;
		public $currentMetaDescription = false;
		public $currentEditElementId = false;

		public $langs = array();
		public $pre_lang = '';
		public $langs_export = array();
		public $errorUrl;
		public $headerLabel = false;

		/**
		 * @var lang|\false $current_lang
		 */
		protected $current_lang = false;
		/**
		 * @var domain|\false $current_domain
		 */
		protected $current_domain = false;
		/**
		 * @var templater|\false $current_templater
		 */
		protected $current_templater = false;

		protected $modules = array();
		protected $current_module = false;
		protected $current_method = false;
		protected $current_mode = false;
		protected $current_element_id = false;
		protected $calculated_referer_uri = false;
		protected $modulesPath;
		protected $url_prefix = '';
		protected $adminDataSet = array();

		/**
		 * @static
		 * @var array Методы, вызов которых надо игнорировать в режиме XSLT. Предназначено для избавления от заплатки $this->breakMe
		 */
		private $skipExecuteMethods = array(
			'eshop/compare',
			'faq/question', 'faq/project', 'faq/category',
			'blogs20/blog', 'blogs20/post', 'blogs20/postEdit',
			'catalog/category', 'catalog/getObjectsList', 'catalog/object', 'catalog/viewObject', 'catalog/search',
			'content/content', 'content/sitemap',
			'dispatches/unsubscribe', 'dispatches/subscribe', 'dispatches/subscribe_do',
			'emarket/compare', 'emarket/order', 'emarket/purchase',
			'filemanager/shared_file',
			'forum/confs_list', 'forum/conf', 'forum/topic', 'forum/topic_last_message', 'forum/conf_last_message',
			'news/lastlist', 'news/rubric', 'news/view', 'news/related_links', 'news/item', 'news/listlents', 'news/lastlents',
			'photoalbum/album', 'photoalbum/photo',
			'search/search_do', 'search/suggestions',
			'users/settings', 'users/registrate', 'users/registrate_done', 'users/activate', 'users/auth',
			'vote/poll', 'vote/insertvote', 'vote/results',
			'webforms/page', 'webforms/posted'
		);

		/**
		 * {@inheritdoc}
		 */
		protected function __construct() {
			$config = mainConfiguration::getInstance();
			showWorkTime("cmscontroller mainconfig init");

			$this->modulesPath = $config->includeParam('system.modules');
			showWorkTime("cmscontroller includeparam");

			$this->init();
			showWorkTime("cmscontroller init");
		}

		/**
		 *
		 * @param string|null $c
		 * @return cmsController
		 */
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		public function loadBuildInModule($moduleName) {
			//TODO
		}

		/**
		 * @param string $moduleName
		 * @return def_module|\false
		 */
		public function getModule($moduleName) {
			if (!$moduleName) {
				return false;
			}

			if (array_key_exists($moduleName, $this->modules)) {
				return $this->modules[$moduleName];
			} else {
				return $this->loadModule($moduleName);
			}
		}

		public function isModule($moduleName) {
			$regedit = regedit::getInstance();

			if ($regedit->getVal('//modules/' . $moduleName)) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Устанавливает модуль.
		 * @param string $installPath установочный файл
		 * @throws publicAdminException если установочный файл не существует
		 */
		public function installModule($installPath) {
			$INFO = array();
			$COMPONENTS = array();

			if (!file_exists($installPath)) {
				throw new publicAdminException(getLabel("label-errors-13052"), 13052);
			}

			/** @noinspection PhpIncludeInspection */
			require_once $installPath;

			$nameByPath = null;
			if (preg_match('|\/modules\/(\S+)\/|i', $installPath, $matches)) {
				$nameByPath = $matches[1];
			}


			if ($nameByPath != $INFO['name']) {
				throw new publicAdminException(getLabel("label-errors-13053"), 13053);
			}

			$this->checkModuleByName($nameByPath);
			$this->checkModuleComponents($COMPONENTS);

			def_module::install($INFO);
		}

		/**
		 * @deprecated
		 */
		public function getSkinPath() {
			// TODO
		}

		/**
		 * Возвращает имя текущего вызываемого модуля.
		 * @return string
		 */
		public function getCurrentModule() {
			return $this->current_module;
		}

		/**
		 * Возвращает имя текущего вызываемого метода.
		 * @return string
		 */
		public function getCurrentMethod() {
			return $this->current_method;
		}

		/**
		 * Возвращает имя текущего иерархического элемента.
		 * @return string
		 */
		public function getCurrentElementId() {
			return $this->current_element_id;
		}

		/**
		 * Возвращает текущий язык в системе.
		 * @return lang|\false
		 */
		public function getLang() {
			return $this->current_lang;
		}

		public function setLang($lang) {
			$this->current_lang = $lang;
		}

		/**
		 * Возвращает текущий язык в системе.
		 * @return lang|\false
		 */
		public function getCurrentLang() {
			return $this->getLang();
		}

		/**
		 * Возвращает текущий режим работы системы (site или admin).
		 * @return string
		 */
		public function getCurrentMode() {
			return $this->current_mode;
		}

		/**
		 * Возвращает текущий домен.
		 * @return domain|\false
		 */
		public function getCurrentDomain() {
			return $this->current_domain;
		}

		/**
		 * Возвращает текущий шаблонизатор.
		 * @throws coreException
		 * @return umiTemplater
		 */
		public function getCurrentTemplater() {
			if (!$this->current_templater instanceof umiTemplater) {
				$this->detectCurrentTemplater();
			}

			if (!$this->current_templater instanceof umiTemplater) {
				throw new coreException("Can't detect current templater.");
			}

			return $this->current_templater;
		}

		/**
		 * Получить директорию с ресурсами для текущего шаблона.
		 * @param bool $httpMode
		 * @return string
		 */
		public function getResourcesDirectory($httpMode = false) {
			if ($this->getCurrentMode() == 'admin') {
				if (($template = templatesCollection::getInstance()->getDefaultTemplate()) instanceof template) {
					return $template->getResourcesDirectory($httpMode);
				}
				return false;
			}

			if (($template = $this->detectCurrentDesignTemplate()) instanceof template) {
				return $template->getResourcesDirectory($httpMode);
			} else {
				return false;
			}
		}

		/**
		 * Получить текущую директорию с шаблонами
		 * @return string
		 */
		public function getTemplatesDirectory() {
			if (($template = $this->detectCurrentDesignTemplate()) instanceof template) {
				return $template->getTemplatesDirectory();
			} else {
				return CURRENT_WORKING_DIR . "xsltTpls/";
			}
		}

		/**
		 * @deprecated
		 * Оставлено для совместимости, ни на что не влияет
		 * @return umiTemplater
		 */
		public function setCurrentTemplater() {
			return $this->getCurrentTemplater();
		}

		/**
		 * Возвращает глобальные переменные в зависимости от
		 * текущего состояния системы
		 *
		 * @param bool $forcePrepare - если true, переменные будут еще раз инициализированы
		 * @return array
		 */
		public function getGlobalVariables($forcePrepare = false) {
			static $globalVariables;
			if (!$forcePrepare && !is_null($globalVariables)) return $globalVariables;

			$globalVariables = array();
			if ($this->getCurrentMode() == 'admin') {
				return $globalVariables = $this->prepareAdminSideGlobalVariables();
			}
			if (def_module::isXSLTResultMode()) {
				return $globalVariables = $this->prepareClientSideGlobalVariablesForXSLT();
			} else {
				return $globalVariables = $this->prepareClientSideGlobalVariablesForTPL();
			}
		}

		/**
		 * Запускает umi-stream, возвращает результат работы
		 * @param $uri
		 * @throws coreException - Если не удалось открыть стрим
		 * @return string - результат работы стрима
		 */
		public function executeStream($uri) {
			if (($data = @file_get_contents($uri)) === false) {
				// bugfix: failed to open stream: infinite recursion prevented
				$uri .= (strpos($uri, "?") === false) ? '?umiHash=' : '&umiHash=';
				$uri .= md5($uri);

				if (($data = @file_get_contents($uri)) === false) {
					throw new coreException("Failed to open udata:// stream");
				}
			}

			return $data;
		}

		/**
		 * Предназначен для избавления от заплатки $this->breakMe
		 * Возвращает false, если метод вызывать не нужно
		 *
		 * @param string $module
		 * @param string $method
		 *
		 * @return boolean
		 */
		public function isAllowedExecuteMethod($module, $method) {
			return !in_array($module . '/' . $method, $this->skipExecuteMethods);
		}

		/**
		 * Определяет текущий шаблон дизайна
		 * @return null|template - текущий шаблон дизайна, либо null
		 */
		public function detectCurrentDesignTemplate() {
			$templates = templatesCollection::getInstance();

			// пытаемся взять шаблон из request
			$template = null;
			if ($templateId = getRequest('template_id')) {
				$template = $templates->getTemplate((int) $templateId);
			}
			// пытаемся взять текущий шаблон страницы
			if (!$template instanceof template) {
				$template = $templates->getCurrentTemplate();
			}
			return $template;
		}

		public function analyzePath($reset = false) {
			$path = trim(getRequest('path'), '/');

			if (!is_null(getRequest('scheme'))) {
				if (preg_replace('/[^\w]/im', "", getRequest('scheme')) == 'upage') {
					preg_match_all('/[\d]+/', $path, $elementId);
					$this->current_element_id = $elementId[0][0];
				}

				return;
			}
			$regedit = regedit::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$config = mainConfiguration::getInstance();
			$buffer = outputBuffer::current();

			if ($reset === true) {
				$this->reset();
			}

			$urlSuffix = $config->get('seo', 'url-suffix');
			$pos = strrpos($path, $urlSuffix);
			if ($pos && ($pos + strlen($urlSuffix) == strlen($path))) {
				$path = substr($path, 0, $pos);
			}

			if ($config->get('seo', 'url-suffix.add')) {
				def_module::requireSlashEnding();
			}

			if ($config->get('seo', 'watch-redirects-history')) {
				redirects::getInstance()->init();
			}

			$pathArray = $this->getPathArray();
			$sz = sizeof($pathArray);
			$urlArray = Array();
			$p = 0;
			for ($i = 0; $i < $sz; $i++) {
				$subPath = $pathArray[$i];

				if ($i <= 1) {
					if (($subPath == $this->current_mode) || ($subPath == $this->current_lang->getPrefix())) {
						continue;
					}
				}

				$urlArray[] = $subPath;

				$subPathType = $this->getSubPathType($subPath);

				if ($subPathType == "PARAM") {
					$_REQUEST['param' . $p++] = $subPath;
				}
			}

			if (!$this->current_module) {
				if ($this->current_mode == "admin") {
					if ($regedit->getVal("//settings/default_module_admin_changed") || ! $moduleName = $regedit->getVal("//modules/events")) {
						$moduleName = $regedit->getVal("//settings/default_module_admin");
					} else {
						$moduleName = $regedit->getVal("//modules/events");
					}
					$this->autoRedirectToMethod($moduleName);
				} else {
					$moduleName = $regedit->getVal("//settings/default_module");
				}
				$this->setCurrentModule($moduleName);
			}

			if (!$this->current_method) {
				if ($this->current_mode == "admin") {
					return $this->autoRedirectToMethod($this->current_module);
				} else {
					$method_name = $regedit->getVal("//modules/" . $this->current_module . "/default_method");
				}
				$this->setCurrentMethod($method_name);
			}

			if ($this->getCurrentMode() == "admin") {
				return;
			}

			$elementId = false;
			$sz = sizeof($urlArray);
			$subPath = "";
			$errorsCount = 0;

			for ($i = 0; $i < $sz; $i++) {
				$subPath .= "/" . $urlArray[$i];

				if (!($tmp = $hierarchy->getIdByPath($subPath, false, $errorsCount))) {
					$elementId = false;
					break;
				} else {
					$elementId = $tmp;
				}
			}

			if ($elementId) {
				if ($errorsCount > 0 && !defined("DISABLE_AUTOCORRECTION_REDIRECT")) {
					$path = $hierarchy->getPathById($elementId);

					if ($i == 0) {
						if ($this->isModule($urlArray[0])) {
							$elementId = false;
						}
					}

					$buffer->status('301 Moved Permanently');
					$buffer->redirect($path);
				}

				$element = $hierarchy->getElement($elementId);
				if ($element instanceof umiHierarchyElement) {
					if ($element->getIsDefault()) {
						$path = $hierarchy->getPathById($elementId);
						$buffer->status('301 Moved Permanently');
						$buffer->redirect($path);
					}
				}
			} elseif (isset($urlArray[0])) {
				if ($this->isModule($urlArray[0])) {
					$module = $this->getModule($urlArray[0]);
					if (isset($urlArray[1]) && !$module->isMethodExists($urlArray[1])) {
						$this->setCurrentModule('content');
						$this->setCurrentMethod('content');
					}
				} else {
					$this->setCurrentModule('content');
					$this->setCurrentMethod('content');
				}
			}


			if (($path == "" || $path == $this->current_lang->getPrefix() ) && $this->current_mode != "admin") {
				if($elementId = $hierarchy->getDefaultElementId($this->getCurrentLang()->getId(), $this->getCurrentDomain()->getId())) {
					$this->current_element_id = $elementId;
				}
			}

			if ($element = $hierarchy->getElement($elementId, true)) {
				$type = umiHierarchyTypesCollection::getInstance()->getType($element->getTypeId());

				if (!$type) {
					return false;
				}

				$this->current_module = $type->getName();

				if ($ext = $type->getExt()) {
					$this->setCurrentMethod($ext);
				} else {
					$this->setCurrentMethod("content");	//Fixme: content "constructor". Maybe, fix in future?
				}

				$this->current_element_id = $elementId;
			}

			if ($this->current_module == "content" && $this->current_method == "content" && !$elementId) {
				redirects::getInstance()->redirectIfRequired($path);
			}
		}

		public function setCurrentModule($module_name) {
			$this->current_module = $module_name;
		}

		public function setCurrentMode($mode) {
			$this->current_mode = $mode;
		}

		public function setAdminDataSet($dataSet) {
			$this->adminDataSet = $dataSet;
		}

		public function setCurrentMethod($method_name) {
			$magic = array(
				"__construct",
				"__destruct",
				"__call",
				"__callStatic",
				"__get",
				"__set",
				"__isset",
				"__unset",
				"__sleep",
				"__wakeup",
				"__toString",
				"__invoke",
				"__set_state",
				"__clone"
			);
			if (in_array($method_name, $magic)) {
				$this->current_module = "content";
				$this->current_method = "notfound";
				return false;
			}

			$this->current_method = $method_name;
		}

		public function loadLangs() {
			showWorkTime("loadLangs started");

			$modules = regedit::getInstance()->getList("//modules");
			showWorkTime("loadLangs getList");

			foreach ($modules as $module) {
				$moduleName = $module[0];

				$langPath = CURRENT_WORKING_DIR . '/classes/modules/' . $moduleName . '/lang.php';

				if (file_exists($langPath)) {
					require $langPath;
				}

				if (isset($C_LANG)) {
					if (is_array($C_LANG)) {
						$this->langs[$moduleName] = $C_LANG;
						unset($C_LANG);
					}
				}

				if (isset($LANG_EXPORT)) {
					if (is_array($LANG_EXPORT)) {
						$this->langs = array_merge($this->langs, $LANG_EXPORT);
						unset($LANG_EXPORT);
					}
				}

				$langPath = CURRENT_WORKING_DIR . '/classes/modules/' . $moduleName . '/lang.' . $this->getCurrentLang()->getPrefix() .'.php';

				if (file_exists($langPath)) {
					require $langPath;

					if (isset($C_LANG) && is_array($C_LANG)) {
						$this->langs[$moduleName] = $C_LANG;
						unset($C_LANG);
					}

					if (isset($LANG_EXPORT) && is_array($LANG_EXPORT)) {
						$this->langs = array_merge($this->langs, $LANG_EXPORT);
						unset($LANG_EXPORT);
					}
				}

				$this->loadExtLang($moduleName);

				showWorkTime("loadLangs " . $moduleName . " loaded", 1);
			}

			// загружаем общие языковые файлы
			$tryPath = CURRENT_WORKING_DIR . '/classes/modules/lang.' . $this->getLang()->getPrefix() . '.php';

			if (!file_exists($tryPath)) {
				$tryPath = CURRENT_WORKING_DIR . '/classes/modules/lang.php';
			}
			/** @noinspection PhpIncludeInspection */
			include_once $tryPath;

			if (isset($LANG_EXPORT)) {
				$this->langs = array_merge($this->langs, $LANG_EXPORT);
				unset($LANG_EXPORT);
			}
		}

		/**
		 * Подключает языковой файл из расширения.
		 * @param string $moduleName название модуля
		 */
		public function loadExtLang($moduleName) {
			$langPath = CURRENT_WORKING_DIR . '/classes/modules/' . $moduleName . '/ext/lang.*.' . $this->getCurrentLang()->getPrefix() .'.php';

            $langPaths = glob($langPath);

            if (!is_array($langPaths)) {
                return;
            }

			foreach ($langPaths as $filename) {
				if (file_exists($filename)) {
					require $filename;

					if (isset($C_LANG) && is_array($C_LANG)) {
						$this->langs[$moduleName] = array_merge($this->langs[$moduleName], $C_LANG);
						unset($C_LANG);
					}

					if (isset($LANG_EXPORT) && is_array($LANG_EXPORT)) {
						$this->langs = array_merge($this->langs, $LANG_EXPORT);
						unset($LANG_EXPORT);
					}
				}
			}
		}

		public function getModulesList() {
			$regedit = regedit::getInstance();
			$list = $regedit->getList('//modules');
			$result = array();
			foreach ($list as $arr) {
				$result[] = getArrayKey($arr, 0);
			}
			return $result;
		}

		public static function isCSRFTokenValid() {
			if (! mainConfiguration::getInstance()->get('kernel', 'csrf_protection')) {
				return true;
			}
			if (strlen(getRequest('csrf')) && strlen(getArrayKey($_SESSION, 'csrf_token')) && getRequest('csrf') == getArrayKey($_SESSION, 'csrf_token')) {
				return true;
			}
			return false;
		}

		public function getRequestId() {
			static $requestId = false;
			if ($requestId === false) {
				$requestId = time();
			}
			return $requestId;
		}

		public function getPreLang() {
			return $this->pre_lang;
		}

		public function calculateRefererUri() {
			if ($referer = getRequest('referer')) {
				$_SESSION['referer'] = $referer;
			} else {
				if ($referer = getSession('referer')) {
					unset($_SESSION['referer']);
				} else {
					$referer = getServer('HTTP_REFERER');
				}
			}
			$this->calculated_referer_uri = $referer;
		}

		public function getCalculatedRefererUri() {
			if ($this->calculated_referer_uri === false) {
				$this->calculateRefererUri();
			}
			return $this->calculated_referer_uri;
		}

		public function setUrlPrefix($prefix = '') {
			$this->url_prefix = $prefix;
		}

		public function getUrlPrefix(){
			return $this->url_prefix ? $this->url_prefix : '';
		}

		protected function autoRedirectToMethod($module) {
			$preLang = $this->pre_lang;
			$method = regedit::getInstance()->getVal("//modules/" . $module . "/default_method_admin");

			$url = $preLang . "/admin/" . $module . "/" . $method . "/";

			outputBuffer::current()->redirect($url);
		}

		final private function doSomething () {
			if (defined("CRON") && (constant('CRON') == 'CLI')) {
				return true;
			}

			if (defined("CURRENT_VERSION_LINE") && CURRENT_VERSION_LINE != "demo") {
				require CURRENT_WORKING_DIR . "/errors/invalid_license.html";
				exit();
			}

			if (!is_writable(SYS_CACHE_RUNTIME) || (file_exists(SYS_CACHE_RUNTIME.'registry') && !is_writable(SYS_CACHE_RUNTIME.'registry'))) {
				require CURRENT_WORKING_DIR . "/errors/invalid_permissions.html";
				exit();
			}

			$keycode = regedit::getInstance()->getVal("//settings/keycode");

			if($this->doStrangeThings($keycode)) {
				return true;
			}

			$compKeycode = array(
				'pro' => umiTemplater::getSomething("pro"),
				'shop' => umiTemplater::getSomething("shop"),
				'lite' => umiTemplater::getSomething("lite"),
				'start' => umiTemplater::getSomething("start"),
				'trial' => umiTemplater::getSomething("trial")
			);

			if (regedit::checkSomething($keycode, $compKeycode)) {
				return true;
			} else {
				require CURRENT_WORKING_DIR . "/errors/invalid_license.html";
				exit();
			}
		}

		final private function doStrangeThings($keycode) {
			$licenseFile = SYS_CACHE_RUNTIME . 'trash';
			$cmpKeycode = false;
			$expire = 604800;

			if (file_exists($licenseFile)) {
				if ((time() - filemtime($licenseFile)) > $expire) {
					$cmpKeycode = base64_decode(file_get_contents($licenseFile));
				}
			} else {
				file_put_contents($licenseFile, base64_encode($keycode));
			}

			if ($cmpKeycode !== false && $keycode) {
				if ($keycode === $cmpKeycode) {
					return true;
				}
			}
			return false;
		}

		private function loadModule($moduleName) {
			if (!defined("CURRENT_VERSION_LINE")) {
				define("CURRENT_VERSION_LINE", "");
			}

			if (regedit::getInstance()->getVal("//modules/" . $moduleName) != $moduleName) {
				return false;
			}

			$modulePath = $this->modulesPath . $moduleName . "/class.php";
			if (!file_exists($modulePath)) {
				return false;
			}

			if (!class_exists($moduleName)) {
				require($modulePath);
			}

			$module = new $moduleName();
			$module->pre_lang = $this->pre_lang;
			$module->pid = $this->getCurrentElementId();
			$this->modules[$moduleName] = $module;

			return $module;
		}


		/**
		 * Проверка наличия всех компонентов модуля
		 *
		 * @param $components
		 * @throws coreException
		 * @return bool
		 */
		private function checkModuleComponents($components) {
			if (!is_array($components)) {
				return false;
			}

			$files = array();
			foreach ($components as $component) {
				$file = preg_replace('/.\/(.+)/', CURRENT_WORKING_DIR . '/' . "$1", $component);
				if (!file_exists($file) || !is_readable($file)) {
					$files[] = $file;
				}
			}

			if (count($files)) {
				$error = getLabel("label-errors-13058") . "\n";
				foreach ($files as $file) {
					$error .= getLabel('error-file-does-not-exist', null, $file) . "\n";
				}

				throw new coreException($error);
			}

			return true;
		}

		/**
		 * Проверяет, что модуль доступен для данной лицензии.
		 * @param string $moduleName - имя модуля
		 * @throws publicAdminException
		 */
		private function checkModuleByName($moduleName) {
			if (!defined("UPDATE_SERVER")) {
				define("UPDATE_SERVER", base64_decode('aHR0cDovL3VwZGF0ZXMudW1pLWNtcy5ydS91cGRhdGVzZXJ2ZXIv'));
			}

			$regedit = regedit::getInstance();
			$domainsCollection = domainsCollection::getInstance();

			$info = array(
				'type' => 'get-modules-list',
				'revision' => $regedit->getVal("//modules/autoupdate/system_build"),
				'host' => $domainsCollection->getDefaultDomain()->getHost(),
				'ip' => getServer('SERVER_ADDR'),
				'key' => $regedit->getVal("//settings/keycode")
			);
			$url = UPDATE_SERVER . "?" . http_build_query($info, '', '&');

			$result = $this->getFile($url);

			if (!$result) {
				throw new publicAdminException(getLabel("label-errors-13054"), 13054);
			}

			$xml = new DOMDocument();
			if (!$xml->loadXML($result)) {
				throw new publicAdminException(getLabel("label-errors-13055"), 13055);
			}

			$xpath = new DOMXPath($xml);

			// Проверяем, возможно сервер возвратил ошибку.
			$errors = $xpath->query('error');

			if ($errors->length != 0) {
				/** @var DomElement $error */
				$error = $errors->item(0);
				$code = $error->getAttribute("code");
				throw new publicAdminException(getLabel("label-errors-" . $code), $code);
			}

			$modules = $xpath->query("module");
			if ($modules->length==0) {
				throw new publicAdminException(getLabel("label-errors-13056"), 13056);
			}

			$moduleName = strtolower($moduleName);

			$modules = $xpath->query("module[@name='" . $moduleName . "']");
			if ($modules->length != 0) {
				/** @var DomElement $module */
				$module = $modules->item(0);

				if ($module->getAttribute("active")!="1") {
					throw new publicAdminException(getLabel("label-errors-13057"), 13057);
				}
			}
		}

		/**
		 * Выполняет запрос к серверу обновлений
		 *
		 * @param mixed $url - сформированная строка запроса
		 * @throws publicAdminException
		 * @return string;
		 */
		private function getFile($url) {
			try {
				return umiRemoteFileGetter::get($url);
			} catch (Exception $e) {
				throw new publicAdminException(getLabel("label-errors-13041"), 13041);
			}
		}

		/**
		 * Проверяет, является ли URL gateway
		 * @internal
		 *
		 * @return bool
		 */
		private function isGateway() {
			$gates = array(
				array('exchange', 'auto'),
				array('exchange', 'export1C'),
				array('users', 'login_do'),
				array('photoalbum', 'picasa'),
			);

			return array_search(
				array($this->getCurrentModule(), $this->getCurrentMethod()),
				$gates
			) !== false;
		}

		/**
		 * Подготавливает и возвращает глобальные переменные в режиме работы со стороны админки
		 * @throws coreException
		 * @return array
		 */
		private function prepareAdminSideGlobalVariables() {
			if (!$this->isGateway() && strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
				if (!def_module::checkHTTPReferer()) {
					throw new coreException(getLabel('error-users-non-referer', 'users'));
				}
				if (!self::isCSRFTokenValid()) {
					throw new coreException('CSRF Protection');
				}
			}

			$permissions = permissionsCollection::getInstance();
			$domains = domainsCollection::getInstance();
			$regedit = regedit::getInstance();

			$result = array(
				'@module' => $this->current_module,
				'@method' => $this->current_method,
				'@lang' => $this->current_lang->getPrefix(),
				'@lang-id' => $this->current_lang->getId(),
				'@pre-lang' => $this->pre_lang,
				'@domain' => $this->current_domain->getHost(),
				'@domain-id' => $this->current_domain->getId(),
				'@session-lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 0,
				'@system-build' => $regedit->getVal("//modules/autoupdate/system_build"),
				'@referer-uri' => $this->getCalculatedRefererUri(),
				'@user-id' => $permissions->getUserId(),
				'@interface-lang' => ulangStream::getLangPrefix(),
				'@csrf' => getArrayKey($_SESSION, 'csrf_token')
			);

			if (defined('CURRENT_VERSION_LINE') && CURRENT_VERSION_LINE == 'demo') {
				$result['@demo'] = 1;
			}

			if ($requestUri = getServer('REQUEST_URI')) {
				$requestUriInfo = parse_url($requestUri);
				$requestUri = getArrayKey($requestUriInfo, 'path');
				$queryParams = getArrayKey($requestUriInfo, 'query');
				if ($queryParams) {
					parse_str($queryParams, $queryParamsArr);
					if (isset($queryParamsArr['p'])) {
						unset($queryParamsArr['p']);
					}
					if (isset($queryParamsArr['xmlMode'])) {
						unset($queryParamsArr['xmlMode']);
					}

					$queryParams = http_build_query($queryParamsArr, '', '&');
					if ($queryParams) {
						$requestUri .= '?' . $queryParams;
					}
				}
				$result['@request-uri'] = $requestUri;
			}

			$result['@edition'] = CURRENT_VERSION_LINE;
			$result['@disableTooManyChildsNotification'] = (int) mainConfiguration::getInstance()->get('system', 'disable-too-many-childs-notification');

			$isUserAdmin = $permissions->isAdmin();

			if (system_is_allowed($this->current_module, $this->current_method)) {
				// execute admin mode method
				try {
					if ($module = $this->getModule($this->current_module)) {
						$module->cms_callMethod($this->current_method, null);
					}

					$result['data'] = $this->adminDataSet;
				} catch (publicException $e) {
					$result['data'] = $e;
				}
			} elseif ($isUserAdmin) {
				$result['data'] = new requreMoreAdminPermissionsException(getLabel("error-require-more-permissions"));
				/** @var HTTPOutputBuffer $buffer */
				$buffer = OutputBuffer::current('HTTPOutputBuffer');
				$buffer->status('403 Forbidden');
			} elseif ($this->current_module != 'events' && $this->current_method != 'last') {
				/** @var HTTPOutputBuffer $buffer */
				$buffer = OutputBuffer::current('HTTPOutputBuffer');
				$buffer->status('403 Forbidden');
			}

			if (!is_null($domainFloated = getRequest('domain'))) {
				$result['@domain-floated'] = $domainFloated;
				$result['@domain-floated-id'] = $domains->getDomainId($domainFloated);
			} else {
				if ($this->currentEditElementId) {
					$element = umiHierarchy::getInstance()->getElement($this->currentEditElementId);
					if ($element instanceof umiHierarchyElement) {
						$domain = $domains->getDomain($element->getDomainId());

						if ($domain instanceof domain) {
							$result['@domain-floated'] = $domainFloated = $domain->getHost();
						}
					}
				} else {
					$result['@domain-floated'] = $result['@domain'];
				}
			}
			return $result;
		}

		/**
		 * Подготавливает и возвращает глобальные переменные в режиме работы со стороны сайта для режима TPL.
		 * @throws coreException если нет модуля пользователи
		 * @return array
		 */
		private function prepareClientSideGlobalVariablesForTPL() {
			$permissions = permissionsCollection::getInstance();

			$currentModule = $this->getCurrentModule();
			$currentMethod = $this->getCurrentMethod();
			$elementId = $this->getCurrentElementId();
			$userId = $permissions->getUserId();

			// check permissions
			$notPermitted = true;
			if ($permissions->isAllowedMethod($userId, $currentModule, $currentMethod)) {
				$notPermitted = false;
				if ($elementId) {
					list($r) = $permissions->isPageReadable($elementId);
					$notPermitted = !$r;
				}
			}
			// если нет прав на текущую страницу либо на доступ к текущему методу
			if ($notPermitted) {
				header("Status: 401 Unauthorized");
				$this->setCurrentModule('users');
				$this->setCurrentMethod('login');

				/** @var users $moduleUsers */
				if (!$moduleUsers = $this->getModule('users')) {
					throw new coreException('Module "users" not found.');
				}

				return array('content' => $moduleUsers->login());
			}

			$module = $this->getModule($currentModule);
			try {
				$content = $module->cms_callMethod($currentMethod, array());
			} catch (publicException $e) {
				$content = $e->getMessage();
			}

			return array('content' => $content);
		}

		/**
		 * Подготавливает и возвращает глобальные переменные в режиме работы со стороны сайта для режима XSLT.
		 * @throws coreException
		 * @return array
		 */
		private function prepareClientSideGlobalVariablesForXSLT() {
			$globalVariables = array();

			$permissions = permissionsCollection::getInstance();
			$regedit = regedit::getInstance();
			$objectsCollection = umiObjectsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();

			$userId = $permissions->getUserId();
			$elementId = $this->getCurrentElementId();

			$currentModule = $this->getCurrentModule();
			$currentMethod = $this->getCurrentMethod();

			// check permissions
			$notPermitted = true;
			if ($permissions->isAllowedMethod($userId, $currentModule, $currentMethod)) {
				$notPermitted = false;
				if ($elementId) {
					list($r) = $permissions->isPageReadable($elementId);
					if (!$r) {
						$notPermitted = true;
						$globalVariables['attribute:not-permitted'] = 1;
					}
				}
			}

			if ($notPermitted) {
				$currentModule = "users";
				$currentMethod = "login";

				$this->setCurrentModule($currentModule);
				$this->setCurrentMethod($currentMethod);
			}

			$this->currentHeader = def_module::parseTPLMacroses(macros_header());

			$globalVariables += array(
				'@module' => $currentModule,
				'@method' => $currentMethod,
				'@domain' =>  $this->getCurrentDomain()->getHost(),
				'@system-build' => $regedit->getVal("//modules/autoupdate/system_build"),
				'@lang' => $this->getCurrentLang()->getPrefix(),
				'@pre-lang' => $this->pre_lang,
				'@header' => $this->currentHeader,
				'@title' =>  def_module::parseTPLMacroses(macros_title()),
				'@site-name' =>  def_module::parseTPLMacroses(macros_sitename()),
				'meta' => array(
					'keywords' => macros_keywords(),
					'description' => macros_describtion()
				)
			);

			if (defined('CURRENT_VERSION_LINE') and CURRENT_VERSION_LINE=='demo') {
				$globalVariables['@demo'] = 1;
			}

			if (!is_null(getRequest('p'))) {
				$globalVariables['@paging'] = "yes";
			}

			/** @var social_networks $socialModule */
			$socialModule = cmsController::getInstance()->getModule("social_networks");

			if ($socialModule && ($currentSocial = $socialModule->getCurrentSocial())) {
				$globalVariables['@socialId'] = $currentSocial->getId();
			}

			if ($requestUri = getServer('REQUEST_URI')) {
				$requestUriInfo = @parse_url($requestUri);
				$requestUri = getArrayKey($requestUriInfo, 'path');
				$queryParams = getArrayKey($requestUriInfo, 'query');

				// TODO: разобраться, зачем из социального модуля срезаются query params.
				if ($socialModule && ($currentSocial = $socialModule->getCurrentSocial())) {
					$queryParams = '';
				}

				if ($queryParams) {
					parse_str($queryParams, $queryParamsArr);
					if(isset($queryParamsArr['p'])) unset($queryParamsArr['p']);
					if(isset($queryParamsArr['xmlMode'])) unset($queryParamsArr['xmlMode']);

					$queryParams = http_build_query($queryParamsArr, '', '&');
					if($queryParams) $requestUri .= '?' . $queryParams;
				}
				$globalVariables['@request-uri'] = $requestUri;
			}

			/** @var users $usersModule */
			$usersModule = $this->getModule('users');
			// user info
			$userInfo = array();
			$userId = $usersModule->user_id;
			$userInfo['@id'] = $userId;
			$userType = 'guest';
			if ($permissions->isAuth() && ($user = $objectsCollection->getObject($userId))) {
				if (getRequest('mobile_application') == 'true') {
					if (! regedit::getInstance()->getVal('//modules/emarket/')) {
						$globalVariables['data']['error'] = getLabel("error-module-emarket-absent");
						return $globalVariables;
					} elseif (!$permissions->isAllowedMethod($userId, 'emarket', 'mobile_application_get_data')) {
						$globalVariables['data']['error'] = getLabel("error-mobile-application-not-allowed");
						return $globalVariables;
					}
				}
				/**
				 * @var umiObject $user
				 */
				$userType = 'user';
				$userInfo['@status'] = 'auth';
				$userInfo['@login'] = $user->login;
				$userInfo['xlink:href'] = $user->xlink;
				if ($permissions->isAdmin()) {
					$userType = 'admin';
					if ($permissions->isSv()) $userType = 'sv';
				}
			}
			$userInfo['@type'] = $userType;

			if ($geoip = $this->getModule("geoip")) {
				/**
				 * @var geoip $geoip
				 */
				$geoinfo = $geoip->lookupIp();
				if (!isset($geoinfo['special'])) {
					$userInfo['geo'] = array(
						'country'	=> $geoinfo['country'],
						'region'	=> $geoinfo['region'],
						'city'		=> $geoinfo['city'],
						'latitude'	=> $geoinfo['lat'],
						'longitude'	=> $geoinfo['lon']
					);
				} else {
					$userInfo['geo'] = array('special' => $geoinfo['special']);
				}

			}

			$globalVariables['user'] = $userInfo;

			if ($elementId && ($element = $hierarchy->getElement($elementId))) {

				$parentElements = $hierarchy->getAllParents($elementId);

				$parentsInfo = array();
				foreach ($parentElements as $parentElementId) {
					if ($parentElementId == 0) {
						continue;
					}

					if ($parentElement = $hierarchy->getElement($parentElementId)) {
						$parentsInfo[] = $parentElement;
					}
				}
				$globalVariables += array(
					'@pageId' => $elementId,
					'parents' => array(
						'+page' => $parentsInfo
					),
					'full:page' => $element
				);

				def_module::pushEditable($currentModule, $currentMethod, $elementId);

			} elseif ($currentModule == 'content' && $currentMethod == 'content') {
				/** @var HttpOutputBuffer $buffer */
				$buffer = outputBuffer::current();
				$buffer->status("404 Not Found");
				$globalVariables['@method'] = "notfound";
			} elseif (!$notPermitted && $this->isAllowedExecuteMethod($currentModule, $currentMethod)) {
				// execute site mode method
				try {
					$path = getRequest('path');
					// secure path
					$pathParts = explode("/", $path);
					if (isset($pathParts[0]) && $pathParts[0] == $this->current_lang->getPrefix()) {
						$pathParts = array_slice($pathParts, 1);
					}
					if (count($pathParts) < 2) {
						throw new coreException("Invalid udata path");
					}
					$pathParts[0] = $currentModule;
					$pathParts[1] = $currentMethod;
					$path = 'udata://' . implode("/", $pathParts);

					$globalVariables['xml:data'] = $this->executeStream($path);
				} catch (publicException $e) {
					$globalVariables['data'] = $e;
				}
			}
			return $globalVariables;
		}

		private function init() {
			$this->detectMode();
			showWorkTime("cmscontroller detect mode");

			$this->detectDomain();
			showWorkTime("cmscontroller detect domain");

			$this->detectLang();
			showWorkTime("cmscontroller detect lang");

			$this->loadLangs();
			showWorkTime("cmscontroller load lang");

			cacheFrontend::$currentlangId = $this->getCurrentLang()->getId();
			cacheFrontend::$currentDomainId = $this->getCurrentDomain()->getId();

			$LANG_EXPORT = array();

			$langFile = CURRENT_WORKING_DIR . "/classes/modules/lang.php";
			if (file_exists($langFile)) {
				require $langFile;
			}
			$this->langs = array_merge($this->langs, $LANG_EXPORT);

			$ext_lang = CURRENT_WORKING_DIR . "/classes/modules/lang." . $this->getCurrentLang()->getPrefix() . ".php";
			if (file_exists($ext_lang)) {
				require $ext_lang;
				$this->langs = array_merge($this->langs, $LANG_EXPORT);
			}

			$this->errorUrl = getServer('HTTP_REFERER');
			$this->doSomething();
			$this->calculateRefererUri();
		}

		private function detectDomain() {
			$domains = domainsCollection::getInstance();
			$host = getServer('HTTP_HOST');
			$mirror = false;

			if ($domain_id = $domains->getDomainId($host)) {
				$domain = $domains->getDomain($domain_id);
				$mirror = true;
			} else {
				$domain = $domains->getDefaultDomain();

				if (!$domain instanceof domain) {
					throw new coreException("Default domain could not be found");
				}
			}

			if ($host != $domain->getHost()) {
				$config = mainConfiguration::getInstance();

				$primaryDomainRedirect = $config->get('seo', 'primary-domain-redirect');

				if ($primaryDomainRedirect == 1) {
					$uri = 'http://' . $domain->getHost() . getServer('REQUEST_URI');
					outputBuffer::current()->redirect($uri);
				} elseif ($primaryDomainRedirect == 2 && !$mirror) {
					/** @var HttpOutputBuffer $buffer */
					$buffer = outputBuffer::current();
					$buffer->status('404 Not Found');
					$buffer->option('generation-time', false);
					ob_start();
					require CURRENT_WORKING_DIR . "/errors/invalid_domain.html";
					$content = ob_get_clean();
					$buffer->push($content);
					$buffer->end();
				}
			}

			if (is_object($domain)) {
				$this->current_domain = $domain;
				return true;
			} else {
				$domain = $domains->getDefaultDomain();
				if ($domain instanceof domain) {
					$this->current_domain = $domain;
					$domain->addMirrow($host);
					return false;
				} else {
					throw new coreException("Current domain could not be found");
				}
			}
		}

		private function detectLang() {
			$LangIDs = getRequest('lang_id');

			$langId = false;
			if ($LangIDs != null) {
				if (is_array($LangIDs)) {
					list($LangIDs) = $LangIDs;
				}
				$langId = intval($LangIDs);
			} else if (!is_null(getRequest('links')) && is_array($rel = getRequest('rel'))) {
				if (sizeof($rel) && ($elementId = array_pop($rel))) {
					$element = umiHierarchy::getInstance()->getElement($elementId, true);
					if ($element instanceof umiHierarchyElement) {
						$langId = $element->getLangId();
					}
				}
			} else {
				list($sub_path) = $this->getPathArray();
				$langId = langsCollection::getInstance()->getLangId($sub_path);
			}

			if (!langsCollection::getInstance()->getDefaultLang()) {
				throw new coreException('Cannot find default language');
			}

			if (($this->current_lang = langsCollection::getInstance()->getLang($langId)) === false ) {
				if ($this->current_domain) {
					if ($langId = $this->current_domain->getDefaultLangId()) {
						$this->current_lang = langsCollection::getInstance()->getLang($langId);
					} else {
						$this->current_lang = langsCollection::getInstance()->getDefaultLang();
					}
				} else {
					$this->current_lang = langsCollection::getInstance()->getDefaultLang();
				}
			}

			if ($this->current_lang->getId() != $this->current_domain->getDefaultLangId()) {
				$this->pre_lang = "/" . $this->current_lang->getPrefix();
				$_REQUEST['pre_lang'] = $this->pre_lang;
			}
		}

		/**
		 * Определяет текущий шаблонизатор.
		 * @return umiTemplater
		 */
		private function detectCurrentTemplater() {
			if (defined('VIA_HTTP_SCHEME') && VIA_HTTP_SCHEME) {
				return $this->current_templater = $this->initHTTPSchemeModeTemplater();
			} elseif ($this->current_mode == 'admin') {
				return $this->current_templater = $this->initAdminModeTemplater();
			} else {
				return $this->current_templater = $this->initSiteModeTemplater();
			}
		}

		/**
		 * Инициализируем шаблонизатор для режима работы VIA_HTTP_SCHEME
		 * @return umiTemplater
		 */
		private function initHTTPSchemeModeTemplater() {
			outputBuffer::contentGenerator('XSLT, HTTP SCHEME MODE');
			return umiTemplater::create('XSLT');
		}

		/**
		 * Инициализируем шаблонизатор для Site Mode,
		 * определяем шаблон и возвращаем инстанс соответсвующего шаблонизатора
		 * @return umiTemplater
		 */
		private function initSiteModeTemplater() {
			$template = $this->detectCurrentDesignTemplate();
			// шаблон не определен, выдаем ошибку, завершаем работу
			if (!$template instanceof template) {
				$buffer = outputBuffer::current();
				$buffer->clear();
				$buffer->push(file_get_contents(SYS_ERRORS_PATH . 'no_design_template.html'));
				$buffer->end();
			}

			$templaterType = $template->getType();
			if ($templaterType == 'xsl') $templaterType = 'XSLT';
			if ($templaterType == 'tpls') $templaterType = 'TPL';

			$templaterType = strtoupper($templaterType);
			outputBuffer::contentGenerator($templaterType . ', SITE MODE');

			return umiTemplater::create($templaterType, $template->getFilePath());
		}

		/**
		 * Инициализируем шаблонизатор для Admin Mode
		 */
		private function initAdminModeTemplater() {
			$config = mainConfiguration::getInstance();
			$skinPath = $config->includeParam('templates.skins', array('skin' => system_get_skinName()));
			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();

			$isAllowed = $permissions->isAllowedMethod($userId, $this->current_module, $this->current_method);

			// TODO: вынести в конфиг все названия шаблонов
			$fileName = 'main.xsl';

			if (!$permissions->isAdmin(false, true) || !$isAllowed) {

				if ($permissions->isAuth()) {
					$sqlWhere = "owner_id = {$userId}";
					$userGroups = umiObjectsCollection::getInstance()->getObject($userId)->getValue('groups');
					foreach ($userGroups as $userGroup) {
						$sqlWhere .= " or owner_id = {$userGroup}";
					}

					// TODO: убрать прямые запросы к БД
					$sql = "SELECT `module` FROM cms_permissions WHERE (" . $sqlWhere . ") and (method = '' or method is null)";
					$result = l_mysql_query($sql);

					if (mysql_num_rows($result) !== 0) {
						$regedit = regedit::getInstance();
						while ($row = mysql_fetch_array($result)){
							$module = $row[0];
							$method = $regedit->getVal("//modules/{$module}/default_method_admin");
							if ($permissions->isAllowedMethod($userId, $module, $method)) {
								def_module::redirect('http://' . $this->getCurrentDomain()->getHost() . '/admin/'. $module . '/' . $method . "/");
								break;
							}
						}
					}
				}
				$fileName = 'main_login.xsl';
			}
			$templateSource = $skinPath . $fileName;

			if (!is_file($templateSource)) {
				throw new coreException('Template "' . $templateSource . '" not found.');
			}

			outputBuffer::contentGenerator('XSLT, ADMIN MODE');

			return umiTemplater::create('XSLT', $templateSource);
		}

		private function getPathArray() {
			$path = getRequest('path');
			$path = trim($path, "/");

			$urlSuffix = mainConfiguration::getInstance()->get('seo', 'url-suffix');
			$pos = strrpos($path, $urlSuffix);
			if ($pos && ($pos + strlen($urlSuffix) == strlen($path))) {
				$path = substr($path, 0, $pos);
			}

			return explode("/", $path);
		}

		private function detectMode() {
			if (isset($_SERVER['argv']) && 1<=count($_SERVER['argv'])
				&& !(isset($_SERVER['QUERY_STRING']) && $_SERVER['argv'][0]==$_SERVER['QUERY_STRING'])) {
				$this->setCurrentMode("cli");
				cacheFrontend::$cacheMode = true;
				return;
			}

			$path_arr = $this->getPathArray();

			if (sizeof($path_arr) < 2) {
				$path_arr[1] = NULL;
			}

			list($sub_path1, $sub_path2) = $path_arr;

			if ($sub_path1 == "admin" || $sub_path2 == "admin") {
				$this->setCurrentMode("admin");
				cacheFrontend::$adminMode = true;
			} else {
				$this->setCurrentMode("");
				cacheFrontend::$cacheMode = true;
				cacheFrontend::$adminMode = false;
			}
		}

		private function getSubPathType($sub_path) {
			$regedit = regedit::getInstance();

			if (!$this->current_module) {

				if ($sub_path == "trash") {
					def_module::redirect($this->pre_lang . "/admin/data/trash/");
				}

				if ($regedit->getVal("//modules/" . $sub_path, true)) {
					$this->setCurrentModule($sub_path);
					return "MODULE";
				}
			}

			if ($this->current_module && !$this->current_method) {
				$this->setCurrentMethod($sub_path);
				return "METHOD";
			}

			if ($this->current_module && $this->current_method) {
				return "PARAM";
			}

			return "UNKNOWN";
		}

		private function reset() {
			$this->current_module = $this->current_method = '';

			for ($i=0;$i<10;$i++) {
				if (isset($_REQUEST['param'.$i])) {
					unset($_REQUEST['param'.$i]);
				}
				else break;
			}
		}
	};
?>