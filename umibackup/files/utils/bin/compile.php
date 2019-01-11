<?php
	include "./config.php";
	error_reporting(E_ALL);
	ini_set("display_errors", "1");
	$tmpname = CACHE_CONFIG_PATH . ".tmp";
	
	$requiredClasses = Array(
		'regedit',
		'permissionsCollection',
		'templater',
		'cmsController',
		'umiDate',
		'umiFile',
		'umiImageFile',
		'lang',
		'langsCollection',
		'domain',
		'domainsCollection',
		'template',
		'templatesCollection',
		'umiHierarchyType',
		'umiHierarchyTypesCollection',
		'umiHierarchyElement',
		'umiHierarchy',
		'umiSelection',
		'umiSelectionsParser',
		'umiFieldType',
		'umiField',
		'umiFieldsGroup',
		'umiObjectType',
		'umiObjectProperty',
		'umiObject',
		'umiFieldTypesCollection',
		'umiFieldsCollection',
		'umiObjectTypesCollection',
		'umiObjectsCollection',
		'cacheFrontend',
		'umiEventPoint',
		'umiEventListener',
		'umiEventsController',
		'xslTemplater',
		'xmlTranslator',
		'matches',
		'umiObjectPropertyBoolean',
		'umiObjectPropertyImgFile',
		'umiObjectPropertyRelation',
		'umiObjectPropertyTags',
		'umiObjectPropertyDate',
		'umiObjectPropertyInt',
		'umiObjectPropertyString',
		'umiObjectPropertyText',
		'umiObjectPropertyFile',
		'umiObjectPropertyPassword',
		'umiObjectPropertySwfFile',
		'umiObjectPropertyWYSIWYG',
		'umiObjectPropertyFloat',
		'umiObjectPropertyPrice',
		'umiObjectPropertySymlink'
	);
	
	file_put_contents($tmpname, "");
	foreach($includes as $className => $a) {
		if(in_array($className, $requiredClasses) == false) continue;
		
		foreach($a as $b) {
			appendCommonFile($tmpname, $b);
		}
		
	}

	$cont = file_get_contents($tmpname);
	
	header("Content-type: text/plain; charset=utf-8");
	$cont = preg_replace("/^\t+/m", "", $cont);
	$cont = preg_replace("/^[ ]{2,}/m", "", $cont);
	$cont = preg_replace("|^/\*\*[^\/]*\/$|mu", "", $cont);
	$cont = preg_replace("|^//.*$|mu", "", $cont);
	
	$cont = str_replace('?><?php', '', $cont);
	$cont = str_replace(Array("\r\n", "\n\n"), Array("\n", "\n"), $cont);
	file_put_contents($tmpname, $cont);
	
	if(is_file(CACHE_CONFIG_PATH)) {
		unlink(CACHE_CONFIG_PATH);
	}
	
	copy($tmpname, CACHE_CONFIG_PATH);
	chmod(CACHE_CONFIG_PATH, 0777);
	unlink($tmpname);
	
	echo "Assembling completed";
	
	function appendCommonFile($tmpname, $path) {
		if(is_file($path)) {
			$cont = trim(file_get_contents($path));
			$cc = file_get_contents($tmpname);
			if($cc) {
				$cc .= "";
			}
			file_put_contents($tmpname, $cc . $cont);
			chmod($tmpname, 0777);
		}
	}
?>