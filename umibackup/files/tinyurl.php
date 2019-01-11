<?php

if (!isset($_REQUEST['id'])) die;

$id=intval($_REQUEST['id']);

require_once('standalone.php');

$hierarchy = umiHierarchy::getInstance();
$url = $hierarchy->getPathById($id);

if ($url) {
	header ("Location:".$url);
}
else {
	header ("Status: 404 Not Found",true,404);
}

?>