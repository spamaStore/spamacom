<?php
class ModelExtensionCiAdvancedOptions extends Model {
	public function BuiltTable() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `". DB_PREFIX ."product_option_value_group` (`product_option_value_id` int(11) NOT NULL, `customer_group_id` int(11) NOT NULL, `product_id` int(11) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

		$query = $this->db->query("SHOW COLUMNS FROM `". DB_PREFIX ."option_description` WHERE `Field` = 'description'");
		if(!$query->num_rows) {
			$this->db->query("ALTER TABLE `". DB_PREFIX ."option_description` ADD `description` text NOT NULL AFTER `name`");
		}


		$query = $this->db->query("SHOW COLUMNS FROM `". DB_PREFIX ."product_option_value` WHERE `Field` = 'ciopmodel'");
		if(!$query->num_rows) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_option_value ADD ciopmodel varchar(255) NOT NULL AFTER points_prefix");
		}

		$query = $this->db->query("SHOW COLUMNS FROM `". DB_PREFIX ."product_option_value` WHERE `Field` = 'ciopsku'");
		if(!$query->num_rows) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_option_value ADD ciopsku varchar(255) NOT NULL AFTER points_prefix");
		}

		$query = $this->db->query("SHOW COLUMNS FROM `". DB_PREFIX ."product_option_value` WHERE `Field` = 'ciopimage'");
		if(!$query->num_rows) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_option_value ADD ciopimage varchar(255) NOT NULL AFTER points_prefix");
		}
	}
}