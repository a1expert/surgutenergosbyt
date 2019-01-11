<?php
	/**
	 * Этот класс служит для управления свойством объекта
	 * Обрабатывает тип поля "Ссылка на дерево".
	 */
	class umiObjectPropertySymlink extends umiObjectProperty {
		/**
		 * Загружает значение свойства из БД, если тип свойства "Ссылка на дерево"
		 * @return Array массив со значением Integer - id элемента структуры
		 */
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if ($data = $this->getPropData()) {
				$this->loadElements($data['tree_val']);
				foreach ($data['tree_val'] as $val) {
					if (is_null($val)) continue;
					$element = umiHierarchy::getInstance()->getElement( (int) $val );
					if ($element === false) continue;
					if ($element->getIsActive() == false) continue;

					$res[] = $element;
				}
				return $res;
			}

			$sql = "SELECT tree_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}'";
			$result = l_mysql_query($sql, true);

			while (list($val) = mysql_fetch_row($result)) {
				if (is_null($val)) continue;
				$element = umiHierarchy::getInstance()->getElement( (int) $val );
				if ($element === false) continue;
				if ($element->getIsActive() == false) continue;

				$res[] = $element;
			}
			return $res;
		}

		/**
		 * Формирует объекты страниц (umiHierarchyElement) по идентификаторам
		 * @param array $elementsIds массив с идентификаторами страниц
		 * @return void
		 */
		private function loadElements($elementsIds) {
			if (!$elementsIds) {
				return;
			}

			if (!is_array($elementsIds)) {
				$elementsIds = array($elementsIds);
			}

			if (count($elementsIds) == 0) {
				return;
			}

			$umiHierarchy = umiHierarchy::getInstance();
			$permittedIds = array();
			foreach($elementsIds as $id) {
				if ($umiHierarchy->isAllowed($id) && !$umiHierarchy->isLoaded($id)) {
					$permittedIds[] = $id;
				}
			}
			$elementsIds = $permittedIds;

			if (count($elementsIds) == 0) {
				return;
			}

			$elementsIds = implode(',', $elementsIds);
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
SELECT
  h.id,
  h.rel,
  h.type_id,
  h.lang_id,
  h.domain_id,
  h.tpl_id,
  h.obj_id,
  h.ord,
  h.alt_name,
  h.is_active,
  h.is_visible,
  h.is_deleted,
  h.updatetime,
  h.is_default,
  o.name,
  o.type_id AS object_type_id
FROM cms3_hierarchy h, cms3_objects o
WHERE h.id IN ($elementsIds) AND o.id = h.obj_id;
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			if ($result->length() == 0) {
				return;
			}

			foreach($result as $row) {
				$elementId = array_shift($row);
				$umiHierarchy->getElement($elementId, false, false, $row);
			}
		}

		/**
		 * Сохраняет значение свойства в БД, если тип свойства "Ссылка на дерево"
		 */
		protected function saveValue() {
			$this->deleteCurrentRows();
			$hierarchy = umiHierarchy::getInstance();

			$cnt = 0;
			foreach ($this->value as $i => $val) {
				if (is_object($val)) {
					$val = (int) $val->getId();
				} else {
					$val = intval($val);
				}

				if (!$val) continue;
				$this->value[$i] = $hierarchy->getElement($val);

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, tree_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";

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

			foreach ($newValue as $newValueTag) {
				if (!in_array($newValueTag, $oldValue)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Приводит значение поля типа "ссылка на дерево" к определенному формату, для сравнения.
		 * Возвращает результат форматирования.
		 * @param array $values значение поля типа "ссылка на дерево""
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
				}
			}

			return $normalisedValues;
		}
	};
?>