<?php
class ModelModuleKlaviyo extends Model {

  public function getSettings($store_id = 0) {
    $data = array();

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = 'module_klaviyo'");

    foreach ($query->rows as $result) {
      $data[$result['key']] = $result['value'];
    }

    return $data;
  }



  //
  // FETCH ORDERS
  // ----------------------------------------

  public function getRecentOrders($data=array()) {
    $sql =  "SELECT o.order_id, ";
    $sql .= "(SELECT os.name ";
    $sql .= " FROM " . DB_PREFIX . "order_status os ";
    $sql .= " WHERE os.order_status_id = o.order_status_id ";
    $sql .= "   AND os.language_id = '" . (int) $this->config->get('config_language_id') . "'";
    $sql .= ") AS status ";
    $sql .= "FROM `" . DB_PREFIX . "order` o";

    if (isset($data['since']) && !is_null($data['since'])) {
      $sql .= " WHERE o.date_modified >= '" . $this->db->escape($data['since']) . "'";
    }

    $sql .= " ORDER BY o.date_modified ASC";

    $start = ((int) $data['page']) * (int) $data['count'];
    $limit = (int) $data['count'];
    $sql .= " LIMIT " . (int) $start . "," . (int) $limit;

    $query = $this->db->query($sql);

    $data = array();

    foreach ($query->rows as $result) {
      $order_id = $result['order_id'];

      $order = $this->getFullOrderData($order_id);

      if (is_null($order)) {
        continue;
      }

      $order['status'] = $result['status'];
      $data[] = $order;
    }

    return $data;
  }

  protected function getFullOrderData($order_id) {
    $this->load->model('account/customer');
    $this->load->model('catalog/product');
    $this->load->model('account/order');

    $order = $this->getOrder($order_id);

    if ($order === FALSE) {
      return NULL;
    }

    $customer = $this->model_account_customer->getCustomer($order['customer_id']);

    if (empty($customer)) {
      $customer = array(
        'email' => $order['email'],
        'firstname' => $order['firstname'],
        'lastname' => $order['lastname'],
        'telephone' => $order['telephone'],
        'newsletter' => FALSE
      );
    }

    if (array_key_exists('customer_group_id', $customer) && $this->doesModelExist('account/customer_group')) {
      $this->load->model('account/customer_group');
      $customer_group = $this->model_account_customer_group->getCustomerGroup($customer['customer_group_id']);
      $customer_group = array(
        'id' => $customer_group['customer_group_id'],
        'name' => $customer_group['name']
      );
    } else {
      $customer_group = NULL;
    }

    $data = array(
      'id' => $order_id,
      'created_at' => $order['date_added'],
      'updated_at' => $order['date_modified'],
      'store' => array(
        'id' => $order['store_id'],
        'name' => $order['store_name'],
        'url' => $order['store_url']
      ),
      'customer' => array(
        'email' => $customer['email'],
        'first_name' => $customer['firstname'],
        'last_name' => $customer['lastname'],
        'telephone' => $customer['telephone'],
        'newsletter_optin' => $customer['newsletter'] == 1,
        'group' => $customer_group
      ),
      'currency_code' => $order['currency_code'],
      'currency_value' => $order['currency_value'],
      'line_items' => array(),
      'vouchers' => array(),
    );

    // Products.
    $line_items = $this->model_account_order->getOrderProducts($order_id);

    foreach ($line_items as $line_item) {
      $product = $this->model_catalog_product->getProduct($line_item['product_id']);

      if (!file_exists(DIR_IMAGE . $product['image']) || !is_file(DIR_IMAGE . $product['image'])) {
        $product['image'] = NULL;
      } else {
        $product['image'] = $this->config->get('config_url') . 'image/' . $product['image'];
      }

      $product['categories'] = $this->getProductCategoryNames($line_item['product_id']);

      $option_data = array();
      $options = $this->model_account_order->getOrderOptions($order_id, $line_item['order_product_id']);

      foreach ($options as $option) {
        if ($option['type'] == 'file') {
          continue;
        }

        $option_data[] = array(
          'name'  => $option['name'],
          'value' => $option['value']
        );
      }

      $data['line_items'][] = array(
        'product' => $product,
        'quantity' => $line_item['quantity'],
        'options' => $option_data,

        'price' => $line_item['price'] + ($this->config->get('config_tax') ? $line_item['tax'] : 0),
        'total' => $line_item['total'] + ($this->config->get('config_tax') ? ($line_item['tax'] * $line_item['quantity']) : 0)
      );
    }

    // Vouchers
    if (method_exists($this->model_account_order, 'getOrderVouchers')) {
      $vouchers = $this->model_account_order->getOrderVouchers($order_id);

      foreach ($vouchers as $voucher) {
        $data['vouchers'][] = array(
          'description' => $voucher['description'],
          'amount' => $voucher['amount']
        );
      }
    } else {
      $data['vouchers'] = array();
    }

    $data['totals'] = array();
    $totals = $this->model_account_order->getOrderTotals($order_id);

    foreach ($totals as $total) {
      $data['totals'][] = array(
        'code' => $total['code'],
        'title' => $total['title'],
        'value' => (float) $total['value']
      );
    }

    $data['histories'] = array();
    $histories = $this->model_account_order->getOrderHistories($order_id);

    foreach ($histories as $history) {
      $data['histories'][] = array(
        'date_added' => $history['date_added'],
        'status' => $history['status'],
        'comment' => $history['comment']
      );
    }

    return $data;
  }

  /*
   * NOTE: This method is mainly taken from the "model/account/order" file, except that it is generic
   * in that it can fetch any order, not just the currently logged in customer's.
   */
  protected function getOrder($order_id) {
    $order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int) $order_id . "' AND order_status_id > '0'");

    if ($order_query->num_rows) {
      $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int) $order_query->row['payment_country_id'] . "'");

      if ($country_query->num_rows) {
        $payment_iso_code_2 = $country_query->row['iso_code_2'];
        $payment_iso_code_3 = $country_query->row['iso_code_3'];
      } else {
        $payment_iso_code_2 = '';
        $payment_iso_code_3 = '';
      }

      $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

      if ($zone_query->num_rows) {
        $payment_zone_code = $zone_query->row['code'];
      } else {
        $payment_zone_code = '';
      }

      $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

      if ($country_query->num_rows) {
        $shipping_iso_code_2 = $country_query->row['iso_code_2'];
        $shipping_iso_code_3 = $country_query->row['iso_code_3'];
      } else {
        $shipping_iso_code_2 = '';
        $shipping_iso_code_3 = '';
      }

      $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

      if ($zone_query->num_rows) {
        $shipping_zone_code = $zone_query->row['code'];
      } else {
        $shipping_zone_code = '';
      }

      return array(
        'order_id'                => $order_query->row['order_id'],
        'invoice_no'              => $order_query->row['invoice_no'],
        'invoice_prefix'          => $order_query->row['invoice_prefix'],
        'store_id'                => $order_query->row['store_id'],
        'store_name'              => $order_query->row['store_name'],
        'store_url'               => $order_query->row['store_url'],
        'customer_id'             => $order_query->row['customer_id'],
        'firstname'               => $order_query->row['firstname'],
        'lastname'                => $order_query->row['lastname'],
        'telephone'               => $order_query->row['telephone'],
        'fax'                     => $order_query->row['fax'],
        'email'                   => $order_query->row['email'],
        'payment_firstname'       => $order_query->row['payment_firstname'],
        'payment_lastname'        => $order_query->row['payment_lastname'],
        'payment_company'         => $order_query->row['payment_company'],
        'payment_address_1'       => $order_query->row['payment_address_1'],
        'payment_address_2'       => $order_query->row['payment_address_2'],
        'payment_postcode'        => $order_query->row['payment_postcode'],
        'payment_city'            => $order_query->row['payment_city'],
        'payment_zone_id'         => $order_query->row['payment_zone_id'],
        'payment_zone'            => $order_query->row['payment_zone'],
        'payment_zone_code'       => $payment_zone_code,
        'payment_country_id'      => $order_query->row['payment_country_id'],
        'payment_country'         => $order_query->row['payment_country'],
        'payment_iso_code_2'      => $payment_iso_code_2,
        'payment_iso_code_3'      => $payment_iso_code_3,
        'payment_address_format'  => $order_query->row['payment_address_format'],
        'payment_method'          => $order_query->row['payment_method'],
        'shipping_firstname'      => $order_query->row['shipping_firstname'],
        'shipping_lastname'       => $order_query->row['shipping_lastname'],
        'shipping_company'        => $order_query->row['shipping_company'],
        'shipping_address_1'      => $order_query->row['shipping_address_1'],
        'shipping_address_2'      => $order_query->row['shipping_address_2'],
        'shipping_postcode'       => $order_query->row['shipping_postcode'],
        'shipping_city'           => $order_query->row['shipping_city'],
        'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
        'shipping_zone'           => $order_query->row['shipping_zone'],
        'shipping_zone_code'      => $shipping_zone_code,
        'shipping_country_id'     => $order_query->row['shipping_country_id'],
        'shipping_country'        => $order_query->row['shipping_country'],
        'shipping_iso_code_2'     => $shipping_iso_code_2,
        'shipping_iso_code_3'     => $shipping_iso_code_3,
        'shipping_address_format' => $order_query->row['shipping_address_format'],
        'shipping_method'         => $order_query->row['shipping_method'],
        'comment'                 => $order_query->row['comment'],
        'total'                   => $order_query->row['total'],
        'order_status_id'         => $order_query->row['order_status_id'],
        'language_id'             => $order_query->row['language_id'],
        'currency_id'             => $order_query->row['currency_id'],
        'currency_code'           => $order_query->row['currency_code'],
        'currency_value'          => $order_query->row['currency_value'],
        'date_modified'           => $order_query->row['date_modified'],
        'date_added'              => $order_query->row['date_added'],
        'ip'                      => $order_query->row['ip']
      );
    } else {
      return FALSE;
    }
  }



  //
  // SAVE / FETCH ORDERS
  // ----------------------------------------

  public function saveGuestCart($session_id, $guest, $cart) {
    $query = $this->db->query("SELECT data FROM " . DB_PREFIX . "klaviyo_cart WHERE session_id = '" . $this->db->escape($session_id) . "'");
    $data = array(
      'guest' => $guest,
      'cart' => $cart
    );

    $utcnow = gmdate('Y-m-d H:i:s');

    if ((int) $query->num_rows == 0) {
      $store_id = $this->config->get('config_store_id');
      $store_name = $this->config->get('config_name');

      if ($store_id) {
        $store_url = $this->config->get('config_url');
      } else {
        $store_url = HTTP_SERVER;
      }

      $this->db->query("INSERT INTO " . DB_PREFIX . "klaviyo_cart (session_id, data, store_id, store_name, store_url, created_at, updated_at) VALUES ('" . $this->db->escape($session_id) . "', '" . $this->db->escape(serialize($data)) . "', '" . $this->db->escape($store_id) . "', '" . $this->db->escape($store_name) . "', '" . $this->db->escape($store_url) . "', '" . $this->db->escape($utcnow) . "', '" . $this->db->escape($utcnow) . "')");
    } else {
      $serialized = serialize($data);
      if ($query->rows[0]['data'] != $serialized) {
        $this->db->query("UPDATE " . DB_PREFIX . "klaviyo_cart SET data = '" . $this->db->escape($serialized) . "', updated_at = '" . $this->db->escape($utcnow) . "' WHERE session_id = '" . $this->db->escape($session_id) . "'");
      }
    }
  }

  public function saveCustomerCart($session_id, $customer_id, $cart) {
    $query = $this->db->query("SELECT data FROM " . DB_PREFIX . "klaviyo_cart WHERE session_id = '" . $this->db->escape($session_id) . "'");
    $data = array(
      'customer_id' => $customer_id,
      'cart' => $cart
    );

    $utcnow = gmdate('Y-m-d H:i:s');

    if ((int) $query->num_rows == 0) {
      $store_id = $this->config->get('config_store_id');
      $store_name = $this->config->get('config_name');

      if ($store_id) {
        $store_url = $this->config->get('config_url');
      } else {
        $store_url = HTTP_SERVER;
      }

      $this->db->query("INSERT INTO " . DB_PREFIX . "klaviyo_cart (session_id, data, store_id, store_name, store_url, created_at, updated_at) VALUES ('" . $this->db->escape($session_id) . "', '" . $this->db->escape(serialize($data)) . "', '" . $this->db->escape($store_id) . "', '" . $this->db->escape($store_name) . "', '" . $this->db->escape($store_url) . "', '" . $this->db->escape($utcnow) . "', '" . $this->db->escape($utcnow) . "')");
    } else {
      $serialized = serialize($data);
      if ($query->rows[0]['data'] != $serialized) {
        $this->db->query("UPDATE " . DB_PREFIX . "klaviyo_cart SET data = '" . $this->db->escape($serialized) . "', updated_at = '" . $this->db->escape($utcnow) . "' WHERE session_id = '" . $this->db->escape($session_id) . "'");
      }
    }
  }

  public function getRecentCarts($data) {
    $utcnow = gmdate('Y-m-d H:i:s');

    // Find carts that are at least 15 minutes old and have been updated in the last 60 minutes (by default).
    $sql =  "SELECT kc.cart_id, kc.data, kc.store_id, kc.store_name, kc.store_url, kc.created_at, kc.updated_at ";
    $sql .= "FROM " . DB_PREFIX . "klaviyo_cart kc ";
    $sql .= "WHERE kc.created_at <= DATE_SUB('" . $this->db->escape($utcnow) . "', INTERVAL 15 MINUTE) ";

    if (is_null($data['since'])) {
      $sql .= "    AND kc.updated_at >= DATE_SUB('" . $this->db->escape($utcnow) . "', INTERVAL 60 MINUTE) ";
    } else {
      $sql .= "    AND kc.updated_at >= DATE_SUB('" . $this->db->escape($data['since']) . "', INTERVAL 60 MINUTE) ";
    }

    $sql .= "ORDER BY kc.updated_at ASC";

    $start = ((int) $data['page']) * (int) $data['count'];
    $limit = (int) $data['count'];
    $sql .= " LIMIT " . (int) $start . "," . (int) $limit;

    $query = $this->db->query($sql);

    $data = array();

    foreach ($query->rows as $record) {
      $data[] = $this->getCartData($record);
    }

    return $data;
  }

  protected function getCartData($record) {
    $this->load->model('catalog/product');

    $include_customer_group = $this->doesModelExist('account/customer_group');
    if ($include_customer_group) {
      $this->load->model('account/customer_group');
    }

    $cart_id = $record['cart_id'];
    $checkout = unserialize($record['data']);
    $cart = $checkout['cart'];

    $data = array(
      'id' => $cart_id,
      'created_at' => $record['created_at'],
      'updated_at' => $record['updated_at'],
      'store' => array(
        'id' => $record['store_id'],
        'name' => $record['store_name'],
        'url' => $record['store_url']
      ),
      'customer' => NULL,
      'line_items' => array()
    );

    if (array_key_exists('guest', $checkout)) {
      $guest = $checkout['guest'];

      if ($include_customer_group) {
        $customer_group = $this->model_account_customer_group->getCustomerGroup($guest['customer_group_id']);
        $customer_group = array(
          'id' => $customer_group['customer_group_id'],
          'name' => $customer_group['name']
        );
      } else {
        $customer_group = NULL;
      }

      $data['customer'] = array(
        'email' => $guest['email'],
        'first_name' => $guest['firstname'],
        'last_name' => $guest['lastname'],
        'telephone' => $guest['telephone'],
        'newsletter_optin' => FALSE,
        'group' => $customer_group
      );
    } else if (array_key_exists('customer_id', $checkout)) {
      $this->load->model('account/customer');
      $customer = $this->model_account_customer->getCustomer($checkout['customer_id']);

      if ($customer) {
        if ($include_customer_group) {
          $customer_group = $this->model_account_customer_group->getCustomerGroup($customer['customer_group_id']);
          $customer_group = array(
            'id' => $customer_group['customer_group_id'],
            'name' => $customer_group['name']
          );
        } else {
          $customer_group = NULL;
        }

        $data['customer'] = array(
          'email' => $customer['email'],
          'first_name' => $customer['firstname'],
          'last_name' => $customer['lastname'],
          'telephone' => $customer['telephone'],
          'newsletter_optin' => $customer['newsletter'] == 1,
          'group' => $customer_group
        );
      }
    }

    foreach ($cart as $product_info) {

      $product_id = $product_info['product_id'];

      $options = $product_info['option'];


      $product = $this->model_catalog_product->getProduct($product_id);

      if (!file_exists(DIR_IMAGE . $product['image']) || !is_file(DIR_IMAGE . $product['image'])) {
        $product['image'] = NULL;
      } else {
        $product['image'] = $this->config->get('config_url') . 'image/' . $product['image'];
      }
      $product['categories'] = $this->getProductCategoryNames($product_id);

      $data['line_items'][] = array(
        'product' => $product,
        'quantity' => $product_info['quantity'],
        'options' => $options
      );
    }

    return $data;
  }



  //
  // HELPERS
  // ----------------------------------------

  protected function getProductCategoryNames($product_id) {
    $query = $this->db->query("SELECT cd.category_id, cd.name FROM " . DB_PREFIX . "product_to_category ptc JOIN " . DB_PREFIX . "category_description cd on ptc.category_id = cd.category_id WHERE product_id = '" . (int)$product_id . "'");

    $category_names = array();

    foreach ($query->rows as $record) {
      // Replace special characters.
      $category_names[] = str_replace('&amp;', '&', $record['name']);
    }

    return $category_names;
  }

  protected function doesModelExist($model) {
    return file_exists(DIR_APPLICATION . 'model/' . $model . '.php');
  }
}
?>
