<?php
	class csvExporter extends umiExporter {
		public function setOutputBuffer() {
			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset("windows-1251");
			$buffer->contentType("text/plain");
			return $buffer;
		}

		public function export($branches, $excludedBranches) {

			$temp_dir = CURRENT_WORKING_DIR . "/sys-temp/export/";
			$id = getRequest('param0');
			if (!is_dir($temp_dir)) mkdir($temp_dir, 0777, true);
			$cache_file_path = $temp_dir . $id . "." . $this->getFileExt();
			$sourceName = $id . "." . $this->getFileExt();

			if(file_exists($cache_file_path) && !file_exists($cache_file_path . 'array')) unlink($cache_file_path);
			
			$elementsToExport = array();
			
			if (!file_exists($cache_file_path . 'array')) {
				
				if (!count($branches)) {
					$sel = new selector('pages');
					$sel->where('hierarchy')->page(0)->childs(0);
					$branches = $sel->result;
				}
				
				foreach ($branches as $element) {
					if (!$element instanceof umiHierarchyElement) {
						$element = umiHierarchy::getInstance()->getElement($element, true, true);
					}
					if (!$element instanceof umiHierarchyElement) continue;
					$elementId = $element->getId();
					$elementsToExport[$elementId] = $elementId;
					
					$level = umiHierarchy::getInstance()->getMaxNestingLevel($elementId);
					if (!$level) continue;
					
					for ($i = 1; $i <= $level; $i++) {
						$sel = new selector('pages');
						$sel->option('return')->value('id');
						$sel->where('hierarchy')->page($elementId)->childs($i);
						foreach($sel->result() as $res) {
							$elementsToExport[$res['id']] = $res['id'];
						}	
					}
				}
				
				foreach ($excludedBranches as $element) {
					if (!$element instanceof umiHierarchyElement) {
						$element = umiHierarchy::getInstance()->getElement($element, true, true);
					}
					if (!$element instanceof umiHierarchyElement) continue;
					$elementId = $element->getId();
					
					if (isset($elementsToExport[$elementId])) {
						unset($elementsToExport[$elementId]);
					}
				
					$level = umiHierarchy::getInstance()->getMaxNestingLevel($elementId);
					if (!$level) continue;
					
					for ($i = 1; $i <= $level; $i++) {
						$sel = new selector('pages');
						$sel->option('return')->value('id');
						$sel->where('hierarchy')->page($elementId)->childs($i);
						foreach($sel->result() as $res) {
							if (isset($elementsToExport[$res['id']])) {
								unset($elementsToExport[$res['id']]);
							}
						}	
					}
				}
				
				
				
			} else {
				$elementsToExport = unserialize(file_get_contents($cache_file_path . 'array'));
			}

			$blockSize = false;
			if(getRequest('as_file') !== '0') {
				$blockSize = (int) mainConfiguration::getInstance()->get("modules", "exchange.export.limit");
				if($blockSize <= 0) $blockSize = 25;
			}

			$exporter = new xmlExporter($sourceName, $blockSize);
			if(getRequest('as_file') !== '0') {
				$exporter->addElements(array_slice($elementsToExport, 0, $blockSize + 1));
			} else {
				$exporter->addElements($elementsToExport);	
			}
			
			$exporter->setIgnoreRelations();

			$doc = $exporter->execute();
						
			$list = $this->getCSV($doc, $cache_file_path);

			$handle = fopen($cache_file_path, 'a');
			foreach ($list as $fields) {
				$string = "\"" . implode('";"', $fields) . "\"\n";
				if ($string = @iconv('utf-8', 'windows-1251//IGNORE', $string)) {
					fputs($handle, $string);
				}
			}
			fclose($handle);

			
			$completed = $exporter->isCompleted();
			if(getRequest('as_file') !== '0') {

				$exportedElements = array_keys($exporter->getExportedElements());
				$elementsToExport = array_diff($elementsToExport, $exportedElements);
				
				if (count($elementsToExport)) {
					$this->completed = false;
					file_put_contents($cache_file_path . 'array', serialize($elementsToExport));	
				} else {
					if (file_exists($cache_file_path . 'array')) unlink ($cache_file_path . 'array');
					$this->completed = true;
				}
				
			} else {
				$this->completed = $completed;
			}
			
			if ($this->completed) {

				$list = unserialize(file_get_contents($cache_file_path . ".tmp"));
				$handle = fopen($cache_file_path . ".tmp", 'w');
				foreach ($list as $fields) {
					$string = "\"" . implode('";"', $fields) . "\"\n";
					if ($string = @iconv('utf-8', 'windows-1251//IGNORE', $string)) {
						fwrite($handle, $string);
					}
				}

				$handle2 = fopen($cache_file_path, 'r');
				while ($string = fgets($handle2)) {
					if (substr_count($string, '"') % 2 != 0) {
						$isRecord = false;
						while (!feof($handle2) && !$isRecord) {
						    $string .= fgets($handle2);
							if (substr_count($string, '"') % 2 == 0) {
	        					$isRecord = true;
							}
					    }
					}

					fwrite($handle, $string);
				}

				fclose($handle);
				fclose($handle2);

				unlink($cache_file_path);
				rename($cache_file_path . ".tmp", $cache_file_path);

			}

			chmod($cache_file_path, 0777);
			return false;
		}

		public function getFileExt() {
			return "csv";
		}

		protected function getCSV($doc, $filePath) {

			$result = array();

			if (file_exists($filePath . ".tmp")) {
				$arrays = unserialize(file_get_contents($filePath . ".tmp"));
				$names = $arrays[0];
				$titles = $arrays[1];
				$types = $arrays[2];

			} else {
				$names = array(
					0 => 'id',
					1 => 'name',
					2 => 'type-id',
					3 => 'is-active',
					4 => 'template-id',
					5 => 'parent-id'
				);

				$titles = array(
					0 => 'id',
					1 => 'Наименование',
					2 => 'Идентификатор типа',
					3 => 'Активность',
					4 => 'Идентификатор шаблона',
					5 => 'id родительской страницы'
				);

				$types = array(
					0 => 'native',
					1 => 'native',
					2 => 'native',
					3 => 'native',
					4 => 'native',
					5 => 'native'
				);
			}

			$xpath = new DOMXPath($doc);

			if ($xpath->query('//pages/page')->length) {

				$pages = $xpath->query('//pages/page');
				$i = 3;
				foreach ($pages as $page) {
					$pageContent = array();
					$pageContent[0] = $page->getAttribute('id');
					$pageContent[1] = $page->getElementsByTagName('name')->item(0)->nodeValue;
					$pageContent[2] = $page->getAttribute('type-id');
					$pageContent[3] = $page->getAttribute('is-active');
					if ($page->getElementsByTagName('template')->length) {
						$pageContent[4] = $page->getElementsByTagName('template')->item(0)->getAttribute('id');
					} else {
						$pageContent[4]='';
					}

					$pageContent[5] = $page->hasAttribute('parentId') ? $page->getAttribute('parentId') : 0;
					$properties = $page->getElementsByTagName('property');

					foreach ($properties as $property) {

						$propName = $property->getAttribute('name');
						$propType = $property->getAttribute('type');

						if(in_array($propType, array('optioned', 'symlink'))) continue;

						if ($propType == 'relation') {

							if ($property->hasAttribute('multiple') && $property->getAttribute('multiple') == 'multiple') {
								$propType = 'multiple-relation';
							}

							$value = $property->getElementsByTagName('value')->item(0);
							$propValues = $value->getElementsByTagName('item');
							$propValue = array();
							if($propValues->length) {
								for($k = 0; $k < $propValues->length ; $k++) {
									$propValue[] = $propValues->item($k)->getAttribute('name');
								}
							}
							$propValue = implode(',', $propValue);

						} elseif($propType == 'tags') {
							$propValue = $property->getElementsByTagName('combined')->item(0)->nodeValue;
						} else {
							$propValue = $property->getElementsByTagName('value')->item(0)->nodeValue;
						}

						$key = array_search($propName, $names);

						if (!$key) {
							$names[] = $propName;
							$key = array_search($propName, $names);
							$types[$key] = $propType;
							$propTitle = $property->getElementsByTagName('title')->item(0)->nodeValue;
							$titles[$key] = $propTitle;
						}

						$pageContent[$key] = $propValue;
					}

					$keys = array_keys($names);
					foreach ($keys as $key) {
						if (!array_key_exists($key, $pageContent)) {
							$pageContent[$key] ='';
						} else {
							$pageContent[$key] = str_replace('"', '""', $pageContent[$key]);
						}
					}

					ksort($pageContent);
					$result[$i] = $pageContent;
					$i++;
				}

			}

			$arrays = array();
			$arrays[0] = $names;
			$arrays[1] = $titles;
			$arrays[2] = $types;

			file_put_contents($filePath . ".tmp", serialize($arrays));

			return $result;
		}

	}
?>