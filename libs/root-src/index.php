<?php
	require CURRENT_WORKING_DIR . '/libs/config.php';

	// Patch for Flex files uploader
	if (isset($_SERVER['HTTP_USER_AGENT']) &&
	(strstr($_SERVER['HTTP_USER_AGENT'], 'Shockwave Flash')!== false || strstr($_SERVER['HTTP_USER_AGENT'], 'Adobe Flash Player'))
	&& isset($_GET['PHPSESSID'])) {
		session_id($_GET['PHPSESSID']);
	}
	$config = mainConfiguration::getInstance();
	$buffer = OutputBuffer::current('HTTPOutputBuffer');

	if ($config->get('seo', 'index-redirect') && strpos(trim($_SERVER['REQUEST_URI'], ' /'), "index.php") === 0) {
		$buffer->redirect('/', '301 Moved Permanently', 301);
	}

	// don't use getRequest('p') for this
	if (isset($_GET['p']) && $_GET['p'] === '0' && !getRequest("xmlMode") && !getRequest("jsonMode")) {
		$urlInfo = parse_url($_SERVER['REQUEST_URI']);
		$vars = array();
		parse_str($urlInfo['query'], $vars);
		unset($vars['p']);
		$validUrl = $urlInfo['path'];
		if (count($vars)) {
			$validUrl .= '?' . http_build_query($vars);
		}
		$buffer->redirect($validUrl, '301 Moved Permanently', 301);
	}

	if(PRE_AUTH_ENABLED) {
		umiAuth::tryPreAuth();
	}

	$referer = preg_replace('/^(http(s)?:\/\/)?(www\.)?/', '', getServer("HTTP_REFERER"));
	$host = preg_replace('/^(http(s)?:\/\/)?(www\.)?/', '', getServer("HTTP_HOST"));
	if (strpos($referer, $host) !== 0) {
		$_SESSION["http_referer"] = getServer("HTTP_REFERER");
		$_SESSION["http_target"] = getServer("REQUEST_URI");
	}

	if(!getSession("http_target")) {
		$_SESSION["http_target"] = getServer("REQUEST_URI");
	}

	//Parse [stub] ini section
	if ($config->get('stub', 'enabled')) {
		if(is_array($ips = $config->get('stub', 'filter.ip'))) {
			$enabled = !in_array(getServer('REMOTE_ADDR'), $ips);
		}
		else $enabled = true;

		if ($enabled) {
			$stubFilePath = $config->includeParam('system.stub');
			if (is_file($stubFilePath)) {
				require $stubFilePath;
				exit;
			}
			else throw new coreException("Stub file \"{$stubFilePath}\" not found");
		}
	}

	if ($config->get('kernel', 'matches-enabled')) {
		try {
			$matches = new matches("sitemap.xml");
			$matches->setCurrentURI(getRequest('path'));
			$matches->execute();
		} catch (Exception $e) {
		}

		unset($matches);
	}

	$cmsController = cmsController::getInstance();
	$cmsController->analyzePath();

	// static cache
	if ($config->get('cache', 'static.enabled') && $cmsController->getCurrentElementId()) {
		require_once CURRENT_WORKING_DIR . '/libs/cacheControl.php';
		$staticCache = new staticCache;
		$staticCache->load();
	} else {
		$staticCache = null;
	}

	$currentModule = $cmsController->getCurrentModule();
	$currentMethod = $cmsController->getCurrentMethod();
	$currentTemplater = $cmsController->getCurrentTemplater();

	if (getRequest("xmlMode") == 'force') {
		// flush XML
		$dom = new DOMDocument('1.0', 'utf-8');
		$rootNode = $dom->createElement("result");
		$dom->appendChild($rootNode);
		$rootNode->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');


		// принудительный режим xslt для получения глобальных переменных
		def_module::isXSLTResultMode(true);
		$globalVars = $cmsController->getGlobalVariables();

		$translator = new xmlTranslator($dom);
		$translator->translateToXml($rootNode, $globalVars);

		$buffer->contentType('text/xml');
		$buffer->push($dom->saveXML());
	} elseif (getRequest("jsonMode") == 'force') {

		// flush json
		$buffer->contentType('text/javascript');
		$buffer->option('generation-time', false);

		// принудительный режим xslt для получения глобальных переменных
		def_module::isXSLTResultMode(true);
		$globalVars = $cmsController->getGlobalVariables();

		$translator = new jsonTranslator;
		$result = $translator->translateToJson($globalVars);
		$buffer->push($result);
	} else {
		// enable callstack
		if (!is_null(getRequest('showStreamsCalls'))) {
			$currentTemplater->setEnabledCallStack(!$config->get('debug', 'callstack.disabled'));
		}

		$templatesSource = $currentTemplater->getTemplatesSource();
		list($commonTemplate) = $currentTemplater->getTemplates($templatesSource, 'common');

		$globalVars = $cmsController->getGlobalVariables();
		if ($currentElementId = $cmsController->getCurrentElementId()) {
			$currentTemplater->setScope($currentElementId);
		}
		$result = $currentTemplater->parse($globalVars, $commonTemplate);
		if ($cmsController->getCurrentMode() != 'admin') {
			$result = $currentTemplater->cleanup($result);
		}
		$buffer->push($result);

		// flush streams calls
		if (!is_null(getRequest('showStreamsCalls'))) {
			$buffer->contentType('text/xml');
			$buffer->clear();
			$buffer->push($currentTemplater->getCallStackXML());
			$buffer->end();
		}
	}

	if ($cmsController->getCurrentMode() != 'admin' && regedit::getInstance()->getVal("//modules/stat/collect") && $statistics = $cmsController->getModule('stat')) {
		$statistics->pushStat();
	}

	if ($staticCache instanceof staticCache) {
		$staticCache->save($buffer->content());
	}

	$buffer->end();
?>