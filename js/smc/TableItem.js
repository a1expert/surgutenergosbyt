/**
* TableItem
* Класс осуществяляет визуализацию строки таблицы
* @param {Object} _oControl - экземпляр класса Control
* @param {Object} _oParent - экземпляр класса TableItem, указывающий на прямого предка элемента
* @param {Object} _oData - информация о элементе
* @param {Object} _oSiblingItem - экземпляр класса TableItem, указывающий на соседа элемента
* @param {String} _sInsertMode - режим добавление элемента по отношению к соседу _oSiblingItem. Может быть "after" и "before"
*/
var TableItem = function(_oControl, _oParent, _oData, _oSiblingItem, _sInsertMode) {
	var id = parseInt(_oData.id);
	var __self = this;
	var Data = _oData;
	var Parent = _oParent;
	var SiblingItem = _oSiblingItem || null;
	var InsertMode = _sInsertMode || 'after';
	var ForceDraw = typeof(_oData['force-draw']) !== 'undefined' ? parseInt(_oData['force-draw']) : 1;
	var CountChilds = typeof(_oData['childs']) !== 'undefined' ? parseInt(_oData['childs']) : 0;
	var baseModule = (typeof(_oData['basetype']) !== 'undefined' && typeof(_oData['basetype']['module']) === 'string') ? _oData['basetype']['module'] : "content";
	var baseMethod = (typeof(_oData['basetype']) !== 'undefined' && typeof(_oData['basetype']['method']) === 'string')? _oData['basetype']['method'] : "";
	var typeName = (typeof(_oData['basetype']) !== 'undefined' && typeof(_oData['basetype']['_value']) === 'string')? _oData['basetype']['_value'] : "";
	var IconsPath = _oControl.iconsPath;
	var iconSrc = typeof(_oData['iconbase']) !== 'undefined' ? _oData['iconbase'] : IconsPath + 'ico_' + baseModule + '_' + baseMethod + '.png';
	var toggleCtrl = null;
	var labelCtrl = null;
	var labelText = null;
	var itemIcon = null;
	var dropIndicator = null;
	var Settings = SettingsStore.getInstance();
	var AutoexpandAllowed = true;
	var oldClassName      = null;
	var selected		  = false;
	var flatMode = _oControl.flatMode;
	var objectTypesMode = _oControl.objectTypesMode;
	var pagesBar = null;
	var filterRow = null;

	var activeColumn = null;
	/**
	* (Public properties)
	*/
	this.element = null;
	this.control = _oControl;
	this.childsContainer = null;
	this.id = id;
	this.name = typeof(_oData['name']) !== 'undefined' ? _oData['name'] : getLabel('js-smc-noname-page');
	this.isRoot = Parent ? false: true;
	this.loaded = false;
	this.viewLink = typeof(_oData['link']) !== 'undefined' ? _oData['link'] : false;
	this.editLink = typeof(_oData['edit-link']) !== 'undefined' ? _oData['edit-link'] : false;
	this.createLink = typeof(_oData['create-link']) !== 'undefined' ? _oData['create-link'] : false;
	this.permissions = typeof(_oData['permissions']) !== 'undefined' ? parseInt(_oData['permissions']) : 0;
	this.isActive = typeof(_oData['is-active']) !== 'undefined' ? parseInt(_oData['is-active']) : 0;
	if(objectTypesMode) {
		this.isActive = true;
	}
	this.isVirtualCopy = typeof(_oData['virtual-copy']) !== 'undefined' ? true : false;
	this.lockedBy = typeof(_oData['locked-by']) === 'object' ? _oData['locked-by'] : null;
	this.expiration = typeof(_oData['expiration']) === 'object' ? _oData['expiration'] : null;

	this.templateId = typeof(_oData['template-id']) !== 'undefined' ? _oData['template-id'] : null;
	this.langId = typeof(_oData['lang-id']) !== 'undefined' ? _oData['lang-id'] : null;
	this.domainId = typeof(_oData['domain-id']) !== 'undefined' ? _oData['domain-id'] : null;

	this.allowCopy = typeof(_oData['allow-copy']) !== 'undefined' ? parseInt(_oData['allow-copy']) : true;
	this.allowActivity = typeof(_oData['allow-activity']) !== 'undefined' ? parseInt(_oData['allow-activity']) : true;
	this.allowDrag = false;//typeof(_oData['allow-drag']) !== 'undefined' ? _oData['allow-drag'] : true;

	this.parent = Parent;
	this.childs = [];
	this.hasChilds = (CountChilds > 0 || id == 0);
	this.labelControl = null;
	this.position = false;
	this.isExpanded = false;
	this.filter = new filter;
	this.filter.setParentElements(id);
	this.level = Parent ? Parent.level + 1 : 0;
	this.ignoreEmptyFilter = false;
	this.pageLimits = this.control.PerPageLimits;

	this.pageing = {
		'total' : 0,
		'limit' : 0,
		'offset': 0
	};


	this.baseModule = (typeof(_oData['basetype']) !== 'undefined' && typeof(_oData['basetype']['module']) === 'string') ? _oData['basetype']['module'] : "content";
	this.baseMethod = (typeof(_oData['basetype']) !== 'undefined' && typeof(_oData['basetype']['method']) === 'string')? _oData['basetype']['method'] : "";

	/**
	* (Private methods)
	*/
	/**
	* Конструктор класса
	* @access private
	*/
	var __constructor = function() {
		if (_oParent) {
			_oParent.childs.push(id);
			if (ForceDraw) __draw(Parent.childsContainer);
		} else {
			if (ForceDraw) __drawRoot();
		}
	};

	var isObjectActivated = function (item, defaultActivity) {
		if(flatMode && __self.control.contentType != 'pages') {
			if(__self.control.enableObjectsActivity) {
				return (defaultActivity
					|| item.getValue('is_activated')
					|| item.getValue('is_active')
					|| item.getValue('activated')) !== "";
			} else {
				return true;
			}
		} else {
			return defaultActivity;
		}
	};

	/**
	* "Рисует" элемент
	* @access private
	* @param {DOMElement} _oContainerEl контейнер для добавления элемента
	*/
	var __draw = function(_oContainerEl) {
		__self.isActive = isObjectActivated(__self, __self.isActive);
		var usedColumns = __getUsedColumns();
		var columnsMenu = __getColumnsMenu();
		var columnsTable = __getColumnsTable();

		var element = document.createElement('tr');
		element.setAttribute('rel', id);
		element.className = 'ti';
		element.rel = id;
		element.className = 'ti';

		labelCtrl = element;
		__self.labelControl = labelCtrl;

		// fix for opera and mozilla (cancel user select)
		//element.onmousedown = function() { return false; }

		// toggle column
		var toggleColumn = document.createElement('td');
		toggleColumn.className = 'ti-toggle';
		toggleCtrl = document.createElement('img');
		toggleCtrl.className = 'ti-toggle';

		var tsrc = __self.hasChilds ? IconsPath + 'expand.png' : IconsPath + 'collapse-disabled.png';
		toggleCtrl.setAttribute('src', tsrc);

		toggleCtrl.onclick = function() {
			__self.toggle();
			return false;
		};

		toggleColumn.appendChild(toggleCtrl);


		// label column
		var nameColumn = usedColumns['name'];
		var cLabel = document.createElement('td');
		cLabel.className = 'ti column';
		cLabel.id = 'c_' + __self.control.id + '_' + __self.id  + '_name';

		var size = parseInt(nameColumn['params'][0]);
		if (!flatMode) size -= 20 * (__self.level - 1);

		cLabel.style.width = size + 'px';
		cLabel.style.owerflow = 'hidden';
		cLabel.name = 'name';

		var labelColumn = document.createElement('div');
		labelColumn.style.width = size + 'px';
		labelColumn.style.owerflow = 'hidden';

		cLabel.appendChild(labelColumn);

		if (!objectTypesMode) new editableCell(__self, cLabel, usedColumns['name']);

		// itemicon
		itemIcon = document.createElement('img');
		itemIcon.style.border = '0px';
		itemIcon.setAttribute('alt', typeName);
		itemIcon.setAttribute('title',typeName);
		itemIcon.setAttribute('src', iconSrc);
		itemIcon.className = 'ti-icon';
		itemIcon.onmousedown = function() {
			return false;
		};

		if (__self.control.allowDrag && __self.allowDrag) itemIcon.style.cursor = 'move';

		if(!__self.control.flatMode && !__self.control.objectTypesMode) {
			labelColumn.appendChild(itemIcon);
		} else {
			itemIcon = null;
		}

		// indicators
		if (__self.expiration) {
			var oStatus = __self.expiration['status'];
			if (oStatus) {
				var statusSID = oStatus['id'];
				var statusName = oStatus['_value'];
				if (statusSID) {
					var expInd =  document.createElement('img');
					var ico = IconsPath + 'ico_' + statusSID + '.png';
					expInd.setAttribute("src", ico);
					expInd.setAttribute("alt", statusName);
					expInd.setAttribute("title", statusName);
					expInd.className = 'page-status';
					labelColumn.appendChild(expInd);
				}
			}
		}

		labelText = document.createElement('a');
		var t = __self.getValue('name');
		var val = new String((typeof(t) == 'string' && t.length) ? t : "" );

		if(!val.length) {
			val = getLabel('js-smc-noname-page');
		}

		if(val.length > 45) {
			val = val.substring(0, 45) + "&#8230;";
		}

		labelColumn.title = (__self.viewLink) ? __self.viewLink : val.replace(/<[^>]+>/g, '');
		labelText.innerHTML = val;
		if (__self.editLink) {
			labelText.setAttribute('href', __self.editLink); // Edit Link);
		} else {
			labelText.className = 'unactive';
			labelText.onclick = function() {
				return false;
			};
		}

		labelColumn.appendChild(labelText);

		//if (!__self.isActive && !__self.control.flatMode && !__self.control.objectTypesMode) {
		if(__self.isVirtualCopy) {
			var virtualLabel = document.createElement('span');
			virtualLabel.className = 'label-virtual';
			virtualLabel.innerHTML = getLabel('js-smc-virtual-copy');
			labelColumn.appendChild(virtualLabel);
		}

		if (!__self.isActive && !__self.control.objectTypesMode) {
			if(!__self.control.flatMode) {
				if (itemIcon) jQuery(itemIcon).fadeTo(300, 0.5);
				if (toggleCtrl) jQuery(toggleCtrl).fadeTo(300, 0.5);
			}
			jQuery(labelText).fadeTo(300, 0.5);
		}


		// columns
		if (!flatMode) element.appendChild(toggleColumn);
		element.appendChild(cLabel);

		if (__self.control.enableEdit) {
			var cManage = document.createElement('td');
			cManage.className   = 'ti column';
			cManage.style.width = '140px';
			var labelManage = document.createElement('div');
			labelManage.style.width = '140px';
			//cManage.appendChild(labelManage);
			element.appendChild(cManage);
		}

		// additional columns
		//temp

		var colSpan = 2;
		for (var name in usedColumns) {
			var column = columnsTable[name];
			var params = usedColumns[name]['params'];
			if (params[1] == 'static') {
				column = usedColumns[name];
			}
			if (!column) continue;

			colSpan++;
			//var column = columnsMenu[name];
			var col = document.createElement('td');
			col.id = 'c_' + __self.control.id + '_' + __self.id  + '_' + name;
			col.className = 'column';
			col.style.width = params[0];
			col.name = name;
			col.style.cursor = 'text';

			val = __self.getValue(name);

			col.innerHTML = '<div style="width:' + usedColumns[name]['params'][0] + '">' + val + '</div>';

			element.appendChild(col);
			// make editable
			if (column.dataType) {
				new editableCell(__self, col, column);
			}

			if(name == 'is_activated' || name== 'active' || name =='is_active') {
				activeColumn = col;;
			}
		}

		var autoCol = document.createElement('td');
		autoCol.className = 'auto-column';
		autoCol.innerHTML = '&nbsp;';
		autoCol.style.width = 'auto';
		element.appendChild(autoCol);

		if (SiblingItem) {
			var prevEl = null;
			if (InsertMode.toLowerCase() == 'after') {
				prevEl = SiblingItem.element.nextSibling;
			} else {
				prevEl = SiblingItem.element;
			}
			if (prevEl) {
				__self.element = _oContainerEl.insertBefore(element, prevEl);
			} else {
				__self.element = _oContainerEl.appendChild(element);
			}
		} else {
			__self.element = _oContainerEl.appendChild(element);
		}

		// drop indicator
		/*
		dropIndicator = $(document.createElement('tbody'));
		dropIndicator.className = 'ti-drop';
		__self.control.container.appendChild(dropIndicator);
		*/

		if (__self.control.dragAllowed && __self.allowDrag) {
			var DropMode = 'child';

			jQuery(labelCtrl).draggable({
				distance: Control.DragSensitivity,
				handle : '.ti-icon, a',
				helper: function() {
					// clone item element
					var el = __self.element;
					var drag_el = document.createElement('div');
					drag_el.innerHTML = '<div>' + __self.name + '</div>';
					drag_el.className = 'ti-draggable';
					jQuery(drag_el).css({
						'position' : 'absolute',
						'background' : 'url(' + iconSrc + ') no-repeat 0 4px'
					});

					return  drag_el;
				},
				start: function() {
					Control.DraggableItem = __self;
					Control.DragMode = true;
					if (__self.control.toolbar) __self.control.toolbar.hide();
				},
				stop: function() {
					if (Control.HandleItem) {
						Control.HandleItem.deInitDroppable();
						if (Control.DraggableItem) {
							Control.DraggableItem.tryMoveTo(Control.HandleItem, DropMode);
						}
					}
					Control.DraggableItem = null;
					Control.DragMode = false;
				},
				drag: function(event, ui) {
					var x = event.pageX;
					var y = event.pageY;
					var hItem = Control.detectItemByMousePointer(x, y);
					var oldHItem = Control.HandleItem;
					if (oldHItem) {
						oldHItem.deInitDroppable();
					}

					Control.HandleItem = hItem;
					if (hItem) {
						var cpos = jQuery(hItem.control.initContainer).position();
						var itmHeight = hItem.position.bottom - hItem.position.top;
						var itmDelta = y - cpos.top - hItem.position.top;
						if (itmDelta < itmHeight / 3) {
							DropMode = 'before';
						}
						if (itmDelta > itmHeight / 3 && itmDelta < 2 * itmHeight / 3) {
							DropMode = 'child';
						}
						if (itmDelta > 2 * itmHeight / 3) {
							DropMode = 'after';
						}

						// force disable for root
						if (hItem.isRoot) DropMode = 'child';
						hItem.initDroppable(DropMode);
					}
				}

			});
		}


		// childs container
		var childsRow = document.createElement('tr');
		childsRow.style.display = 'none';
		{
			var childsCol = document.createElement('td');
			childsCol.colSpan = colSpan + 1;
			{
				var childsTable = document.createElement('table');
				childsTable.cellSpacing = "0";
				{
					var childsTableBody = document.createElement('tbody');
					childsTable.appendChild(childsTableBody);
					childsTableBody.className = 'ti-childs-container';

					// pageing
					var pagesRow = document.createElement('tr');
					pagesRow.style.display = 'none';
					{
						pagesBar = document.createElement('td');
						pagesBar.id = 'pb_' + __self.control.id + '_' + __self.id;
						pagesBar.className = 'pages-bar';

						if (!flatMode) colSpan++;
						pagesBar.colSpan = colSpan;


						pagesRow.appendChild(pagesBar);
					}
					childsTableBody.appendChild(pagesRow);

					__self.childsContainer = childsTableBody;
				}
				childsCol.appendChild(childsTable);
			}
			if (!flatMode) {
				var emptyCol = document.createElement('td');
				emptyCol.className = 'ti-toggle';
				childsRow.appendChild(emptyCol);

			}
			childsRow.appendChild(childsCol);
		}


		if(!element.nextSibling)
			  _oContainerEl.appendChild(childsRow);
		else element.parentNode.insertBefore(childsRow,element.nextSibling);
	};

	/**
	* Пытается отправить запрос на перемещение элемента
	* @access public
	* @param Object Item - элемент в который (или после которого) пытаемся переместить текущий
	* @param Boolean asSibling - если true, перемещаем после элемента Item, если false, то делаем элемент первым ребенком Item'a
	* @return False ,в вслучае если перемещение не возможно
	*/
	this.tryMoveTo = function(Item, MoveMode) {
		if (Item) {
			var before = this.control.getRootNodeId();
			var rel = Item.id;
			var asSibling = MoveMode !== 'child' ? 1 : 0;
			if (MoveMode == 'before') {
				before = Item.id;
				rel = Item.parent.id;
			}
			if (MoveMode == 'after') {
				var s = Item.getNextSibling();
				rel = Item.parent.id;
				before = s ? s.id : this.control.getRootNodeId();
			}

			if (Item === this) return false;
			if (Item.checkIsChild(this)) return false;
			if (before == this.id) return false;

			var receiver = Item;
			if (asSibling) {
				receiver = Item.parent  ? Item.parent : Item.control.getRoot();
			}

			this.control.dataSet.execute('tree_move_element', {
				'element' : this.id,
				'before' : before,
				'rel' : rel,
				'as-sibling' : asSibling,
				'domain' : Item.control.getRoot().name,
				'childs' : 1,
				'links' : 1,
				'virtuals' : 1,
				'permissions' : 1,
				'templates': 1,
				'receiver_item' : receiver,
				'handle_item' : this
			});
		}
	};


	/**
	* Пытается подготовить элемент, как контейнер для перемещаемого
	* @access public
	* @param Boolean asSibling - если true, готовим для перемещения после текущего элемента, если false, то готвоим для перемещения в качестве первого ребенка
	* @return False ,в вслучае если перемещение в этот элемент не возможно
	*/
	this.initDroppable = function(DropMode) {
		var DropMode = DropMode || 'child';
		var di = Control.DraggableItem;
		var cpos = jQuery(this.control.initContainer).position();
		if (di) {
			if (di === this) return false;
			if (this.checkIsChild(di)) return false;

			var ind = Control.dropIndicator;

			if(oTable) {
				var table_w = jQuery(oTable.container).width();
				if(table_w <  this.position.right) {
						this.position.right = table_w;
				}
			}

			if (DropMode == 'after') {
				//document.title = 'drop after ' + this.name;
				ind.style.top = this.position.bottom + cpos.top + 'px';
				ind.style.left = this.position.left + cpos.left + 'px';
				ind.style.width = this.position.right -  this.position.left;
			}
			if (DropMode == 'before') {
				//document.title = 'drop before ' + this.name;
				ind.style.top = this.position.top + cpos.top + ind.offsetHeight + 'px';
				ind.style.left = this.position.left + cpos.left + 'px';
				ind.style.width = this.position.right -  this.position.left + 'px';
			}
			if (DropMode == 'child') {
				//document.title = 'drop as child ' + this.name;
				ind.style.top = this.position.bottom + cpos.top + 'px';
				ind.style.left = this.position.left + cpos.left + 20 + 'px';
				ind.style.width = this.position.right + cpos.left - 20 -  this.position.left + 'px';
			}

			ind.style.display = '';

			setTimeout(__autoExpandSelf, 3239); //MUF
		}
	};

	/**
	* Восстанавливает состояние элемента из режима "контейнер для перемещаемого"
	* @access public
	*/
	this.deInitDroppable = function() {
		if (!Control.dropIndicator) return;
		Control.dropIndicator.style.display = 'none';
		//this.labelControl.className = selected ? 'ti selected' : 'ti';
	};

	var __getUsedColumns = function() {
		if (__self.control.usedColumns) return __self.control.usedColumns;

		var usedColumns = {};

		var setColumns = Settings.get(__self.control.id, "used-columns");
		if (setColumns === false) {
			// get default columns
			setColumns = __self.control.visiblePropsMenu
			if (setColumns.length == 0) {
				setColumns = __self.control.dataSet.getDefaultFields();
			}
			if (!setColumns) setColumns = "";
		}

		if (setColumns.length) {
			var arrCols = setColumns.split("|");
			for (var i = 0; i < arrCols.length; i++) {
				var info = arrCols[i];
				var colName = arrCols[i];
				var colParams = [];
				var offset = info.indexOf('[');
				if (offset) {
					colName = info.substring(0, offset);
					colParams = info.substring(offset + 1, info.length - 1).split(',');

				}
				usedColumns[colName] = {
					'name' : colName,
					'params' : colParams
				};
			}
		}

		if (!usedColumns['name']) {
			usedColumns['name'] = {
				'name': 'name',
				'params' : ['250px']
			};
		}

		var sequenceProps = __self.control.sequencePropsMenu;

		usedColumns = objectSort(usedColumns, sequenceProps);

		__self.control.usedColumns = usedColumns;

		return usedColumns;
	};

	var objectSort = function(object, keys){
		var property, sortedObject = {}, i = 0;

		for(property in object){
			if(!object.hasOwnProperty(keys[i])){
				delete keys[i];
				keys.splice(i);
			}
			if(object.hasOwnProperty(property)){
				// Проверим наличие свойства в массиве ключей
				if(keys.indexOf(property) === -1){
					keys.push(property);
				}
				sortedObject[keys[i]] = object[keys[i]] || "";
				i++;
			}
		}
		return sortedObject;
	}

	var __setUsedColumns = function() {
		var usedColumns = __getUsedColumns();
		var cols = [];
		for (name in usedColumns) {
			var col = usedColumns[name];
			if (!col) continue;
			cols[cols.length] = name + '[' + col.params.join(',') + "]";
		}

		Settings.set(__self.control.id, cols.join('|'), "used-columns");
	};

	var __getColumnsMenu = function() {
		if (__self.control.columnsMenu) return __self.control.columnsMenu;


		var commonGroups = __self.control.dataSet.getCommonFields();
		var usedColumns = __getUsedColumns();
		var stopList = __self.control.dataSet.getFieldsStoplist();

		var ColumnsMenu = {};
		__self.control.needColumnsMenu = false;

		var num = 1, usedNum = 0;

		for (var i = 0; i < commonGroups.length; i++) {
			num++;
			var groupFields = commonGroups[i].getElementsByTagName('field');
			var needSeparator = false;

			for (var j = 0; j < groupFields.length; j++) {
				var field = groupFields[j];
				var title = field.getAttribute('title');
				var fieldId = field.getAttribute('id');
				var name = field.getAttribute('name');

				if(jQuery.inArray(name, __self.control.requiredPropsMenu) != -1) {
					continue;
				}

				var tnodes = field.getElementsByTagName('type');
				var dataType = tnodes[0].getAttribute('data-type');
				var guideId = field.getAttribute('guide-id');

				if(dataType == "symlink" || dataType == "password") {
					continue;
				}

				var deny = false;
				for (var s = 0; s < stopList.length; s++) {
					if (stopList[s] == name) {
						deny = true;
						break;
					}
				}

				if (deny) continue;
				__self.control.needColumnsMenu = true;

				var used = false;
				if (usedColumns[name]) used = true;
				ColumnsMenu[name] = {
					'caption' : title,
					'icon' : used ? 'checked' : 'undefined',
					'id': fieldId,
					'title' : title,
					'fieldName' : name,
					'dataType' : dataType,
					'guideId' : guideId,
					'checked' : used,
					'execute' : function(item) {
						var mitm = item;
						item.checked ? __self.removeColumn(item.fieldName) : __self.appendColumn(item.fieldName);
						Control.recalcItemsPosition();
					}
				};

				num++;
				if (i < groupFields.length - 1) needSeparator = true;
				if (used) usedNum++;
			}

			if (needSeparator) {
				// separator
				ColumnsMenu[num + '-sep'] = '-';
			}
		}

		__self.control.columnsMenu = ColumnsMenu;

		return ColumnsMenu;
	};

	var __getColumnsTable = function() {
		if (__self.control.columnsTable) return __self.control.columnsTable;


		var commonGroups = __self.control.dataSet.getCommonFields();
		var usedColumns = __getUsedColumns();
		var stopList = __self.control.dataSet.getFieldsStoplist();

		var ColumnsTable = {};
		__self.control.needColumnsTable = false;

		var num = 1, usedNum = 0;

		for (var i = 0; i < commonGroups.length; i++) {
			num++;
			var groupFields = commonGroups[i].getElementsByTagName('field');
			var needSeparator = false;

			for (var j = 0; j < groupFields.length; j++) {
				var field = groupFields[j];
				var title = field.getAttribute('title');
				var fieldId = field.getAttribute('id');
				var name = field.getAttribute('name');
				var tnodes = field.getElementsByTagName('type');
				var dataType = tnodes[0].getAttribute('data-type');
				var guideId = field.getAttribute('guide-id');

				if(dataType == "symlink" || dataType == "password") {
					continue;
				}

				var deny = false;
				for (var s = 0; s < stopList.length; s++) {
					if (stopList[s] == name) {
						deny = true;
						break;
					}
				}

				if (deny) continue;
				__self.control.needColumnsMenu = true;

				var used = false;
				if (usedColumns[name]) used = true;
				ColumnsTable[name] = {
					'caption' : title,
					'icon' : used ? 'checked' : 'undefined',
					'id': fieldId,
					'title' : title,
					'fieldName' : name,
					'dataType' : dataType,
					'guideId' : guideId,
					'checked' : used,
					'execute' : function(item) {
						var mitm = item;
						item.checked ? __self.removeColumn(item.fieldName) : __self.appendColumn(item.fieldName);
						Control.recalcItemsPosition();
					}
				};

				num++;
				if (i < groupFields.length - 1) needSeparator = true;
				if (used) usedNum++;
			}

			if (needSeparator) {
				// separator
				ColumnsTable[num + '-sep'] = '-';
			}
		}

		__self.control.columnsTable = ColumnsTable;

		return ColumnsTable;
	};

	this.resizeColumn = function(fieldName, size) {
		var usedColumns = __getUsedColumns();
		var columnsMenu = __getColumnsMenu();
		var column = usedColumns[fieldName];
		if (!column) return false;

		for (var j = 0; j < __self.childs.length; j++) {
			var ch = __self.control.items[__self.childs[j]];
			if (ch) ch.resizeColumn(fieldName, size);
		}


		if (__self.isRoot) {
			var el = document.getElementById('h_' + __self.control.id + '_' + fieldName);
			if (!el) return false;
			el.style.width = size + 'px';
			el.firstChild.style.width = size + 'px';

			usedColumns[fieldName].params[0] = size + 'px';
			__self.control.usedColumns = usedColumns;
			__setUsedColumns();

			// filter
			var fltr = document.getElementById('f_' + __self.control.id + '_' + fieldName);
			if (fltr) {
				fltr.style.width = size + 'px';
				if(fltr.firstChild) {
					fltr.firstChild.style.width = size + 'px';
				}
			}

		} else {
			var el = document.getElementById('c_' + __self.control.id + '_' + __self.id + '_' + fieldName);
			if (!el) return false;

			if (!flatMode && fieldName == "name") size -= 20 * (__self.level - 1);
			el.style.width = size + 'px';
			el.firstChild.style.width = size + 'px';
		}

		return true;
	};


	this.startResizeColumn = function(fieldName, evnt) {
		var columnEl = document.getElementById('h_' + this.control.id + '_' + fieldName);
		if (!columnEl) return false;
		var cHeight = this.control.initContainer.offsetHeight;
		var cpos = jQuery(this.control.initContainer).position();
		var colPos = jQuery(columnEl).position();
		colPos.left -= this.control.initContainer.scrollLeft;
		var colWidth = columnEl.offsetWidth;
		var colRightPos = colPos.left + colWidth;
		var resizer = document.createElement('div');
		resizer.className = 'resizer';
		jQuery(resizer).css({
			'z-index' : 1,
			'position' : 'absolute',
			'width'		: '1px',
			'height'	: cHeight,
			'borderLeft': '1px dotted #CCC',
			'top'		: colPos.top + cpos.top,
			'left'		: colRightPos + cpos.left

		});


		var deltaX = colRightPos - evnt.clientX;
		resizer.style.left = deltaX + 'px';

		var newColWidth = colWidth;
		var onMouseMove = function(mEvent) {
			try {
				document.body.style['-moz-user-select'] = 'none';
				document.body.style['-khtml-user-select'] = 'none';
				document.body.onselectstart = function() {return false;};
				document.selection.empty();
			} catch (err) {}

			var mX = mEvent.clientX + deltaX;
			newColWidth = colWidth + mX - colRightPos;

			if (newColWidth > TableItem.maxColumnWidth) newColWidth = TableItem.maxColumnWidth;
			if (newColWidth < TableItem.minColumnWidth) newColWidth = TableItem.minColumnWidth;

			resizer.style.left = cpos.left + colPos.left + newColWidth + 'px';

		};

		var onMouseUp = function(event) {
			jQuery(document).unbind("mousemove", onMouseMove);
			jQuery(document).unbind("mouseup", onMouseUp);
			document.body.removeChild(resizer);
			if (newColWidth != colWidth) {
				__self.resizeColumn(fieldName, newColWidth);
				Control.recalcItemsPosition();
			}
		};

		jQuery(document).bind('mousemove', onMouseMove);
		jQuery(document).bind('mouseup', onMouseUp);

		document.body.appendChild(resizer);


	};

	this.appendColumn = function(fieldName) {
		var usedColumns = __getUsedColumns();
		var columnsMenu = __getColumnsMenu();
		var column = columnsMenu[fieldName];
		if (!column || usedColumns[fieldName]) return false;

		for (var j = 0; j < __self.childs.length; j++) {
			var ch = __self.control.items[__self.childs[j]];
			if (ch) ch.appendColumn(fieldName);
		}

		if (__self.isRoot) {
			// root el
			var col = document.createElement('th');
			col.className = 'column';
			col.setAttribute("id", 'h_' + __self.control.id + '_' + fieldName);
			col.setAttribute("name", name);
			col.name = fieldName;
			col.width = '100px';
			var header = document.createElement('div');
			header.title = column.title;
			header.innerHTML = column.title;
			col.appendChild(header);

			var resizer = document.createElement('span');
			resizer.onmousedown = function(event) {
				if (!event) event = window.event;
				__self.startResizeColumn(fieldName, event);
			};

			header.appendChild(resizer);

			__self.element.firstChild.insertBefore(col, __self.element.firstChild.lastChild);

			usedColumns[fieldName] = {
				'name': fieldName,
				'params': ['100px']
			};
			__self.control.usedColumns = usedColumns;

			columnsMenu[fieldName].checked = true;
			columnsMenu[fieldName].icon = 'checked';
			__self.control.columnsMenu = columnsMenu;

			// column filter
			var colFltr = document.createElement('th');
			colFltr.setAttribute("id", 'f_' + __self.control.id + '_' + fieldName);
			colFltr.style.width = usedColumns[fieldName]['params'][0];
			__self.control.onDrawFieldFilter(columnsMenu[fieldName], colFltr, usedColumns[fieldName].params);
			filterRow.insertBefore(colFltr, filterRow.lastChild);

			// change pgBar colspan
			var pgBar = document.getElementById('pb_' + __self.control.id + '_' + __self.id);
			if (pgBar) pgBar.colSpan += 1;

			__setUsedColumns();
			__toggleFilterRow();
		} else {
			var col = document.createElement('td');
			col.id = 'c_' + __self.control.id + '_' + __self.id + '_' + fieldName;
			col.style.width = '100px';
			col.className = 'column';
			col.name = fieldName;

			if(document.getElementById(col.id)) {
				return true;
			}

			var val = __self.getValue(fieldName);

			col.innerHTML = '<div>' + val + '</div>';
			__self.element.insertBefore(col, __self.element.lastChild);
			// change colspan
			__self.childsContainer.parentNode.parentNode.colSpan += 1;

			new editableCell(__self, col, column);
		}

		return true;
	};

	this.removeColumn = function(fieldName) {
		var usedColumns = __getUsedColumns();
		var columnsMenu = __getColumnsMenu();
		var column = columnsMenu[fieldName];
		if (!column || !usedColumns[fieldName]) return false;

		for (var j = 0; j < __self.childs.length; j++) {
			var ch = __self.control.items[__self.childs[j]];
			if (ch) ch.removeColumn(fieldName);
		}


		if (__self.isRoot) {
			// root el
			var el = document.getElementById('h_' + __self.control.id + '_' + fieldName);
			if (!el) return false;
			el.parentNode.removeChild(el);

			var fCell = document.getElementById('f_' + __self.control.id + '_' + fieldName);
			if (fCell) {
				fCell.parentNode.removeChild(fCell);
			}

			var usedCols = {};
			for (var name in usedColumns) {
				if (name == fieldName) continue;
				usedCols[name] = usedColumns[name];
			}
			__self.control.usedColumns = usedCols;

			columnsMenu[fieldName].checked = false;
			columnsMenu[fieldName].icon = 'undefined';
			__self.control.columnsMenu = columnsMenu;

			__self.control.onRemoveColumn(column);

			// change pgBar colspan
			__self.childsContainer.parentNode.parentNode.colSpan -= 1;
			var pgBar = document.getElementById('pb_' + __self.control.id + '_' + __self.id);
			if (pgBar) pgBar.colSpan -= 1;

			__setUsedColumns();
			__toggleFilterRow();
		} else {
			var el = document.getElementById('c_' + __self.control.id + '_' + __self.id + '_' + fieldName);
			if (!el) return false;
			el.parentNode.removeChild(el);
			// change colspan
			__self.childsContainer.parentNode.parentNode.colSpan -= 1;
		}

		return true;
	};

	var __toggleFilterRow = function () {
		var usedColumns = __getUsedColumns();
		var columnsTable = __getColumnsTable();

		var i = 0;
		for(var name in usedColumns) {
			var column = columnsTable[name];
			var params = usedColumns[name]['params'];

			if (params[1] == 'static') {
				column = usedColumns[name];
				column.title = getLabel('js-smc-'+name);
			}

			if (!column) continue;
			i++;
		}

		if(filterRow) {
			filterRow.style.display = (i > 0) ? '' : 'none';
		}
	};


	/**
	* Рисует корневой элемент
	* @access private
	*/
	var __drawRoot = function() {
		// drop indicator
		if (!Control.dropIndicator) {
			var dropIndicator = document.createElement('div');
			dropIndicator.className = 'ti-drop';
			Control.dropIndicator = document.body.appendChild(dropIndicator);
		}

		var usedColumns = __getUsedColumns();
		var columnsMenu = __getColumnsMenu();
		var columnsTable = __getColumnsTable();

		var possibleColumnsCount = 0, usedColumnsCount = 0;
		for(var i in columnsMenu) {
			++possibleColumnsCount;
		}

		// рисуем колонки и контейнер для детей, корневой элемент не отображаем
		var tHead = document.createElement('thead');
		{
			tHead.setAttribute('name', __self.control.id);
			var hRow = document.createElement('tr');
			hRow.className = 'header-row';
			filterRow = document.createElement('tr');
			filterRow.className = 'filter-row';
			{

				var getResizeCallback = function(fieldName) {
					return function(event) {
						if (!event) event = window.event;
						__self.startResizeColumn(fieldName, event);return true;
					};
				};

				var cVoid = document.createElement('th');
				cVoid.colspan = "1";
				cVoid.className = 'void';


				var nameColumn = usedColumns['name'];
				var cName = document.createElement('th');
				cName.className = 'column';
				cName.setAttribute("id", 'h_' + __self.control.id + '_name');
				cName.setAttribute("name", 'name');
				cName.name = 'name';
				cName.style.width = nameColumn['params'][0];

				var header = document.createElement('div');
				header.style.width = nameColumn['params'][0];
				header.innerHTML = getLabel('js-smc-name-column');
				header.title = getLabel('js-smc-name-column');

				var resizer = document.createElement('span');
				resizer.onmousedown = getResizeCallback('name');

				header.appendChild(resizer);

				cName.appendChild(header);

				if (!flatMode) {
					hRow.appendChild(cVoid);
					filterRow.appendChild(cVoid.cloneNode(true));
					filterRow.firstChild.className = 'filter-void';
				}

				// name header
				hRow.appendChild(cName);

				if (__self.control.enableEdit) {
					var cManage = document.createElement('th');
					cManage.className   = 'column';
					cManage.style.width = '140px';
					var cManageLabel = document.createElement('div');
					cManageLabel.style.width = '132px';
					cManage.appendChild(cManageLabel);
					hRow.appendChild(cManage);
				}

				// name filter
				if (__self.control.enableEdit) {
					var cNameFltr = document.createElement('th');
					cNameFltr.setAttribute("id", 'f_' + __self.control.id + '_name');
					cNameFltr.style.width = nameColumn['params'][0];
					__self.control.onDrawFieldFilter('name', cNameFltr, nameColumn.params);
					filterRow.appendChild(cNameFltr);
				}

				var cManageFltr = document.createElement('th');
				cManageFltr.id  = 'f_' + __self.control.id + '_manage';
				cManageFltr.style.width = '140px';
				filterRow.appendChild(cManageFltr);


				var colSpan = 2;

				for (var name in usedColumns) {
					var column = columnsTable[name];
					var params = usedColumns[name]['params'];

					if (params[1] == 'static') {
						column = usedColumns[name];
						column.title = getLabel('js-smc-'+name);
					}

					if (!column) continue;

					colSpan++;
					var col = document.createElement('th');
					col.className = 'column';
					col.setAttribute("id", 'h_' + __self.control.id + '_' + name);
					col.setAttribute("name", name);
					col.style.width = usedColumns[name]['params'][0];
					col.name = name;

					header = document.createElement('div');
					header.style.width = usedColumns[name]['params'][0];
					header.title = column.title;
					header.innerHTML = column.title;

					col.appendChild(header);

					resizer = document.createElement('span');
					resizer.onmousedown = getResizeCallback(name);

					header.appendChild(resizer);

					hRow.appendChild(col);

					// column filter
					var colFltr = document.createElement('th');
					colFltr.setAttribute("id", 'f_' + __self.control.id + '_' + name);
					colFltr.style.width = usedColumns[name]['params'][0];
					__self.control.onDrawFieldFilter(column, colFltr, usedColumns[name].params);
					filterRow.appendChild(colFltr);

					++usedColumnsCount;
				}


				var autoCol = document.createElement('th');
				autoCol.style.width = 'auto';

				filterRow.appendChild(autoCol.cloneNode(true));

				if (!__self.control.objectTypesMode && __self.control.needColumnsMenu) {
					var invokeBtn = document.createElement('div');
					invokeBtn.className = 'invoke-btn';
					autoCol.appendChild(invokeBtn);
					jQuery(invokeBtn).bind("click", function(event) {
						jQuery.cmenu.lockHiding = true;
						jQuery.cmenu.show(jQuery.cmenu.getMenu(__self.control.columnsMenu), __self.control.initContainer.offsetParent, event);
						return;
					});

					jQuery(invokeBtn).bind('mousedown', function(event) {
						if(event.altKey) {
							jQuery.cmenu.lockHiding = true;
							jQuery.cmenu.show(jQuery.cmenu.getMenu(__self.control.columnsMenu), __self.control.initContainer.offsetParent, event);
							return;
						}
					});


					jQuery(invokeBtn).bind('mouseout', function () {
						jQuery.cmenu.lockHiding = false;
					});
				}

				hRow.appendChild(autoCol);
			}

			tHead.appendChild(hRow);
			if (!__self.control.objectTypesMode) tHead.appendChild(filterRow);
			__toggleFilterRow();
		}

		jQuery(tHead).bind("click", function(event) {
			var el = event.target;
			if (el.parentNode.tagName.toUpperCase() != 'TH') return;

			TableItem.orderByColumn(el.parentNode.name, __self.control, el);
		});

		var tBody = document.createElement('tbody');
		tBody.className = "table-container";

		// pageing
		var pagesRow = document.createElement('tr');
		pagesRow.style.display = 'none';
		{
			pagesBar = document.createElement('td');
			pagesBar.id = 'pb_' + __self.control.id + '_' + __self.id;
			pagesBar.className = 'pages-bar';
			if (!flatMode) colSpan++;
			pagesBar.colSpan = colSpan;

			pagesRow.appendChild(pagesBar);

			tBody.appendChild(pagesRow);
		}

		__self.element = __self.control.container.appendChild(tHead);
		__self.childsContainer  = __self.control.container.appendChild(tBody);
		__self.control.initContainer = __self.control.container;
		__self.control.container = __self.childsContainer;

		//Добавить ссылку на экспорт CSV в том случае, если мы имеем дело с таблицами
		if(flatMode && !objectTypesMode) {
			var exportCallback = function () {
				var link = __self.getExportLink();
				window.location = link;
			};

			var importCallback = function () {
				var link = __self.getImportLink();
				__self.callCsvImport(link);
				return false;
			};

			__self.showCsvButtons(exportCallback, importCallback);
		}

		__self.expand();
	};

	this.showCsvButtons = function (exportCallback, importCallback) {
		if(this.control.disableCSVButtons) return;
		__self.hideCsvButtons();

		var csvButtons = document.createElement('div');
		csvButtons.id = 'csv-buttons';

		var aExportCsv = document.createElement('a');
		aExportCsv.appendChild(document.createTextNode(getLabel('js-csv-export')));
		aExportCsv.href = "#";
		aExportCsv.className = "csvLink csvExport";

		aExportCsv.onclick = exportCallback;
		csvButtons.appendChild(aExportCsv);

		if(!this.control.hideCsvImportButton) {
			var aImportCsv = document.createElement('a');
			aImportCsv.appendChild(document.createTextNode(getLabel('js-csv-import')));
			aImportCsv.href = "#";
			aImportCsv.className = "csvLink csvImport";

			aImportCsv.onclick = importCallback;
			csvButtons.appendChild(aImportCsv);
		}

		__self.control.container.parentNode.parentNode.appendChild(csvButtons);
		__self.csvButtons = csvButtons;
	};

	this.hideCsvButtons = function () {
		if(__self.csvButtons) {
			jQuery(__self.csvButtons).remove();
			__self.csvButtons = false;
		}

		jQuery('#csv-buttons').remove();
	};

	this.getExportLink = function (elementId) {
		var usedColumns = __getUsedColumns();
		var filterQueryString = this.control.getCurrentFilter().getQueryString();
		
		var path = document.location['pathname'];
		try { // Обработка вариантов, в которых document.location не совпадает с заданным адресом
			var path = __self.control.dataSet.getPathModuleMethod();
		} catch( err ) {}
		
		var link = path + filterQueryString + "&xmlMode=force&export=csv";
		for(var i in usedColumns) {
			var columnName = usedColumns[i]['name'];
			if(columnName != "name") {
				link += "&used-fields[]=" + columnName;
			}
		}
		if(elementId) {
			link += "&rel[]=" + elementId;
		}
		return link;
	};

	this.getImportLink = function (elementId) {
		var filterQueryString = this.control.getCurrentFilter().getQueryString();
		var link = document.location['pathname'];
		link += filterQueryString + "&xmlMode=force&import=csv";
		if(elementId) {
			link += "&rel[]=" + elementId;
		}
		return link;
	};

	this.callCsvImport = function (link) {
		var html = "<form method='post' id='import-csv-form' enctype='multipart/form-data' action='" + link + "' mime-type='text/html'";
		html += " target='import-iframe'>";
		html += "<label for='csv-file'>" + getLabel('js-csv-import-question') + "</label> ";
		html += "<input type='file' name='csv-file' id='csv-file' size='28' />" + "<br />";
		html += "<input type='hidden' name='csrf' value='' />";
		html += "<label for='ignore-id'>" + getLabel('js-csv-import-ignore-id') + "</label><input type='checkbox' name='ignore-id' id='ignore-id' />";
		html += "</form>";
		html += "<iframe name='import-iframe' id='import-iframe' src='' style='display: none;'></iframe>";


		window.csvQuickImportCallback = function () {
			closeDialog();
			document.location.href = document.location.href;
		};

		openDialog({
			text       : html,
			OKText     : getLabel('js-csv-import-button'),
			OKCallback : function() {
				jQuery("#import-csv-form input[name='csrf']").val(csrfProtection.getToken());
				jQuery("#import-csv-form").submit();
				return false;
			}
		});
	};

	/**
	* Разворачивает элемент, если он находится под курсором
	* Используется в режиме drag&drop
	* @access private
	*/
	var __autoExpandSelf = function() {
		if (AutoexpandAllowed && __self === Control.HandleItem) {
			__self.expand();
		}
	};

	this.draw = function() {
		if (_oParent) {
			if (!ForceDraw) __draw(Parent.childsContainer);
		} else {
			if (!ForceDraw) __drawRoot();
		}
	};

	/**
	* Добавляет дочерний элемент последним в списке
	* @access public
	* @param {Array} _oChildData - массив с информацией о новом элементе
	* @return {Object} - новый элемент
	*/
	this.appendChild = function(_oChildData) {
		return new TableItem(this.control, __self, _oChildData);
	};

	/**
	* Добавляет дочерний элемент после указанного элемента
	* @access public
	* @param {Array} _oChildData - массив с информацией о новом элементе
	* @param {Object} oItem - элемент, после которого добавится новый
	* @return {Object} - новый элемент
	*/
	this.appendAfter = function(_oChildData, oItem) {
		return new TableItem(this.control, __self, _oChildData, oItem, 'after');
	};

	/**
	* Добавляет дочерний элемент перед указанным элементом
	* @access public
	* @param {Array} _oChildData - массив с информацией о новом элементе
	* @param {Object} oItem - элемент, перед которым добавится новый
	* @return {Object} - новый элемент
	*/
	this.appendBefore = function(_oChildData, oItem) {
		return new TableItem(this.control, __self, _oChildData, oItem, 'before');
	};

	/**
	* Добавляет дочерний элемент в начало списка
	* @access public
	* @param {Array} _oChildData - массив с информацией о новом элементе
	* @return {Object} - новый элемент
	*/
	this.appendFirst = function(_oChildData) {
		if (!this.childsContainer.childNodes.length) {
			return this.appendChild(_oChildData);
		} else if(typeof(this.childsContainer.childNodes[1].rel) != 'undefined') {
			return this.appendBefore(_oChildData, this.control.getItem(this.childsContainer.childNodes[1].rel));
		} else return false;
	};


	/**
	* Возвращает предыдущего соседа элемента
	* @access public
	* @return {Object} предыдущий сосед, либо null
	*/
	this.getPreviousSibling = function() {
		var prevEl = this.element.previousSibling;
		if (prevEl && prevEl.rel) {
			return this.control.getItem(prevEl.rel);
		}
		return null;
	};

	/**
	* Возвращает последующего соседа элемента
	* @access public
	* @return {Object} последующий сосед, либо null
	*/
	this.getNextSibling = function() {
		var prevEl = this.element.nextSibling;
		if (prevEl && prevEl.rel) {
			return this.control.getItem(prevEl.rel);
		}
		return null;
	};

	/**
	* Удаляет элемент из DOM
	* @access public
	*/
	this.clear = function() {
		if (this.isRoot) {
			var parent = this.element.parentNode;
			while(parent.firstChild) {
				parent.removeChild(parent.firstChild);
			}
		} else if (this.element.parentNode) {
			this.element.parentNode.removeChild(this.element.nextSibling);
			this.element.parentNode.removeChild(this.element);
		}
	};

	/**
	* Проверяет, является ли текущий элемент потомком указанного (на всю глубину)
	* @access public
	* @param {Object} oItem - элемент
	* @return {Boolean} true, если является
	*/
	this.checkIsChild = function(oItem) {
		var parent = this.parent;
		while (parent) {
			if (oItem === parent) return true;
			parent = parent.parent;
		}
		return false;
	};


	/**
	* Возвращает координаты DOM-представления элемента
	* Метод является обязательным, вызывается Control'ом.
	* Служит для определения элемента под курсором мыши
	* @access public
	*/
	this.recalcPosition = function() {
		if (__self.isRoot) {
			return false;
		}
		try {
			var parent = this.parent;
			while (parent) {
				if (!parent.isExpanded) {
					this.position = false;
					return false;
				}
				parent = parent.parent;
			}

			var container = this.control.getRoot().childsContainer;
			var pos = jQuery(labelCtrl).position();

			this.position = {
				'left' : pos.left,
				'top' :  pos.top,
				'right' : pos.left + container.offsetWidth,
				'bottom' : pos.top + jQuery(labelCtrl).height()
			};

			return this.position;
		} catch(e) {
			this.position = false;
			return false;
		}
	};

	/**
	* Выставляет статус загружены/не загружены дети элемента
	* Метод является обязательным, вызывается Control'ом!
	* @param {Boolean} loaded - статус
	* @access public
	*/
	this.setLoaded = function(loaded) {
		this.loaded = loaded;
		var ico = loaded ? IconsPath + 'collapse.png' : IconsPath + 'expand.png';
		if (toggleCtrl) toggleCtrl.setAttribute('src', IconsPath + 'collapse.png');
	};


	var __renderProperty = function(propEl) {
		if (!propEl) return "";
		if (!propEl['type']) return "";

		var val = "", tmp, src;

		var _v = function(el, def) {
			return el.value._value || (el.value || (def || ''));
		};

		switch(propEl['type']) {
			case "int":
			case "float":
			case "price":
			case "counter":
				val = _v(propEl, 0);
			break;
			case "tags" :
			case "string" :
				val = _v(propEl);
				if (propEl.restriction == 'email') {
					val = "<a href='mailto:" + val + "' title='" + val + "' class='link'>" + val + "</a>";
					break;
				}
				if(typeof val == "array") {
					tmp = val.join(", ");
					val = tmp;
				}
				if(/http:\/\//.test(val)) {
					val = "<a href=\"" + val + "\" title=\"" + val + "\" class=\"link\">" + val + "</a>";
				} else {
					val = '<span title="' + val + '">' + val + '</span>';
				}
			break;

			case "text": {
				val = _v(propEl);
				if(val) {
					val = '<span title="' + val + '">' + val.substring(0, 255) + '</span>';
				}
				break;
			}

			case "boolean":
				val = _v(propEl, false) ?  '<img alt="" style="width:13px;height:13px;" src="/images/cms/admin/mac/tree/checked.png" />': '';
			break;
			case "img_file": {
				val = propEl.value;
				if (val) {
					src = val._value;
					var path = src.substring(0, src.lastIndexOf('.'));
					var filename = "";
					if(typeof src == "string") {
						tmp = src.split(/\//);
						filename = tmp[tmp.length - 1];
					}
					if (val.is_broken == '1') {
						val = '<span title="404" style="color:red;font-weight: bold;cursor: pointer;">?</span>';
						val += '&nbsp;&nbsp;<span style="text-decoration: line-through;" title=\"' + src + '\">' + filename + "</span>";
					} else {
						var ext = val.ext;
						var thumbSrc = "/autothumbs.php?img=" + path + '_sl_180_120.' + ext;
						val = '<img alt="" style="width:13px;height:13px;cursor: pointer;" src="/images/cms/image.png" onmouseover="TableItem.showPreviewImage(event, \'' + thumbSrc + '\')" />';
						val += '&nbsp;&nbsp;<span title=\"' + src + '\">' + filename + "</span>";
					}
				}
				break;
			}
			case "video_file":
			case "swf_file":
			case "file":
			{
				val = propEl.value;
				if (val) {
					src = val._value;
					if (val.is_broken == '1') {
						val = src ? ('<span style="text-decoration: line-through;" title="' + src + '">' + src + '</span>') : '';
					} else {
						val = src ? ('<span title="' + src + '">' + src + '</span>') : '';
					}
				}
				break;
			}
			case "relation":
				var o = _v(propEl, null);
				if (o) {
					if (o.item[0]) {
						for (var i = 0; i < o.item.length; i++) {
							val += o.item[i].name;
							if (i < o.item.length - 1) val += ', ';
						}
						val = '<span title="' + val + '">' + val + '</span>';
					} else {
						val = o.item.name;
						var guid = 'relation-value';
						if (o.item.guid != undefined) guid = o.item.guid;
						val = '<span title="' + val + '" class="c-' + guid + '">' + val + '</span>';
					}
				}
			break;
			case "date":
				val = propEl['value']['formatted-date'];
			break;
			case "wysiwyg" :
				val = _v(propEl, null);
				if (val['#text']) {
					val = val['#text'].join();
				}
				val = val.replace(/<\/?[^>]+>/g, '');
			break;
			default:
				return "";
			break;
		}

		return val;
	};

	/**
	* Получает значение свойства c именем fieldName
	* @param {String} fieldName - имя свойства
	* @return {Mixed} значение свойства, либо false, в случае неудачи
	*/
	this.getValue = function(fieldName) {
		var usedColumns = __getUsedColumns();

		var col = usedColumns[fieldName];

		if(fieldName == 'name') {
			return this.control.onGetValueCallback(this.name, fieldName, this);
		}

		if(col) {
			if (col.params[1] == 'static') return this.control.onGetValueCallback(col, fieldName, this);
		}


		if (!Data['properties']) return '';
		if (!Data['properties']['group']) return '';

		var Groups = typeof(Data['properties']['group'][0]) != 'undefined' ? Data['properties']['group'] : [Data['properties']['group']];

		for (var i = 0; i < Groups.length; i++) {
			if (!Groups[i]['property']) continue;
			var Props = typeof(Groups[i]['property'][0]) != 'undefined' ? Groups[i]['property'] : [Groups[i]['property']];

			for (var j = 0; j < Props.length; j++) {
				if (Props[j]['name'] == fieldName) {
					Props[j] = this.control.onGetValueCallback(Props[j], fieldName, this);
					return __renderProperty(Props[j]);
				}
			}

		}

		return '';
	};

	/**
		* Получить данные, которые отдал DataSet
		* @return Array объект со свойствами
	*/
	this.getData = function () {
		return Data;
	};

	/**
	* Выставляет pageing у item'а и заполняет pagesBar страницами
	* @param {Object} pageing
	* @return {Boolean} false, в случае неудачи
	*/
	this.setPageing = function(pageing) {
		if (!pageing || !pagesBar) return false;
		this.pageing = pageing;
		pagesBar.parentNode.style.display = 'none';
		pagesBar.innerHTML = '';
		pagesBar.style.textAlign = 'left';

		if (this.isRoot) {
			this.setPageingLimits(pageing);
		}
		if (pageing.total == 0) {
			pagesBar.parentNode.style.display = '';
			var emptyResult = document.createElement('span');
			emptyResult.className = 'empty-result';
			if (this.isRoot) {
				pagesBar.style.textAlign = 'center';
			}

			emptyResult.appendChild(document.createTextNode(getLabel('js-smc-empty-result')));
			pagesBar.appendChild(emptyResult);
		}
		if (pageing.total > pageing.limit || pageing.offset > 0) {
			var pagesLabel = document.createElement('span');
			pagesLabel.appendChild(document.createTextNode(getLabel('js-pages-label')));
			pagesLabel.className = 'pagesLabel';
			pagesBar.appendChild(pagesLabel);
			pagesBar.parentNode.style.display = '';
			var pages = Math.ceil(pageing.total / pageing.limit);
			var curr_page = Math.ceil(pageing.offset / pageing.limit);

			var getCallback = function(page) {
				return function() {
					__self.filter.setPage(page);
					__self.applyFilter(__self.filter, true);
					return false;
				};
			};

			var skippedPrevious = false;
			var nextPage = false;
			for (var i = 0; i < pages; i++) {
				if(i != 0 && i != (Math.ceil(pageing.total / pageing.limit) - 1)) {
					if(Math.abs(i - curr_page) > 15) {
						if(!skippedPrevious) {
							nextPage = document.createElement('a');
							nextPage.href = "#";
							if (curr_page == i) {
								nextPage.className = 'current';
							}

							nextPage.innerHTML = "&#8230;";
							nextPage.className = 'current';
							nextPage.onclick = function () {return false;};

							pagesBar.appendChild(nextPage);
						}
						skippedPrevious = true;
						continue;
					}
				}
				skippedPrevious = false;

				nextPage = document.createElement('a');
				nextPage.href = "#";
				if (curr_page == i) {
					nextPage.className = 'current';
				}

				nextPage.innerHTML = i + 1;
				nextPage.onclick = getCallback(i);

				pagesBar.appendChild(nextPage);
			}

		}
	};

	/**
	* Разворачивает элемент
	* Метод является обязательным, вызывается Control'ом!
	* @access public
	*/
	this.expand = function() {
		if (!this.hasChilds) return false;

		this.isExpanded = true;
		if (!this.loaded) {
			this.loaded = true;
			this.control.load(this.filter);
			if (toggleCtrl) toggleCtrl.setAttribute('src', IconsPath + 'loading.gif');
		} else {
			if (toggleCtrl) toggleCtrl.setAttribute('src', IconsPath + 'collapse.png');
		}

		this.childsContainer.parentNode.parentNode.parentNode.style.display = '';
		if (this.loaded) Control.recalcItemsPosition();

		// save settings
		this.control.saveItemState(this.id);

	};

	/**
	* Сворачивает элемент
	* Метод является обязательным, вызывается Control'ом!
	* @access public
	*/
	this.collapse = function() {
		if (!this.hasChilds) return false;

		this.isExpanded = false;
		if (toggleCtrl) toggleCtrl.setAttribute('src', IconsPath + 'expand.png');
		this.childsContainer.parentNode.parentNode.parentNode.style.display = 'none';
		Control.recalcItemsPosition();
		// save settings
		this.control.saveItemState(this.id);
	};

	/**
	* Сворачивает/разворачивает элемент в зависимости от текущего состояния
	* Метод является обязательным, вызывается Control'ом!
	* @access public
	*/
	this.toggle = function() {
		if (this.hasChilds) {
			this.isExpanded ? this.collapse() : this.expand();
		}
	};

	/**
	* Обновляет элемент, используя новые данные о нем
	* @param {Array} _oNewData - новые данные о элементе
	* Метод является обязательным, вызывается Control'ом!
	* @access public
	*/
	this.update = function(_oNewData) {

		if (_oNewData) {
			if (this.id != _oNewData.id) return false;

			Data = _oNewData;

			//change template id
			if (typeof(_oNewData['template-id']) !== 'undefined') this.templateId = _oNewData['template-id'];

			// change view link
			if (typeof(_oNewData['link']) !== 'undefined' && _oNewData['link'] != this.viewLink) {
				this.viewLink = _oNewData['link'];
				labelCtrl.setAttribute('href', this.viewLink);
			}

			// change childs
			if (typeof(_oNewData['childs']) !== 'undefined' && parseInt(_oNewData['childs']) !== CountChilds) {
				CountChilds = parseInt(_oNewData['childs']);
				this.hasChilds = CountChilds > 0 || id == 0;
				var tsrc = IconsPath + 'collapse-disabled.png';
				if (this.hasChilds) {
					tsrc = this.isExpanded ? IconsPath + 'collapse.png' : IconsPath + 'expand.png';
				}
				if (!this.hasChilds) {
					this.isExpanded = this.loaded = false;
				}
				if (toggleCtrl) toggleCtrl.setAttribute('src', tsrc);
			}

			// change active
			var newActive = typeof(_oNewData['is-active']) !== 'undefined' ? parseInt(_oNewData['is-active']) : 0;
			if (newActive !== this.isActive) {
				this.isActive = newActive;
				this.isActive = isObjectActivated(this, this.isActive);
				if (!this.isActive && !__self.control.objectTypesMode) {
					if(!__self.control.flatMode) {
						jQuery(itemIcon).fadeTo(300, 0.5);
						jQuery(toggleCtrl).fadeTo(300, 0.5);
					}
					jQuery(labelText).fadeTo(300, 0.5);
				} else {
					if(!__self.control.flatMode && !__self.control.objectTypesMode) {
						jQuery(itemIcon).fadeTo(300, 1);
						jQuery(toggleCtrl).fadeTo(300, 1);
					}
					jQuery(labelText).fadeTo(300, 1);
				}

				if(activeColumn) {
					jQuery('div', activeColumn).html(  __renderProperty( { 'type':'boolean', 'value': this.isActive }) );
				}
			}

			if (typeof(_oNewData['name']) !== 'undefined' && _oNewData['name'] != this.name) {
				this.name = _oNewData['name'].length ? _oNewData['name'] : "";
				labelText.innerHTML = this.name.length ? this.name : getLabel('js-smc-noname-page');
			}

		}
	};



	/**
	* Устанавливает фильтр для детей элемента и обновляет содержимое, если потребуется
	* @access public
	* @param _Filter
	* @param ignoreHierarchy
	*/
	this.applyFilter = function(_Filter, ignoreHierarchy) {
		if(_Filter instanceof Object) {
			this.filter = _Filter;
		} else {
			this.filter.clear();
		}
		if(!ignoreHierarchy) this.filter.setParentElements(id);
		if (this.loaded) {
			this.control.removeItem(id, true);
			this.loaded = false;
			if(this.isExpanded) this.expand();
		}

		if(this.isRoot) {
			if(this.control.getCurrentFilter().empty() && !this.ignoreEmptyFilter && !(this.control.flatMode && !this.control.objectTypesMode)) {
			} else {
				var exportCallback = function () {
					var link = __self.getExportLink();

					if(__self.control.contentType == 'pages') {
						link += "&force-hierarchy=1";
					}
					window.location = link;
				};

				var importCallback = function () {
					var link = __self.getImportLink();
					if(__self.control.contentType == 'pages') {
						link += "&force-hierarchy=1";
					}
					link += "&rnd=" + Math.round(Math.random() * 1000 % 1000);
					__self.callCsvImport(link);
					return false;
				};

				if(this.control.contentType != 'objectTypes') {
					__self.showCsvButtons(exportCallback, importCallback);
				}
			}
		}
	};

	this.setSelected = function(_selected) {
		if(_selected) {
			if(!oldClassName) {
				oldClassName = 'ti';//labelCtrl.className;
				labelCtrl.className = 'ti selected-highlight';
			}
			selected = true;
		} else {
			if(oldClassName) {
				labelCtrl.className = oldClassName;
				oldClassName        = null;
			}
			selected = false;
		}
	};

	this.getSelected = function() {
		return selected;
	};
	
	this.setPageingLimits = function(pageing) {
		var getCallback = function(limit) {
			return function() {
				__self.filter.setLimit(limit);
				__self.applyFilter(__self.filter, true);
				return false;
			};
		};
		root = document.getElementById('per_page_limit');
		root.innerHTML = '';
		for (i in __self.pageLimits) {
			var link = document.createElement('a');
			link.className = 'per_page_limit';
			link.rel = '0';
			link.innerHTML = __self.pageLimits[i];
			link.onclick = getCallback(__self.pageLimits[i]);
			if (__self.pageLimits[i] == pageing.limit) {
				link.className += ' current';
			}
			root.appendChild(link);
		}
	};

	// {main}
	__constructor();
};

// static props

TableItem.minColumnWidth = 110;
TableItem.maxColumnWidth = 800;



TableItem.orderByColumn = function(_FieldName, _Control, _ColumnEl) {
	if (!_Control || !_FieldName || !_ColumnEl) return false;

	var direction = "asc";
	if (_Control.orderColumn === _ColumnEl) {
		direction = _ColumnEl.className == "asc" ? "desc" : "asc";
		_ColumnEl.className = direction;
	} else {
		if (_Control.orderColumn) {
			_Control.orderColumn.className = '';
		}
		_Control.orderColumn = _ColumnEl;
		_Control.orderColumn.className = direction;
	}

	var defFilter = _Control.dataSet.getDefaultFilter();
	defFilter.setOrder(_FieldName, direction);
	_Control.dataSet.setDefaultFilter(defFilter);

	_Control.applyFilter();
};


TableItem.showPreviewImage = function(event, src) {
	if (!event || !src) return;
	var el = (!event.target) ? event.toElement : event.target;
	var x = (!event.pageX) ? event.clientX : event.pageX;
	var y = (!event.pageY) ? event.clientY : event.pageY;

	var img = document.createElement('img');
	img.setAttribute('src', src);
	img.src = src;
	img.className = 'img-fieled-preview';
	img.style.position = 'absolute';
	img.style.width  = '180px';
	img.style.height = '120px';
	img.style.top    = y + 10 + 'px';
	img.style.left   = x + 10 + 'px';
	img.style.border = '1px solid #666';

	document.body.appendChild(img);

	el.onmouseout = function() {
		img.parentNode.removeChild(img);
	};

};
