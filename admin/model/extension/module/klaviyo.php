<?php
class ModelExtensionModuleKlaviyo extends Model {
  
  public function createTables() {
    $query = $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "klaviyo_cart (cart_id INT(11) AUTO_INCREMENT, session_id VARCHAR(32) NOT NULL, data TEXT NOT NULL, store_id INT(11) NOT NULL, store_name VARCHAR(64) NOT NULL, store_url VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX IX_" . DB_PREFIX . "klaviyo_cart_updated_at (updated_at, created_at), INDEX UQ_" . DB_PREFIX . "klaviyo_cart_session_id (session_id), PRIMARY KEY (cart_id))");
  }

  public function dropTables() {
    $query = $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "klaviyo_cart");
  }
}
?>