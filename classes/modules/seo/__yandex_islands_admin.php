<?php

	abstract class __yandex_islands_admin extends baseModuleAdmin {

		public function island_field_add() {
			$settingsGroup = $this->islandsGetSettingsType((int) getRequest('currentIslandType'));

			if (!$settingsGroup) {
				return array();
			}

			$object_id = (int) getRequest('param0');
			$object = umiObjectsCollection::getInstance()->getObject($object_id);

			$settings = $this->islandGetFields($object);
			$newFieldId = $this->islandGetNewFieldId($settings[$settingsGroup]);

			$data = getRequest('data');

			$order = 10;
			if (count($settings[$settingsGroup]) > 0) {
				$order = max(array_keys($settings[$settingsGroup])) + 10;
			}

			$settings[$settingsGroup][$order] = array(
				'fieldId' => $newFieldId,
				'order' => $order,
				'name' => $data['name'],
				'title' => $data['title'],
				'visible' => isset($data['is_visible']) ? 1 : 0,
				'guide-id' => isset($data['guide_id']) ? $data['guide_id'] : 0,
				'field-type-id' => $data['field_type_id'],
				'field-restriction-id' => $data['restriction_id']
			);

			$this->islandSetFields($object, $settings);

			$this->setData(array());
			return $this->doData();
		}

		public function island_field_delete() {
			$fieldId = getRequest('param0');
			$objectId = getRequest('param1');
			$settingsGroup = $this->islandsGetSettingsType((int) getRequest('param2'));

			$object = umiObjectsCollection::getInstance()->getObject($objectId);
			$settings = $this->islandGetFields($object);
			$map = $this->islandGetFieldsMap($settings[$settingsGroup]);

			$elementKey = array_search($fieldId, $map);
			unset($settings[$settingsGroup][$elementKey]);

			$counter = 10;
			$result = array();
			foreach($settings[$settingsGroup] as $field) {
				$field['order'] = $counter;
				$result[$counter] = $field;
				$counter += 10;
			}

			$settings[$settingsGroup] = $result;

			$this->islandSetFields($object, $settings);

			$this->setData(array());
			return $this->doData();
		}

		public function island_field_edit() {
			$fieldId = (int) getRequest('param0');
			$objectId = (int) getRequest('param1');
			$settingsGroup = $this->islandsGetSettingsType((int) getRequest('param2'));

			$object = umiObjectsCollection::getInstance()->getObject($objectId);
			$settings = $this->islandGetFields($object);
			$map = $this->islandGetFieldsMap($settings[$settingsGroup]);

			$elementKey = array_search($fieldId, $map);

			$data = getRequest('data');

			$element = $settings[$settingsGroup][$elementKey];
			$settings[$settingsGroup][$elementKey] = array(
				'fieldId' => $fieldId,
				'order' => $elementKey,
				'name' => $data['name'],
				'title' => $data['title'],
				'visible' => isset($data['is_visible']) ? 1 : 0,
				'guide-id' => isset($data['guide_id']) ? $data['guide_id'] : 0,
				'field-type-id' => $data['field_type_id'],
				'field-restriction-id' => $data['restriction_id']
			);

			if (isset($element['baseFieldId'])) {
				$settings[$settingsGroup][$elementKey]['baseFieldId'] = $element['baseFieldId'];
			}

			$this->islandSetFields($object, $settings);

			$this->setData(array());
			return $this->doData();
		}

		public function island_move_field_after() {
			$field_id = (int) getRequest('param0');
			$before_field_id = (int) getRequest('param1');
			$is_last = (string) getRequest('param2');
			$object_id = (int) getRequest('param3');
			$settingsGroup = $this->islandsGetSettingsType((int) getRequest('param4'));

			$object = umiObjectsCollection::getInstance()->getObject($object_id);

			$settings = $this->islandGetFields($object);
			$map = $this->islandGetFieldsMap($settings[$settingsGroup]);

			$currentElementKey = array_search($field_id, $map);
			$currentElement = $settings[$settingsGroup][$currentElementKey];
			unset($settings[$settingsGroup][$currentElementKey]);

			$result = array();
			$counter = 10;
			if ($is_last == 'false') {
				$beforeElementKey = array_search($before_field_id, $map);
				foreach($settings[$settingsGroup] as $elementKey=>$field) {
					if ($elementKey == $beforeElementKey) {
						$currentElement['order'] = $counter;
						$result[$counter] = $currentElement;
						$counter += 10;
					}
					$field['order'] = $counter;
					$result[$counter] = $field;
					$counter += 10;
				}
			} else {
				$settings[$settingsGroup][max(array_keys($settings)) + 10] = $currentElement;
				foreach($settings[$settingsGroup] as $field) {
					$field['order'] = $counter;
					$result[$counter] = $field;
					$counter += 10;
				}
			}

			$settings[$settingsGroup] = $result;
			$this->islandSetFields($object, $settings);

			$res = "";
			$this->flush($res);
		}


		public function islands() {
			$this->setDataType("list");
			$this->setActionType("view");

			$limit = getRequest('per_page_limit');
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->guid('seo-yandex-island');
			$sel->limit($offset, $limit);

			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");

			$this->setData($data, $sel->length);
			return $this->doData();
		}

		public function island_add() {
			$objectTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('seo-yandex-island');
			$objectId = umiObjectsCollection::getInstance()->addObject("i18n::object-new-seo-island", $objectTypeId);
			$this->redirect($this->pre_lang . "/admin/seo/island_edit/" . $objectId . "/");
		}

		protected function userFieldsCallback($userField) {
			if (isset($userField['baseFieldId'])) {
				return $userField['baseFieldId'];
			}
			return false;
		}

		public function island_edit() {
			$object = $this->expectObject("param0", true);
			$mode = (string) getRequest('param1');
			$objectId = $object->getId();
			$this->setDataType("form");
			$this->setActionType("modify");

			$settings = $this->islandGetFields($object);

			$resource = $object->getValue('island_tree_link');

			if (!is_null($resource) && is_array($resource) && count($resource) > 0) {
				$element = $resource[0];
				if ($element instanceof umiHierarchyElement) {
					$map = array_map(
						array($this, "userFieldsCallback"),
						$settings['relation']
					);

					$dominantTypeId = umiHierarchy::getInstance()->getDominantTypeId($element->getId(), 100);
					$dominantType = umiObjectTypesCollection::getInstance()->getType($dominantTypeId);

					if ($dominantType) {
						$groups = $dominantType->getFieldsGroupsList();
						$baseTypeFields = array();
						foreach($groups as $group) {
							if (!$group->getIsVisible() || !$group->getIsActive()) {
								continue;
							}
							$fields = $group->getFields();
							foreach($fields as $field) {
								if (!$field->getIsVisible() || !$field->getIsInFilter()) {
									continue;
								}
								$baseTypeFields[$field->getId()] = $field;
							}
						}

						foreach($map as $key=>$fieldId) {
							if ($fieldId == false) {
								continue;
							}
							if (!in_array($fieldId, array_keys($baseTypeFields))) {
								unset($settings['relation'][$key]);
							} else {
								unset($baseTypeFields[$fieldId]);
							}
						}

						if (count($baseTypeFields) > 0) {
							$isCatalogFilter = $element->getModule() == 'catalog' && $element->getMethod() == 'category';
							$order = 0;
							if (count($settings['relation']) > 0) {
								$order = max(array_keys($settings['relation']));
							}
							foreach($baseTypeFields as $field) {
								$order += 10;
								$newFieldId = $this->islandGetNewFieldId($settings['relation']);
								$settings['relation'][$order] = array(
									'fieldId' => $newFieldId,
									'order' => $order,
									'name' => $isCatalogFilter ? 'fields_filter[' . $field->getName() . ']' : $field->getName(),
									'title' => $field->getTitle(),
									'visible' => $field->getIsVisible(),
									'guide-id' => $field->getGuideId(),
									'field-type-id' =>  $field->getFieldTypeId(),
									'field-restriction-id' => $field->getRestrictionId(),
									'baseFieldId' => $field->getId()

								);
							}
							$this->islandSetFields($object, $settings);
						}
					}
				}
			} elseif (count($settings['relation']) > 0) {
				$map = array_map(
					array($this, "userFieldsCallback"),
					$settings['relation']
				);
				foreach($map as $order=>$fieldId) {
					if ($fieldId == false) {
						continue;
					}
					unset($settings['relation'][$order]);
				}
				$this->islandSetFields($object, $settings);
			}

			$inputData = Array(
				"object"	=> $object
			);

			if($mode == "do") {
				$object = $this->saveEditedObjectData($inputData);
				$this->chooseRedirect();
			}

			$data = $this->prepareData($inputData, "object");

			foreach($settings as $groupName => $settingsGroup) {
				$resFields = array();
				foreach($settingsGroup as $field) {
					$resFields[] = array(
						'attribute:id' => $field['fieldId'],
						'attribute:name' => $field['name'],
						'attribute:title' => $field['title'],
						'attribute:visible' => $field['visible'],
						'attribute:guide-id' => $field['guide-id'],
						'attribute:field-type-id' => $field['field-type-id'],
						'attribute:field-type-title' => umiFieldTypesCollection::getInstance()->getFieldType($field['field-type-id'])->getName(),
					);
				}
				$data['object']['properties']['list:group'][] = array(
					'attribute:name' => 'filter_' . $groupName,
					'attribute:title' => getLabel('label-island-user-fields-group'),
					'list:field' => $resFields
				);
			}

			$this->setData($data);
			return $this->doData();

		}

		public function island_delete() {
			$objects = getRequest('element');
			if(!is_array($objects)) {
				$objects = Array($objects);
			}

			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);

				$params = Array(
					'object'		=> $object,
					'allowed-element-types' => Array('island')
				);

				$this->deleteObject($params);
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
		}

		public function islandSetFields(umiObject $object, $settings) {
			if (!$object instanceOf umiObject) {
				return false;
			}
			$object->setValue('island_user_fields', serialize($settings));
			$object->commit();
		}

		public function islandGetNewFieldId($settingsGroup) {
			$fieldIds = $this->islandGetFieldsMap($settingsGroup);
			if (count($fieldIds) > 0) {
				return max($fieldIds) + 10;
			}
			return 10;
		}

		protected function callbackField($field) {
			return $field['fieldId'];
		}

		public function islandGetFieldsMap($settingsGroup) {
			return array_map(
				array($this, "callbackField"),
				$settingsGroup);
		}

		public function islandGetFields(umiObject $object) {
			$elements = array(
				'relation' 	=> array(),
				'system'	=> array()
			);

			$settings = $object->getValue('island_user_fields');

			if (!$settings || is_null($settings)) {
				$object->setValue('island_user_fields', serialize($elements));
				$object->commit();
			} else {
				$settings = unserialize($settings);
				foreach($settings as $groupName => $settingsGroup) {
					if (count($settingsGroup) > 0) {
						foreach($settingsGroup as $field) {
							$elements[$groupName][$field['order']] = $field;
						}
						ksort($elements[$groupName], SORT_NUMERIC);
					}
				}
			}
			return $elements;
		}

		public function islandsGetSettingsType($islandTypeObjectId = false) {
			if (!$islandTypeObjectId) {
				return false;
			}

			$islandTypeObject = umiObjectsCollection::getInstance()->getObject($islandTypeObjectId);

			if (!$islandTypeObject) {
				return false;
			}

			return $islandTypeObject->getValue('island_island_type');
		}

		public function island_get() {
			$objectId = (int) getRequest('param0');
			$asFile = (int) getRequest('as_file');

			$object = umiObjectsCollection::getInstance()->getObject($objectId);

			$settings = $this->islandGetFields($object);
			$settingsGroup = $this->islandsGetSettingsType($object->getValue('island_type'));

			$dom = new DOMDocument('1.0', 'utf-8');
			$dom->formatOutput = true;

			$site = $dom->createElementNS('http://interactive-answers.webmaster.yandex.ru/schemas/site/0.0.1', 'site');
			$dom->appendChild($site);

			$dom->createAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:attr');

			$site->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance' ,'xsi:schemaLocation', 'http://interactive-answers.webmaster.yandex.ru/schemas/site-0.0.1.xsd');

			$title = $dom->createElement('title', $object->getValue('island_title'));
			$site->appendChild($title);

			$metrikaCounterId = $dom->createElement('metrikaCounterId', $object->getValue('island_metrika_id'));
			$site->appendChild($metrikaCounterId);

			$rootUrl = $dom->createElement('rootUrl', 'http://' . $object->getValue('island_domain_id') . '/');
			$site->appendChild($rootUrl);

			$protocol = $dom->createElement('protocol', 'HTTP');
			$site->appendChild($protocol);

			$description = $dom->createElement('description', $object->getValue('island_description'));
			$site->appendChild($description);

			if ($settingsGroup == 'relation') {
				$fixedLink = $object->getValue('island_tree_link');
				if (!is_null($fixedLink) && is_array($fixedLink) && count($fixedLink) > 0) {
					$fixedLink = umiHierarchy::getInstance()->getPathById($fixedLink[0]->getId());
				}
			} else {
				$fixedLink = $object->getValue('island_url_link');
			}

			$fixedLink = 'http://' . $object->getValue('island_domain_id') . '/' . ltrim($fixedLink, DIRECTORY_SEPARATOR);

			$resource = $dom->createElement('resource');
			$site->appendChild($resource);

			$fixed = $dom->createElement('fixed');
			$resource->appendChild($fixed);

			$name = $dom->createAttribute('name');
			$name->value = $fixedLink;
			$fixed->appendChild($name);

			$filters = $dom->createElement('filters');
			$site->appendChild($filters);

			if (!$settingsGroup) {
				return false;
			}

			foreach($settings[$settingsGroup] as $field) {
				if ($field['visible'] != 1) continue;

				$fieldType = umiFieldTypesCollection::getInstance()->getFieldType($field['field-type-id'])->getDataType();

				switch($fieldType) {
					case 'string': {
						$fieldTextNode = $dom->createElement('textBox');
						$filters->appendChild($fieldTextNode);

						$fieldType = $dom->createAttribute('type');
						$fieldType->value = 'NoFilling';
						$fieldTextNode->appendChild($fieldType);

						$fieldDescription = $dom->createElement('description');
						$fieldTextNode->appendChild($fieldDescription);

						$fieldCaption = $dom->createAttribute('caption');
						$fieldCaption->value = $field['title'];
						$fieldDescription->appendChild($fieldCaption);

						$fieldDescriptionParameter = $dom->createElement('setParameter');
						$fieldDescription->appendChild($fieldDescriptionParameter);

						$attributeName = $dom->createAttribute('name');
						$attributeName->value = $field['name'];
						$fieldDescriptionParameter->appendChild($attributeName);
						break;
					}
					case 'relation': {
						$fieldDropDown = $dom->createElement('dropDown');
						$filters->appendChild($fieldDropDown);

						$fieldDescription = $dom->createElement('description');
						$fieldDropDown->appendChild($fieldDescription);

						$fieldCaption = $dom->createAttribute('caption');
						$fieldCaption->value = $field['title'];
						$fieldDescription->appendChild($fieldCaption);

						$fieldDescriptionParameter = $dom->createElement('setParameter');
						$fieldDescription->appendChild($fieldDescriptionParameter);

						$attributeName = $dom->createAttribute('name');
						$attributeName->value = $field['name'];
						$fieldDescriptionParameter->appendChild($attributeName);

						$items = umiObjectsCollection::getInstance()->getGuidedItems($field['guide-id']);
						foreach($items as $itemKey=>$itemValue) {
							$fieldItem = $dom->createElement('dropDownValue');
							$fieldDropDown->appendChild($fieldItem);

							$attributeKey = $dom->createAttribute('key');
							$attributeKey->value = $itemKey;
							$fieldItem->appendChild($attributeKey);

							$attributeCaption = $dom->createAttribute('caption');
							$attributeCaption->value = $itemValue;
							$fieldItem->appendChild($attributeCaption);
						}
						break;
					}
					case 'date': {
						$fieldDate = $dom->createElement('date');
						$filters->appendChild($fieldDate);

						$fieldFormat = $dom->createAttribute('format');
						$fieldFormat->value = 'ddMMyyyy';
						$fieldDate->appendChild($fieldFormat);

						$fieldDescription = $dom->createElement('description');
						$fieldDate->appendChild($fieldDescription);

						$fieldCaption = $dom->createAttribute('caption');
						$fieldCaption->value = $field['title'];
						$fieldDescription->appendChild($fieldCaption);

						$fieldDescriptionParameter = $dom->createElement('setParameter');
						$fieldDescription->appendChild($fieldDescriptionParameter);

						$attributeName = $dom->createAttribute('name');
						$attributeName->value = $field['name'];
						$fieldDescriptionParameter->appendChild($attributeName);

						break;
					}
					case 'int':
					case 'float':
					case 'price': {
						$fieldRangeFilter = $dom->createElement('rangeFilter');
						$filters->appendChild($fieldRangeFilter);

						$fieldMin = $dom->createAttribute('min');
						$fieldMin->value = '1';
						$fieldRangeFilter->appendChild($fieldMin);

						$fieldMax = $dom->createAttribute('max');
						$fieldMax->value = '10000000';
						$fieldRangeFilter->appendChild($fieldMax);

						$fieldCaptionFrom = $dom->createAttribute('captionFrom');
						$fieldCaptionFrom->value = 'от';
						$fieldRangeFilter->appendChild($fieldCaptionFrom);

						$fieldCaptionTo = $dom->createAttribute('captionTo');
						$fieldCaptionTo->value = 'до';
						$fieldRangeFilter->appendChild($fieldCaptionTo);

						$fieldDescription = $dom->createElement('description');
						$fieldRangeFilter->appendChild($fieldDescription);

						$fieldCaption = $dom->createAttribute('caption');
						$fieldCaption->value = $field['title'];
						$fieldDescription->appendChild($fieldCaption);

						$fieldDescriptionParameter = $dom->createElement('setParameter');
						$fieldDescription->appendChild($fieldDescriptionParameter);

						$attributeName = $dom->createAttribute('name');
						$attributeName->value = $field['name'] . '[0]';
						$fieldDescriptionParameter->appendChild($attributeName);

						$fieldDescriptionParameter = $dom->createElement('setParameter');
						$fieldDescription->appendChild($fieldDescriptionParameter);

						$attributeName = $dom->createAttribute('name');
						$attributeName->value = $field['name'] . '[1]';
						$fieldDescriptionParameter->appendChild($attributeName);

						break;
					}
					case 'boolean': {
						$fieldCheckBox = $dom->createElement('checkBox');
						$filters->appendChild($fieldCheckBox);

						$fieldDescription = $dom->createElement('description');
						$fieldCheckBox->appendChild($fieldDescription);

						$fieldCaption = $dom->createAttribute('caption');
						$fieldCaption->value = $field['title'];
						$fieldDescription->appendChild($fieldCaption);

						$fieldDescriptionParameter = $dom->createElement('setParameter');
						$fieldDescription->appendChild($fieldDescriptionParameter);

						$attributeName = $dom->createAttribute('name');
						$attributeName->value = $field['name'];
						$fieldDescriptionParameter->appendChild($attributeName);

						$fieldChecked = $dom->createElement('checked');
						$fieldCheckBox->appendChild($fieldChecked);

						$attributeCheckedKey = $dom->createAttribute('key');
						$attributeCheckedKey->value = 1;
						$fieldChecked->appendChild($attributeCheckedKey);

						$fieldUnChecked = $dom->createElement('unchecked');
						$fieldCheckBox->appendChild($fieldUnChecked);

						$attributeUnCheckedKey = $dom->createAttribute('key');
						$attributeUnCheckedKey->value = 0;
						$fieldUnChecked->appendChild($attributeUnCheckedKey);

						$attributeUnCheckedDefault = $dom->createAttribute('default');
						$attributeUnCheckedDefault->value = 'true';
						$fieldUnChecked->appendChild($attributeUnCheckedDefault);

						break;
					}
					case 'symlink': {
						break;
					}
					default: {
					var_dump($fieldType);
					exit();
					}
				}
			}

			if ($asFile == 1) {
				$temp_file = './files/' . time() . '.xml';
				$dom->save($temp_file);
				$xmlFile = new umiFile($temp_file);
				$xmlFile->download(true);
				return;
			}

			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset("utf-8");
			$buffer->contentType("text/xml");
			$buffer->push($dom->saveXML());
			$buffer->end();
			return;
		}
	}
 