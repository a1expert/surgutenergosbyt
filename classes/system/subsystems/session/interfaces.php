<?php
	/**
	 * Интерфейс работы с сессиями. Синглтон.
	 *
	 * Сессия подразумевает хранение пар ключ-значение.
	 * Подразумевает возможность создания контекстов, для возможности разделения
	 * области хранения.
	 *
	 * Идентификатор и имя устанавливаются для экземпляра, а не для объектов.
	 */
	interface iSession{

		/**
		 * Получить сессию
		 * При необходимости, сессия будет запущена
		 *
		 * @return iSession
		 */
		public static function getInstance();

		/**
		 * Пересоздать сессию
		 * При необходимости, сессия будет запущенна
		 *
		 * @param bool $resetData True если значения, хранящиеся в сессии нужно уничтожить
		 * @return iSession
		 */
		public static function recreateInstance($resetData = false);

		/**
		 * Уничтожает экземпляр.
		 */
		public static function destroy();

		/**
		 * Сохраняет экземпляр сессии и закрывает его.
		 */
		public static function commit();

		/**
		 * Получает идентификатор сессии
		 *
		 * @return string Идентификатор
		 */
		public static function getId();

		/**
		 * Устанавливает идентификатор экземпляра сессии
		 *
		 * @param string $id Идентификатор
		 *
		 * @return string Идентификатор
		 */
		public static function setId($id);

		/**
		 * Получает имя экземпляра сессии
		 *
		 * @return string Имя
		 */
		public static function getName();

		/**
		 * Устанавливает имя экземпляра сессии
		 *
		 * @param string $name Имя
		 *
		 * @return string Имя
		 */
		public static function setName($name);
	}

	/**
	 * Интерфейс валидации для сессий.
	 * Позвовляет проверить валидность текущего экземпляра сессии,
	 * либо установить флаг валидности
	*/
	interface iSessionValidation {
		/**
		 * Проверяет валидность текущего экземпляра
		 * @api
		 *
		 * @return bool|int
		 */
		public function isValid();

		/**
		 * Устанавливает валидность текущей сессии
		 *
		 * @param bool $isValid
		 *
		 * @return bool|int
		 */
		public function setValid($isValid = true);
	}
?>