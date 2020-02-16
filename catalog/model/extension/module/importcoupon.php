<?php
class ModelExtensionModuleImportCoupon extends Model {
	public function getCouponCustomerGroups($coupon_id) {
		$coupon_customergroup_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "coupon_to_customergroup WHERE coupon_id = '" . (int)$coupon_id . "'");

		foreach ($query->rows as $result) {
			$coupon_customergroup_data[] = $result['customer_group_id'];
		}

		return $coupon_customergroup_data;
	}
}
?>