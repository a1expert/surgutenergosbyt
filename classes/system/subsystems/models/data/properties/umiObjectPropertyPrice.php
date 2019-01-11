<?php
	/**
	 * Этот класс служит для управления свойством объекта.
	 * Обрабатывает тип поля "Цена". При загрузке данных вызывается событие "umiObjectProperty_loadPriceValue".
	 */
	class umiObjectPropertyPrice extends umiObjectPropertyFloat {
		protected $dbValue;

		/**
		 * Загружает значение свойства из БД, если тип свойства "Цена"
		 * @return Array массив со значением Float
		 */
		protected function loadValue() {
			$res = parent::loadValue();

			$price = 0;
			if (is_array($res) && isset($res[0])) {
				list($price) = $res;
			}

			$this->dbValue = $price;

			$oEventPoint = new umiEventPoint("umiObjectProperty_loadPriceValue");
			$oEventPoint->setParam("object_id", $this->object_id);
			$oEventPoint->addRef("price", $price);
			$oEventPoint->call();

			$res = Array($price);
			return $res;
		}

		/**
		 * Сохраняет значение свойства в БД, если тип свойства "Цена"
		 */
		protected function saveValue() {
			$this->deleteCurrentRows();

			$cnt = 0;
			foreach ($this->value as $val) {
				if ($val === false || $val === "") continue;

				if (strpos(".", $val) === false) {
					$val = str_replace(",", ".", $val);
				}
				$val = abs((float) $val);
				if ($val > 999999999.99) {
					$val = 999999999.99;
				}

				$sql = "INSERT INTO {$this->tableName} (obj_id, field_id, float_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				l_mysql_query($sql);

				if ($err = l_mysql_error()) {
					throw new coreException($err);
				}
				++$cnt;
			}

			$this->dbValue = $this->value;

			if (!$cnt) {
				$this->fillNull();
			}
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		public function __wakeup() {
			if ($this->dbValue) {
				$price = $this->dbValue;

				$oEventPoint = new umiEventPoint("umiObjectProperty_loadPriceValue");
				$oEventPoint->setParam("object_id", $this->object_id);
				$oEventPoint->addRef("price", $price);
				$oEventPoint->call();

				$value = Array($price);
				$this->value = $value;
			}
		}
	};
?>