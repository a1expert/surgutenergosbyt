<?php
	class content extends def_module {
		public function __construct() {
			parent::__construct();

			$this->loadCommonExtension();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				cmsController::getInstance()->getModule('users');

				$configTabs = $this->getConfigTabs();
				if ($configTabs) {
					$configTabs->add("config");
					$configTabs->add("content_control");
					$configTabs->add("tickets");
				}

				$this->__loadLib("__admin.php");
				$this->__implement("__content");

				$this->loadAdminExtension();

				// custom admin methods
				$this->__loadLib("__custom_adm.php");
				$this->__implement("__content_custom_admin");
			} else {
				$this->__loadLib("__tickets.php");
				$this->__implement("__tickets_content");
			}
			$this->__loadLib("__json.php");
			$this->__implement("__json_content");

			$this->__loadLib("__lib.php");
			$this->__implement("__lib_content");

			$this->__loadLib("__events.php");
			$this->__implement("__content_events");

			$this->__loadLib("__editor.php");
			$this->__implement("__editor_content");

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__custom_content");
		}


		public function isMethodExists($_sMethodName) {
			/**
			 * @TODO: temporary fix for some methods
			 */
			if (in_array($_sMethodName, array(
					'pages_mklist_by_tags',
					'pagesByAccountTags',
					'pagesByDomainTags',
					'tags_mk_cloud',
					'tags_mk_eff_cloud',
					'tagsAccountCloud',
					'tagsAccountEfficiencyCloud',
					'tagsAccountUsageCloud',
					'tagsDomainCloud',
					'tagsDomainEfficiencyCloud',
					'tagsDomainUsageCloud'
				))) {
				return true;
			}
			return parent::isMethodExists($_sMethodName);
		}

		function __call($method, $args) {
			static $cache = array();

			if (!isset($cache[$method]) && !method_exists($this, $method)) { // если еще не имлементировали
				$cache[$method] = true;
				$method_prefix = '';
				$matches = array();
				$bSucc = preg_match('/[A-Z_]/', $method, $matches);

				if ($bSucc) {
					$match = $matches[0];
					$match_pos = strpos($method, $match);
					$method_prefix = substr($method, 0, $match_pos) . '/';
				}

				$currdir = __DIR__ . '/';
				$class_enter = '__' . $method;
				$entermethod_lib = 'methods/' . $method_prefix . '__' . $method . '.lib.php';

				if (file_exists($currdir . $entermethod_lib) && !class_exists($class_enter)) { // метод, общий для всех режимов
					$this->__loadLib($entermethod_lib);
					$this->__implement($class_enter);
				}

				$class_mode = '__' . $method . '_'; // метод, выбираемый в зависимости от режима
				$modemethod_lib = 'methods/' . $method_prefix . '__' . $method . '_' . cmsController::getInstance()->getCurrentMode() . '.lib.php';

				if (file_exists($currdir . $modemethod_lib) && !class_exists($class_mode)) {
					$this->__loadLib($modemethod_lib);
					$this->__implement($class_mode);
				}
			}

			return parent::__call($method, $args);
		}


		public function content($elementId = false) {
			$cmsController = cmsController::getInstance();
			if(!$elementId) $elementId = $cmsController->getCurrentElementId();

			$hierarchy = umiHierarchy::getInstance();
			$element = $hierarchy->getElement($elementId);

			if($element instanceof iUmiHierarchyElement) {
				$this->pushEditable("content", "", $elementId);
				return $element->content;
			} else {
				return $this->gen404();
			}
		}


		public function gen404($template = 'default') {
			if(!$template) $template = 'default';

			$buffer = outputBuffer::current();
			$buffer->status('404 Not Found');

			$this->setHeader('%content_error_404_header%');
			list($tpl_block) = def_module::loadTemplates("content/not_found/".$template, 'block');
			$template = $tpl_block ? $tpl_block : '%content_usesitemap%';
			return def_module::parseTemplate($template, array());
		}


		public function menu($menu_tpl = "default", $max_depth = 1, $pid = false) {
			$cmsController = cmsController::getInstance();
			$hierarchy = umiHierarchy::getInstance();

			if($pid) {
				if(!is_numeric($pid)) {
					$pid = $hierarchy->getIdByPath($pid);
				}
				$parent_alt = $hierarchy->getPathById($pid, false, true, false, true);
			} else {
				$pid = 0;
				$parent_alt = $cmsController->pre_lang ."/". $cmsController->getUrlPrefix();
			}

			if($parent_alt) {
				$parent_alt = rtrim($parent_alt, '/');
			}

			$templates = def_module::loadTemplates("content/menu/" . $menu_tpl);

			return $this->build_menu($pid, $templates, 0, $parent_alt, $max_depth);
		}


		public function sitemap($template = "default", $max_depth = false, $root_id = false) {
			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();

			if(!$max_depth) $max_depth = getRequest('param0');
			if(!$max_depth) $max_depth = 4;

			if(!$root_id) $root_id = (int) getRequest('param1');
			if(!$root_id) $root_id = 0;

			if($cmsController->getCurrentMethod() == "sitemap") {
				$this->setHeader("%content_sitemap%");
			}

			$site_tree = $hierarchy->getChilds($root_id, false, false, $max_depth - 1);
			return $this->gen_sitemap($template, $site_tree, $max_depth - 1);
		}


		public function get_page_url($element_id, $ignore_lang = false) {
			$ignore_lang = (bool) $ignore_lang;
			return umiHierarchy::getInstance()->getPathById($element_id, $ignore_lang);
		}


		public function get_page_id($url) {
			$hierarchy = umiHierarchy::getInstance();
			$elementId = $hierarchy->getIdByPath($url);
			if($elementId) return $elementId; else {
				throw new publicException(getLabel('error-page-does-not-exist', null, $url));
			}
		}


		public function redirect($url = "") {
			if(is_numeric($url)) {
				$url = $this->get_page_url($url);
			}
			parent::redirect($url);
		}


		public function insert($elementId) {
			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();
			$currentElementId = $cmsController->getCurrentElementId();
			$elementId = trim($elementId);

			if(!$elementId) return "%content_error_insert_null%";

			$elementId = (int) is_numeric($elementId) ? $elementId : $hierarchy->getIdByPath($elementId);
			if($elementId == $currentElementId) return "%content_error_insert_recursy%";
			if(!$elementId) return;

			if($element = $hierarchy->getElement($elementId)) {
				$this->pushEditable("content", "", $elementId);
				return $element->content;
			}
		}


		public function get_parents($elementId) {
			return umiHierarchy::getInstance()->getAllParents($elementId, true);
		}


		public function getEditLink($elementId, $element_type) {
			return array(
				$this->pre_lang . "/admin/content/add/{$elementId}/page/",
				$this->pre_lang . "/admin/content/edit/{$elementId}/"
			);
		}


		private function gen_sitemap($template = "default", $site_tree, $max_depth) {
			$hierarchy = umiHierarchy::getInstance();

			list($template_block, $template_item) = def_module::loadTemplates("content/sitemap/" . $template, "block", "item");

			$block_arr = array(); $items = array();
			if(is_array($site_tree)) {
				foreach($site_tree as $elementId => $childs) {
					if($element = $hierarchy->getElement($elementId)) {
						$item_arr = array(
							'attribute:id'		=> $elementId,
							'attribute:link'	=> $element->link,
							'attribute:name'	=> $element->name,
							'xlink:href'		=> ("upage://" . $elementId)
						);

						if(($max_depth > 0) && $element->show_submenu) {
							$item_arr['nodes:items'] = $item_arr['void:sub_items'] = (sizeof($childs) && is_array($childs)) ? $this->gen_sitemap($template, $childs, ($max_depth - 1)) : "";
						} else {
							$item_arr['sub_items'] = "";
						}
						$items[] = self::parseTemplate($template_item, $item_arr, $elementId);
						$hierarchy->unloadElement($elementId);
					} else {
						continue;
					}
				}
			}

			$block_arr['subnodes:items'] = $items;
			return self::parseTemplate($template_block, $block_arr, 0);
		}


		private function getMenuTemplates($templates, $curr_depth) {
			$suffix = "_level" . $curr_depth;

			$block = getArrayKey($templates, "menu_block" . $suffix);
			$line = getArrayKey($templates, "menu_line" . $suffix);
			$line_a = (array_key_exists("menu_line" . $suffix . "_a", $templates)) ? $templates["menu_line" . $suffix . "_a"] : $line;
			$line_in = (array_key_exists("menu_line" . $suffix . "_in", $templates)) ? $templates["menu_line" . $suffix . "_in"] : $line;

			$class = getArrayKey($templates, "menu_class" . $suffix . "");
			$class_last = getArrayKey($templates, "menu_class" . $suffix . "_last");


			if(!$block) {
				switch($curr_depth) {
					case 1: $suffix = "_fl"; break;
					case 2: $suffix = "_sl"; break;
				}
				$block = getArrayKey($templates, 'menu_block' . $suffix);
				$line = getArrayKey($templates, 'menu_line' . $suffix);
				$line_a = (array_key_exists("menu_line" . $suffix . "_a", $templates)) ? $templates["menu_line" . $suffix . "_a"] : $line;
				$line_in = (array_key_exists("menu_line" . $suffix . "_in", $templates)) ? $templates["menu_line" . $suffix . "_in"] : $line;
			}

			if(!($separator = getArrayKey($templates, 'separator' . $suffix))) {
				$separator = getArrayKey($templates, 'separator');
			}

			if(!($separator_last = getArrayKey($templates, 'separator_last' . $suffix))) {
				$separator_last = getArrayKey($templates, 'separator_last');
			}

			return array($block, $line, $line_a, $line_in, $separator, $separator_last, $class, $class_last);
		}


		private function build_menu($page_id, &$templates, $curr_depth = 0, $parent_alt_name = "/", $max_depth = 1) {
			static $childsCache = array();

			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();
			$config = mainConfiguration::getInstance();
			$social_module = cmsController::getInstance()->getModule("social_networks");

			if($social_module) {
				$social = $social_module->getCurrentSocial();
			}
			else {
				$social = false;
			}

			list(
				$template_block, $template_line, $template_line_a, $template_line_in, $separator, $separator_last, $class, $class_last
			) = $this->getMenuTemplates($templates, ($curr_depth + 1));

			if(isset($childsCache[$page_id])) {
				$result = $childsCache[$page_id];
			} else {
				$allow_visible = false;
				if($social) $allow_visible = true;
				$langId = false;
				if($page_id) {
					$parentElement = $hierarchy->getElement($page_id);
					if (! ($parentElement instanceOf umiHierarchyElement)) {
						return false;
					}
					$langId = $parentElement->getLangId();
				}
				$childs = $hierarchy->getChilds($page_id, false, $allow_visible, 1, false, false, $langId);
				if($childs === false) $childs = array();
				$result = array_keys($childs);
				$this->loadMenuItems($result);
				$childsCache[$page_id] = $result;
			}

			$sz = sizeof($result);
			if($sz == 0) {
				return "";
			}

			$arr_lines = array();
			$c = 0;

			$currentElementId = $cmsController->getCurrentElementId();
			$allParents = $hierarchy->getAllParents($currentElementId, true);

			foreach($result as $element_id) {
				if($social && !$social->isHierarchyAllowed($element_id)) {
					continue;
				}

				$element = $hierarchy->getElement($element_id);
				if (!$element) continue;
				$text = $element->getName();

				$link = $rawLink = $parent_alt_name . '/' . $element->getAltName();


				if ($config->get('seo', 'url-suffix.add')) {
					$link .= $config->get('seo', 'url-suffix');
				}

				if($template_line_in && $template_line_in != $template_line) {
					if ($max_depth > 1 && $this->isInPath($element_id, $templates, ($curr_depth + 1), $link, $max_depth - 1)) {
						$is_active = true;
						$line = $template_line_in;
					} else {
						$is_active = (in_array($element_id, $allParents) !== false);
						$line = ($is_active) ? $template_line_a : $template_line;
					}
				} else {
					$is_active = (in_array($element_id, $allParents) !== false);
					$line = ($is_active) ? $template_line_a : $template_line;
				}


				$sub_menu = '';
				if(strstr($line, "%sub_menu%") && $max_depth > 1) {
					if($element->getValue("show_submenu") && ($is_active || $element->getValue("is_expanded"))) {
						$sub_menu = $this->build_menu($element_id, $templates, ($curr_depth + 1), $rawLink, $max_depth - 1);
					}
				}

				if($element->getIsDefault()) {
					$link = $element->link;
				}

				$item_arr = array();
				$item_arr['@id'] = $element_id;
				$item_arr['@link'] = $link;
				$item_arr['@name'] = $text;
				$item_arr['@alt-name'] = $element->getAltName();
				$item_arr['xlink:href'] = "upage://" . $element_id;

				if ($this->isXSLTResultMode()) {
					if($max_depth > 1 && XSLT_NESTED_MENU && $element->getValue("show_submenu") && ($is_active || $element->getValue("is_expanded"))) {
					$xslt_submenu = $this->build_menu($element_id, $templates, ($curr_depth + 1), $rawLink, $max_depth - 1);
						if(is_array($xslt_submenu) && isset($xslt_submenu['items'], $xslt_submenu['items']['nodes:item']) && sizeof($xslt_submenu['items']['nodes:item'])) {
							$item_arr['items']['nodes:item'] = $xslt_submenu['items']['nodes:item'];
						}
					}
				}

				if(XSLT_NESTED_MENU != 2) {
					$item_arr['node:text'] = $text;
				}

				$item_arr['void:num'] = ($c+1);
				$item_arr['void:sub_menu'] = $sub_menu;
				$item_arr['void:separator'] = (($sz == ($c + 1)) && $separator_last) ? $separator_last : $separator;

				if($is_active) {
					$item_arr['attribute:status'] = "active";
				}

				$item_arr['class'] = ($sz > ($c + 1)) ? $class : $class_last;

				$arr_lines[] = self::parseTemplate($line, $item_arr, $element_id);

				$c++;
				$hierarchy->unloadElement($element_id);
			}

			$block_arr = array(
				'subnodes:items'	=> $arr_lines,
				'void:lines'		=> $arr_lines,
				'id'				=> $page_id
			);
			return self::parseTemplate($template_block, $block_arr, $page_id);
		}

		private function loadMenuItems($pageIds) {
			if (!$pageIds) {
				return false;
			}
			if (!is_array($pageIds)) {
				$pageIds = array($pageIds);
			}
			if (count($pageIds) == 0) {
				return false;
			}
			$this->loadElements($pageIds);
			return true;
		}

		private function isInPath($pageId) {
			static $cache;

			if (isset($cache[$pageId])) {
				return (bool) $cache[$pageId];
			}

			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();
			$currentElementId = $cmsController->getCurrentElementId();

			$is_active_child = false;
			$children = array_keys($hierarchy->getChilds($pageId));
			$this->loadMenuItems($children);
			foreach($children as $elementId) {
				$element = $hierarchy->getElement($elementId);
				if(!$element || !$element->getIsActive()) continue;

				$is_active_child |= (in_array($elementId, $hierarchy->getAllParents($currentElementId, true)) !== false);
			}
			return $cache[$pageId] = $is_active_child;
		}

		/**
		 * Добавляет страницу к списку последних просмотреных страниц
		 *
		 * @param int $elementId Текущая страница
		 * @param string $scope Тэг(группировка страниц)
		 *
		 * @return null
		 */
		public function addRecentPage($elementId, $scope = "default") {
			if(!$scope) $scope = "default";

			if ($elementId != cmsController::getInstance()->getCurrentElementId()) return null;

			$limit = mainConfiguration::getInstance()->get("modules", "content.recent-pages.max-items");
			$limit = $limit ? $limit : 100;

			session::getInstance();
			if (!isset($_SESSION['content:recent_pages'])) {
				$_SESSION['content:recent_pages'] = array();
			}
			if(!isset($_SESSION['content:recent_pages'][$scope])) {
				$_SESSION['content:recent_pages'][$scope] = array();
			}

			$_SESSION['content:recent_pages'][$scope][$elementId] = time();

			asort($_SESSION['content:recent_pages'][$scope]);
			$_SESSION['content:recent_pages'][$scope] = array_reverse($_SESSION['content:recent_pages'][$scope], true);
			$_SESSION['content:recent_pages'][$scope] = array_slice($_SESSION['content:recent_pages'][$scope], 0, $limit, true);

			return null;
		}

		/**
		 * Получение списка последних просмотренных страниц
		 *
		 * @param string $template Шаблон для вывода
		 *
		 * @param string $scope Тэг(группировка страниц), без пробелов и запятых
		 * @param bool $showCurrentElement Если false - текущая страница не будет включена в результат
		 * @param int|null $limit Количество выводимых элементов
		 *
		 * @return mixed
		 */
		public function getRecentPages($template = "default", $scope = "default", $showCurrentElement = false, $limit = null) {
			if(!$scope) $scope = "default";
			$hierarchy = umiHierarchy::getInstance();

			$currentElementId = cmsController::getInstance()->getCurrentElementId();

			list($itemsTemplate, $itemTemplate) = def_module::loadTemplates("content/" . $template, "items", "item");

			session::getInstance();

			$items = array();

			if(isset($_SESSION['content:recent_pages'][$scope])) {
				$pagesIds = array();
				foreach ($_SESSION['content:recent_pages'][$scope] as $recentPage) {
					$pagesIds[] = $recentPage;
				}
				$this->loadElements($pagesIds);
				foreach ($_SESSION['content:recent_pages'][$scope] as $recentPage => $time) {
					$element = $hierarchy->getElement($recentPage, true);

					if (! ($element instanceOf umiHierarchyElement)) {
						continue;
					}

					if (!$showCurrentElement && $element->getId() == $currentElementId) {
						continue;
					} elseif (!is_null($limit) && $limit <= 0) {
						break;
					} elseif(!is_null($limit)) {
						$limit--;
					}

					$items[] = self::parseTemplate($itemTemplate, array(
						'@id' => $element->getId(),
						'@link' => $element->link,
						'@name' => $element->getName(),
						'@alt-name' => $element->getAltName(),
						'@xlink:href' => "upage://" . $element->getId(),
						'@last-view-time' => $time,
						'node:text' => $element->getName()
					), $element->getId());
				}
			}

			return self::parseTemplate($itemsTemplate, array("subnodes:items" => $items));
		}

		/**
		 * Удаляет страницу из списка последних использований
		 *
		 * @param int $elementId Id страницы
		 * @param string $scope Тэг
		 *
		 * @return bool
		 */
		public function delRecentPage($elementId, $scope = "default") {
			if(!$scope) $scope = "default";
			unset($_SESSION['content:recent_pages'][$scope][$elementId]);
			parent::redirect(getServer('HTTP_REFERER'));
		}

		/**
		 * Получает список режимов отображения
		 * Текущий помечается как current
		 *
		 * @param string $template TPL шаблон
		 *
		 * @return mixed
		 */
		public function getMobileModesList($template = "default") {
			$isMobile = (bool) system_is_mobile();
			$modes = array(
				"is_mobile" => 1,
				"is_desktop" => 0
			);

			$items = array();
			foreach ($modes as $mode => $value) {
				$itemArray = array (
					"@name" => $mode,
					"@link" => '/content/setMobileMode/' . ($value ? 0 : 1),
				);

				if ($value == $isMobile) {
					$itemArray["@status"] = "active";
					$items[] = def_module::renderTemplate("content/mobile/" . $template, $mode, $itemArray);
				} else {
					$items[] = def_module::parseTemplate("", $itemArray);;
				}
			}

			return def_module::renderTemplate("content/mobile/" . $template, "modes", array(
				"subnodes:items" => $items
			));
		}

		/**
		 * Устанавливает режим отображения сайта
		 * @internal
		 *
		 * @param bool $isMobile Режим
		 */
		public function setMobileMode($isMobile = null) {
			if(is_null($isMobile)) {
				$isMobile = getRequest('param0');
			}
			if ($isMobile == 1) {
				setcookie ("is_mobile", "1", null, "/");
			} elseif ($isMobile == 0) {
				setcookie ("is_mobile", "0", null, "/");
			}
			parent::redirect(getServer('HTTP_REFERER'));
		}
	};
?>
