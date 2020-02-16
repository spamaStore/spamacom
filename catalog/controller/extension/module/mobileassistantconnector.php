<?php
/**
 *   This file is part of Mobile Assistant Connector.
 *
 *   Mobile Assistant Connector is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Mobile Assistant Connector is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Mobile Assistant Connector. If not, see <http://www.gnu.org/licenses/>.
 */

class BaseControllerModuleMobileAssistantConnector extends Controller
{
    private $call_function;
    private $callback;
    private $hash;
    private $s;
    private $currency;
    private $module_user;
    private $show;
    private $page;
    private $search_order_id;
    private $orders_from;
    private $orders_to;
    private $customers_from;
    private $customers_to;
    private $graph_from;
    private $graph_to;
    private $stats_from;
    private $stats_to;
    private $products_to;
    private $products_from;
    private $order_id;
    private $user_id;
    private $params;
    private $val;
    private $search_val;
    private $statuses;
    private $sort_by;
    private $product_id;
    private $get_statuses;
    private $cust_with_orders;
    private $data_for_widget;
    private $registration_id;
    private $registration_id_old;
    private $api_key;
    private $push_new_order;
    private $push_order_statuses;
    private $push_new_customer;
    private $app_connection_id;
    private $push_currency_code;
    private $action;
    private $custom_period;
    private $store_id;
    private $new_status;
    private $currency_code;
    private $notify_customer;
    private $change_order_status_comment;
    private $account_email;
    private $device_name;
    private $key;
    private $device_unique_id;
    private $without_thumbnails;
    private $only_items;
    private $order_by;
    private $group_by_product_id;
    private $qr_hash;

    const PUSH_TYPE_NEW_ORDER           = 'new_order';
    const PUSH_TYPE_CHANGE_ORDER_STATUS = 'order_changed';
    const PUSH_TYPE_NEW_CUSTOMER        = 'new_customer';
    const DEBUG_MODE                    = false;
    const MOB_ASSIST_API_KEY            = 'AIzaSyDIq4agB70Zv7AkB9pVuF2KxcU4WQ94CVI';
    const HASH_ALGORITHM                = 'sha256';
    const MAX_LIFETIME                  = 86400; /* 24 hours */
    const T_SESSION_KEYS                = 'mobassistantconnector_session_keys';
    const T_FAILED_LOGIN                = 'mobassistantconnector_failed_login';
    const T_PUSH_NOTIFICATIONS          = 'mobileassistant_push_settings';
    const T_DEVICES                     = 'mobassistantconnector_devices';
    const T_USERS                       = 'mobassistantconnector_users';

    public function __construct($registry)
    {
        parent::__construct($registry);

        if (file_exists(DIR_SYSTEM . 'library/mobileassistant_helper.php')) {
            $this->load->library('mobileassistant_helper');
        } else {
            $this->load->helper('mobileassistant_helper');
        }
    }

    public function index()
    {
        @date_default_timezone_set('UTC');
        $this->s = Mobileassistant_helper::getSetting(
            $this->db,
            Mobileassistant_helper::MODULE_SETTING_CODE
        );

        $request = $this->request->request;
        $this->_validate_types($request);
        if (empty($this->call_function)) {
            $this->run_self_test();
        }

        if ($this->call_function == 'get_qr_code') {
            $this->get_qr_code();
        }

        Mobileassistant_helper::checkAndAddEvents($this, Mobileassistant_helper::AREA_FRONTEND);
        Mobileassistant_helper::createTables($this->db);
        $this->_checkUpdateModule();
        $this->clear_old_data();

        if ($this->call_function == 'get_version') {
            $this->get_version();
        }

        if ($this->hash) {
            if (!$this->check_auth()) {
                $this->add_failed_login();
                $this->generate_output('auth_error');
            }

            $key = $this->get_session_key();

            $this->generate_output(array('session_key' => $key));
        } elseif ($this->key) {
            if (!$this->check_session_key($this->key)) {
                $this->generate_output(array('bad_session_key' => true));
            }
        } else {
            $this->add_failed_login();
            $this->generate_output('auth_error');
        }

        if ($this->call_function == 'test_config') {
            $this->generate_output(array('test' => 1));
        }

        $this->show = (empty($this->show) || $this->show < 1) ? 17 : $this->show;
        $this->page = (empty($this->page) || $this->page < 1) ? 1 : $this->page;
        $this->map_push_notification_to_device();
        if (empty($this->currency_code) || $this->currency_code == 'not_set') {
            $this->currency = '';
        } elseif ($this->currency_code == 'base_currency') {
            $this->currency = $this->config->get('config_currency');
        } else {
            $this->currency = $this->currency_code;
        }

        if (empty($this->push_currency_code) || $this->push_currency_code == 'not_set') {
            $this->push_currency_code = '';
        }

        if ($this->store_id == '') {
            $this->store_id = -1;
        }

        $this->store_id = (int)$this->store_id;
        if (!method_exists($this, $this->call_function)) {
            $this->generate_output('old_module');
        }

        if ($this->_check_allowed_actions($this->call_function)) {
            $result = $this->{$this->call_function}();
            $this->generate_output($result);
        } else {
            $this->generate_output('action_forbidden');
        }
    }

    private function _check_allowed_actions($function)
    {
        if ($this->module_user
            && isset($this->module_user['user_status'])
            && $this->module_user['user_status'] == 1
            && isset($this->module_user['allowed_actions'])
        ) {
            $actions = array(
                'push_new_order' => 'push_new_order',
                'push_new_order_156x' => 'push_new_order',
                'push_new_order_23x' => 'push_new_order',

                'push_new_customer' => 'push_new_customer',
                'push_new_customer_156x' => 'push_new_customer',

                'push_change_status' => 'push_order_status_changed',
                'push_change_status_pre' => 'push_order_status_changed',
                'push_change_status_156x' => 'push_order_status_changed',

                'get_store_stats' => 'store_statistics',
                'get_data_graphs' => 'store_statistics',
                'get_status_stats' => 'store_statistics',

                'get_orders' => 'order_list',
                'get_orders_statuses' => 'order_list',
                'get_orders_info' => 'order_details',
                'set_order_action' => 'order_status_updating',
                'get_order_product_list_pickup' => 'order_details_products_list_pickup',

                'get_customers' => 'customer_list',
                'get_customers_info' => 'customer_details',

                'search_products' => 'product_list',
                'search_products_ordered' => 'product_list',

                'get_products_info' => 'product_details',
                'get_products_descr' => 'product_details',
                'get_product_to_edit' => 'product_edit',
                'update_product' => 'product_edit',
                'get_data_for_new_product' => 'product_add',
                'add_product' => 'product_add'
            );

            if (isset($actions[$function])) {
                $action = $actions[$function];
                if (isset($this->module_user['allowed_actions'][$action])
                    && $this->module_user['allowed_actions'][$action] == 1
                ) {
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
    }

    private function _validate_types($array)
    {
        $names = array(
            'show' => 'INT',
            'page' => 'INT',
            'search_order_id' => 'STR',
            'orders_from' => 'STR',
            'orders_to' => 'STR',
            'customers_from' => 'STR',
            'customers_to' => 'STR',
            'date_from' => 'STR',
            'date_to' => 'STR',
            'graph_from' => 'STR',
            'graph_to' => 'STR',
            'stats_from' => 'STR',
            'stats_to' => 'STR',
            'products_to' => 'STR',
            'products_from' => 'STR',
            'order_id' => 'INT',
            'user_id' => 'INT',
            'params' => 'STR',
            'val' => 'STR',
            'search_val' => 'STR',
            'statuses' => 'STR',
            'sort_by' => 'STR',
            'last_order_id' => 'STR',
            'product_id' => 'INT',
            'get_statuses' => 'INT',
            'cust_with_orders' => 'INT',
            'data_for_widget' => 'INT',
            'registration_id' => 'STR',
            'registration_id_old' => 'STR',
            'api_key' => 'STR',
            'push_new_order' => 'INT',
            'push_order_statuses' => 'STR',
            'push_new_customer' => 'INT',
            'app_connection_id' => 'STR',
            'push_currency_code' => 'STR',
            'action' => 'STR',
            'carrier_code' => 'STR',
            'custom_period' => 'INT',
            'store_id' => 'STR',
            'new_status' => 'INT',
            'notify_customer' => 'INT',
            'currency_code' => 'STR',
            'account_email' => 'STR',
            'device_name' => 'STR',
            'last_activity' => 'STR',
            'fc' => 'STR',
            'module' => 'STR',
            'controller' => 'STR',
            'change_order_status_comment' => 'STR',
            'param' => 'STR',
            'new_value' => 'STR',
            'hash' => 'STR',
            'device_unique_id' => 'STR',
            'call_function' => 'STR',
            'order_by' => 'STR',
            'key' => 'STR',
            'qr_hash' => 'STR',
            'without_thumbnails' => 'INT',
            'only_items' => 'INT',
            'group_by_product_id' => 'INT',
            'product' => 'STR',
        );

        foreach ($names as $name => $type) {
            if (isset($array[(string)$name])) {
                switch ($type) {
                    case 'INT':
                        $array[(string)$name] = (int)$array[(string)$name];
                        break;
                    case 'FLOAT':
                        $array[(string)$name] = (float)$array[(string)$name];
                        break;
                    case 'STR':
                        if ($name != 'product') {
                            $array[(string)$name] = str_replace(
                                array("\r", "\n"),
                                ' ',
                                addslashes(htmlspecialchars(trim(urldecode($array[(string)$name]))))
                            );
                        } else {
                            $array[(string)$name] = htmlspecialchars_decode($array[(string)$name]);
                        }
                        break;
                    case 'STR_HTML':
                        $array[(string)$name] = addslashes(trim(urldecode($array[(string)$name])));
                        break;
                    default:
                        $array[(string)$name] = '';
                }
            } else {
                $array[(string)$name] = '';
            }

            $this->{$name} = $array[(string)$name];
        }

        return $array;
    }

    private function get_version()
    {
        $session_key = false;

        if ($this->hash) {
            if ($this->check_auth()) {
                $this->add_failed_login();
                if ($this->key && $this->check_session_key($this->key)) {
                    $session_key = $this->key;
                } else {
                    $session_key = $this->get_session_key();
                }
            } else {
                $this->add_failed_login();
            }

        } elseif ($this->key) {
            if (!$this->check_session_key($this->key)) {
                $session_key = $this->key;
            } else {
                $this->add_failed_login();
            }
        }

        if ($session_key) {
            $this->generate_output(array('session_key' => $session_key));
        }

        $this->add_failed_login();
        $this->generate_output(array());
    }

    private function run_self_test()
    {
        $html = '<h2>Mobile Assistant Connector (v. ' . Mobileassistant_helper::MODULE_VERSION . ')</h2>';

        if (class_exists('MijoShop')) {
            $base = MijoShop::get('base');

            $installed_ms_version = (array)$base->getMijoshopVersion();
            $mijo_version         = $installed_ms_version[0];

            $html .= '<table cellpadding=4><tr><th>Test Title</th><th>Result</th></tr>';
            $html .= '<tr><td>MijoShop Version</td><td>' . $mijo_version . '</td><td></td></tr>';
            $html .= '<tr><td>MijoShop Opencart Version</td><td>' . VERSION . '</td><td></td></tr>';
            $html .= '</table><br/>';
        }

        $html .= '<div style="margin-top: 15px; font-size: 13px;">Mobile Assistant Connector by <a href="https://emagicone.com" target="_blank" style="color: #15428B">eMagicOne</a></div>';

        die($html);
    }

    private function get_qr_code()
    {
        if (empty($this->qr_hash)) {
            return;
        }

        $data = array('qr_hash' => $this->qr_hash);
        $user = Mobileassistant_helper::getModel('connector', $this)->getModuleUser($data);
        if (!$user) {
            return;
        }

        $qrcode = $this->generate_QR_code($user);

        $html = '<script type="text/javascript" src="admin/view/javascript/qrcode.min.js"></script>';
        $html .= '<h3>Mobile Assistant Connector (v. ' . Mobileassistant_helper::MODULE_VERSION . ')</h3>';
        $html .= '<div id="mobassist_qr_code" style="margin-left: 20px; margin-top: 20px;"></div>';
        $html .= '
        <script type="text/javascript">
        var qrcode = new QRCode(document.getElementById("mobassist_qr_code"), {
            width : 250,
            height : 250
        });
        qrcode.makeCode("' . $qrcode . '");
        </script>';

        die($html);
    }

    private function generate_QR_code($user)
    {
        $url = '';
        if (defined('HTTP_CATALOG')) {
            $url = HTTP_CATALOG;
        } elseif (defined('HTTP_SERVER')) {
            $url = HTTP_SERVER;
        }

        $url    = str_replace(array('http://', 'https://'), '', $url);
        $config = array(
            'url' => $url,
            'login' => $user['username'],
            'password' => $user['password'],
        );

        return base64_encode(json_encode($config));
    }

    private function test_default_password_is_changed()
    {
        return !($this->s['mobassist_login'] == '1'
            && $this->s['mobassist_pass'] == 'c4ca4238a0b923820dcc509a6f75849b');
    }

    private function generate_output($data)
    {
        $add_bridge_version = false;

        if (in_array(
                $this->call_function,
                array(
                    'test_config',
                    'get_store_title',
                    'get_store_stats',
                    'get_data_graphs',
                    'get_version'
                )
            )
            && is_array($data)
            && $data != 'auth_error'
            && $data != 'connection_error'
            && $data != 'old_bridge'
        ) {
            $add_bridge_version = true;
        }

        if (!is_array($data)) {
            $data = array($data);
        } else {
            $data['module_response'] = '1';
        }

        if ($add_bridge_version) {
            $data['module_version'] = Mobileassistant_helper::MODULE_CODE;
            $data['cart_version']   = VERSION;
        }

        if (is_array($data)) {
            array_walk_recursive($data, array($this, 'reset_null'));
        }

        $data = json_encode($data);

        if ($this->callback) {
            header('Content-Type: text/javascript;charset=utf-8');
            die($this->callback . '(' . $data . ');');
        }

        header('Content-Type: text/javascript;charset=utf-8');
        die($data);
    }

    private function reset_null(&$item)
    {
        if (empty($item) && $item != 0) {
            $item = '';
        }

        $item = trim($item);
    }

    private function check_auth()
    {
        $user = Mobileassistant_helper::getModel('connector', $this)->checkAuth($this->hash);

        if ($user) {
            if (isset($user['user_status']) && $user['user_status'] == 1) {
                $this->module_user = $user;
                $this->clear_failed_login();
                return true;
            }

            $this->generate_output('user_disabled');
        }

        return false;
    }

    private function get_stores()
    {
        $this->load->model('setting/store');
        $all_stores[] = array('store_id' => 0, 'name' => $this->config->get('config_name'));

        $stores = $this->model_setting_store->getStores();

        foreach ($stores as $store) {
            $all_stores[] = array('store_id' => $store['store_id'], 'name' => $store['name']);
        }

        return $all_stores;
    }

    private function get_currencies()
    {
        $this->load->model('localisation/currency');

        $currencies = $this->model_localisation_currency->getCurrencies();

        $all_currencies = array();

        foreach ($currencies as $currency) {
            $all_currencies[] = array('code' => $currency['code'], 'name' => $currency['title']);
        }

        return $all_currencies;
    }

    private function get_store_title()
    {
        if ($this->store_id > -1) {
            $this->load->model('setting/setting');
            $settings = $this->model_setting_setting->getSetting('config', $this->store_id);
            $title    = $settings['config_name'];

        } else {
            $title = $this->config->get('config_name');
        }

        return array('test' => 1, 'title' => $title);
    }

    private function get_store_stats()
    {
        $data_graphs        = '';
        $order_status_stats = array();
        $store_stats        = array(
            'count_orders' => '0',
            'total_sales' => '0',
            'count_customers' => '0',
            'count_products' => '0',
            'last_order_id' => '0',
            'new_orders' => '0'
        );

        $today     = date('Y-m-d');
        $date_from = $date_to = $today;

        $data = array();

        if (!empty($this->stats_from)) {
            $date_from = $this->stats_from;
        }

        if (!empty($this->stats_to)) {
            $date_to = $this->stats_to;
        }

        if (isset($this->custom_period) && strlen($this->custom_period) > 0) {
            $custom_period = $this->get_custom_period($this->custom_period);

            $date_from = $custom_period['start_date'];
            $date_to   = $custom_period['end_date'];
        }

        if (!empty($date_from)) {
            $data['date_from'] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $data['date_to'] = $date_to . ' 23:59:59';
        }

        if ($this->statuses != '') {
            $data['statuses'] = $this->get_filter_statuses($this->statuses);
        }

        if ($this->store_id > -1) {
            $data['store_id'] = $this->store_id;
        }

        if (!empty($this->currency)) {
            $data['currency_code'] = $this->currency;
        }

        $orders_stats = Mobileassistant_helper::getModel('connector', $this)->getTotalOrders($data);
        $store_stats  = array_merge($store_stats, $orders_stats);

        $customers_stats = Mobileassistant_helper::getModel('connector', $this)->getTotalCustomers($data);
        $store_stats     = array_merge($store_stats, $customers_stats);

        $products_stats = Mobileassistant_helper::getModel('connector', $this)->getTotalSoldProducts($data);
        $store_stats    = array_merge($store_stats, $products_stats);

        if (!isset($this->data_for_widget) || empty($this->data_for_widget) || $this->data_for_widget != 1) {
            $data_graphs = $this->get_data_graphs();
        }

        if (!isset($this->data_for_widget) || $this->data_for_widget != 1) {
            $order_status_stats = $this->get_status_stats();
        }

        $result = array_merge($store_stats, array('data_graphs' => $data_graphs),
            array('order_status_stats' => $order_status_stats));

        return $result;
    }

    private function get_data_graphs()
    {
        $data = array();

        if (empty($this->graph_from)) {
            $this->graph_from = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 7, date('Y')));
        }
        $data['graph_from'] = $this->graph_from . ' 00:00:00';

        if (empty($this->graph_to)) {
            if (!empty($this->stats_to)) {
                $this->graph_to = $this->stats_to;
            } else {
                $this->graph_to = date('Y-m-d');
            }
        }
        $data['graph_to'] = $this->graph_to . ' 23:59:59';

        if (isset($this->custom_period) && strlen($this->custom_period) > 0) {
            $data['custom_period']      = $this->custom_period;
            $data['custom_period_date'] = $this->get_custom_period($this->custom_period);
        }

        if ($this->store_id > -1) {
            $data['store_id'] = $this->store_id;
        }

        if ($this->statuses != '') {
            $data['statuses'] = $this->get_filter_statuses($this->statuses);
        }

        if (!empty($this->currency)) {
            $data['currency_code'] = $this->currency;
        }

        $chart_data = Mobileassistant_helper::getModel('connector', $this)->getChartData($data);

        return $chart_data;
    }

    private function get_status_stats()
    {
        $today     = date('Y-m-d');
        $date_from = $date_to = $today;

        $data = array();

        if (!empty($this->stats_from)) {
            $date_from = $this->stats_from;
        }

        if (!empty($this->stats_to)) {
            $date_to = $this->stats_to;
        }

        if (isset($this->custom_period) && strlen($this->custom_period) > 0) {
            $custom_period = $this->get_custom_period($this->custom_period);

            $date_from = $custom_period['start_date'];
            $date_to   = $custom_period['end_date'];
        }

        if (!empty($date_from)) {
            $data['date_from'] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $data['date_to'] = $date_to . ' 23:59:59';
        }

        if ($this->store_id > -1) {
            $data['store_id'] = $this->store_id;
        }

        if (!empty($this->currency)) {
            $data['currency_code'] = $this->currency;
        }

        $order_statuses = Mobileassistant_helper::getModel('connector', $this)->getOrderStatusStats($data);

        return $order_statuses;
    }

    private function get_orders()
    {
        $data = array();

        if ($this->store_id > -1) {
            $data['store_id'] = $this->store_id;
        }

        if ($this->statuses !== null && $this->statuses != '') {
            $data['statuses'] = $this->get_filter_statuses($this->statuses);
        }

        if (!empty($this->search_order_id)) {
            $data['search_order_id'] = $this->search_order_id;
        }

        if ($this->orders_from !== null && !empty($this->orders_from)) {
            $data['orders_from'] = $this->orders_from . ' 00:00:00';
        }

        if ($this->orders_to !== null && !empty($this->orders_to)) {
            $data['orders_to'] = $this->orders_to . ' 23:59:59';
        }

        if (!empty($this->currency)) {
            $data['currency_code'] = $this->currency;
        }

        if (!empty($this->get_statuses)) {
            $data['get_statuses'] = $this->get_statuses;
        }

        if ($this->page !== null && !empty($this->page) && $this->show !== null && !empty($this->show)) {
            $data['page'] = ($this->page - 1) * $this->show;
            $data['show'] = $this->show;
        }

        if (!empty($this->sort_by)) {
            $data['sort_by'] = $this->sort_by;
        } else {
            $data['sort_by'] = 'id';
        }

        if (!empty($this->order_by)) {
            $data['order_by'] = $this->order_by;
        }

        return Mobileassistant_helper::getModel('connector', $this)->getOrders($data);
    }

    private function get_orders_statuses()
    {
        return Mobileassistant_helper::getModel('connector', $this)->getOrdersStatuses();
    }

    private function get_orders_info()
    {
        $data = array('get_order_product_list_pickup' => $this->call_function == 'get_order_product_list_pickup');

        $data['order_id'] = $this->order_id;
        $data['page']     = ($this->page - 1) * $this->show;
        $data['show']     = $this->show;

        if (!empty($this->currency)) {
            $data['currency_code'] = $this->currency;
        }

        $data['without_thumbnails'] = false;
        if (!empty($this->without_thumbnails) && $this->without_thumbnails == 1) {
            $data['without_thumbnails'] = true;
        }

        $only_items = false;
        if (!empty($this->only_items) && $this->only_items == 1) {
            $only_items = true;
        }

        $order_products  = Mobileassistant_helper::getModel('connector', $this)->getOrderProducts($data);
        $order_full_info = array('order_products' => $order_products);

        if (!$only_items) {
            $order_info                    = Mobileassistant_helper::getModel('connector', $this)->getOrdersInfo($data);
            $order_full_info['order_info'] = $order_info;
        }
        if (!$only_items) {
            $count_prods                         =
                Mobileassistant_helper::getModel('connector', $this)->getOrderCountProducts($data);
            $order_full_info['o_products_count'] = $count_prods;
        }
        if (!$only_items) {
            $order_total                    =
                Mobileassistant_helper::getModel('connector', $this)->getOrderTotals($data);
            $order_full_info['order_total'] = $order_total;
        }

        return $order_full_info;
    }

    private function get_order_product_list_pickup()
    {
        return $this->get_orders_info();
    }

    private function get_customers()
    {
        $data = array();

        if (!empty($this->customers_from)) {
            $data['customers_from'] = $this->customers_from . ' 00:00:00';
        }

        if (!empty($this->customers_to)) {
            $data['customers_to'] = $this->customers_to . ' 23:59:59';
        }

        if (!empty($this->search_val)) {
            $data['search_val'] = $this->search_val;
        }

        if (!empty($this->cust_with_orders)) {
            $data['cust_with_orders'] = $this->cust_with_orders;
        }

        if ($this->store_id > -1) {
            $data['store_id'] = $this->store_id;
        }

        $data['page'] = ($this->page - 1) * $this->show;
        $data['show'] = $this->show;

        if (empty($this->sort_by)) {
            $data['sort_by'] = 'id';
        } else {
            $data['sort_by'] = $this->sort_by;
        }

        if (!empty($this->order_by)) {
            $data['order_by'] = $this->order_by;
        }

        $customers = Mobileassistant_helper::getModel('connector', $this)->getCustomers($data);

        return $customers;
    }

    private function get_customers_info()
    {
        $data = array();

        $data['page'] = ($this->page - 1) * $this->show;
        $data['show'] = $this->show;

        $data['user_id'] = $this->user_id;

        if (!empty($this->currency)) {
            $data['currency_code'] = $this->currency;
        }

        $data['only_items'] = false;
        if (!empty($this->only_items) && $this->only_items == 1) {
            $data['only_items'] = true;
        }

        return Mobileassistant_helper::getModel('connector', $this)->getCustomersInfo($data);
    }

    private function search_products($ordered = false)
    {
        $data = array();

        if (!empty($this->params)) {
            $data['params'] = explode('|', $this->params);
        }

        if (!empty($this->val)) {
            $data['val'] = $this->val;
        }

        if (!empty($this->products_from)) {
            $data['products_from'] = $this->products_from . ' 00:00:00';
        }

        if (!empty($this->products_to)) {
            $data['products_to'] = $this->products_to . ' 23:59:59';
        }

        if (empty($this->sort_by)) {
            $data['sort_by'] = 'id';
        } else {
            $data['sort_by'] = $this->sort_by;
        }

        if (!empty($this->order_by)) {
            $data['order_by'] = $this->order_by;
        }

        if (!empty($this->currency)) {
            $data['currency_code'] = $this->currency;
        }

        if ($this->store_id > -1) {
            $data['store_id'] = $this->store_id;
        }

        if ($this->statuses != '') {
            $data['statuses'] = $this->get_filter_statuses($this->statuses);
        }

        $data['without_thumbnails'] = false;
        if (!empty($this->without_thumbnails) && $this->without_thumbnails == 1) {
            $data['without_thumbnails'] = true;
        }

        $data['page'] = ($this->page - 1) * $this->show;
        $data['show'] = $this->show;

        if ($ordered) {
            $data['group_by_product_id'] = false;

            if (!empty($this->group_by_product_id) && $this->group_by_product_id == 1) {
                $data['group_by_product_id'] = true;
            }

            return Mobileassistant_helper::getModel('connector', $this)->getOrderedProducts($data);
        }

        return Mobileassistant_helper::getModel('connector', $this)->getProducts($data);
    }

    private function search_products_ordered()
    {
        return $this->search_products(true);
    }

    private function get_products_info()
    {
        $data = array('currency_code' => $this->currency, 'product_id' => $this->product_id);

        $data['without_thumbnails'] = false;

        if (!empty($this->without_thumbnails) && $this->without_thumbnails == 1) {
            $data['without_thumbnails'] = true;
        }

        return Mobileassistant_helper::getModel('connector', $this)->getProductInfo($data);
    }

    private function get_products_descr()
    {
        $data = array('product_id' => $this->product_id);

        return Mobileassistant_helper::getModel('connector', $this)->getProductDescr($data);
    }

    private function get_product_to_edit()
    {
        return Mobileassistant_helper::getModel('connector', $this)->getProductToEdit($this->product_id);
    }

    private function update_product()
    {
        $product = $this->product;

        if (empty($product)) {
            return false;
        }

        $product = urldecode($product);

        $product_data = json_decode($product, true);

        if (empty($product_data) || !isset($product_data['product_id']) || $product_data['product_id'] < 1) {
            return false;
        }

        return Mobileassistant_helper::getModel('connector', $this)
            ->saveProductData($product_data, $this->store_id, $this->request->files);
    }

    public function get_data_for_new_product()
    {
        return Mobileassistant_helper::getModel('connector', $this)->getCommonDataForProduct();
    }

    public function add_product()
    {
        $product = $this->product;
        if (empty($product)) {
            return false;
        }

        $product_data = json_decode(stripslashes(urldecode($this->product)), true);

        if (empty($product_data)) {
            return false;
        }

        $id_added_product = Mobileassistant_helper::getModel('connector', $this)
            ->saveProductData($product_data, $this->store_id, $this->request->files);

        if (!empty($id_added_product['product_id'])) {
            return array('success' => 'true', 'product_id' => $id_added_product['product_id']);
        }

        return array('error' => 'something_wrong');
    }

    private function set_order_action()
    {
        if ($this->order_id <= 0) {
            $error = 'Order ID cannot be empty!';
            Mobileassistant_helper::getModel('helper', $this)->write_log('ORDER ACTION ERROR: ' . $error);
            return array('error' => $error);
        }

        if (empty($this->action)) {
            $error = 'Action is not set!';
            Mobileassistant_helper::getModel('helper', $this)->write_log('ORDER ACTION ERROR: ' . $error);
            return array('error' => $error);
        }

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($this->order_id);

        if (!$order) {
            $error = 'Order not found!';
            Mobileassistant_helper::getModel('helper', $this)->write_log('ORDER ACTION ERROR: ' . $error);
            return array('error' => $error);
        }

        if ($this->action == 'change_status') {
            if (!isset($this->new_status) || (int)$this->new_status < 0) {
                $error = 'New order status is not set!';
                Mobileassistant_helper::getModel('helper', $this)->write_log('ORDER ACTION ERROR: ' . $error);
                return array('error' => $error);
            }

            $notify = false;
            if (isset($this->notify_customer) && $this->notify_customer == 1) {
                $notify = true;
            }


            if (Mobileassistant_helper::isCartVersion20()) {
                $this->model_checkout_order->addOrderHistory(
                    $this->order_id,
                    $this->new_status,
                    $this->change_order_status_comment,
                    $notify
                );
            } elseif (version_compare(Mobileassistant_helper::getCartVersion(), '1.5.4.1', '<=')) {
                Mobileassistant_helper::getModel('connector', $this)->addOrderHistory_154x(
                    $this->order_id,
                    $this->new_status,
                    $this->change_order_status_comment,
                    $notify
                );
            } else {
                Mobileassistant_helper::getModel('connector', $this)->addOrderHistory_156x(
                    $this->order_id,
                    $this->new_status,
                    $this->change_order_status_comment,
                    $notify
                );
            }

            return array('success' => 'true');
        }

        $error = 'Unknown error!';
        Mobileassistant_helper::getModel('helper', $this)->write_log('ORDER ACTION ERROR: ' . $error);

        return array('error' => $error);
    }

    private function push_notification_settings()
    {
        $data = array();

        if (empty($this->registration_id)) {
            $error = 'Empty device ID';
            Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH SETTINGS ERROR: ' . $error);
            return array('error' => $error);
        }

        if (empty($this->app_connection_id) || $this->app_connection_id < 0) {
            $error = 'Wrong app connection ID: ' . $this->app_connection_id;
            Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH SETTINGS ERROR: ' . $error);
            return array('error' => $error);
        }

        if (empty($this->api_key)) {
            $error = 'Empty application API key';
            Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH SETTINGS ERROR: ' . $error);
            return array('error' => $error);
        }

        if (!$this->module_user || !isset($this->module_user['user_id'])) {
            $error = 'User not found!';
            Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH SETTINGS ERROR: ' . $error);
            return array('error' => $error);
        }

        $s = Mobileassistant_helper::getSetting(
            $this->db,
            Mobileassistant_helper::MODULE_SETTING_CODE
        );

        $s['mobassist_api_key'] = $this->api_key;

        Mobileassistant_helper::editSetting(
            $this->db,
            Mobileassistant_helper::MODULE_SETTING_CODE,
            $s
        );

        $data['registration_id']     = $this->registration_id;
        $data['app_connection_id']   = $this->app_connection_id;
        $data['store_id']            = $this->store_id;
        $data['push_new_order']      = $this->push_new_order;
        $data['push_order_statuses'] = $this->push_order_statuses;
        $data['push_new_customer']   = $this->push_new_customer;
        $data['push_currency_code']  = $this->push_currency_code;
        $data['user_id']             = $this->module_user['user_id'];

        if (!empty($this->registration_id_old)) {
            $data['registration_id_old'] = $this->registration_id_old;
        }

        if (Mobileassistant_helper::getModel('connector', $this)->savePushNotificationSettings($data)) {
            $this->map_push_notification_to_device();
            return array('success' => 'true');
        }

        $error = 'Unknown occurred!';

        Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH SETTINGS ERROR: ' . $error);
        return array('error' => $error);
    }

    public function push_new_order($route, $order_id = 0)
    {
        if (!$this->check_module_installed()) {
            return;
        }

        if (version_compare(Mobileassistant_helper::getCartVersion(), '2.2.0.0', '<')) {
            $order_id = $route;
        }

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($order_id);

        if (!$order) {
            if (self::DEBUG_MODE) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: function: '
                    . __FUNCTION__ . ': Order not found');
            }

            return;
        }

        $type = self::PUSH_TYPE_NEW_ORDER;
        $this->sendOrderPushMessage($order, $type);
    }

    public function push_new_order_23x($route, $order_info, $order_id)
    {
        $this->push_new_order($route, $order_id);
    }

    public function push_new_order_156x($order_id, $total = 0)
    {
        if (!$this->check_module_installed()) {
            return;
        }

        $this->load->model('sale/order');
        $order = $this->model_sale_order->getOrder($order_id);

        if (!$order) {
            if (self::DEBUG_MODE) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: function: '
                    . __FUNCTION__ . ': Order not found');
            }
            return;
        }

        if (!isset($order['total']) || $order['total'] == 0) {
            $order['total'] = $total;
        }

        if (!isset($order['order_status'])) {
            if ($order['order_status_id'] == 0) {

                $default_attrs         = Mobileassistant_helper::getModel('helper', $this)->_get_default_attrs();
                $order['order_status'] = $default_attrs['text_missing'];
            } else {
                $sql   =
                    'SELECT name FROM ' . DB_PREFIX . "order_status WHERE language_id = '" . $this->getAdminLanguageId()
                    . "' AND order_status_id = '" . $order['order_status_id'] . "'";
                $query = $this->db->query($sql);
                if ($query->num_rows) {
                    $order['order_status'] = $query->row['name'];
                } else {
                    $order['order_status'] = '';
                }
            }
        }

        $type = self::PUSH_TYPE_NEW_ORDER;
        $this->sendOrderPushMessage($order, $type);
    }

    public function push_change_status($route, $response = '', $order_id = 0, $data = '')
    {
        if (!$this->check_module_installed()) {
            return;
        }

        if (version_compare(Mobileassistant_helper::getCartVersion(), '2.2.0.0', '<')) {
            $order_id = $route;
        } elseif (version_compare(Mobileassistant_helper::getCartVersion(), '2.3.0.0', '>=')) {
            $order_id = $response[0];
        } else {
            if (self::DEBUG_MODE) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: $route: ' . $route
                    . ' | function: ' . __FUNCTION__);
            }
        }

        $this->load->model('checkout/order');

        $order = $this->model_checkout_order->getOrder($order_id);

        if (!$order) {
            if (self::DEBUG_MODE) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: function: '
                    . __FUNCTION__ . ': Order not found');
            }

            return;
        }

        $type = self::PUSH_TYPE_CHANGE_ORDER_STATUS;
        $this->sendOrderPushMessage($order, $type);
    }

    public function push_change_status_pre($route, $order_id = 0)
    {
        if (!$this->check_module_installed()) {
            return;
        }

        if (version_compare(Mobileassistant_helper::getCartVersion(), '2.2.0.0', '<')) {
            $order_id = is_array($route) ? $route['order_id'] : $route;
        } elseif (version_compare(Mobileassistant_helper::getCartVersion(), '2.3.0.0', '>=')) {
            $order_id = $order_id[0];
        } else {
            if (self::DEBUG_MODE) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: $route: ' . $route
                    . ' | function: ' . __FUNCTION__);
            }
        }

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($order_id);

        if (!$order) {
            if (self::DEBUG_MODE) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: function: '
                    . __FUNCTION__ . ': Order not found');
            }

            return;
        }

        if ($order['order_status_id'] == 0) {
            $type = self::PUSH_TYPE_CHANGE_ORDER_STATUS;
            $this->sendOrderPushMessage($order, $type);
        }
    }

    public function push_change_status_156x($order_id, $data)
    {
        if (!$this->check_module_installed()) {
            return;
        }

        $this->load->model('sale/order');
        $order = $this->model_sale_order->getOrder($order_id);

        if (!$order) {
            if (self::DEBUG_MODE) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: function: '
                    . __FUNCTION__ . ': Order not found');
            }
            return;
        }

        $order['order_status_id'] = $data['order_status_id'];

        if (!isset($order['order_status'])) {
            if ($order['order_status_id'] == 0) {
                $default_attrs         = Mobileassistant_helper::getModel('helper', $this)->_get_default_attrs();
                $order['order_status'] = $default_attrs['text_missing'];
            } else {
                $sql   = 'SELECT `name` FROM ' . DB_PREFIX . "order_status WHERE language_id = '"
                    . $this->getAdminLanguageId() . "' AND order_status_id = '" . $order['order_status_id'] . "'";
                $query = $this->db->query($sql);
                if ($query->num_rows) {
                    $order['order_status'] = $query->row['name'];
                } else {
                    $order['order_status'] = '';
                }
            }
        }

        $type = self::PUSH_TYPE_CHANGE_ORDER_STATUS;
        $this->sendOrderPushMessage($order, $type);
    }

    public function push_new_customer($route, $customer_id = 0)
    {
        if (!$this->check_module_installed()) {
            return;
        }

        if (version_compare(Mobileassistant_helper::getCartVersion(), '2.2.0.0', '<')) {
            $customer_id = $route;
        } else {
            if (self::DEBUG_MODE) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: $route: ' . $route
                    . ' | function: ' . __FUNCTION__);
            }
        }

        $this->load->model('account/customer');
        $customer = $this->model_account_customer->getCustomer($customer_id);

        if (!$customer) {
            if (self::DEBUG_MODE) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: function: '
                    . __FUNCTION__ . ': Customer not found');
            }

            return;
        }

        $this->sendCustomerPushMessage($customer);
    }

    public function push_new_customer_23x($route, $customer_info, $customer_id)
    {
        $this->push_new_customer($route, $customer_id);
    }

    public function push_new_customer_156x($customer_id)
    {
        if (!$this->check_module_installed()) {
            return;
        }

        $this->load->model('sale/customer');
        $customer = $this->model_sale_customer->getCustomer($customer_id);

        if (!$customer) {
            return;
        }

        $this->sendCustomerPushMessage($customer);
    }

    private function sendOrderPushMessage($order, $type)
    {
        $data = array('store_id' => $this->config->get('config_store_id'));

        if ($type == self::PUSH_TYPE_NEW_ORDER && $order['order_status_id'] == 0) {
            $data['missing_new_order'] = true;
        } elseif ($type == self::PUSH_TYPE_CHANGE_ORDER_STATUS) {
            $data['status'] = $order['order_status_id'];

            if ($order['order_status_id'] == 0) {
                $type                   = self::PUSH_TYPE_NEW_ORDER;
                $data['real_new_order'] = true;
            }
        }

        $data['type'] = $type;

        $push_devices = $this->getPushDevices($data);

        if (self::DEBUG_MODE) {
            Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: $type: ' . $type);
        }

        if (!$push_devices || empty($push_devices)) {
            if (self::DEBUG_MODE) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: function: '
                    . __FUNCTION__ . ': Devices not found');
            }

            return;
        }

        $url = defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER;
        $url = str_replace(array('http://', 'https://'), '', $url);

        foreach ($push_devices as $push_device) {
            if (empty($push_device['push_currency_code']) || $push_device['push_currency_code'] == 'not_set') {
                $currency_code = (isset($order['currency_code']) ? $order['currency_code'] : $order['currency']);
            } elseif ($push_device['push_currency_code'] == 'base_currency') {
                $currency_code = $this->config->get('config_currency');
            } else {
                $currency_code = $push_device['push_currency_code'];
            }

            $total = Mobileassistant_helper::getModel('helper', $this)->nice_price($order['total'], $currency_code);

            $message = array(
                'push_notif_type' => $type,
                'order_id' => $order['order_id'],
                'customer_name' => $order['firstname'] . ' ' . $order['lastname'],
                'email' => $order['email'],
                'new_status' => $order['order_status'],
                'total' => Mobileassistant_helper::getModel('helper', $this)
                    ->nice_price($order['total'], $currency_code),
                'store_url' => $url,
                'app_connection_id' => $push_device['app_connection_id']
            );

            if ($type == self::PUSH_TYPE_CHANGE_ORDER_STATUS) {
                $message['new_status_code'] = $order['order_status_id'];
            }

            $this->sendPush2Google($push_device['setting_id'], $push_device['registration_id'], $message);
            $this->sendFCM($push_device['setting_id'], $push_device['registration_id'], $message);
        }
    }

    public function delete_push_config()
    {
        if (!empty($this->app_connection_id) && !empty($this->registration_id)) {
            $sql   = 'SELECT setting_id, device_id FROM ' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
                . " WHERE registration_id = '%s' AND app_connection_id = '%s' AND user_id = '%d' GROUP BY device_id";
            $sql   = sprintf($sql, $this->registration_id, $this->app_connection_id, $this->module_user['user_id']);
            $query = $this->db->query($sql);

            if ($query->num_rows) {
                foreach ($query->rows as $row) {
                    $device_id = $query->row['device_id'];

                    if ($this->deletePushRegId($row['setting_id'])) {
                        $this->delete_empty_devices($device_id);
                    }
                }
            }

            return array('success' => 'true');
        }

        return array('error' => 'missing_parameters');
    }

    public function delete_empty_devices($device_id)
    {
        $sql_d   = 'SELECT setting_id FROM ' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
            . " WHERE device_id = '%d' AND user_id = '%d'";
        $sql_d   = sprintf($sql_d, $device_id, $this->module_user['user_id']);
        $query_d = $this->db->query($sql_d);

        if ($query_d->num_rows <= 0) {
            $sql = 'DELETE FROM ' . DB_PREFIX . self::T_DEVICES . " WHERE device_id = '%d'";
            $sql = sprintf($sql, $device_id);
            $this->db->query($sql);
        }
    }

    public function sendCustomerPushMessage($customer)
    {
        $type = self::PUSH_TYPE_NEW_CUSTOMER;
        $data = array('store_id' => $this->config->get('config_store_id'), 'type' => $type);

        $push_devices = $this->getPushDevices($data);

        if (!$push_devices || count($push_devices) <= 0) {
            if (self::DEBUG_MODE) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: function: '
                    . __FUNCTION__ . ': Devices not found');
            }
            return;
        }

        $url = '';
        if (defined('HTTP_CATALOG')) {
            $url = HTTP_CATALOG;
        } else {
            if (defined('HTTP_SERVER')) {
                $url = HTTP_SERVER;
            }
        }

        $url = str_replace(array('http://', 'https://'), '', $url);

        foreach ($push_devices as $push_device) {
            $message = array(
                'push_notif_type' => $type,
                'customer_id' => $customer['customer_id'],
                'customer_name' => $customer['firstname'] . ' ' . $customer['lastname'],
                'email' => $customer['email'],
                'store_url' => $url,
                'app_connection_id' => $push_device['app_connection_id']
            );

            $this->sendPush2Google($push_device['setting_id'], $push_device['registration_id'], $message);
            $this->sendFCM($push_device['setting_id'], $push_device['registration_id'], $message);
        }
    }

    private function sendPush2Google($setting_id, $registration_id, $message)
    {
        if (function_exists('curl_version')) {
            $s = Mobileassistant_helper::getSetting(
                $this->db,
                Mobileassistant_helper::MODULE_SETTING_CODE
            );

            $apiKey = self::MOB_ASSIST_API_KEY;
            if (isset($s['mobassist_api_key'])) {
                $apiKey = $s['mobassist_api_key'];
            }

            $headers = array(
                'Authorization: key=' . $apiKey,
                'Content-Type: application/json'
            );

            $data = array(
                'registration_ids' => array($registration_id),
                'data' => array('message' => $message)
            );
            $data = json_encode($data);

            if (self::DEBUG_MODE) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: ' . $data);
            }

            $url = 'https://android.googleapis.com/gcm/send';
            $ch  = curl_init();
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt_array(
                $ch,
                array(
                    CURLOPT_URL => $url,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_POSTFIELDS => $data
                )
            );

            $response = curl_exec($ch);
            $info     = curl_getinfo($ch);

            $this->onResponse($setting_id, $response, $info);
        } else {
            Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: no cURL installed');
        }
    }

    private function sendFCM($settingId, $registrationId, $message)
    {
        if (function_exists('curl_version')) {
            $headers = array(
                'Authorization: key=AAAAab3qeg4:APA91bHPKeTH10KjPzZzyepq8xGE-XqXYGd3Nr2QVCa4GUlrY8VfCAFgcP' .
                'A3kSYBg_glweJ8hiMp8HKXkSN1DTm5DFTURBmBJImGLgi7L1_77w8TcXqx054g484iM17QpqPBWDbIIk8v',
                'Content-Type: application/json'
            );

            $data = array(
                'to' => $registrationId,
                // If we ever will work with notification title/body.
                // Should be fixed firstly from firebase side - https://github.com/firebase/quickstart-android/issues/4
                // 'notification'  => array(
                //     'body'          => $notificationTitle,
                //     'title'         => $notificationBody,
                //     'icon'          => 'ic_launcher',
                //     'sound'         => 'default',
                //     'badge'         => '1'
                // ),
                'data' => array('message' => json_encode($message)),
                'priority' => 'high'
            );
            $data = json_encode($data);

            if (self::DEBUG_MODE) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: ' . $data);
            }

            $url = 'https://fcm.googleapis.com/fcm/send';
            $ch  = curl_init();
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt_array(
                $ch,
                array(
                    CURLOPT_URL => $url,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_POSTFIELDS => $data
                )
            );

            $result = curl_exec($ch);
            $info   = curl_getinfo($ch);

            $this->onResponse($settingId, $result, $info);
        } else {
            Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: no cURL installed');
        }
    }

    public function onResponse($setting_id, $response, $info)
    {
        $code = $info != null && isset($info['http_code']) ? $info['http_code'] : 0;

        $codeGroup = (int)($code / 100);
        if ($codeGroup == 5) {
            Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH RESPONSE: code: ' . $code
                . ' :: GCM server not available');
            return;
        }

        if ($code !== 200) {
            Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH RESPONSE: code: ' . $code);
            return;
        }

        if (!$response || strlen(trim($response)) == null) {
            Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH RESPONSE: null response');
            return;
        }

        if ($response) {
            $json = json_decode($response, true);

            if (!$json) {
                Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH RESPONSE: json decode error');
            }
        }

        $failure      = isset($json['failure']) ? $json['failure'] : null;
        $canonicalIds = isset($json['canonical_ids']) ? $json['canonical_ids'] : null;

        if ($failure || $canonicalIds) {
            $results = isset($json['results']) ? $json['results'] : array();

            foreach ($results as $result) {
                $newRegId = isset($result['registration_id']) ? $result['registration_id'] : null;
                $error    = isset($result['error']) ? $result['error'] : null;

                if ($newRegId) {
                    $this->updatePushRegId($setting_id, $newRegId);
                } else {
                    if ($error) {
                        if ($error == 'NotRegistered' || $error == 'InvalidRegistration') {
                            $this->deletePushRegId($setting_id);
                        }

                        Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH RESPONSE: error: ' . $error);
                    }
                }
            }
        }
    }

    public function updatePushRegId($setting_id, $new_reg_id)
    {
        $sql =
            'UPDATE ' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . " SET registration_id = '%s' WHERE setting_id = '%d'";
        $sql = sprintf($sql, $new_reg_id, $setting_id);
        $this->db->query($sql);
    }

    public function deletePushRegId($setting_id)
    {
        $sql = 'DELETE FROM ' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . " WHERE setting_id = '%d'";
        $sql = sprintf($sql, $setting_id);
        return $this->db->query($sql);
    }

    public function getPushDevices($data = array())
    {
        $column = 'status';
        $sql    = 'SHOW COLUMNS FROM `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "` WHERE `Field` = '$column'";
        $q      = $this->db->query($sql);
        if (!$q->num_rows) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "` ADD " . $column
                . " INT(1) NOT NULL DEFAULT '1'");
        }

        $action_type = 'push_new_order';
        if ($data['type'] == self::PUSH_TYPE_CHANGE_ORDER_STATUS) {
            $action_type = 'push_order_status_changed';
        } elseif ($data['type'] == self::PUSH_TYPE_NEW_CUSTOMER) {
            $action_type = 'push_new_customer';
        }

        $sql = 'SELECT pn.setting_id, pn.registration_id, pn.app_connection_id, pn.push_currency_code
                FROM ' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . ' AS pn
                LEFT JOIN `' . DB_PREFIX . self::T_USERS . "` AS u ON u.user_id = pn.user_id
                WHERE u.allowed_actions LIKE '%" . $action_type . "%'";

        switch ($data['type']) {
            case self::PUSH_TYPE_NEW_ORDER:
                $query_where[] = " pn.push_new_order = '1' ";
                break;
            case self::PUSH_TYPE_CHANGE_ORDER_STATUS:
                $query_where[] =
                    sprintf(" (pn.push_order_statuses = '%s' OR pn.push_order_statuses LIKE '%%|%s' OR pn.push_order_statuses LIKE '%s|%%' OR pn.push_order_statuses LIKE '%%|%s|%%' OR pn.push_order_statuses = '-1') ",
                        $data['status'], $data['status'], $data['status'], $data['status']);
                break;
            case self::PUSH_TYPE_NEW_CUSTOMER:
                $query_where[] = " pn.push_new_customer = '1' ";
                break;
            default:
                return false;
        }

        $query_where[] = sprintf(" (pn.store_id = '-1' OR pn.store_id = '%d') ", $data['store_id']);
        $query_where[] = " pn.status = '1' ";
        $query_where[] = " u.user_status = '1' ";

        if (isset($data['missing_new_order']) && $data['missing_new_order']) {
            $query_where[] = " u.mobassist_disable_mis_ord_notif = '0' ";
        }

        if (isset($data['real_new_order']) && $data['real_new_order']) {
            $query_where[] = " u.mobassist_disable_mis_ord_notif = '1' ";
        }

        if (!empty($query_where)) {
            $sql .= ' AND ' . implode(' AND ', $query_where);
        }

        if (self::DEBUG_MODE) {
            Mobileassistant_helper::getModel('helper', $this)->write_log('PUSH REQUEST DATA: getPushDevices: ' . $sql);
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    private function check_module_installed()
    {
        $s = Mobileassistant_helper::getSetting(
            $this->db,
            Mobileassistant_helper::MODULE_SETTING_CODE
        );

        return !empty($s);
    }

    private function get_filter_statuses($statuses)
    {
        $statuses = explode('|', $statuses);

        if (!empty($statuses)) {
            $stat = array();

            foreach ($statuses as $status) {
                if (!empty($status)) {
                    $stat[] = $status;
                }
            }

            $parse_statuses = implode("','", $stat);

            return $parse_statuses;
        }

        return $statuses;
    }

    private function get_custom_period($period = 0)
    {
        $custom_period = array('start_date' => '', 'end_date' => '');
        $format        = 'm/d/Y';

        switch ($period) {
            case 0: //3 days
                $custom_period['start_date'] = date($format, mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')));
                $custom_period['end_date']   = date($format, mktime(23, 59, 59, date('m'), date('d'), date('Y')));
                break;

            case 1: //7 days
                $custom_period['start_date'] = date($format, mktime(0, 0, 0, date('m'), date('d') - 6, date('Y')));
                $custom_period['end_date']   = date($format, mktime(23, 59, 59, date('m'), date('d'), date('Y')));
                break;

            case 2: //Prev week
                /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
                $custom_period['start_date'] =
                    date($format, mktime(0, 0, 0, date('n'), date('j') - 6, date('Y')) - (date('N') * 3600 * 24));
                $custom_period['end_date']   =
                    date($format, mktime(23, 59, 59, date('n'), date('j'), date('Y')) - (date('N') * 3600 * 24));
                break;

            case 3: //Prev month
                $custom_period['start_date'] = date($format, mktime(0, 0, 0, date('m') - 1, 1, date('Y')));
                $custom_period['end_date']   =
                    date($format, mktime(23, 59, 59, date('m'), date('d') - date('j'), date('Y')));
                break;

            case 4: //This quarter
                $m       = date('n');
                $start_m = 1;
                $end_m   = 3;

                if ($m <= 3) {
                    $start_m = 1;
                    $end_m   = 3;
                } else {
                    if ($m >= 4 && $m <= 6) {
                        $start_m = 4;
                        $end_m   = 6;
                    } else {
                        if ($m >= 7 && $m <= 9) {
                            $start_m = 7;
                            $end_m   = 9;
                        } else {
                            if ($m >= 10) {
                                $start_m = 10;
                                $end_m   = 12;
                            }
                        }
                    }
                }

                $custom_period['start_date'] = date($format, mktime(0, 0, 0, $start_m, 1, date('Y')));
                $custom_period['end_date']   = date($format, mktime(23, 59, 59, $end_m + 1, date(1) - 1, date('Y')));
                break;

            case 5: //This year
                $custom_period['start_date'] = date($format, mktime(0, 0, 0, date(1), date(1), date('Y')));
                $custom_period['end_date']   = date($format, mktime(23, 59, 59, date(1), date(1) - 1, date('Y') + 1));
                break;

            case 6: //Last year
                $custom_period['start_date'] = date($format, mktime(0, 0, 0, date(1), date(1), date('Y') - 1));
                $custom_period['end_date']   = date($format, mktime(23, 59, 59, date(1), date(1) - 1, date('Y')));
                break;

            case 8: //Last quarter
                $m           = date('n');
                $start_m     = 1;
                $end_m       = 3;
                $year_offset = 0;

                if ($m <= 3) {
                    $start_m     = 10;
                    $end_m       = 12;
                    $year_offset = -1;
                } elseif ($m >= 4 && $m <= 6) {
                    $start_m = 1;
                    $end_m   = 3;
                } elseif ($m >= 7 && $m <= 9) {
                    $start_m = 4;
                    $end_m   = 6;
                } elseif ($m >= 10) {
                    $start_m = 7;
                    $end_m   = 9;
                }

                $custom_period['start_date'] = date($format, mktime(0, 0, 0, $start_m, 1, date('Y')));
                $custom_period['end_date']   =
                    date($format, mktime(23, 59, 59, $end_m + 1, date(1) + $year_offset, date('Y')));
                break;
        }

        return $custom_period;
    }

    public function clear_old_data()
    {
        $timestamp = time();
        $date      = date('Y-m-d H:i:s', ($timestamp - self::MAX_LIFETIME));

        $s = Mobileassistant_helper::getSetting(
            $this->db,
            Mobileassistant_helper::MODULE_SETTING_CODE
        );

        if (!isset($s['mobassist_cl_date'])) {
            $s['mobassist_cl_date'] = 1;
        }
        $date_clear_prev = $s['mobassist_cl_date'];

        if ($date_clear_prev === false || ($timestamp - (int)$date_clear_prev) > self::MAX_LIFETIME) {
            $sql = "DELETE FROM `" . DB_PREFIX . self::T_SESSION_KEYS . "` WHERE `date_added` < '%s'";
            $sql = sprintf($sql, $date);
            $this->db->query($sql);

            $sql = "DELETE FROM `" . DB_PREFIX . self::T_FAILED_LOGIN . "` WHERE `date_added` < '%s'";
            $sql = sprintf($sql, $date);
            $this->db->query($sql);

            $s['mobassist_cl_date'] = $timestamp;

            Mobileassistant_helper::editSetting(
                $this->db,
                Mobileassistant_helper::MODULE_SETTING_CODE,
                $s
            );
        }
    }

    public function get_session_key()
    {
        $timestamp = time();
        $key       = hash(self::HASH_ALGORITHM, $this->module_user['username'] . $timestamp . rand(1111, 99999));

        $column = 'user_id';
        $sql    = 'SHOW COLUMNS FROM `' . DB_PREFIX . self::T_SESSION_KEYS . "` WHERE `Field` = '" . $column . "'";
        $q      = $this->db->query($sql);
        if (!$q->num_rows) {
            $this->db->query('ALTER TABLE `' . DB_PREFIX . self::T_SESSION_KEYS . '` ADD ' . $column
                . ' INT(10) NOT NULL');
        }

        $sql = 'INSERT INTO `' . DB_PREFIX . self::T_SESSION_KEYS
            . "` SET session_key = '%s', date_added = '%s', user_id = '%d'";
        $sql = sprintf($sql, $key, date('Y-m-d H:i:s', $timestamp), $this->module_user['user_id']);

        $this->db->query($sql);

        return $key;
    }

    public function check_session_key($key)
    {
        if (!$key || empty($key)) {
            return false;
        }

        $timestamp = time();
        $sql       = 'SELECT `session_key`, user_id FROM `' . DB_PREFIX . self::T_SESSION_KEYS
            . "` WHERE `session_key` = '%s' AND `date_added` > '%s'";

        if ($this->module_user && isset($this->module_user['user_id'])) {
            $sql .= " AND `user_id` = '%d'";
            $sql = sprintf($sql, $key, date('Y-m-d H:i:s', ($timestamp - self::MAX_LIFETIME)),
                $this->module_user['user_id']);
        } else {
            $sql = sprintf($sql, $key, date('Y-m-d H:i:s', ($timestamp - self::MAX_LIFETIME)));
        }

        $q = $this->db->query($sql);

        if ($q->num_rows) {
            if (!$this->module_user) {
                $row               = $q->row;
                $this->module_user = Mobileassistant_helper::getModel('connector', $this)
                    ->getModuleUser(array('user_id' => $row['user_id']));
            }

            if ($this->module_user) {
                if (isset($this->module_user['user_status']) && $this->module_user['user_status'] == 1) {
                    $this->clear_failed_login();
                    return true;
                }

                $this->generate_output('user_disabled');
                return false;
            }
        }

        $this->add_failed_login();

        return false;
    }

    private function add_failed_login()
    {
        $timestamp = time();

        $sql = 'INSERT INTO `' . DB_PREFIX . self::T_FAILED_LOGIN . "` SET `ip` = '%s', `date_added` = '%s'";
        $sql = sprintf($sql, $_SERVER['REMOTE_ADDR'], date('Y-m-d H:i:s', $timestamp));

        $this->db->query($sql);


        $sql = 'SELECT COUNT(`row_id`) AS count_row_id FROM `' . DB_PREFIX . self::T_FAILED_LOGIN
            . "` WHERE `ip` = '%s' AND `date_added` > '%s'";
        $sql = sprintf($sql, $_SERVER['REMOTE_ADDR'], date('Y-m-d H:i:s', ($timestamp - self::MAX_LIFETIME)));

        $query = $this->db->query($sql);
        if ($query->num_rows) {
            $row = $query->row;

            $this->set_delay((int)$row['count_row_id']);
        }
    }

    private function clear_failed_login()
    {
        $sql = 'DELETE FROM  `' . DB_PREFIX . self::T_FAILED_LOGIN . "` WHERE `ip` = '%s'";
        $sql = @sprintf($sql, $_SERVER['REMOTE_ADDR']);

        @$this->db->query($sql);
    }

    private function set_delay($count_attempts)
    {
        if ($count_attempts > 50) {
            sleep(15);

        } elseif ($count_attempts > 30) {
            sleep(8);

        } elseif ($count_attempts > 20) {
            sleep(5);
        }
    }

    private function map_push_notification_to_device()
    {
        if (!$this->device_unique_id && !$this->account_email) {
            return;
        }

        $date      = date('Y-m-d H:i:s');
        $device_id = 0;

        $sql   = 'SELECT `device_id` FROM `' . DB_PREFIX . self::T_DEVICES
            . "` WHERE `device_unique_id` = '%s' AND account_email = '%s' AND user_id = '%d'";
        $sql   = sprintf($sql, $this->device_unique_id, $this->account_email, $this->module_user['user_id']);
        $query = $this->db->query($sql);

        if ($query->num_rows <= 0) {
            $sql = 'INSERT INTO `' . DB_PREFIX . self::T_DEVICES . "` (device_unique_id, account_email, device_name, last_activity, user_id)
			        VALUES ('%s', '%s', '%s', '%s', '%d')";
            $sql = sprintf($sql, $this->device_unique_id, $this->account_email, $this->device_name, $date,
                $this->module_user['user_id']);

            $this->db->query($sql);

            $device_id = $this->db->getLastId();

        } else {
            $row       = $query->row;
            $device_id = $row['device_id'];

            $sql = 'UPDATE `' . DB_PREFIX . self::T_DEVICES
                . "` SET device_name = '%s', last_activity = '%s', user_id = '%d' WHERE device_id = '%d'";
            $sql = sprintf($sql, $this->device_name, $date, $this->module_user['user_id'], $device_id);
            $this->db->query($sql);
        }

        if (!$this->registration_id || $this->call_function == 'delete_push_config') {
            return;
        }

        if ($device_id > 0) {
            $column = 'device_id';
            $sql    =
                'SHOW COLUMNS FROM `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "` WHERE `Field` = '" . $column . "'";
            $q      = $this->db->query($sql);
            if (!$q->num_rows) {
                $this->db->query('ALTER TABLE `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . '` ADD ' . $column
                    . ' INT(10) NOT NULL');
            }

            $column = 'user_id';
            $sql    =
                'SHOW COLUMNS FROM `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "` WHERE `Field` = '" . $column . "'";
            $q      = $this->db->query($sql);
            if (!$q->num_rows) {
                $this->db->query('ALTER TABLE `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . '` ADD ' . $column
                    . ' INT(10) NOT NULL');
            }

            $column = 'status';
            $sql    =
                'SHOW COLUMNS FROM `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "` WHERE `Field` = '" . $column . "'";
            $q      = $this->db->query($sql);
            if (!$q->num_rows) {
                $this->db->query('ALTER TABLE `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . '` ADD ' . $column
                    . " INT(1) NOT NULL DEFAULT '1'");
            }

            if ($this->registration_id != '') {
                $sql_upd = 'UPDATE ' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
                    . " SET device_id = '%s' WHERE registration_id = '%s' AND user_id = '%d'";
                $sql_upd = sprintf($sql_upd, $device_id, $this->registration_id, $this->module_user['user_id']);
                $this->db->query($sql_upd);
            }
        }
    }

    private function update_device_last_activity()
    {
        if ($this->device_unique_id) {
            $sql = 'UPDATE ' . DB_PREFIX . self::T_DEVICES
                . " SET last_activity = '%s' WHERE device_unique_id = '%s' AND account_email = '%s'";
            $sql = sprintf($sql, date('Y-m-d H:i:s'), $this->device_unique_id, $this->account_email);
            $this->db->query($sql);
        }
    }

    private function _checkUpdateModule()
    {
        $s = Mobileassistant_helper::getSetting(
            $this->db,
            Mobileassistant_helper::MODULE_SETTING_CODE
        );

        if (isset($s['mobassist_module_code']) && $s['mobassist_module_code'] < Mobileassistant_helper::MODULE_CODE) {
            Mobileassistant_helper::updateModule($this->db, $s);
            Mobileassistant_helper::checkAndAddEvents(
                $this,
                Mobileassistant_helper::AREA_FRONTEND
            );

            if (!isset($s['mobassist_cl_date'])) {
                $s['mobassist_cl_date'] = 1;
            }

            $settings = array(
                'mobassist_module_code' => Mobileassistant_helper::MODULE_CODE,
                'mobassist_module_version' => Mobileassistant_helper::MODULE_VERSION,
                'mobassist_cl_date' => $s['mobassist_cl_date']
            );

            Mobileassistant_helper::editSetting(
                $this->db,
                Mobileassistant_helper::MODULE_SETTING_CODE,
                $settings
            );
        }
    }

    public function getAdminLanguageId()
    {
        $query = $this->db->query(
            'SELECT * FROM `' . DB_PREFIX . "language` WHERE code = '"
            . $this->db->escape($this->config->get('config_admin_language')) . "'"
        );
        $lang  = $query->row;

        return (int)$lang['language_id'];
    }
}

class ControllerExtensionModuleMobileAssistantConnector extends BaseControllerModuleMobileAssistantConnector
{

}

class ControllerModuleMobileAssistantConnector extends BaseControllerModuleMobileAssistantConnector
{

}
