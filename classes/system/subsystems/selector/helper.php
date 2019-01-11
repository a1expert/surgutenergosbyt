<?php
	class selectorHelper {
		
		static function detectFilters(selector $sel) {
			if($sel->mode == 'pages') {
				$domains = (array) getRequest('domain_id');
				foreach($domains as $domainId) {
					$sel->where('domain')->equals($domainId);
				}
				
				$langs = (array) getRequest('lang_id');
				foreach($langs as $langId) {
					$sel->where('lang')->equals($langId);
				}
			}
			
			
			if ( $sel->mode == 'pages' && sizeof($sel->types) && is_array(getRequest('rel')) && regedit::getInstance()->getVal('//modules/comments') ) {
				$sel->types('hierarchy-type')->name('comments', 'comment');
			}
			
			if( isset($_REQUEST['hierarchy_types'] ) ) {
				$htypes = (array) $_REQUEST['hierarchy_types'];
				
				foreach($htypes as $v) {
					$v = explode('-',$v);
					
					if(sizeof($v)==2) 
						$sel->types('hierarchy-type')->name($v[0],$v[1]);
				}
				
			}
			
			self::detectHierarchyFilters($sel);
			self::detectWhereFilters($sel);
			self::detectOrderFilters($sel);
			
			//$sel->option('exclude-nested', true);
			
			self::checkSyncParams($sel);
		}
		
		static function checkSyncParams(selector $sel) {
			if(getRequest('export')) {
				quickCsvExporter::autoExport($sel, (bool) getRequest('force-hierarchy'));
			}
			
			if(getRequest('import')) {
				quickCsvImporter::autoImport($sel, (bool) getRequest('force-hierarchy'));
			}
		}
		
		
		static function detectHierarchyFilters(selector $sel) {
			//if(sizeof(getRequest('fields_filter'))) return;
			//if(sizeof(getRequest('order_filter'))) return;
		
			$rels = (array) getRequest('rel');
			
			if(sizeof($rels) == 0 && $sel->mode == 'pages') {				
				//$rels[] = '0';
				$sel->option('exclude-nested', true);
			}
			
			foreach($rels as $id) {
				try {
					if($id || $id === '0') $sel->where('hierarchy')->page($id)->childs(1);
					if($id === '0') $sel->option('exclude-nested', true);
				} catch (selectorException $e) {}
			}
		}
		
		static function detectWhereFilters(selector $sel) {
			static $funcs = array('eq' => 'equals', 'ne' => 'notequals', 'like' => 'like', 'gt' => 'more', 'lt' => 'less' );
			
			
			$searchAllText = (array) getRequest('search-all-text');
			$umiTypesHelper = umiTypesHelper::getInstance();
			//fix for guide items without fields
			if(sizeof($sel->types) == 1 && !is_null($sel->types[0]->objectTypeIds) && count($umiTypesHelper->getFieldsByObjectTypeIds($sel->types[0]->objectTypeIds)) == 0) {
				foreach($searchAllText as $searchString) {
					$sel->where('name')->like('%' . $searchString . '%');
				}
				return;
			} else {
				foreach($searchAllText as $searchString) {
					try {
						if($searchString !== "") $sel->where('*')->like('%' . $searchString . '%');
					} catch (selectorException $e) {}
				}
			}
			$umiTypesHelper = umiTypesHelper::getInstance();
			$umiFieldsTypes = umiFieldTypesCollection::getInstance();
			$filters = (array) getRequest('fields_filter');
			foreach($filters as $fieldName => $info) {
				if(is_array($info)) {
					//Old-style between filter
					if(isset($info[0]) && isset($info[1])) {
						try {
							$sel->where($fieldName)->between($info[0], $info[1]);
						} catch (selectorException $e) {}
					}
					
					//Try new-style filter
					foreach($info as $i => $v) {
						if(isset($funcs[$i])) {
							try {
								switch(true) {
									case $funcs[$i] == 'like': {
										$v .= '%';
										break;
									}
									case $funcs[$i] == 'equals' && ($v == '1' || $v == '-1'): {
										$fieldId = $sel->searchField($fieldName);
										$fieldsTypeId = $umiTypesHelper->getFieldTypeIdByFieldId($fieldId);
										$fieldType = $umiFieldsTypes->getFieldType($fieldsTypeId);
										if (!$fieldType instanceof umiFieldType) {
											break;
										}
										$fieldsTypeName = $fieldType->getDataType();
										if (!preg_match('/(file)/', $fieldsTypeName)) {
											break;
										}
										$method = ($v > 0) ? 'isnotnull' : 'isnull';
										$value = ($v > 0) ? false : true;
										$sel->where($fieldName)->$method($value);
										$v = '';
										break;
									}
								}
								if($v !== "") $sel->where($fieldName)->$funcs[$i]($v);
							} catch(selectorException $e) { self::tryException($e); }
						}
					}
				} else {
					//Old-style strict equals filter
					try {
						if($info !== "") $sel->where($fieldName)->equals($info);
					} catch(selectorException $e) {}
				}
			}
		}
		
		static function detectOrderFilters(selector $sel) {
			$orders = (array) getRequest('order_filter');
			foreach($orders as $fieldName => $direction) {
				$func = (strtolower($direction) == 'desc') ? 'desc' : 'asc';
				
				try {
					$sel->order($fieldName)->$func();
				} catch (selectorException $e) { self::tryException($e); }
			}
		}
		
		static private function tryException(Exception $e) {
			//if(DEBUG) throw $e;
		}
	};
?>