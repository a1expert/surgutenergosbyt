<?php
	class uselStream extends umiBaseStream {
		protected $scheme = "usel", $selectionFilePath, $selectionName, $selectionParams = Array();
		protected $dom, $sel, $mode = "objects";
		protected $modes = Array("objects" => "objects", "pages" => "pages", "count" => "count", "objects count" => "objects", "pages count" => "pages");
		protected $lastTypeId = false;
		protected $forceCounts = true;
		protected $extendedProperties = array();
		protected $extendedGroups = array();


		public function stream_open($path, $mode, $options, $opened_path) {
			$path = $this->removeHash($path);
			$cacheFrontend = cacheFrontend::getInstance();
			$queryStringHash = $cacheFrontend->getQueryStringHash();
			$key = $path . $queryStringHash;
			if($data = $cacheFrontend->loadData($key)) {
				return $this->setData($data);
			}
			
			try {
				$sel = $this->parsePath($path);
			} catch (Exception $e) {
				$result = array('error'  => $e->getMessage());
				$data = parent::translateToXml($result);
				
				return $this->setData($data);
			}
			
			if($sel instanceof selector) {
				if($this->mode !== "count") {
					$res = $sel->result;
					if($this->forceCounts) {
						$res['total'] = $sel->length;
					}
				} else {
					$res = $sel->length;
				}

				$data = $this->translateToXml($res);
				if($this->expire) $cacheFrontend->saveData($key, $data, $this->expire);
				return $this->setData($data);
			} else {
				return $this->setDataError('not-found');
			}
		}
		
		public function call($selectionName, $params = NULL) {
			$this->selectionFilePath = $selectionFilePath = realpath(CURRENT_WORKING_DIR . "/usels/" . $selectionName . ".xml");

			// подключаем usels из ресурсов шаблона
			// TODO: refactoring
			if (!$this->selectionFilePath && ($resourcesDir = cmsController::getInstance()->getResourcesDirectory())) {
				$this->selectionFilePath = $selectionFilePath = realpath($resourcesDir . "/usels/" . $selectionName . ".xml");
			}

			if(!file_exists($selectionFilePath)) {
				throw new publicException("File ./usels/" . $selectionName . ".xml not found");
			}
			
			if(is_array($params)) {
				$this->selectionParams = $params;
			}
			
			return array(
				"sel" => $this->createSelection(),
				"mode" => $this->mode
			);
		}
		
		protected function parsePath($path) {
			$path = parent::parsePath($path);
			$path = trim($path, "/");

			$path = str_replace(")(", ") (", $path);
			$path = preg_replace("/\(([^\)]+)\)/Ue", "umiBaseStream::protectParams('\\1')", $path);
			
			$path_arr = explode("/", $path);
			
			if(sizeof($path_arr) == 0) {
				throw new publicException("File {$path} not found");
			}

			$this->selectionName = $selectionName = $path_arr[0];

			$this->selectionFilePath = $selectionFilePath = realpath(CURRENT_WORKING_DIR . "/usels/" . $selectionName . ".xml");

			// подключаем usels из ресурсов шаблона
			// TODO: refactoring
			if (!$this->selectionFilePath && ($resourcesDir = cmsController::getInstance()->getResourcesDirectory())) {
				$this->selectionFilePath = $selectionFilePath = realpath($resourcesDir . "/usels/" . $selectionName . ".xml");
			}

			if(!file_exists($selectionFilePath)) {
				return false;
			}
			
			for($i = 1; $i < sizeof($path_arr); $i++) {
				$this->selectionParams[] = umiBaseStream::unprotectParams($path_arr[$i]);
			}
			
			return $this->createSelection();
		}
		
		
		protected function translateToXml() {
			$args = func_get_args();
			$arr = $args[0];

			$objectsCollection = umiObjectsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$request = Array();
			
			switch($this->mode) {
				case "pages": {
					$pages = Array();
					foreach($arr as $i => $element) {
						if("total" == (string) $i) {
							continue;
						}
						
						if($element instanceof umiHierarchyElement) {

							if (count($this->extendedGroups) || count($this->extendedProperties)) {
								$data = translatorWrapper::get($element)->translate($element);

								$object = $element->getObject();
								$data['extended'] = array();
								
								if (count($this->extendedProperties)) {
									$data['extended']['properties'] = array();
									foreach ($this->extendedProperties as $extendedPropery) {
										$property = $object->getPropByName($extendedPropery);
										if (!$property instanceof umiObjectProperty) continue;
										$data['extended']['properties']['nodes:property'][] = translatorWrapper::get($property)->translate($property);
									}
								}
								
								if (count($this->extendedGroups)) {
									$data['extended']['groups'] = array();
									$data['extended']['groups']['nodes:group'] = array();
									
									$objectType = $object->getType();
								
									foreach ($this->extendedGroups as $extendedGroup) {			
										$group = $objectType->getFieldsGroupByName($extendedGroup);
										if (!$group instanceof umiFieldsGroup) continue;
										
										$data['extended']['groups']['nodes:group'][] = translatorWrapper::get($group)->translateProperties($group, $object);
									}
								}
							} else {
								$data = $element;
							}

							$pages[] = $data;											
						}
					}
					
					$request['nodes:page'] = $pages;
					
					if(isset($arr['total'])) {
						$request['total'] = $arr['total'];
					}
					
					break;
				}
				
				
				case "objects": {
					$objects = array();
					foreach ($arr as $i => $object) {
						if ("total" == (string) $i) {
							continue;
						}
						
						if ($object instanceof umiObject) {

							if (count($this->extendedGroups) || count($this->extendedProperties)) {

								$data = translatorWrapper::get($object)->translate($object);

								$data['extended'] = array();
								
								if (count($this->extendedProperties)) {
									$data['extended']['properties'] = array();
									foreach ($this->extendedProperties as $extendedPropery) {
										$property = $object->getPropByName($extendedPropery);
										if (!$property instanceof umiObjectProperty) continue;
										$data['extended']['properties']['nodes:property'][] = translatorWrapper::get($property)->translate($property);
									}
								}
								
								if (count($this->extendedGroups)) {
									$data['extended']['groups'] = array();
									$data['extended']['groups']['nodes:group'] = array();
									
									$objectType = $object->getType();
								
									foreach ($this->extendedGroups as $extendedGroup) {			
										$group = $objectType->getFieldsGroupByName($extendedGroup);
										if (!$group instanceof umiFieldsGroup) continue;
										
										$data['extended']['groups']['nodes:group'][] = translatorWrapper::get($group)->translateProperties($group, $object);
									}
								}
							} else {
								$data = $object;
							}

							$objects[] = $data;											
						}
						
					}
					
					$request['nodes:item'] = $objects;
					
					if(isset($arr['total'])) {
						$request['total'] = $arr['total'];
					}
					
					break;
				}
				
				case "count": {
					$request['total'] = $arr;
					break;
				}

				default: {
					$request['error'] = "Unknown result mode \"{$this->mode}\"";
					break;
				}
			}
			
			$request['attribute:module']= $this->scheme;
			$request['attribute:method']= $this->selectionName;
			
			$result =  parent::translateToXml($request);
			return $result;
		}
		

		protected function loadDomXml() {
			return secure_load_dom_document(file_get_contents($this->selectionFilePath), $this->dom);
		}
		

		protected function createSelection() {
			if (!$this->loadDomXml()) {
				throw new publicException("Usel source is not well-formed XML");
			}

			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("/selection");
			
			if($nodes->length == 1) {
				$selectionNode = $nodes->item(0);

				$this->getTargets($selectionNode);
				$this->getOptions($selectionNode);
				$this->getHierarchy($selectionNode);
				$this->getSorts($selectionNode);
				$this->getLimit($selectionNode);
				$this->getProperties($selectionNode);
				$this->getExtendedProperties($selectionNode);
				$this->getExtendedGroups($selectionNode);

				return $this->sel;
			} else {
				return false;
			}
		}
		
		protected function getOptions(DOMElement $selectionNode) {
			$selectionObj = $this->sel;
			
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("option", $selectionNode);
			foreach($nodes as $optionNode) {
				$name = (string) $optionNode->getAttribute("name");
				$name = $this->parseInputParams($name);
				
				$value = (string) $optionNode->getAttribute("value");
				$value = $this->parseInputParams($value);
				
				$selectionObj->option($name, $value);
			}
		}


		protected function getTargets(DOMElement $selectionNode) {
			$xpath = new DOMXPath($this->dom);
			
			$nodes = $xpath->evaluate("target", $selectionNode);
			$targetNode = $nodes->item(0);

            if (is_null($targetNode)) {
                return false;
            }

            $targetResult = (string) $targetNode->getAttribute("result");
            if (!$targetResult) {
                $targetResult = (string) $targetNode->getAttribute("expected-result");
            }

            $forceHierarchy = (string) $targetNode->getAttribute("force-hierarchy");
            if (!isset($this->modes[$targetResult])) {
                return false;
            }

            if (strpos($targetResult, " ") !== false) {
                $this->forceCounts = true;
                $this->forceCounts = true;
            }
            $targetResult = $this->modes[$targetResult];
            $this->mode = $targetResult;

            $selectorMode = $this->calculateSelectorMode($forceHierarchy);
            $this->sel = new selector($selectorMode);
            $selectionObj = $this->sel;

            // set targets domain if need...
            if ($this->mode == 'pages') {
                $domains_nl = $xpath->evaluate("domain", $targetNode);
                if ($domains_nl->length > 0) {
                    $domainNode = $domains_nl->item(0);
                    $domain = $domainNode->nodeValue;
                    if (!is_numeric($domain)) {
                        $domain = domainsCollection::getInstance()->getDomainId($domain);
                    }
                    if ($domain && domainsCollection::getInstance()->isExists($domain)) {
                        $selectionObj->where('domain')->equals($domain);
                    }
                }
            }

			/** @var DOMNodeList $nodes */
			$nodes = $xpath->evaluate("target/type");

            foreach($nodes as $typeNode) {
				$typeId = $typeNode->getAttribute("id");
				$typeModule = $typeNode->getAttribute("module");
				$typeMethod = $typeNode->getAttribute("method");

				$typeId = $this->parseInputParams($typeId);
				$typeModule = $this->parseInputParams($typeModule);
				$typeMethod = $this->parseInputParams($typeMethod);

                if($typeId) {
					$this->lastTypeId = $typeId;
					$selectionObj->types('object-type')->id((int) $typeId);
					continue;
				}
				
				if($typeModule && $typeMethod) {
					$hierarchyType = umiHierarchyTypesCollection::getInstance()->getTypeByName($typeModule, $typeMethod);

					if($hierarchyType instanceof iUmiHierarchyType) {
						$typeId = umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($hierarchyType->getId());
						$this->lastTypeId = $typeId;

                        $this->setSelectorMode($this->mode, $selectionObj, $hierarchyType, $typeId);
                        if ($this->mode == "count") {
                            $this->setSelectorMode($selectorMode, $selectionObj, $hierarchyType, $typeId);
                        }
					} else {
						continue;
					}
				}
			}
		}

        /**
         * Устанавливает резим работы селектора.
         * @param string $mode
         * @param selector $selectionObj
         * @param iUmiHierarchyType $hierarchyType
         * @param int $typeId
         */
        protected function setSelectorMode($mode, $selectionObj, $hierarchyType, $typeId)
        {
            switch ($mode) {
                case 'pages' : {
                    $selectionObj->types('hierarchy-type')->id($hierarchyType->getId());
                    break;
                }
                case 'objects' : {
                    $selectionObj->types('object-type')->id((int) $typeId);
                    break;
                }
            }
        }

        /**
         * Вычисляет режим работы серектора, в зависимости настроек в usel.
         * @param int|bool $forceHierarchy
         * @return string
         */
        protected function calculateSelectorMode($forceHierarchy)
        {
            if ($this->mode != 'count') {
                return $this->mode;
            }

            if ($forceHierarchy) {
                return 'pages';
            }

            return 'objects';
        }


		protected function getSorts(DOMElement $selectionNode) {
			$hasTypeId = true;
		
			if($this->lastTypeId !== false) {
				$hasTypeId = true;
			} else {
				$hasTypeId = false;
			}
		
			$selectionObj = $this->sel;
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("sort", $selectionNode);

			foreach($nodes as $sortNode) {
				$order = (string) $sortNode->getAttribute("order");
				$order = $this->parseInputParams($order);
				
				$field_name = $sortNode->nodeValue;
				$field_name = $this->parseInputParams($field_name);
				
				$order = (strtolower($order) == "descending" || strtolower($order) == "desc") ? false : true;
				
				$sort = null;
				switch($field_name) {
					case "name": {
						if($hasTypeId) {
							$sort = $selectionObj->order('name');
						}
						break;
					}
					
					case "ord": {
						$sort = $selectionObj->order('ord');
						break;
					}
					
					
					case "rand()": {
						$sort = $selectionObj->order('rand');
						break;
					}
					
					default: {
						if($hasTypeId && $field_name) {
							$sort = $selectionObj->order($field_name);
						}
						break;
					}
				}

				if($sort) {
					if($order) $sort->asc(); else $sort->desc();
				}
			}
		}
		
		
		protected function getLimit(DOMElement $selectionNode) {
			$selectionObj = $this->sel;
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("limit", $selectionNode);
			
			if($limitNode = $nodes->item(0)) {
				$limit = $limitNode->nodeValue;
				$page = $limitNode->getAttribute("page");
				
				$limit = $this->parseInputParams($limit);
				$page = (int) $this->parseInputParams($page);
				
				if($limit) {
					$selectionObj->limit($limit * $page, $limit);
				}
			} else {
				return false;
			}
		}
		
		protected function getExtendedProperties(DOMElement $selectionNode) {

			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("extended/properties", $selectionNode);
			
			if($extendedPropertiesNode = $nodes->item(0)) {
				$extendedProperties = $extendedPropertiesNode->nodeValue;
				$extendedProperties = $this->parseInputParams($extendedProperties);
				$this->extendedProperties = array_unique(array_map("trim", explode(',', $extendedProperties)));		
			} else {
				return false;
			}
		}
		
		protected function getExtendedGroups(DOMElement $selectionNode) {

			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("extended/groups", $selectionNode);
			
			if($extendedGroupsNode = $nodes->item(0)) {
				$extendedGroups = $extendedGroupsNode->nodeValue;
				$extendedGroups = $this->parseInputParams($extendedGroups);
				$this->extendedGroups = array_unique(array_map("trim", explode(',', $extendedGroups)));		
			} else {
				return false;
			}
		}
		
		
		protected function getProperties(DOMElement $selectionNode) {
			if ($this->lastTypeId === false) {
				return false;
			}

			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("property", $selectionNode);
			
			foreach($nodes as $propertyNode) {
				$propertyName = $propertyNode->getAttribute("name");
				$propertyName = $this->parseInputParams($propertyName);
				
				$propertyValue = $propertyNode->getAttribute("value");
				$propertyValue = $this->parseInputParams($propertyValue);
					
				$compareMode = $propertyNode->getAttribute("mode") == "not" ? true : false;
				$likeMode = $propertyNode->getAttribute("mode") == "like" ? true : false;
				$interval = $this->getInterval($propertyNode);
				$pages = $this->getPages($propertyNode);
				$objects = $this->getObjects($propertyNode);

				switch(true) {
					case (bool) $propertyValue: {
						$this->filterSimpleProperty($propertyName, $propertyValue, $compareMode, $likeMode);
						break;
					}
						
					case ($interval['min'] !== false || $interval['max'] !== false): {
						$this->filterIntervalProperty($propertyName, $interval);
						break;
					}
						
					case sizeof($pages) > 0 : {
						$this->filterPagesProperty($propertyName, $pages, $compareMode);
						break;
					}
						
					case sizeof($objects) > 0 : {
						$this->filterObjectsProperty($propertyName, $objects, $compareMode);
						break;
					}
						
					default: {
						break;
					}
				}
			}
		}
		
		
		protected function getInterval(DOMElement $propertyNode) {
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("min-value|max-value", $propertyNode);

			$result = Array("min" => false, "max" => false);

			foreach($nodes as $node) {
				$value = $node->nodeValue;
				$value = $this->parseInputParams($value);

				if (!strlen($value)) $value = false;
				
				if($format = $node->getAttribute("format")) {
					$value = $this->formatValue($value, $format);
				}
				
			
				if($node->tagName == "min-value") {
					$result['min'] = $value;
				}
				
				if($node->tagName == "max-value") {
					$result['max'] = $value;
				}
			}

			return $result;
		}
		
		
		protected function formatValue($value, $format) {
			switch($format) {
				case "timestamp": {
					$value = (int) $value;
					break;
				}
				
				case "GMT":
				case "UTC": {
					$value = strtotime($value);
					break;
				}
			}
			
			return $value;
		}
		
		
		protected function getPages(DOMElement $propertyNode) {
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("page", $propertyNode);
			
			$result = Array();

			foreach($nodes as $node) {
				$value = $node->nodeValue;
				$value = $this->parseInputParams($value);
				
				if(is_numeric($value)) {
					$result[] = $value;
				} else {
					$value = umiHierarchy::getInstance()->getIdByPath($value);
					if($value) {
						$result[] = $value;
					} else {
						continue;
					}
				}
			}
			
			return $result;
		}
		
		
		protected function getHierarchy(DOMElement $selectionNode) {
			$selectionObj = $this->sel;	

			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("target/category", $selectionNode);

			$result = Array();

			foreach($nodes as $node) {
				$value = $node->nodeValue;
				$value = $this->parseInputParams($value);
				
				$depth = $node->getAttribute("depth");
				$depth = $this->parseInputParams($depth);
				$depth = ($depth === '') ? 1 : (int) $depth;

				if(is_numeric($value)) {
					$value = (int) $value;
				} else {
					$hierarchy = umiHierarchy::getInstance();
					$value = (int) $hierarchy->getIdByPath($value);
				}

				if(is_numeric($value)) {
					$h = $selectionObj->where('hierarchy')->page($value)->childs($depth ? $depth : 100);
				}
			}
		}
		
		
		protected function getObjects(DOMElement $propertyNode) {
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("object", $propertyNode);
			
			$result = Array();
			
			foreach($nodes as $node) {
				if($value = $node->nodeValue) {
					if($value = $this->parseInputParams($value)) {
						$result[] = $value;
					}
				}
			}
			
			return $result;
		}
		
		
		protected function filterSimpleProperty($fieldName, $value, $mode, $like) {
			$selectionObj = $this->sel;

			if($fieldName != "name") {
				if($mode) {
					$selectionObj->where($fieldName)->notequals($value);
					return true;
				} else {
					if($like) {
						$selectionObj->where($fieldName)->like('%' . $value . '%');
					} else {
						$selectionObj->where($fieldName)->equals($value);
					}
					return true;
				}
			} else {
				if(!$mode) {
					if($like) {
						$selectionObj->where('name')->like('%' . $value . '%');
					} else {
						$selectionObj->where('name')->equals($value);
					}
					return true;
				} else {
					return false;
				}
			}
		}
		
		
		protected function filterIntervalProperty($fieldName, $interval) {
			$min = $interval['min'];
			$max = $interval['max'];
			
			$selectionObj = $this->sel;
			
			if($min !== false && $max !== false) {
				$selectionObj->where($fieldName)->between($min, $max);
				return true;
			} else {
				if($min !== false) {
					$selectionObj->where($fieldName)->more($min);
					return true;
				}
				
				if($max !== false) {
					$selectionObj->where($fieldName)->less($max);
					return true;
				}
				
				return false;
			}
		}
		
		
		protected function filterPagesProperty($fieldName, $pages, $mode) {
			$selectionObj = $this->sel;
			
			if($mode) {
				$selectionObj->where($fieldName)->notequals($pages);
			} else {
				$selectionObj->where($fieldName)->equals($pages);
			}
		}
		
		
		protected function filterObjectsProperty($fieldName, $objects, $mode) {
			$selectionObj = $this->sel;

			if($mode) {
				$selectionObj->where($fieldName)->notequals($objects);
			} else {
				$selectionObj->where($fieldName)->equals($objects);
			}
		}
		
		
		protected function parseInputParams($str) {
			$params = $this->selectionParams;

			foreach ($params as $key => $value) {
				$p = "{" . $key . "}";
				if (is_int($key)) {
					$p = "{" . ($key + 1) . "}";
				}
				$v = $params[$key];
				$str = str_replace($p, $v, $str);
			}

			foreach($this->params as $i => $v) {
				$p = "{" . $i . "}";
				$str = str_replace($p, $v, $str);
			}
			
			return preg_replace("/\{[^\{\}]+\}/", "", $str);
		}
	};
?>
