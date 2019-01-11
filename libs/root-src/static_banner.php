<?php
	include "./standalone.php";

	/**
	 * @var banners $banners
	 */
	$banners = cmsController::getInstance()->getModule("banners");
	if (!($banners instanceof def_module)) {
		exit();
	}

	header("Content-type: text/javascript; charset=utf-8");

	$place = addslashes(getRequest('place'));
	$currentElementId = intval(getRequest('current_element_id'));

	$result = $banners->insert($place, 0, false, $currentElementId);
	$result = trim($result);
	$result = mysql_real_escape_string($result);
	$result = str_replace('\"', '"', $result);

	echo <<<JS
var response = {
	'place':	'{$place}',
	'data':		'{$result}'
};

if(typeof window.onBannerLoad == "function") {
	window.onBannerLoad(response);
} else {
	var placer = document.getElementById('banner_place_{$place}');
	if(placer) {
		placer.innerHTML = response['data'];
	}
}
JS;
?>