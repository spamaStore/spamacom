<?php
class ControllerExtensionFeedGoogleMerchantCenter extends Controller {
	private $error = array();

	public function install() {
		$this->load->model('extension/feed/feed_manager_taxonomy');
		$this->model_extension_feed_feed_manager_taxonomy->install();
	}

	public function index() {
		$this->language->load('extension/feed/google_merchant_center');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		$this->load->model('extension/feed/google_merchant_center');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('feed_google_merchant_center', $this->request->post);
			$this->model_extension_feed_google_merchant_center->saveSetting($this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			if ((int)str_replace('.','',VERSION)>=3000) {
                $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true));
            }
		}
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_feed'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/feed/google_merchant_center', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/feed/google_merchant_center', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true);
        $data['text_edit'] = $this->language->get('text_edit');
        //collect settings
        $switch = array(1 => $this->language->get('text_enabled'), 0 => $this->language->get('text_disabled'));
		$product_id1 = array('product_id' => $this->language->get('text_product_id'), 'model' => $this->language->get('text_model'));
		$product_options = array('option_id' => $this->language->get('text_option_id'), 'option_name' => $this->language->get('text_option_name'));

        $taxonomy_base = array();
        foreach ($this->model_extension_feed_google_merchant_center->getBaseCategory() as $base) {
            $taxonomy_base[$base['taxonomy_id']] = $base['name'];
        }
        $options_attributes = array();
        foreach ($this->model_extension_feed_google_merchant_center->getOptionID() as $value) {
            $option_id = 'o'.$value['option_id'];
            $options_attributes[$option_id]=$value['name'].$this->language->get('text_option');
        }
        foreach ($this->model_extension_feed_google_merchant_center->getAttributes() as $value) {
            $attribute_id = 'a'.$value['attribute_id'];
            $options_attributes[$attribute_id]=$value['name'].$this->language->get('text_attribute');
        }
        $image_sizes = array('direct' => $this->language->get('text_direct_image'));
        foreach ($this->model_extension_feed_google_merchant_center->getImageSizes($this->config->get('config_store_id'), 600) as $value) {
            $image_sizes[$value['wh']]=$value['wh'].' '.$value['name'].' ('.$value['theme'].')';
        }
        if (count($image_sizes) <= 1) {
            $image_sizes['600x600'] = '600x600 '.$this->language->get('text_minimal_image');
        }
        $languages = $this->model_extension_feed_google_merchant_center->getLanguages();
        $currencies = $this->model_extension_feed_google_merchant_center->getCurrencies();
        $availability = array('in stock' => $this->language->get('text_in_stock'), 'out of stock' => $this->language->get('text_out_of_stock'), 'preorder' => $this->language->get('text_preorder'), 'skip products' => $this->language->get('text_skip_products'));
        $prefix = 'feed_google_merchant_center_';
        //create twig settings
		$data['sm_status'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix,'status', $switch, 0);
		$data['sm_save_to_file'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix,'save_to_file', $switch, 0);
		$data['sm_base_taxonomy'] = $this->model_extension_feed_google_merchant_center->createMultiCheckboxSetting($prefix,'base_taxonomy', $taxonomy_base, array());
        $data['sm_size_options'] = $this->model_extension_feed_google_merchant_center->createMultiCheckboxSetting($prefix,'size_options', $options_attributes, array());
        $data['sm_color_options'] = $this->model_extension_feed_google_merchant_center->createMultiCheckboxSetting($prefix,'color_options', $options_attributes, array());
        $data['sm_pattern_options'] = $this->model_extension_feed_google_merchant_center->createMultiCheckboxSetting($prefix,'pattern_options', $options_attributes, array());
        $data['sm_material_options'] = $this->model_extension_feed_google_merchant_center->createMultiCheckboxSetting($prefix,'material_options', $options_attributes, array());
		$data['sm_clear_html'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix,'clear_html', $switch, 1);
        $data['sm_use_meta'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix,'use_meta', $switch, 1);
		$data['sm_pid1'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix,'pid1', $product_id1, 'product_id');
        $data['sm_option_ids'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix,'option_ids', $product_options, 'option_id');
        $data['sm_use_tax'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix, 'use_tax', $switch, 0);//disable tax USA,Canada and India
        $data['sm_image_cache'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix, 'image_cache', $image_sizes, null);
        $data['sm_disabled_products'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix, 'disabled_products', $availability, 'out of stock');
        $data['sm_sold_out_products'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix, 'sold_out_products', $availability, 'out of stock');
        $data['sm_language'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix, 'language', $languages, $this->config->get('config_language'));
        $data['sm_currency'] = $this->model_extension_feed_google_merchant_center->createCheckboxSetting($prefix, 'currency', $currencies, $this->config->get('config_currency'));
        $data['entry_feed_url'] = $this->language->get('entry_feed_url');
        $data['help_feed_url'] = $this->language->get('help_feed_url');
        $http_sep = '';
		if (substr(HTTP_CATALOG, -1) != '/') {
            $http_sep = '/';
        }
        $data['feed_url'] = HTTP_CATALOG .$http_sep. 'index.php?route=extension/feed/google_merchant_center';
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/feed/google_merchant_center', $data));
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/feed/google_merchant_center')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}
}
?>
