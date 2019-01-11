<?php
	class selectorType {
		protected $typeClass, $objectType, $hierarchyType, $selector;
		protected static $typeClasses = array('object-type', 'hierarchy-type');
		public $objectTypeIds, $hierarchyTypeIds;
		
		public function __construct($typeClass, $selector) {
			$this->setTypeClass($typeClass);
			$this->selector = $selector;
		}
		
		public function name($module, $method) {
			if(!$method && $module == 'content') $method = 'page';
			$umiTypesHelper = umiTypesHelper::getInstance();

			switch($this->typeClass) {
				case 'object-type': {
					$umiTypesHelper->getFieldsByHierarchyTypeName($module, $method);
					$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName($module, $method);

					if (!$hierarchyTypeId) {
						throw new selectorException(__METHOD__ . ": Hierarchy type ($module, $method) not found");
					}

					$objectTypeIds = $umiTypesHelper->getObjectTypesIdsByHierarchyTypeId($hierarchyTypeId);

					if (!is_array($objectTypeIds) || count($objectTypeIds) == 0) {
						throw new selectorException(__METHOD__ . ": Object types ids by hierarchy type ($hierarchyTypeId) not found");
					}

					return $this->setObjectTypeIds($objectTypeIds);
				}
				
				case 'hierarchy-type': {
					$umiTypesHelper->getFieldsByHierarchyTypeName($module, $method);
					$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName($module, $method);

					if (!$hierarchyTypeId) {
						throw new selectorException(__METHOD__ . ": Hierarchy type ($module, $method) not found");
					}

					$this->setHierarchyTypeIds($hierarchyTypeId);
				}
			}
		}
		
		public function id($id) {
			if (!is_numeric($id)) {
				return $this->guid($id);
			}
			if (!is_array($id)) {
				$id = array($id);
			}
			$id = array_map('intval', $id);
			$umiTypesHelper = umiTypesHelper::getInstance();
			
			switch($this->typeClass) {
				case 'object-type': {
					$umiTypesHelper->getFieldsByObjectTypeIds($id);
					return $this->setObjectTypeIds($id);
				}
				
				case 'hierarchy-type': {
					$umiTypesHelper->getFieldsByHierarchyTypeId($id);
					return $this->setHierarchyTypeIds($id);
				}
			}
		}
		
		public function guid($guid) {
			if($this->typeClass != 'object-type') {
				throw new selectorException("Select by guid is allowed only for object-type");
			}

			$umiTypesHelper = umiTypesHelper::getInstance();
			$umiTypesHelper->getFieldsByObjectTypeGuid($guid);

			if(!is_array($guid)) {
				$guid = array($guid);
			}

			$objectTypeIds = array();

			foreach($guid as $value) {
				$typeId = $umiTypesHelper->getObjectTypeIdByGuid($value);
				if (is_numeric($typeId)) {
					$objectTypeIds[] = $typeId;
				}
			}

			if (!is_array($objectTypeIds) || count($objectTypeIds) == 0) {
				throw new selectorException(__METHOD__ . ": Object types ids by guid ($guid) not found");
			}

			return $this->setObjectTypeIds($objectTypeIds);
		}
		
		public function setTypeClass($typeClass) {
			if(in_array($typeClass, self::$typeClasses)) {
				$this->typeClass = $typeClass;
			} else {
				throw new selectorException(
					"Unkown type class \"{$typeClass}\". These types are only supported: " . implode(", ", self::$typeClasses)
				);
			}
		}
		
		public function getFieldId($fieldName) {
			if (is_null($this->objectTypeIds) && is_null($this->hierarchyTypeIds)) {
				throw new selectorException("Object and hierarchy type prop can't be empty both");
			}

			$umiTypesHelper = umiTypesHelper::getInstance();
			$fieldIds = array();

			if (!is_null($this->objectTypeIds)) {
				$objectTypesFields = $umiTypesHelper->getFieldsByObjectTypeIds($this->objectTypeIds);
				foreach ($this->objectTypeIds as $id) {
					if (isset($objectTypesFields[$id][$fieldName])) {
						$fieldIds[] = $objectTypesFields[$id][$fieldName];
					}
				}
			}

			if (!is_null($this->hierarchyTypeIds)) {
				foreach ($this->hierarchyTypeIds as $hierarchyTypeId) {
					$objectTypeIds = $umiTypesHelper->getObjectTypesIdsByHierarchyTypeId($hierarchyTypeId);
					$objectTypesFields = $umiTypesHelper->getFieldsByObjectTypeIds($objectTypeIds);
					foreach ($objectTypeIds as $id) {
						if (isset($objectTypesFields[$id][$fieldName])) {
							$fieldIds[] = $objectTypesFields[$id][$fieldName];
						}
					}
				}
			}

			$fieldIds = array_unique($fieldIds);
			$fieldIdsCount = count($fieldIds);

			if ($fieldIdsCount === 0) {
				return false;
			}

			return (int) array_shift($fieldIds);
		}

		/**
		 * @deprecated
		 * @param $prop
		 * @return mixed
		 */
		public function __get($prop) {
			$allowedProps = array('objectType', 'hierarchyType');
			
			if(in_array($prop, $allowedProps)) {
				return $this->$prop;
			}
		}

		protected function setObjectTypeIds($objectTypeIds) {
			if (!is_array($objectTypeIds)) {
				$objectTypeIds = array($objectTypeIds);
			}

			if (is_null($this->objectTypeIds)) {
				$this->objectTypeIds = $objectTypeIds;
			} else {
				$this->objectTypeIds = array_unique(array_merge($this->objectTypeIds, $objectTypeIds));
			}
		}

		protected function setHierarchyTypeIds($hierarchyTypeId) {
			if (!is_array($hierarchyTypeId)) {
				$hierarchyTypeId = array($hierarchyTypeId);
			}
			if (is_null($this->hierarchyTypeIds)) {
				$this->hierarchyTypeIds = $hierarchyTypeId;
			} else {
				$this->hierarchyTypeIds = array_unique(array_merge($this->hierarchyTypeIds, $hierarchyTypeId));
			}
		}

		/**
		 * @deprecated
		 * @param $objectType
		 * @throws selectorException
		 */
		protected function setObjectType($objectType) {
			if($objectType instanceof iUmiObjectType) {
				$this->objectType = $objectType;
			} else {
				throw new selectorException("Wrong object type given");
			}
		}

		/**
		 * @deprecated
		 * @param $hierarchyType
		 * @throws selectorException
		 */
		protected function setHierarchyType($hierarchyType) {
			if($hierarchyType instanceof iUmiHierarchyType) {
				$this->hierarchyType = $hierarchyType;
			} else {
				throw new selectorException("Wrong hierarchy type given");
			}
		}
	};
?>