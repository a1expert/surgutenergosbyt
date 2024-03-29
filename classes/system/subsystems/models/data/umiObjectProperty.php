<?php
	/**
	 * TODO Check and format all PHPDoc's
	 * Этот класс служит для управления свойством объекта
	 */
	abstract class umiObjectProperty extends umiEntinty implements iUmiEntinty, iUmiObjectProperty {
		protected
			$object_id, $field_id, $field, $field_type,
			$value = array(), $tableName = "cms3_object_content", $is_updated = false;
		protected static $dataCache = array();

		public	$store_type = "property";
		public static $USE_FORCE_OBJECTS_CREATION = false;
		public static $IGNORE_FILTER_INPUT_STRING = false;
		public static $IGNORE_FILTER_OUTPUT_STRING = false;
		public static $USE_TRANSACTIONS = true;
		public static $IGNORE_CACHE = false;

		private $objectPropsCashSize = 3;

		/**
		 * Конструктор класса
		 * @param Integer $id id свойства
		 * @param Integer $field_id id поля (umiField), с которым связано свойство
		 * @param Integer $type_id тип данных
		 */
		public function __construct($id, $field_id, $type_id, $field_type_id = false) {
			$this->tableName = umiBranch::getBranchedTableByTypeId($type_id);

			$this->setId($id);
			$this->object_id = (int) $id;
			$this->field_id = $field_id;
			$mainConfigs = mainConfiguration::getInstance();
			$configSize = intval($mainConfigs->get('kernel', 'objects-props-cash-size'));

			if ($configSize > 3) {
				$this->objectPropsCashSize = $configSize;
			}

			if ($field_type_id) {
				$this->loadShortInfo($field_type_id);
			} else {
				$this->loadInfo();
			}
		}

		/**
		 * Получить класс свойства (umiObjectProperty) для объекта $id, поля $field_id, типа данных $type_id
		 * @param Integer $id id объекта
		 * @param Integer $field_id id поля (класс umiField)
		 * @param Integer $type_id id типа данных (класс umiObjectType)
		 * @return umiObjectProperty объект свойства
		 */
		public static function getProperty($id, $field_id, $type_id, $field_type_id = false) {
			if ($field_type_id) {
				$className = self::getClassNameByFieldTypeId($field_type_id);
			} else {
				$className = self::getClassNameByFieldId($field_id);
			}
			return new $className($id, $field_id, $type_id, $field_type_id);
		}

		/**
		 * Получить уникальный идентификатор свойства
		 * @return Integer id свойства
		 */
		public function getId() {
			return $this->id . "." . $this->field_id;
		}

		/**
		 * Получить значение свойства
		 * @param Array $params = NULL дополнительные параметры (обычно не используется)
		 * @return Mixed значение поля. Тип значения зависит от типа поля, связанного с этим свойством. Вернет NULL, если значение свойства не выставленно.
		 */
		public function getValue(array $params = NULL) {
			if ($this->getIsMultiple() === false) {
				if (sizeof($this->value) > 0) {
					list($value) = $this->value;
				} else {
					$value = null;
				}
			} else {
				$value = $this->value;
			}

			if (!is_null($params)) {
				$value = $this->applyParams($value, $params);
			}

			$restrictionId = umiFieldsCollection::getInstance();
			if ($restrictionId = $restrictionId->getRestrictionIdByFieldId($this->field_id)) {
				$restriction = baseRestriction::get($restrictionId);
				if ($restriction instanceof iNormalizeOutRestriction) {
					$value = $restriction->normalizeOut($value, $this->object_id);
				}
			}
			return $value;
		}

		/**
		 * Получить имя свойсива
		 * @return String имя свойства.
		 */
		public function getName() {
			return $this->getField()->getName();
		}

		/**
		 * Получить описание свойсива
		 * @return String описание свойства.
		 */
		public function getTitle() {
			return $this->getField()->getTitle();
		}

		/**
		 * Провалидировать значение согласно настройкам поля
		 * @param String $value проверяемое начение
		 * @return String проверенное (возможно, модифицированное) значение поля
		 */
		public function validateValue($value) {
			$umiFieldsCollection = umiFieldsCollection::getInstance();
			if ((is_null($value) || $value === false || $value === "") && $umiFieldsCollection->isFieldRequired($this->field_id)) {
				throw new valueRequiredException(getLabel('error-value-required', null, $this->getTitle()));
			}
			if ($value && $restrictionId = $umiFieldsCollection->getRestrictionIdByFieldId($this->field_id)) {
				$restriction = baseRestriction::get($restrictionId);
				if ($restriction instanceof baseRestriction) {
					if ($restriction instanceof iNormalizeInRestriction) {
						$value = $restriction->normalizeIn($value, $this->object_id);
					}

					if ($restriction->validate($value, $this->object_id) === false) {
						throw new wrongValueException(getLabel($restriction->getErrorMessage(), null, $this->getTitle()));
					}
				}
			}
			return $value;
		}

		/**
		 * Установить значение свойства.
		 * Устанавливает флаг "Модифицирован".
		 * Значение в БД изменится только когда на экземпляре umiObjectProperty будет вызван темод commit(), либо в деструкторе экземпляра
		 * @param Mixed $value новое значение для поля. Зависит от типа поля, связанного с этим свойством
		 * @return Boolean true если прошло успешно
		 */
		public function setValue($value) {
			$value = $this->validateValue($value);

			if (!is_array($value)) {
				$value = Array($value);
			}

			$not_allowed_symbols = array(1, 2, 3, 4, 5, 6, 7, 8, 11, 12, 14, 15, 16, 17, 18, 19, 20,21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31);

			$pattern = '';
			foreach ($not_allowed_symbols as $symbol) {
				$pattern = $pattern . chr($symbol);
			}

			foreach ($value as &$v) {
				if (is_string($v)) {
					$v = preg_replace("/[" . $pattern . "]/isu", "", $v);
				}
			}

			$data_type = $this->getDataType();
			if ($data_type === 'date') {
				foreach ($value as $vKey=>$vVal) {
					if (!($vVal instanceof umiDate)) {
						$value[$vKey] = new umiDate(intval($vVal));
					}
				}
			}
			$valueWillBeChange = $this->isNeedToSave($value);

			if ($valueWillBeChange) {
				$this->value = $value;
				$this->setIsUpdated(true);
			}

			return true;
		}

		/**
		 * Нужно ли сохранять поле, то есть было ли оно изменено.
		 * @param array $newValue переданное значение
		 * @return bool
		 */
		protected function isNeedToSave(array $newValue){}

		/**
		 * Сбросить значение свойства.
		 * Устанавливает флаг "Модифицирован".
		 * Значение в БД изменится только когда на экземпляре umiObjectProperty будет вызван темод commit(), либо в деструкторе экземпляра
		 * @return Boolean true если прошло успешно
		 */
		public function resetValue() {
			$this->value = Array();
			$this->setIsUpdated();
		}

		/**
		 * Загружает необходимые данные для формирования объекта umiObjectProperty из БД.
		 * @return Boolean true в случае успеха
		 */
		protected function loadInfo() {
			$this->field = umiFieldsCollection::getInstance()->getField($this->field_id);
			$field = $this->field;
			$field_types = umiFieldTypesCollection::getInstance();
			$field_type_id = $field->getFieldTypeId();
			$field_type = $field_types->getFieldType($field_type_id);
			$this->field_type = $field_type;
			$this->value = $this->loadValue();
		}

		/**
		 * Загружает минимально необходимые данные для формирования объекта umiObjectProperty из БД.
		 * @param int $fieldTypeId ид типа данных поля
		 * @return Boolean true в случае успеха
		 */
		protected function loadShortInfo($fieldTypeId) {
			$umiFieldsTypesCollection = umiFieldTypesCollection::getInstance();
			$this->field_type = $umiFieldsTypesCollection->getFieldType($fieldTypeId);
			$this->value = $this->loadValue();
		}

		/**
		 * Сохраняет значение свойства в БД.
		 * Вызывает метод saveValue класса, соответствующего типу свойства
		 * @return ничего т.к. вызываемый метод saveValue ничего не возвращает
		 * TODO сделать нормальный return
		 */
		protected function save() {

			if (!$this->getIsUpdated()) {
				return;
			}

			$umiObjects = umiObjectsCollection::getInstance();

			if (!$umiObjects->checkObjectById($this->getObjectId())) {
				cacheFrontend::getInstance()->del($this->getObjectId(), "object");
				return false;
			}

			cacheFrontend::getInstance()->del($this->getId(), "property");

			if (self::$USE_TRANSACTIONS) {
				l_mysql_query("START TRANSACTION /* Saving property for object {$this->getObjectId()} */");
			}

			$result = $this->saveValue();

			if (self::$USE_TRANSACTIONS) {
				l_mysql_query("COMMIT");
			}

			if (isset(umiObjectProperty::$dataCache[$this->object_id])) {
				unset(umiObjectProperty::$dataCache[$this->object_id]);
			}

			$this->setIsUpdated(false);

            return $result;
		}

		/**
		 * Узнать, может ли значение данного свойства состоять из массива значений (составной тип)
		 * @return Boolean true, если тип составной
		 */
		public function getIsMultiple() {
			return $this->field_type->getIsMultiple();
		}

		/**
		 * Узнать, может ли значение данного свойства иметь знак.
		 * Зарезервировано и пока не используется
		 * @return Boolean true, если значение свойства не будет иметь знак
		 */
		public function getIsUnsigned() {
			return $this->field_type->getIsUnsigned();
		}

		/**
		 * Получить идентификатор типа поля, связанного с данным свойством
		 * @return String идентификатор типа
		 */
		public function getDataType() {
			return $this->field_type->getDataType();
		}

		/**
		 * Узнать, заблокировано ли свойство на изменение
		 * @return Boolean true если свойство заблокировано
		 */
		public function getIsLocked() {
			return $this->getField()->getIsLocked();
		}

		/**
		 * Узнать, наследуется ли значение свойства. Зарезервировано, но пока не используется.
		 * @return Boolean true если свойство наследуется
		 */
		public function getIsInheritable() {
			return $this->getField()->getIsInheritable();
		}

		/**
		 * Узнать видимость свойства для пользователя
		 * @return Boolean true если свойство видимое для пользователя
		 */
		public function getIsVisible() {
			return $this->getField()->getIsVisible();
		}

		/**
		 * Заменяет в строке символ "%" на "&#037;" и обратно, в зависимости от режима работы cms.
		 * Используется ядром для защиты от иньекций макросов на клинтской стороне
		 * @param String $string фильтруемая строка
		 * @return String отфильтрованная строка
		 */
		public static function filterInputString($string) {
			$string = l_mysql_real_escape_string($string);

			if (isset($_SERVER['DOCUMENT_ROOT']) && cmsController::getInstance()->getCurrentMode() != "admin" && !umiObjectProperty::$IGNORE_FILTER_INPUT_STRING && !(defined("CRON") && CRON)) {
				$string = str_replace(array("&#037;", "&#37;"), "%", $string);
				$string = htmlspecialchars(htmlspecialchars_decode($string), ENT_NOQUOTES);
				$string = str_replace("%", "&#37;", $string);
			}
			return $string;
		}

		/**
		 * @internal
		 * @deprecated
		 * @param string $string Строка
		 * @return string
		 */
		public static function filterOutputString($string) {
			return $string;
		}

		/**
		 * Заменяет в строке символ закрывающую последовательность для CDATA (]]>) на "]]&gt;"
		 * Используется ядром поддержания валидности XML-документов
		 * @param String $string фильтруемая строка
		 * @return String отфильтрованная строка
		 * @deprecated Начиная с версии 2.8.6.1
		 */
		public static function filterCDATA($string) {
			$string = str_replace("]]>", "]]&gt;", $string);
			return $string;
		}

		/**
		 * Устанавливает маркер "модифицирован" у связанного с этим свойством объекта
		 * @return Boolean false, в случае неудачи
		 */
		protected function setObjectIsUpdated() {
			if ($object = $this->getObject()) {
				$object->setIsUpdated();
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Загружает значение полей объекта
		 * @return bool
		 */
		protected function getPropData() {
			$cache = &umiObjectProperty::$dataCache;

			if (defined("DISABLE_GETVAL_OPT") && DISABLE_GETVAL_OPT) {
				return false;
			}

			$fieldId = $this->field_id;
			$objectId = $this->object_id;

			if (!isset($cache[$objectId])) {
				$data = array();

				$sql = "SELECT field_id, int_val, varchar_val, text_val, rel_val, tree_val, float_val FROM {$this->tableName} WHERE obj_id = '{$this->id}'";
				$result = l_mysql_query($sql);
				while($row = mysql_fetch_assoc($result)) {
					$data[$row['field_id']]['int_val'][] = $row['int_val'];
					$data[$row['field_id']]['varchar_val'][] = $row['varchar_val'];
					$data[$row['field_id']]['text_val'][] = $row['text_val'];
					$data[$row['field_id']]['rel_val'][] = $row['rel_val'];
					$data[$row['field_id']]['tree_val'][] = $row['tree_val'];
					$data[$row['field_id']]['float_val'][] = $row['float_val'];
				}
				$cache[$objectId] = $data;

				if (sizeof($cache) >= $this->objectPropsCashSize) {
					foreach($cache as $i => $d) {
						unset($cache[$i]);
						break;
					}
				}
			} else {
				$data = $cache[$objectId];
			}

			if (isset($data[$fieldId])) {
				return $data[$fieldId];
			} else {
				return false;
			}
		}

		/**
		 * Загружает значение полей объектов
		 * @param array $objectIds массив с идентификаторами объектов
		 * @param int $hierarchyTypeIds ид иерархического типа данных, к которому принадлежат объекты
		 * @return bool
		 */
		public static function loadPropsData(array $objectIds, $hierarchyTypeIds) {
			if (count($objectIds) == 0 || !$hierarchyTypeIds) {
				return false;
			}
			$objectIds = array_map('intval', $objectIds);
			$cache = &umiObjectProperty::$dataCache;

			$objectsIdsToLoad = array();
			foreach ($objectIds as $objectId) {
				if (!isset($cache[$objectId])) {
					$objectsIdsToLoad[] = $objectId;
				}
			}

			if (count($objectsIdsToLoad) == 0) {
				return false;
			}

			$hierarchyTypeIds = intval($hierarchyTypeIds);
			$tableName = mysql_real_escape_string(umiBranch::getBranchedTableByHierarchyTypeId($hierarchyTypeIds));
			$connection = ConnectionPool::getInstance()->getConnection();
			$objectsIdsToLoad = implode(',', $objectsIdsToLoad);
			$sql = "SELECT obj_id, field_id, int_val, varchar_val, text_val, rel_val, tree_val, float_val FROM `$tableName` WHERE obj_id IN ($objectsIdsToLoad)";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return false;
			}

			foreach($result as $row) {
				$cache[$row['obj_id']][$row['field_id']]['int_val'][] = $row['int_val'];
				$cache[$row['obj_id']][$row['field_id']]['varchar_val'][] = $row['varchar_val'];
				$cache[$row['obj_id']][$row['field_id']]['text_val'][] = $row['text_val'];
				$cache[$row['obj_id']][$row['field_id']]['rel_val'][] = $row['rel_val'];
				$cache[$row['obj_id']][$row['field_id']]['tree_val'][] = $row['tree_val'];
				$cache[$row['obj_id']][$row['field_id']]['float_val'][] = $row['float_val'];
			}

			return true;
		}

		/**
		 * Возвращает связанный с этим свойством объект (umiObject)
		 * @return umiObject
		 * @see umiObject
		 */
		public function getObject() {
			return umiObjectsCollection::getInstance()->getObject($this->object_id);
		}

		/**
		 * Возвращает id объекта (umiObject), связанного с этим свойством
		 * @return umiObject
		 * @see umiObject
		 */
		public function getObjectId() {
			return $this->object_id;
		}

		/**
		 * Возвращает свойство (umiField)
		 * @return umiField
		 * @see umiField
		 */
		public function getField() {
			if (!$this->field instanceof umiField) {
				$this->field = umiFieldsCollection::getInstance()->getField($this->field_id);
			}
			return $this->field;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param unknown_type $filepath
		 */
		protected static function unescapeFilePath($filepath) {
			return str_replace("\\\\", "/", $filepath);
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @throws coreException
		 */
		protected function deleteCurrentRows() {
			$sql = "DELETE FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND (field_id = '{$this->field_id}' OR field_id IS NULL)";
			l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
			}
		}

		/**
		 * TODO PHPDoc
		 * Заполнить все столбцы значений таблицы БД, соответствующие данному свойству NULL'ами
		 */
		protected function fillNull() {
			$sql = "SELECT COUNT(*) FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$this->field_id}'";
			$result = l_mysql_query($sql);

			if ($err = l_mysql_error()) {
				throw new coreException($err);
			} else {
				list($c) = mysql_fetch_row($result);
			}

			if ($c == 0) {
				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id) VALUES('{$this->object_id}', '{$this->field_id}')";
				l_mysql_query($sql);

				if ($err = l_mysql_error()) {
					throw new coreException($err);	//TODO: Ignore references, debug.
				} else {
					return true;
				}
			}
			return true;
		}

		/**
		 * Возвращает имя класса поля объекта по его id
		 * @param int $field_id ид поля
		 * @return string
		 * @throws coreException если не удалось получить тип данных поля
		 * @throws coreException если для полученного типа данных поля не существует ответственного класса
		 */
		protected static function getClassNameByFieldId($field_id) {
			static $cache = Array();

			if (isset($cache[$field_id]) && !self::$IGNORE_CACHE) {
				return $cache[$field_id];
			}

			$field = umiFieldsCollection::getInstance()->getField($field_id);
			$fieldTypeId = $field->getFieldTypeId();
			$fieldType = umiFieldTypesCollection::getInstance()->getFieldType($fieldTypeId);

			if (!$fieldType instanceof umiFieldType) {
				throw new coreException("Unhandled field of type id \"{$fieldTypeId}\"");
			}

			$fieldDataType = $fieldType->getDataType();

			$propertyClasses = self::getClassNameList();

			if (isset($propertyClasses[$fieldDataType])) {
				return $cache[$field_id] = $propertyClasses[$fieldDataType];
			} else {
				throw new coreException("Unhandled field of type \"{$fieldDataType}\"");
			}
		}

		/**
		 * Возвращает имя класса поля объекта по id его типа данных
		 * @param int $fieldTypeId ид типа данных поля
		 * @return string
		 * @throws coreException если не удалось получить тип данных поля по $fieldTypeId
		 * @throws coreException если для полученного типа данных поля не существует ответственного класса
		 */
		protected static function getClassNameByFieldTypeId($fieldTypeId) {
			static $cache = Array();

			if (isset($cache[$fieldTypeId]) && !self::$IGNORE_CACHE) {
				return $cache[$fieldTypeId];
			}

			$umiFieldTypesCollection = umiFieldTypesCollection::getInstance();
			$fieldType = $umiFieldTypesCollection->getFieldType($fieldTypeId);

			if (!$fieldType instanceof umiFieldType) {
				throw new coreException("Unhandled field of type id \"{$fieldTypeId}\"");
			}

			$fieldDataType = $fieldType->getDataType();
			$propertyClasses = self::getClassNameList();

			if (isset($propertyClasses[$fieldDataType])) {
				return $cache[$fieldTypeId] = $propertyClasses[$fieldDataType];
			} else {
				throw new coreException("Unhandled field of type \"{$fieldDataType}\"");
			}
		}

		/**
		 * Возвращает массив имен классов полей объектов.
		 * @return array(guid => class)
		 */
		private static function getClassNameList() {
			return array(
				'relation' => 'umiObjectPropertyRelation',
				'wysiwyg' => 'umiObjectPropertyWYSIWYG',
				'string' => 'umiObjectPropertyString',
				'file' => 'umiObjectPropertyFile',
				'img_file' => 'umiObjectPropertyImgFile',
				'swf_file' => 'umiObjectPropertyImgFile',
				'video_file' => 'umiObjectPropertyFile',
				'boolean' => 'umiObjectPropertyBoolean',
				'int' => 'umiObjectPropertyInt',
				'text' => 'umiObjectPropertyText',
				'date' => 'umiObjectPropertyDate',
				'symlink' => 'umiObjectPropertySymlink',
				'price' => 'umiObjectPropertyPrice',
				'float' => 'umiObjectPropertyFloat',
				'tags' => 'umiObjectPropertyTags',
				'password' => 'umiObjectPropertyPassword',
				'counter' => 'umiObjectPropertyCounter',
				'optioned' => 'umiObjectPropertyOptioned'
			);
		}

		/**
		 * TODO PHPDoc
		 * Не используйте этот метод, его поведение будет изменено в ближайших версиях
		 */
		public static function objectsByValue($i_field_id, $arr_value = NULL, $b_elements = false, $b_stat = true, $arr_domains = NULL) {
			$arr_answer = array();

			// ==== validate input : =======================

			if (!(is_null($arr_value) || is_array($arr_value) || intval($arr_value) === -1 || strval($arr_value) === 'all' || strval($arr_value) == 'Все')) {
				$arr_value = array($arr_value);
			}

			// h.domain_id
			$arr_domain_ids = NULL;
			if ($b_elements) {
				if (is_null($arr_domains)) { // current domain
					$arr_domain_ids = array(cmsController::getInstance()->getCurrentDomain()->getId());
				} elseif (intval($arr_domains) === -1 || strval($arr_domains) === 'all' || strval($arr_domains) == 'Все') {
					$arr_domain_ids = array();
				} elseif (is_array($arr_domains)) {
					$arr_domain_ids = array_map('intval', $arr_domains);
				} else {
					$arr_domain_ids = array(intval($arr_domains));
				}
			}

			$field = umiFieldsCollection::getInstance()->getField($i_field_id);
			if ($field instanceof umiField) {
				$fieldDataType = $field->getFieldType()->getDataType();
				$s_col_name = umiFieldType::getDataTypeDB($fieldDataType);
			} else {
				throw new coreException("Field #{$fieldId} not found");
			}

			// ==== construct sql queries : ================

			$objectTypeId = umiSelectionsParser::getObjectTypeByFieldId($i_field_id);
			$tableName = umiBranch::getBranchedTableByTypeId($objectTypeId);

			$s_from = "{$tableName} `o`";
			if ($b_elements) $s_from .= ", cms3_hierarchy `h`";

			if ($b_elements) {
				$s_count_field = "h.id";
			} else {
				$s_count_field = "o.obj_id";
			}

			$s_where_tail = ($b_elements ? " AND h.obj_id = o.obj_id AND h.is_active=1 AND h.is_deleted=0" : "");

			if ($b_elements && is_array($arr_domain_ids) && count($arr_domain_ids)) {
				$s_where_tail .= " AND h.domain_id IN ('".implode("', '", $arr_domain_ids)."')";
			}

			$s_values_filter = "";
			if (!(intval($arr_value) === -1 || strval($arr_value) === 'all' || strval($arr_value) === 'Âñå')) {
				$s_values_filter = " AND o.{$s_col_name} ".(is_null($arr_value) ? "IS NULL" : "IN ('".implode("', '", $arr_value)."')");
			}

			if ($b_stat) {
				$s_query = "SELECT o.".$s_col_name." as `value`, COUNT(".$s_count_field.") as `items` FROM ".$s_from." WHERE o.field_id = ".$i_field_id.$s_values_filter.$s_where_tail." GROUP BY o.".$s_col_name." ORDER BY `items`";
			} else {
				$s_query = "SELECT DISTINCT ".$s_count_field." as `item` FROM ".$s_from." WHERE o.field_id = ".$i_field_id.$s_values_filter.$s_where_tail;
			}

			// ==== execute sql query : ====================

			$arr_query = array();
			$rs_query = l_mysql_query($s_query);
			$i_query_error = mysql_errno();
			$s_query_error = l_mysql_error();
			if ($rs_query === false || $i_query_error) {
				throw new coreException("Error executing db query (errno ".$i_query_error.", error ".$s_query_error.", query ".$s_query.")");
			} else {
				while ($arr_next_row = mysql_fetch_assoc($rs_query)) {
					$arr_query[] = $arr_next_row;
				}
			}

			// ==== construct returning answer : ===========

			if ($b_stat) {
				$arr_answer['values'] = array();
				$i_max = 0;
				$i_summ = 0;
				foreach ($arr_query as $arr_row) {
					$i_cnt = intval($arr_row['items']);

					$arr_answer['values'][] = array('value' => $arr_row['value'], 'cnt' => $i_cnt);

					if ($i_cnt > $i_max) $i_max = $i_cnt;
					$i_summ += $i_cnt;
				}
				$arr_answer['max'] = $i_max;
				$arr_answer['sum'] = $i_summ;
			} else {
				foreach ($arr_query as $arr_row) $arr_answer[] = $arr_row['item'];
			}

			// RETURN :
			return $arr_answer;

		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param unknown_type $values
		 * @param unknown_type $params
		 */
		protected function applyParams($values, $params = NULL) {
			return $values;
		}

		/**
		 * TODO PHPDoc
		 * @param Mixed $value - устанавливаемое значение свойства
		 * @return Integer - id связанного элемента или null если элемент не существует, и не удалось добавить новый
		 * @throws coreException
		 */
		protected function prepareRelationValue($value) {
			if (!$value) {
				return false;
			}

			$objects = umiObjectsCollection::getInstance();
			$forceObjectsCreation = self::$USE_FORCE_OBJECTS_CREATION;

			if (is_object($value)) {
				return $value->getId();
			} else {
				if (is_numeric($value) && $objects->isExists($value) && !$forceObjectsCreation) {
					return (int) $value;
				} else {
					if ($guide_id = $this->getField()->getGuideId()) {
						$val_name = self::filterInputString($value);
						$val_label = $this->translateI18n($val_name);

						$sql = "SELECT id FROM cms3_objects WHERE type_id = '{$guide_id}' AND name = '{$val_label}'";
						$result = l_mysql_query($sql);

						if (mysql_num_rows($result)) {
							list($value) = mysql_fetch_row($result);
							return $value;
						} elseif (cmsController::getInstance()->getCurrentMode() === 'admin') {
							if (!$forceObjectsCreation) {
								$sql = "SELECT `id` FROM `cms3_object_types` WHERE `id` = '{$guide_id}' AND `is_guidable`=1 AND `is_public`=1 LIMIT 1";
								$result = l_mysql_query($sql);
								if (!mysql_num_rows($result)) {
									return null;
								}
							}

							$valueToAdd = ($val_label == $val_name) ? $value : $val_label;
							if ($value = $objects->addObject($valueToAdd, $guide_id)) {
								return (int) $value;
							} else {
								throw new coreException("Can't create guide item");
							}
						}
					}
				}
			}
			return null;
		}

	};
?>