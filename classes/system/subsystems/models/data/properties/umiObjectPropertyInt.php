<?php
	/**
	 * Этот класс служит для управления свойством объекта.
	 * Обрабатывает тип поля "Число"
	 */
	class umiObjectPropertyInt extends umiObjectProperty {
		/**
		 * Загружает значение свойства из БД, если тип свойства целое число
		 * @return Array массив со значением Integer
		 */
		protected function loadValue() {
			$res = Array();
			$field_id = $this->field_id;

			if ($data = $this->getPropData()) {
				foreach ($data['int_val'] as $val) {
					if (is_null($val)) continue;
					$res[] = (int) $val;
				}
				return $res;
			}

			$sql = "SELECT int_val FROM {$this->tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$field_id}' LIMIT 1";
			$result = l_mysql_query($sql, true);

			while (list($val) = mysql_fetch_row($result)) {
				if (is_null($val)) continue;
				$res[] = (int) $val;
			}

			return $res;
		}

		/**
		 * Сохраняет значение свойства в БД, если тип свойства "Число"
		 */
		protected function saveValue() {
			$this->deleteCurrentRows();

			$cnt = 0;
			foreach ($this->value as $val) {
				if ($val === false || $val === "") continue;
				$val = (int) $val;

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, int_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
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

			if (!isset($oldValue[0])) {
				$oldValue = 0;
			} else {
				$oldValue = intval($oldValue[0]);
			}

			if (!isset($newValue[0])) {
				$newValue = 0;
			} else {
				$newValue = intval($newValue[0]);
			}

			return ($oldValue === $newValue) ? false : true;
		}
	};
?>