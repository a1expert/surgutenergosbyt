<?php
	/**
	 * Класс для работы с сессиями
	 *
	 * Реализует стандартные(базовые) возможности
	 * для работы с сессиями
	 * Контексты на данный момент не поддерживаются
	 */
	class session implements iSession, iMapContainer, iSessionValidation {

		/**
		 * Запущена ли сессия
		 *
		 * @internal
		 * @var bool
		 */
		private static $isStarted = false;

		/**
		 * Инициализация сессии
		 */
		protected function __construct() {
			if(!self::$isStarted || !isset($_SESSION)) {
				@session_start();
				self::$isStarted = true;
			}
		}

		/**
		 * Инициализация сессии
		 *
		 * @return session Экземпляр
		 */
		public static function getInstance() {
			if (isset($_POST['auth-sid-token'])) {
				session_id($_POST['auth-sid-token']);
			}
			return new session();
		}

		/**
		 * Пересоздание сессии
		 * @param bool $resetData True если значения, хранящиеся в сессии нужно уничтожить
		 *
		 * @return session Экземпляр
		 */
		public static function recreateInstance($resetData = false) {
			if ((bool) $resetData) {
				 self::destroy();
			} else {
				self::commit();
			}

			return self::getInstance();
		}

		/**
		 * Получает значение из сессии
		 *
		 * @param $name Имя
		 *
		 * @return bool|null|mixed Значение, false в случае ошибки или null если значение отсутствует
		 */
		public function get($name) {
			return isset($_SESSION) ? getArrayKey($_SESSION, $name) : false;
		}

		/**
		 * Записывает значение в сессию
		 *
		 * @param $name Имя
		 * @param $value Значение
		 *
		 * @return mixed|bool записанное значение, false если сессия не существует
		 */
		public function set($name, $value) {
			return self::$isStarted ? $_SESSION[$name] = $value : false;
		}

		/**
		 * Удаляет значение из сессии
		 *
		 * @param string|array $name Имя, или массив имен для удаления
		 */
		public function del($name) {
			if(is_array($name)) {
				foreach($name as $singleName) {
					unset($_SESSION[$singleName]);
				}
			} else {
				unset($_SESSION[$name]);
			}
		}

		/**
		 * {@inheritdoc}
		 */
		public function getArrayCopy() {
			return isset($_SESSION) ? $_SESSION : array();
		}

		/**
		 * {@inheritdoc}
		 */
		public function clear() {
			session_unset();
			$_SESSION = array();
		}

		/**
		 * {@inheritdoc}
		 */
		public static function commit() {
			self::$isStarted = false;
			session_commit();
		}

		/**
		 * {@inheritdoc}
		 */
		public static function destroy() {
			self::$isStarted = false;
			session_destroy();
		}

		/**
		 * {@inheritdoc}
		 */
		public static function setId($id) {
			return session_id($id);
		}

		/**
		 * {@inheritdoc}
		 */
		public static function getId() {
			return session_id();
		}

		/**
		 * {@inheritdoc}
		 */
		public static function setName($name) {
			return session_name($name);
		}

		/**
		 * {@inheritdoc}
		 */
		public static function getName() {
			return session_name();
		}

		/**
		 * {@inheritdoc}
		 */
		public function __get($name) {
			return $this->get($name);
		}

		/**
		 * {@inheritdoc}
		 */
		public function __set($name, $value) {
			return $this->set($name, $value);
		}

		/**
		 * {@inheritdoc}
		 */
		public function __unset($name) {
			$this->del($name);
		}

		/**
		 * {@inheritdoc}
		 */
		public function setValid($isValid = true) {
			if ($isValid) {
				$this->set("starttime", time());
			} else {
				$this->set("starttime", time() - (SESSION_LIFETIME + 1) * 60);
			}
		}

		/**
		 * {@inheritdoc}
		 */
		public function isValid() {
			if (!$this->get("starttime")) { // is exists?
				return SESSION_LIFETIME * 60;
			} elseif ($this->get("starttime") + SESSION_LIFETIME * 60 < time()) { // is expired?
				return false;
			} else {
				return $this->get("starttime") + SESSION_LIFETIME * 60 - time();
			}
		}
	}
?>