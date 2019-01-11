<?php
	/**
	 * Selector - механизм формирования выборок, который должен заменить старый механизм выборок с помощью umiSelection и umiSelectionParser.
	 * Класс selector создан для того, чтобы избавиться от необходимости использования дополнительных классов и упростить определение искомых полей.
	 *
	 * @property int $length Кол-во элементов в выборке
	 * @property int $total Кол-во элементов в выборке
	 * @property array $result Результат выборки
	 * @property mixed $first Первый элемент в выборке
	 * @property mixed $last Последний элемент в выборке
	 *
	 * @version 1.0
	 */
	class selector implements IteratorAggregate {
		protected
			$mode, $permissions = null, $limit, $offset,
			$types = array(), $hierarchy = array(),
			$whereFieldProps = array(), $whereSysProps = array(),
			$orderSysProps = array(), $orderFieldProps = array(),
			$groupSysProps = array(), $groupFieldProps = array(),
			$executor, $result = null, $length = null,
			$options = array();

		protected static
			$modes = array('objects', 'pages'),
			$sysPagesWhereFields = array('id', 'name', 'owner', 'domain', 'lang', 'is_deleted',
				'is_active', 'is_visible', 'updatetime', 'is_default', 'template_id', '*'),
			$sysObjectsWhereFields = array('id', 'name', 'owner', 'guid', '*'),
			$sysPagesGroupFields = array('name', 'owner', 'domain', 'lang', 'is_deleted',
				'is_active', 'is_visible', 'updatetime', 'is_default', 'template_id'),
			$sysObjectsGroupFields = array('id', 'name', 'owner', 'guid'),
			$sysOrderFields = array('name', 'ord', 'rand', 'updatetime', 'id');

		protected function checkExecuted() {
			if($this->executor && $this->executor->getSkipExecutedCheckState()) return;
			if(!is_null($this->result) || !is_null($this->length)) {
				throw new selectorException("Selector has been already executed. You should create new one or use selector::flush() method instead.");
			}
		}

		protected function executor() {
			if(!$this->executor) $this->executor = new selectorExecutor($this);
			return $this->executor;
		}

		protected function unloadExecutor() {
			if(!is_null($this->length) && !is_null($this->result)) {
				unset($this->executor);
			}
		}

		protected function setMode($mode) {
			if(in_array($mode, self::$modes)) {
				$this->mode = $mode;
				if($mode == 'pages') {
					$this->setDefaultPagesWhere();
				}
			} else {
				throw new selectorException(
					"This mode \"{$mode}\" is not supported, choose one of these: " . implode(', ', self::$modes)
				);
			}
		}

		protected function setDefaultPagesWhere () {
			$cmsController = cmsController::getInstance();
			$this->where('domain')->equals($cmsController->getCurrentDomain());
			$this->where('lang')->equals($cmsController->getCurrentLang());
			$this->where('is_deleted')->equals(0);

			if($cmsController->getCurrentMode() != 'admin') {
				$this->where('is_active')->equals(1);
			}
		}


		public static function get($requestedType) {
			return new selectorGetter($requestedType);
		}

		/**
		 * Конструктор.
		 *
		 * @param $mode Режим: 'objects' или 'pages'
		 */
		public function __construct($mode) {
			$this->setMode($mode);
		}

		/**
		 * @param bool|string $typeClass
		 *
		 * @return array|selectorType
		 */
		public function types($typeClass = false) {
			$this->checkExecuted();
			if ($typeClass === false) {
				return $this->types;
			}
			return $this->types[] = new selectorType($typeClass, $this);
		}

		/**
		 * Выбор поля
		 *
		 * @param string $fieldName Имя поля
		 *
		 * @return selectorWhereProp|selectorWhereHierarchy|selectorWherePermissions
		 * @throws selectorException Поле выбрано неверно или не существует
		 */
		public function where($fieldName) {
			$this->checkExecuted();
			if($fieldName == 'hierarchy') {
				if($this->mode == 'objects')
					throw new selectorException("Hierarchy filter is not suitable for \"objects\" selector mode");
				return $this->hierarchy[] = new selectorWhereHierarchy;
			}

			if($fieldName == 'permissions') {
				if($this->mode == 'objects')
					throw new selectorException("Permissions filter is not suitable for \"objects\" selector mode");
				if(is_null($this->permissions)) $this->permissions = new selectorWherePermissions;
				return $this->permissions;
			}

			if(in_array($fieldName, ($this->mode == 'pages') ? self::$sysPagesWhereFields : self::$sysObjectsWhereFields)) {
				return $this->whereSysProps[] = new selectorWhereSysProp($fieldName);
			} else {
				$fieldId = $this->searchField($fieldName);

				if (!$fieldId) {
					throw new selectorException(__METHOD__ . ": Field \"{$fieldName}\" is not presented in selected object types");
				}

				return $this->whereFieldProps[] = new selectorWhereFieldProp($fieldId);
			}
		}

		/**
		 * Упорядочивание
		 *
		 * Сортирует результат по выбранному полю
		 *
		 * @param string $fieldName Имя поля для сортировки
		 *
		 * @return selectorOrderField
		 * @throws selectorException если поле не существует
		 */
		public function order($fieldName) {
			$this->checkExecuted();
			if(in_array($fieldName, self::$sysOrderFields)) {
				return $this->orderSysProps[] = new selectorOrderSysProp($fieldName);
			} else {
				$fieldId = $this->searchField($fieldName);

				if (!$fieldId) {
					throw new selectorException(__METHOD__ . ": Field \"{$fieldName}\" is not presented in selected objects types");
				}

				return $this->orderFieldProps[] = new selectorOrderFieldProp($fieldId);
			}
		}

		/**
		 * Группировка
		 *
		 * @param string $fieldName Поле для группировки
		 *
		 * @return selectorGroupField
		 * @throws selectorException если поле не существует
		 */
		public function group($fieldName) {
			$this->checkExecuted();
			if(in_array($fieldName, ($this->mode == 'pages') ? self::$sysPagesGroupFields : self::$sysObjectsGroupFields)) {
				return $this->groupSysProps[] = new selectorGroupSysProp($fieldName);
			} else {
				$fieldId = $this->searchField($fieldName);

				if (!$fieldId) {
					throw new selectorException(__METHOD__ . ": Field \"{$fieldName}\" is not presented in selected objects types");
				}

				return $this->groupFieldProps[] = new selectorGroupFieldProp($fieldId);
			}
		}

		/**
		 * Ограничение количества результатов
		 *
		 * @param int $offset
		 * @param int $limit
		 */
		public function limit($offset, $limit) {
			$this->checkExecuted();
			$this->limit = (int) $limit;
			$this->offset = (int) $offset;
		}

		/**
		 * Результат работы селектора
		 *
		 * Запускает executor для объекта
		 *
		 * @return array выбранные поля
		 */
		public function result() {
			if(is_null($this->result)) {
				if($this->mode == 'pages') {
					if(sizeof($this->orderSysProps) == 0) $this->order('ord')->asc();
					if(is_null($this->permissions) && !$this->option('no-permissions')->value) $this->where('permissions');
				}
				$return = $this->option('return')->value;
				if (is_array($return) && in_array('count', $return)) {
					$this->result = $this->executor()->length();
				} else {
					$this->result = $this->executor()->result();
					$this->length = $this->executor()->length;
				}
			}
			$this->unloadExecutor();
			return $this->result;
		}

		/**
		 * Получает кол-во элементов в выборке
		 *
		 * @return int
		 */
		public function length() {
			if(is_null($this->length)) {
				if($this->mode == 'pages' && is_null($this->permissions)) {
					$this->where('permissions');
				}
				$length = $this->executor()->length();
				if (in_array('count', $this->option('return')->value)) {
					$this->result = $length;
				}
				else $this->result = $this->executor()->result();
				$this->length = $length;
			}
			$this->unloadExecutor();
			return $this->length;
		}

		public function option($name, $value = null) {
			$this->checkExecuted();
			if (!isset($this->options[$name])) {
				$selectorOption = new selectorOption($name);
				$this->options[$name] = $selectorOption;
			}
			if (!is_null($value)) $this->options[$name]->value($value);
			return $this->options[$name];
		}

		public function flush() {
			$this->result = null;
			$this->length = null;
		}

		/**
		 * {@inheritdoc}
		 */
		public function __get($prop) {
			switch($prop) {
				case 'length':
				case 'total':
					return $this->length();
				case 'result':
					return $this->result();
				case 'first':
					return (sizeof($this->result())) ? $this->result[0] : null;
				case 'last':
					return (sizeof($this->result())) ? $this->result[sizeof($this->result) - 1] : null;
			}

			$allowedProps = array('mode', 'offset', 'limit', 'whereFieldProps', 'orderFieldProps', 'groupFieldProps',
				'whereSysProps', 'orderSysProps', 'groupSysProps', 'types', 'permissions', 'hierarchy', 'options');

			if(in_array($prop, $allowedProps)) {
				return $this->$prop;
			}
		}

		public function getIterator() {
			$this->result();
			return new ArrayIterator($this->result);
		}

		/**
		 * Получает запрос сформированный executor'ом
		 *
		 * @return string
		 */
		public function query() {
			if($this->mode == 'pages') {
				if(sizeof($this->orderSysProps) == 0) $this->order('ord')->asc();
				if(is_null($this->permissions)) $this->where('permissions');
			}

			return $this->executor()->query();
		}

		/**
		 * Получает Id поля по его имени
		 * @internal
		 *
		 * @param string $fieldName Имя поля
		 *
		 * @return int Id поля
		 */
		public function searchField($fieldName) {
			$fieldIds = array();

			if ($this->mode == 'pages' && count($this->types) == 0) {
				$type = new selectorType('object-type', $this);
				$type->guid('root-pages-type');
				$this->types[] = $type;
			}

			foreach($this->types as $type) {
				$fieldId = $type->getFieldId($fieldName);
				if ($fieldId) {
					if (is_array($fieldId)) {
						$fieldIds = array_unique(
							array_merge(
								$fieldIds,
								$fieldId)
						);
					} else {
						$fieldIds[] = $fieldId;
					}
				}
			}

			if (count($fieldIds) > 0) {
				return (int) array_shift($fieldIds);
			}

			return false;
		}
	};
?>