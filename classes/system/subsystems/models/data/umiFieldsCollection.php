<?php
/**
	* Этот класс-коллекция служит для управления/получения доступа к полям
	* Класс является синглтоном, экземпляр класса можно получить через статический метод getInstance()
	* TODO Check and format all PHPDoc's
*/
	class umiFieldsCollection extends singleton implements iSingleton, iUmiFieldsCollection {
		private	$fields = Array();
		private $restrictedFieldIds;
		private $requiredFieldsIds;

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		protected function __construct() {
			$this->loadRestrictedFieldIds();
			$this->loadRequiredFieldsIds();
		}

		private function loadRestrictedFieldIds() {
			$sql  = 'SELECT `id` AS `field_id`, `restriction_id` FROM `cms3_object_fields` WHERE `restriction_id` IS NOT NULL';
			$result = l_mysql_query($sql);

			if ($error = l_mysql_error()) {
				throw new coreException($error);
			}

			$restrictedFieldsIds = array();

			while($row = mysql_fetch_assoc($result)) {
				if (isset($row['field_id']) && isset($row['restriction_id'])) {
					$restrictedFieldsIds[$row['field_id']] = $row['restriction_id'];
				}
			}

			$this->restrictedFieldIds = $restrictedFieldsIds;
		}

		private function loadRequiredFieldsIds() {
			$sql  = 'SELECT `id` AS `field_id` FROM `cms3_object_fields` WHERE `is_required` = 1';
			$result = l_mysql_query($sql);

			if ($error = l_mysql_error()) {
				throw new coreException($error);
			}

			$requiredFieldsIds = array();

			while($row = mysql_fetch_assoc($result)) {
				if (isset($row['field_id'])) {
					$requiredFieldsIds[$row['field_id']] = $row['field_id'];
				}
			}

			$this->requiredFieldsIds = $requiredFieldsIds;
		}

		/**
			* Получить экземпляр коллекции
			* @return umiFieldsCollection экземпляр класса
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		public function getRestrictionIdByFieldId($fieldId) {
			if (isset($this->restrictedFieldIds[$fieldId])) {
				return (int) $this->restrictedFieldIds[$fieldId];
			}
			return false;
		}

		public function isFieldRequired($fieldId) {
			return (isset($this->requiredFieldsIds[$fieldId])) ? true : false;
		}

		/**
			* Получтить экземпляр класса umiField, соответсвующший полю с id = $field_id
			* @param Integer $field_id id поля
			* @param Array $row=false или false информация о поле. Это служебный параметр и его передавать не нужно
			* @return umiField экземпляр класса umiField, соответсвующший полю с id = $field_id, либо false в случае неудачи
		*/
		public function getField($field_id, $row = false) {
			if($this->isExists($field_id)) {
				return $this->fields[$field_id];
			} else {
				return $this->loadField($field_id, $row);
			}
		}

		/**
			* Удалить поле с id $field_id из коллекции
			* @param Integer $field_id id поля
			* @return Boolean true, если удаление удалось
		*/
		public function delField($field_id) {
			$field_id = (int) $field_id;
			$this->disableCache();

			if($this->isExists($field_id)) {
				$sql = "DELETE FROM cms3_object_fields WHERE id = '{$field_id}'";
				l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}

				unset($this->fields[$field_id]);
				return true;
			} else {
				return false;
			}
		}

		/**
			* Создать новое поле
			* @param String $name имя поля (строковой идентификатор)
			* @param String $title название поля
			* @param Integer $field_type_id id типа данных поля (см. класс umiFieldType)
			* @param Boolean $is_visible=true видимость поля для пользователя
			* @param Boolean $is_locked=false указывает заблокировано ли поле на изменения
			* @param Boolean $is_inheritable=false указывает наследовать ли значение поля. Зарезервировано, но пока не используется, рекомендуется выставлять в false.
			* @return Integer id созданного поля, либо false в случае неудачи
		*/
		public function addField($name, $title, $field_type_id, $is_visible = true, $is_locked = false, $is_inheritable = false) {
			$this->disableCache();

			$sql = "INSERT INTO cms3_object_fields VALUES()";
			l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$field_id = l_mysql_insert_id();

			$field = new umiField($field_id);

			$field->setName($name);
			$field->setTitle($title);
			if(!$field->setFieldTypeId($field_type_id)) return false;
			$field->setIsVisible($is_visible);
			$field->setIsLocked($is_locked);
			$field->setIsInheritable($is_inheritable);

			if(!$field->commit()) return false;

			$this->fields[$field_id] = $field;

			return $field_id;
		}

		/**
			* Проверить, существует ли в БД поле с id = $field_id
			* @param Integer $field_id id поля
			* @return Boolean true, если поле существует в БД
		*/
		public function isExists($field_id) {
			if(!$field_id) return false;
			return (bool) array_key_exists($field_id, $this->fields);
		}

		/**
			* Загружает в коллекцию экземпляр класса umiField, соответсвующший полю с id = $field_id, и возвращает его
			* @param Integer $field_id id поля
			* @param Array $row=false или false информация о поле. Это служебный параметр и его передавать не нужно
			* @return umiField экземпляр класса umiField, соответсвующший полю c id = $field_id, либо false в случае неудачи
		*/
		private function loadField($field_id, $row) {
			$field = cacheFrontend::getInstance()->load($field_id, "field");

			if($field instanceof umiField == false) {
				try {
					$field = new umiField($field_id, $row);
				} catch(privateException $e) {
					return false;
				}

				cacheFrontend::getInstance()->save($field, "field");
			}

			if($field instanceof umiField) {
				$this->fields[$field_id] = $field;
				return $this->fields[$field_id];
			} else {
				return false;
			}
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		public function clearCache() {
			$keys = array_keys($this->fields);
			foreach ($keys as $key) {
				unset($this->fields[$key]);
			}
			$this->fields = array();
		}
	}
?>