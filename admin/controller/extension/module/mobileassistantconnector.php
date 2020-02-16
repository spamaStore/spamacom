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
    private $error = array();

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
        if (isset($this->request->request['call_function'])) {
            $call_function = (string)$this->request->request['call_function'];

            if (!method_exists($this, $call_function)) {
                $this->generate_output('old_module');
            }

            $result = call_user_func(array($this, $call_function));
            $this->generate_output($result);
        }

        Mobileassistant_helper::checkAndAddEvents($this, Mobileassistant_helper::AREA_BACKEND);

        $this->load->model('setting/setting');
        $s                             = Mobileassistant_helper::getSetting(
            $this->db,
            Mobileassistant_helper::MODULE_SETTING_CODE,
            0,
            $this->model_setting_setting
        );
        $s['mobassist_module_code']    = Mobileassistant_helper::MODULE_CODE;
        $s['mobassist_module_version'] = Mobileassistant_helper::MODULE_VERSION;

        Mobileassistant_helper::createTables($this->db);

        $this->checkUpdateModule();

        Mobileassistant_helper::editSetting(
            $this->db,
            Mobileassistant_helper::MODULE_SETTING_CODE,
            $s,
            0,
            $this->model_setting_setting
        );

        $this->createForm();
    }

    public function install()
    {
        $this->load->model('setting/setting');

        $module_settings = array(
            'mobassist_module_code' => Mobileassistant_helper::MODULE_CODE,
            'mobassist_module_version' => Mobileassistant_helper::MODULE_VERSION,
            'mobassist_cl_date' => 1
        );

        Mobileassistant_helper::editSetting(
            $this->db,
            Mobileassistant_helper::MODULE_SETTING_CODE,
            $module_settings,
            0,
            $this->model_setting_setting
        );

        Mobileassistant_helper::checkAndAddEvents($this, Mobileassistant_helper::AREA_BACKEND);
        Mobileassistant_helper::createTables($this->db);

        $default_user                    = array('username' => 1);
        $default_user['user_status']     = 1;
        $default_user['password']        = 1;
        $default_user['allowed_actions'] = Mobileassistant_helper::getActionCodes();
        $this->saveUsers(array($default_user));
    }

    public function uninstall()
    {
        $this->load->model('setting/setting');
        Mobileassistant_helper::deleteSetting(
            $this->db,
            Mobileassistant_helper::MODULE_SETTING_CODE,
            0,
            $this->model_setting_setting
        );

        if (Mobileassistant_helper::isCartVersion20()) {
            if (version_compare(Mobileassistant_helper::getCartVersion(), '3.0', '>=')) {
                $this->load->model('setting/event');
                $this->model_setting_event->deleteEventByCode('mobileassistantconnector');
            } elseif (version_compare(Mobileassistant_helper::getCartVersion(), '2.0.1.0', '>=')) {
                $this->load->model('extension/event');
                $this->model_extension_event->deleteEvent('mobileassistantconnector');
            } else {
                $this->load->model('tool/event');
                $this->model_tool_event->deleteEvent('mobileassistantconnector');
            }
        }

        Mobileassistant_helper::getModel('connector', $this)->drop_tables();
    }

    private function checkUpdateModule()
    {
        $this->load->model('setting/setting');

        $s = Mobileassistant_helper::getSetting(
            $this->db,
            Mobileassistant_helper::MODULE_SETTING_CODE,
            0,
            $this->model_setting_setting
        );

        if (isset($s['mobassist_module_code']) && $s['mobassist_module_code'] < Mobileassistant_helper::MODULE_CODE) {
            Mobileassistant_helper::updateModule($this->db, $s);

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
                $settings,
                0,
                $this->model_setting_setting
            );
        }
    }

    private function createForm()
    {
        Mobileassistant_helper::getModel('connector', $this);

        $this->load->language(
            Mobileassistant_helper::isCartVersion23()
                ? 'extension/module/mobileassistantconnector'
                : 'module/mobileassistantconnector'
        );

        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        $d = array();

        if (version_compare(Mobileassistant_helper::getCartVersion(), '3.0', '>=')) {
            $token           = $this->session->data['user_token'];
            $token_param     = 'user_token';
            $d['user_token'] = $token;
        } else {
            $token       = $this->session->data['token'];
            $token_param = 'token';
            $d['token']  = $token;
        }

        if ($this->request->server['REQUEST_METHOD'] == 'GET' && $this->validate()) {
            if (version_compare(Mobileassistant_helper::getCartVersion(), '3.0', '>=')) {
                $args = array('user_token' => $token);
            } else {
                $args = array('token' => $token);
            }

            if (isset($this->request->get['user_id']) && $this->request->get['user_id'] > 0) {
                $args['user_id'] = $this->request->get['user_id'];
            }

            if (version_compare(Mobileassistant_helper::getCartVersion(), '2.2.0.0', '<')) {
                $args = http_build_query($args);
            }

            if (isset($this->request->get['enable_setting_id'])) {
                $this->actionPushDevices(1, $this->request->get['enable_setting_id']);
                $url = Mobileassistant_helper::isCartVersion23()
                    ? $this->url->link('extension/module/mobileassistantconnector', $args, 'SSL')
                    : $this->url->link('module/mobileassistantconnector', $args, 'SSL');

                if (Mobileassistant_helper::isCartVersion20()) {
                    $this->response->redirect($url);
                } else {
                    $this->redirect($url);
                }
            }

            if (isset($this->request->get['disable_setting_id'])) {
                $this->actionPushDevices(2, $this->request->get['disable_setting_id']);
                $url = Mobileassistant_helper::isCartVersion23()
                    ? $this->url->link('extension/module/mobileassistantconnector', $args, 'SSL')
                    : $this->url->link('module/mobileassistantconnector', $args, 'SSL');

                if (Mobileassistant_helper::isCartVersion20()) {
                    $this->response->redirect($url);
                } else {
                    $this->redirect($url);
                }
            }

            if (isset($this->request->get['delete_setting_id'])) {
                $this->actionPushDevices(3, $this->request->get['delete_setting_id']);
                $url = Mobileassistant_helper::isCartVersion23()
                    ? $this->url->link('extension/module/mobileassistantconnector', $args, 'SSL')
                    : $this->url->link('module/mobileassistantconnector', $args, 'SSL');

                if (Mobileassistant_helper::isCartVersion20()) {
                    $this->response->redirect($url);
                } else {
                    $this->redirect($url);
                }
            }
        }

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
            $users = $this->request->post['user'];
            if ($this->checkUsers($users)) {
                $this->saveUsers($users);

                $this->session->data['success'] = $this->language->get('text_success');

                if (isset($this->request->post['save_continue']) && $this->request->post['save_continue'] == 1) {
                    $link_path = Mobileassistant_helper::isCartVersion23()
                        ? 'extension/module/mobileassistantconnector'
                        : 'module/mobileassistantconnector';
                } elseif (Mobileassistant_helper::isCartVersion30()) {
                    $link_path = 'marketplace/extension';
                } elseif (Mobileassistant_helper::isCartVersion23()) {
                    $link_path = 'extension/extension';
                } else {
                    $link_path = 'extension/module';
                }

                $url = $this->url->link($link_path, $token_param . '=' . $token, 'SSL');

                if (Mobileassistant_helper::isCartVersion20()) {
                    $this->response->redirect($url);
                } else {
                    $this->redirect($url);
                }
            }
        }

        $d['is_ver20'] = Mobileassistant_helper::isCartVersion20();
        $d['is_ver23'] = Mobileassistant_helper::isCartVersion23();
        $d['is_ver30'] = Mobileassistant_helper::isCartVersion30();

        if (isset($this->session->data['success'])) {
            $d['saving_success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $d['saving_success'] = '';
        }

        $d['heading_title'] = $this->language->get('heading_title');

        $d['text_enabled']  = $this->language->get('text_enabled');
        $d['text_disabled'] = $this->language->get('text_disabled');
        $d['text_edit']     = $this->language->get('text_edit');

        $d['entry_login'] = $this->language->get('entry_login');
        $d['help_login']  = $this->language->get('help_login');

        $d['entry_pass'] = $this->language->get('entry_pass');
        $d['help_pass']  = $this->language->get('help_pass');

        $d['entry_disable_mis_ord_notif'] = $this->language->get('entry_disable_mis_ord_notif');

        $d['entry_qr'] = $this->language->get('entry_qr');
        $d['help_qr']  = $this->language->get('help_qr');

        $d['entry_status'] = $this->language->get('entry_status');

        $d['module_version']    = $this->language->get('module_version');
        $d['connector_version'] = Mobileassistant_helper::MODULE_VERSION;

        $d['useful_links']      = $this->language->get('useful_links');
        $d['check_new_version'] = $this->language->get('check_new_version');
        $d['submit_ticket']     = $this->language->get('submit_ticket');
        $d['documentation']     = $this->language->get('documentation');

        $d['button_save']          = $this->language->get('button_save');
        $d['button_save_continue'] = $this->language->get('button_save_continue');
        $d['button_cancel']        = $this->language->get('button_cancel');

        $d['error_login_details_changed'] = $this->language->get('error_login_details_changed');


        $d['push_messages_settings']      = $this->language->get("push_messages_settings");
        $d['push_messages_settings_help'] = $this->language->get("push_messages_settings_help");

        $d['device_name']       = $this->language->get("device_name");
        $d['account_email']     = $this->language->get("account_email");
        $d['last_activity']     = $this->language->get("last_activity");
        $d['select_all_none']   = $this->language->get("select_all_none");
        $d['app_connection_id'] = $this->language->get("app_connection_id");
        $d['store']             = $this->language->get("store");
        $d['new_order']         = $this->language->get("new_order");
        $d['new_customer']      = $this->language->get("new_customer");
        $d['order_statuses']    = $this->language->get("order_statuses");
        $d['currency']          = $this->language->get("currency");
        $d['status']            = $this->language->get("status");
        $d['delete']            = $this->language->get("delete");
        $d['unknown']           = $this->language->get("unknown");

        $d['disable']      = $this->language->get("disable");
        $d['enabled']      = $this->language->get("enabled");
        $d['enable']       = $this->language->get("enable");
        $d['disabled']     = $this->language->get("disabled");
        $d['are_you_sure'] = $this->language->get("are_you_sure");
        $d['no_data']      = $this->language->get("no_data");

        $d['bulk_actions']             = $this->language->get("bulk_actions");
        $d['enable_selected_devices']  = $this->language->get("enable_selected_devices");
        $d['disable_selected_devices'] = $this->language->get("disable_selected_devices");
        $d['delete_selected_devices']  = $this->language->get("delete_selected_devices");

        $d['please_select_push_settings'] = $this->language->get("please_select_push_settings");

        $d['mac_user']                  = $this->language->get('mac_user');
        $d['mac_add_user']              = $this->language->get('mac_add_user');
        $d['mac_get_app_from_gp']       = $this->language->get('mac_get_app_from_gp');
        $d['mac_click_or_scan_qr']      = $this->language->get('mac_click_or_scan_qr');
        $d['mac_permissions']           = $this->language->get('mac_permissions');
        $d['mac_regenerate_hash_url']   = $this->language->get('mac_regenerate_hash_url');
        $d['mac_push_notifications']    = $this->language->get('mac_push_notifications');
        $d['mac_new_order_created']     = $this->language->get('mac_new_order_created');
        $d['mac_order_status_changed']  = $this->language->get('mac_order_status_changed');
        $d['mac_new_customer_created']  = $this->language->get('mac_new_customer_created');
        $d['mac_store_statistics']      = $this->language->get('mac_store_statistics');
        $d['mac_view_store_statistics'] = $this->language->get('mac_view_store_statistics');
        $d['mac_orders']                = $this->language->get('mac_orders');
        $d['mac_view_order_list']       = $this->language->get('mac_view_order_list');
        $d['mac_view_order_details']    = $this->language->get('mac_view_order_details');
        $d['mac_change_order_status']   = $this->language->get('mac_change_order_status');
        $d['mac_order_picking']         = $this->language->get('mac_order_picking');
        $d['mac_customers']             = $this->language->get('mac_customers');
        $d['mac_view_customer_list']    = $this->language->get('mac_view_customer_list');
        $d['mac_view_customer_details'] = $this->language->get('mac_view_customer_details');
        $d['mac_products']              = $this->language->get('mac_products');
        $d['mac_view_product_list']     = $this->language->get('mac_view_product_list');
        $d['mac_view_product_details']  = $this->language->get('mac_view_product_details');
        $d['mac_view_product_edit']     = $this->language->get('mac_view_product_edit');
        $d['mac_view_product_add']      = $this->language->get('mac_view_product_add');
        $d['mac_all']                   = $this->language->get('mac_all');
        $d['mac_not_set']               = $this->language->get('mac_not_set');
        $d['mac_base_currency']         = $this->language->get('mac_base_currency');
        $d['hash_of_one']               = md5(1);

        if (defined('HTTP_CATALOG')) {
            $d['url'] = HTTP_CATALOG;
        } elseif (defined('HTTP_SERVER')) {
            $d['url'] = HTTP_SERVER;
        }

        if (isset($this->error['warning'])) {
            $d['error_warning'] = $this->error['warning'];
        } else {
            $d['error_warning'] = '';
        }

        $d['breadcrumbs'] = array();

        $d['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link(
                Mobileassistant_helper::isCartVersion23()
                    ? 'common/dashboard'
                    : 'common/home',
                $token_param . '=' . $token,
                'SSL'
            ),
            'separator' => false
        );

        $d['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link(
                Mobileassistant_helper::isCartVersion23()
                    ? 'extension/extension'
                    : 'extension/module',
                $token_param . '=' . $token,
                'SSL'
            ),
            'separator' => ' :: '
        );

        $d['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                Mobileassistant_helper::isCartVersion23()
                    ? 'extension/module/mobileassistantconnector'
                    : 'module/mobileassistantconnector',
                $token_param . '=' . $token,
                'SSL'
            ),
            'separator' => ' :: '
        );

        $d['action'] = $this->url->link(
            Mobileassistant_helper::isCartVersion23()
                ? 'extension/module/mobileassistantconnector'
                : 'module/mobileassistantconnector',
            $token_param . '=' . $token,
            'SSL'
        );

        $d['cancel'] = $this->url->link('extension/module', $token_param . '=' . $token,
            'SSL'
        );

        $users_info         = $this->getUsers();
        $d['users']         = $users_info['users'];
        $d['user_id_check'] = $d['users'][0]['user_id'];

        if (isset($users_info['message_info']) && $users_info['message_info'] == 'error_default_cred') {
            $d['message_info'] = $this->language->get('error_default_cred');
        } else {
            $d['message_info'] = '';
        }

        if (Mobileassistant_helper::isCartVersion20()) {
            if (!isset($data) || !is_array($data)) {
                $data = array();
            }

            $data = array_merge($data, $d);

            $data['header']      = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer']      = $this->load->controller('common/footer');

            if (version_compare(Mobileassistant_helper::getCartVersion(), '3.0', '>=')) {
                $this->response->setOutput(
                    $this->load->view(
                        'extension/module/mobileassistant',
                        $data
                    )
                );
            } elseif (version_compare(Mobileassistant_helper::getCartVersion(), '2.3', '>=')) {
                $this->response->setOutput(
                    $this->load->view('extension/module/mobileassistant.tpl', $data)
                );
            } else {
                $this->response->setOutput(
                    $this->load->view('module/mobileassistant.tpl', $data)
                );
            }

        } else {
            $this->data = array_merge($this->data, $d);
            $this->load->model('design/layout');
            $this->data['layouts'] = $this->model_design_layout->getLayouts();
            $this->template        = 'module/mobileassistant.tpl';
            $this->children        = array(
                'common/header',
                'common/footer'
            );

            $this->response->setOutput($this->render());
        }
    }

    private function getUsers()
    {
        return Mobileassistant_helper::getModel('connector', $this)->get_users();
    }

    private function checkUsers($users)
    {
        foreach ($users as $user) {
            if (!isset($user['username']) || strlen($user['username']) <= 0) {
                $this->error['warning'] = $this->language->get('error_empty_login');
                return false;
            }
        }

        return true;
    }

    private function saveUsers($users)
    {
        return Mobileassistant_helper::getModel('connector', $this)->save_users($users);
    }

    function generate_qr_code_hash()
    {
        if (isset($this->request->request['user_id']) && $this->request->request['user_id'] > 0) {
            $user_id = (int)$this->request->request['user_id'];

            $qr_code_hash = hash('sha256', md5(time() . rand(1111, 99999)));
            if (Mobileassistant_helper::getModel('connector', $this)->update_qr_code_hash($user_id, $qr_code_hash)) {
                return array("qr_code_hash" => $qr_code_hash);
            }
        }

        return false;
    }

    private function getPushDevices()
    {
        $push_devices = Mobileassistant_helper::getModel('connector', $this)->get_push_devices();
        $this->load->language(
            Mobileassistant_helper::isCartVersion23()
                ? 'extension/module/mobileassistantconnector'
                : 'module/mobileassistantconnector'
        );

        $devices = array();

        foreach ($push_devices as $push_device) {
            if (!$push_device['device_id'] || $push_device['device_id'] == '') {
                $push_device['device_name'] = 'Unknown';
            }

            $devices[$push_device['device_unique_id']]['device_id']     = $push_device['device_id'];
            $devices[$push_device['device_unique_id']]['device_name']   = $push_device['device_name'];
            $devices[$push_device['device_unique_id']]['account_email'] = $push_device['account_email'];
            $devices[$push_device['device_unique_id']]['last_activity'] = $push_device['last_activity'];

            $push_device['store_id_name'] = $this->language->get('mac_all');
            if (isset($push_device['store_id']) && $push_device['store_id'] != '' && $push_device['store_id'] != -1) {
                $this->load->model('setting/store');
                $all_stores[0] = $this->config->get('config_name');
                $stores        = $this->model_setting_store->getStores();

                foreach ($stores as $store) {
                    $all_stores[$store['store_id']] = $store['name'];
                }

                if (count($all_stores) > 0) {
                    $push_device['store_id_name'] = $all_stores[$push_device['store_id']];
                }
            }

            $push_device['push_currency_name'] = $this->language->get('mac_not_set');
            if (isset($push_device['push_currency_code']) && $push_device['push_currency_code'] != ''
                && $push_device['push_currency_code'] != 'not_set') {
                if ($push_device['push_currency_code'] == 'base_currency') {
                    $push_device['push_currency_name'] = $this->language->get('mac_base_currency');

                } else {
                    $this->load->model('localisation/currency');
                    $currencies     = $this->model_localisation_currency->getCurrencies();
                    $all_currencies = array();

                    foreach ($currencies as $currency) {
                        $all_currencies[$currency['code']] = $currency['title'];
                    }

                    if (count($all_currencies) > 0) {
                        $push_device['push_currency_name'] = $all_currencies[$push_device['push_currency_code']];
                    }
                }
            }

            $push_device['push_order_statuses_names'] = '-';
            if (isset($push_device['push_order_statuses']) && $push_device['push_order_statuses'] != '') {
                if ($push_device['push_order_statuses'] == '-1') {
                    $push_device['push_order_statuses_names'] = $this->language->get('mac_all');
                } else {
                    $orders_statuses = $this->get_orders_statuses($push_device['push_order_statuses']);

                    $push_device['push_order_statuses_names'] = implode(", ", $orders_statuses);
                }
            }

            $devices[$push_device['device_unique_id']]['push_settings'][] = array(
                'setting_id' => $push_device['setting_id'],
                'registration_id' => $push_device['registration_id'],
                'app_connection_id' => $push_device['app_connection_id'],
                'store_id_name' => $push_device['store_id_name'],
                'push_new_order' => $push_device['push_new_order'],
                'push_order_statuses_names' => $push_device['push_order_statuses_names'],
                'push_new_customer' => $push_device['push_new_customer'],
                'push_currency_name' => $push_device['push_currency_name'],
                'status' => $push_device['status'],
            );
        }

        return $devices;
    }


    private function actionPushDevices($action, $ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        if (count($ids) <= 0) {
            return;
        }

        switch ($action) {
            case 1:
                Mobileassistant_helper::getModel('connector', $this)->enable_push_devices($ids);
                break;
            case 2:
                Mobileassistant_helper::getModel('connector', $this)->disable_push_devices($ids);
                break;
            case 3:
                Mobileassistant_helper::getModel('connector', $this)->delete_push_devices($ids);
                break;
        }
    }

    protected function validate()
    {
        $error = true;

        if (!$this->user->hasPermission(
            'modify',
            Mobileassistant_helper::isCartVersion23()
                ? 'extension/module/mobileassistantconnector'
                : 'module/mobileassistantconnector'
        )
        ) {
            $this->error['warning'] = $this->language->get('error_permission');
            $error                  = false;
        }

        return $error;
    }

    private function generate_output($data)
    {
        if (!is_array($data)) {
            $data = array($data);
        }

        $data = json_encode($data);
        if ($this->callback) {
            header('Content-Type: text/javascript;charset=utf-8');
            die($this->callback . '(' . $data . ');');
        }

        header('Content-Type: text/javascript;charset=utf-8');
        die($data);
    }
}

class ControllerExtensionModuleMobileAssistantConnector extends BaseControllerModuleMobileAssistantConnector
{

}

class ControllerModuleMobileAssistantConnector extends BaseControllerModuleMobileAssistantConnector
{

}