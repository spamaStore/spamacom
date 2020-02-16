<?php
class ModelExtensionPaymentPaytabsexpress extends Model {
	private $paytabs_api = "https://www.paytabs.com/apiv2/";
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/paytabsexpress');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_paytabsexpress_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('payment_paytabsexpress_total') > 0 && $this->config->get('payment_paytabsexpress_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('payment_paytabsexpress_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'paytabsexpress',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_paytabsexpress_sort_order')
			);
		}

		return $method_data;
	}


	public function verify_payment($ref_code){
		$api_link = $this->paytabs_api.'verify_payment_transaction';
		$merchant_email = $this->config->get('payment_paytabsexpress_merchant_email');
		$merchant_secret_key = $this->config->get('payment_paytabsexpress_merchant_secret_key');
		$response = $this->_curl($api_link,['merchant_email'=>$merchant_email,'secret_key'=>$merchant_secret_key,'transaction_id'=>$ref_code]);

		$resp = json_decode($response,true);
		return $resp;
	}

	private function _curl($link,$post_data = []){
			$ch = curl_init($link);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			if($post_data)
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			// execute!
			$response = curl_exec($ch);
			// close the connection, release resources used
			curl_close($ch);

			return $response;
	}

}
