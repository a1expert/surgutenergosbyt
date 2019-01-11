<?php
	class umiObjectsExpiration extends singleton implements iSingleton, iUmiObjectsExpiration {
		protected $defaultExpires = 86400;

		protected function __construct() {

		}

		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		public function isExpirationExists($objectId) {
			$sql = <<<SQL
			SELECT
				`obj_id`
			FROM
				`cms3_objects_expiration`
			WHERE
				`obj_id` = {$objectId}
			LIMIT 1
SQL;
			$res = l_mysql_query($sql);
			return mysql_num_rows($res) > 0;
		}

		public function getExpiredObjectsByTypeId($typeId, $limit = 50) {
			$time = time();

			$sql = <<<SQL
			SELECT
				`obj_id`
			FROM
				`cms3_objects_expiration`
			WHERE
				`obj_id`  IN (
					SELECT
						`id`
					FROM
						`cms3_objects`
					WHERE
						`type_id`='{$typeId}'
					)
				AND (`entrytime` +  `expire`) <= {$time}
			ORDER BY (`entrytime` +  `expire`)
			LIMIT {$limit}
SQL;

			$result = array();
			$res = l_mysql_query($sql);
			if (mysql_numrows($res) > 0) {
				while($row = mysql_fetch_assoc($res)) {
					$result[] = $row['obj_id'];
				}
			}

			return $result;
		}

		public function run() {
			return;
			$time = time();

			$sql = <<<SQL
DELETE FROM `cms3_objects`
	WHERE `id` IN (
		SELECT `obj_id`
			FROM `cms3_objects_expiration`
				WHERE (`entrytime` + `expire`) >= '{$time}'
	)
SQL;
			l_mysql_query($sql);
		}

		public function update($objectId, $expires = false) {
			if($expires == false) {
				$expires = $this->defaultExpires;
			}
			$objectId = (int) $objectId;
			$expires = (int) $expires;
			$time = time();
			$sql = <<<SQL
			UPDATE
				`cms3_objects_expiration`
			SET
				`entrytime`='{$time}',
				`expire`='{$expires}'
			WHERE
				`obj_id` = '{$objectId}'
SQL;
			l_mysql_query($sql);
		}

		public function add($objectId, $expires = false) {
			if($expires == false) {
				$expires = $this->defaultExpires;
			}
			$objectId = (int) $objectId;
			$expires = (int) $expires;
			$time = time();

			$sql = <<<SQL
INSERT INTO `cms3_objects_expiration`
	(`obj_id`, `entrytime`, `expire`)
		VALUES ('{$objectId}', '{$time}', '{$expires}')
SQL;
			l_mysql_query($sql);
		}

		public function clear($objectId) {
			$objectId = (int) $objectId;

			$sql = <<<SQL
DELETE FROM `cms3_objects_expiration`
	WHERE `obj_id` = '{$objectId}'
SQL;
			l_mysql_query($sql);
		}
	};
?>