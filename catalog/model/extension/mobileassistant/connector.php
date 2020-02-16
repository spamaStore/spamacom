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

    public function checkAuth($hash)
    {
        $sql = 'SELECT `user_id`,
                       `username`,
                       `password`,
                       `allowed_actions`,
                       `mobassist_disable_mis_ord_notif`,
                       `user_status`
                FROM `' . DB_PREFIX . self::T_USERS . '` ORDER BY `user_id` ASC';

        $query = $this->db->query($sql);
        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                if (!empty($row['allowed_actions'])) {
                    $row['allowed_actions'] = json_decode($row['allowed_actions'], true);
                }

                if (hash('sha256', $row['username'] . $row['password']) == $hash) {
                    return $row;
                }
            }
        }

        return false;
    }

    public function getModuleUser($data = array())
    {
        $sql = 'SELECT `user_id`,
                       `username`,
                       `password`,
                       `allowed_actions`,
                       `qr_code_hash`,
                       `mobassist_disable_mis_ord_notif`,
                       `user_status`
                FROM `' . DB_PREFIX . self::T_USERS . '`';

        if (isset($data['user_id'])) {
            $sql .= " WHERE `user_id` = '%d'";
            $sql = sprintf($sql, $data['user_id']);

        } elseif (isset($data['qr_hash'])) {
            $sql .= " WHERE `qr_code_hash` = '%s'";
            $sql = sprintf($sql, $data['qr_hash']);
        }

        $query = $this->db->query($sql);
        $row   = $query->row;
        if (!empty($row['allowed_actions'])) {
            $row['allowed_actions'] = json_decode($row['allowed_actions'], true);
        }

        return $row;
    }

    public function getTotalOrders($data = array())
    {
        $sql = 'SELECT COUNT(`order_id`) AS count_orders, SUM(`total`) AS total_sales FROM `' . DB_PREFIX . 'order`';

        $query_where_parts = array();
        if (isset($data['date_from'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(`date_added`, '+00:00', @@global.time_zone)) >= '%d'",
                    strtotime($data['date_from'])
                );
        }

        if (isset($data['date_to'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(`date_added`, '+00:00', @@global.time_zone)) <= '%d'",
                    strtotime($data['date_to'])
                );
        }

        if (isset($data['statuses'])) {
            $query_where_parts[] = sprintf(" `order_status_id` IN ('%s')", $data['statuses']);
        }

        if (isset($data['store_id'])) {
            $query_where_parts[] = sprintf(" `store_id` = '%d'", $data['store_id']);
        }

        if (!empty($query_where_parts)) {
            $sql .= ' WHERE ' . implode(' AND ', $query_where_parts);
        }

        $query = $this->db->query($sql);
        $row   = $query->row;
        if (!isset($data['currency_code'])) {
            $data['currency_code'] = $this->config->get('config_currency');
        }

        $row['count_orders'] = Mobileassistant_helper::getModel('helper', $this)
            ->nice_count($row['count_orders'], true);
        $row['total_sales']  = Mobileassistant_helper::getModel('helper', $this)
            ->nice_price($row['total_sales'], $data['currency_code']);

        return $row;
    }

    public function getTotalCustomers($data = array())
    {
        $sql = 'SELECT COUNT(`customer_id`) AS count_customers FROM `' . DB_PREFIX . 'customer`';

        if (isset($data['date_from'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(date_added, '+00:00', @@global.time_zone)) >= '%d'",
                    strtotime($data['date_from'])
                );
        }
        if (isset($data['date_to'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(date_added, '+00:00', @@global.time_zone)) <= '%d'",
                    strtotime($data['date_to'])
                );
        }

        if (!empty($query_where_parts)) {
            $sql .= ' WHERE ' . implode(' AND ', $query_where_parts);
        }

        $query                  = $this->db->query($sql);
        $row                    = $query->row;
        $row['count_customers'] = Mobileassistant_helper::getModel('helper', $this)
            ->nice_count($row['count_customers'], true);

        return $row;
    }

    public function getTotalSoldProducts($data = array())
    {
        $sql = 'SELECT COUNT(op.`product_id`) AS count_products 
                FROM `' . DB_PREFIX . 'order_product` AS op
                LEFT JOIN `' . DB_PREFIX . 'order` AS o ON o.`order_id` = op.`order_id`';

        $query_where_parts = array();
        if (isset($data['date_from'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(o.date_added, '+00:00', @@global.time_zone)) >= '%d'",
                    strtotime($data['date_from'])
                );
        }

        if (isset($data['date_to'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(o.date_added, '+00:00', @@global.time_zone)) <= '%d'",
                    strtotime($data['date_to'])
                );
        }

        if (isset($data['statuses'])) {
            $query_where_parts[] = sprintf(" o.order_status_id IN ('%s')", $data['statuses']);
        }

        if (isset($data['store_id'])) {
            $query_where_parts[] = sprintf(" o.store_id = '%d'", $data['store_id']);
        }

        if (!empty($query_where_parts)) {
            $sql .= ' WHERE ' . implode(' AND ', $query_where_parts);
        }

        $query                 = $this->db->query($sql);
        $row                   = $query->row;
        $row['count_products'] = Mobileassistant_helper::getModel('helper', $this)
            ->nice_count($row['count_products'], true);

        return $row;
    }

    public function getChartData($data = array())
    {
        $orders    = array();
        $customers = array();
        $average   = array(
            'avg_sum_orders' => 0,
            'avg_orders' => 0,
            'avg_customers' => 0,
            'avg_cust_order' => '0.00',
            'tot_orders' => 0,
            'sum_orders' => '0.00',
            'tot_customers' => 0,
            'currency_symbol' => ''
        );

        $startDate = $data['graph_from'];
        $endDate   = $data['graph_to'];

        if (!isset($data['currency_code'])) {
            $data['currency_code'] = $this->config->get('config_currency');
        }

        $plus_date = '+1 day';
        if (isset($data['custom_period']) && strlen($data['custom_period']) > 0) {
            $custom_period = $data['custom_period_date'];

            if ($data['custom_period'] == 3) {
                $plus_date = "+3 day";
            } else {
                if ($data['custom_period'] == 4 || $data['custom_period'] == 8) {
                    $plus_date = "+1 week";
                } else {
                    if ($data['custom_period'] == 5 || $data['custom_period'] == 6 || $data['custom_period'] == 7) {
                        $plus_date = "+1 month";
                    }
                }
            }

            if ($data['custom_period'] == 7) {
                $startDateO = 0;
                $endDateO   = 0;

                $sql   = "SELECT MIN(date_added) AS min_date_add, MAX(date_added) AS max_date_add FROM `" . DB_PREFIX
                    . "order`";
                $query = $this->db->query($sql);
                if ($query->num_rows) {
                    $row        = $query->row;
                    $startDateO = strtotime($row['min_date_add']);
                    $endDateO   = strtotime($row['max_date_add']);
                }

                $startDateC = 0;
                $endDateC   = 0;

                $sql   = "SELECT MIN(date_added) AS min_date_add, MAX(date_added) AS max_date_add FROM `" . DB_PREFIX
                    . "customer`";
                $query = $this->db->query($sql);
                if ($query->num_rows) {
                    $row        = $query->row;
                    $startDateC = strtotime($row['min_date_add']);
                    $endDateC   = strtotime($row['max_date_add']);
                }

                $startDate = date("m/d/Y", min($startDateO, $startDateC)) . " 00:00:00";
                $endDate   = date("m/d/Y", max($endDateC, $endDateO)) . " 23:59:59";

            } else {
                $startDate = $custom_period['start_date'] . " 00:00:00";
                $endDate   = $custom_period['end_date'] . " 23:59:59";
            }
        }

        $startDate = strtotime($startDate);
        $endDate   = strtotime($endDate);

        $date = $startDate;
        $d    = 0;
        while ($date <= $endDate) {
            $d++;

            $sql = "SELECT COUNT(order_id) AS tot_orders, UNIX_TIMESTAMP(CONVERT_TZ(date_added, '+00:00', @@global.time_zone)) AS date_add, SUM(total) AS value
                      FROM `" . DB_PREFIX . "order`
                      WHERE UNIX_TIMESTAMP(CONVERT_TZ(date_added, '+00:00', @@global.time_zone)) >= '%d' AND UNIX_TIMESTAMP(CONVERT_TZ(date_added, '+00:00', @@global.time_zone)) < '%d'";

            $sql = sprintf($sql, $date, strtotime($plus_date, $date));

            if (isset($data['statuses'])) {
                $sql .= sprintf(" AND order_status_id IN ('%s')", $data['statuses']);
            }

            if (isset($data['store_id'])) {
                $sql .= sprintf(" AND store_id = '%d'", $data['store_id']);
            }
            $sql .= " GROUP BY DATE(date_added) ORDER BY date_added";

            $total_order_per_day = 0;
            $query               = $this->db->query($sql);
            if ($query->num_rows) {
                foreach ($query->rows as $row) {
                    $total_order_per_day += $row['value'];

                    $average['tot_orders'] += $row['tot_orders'];
                    $average['sum_orders'] += $row['value'];
                }
            }

            $total_order_per_day = Mobileassistant_helper::getModel('helper', $this)
                ->nice_price($total_order_per_day, $data['currency_code'], false, false, false);

            $orders[] = array($date * 1000, $total_order_per_day);

            $sql =
                "SELECT COUNT(customer_id) AS tot_customers, UNIX_TIMESTAMP(CONVERT_TZ(date_added, '+00:00', @@global.time_zone)) AS date_add FROM `"
                . DB_PREFIX . "customer`
				  WHERE UNIX_TIMESTAMP(CONVERT_TZ(date_added, '+00:00', @@global.time_zone)) >= '%d' AND UNIX_TIMESTAMP(CONVERT_TZ(date_added, '+00:00', @@global.time_zone)) < '%d'";
            $sql = sprintf($sql, $date, strtotime($plus_date, $date));

            if (isset($data['store_id'])) {
                $sql .= sprintf(" AND store_id = '%d'", $data['store_id']);
            }
            $sql .= ' GROUP BY DATE(date_added) ORDER BY date_added';

            $total_customer_per_day = 0;
            $query                  = $this->db->query($sql);
            if ($query->num_rows) {
                foreach ($query->rows as $row) {
                    $total_customer_per_day += $row['tot_customers'];

                    $average['tot_customers'] += $row['tot_customers'];
                }
            }
            $customers[] = array($date * 1000, $total_customer_per_day);

            $date = strtotime($plus_date, $date);
        }

        // Add 2 additional element into array of orders for graph in mobile application
        if (count($orders) == 1) {
            $orders_tmp  = $orders[0];
            $orders      = array();
            $orders[0][] = strtotime(date('Y-m-d', $orders_tmp[0] / 1000) . '-1 month') * 1000;
            $orders[0][] = 0;
            $orders[1]   = $orders_tmp;
            $orders[2][] = strtotime(date('Y-m-d', $orders_tmp[0] / 1000) . '+1 month') * 1000;
            $orders[2][] = 0;
        }

        // Add 2 additional element into array of customers for graph in mobile application
        if (count($customers) == 1) {
            $customers_tmp  = $customers[0];
            $customers      = array();
            $customers[0][] = strtotime(date('Y-m-d', $customers_tmp[0] / 1000) . '-1 month') * 1000;
            $customers[0][] = 0;
            $customers[1]   = $customers_tmp;
            $customers[2][] = strtotime(date('Y-m-d', $customers_tmp[0] / 1000) . '+1 month') * 1000;
            $customers[2][] = 0;
        }

        if ($d <= 0) {
            $d = 1;
        }

        $average['avg_sum_orders']  = Mobileassistant_helper::getModel('helper', $this)
            ->nice_price($average['sum_orders'] / $d, $data['currency_code']);
        $average['avg_orders']      = number_format($average['tot_orders'] / $d, 1, '.', ' ');
        $average['avg_customers']   = number_format($average['tot_customers'] / $d, 1, '.', ' ');

        if ($average['tot_customers'] > 0) {
            $average['avg_cust_order'] = Mobileassistant_helper::getModel('helper', $this)
                ->nice_price($average['sum_orders'] / $average['tot_customers'], $data['currency_code']);
        }

        return array('orders' => $orders, 'customers' => $customers, 'average' => $average);
    }

    public function getOrderStatusStats($data = array())
    {
        $order_statuses = array();
        $default_attrs  = $this->_get_default_attrs();

        $sql = "SELECT COUNT(o.order_id) AS count,
                       SUM(o.total) AS total,
                       o.order_status_id AS code,
                       (SELECT os.name FROM `" . DB_PREFIX
            . "order_status` os WHERE os.order_status_id = o.order_status_id AND os.language_id = '"
            . $this->getAdminLanguageId() . "') AS name
                FROM `" . DB_PREFIX . "order` AS o";

        $query_where_parts = array();
        if (isset($data['date_from'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(o.date_added, '+00:00', @@global.time_zone)) >= '%d'",
                    strtotime($data['date_from']));
        }

        if (isset($data['date_to'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(o.date_added, '+00:00', @@global.time_zone)) <= '%d'",
                    strtotime($data['date_to']));
        }

        if (isset($data['store_id'])) {
            $query_where_parts[] = sprintf(" o.store_id = '%d'", $data['store_id']);
        }

        if (!empty($query_where_parts)) {
            $sql .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $sql .= " GROUP BY code ORDER BY count ASC";

        if (!isset($data['currency_code'])) {
            $data['currency_code'] = $this->config->get('config_currency');
        }

        $query = $this->db->query($sql);
        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                if ($query->row['count'] == 0) {
                    continue;
                }

                if ($row['code'] == 0) {
                    $row['name'] = $default_attrs['text_missing'];
                }

                $row['total'] = Mobileassistant_helper::getModel('helper', $this)
                    ->nice_price($row['total'], $data['currency_code']);

                $order_statuses[] = $row;
            }
        }

        return $order_statuses;
    }

    private function _get_default_attrs()
    {
        $default_attrs = array();
        Mobileassistant_helper::getModel('helper', $this);

        $this->load->model('localisation/language');
        $language = $this->model_localisation_language->getLanguage($this->getAdminLanguageId());

//        $this->check_version();
        $language_code = $language['directory'];
        if (version_compare(Mobileassistant_helper::getCartVersion(), '2.2.0.0', '>=')) {
            $language_code = $language['code'];
        }

        $default_attrs['text_missing'] = 'Missing Orders';
        if (file_exists('./admin/language/' . $language_code . '/sale/order.php')) {
            include('./admin/language/' . $language_code . '/sale/order.php');

            if (isset($_['text_missing'])) {
                $default_attrs['text_missing'] = $_['text_missing'];
            }
        }

        return $default_attrs;
    }

    public function getAdminLanguageId()
    {
        $query = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "language` WHERE code = '"
            . $this->db->escape($this->config->get('config_admin_language')) . "'"
        );
        $lang  = $query->row;

        return (int)$lang['language_id'];
    }

    public function getOrders($data = array())
    {
        $orders            = array();
        $query_where_parts = array();
        $default_attrs     = $this->_get_default_attrs();

        $select_currency_code = " o.currency ";
        $sql_currency_code    = "SHOW COLUMNS FROM `" . DB_PREFIX . "order` WHERE `Field` = 'currency_code'";
        $res_currency_code    = $this->db->query($sql_currency_code);
        if ($res_currency_code->num_rows) {
            $select_currency_code = " o.currency_code ";
        }

        $sql = "SELECT
                    o.order_id AS id_order,
                    o.date_added AS date_add,
                    o.total AS total_paid,
                    " . $select_currency_code . " AS currency_code,
                    currency_value,
                    CONCAT(o.firstname, ' ', o.lastname) AS customer,
                    o.email AS customer_email,
                    (SELECT
                        os.name
                        FROM `" . DB_PREFIX . "order_status` os
                        WHERE os.order_status_id = o.order_status_id
                              AND os.language_id = '" . $this->getAdminLanguageId() . "'
                    ) AS ord_status,
                    o.order_status_id AS status_code,
                    o.store_name AS shop_name,
                    (SELECT SUM(quantity) FROM `" . DB_PREFIX . "order_product` WHERE order_id = o.order_id) AS count_prods
                FROM `" . DB_PREFIX . "order` o";


        if (isset($data['store_id'])) {
            $query_where_parts[] = "o.store_id = " . $data['store_id'];
        }

        if (isset($data['statuses'])) {
            $query_where_parts[] = sprintf(" o.order_status_id IN ('%s')", $data['statuses']);
        }

        if (isset($data['search_order_id']) && preg_match('/^\d+(?:,\d+)*$/', $data['search_order_id'])) {
            $query_where_parts[] = sprintf("o.order_id IN (%s)", $data['search_order_id']);
        } elseif (isset($data['search_order_id'])) {
            $query_where_parts[] = sprintf(
                " CONCAT(o.firstname, ' ', o.lastname) LIKE '%%%s%%' OR o.email = '%s'",
                $data['search_order_id'],
                $data['search_order_id']
            );
        }

        if (isset($data['orders_from'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(o.date_added, '+00:00', @@global.time_zone)) >= '%d'",
                    strtotime($data['orders_from']));
        }

        if (isset($data['orders_to'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(o.date_added, '+00:00', @@global.time_zone)) <= '%d'",
                    strtotime($data['orders_to']));
        }


        if (!empty($query_where_parts)) {
            $sql .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $sql .= " ORDER BY ";

        $sort_by  = "o.order_id";
        $order_by = "DESC";
        switch ($data['sort_by']) {
            case 'id':
                $sort_by  = "o.order_id";
                $order_by = "DESC";
                break;
            case 'date':
                $sort_by  = "o.date_added";
                $order_by = "DESC";
                break;
            case 'name':
                $sort_by  = "customer";
                $order_by = "ASC";
                break;
            case 'total':
                $sort_by  = "o.total";
                $order_by = "DESC";
                break;
            case 'qty':
                $sort_by  = "count_prods";
                $order_by = "DESC";
                break;
        }

        if (isset($data['order_by']) && in_array($data['order_by'], array("asc", "desc"))) {
            $order_by = $data['order_by'];
        }
        $sql .= $sort_by . " " . $order_by;

        $sql .= sprintf(" LIMIT %d, %d", $data['page'], $data['show']);

        $query = $this->db->query($sql);

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $currency_value = false;
                if (isset($data['currency_code'])) {
                    $currency_code = $data['currency_code'];
                } else {
                    $currency_code  = $row['currency_code'];
                    $currency_value = $row['currency_value'];
                }

                $row['total_paid'] = Mobileassistant_helper::getModel('helper', $this)
                    ->nice_price($row['total_paid'], $currency_code, $currency_value);

                if ($row['status_code'] == 0) {
                    $row['ord_status'] = $default_attrs['text_missing'];
                }

                $orders[] = $row;
            }
        }

        $orders_status = null;
        if (isset($data['get_statuses']) && $data['get_statuses'] == 1) {
            $orders_status = $this->getOrdersStatuses();
        }

        if (!isset($data['currency_code'])) {
            $data['currency_code'] = $this->config->get('config_currency');
        }

        $orders_total = $this->getOrdersTotal($query_where_parts, $data['currency_code']);

        return array(
            "orders" => $orders,
            "orders_count" => $orders_total['count_ords'],
            "orders_total" => $orders_total['orders_total'],
            "orders_status" => $orders_status
        );
    }

    public function getOrdersStatuses()
    {
        $default_attrs   = $this->_get_default_attrs();
        $orders_status   = array();
        $orders_status[] = array('st_id' => 0, 'st_name' => $default_attrs['text_missing']);

        $sql   = "SELECT order_status_id AS st_id, name AS st_name FROM `" . DB_PREFIX
            . "order_status` WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name";
        $query = $this->db->query($sql);

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $orders_status[] = array('st_id' => $row['st_id'], 'st_name' => $row['st_name']);
            }
        }

        return $orders_status;
    }

    public function getOrdersTotal($query_where_parts = array(), $currency_code)
    {

        $sql = "SELECT SUM(o.total) AS orders_total, COUNT(o.order_id) AS count_ords
				  FROM `" . DB_PREFIX . "order` AS o";

        if (!empty($query_where_parts)) {
            $sql .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $query = $this->db->query($sql);

        $row = $query->row;

        $row['orders_total'] = Mobileassistant_helper::getModel('helper', $this)
            ->nice_price($row['orders_total'], $currency_code);
        $row['count_ords']   = Mobileassistant_helper::getModel('helper', $this)
            ->nice_count($row['count_ords']);

        return $row;
    }

    public function getOrdersInfo($data = array())
    {
        $default_attrs = $this->_get_default_attrs();
        $this->load->model('checkout/order');
        $this->load->model('account/order');

        $order = $this->model_checkout_order->getOrder($data['order_id']);

        $order_info = array(
            'id_order' => $order['order_id'],
            'id_customer' => $order['customer_id'],
            'email' => $order['email'],
            'telephone' => $order['telephone'],
            'customer' => $order['firstname'] . ' ' . $order['lastname'],
            'date_added' => $order['date_added'],
            'status_code' => $order['order_status_id'],
            'status' => $order['order_status'],
            'total' => $order['total'],
            'currency_code' => (isset($order['currency_code']) ? $order['currency_code'] : $order['currency']),
            'currency_value' => $order['currency_value'],
            'p_method' => $order['payment_method'],
            'p_name' => $order['payment_firstname'] . ' ' . $order['payment_lastname'],
            'p_company' => $order['payment_company'],
            'p_address_1' => $order['payment_address_1'],
            'p_address_2' => $order['payment_address_2'],
            'p_city' => $order['payment_city'],
            'p_postcode' => $order['payment_postcode'],
            'p_country' => $order['payment_country'],
            'p_zone' => $order['payment_zone'],
            's_method' => $order['shipping_method'],
            's_name' => $order['shipping_firstname'] . ' ' . $order['shipping_lastname'],
            's_company' => $order['shipping_company'],
            's_address_1' => $order['shipping_address_1'],
            's_address_2' => $order['shipping_address_2'],
            's_city' => $order['shipping_city'],
            's_postcode' => $order['shipping_postcode'],
            's_country' => $order['shipping_country'],
            's_zone' => $order['shipping_zone'],
            'comment' => nl2br($order['comment']),
            'admin_comments' => $this->_getOrderHistories($data['order_id'])
        );

        if (version_compare(Mobileassistant_helper::getCartVersion(), '3.0', '<')) {
            $order_info['fax'] = $order['fax'];
        }

        $currency_value = false;
        if (isset($data['currency_code'])) {
            $currency_code = $data['currency_code'];
        } else {
            if (isset($order_info['currency_code'])) {
                $currency_code  = $order_info['currency_code'];
                $currency_value = $order_info['currency_value'];
            } else {
                $currency_code = $this->config->get('config_currency');
            }
        }

        $order_info['total'] = Mobileassistant_helper::getModel('helper', $this)
            ->nice_price($order_info['total'], $currency_code, $currency_value);

        if ($order['order_status_id'] == 0) {
            $order_info['status'] = $default_attrs['text_missing'];
        }

        $order_info['currency_code'] = $order['currency_code'];

        return $order_info;
    }

    public function _getOrderHistories($order_id)
    {
        $sql = "SELECT
                  date_added,
                  os.name AS status,
                  os.order_status_id,
                  oh.comment,
                  oh.notify
                FROM `" . DB_PREFIX . "order_history` oh
                LEFT JOIN `" . DB_PREFIX . "order_status` os ON oh.order_status_id = os.order_status_id
                WHERE oh.order_id = '" . (int)$order_id . "'
                  AND os.language_id = '" . $this->getAdminLanguageId() . "'
                ORDER BY oh.date_added";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getOrderProducts($data)
    {
        $this->load->model('tool/image');

        if (version_compare(Mobileassistant_helper::getCartVersion(), '2.0.0.0', '>=')) {
            $this->load->model('tool/upload');
        }

        $sql = "SELECT
                    op.order_id AS id_order,
                    op.product_id,
                    op.name,
                    op.quantity,
                    (op.price+op.tax) AS product_price,
                    op.model,
                    op.order_product_id,
                    p.image,
                    o.currency_code,
                    o.currency_value,
                    p.sku,
                    p.upc,
                    p.ean,
                    p.jan,
                    p.isbn,
                    p.manufacturer_id,
                    p.weight,
                    p.length,
                    p.width,
                    p.height,
                    p.location,
                    m.name AS manufacturer_name
                FROM `" . DB_PREFIX . "order_product` AS op
                LEFT JOIN `" . DB_PREFIX . "order` o ON op.order_id = o.order_id
                LEFT JOIN `" . DB_PREFIX . "product` AS p ON op.product_id = p.product_id
                LEFT JOIN `" . DB_PREFIX . "manufacturer` AS m ON m.manufacturer_id = p.manufacturer_id
                WHERE op.order_id = '%d'";

        if (!$data['get_order_product_list_pickup']) {
            $sql .= ' LIMIT %d, %d';
            $sql = sprintf($sql, $data['order_id'], $data['page'], $data['show']);
        } else {
            $sql = sprintf($sql, $data['order_id']);
        }

        $order_products = array();
        $query          = $this->db->query($sql);
        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $currency_value = false;
                if (isset($data['currency_code'])) {
                    $currency_code = $data['currency_code'];
                } else {
                    if (isset($row['currency_code'])) {
                        $currency_code  = $row['currency_code'];
                        $currency_value = $row['currency_value'];
                    } else {
                        $currency_code = $this->config->get('config_currency');
                    }
                }

                $row['product_price']    = Mobileassistant_helper::getModel('helper', $this)
                    ->nice_price($row['product_price'], $currency_code, $currency_value);
                $row['product_quantity'] = (int)$row['quantity'];
                $row['product_name']     = $row['name'];

                $query                 =
                    $this->db->query("SELECT type, value, name FROM `" . DB_PREFIX . "order_option` WHERE order_id = '"
                        . (int)$data['order_id'] . "' AND order_product_id = '" . (int)$row['order_product_id'] . "'");
                $order_product_options = $query->rows;

                $option_data = array();
                foreach ($order_product_options as $option) {
                    if ($option['type'] != 'file') {
                        $value = $option['value'];
                    } else {
                        if (version_compare(Mobileassistant_helper::getCartVersion(), '2.0.0.0', '>=')) {
                            $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);
                            if ($upload_info) {
                                $value = $option['value'];
                            } else {
                                $value = '';
                            }

                        } else {
                            $value = utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.'));
                        }
                    }

                    $option_data[] = array(
                        $option['name'] => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 26) . '..' : $value)
                    );
                }

                $row['product_options'] = $option_data;
                if (!$data['without_thumbnails']) {
                    $thumb = $this->_get_product_images_size();

                    if (isset($row['image']) && strlen($row['image']) > 0) {
                        $row['product_image'] = $this->model_tool_image
                            ->resize($row['image'], $thumb['width'], $thumb['height']);
                    } else {
                        $row['product_image'] = $this->model_tool_image
                            ->resize('placeholder.png', $thumb['width'], $thumb['height']);
                    }
                }
                unset($row['image']);

                $order_products[] = $row;
            }
        }

        return $order_products;
    }

    private function _get_product_images_size($type = "thumb", $default = 300)
    {
        if (version_compare(Mobileassistant_helper::getCartVersion(), '2.2.0.0', '>=')) {
            $width  = $this->config->get($this->config->get('config_theme') . '_image_' . $type . '_width');
            $height = $this->config->get($this->config->get('config_theme') . '_image_' . $type . '_height');
        } else {
            $width  = $this->config->get('config_image_' . $type . '_width');
            $height = $this->config->get('config_image_' . $type . '_height');
        }

        if ($width <= 0) {
            $width = $default;
        }

        if ($height <= 0) {
            $height = $default;
        }

        return array('width' => $width, 'height' => $height);
    }

    public function getOrderCountProducts($data)
    {
        $sql = "SELECT COUNT(order_id) AS count_prods FROM `" . DB_PREFIX . "order_product` WHERE order_id = '%d'";
        $sql = sprintf($sql, $data['order_id']);

        $count_prods = 0;
        $query       = $this->db->query($sql);

        $row = $query->row;
        if ($row) {
            $count_prods = $row['count_prods'];
        }

        return $count_prods;
    }

    public function getOrderTotals($data)
    {
        $order_total = array();
//        $this->check_version();

//        $value_field = "text";
//        if(version_compare($this->opencart_version, '2.0.0.0', '>=')) {
//            $value_field = "value";
//        }

        $order_info = $this->model_checkout_order->getOrder($data['order_id']);

        $currency_value = false;
        if (isset($data['currency_code'])) {
            $currency_code = $data['currency_code'];
        } else {
            if (isset($order_info['currency_code'])) {
                $currency_code  = $order_info['currency_code'];
                $currency_value = $order_info['currency_value'];
            } else {
                $currency_code = $this->config->get('config_currency');
            }
        }

        $sql = "SELECT title, value FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$data['order_id']
            . "' ORDER BY sort_order";

        if (!isset($data['currency_code'])) {
            $data['currency_code'] = $this->config->get('config_currency');
        }

        $query = $this->db->query($sql);
        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $row['value'] = Mobileassistant_helper::getModel('helper', $this)
                    ->nice_price($row['value'], $currency_code, $currency_value);
                $order_total[] = array('title' => $row['title'], 'value' => $row['value']);
            }
        }
        return $order_total;
    }

    public function getCustomers($data = array())
    {
        $query_where_parts = array();

        $sql = "SELECT
                    c.customer_id AS id_customer,
                    c.firstname,
                    c.lastname,
                    CONCAT(c.firstname, ' ', c.lastname) AS full_name,
                    c.date_added AS date_add,
                    c.email,
                    IFNULL(tot.total_orders, 0) AS total_orders,
                    cgd.name AS customer_group
                FROM `" . DB_PREFIX . "customer` c
                LEFT JOIN `" . DB_PREFIX
            . "customer_group_description` cgd ON (c.customer_group_id = cgd.customer_group_id AND cgd.language_id = '"
            . $this->getAdminLanguageId() . "')
                LEFT OUTER JOIN (SELECT COUNT(order_id) AS total_orders, customer_id FROM `" . DB_PREFIX
            . "order` GROUP BY customer_id) AS tot ON tot.customer_id = c.customer_id";

        $customers = array();
        if (isset($data['customers_from'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(c.date_added, '+00:00', @@global.time_zone)) >= '%d'",
                    strtotime($data['customers_from']));
        }

        if (isset($data['customers_to'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(c.date_added, '+00:00', @@global.time_zone)) <= '%d'",
                    strtotime($data['customers_to']));
        }

        if (isset($data['search_val']) && preg_match('/^\d+(?:,\d+)*$/', $data['search_val'])) {
            if (strpos($data['search_val'], ',') === false) {
                $query_where_parts[] = sprintf(
                    "(c.customer_id IN (%s) OR c.telephone LIKE '%%%s%%')",
                    $data['search_val'],
                    $data['search_val']
                );
            } else {
                $query_where_parts[] = sprintf("c.customer_id IN (%s)", $data['search_val']);
            }
        } elseif (isset($data['search_val'])) {
            $query_where_parts[] = sprintf(
                "(c.email LIKE '%%%s%%' OR CONCAT(c.firstname, ' ', c.lastname) LIKE '%%%s%%')",
                $data['search_val'],
                $data['search_val']
            );
        }

        if (isset($data['cust_with_orders'])) {
            $query_where_parts[] = " tot.total_orders > 0";
        }

        if (!empty($query_where_parts)) {
            $sql .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $sql .= " ORDER BY ";

        $sort_by  = "c.customer_id";
        $order_by = "DESC";
        switch ($data['sort_by']) {
            case 'id':
                $sort_by  = "c.customer_id";
                $order_by = "DESC";
                break;
            case 'date':
                $sort_by  = "c.date_added";
                $order_by = "DESC";
                break;
            case 'name':
                $sort_by  = "full_name";
                $order_by = "ASC";
                break;
            case 'qty':
                $sort_by  = "total_orders";
                $order_by = "DESC";
                break;
        }

        if (isset($data['order_by']) && in_array($data['order_by'], array("asc", "desc"))) {
            $order_by = $data['order_by'];
        }
        $sql .= $sort_by . " " . $order_by;

        $sql .= sprintf(" LIMIT %d, %d", $data['page'], $data['show']);

        Mobileassistant_helper::getModel('helper', $this);

        $query = $this->db->query($sql);
        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $row['total_orders'] = (int)$row['total_orders'];
                $customers[]         = $row;
            }
        }

        $customers_total = $this->getCustomersTotal($query_where_parts);

        return array('customers_count' => $customers_total, 'customers' => $customers);
    }

    private function getCustomersTotal($query_where_parts = array())
    {

        $sql = "SELECT COUNT(c.customer_id) AS count_custs
						FROM " . DB_PREFIX . "customer AS c
						LEFT OUTER JOIN (SELECT COUNT(order_id) AS total_orders, customer_id FROM `" . DB_PREFIX
            . "order` GROUP BY customer_id) AS tot ON tot.customer_id = c.customer_id";

        if (!empty($query_where_parts)) {
            $sql .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $query = $this->db->query($sql);
        $row   = $query->row;

        return Mobileassistant_helper::getModel('helper', $this)->nice_count($row['count_custs']);
    }

    public function getCustomersInfo($data = array())
    {
        $customer_orders = $this->getCustomerOrders($data);
        $customer_info   = array("customer_orders" => $customer_orders);

        if (!$data['only_items']) {
            $this->load->model('account/customer');
            $customer = $this->model_account_customer->getCustomer($data['user_id']);

            $user_info = array(
                'customer_id' => $customer['customer_id'],
                'email' => $customer['email'],
                'name' => $customer['firstname'] . ' ' . $customer['lastname'],
                'phone' => $customer['telephone'],
                'fax' => $customer['fax'],
                'date_add' => $customer['date_added'],
            );

            $user_info['address'] = $this->getAddress($customer['address_id']);

            $customer_info['user_info'] = $user_info;

            $customer_order_totals = $this->getCustomerOrderTotals($data);

            $customer_info = array_merge($customer_info, $customer_order_totals);
        }

        return $customer_info;
    }

    public function getCustomerOrders($data = array())
    {
        $customer_orders = array();
        $default_attrs   = $this->_get_default_attrs();

        $select_currency_code = " o.currency ";
        $sql_currency_code    = "SHOW COLUMNS FROM `" . DB_PREFIX . "order` WHERE `Field` = 'currency_code'";
        $res_currency_code    = $this->db->query($sql_currency_code);
        if ($res_currency_code->num_rows) {
            $select_currency_code = " o.currency_code ";
        }

        $sql = "SELECT o.order_id AS id_order, o.total AS total_paid, o.order_status_id AS status_code, "
            . $select_currency_code
            . " AS currency_code, os.name AS ord_status, o.date_added as date_add, (SELECT SUM(quantity) FROM `"
            . DB_PREFIX . "order_product` WHERE order_id = o.order_id) AS pr_qty
				    FROM `" . DB_PREFIX . "order` AS o
				    LEFT JOIN `" . DB_PREFIX
            . "order_status` AS os ON os.order_status_id = o.order_status_id AND os.language_id = '"
            . $this->getAdminLanguageId() . "'
				    WHERE o.customer_id = '%d' ORDER BY o.order_id DESC LIMIT %d, %d";
        $sql = sprintf($sql, $data['user_id'], $data['page'], $data['show']);

        $query = $this->db->query($sql);
        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                if (isset($data['currency_code'])) {
                    $currency_code = $data['currency_code'];
                } else {
                    $currency_code = $row['currency_code'];
                }

                $row['total_paid'] = Mobileassistant_helper::getModel('helper', $this)
                    ->nice_price($row['total_paid'], $currency_code);
                if ($row['status_code'] == 0) {
                    $row['ord_status'] = $default_attrs['text_missing'];
                }
                $customer_orders[] = $row;
            }
        }

        return $customer_orders;
    }

    public function getAddress($address_id)
    {
        $address_query =
            $this->db->query("SELECT company, address_1, address_2, postcode, city, country_id, zone_id FROM `"
                . DB_PREFIX . "address` WHERE address_id = '" . (int)$address_id . "'");

        if ($address_query->num_rows) {
            $country_query =
                $this->db->query("SELECT name, address_format FROM `" . DB_PREFIX . "country` WHERE country_id = '"
                    . (int)$address_query->row['country_id'] . "'");

            if ($country_query->num_rows) {
                $address_query->row['country'] = $country_query->row['name'];
            } else {
                $address_query->row['country'] = '';
            }

            $zone_query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "zone` WHERE zone_id = '"
                . (int)$address_query->row['zone_id'] . "'");

            if ($zone_query->num_rows) {
                $address_query->row['zone'] = $zone_query->row['name'];
            } else {
                $address_query->row['zone'] = '';
            }

            $keys = array('company', 'address_1', 'address_2', 'postcode', 'city', 'zone', 'country');

            $new_arr = array();
            foreach ($keys as $key) {
                if (isset($address_query->row[$key]) && !is_null($address_query->row[$key])
                    && $address_query->row[$key] != '') {
                    $new_arr[] = $address_query->row[$key];
                }
            }

            return implode(', ', $new_arr);
        }

        return '';
    }

    public function getCustomerOrderTotals($data)
    {
        $sql = "SELECT COUNT(order_id) AS count_ords, SUM(total) AS sum_ords FROM `" . DB_PREFIX
            . "order` WHERE customer_id = '%d'";
        $sql = sprintf($sql, $data['user_id']);

        $sum_ords   = 0;
        $count_ords = 0;
        $query      = $this->db->query($sql);
        if ($query->num_rows) {
            $row = $query->row;
            if (isset($row['sum_ords'])) {
                $sum_ords = $row['sum_ords'];
            }
            if (isset($row['count_ords'])) {
                $count_ords = $row['count_ords'];
            }
        }

        if (!isset($data['currency_code'])) {
            $data['currency_code'] = $this->config->get('config_currency');
        }

        $sum_ords   = Mobileassistant_helper::getModel('helper', $this)->nice_price($sum_ords, $data['currency_code']);
        $count_ords = Mobileassistant_helper::getModel('helper', $this)->nice_count($count_ords);

        return array("c_orders_count" => (int)$count_ords, "sum_ords" => $sum_ords);
    }

    public function getProducts($data = array())
    {
        $products           = array();
        $query_where_parts  = array();
        $query_params_parts = array();
        $this->load->model('tool/image');

        $sql = "SELECT p.product_id AS main_id,
                    p.product_id AS product_id,
                    p.model,
                    p.sku,
                    pd.name,
                    p.price,
                    p.quantity,
                    p.image,
                    p.status AS status_code,
                    p.stock_status_id AS stock_code,
                    ss.name AS stock_title,
                    p.sku,
                    p.upc,
                    p.ean,
                    p.jan,
                    p.isbn,
                    p.manufacturer_id,
                    p.weight,
                    p.length,
                    p.width,
                    p.height,
                    m.name AS manufacturer_name
				  FROM `" . DB_PREFIX . "product` AS p
				  LEFT JOIN `" . DB_PREFIX
            . "stock_status` AS ss ON ss.stock_status_id = p.stock_status_id AND ss.language_id = '"
            . $this->getAdminLanguageId() . "'
				  LEFT JOIN `" . DB_PREFIX
            . "product_description` AS pd ON pd.product_id = p.product_id AND pd.language_id = '"
            . $this->getAdminLanguageId() . "'
				  LEFT JOIN `" . DB_PREFIX . "manufacturer` AS m ON m.manufacturer_id = p.manufacturer_id";

        if (isset($data['params']) && isset($data['val'])) {
            foreach ($data['params'] as $param) {
                switch ($param) {
                    case 'pr_id':
                        $query_params_parts[] = sprintf(" p.product_id LIKE '%%%s%%'", $data['val']);
                        break;
                    case 'pr_sku':
                        $query_params_parts[] = sprintf(" p.model LIKE '%%%s%%'", $data['val']);
                        break;
                    case 'pr_name':
                        $query_params_parts[] = sprintf(" pd.name LIKE '%%%s%%'", $data['val']);
                        break;
                    case 'pr_desc':
                    case 'pr_short_desc':
                        $query_params_parts[] = sprintf(" pd.description LIKE '%%%s%%'", $data['val']);
                        break;
                }
            }
        }

        if (!empty($query_params_parts)) {
            $query_where_parts[] = " ( " . implode(" OR ", $query_params_parts) . " )";
        }

        if (isset($data['store_id'])) {
            $query_where_parts[] = sprintf(" p.product_id IN (SELECT product_id FROM `" . DB_PREFIX
                . "product_to_store` WHERE store_id = '%d')", $data['store_id']);
        }

        if (!empty($query_where_parts)) {
            $sql .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $sql .= " GROUP BY p.product_id ORDER BY ";

        $sort_by  = "p.product_id";
        $order_by = "DESC";
        switch ($data['sort_by']) {
            case 'id':
                $sort_by  = "p.product_id";
                $order_by = "DESC";
                break;
            case 'name':
                $sort_by  = "pd.name";
                $order_by = "ASC";
                break;
            case 'qty':
                $sort_by  = "p.quantity";
                $order_by = "DESC";
                break;
            case 'price':
                $sort_by  = "p.price";
                $order_by = "DESC";
                break;
            case 'status':
                $sort_by  = "p.status";
                $order_by = "DESC";
                break;
        }

        if (isset($data['order_by']) && in_array($data['order_by'], array("asc", "desc"))) {
            $order_by = $data['order_by'];
        }
        $sql .= $sort_by . " " . $order_by;


        $sql .= sprintf(" LIMIT %d, %d", $data['page'], $data['show']);

        if (!isset($data['currency_code'])) {
            $data['currency_code'] = $this->config->get('config_currency');
        }

        $query = $this->db->query($sql);
        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                if ($status_title = $this->_get_product_status_title($row['status_code'])) {
                    $row['status_title'] = $status_title;
                }

                $row['price'] = Mobileassistant_helper::getModel('helper', $this)
                    ->nice_price($row['price'], $data['currency_code']);

                if (!$data['without_thumbnails']) {
                    $thumb = $this->_get_product_images_size();

                    if (isset($row['image']) && strlen($row['image']) > 0) {
                        $row['product_image'] = $this->model_tool_image
                            ->resize($row['image'], $thumb['width'], $thumb['height']);
                    } else {
                        $row['product_image'] = $this->model_tool_image
                            ->resize('placeholder.png', $thumb['width'], $thumb['height']);
                    }
                }
                unset($row['image']);

                $products[] = $row;
            }
        }

        return array("products_count" => $this->getCountProducts($query_where_parts), "products" => $products);
    }

    private function _get_product_status_title($status_code)
    {
        $this->load->model('localisation/language');
        $language = $this->model_localisation_language->getLanguage($this->getAdminLanguageId());

//        $this->check_version();
        $language_code = $language['directory'];
        if (version_compare(Mobileassistant_helper::getCartVersion(), '2.2.0.0', '>=')) {
            $language_code = $language['code'];
        }

        if (file_exists('./admin/language/' . $language_code . '/' . $language_code . '.php')) {
            include('./admin/language/' . $language_code . '/' . $language_code . '.php');

            if ($status_code && isset($_['text_enabled'])) {
                return $_['text_enabled'];
            }
            if (!$status_code && isset($_['text_disabled'])) {
                return $_['text_disabled'];
            }
        }

        return false;
    }

    public function getCountProducts($query_where_parts = array())
    {
        $sql = "SELECT COUNT(p.product_id) AS count_prods FROM `" . DB_PREFIX . "product` AS p
                       LEFT JOIN `" . DB_PREFIX
            . "product_description` AS pd ON pd.product_id = p.product_id AND pd.language_id = '"
            . $this->getAdminLanguageId() . "'";

        if (!empty($query_where_parts)) {
            $sql .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $query = $this->db->query($sql);
        $row   = $query->row;

        return Mobileassistant_helper::getModel('helper', $this)->nice_count($row['count_prods']);
    }

    public function getOrderedProducts($data = array())
    {
        $products           = array();
        $query_where_parts  = array();
        $query_params_parts = array();
        $this->load->model('tool/image');

        $select_currency_code = " o.currency ";
        $sql_currency_code    = "SHOW COLUMNS FROM `" . DB_PREFIX . "order` WHERE `Field` = 'currency_code'";
        $res_currency_code    = $this->db->query($sql_currency_code);
        if ($res_currency_code->num_rows) {
            $select_currency_code = " o.currency_code ";
        }

        $price_column    = "op.price";
        $quantity_column = "op.quantity";
        if ($data['group_by_product_id']) {
            $price_column    = "SUM(op.price) AS price";
            $quantity_column = "SUM(op.quantity) AS quantity";
        }

        $sql = "SELECT
                    op.order_id AS main_id,
                    op.order_id AS order_id,
                    op.product_id,
                    op.model,
                    op.name,
                    " . $price_column . ",
                    p.price AS real_price,
                    " . $quantity_column . ",
                    " . $select_currency_code . " AS currency_code,
                    p.image,
                    p.sku,
                    p.upc,
                    p.ean,
                    p.jan,
                    p.isbn,
                    p.manufacturer_id,
                    p.weight,
                    p.length,
                    p.width,
                    p.height,
                    m.name AS manufacturer_name
				  FROM `" . DB_PREFIX . "order_product` AS op
				  LEFT JOIN `" . DB_PREFIX . "order` AS o ON o.order_id = op.order_id
				  LEFT JOIN `" . DB_PREFIX . "product` AS p ON op.product_id = p.product_id
				  LEFT JOIN `" . DB_PREFIX . "manufacturer` AS m ON m.manufacturer_id = p.manufacturer_id";

        if (isset($data['params']) && isset($data['val'])) {
            foreach ($data['params'] as $param) {
                switch ($param) {
                    case 'pr_id':
                        if (isset($data['val']) && preg_match('/^\d+(?:,\d+)*$/', $data['val'])) {
                            $query_params_parts[] = sprintf(" op.product_id IN ('%s')", $data['val']);
                        } else {
                            $query_params_parts[] = sprintf(" op.product_id = '%d'", $data['val']);
                        }

                        break;
                    case 'pr_sku':
                        $query_params_parts[] = sprintf(" op.model LIKE '%%%s%%'", $data['val']);
                        break;
                    case 'pr_name':
                        $query_params_parts[] = sprintf(" op.name LIKE '%%%s%%'", $data['val']);
                        break;
                }
            }
        }

        if (!empty($query_params_parts)) {
            $query_where_parts[] = " ( " . implode(" OR ", $query_params_parts) . " )";
        }

        if (isset($data['products_from'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(o.date_added, '+00:00', @@global.time_zone)) >= '%d'",
                    strtotime($data['products_from']));
        }

        if (isset($data['products_to'])) {
            $query_where_parts[] =
                sprintf(" UNIX_TIMESTAMP(CONVERT_TZ(o.date_added, '+00:00', @@global.time_zone)) <= '%d'",
                    strtotime($data['products_to']));
        }

        if (isset($data['statuses'])) {
            $query_where_parts[] = sprintf(" o.order_status_id IN ('%s')", $data['statuses']);
        }

        if (isset($data['store_id'])) {
            $query_where_parts[] = sprintf(" o.store_id = '%d'", $data['store_id']);
        }

        if (!empty($query_where_parts)) {
            $sql .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        if ($data['group_by_product_id']) {
            $sql .= " GROUP BY op.product_id ";
        }

        $sql .= " ORDER BY ";

        $sort_by  = "op.product_id";
        $order_by = "DESC";
        switch ($data['sort_by']) {
            case 'id':
                $sort_by  = "op.product_id";
                $order_by = "DESC";
                break;
            case 'name':
                $sort_by  = "op.name";
                $order_by = "ASC";
                break;
            case 'qty':
                $sort_by  = "quantity";
                $order_by = "DESC";
                break;
            case 'total':
                $sort_by  = "price";
                $order_by = "DESC";
                break;
        }

        if (isset($data['order_by']) && in_array($data['order_by'], array("asc", "desc"))) {
            $order_by = $data['order_by'];
        }
        $sql .= $sort_by . " " . $order_by;

        $sql .= sprintf(" LIMIT %d, %d", $data['page'], $data['show']);

        $query = $this->db->query($sql);
        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                if (isset($data['currency_code'])) {
                    $currency_code = $data['currency_code'];
                } else {
                    $currency_code = $row['currency_code'];
                }

                $row['price'] = Mobileassistant_helper::getModel('helper', $this)
                    ->nice_price($row['price'], $currency_code);

                if (!$data['without_thumbnails']) {
                    $thumb = $this->_get_product_images_size();

                    if (isset($row['image']) && strlen($row['image']) > 0) {
                        $row['product_image'] = $this->model_tool_image
                            ->resize($row['image'], $thumb['width'], $thumb['height']);
                    } else {
                        $row['product_image'] = $this->model_tool_image
                            ->resize('placeholder.png', $thumb['width'], $thumb['height']);
                    }
                }
                unset($row['image']);

                $products[] = $row;
            }
        }

        $total_ordered_products = $this->getTotalOrderedProducts($query_where_parts, $data['group_by_product_id']);

        return array("products_count" => $total_ordered_products['count_prods'], "products" => $products);
    }

    public function getTotalOrderedProducts($query_where_parts = array(), $group_by_product_id = false)
    {

        $count_field = "COUNT(op.product_id)";
        if ($group_by_product_id) {
            $count_field = "COUNT(DISTINCT op.product_id)";
        }

        $sql = "SELECT " . $count_field . " AS count_prods FROM `" . DB_PREFIX . "order_product` AS op
                    LEFT JOIN `" . DB_PREFIX . "order` AS o ON o.order_id = op.order_id";

        if (!empty($query_where_parts)) {
            $sql .= " WHERE " . implode(" AND ", $query_where_parts);
        }

        $query = $this->db->query($sql);
        $row   = $query->row;

        $row['count_prods'] = Mobileassistant_helper::getModel('helper', $this)->nice_count($row['count_prods']);

        return $row;
    }

    public function getProductTotalOrdered($product_id)
    {
        $query_where_parts[] = sprintf(" op.product_id = '%d'", $product_id);

        $total_ordered_products = $this->getTotalOrderedProducts($query_where_parts);

        return $total_ordered_products['total_ordered'];
    }

    public function getProductInfo($data = array())
    {
        $this->load->model('tool/image');

        $sql = "SELECT
					p.product_id AS id_product,
					p.product_id AS product_id,
					pd.name,
					p.model,
					p.sku,
					p.price,
					p.quantity,
					p.image,
					(SELECT SUM(quantity) FROM `" . DB_PREFIX . "order_product` WHERE product_id = p.product_id) AS total_ordered,
					(IF(p.status = 1, 'Enabled', 'Disabled')) AS forsale,
					p.status AS status_code,
					p.stock_status_id AS stock_code,
                    ss.name AS stock_title,
                    p.sku,
                    p.upc,
                    p.ean,
                    p.jan,
                    p.isbn,
                    p.manufacturer_id,
                    p.weight,
                    p.length,
                    p.width,
                    p.height,
                    m.name AS manufacturer_name
				FROM `" . DB_PREFIX . "product` AS p
				    LEFT JOIN `" . DB_PREFIX
            . "product_description` AS pd ON pd.product_id = p.product_id AND pd.language_id = '"
            . $this->getAdminLanguageId() . "'
				    LEFT JOIN `" . DB_PREFIX
            . "stock_status` AS ss ON ss.stock_status_id = p.stock_status_id AND ss.language_id = '"
            . $this->getAdminLanguageId() . "'
				    LEFT JOIN `" . DB_PREFIX . "manufacturer` AS m ON m.manufacturer_id = p.manufacturer_id
				WHERE p.product_id = '%d' GROUP BY p.product_id";
        $sql = sprintf($sql, $data['product_id']);

        $query = $this->db->query($sql);

        if ($query->num_rows) {
            $product                  = $query->row;
            $product['total_ordered'] = (int)$product['total_ordered'];

            $product['price'] = Mobileassistant_helper::getModel('helper', $this)
                ->nice_price($product['price'], $data['currency_code']);

            if ($status_title = $this->_get_product_status_title($product['status_code'])) {
                $product['status_title'] = $status_title;
            }

            if (!$data['without_thumbnails']) {
                $images_array = array();

                $popup = $this->_get_product_images_size("popup", 500);

                if ($product['image']) {
                    $images_array[] = array(
                        "small" => $this->model_tool_image->resize($product['image'], $popup['width'],
                            $popup['height']),
                        "large" => $this->model_tool_image->resize($product['image'], $popup['width'], $popup['height'])
                    );
                } else {
                    $images_array[] = array(
                        "small" => $this->model_tool_image->resize('placeholder.png', $popup['width'], $popup['height'])
                    );
                }


                $sql_img   = "SELECT image FROM `" . DB_PREFIX
                    . "product_image` WHERE product_id = '%d' AND image != '' ORDER BY sort_order";
                $sql_img   = sprintf($sql_img, $data['product_id']);
                $query_img = $this->db->query($sql_img);

                if ($query_img->num_rows) {
                    foreach ($query_img->rows as $image) {
                        $images_array[] = array(
                            "small" => $this->model_tool_image->resize($image['image'], $popup['width'],
                                $popup['height']),
                            "large" => $this->model_tool_image->resize($image['image'], $popup['width'],
                                $popup['height'])
                        );
                    }
                }

                $product["images"] = $images_array;
            }

            unset($product['image']);

            return $product;
        } else {
            return false;
        }
    }

    public function getProductDescr($data = array())
    {
        $sql = "SELECT description AS descr FROM `" . DB_PREFIX
            . "product_description` WHERE product_id = '%d' AND language_id = '" . $this->getAdminLanguageId() . "'";
        $sql = sprintf($sql, $data['product_id']);

        $query = $this->db->query($sql);

        if ($query->num_rows) {
            return $query->row;
        }

        return false;
    }

    public function getProductToEdit($product_id)
    {
        $product_id = (int)$product_id;

        if ($product_id < 1) {
            return false;
        }

        $query = $this->db->query(
            'SELECT `product_id`, `model`, `sku`, `upc`, `ean`, `jan`, `isbn`, `mpn`, `location`, `quantity`, `status`, 
            `stock_status_id`,`shipping`,`tax_class_id`,`date_available`,`subtract`,`manufacturer_id`,`price`, `image`,
             `minimum` FROM `' . DB_PREFIX . "product` WHERE `product_id` = $product_id"
        );

        if (!$query->num_rows) {
            return false;
        }

        $this->load->model('tool/image');
        $popup = $this->_get_product_images_size("popup", 500);

        $result = array(
            'product' => array(
                'id_product' => $query->row['product_id'],
                'status' => $query->row['status'],
                'model' => $query->row['model'],
                'ean' => $query->row['ean'],
                'upc' => $query->row['upc'],
                'sku' => $query->row['sku'],
                'jan' => $query->row['jan'],
                'mpn' => $query->row['mpn'],
                'isbn' => $query->row['isbn'],
                'location' => $query->row['location'],
                'quantity' => $query->row['quantity'],
                'stock_status_id' => $query->row['stock_status_id'],
                'shipping' => $query->row['shipping'],
                'tax_class_id' => $query->row['tax_class_id'],
                'date_available' => $query->row['date_available'],
                'subtract' => $query->row['subtract'],
                'manufacturer_id' => $query->row['manufacturer_id'],
                'minimum' => $query->row['minimum'],
                'price_tax_excluded' => $query->row['price'],
                'main_image' => !empty($query->row['image'])
                    ? $this->model_tool_image->resize($query->row['image'], $popup['width'], $popup['height'])
                    : '',
            ),
            'image_placeholder' => $this->model_tool_image->resize(
                'no_image.' . (Mobileassistant_helper::isCartVersion20() ? 'png' : 'jpg'),
                $popup['width'],
                $popup['height']
            )
        );

        // Get product name
        $query = $this->db->query(
            'SELECT `language_id`, `name`, `description` FROM `' . DB_PREFIX
            . "product_description` WHERE `product_id` = $product_id"
        );

        foreach ($query->rows as $row) {
            $result['product']['name'][$row['language_id']]        = htmlspecialchars_decode($row['name']);
            $result['product']['description'][$row['language_id']] = htmlspecialchars_decode($row['description']);
        }

        // Get product images
        $result['images'] = array();
        $query            = $this->db->query(
            "SELECT `product_image_id`, image, sort_order FROM " . DB_PREFIX . "product_image WHERE product_id = '"
            . (int)$product_id . "' ORDER BY sort_order ASC"
        );
        if ($query->num_rows) {
            $i = 0;

            foreach ($query->rows as $image) {
                $result['images'][$i]['id_image']   = $image['product_image_id'];
                $result['images'][$i]['sort_order'] = $image['sort_order'];
                $result['images'][$i]['image_url']  = $this->model_tool_image->resize(
                    $image['image'],
                    $popup['width'],
                    $popup['height']
                );

                $i++;
            }
        }

        $query = $this->db->query(
            "SELECT product_special_id, customer_group_id, priority, price, date_start, date_end 
            FROM " . DB_PREFIX . "product_special WHERE product_id = "
            . (int)$product_id
        );

        if ($query->num_rows) {
            $i = 0;

            foreach ($query->rows as $special) {
                $result['product']['product_special'][$i]['product_special_id'] = $special['product_special_id'];
                $result['product']['product_special'][$i]['customer_group_id']  = $special['customer_group_id'];
                $result['product']['product_special'][$i]['priority']           = $special['priority'];
                $result['product']['product_special'][$i]['price']              = $special['price'];
                $result['product']['product_special'][$i]['date_start']         = $special['date_start'];
                $result['product']['product_special'][$i]['date_end']           = $special['date_end'];

                $i++;
            }
        } else {
            $result['product']['product_special'] = "";
        }

        $product_data = self::getCommonDataForProduct();
        $result       = array_merge($result, $product_data);

        return $result;
    }

    public function getCommonDataForProduct()
    {

        // Get all stock statuses
        $query = $this->db->query(
            "SELECT stock_status_id, language_id, name FROM " . DB_PREFIX . "stock_status"
        );

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $result['stock_statuses'][$row['language_id']][$row['stock_status_id']] = $row['name'];
            }
        }

        // Get all tax classes
        $query = $this->db->query(
            "SELECT tax_class_id, title FROM " . DB_PREFIX . "tax_class"
        );

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $result['tax_classes'][$row['tax_class_id']] = $row['title'];
            }
        }

        $query = $this->db->query(
            "SELECT manufacturer_id, name FROM " . DB_PREFIX . "manufacturer"
        );

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $result['manufacturers'][$row['manufacturer_id']] = $row['name'];
            }
        }

        $query = $this->db->query(
            "SELECT customer_group_id, language_id, name FROM " . DB_PREFIX . "customer_group_description"
        );

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $result['customer_group'][$row['language_id']][$row['customer_group_id']] = $row['name'];
            }
        }

        // Get all languages
        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();

        foreach ($languages as $language) {
            $result['languages'][$language['language_id']] = $language['name'];
        }

        $result['max_file_upload_in_bytes'] = self::get_max_file_upload_in_bytes();

        return $result;
    }

    private static function get_max_file_upload_in_bytes()
    {
        //select maximum upload size
        $max_upload = self::return_bytes(ini_get('upload_max_filesize'));

        //select post limit
        $max_post = self::return_bytes(ini_get('post_max_size'));

        //select memory limit
        $memory_limit = self::return_bytes(ini_get('memory_limit'));

        // return the smallest of them, this defines the real limit
        return min($max_upload, $max_post, $memory_limit);
    }

    private static function return_bytes($val)
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val) - 1]);

        switch ($last) {
            case 'g':
                $val *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $val *= 1024 * 1024;
                break;
            case 'k':
                $val *= 1024;
                break;
        }

        return $val;
    }

    public function saveProductData($data, $store_id, $image)
    {
        if (empty($data)) {
            return false;
        }

        if (!empty($image)) {
            return $this->storeImage($data['product_id'], $image, isset($data['main_image']));
        }

        if (empty($data['product_id'])) {
            $is_new                        = true;
            $query_action                  = "INSERT INTO ";
            $query_data_added_condition    = "date_added = NOW(), ";
            $query_where_product_condition = '';
            $query_where_condition         = ', ';
            $query_and_condition           = ', ';
        } else {
            $is_new                        = false;
            $query_action                  = "UPDATE ";
            $query_where_product_condition = "WHERE product_id = " . $data['product_id'];
            $query_where_condition         = ' WHERE ';
            $query_data_added_condition    = '';
            $query_and_condition           = ' AND ';
        }

        if (array_key_exists('shipping', $data) && array_key_exists('subtract', $data)) {
            $query_parametrs = "', subtract = '" . (int)$data['subtract'] .
                "', minimum = '" . (int)$data['minimum'] .
                "', stock_status_id = '" . (int)$data['stock_status_id'] .
                "', date_available = '" . $this->db->escape($data['date_available']) .
                "', manufacturer_id = '" . (int)$data['manufacturer_id'] .
                "', shipping = '" . (int)$data['shipping'] .
                "', tax_class_id = '" . (int)$data['tax_class_id'];
        } else {
            $query_parametrs = " ";
        }

        try {
            $this->db->query($query_action . DB_PREFIX . "product 
                SET model = '" . $this->db->escape($data['model']) .
                "', sku = '" . $this->db->escape($data['sku']) .
                "', upc = '" . $this->db->escape($data['upc']) .
                "', ean = '" . $this->db->escape($data['ean']) .
                "', jan = '" . $this->db->escape($data['jan']) .
                "', isbn = '" . $this->db->escape($data['isbn']) .
                "', mpn = '" . $this->db->escape($data['mpn']) .
                "', location = '" . $this->db->escape($data['location']) .
                "', quantity = '" . (int)$data['quantity'] .
                "', price = '" . (float)$data['price_tax_excluded'] .
                $query_parametrs . "', status = '" . (int)$data['status'] .
                "', " . $query_data_added_condition . "date_modified = NOW()" . $query_where_product_condition);

            if ($is_new) {
                $data['product_id'] = $this->db->getLastId();

                $this->db->query(
                    'INSERT ' . DB_PREFIX . 'product_to_store (product_id, store_id)
                    VALUES (' . $data['product_id'] . ', ' .
                    $store_id . ')'
                );

            } else {
                // Delete images
                if (!empty($data['deleted_images'])) {
                    $this->db->query(
                        'DELETE FROM ' . DB_PREFIX . 'product_image WHERE `product_id` = ' . $data['product_id']
                        . ' AND `product_image_id` IN (' . $data['deleted_images'] . ')'
                    );
                }

                if (!empty($data['delete_main_image'])) {
                    $this->db->query(
                        'UPDATE ' . DB_PREFIX . "product SET image = '' WHERE `product_id` = " . $data['product_id']
                    );
                }
            }

            if (!empty($data['product_special'])) {
                $query = $this->db->query(
                    'SELECT product_special_id FROM ' . DB_PREFIX . "product_special WHERE product_id = "
                    . (int)$data['product_id']
                );

                $product_special_ids_db      = array();
                $product_special_ids_request = array();
                if ($query->num_rows) {
                    foreach ($query->rows as $special) {
                        array_push($product_special_ids_db, (int)$special['product_special_id']);
                    }
                }

                foreach ($data['product_special'] as $special) {
                    if (in_array($special['product_special_id'], $product_special_ids_db)) {
                        $this->db->query(
                            'UPDATE ' . DB_PREFIX . "product_special SET customer_group_id = '"
                            . $this->db->escape(htmlspecialchars($special['customer_group_id'])) . "', priority = '"
                            . $this->db->escape(htmlspecialchars($special['priority'])) . "', price = '"
                            . $this->db->escape(htmlspecialchars($special['price'])) . "', date_start = '"
                            . $this->db->escape(htmlspecialchars($special['date_start'])) . "', date_end = '"
                            . $this->db->escape(htmlspecialchars($special['date_end']))
                            . "'WHERE product_id = " . (int)$data['product_id'] . " AND product_special_id = "
                            . (int)$special['product_special_id']
                        );
                    } elseif (!in_array($special['product_special_id'], $product_special_ids_db) || $is_new) {
                        $this->db->query(
                            'INSERT INTO ' . DB_PREFIX . "product_special SET customer_group_id = '"
                            . $this->db->escape(htmlspecialchars($special['customer_group_id'])) . "', priority = '"
                            . $this->db->escape(htmlspecialchars($special['priority'])) . "', price = '"
                            . $this->db->escape(htmlspecialchars($special['price'])) . "', date_start = '"
                            . $this->db->escape(htmlspecialchars($special['date_start'])) . "', date_end = '"
                            . $this->db->escape(htmlspecialchars($special['date_end']))
                            . "', product_id = " . (int)$data['product_id']
                        );
                    }
                    array_push($product_special_ids_request, $special['product_special_id']);
                }

                $product_special_ids_for_delete = array_diff($product_special_ids_db, $product_special_ids_request);
                if (!empty($product_special_ids_for_delete)) {
                    $product_special_ids = implode(", ", $product_special_ids_for_delete);
                    $this->db->query(
                        "DELETE FROM " . DB_PREFIX . "product_special WHERE product_special_id IN("
                        . $product_special_ids . ")"
                    );
                }
            } else {
                $this->db->query(
                    "DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = "
                    . (int)$data['product_id']
                );
            }

            if (!empty($data['name'])) {
                foreach ($data['name'] as $language_id => $value) {
                    $this->db->query(
                        $query_action . DB_PREFIX . "product_description SET name = '"
                        . $this->db->escape(htmlspecialchars($value)) . "' " . $query_where_condition . " product_id = "
                        . $data['product_id']
                        . $query_and_condition . 'language_id = ' . (int)$language_id
                    );
                }
            }

            if (!empty($data['description'])) {
                foreach ($data['description'] as $language_id => $value) {
                    $this->db->query(
                        "UPDATE " . DB_PREFIX . "product_description SET description = '"
                        . $this->db->escape(htmlspecialchars($value)) . "' WHERE product_id = " . $data['product_id']
                        . " AND language_id = " . (int)$language_id
                    );
                }
            }

        } catch (Exception $e) {
            return array('error' => $e->getMessage());
        }

        return array('product_id' => $data['product_id'], 'success' => 'true');
    }

    private function storeImage($product_id, $image, $is_main_image = false)
    {
        // Return any upload error
        if ($image['image']['error'] != UPLOAD_ERR_OK) {
            return array('error' => 'Error uploading file');
        }

        // Allowed file extension types
        $allowed = array('jpg', 'jpeg', 'gif', 'png');

        if (!in_array(utf8_strtolower(utf8_substr(strrchr($image['image']['name'], '.'), 1)), $allowed)) {
            return array('error' => 'Not supported file type');
        }

        // Allowed file mime types
        $allowed = array(
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/x-png',
            'image/gif'
        );

        if (!in_array($image['image']['type'], $allowed)) {
            return array('error' => 'Not supported file type');
        }

        $image_db_path = (Mobileassistant_helper::isCartVersion20() ? 'catalog' : 'data')
            . '/' . $image['image']['name'];

        if (move_uploaded_file($image['image']['tmp_name'], DIR_IMAGE . $image_db_path)) {
            if ($is_main_image) {
                $this->db->query(
                    'UPDATE ' . DB_PREFIX . "product SET `image` = '" . $this->db->escape($image_db_path)
                    . "' WHERE `product_id` = $product_id"
                );
            } else {
                $this->db->query(
                    'INSERT INTO ' . DB_PREFIX . "product_image SET product_id = $product_id, image = '"
                    . $this->db->escape($image_db_path) . "'"
                );
            }

            return array('success' => 'true');
        }

        return array('error' => 'Error moving file');
    }

    public function savePushNotificationSettings($data = array())
    {
        $query_values = array();
        $query_where  = array();

        if (isset($data['registration_id_old'])) {
            $sql = "UPDATE `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
                . "` SET registration_id = '%s' WHERE registration_id = '%s' AND user_id = '%d'";
            $sql = sprintf($sql, $data['registration_id'], $data['registration_id_old'], $data['user_id']);
            $this->db->query($sql);
        }

        if (empty($data['push_new_order']) && empty($data['push_order_statuses'])
            && empty($data['push_new_customer'])) {
            $sql_del = "DELETE FROM `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
                . "` WHERE registration_id = '%s' AND app_connection_id = '%s' AND user_id = '%d'";
            $sql_del = sprintf($sql_del, $data['registration_id'], $data['app_connection_id'], $data['user_id']);

            $this->db->query($sql_del);

            return true;
        }

        $query_values[] = sprintf(" push_new_order = '%d'", $data['push_new_order']);
        $query_values[] = sprintf(" push_order_statuses = '%s'", $data['push_order_statuses']);
        $query_values[] = sprintf(" push_new_customer = '%d'", $data['push_new_customer']);
        $query_values[] = sprintf(" push_currency_code = '%s'", $data['push_currency_code']);
        $query_values[] = sprintf(" store_id = '%d'", $data['store_id']);
        $query_values[] = sprintf(" user_id = '%d'", $data['user_id']);

        $sql = "SELECT setting_id FROM `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "`
                WHERE registration_id = '%s' AND app_connection_id = '%s' AND user_id = '%d'";

        $sql = sprintf($sql, $data['registration_id'], $data['app_connection_id'], $data['user_id']);

        $query = $this->db->query($sql);

        if ($query->num_rows > 1 || $query->num_rows <= 0 || !$query->num_rows) {
            if ($query->num_rows > 1) {
                foreach ($query->rows as $row) {
                    $sql_del = "DELETE FROM `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS
                        . "` WHERE setting_id = '%d' AND user_id = '%d'";
                    $sql_del = sprintf($sql_del, $row['setting_id'], $data['user_id']);
                    $this->db->query($sql_del);
                }
            }

            $query_values[] = sprintf(" registration_id = '%s'", $data['registration_id']);
            $query_values[] = sprintf(" app_connection_id = '%s'", $data['app_connection_id']);

            $sql = "INSERT INTO `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "` SET ";

            if (!empty($query_values)) {
                $sql .= implode(" , ", $query_values);
            }

            $this->db->query($sql);
            return true;
        } else {
            $query_where[] = sprintf(" registration_id = '%s'", $data['registration_id']);
            $query_where[] = sprintf(" app_connection_id = '%s'", $data['app_connection_id']);
            $query_where[] = sprintf(" user_id = '%d'", $data['user_id']);

            $sql = "UPDATE `" . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "` SET ";

            if (!empty($query_values)) {
                $sql .= implode(" , ", $query_values);
            }

            if (!empty($query_where)) {
                $sql .= " WHERE " . implode(" AND ", $query_where);
            }

            $this->db->query($sql);
            return true;
        }
    }

    public function addOrderHistory_156x($order_id, $order_status_id, $comment = '', $notify = false)
    {
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info) {
            // Fraud Detection
            if ($this->config->get('config_fraud_detection')) {
                $this->load->model('checkout/fraud');

                $risk_score = $this->model_checkout_fraud->getFraudScore($order_info);

                if ($risk_score > $this->config->get('config_fraud_score')) {
                    $order_status_id = $this->config->get('config_fraud_status_id');
                }
            }


            // Ban IP
            $status = false;

            $this->load->model('account/customer');

            if ($order_info['customer_id']) {

                $results = $this->model_account_customer->getIps($order_info['customer_id']);

                foreach ($results as $result) {
                    if ($this->model_account_customer->isBanIp($result['ip'])) {
                        $status = true;

                        break;
                    }
                }
            } else {
                $status = $this->model_account_customer->isBanIp($order_info['ip']);
            }

            if ($status) {
                $order_status_id = $this->config->get('config_order_status_id');
            }

            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id
                . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

            $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id
                . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '"
                . $this->db->escape($comment) . "', date_added = NOW()");

            // Send out any gift voucher mails
            if ($this->config->get('config_complete_status_id') == $order_status_id) {
                $this->load->model('checkout/voucher');

                $this->model_checkout_voucher->confirm($order_id);
            }

            if ($notify) {
                $language = new Language($order_info['language_directory']);
                $language->load($order_info['language_filename']);
                $language->load('mail/order');

                $subject = sprintf($language->get('text_update_subject'),
                    html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'), $order_id);

                $message = $language->get('text_update_order') . ' ' . $order_id . "\n";
                $message .= $language->get('text_update_date_added') . ' ' . date($language->get('date_format_short'),
                        strtotime($order_info['date_added'])) . "\n\n";

                $order_status_query =
                    $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_status` WHERE order_status_id = '"
                        . (int)$order_status_id . "' AND language_id = '" . (int)$order_info['language_id'] . "'");

                if ($order_status_query->num_rows) {
                    $message .= $language->get('text_update_order_status') . "\n\n";
                    $message .= $order_status_query->row['name'] . "\n\n";
                }

                if ($order_info['customer_id']) {
                    $message .= $language->get('text_update_link') . "\n";
                    $message .= $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_id
                        . "\n\n";
                }

                if ($comment) {
                    $message .= $language->get('text_update_comment') . "\n\n";
                    $message .= $comment . "\n\n";
                }

                $message .= $language->get('text_update_footer');

                $mail            = new Mail();
                $mail->protocol  = $this->config->get('config_mail_protocol');
                $mail->parameter = $this->config->get('config_mail_parameter');
                $mail->hostname  = $this->config->get('config_smtp_host');
                $mail->username  = $this->config->get('config_smtp_username');
                $mail->password  = $this->config->get('config_smtp_password');
                $mail->port      = $this->config->get('config_smtp_port');
                $mail->timeout   = $this->config->get('config_smtp_timeout');
                $mail->setTo($order_info['email']);
                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender($order_info['store_name']);
                $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
                $mail->setText(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                $mail->send();
            }
        }
    }

    public function addOrderHistory_154x($order_id, $order_status_id, $comment = '', $notify = false)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id
            . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

        $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id
            . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . $notify . "', comment = '"
            . $this->db->escape(strip_tags($comment)) . "', date_added = NOW()");

        $order_info = $this->model_checkout_order->getOrder($order_id);

        // Send out any gift voucher mails
        if ($this->config->get('config_complete_status_id') == $order_status_id) {
            $this->load->model('checkout/voucher');
            $this->load->model('account/order');

            $results = $this->model_account_order->getOrderVouchers($order_id);

            foreach ($results as $result) {
                $this->model_sale_voucher->sendVoucher($result['voucher_id']);
            }
        }

        if ($notify) {
            $language = new Language($order_info['language_directory']);
            $language->load($order_info['language_filename']);
            $language->load('mail/order');

//            $subject = sprintf($language->get('text_subject'), $order_info['store_name'], $order_id);
            $subject = sprintf($language->get('text_update_subject'), $order_info['store_name'], $order_id);

//            $message  = $language->get('text_order') . ' ' . $order_id . "\n";
            $message = $language->get('text_update_order') . ' ' . $order_id . "\n";
            $message .= $language->get('text_update_date_added') . ' ' . date($language->get('date_format_short'),
                    strtotime($order_info['date_added'])) . "\n\n";

            $order_status_query =
                $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_status` WHERE order_status_id = '"
                    . (int)$order_status_id . "' AND language_id = '" . (int)$order_info['language_id'] . "'");

            if ($order_status_query->num_rows) {
                $message .= $language->get('text_update_order_status') . "\n";
                $message .= $order_status_query->row['name'] . "\n\n";
            }

            if ($order_info['customer_id']) {
                $message .= $language->get('text_update_link') . "\n";
                $message .= html_entity_decode($order_info['store_url'] . 'index.php?route=account/order/info&order_id='
                        . $order_id, ENT_QUOTES, 'UTF-8') . "\n\n";
            }

            if ($comment) {
                $message .= $language->get('text_update_comment') . "\n\n";
                $message .= strip_tags(html_entity_decode($comment, ENT_QUOTES, 'UTF-8')) . "\n\n";
            }

            $message .= $language->get('text_update_footer');

            $mail            = new Mail();
            $mail->protocol  = $this->config->get('config_mail_protocol');
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->hostname  = $this->config->get('config_smtp_host');
            $mail->username  = $this->config->get('config_smtp_username');
            $mail->password  = $this->config->get('config_smtp_password');
            $mail->port      = $this->config->get('config_smtp_port');
            $mail->timeout   = $this->config->get('config_smtp_timeout');
            $mail->setTo($order_info['email']);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender($order_info['store_name']);
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setText(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
            $mail->send();
        }
    }

    public function update_module($s)
    {
        $sql = "SELECT user_id FROM `" . DB_PREFIX . self::T_USERS . "`";

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

        $sql =
            sprintf($sql, $s['mobassist_login'], $s['mobassist_pass'], hash('sha256', md5(time() . rand(1111, 99999))));
        $this->db->query($sql);

        $user_id = $this->db->getLastId();

        $sql = 'UPDATE `' . DB_PREFIX . self::T_DEVICES . "` SET user_id = '" . $user_id . "'";
        $this->db->query($sql);

        $sql = 'UPDATE `' . DB_PREFIX . self::T_PUSH_NOTIFICATIONS . "` SET user_id = '" . $user_id . "'";
        $this->db->query($sql);
    }

    public function addEvent($code, $trigger, $action)
    {
        $this->db->query(
            "INSERT INTO " . DB_PREFIX . "event SET `code` = '" . $this->db->escape($code) . "', `trigger` = '"
            . $this->db->escape($trigger) . "', `action` = '" . $this->db->escape($action) . "'"
        );

        return $this->db->getLastId();
    }

    public function deleteEvent($code)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "event WHERE `code` = '" . $this->db->escape($code) . "'");
    }

}

class Modelmobileassistantconnector extends BaseModelMobileassistantConnector
{

}

class ModelExtensionMobileassistantConnector extends BaseModelMobileassistantConnector
{

}