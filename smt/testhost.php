<?php
	class testHost {
		/**
		 * @param mixed $phpInfo
		 * @return testHost
		 */
		function __construct($phpInfo = array(), $domain = null) {
			$this->listErrors = array();
			//$this->parsedPhpInfo = array();
			$this->cli_mode = (boolean) (defined('UMICMS_CLI_MODE') && UMICMS_CLI_MODE);
			$this->domain = $domain;
		}
		
		/**
		 * Запускает тесты
		 */
		function run(){
			$classMethods = get_class_methods($this);
			foreach ($classMethods as $methodName) {
				if ( (preg_match("/^testALL/i", $methodName))
					|| (preg_match("/^testCLI/i", $methodName) && $this->cli_mode)
					|| (preg_match("/^testWWW/i", $methodName) && !$this->cli_mode) ) {
					$this->$methodName();
				}
			}
		}
		
		function getResults(){
			$this->run();
			return $this->listErrors;
		}

		/**
		 * Добавляет сообщение в случае ошибки
		 *
		 * @param Boolean $value Есть ошибка/нет ошибки
		 * @param String $errorCode Код ошибки
		 * @param Boolean $critical Критичность
		 * @param String $errorParams Дополнительные параметры ошибки
		 */
		function assert($value, $errorCode, $critical = true, $errorParams = '') {
			if(!$value) {
				$this->listErrors[] = array($errorCode, $critical, $errorParams);
			}
		}

		/**
		 *  Метод проверяет, запущен ли php под Apache с помощью mod_php
		 */
		function isApacheServer() {
			return extension_loaded( 'apache2handler' );
		}
		
		/**
		 *  Метод проверяет, запущен ли php через php-fpm или другой fcgi сервер
		 */
		function isFPMServer() {
			return extension_loaded( 'cgi-fcgi' );
		}
		
		/**
		 * Проверка IIS
		 */
		function testALLIIS() {
			$uname = strtolower(php_uname());
			$this->assert( strpos($uname, "windows") === false , 13090, false);
		}

		/**
		 * Проверка версии PHP
		 */
		function testALLPhpVersion() {
			$check1 = version_compare(phpversion(), '5.2.11', '>=') && version_compare(phpversion(), '5.6.99', '<=');
			
			$this->assert( $check1, 13000);
			// 5.6 версия выдает некритичную ошибку.
			if( ! $check1 ) {
				$this->assert(version_compare(phpversion(), '5.6.0', '>=') && version_compare(phpversion(), '5.6.99', '<='), 13000, false);
			}
		}


		/**
		 * Проверка отсутствия Suhosin Patch
		 */
		function testALLSuhosin() {
			$this->assert( ! extension_loaded('suhosin'), 13001, false);
		}

		/**
		 * Проверка параметра memory_limit - 32m минимум
		 */
		function testALLMemoryLimit() {
			$memory_limit = ini_get('memory_limit');
			if ( ! $memory_limit ) {
				$this->assert(false, 13002, false);
			} elseif( $memory_limit > 0 ) {
				$last = strtolower( $memory_limit[ strlen($memory_limit) - 1 ] );
				switch($last) {
					case 'g':
						$memory_limit *= 1024;
					case 'm':
						$memory_limit *= 1024;
					case 'k':
						$memory_limit *= 1024;
				}
				$this->assert( $memory_limit >= 32*1024*1024, 13003 );
			}
		}

		/**
		 * Проверка safe_mode=0ff
		 */
		function testALLSafeMode() {
			$this->assert( ! ini_get('safe_mode'), 13005);
		}
		
		/**
		 * Проверка register_globals=0ff ( выключен или отсутствует )
		 */
		function testALLRegisterGlobals() {
			$this->assert( ! ini_get( 'register_globals' ), 13081, false);
		}

		/**
		 * Проверка наличия модуля mod_rewrite в Apache
		 */
		function testWWWModRewrite() {
			if ( $this->isApacheServer() ) {
				$this->assert( in_array( 'mod_rewrite', apache_get_modules() ), 13007 );
			}
		}

		/**
		 * Проверка наличия модуля mod_auth в Apache
		 */
		function testWWWModAuth() {
			if ( $this->isApacheServer() ) {
				$this->assert( in_array( 'mod_auth_basic', apache_get_modules() ), 13009 );
			}
		}

		/**
		 * Проверка наличия библиотек
		 */
		function testALLLibraries() {

			$libraries = array('zlib', 'gd', 'libxml', 'iconv', 'xsl', 'simplexml', 'xmlreader', 'mbstring');

			$errorCounter = 0;
			foreach ($libraries as $key => $val) {

				$critical = true;
				if ($val == 'multibyte') $critical = false;

				$this->assert( extension_loaded( $val ), 13030 + $errorCounter++, $critical);
			}
		}
		
		/**
		 * Проверяет наличие функций для работы с json 
		 * @deprecated С PHP 5.2 PHP поставляется с этими функциями по-умолчанию. Плюс не вижу, чтобы эта функция где-то вызывалась. 
		 */
		function testJSONSupport() {
			if (!function_exists('json_decode') || !function_exists('json_encode')) {
				$this->assert(false, 13028, true);
			}
		}

		/**
		 * Проверка allow_url_fopen=on или наличие библиотеки curl
		 */
		function testALLAllowUrlFopen() {
			if ( ini_get('allow_url_fopen') != 1 ) {
				if ( extension_loaded( 'curl' ) ) {
					$this->checkSession('curl');
				} else {
					$this->assert(false, 13041);
				}
			} else {
				$this->checkSession();
			}
		}

		/**
		 * Проверка текущей директории на запись
		 */
		function testALLPerms() {
			$this->assert(is_writable(dirname(__FILE__)), 13010);
		}

		/**
		 * Проверка работы сессии
		 */
		function checkSession($mode = 'fopen') {
			if (!$this->domain) return;

			file_put_contents(CURRENT_WORKING_DIR . '/umi_smt.php', '<?php 
			@session_start(); 
			$_SESSION["test"] = "test"; 
			$sessionId = session_id();
			@session_write_close(); 
			unset($_SESSION["test"]); 
			@session_start($sessionId);
			echo($_SESSION["test"]);');

			if (!defined("PHP_FILES_ACCESS_MODE")) {
				$mode = substr(decoct(fileperms(__FILE__)), -4, 4);
				chmod(CURRENT_WORKING_DIR . '/umi_smt.php', octdec($mode));
			} else {
				chmod(CURRENT_WORKING_DIR . '/umi_smt.php', PHP_FILES_ACCESS_MODE);
			}

			$checkUrl = 'http://' . $this->domain . '/umi_smt.php';
			$result = '';

			if ($mode == 'fopen') {
				$result = file_get_contents($checkUrl);	
			} else {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $checkUrl);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$result = curl_exec($ch);
			}

			$this->assert($result == 'test', 13083);

			unlink(CURRENT_WORKING_DIR . '/umi_smt.php');
		}

		/**
		 * Проверка коннекта к бд, определение кодировки, разрешений на изменения
		 */
		function testALLConnect() {
			$link = @mysql_connect ($this->host, $this->user, $this->password);

			$db = @mysql_select_db($this->database);

			$this->assert($link &&	$db, 13011);

			if ($link && $db){

				/**
				 * Проверка версии MySQL
				 */
				$mysqlVersion = mysql_get_server_info();

				if (!$mysqlVersion) {
					$this->assert(false, 13070);
				} else {
					preg_match("/[1-9]+.[0-9]+.[0-9]+/", $mysqlVersion, $matches);
					$mysqlVersion = $matches[0];
					$this->assert(version_compare($mysqlVersion, '4.1.0', '>='), 13071);
				}

				$row=mysql_fetch_array(mysql_query("show variables like 'character_set_database'"));
				$this->assert($row[1] == 'utf8', 13012, false);
				
				$time = time();

				$this->assert(mysql_query("create table `test{$time}` (a int not null auto_increment, primary key (a))"), 13013);

				$this->assert(mysql_query("create temporary table `temporary_table{$time}` like `test{$time}`"), 13048);
				mysql_query("drop temporary table `temporary_table{$time}`");

				$this->assert(mysql_query("alter table `test{$time}` ADD b int(7) NULL"), 13014);

				$this->assert(mysql_query("insert into `test{$time}` (b) values (11)"), 13043);

				$this->assert(mysql_query("select * from `test{$time}`"), 13044);

				$this->assert(mysql_query("update `test{$time}` set b=12 where b=11"), 13045);

				$this->assert(mysql_query("delete from `test{$time}`"), 13046);

				$this->assert(mysql_query("SET foreign_key_checks = 1"), 13047);

				$this->assert(mysql_query("drop table `test{$time}`"), 13015);

				$innoDBSupported = false;
				$result = mysql_query("SHOW VARIABLES LIKE 'have_innodb'");
				if (mysql_numrows($result) > 0) {
					$row = mysql_fetch_array($result);
					if (strtolower($row['Value']) == "yes") {
						$innoDBSupported = true;
					}
				} else {
					$result = mysql_query("SHOW ENGINES");
					if (mysql_numrows($result) > 0) {
						while($row = mysql_fetch_assoc($result)) {
							if (strtolower($row['Engine']) == 'innodb' && (strtolower($row['Support']) == 'yes' || strtolower($row['Support']) == 'default')) {
								$innoDBSupported = true;
								break;
							}
						}
					}
				}
				$this->assert($innoDBSupported, 13016);

			}
		}

		function setConnect($host, $user, $password, $database) {
			$this->user = $user;
			$this->host = $host;
			$this->password = $password;
			$this->database = $database;
		}
	};
?>
