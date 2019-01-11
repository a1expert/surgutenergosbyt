<?php
	ob_start();
	require CURRENT_WORKING_DIR . "/libs/root-src/standalone.php";
	ob_end_clean();

	session::getInstance();
	$drawer = umiCaptcha::getDrawer();

	$code = $drawer->getRandomCode();
	$_SESSION['umi_captcha'] =  md5($code);

	$drawer->draw($code);
?>