<?php
	interface iTemplater {
		public function init($input);

		public function loadLangs();
		public function putLangs($input);

		public function parseInput($input);

		public function parseMacros($macrosStr);
		public function executeMacros($macrosArr);

		public static function pushEditable($module, $method, $id);
		
		public function cleanUpResult($input);
	}
?>