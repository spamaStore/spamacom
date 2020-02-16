<?php

class ModelExtensionModuleEeTracking extends Model {

    protected function getGlobalFields() {
        $fields = array();
        $fields['v'] = 1;
        $fields['tid'] = $this->config->get('module_ee_tracking_tracking_id');
        $fields['t'] = 'event';
        $fields['ec'] = 'Enhanced Ecommerce';
        $fields['uip'] = $this->getUserIP();
        if (isset($this->request->server['HTTP_USER_AGENT'])) {
            $fields['ua'] = $this->request->server['HTTP_USER_AGENT'];
        }
        return $fields;
    }

    public function listView($data) {
        $client_id = $this->getClientId();
        if ($client_id && $data) {
            $this->load->language('extension/module/ee_tracking');
            $fields = $this->getGlobalFields();
            if (isset($data['url']) && isset($data['title'])) {
                $fields['dl'] = $data['url'];
                $fields['dt'] = $data['title'];
            }
            $fields['cid'] = $client_id;
            $fields['ni'] = 1;
            $fields['ea'] = 'List View';
            $settings = $this->getSettings();
            $fields['il1nm'] = $this->language->get('text_' . $data['type']);
            $position = isset($data['position']) ? $data['position'] : 1;
            if (count($data['products']) > 26) {
                $result = '';
                $batches = array_chunk($data['products'], 26);
                foreach ($batches as $batch) {
                    $product_fields = $this->getProducts($batch, $settings, 'il1pi', $position);
                    $product_fields = array_merge($fields, $product_fields);
                    $response = $this->setCurlRequest($product_fields, $this->config->get('module_ee_tracking_impression_debug'));
                    if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_impression_log')) {
                        $log = 'LIST VIEW';
                        $log .= $this->config->get('module_ee_tracking_impression_debug') ? ' / mode: debug' : ' / mode: release';
                        $log .= ' / list: ' . $fields['il1nm'] . $response;
                        if ($this->config->get('module_ee_tracking_extended_log')) {
                            $log .= "\n" . implode(', ', $product_fields);
                        }
                        $this->addLog($log);
                        $result .= $log . "\n";
                    }
                    $position += 26;
                }
                return $result;
            } else {
                $product_fields = $this->getProducts($data['products'], $settings, 'il1pi', $position);
                $fields = array_merge($fields, $product_fields);
                $response = $this->setCurlRequest($fields, $this->config->get('module_ee_tracking_impression_debug'));
                if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_impression_log')) {
                    $log = 'LIST VIEW';
                    $log .= $this->config->get('module_ee_tracking_impression_debug') ? ' / mode: debug' : ' / mode: release';
                    $log .= ' / list: ' . $fields['il1nm'] . $response;
                    if ($this->config->get('module_ee_tracking_extended_log')) {
                        $log .= "\n" . implode(', ', $fields);
                    }
                    $this->addLog($log);
                    return $log;
                }
            }
        }
    }

    public function detail($data) {
        $client_id = $this->getClientId();
        if ($client_id && $data) {
            $fields = $this->getGlobalFields();
            if (isset($data['url']) && isset($data['title'])) {
                $fields['dl'] = $data['url'];
                $fields['dt'] = $data['title'];
            }
            $fields['cid'] = $client_id;
            $fields['ni'] = 1;
            $fields['ea'] = 'Product Detail View';
            $fields['pa'] = 'detail';
            $settings = $this->getSettings();
            $product_fields = $this->getProducts(array($data['product_id']), $settings, 'pr');
            $fields = array_merge($fields, $product_fields);
            $response = $this->setCurlRequest($fields, $this->config->get('module_ee_tracking_detail_debug'));
            if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_detail_log')) {
                $product_info = $this->getProductDescription($data['product_id'], $settings['language_id']);
                $log = 'PRODUCT DETAIL VIEW';
                $log .= $this->config->get('module_ee_tracking_detail_debug') ? ' / mode: debug' : ' / mode: release';
                $log .= ' / product: ' . $product_info['name'] . $response;
                if ($this->config->get('module_ee_tracking_extended_log')) {
                    $log .= "\n" . implode(', ', $fields);
                }
                $this->addLog($log);
                return $log;
            }
        }
    }

    public function click($data) {
        $client_id = $this->getClientId();
        if ($client_id && $data) {
            $this->load->language('extension/module/ee_tracking');
            $fields = $this->getGlobalFields();
            if (isset($data['url']) && isset($data['title'])) {
                $fields['dl'] = $data['url'];
                $fields['dt'] = $data['title'];
            }
            $fields['cid'] = $client_id;
            $fields['ea'] = 'Product Click';
            $settings = $this->getSettings();
            if ($settings['compatibility']) {
                $this->addProductClick($data['product_id'], $fields['cid']);
            }
            $fields['pa'] = 'click';
            $fields['pal'] = $this->language->get('text_' . $data['type']);
            $product_fields = $this->getProducts(array($data['product_id']), $settings, 'pr', $data['position']);
            $fields = array_merge($fields, $product_fields);
            $response = $this->setCurlRequest($fields, $this->config->get('module_ee_tracking_click_debug'));
            if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_click_log')) {
                $log = 'PRODUCT CLICK';
                $log .= $this->config->get('module_ee_tracking_click_debug') ? ' / mode: debug' : ' / mode: release';
                $log .= ' / product: ' . $product_fields['pr1nm'];
                $log .= ' / list: ' . $fields['pal'] . $response;
                if ($this->config->get('module_ee_tracking_extended_log')) {
                    $log .= "\n" . implode(', ', $fields);
                }
                $this->addLog($log);
                return $log;
            }
        }
    }

    public function createClick($data) {
        $client_id = $this->getClientId();
        if ($client_id && $data) {
            $fields = $this->getGlobalFields();
            $fields['dl'] = $data['url'];
            $fields['cid'] = $client_id;
            $fields['ni'] = 1;
            $fields['ea'] = 'Product Click';
            $fields['pa'] = 'click';
            if (!$this->getProductClick($data['product_id'], $fields['cid'])) {
                $fields['qt'] = '1000';
                $settings = $this->getSettings();
                $info = $this->getDataByUrl($data['url'], $data['product_id'], $settings['compatibility']);
                if ($info['list']) {
                    $fields['pal'] = $info['list'];
                    if (isset($info['position'])) {
                        $position = $info['position'];
                    } else {
                        $position = '';
                    }
                    $product_fields = $this->getProducts(array($data['product_id']), $settings, 'pr', $position);
                    $fields = array_merge($fields, $product_fields);
                    $response = $this->setCurlRequest($fields, $this->config->get('module_ee_tracking_click_debug'));
                    if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_click_log')) {
                        $log = 'PRODUCT CLICK';
                        $log .= $this->config->get('module_ee_tracking_click_debug') ? ' / mode: debug' : ' / mode: release';
                        $log .= ' / product: ' . $product_fields['pr1nm'];
                        $log .= ' / list: ' . $info['list'] . $response;
                        if ($this->config->get('module_ee_tracking_extended_log')) {
                            $log .= "\n" . implode(', ', $fields);
                        }
                        $this->addLog($log);
                        return $log;
                    }
                }
            }
        }
    }

    public function quickAddToCart($data) {
        $client_id = $this->getClientId();
        if ($client_id && $data) {
            $this->load->language('extension/module/ee_tracking');
            $fields = $this->getGlobalFields();
            if (isset($data['url']) && isset($data['title'])) {
                $fields['dl'] = $data['url'];
                $fields['dt'] = $data['title'];
            }
            $fields['cid'] = $client_id;
            $fields['ea'] = 'Add To Cart';
            $fields['pa'] = 'add';
            $settings = $this->getSettings();
            $fields['cu'] = $settings['currency'];
            if (isset($data['type'])) {
                $fields['pal'] = $this->language->get('text_' . $data['type']);
            } else {
                $info = $this->getDataByUrl($data['url'], $data['product_id'], $settings['compatibility']);
                $fields['pal'] = $info['list'];
                if (!isset($data['position']) && isset($info['position'])) {
                    $data['position'] = $info['position'];
                }
            }
            if (!isset($data['position'])) {
                $data['position'] = '';
            }
            $product_fields = $this->getAdvProducts(array($data['product_id']), $settings, 'pr', $data, $data['position']);
            $fields = array_merge($fields, $product_fields);
            $response = $this->setCurlRequest($fields, $this->config->get('module_ee_tracking_cart_debug'));
            if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_cart_log')) {
                $log = 'ADD TO CART';
                $log .= $this->config->get('module_ee_tracking_cart_debug') ? ' / mode: debug' : ' / mode: release';
                $log .= ' / product: ' . $product_fields['pr1nm'] . $response;
                if ($this->config->get('module_ee_tracking_extended_log')) {
                    $log .= "\n" . implode(', ', $fields);
                }
                $this->addLog($log);
                return $log;
            }
        }
    }

    public function addToCart($data) {
        $client_id = $this->getClientId();
        if ($client_id && $data) {
            $this->load->language('extension/module/ee_tracking');
            $fields = $this->getGlobalFields();
            if (isset($data['url']) && isset($data['title'])) {
                $fields['dl'] = $data['url'];
                $fields['dt'] = $data['title'];
            }
            $fields['cid'] = $client_id;
            $fields['ea'] = 'Add To Cart';
            $fields['pa'] = 'add';
            $settings = $this->getSettings();
            $fields['cu'] = $settings['currency'];
            if (isset($data['type'])) {
                $fields['pal'] = $this->language->get('text_' . $data['type']);
            }
            $product_fields = $this->getAdvProducts(array($data['product_id']), $settings, 'pr', $data, '');
            $fields = array_merge($fields, $product_fields);
            $response = $this->setCurlRequest($fields, $this->config->get('module_ee_tracking_cart_debug'));
            if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_cart_log')) {
                $log = 'ADD TO CART';
                $log .= $this->config->get('module_ee_tracking_cart_debug') ? ' / mode: debug' : ' / mode: release';
                $log .= ' / product: ' . $product_fields['pr1nm'];
                $log .= $response;
                if ($this->config->get('module_ee_tracking_extended_log')) {
                    $log .= "\n" . implode(', ', $fields);
                }
                $this->addLog($log);
                return $log;
            }
        }
    }

    public function clickAddToCart($data) {
        $client_id = $this->getClientId();
        if ($client_id && $data) {
            $this->load->language('extension/module/ee_tracking');
            $fields = $this->getGlobalFields();
            if (isset($data['url']) && isset($data['title'])) {
                $fields['dl'] = $data['url'];
                $fields['dt'] = $data['title'];
            }
            $fields['cid'] = $client_id;
            $fields['ea'] = 'Product Click';
            $fields['pa'] = 'click';
            $settings = $this->getSettings();
            if ($settings['compatibility']) {
                $this->addProductClick($data['product_id'], $fields['cid']);
            }
            if (isset($data['type'])) {
                $fields['pal'] = $this->language->get('text_' . $data['type']);
            } else {
                $info = $this->getDataByUrl($data['url'], $data['product_id'], $settings['compatibility']);
                $fields['pal'] = $info['list'];
                if (!isset($data['position']) && isset($info['position'])) {
                    $data['position'] = $info['position'];
                }
            }
            if (!isset($data['position'])) {
                $data['position'] = '';
            }
            $product_fields = $this->getProducts(array($data['product_id']), $settings, 'pr', $data['position']);
            $fields = array_merge($fields, $product_fields);
            $response = $this->setCurlRequest($fields, $this->config->get('module_ee_tracking_click_debug'));
            if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_click_log')) {
                $log = 'PRODUCT CLICK';
                $log .= $this->config->get('module_ee_tracking_click_debug') ? ' / mode: debug' : ' / mode: release';
                $log .= ' / product: ' . $product_fields['pr1nm'];
                $log .= ' / list: ' . $fields['pal'] . $response;
                if ($this->config->get('module_ee_tracking_extended_log')) {
                    $log .= "\n" . implode(', ', $fields);
                }
                $this->addLog($log);
                return $log;
            }
        }
    }

    public function removeFromCart($data) {
        $client_id = $this->getClientId();
        if ($client_id) {
            $fields = $this->getGlobalFields();
            if (isset($data['url'])) {
                $fields['dl'] = $data['url'];
            }
            $fields['cid'] = $client_id;
            $fields['ea'] = 'Remove From Cart';
            $fields['pa'] = 'remove';
            $settings = $this->getSettings();
            $fields['cu'] = $settings['currency'];
            $product_fields = $this->getAdvProducts(array($data['product_id']), $settings, 'pr', $data, '');
            $fields = array_merge($fields, $product_fields);
            $response = $this->setCurlRequest($fields, $this->config->get('module_ee_tracking_cart_debug'));
            if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_cart_log')) {
                $log = 'REMOVE FROM CART';
                $log .= $this->config->get('module_ee_tracking_cart_debug') ? ' / mode: debug' : ' / mode: release';
                $log .= ' / product: ' . $product_fields['pr1nm'] . $response;
                if ($this->config->get('module_ee_tracking_extended_log')) {
                    $log .= "\n" . implode(', ', $fields);
                }
                $this->addLog($log);
                return $log;
            }
        }
    }

    public function checkout($data) {
        $client_id = $this->getClientId();
        if ($client_id && $data) {
            $fields = $this->getGlobalFields();
            if (isset($data['url']) && isset($data['title'])) {
                $fields['dl'] = $data['url'];
                $fields['dt'] = $data['title'];
            }
            $fields['cid'] = $client_id;
            $fields['ea'] = 'Checkout Step';
            $fields['pa'] = 'checkout';
            $settings = $this->getSettings();
            $fields['cu'] = $settings['currency'];
            $cart_products = $this->cart->getProducts();
            if ($cart_products && $this->config->get('module_ee_tracking_checkout_status')) {
                if ($data['step_option'] == 'custom checkout') {
                    $status = true;
                    foreach ($this->config->get('module_ee_tracking_checkout_url') as $key => $value) {
                        if (strpos($data['url'], $value) !== false && $this->config->get('config_language_id') == $key) {
                            $status = false;
                        }
                    }
                    if (!$this->config->get('module_ee_tracking_checkout_custom') || $status) {
                        return false;
                    }
                }
                foreach ($cart_products as $key => $cart_product) {
                    if ($cart_product['option']) {
                        $product_options = array();
                        foreach ($cart_product['option'] as $option) {
                            $product_options[$option['product_option_id']] = $option['product_option_value_id'];
                        }
                        $cart_products[$key]['option'] = $product_options;
                    }
                }
                $product_fields = $this->getCartProducts($cart_products, $settings, 'pr');
                $fields = array_merge($fields, $product_fields);
                $fields['cos'] = (int)$data['step'];
                $fields['col'] = $data['step_option'];
                $response = $this->setCurlRequest($fields, $this->config->get('module_ee_tracking_checkout_debug'));
                if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_checkout_log')) {
                    $log = 'CHECKOUT';
                    $log .= $this->config->get('module_ee_tracking_checkout_debug') ? ' / mode: debug' : ' / mode: release';
                    $log .= ' / step: ' . (int)$fields['cos'];
                    $log .= ' / option: ' . html_entity_decode($fields['col'], ENT_QUOTES, 'UTF-8') . $response;
                    if ($this->config->get('module_ee_tracking_extended_log')) {
                        $log .= "\n" . implode(', ', $fields);
                    }
                    $this->addLog($log);
                    return $log;
                }
            }
        }
    }

    public function checkoutOption($data) {
        $client_id = $this->getClientId();
        if ($client_id) {
            $fields = $this->getGlobalFields();
            if (isset($data['url'])) {
                $fields['dl'] = $data['url'];
            }
            $fields['cid'] = $client_id;
            $fields['ea'] = 'Checkout Option';
            $fields['pa'] = 'checkout_option';
            $fields['cos'] = (int)$data['step'];
            $fields['col'] = $data['step_option'];
            $response = $this->setCurlRequest($fields, $this->config->get('module_ee_tracking_checkout_debug'));
            if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_checkout_log')) {
                $log = 'CHECKOUT OPTION';
                $log .= $this->config->get('module_ee_tracking_checkout_debug') ? ' / mode: debug' : ' / mode: release';
                $log .= ' / step: ' . (int)$fields['cos'];
                $log .= ' / option: ' . html_entity_decode($fields['col'], ENT_QUOTES, 'UTF-8') . $response;
                if ($this->config->get('module_ee_tracking_extended_log')) {
                    $log .= "\n" . implode(', ', $fields);
                }
                $this->addLog($log);
                return $log;
            }
        }
    }

    public function transaction($order_id, $order_status_id) {
        if ($this->config->get('module_ee_tracking_admin_tracking') &&  isset($this->session->data['user_id']) && $this->session->data['user_id']) {
            return false;
        }
        $fields = array();
        $order_data = $this->getOrder($order_id);
        if ($order_data) {
            $settings = $this->getSettingsByStore($order_data);
            $fields['v'] = 1;
            $fields['tid'] = $settings['tracking_id'];
            $fields['uip'] = $order_data['forwarded_ip'] ? $order_data['forwarded_ip'] : $order_data['ip'];
            $fields['ua'] = $order_data['user_agent'];
            $fields['t'] = 'event';
            $fields['ec'] = 'Enhanced Ecommerce';
            if ($settings['status'] && $settings['transaction_status'] && in_array($order_status_id, $settings['order_status'])) {
                $client_id = $this->getOrderToClient($order_id, $settings['transaction_debug']);
                if ($client_id) {
                    $fields['cid'] = $client_id;
                    $fields['ea'] = 'Transaction';
                    if (isset($this->request->server['REQUEST_URI']) && strpos($this->request->server['REQUEST_URI'], 'api/order/history') !== false) {
                        $query = $this->db->query("SELECT NOW() as time");
                        if (isset($query->row['time'])) {
                            $time_now = strtotime($query->row['time']);
                        } else {
                            $time_now = time();
                        }
                        $queue_time = $time_now - strtotime($order_data['date_added']);
                        if ($queue_time > 0 && $queue_time < 14100) {
                            $fields['qt'] = $queue_time;
                        } else {
                            $fields['ni'] = 1;
                        }
                    }
                    $totals = $this->getOrderTotals($order_id);
                    $revenue = 0;
                    $shipping = 0;
                    $tax = 0;
                    $coupon = '';
                    if ($totals) {
                        foreach ($totals as $item) {
                            if ($item['code'] == 'shipping') {
                                $shipping += $item['value'];
                            }
                            if ($item['code'] == 'tax') {
                                $tax += $item['value'];
                            }
                            if ($item['code'] == 'total') {
                                $revenue += $item['value'];
                            }
                            if ($item['code'] == 'coupon') {
                                $coupon = $item['title'];
                            }
                        }
                    }
                    if (!$settings['total_shipping']) {
                        $revenue = $revenue - $shipping;
                    }
                    if (!$settings['total_tax']) {
                        $revenue = $revenue - $tax;
                    }
                    $revenue = $this->currency->format($revenue, $settings['currency'], 0, false);
                    $shipping = $this->currency->format($shipping, $settings['currency'], 0, false);
                    $tax = $this->currency->format($tax, $settings['currency'], 0, false);
                    $fields['ti'] = $order_id;
                    $fields['ta'] = $settings['affiliation'];
                    $fields['cu'] = $settings['currency'];
                    $fields['tr'] = $revenue;
                    $fields['tt'] = $tax;
                    $fields['ts'] = $shipping;
                    if ($coupon) {
                        $fields['tcc'] = $coupon;
                    }
                    foreach ($settings['custom_dimension_order'] as $dimension) {
                        if (isset($order_data[$dimension['value']]) && $order_data[$dimension['value']]) {
                            $fields['cd' . $dimension['index']] = $order_data[$dimension['value']];
                        }
                    }
                    $fields['pa'] = 'purchase';
                    $order_products = $this->getOrderProducts($order_id);
                    foreach ($order_products as $key => $product) {
                        $order_options = $this->getOrderOptions($product['order_id'], $product['order_product_id']);
                        $product_options = array();
                        foreach ($order_options as $order_option) {
                            $product_options[$order_option['product_option_id']] = $order_option['product_option_value_id'];
                        }
                        $order_products[$key]['option'] = $product_options;
                    }
                    $product_fields = $this->getCartProducts($order_products, $settings, 'pr');
                    $fields = array_merge($fields, $product_fields);
                    $response = $this->setCurlRequest($fields, $settings['transaction_debug']);
                    if ($settings['log'] && $settings['transaction_log']) {
                        $log = 'TRANSACTION';
                        $log .= $settings['transaction_debug'] ? ' / mode: debug' : ' / mode: release';
                        $log .= ' / order: ' . (int)$order_id . $response;
                        if ($settings['extended_log']) {
                            $log .= "\n" . implode(', ', $fields);
                        }

                        $this->addStoreLog($log, $settings['store_id']);
                        return $log;
                    }

                }
            } elseif ($settings['status'] && $settings['refund_status'] && in_array($order_status_id, $settings['refund_order_status'])) {
                $client_id = $this->getOrderToClient($order_id, $settings['refund_debug'], true);
                if ($client_id) {
                    $fields['cid'] = $client_id;
                    $fields['ea'] = 'Refund';
                    $fields['ni'] = 1;
                    $fields['ti'] = $order_id;
                    $fields['pa'] = 'refund';
                    $response = $this->setCurlRequest($fields, $settings['refund_debug']);
                    if ($settings['log'] && $settings['refund_log']) {
                        $log = 'REFUND';
                        $log .= $settings['refund_debug'] ? ' / mode: debug' : ' / mode: release';
                        $log .= ' / order: ' . (int)$order_id . $response;
                        if ($settings['extended_log']) {
                            $log .= "\n" . implode(', ', $fields);
                        }
                        $this->addStoreLog($log, $settings['store_id']);
                        return $log;
                    }
                }
            }
        }
    }
    
    public function refund($data) {
        if ($this->config->get('module_ee_tracking_admin_tracking') &&  isset($this->session->data['user_id']) && $this->session->data['user_id']) {
            return false;
        }
        if (isset($data['order_id'])) {
            $fields = $this->getGlobalFields();
            $client_id = $this->getOrderToClient($data['order_id'], $this->config->get('module_ee_tracking_refund_debug'), true);
            if ($client_id) {
                $fields['dl'] = html_entity_decode($this->url->link('account/return/add', '', true), ENT_QUOTES, 'UTF-8');
                $fields['cid'] = $client_id;
                $fields['ea'] = 'Refund';
                $fields['ti'] = $data['order_id'];
                $fields['pa'] = 'refund';
                if (isset($data['product_id'])) {
                    $fields['pr1id'] = $data['product_id'];
                }
                if (isset($data['quantity'])) {
                    $fields['pr1qt'] = $data['quantity'];
                }
                $response = $this->setCurlRequest($fields, $this->config->get('module_ee_tracking_refund_debug'));
                if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_refund_log')) {
                    $log = 'REFUND';
                    $log .= $this->config->get('module_ee_tracking_refund_debug') ? ' / mode: debug' : ' / mode: release';
                    $log .= ' / order: ' . (int)$data['order_id'] . $response;
                    if ($this->config->get('module_ee_tracking_extended_log')) {
                        $log .= "\n" . implode(', ', $fields);
                    }
                    $this->addLog($log);
                    return $log;
                }
            }
        }
    }
    
    public function promotion($data) {
        $client_id = $this->getClientId();
        if ($client_id) {
            $fields = $this->getGlobalFields();
            if (isset($data['url']) && isset($data['title'])) {
                $fields['dl'] = $data['url'];
                $fields['dt'] = $data['title'];
            }
            $fields['cid'] = $client_id;
            $fields['ea'] = 'Internal Promotion View';
            $settings = $this->getSettings();
            $banner_info = $this->getBanner($data['banner_id']);
            $banners = $this->getBannerImages($data['banner_id'], $settings['language_id']);
            if ($banner_info && $banners) {
                $index = 1;
                foreach ($banners as $banner) {
                    $fields['promo' . $index . 'id'] = 'PROMO_' . $banner['banner_id'] . '_' . $banner["banner_image_id"];
                    $fields['promo' . $index . 'nm'] = $banner_info['name'];
                    $fields['promo' . $index . 'cr'] = $banner['title'];
                    $fields['promo' . $index . 'ps'] = 'banner_slot' . $index;
                    $index++;
                }
                $response = $this->setCurlRequest($fields, $this->config->get('module_ee_tracking_promotion_debug'));
                if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_promotion_log')) {
                    $log = 'PROMOTION IMPRESSION';
                    $log .= $this->config->get('module_ee_tracking_promotion_debug') ? ' / mode: debug' : ' / mode: release';
                    $log .= ' / name: ' . $banner_info['name'] . $response;
                    if ($this->config->get('module_ee_tracking_extended_log')) {
                        $log .= "\n" . implode(', ', $fields);
                    }
                    $this->addLog($log);
                    return $log;
                }
            }
        }
    }

    public function promotionClick($data) {
        $client_id = $this->getClientId();
        if ($client_id) {
            $fields = $this->getGlobalFields();
            if (isset($data['url']) && isset($data['title'])) {
                $fields['dl'] = $data['url'];
                $fields['dt'] = $data['title'];
            }
            $fields['cid'] = $client_id;
            $fields['ea'] = 'Internal Promotion Click';
            $settings = $this->getSettings();
            $banner_info = $this->getBanner($data['banner_id']);
            $banners = $this->getBannerImages($data['banner_id'], $settings['language_id']);
            if ($banner_info && $banners) {
                $index = 1;
                foreach ($banners as $banner) {
                    if ($index == (int)$data['position']) {
                        if ($this->config->get('module_ee_tracking_promotion_debug')) {
                            $fields['promo1a'] = 'click';
                        } else {
                            $fields['promoa'] = 'click';
                        }
                        $fields['promo1id'] = 'PROMO_' . $banner['banner_id'] . '_' . $banner["banner_image_id"];
                        $fields['promo1nm'] = $banner_info['name'];
                        $fields['promo1cr'] = $banner['title'];
                        $fields['promo1ps'] = 'banner_slot' . $index;
                    }
                    $index++;
                }
                $response = $this->setCurlRequest($fields, $this->config->get('module_ee_tracking_promotion_debug'));
                if ($this->config->get('module_ee_tracking_log') && $this->config->get('module_ee_tracking_promotion_log')) {
                    $log = 'PROMOTION CLICK';
                    $log .= $this->config->get('module_ee_tracking_promotion_debug') ? ' / mode: debug' : ' / mode: release';
                    $log .= ' / name: ' . $banner_info['name'] . $response;
                    if ($this->config->get('module_ee_tracking_extended_log')) {
                        $log .= "\n" . implode(', ', $fields);
                    }
                    $this->addLog($log);
                    return $log;
                }
            }
        }
    }

    protected function setCurlRequest($fields, $debug = 0) {
        foreach ($fields as $key => $value) {
            $fields[$key] = html_entity_decode(trim($value), ENT_QUOTES, 'UTF-8');
        }
        $fields_string = utf8_encode(http_build_query($fields, '', '&', PHP_QUERY_RFC3986));
        if ($debug) {
            $url = 'https://www.google-analytics.com/debug/collect';
        } else {
            $url = 'https://www.google-analytics.com/collect';
        }
        $curl = curl_init();
        if (isset($this->request->server['HTTP_USER_AGENT'])) {
            curl_setopt($curl, CURLOPT_USERAGENT, $this->request->server['HTTP_USER_AGENT']);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded'));
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($curl);
        curl_close($curl);

        if ($debug) {
            $response_array = json_decode($response, true);
            if (isset($response_array['hitParsingResult']) && isset($response_array['hitParsingResult'][0]['valid'])) {
                $debug_response = $response_array['hitParsingResult'][0];
                $log = ' / validation: ' . ($debug_response['valid'] ? 'TRUE' : 'FALSE');
                if (isset($debug_response['parserMessage'][0]) && $debug_response['parserMessage'][0]['messageType'] != 'INFO') {
                    foreach ($debug_response['parserMessage'][0] as $key => $item) {
                        $log .= ' / ' . $key . ': ' . $item;
                    }
                }
            } else {
                $log = ' / error: Validation Server does not return the required parameters';
            }
            /*$log .= "\n" . $response . "\n" . implode(',', $fields);*/
            return $log;
        }
    }
    
    protected function getDataByUrl($page_url, $product_id = 0, $position = 0) {
        $data = array('list' => '');
        $url_data = parse_url(html_entity_decode($page_url, ENT_QUOTES, 'UTF-8'));
        if (isset($url_data['host']) && isset($url_data['path']) && isset($this->request->server['HTTP_HOST']) && strpos($this->request->server['HTTP_HOST'], $url_data['host']) !== false || strpos(HTTP_SERVER, $url_data['host']) !== false) {
            $this->load->language('extension/module/ee_tracking');
            $query = array();
            if (isset($url_data['query'])) {
                parse_str($url_data['query'], $query);
            }
            if ($query && isset($query['route']) || isset($query['product_id'])) {
                if (isset($query['route'])) {
                    switch ($query['route']) {
                        case 'product/category': {
                            $data['list'] = $this->language->get('text_category');
                            if ($position && isset($query['path'])) {
                                $categories = explode('_', (string)$query['path']);
                                $category_id = end($categories);
                                $data['position'] = $this->getCategoryProductPosition($product_id, $category_id, $query);
                            }
                            break;
                        }
                        case 'product/search': {
                            $data['list'] = $this->language->get('text_search');
                            if ($position) {
                                $data['position'] = $this->getSearchProductPosition($product_id, $query);
                            }
                            break;
                        }
                        case 'product/manufacturer/info': {
                            $data['list'] = $this->language->get('text_manufacturer');
                            if ($position && isset($query['manufacturer_id'])) {
                                $data['position'] = $this->getManufacturerProductPosition($product_id, $query['manufacturer_id'], $query);
                            }
                            break;
                        }
                        case 'product/special': {
                            $data['list'] = $this->language->get('text_special');
                            if ($position) {
                                $data['position'] = $this->getSpecialProductPosition($product_id, $query);
                            }
                            break;
                        }
                        case 'product/product': {
                            if (isset($query['product_id']) && $product_id != $query['product_id']) {
                                $data['list'] = $this->language->get('text_related');
                                if ($position) {
                                    $data['position'] = $this->getRelatedProductPosition($product_id, $query['product_id']);
                                }
                            }
                            break;
                        }
                        case 'product/compare': {
                            $data['list'] = $this->language->get('text_compare');
                            if ($position) {
                                $data['position'] = $this->getCompareProductPosition($product_id);
                            }
                            break;
                        }
                        case 'information/information': {
                            $data['list'] = $this->language->get('text_information');
                            break;
                        }
                        case 'common/home': {
                            $data['list'] = $this->language->get('text_home');
                            break;
                        }
                        case 'checkout/cart': {
                            $data['list'] = $this->language->get('text_cart');
                            break;
                        }
                    }
                } elseif (isset($query['product_id'])) {
                    if ($product_id != $query['product_id']) {
                        $data['list'] = $this->language->get('text_related');
                        if ($position) {
                            $data['position'] = $this->getRelatedProductPosition($product_id, $query['product_id']);
                        }
                    }
                }
            } elseif ($url_data['path'] == '/') {
                $data['list'] = $this->language->get('text_home');
            } else {
                $parts = explode('/', trim($url_data['path'], '/'));
                if (utf8_strlen(end($parts)) == 0) {
                    array_pop($parts);
                }
                if (strpos(end($parts), 'page=') !== false || strpos(end($parts), 'page-') !== false) {
                    $keyword = prev($parts);
                } else {
                    $keyword = end($parts);
                }
                $db_query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($keyword) . "'");
                if ($db_query->num_rows) {
                    $url = explode('=', $db_query->row['query']);
                    $query_data = $this->getQueryData($product_id, $url, $position, $query);
                    if (!$query_data['list'] && isset($url[1])) {
                        $url[0] = $url[1];
                        $url[1] = '';
                        $query_data = $this->getQueryData($product_id, $url, $position, $query);
                    }
                    $data = $query_data;
                }
            }
        }
        return $data;
    }

    protected function getQueryData($product_id, $url, $position, $query) {
        $this->load->language('extension/module/ee_tracking');
        $data = array('list' => '');
        switch ($url[0]) {
            case 'category_id': {
                $data['list'] = $this->language->get('text_category');
                if ($position) {
                    $data['position'] = $this->getCategoryProductPosition($product_id, $url[1], $query);
                }
                break;
            }
            case 'product/search': {
                $data['list'] = $this->language->get('text_search');
                if ($position) {
                    $data['position'] = $this->getSearchProductPosition($product_id, $query);
                }
                break;
            }
            case 'manufacturer_id': {
                $data['list'] = $this->language->get('text_manufacturer');
                if ($position) {
                    $data['position'] = $this->getManufacturerProductPosition($product_id, $url[1], $query);
                }
                break;
            }
            case 'product/special': {
                $data['list'] = $this->language->get('text_special');
                if ($position) {
                    $data['position'] = $this->getSpecialProductPosition($product_id, $query);
                }
                break;
            }
            case 'product_id': {
                $data['list'] = $this->language->get('text_related');
                if ($position) {
                    $data['position'] = $this->getRelatedProductPosition($product_id, $url[1]);
                }
                break;
            }
            case 'product/compare': {
                $data['list'] = $this->language->get('text_compare');
                if ($position) {
                    $data['position'] = $this->getCompareProductPosition($product_id);
                }
                break;
            }
            case 'information_id': {
                $data['list'] = $this->language->get('text_information');
                break;
            }
            case 'common/home': {
                $data['list'] = $this->language->get('text_home');
                break;
            }
            case 'checkout/cart': {
                $data['list'] = $this->language->get('text_cart');
                break;
            }
        }
        return $data;
    }

    public function getProduct($product_id, $language_id) {
        $query = $this->db->query("SELECT DISTINCT p.product_id, p.sku, p.model, pd.name AS name, pd.meta_title AS meta_title, m.name AS manufacturer FROM " . DB_PREFIX . "product p 
        LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
        LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) 
        WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$language_id . "'");
        return  $query->row;
    }

    public function getAdvProduct($product_id, $language_id) {
        $query = $this->db->query("SELECT DISTINCT p.*, pd.*, m.name AS manufacturer, 
        (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, 
        (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, 
        (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$language_id . "') AS stock_status, 
        (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$language_id . "') AS weight_class, 
        (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$language_id . "') AS length_class, 
        (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, 
        (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews 
        FROM " . DB_PREFIX . "product p 
        LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
        LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) 
        WHERE p.product_id = '" . (int)$product_id . "' 
        AND pd.language_id = '" . (int)$language_id . "'");
        if ($query->num_rows) {
            $query->row['price'] = $query->row['discount'] ? $query->row['discount'] : $query->row['price'];
            $query->row['rating'] = round($query->row['rating']);
            $query->row['reviews'] = $query->row['reviews'] ? $query->row['reviews'] : 0;
            $query->row['store_id'] = $this->config->get('config_store_id') ? $this->config->get('config_store_id') : 'default';
            return  $query->row;
        } else {
            return false;
        }
    }

    public function getProductDescription($product_id, $language_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "' AND language_id = '" . (int)$language_id . "'");
        return  $query->row;
    }

    protected function getProducts($items, $settings, $prefix, $position = 1) {
        $fields = array();
        $index = 1;
        if ($settings['custom_dimension_product']) {
            foreach ($items as $product_id) {
                $product = $this->getAdvProduct($product_id, $settings['language_id']);
                $category = $this->getProductCategoryName($product_id, $settings['language_id'], $settings['product_category']);
                if ($product) {
                    $fields[$prefix . $index . 'id'] = $product[$settings['product_id']] ? $product[$settings['product_id']] : $product_id;
                    $fields[$prefix . $index . 'nm'] = $product['name'];
                    $fields[$prefix . $index . 'ca'] = $category;
                    $fields[$prefix . $index . 'br'] = $product['manufacturer'];
                    $fields[$prefix . $index . 'ps'] = $position++;
                    foreach ($settings['custom_dimension_product'] as $dimension) {
                        if (isset($product[$dimension['value']]) && $product[$dimension['value']]) {
                            $fields[$prefix . $index . 'cd' . $dimension['index']] = $product[$dimension['value']];
                        }
                    }
                    $index++;
                }
            }
        } else {
            foreach ($items as $product_id) {
                $product = $this->getProduct($product_id, $settings['language_id']);
                $category = $this->getProductCategoryName($product_id, $settings['language_id'], $settings['product_category']);
                if ($product) {
                    $fields[$prefix . $index . 'id'] = $product[$settings['product_id']] ? $product[$settings['product_id']] : $product_id;
                    $fields[$prefix . $index . 'nm'] = $product['name'];
                    $fields[$prefix . $index . 'ca'] = $category;
                    $fields[$prefix . $index . 'br'] = $product['manufacturer'];
                    $fields[$prefix . $index . 'ps'] = $position++;
                    $index++;
                }
            }
        }
        return $fields;
    }

    protected function getAdvProducts($items, $settings, $prefix, $data, $position = 1) {
        $fields = array();
        $index = 1;
        foreach ($items as $product_id) {
            $product = $this->getAdvProduct($product_id, $settings['language_id']);
            $category = $this->getProductCategoryName($product_id, $settings['language_id'], $settings['product_category']);
            if ($product) {
                if ((float)$product['special']) {
                    if ($settings['tax']) {
                        $product['special'] = $this->tax->calculate($product['special'], $product['tax_class_id']);
                    }
                    $product['price'] = $this->currency->format($product['special'], $settings['currency'], 0, false);
                } else {
                    if ($settings['tax']) {
                        $product['price'] = $this->tax->calculate($product['price'], $product['tax_class_id']);
                    }
                    $product['price'] = $this->currency->format($product['price'], $settings['currency'], 0, false);
                }
                $product['option'] = '';
                if (isset($data['option'])) {
                    $product_options = $this->getProductOptions($product_id, $settings['language_id']);
                    $options = array();
                    $option_price = 0;
                    foreach ($product_options as $product_option) {
                        if (isset($product_option['product_option_value']) && is_array($product_option['product_option_value'])) {
                            foreach ($product_option['product_option_value'] as $option_value) {
                                if (isset($data['option'][$product_option['product_option_id']])) {
                                    if (($option_value['product_option_value_id'] == $data['option'][$product_option['product_option_id']]) || is_array($data['option'][$product_option['product_option_id']]) && in_array($option_value['product_option_value_id'], $data['option'][$product_option['product_option_id']])) {
                                        if (isset($options[$product_option['product_option_id']])) {
                                            $options[$product_option['product_option_id']] .= ',' . $option_value['name'];
                                        } else {
                                            $options[$product_option['product_option_id']] = $product_option['name'] . ':' . $option_value['name'];
                                        }
                                        if ($option_value['price_prefix'] == '+') {
                                            $option_price = $option_price + $option_value['price'];
                                        } else if ($option_value['price_prefix'] == '-') {
                                            $option_price = $option_price - $option_value['price'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($options) {
                        $product['option'] = implode("/", $options);
                        if ($settings['tax']) {
                            $option_price = $this->tax->calculate($option_price, $product['tax_class_id']);
                        }
                        $option_price = $this->currency->format($option_price, $settings['currency'], 0, false);
                        $product['price'] = (float)$product['price'] + (float)$option_price;
                    }
                }
                $fields[$prefix . $index . 'id'] = $product[$settings['product_id']] ? $product[$settings['product_id']] : $product_id;
                $fields[$prefix . $index . 'nm'] = $product['name'];
                $fields[$prefix . $index . 'ca'] = $category;
                $fields[$prefix . $index . 'br'] = $product['manufacturer'];
                $fields[$prefix . $index . 'va'] = $product['option'];
                $fields[$prefix . $index . 'pr'] = $product['price'];
                $fields[$prefix . $index . 'qt'] = isset($data['quantity']) ? (int)$data['quantity'] : 1;
                $fields[$prefix . $index . 'ps'] = $position++;
                foreach ($settings['custom_dimension_product'] as $dimension) {
                    if (isset($product[$dimension['value']]) && $product[$dimension['value']]) {
                        $fields[$prefix . $index . 'cd' . $dimension['index']] = $product[$dimension['value']];
                    }
                }
                $index++;
            }
        }
        return $fields;
    }

    protected function getCartProducts($items, $settings, $prefix, $position = 1) {
        $fields = array();
        $index = 1;
        foreach ($items as $item) {
            $product = $this->getAdvProduct($item['product_id'], $settings['language_id']);
            $category = $this->getProductCategoryName($item['product_id'], $settings['language_id'], $settings['product_category']);
            if ($product) {
                if ($settings['tax']) {
                    if (isset($item['tax'])) {
                        $item['price'] = $item['price'] + $item['tax'];
                    } elseif (isset($item['tax_class_id'])) {
                        $item['price'] = $this->tax->calculate($item['price'], $item['tax_class_id']);
                    }
                }
                $product['price'] = $this->currency->format($item['price'], $settings['currency'], 0, false);

                $product['option'] = '';
                if (isset($item['option'])) {
                    $product_options = $this->getProductOptions($item['product_id'], $settings['language_id']);
                    $options = array();
                    foreach ($product_options as $product_option) {
                        if (isset($product_option['product_option_value']) && is_array($product_option['product_option_value'])) {
                            foreach ($product_option['product_option_value'] as $option_value) {
                                if (isset($item['option'][$product_option['product_option_id']])) {
                                    if (($option_value['product_option_value_id'] == $item['option'][$product_option['product_option_id']]) || is_array($item['option'][$product_option['product_option_id']]) && in_array($option_value['product_option_value_id'], $item['option'][$product_option['product_option_id']])) {
                                        if (isset($options[$product_option['product_option_id']])) {
                                            $options[$product_option['product_option_id']] .= ',' . $option_value['name'];
                                        } else {
                                            $options[$product_option['product_option_id']] = $product_option['name'] . ':' . $option_value['name'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($options) {
                        $product['option'] = implode("/", $options);
                    }
                }
                $fields[$prefix . $index . 'id'] = $product[$settings['product_id']] ? $product[$settings['product_id']] : $item['product_id'];
                $fields[$prefix . $index . 'nm'] = $product['name'];
                $fields[$prefix . $index . 'ca'] = $category;
                $fields[$prefix . $index . 'br'] = $product['manufacturer'];
                $fields[$prefix . $index . 'va'] = $product['option'];
                $fields[$prefix . $index . 'pr'] = $product['price'];
                $fields[$prefix . $index . 'qt'] = $item['quantity'];
                $fields[$prefix . $index . 'ps'] = $position++;
                foreach ($settings['custom_dimension_product'] as $dimension) {
                    if (isset($product[$dimension['value']]) && $product[$dimension['value']]) {
                        $fields[$prefix . $index . 'cd' . $dimension['index']] = $product[$dimension['value']];
                    }
                }
                $index++;
            }
        }
        return $fields;
    }

    public function getProductOptions($product_id, $language_id) {
        $product_option_data = array();
        $product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option po 
        LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) 
        LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) 
        WHERE po.product_id = '" . (int)$product_id . "' 
        AND od.language_id = '" . (int)$language_id . "'
        AND o.type IN ('select', 'radio', 'checkbox', 'image') 
        ORDER BY o.sort_order");
        foreach ($product_option_query->rows as $product_option) {
            $product_option_value_data = array();
            $product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov 
            LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) 
            LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) 
            WHERE pov.product_id = '" . (int)$product_id . "' 
            AND pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' 
            AND ovd.language_id = '" . (int)$language_id . "'
            ORDER BY ov.sort_order");
            foreach ($product_option_value_query->rows as $product_option_value) {
                $product_option_value_data[] = array(
                    'product_option_value_id' => $product_option_value['product_option_value_id'],
                    'option_value_id' => $product_option_value['option_value_id'],
                    'name' => $product_option_value['name'],
                    'price' => $product_option_value['price'],
                    'price_prefix' => $product_option_value['price_prefix'],
                );
            }
            $product_option_data[] = array(
                'product_option_id' => $product_option['product_option_id'],
                'product_option_value' => $product_option_value_data,
                'option_id' => $product_option['option_id'],
                'name' => $product_option['name']
            );
        }
        return $product_option_data;
    }

    public function getProductRequiredOptions($product_id, $language_id) {
        $product_option_data = array();
        $product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option po 
        LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) 
        LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) 
        WHERE po.product_id = '" . (int)$product_id . "' 
        AND od.language_id = '" . (int)$language_id . "' 
        ORDER BY o.sort_order");
        foreach ($product_option_query->rows as $product_option) {
            $product_option_data[] = array(
                'product_option_id' => $product_option['product_option_id'],
                'required' => $product_option['required']
            );
        }
        return $product_option_data;
    }

    public function getProductCategoryName($product_id, $language_id, $type = 0) {
        $path = '';
        if (!$type) {
            $query = $this->db->query("SELECT DISTINCT cd.name FROM " . DB_PREFIX . "product_to_category pc 
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (pc.category_id = cd.category_id) 
            WHERE pc.product_id = '" . (int)$product_id . "' AND cd.language_id = '" . (int)$language_id . "' LIMIT 1");

            if ($query->num_rows) {
                $path = $query->row['name'];
            }
        } elseif ($type == 1) {
            $query = $this->db->query("SELECT DISTINCT
            (SELECT GROUP_CONCAT(cd1.name ORDER BY level SEPARATOR '/') FROM " . DB_PREFIX . "category_path cp
            LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id)
            WHERE cp.category_id = c.category_id AND cd1.language_id = '" . (int)$language_id . "'
            GROUP BY cp.category_id) AS path FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (c.category_id = cd2.category_id)
            WHERE c.category_id = (SELECT pc.category_id FROM " . DB_PREFIX . "product_to_category pc 
            LEFT JOIN " . DB_PREFIX . "category_path cp3 ON (pc.category_id = cp3.category_id) 
            WHERE pc.product_id = '" . (int)$product_id . "' AND cp3.category_id = cp3.path_id ORDER BY cp3.level DESC LIMIT 1)
            AND cd2.language_id = '" . (int)$language_id . "'");

            if ($query->num_rows) {
                $path = $query->row['path'];
            }
        } elseif ($type == 2) {
            $query = $this->db->query("SELECT DISTINCT
            (SELECT GROUP_CONCAT(cd1.name ORDER BY level SEPARATOR '/') FROM " . DB_PREFIX . "category_path cp
            LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id)
            WHERE cp.category_id = c.category_id AND cd1.language_id = '" . (int)$language_id . "'
            GROUP BY cp.category_id) AS path FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (c.category_id = cd2.category_id)
            WHERE c.category_id = (SELECT pc.category_id FROM " . DB_PREFIX . "product_to_category pc 
            LEFT JOIN " . DB_PREFIX . "category_path cp3 ON (pc.category_id = cp3.category_id) 
            WHERE pc.product_id = '" . (int)$product_id . "' AND cp3.category_id = cp3.path_id ORDER BY cp3.level ASC LIMIT 1)
            AND cd2.language_id = '" . (int)$language_id . "'");

            if ($query->num_rows) {
                $path = $query->row['path'];
            }
        } elseif ($type == 3) {
            $query = $this->db->query("SELECT DISTINCT             
            (SELECT GROUP_CONCAT(cd1.name ORDER BY level SEPARATOR '/') FROM " . DB_PREFIX . "category_path cp
            LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id)
            WHERE cp.category_id = c.category_id AND cd1.language_id = '" . (int)$language_id . "'
            GROUP BY cp.category_id) AS path FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (c.category_id = cd2.category_id)
            WHERE FIND_IN_SET(c.category_id, (SELECT GROUP_CONCAT(category_id) FROM " . DB_PREFIX . "product_to_category
            WHERE product_id = '" . (int)$product_id . "')) > 0
            AND cd2.language_id = '" . (int)$language_id . "'");

            if ($query->num_rows) {
                foreach ($query->rows as $key => $item) {
                    if (!$key) {
                        $path .= $item['path'];
                    } else {
                        $path .= ' | ' . $item['path'];
                    }
                }
            }
        }
        return $path;
    }

    protected function getCategoryProductPosition($product_id, $category_id, $query) {
        if (isset($query['limit'])) {
            $limit = (int)$query['limit'];
        } else {
            $limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
        }
        if (isset($query['page'])) {
            $page = (int)$query['page'];
        } else {
            $page = 1;
        }
        $filter_data = array(
            'filter_category_id' => $category_id,
            'filter_filter'      => isset($query['filter']) ? $query['filter'] : '',
            'sort'               => isset($query['sort']) ? $query['sort'] : 'p.sort_order',
            'order'              => isset($query['order']) ? $query['order'] : 'ASC',
            'start'              => ($page - 1) * $limit,
            'limit'              => $limit
        );
        $this->load->model('catalog/product');
        $results = $this->model_catalog_product->getProducts($filter_data);
        $position = ($page - 1) * $limit + 1;
        foreach ($results as $result) {
            if ($result['product_id'] == $product_id) {
                return $position;
            }
            $position++;
        }
    }

    protected function getManufacturerProductPosition($product_id, $manufacturer_id, $query) {
        if (isset($query['limit'])) {
            $limit = (int)$query['limit'];
        } else {
            $limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
        }
        if (isset($query['page'])) {
            $page = (int)$query['page'];
        } else {
            $page = 1;
        }
        $filter_data = array(
            'filter_manufacturer_id' => $manufacturer_id,
            'sort'                   => isset($query['sort']) ? $query['sort'] : 'p.sort_order',
            'order'                  => isset($query['order']) ? $query['order'] : 'ASC',
            'start'                  => ($page - 1) * $limit,
            'limit'                  => $limit
        );
        $this->load->model('catalog/product');
        $results = $this->model_catalog_product->getProducts($filter_data);
        $position = ($page - 1) * $limit + 1;
        foreach ($results as $result) {
            if ($result['product_id'] == $product_id) {
                return $position;
            }
            $position++;
        }
    }

    protected function getRelatedProductPosition($product_id, $refer_product_id) {
        $this->load->model('catalog/product');
        $results = $this->model_catalog_product->getProductRelated($refer_product_id);
        $position = 1;
        foreach ($results as $result) {
            if ($result['product_id'] == $product_id) {
                return $position;
            }
            $position++;
        }
    }

    protected function getSearchProductPosition($product_id, $query) {
        if (isset($query['search']) || isset($query['tag'])) {
            if (isset($query['limit'])) {
                $limit = (int)$query['limit'];
            } else {
                $limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
            }
            if (isset($query['page'])) {
                $page = (int)$query['page'];
            } else {
                $page = 1;
            }
            $filter_data = array(
                'filter_name'         => isset($query['search']) ? $query['search'] : '',
                'filter_tag'          => isset($query['tag']) ? $query['tag'] : '',
                'filter_description'  => isset($query['description']) ? $query['description'] : '',
                'filter_category_id'  => isset($query['category_id']) ? $query['category_id'] : '',
                'filter_sub_category' => isset($query['sub_category']) ? $query['sub_category'] : '',
                'sort'                => isset($query['sort']) ? $query['sort'] : 'p.sort_order',
                'order'               => isset($query['order']) ? $query['order'] : 'ASC',
                'start'               => ($page - 1) * $limit,
                'limit'               => $limit
            );
            $this->load->model('catalog/product');
            $results = $this->model_catalog_product->getProducts($filter_data);
            $position = ($page - 1) * $limit + 1;
            foreach ($results as $result) {
                if ($result['product_id'] == $product_id) {
                    return $position;
                }
                $position++;
            }
        }
    }

    protected function getSpecialProductPosition($product_id, $query) {
        if (isset($query['limit'])) {
            $limit = (int)$query['limit'];
        } else {
            $limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
        }
        if (isset($query['page'])) {
            $page = (int)$query['page'];
        } else {
            $page = 1;
        }
        $filter_data = array(
            'sort'  => isset($query['sort']) ? $query['sort'] : 'p.sort_order',
            'order' => isset($query['order']) ? $query['order'] : 'ASC',
            'start' => ($page - 1) * $limit,
            'limit' => $limit
        );
        $this->load->model('catalog/product');
        $results = $this->model_catalog_product->getProductSpecials($filter_data);
        $position = ($page - 1) * $limit + 1;
        foreach ($results as $result) {
            if ($result['product_id'] == $product_id) {
                return $position;
            }
            $position++;
        }
    }

    protected function getCompareProductPosition($product_id) {
        $position = 1;
        foreach ($this->session->data['compare'] as $key => $id) {
            if ($product_id == $id) {
                return $position;
            }
            $position++;
        }
    }

    public function getOrder($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");
        return $query->row;

    }

    protected function getOrderProducts($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
        return $query->rows;
    }

    protected function getOrderTotals($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order");
        return $query->rows;
    }

    protected function getOrderOptions($order_id, $order_product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");
        return $query->rows;
    }

    public function getCart($cart_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart_id . "'");
        return $query->row;
    }

    public function getProductByModel($model) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product WHERE model = '" . $this->db->escape($model) . "'");
        return $query->row;
    }

    public function getBanner($banner_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner WHERE banner_id = '" . (int)$banner_id . "' AND status = '1'");
        return $query->row;
    }

    public function getBannerImages($banner_id, $language_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner_image 
        WHERE banner_id = '" . (int)$banner_id . "' 
        AND language_id = '" . (int)$language_id . "' 
        ORDER BY sort_order ASC");
        return $query->rows;
    }

    protected function getSettings() {
        if ($this->config->get('module_ee_tracking_advanced_settings')) {
            if ($this->config->get('module_ee_tracking_language_id')) {
                $settings['language_id'] = $this->config->get('module_ee_tracking_language_id');
            } else {
                $settings['language_id'] = $this->config->get('config_language_id');
            }
            if ($this->config->get('module_ee_tracking_currency')) {
                $settings['currency'] = $this->config->get('module_ee_tracking_currency');
            } else {
                $settings['currency'] = isset($this->session->data['currency']) ? $this->session->data['currency'] : '';
            }
            if ($this->config->get('module_ee_tracking_affiliation')) {
                $settings['affiliation'] = $this->config->get('module_ee_tracking_affiliation');
            } else {
                $settings['affiliation'] = $this->config->get('config_name');
            }
            $settings['tax'] = $this->config->get('module_ee_tracking_tax');
            if ($this->config->get('module_ee_tracking_product_id')) {
                $settings['product_id'] = $this->config->get('module_ee_tracking_product_id');
            } else {
                $settings['product_id'] = 'product_id';
            }
            $settings['product_category'] = $this->config->get('module_ee_tracking_product_category');
            $settings['compatibility'] = $this->config->get('module_ee_tracking_compatibility');
        } else {
            $settings['language_id'] = $this->config->get('config_language_id');
            $settings['currency'] = isset($this->session->data['currency']) ? $this->session->data['currency'] : '';
            $settings['affiliation'] = $this->config->get('config_name');
            $settings['tax'] = 1;
            $settings['product_id'] = 'product_id';
            $settings['product_category'] = 0;
            $settings['compatibility'] = 0;
        }
        $settings['custom_dimension_product'] = array();
        $settings['custom_dimension_order'] = array();
        $settings['custom_dimension'] = $this->config->get('module_ee_tracking_custom_dimension');
        if ($settings['custom_dimension']) {
            foreach ($settings['custom_dimension'] as $custom_dimension) {
                if ($custom_dimension['object'] == 2) {
                    $settings['custom_dimension_order'][] = $custom_dimension;
                } else {
                    $settings['custom_dimension_product'][] = $custom_dimension;
                }
            }
        }
        return $settings;
    }

    protected function getSettingsByStore($data) {
        $settings = array('status' => false);
        if ($this->config->get('module_ee_tracking_multistore')) {
            $settings['store_id'] = $data['store_id'];
        } else {
            $settings['store_id'] = 0;
        }
        $this->load->model('setting/setting');
        $module_info = $this->model_setting_setting->getSetting('module_ee_tracking', $settings['store_id']);
        if ($module_info) {
            foreach ($module_info as $key => $item) {
                $settings[str_replace("module_ee_tracking_", "", $key)] = $item;
            }
            if ($settings['advanced_settings']) {
                if (!$settings['language_id']) {
                    $settings['language_id'] = $data['language_id'];
                }
                if (!$settings['currency']) {
                    $settings['currency'] = $data['currency_code'];
                }
                if (!$settings['affiliation']) {
                    $settings['affiliation'] = $data['store_name'];
                }
            } else {
                $settings['language_id'] = $data['language_id'];
                $settings['currency'] = $data['currency_code'];
                $settings['affiliation'] = $data['store_name'];
                $settings['tax'] = 1;
                $settings['product_id'] = 'product_id';
                $settings['product_category'] = 0;
                $settings['compatibility'] = 0;
            }
            $settings['custom_dimension_product'] = array();
            $settings['custom_dimension_order'] = array();
            foreach ($settings['custom_dimension'] as $custom_dimension) {
                if ($custom_dimension['object'] == 2) {
                    $settings['custom_dimension_order'][] = $custom_dimension;
                } else {
                    $settings['custom_dimension_product'][] = $custom_dimension;
                }
            }
        }
        return $settings;
    }

    public function getClientId() {
        if ($this->config->get('module_ee_tracking_bot_filter') && isset($this->request->server['HTTP_USER_AGENT']) && preg_match('/' . $this->config->get('module_ee_tracking_bot_filter') . '/i', $this->request->server['HTTP_USER_AGENT'])) {
            return false;
        }
        if ($this->config->get('module_ee_tracking_admin_tracking') &&  isset($this->session->data['user_id']) && $this->session->data['user_id']) {
            return false;
        }
        if ($this->config->get('module_ee_tracking_ip_filter')) {
            $ip_address = $this->getUserIP();
            $ip_blocked = preg_replace('~\r?\n~', "\n", $this->config->get('module_ee_tracking_ip_filter'));
            $ips = explode("\n", $ip_blocked);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if ($ip_address == $ip) {
                    return false;
                }
                $mask = str_replace('.*', '', $ip);
                if (strpos($ip_address, $mask) !== false) {
                    return false;
                }
            }
        }
        $client_id = '';
        if (isset($_COOKIE['_ga'])) {
            $cid_arr = explode('.', $_COOKIE['_ga']);
            if (isset($cid_arr['2']) && isset($cid_arr['3'])) {
                $client_id = $cid_arr['2'] . '.' . $cid_arr['3'];
            }
        } elseif (isset($_COOKIE['__utma'])) {
            $cid_arr = explode('.', $_COOKIE['__utma']);
            if (isset($cid_arr['1']) && isset($cid_arr['2'])) {
                $client_id = $cid_arr['1'] . '.' . $cid_arr['2'];
            }
        } elseif ($this->config->get('module_ee_tracking_advanced_settings') && $this->config->get('module_ee_tracking_generate_cid')) {
            if (isset($_COOKIE['_eecid'])) {
                $client_id = $_COOKIE['_eecid'];
            } else {
                $client_id = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                    mt_rand( 0, 0xffff ),
                    mt_rand( 0, 0x0fff ) | 0x4000,
                    mt_rand( 0, 0x3fff ) | 0x8000,
                    mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
                );
                setcookie('_eecid', $client_id, time() + 31536000, '/');
            }
        }
        return $client_id;
    }

    protected function addProductClick($product_id, $client_id) {
        $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "ee_click_to_client SET product_id = '" . (int)$product_id . "', client_id = '" . $this->db->escape($client_id) . "'");
    }

    protected function getProductClick($product_id, $client_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ee_click_to_client WHERE product_id = '" . (int)$product_id . "' AND client_id = '" . $this->db->escape($client_id) . "'");
        if ($query->num_rows) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "ee_click_to_client WHERE client_id = '" . $this->db->escape($client_id) . "'");
            return true;
        }
    }

    public function addOrderToClient($order_id, $status = false, $refund = false) {
        $client_id = '';
        if (isset($_COOKIE['_ga'])) {
            $cid_arr = explode('.', $_COOKIE['_ga']);
            if (isset($cid_arr['2']) && isset($cid_arr['3'])) {
                $client_id = $cid_arr['2'] . '.' . $cid_arr['3'];
            }
        } elseif (!$client_id && isset($_COOKIE['__utma'])) {
            $cid_arr = explode('.', $_COOKIE['__utma']);
            if (isset($cid_arr['1']) && isset($cid_arr['2'])) {
                $client_id = $cid_arr['1'] . '.' . $cid_arr['2'];
            }
        } elseif (!$client_id && isset($_COOKIE['_eecid'])) {
            $client_id = $_COOKIE['_eecid'];
        } else if (!$client_id) {
            $client_id = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                mt_rand( 0, 0xffff ),
                mt_rand( 0, 0x0fff ) | 0x4000,
                mt_rand( 0, 0x3fff ) | 0x8000,
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
            );
        }
        $query = "INSERT IGNORE INTO `" . DB_PREFIX . "ee_order_to_client` SET order_id = '" . (int)$order_id . "', client_id = '" . $this->db->escape($client_id) . "'";
        if ($status) {
            if ($refund) {
                $query .= ', sent = 2';
            } else {
                $query .= ', sent = 1';
            }
        }
        $this->db->query($query);
        return $client_id;
    }

    public function getOrderToClient($order_id, $debug = false, $refund = false) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ee_order_to_client` WHERE order_id = '" . (int)$order_id . "'");
        $client_id = '';
        if ($query->num_rows) {
            if ($debug) {
                $client_id = $query->row['client_id'];
            } else {
                if (!$query->row['sent'] && !$refund) {
                    $this->db->query("UPDATE " . DB_PREFIX . "ee_order_to_client SET sent = 1 WHERE order_id = '" . (int)$order_id . "'");
                    $client_id = $query->row['client_id'];
                } else if ($query->row['sent'] == 1 && $refund) {
                    $this->db->query("UPDATE " . DB_PREFIX . "ee_order_to_client SET sent = 2 WHERE order_id = '" . (int)$order_id . "'");
                    $client_id = $query->row['client_id'];
                }
            }
        } else {
            $client_id = $this->addOrderToClient($order_id, !$debug, $refund);
        }
        return $client_id;
    }

    public function addLog($data) {
        if ($data) {
            if ($this->config->get('module_ee_tracking_multistore')) {
                $store_id = $this->config->get('config_store_id');
            } else {
                $store_id = 0;
            }
            $log = new Log('module_ee_tracking' . $store_id . '.log');
            $log->write($data);
        }
    }

    public function addStoreLog($data, $store_id = 0) {
        if ($data) {
            $log = new Log('module_ee_tracking' . $store_id . '.log');
            $log->write($data);
        }
    }

    protected function getTableColumns($table_name) {
        $columns = $this->cache->get('ee_' . $table_name . '_columns');
        if (!$columns) {
            $query = $this->db->query("SELECT `COLUMN_NAME` as name, `COLUMN_TYPE` as type FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_NAME`='" . DB_PREFIX . $table_name . "' AND `TABLE_SCHEMA` = '" . DB_DATABASE . "'");
            $columns = $query->rows;
            $this->cache->set('ee_' . $table_name . '_columns', $columns);
        }
        return $columns;
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