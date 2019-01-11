<?php
	interface iSingleton {
		public static function getInstance($c = NULL);
	};

	interface iUmiEntinty {
		public function getId();
		public function commit();
		public function update();

		public static function filterInputString($string);
	};

	/**
	 * Интерфейс контейнера типа ключ-значение
	 *
	 * У класса, реализуещего этот интерфейс, должны
	 * существовать магические методы
	 */
	interface iMapContainer {
		/**
		 * Получает значение
		 *
		 * @param mixed $key Ключ
		 *
		 * @return mixed Значение
		 */
		public function get($key);

		/**
		 * Записывает значение
		 *
		 * @param mixed $key Ключ
		 * @param mixed $value Значение
		 *
		 * @return mixed Значение
		 */
		public function set($key, $value);

		/**
		 * Удаляет значение
		 *
		 * @param mixed $key Ключ
		 */
		public function del($key);

		/**
		 * Получает контейнер как массив
		 *
		 * @return array
		 */
		public function getArrayCopy();

		/**
		 * Очищает контейнер
		 */
		public function clear();

		/**
		 * Синоним для get
		 * @see iMapContainer::get()
		 *
		 * @param mixed $key Ключ
		 *
		 * @return mixed Значение
		 */
		public function __get($key);

		/**
		 * Синоним для set
		 * @see iMapContainer::set()
		 *
		 * @param mixed $key Ключ
		 * @param mixed $value Значение
		 *
		 * @return mixed Значение
		 */
		public function __set($key, $value);

		/**
		 * Синоним для del
		 * @see iMapContainer::del()
		 *
		 * @param $key Ключ
		 */
		public function __unset($key);
	}
?>