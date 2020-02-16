<?php

class ControllerExtensionModuleEeTracking extends Controller {
    private $error      = array();
    private $mName      = 'module_ee_tracking';
    private $mId        = '25965';
    private $mModel     = 'model_extension_module_ee_tracking';
    private $mPath      = 'extension/module/ee_tracking';
    private $mMainPath  = 'marketplace/extension';
    private $mToken     = 'user_token';
    private $mVersion   = '2.2.1';
    private $mSSL       = true;
    private $mCustom    = false;
    private $vqVersion  = false;

    public function index() {
        $this->document->addStyle('view/stylesheet/ee_tracking.min.css?v=' . $this->mVersion);

        $lang_ar = $this->load->language($this->mPath);

        $data = array();

        foreach ($lang_ar as $key => $item) {
            if (strpos($key, 'error_') === false) {
                $data[$key] = $item;
            } else {
                $data[$key] = '';
            }
        }

        $this->document->setTitle($this->language->get('heading_title_main'));

        $url = '';

        if (isset($this->request->get['store_id'])) {
            $data['store_id'] = $this->request->get['store_id'];
            $url .= '&store_id=' .  $this->request->get['store_id'];
        } else {
            $data['store_id'] = 0;
        }

        if (isset($this->request->get['translator'])) {
            $data['translator'] = $this->request->get['translator'];
            $url .= '&translator=' .  $this->request->get['translator'];
        }

        $this->load->model($this->mPath);
        $this->load->model('setting/setting');
        $this->load->model('setting/store');

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            $json = array();

            if ($this->validate()) {
                if ($data['store_id'] && !$this->config->get($this->mName . '_multistore')) {
                    $this->model_setting_setting->editSettingValue($this->mName, $this->mName . '_multistore', 1, 0);
                }

                foreach ($this->request->post as $key => $item) {
                    if (strpos($key, '_debug') !== false && $key != $this->mName . '_all_debug' && $item) {
                        if (isset($this->request->post[str_replace('debug', 'status', $key)]) && $this->request->post[str_replace('debug', 'status', $key)]) {
                            $json['info'] = $this->language->get('text_debug_info');
                        }
                    }
                }

                $this->updateJsFile();

                $json['js_version'] = $this->request->post[$this->mName . '_js_version'];
                $json['js_position'] = $this->checkFrontEnd('catalog/view/javascript/ee_tracking');
                $json['js_default'] = $this->checkFrontEnd('catalog/view/javascript/common');

                $module_info = $this->model_setting_setting->getSetting($this->mName, $data['store_id']);

                $stores = $this->model_setting_store->getStores();

                if (!isset($this->request->post[$this->mName . '_custom_dimension'])) {
                    $this->request->post[$this->mName . '_custom_dimension'] = array();
                }

                if (isset($this->request->post[$this->mName . '_multistore']) && isset($module_info[$this->mName . '_multistore']) && $module_info[$this->mName . '_multistore'] != $this->request->post[$this->mName . '_multistore']) {
                    if ($this->request->post[$this->mName . '_multistore']) {
                        foreach ($stores as $store) {
                            $this->model_setting_setting->editSetting($this->mName, array($this->mName . '_status' => 0), $store['store_id']);
                        }
                    } else {
                        foreach ($stores as $store) {
                            $this->model_setting_setting->deleteSetting($this->mName, $store['store_id']);
                        }
                    }
                }

                $this->model_setting_setting->editSetting($this->mName, $this->request->post, $data['store_id']);

                $json['success'] = $this->language->get('text_success');
            } else {
                $json['error'] = $this->error;
            }

            $this->response->addHeader('Content-Type: application/json');

            return $this->response->setOutput(json_encode($json));
        }

        if (!file_exists(DIR_CATALOG . 'model/' . $this->mPath . '.php')) {
            $data['error']['warning'] = $this->language->get('error_file_exist');
        }

        if (!$this->{$this->mModel}->getTableExist('ee_click_to_client') || !$this->{$this->mModel}->getTableExist('ee_order_to_client')) {
            $data['error']['warning'] = sprintf($this->language->get('error_mysql_table'), html_entity_decode($this->url->link($this->mMainPath, $this->mToken . '=' . $this->session->data[$this->mToken] . '&type=module', $this->mSSL)));
        }

        $modification = $this->{$this->mModel}->getModification();

        if ($modification && $modification['version'] != $this->mVersion) {
            $data['error']['warning'] = sprintf($this->language->get('error_ocmod_modification'), $this->mVersion);
        }

        if ($this->vqVersion && $this->vqVersion != $this->mVersion) {
            $data['error']['warning'] = sprintf($this->language->get('error_vqmod_modification'), $this->mVersion);
        }

        if ($modification && $this->vqVersion) {
            $data['error']['warning'] = $this->language->get('error_ocmod_with_vqmod');
        }

        $data['js_position'] = $this->checkFrontEnd('catalog/view/javascript/ee_tracking');

        $data['js_default'] = $this->checkFrontEnd('catalog/view/javascript/common');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->mToken . '=' . $this->session->data[$this->mToken], $this->mSSL)
        );

        $data['breadcrumbs'][] = array(
            'text' => (substr(VERSION, 0, 7) > '2.2.0.0') ? $this->language->get('text_extensions') : $this->language->get('text_modules'),
            'href' => $this->url->link($this->mMainPath, $this->mToken . '=' . $this->session->data[$this->mToken] . '&type=module', $this->mSSL)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->url->link($this->mPath, $this->mToken . '=' . $this->session->data[$this->mToken] . $url, $this->mSSL)
        );

        $data['action'] = html_entity_decode($this->url->link($this->mPath, $this->mToken . '=' . $this->session->data[$this->mToken] . $url, $this->mSSL));
        $data['cancel'] = html_entity_decode($this->url->link($this->mMainPath, $this->mToken . '=' . $this->session->data[$this->mToken] . '&type=module', $this->mSSL));
        $data['update_log'] = html_entity_decode($this->url->link($this->mPath . '/updateLog', $this->mToken . '=' . $this->session->data[$this->mToken], $this->mSSL));
        $data['clear_log'] = html_entity_decode($this->url->link($this->mPath . '/clearLog', $this->mToken . '=' . $this->session->data[$this->mToken], $this->mSSL));

        $data['author'] =  base64_decode('VmFuU3R1ZGlv');
        $data['dev_site'] =  base64_decode('aHR0cHM6Ly92YW5zdHVkaW8uY28udWE=');
        $data['oc_page'] = 'https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=' . $this->mId;
        $data['demo_site'] = 'http://a.demo-store.xyz/';
        $data['demo_admin'] = 'http://a.demo-store.xyz/admin/index.php?route=common/login&username=demo&password=demo&redirect=extension/module/ee_tracking';

        $data['module_id'] = $this->mId;
        $data['module_version'] = $this->mVersion;
        $data['module_name'] = $this->mName;
        $data['module_custom'] = $this->mCustom;
        $data['site_url'] = HTTP_CATALOG ? HTTP_CATALOG : HTTP_SERVER;
        $data['oc_version'] = VERSION;
        $data['config_email'] = $this->config->get('config_email');
        $data['user_ip'] = $this->getUserIP();

        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            $module_info = $this->model_setting_setting->getSetting($this->mName, $data['store_id']);

            foreach ($module_info as $key => $item) {
                $data[$key] = $module_info[$key];
            }

            if (count($module_info) > 1 && (!isset($module_info[$this->mName . '_module_version']) || $module_info[$this->mName . '_module_version'] != $this->mVersion)) {
                $data['error']['warning'] = $this->language->get('error_save');
            }

        } else {
            foreach ($this->request->post as $key => $item) {
                $data[$key] = $item;
            }
        }

        $this->load->model('setting/store');
        $data['stores'][] = array('store_id' => 0, 'name' => $this->language->get('text_default'), 'url' => '', 'ssl' => '');
        $data['stores'] = array_merge($data['stores'], $this->model_setting_store->getStores());

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $this->load->model('localisation/currency');
        $data['currencies'] = $this->model_localisation_currency->getCurrencies();

        $data['product_columns'] = $this->{$this->mModel}->getTableColumns('product');
        $data['order_columns'] = $this->{$this->mModel}->getTableColumns('order');

        $product_additional_columns = array('language_id', 'name', 'description', 'tag', 'meta_title', 'meta_description', 'meta_keyword', 'store_id', 'manufacturer', 'discount', 'special', 'reward', 'stock_status', 'weight_class', 'length_class', 'rating', 'reviews');

        $data['product_columns'] = array_merge($data['product_columns'], $product_additional_columns);

        $file = DIR_LOGS . $this->mName . $data['store_id'] .  '.log';

        if (file_exists($file)) {
            if (filesize($file) > 10000000) {
                $handle = fopen($file, 'w+');
                fclose($handle);
            }
            $data['log'] = html_entity_decode(file_get_contents($file, FILE_USE_INCLUDE_PATH, null), ENT_QUOTES, 'UTF-8');
        } else {
            $data['log'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        if (substr(VERSION, 0, 7) > '2.1.0.2') {
            $this->response->setOutput($this->load->view($this->mPath, $data));
        } else {
            $this->response->setOutput($this->load->view($this->mPath . '.tpl', $data));
        }
    }

    protected function updateJsFile() {
        $this->load->model('setting/setting');
        $this->load->model('setting/store');

        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        } else {
            $store_id = 0;
        }

        $stores = $this->model_setting_store->getStores();

        $stores[] = array('store_id' => 0);

        $checkout_url = array();

        $js_version = 0;

        foreach ($stores as $store) {
            $checkout_custom = $this->model_setting_setting->getSettingValue($this->mName . '_checkout_custom', $store['store_id']);

            if ($store['store_id'] != $store_id && $checkout_custom) {

                $store_checkout_url = json_decode($this->model_setting_setting->getSettingValue($this->mName . '_checkout_url', $store['store_id']), $this->mSSL);

                if (is_array($store_checkout_url)) {
                    $checkout_url = array_merge($checkout_url, $store_checkout_url);
                }
            }

            $store_js_version = $this->model_setting_setting->getSettingValue($this->mName . '_js_version', $store['store_id']);

            if ($js_version < $store_js_version) {
                $js_version = $store_js_version;
            }
        }

        if ($this->request->post[$this->mName . '_checkout_custom']) {
            $checkout_url = array_merge($checkout_url, $this->request->post[$this->mName . '_checkout_url']);
        }

        $checkout_url = array_unique($checkout_url);

        if ($checkout_url) {
            $replace_str = '';

            foreach ($checkout_url as $key => $item) {
                if ($replace_str) {
                    $replace_str .= ',';
                }

                $replace_str .= '"/' .  ltrim(trim($item),'/') . '"';
            }
        } else {
            $replace_str = '"false"';
        }

        $change_js = false;

        $module_info = $this->model_setting_setting->getSetting($this->mName, $store_id);

        if ($this->request->post[$this->mName . '_checkout_custom']) {
            if (!isset($module_info[$this->mName . '_checkout_custom'])) {
                $change_js = true;
            } elseif (!$module_info[$this->mName . '_checkout_custom']) {
                $change_js = true;
            } elseif ($module_info[$this->mName . '_checkout_url'] != $this->request->post[$this->mName . '_checkout_url']) {
                $change_js = true;
            }
        } else {
            if (isset($module_info[$this->mName . '_checkout_custom']) && $module_info[$this->mName . '_checkout_custom']) {
                $change_js = true;
            }
        }

        if (!isset($module_info[$this->mName . '_module_version']) || $module_info[$this->mName . '_module_version'] != $this->mVersion) {
            $change_js = true;
            $this->request->post[$this->mName . '_js_version'] = 0;
            $js_version = 0;
        }
        
        if ($change_js) {
            $file_str = file_get_contents(DIR_CATALOG . 'view/javascript/ee_tracking.min.js');
            $file_str = preg_replace('/,\[[^\]]+\]\)&&setIntervalEE\(/', ',[' . $replace_str . '])&&setIntervalEE(', $file_str);
            file_put_contents(DIR_CATALOG . 'view/javascript/ee_tracking.min.js', $file_str);

            if ($js_version > $this->request->post[$this->mName . '_js_version']) {
                $this->request->post[$this->mName . '_js_version'] = $js_version + 1;
            } else {
                $this->request->post[$this->mName . '_js_version'] = $this->request->post[$this->mName . '_js_version'] + 1;
            }

            foreach ($stores as $store) {
                $this->model_setting_setting->editSettingValue($this->mName, $this->mName . '_js_version', $this->request->post[$this->mName . '_js_version'], $store['store_id']);
            }
        }
    }

    public function updateLog() {
        $json = array();

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->post['file'])) {
            $file = DIR_LOGS . $this->request->post['file'];

            if (file_exists($file)) {
                $json['log'] = html_entity_decode(file_get_contents($file, FILE_USE_INCLUDE_PATH, null), ENT_QUOTES, 'UTF-8');
            } else {
                $json['log'] = '';
            }
        }

        $this->response->setOutput(json_encode($json));
    }

    public function clearLog() {
        $this->load->language($this->mPath);

        $json = array();

        if ($this->user->hasPermission('modify', $this->mPath)) {
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->post['file'])) {
                $handle = fopen(DIR_LOGS . $this->request->post['file'], 'w+');

                fclose($handle);

                $json['success'] = $this->language->get('text_clear_log');
            }
        } else {
            $json['error'] = $this->language->get('error_permission');
        }

        $this->response->setOutput(json_encode($json));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', $this->mPath)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ($this->request->post[$this->mName . '_status']) {
            if (utf8_strlen(trim($this->request->post[$this->mName . '_tracking_id'])) < 1) {
                $this->error['error_tracking_id'] = $this->language->get('error_tracking_id');
            }

            if ((!isset($this->request->get['store_id']) || !$this->request->get['store_id']) && utf8_strlen(trim($this->request->post[$this->mName . '_order_id'])) < 1) {
                $this->error['error_order_id'] = $this->language->get('error_order_id');
            }

            if ($this->request->post[$this->mName . '_checkout_status'] && $this->request->post[$this->mName . '_checkout_custom']) {
                foreach ($this->request->post[$this->mName . '_checkout_url'] as $key => $url) {
                    if (utf8_strlen(trim($url)) < 1) {
                        $this->error['error_checkout_url_' . $key] = $this->language->get('error_checkout_url');
                    }
                }
            }

            if ($this->request->post[$this->mName . '_transaction_status'] && !isset($this->request->post[$this->mName . '_order_status'])) {
                $this->error['error_order_status'] = $this->language->get('error_order_status');
            }

            if ($this->request->post[$this->mName . '_refund_status'] && !isset($this->request->post[$this->mName . '_refund_order_status'])) {
                $this->error['error_refund_order_status'] = $this->language->get('error_order_status');
            }

            if (!$this->{$this->mModel}->getTableExist('ee_click_to_client') || !$this->{$this->mModel}->getTableExist('ee_order_to_client')) {
                $this->error['warning'] = sprintf($this->language->get('error_mysql_table'), html_entity_decode($this->url->link($this->mMainPath, $this->mToken . '=' . $this->session->data[$this->mToken] . '&type=module', $this->mSSL)));
            }
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }

    protected function checkFrontEnd($string, $url = HTTPS_CATALOG) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        $page_html = curl_exec($curl);
        curl_close($curl);
        return strpos($page_html, $string);
    }

    public function install() {
        $this->load->model('setting/store');
        $this->load->model('setting/setting');
        $this->load->model($this->mPath);

        $settings = array(
            $this->mName . '_all_status' => 0,
            $this->mName . '_all_debug' => 0,
            $this->mName . '_all_log' => 0,
            $this->mName . '_status' => 0,
            $this->mName . '_module_version' => $this->mVersion,
            $this->mName . '_multistore' => 0,
            $this->mName . '_tracking_id' => '',
            $this->mName . '_order_id' => '',
            $this->mName . '_js_position' => 0,
            $this->mName . '_advanced_settings' => 0,
            $this->mName . '_language_id' => 0,
            $this->mName . '_currency' => 0,
            $this->mName . '_tax' => 1,
            $this->mName . '_total_shipping' => 1,
            $this->mName . '_total_tax' => 1,
            $this->mName . '_affiliation' => '',
            $this->mName . '_product_id' => 'product_id',
            $this->mName . '_product_category' => 0,
            $this->mName . '_compatibility' => 0,
            $this->mName . '_generate_cid' => 0,
            $this->mName . '_ga_callback' => 0,

            $this->mName . '_impression_status' => 0,
            $this->mName . '_impression_debug' => 0,
            $this->mName . '_impression_log' => 0,
            
            $this->mName . '_click_status' => 0,
            $this->mName . '_click_debug' => 0,
            $this->mName . '_click_log' => 0,
            
            $this->mName . '_detail_status' => 0,
            $this->mName . '_detail_debug' => 0,
            $this->mName . '_detail_log' => 0,
            
            $this->mName . '_cart_status' => 0,
            $this->mName . '_cart_debug' => 0,
            $this->mName . '_cart_log' => 0,
            
            $this->mName . '_checkout_status' => 0,
            $this->mName . '_checkout_debug' => 0,
            $this->mName . '_checkout_log' => 0,
            $this->mName . '_checkout_custom' => 0,
            $this->mName . '_js_version' => 0,
            
            $this->mName . '_transaction_status' => 0,
            $this->mName . '_transaction_debug' => 0,
            $this->mName . '_transaction_log' => 0,
            $this->mName . '_order_status' => array('1','5'),

            $this->mName . '_refund_status' => 0,
            $this->mName . '_refund_debug' => 0,
            $this->mName . '_refund_log' => 0,
            $this->mName . '_refund_order_status' => array('11'),
            $this->mName . '_customer_refund' => 0,

            $this->mName . '_promotion_status' => 0,
            $this->mName . '_promotion_debug' => 0,
            $this->mName . '_promotion_log' => 0,

            $this->mName . '_custom_dimension' => array(),

            $this->mName . '_bot_filter' => 'bot|crawl|slurp|spider|mediapartners|google',
            $this->mName . '_ip_filter' => '',
            $this->mName . '_admin_tracking' => 0,

            $this->mName . '_log' => 0,
            $this->mName . '_extended_log' => 0,
        );

        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();

        $checkout_url = array();
        foreach ($languages as $language) {
            $checkout_url[$language['language_id']] = 'index.php?route=checkout/checkout';
        }

        $settings[$this->mName . '_checkout_url'] = $checkout_url;

        $stores = $this->model_setting_store->getStores();

        $this->model_setting_setting->editSetting($this->mName, $settings, 0);

        foreach ($stores as $store) {
            $this->model_setting_setting->editSetting($this->mName, $settings, $store['store_id']);
        }

        $result = $this->{$this->mModel}->install();

        if ($result->num_rows) {
            if (substr(VERSION, 0, 7) > '2.3.0.2') {
                $this->load->controller('marketplace/modification/refresh', array('redirect' => 'extension/extension/module'));
            } else {
                $this->load->controller('extension/modification/refresh');
            }
        }
    }

    public function uninstall() {
        $this->load->model('setting/store');
        $this->load->model('setting/setting');
        $this->load->model($this->mPath);

        $this->model_setting_setting->deleteSetting($this->mName, 0);

        $stores = $this->model_setting_store->getStores();

        foreach ($stores as $store) {
            $this->model_setting_setting->deleteSetting($this->mName, $store['store_id']);
        }

        $result = $this->{$this->mModel}->uninstall();

        if ($result->num_rows) {
            if (substr(VERSION, 0, 7) > '2.3.0.2') {
                $this->load->controller('marketplace/modification/refresh', array('redirect' => 'extension/extension/module'));
            } else {
                $this->load->controller('extension/modification/refresh');
            }
        }
    }

    protected function getUserIP() {
        if (!empty($this->request->server['HTTP_CLIENT_IP'])) {
            $ip_address = $this->request->server['HTTP_CLIENT_IP'];
        } elseif (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $this->request->server['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip_address = $this->request->server['REMOTE_ADDR'];
        }
        return $ip_address;
    }
}