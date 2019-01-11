<?php
	if (!defined('_C_ERRORS')) {
		define('_C_ERRORS', true);
	}

	error_reporting(DEBUG ? (defined('E_DEPRECATED') ? ~E_STRICT & ~E_DEPRECATED : ~E_STRICT) : E_ERROR);
	ini_set("display_errors", "1");

	class umiExceptionHandler {
		/**
		 * Шаблон для вывода Exception
		 *
		 * @var string $templateFile
		 */
		private static $templateFile = NULL;

		/**
		 * Получает шаблон вывода
		 * Устанавливает default шаблон, при отсутвии текущего
		 *
		 * @return string Возвращает шаблон вывода для Exception
		 */
		protected static function getExceptionTemplate() {
			if ( !self::$templateFile ) {
				self::setExceptionTemplate(SYS_ERRORS_PATH . "exception.html.php")
				|| self::setExceptionTemplate(SYS_ERRORS_PATH . "exception.php");
			}

			return self::$templateFile;
		}

		/**
		 * Устанавливает шаблон вывода для Exception
		 * В случае ошибки текущий шаблон будет обнулен
		 *
		 * @param $file Файл шаблона
		 * @return bool TRUE если шаблон установлен, FALSE если файл с шаблоном не найден
		 */
		protected static function setExceptionTemplate($file) {
			if (!file_exists($file) ) {
				self::$templateFile = NULL;
				return false;
			} else {
				self::$templateFile = $file;
			}
		}

		/**
		 * Выбирает требуемый шаблон, в зависимости от режима работы
		 *
		 * @param Exception $e Исключение
		 */
		protected static function printTemplate($e) {
			$exception = new StdClass;
			$exception->code 	= $e->getCode();
			$exception->message = $e->getMessage();
			$exception->type 	= get_class($e);
			if (DEBUG_SHOW_BACKTRACE) {
				$exception->trace = $e->getTrace();
				$exception->traceAsString = $e->getTraceAsString();
			}

			require self::getExceptionTemplate();
		}

		/**
		 * Устанавливает обработчик исключений
		 * Обработчик должен быть статическим методом этого класса
		 *
		 * @param string $exceptionHandler Имя обработчика
		 * @param string $template Шаблон вывода
		 *
		 * @return callable Прошлый обработчик
		 * @throws Exception если обработчика не существует
		 */
		public static function set($exceptionHandler='base', $template = "") {
			$exceptionHandler = $exceptionHandler."Handler";

			if ( method_exists(__CLASS__, $exceptionHandler) ) {
				self::setExceptionTemplate($template);
				return set_exception_handler(array(
							__CLASS__,
							$exceptionHandler
						));
			} else {
				throw new Exception("Error handler not exist");
			}
		}

		/**
		 * Устанавливает прошлый обработчик исключений
		 * @link http://php.net/manual/en/function.restore-exception-handler.php
		 * @return bool Всегда возвращает TRUE
		 */
		public static function restore() {
			return restore_exception_handler();
		}

		/**
		 * Запись исключения в логи
		 *
		 * @param string $message Сообщение об ошибке
		 * @param string $trace Trace ошибки
		 *
		 * @return bool Возвращает TRUE если при записи не произошло ошибок и FALSE в противном случае
		 */
		public static function createCrashReport($message, $trace) {
			$logsDirectory = CURRENT_WORKING_DIR . "/errors/logs/exceptions/";

			if(!is_dir($logsDirectory)) {
				mkdir($logsDirectory, 0777, true);
			}

			try {
				$logger = new umiLogger($logsDirectory);
				$logger->pushGlobalEnviroment();
				$logger->push($message);
				$logger->push($trace);
				$logger->save();
				return true;
			} catch (Exception $e) {
				echo "Can't log exception";
				return false;
			}
		}

		/**
		 * Стандартный обработчик исключений
		 *
		 * @param Exception $e Брошенное исключение
		 */
		public static function baseHandler($e) {
			if (!defined('CURRENT_WORKING_DIR')) {
				define(
					'CURRENT_WORKING_DIR',
					dirname(dirname(__FILE__))
				);
			}

			require_once CURRENT_WORKING_DIR . "/classes/system/utils/logger/iLogger.php";
			require_once CURRENT_WORKING_DIR . "/classes/system/utils/logger/logger.php";

			if (!DEBUG_SHOW_BACKTRACE && $e instanceof Exception && get_class($e) == 'databaseException') {
				$e = new Exception('Произошла критическая ошибка. Скорее всего, потребуется участие разработчиков.  Подробности по ссылке <a title="" target="_blank" href="http://errors.umi-cms.ru/17000/">17000</a>', 17000, $e);
			}

			if (!headers_sent()) {
				header("HTTP/1.1 500 Internal Server Error");
				header("Content-type: text/html; charset=utf-8");
				header("Status: 500 Internal Server Error");
			}
			self::printTemplate($e);

			self::createCrashReport($e->getMessage(), $e->getTraceAsString());

			exit();
		}

		/**
		 * Обработчик ошибок для Output Buffering
		 *
		 * @param string $buffer Буфер вывода
		 * @return mixed Буфер вывода
		 *
		 * @throws coreException исключение при ошибке
		 */
		public static function outputBufferHandler($buffer) {
			if( isset($GLOBALS['memoryReserve']) ) {
				unset($GLOBALS['memoryReserve']);
			}

			$errors = Array('Fatal', 'Parse');

			foreach($errors as $error) {
				if(strstr($buffer, "<br />\n<b>{$error} error</b>:") !== false) {
					throw new coreException(
						substr(
							trim(strip_tags($buffer)),
							strlen($error) + 9
						)
					);
				}
			}

			return $buffer;
		}
	}

	/*
	 * Установка обработчика исключений
	 */
	if (!defined('CRON')) {
		// Выбор шаблона, в зависимости от режима работы
		if (getRequest("xmlMode") == 'force') {
			$template = SYS_ERRORS_PATH."exception.xml.php";
		} elseif (getRequest("jsonMode") == 'force') {
			$template = SYS_ERRORS_PATH."exception.json.php";
		} else {
			$template = SYS_ERRORS_PATH."exception.html.php";
		}

		umiExceptionHandler::set("base", $template);
	}

	$GLOBALS['memoryReserve'] = str_repeat(" ", 1024);
	
	if(!defined("DEBUG") && function_exists("libxml_use_internal_errors")) {
	    libxml_use_internal_errors(true);
	}

	/**
	 * Проверяет возникновение libxml ошибок
	 *
	 * @deprecated
	 * @param $dom
	 * @throws libXMLErrorException исключение
	 */
	function checkXmlError($dom) {
		if ( !defined("DEBUG") && function_exists("libxml_get_last_error") && $dom === false ) {
			$error = libxml_get_last_error();
			libxml_clear_errors();
			throw new libXMLErrorException($error);
		}
	}

	/**
	 * Старый обработчик ошибок Output Buffering
	 *
	 * @deprecated
	 * @param string $buffer
	 * @return string
	 */
	function criticalErrorsBufferHandler($buffer) {
		return umiExceptionHandler::outputBufferHandler($buffer);
	}

	/**
	 * Обработчик ошибок для XSLT шаблонизатора
	 *
	 * @deprecated Более не используется
	 *
	 * @param $errno
	 * @param $errstr
	 * @param $errfile
	 * @param $errline
	 * @param $e
	 * @throws xlsTemplateException
	 */
	function xsltErrorsHandler($errno, $errstr, $errfile, $errline, $e) {
		if( defined("DEBUG") ||
			!function_exists("libxml_get_last_error") ||
			$errline != 0 ||
			$errno != 2
		) return;

		throw new xlsTemplateException(libxml_get_errors());
	}


	/**
	 * Установка обработчика ошибок для XSLT
	 *
	 * @deprecated В umiTemplaterXSLT используется свой error_handler, который бросает исключение xlsTemplateException
	 * @return bool|int
	 */
	function errorsXsltListen() {
		if ( !defined("DEBUG") ) {
			set_error_handler("xsltErrorsHandler");
			return error_reporting(~E_STRICT);
		}

		return false;
	}

	/**
	 * Удаление обработчика ошибок для XSLT
	 *
	 * @deprecated В umiTemplaterXSLT используется свой error_handler, который бросает исключение xlsTemplateException
	 *
	 * @param int $er level
	 * @return bool|int
	 */
	function errorsXsltCheck($er) {
	    if(!defined("DEBUG")) {
			error_reporting($er);
			return restore_error_handler();
		}

		return false;
	}

	/**
	 * Обработчик исключений
	 *
	 * @deprecated Теперь используется ExceptionHandler::baseHandler()
	 *
	 * @param Exception $e
	 */
	function traceException($e) {
		umiExceptionHandler::baseHandler($e);
	}
?>
