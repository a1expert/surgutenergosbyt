<?php

error_reporting(0); // Set E_ALL for debuging

include_once dirname(__FILE__) . str_replace('/', DIRECTORY_SEPARATOR, '/../../../../../') . 'standalone.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderConnector.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinder.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeDriver.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeLocalFileSystem.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeUmiLocalFileSystem.class.php';

// Required for MySQL storage connector
// include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeMySQL.class.php';

/**
 * Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from  '.' (dot)
 *
 * @param  string  $attr  attribute name (read|write|locked|hidden)
 * @param  string  $path  file path relative to volume root directory started with directory separator
 * @return bool
 **/
function access($attr, $path, $data, $volume) {
	return strpos(basename($path), '.') === 0   // if file/folder begins with '.' (dot)
		? !($attr == 'read' || $attr == 'write')  // set read+write to false, other (locked+hidden) set to true
		: ($attr == 'read' || $attr == 'write');  // else set read+write to true, locked+hidden to false
}

$opts = array(
	'debug' => true,
	'roots' => array(
		array(
			'driver'        => 'UmiLocalFileSystem',   // driver for accessing file system (REQUIRED)
			'path'          => CURRENT_WORKING_DIR . '/files/',         // path to files (REQUIRED)
			'URL'           => '/files/', // URL to files (REQUIRED)
			'accessControl' => 'access'             // disable and hide dot starting files (OPTIONAL)
		),
		array(
			'driver'        => 'UmiLocalFileSystem',
			'path'          => CURRENT_WORKING_DIR . '/images',
			'startPath' 	=> CURRENT_WORKING_DIR . '/images/cms/data',
			'URL'           => '/images/',
			'accessControl' => 'access'
		)

	)
);

// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();
