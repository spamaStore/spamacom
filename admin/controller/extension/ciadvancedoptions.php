<?php
class ControllerExtensionCiAdvancedOptions extends Controller {
	private $error = array();

	public function index() {
		if(isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		} else {
			$store_id = 0;
		}

		$this->load->language('extension/ciadvancedoptions');
		
		$this->load->model('setting/setting');

		$this->load->model('extension/ciadvancedoptions');

		$this->model_extension_ciadvancedoptions->BuiltTable();

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('ciadvancedoptions', $this->request->post, $store_id);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/ciadvancedoptions', 'user_token=' . $this->session->data['user_token'] . '&store_id='. $store_id, true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['legend_extension'] = $this->language->get('legend_extension');
		$data['legend_option_price'] = $this->language->get('legend_option_price');
		$data['legend_description'] = $this->language->get('legend_description');
		$data['legend_model'] = $this->language->get('legend_model');
		$data['legend_sku'] = $this->language->get('legend_sku');
		$data['legend_image'] = $this->language->get('legend_image');
		$data['legend_customergroup'] = $this->language->get('legend_customergroup');

		$data['alert_extension'] = $this->language->get('alert_extension');
		$data['alert_option_price'] = $this->language->get('alert_option_price');
		$data['alert_description'] = $this->language->get('alert_description');
		$data['alert_model'] = $this->language->get('alert_model');
		$data['alert_sku'] = $this->language->get('alert_sku');
		$data['alert_image'] = $this->language->get('alert_image');
		$data['alert_customergroup'] = $this->language->get('alert_customergroup');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_module_status'] = $this->language->get('entry_module_status');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/ciadvancedoptions', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['store_id'] = $store_id;
		if(isset($store_id)) {
			$data['action'] = $this->url->link('extension/ciadvancedoptions', 'user_token=' . $this->session->data['user_token'] . '&store_id='. $store_id, true);
		} else{
			$data['action'] = $this->url->link('extension/ciadvancedoptions', 'user_token=' . $this->session->data['user_token'], true);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('setting/store');
		$data['stores'] = $this->model_setting_store->getStores();

		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			$module_info = $this->model_setting_setting->getSetting('ciadvancedoptions',  $store_id);
		}

		if (isset($this->request->post['ciadvancedoptions_status'])) {
			$data['ciadvancedoptions_status'] = $this->request->post['ciadvancedoptions_status'];
		} else if (isset($module_info['ciadvancedoptions_status'])) {
			$data['ciadvancedoptions_status'] = $module_info['ciadvancedoptions_status'];
		} else {
			$data['ciadvancedoptions_status'] = '';
		}

		if (isset($this->request->post['ciadvancedoptions_option_price_status'])) {
			$data['ciadvancedoptions_option_price_status'] = $this->request->post['ciadvancedoptions_option_price_status'];
		} else if (isset($module_info['ciadvancedoptions_option_price_status'])) {
			$data['ciadvancedoptions_option_price_status'] = $module_info['ciadvancedoptions_option_price_status'];
		} else {
			$data['ciadvancedoptions_option_price_status'] = '';
		}

		if (isset($this->request->post['ciadvancedoptions_option_sku_status'])) {
			$data['ciadvancedoptions_option_sku_status'] = $this->request->post['ciadvancedoptions_option_sku_status'];
		} else if (isset($module_info['ciadvancedoptions_option_sku_status'])) {
			$data['ciadvancedoptions_option_sku_status'] = $module_info['ciadvancedoptions_option_sku_status'];
		} else {
			$data['ciadvancedoptions_option_sku_status'] = '';
		}

		if (isset($this->request->post['ciadvancedoptions_option_image_status'])) {
			$data['ciadvancedoptions_option_image_status'] = $this->request->post['ciadvancedoptions_option_image_status'];
		} else if (isset($module_info['ciadvancedoptions_option_image_status'])) {
			$data['ciadvancedoptions_option_image_status'] = $module_info['ciadvancedoptions_option_image_status'];
		} else {
			$data['ciadvancedoptions_option_image_status'] = '';
		}

		if (isset($this->request->post['ciadvancedoptions_option_model_status'])) {
			$data['ciadvancedoptions_option_model_status'] = $this->request->post['ciadvancedoptions_option_model_status'];
		} else if (isset($module_info['ciadvancedoptions_option_model_status'])) {
			$data['ciadvancedoptions_option_model_status'] = $module_info['ciadvancedoptions_option_model_status'];
		} else {
			$data['ciadvancedoptions_option_model_status'] = '';
		}

		if (isset($this->request->post['ciadvancedoptions_option_description_status'])) {
			$data['ciadvancedoptions_option_description_status'] = $this->request->post['ciadvancedoptions_option_description_status'];
		} else if (isset($module_info['ciadvancedoptions_option_description_status'])) {
			$data['ciadvancedoptions_option_description_status'] = $module_info['ciadvancedoptions_option_description_status'];
		} else {
			$data['ciadvancedoptions_option_description_status'] = '';
		}

		if (isset($this->request->post['ciadvancedoptions_customer_group_status'])) {
			$data['ciadvancedoptions_customer_group_status'] = $this->request->post['ciadvancedoptions_customer_group_status'];
		}else if (isset($module_info['ciadvancedoptions_customer_group_status'])) {
			$data['ciadvancedoptions_customer_group_status'] = $module_info['ciadvancedoptions_customer_group_status'];
		} else {
			$data['ciadvancedoptions_customer_group_status'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/ciadvancedoptions', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/ciadvancedoptions')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}