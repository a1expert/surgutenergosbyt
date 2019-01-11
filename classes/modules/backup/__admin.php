<?php
/**
 * TODO Check and format all PHPDoc's
 * Enter description here ...
 *
 */
	abstract class __backup extends baseModuleAdmin {

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 * @param unknown_type $cparam
		 * @return string
		 */
		public function backup_panel($cparam = "") {
			if(!$cparam) {
				return "";
			}
			return backupModel::getInstance()->getChanges($cparam);
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		public function backup_panel_all() {
			return backupModel::getInstance()->getAllChanges();
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		public function rollback() {
			$revisionId = (int) getRequest('param0');
			backupModel::getInstance()->rollback($revisionId);
		}

		/**
		 * TODO PHPDoc
		 * TODO Корректные типы входных параметров
		 * Вызов создания точки восстановления для страницы $cparam
		 * @param Integer $cparam
		 * @param String $cmodule
		 * @param String $cmethod
		 */
		public function backup_save($cparam = "", $cmodule = "", $cmethod = "") {
			return backupModel::getInstance()->save($cparam, $cmodule, $cmethod);
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		public function config(){
			$regedit = regedit::getInstance();
			$backupDir = str_replace(CURRENT_WORKING_DIR, '', SYS_MANIFEST_PATH) . 'backup/';

			$params = Array(
				"backup" => Array(
					"boolean:enabled"	=> NULL,
					"int:max_timelimit"	=> NULL,
					"int:max_save_actions"	=> NULL
				)
			);

			$mode = getRequest("param0");

			if ($mode == "do") {
				if (!is_demo()) {
					$params = $this->expectParams($params);

					$regedit->setVar("//modules/backup/enabled", $params['backup']['boolean:enabled']);
					$regedit->setVar("//modules/backup/max_timelimit", $params['backup']['int:max_timelimit']);
					$regedit->setVar("//modules/backup/max_save_actions", $params['backup']['int:max_save_actions']);

					$this->chooseRedirect();
				}
			}

			$this->setDataType("settings");
			$this->setActionType("modify");

			$params['backup']['boolean:enabled'] = $regedit->getVal("//modules/backup/enabled");
			$params['backup']['int:max_timelimit'] = $regedit->getVal("//modules/backup/max_timelimit");
			$params['backup']['int:max_save_actions'] = $regedit->getVal("//modules/backup/max_save_actions");


			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		public function backup_copies(){
			$regedit = regedit::getInstance();
			$backupDir = str_replace(CURRENT_WORKING_DIR, '', SYS_MANIFEST_PATH) . 'backup/';

			$params = Array(
				"snapshots" => Array(
					"status:backup-directory" => $backupDir
				)
			);

			$ent = getRequest('ent');
			if (!$ent) {
				$ent = time();
				$this->redirect($this->pre_lang . '/admin/backup/backup_copies/?ent=' . $ent);
			}

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		public function createSnapshot() {
			$buffer = outputBuffer::current();
			$buffer->contentType('text/javascript');
			$buffer->charset('utf-8');
			$buffer->clear();
			$buffer->option('comression', false);

			$location = $this->pre_lang . '/admin/backup/backup_copies/';

			if (defined("CURRENT_VERSION_LINE") && false) {
				if (is_demo()) {
					$err = getLabel('error-disabled-in-demo');
					$buffer->push("alert('{$err}');window.location = '{$location}';");
					$buffer->end();
				}
			}

			$mcfg = new baseXmlConfig(SYS_KERNEL_PATH . "subsystems/manifest/manifests/MakeSystemBackup.xml");
			$manifest = new manifest($mcfg);
			$manifest->hibernationsCountLeft = -1;
			$manifest->setCallback(new jsonManifestCallback());
			$manifest->execute();
			unset($manifest);

			$buffer->push("\nwindow.location = '{$location}';\n");
			$buffer->end();
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		public function deleteSnapshot() {
			$fileName = getRequest('filename');

			if (!is_demo()) {
				$dir = new umiDirectory(SYS_MANIFEST_PATH . 'backup/');
				foreach($dir as $item) {
					if($item instanceof umiFile) {
						if($item->getFileName() == $fileName) {
							$item->delete();
							break;
						}
					}
				}
			}
			$this->chooseRedirect($this->pre_lang . '/admin/backup/backup_copies/');
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		public function restoreSnapshot() {
			$buffer = outputBuffer::current();
			$buffer->contentType('text/javascript');
			$buffer->charset('utf-8');
			$buffer->clear();
			$buffer->option('comression', false);

			$location = $this->pre_lang . '/admin/backup/backup_copies/';

			if(defined("CURRENT_VERSION_LINE") && false) {
				if(is_demo()) {
					$err = getLabel('error-disabled-in-demo');
					$buffer->push("alert('{$err}');window.location = '{$location}';");
					$buffer->end();
				}
			}

/*			if($res = manifest::unhibernate('RestoreSystemBackup')) {
				echo $res;
			} else {*/
				$mcfg = new baseXmlConfig(SYS_KERNEL_PATH . "subsystems/manifest/manifests/RestoreSystemBackup.xml");
				$manifest = new manifest($mcfg);
				$manifest->hibernationsCountLeft = -1;
				$manifest->addParam('external-archive-filepath', getRequest('filename'));
				$manifest->setCallback(new jsonManifestCallback());
				$manifest->execute();
			//}
			echo '';
			$buffer->push("\nwindow.location = '{$location}';\n");
			$buffer->end();
			exit();
		}

		/**
		 * TODO PHPDoc
		 * Enter description here ...
		 */
		public function downloadSnapshot() {
			$fileName = getRequest('filename');

			$dir = new umiDirectory(SYS_MANIFEST_PATH . 'backup/');
			foreach ($dir as $item) {
				if ($item instanceof umiFile) {
					if ($item->getFileName() == $fileName) {
						$item->download();
						break;
					}
				}
			}

			$this->chooseRedirect($this->pre_lang . '/admin/backup/backup_copies/');
		}
	};
?>