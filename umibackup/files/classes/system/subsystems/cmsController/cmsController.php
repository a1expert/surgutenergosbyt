<?php
	/**
	 *
	 * TODO PHPDoc's, sort methods
	 *
	 */
	class cmsController extends singleton implements iSingleton, iCmsController {
		protected
				$modules = array(),
				$current_module = false,
				$current_method = false,
				$current_mode = false,
				$current_element_id = false,
				$current_lang = false,
				$current_domain = false,
				$current_templater = false,
				$calculated_referer_uri = false,
				$modulesPath,
				$url_prefix = '',
				$adminDataSet = array();


		public
				$parsedContent = false,
				$currentTitle = false,
				$currentHeader = false,
				$currentMetaKeywords = false,
				$currentMetaDescription = false,
				$currentEditElementId = false,

				$langs = array(),
				$langs_export = array(),
				$pre_lang = "",
				$errorUrl, $headerLabel = false;

		public		$isContentMode = false;

		public static $IGNORE_MICROCACHE = false;

		protected function __construct() {
			$config = mainConfiguration::getInstance();
			showWorkTime("cmscontroller mainconfig init");
			$this->modulesPath = $config->includeParam('system.modules');
			showWorkTime("cmscontroller includeparam");
			$this->init();
			showWorkTime("cmscontroller init");
		}

		/**
		* @desc
		* @return cmsController
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		private function loadModule($module_name) {
			$xpath = "//modules/" . $module_name;

			if (!defined("CURRENT_VERSION_LINE")) {
				define("CURRENT_VERSION_LINE", "");
			}

			if (regedit::getInstance()->getVal($xpath) == $module_name) {
				$module_path = $this->modulesPath . $module_name . "/class.php";
				if (file_exists($module_path)) {
					require_once $module_path;

					if (class_exists($module_name)) {
						$new_module = new $module_name();
						$new_module->pre_lang = $this->pre_lang;
						$new_module->pid = $this->getCurrentElementId();
						$this->modules[$module_name] = $new_module;

						return $new_module;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		public function loadBuildInModule($moduleName) {
			//TODO
		}

		public function getModule($module_name) {
			if (!$module_name) {
				return false;
			}

			if (array_key_exists($module_name, $this->modules)) {
				return $this->modules[$module_name];
			} else {
				return $this->loadModule($module_name);
			}
		}

		public function installModule($installPath) {
			if (!file_exists($installPath)) {
				throw new publicAdminException(getLabel("label-errors-13052"), 13052);
			}
			require_once $installPath;

			preg_match("|\/modules\/(\S+)\/|i", $installPath, $matches);
			$name_by_path = $matches[1];

			if ($name_by_path!=$INFO['name']) {
				throw new publicAdminException(getLabel("label-errors-13053"), 13053);
			}

			// Проверяем, что модуль разрешен для данной системы
			$this->checkModuleByName($name_by_path);

			$this->checkModuleComponents($COMPONENTS);

			def_module::install($INFO);
		}

		/** Проверка наличия всех компонентов модуля
		*
		* @param mixed $module_name - имя модуля
		*/
		private function checkModuleComponents($components) {
			if (!is_array($components)) {
				return false;
			}

			$files = array();
			foreach ($components as $component) {
				$file = preg_replace("/.\/(.+)/", CURRENT_WORKING_DIR . '/' . "$1", $component);
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
		}

		/**
		* Проверяет, что модуль доступен для данной лицензии
		*
		* @param mixed $module_name - имя модуля
		*/
		private function checkModuleByName($module_name) {
			if (!defined("UPDATE_SERVER")) {
				define("UPDATE_SERVER", base64_decode('aHR0cDovL3Vkb2QudW1paG9zdC5ydS91cGRhdGVzZXJ2ZXIv'));
			}

			$regedit = regedit::getInstance();
			$domainsCollection = domainsCollection::getInstance();

			$info = array();
			$info['type']='get-modules-list';
			$info['revision'] = $regedit->getVal("//modules/autoupdate/system_build");
			$info['host'] = $domainsCollection->getDefaultDomain()->getHost();
			$info['ip'] = getServer('SERVER_ADDR');
			$info['key'] = $regedit->getVal("//settings/keycode");
			$url = UPDATE_SERVER . "?" . http_build_query($info, '', '&');

			$result = $this->get_file($url);

			if (!$result) {
				throw new publicAdminException(getLabel("label-errors-13054"), 13054);
			}

			$xml = new DOMDocument();
			if (!$xml->loadXML($result)) {
				throw new publicAdminException(getLabel("label-errors-13055"), 13055);
			}

			$xpath = new DOMXPath($xml);

			// Проверяем, возможно сервер возвратил ошибку.
			$errors = $xpath->query("error");

			if ($errors->length!=0) {
				$code = $errors->item(0)->getAttribute("code");
				throw new publicAdminException(getLabel("label-errors-".$code), $code);
			}

			$modules = $xpath->query("module");
			if ($modules->length==0) {
				throw new publicAdminException(getLabel("label-errors-13056"), 13056);
			}

			$module_name = strtolower($module_name);

			$modules = $xpath->query("module[@name='".$module_name."']");
			if ($modules->length!=0) {
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
		* @return string;
		*/
		private function get_file($url) {
			try {
				$result = umiRemoteFileGetter::get($url);
				return $result;
			} catch (Exception $e) {
				throw new publicAdminException(getLabel("label-errors-13041"), 13041);
			}
		}

		public function getSkinPath() {
			// TODO
		}

		public function getCurrentModule() {
			return $this->current_module;
		}

		public function getCurrentMethod() {
			return $this->current_method;
		}

		public function getCurrentElementId() {
			return $this->current_element_id;
		}

		public function getLang() {
			return $this->current_lang;
		}

		public function getCurrentLang() {
			return $this->getLang();
		}

		public function getCurrentMode() {
			return $this->current_mode;
		}


		public function getCurrentDomain() {
			return $this->current_domain;
		}

		/**
			* Получить текущий шаблонизатор
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
		 * Получить директорию с ресурсами для текущего шаблона
		 * @return string
		*/
		public function getResourcesDirectory() {
			if ($this->getCurrentMode() == 'admin') {
				return false;
			}

			if (($template = $this->detectCurrentDesignTemplate()) instanceof template) {
				return $template->getResourcesDirectory();
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
		 */
		public function getGlobalVariables($forcePrepare = false) {
			static $globalVariables;
			if (!$forcePrepare && !is_null($globalVariables)) return $globalVariables;

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
		 * Подготавливает и возвращает глобальные переменные в режиме работы со стороны админки
		 * @return array
		 */
		private function prepareAdminSideGlobalVariables() {
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
				'@interface-lang' => ulangStream::getLangPrefix()
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
						$module->cms_callMethod($this->current_method, NULL);
					}
					$result['data'] = $this->adminDataSet;
				} catch (publicException $e) {
					$result['data'] = $e;
				}
			} elseif ($isUserAdmin) {
				$result['data'] = new requreMoreAdminPermissionsException(getLabel("error-require-more-permissions"));
			}

			if (!is_null($domainFloated = getRequest('domain'))) {
				$result['@domain-floated'] = $domainFloated;
				$result['@domain-floated-id'] = $domains->getDomainId($domainFloated);
			} else {
				if ($this->currentEditElementId) {
					$element = umiHierarchy::getInstance()->getElement($this->currentEditElementId);
					if ($element instanceof umiHierarchyElement) {
						$domainId = $element->getDomainId();
						$domain = $domains->getDomain($domainId);
						if ($domain instanceof iDomain) {
							$result['@domain-floated'] = $domain_floated = $domain->getHost();
						}
					}
				} else {
					$result['@domain-floated'] = $result['@domain'];
				}
			}
			return $result;
		}

		/**
		 * Подготавливает и возвращает глобальные переменные в режиме работы со стороны сайта
		 * для режима TPL
		 * @return array
		 */
		private function prepareClientSideGlobalVariablesForTPL() {
			$permissions = permissionsCollection::getInstance();

			$currentModule = $this->getCurrentModule();
			$currentMethod = $this->getCurrentMethod();
			$elementId = $this->getCurrentElementId();
			$userId = $permissions->getUserId();

			$content = "";
			// check permissions
			$notPermitted = true;
			if ($permissions->isAllowedMethod($userId, $currentModule, $currentMethod)) {
				$notPermitted = false;
				if ($elementId) {
					list($r) = $permissions->isAllowedObject($userId, $elementId);
					$notPermitted = !$r;
				}
			}
			// если нет прав на текущую страницу либо на доступ к текущему методу
			if ($notPermitted) {
				header("Status: 401 Unauthorized");
				$this->setCurrentModule('users');
				$this->setCurrentMethod('login');
				if (!$moduleUsers = $this->getModule('users')) {
					throw new coreException('Module "users" not found.');
				}
				$content = $moduleUsers->login();
			} else {
				$module = $this->getModule($currentModule);
				try {
					$content = $module->cms_callMethod($currentMethod, array());
				} catch (publicException $e) {
					$content = $e->getMessage();
				}
			}

			return array('content' => $content);
		}

		/**
		 * Подготавливает и возвращает глобальные переменные в режиме работы со стороны сайта
		 * для режима XSLT
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
					list($r) = $permissions->isAllowedObject($userId, $elementId);
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
					$queryParams = "";
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

			// user info
			$userInfo = array();
			$userId = $this->getModule('users')->user_id;
			$userInfo['@id'] = $userId;
			$userType = 'guest';
			if ($permissions->isAuth() && ($user = $objectsCollection->getObject($userId))) {
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
				$buffer = outputBuffer::current();
				$buffer->status("404 Not Found");
				$globalVariables['@method'] = "notfound";
			} elseif (!$notPermitted && $this->isAllowedExecuteMethod($currentModule, $currentMethod)) {
				// execute site mode method
				try {
					$path = getRequest('path');
					// secure path
					$pathParts = explode("/", $path);
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

		/**
		 * Запускает umi-stream, возвращает результат работы
		 * @param $uri
		 * @throws coreException - Если не удалось открыть стрим
		 * @return string - результат работы стрима
		 */
		public function executeStream($uri) {
			if (($data = @file_get_contents($uri)) === false) {
				// bugfix: failed to open stream: infinite recursion prevented
				$uri .= (strpos($uri, "?") === false) ? '?r=' : '&r=';
				$uri .= uniqid('');
				if (($data = @file_get_contents($uri)) === false) {
					throw new coreException("Failed to open udata:// stream");
				}
			}
			return $data;
		}

		/**
		 * Методы, вызов которых надо игнорировать в режиме XSLT
		 * Предназначено для избавления от заплатки $this->breakMe
		 * @static
		 * @var array
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
			$lang_file = CURRENT_WORKING_DIR . "/classes/modules/lang.php";
			if (file_exists($lang_file)) {
				require $lang_file;
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
				if (!$domain instanceof domain) throw new coreException("Default domain could not be found");
			}

			if ($host != $domain->getHost()) {
				$config = mainConfiguration::getInstance();

				$primaryDomainRedirect = $config->get('seo', 'primary-domain-redirect');

				if ($primaryDomainRedirect == 1) {
					$uri = 'http://' . $domain->getHost() . getServer('REQUEST_URI');

					$buffer = outputBuffer::current();
					$buffer->header('Location', $uri);
					$buffer->clear();
					$buffer->end();
				} elseif ($primaryDomainRedirect == 2 && !$mirror) {
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

			$lang_id = false;
			if ($LangIDs != null) {
				if (is_array($LangIDs)) {
					list($LangIDs) = $LangIDs;
				}
				$lang_id = intval($LangIDs);
			} else if (!is_null(getRequest('links')) && is_array($rel = getRequest('rel'))) {
				if (sizeof($rel) && ($elementId = array_pop($rel))) {
					$element = umiHierarchy::getInstance()->getElement($elementId, true);
					if ($element instanceof umiHierarchyElement) {
						$lang_id = $element->getLangId();
					}
				}
			} else {
				list($sub_path) = $this->getPathArray();
				$lang_id = langsCollection::getInstance()->getLangId($sub_path);
			}

			if (!langsCollection::getInstance()->getDefaultLang()) {
				throw new coreException('Cannot find default language');
			}

			if (($this->current_lang = langsCollection::getInstance()->getLang($lang_id)) === false ) {
				if ($this->current_domain) {
					if ($lang_id = $this->current_domain->getDefaultLangId()) {
						$this->current_lang = langsCollection::getInstance()->getLang($lang_id);
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
		 * Определяет текущий шаблон дизайна
		 * @return null|template - текущий шаблон дизайна, либо null
		 */
		public function detectCurrentDesignTemplate() {
			static $currentDesignTemplate = null;
			if ($currentDesignTemplate instanceof template) {
				return $currentDesignTemplate;
			}

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

					if (mysql_num_rows($result) !==0) {
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
			return explode("/", $path);
		}

		private function detectMode() {
			if (isset($_SERVER['argv']) && 1<=count($_SERVER['argv'])
				&& !(isset($_SERVER['QUERY_STRING']) && $_SERVER['argv'][0]==$_SERVER['QUERY_STRING'])) {
				$this->current_mode = "cli";
				cacheFrontend::$cacheMode = true;
				return;
			}

			$path_arr = $this->getPathArray();

			if (sizeof($path_arr) < 2) {
				$path_arr[1] = NULL;
			}

			list($sub_path1, $sub_path2) = $path_arr;

			if ($sub_path1 == "admin" || $sub_path2 == "admin") {
				$this->current_mode = "admin";
				cacheFrontend::$adminMode = true;
			} else {
				$this->current_mode = "";
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

				if ($regedit->getVal("//modules/" . $sub_path)) {
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

		public function analyzePath($reset = false) {
			showWorkTime("analyzePath started");
			$path = getRequest('path');
			$path = trim($path, "/");

			if (!is_null(getRequest('scheme'))) {
				if (preg_replace("/[^\w]/im", "", getRequest('scheme')) == 'upage') {
					preg_match_all("/[\d]+/", $path, $element_id);
					$this->current_element_id = $element_id[0][0];
				}
				return;
			}
			showWorkTime("analyzePath something");
			$regedit = regedit::getInstance();
			showWorkTime("analyzePath regedit init");
			$hierarchy = umiHierarchy::getInstance();
			showWorkTime("analyzePath umiHierarchy init");
			$config = mainConfiguration::getInstance();
			showWorkTime("analyzePath mainConfiguration init");
			$buffer = outputBuffer::current();
			showWorkTime("analyzePath outputBuffer init");

			if ($reset === true) {
				$this->reset();
			}

			if ($config->get('seo', 'folder-redirect')) {
				def_module::requireSlashEnding();
			}

			if ($config->get('seo', 'watch-redirects-history')) {
				redirects::getInstance()->init();
			}

			$path_arr = $this->getPathArray();

			$sz = sizeof($path_arr);
			$url_arr = Array();
			$p = 0;
			for ($i = 0; $i < $sz; $i++) {
				$sub_path = $path_arr[$i];

				if ($i <= 1) {
					if (($sub_path == $this->current_mode) || ($sub_path == $this->current_lang->getPrefix())) {
						continue;
					}
				}

				$url_arr[] = $sub_path;

				$sub_path_type = $this->getSubPathType($sub_path);

				if ($sub_path_type == "PARAM") {
					$_REQUEST['param' . $p++] = $sub_path;
				}
			}

			if (!$this->current_module) {
				if ($this->current_mode == "admin") {
					$module_name = $regedit->getVal("//settings/default_module_admin");
					$this->autoRedirectToMethod($module_name);
				} else {
					$module_name = $regedit->getVal("//settings/default_module");
				}
				$this->setCurrentModule($module_name);
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

			$element_id = false;
			$sz = sizeof($url_arr);
			$sub_path = "";
			for ($i = 0; $i < $sz; $i++) {
				$sub_path .= "/" . $url_arr[$i];

				if (!($tmp = $hierarchy->getIdByPath($sub_path, false, $errors_count))) {
					$element_id = false;
					break;
				} else {
					$element_id = $tmp;
				}
			}

			if ($element_id) {
				if ($errors_count > 0 && !defined("DISABLE_AUTOCORRECTION_REDIRECT")) {
					$path = $hierarchy->getPathById($element_id);

					if ($i == 0) {
						if ($this->isModule($url_arr[0])) {
							$element_id = false;
							break;
						}
					}

					$buffer->status('301 Moved Permanently');
					$buffer->redirect($path);
				}

				$element = $hierarchy->getElement($element_id);
				if ($element instanceof umiHierarchyElement) {
					if ($element->getIsDefault()) {
						$path = $hierarchy->getPathById($element_id);
						$buffer->status('301 Moved Permanently');
						$buffer->redirect($path);
					}
				}
			} elseif (isset($url_arr[0])) {
				if ($this->isModule($url_arr[0])) {
					$module = $this->getModule($url_arr[0]);
					if (isset($url_arr[1]) && !$module->isMethodExists($url_arr[1])) {
						$this->setCurrentModule('content');
						$this->setCurrentMethod('content');
					}
				} else {
					$this->setCurrentModule('content');
					$this->setCurrentMethod('content');
				}
			}

			if (($path == "" || $path == $this->current_lang->getPrefix() ) && $this->current_mode != "admin") {
				if($element_id = $hierarchy->getDefaultElementId($this->getCurrentLang()->getId(), $this->getCurrentDomain()->getId())) {
					$this->current_element_id = $element_id;
				}
			}

			if ($element = $hierarchy->getElement($element_id, true)) {
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

				$this->current_element_id = $element_id;
			}

			if ($this->current_module == "content" && $this->current_method == "content" && !$element_id) {
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
				$module_name = $module[0];

				$lang_path = CURRENT_WORKING_DIR . '/classes/modules/' . $module_name . '/';
				$lang_path .= "lang.php";

				if (file_exists($lang_path)) {
					require $lang_path;
				}

				if (isset($C_LANG)) {
					if (is_array($C_LANG)) {
						$this->langs[$module_name] = $C_LANG;
						unset($C_LANG);
					}
				}

				if (isset($LANG_EXPORT)) {
					if (is_array($LANG_EXPORT)) {
						$this->langs = array_merge($this->langs, $LANG_EXPORT);
						//$this->langs = $this->langs + $LANG_EXPORT;
						unset($LANG_EXPORT);
					}
				}

				$lang_path = CURRENT_WORKING_DIR . '/classes/modules/' . $module_name . '/';
				$lang_path .= "lang." . $this->getCurrentLang()->getPrefix() .".php";

				if (file_exists($lang_path)) {
					require $lang_path;

					if (isset($C_LANG) && is_array($C_LANG)) {
						$this->langs[$module_name] = $C_LANG;
						unset($C_LANG);
					}

					if (isset($LANG_EXPORT) && is_array($LANG_EXPORT)) {
						$this->langs = array_merge($this->langs, $LANG_EXPORT);
						//$this->langs = $this->langs + $LANG_EXPORT;
						unset($LANG_EXPORT);
					}
				}
				showWorkTime("loadLangs ".$module_name." loaded",1);
			}
			// загружаем общие языковые файлы
			$try_path = CURRENT_WORKING_DIR . "/classes/modules/lang." . $this->getLang()->getPrefix() . ".php";
			if (!file_exists($try_path)) {
				$try_path = CURRENT_WORKING_DIR . "/classes/modules/lang.php";
			}

			include_once $try_path;

			if (isset($LANG_EXPORT)) {
				$this->langs = array_merge($this->langs, $LANG_EXPORT);
				unset($LANG_EXPORT);
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


		final private function doSomething () {
			if (defined("CRON") && (constant('CRON') == 'CLI')) {
				return true;
			}

			if (defined("CURRENT_VERSION_LINE")) {
				if (CURRENT_VERSION_LINE != "demo") {
					require CURRENT_WORKING_DIR . "/errors/invalid_license.html";
					exit();
				} else {
					return true;
				}
			}

			if ( !is_writable(SYS_CACHE_RUNTIME) || (file_exists(SYS_CACHE_RUNTIME.'registry') && !is_writable(SYS_CACHE_RUNTIME.'registry')) ) {
				require CURRENT_WORKING_DIR . "/errors/invalid_permissions.html";
				exit();
			}

			$keycode = regedit::getInstance()->getVal("//settings/keycode");

			if($this->doStrangeThings($keycode)) {
				return true;
			}

			$comp_keycode = Array();
			$comp_keycode['pro'] = umiTemplater::getSomething("pro");
			$comp_keycode['shop'] = umiTemplater::getSomething("shop");
			$comp_keycode['lite'] = umiTemplater::getSomething("lite");
			$comp_keycode['start'] = umiTemplater::getSomething("start");
			$comp_keycode['trial'] = umiTemplater::getSomething("trial");

			if (regedit::checkSomething($keycode, $comp_keycode)) {
				return true;
			} else {
				require CURRENT_WORKING_DIR . "/errors/invalid_license.html";
				exit();
			}
		}

		final private function doStrangeThings($keycode) {
			$license_file = SYS_CACHE_RUNTIME . 'trash';
			$cmp_keycode = false;
			$expire = 604800;

			if (file_exists($license_file)) {
				if ((time() - filemtime($license_file)) > $expire) {
					$cmp_keycode = base64_decode(file_get_contents($license_file));
				}
			} else {
				file_put_contents($license_file, base64_encode($keycode));
			}

			if ($cmp_keycode !== false && $keycode) {
				if ($keycode === $cmp_keycode) {
					return true;
				}
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


		protected function autoRedirectToMethod($module) {
			$pre_lang = $this->pre_lang;
			$method = regedit::getInstance()->getVal("//modules/" . $module . "/default_method_admin");

			$url = $pre_lang . "/admin/" . $module . "/" . $method . "/";

			outputBuffer::current()->redirect($url);
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


		public function isModule($module_name) {
			$regedit = regedit::getInstance();

			if ($regedit->getVal('//modules/' . $module_name)) {
				return true;
			} else {
				return false;
			}
		}

		public function setUrlPrefix($prefix = '') {
			$this->url_prefix = $prefix;
		}

		public function getUrlPrefix(){
			return $this->url_prefix ? $this->url_prefix : '';
		}

	};
?>
