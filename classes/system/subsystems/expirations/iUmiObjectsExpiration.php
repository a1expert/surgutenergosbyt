<?php
	interface iUmiObjectsExpiration {
		public function run();
		public function add($objectId, $expiration = false);
		public function update($objectId, $expiration = false);
		public function clear($objectId);
	};
?>