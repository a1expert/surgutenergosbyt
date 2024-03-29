<?php
	class backup extends def_module {
		public function __construct() {
			parent::__construct();

			$cmsController = cmsController::getInstance();
			$config = mainConfiguration::getInstance();

			$this->loadCommonExtension();

			if($cmsController->getCurrentMode() == 'admin') {
				$commonTabs = $this->getCommonTabs();
				if($commonTabs) {
					$commonTabs->add('config');
					$commonTabs->add('backup_copies');
				}

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__backup_custom_admin");
			}

			$this->__loadLib("__admin.php");
			$this->__implement("__backup");

			$this->loadSiteExtension();

			$this->__loadLib("__custom.php");
			$this->__implement("__backup_custom");
		}

		public function config() {
			return __backup::config();
		}
	};
?>