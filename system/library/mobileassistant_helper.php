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

class Mobileassistant_helper
{
    const MODULE_CODE    = 36;
    const MODULE_VERSION = '1.4.8';

    const T_SESSION_KEYS       = 'mobassistantconnector_session_keys';
    const T_FAILED_LOGIN       = 'mobassistantconnector_failed_login';
    const T_PUSH_NOTIFICATIONS = 'mobileassistant_push_settings';
    const T_DEVICES            = 'mobassistantconnector_devices';
    const T_USERS              = 'mobassistantconnector_users';

    const MODULE_SETTING_CODE = 'mobassist';

    const AREA_FRONTEND = 1;
    const AREA_BACKEND  = 2;

    private static $cartVersion;
    private static $isCartVersion20 = null;
    private static $isCartVersion23 = null;
    private static $isCartVersion30 = null;

    private static $actions_to_module_version = array(
        'order_details_products_list_pickup' => '1.4.0',
        'product_edit' => '1.4.3',
        'product_add' => '1.4.6',
    );

    public static function isCartVersion23()
    {
        if (null === self::$isCartVersion23) {
            self::checkCartVersion();
        }

        return self::$isCartVersion23;
    }

    private static function checkCartVersion()
    {
        if (class_exists('MijoShop')) {
            $base = MijoShop::get('base');

            $installed_ms_version = (array)$base->getMijoshopVersion();
            $mijo_version         = $installed_ms_version[0];

            self::$cartVersion = version_compare($mijo_version, '3.0.0', '>=')
            && version_compare(VERSION, '2.0.0.0', '<')
                ? '2.0.1.0'
                : VERSION;
        } else {
            self::$cartVersion = VERSION;
        }

        self::$isCartVersion20 = version_compare(self::$cartVersion, '2.0.0.0', '>=');
        self::$isCartVersion23 = version_compare(self::$cartVersion, '2.3.0.0', '>=');
        self::$isCartVersion30 = version_compare(self::$cartVersion, '3.0.0.0', '>=');
    }

    public static function isCartVersion30()
    {
        if (null === self::$isCartVersion30) {
            self::checkCartVersion();
        }

        return self::$isCartVersion30;
    }

    public static function updateModule($db, $settings)
    {
        if (version_compare($settings['mobassist_module_version'], '1.4.0', '<')) {
            self::updateTo1Point4Point0($db);
        }

        if (version_compare($settings['mobassist_module_version'], '1.4.3', '<')) {
            self::updateTo1Point4Point3($db);
        }

        $sql   = 'SELECT user_id FROM `' . DB_PREFIX . self::T_USERS . '`';
        $query = $db->query($sql);

        if ($query->num_rows) {
            return;
        }

        $user_allowed_actions = '{"push_new_order":"1","push_order_status_changed":"1","push_new_customer":"1","'
            . 'store_statistics":"1","order_list":"1","order_details":"1","order_status_updating":"1","order_det'
            . 'ails_products_list_pickup":"1","customer_list":"1","customer_details":"1","product_list":"1","pro'
            . 'duct_details":"1","product_edit":"1","product_add":"1"}';

        $sql = 'INSERT INTO `' . DB_PREFIX . self::T_USERS . "` SET
                username = '%s',
                password = '%s',
                allowed_actions = {$user_allowed_actions},
                qr_code_hash = '%s'";

        if (empty($settings['mobassist_login'])) {
            $settings['mobassist_login'] = 1;
        }

        if (empty($settings['mobassist_pass'])) {
            $settings['mobassist_pass'] = md5(1);
        }

        $sql = sprintf(
            $sql,
            $settings['mobassist_login'],
            $settings['mobassist_pass'],
            hash('sha256', md5(time() . rand(1111, 99999)))
        );
        $db->query($sql);

        $user_id = $db->getLastId();

        $db->query('UPDATE `' . DB_PREFIX . self::T_DEVICES . "` SET user_id = $user_id");
        $db->query(
            'UPDATE `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
            . "` SET user_id = $user_id"
        );
    }

    private static function updateTo1Point4Point0($db)
    {
        self::addAllowedActionsToAdminUsers($db, self::getActionsAddedLater('1.4.0'));
    }

    private static function addAllowedActionsToAdminUsers($db, $actions)
    {
        $query = $db->query(
            'SELECT user_id, allowed_actions FROM `' . DB_PREFIX . self::T_USERS . '`'
        );

        if (!$query->num_rows) {
            return;
        }

        $action_codes_all  = array_keys(self::getActionCodes());
        $count_actions_all = count($action_codes_all);

        for ($i = 0; $i < $query->num_rows; $i++) {
            if (empty($query->rows[$i]['allowed_actions'])) {
                continue;
            }

            $user_actions         = (array)json_decode($query->rows[$i]['allowed_actions']);
            $has_user_all_actions = true;
            $user_actions_codes   = array_keys($user_actions);

            for ($j = 0; $j < $count_actions_all; $j++) {
                if (
                    (!in_array($action_codes_all[$j], $user_actions_codes) || $user_actions[$action_codes_all[$j]] == 0)
                    && !in_array($action_codes_all[$j], $actions)
                ) {
                    $has_user_all_actions = false;
                    break;
                }
            }

            if ($has_user_all_actions) {
                $db->query(
                    'UPDATE `' . DB_PREFIX . self::T_USERS . "` SET allowed_actions = '"
                    . json_encode(self::getActionCodes()) . "' WHERE user_id = " . $query->rows[$i]['user_id']
                );
            }
        }
    }

    public static function getActionCodes()
    {
        return array(
            'push_new_order' => 1,
            'push_order_status_changed' => 1,
            'push_new_customer' => 1,
            'store_statistics' => 1,
            'order_list' => 1,
            'order_details' => 1,
            'order_status_updating' => 1,
            'order_details_products_list_pickup' => 1,
            'customer_list' => 1,
            'customer_details' => 1,
            'product_list' => 1,
            'product_details' => 1,
            'product_edit' => 1,
            'product_add' => 1
        );
    }

    private static function getActionsAddedLater($module_version)
    {
        $result = array();

        foreach (self::$actions_to_module_version as $action_code => $version) {
            if (version_compare($version, $module_version, '>=')) {
                $result[] = $action_code;
            }
        }

        return $result;
    }

    private static function updateTo1Point4Point3($db)
    {
        self::addAllowedActionsToAdminUsers($db, self::getActionsAddedLater('1.4.3'));
    }

    public static function createTables($db)
    {
        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "` (
                `setting_id` int(11) NOT NULL AUTO_INCREMENT,
                `device_id` INT(10),
                `user_id` INT(10) NOT NULL,
                `registration_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `app_connection_id` int(5) NOT NULL,
                `store_id` int(5) NOT NULL,
                `push_new_order` tinyint(1) NOT NULL DEFAULT '0',
                `push_order_statuses` text COLLATE utf8_unicode_ci NOT NULL,
                `push_new_customer` tinyint(1) NOT NULL DEFAULT '0',
                `push_currency_code` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
                `status` INT(1) NOT NULL DEFAULT '1',
                PRIMARY KEY (`setting_id`)
            )"
        );

        $column = 'device_id';
        $sql    = 'SHOW COLUMNS FROM `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
            . "` WHERE `Field` = '$column'";
        $q      = $db->query($sql);
        if (!$q->num_rows) {
            $db->query(
                'ALTER TABLE `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
                . "` ADD $column INT(10) NOT NULL"
            );
        }

        $column = 'user_id';
        $sql    = 'SHOW COLUMNS FROM `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
            . "` WHERE `Field` = '$column'";
        $q      = $db->query($sql);
        if (!$q->num_rows) {
            $db->query(
                "ALTER TABLE `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
                . "` ADD $column INT(10) NOT NULL"
            );
        }

        $column = 'status';
        $sql    = 'SHOW COLUMNS FROM `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
            . "` WHERE `Field` = '$column'";
        $q      = $db->query($sql);
        if (!$q->num_rows) {
            $db->query(
                'ALTER TABLE `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
                . "` ADD $column INT(1) NOT NULL DEFAULT '1'"
            );
        }

        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . self::T_SESSION_KEYS . "` (
                `key_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT(10) NOT NULL,
                `session_key` VARCHAR(100) NOT NULL,
                `date_added` DATETIME NOT NULL,
                PRIMARY KEY (`key_id`)
            )"
        );

        $column = 'user_id';
        $sql    = 'SHOW COLUMNS FROM `' . DB_PREFIX . self::T_SESSION_KEYS
            . "` WHERE `Field` = '$column'";
        $q      = $db->query($sql);
        if (!$q->num_rows) {
            $db->query(
                'ALTER TABLE `' . DB_PREFIX . self::T_SESSION_KEYS
                . "` ADD $column INT(10) NOT NULL"
            );
        }

        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . self::T_FAILED_LOGIN . "` (
                `row_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `ip` VARCHAR(20) NOT NULL,
                `date_added` DATETIME NOT NULL,
                PRIMARY KEY (`row_id`)
			)"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . self::T_DEVICES . "` (
                `device_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT(10) NOT NULL,
                `device_unique_id` VARCHAR(80),
                `account_email` VARCHAR(150),
                `device_name` VARCHAR(150),
                `last_activity` DATETIME NOT NULL,
                PRIMARY KEY (`device_id`)
            )"
        );

        $column = 'user_id';
        $sql    = 'SHOW COLUMNS FROM `' . DB_PREFIX . self::T_DEVICES
            . "` WHERE `Field` = '$column'";
        $q      = $db->query($sql);
        if (!$q->num_rows) {
            $db->query(
                'ALTER TABLE `' . DB_PREFIX . self::T_DEVICES
                . "` ADD $column INT(10) NOT NULL"
            );
        }

        $db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . self::T_USERS . "` (
                `user_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `username` VARCHAR(50),
                `password` VARCHAR(50),
                `allowed_actions` text COLLATE utf8_unicode_ci NOT NULL,
                `qr_code_hash` VARCHAR(70),
                `mobassist_disable_mis_ord_notif` tinyint(1) NOT NULL DEFAULT '0',
                `user_status` tinyint(1) NOT NULL DEFAULT '1',
                PRIMARY KEY (`user_id`)
            )"
        );
    }

    public static function checkAndAddEvents($controller, $area)
    {
        if (!self::isCartVersion20()) {
            return;
        }

        $cartVersion = self::getCartVersion();

        if (version_compare($cartVersion, '2.3.0.0', '>=')) {
            self::prepareEventsCart23x($controller, $area);
        } elseif (version_compare($cartVersion, '2.2.0.0', '>=')) {
            self::prepareEventsCart22x($controller, $area);
        } else {
            self::prepareEventsCart22Down($controller, $area);
        }
    }

    public static function isCartVersion20()
    {
        if (null === self::$isCartVersion20) {
            self::checkCartVersion();
        }

        return self::$isCartVersion20;
    }

    public static function getCartVersion()
    {
        if (null === self::$cartVersion) {
            self::checkCartVersion();
        }

        return self::$cartVersion;
    }

    private static function prepareEventsCart23x($controller, $area)
    {
        self::deleteEventsCart22Down($controller->db);
        self::deleteEventsCart22x($controller->db);

        self::addEvents(
            self::getModelEvent($controller, 'extension/event', $area),
            $controller->db,
            array(
                array(
                    'trigger' => 'catalog/model/checkout/order/addOrder/after',
                    'action' => 'extension/module/mobileassistantconnector/push_new_order_23x'
                ),
                array(
                    'trigger' => 'catalog/model/checkout/order/addOrderHistory/before',
                    'action' => 'extension/module/mobileassistantconnector/push_change_status_pre'
                ),
                array(
                    'trigger' => 'catalog/model/checkout/order/addOrderHistory/after',
                    'action' => 'extension/module/mobileassistantconnector/push_change_status'
                ),
                array(
                    'trigger' => 'admin/controller/sale/order/history/before',
                    'action' => 'extension/module/mobileassistantconnector/push_change_status_pre'
                ),
                array(
                    'trigger' => 'admin/controller/sale/order/history/after',
                    'action' => 'extension/module/mobileassistantconnector/push_change_status'
                ),
                array(
                    'trigger' => 'catalog/model/account/customer/addCustomer/after',
                    'action' => 'extension/module/mobileassistantconnector/push_new_customer_23x'
                ),
            )
        );
    }

    private static function deleteEventsCart22Down($db)
    {
        self::deleteEvents(
            $db,
            array(
                array(
                    'trigger' => 'post.order.add',
                    'action' => 'module/mobileassistantconnector/push_new_order'
                ),
                array(
                    'trigger' => 'pre.order.history.add',
                    'action' => 'module/mobileassistantconnector/push_change_status_pre'
                ),
                array(
                    'trigger' => 'post.order.history.add',
                    'action' => 'module/mobileassistantconnector/push_change_status'
                ),
                array(
                    'trigger' => 'post.customer.add',
                    'action' => 'module/mobileassistantconnector/push_new_customer'
                ),
            )
        );
    }

    /**
     * Deletes old events after shopping cart updating
     *
     * @param $db
     * @param $events
     */
    private static function deleteEvents($db, $events)
    {
        $count = count($events);

        for ($i = 0; $i < $count; $i++) {
            $db->query(
                'DELETE FROM ' . DB_PREFIX . "event WHERE `code` = 'mobileassistantconnector' AND `trigger` = '"
                . $db->escape($events[$i]['trigger']) . "' AND `action` = '"
                . $db->escape($events[$i]['action']) . "'"
            );
        }
    }

    private static function deleteEventsCart22x($db)
    {
        self::deleteEvents(
            $db,
            array(
                array(
                    'trigger' => 'catalog/model/checkout/order/addOrder/after',
                    'action' => 'module/mobileassistantconnector/push_new_order'
                ),
                array(
                    'trigger' => 'catalog/model/checkout/order/addOrderHistory/before',
                    'action' => 'module/mobileassistantconnector/push_change_status_pre'
                ),
                array(
                    'trigger' => 'catalog/model/checkout/order/addOrderHistory/after',
                    'action' => 'module/mobileassistantconnector/push_change_status'
                ),
                array(
                    'trigger' => 'admin/controller/sale/order/history/before',
                    'action' => 'module/mobileassistantconnector/push_change_status_pre'
                ),
                array(
                    'trigger' => 'admin/controller/sale/order/history/after',
                    'action' => 'module/mobileassistantconnector/push_change_status'
                ),
                array(
                    'trigger' => 'catalog/model/account/customer/addCustomer/after',
                    'action' => 'module/mobileassistantconnector/push_new_customer'
                )
            )
        );
    }

    private static function addEvents($modelEvent, $db, $events)
    {
        $count = count($events);

        for ($i = 0; $i < $count; $i++) {
            $event = self::getEvent($modelEvent, $db, $events[$i]['trigger'], $events[$i]['action']);

            if (!empty($event)) {
                continue;
            }

            if (null !== $modelEvent) {
                $modelEvent->addEvent('mobileassistantconnector', $events[$i]['trigger'], $events[$i]['action']);
            } else {
                self::addEvent($db, 'mobileassistantconnector', $events[$i]['trigger'], $events[$i]['action']);
            }
        }
    }

    private static function getEvent($modelEvent, $db, $trigger, $action)
    {
        if (null !== $modelEvent && version_compare(self::getCartVersion(), '2.3.0.0', '>=')) {
            return $modelEvent->getEvent('mobileassistantconnector', $trigger, $action);
        }

        $event = $db->query(
            'SELECT `code` FROM `' . DB_PREFIX . "event` WHERE `code` = 'mobileassistantconnector' AND `trigger` = '"
            . $db->escape($trigger) . "' AND `action` = '" . $db->escape($action) . "'"
        );

        return $event->rows;
    }

    private static function addEvent($db, $code, $trigger, $action)
    {
        $sql = 'INSERT INTO ' . DB_PREFIX . "event SET `code` = '" . $db->escape($code) . "', `trigger` = '"
            . $db->escape($trigger) . "', `action` = '" . $db->escape($action) . "'";

        if (version_compare(self::getCartVersion(), '3.0.0.0', '>=')) {
            $sql .= ', `status` = 1';
        } elseif (version_compare(self::getCartVersion(), '2.3.0.0', '>=')) {
            $sql .= ', `status` = 1, `date_added` = now()';
        }

        $db->query($sql);
    }

    private static function getModelEvent($controller, $route, $area)
    {
        if (self::AREA_FRONTEND == $area) {
            return null;
        }

        try {
            $controller->load->model($route);
            $modelEvent = $controller->{'model_' . str_replace('/', '_', $route)};
        } catch (Exception $e) {
            $modelEvent = null;
        }

        return $modelEvent;
    }

    private static function prepareEventsCart22x($controller, $area)
    {
        self::deleteEventsCart22Down($controller->db);

        self::addEvents(
            self::getModelEvent($controller, 'extension/event', $area),
            $controller->db,
            array(
                array(
                    'trigger' => 'catalog/model/checkout/order/addOrder/after',
                    'action' => 'module/mobileassistantconnector/push_new_order'
                ),
                array(
                    'trigger' => 'catalog/model/checkout/order/addOrderHistory/before',
                    'action' => 'module/mobileassistantconnector/push_change_status_pre'
                ),
                array(
                    'trigger' => 'catalog/model/checkout/order/addOrderHistory/after',
                    'action' => 'module/mobileassistantconnector/push_change_status'
                ),
                array(
                    'trigger' => 'admin/controller/sale/order/history/before',
                    'action' => 'module/mobileassistantconnector/push_change_status_pre'
                ),
                array(
                    'trigger' => 'admin/controller/sale/order/history/after',
                    'action' => 'module/mobileassistantconnector/push_change_status'
                ),
                array(
                    'trigger' => 'catalog/model/account/customer/addCustomer/after',
                    'action' => 'module/mobileassistantconnector/push_new_customer'
                ),
            )
        );
    }

    private static function prepareEventsCart22Down($controller, $area)
    {
        self::addEvents(
            self::getModelEvent(
                $controller,
                version_compare(self::getCartVersion(), '2.0.1.0', '>=')
                    ? 'extension/event'
                    : 'tool/event',
                $area
            ),
            $controller->db,
            array(
                array(
                    'trigger' => 'post.order.add',
                    'action' => 'module/mobileassistantconnector/push_new_order'
                ),
                array(
                    'trigger' => 'pre.order.history.add',
                    'action' => 'module/mobileassistantconnector/push_change_status_pre'
                ),
                array(
                    'trigger' => 'post.order.history.add',
                    'action' => 'module/mobileassistantconnector/push_change_status'
                ),
                array(
                    'trigger' => 'post.customer.add',
                    'action' => 'module/mobileassistantconnector/push_new_customer'
                ),
            )
        );
    }

    public static function getSetting($db, $group, $store_id = 0, $settingModel = null)
    {
        if (null !== $settingModel) {
            return $settingModel->getSetting($group, $store_id);
        }

        $data = array();

        $query = $db->query(
            'SELECT * FROM ' . DB_PREFIX . 'setting WHERE store_id = ' . (int)$store_id . ' AND `'
            . self::getGroupField() . "` = '" . $db->escape($group) . "'"
        );

        foreach ($query->rows as $result) {
            if (!$result['serialized']) {
                $data[$result['key']] = $result['value'];
            } else {
                $data[$result['key']] = version_compare(self::getCartVersion(), '2.0.1.0', '>=')
                    ? json_decode($result['value'], true)
                    : unserialize($result['value']);
            }
        }

        return $data;
    }

    private static function getGroupField()
    {
        return version_compare(self::getCartVersion(), '2.0.1.0', '>=') ? 'code' : 'group';
    }

    public static function editSetting($db, $group, $data, $store_id = 0, $settingModel = null)
    {
        $store_id = (int)$store_id;

        if (null !== $settingModel) {
            $settingModel->editSetting($group, $data, $store_id);
            return;
        }

        self::deleteSetting($db, $group, $store_id);

        foreach ($data as $key => $value) {
            $sql = 'INSERT INTO ' . DB_PREFIX . "setting SET store_id = $store_id, `"
                . self::getGroupField() . "` = '" . $db->escape($group) . "', `key` = '" . $db->escape($key)
                . "', `value` = '%s', serialized = %d";

            self::saveSettingValue($db, $sql, $value);
        }
    }

    public static function deleteSetting($db, $group, $store_id = 0, $settingModel = null)
    {
        if (null !== $settingModel) {
            $settingModel->deleteSetting($group, $store_id);
            return;
        }

        $db->query(
            'DELETE FROM ' . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `"
            . self::getGroupField() . "` = '" . $db->escape($group) . "'"
        );
    }

    private static function saveSettingValue($db, $sql, $value)
    {
        $isSerialized = false;

        if (is_array($value)) {
            $value = version_compare(self::getCartVersion(), '2.1.0.1', '>=')
                ? json_encode($value)
                : serialize($value);

            $isSerialized = true;
        }

        $sql = sprintf($sql, $value, $isSerialized ? 1 : 0);
        $db->query($sql);
    }

    public static function editSettingValue(
        $db,
        $group = '',
        $key = '',
        $value = '',
        $store_id = 0,
        $settingModel = null
    ) {
        if (null !== $settingModel) {
            $settingModel->editSettingValue($group, $key, $value, $store_id);
            return;
        }

        $sql = 'UPDATE ' . DB_PREFIX . "setting SET `value` = '%s', serialized = %d WHERE `"
            . self::getGroupField() . "` = '" . $db->escape($group) . "' AND `key` = '" . $db->escape($key)
            . "' AND store_id = " . (int)$store_id;

        self::saveSettingValue($db, $sql, $value);
    }

    public static function getModel($name, $controller)
    {
        $model_name_part = version_compare(self::getCartVersion(), '3.0', '>=') ? 'extension' : '';
        $loaded_model    =
            'model_' . (!empty($model_name_part) ? $model_name_part . '_' : '') . 'mobileassistant_' . $name;

        if (!empty($controller->{$loaded_model})) {
            return $controller->{$loaded_model};
        }

        $controller->load->model((!empty($model_name_part) ? $model_name_part . '/' : '') . 'mobileassistant/' . $name);

        return $controller->{$loaded_model};
    }
}