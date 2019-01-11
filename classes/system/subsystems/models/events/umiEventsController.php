<?php
/**
	* Класс для регистрации и управления вызовами событий
*/
	class umiEventsController implements iUmiEventsController {
		protected static $eventListeners = Array();
		private   static $oInstance  = null;

		protected function __construct() {
			$this->loadEventListeners();
		}

		/**
			* Вернуть экземпляр коллекции
			* @return umiEventsController
		*/
		public static function getInstance() {
			if(self::$oInstance == null) {
				self::$oInstance = new umiEventsController();
			}
			return self::$oInstance;
		}


		protected function loadEventListeners() {
			$modules_keys = regedit::getInstance()->getList("//modules");

			foreach($modules_keys as $arr) {
				$module = $arr[0];

				$this->loadModuleEventListeners($module);
			}
		}


		protected function loadModuleEventListeners($module) {
			$path = CURRENT_WORKING_DIR . "/classes/modules/{$module}/events.php";
			$path_custom = CURRENT_WORKING_DIR . "/classes/modules/{$module}/custom_events.php";

			// подключаем евенты из ресурсов шаблона
			if ($resourcesDir = cmsController::getInstance()->getResourcesDirectory()) {
				$this->tryLoadEvents($resourcesDir . "/classes/modules/{$module}/events.php");
			}

			// подключение событий из расширений
			$pathExtEvents = CURRENT_WORKING_DIR . "/classes/modules/{$module}/ext/events_*.php";
			$extEvents = glob($pathExtEvents);
			if(is_array($extEvents)) {
				foreach (glob($pathExtEvents) as $filename) {
					if (file_exists($filename)) {
						$this->tryLoadEvents($filename);
					}
				}
			}
			$this->tryLoadEvents($path_custom);
			$this->tryLoadEvents($path);
		}


		protected function tryLoadEvents($path) {
			if(file_exists($path)) {
				require $path;
				return true;
			} else {
				return false;
			}
		}


		protected function searchEventListeners($eventId) {
			static $cache = array();

			if (isset($cache[$eventId])) {
				return $cache[$eventId];
			}

			$result = array();

			foreach(self::$eventListeners as $eventListener) {
				if($eventListener->getEventId() == $eventId) {
					$result[] = $eventListener;
				}
			}

			$temp = Array();

			foreach($result as $callback) {
				$temp[$callback->getPriority()][] = $callback;
			}

			$result = array();
			ksort($temp);

			foreach($temp as $callbackArray) {
				foreach ($callbackArray as $callback) {
					$result[] = $callback;
				}
			}

			$cache[$eventId] = $result;

			return $cache[$eventId];
		}


		protected function executeCallback($callback, $eventPoint) {
			$module = $callback->getCallbackModule();
			$method = $callback->getCallbackMethod();

			if($module_inst = cmsController::getInstance()->getModule($module)) {
				$module_inst->$method($eventPoint);
			} else {
				throw new coreException("Cannot find module \"{$module}\"");
			}
		}

		/**
			* Вызвать событие и выполнить все обработчики, которые его слушают
			* @param umiEventPoint $eventPoint точка входа в событие
			* @return Array лог обработанных callback'ов
		*/
		public function callEvent(iUmiEventPoint $eventPoint, $allowed_modules = array()) {
			$eventId = $eventPoint->getEventId();
			$callbacks = $this->searchEventListeners($eventId);
			$logs = array('executed' => array(), 'failed' => array(), 'breaked' => array());

			foreach($callbacks as $callback) {
			
				if(!empty($allowed_modules) && !in_array($callback->getCallbackModule(), $allowed_modules)) 
				{
					continue;
				}				
				
				try {
					$this->executeCallback($callback, $eventPoint);
					$logs['executed'][] = $callback;
				} catch (baseException $e) {
					$logs['failed'][] = $callback;
					
					if($callback->getIsCritical()) {
						throw $e;
					} else {
						continue;
					}
				} catch (breakException $e) {
					$logs['breaked'][] = $callback;
					break;
				}
			}
			
			return $logs;
		}

		/**
			* Зарегистрировать в коллекции обработчик события
			* @param umiEventListener $eventListener обработчик события
		*/
		static public function registerEventListener(iUmiEventListener $eventListener) {
			self::$eventListeners[] = $eventListener;
		}
	};
?>