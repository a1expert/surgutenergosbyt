<?php
	require './libs/config.php';
	
	$url = getRequest('url');
	$host = getServer('HTTP_HOST') ? str_replace('www.', '', getServer('HTTP_HOST')) : false;
	$referer = getServer('HTTP_REFERER') ? parse_url(getServer('HTTP_REFERER')) : false;

	$refererHost = false;
	if ($referer && isset($referer['host'])) $refererHost = $referer['host'];

	if (!$url || !$refererHost || !$host || strpos($refererHost, $host) === false){
		header ("HTTP/1.0 404 Not Found");
		exit();
	} else {
		header("Content-type: text/html; charset=utf8");
		$config = mainConfiguration::getInstance();
		$apikey = $config->get('system', 'gsb-apikey');
		if ($apikey) {
			
			require './gsb/phpgsb.class.php';			
		
			$phpgsb = new phpGSB($config->get('connection', 'core.dbname'), $config->get('connection', 'core.login'), $config->get('connection', 'core.password'), $config->get('connection', 'core.host'));			
			$phpgsb->apikey = $apikey;
			$phpgsb->usinglists = array('googpub-phish-shavar','goog-malware-shavar');
			
			if ($phpgsb->doLookup($url)) {
				
				$html = <<<HTML
<html style="margin:0; padding:0;">
	<head></head>
	<body style="margin:0; padding:0;">
		<div style="background: url('//yandex.st/serp/_/VipTApuC_1mDAMs6DzoMLtK89jg.png') repeat-x scroll 20px 0 transparent; height: 16px;"/>
		<div style="float:left; width: 240px; text-align:center; padding-top:32px">
			<a href="http://yandex.ru"><img src="http://avatars.yandex.net/get-avatar/127047242/0636c66ad5ff3c13438c04bb6a6ad7b1.4704-normal" alt="Безопасный Поиск Яндекса"></a>
		</div>
		<div style="float:left; padding-top:32px">
			<p>Сайт <strong>$url</strong> может быть опасен для вашего компьютера</p>
			<h2>Что произошло</h2>
			<p>Яндекс обнаружил на этом сайте вредоносный программный код, который может заразить ваш компьютер вирусом или получить доступ к вашей личной информации.</p>
			<h2>Что делать дальше</h2>
			<a href="$url">Всё равно перейти на опасный сайт</a>
			<p>Переход может нанести вред вашему компьютеру.</p>
		</div>
	</body>
</html>
HTML;
				echo $html;
				exit();
			} 
		}
		
		header('Location:' . $url);
		exit();	 
	}



?>
