<?php
class ModelExtensionModuleEeTracking extends Model {

	public function install() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ee_order_to_client`");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ee_order_to_client` (
          `order_id` int(11) NOT NULL,
          `client_id` varchar(64) NOT NULL,
          `sent` tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (`order_id`, `client_id`, `sent`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ee_click_to_client`");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ee_click_to_client` (
          `product_id` int(11) NOT NULL,
          `client_id` varchar(64) NOT NULL,
          PRIMARY KEY (`product_id`,`client_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

		$this->db->query("UPDATE `" . DB_PREFIX . "modification` SET status=1 WHERE `name` LIKE '%Enhanced E-Commerce Tracking%'");
		
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "modification` WHERE `name` LIKE '%Enhanced E-Commerce Tracking%'");
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ee_order_to_client`");
		
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ee_click_to_client`");

		if ($this->getTableExist('ee_order_to_client')) {
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ee_order_to_client`");
		}
		if ($this->getTableExist('ee_click_to_client')) {
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ee_click_to_client`");
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "modification` SET status=0 WHERE `name` LIKE '%Enhanced E-Commerce Tracking%'");
		
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "modification` WHERE `name` LIKE '%Enhanced E-Commerce Tracking%'");
	}

	public function getModification() {
		$query = $this->db->query("SELECT name, version FROM `" . DB_PREFIX . "modification` WHERE `name` LIKE '%Enhanced E-Commerce Tracking%' and `status` = 1");

		return $query->row;
	}

	public function getTableExist($table_name) {
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . $table_name . "'");
		
		return $query->num_rows;
	}

	public function getTableColumns($table_name) {
		$query = $this->db->query("SELECT `COLUMN_NAME` as name FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_NAME`='" . DB_PREFIX . $table_name . "' AND `TABLE_SCHEMA` = '" . DB_DATABASE . "'");
		
		$columns = array();
		
		if ($query->num_rows) {
			foreach ($query->rows as $column) {
				$columns[] = $column['name'];
			}
		}
		
		return $columns;
	}
}