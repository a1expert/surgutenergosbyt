<?php
	interface iXslTemplater {

		public function init($xsltFileName = false);

		public function getIsInited();

		public function getXmlDocument();

		public function parseResult();
		public function flushXml();

	};
?>