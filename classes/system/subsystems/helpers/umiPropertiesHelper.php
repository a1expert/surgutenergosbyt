<?php
/**
 * Класс для работы со полями объекта, без обращения к нему.
 */
	class umiPropertiesHelper	{
		/* @var umiPropertiesHelper $instance экземпляр класса */
		private static $instance = null;
		/* @var array $properties массив с загруженными свойствами */
		private $properties = array();
		/* @var umiTypesHelper $umiTypesHelper класс для работы с типами данных*/
		private $umiTypesHelper;

		/**
		 * Возвращает экземпляр текущего класса
		 * @return umiPropertiesHelper
		 */
		public static function getInstance() {
			if (is_null(self::$instance)) {
				self::$instance = new umiPropertiesHelper();
			}
			return self::$instance;
		}

		/**
		 * Получает и возвращает объект поля, либо null, если
		 * операция не удалась.
		 * @param int $objectId ид объекта, которому принадлежит поле
		 * @param string $fieldName guid поля
		 * @param int $typeId ид типа данных поля
		 * @param bool $resetCache проигнорировать кеш класса
		 * @return null|umiObjectProperty
		 */
		public function getProperty($objectId, $fieldName, $typeId, $resetCache = false) {
			$objectId = intval($objectId);
			$fieldName = strval($fieldName);
			$typeId = intval($typeId);
			$hash = md5($objectId . $fieldName . $typeId);

			if (isset($this->properties[$hash]) && !$resetCache) {
				return $this->properties[$hash];
			}

			$fieldId = $this->getFieldIdByName($fieldName, $typeId);

			if (is_null($fieldId)) {
				return $this->properties[$hash] = null;
			}

			$fieldTypeId = $this->umiTypesHelper->getFieldTypeIdByFieldId($fieldId);
			$property = umiObjectProperty::getProperty($objectId, $fieldId, $typeId, $fieldTypeId);
			return $this->properties[$hash] = $property;
		}

		/**
		 * Получает и возвращает значение поля, либо null, если
		 * операция не удалась.
		 * @param int $objectId ид объекта, которому принадлежит поле
		 * @param string $fieldName guid поля
		 * @param int $typeId ид типа данных поля
		 * @param bool $resetCache проигнорировать кеш класса
		 * @return Mixed|null
		 */
		public function getPropertyValue($objectId, $fieldName, $typeId, $resetCache = false, $params = null) {
			$objectId = intval($objectId);
			$fieldName = strval($fieldName);
			$typeId = intval($typeId);
			$hash = md5($objectId . $fieldName . $typeId);

			if (isset($this->properties[$hash]) && !$resetCache) {
				return ($this->properties[$hash] instanceof umiObjectProperty) ? $this->properties[$hash]->getValue() : null;
			}

			$property = $this->getProperty($objectId, $fieldName, $typeId, $resetCache);
			return ($property instanceof umiObjectProperty) ? $property->getValue($params) : null;
		}

		/**
		 * Инициирует сохранение всех загруженных полей, если они были обновлены
		 * @return bool
		 */
		public function saveProperties() {
			$properties = $this->properties;

			if (count($properties) == 0) {
				return true;
			}

			foreach ($properties as $property) {
				if ($property instanceof umiObjectProperty && $property->getIsUpdated()) {
					$property->commit();
				}
			}

			return true;
		}

		/**
		 * Очищает внутренний кеш класса
		 * @retun void
		 */
		public function clearCache() {
			$this->properties = array();
		}

		/**
		 * Деструктор
		 */
		public function __destruct() {
			$this->saveProperties();
		}

		/**
		 * Конструктор
		 */
		private function __construct() {
			$this->umiTypesHelper = umiTypesHelper::getInstance();
		}

		/**
		 * Возвращает id поля по его гуиду, если такое поле есть,
		 * иначе - null.
		 * @param string $fieldName гуид поля
		 * @param id $typeId ид типа данных поля
		 * @return int|null
		 */
		private function getFieldIdByName($fieldName, $typeId) {
			$fields = $this->umiTypesHelper->getFieldsByObjectTypeIds($typeId);

			if (isset($fields[$typeId][$fieldName])) {
				return (int) $fields[$typeId][$fieldName];
			}

			return null;
		}
	}
?>