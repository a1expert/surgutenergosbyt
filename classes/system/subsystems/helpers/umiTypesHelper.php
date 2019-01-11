<?php
/**
 * Класс для быстрого доступа к данным полей объектов.
 */
	class umiTypesHelper {
		/* @var umiTypesHelper $instance экземпляр класса */
		private static $instance = null;
		/* @var array $fieldsByObjectTypes данные о полях объектов по типам данных:
		 * array(type_id => array(fields_guid => field_id))
		 */
		private $fieldsByObjectTypes = array();
		/* @var array $objectTypeIdsByHierarchyTypeId id объектных типов данных, связанные с ид иерархических */
		private $objectTypeIdsByHierarchyTypeId = array();
		/* @var array $hierarchyTypeIdsByName id иерархических типов данных, связанные с именем и расширением типа */
		private $hierarchyTypeIdsByName = array();
		/* @var array $guidsByObjectTypes id объектных типов данных, связанные с их гуидами */
		private $guidsByObjectTypes = array();
		/* @var umiObjectTypesCollection $umiObjectTypesCollection класс для работы с объектными типами данных */
		private $umiObjectTypesCollection;
		/* @var umiHierarchyTypesCollection $umiHierarchyTypesCollection класс для работы с иерархическими типами данных */
		private $umiHierarchyTypesCollection;
		/* @var array $fieldsIdsToFieldTypeId id полей, связанные с id типов полей */
		private $fieldsIdsToFieldTypeId = array();

		/**
		 * Возвращает экземпляр текущего класса
		 * @return umiTypesHelper
		 */
		public static function getInstance() {
			if (is_null(self::$instance)) {
				self::$instance = new umiTypesHelper();
			}
			return self::$instance;
		}

		/**
		 * Возвращает данные о полях (связка guid => id),
		 * присутствующих в объектных типа данных, связанных
		 * иерархическим типом данных по его имени и расширению.
		 * @param string $name название иерархического типа данных
		 * @param string $ext расширение иерархического типа данных
		 * @return array
		 */
		public function getFieldsByHierarchyTypeName($name, $ext) {

			if ($name == 'content' and $ext == 'page') {
				$ext = '';
			}

			if (isset($this->hierarchyTypeIdsByName[$name][$ext])) {
				return $this->getFieldsByHierarchyTypeId($this->hierarchyTypeIdsByName[$name][$ext]);
			}

			$name = umiObjectProperty::filterInputString($name);
			$ext = umiObjectProperty::filterInputString($ext);

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT
  cms3_object_fields.id            AS field_id,
  cms3_object_fields.name          AS field_guid,
  cms3_object_fields.field_type_id AS field_type_id,
  cms3_object_types.id             AS object_type_id,
  cms3_hierarchy_types.id          AS hierarchy_type_id
FROM
  cms3_object_fields
  LEFT JOIN cms3_fields_controller ON cms3_object_fields.id = cms3_fields_controller.field_id
  LEFT JOIN cms3_object_field_groups ON cms3_fields_controller.group_id = cms3_object_field_groups.id
  LEFT JOIN cms3_object_types ON cms3_object_field_groups.type_id = cms3_object_types.id
  LEFT JOIN cms3_hierarchy_types ON cms3_object_types.hierarchy_type_id = cms3_hierarchy_types.id
WHERE
  cms3_hierarchy_types.name = '$name' AND cms3_hierarchy_types.ext = '$ext';
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return array();
			}

			$fields = array();
			foreach($result as $row) {
				$this->fieldsByObjectTypes[$row['object_type_id']][$row['field_guid']] = $row['field_id'];
				$this->objectTypeIdsByHierarchyTypeId[$row['hierarchy_type_id']][$row['object_type_id']] = $row['object_type_id'];
				$this->hierarchyTypeIdsByName[$name][$ext] = $row['hierarchy_type_id'];
				$this->fieldsIdsToFieldTypeId[$row['field_id']] = $row['field_type_id'];
				$fields[$row['object_type_id']][$row['field_guid']] = $row['field_id'];
			}

			return $fields;
		}

		/**
		 * Возвращает данные о полях (связка guid => id),
		 * присутствующих в объектных типа данных, связанных
		 * с иерархическими типами данных, по их id.
		 * @param array $hierarchyTypeIds массив с идентификаторами иерархических типов данных
		 * @return array
		 */
		public function getFieldsByHierarchyTypeId($hierarchyTypeIds) {
			if (!is_array($hierarchyTypeIds)) {
				$hierarchyTypeIds = array(intval($hierarchyTypeIds));
			}

			$loadedFields = array();
			$loadedTypes = array();

			foreach ($hierarchyTypeIds as $hierarchyTypeId) {
				if (isset($this->objectTypeIdsByHierarchyTypeId[$hierarchyTypeId])) {
					$loadedFields[$hierarchyTypeId] = $this->objectTypeIdsByHierarchyTypeId[$hierarchyTypeId];
					$loadedTypes[$hierarchyTypeId] = $hierarchyTypeId;
				}
			}

			if (count($loadedTypes) == count($hierarchyTypeIds)) {
				return $loadedFields;
			}

			$typedIdsToLoad = array_diff($hierarchyTypeIds, $loadedTypes);
			$whereExpression = $this->prepareWhereForHierarchyTypes($typedIdsToLoad);

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT
  cms3_object_fields.id            AS field_id,
  cms3_object_fields.name          AS field_guid,
  cms3_object_fields.field_type_id AS field_type_id,
  cms3_object_types.id             AS object_type_id,
  cms3_hierarchy_types.name        AS hierarchy_type_name,
  cms3_hierarchy_types.ext         AS hierarchy_type_ext,
  cms3_hierarchy_types.id          AS hierarchy_type_id
FROM
  cms3_object_fields
  LEFT JOIN cms3_fields_controller ON cms3_object_fields.id = cms3_fields_controller.field_id
  LEFT JOIN cms3_object_field_groups ON cms3_fields_controller.group_id = cms3_object_field_groups.id
  LEFT JOIN cms3_object_types ON cms3_object_field_groups.type_id = cms3_object_types.id
  LEFT JOIN cms3_hierarchy_types ON cms3_object_types.hierarchy_type_id = cms3_hierarchy_types.id
WHERE
  $whereExpression;
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return array();
			}

			foreach($result as $row) {
				$this->fieldsByObjectTypes[$row['object_type_id']][$row['field_guid']] = $row['field_id'];
				$this->objectTypeIdsByHierarchyTypeId[$row['hierarchy_type_id']][$row['object_type_id']] = $row['object_type_id'];
				$this->hierarchyTypeIdsByName[$row['hierarchy_type_name']][$row['hierarchy_type_ext']] = $row['hierarchy_type_id'];
				$this->fieldsIdsToFieldTypeId[$row['field_id']] = $row['field_type_id'];
				$loadedFields[$row['object_type_id']][$row['field_guid']] = $row['field_id'];

			}

			return $loadedFields;
		}

		/**
		 * Возвращает данные о полях (связка guid => id),
		 * присутствующих в объектных типа данных, по их id.
		 * @param array $objectTypeIds массив с идентификаторами объектных типов данных
		 * @return array
		 */
		public function getFieldsByObjectTypeIds($objectTypeIds) {

			if (!is_array($objectTypeIds)) {
				$objectTypeIds = array(intval($objectTypeIds));
			}

			$loadedFields = array();
			$loadedTypes = array();

			foreach ($objectTypeIds as $objectTypeId) {
				if (isset($this->fieldsByObjectTypes[$objectTypeId])) {
					$loadedFields[$objectTypeId] = $this->fieldsByObjectTypes[$objectTypeId];
					$loadedTypes[$objectTypeId] = $objectTypeId;
				}
			}

			if (count($loadedTypes) == count($objectTypeIds)) {
				return $loadedFields;
			}

			$typedIdsToLoad = array_diff($objectTypeIds, $loadedTypes);
			$whereExpression = $this->prepareWhereForObjectTypes($typedIdsToLoad);

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT
  cms3_object_fields.id               AS field_id,
  cms3_object_fields.name             AS field_guid,
  cms3_object_fields.field_type_id    AS field_type_id,
  cms3_object_types.id                AS object_type_id,
  cms3_object_types.hierarchy_type_id AS hierarchy_type_id
FROM
  cms3_object_fields
  LEFT JOIN cms3_fields_controller ON cms3_object_fields.id = cms3_fields_controller.field_id
  LEFT JOIN cms3_object_field_groups ON cms3_fields_controller.group_id = cms3_object_field_groups.id
  LEFT JOIN cms3_object_types ON cms3_object_field_groups.type_id = cms3_object_types.id
WHERE
  $whereExpression;
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return array();
			}

			foreach($result as $row) {
				$this->fieldsByObjectTypes[$row['object_type_id']][$row['field_guid']] = $row['field_id'];
				$this->objectTypeIdsByHierarchyTypeId[$row['hierarchy_type_id']][$row['object_type_id']] = $row['object_type_id'];
				$this->fieldsIdsToFieldTypeId[$row['field_id']] = $row['field_type_id'];
				$loadedFields[$row['object_type_id']][$row['field_guid']] = $row['field_id'];
			}

			return $loadedFields;
		}

		/**
		 * Возвращает данные о полях (связка guid => id),
		 * присутствующих в объектных типа данных, по их guid.
		 * @param array $objectTypeGuids массив с гуидами объектных типов данных
		 * @return array
		 */
		public function getFieldsByObjectTypeGuid($objectTypeGuids) {

			if (!is_array($objectTypeGuids)) {
				$objectTypeGuids = array(strval($objectTypeGuids));
			}

			$loadedFields = array();
			$loadedTypes = array();

			foreach ($objectTypeGuids as $objectTypeGuid) {
				if (isset($this->guidsByObjectTypes[$objectTypeGuid])) {
					$objectTypeId = $this->guidsByObjectTypes[$objectTypeGuid];
					$loadedFields[$objectTypeId] = $this->getFieldsByObjectTypeIds(array($objectTypeId));
					$loadedTypes[$objectTypeGuid] = $objectTypeGuid;
				}
			}

			if (count($loadedTypes) == count($objectTypeGuids)) {
				return $loadedFields;
			}

			$typedGuidsToLoad = array_diff($objectTypeGuids, $loadedTypes);
			$whereExpression = $this->prepareWhereForGuids($typedGuidsToLoad);

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT
  cms3_object_fields.id            AS field_id,
  cms3_object_fields.name          AS field_guid,
  cms3_object_fields.field_type_id AS field_type_id,
  cms3_object_types.id             AS object_type_id,
  cms3_object_types.guid           AS guid
FROM
  cms3_object_fields
  LEFT JOIN cms3_fields_controller ON cms3_object_fields.id = cms3_fields_controller.field_id
  LEFT JOIN cms3_object_field_groups ON cms3_fields_controller.group_id = cms3_object_field_groups.id
  LEFT JOIN cms3_object_types ON cms3_object_field_groups.type_id = cms3_object_types.id
WHERE
  $whereExpression;
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return array();
			}

			foreach($result as $row) {
				$this->fieldsByObjectTypes[$row['object_type_id']][$row['field_guid']] = $row['field_id'];
				$this->guidsByObjectTypes[$row['guid']] = $row['object_type_id'];
				$this->fieldsIdsToFieldTypeId[$row['field_id']] = $row['field_type_id'];
				$loadedFields[$row['object_type_id']][$row['field_guid']] = $row['field_id'];
			}

			return $loadedFields;
		}

		/**
		 * Возвращает id объектного типа данных по его гуиду.
		 * @param string $guid гуид объектного типа данных
		 * @return int
		 */
		public function getObjectTypeIdByGuid($guid) {

			if (isset($this->guidsByObjectTypes[$guid])) {
				return (int) $this->guidsByObjectTypes[$guid];
			}

			$typeId = $this->umiObjectTypesCollection->getTypeIdByGUID($guid);

			if (is_numeric($typeId)) {
				return $this->guidsByObjectTypes[$guid] = $typeId;
			}

			return $typeId;
		}

		/**
		 * Возвращает id иерархического типа данных по имени и расширению.
		 * @param string $name имя иерархического типа данных
		 * @param string $ext расширение иерархического типа данных
		 * @return int
		 */
		public function getHierarchyTypeIdByName($name, $ext) {

			if ($name == 'content' and $ext == 'page') {
				$ext = '';
			}

			if (isset($this->hierarchyTypeIdsByName[$name][$ext])) {
				return (int) $this->hierarchyTypeIdsByName[$name][$ext];
			}

			$typeId = $this->umiHierarchyTypesCollection->getTypeByName($name, $ext);

			if (is_numeric($typeId)) {
				return $this->hierarchyTypeIdsByName[$name][$ext] = $typeId;
			}

			return $typeId;
		}

		/**
		 * Возвращает массив идентификатор объектных типов данных,
		 * связанных с иерархическим типом, по его id.
		 * @param int $hierarchyTypeId ид иерархического типа данных
		 * @return array
		 */
		public function getObjectTypesIdsByHierarchyTypeId($hierarchyTypeId) {
			$hierarchyTypeId = intval($hierarchyTypeId);

			if (isset($this->objectTypeIdsByHierarchyTypeId[$hierarchyTypeId])) {
				return $this->objectTypeIdsByHierarchyTypeId[$hierarchyTypeId];
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT `id`, `guid` FROM `cms3_object_types` WHERE `hierarchy_type_id` = $hierarchyTypeId;";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return array();
			}

			foreach($result as $row) {
				$this->objectTypeIdsByHierarchyTypeId[$hierarchyTypeId][$row['id']] = $row['id'];
				if ($row['guid'] != '' || is_null($row['guid'])) {
					$this->guidsByObjectTypes[$row['guid']] = $row['id'];
				}
			}

			return $this->objectTypeIdsByHierarchyTypeId[$hierarchyTypeId];
		}

		/**
		 * Возвращает ид типа данных поля (umiFieldType) по ид поля (umiField)
		 * @param int $fieldId ид поля
		 * @return bool|int
		 */
		public function getFieldTypeIdByFieldId($fieldId) {
			if (is_numeric($fieldId) && isset($this->fieldsIdsToFieldTypeId[$fieldId])) {
				return (int) $this->fieldsIdsToFieldTypeId[$fieldId];
			}
			return false;
		}

		/**
		 * Очищает внутренний кеш
		 * @return void
		 */
		public function clearCache() {
			$this->fieldsByObjectTypes = array();
			$this->objectTypeIdsByHierarchyTypeId = array();
			$this->hierarchyTypeIdsByName = array();
			$this->guidsByObjectTypes = array();
			$this->fieldsIdsToFieldTypeId = array();
		}

		/**
		 * Конструктор
		 */
		private function __construct() {
			$this->umiObjectTypesCollection = umiObjectTypesCollection::getInstance();
			$this->umiHierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
			$this->preLoadHierarchyTypes();
		}

		/**
		 * Загружает данные (связка id => name+ext) об иерархических типах
		 * @return void
		 */
		private function preLoadHierarchyTypes() {
			$hierarchyTypes = $this->umiHierarchyTypesCollection->getTypesList();
			/* @var $hierarchyTypes umiHierarchyType[]*/
			foreach ($hierarchyTypes as $hierarchyType) {
				$this->hierarchyTypeIdsByName[$hierarchyType->getName()][$hierarchyType->getExt()] = $hierarchyType->getId();
			}
		}

		/**
		 * Возвращает условие для sql запроса данных объектных типов данных
		 * @param array $objectTypeIds массив с идентификаторами объектных типов
		 * @return string
		 */
		private function prepareWhereForObjectTypes(array $objectTypeIds) {
			if (count($objectTypeIds) == 1) {
				$objectTypeIds = (int) array_shift($objectTypeIds);
				return "cms3_object_types.id = $objectTypeIds";
			}
			$objectTypeIds = array_map('intval', $objectTypeIds);
			$objectTypeIds = implode(',', $objectTypeIds);
			return "cms3_object_types.id in ($objectTypeIds)";
		}

		/**
		 * Возвращает условие для sql запроса данных иерархических типов данных
		 * @param array $objectTypeIds массив с идентификаторами иерархических типов
		 * @return string
		 */
		private function prepareWhereForHierarchyTypes(array $hierarchyTypeIds) {
			if (count($hierarchyTypeIds) == 1) {
				$hierarchyTypeIds = (int) array_shift($hierarchyTypeIds);
				return "cms3_hierarchy_types.id = $hierarchyTypeIds";
			}
			$hierarchyTypeIds = array_map('intval', $hierarchyTypeIds);
			$hierarchyTypeIds = implode(',', $hierarchyTypeIds);
			return "cms3_hierarchy_types.id in ($hierarchyTypeIds)";
		}

		/**
		 * Возвращает условие для sql запроса данных объектыъ типов данных
		 * @param array $guids массив с гуидами объектных типов
		 * @return string
		 */
		private function prepareWhereForGuids(array $guids) {
			if (count($guids) == 1) {
				$guids = umiObjectProperty::filterInputString(array_shift($guids));
				return "cms3_object_types.guid  = '$guids'";
			}
			$guids = array_map('umiObjectProperty::filterInputString', $guids);
			$guids = implode('\', \'', $guids);
			return "cms3_hierarchy_types.id in ('$guids')";
		}
	}
?>