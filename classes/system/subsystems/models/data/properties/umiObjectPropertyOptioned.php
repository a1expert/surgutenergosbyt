<?php
	/**
	 * Этот класс служит для управления свойством объекта.
	 * Обрабатывает тип поля "Кнопка-флажок"
	 */
	class umiObjectPropertyOptioned extends umiObjectProperty {
		public function setValue($value) {
			if (is_array($value)) {
				$value = array_distinct($value);
			}
			parent::setValue($value);
		}

		/**
		 * Загружает значение свойства из БД
		 * @return Array массив со значениями свойства
		 */
		protected function loadValue() {
			$values = array();

			$data = $this->getPropData();
			if ($data == false) {
				$data = array();
				$sql = "SELECT int_val, varchar_val, text_val, rel_val, tree_val, float_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$this->field_id}'";
				$result = l_mysql_query($sql, true);
				while ($row = mysql_fetch_assoc($result)) {
					foreach ($row as $i => $v) {
						$data[$i][] = $v;
					}
				}
			}

			$i = 0;
			while ($value = $this->parsePropData($data, $i)) {
				foreach ($value as $t => $v) {
					$value[$t] = ($t == 'float') ? $this->filterFloat($v) : self::filterOutputString($v);
				}

				$values[] = $value;
				$i++;
			}
			return $values;
		}

		/**
		 * Сохраняет значение свойства в БД
		 */
		protected function saveValue() {
			$this->deleteCurrentRows();
			foreach ($this->value as $key => $data) {
				$sql = "INSERT INTO `{$this->tableName}` (`obj_id`, `field_id`, `int_val`, `varchar_val`, `rel_val`, `tree_val`, `float_val`) VALUES ('{$this->object_id}', '{$this->field_id}', ";

				$cnt = 0;
				if ($intValue = (int) getArrayKey($data, 'int')) {
					$sql .= "'{$intValue}', ";
					++$cnt;
				} else {
					$sql .= "NULL, ";
				}

				if ($varcharValue = (string) getArrayKey($data, 'varchar')) {
					$varcharValue = self::filterInputString($varcharValue);
					$sql .= "'{$varcharValue}', ";
					++$cnt;
				} else {
					$sql .= "NULL, ";
				}

				if ($relValue = (int) $this->prepareRelationValue(getArrayKey($data, 'rel'))) {
					$sql .= "'{$relValue}', ";
					++$cnt;
				} else {
					$sql .= "NULL, ";
				}
				$this->values[$key]['rel'] = $relValue;

				if ($treeValue = (int) getArrayKey($data, 'tree')) {
					$sql .= "'{$treeValue}', ";
					++$cnt;
				} else {
					$sql .= "NULL, ";
				}

				if ($floatValue = (float) getArrayKey($data, 'float')) {
					$sql .= "'{$floatValue}'";
					++$cnt;
				} else {
					$sql .= "NULL";
				}

				$sql .= ")";

				if ($cnt < 2) {
					continue;
				}

				l_mysql_query($sql);
			}
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param Array $data
		 * @param Integer $index
		 */
		protected function parsePropData($data, $index) {
			$result = Array();
			$hasValue = false;
			foreach ($data as $contentType => $values) {
				if (isset($values[$index])) {
					$contentType = $this->decodeContentType($contentType);
					$result[$contentType] = $values[$index];
					$hasValue = true;
				}
			}
			return $hasValue ? $result : false;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param unknown_type $contentType
		 */
		protected function decodeContentType($contentType) {
			if (substr($contentType, -4) == '_val') {
				$contentType = substr($contentType, 0, strlen($contentType) - 4);
			}
			return $contentType;
		}

		/**
		 * TODO PHPDoc
		 * (non-PHPdoc)
		 * @see umiObjectProperty::applyParams()
		 */
		protected function applyParams($values, $params = NULL) {
			$filter = getArrayKey($params, 'filter');
			$requireFieldType = getArrayKey($params, 'field-type');

			if (!is_null($filter)) {
				$result = Array();
				foreach ($values as $index => $value) {
					foreach ($filter as $fieldType => $filterValue) {
						if (isset($value[$fieldType]) && $value[$fieldType] == $filterValue) {
							$result[] = $value;
						}
					}
				}
				$values = $result;
			}

			if (!is_null($requireFieldType)) {
				foreach ($values as $i => $value) {
					$values[$i] = getArrayKey($value, $requireFieldType);
				}
			}
			return $values;
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param unknown_type $value
		 */
		protected function filterFloat($value) {
			return round($value, 2);
		}

		/**
		 * @inherit
		 */
		protected function isNeedToSave(array $newValue) {
			$oldValue = $this->value;

			$oldValue = $this->normaliseValue($oldValue);
			$newValue = $this->normaliseValue($newValue);

			if (count($oldValue) !== count($newValue)) {
				return true;
			}

			foreach ($newValue as $key => $value) {
				if (!isset($oldValue[$key])) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Приводит значение составного поля к определенному формату, для сравнения.
		 * Возвращает результат форматирования.
		 * @param array $values значение составного поля
		 * @return array
		 */
		private function normaliseValue(array $values) {

			if (count($values) == 0) {
				return $values;
			}

			$normalisedValues = array();

			foreach ($values as $value) {

				$normalisedInt = $this->extractIntValue($value);

				if (!is_null($normalisedInt)) {
					$value['int'] = $normalisedInt;
				} else {
					unset($value['int']);
				}

				$normalisedFloat = $this->extractFloatValue($value);

				if (!is_null($normalisedFloat)) {
					$value['float'] = $normalisedFloat;
				} else {
					unset($value['float']);
				}

				$normalisedRel = $this->extractRelValue($value);

				if (!is_null($normalisedRel)) {
					$value['rel'] = $normalisedRel;
				} else {
					unset($value['rel']);
				}

				$normalisedVarchar = $this->extractStringValue($value);

				if (!is_null($normalisedVarchar)) {
					$value['varchar'] = $normalisedVarchar;
				} else {
					unset($value['varchar']);
				}

				$normalisedTree = $this->extractTreeValue($value);

				if (!is_null($normalisedTree)) {
					$value['tree'] = $normalisedTree;
				} else {
					unset($value['tree']);
				}

				$propertyKey = '';

				foreach ($value as $key) {
					$propertyKey .= $key;
				}

				$normalisedValues[md5($propertyKey)] = $value;
			}

			return $normalisedValues;
		}

		/**
		 * Извлекает значение ссылки на дерево из составного поля
		 * @param array $value значение составного поля
		 * @return int|null
		 */
		private function extractTreeValue(array $value) {

			if (!isset($value['tree'])) {
				return null;
			}

			switch(true) {
				case $value['tree'] instanceof umiHierarchyElement === true: {
					return (int) $value['tree']->getId();
				}
				case is_numeric($value['tree']): {
					return intval($value['tree']);
				}
				default: {
					return null;
				}
			}
		}

		/**
		 * Извлекает строковое значение из составного поля
		 * @param array $value значение составного поля
		 * @return int|null
		 */
		private function extractStringValue($value) {

			if (!isset($value['varchar'])) {
				return null;
			}

			return strval($value['varchar']);
		}

		/**
		 * Извлекает целочисленное значение из составного поля
		 * @param array $value значение составного поля
		 * @return int|null
		 */
		private function extractIntValue($value) {

			if (!isset($value['int'])) {
				return null;
			}

			return intval($value['int']);
		}

		/**
		 * Извлекает дробное числовое значение из составного поля
		 * @param array $value значение составного поля
		 * @return int|null
		 */
		private function extractFloatValue($value) {

			if (!isset($value['float'])) {
				return null;
			}

			return floatval($value['float']);
		}

		/**
		 * Извлекает значение ссылки на объект из составного поля
		 * @param array $value значение составного поля
		 * @return int|null
		 */
		private function extractRelValue($value) {

			if (!isset($value['rel'])) {
				return null;
			}

			switch(true) {
				case $value['rel'] instanceof umiObject === true: {
					return (int) $value['rel']->getId();
				}
				case is_numeric($value['rel']): {
					return intval($value['rel']);
				}
				case is_string($value['string']): {
					return strval($value['rel']);
				}
				default: {
					return null;
				}
			}
		}
	};
?>