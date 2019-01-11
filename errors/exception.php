<?php
	if(!headers_sent()) {
		header("Content-type: text/html; charset=utf-8");
	}
	
	if(!isset($e)) {
		$e = null;
	}

	if(!isset($message)) {
		if($e instanceof Exception) {
			$message = $e->getMessage();
		} else {
			$message = "Error message not provided";
		}
	}
	
	if(!isset($traceAsString)) {
		$traceAsString = 'Backtrace not provided';
	}
	
	$message = htmlspecialchars($message);
	$message = nl2br($message);
	$traceAsString = htmlspecialchars($traceAsString);
	
	if (!DEBUG_SHOW_BACKTRACE && $e instanceof Exception && get_class($e) == 'databaseException') {
		$message = '<p>Произошла критическая ошибка. Скорее всего, потребуется участие разработчиков.  Подробности по ссылке <a title="" target="_blank" href="http://errors.umi-cms.ru/17000/">17000</a></p>';
	}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Неперехваченное исключение</title>
	<link href="/errors/style.css" type="text/css" rel="stylesheet" />
<script type="text/javascript">
	function displayTrace(link) {
		if(link) link.style.display = 'none';
		document.getElementById('trace').style.display = '';
	}
	
	function sendErrorReport() {
		var url = "http://www.umi-cms.ru/errors-gw/accept-bug.php";
		url += "?log=" + sendErrorReport.errorMessage;
		
		var d = new Date;
		url += "&t=" + d.getTime();
		var script = document.createElement('script');
		script.charset = "utf-8";
		script.src = url;
		document.body.appendChild(script);
	}
	
	function solutionFound(solutionText) {
		var obj = document.getElementById('solution');
		obj.style.display = '';
		obj.innerHTML = '<b>Найдено решение для данной проблемы:</b><br />' + solutionText;
	}
</script>
</head>
<body>
	<div class="exception">
		<div id="header">
			<h1>Неперехваченное исключение</h1>
			<a target="_blank" title="UMI.CMS" href="http://umi-cms.ru"><img class="logo" src="/errors/images/main_logo.png" alt="UMI.CMS" /></a>
		</div>
		<div id="message">
			<h2>Ошибка <?php if($e instanceof Exception) { echo "(", get_class($e), ")"; }?>: <?php echo $message; ?></h2>
			<p id="solution" style="display: none;"></p>
				<?php
				if (DEBUG_SHOW_BACKTRACE) {
				?>
					<p>
						<a href="#" onclick="javascript: displayTrace(this);">
							Показать отладочную информацию
						</a>
					</p>

					<div id="trace" class="trace" style="display: none;"><pre><?php echo $traceAsString; ?></pre></div>
				<?php
				}
				?>
		</div>
		<div id="footer">
			<p><a href="http://www.umi-cms.ru/support">Поддержка пользователей UMI.CMS</a></p>
		</div>
	</div>
</body>
</html>
<?php
		if (!defined('CURRENT_WORKING_DIR')) define('CURRENT_WORKING_DIR', dirname(dirname(__FILE__)));

		require_once CURRENT_WORKING_DIR . "/classes/system/utils/logger/iLogger.php";
		require_once CURRENT_WORKING_DIR . "/classes/system/utils/logger/logger.php";
		
		if(!function_exists("tryCreateCrashReport")) {
			function tryCreateCrashReport($message, $traceAsString) {
				$log_dir = CURRENT_WORKING_DIR . "/errors/logs/exceptions/";
				
				if(!is_dir($log_dir)) {
					mkdir($log_dir, 0777, true);
				}
				
				try {
					$logger = new umiLogger($log_dir);
					$logger->pushGlobalEnviroment();
					$logger->push($message);
					$logger->push($traceAsString);
					$logFilePath = $logger->save();
					unset($logger);
				} catch (Exception $e) {
					echo "Can't log exception: ", $e->getMessage();
					exit();
				}
			}
			
			tryCreateCrashReport($message, $traceAsString);
			
			if (DEBUG_SHOW_BACKTRACE) {
				$logContent = $message . "\n\n\n" . $traceAsString;
				if($logContent) {
					$logContent = base64_encode($logContent);
					//$logContent = base64_decode($logContent);
					echo <<<JS
<script type="text/javascript" charset="utf-8">
var errorLogMessage = new String('{$logContent}');
sendErrorReport.errorMessage = encodeURIComponent(errorLogMessage);
setTimeout(sendErrorReport, 500);
</script>
JS;
				}
			}
		}
		
?>