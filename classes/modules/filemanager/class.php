<?php
	class filemanager extends def_module {
		public function __construct() {
			parent::__construct();

			$this->loadCommonExtension();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__filemanager");

				$this->__loadLib("__shared_files.php");
				$this->__implement("__shared_files");

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__filemanager_custom_admin");
			}

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__custom_filemanager");

			$this->per_page = 25;

			// define root path
			$this->s_root_path = realpath(CURRENT_WORKING_DIR);
			// fix for Win Os
			$this->s_root_path = str_replace("\\", "/", $this->s_root_path);
			while (substr($this->s_root_path, -1) == "/") $this->s_root_path = substr($this->s_root_path, 0, (strlen($this->s_root_path)-1));

		}



		public function list_files($element_id = false, $template = "default", $per_page = false, $ignore_paging = false, $depth = 1) {
			if(!$template) $template = "default";

			$depth = (int) $depth;
			if ( !$depth ) $depth = 1;

			list($template_block, $template_line) = def_module::loadTemplates("filemanager/".$template, "list_files", "list_files_row");

			$block_arr = Array();

			$element_id = $this->analyzeRequiredPath($element_id);

			if(!$per_page) $per_page = $this->per_page;
			$curr_page = (int) getRequest('p');
			if($ignore_paging) $curr_page = 0;

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('filemanager', 'shared_file');
			$sel->where('hierarchy')->page($element_id)->childs($depth);
			$sel->option('load-all-props')->value(true);
			$sel->limit($curr_page * $per_page, $per_page);

			$result = $sel->result();
			$total = $sel->length();

			$lines = Array();
			foreach($result as $element) {
				$line_arr = Array();

				$next_element_id = $element->getId();

				$line_arr['attribute:id'] = $element->getId();
				$line_arr['attribute:name'] = $element->getName();
				$line_arr['attribute:link'] = $this->umiLinksHelper->getLinkByParts($element);
				$line_arr['attribute:downloads-count'] = $element->getValue('downloads_counter');
				$line_arr['xlink:download-link'] = $this->pre_lang . "/filemanager/download/" . $next_element_id;
				$line_arr['xlink:href'] = "upage://" . $next_element_id;
				$line_arr['node:desc'] = $element->getValue("content");

				$this->pushEditable("filemanager", "shared_file", $next_element_id);

				$lines[] = self::parseTemplate($template_line, $line_arr, $next_element_id);
			}

			$block_arr['nodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['per_page'] = $per_page;
			$block_arr['total'] = $total;

			return self::parseTemplate($template_block, $block_arr);
		}

		public function shared_file($template = "default", $element_path = false) {
			if(!$template) $template = "default";
			list($s_download_file, $s_broken_file, $s_upload_file) = def_module::loadTemplates("filemanager/".$template, "shared_file", "broken_file", "upload_file");

			$element_id = $this->analyzeRequiredPath($element_path);
			$umiHierarchy = umiHierarchy::getInstance();
			$element = $umiHierarchy->getElement($element_id);

			$block_arr = Array();
			$template_block = $s_broken_file;
			if ($element) {
				$permissionsCollection = permissionsCollection::getInstance();
				$iUserId = $permissionsCollection->getUserId();
				list($bAllowRead, $bAllowWrite) = $permissionsCollection->isAllowedObject($iUserId, $element_id);
				$block_arr['upload_file'] = "";
				if ($bAllowWrite) {
					$block_arr['upload_file'] = $s_upload_file;
					// upload first file in $_FILES
					if (count($_FILES)) {
						$oUploadedFile = umiFile::upload("shared_files", "upload", "./files/");
						if ($oUploadedFile instanceof umiFile) {
							$element->setValue("fs_file", $oUploadedFile);
							$element->commit();
						}
					}
				}

				$block_arr['id'] = $element_id;
				$block_arr['descr'] = ($descr = $element->getValue("descr")) ? $descr : $element->getValue("content");
				$block_arr['alt_name'] = $element->getAltName();
				$block_arr['link'] = $umiHierarchy->getPathById($element_id);
				// file
				$block_arr['download_link'] = "";
				$block_arr['file_name'] = "";
				$block_arr['file_size'] = 0;

				$o_file = $element->getValue("fs_file");

				if ($o_file instanceof umiFile) {
					if (!$o_file->getIsBroken()) {
						$template_block = $s_download_file;
						$block_arr['download_link'] = $this->pre_lang."/filemanager/download/".$element_id;
						$block_arr['file_name'] = $o_file->getFileName();
						$block_arr['file_size'] = round($o_file->getSize()/1024, 2);
					}
				}
			} else {
				return $this->cmsController->getModule("users")->auth();
			}

			$this->pushEditable("filemanager", "shared_file", $element_id);

			return self::parseTemplate($template_block, $block_arr);
		}

		public function download() {
			$element_id = getRequest('param0');
			$element = umiHierarchy::getInstance()->getElement($element_id);

			define("DISABLE_STATIC_CACHE", 1);

			if ($element instanceof umiHierarchyElement) {
				$o_file = $element->getValue("fs_file");
				if ($o_file instanceof umiFile) {
					if (!$o_file->getIsBroken()) {
						// counter
						$i_downloads_counter = (int) $element->getValue("downloads_counter");
						$element->setValue("downloads_counter", ++$i_downloads_counter);
						$element->commit();

						$o_file->download();
						exit();
					} else {
						// broken file
					}
				}
			}

			return $this->cmsController->getModule("users")->auth();
		}

		public function getEditLink($element_id, $element_type) {
			switch($element_type) {
				case "shared_file": {
					$link_edit = $this->pre_lang . "/admin/filemanager/edit_shared_file/{$element_id}/";

					return Array(false, $link_edit);
					break;
				}

				default: {
					return false;
				}
			}
	}

	};
?>