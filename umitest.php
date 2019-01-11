<?php define('UMITEST_VERSION', '1.0.8');
	
	/*
		project: UMI Test 
		author: JFima 
		24.06.2008 Проект инициирован
		26.06.2008 Добавлено тестирование файловой системы
		17.09.2008 Добавлена проверка real_ip
		27.10.2008 Добавлена проверка на существование константы CURRENT_VERSION_LINE
		
	*/
	header("Content-type: text/html; charset=utf-8");

	error_reporting(E_ALL);
	$umicms_root_dir = $current_dir = dirname(dirname(__FILE__));
	$sCurrentDir = dirname(__FILE__);
	$_REQUEST['path'] = "/admin/";
	chdir($umicms_root_dir.'/');
	ini_set('include_path', $umicms_root_dir.'/');

	

function colorize($txt, $bool) {
	return '<span class="'.($bool?'c_true':'c_false').'">' . $txt . '</span>';	
}

function print_param($bool, $true_str, $false_str = false) {
	return colorize(($bool?$true_str:($false_str?$false_str:$true_str)),$bool);
}

	$C_TITLE = "UMI Test " . UMITEST_VERSION;
	$C_H1 = "проверка настроек сервера";

	// session check
	session_start();
	$session_test_result = false;
	if(isset($_GET['sessiontest'])) {
		if(isset($_SESSION['sessiontest']))   $session_test_result = true;
	}
	else {
		$_SESSION['sessiontest'] = time();
		header("Location: umitest.php?sessiontest");
	}
	$session_test_result_txt = print_param($session_test_result, "тест пройден", "тест провален");
	$session_save_path = ini_get("session.save_path");
	//$session_save_path = session_save_path();  
	$is_valid_session_save_path = (bool)$session_save_path && is_writeable($session_save_path) && is_readable($session_save_path );
	$session_save_path_txt = 
  	print_param($is_valid_session_save_path,($session_save_path ? $session_save_path:"путь не определен в файле php.ini"));

	//
	$uname = PHP_OS;	
	$php_server_txt = "$uname";
	$php_sapi_name = php_sapi_name();
	$php_sapi_name_txt = colorize($php_sapi_name,true);
	
	$php_version = preg_replace('/[a-z-]/', '', phpversion());
  
	$is_valid_php_version = 
		version_compare($php_version,'5.0.4', '>' ) &&
		version_compare($php_version,'5.2.0', '!=');
  
  $php_version_txt = print_param($is_valid_php_version,$php_version);
  
	$safe_mode =  (int) ini_get('safe_mode');
	$safe_mode_txt = print_param(!$safe_mode, "Выключен","Включен");
	

	$cd_r = (int) is_readable($current_dir);
	$cd_w = (int) is_writeable($current_dir);

	$cd_r_txt = print_param($cd_r, "Разрешено", "Запрещено <a href=\"@\" onclick='javascript: return switchLog(\"permsFix\");'>подробнее</a>");
	$cd_w_txt = print_param($cd_w, "Разрешено", "Запрещено <a href=\"@\" onclick='javascript: return switchLog(\"permsFix\");'>подробнее</a>");
	$umicms_root_dir_txt = print_param($cd_r && $cd_w, $umicms_root_dir);

	//
	$test_file_creation = false;
	$test_file_deletion = false;
	$test_dir_name = './'.time();
	$test_file_name = $test_dir_name . '/' . time().'file';
	$test_file_put_content = time();
	$test_dir_creation = @mkdir($test_dir_name);

	$bytes_written = file_put_contents($test_file_name,$test_file_put_content);
	$test_chmod_result = @chmod($test_file_name, 0755);
	if($bytes_written) {
		$test_file_get_content = file_get_contents($test_file_name);
		if($test_file_get_content == $test_file_put_content) { 
			$test_file_creation = true;			
		}
		
		$test_file_deletion = unlink($test_file_name);

	}
	$test_dir_deletion = rmdir($test_dir_name);
	chdir($umicms_root_dir.'/');
	
	$test_dir_creation_txt =  print_param($test_dir_creation, "Создан", "Невозможно создать каталог");	
	$test_file_creation_txt = print_param($test_file_creation, "Создан", "Невозможно создать файл");
	$test_file_deletion_txt = print_param($test_file_deletion, "Удален", "Невозможно удалить файл");
	$test_dir_deletion_txt = print_param($test_dir_deletion, "Удален", "Невозможно удалить каталог");
	$test_chmod_result_txt = print_param($test_chmod_result, "Изменение прав разрешено", "Изменение прав доступа запрещено");
	

	$user = get_current_user();

	$max_etime = ini_get('max_execution_time');
	$is_valid_max_etime = ($max_etime >= 29 || $max_etime == 0);
	$max_etime_txt = print_param($is_valid_max_etime,$max_etime,$max_etime);
	
	// GDLib support check
	if($has_gd = function_exists("gd_info")) {
		$gd_info =gd_info();
		$gd_version = $gd_info['GD Version'];
		$gd_depended_types = array('JPG Support','GIF Read Support','GIF Create Support','PNG Support');
		$is_valid_gd = true;
		foreach($gd_depended_types as $gd_type) {
			if(!isset($gd_info[$gd_type]) && !$gd_info[$gd_type]) $is_valid_gd  = false;
		}
		$gd_info_txt = '';
		foreach($gd_info as $param => $mianing) {
			if(in_array($param , $gd_depended_types) || (gettype($mianing) == 'boolean' && $mianing === true)) 
			$gd_info_txt .= "<li>".print_param($mianing,$param, $param. " (Необходимо добавить поддержку этого формата)")."</li>\n";
		}

		$gd_info_txt = "<ul>$gd_info_txt</ul>";
	}
	
	$has_gd_txt = print_param($has_gd, "Установлена версия $gd_version", "Не установлен");
	
	
	// UMI.CMS version and keycode check
	$coreInited = false;
	if(file_exists("./config.php")) {
		include "./config.php";
		cmsController::getInstance();
		$coreInited = true;
	}
	
	if(file_exists("./standalone.php")) {
		include "./standalone.php";
		cmsController::getInstance();
		$coreInited = true;
	}
	
	if($coreInited) {
		$umicms_keycode = colorize(regedit::getInstance()->getVal("//settings/keycode"),true);
		$umicms_system_build = regedit::getInstance()->getVal("//modules/autoupdate/system_build");
		$umicms_system_edition = regedit::getInstance()->getVal("//modules/autoupdate/system_edition");
		
		
		
		$umicms_system_version = regedit::getInstance()->getVal("//modules/autoupdate/system_version");
		
		
		
		
		$umicms_version = defined('CURRENT_VERSION_LINE') ? strtoupper(constant('CURRENT_VERSION_LINE')):'install update detected';
		
	}
  
  $has_iconv = function_exists("iconv");
  $has_iconv_txt = print_param($has_iconv, "Установлена","Не установлена");
  
  $allow_call_time_pass_reference = ini_get("allow_call_time_pass_reference");
  $allow_call_time_pass_reference_txt = 
  print_param($allow_call_time_pass_reference,"On","Off");

	

  $memory_limit = ini_get("memory_limit");
  $is_valid_memory_limit = substr($memory_limit,0,-1) >= 16;
  $memory_limit_txt = 
  print_param($is_valid_memory_limit , $memory_limit, "$memory_limit (Рекомендуется не менее 16M)");
  
  $ze1_compatibility_mode = ini_get("zend.ze1_compatibility_mode");
  $ze1_compatibility_mode_txt = 
  print_param(!$ze1_compatibility_mode,"Off","On");
  
  // MYSQL InnoDB check  
 	$sql = "SHOW VARIABLES LIKE 'have_innodb'";
	$result = l_mysql_query($sql);
	$row = mysql_fetch_assoc($result);
	$innodb_status = (strtolower($row['Value']) == "yes" ? true:false);
	$innodb_status_txt = print_param($innodb_status,"installed and enabled","INNODB support is not installed or enabled");
  
  // MySQL default character set check
  $mysql_dbname = mysql_result(l_mysql_query("SELECT DATABASE()"),0) or die(mysql_error());
  $row = mysql_fetch_row(l_mysql_query("SHOW CREATE DATABASE {$mysql_dbname}"));
  $mysql_create_database = $row[1];
  
  // MySQL info
  $mysql_status = mysql_stat();
 
  $mysql_get_host_info = mysql_get_host_info();
  $mysql_server_info = mysql_get_server_info();
  $mysql_get_proto_info = mysql_get_proto_info();
  $mysql_get_client_info = mysql_get_client_info();
  $mysql_server_info_txt = $mysql_server_info;
  $mysql_client_info_txt = $mysql_get_client_info;
  $mysql_host_info_txt = "$mysql_get_host_info [protocol version: $mysql_get_proto_info]";
  
  $mysql_max_links = ini_get('mysql.max_links');
  $mysql_max_links_txt = print_param(true, ($mysql_max_links == -1 ? "без ограничений" : $mysql_max_links));
  
  // MySQL connection encoding
  $row = mysql_fetch_row(l_mysql_query("SHOW VARIABLES LIKE 'character_set_connection'"));  
  $mysql_client_encoding = $row[1];
  $mysql_client_encoding_txt = print_param($mysql_client_encoding == 'utf8',$mysql_client_encoding);

  
  // non critical warnings  
  $register_globals = ini_get('register_globals');
  $register_globals_txt = print_param(!$register_globals, "Off", "On");
  
  
  // xslt  support
  if($has_xslt = class_exists('xsltprocessor')) {
    $libxslt_version = LIBXSLT_VERSION;
    $libexslt_version = LIBEXSLT_VERSION;
  }
  $has_xslt_txt = print_param($has_xslt,"Поддерживается [libxslt:$libxslt_version] [libexslt:$libexslt_version]","Не поддерживается. Необходимо установить LIBXSLT.");
    
  
  // xml support check
  if($has_xml = class_exists('DomDocument')) {
    $libxml_version = LIBXML_VERSION;
  }
    $has_xml_txt = print_param($has_xml,"Поддерживается [libxml:$libxml_version]","Не поддерживается. Требуется установить LIBXML.");
  
    
  
  $server_good = (int) 
  ( !$safe_mode && 
    $is_valid_php_version &&
    $cd_r && 
    $cd_w && 
    $has_optimizer &&
    $has_xml &&
    $has_xslt &&
    $is_valid_max_etime &&
    $session_test_result &&
    $innodb_status);
/*var_dump(!$safe_mode ,
    $is_valid_php_version,
    $cd_r ,
    $cd_w ,
    $has_optimizer,
    $has_xml ,
    $has_xslt ,
    $is_valid_max_etime,
    $is_valid_session_save_path,
    $innodb_status);die;*/
	$goodtxt = <<<END
<p><b>Настройки хостинга полностью удовлетворяют требованиям UMI.CMS</b></p>
END;
	$badtxt = <<<END
<b>Некоторые настройки хостинга не удовлетворяют требованиям UMI.CMS</b>
END;

	$server_good_txt = print_param($server_good, $goodtxt, $badtxt);

	$C_CONTENT = <<<END

<ol>
	<li>ОС сервера: $php_server_txt</li>
	<li>API сервера: $php_sapi_name_txt</li>
	<li>Версия PHP: $php_version_txt</li>
	<li>Хост: <strong>{$_SERVER['HTTP_HOST']}</strong></li>
	<li>IP Адрес сервера: {$_SERVER['SERVER_ADDR']}</li>
	<li>IP Адрес клиента: {$_SERVER['REMOTE_ADDR']} (Если адрес изменяется при перезагрузке страницы, то включите <a href="http://www.opennet.ru/base/rel/mod_realip.txt.html">realip_module</a>)</li>
	<li>Реестр UMI.CMS</li>
	<ul>	
		<li>Доменный ключ: $umicms_keycode</li>
		<li>Семейство: $umicms_version</li>
		<li>Редакция: $umicms_system_edition</li>
		<li>Версия системы: $umicms_system_version</li>
		<li>Билд: $umicms_system_build</li>
	</ul>
	<li>Безопасный режим (php safe_mode): $safe_mode_txt</li>
	<li>Корневая директория UMI.CMS: <strong>"$umicms_root_dir_txt"</strong></li>
	<ul>
		<li>Чтение: $cd_r_txt</li>
		<li>Запись: $cd_w_txt</li>
		<li>Владелец: $user</li>
	</ul>
	<li>Тестирование файловой системы:</li>
	<ul>
		<li>Создание каталога: $test_dir_creation_txt</li>
		<li>Создание файла: $test_file_creation_txt</li>
		<li>Изменение прав доступа: $test_chmod_result_txt</li>		
		<li>Удаление файла: $test_file_deletion_txt</li>
		<li>Удаление каталога: $test_dir_deletion_txt</li>
	</ul>

	<li>GDlib: $has_gd_txt</li>
		$gd_info_txt
	<li>Тестирование сессии: $session_test_result_txt</li>
	<li>Максимальное время выполнения скрипта: $max_etime_txt сек</li>
	<li>Библиотека iconv: $has_iconv_txt</li>
	<li>XML: $has_xml_txt</li>		
	<li>XSLT: $has_xslt_txt</li>
	
  <li>php.ini</li>
  <ul>
    <li>allow_call_time_pass_reference = $allow_call_time_pass_reference_txt</li>
    <li>memory_limit = $memory_limit_txt</li>
    <li>zend.ze1_compatibility_mode_txt = $ze1_compatibility_mode_txt</li>
    <li>register_globals = $register_globals_txt</li>
    <li>session_save_path = $session_save_path_txt</li>
  </ul>
  <li>mysql</li>
  <ul>
    <li>База данных: $mysql_create_database </li>
    <li>Версия сервера: $mysql_server_info_txt</li>
    <li>Соединение с хостом: $mysql_host_info_txt</li>
    <li>Версия клиентской библиотеки: $mysql_client_info_txt</li>
    <li>Максимальное кол-во соединений: $mysql_max_links_txt</li>
    <li>Кодировка текущего соединения: $mysql_client_encoding_txt</li>
    <li>Проверка статуса INNODB: $innodb_status_txt</li>  
  </ul>
  
  
</ol>

<div id="permsFix" style="display: none; color: darkblue;">
<p>На корневую директорию, в которую вы устанавливаете UMI.CMS ("$umicms_root_dir") дожны стоять права на чтение и запись (0777).</p>
<p>Чтобы исправить, зайдите через ваш FTP-клиент (например, Far, windows commander, cuteFTP) на этот хостинг, найдите там папку ("$umicms_root_dir"), и зайдите в редактирование аттрибутов этой папки (обычно CTRL+A или ALT+A). Там надо будет либо выделить все галочки, либо ввести 777.</p>
</div>

$server_good_txt

END;
	

?><html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>UMI Test</title>
<style type="text/css">
body, td, select {
	margin: 0px;
	background-color: #F7F7F7;
	font-family: Verdana;
	font-size: 11px;
}


h1 {
	font-family: Verdana, arial, helvetica, sans serif;
	font-weight: bold; font-size: 17px;
	color: #A7A7A7;
}

div.content {
	margin: 10px;
}

input {
	font-size: 11px;
	font-family: verdana;
	margin-top: 2px;
	margin-bottom: 2px;
	border: #C0C0C0 0.5pt solid;

	padding-left: 7px;
	padding-right: 7px;
	padding-bottom: 2px;
	height: 21px;
}

input.text {
	font-size: 11px;
	font-family: verdana;
	margin-top: 2px;
	margin-bottom: 2px;
	border: #C0C0C0 0.5pt solid;

	padding-left: 7px;
	padding-right: 7px;
	padding-bottom: 0px;
	height: 18px;
	width: 100%;
}

textarea.licence {
	width: 550px;
	height: 235px;
}

li {
	margin-top: 3px;
}

.c_true {
	color: green;
}

.c_false {
	color: red;
}

a {
	color: #008000;
	text-decoration: underline;
}

#log, #mods {
	margin-left: 15px;
}

#license_msg {
	color:		red;
	margin:		10px;
}


#installProgressBarBox {
	width:		400px;
	height:		14px;

	border:		#DDD 1px solid;
}

#installProgressBarLine {
	width:		0px;
	height:		14px;

	background-color:	darkblue;
}

#installProgressBarNum {
	position:	absolute;
	margin-left:	200px;
}

#installProgressBarContainer, #installButton {
	margin:		30px;
}
		</style>    
    </head>
      	<body>

<div style="background-color: #FFF;">
<img src="http://www.umi-cms.ru/images/logo.jpg" width="208" height="63" alt="UMI.CMS" />
</div>
<table width="100%" cellspacing="0">
   <tr>
    <td colspan="5" height="3" style="background-image: url('images_install/cms/gray_line.gif')"></td>
   </tr>

   <tr>
    <td colspan="5" height="10" style="background-image: url('images_install/cms/top_line.gif')"></td>
   </tr>

   <tr>
    <td colspan="5" height="3" style="background-image: url('images_install/cms/gray_line.gif')"></td>
   </tr>

   <tr>
    <td colSpan="5" style="background-image: url('images_install/cms/gray_line.gif')" height="1"></td>
   </tr>
</table>

<div class="content">

<h1> <?php echo $C_TITLE; ?><?php if($C_H1) echo " - " . $C_H1; ?></h1>

<table width="100%" cellspacing="0" cellpadding="0" border="0">
	<tr>
		<td width="65%" valign="top">

			<?php echo $C_CONTENT; ?>

		</td>
		<td valign="top">
			<?php  print_r($mysql_status); ?>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="right">
			Copyright &copy; 2008, UMI Soft
		</td>
	</tr>
</table>



	</body>
</html>