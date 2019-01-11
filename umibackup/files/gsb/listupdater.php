<?php
/*
phpGSB - PHP Google Safe Browsing Implementation
Released under New BSD License (see LICENSE)
Copyright (c) 2010-2012, Sam Cleaver (Beaver6813, Beaver6813.com)
All rights reserved.

UPDATER EXAMPLE
*/
require("phpgsb.class.php");

$ini = parse_ini_file('../config.ini');
			
$phpgsb = new phpGSB($ini['core.dbname'],$ini['core.login'],$ini['core.password'],$ini['core.host']);
$phpgsb->apikey = $ini['gsb-apikey'];
$phpgsb->usinglists = array('googpub-phish-shavar','goog-malware-shavar');
$phpgsb->runUpdate();
$phpgsb->close();
?>
