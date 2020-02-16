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

class BaseModelMobileassistantConnector extends Model
{
    const T_SESSION_KEYS       = 'mobassistantconnector_session_keys';
    const T_FAILED_LOGIN       = 'mobassistantconnector_failed_login';
    const T_PUSH_NOTIFICATIONS = 'mobileassistant_push_settings';
    const T_DEVICES            = 'mobassistantconnector_devices';
    const T_USERS              = 'mobassistantconnector_users';
    private $is_ver20;
    private $is_ver23;
    private $is_ver30;
    private $opencart_version;

    public function get_users()
    {
        $sql = "SELECT
                    user_id,
                    user_status,
                    username,
                    password,
                    mobassist_disable_mis_ord_notif,
                    allowed_actions,
                    qr_code_hash
                FROM `" . DB_PREFIX . self::T_USERS . "` ORDER BY user_id ASC";

        $users        = array();
        $message_info = '';
        $query        = $this->db->query($sql);
        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $row['devices'] = $this->get_push_devices($row['user_id']);
                $row['qr_code'] = $this->get_QR_code($row);
                if (empty($row['username'])) {
                    $row['username'] = 1;
                }
                if (empty($row['password'])) {
                    $row['password'] = md5(1);
                }

                if (!empty($row['allowed_actions'])) {
                    $row['allowed_actions'] = json_decode($row['allowed_actions'], true);
                }

                if ($row['username'] == 1 && $row['password'] == md5(1)) {
                    $message_info = 'error_default_cred';
                }

                $users[] = $row;
            }
        }

        return array('users' => $users, 'message_info' => $message_info);
    }

    public function get_user($user_id)
    {
        if ($user_id <= 0) {
            return;
        }

        $sql = "SELECT
                    user_id,
                    user_status,
                    username,
                    password,
                    mobassist_disable_mis_ord_notif,
                    allowed_actions,
                    qr_code_hash
                FROM `" . DB_PREFIX . self::T_USERS . "` WHERE user_id = '%d'";

        $sql = sprintf($sql, $user_id);
        $this->db->query($sql);

        if ($sql->num_rows) {
            return $sql->row;
        }

        return false;
    }

    public function save_users($users)
    {
        $exists_users_ids = array();
        foreach ($users as $user) {
            if (!isset($user['allowed_actions'])) {
                $user['allowed_actions'] = '';
            }
            if (!isset($user['mobassist_disable_mis_ord_notif'])) {
                $user['mobassist_disable_mis_ord_notif'] = 0;
            }
            if (!empty($user['allowed_actions'])) {
                $user['allowed_actions'] = json_encode($user['allowed_actions']);
            }

            if (isset($user['user_id']) && $user['user_id'] > 0) {
                $exists_users_ids[] = $user['user_id'];
                $this->update_user($user);
            } else {
                $exists_users_ids[] = $this->add_user($user);
            }
        }

        $this->delete_users_not_in($exists_users_ids);
        $this->delete_users_sessions();
    }

    private function update_user($user)
    {
        $sql = "SELECT
                    user_id,
                    user_status,
                    username,
                    password,
                    mobassist_disable_mis_ord_notif,
                    allowed_actions,
                    qr_code_hash
                FROM `" . DB_PREFIX . self::T_USERS . "` WHERE user_id = '%d'";
        $sql = sprintf($sql, $user['user_id']);

        $query = $this->db->query($sql);
        if ($query->num_rows) {
            $old_user = $query->row;

            if ($user['password'] != "" && $old_user['password'] != $user['password']) {
                $user['password'] = md5($user['password']);
            }

            if (!isset($user['mobassist_disable_mis_ord_notif'])) {
                $user['mobassist_disable_mis_ord_notif'] = 0;
            }

            $sql = 'UPDATE `' . DB_PREFIX . self::T_USERS . "` SET
                    user_status = '%d',
                    username = '%s',
                    password = '%s',
                    mobassist_disable_mis_ord_notif = '%d',
                    allowed_actions = '%s'
                    WHERE user_id = '%d'";

            $sql = sprintf($sql, $user['user_status'], $user['username'], $user['password'],
                $user['mobassist_disable_mis_ord_notif'], $user['allowed_actions'], $user['user_id']);

            $this->db->query($sql);
        } else {
            $this->add_user($user);
        }
    }

    private function add_user($user)
    {
        $user['password'] = md5($user['password']);

        $sql = "INSERT INTO `" . DB_PREFIX . self::T_USERS . "` SET
                user_status = '%d',
                username = '%s',
                password = '%s',
                mobassist_disable_mis_ord_notif = '%d',
                allowed_actions = '%s',
                qr_code_hash = '%s'";

        $sql = sprintf($sql,
            $user['user_status'],
            $user['username'],
            $user['password'],
            $user['mobassist_disable_mis_ord_notif'],
            $user['allowed_actions'],
            hash('sha256', md5(time() . rand(1111, 99999))));

        $this->db->query($sql);

        return $this->db->getLastId();
    }

    private function delete_users_not_in($users_ids)
    {
        $sql = "DELETE FROM `" . DB_PREFIX . self::T_USERS . "` 
                WHERE user_id NOT IN ( '" . implode($users_ids, "', '") . "' )";
        $this->db->query($sql);

        $sql = "DELETE FROM `" . DB_PREFIX . self::T_DEVICES . "` 
                WHERE user_id NOT IN ( '" . implode($users_ids, "', '") . "' )";
        $this->db->query($sql);

        $sql = "DELETE FROM `" . DB_PREFIX . self::T_SESSION_KEYS . "` 
                WHERE user_id NOT IN ( '" . implode($users_ids, "', '") . "' )";
        $this->db->query($sql);

        $sql = "DELETE FROM `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "` 
                WHERE user_id NOT IN ( '" . implode($users_ids, "', '") . "' )";
        $this->db->query($sql);
    }

    private function delete_users_sessions()
    {
        $sql = 'DELETE FROM `' . DB_PREFIX . self::T_SESSION_KEYS . '`';
        $this->db->query($sql);

        $sql = 'DELETE FROM `' . DB_PREFIX . self::T_FAILED_LOGIN . '`';
        $this->db->query($sql);
    }

    public function update_qr_code_hash($user_id, $qr_code_hash)
    {
        $sql = 'UPDATE `' . DB_PREFIX . self::T_USERS . "` SET qr_code_hash = '%s' WHERE user_id = '%d'";
        $sql = sprintf($sql, $qr_code_hash, $user_id);

        return $this->db->query($sql);
    }

    public function get_push_devices($user_id)
    {
        $sql = "SELECT
                    d.device_id,
                    d.device_unique_id,
                    d.account_email,
                    d.device_name,
                    d.last_activity,
                    pn.setting_id,
                    pn.registration_id,
                    pn.app_connection_id,
                    pn.store_id,
                    pn.push_new_order,
                    pn.push_order_statuses,
                    pn.push_new_customer,
                    pn.push_currency_code,
                    pn.status
                FROM `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "` pn
                LEFT JOIN `" . DB_PREFIX . self::T_DEVICES . "` d ON pn.device_id = d.device_id
                WHERE d.user_id = '%d'
                ORDER BY pn.setting_id DESC";

        $sql = sprintf($sql, $user_id);

        $query = $this->db->query($sql);

        $devices = array();
        if (!$query->num_rows) {
            return $devices;
        }

        $this->load->language(
            $this->is_ver23 ? 'extension/module/mobileassistantconnector' : 'module/mobileassistantconnector'
        );

        foreach ($query->rows as $push_device) {
            if (!$push_device['device_id'] || $push_device['device_id'] == '') {
                $push_device['device_name'] = "Unknown";
            }

            $devices[$push_device['device_unique_id']]['device_id']     = $push_device['device_id'];
            $devices[$push_device['device_unique_id']]['device_name']   = $push_device['device_name'];
            $devices[$push_device['device_unique_id']]['account_email'] = $push_device['account_email'];
            $devices[$push_device['device_unique_id']]['last_activity'] = $push_device['last_activity'];

            $push_device['store_id_name'] = $this->language->get('mac_all');
            if (isset($push_device['store_id']) && $push_device['store_id'] != "" && $push_device['store_id'] != -1) {
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
            if (isset($push_device['push_currency_code']) && $push_device['push_currency_code'] != ""
                && $push_device['push_currency_code'] != "not_set") {
                if ($push_device['push_currency_code'] == "base_currency") {
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

            $push_device['push_order_statuses_names'] = "-";
            if (isset($push_device['push_order_statuses']) && $push_device['push_order_statuses'] != "") {
                if ($push_device['push_order_statuses'] == "-1") {
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

    private function get_orders_statuses($statuses)
    {
        $default_attrs = array();

        $this->load->model('localisation/language');
        $language = $this->model_localisation_language->getLanguage($this->getAdminLanguageId());

        $this->check_version();
        $language_code = $language['directory'];
        if (version_compare($this->opencart_version, '2.2.0.0', '>=')) {
            $language_code = $language['code'];
        }

        $default_attrs['text_missing'] = 'Missing Orders';
        if (file_exists('language/' . $language_code . '/sale/order.php')) {
            include('language/' . $language_code . '/sale/order.php');

            if (isset($_['text_missing'])) {
                $default_attrs['text_missing'] = $_['text_missing'];
            }
        }

        $orders_status   = array();
        $orders_status[] = $default_attrs['text_missing'];

        $push_order_statuses = $this->get_filter_statuses($statuses);

        $sql   = "SELECT order_status_id AS st_id, name AS st_name
                    FROM " . DB_PREFIX . "order_status
                    WHERE language_id = '" . (int)$language['language_id'] . "' ORDER BY name";
        $query = $this->db->query($sql);
        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                if (in_array($row['st_id'], $push_order_statuses)) {
                    $orders_status[] = $row['st_name'];
                }
            }
        }
        return $orders_status;
    }

    private function get_filter_statuses($statuses)
    {
        $statuses = explode('|', $statuses);
        if (!empty($statuses)) {
            $stat = array();
            foreach ($statuses as $status) {
                if ($status != '') {
                    $stat[] = $status;
                }
            }
            return $stat;
        }

        return $statuses;
    }

    private function get_QR_code($user)
    {
        $url = '';
        if (defined('HTTP_CATALOG')) {
            $url = HTTP_CATALOG;
        } else {
            if (defined('HTTP_SERVER')) {
                $url = HTTP_SERVER;
            }
        }

        $url    = str_replace(array('http://', 'https://'), '', $url);
        $config = array(
            'url' => $url,
            'login' => $user['username'],
            'password' => $user['password'],
        );

        $config = base64_encode(json_encode($config));

        return $config;
    }

    public function enable_push_devices($ids)
    {
        $this->change_push_devices_status($ids, 1);
    }

    public function disable_push_devices($ids)
    {
        $this->change_push_devices_status($ids, 0);
    }

    public function change_push_devices_status($ids, $status)
    {
        $sql = 'UPDATE `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "` SET status = '%d' WHERE setting_id IN (%s)";
        $sql = sprintf($sql, $status, implode(' , ', $ids));

        $this->db->query($sql);
    }

    public function delete_push_devices($ids)
    {
        $sql   = "SELECT setting_id, device_id FROM `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
            . "` WHERE setting_id IN (%s)";
        $sql   = sprintf($sql, implode(" , ", $ids));
        $query = $this->db->query($sql);

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $device_id = $query->row['device_id'];

                if ($this->deletePushRegId($row['setting_id'])) {
                    $sql_d   = "SELECT setting_id FROM `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
                        . "` WHERE device_id = '%d'";
                    $sql_d   = sprintf($sql_d, $device_id);
                    $query_d = $this->db->query($sql_d);

                    if ($query_d->num_rows <= 0) {
                        $sql = "DELETE FROM `" . DB_PREFIX . self::T_DEVICES . "` WHERE device_id = '%d'";
                        $sql = sprintf($sql, $device_id);
                        $this->db->query($sql);
                    }
                }
            }
        }
    }

    public function update_module($s)
    {
        $sql = "SELECT
                    user_id,
                    user_status,
                    username,
                    password,
                    mobassist_disable_mis_ord_notif,
                    allowed_actions,
                    qr_code_hash
                FROM `" . DB_PREFIX . self::T_USERS . "`";

        $query = $this->db->query($sql);
        if ($query->num_rows) {
            return;
        }

        $sql = "INSERT INTO `" . DB_PREFIX . self::T_USERS . "` SET
                username = '%s',
                password = '%s',
                allowed_actions = '{\"push_new_order\":\"1\",\"push_order_status_changed\":\"1\",\"push_new_customer\":\"1\",\"store_statistics\":\"1\",\"order_list\":\"1\",\"order_details\":\"1\",\"order_status_updating\":\"1\",\"order_details_products_list_pickup\":\"1\",\"customer_list\":\"1\",\"customer_details\":\"1\",\"product_list\":\"1\",\"product_details\":\"1\"}',
                qr_code_hash = '%s'";

        if (!isset($s['mobassist_login']) || empty($s['mobassist_login'])) {
            $s['mobassist_login'] = 1;
        }
        if (!isset($s['mobassist_pass']) || empty($s['mobassist_pass'])) {
            $s['mobassist_pass'] = md5(1);
        }

        $sql = sprintf($sql, $s['mobassist_login'], $s['mobassist_pass'],
            hash('sha256', md5(time() . rand(1111, 99999))));
        $this->db->query($sql);

        $user_id = $this->db->getLastId();

        $sql = 'UPDATE `' . DB_PREFIX . self::T_DEVICES . "` SET user_id = '" . $user_id . "'";
        $this->db->query($sql);

        $sql = 'UPDATE `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "` SET user_id = '" . $user_id . "'";
        $this->db->query($sql);
    }

    public function deletePushRegId($setting_id)
    {
        $sql = "DELETE FROM " . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . " WHERE setting_id = '%d'";
        $sql = sprintf($sql, $setting_id);
        return $this->db->query($sql);
    }

    public function drop_tables()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . self::T_SESSION_KEYS . "`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . self::T_FAILED_LOGIN . "`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . self::T_DEVICES . "`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . self::T_USERS . "`");
    }

    private function check_version()
    {
        if (class_exists('MijoShop')) {
            $base = MijoShop::get('base');

            $installed_ms_version = (array)$base->getMijoshopVersion();
            $mijo_version         = $installed_ms_version[0];
            if (version_compare($mijo_version, '3.0.0', '>=') && version_compare(VERSION, '2.0.0.0', '<')) {
                $this->opencart_version = '2.0.1.0';
            } else {
                $this->opencart_version = VERSION;
            }

        } else {
            $this->opencart_version = VERSION;
        }

        $this->is_ver20 = version_compare($this->opencart_version, '2.0.0.0', '>=');
        $this->is_ver23 = version_compare($this->opencart_version, '2.3.0.0', '>=');
        $this->is_ver30 = version_compare($this->opencart_version, '3.0.0.0', '>=');
    }

    public function getAdminLanguageId()
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` WHERE code = '"
            . $this->db->escape($this->config->get('config_admin_language')) . "'");
        $lang  = $query->row;

        return (int)$lang['language_id'];
    }
}

class Modelmobileassistantconnector extends BaseModelMobileassistantConnector
{

}

class ModelExtensionMobileassistantConnector extends BaseModelMobileassistantConnector
{

}