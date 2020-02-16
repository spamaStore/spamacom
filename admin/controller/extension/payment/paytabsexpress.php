<?php
class ControllerExtensionPaymentPaytabsexpress extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/paytabsexpress');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_paytabsexpress', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['merchant_id'])) {
			$data['error_merchant_id'] = $this->error['merchant_id'];
		} else {
			$data['error_merchant_id'] = '';
		}

		if (isset($this->error['merchant_email'])) {
			$data['error_merchant_email'] = $this->error['merchant_email'];
		} else {
			$data['error_merchant_email'] = '';
		}

		if (isset($this->error['merchant_secret_key'])) {
			$data['error_merchant_secret_key'] = $this->error['merchant_secret_key'];
		} else {
			$data['error_merchant_secret_key'] = '';
		}

		if (isset($this->error['secure_sign'])) {
			$data['error_secure_sign'] = $this->error['secure_sign'];
		} else {
			$data['error_secure_sign'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/paytabsexpress', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/paytabsexpress', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_paytabsexpress_merchant_id'])) {
			$data['payment_paytabsexpress_merchant_id'] = $this->request->post['payment_paytabsexpress_merchant_id'];
		} else {
			$data['payment_paytabsexpress_merchant_id'] = $this->config->get('payment_paytabsexpress_merchant_id');
		}

		if (isset($this->request->post['payment_paytabsexpress_merchant_secret_key'])) {
			$data['payment_paytabsexpress_merchant_secret_key'] = $this->request->post['payment_paytabsexpress_merchant_secret_key'];
		} else {
			$data['payment_paytabsexpress_merchant_secret_key'] = $this->config->get('payment_paytabsexpress_merchant_secret_key');
		}

		if (isset($this->request->post['payment_paytabsexpress_merchant_email'])) {
			$data['payment_paytabsexpress_merchant_email'] = $this->request->post['payment_paytabsexpress_merchant_email'];
		} else {
			$data['payment_paytabsexpress_merchant_email'] = $this->config->get('payment_paytabsexpress_merchant_email');
		}
		

		if (isset($this->request->post['payment_paytabsexpress_total'])) {
			$data['payment_paytabsexpress_total'] = $this->request->post['payment_paytabsexpress_total'];
		} else {
			$data['payment_paytabsexpress_total'] = $this->config->get('payment_paytabsexpress_total');
		}

		if (isset($this->request->post['payment_paytabsexpress_order_status_id'])) {
			$data['payment_paytabsexpress_order_status_id'] = $this->request->post['payment_paytabsexpress_order_status_id'];
		} else {
			$data['payment_paytabsexpress_order_status_id'] = $this->config->get('payment_paytabsexpress_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_paytabsexpress_geo_zone_id'])) {
			$data['payment_paytabsexpress_geo_zone_id'] = $this->request->post['payment_paytabsexpress_geo_zone_id'];
		} else {
			$data['payment_paytabsexpress_geo_zone_id'] = $this->config->get('payment_paytabsexpress_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_paytabsexpress_secure_sign'])) {
			$data['payment_paytabsexpress_secure_sign'] = $this->request->post['payment_paytabsexpress_secure_sign'];
		} else {
			$data['payment_paytabsexpress_secure_sign'] = $this->config->get('payment_paytabsexpress_secure_sign');
		}

		if (isset($this->request->post['payment_paytabsexpress_status'])) {
			$data['payment_paytabsexpress_status'] = $this->request->post['payment_paytabsexpress_status'];
		} else {
			$data['payment_paytabsexpress_status'] = $this->config->get('payment_paytabsexpress_status');
		}

		if (isset($this->request->post['payment_paytabsexpress_sort_order'])) {
			$data['payment_paytabsexpress_sort_order'] = $this->request->post['payment_paytabsexpress_sort_order'];
		} else {
			$data['payment_paytabsexpress_sort_order'] = $this->config->get('payment_paytabsexpress_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/paytabsexpress', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/paytabsexpress')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_paytabsexpress_merchant_email']) {
			$this->error['merchant_email'] = $this->language->get('error_merchant_email');
		}

		if (!$this->request->post['payment_paytabsexpress_merchant_id']) {
			$this->error['merchant_id'] = $this->language->get('error_merchant_id');
		}

		if (!$this->request->post['payment_paytabsexpress_merchant_secret_key']) {
			$this->error['merchant_secret_key'] = $this->language->get('error_merchant_secret_key');
		}

		// if (!$this->request->post['payment_paytabsexpress_secure_sign']) {
		// 	$this->error['secure_sign'] = $this->language->get('error_secure_sign');
		// }
		
	
		return !$this->error;
	}
}
