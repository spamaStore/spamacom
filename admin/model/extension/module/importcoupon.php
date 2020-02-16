<?php
class ModelExtensionModuleImportCoupon extends Model {


	public function bulkAddCoupon($sample) {
		
		foreach ($sample as $key => $data) {
			
	        if($data['discount'] < 0) {
	            $data['discount'] = 0;  
	        }
	        $coupon_info = array();
	        if($data['code'] != "" && (strlen($data['code']) <=10 )) {
	        	$coupon_info = $this->getCouponByCode($data['code']);
	        }
	        if (empty($coupon_info) && $data['code'] != "") {
		        $this->db->query("INSERT INTO " . DB_PREFIX . "coupon SET name = '" . $this->db->escape($data['name']) . "', code = '" . $this->db->escape($data['code']) . "', discount = '" . (float)$data['discount'] . "', type = '" . $this->db->escape($data['type']) . "', total = '" . (float)$data['total'] . "', logged = '" . (int)$data['logged'] . "', shipping = '" . (int)$data['free-shipping'] . "', date_start = '" . $this->db->escape(date('Y-m-d', strtotime(str_replace('/', '-', $data['date_start'])))) . "', date_end = '" . $this->db->escape(date('Y-m-d', strtotime(str_replace('/', '-', $data['date_end'])))) . "', uses_total = '" . (int)$data['uses_total'] . "', uses_customer = '" . (int)$data['uses_customer'] . "', status = '" . (int)$data['status'] . "', date_added = NOW()");

		        $coupon_id = $this->db->getLastId();

		        if(!empty($data['customer_group_id'])){

		            $customer_group_ids = explode(':', $data['customer_group_id']);
		            
		            foreach($customer_group_ids as $customer_group_id) {
		                $sql = "INSERT INTO `" . DB_PREFIX . "coupon_to_customergroup` (coupon_id, customer_group_id) VALUES
		                        ('" . (int)$coupon_id . "', '" . (int)$customer_group_id. "')";
		                $this->db->query($sql);
		        	}   
		        }

		        if(!empty($data['product_id'])){

		            $products = explode(':', $data['product_id']);
		            
		            foreach($products as $productId) {
		                $sql = "INSERT INTO `" . DB_PREFIX . "coupon_product` (coupon_id, product_id) VALUES
		                        ('" . (int)$coupon_id . "', '" . (int)$productId. "')";
		                $this->db->query($sql);
		        	}   
		        }

		        if(!empty($data['category_id'])){

		            $categories = explode(':', $data['category_id']);
		            
		            foreach($categories as $category_id) {
		                $sql = "INSERT INTO `" . DB_PREFIX . "coupon_category` (coupon_id, category_id) VALUES
		                        ('" . (int)$coupon_id . "', '" . (int)$category_id. "')";
		                $this->db->query($sql);
		        	}   
		        }

	       }
	    }  
	}

	public function getCoupons($data = array()) {
		
		$sql = "SELECT c.coupon_id, c.name, c.code, c.discount, c.date_start, c.date_end, c.status FROM " . DB_PREFIX . "coupon c ";

		if (!empty($data['filter_productid'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "coupon_product cp on (c.coupon_id = cp.coupon_id) ";
		}

		if (!empty($data['filter_categoryid'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "coupon_category cc on (c.coupon_id = cc.coupon_id) ";
		}

		$sql .= " WHERE 1 ";

		if (!empty($data['filter_name'])) {
			$sql .= " AND c.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_code'])) {
			$sql .= " AND c.code LIKE '" . $this->db->escape($data['filter_code']) . "%'";
		}

		if (isset($data['filter_discount']) && !is_null($data['filter_discount'])) {
			$sql .= " AND c.discount LIKE '" . $this->db->escape($data['filter_discount']) . "%'";
		}

		if (isset($data['filter_productid']) && !is_null($data['filter_productid'])) {
			$sql .= " AND cp.product_id = '" . (int)$data['filter_productid'] . "'";
		}

		if (isset($data['filter_date']) && !is_null($data['filter_date'])) {
			$sql .= " AND c.date_start <= '" . $data['filter_date'] . "' AND c.date_end >= '".$data['filter_date']."'";
		}

		if (isset($data['filter_datestart']) && !is_null($data['filter_datestart'])) {
			$sql .= " AND c.date_start >= '" . $data['filter_datestart'] . "'";
		}

		if (isset($data['filter_dateend']) && !is_null($data['filter_dateend'])) {
			$sql .= " AND c.date_end <= '" . $data['filter_dateend'] . "'";
		}

		if (isset($data['filter_categoryid']) && !is_null($data['filter_categoryid'])) {
			$sql .= " AND cc.category_id = '" . (int)$data['filter_categoryid'] . "'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND c.status = '" . (int)$data['filter_status'] . "'";
		}

		$sql .= " GROUP BY c.coupon_id";

		$sort_data = array(
			'name',
			'code',
			'discount',
			'date_start',
			'date_end',
			'status'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY c.name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}
		
		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalCoupons($data = array()) {
		$sql = "SELECT  COUNT(*) AS total FROM " . DB_PREFIX . "coupon c ";

		if (!empty($data['filter_productid'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "coupon_product cp on (c.coupon_id = cp.coupon_id) ";
		}

		if (!empty($data['filter_categoryid'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "coupon_category cc on (c.coupon_id = cc.coupon_id) ";
		}

		$sql .= " WHERE 1 ";

		if (!empty($data['filter_name'])) {
			$sql .= " AND c.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_code'])) {
			$sql .= " AND c.code LIKE '" . $this->db->escape($data['filter_code']) . "%'";
		}

		if (isset($data['filter_discount']) && !is_null($data['filter_discount'])) {
			$sql .= " AND c.discount LIKE '" . $this->db->escape($data['filter_discount']) . "%'";
		}

		if (isset($data['filter_productid']) && !is_null($data['filter_productid'])) {
			$sql .= " AND cp.product_id = '" . (int)$data['filter_productid'] . "'";
		}

		if (isset($data['filter_date']) && !is_null($data['filter_date'])) {
			$sql .= " AND c.date_start <= '" . $data['filter_date'] . "' AND c.date_end >= '".$data['filter_date']."'";
		}

		if (isset($data['filter_datestart']) && !is_null($data['filter_datestart'])) {
			$sql .= " AND c.date_start >= '" . $data['filter_datestart'] . "'";
		}

		if (isset($data['filter_dateend']) && !is_null($data['filter_dateend'])) {
			$sql .= " AND c.date_end <= '" . $data['filter_dateend'] . "'";
		}

		if (isset($data['filter_categoryid']) && !is_null($data['filter_categoryid'])) {
			$sql .= " AND cc.category_id = '" . (int)$data['filter_categoryid'] . "'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND c.status = '" . (int)$data['filter_status'] . "'";
		}


		$query = $this->db->query($sql);

		return $query->row['total'];
	}
	
	public function deleteallCoupon() {
      	$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "coupon");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "coupon_product");		
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "coupon_history");	
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "coupon_category");
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "coupon_to_customergroup");		
	}

	public function getCoupon($coupon_id) {
      	$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "coupon WHERE coupon_id = '" . (int)$coupon_id . "'");
		
		return $query->row;
	}

	public function getlastid() {
      	$query = $this->db->query("SELECT coupon_id FROM " . DB_PREFIX . "coupon ORDER by coupon_id DESC LIMIT 1");
		
		return isset($query->row['coupon_id'])?$query->row['coupon_id']:0;
	}

	public function getCouponByCode($code) {
      	$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "coupon WHERE code = '" . $this->db->escape($code) . "'");
		
		return $query->row;
	}

	public function getCouponCustomerGroups($coupon_id) {
		$coupon_customergroup_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "coupon_to_customergroup WHERE coupon_id = '" . (int)$coupon_id . "'");

		foreach ($query->rows as $result) {
			$coupon_customergroup_data[] = $result['customer_group_id'];
		}

		return $coupon_customergroup_data;
	}

	public function createTable() {
		if ($this->db->query("SHOW TABLES LIKE '". DB_PREFIX ."coupon_to_customergroup'")->num_rows == 0) {
            $sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "coupon_to_customergroup` (
                      `coupon_id` int(11) NOT NULL,
					  `customer_group_id` int(11) NOT NULL,
					  PRIMARY KEY (`coupon_id`,`customer_group_id`),
					  KEY `customer_group_id` (`customer_group_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
            $this->db->query($sql);
        }
	}
}
?>