<?php
	/**
	 * Этот класс служит для управления свойством объекта.
	 * Обрабатывает тип поля "Выпадающий список", т.е. свойства с использованием справочников.
	 */
	class umiObjectPropertyRelation extends umiObjectProperty {
		/**
		 * Загружает значение свойства из БД, если тип свойства "Ссылка на объект"
		 * @return Array массив со значениями свойства
		 */
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if ($data = $this->getPropData()) {
				foreach ($data['rel_val'] as $val) {
					if (is_null($val)) continue;
					$res[] = $val;
				}
				return $res;
			}

			if ($this->getIsMultiple()) {
				$sql = "SELECT rel_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}'";
			} else {
				$sql = "SELECT rel_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			}

			$result = l_mysql_query($sql, true);

			while (list($val) = mysql_fetch_row($result)) {
				if (is_null($val)) continue;
				$res[] = $val;
			}
			return $res;
		}

		/**
		 * Сохраняет значение свойства в БД, если тип свойства "Ссылка на объект"
		 */
		protected function saveValue() {
			$this->deleteCurrentRows();

			if (is_null($this->value)) {
				return;
			}

			$tmp = Array();
			foreach ($this->value as $val) {
				if (!$val) continue;

				if (is_string($val) && strpos($val, "|") !== false) {
					$tmp1 = explode("|", $val);
					foreach ($tmp1 as $v) {
						$v = trim($v);
						if ($v) {
							$tmp[] = $v;
						}
						unset($v);
					}
					unset($tmp1);
					//Check, if we can use it without fieldTypeId
					$this->getField()->setFieldTypeId(umiFieldTypesCollection::getInstance()->getFieldTypeByDataType('relation',1)->getId());

				} else {
					$tmp[] = $val;
				}
			}
			$this->value = $tmp;
			unset($tmp);

			$cnt = 0;

			foreach ($this->value as $key => $val) {
				if ($val) {
					$val = $this->prepareRelationValue($val);
					$this->values[$key] = $val;
				}
				if (!$val) continue;

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, rel_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);
				++$cnt;
			}

			if (!$cnt) {
				$this->fillNull();
			}
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

			if (!$this->getIsMultiple()) {
				if (!isset($oldValue[0])) {
					$oldValue = null;
				} else {
					$oldValue = $oldValue[0];
				}

				if (!isset($newValue[0])) {
					$newValue = null;
				} else {
					$newValue = $newValue[0];
				}

				return ($oldValue === $newValue) ? false : true;
			}

			foreach ($newValue as $newValueRel) {
				if (!in_array($newValueRel, $oldValue)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Приводит значение поля типа "выпадающий список" к определенному формату, для сравнения.
		 * Возвращает результат форматирования.
		 * @param array $values значение поля типа "выпадающий список"
		 * @return array
		 */
		private function normaliseValue(array $values) {

			if (count($values) == 0) {
				return $values;
			}

			$normalisedValues = array();

			foreach ($values as $value) {
				switch (true) {
					case $value instanceof umiEntinty: {
						$normalisedValues[] = (int) $value->getId();
						break;
					}
					case is_numeric($value): {
						$normalisedValues[] =  intval($value);
						break;
					}
					case is_string($value): {
						$normalisedValues[] =  strval($value);
						break;
					}
				}
			}

			return $normalisedValues;
		}
	};
?>