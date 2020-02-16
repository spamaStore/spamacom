<?php
class ControllerExtensionModuleImportCoupon extends Controller {
  
  protected $error = array();
  
  public function index() {
    $this->getList();
  }

  public function deleteAll() {

    $this->load->language('extension/module/importcoupon');
    $this->document->setTitle($this->language->get('heading_title'));
    $this->load->model('extension/module/importcoupon');

    if ($this->validate()) {

      $this->model_extension_module_importcoupon->deleteallCoupon();
      $this->session->data['success'] = $this->language->get('text_success_delete');
      $url = '';

      if (isset($this->request->get['page'])) {
        $url .= '&page=' . $this->request->get['page'];
      }

      if (isset($this->request->get['sort'])) {
        $url .= '&sort=' . $this->request->get['sort'];
      }

      if (isset($this->request->get['order'])) {
        $url .= '&order=' . $this->request->get['order'];
      }

      $this->response->redirect($this->url->link('marketing/coupon', 'user_token=' . $this->session->data['user_token'], true));
    }
    
    $this->getList();
  }


  protected function getList() {

    $this->load->language('extension/module/importcoupon');
    $this->load->model('extension/module/importcoupon');
    $this->model_extension_module_importcoupon->createTable();

    $this->document->setTitle($this->language->get('heading_title'));
    $data['button_upload']   = $this->language->get('button_upload');
    $data['upload_action'] = $this->url->link('extension/module/importcoupon/couponcsvtemplate', 'user_token=' . $this->session->data['user_token'], true);
    $data['coupon_list'] = $this->url->link('marketing/coupon', 'user_token=' . $this->session->data['user_token'], true); 
    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text'      => $this->language->get('text_home'),
      'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
      'separator' => false
      );

    $data['breadcrumbs'][] = array(
      'text'      => $this->language->get('heading_title'),
      'href'      => $this->url->link('extension/module/importcoupon', 'user_token=' . $this->session->data['user_token'], true),
      'separator' => ' :: '
      );

    $data['coupons'] = array();


    $data['heading_title'] = $this->language->get('heading_title');

    $data['button_csvimport']   = $this->language->get('button_csvimport');
    $data['step1']   = $this->language->get('step1');
    $data['step2']   = $this->language->get('step2');
    $data['step3']   = $this->language->get('step3');
    $data['help_code'] = $this->language->get('help_code');
    $data['help_number'] = $this->language->get('help_number');
    $data['help_type'] = $this->language->get('help_type');
    $data['help_logged'] = $this->language->get('help_logged');
    $data['help_total'] = $this->language->get('help_total');
    $data['help_category'] = $this->language->get('help_category');
  //  $data['help_shipping_applied'] = $this->language->get('help_shipping_applied');
    $data['help_free_shipping'] = $this->language->get('help_free_shipping');
    $data['help_product'] = $this->language->get('help_product');
    $data['help_uses_total'] = $this->language->get('help_uses_total');
    $data['help_uses_customer'] = $this->language->get('help_uses_customer');
    $data['text_enabled'] = $this->language->get('text_enabled');
    $data['text_disabled'] = $this->language->get('text_disabled');
    $data['text_yes'] = $this->language->get('text_yes');
    $data['text_no'] = $this->language->get('text_no');
    $data['text_percent'] = $this->language->get('text_percent');
    $data['text_amount'] = $this->language->get('text_amount');
    $data['entry_name'] = $this->language->get('entry_name');
    $data['entry_description'] = $this->language->get('entry_description');
    $data['entry_code'] = $this->language->get('entry_code');
    $data['entry_discount'] = $this->language->get('entry_discount');
    $data['entry_logged'] = $this->language->get('entry_logged');
    $data['entry_shipping'] = $this->language->get('entry_shipping');
    $data['entry_type'] = $this->language->get('entry_type');
    $data['entry_total'] = $this->language->get('entry_total');
    $data['entry_category'] = $this->language->get('entry_category');
    $data['entry_product'] = $this->language->get('entry_product');
    $data['entry_date_start'] = $this->language->get('entry_date_start');
    $data['entry_date_end'] = $this->language->get('entry_date_end');
    $data['entry_uses_total'] = $this->language->get('entry_uses_total');
    $data['entry_uses_customer'] = $this->language->get('entry_uses_customer');
    $data['entry_status'] = $this->language->get('entry_status');
    $data['entry_customergroup'] = $this->language->get('entry_customergroup');

    $data['text_no_results'] = $this->language->get('text_no_results');
    $data['button_insert'] = $this->language->get('button_insert');
    $data['button_delete'] = $this->language->get('button_delete');
    $data['button_delete_all'] = $this->language->get('button_delete_all');
    $data['text_percent'] = $this->language->get('text_percent');
    $data['text_amount'] = $this->language->get('text_amount');
    $data['user_token'] = $this->session->data['user_token'];

    if($this->config->get('module_importcoupon_product')){  
      $data['module_importcoupon_product'] = $this->config->get('module_importcoupon_product');
    } else {
      $data['module_importcoupon_product'] = "";
    }

    $products = explode(":", $data['module_importcoupon_product']);
    $this->load->model('catalog/product');

    $data['coupon_product'] = array();

    foreach ($products as $product_id) {
      $product_info = $this->model_catalog_product->getProduct($product_id);

      if ($product_info) {
        $data['coupon_product'][] = array(
          'product_id' => $product_info['product_id'],
          'name'       => $product_info['name']
        );
      }
    }

     if($this->config->get('module_importcoupon_category')){  
      $data['module_importcoupon_category'] = $this->config->get('module_importcoupon_category');
    } else {
      $data['module_importcoupon_category'] = "";
    }

    $categories = explode(":", $data['module_importcoupon_category']);

    $this->load->model('catalog/category');

    $data['coupon_category'] = array();

    foreach ($categories as $category_id) {
      $category_info = $this->model_catalog_category->getCategory($category_id);

      if ($category_info) {
        $data['coupon_category'][] = array(
          'category_id' => $category_info['category_id'],
          'name'        => ($category_info['path'] ? $category_info['path'] . ' &gt; ' : '') . $category_info['name']
        );
      }
    }


    if($this->config->get('module_importcoupon_number')){  
      $data['module_importcoupon_number'] = $this->config->get('module_importcoupon_number');
    } else {
      $data['module_importcoupon_number'] = '200';
    }

    if($this->config->get('module_importcoupon_prefix')){  
      $data['module_importcoupon_prefix'] = $this->config->get('module_importcoupon_prefix');
    } else {
      $data['module_importcoupon_prefix'] = 'PREF';
    }

    if($this->config->get('module_importcoupon_ctype')){  
      $data['module_importcoupon_ctype'] = $this->config->get('module_importcoupon_ctype');
    } else {
      $data['module_importcoupon_ctype'] = 'P';
    }

    if($this->config->get('module_importcoupon_discount')){  
      $data['module_importcoupon_discount'] = $this->config->get('module_importcoupon_discount');
    } else {
      $data['module_importcoupon_discount'] = 10;
    }

    if($this->config->get('module_importcoupon_total')){  
      $data['module_importcoupon_total'] = $this->config->get('module_importcoupon_total');
    } else {
      $data['module_importcoupon_total'] = 0;
    }
   
    if($this->config->get('module_importcoupon_freeshipping')){  
      $data['module_importcoupon_freeshipping'] = $this->config->get('module_importcoupon_freeshipping');
    } else {
      $data['module_importcoupon_freeshipping'] = 0;
    }

    if($this->config->get('module_importcoupon_logged')){  
      $data['module_importcoupon_logged'] = $this->config->get('module_importcoupon_logged');
    } else {
      $data['module_importcoupon_logged'] = 0;
    }

    if($this->config->get('module_importcoupon_sdate')){  
      $data['module_importcoupon_sdate'] = $this->config->get('module_importcoupon_sdate');
    } else {
      $data['module_importcoupon_sdate'] = date('Y-m-d');
    }

    if($this->config->get('module_importcoupon_edate')){  
      $data['module_importcoupon_edate'] = $this->config->get('module_importcoupon_edate');
    } else {
      $data['module_importcoupon_edate'] = date('Y-m-d', strtotime('+1 Month'));
    }

    if($this->config->get('module_importcoupon_usetotal')){  
      $data['module_importcoupon_usetotal'] = $this->config->get('module_importcoupon_usetotal');
    } else {
      $data['module_importcoupon_usetotal'] = 10;
    }

    if($this->config->get('module_importcoupon_cuse')){  
      $data['module_importcoupon_cuse'] = $this->config->get('module_importcoupon_cuse');
    } else {
      $data['module_importcoupon_cuse'] = 10;
    }

    $version = str_replace(".","",VERSION);

    if($version > 2100) {
      $this->load->model('customer/customer_group');
      $data['customergroups'] = $this->model_customer_customer_group->getCustomerGroups();
    } else {
      $this->load->model('sale/customer_group');
      $data['customergroups'] = $this->model_sale_customer_group->getCustomerGroups();
    }

    $products = explode(":", $data['module_importcoupon_product']);
   
    if (isset($this->request->post['customergroup'])) {
      $data['module_importcoupon_customergroup'] = $this->request->post['customergroup'];
    } else {
      $data['module_importcoupon_customergroup'] = explode(":", $this->config->get('module_importcoupon_customergroup'));
    } 
    
    if($data['module_importcoupon_customergroup'] == "") {
       $data['module_importcoupon_customergroup'] = array();
    }

    if (isset($this->session->data['success'])) {
      $data['success'] = $this->session->data['success'];
      
      unset($this->session->data['success']);
    } else {
      $data['success'] = '';
    }


    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/importcoupon_list.', $data));

  }

  public function couponcsvtemplate(){

   $data_rows  = $this->config->get('module_importcoupon_number');
   $code = $this->config->get('module_importcoupon_prefix');
   $fields = array();
   $sample_data = array();
   $this->load->model('extension/module/importcoupon');
   $number = $this->model_extension_module_importcoupon->getlastid();
   $nameprefix = "";
   $type = $this->config->get('module_importcoupon_ctype');
   if ($type == "P") {
     $nameprefix = $this->config->get('module_importcoupon_discount')."% ";
   }
   for($i =0; $i < $data_rows; $i++,$number++){
     $sample_data[$i]['name'] = $nameprefix."Coupon ".$number;
     $sample_data[$i]['code'] = $code.substr(strtoupper(substr(md5(mt_rand()), 0, 13)),strlen($code) + 3);
     $sample_data[$i]['type'] = $this->config->get('module_importcoupon_ctype');
     $sample_data[$i]['discount'] = $this->config->get('module_importcoupon_discount');
     $sample_data[$i]['total'] = $this->config->get('module_importcoupon_total');
     $sample_data[$i]['logged'] = $this->config->get('module_importcoupon_logged');
     $sample_data[$i]['free-shipping'] = $this->config->get('module_importcoupon_freeshipping');
     $sample_data[$i]['product_id'] = $this->config->get('module_importcoupon_product');
     $sample_data[$i]['category_id'] = $this->config->get('module_importcoupon_category');
     $sample_data[$i]['date_start'] = $this->config->get('module_importcoupon_sdate');
     $sample_data[$i]['date_end'] = $this->config->get('module_importcoupon_edate');
     $sample_data[$i]['uses_total'] = $this->config->get('module_importcoupon_usetotal');
     $sample_data[$i]['uses_customer'] = $this->config->get('module_importcoupon_cuse');
     $sample_data[$i]['customer_group_id'] = $this->config->get('module_importcoupon_customergroup');
     $sample_data[$i]['status'] = TRUE;

   }

  $link  = $this->url->link('marketing/coupon', 'user_token=' . $this->session->data['user_token'], true);
  $this->model_extension_module_importcoupon->bulkAddCoupon($sample_data);
  $this->session->data['success'] = "You have successfully imported $data_rows coupons. See uploaded coupons here <a href='$link'>Coupons</a>";


  $this->response->redirect($this->url->link('extension/module/importcoupon', 'user_token=' . $this->session->data['user_token'], true));

  }

  public function exportcouponcsvtemplate(){
   $fields = array();
   $sample_data = array();
   array_push($fields,'name','code','discount','date_start','date_end','status');
   $this->load->model('marketing/coupon');
   $coupons = $this->model_marketing_coupon->getCoupons();
   $data_rows = count($coupons);
   for($i =0; $i < $data_rows; $i++){
     $sample_data[$i]['name'] = $coupons[$i]['name'];
     $sample_data[$i]['code'] = $coupons[$i]['code'];
     $sample_data[$i]['discount'] = $coupons[$i]['discount'];
     $sample_data[$i]['date_start'] = $coupons[$i]['date_start'];
     $sample_data[$i]['date_end'] = $coupons[$i]['date_end'];
     $sample_data[$i]['status'] = $coupons[$i]['status'];
   }

  $version = str_replace(".","",VERSION);

  if($version < 2100) {
     $this->load->library('exportcsv');
  }
   $csv = new ExportCSV();
   $csv->fields = $fields;
   $csv->result = $sample_data;
   $csv->process();
   $date = date('Y-m-d');
   $csv->download('store_coupons_'.$date.'.csv');

}

  private function validate() {
    if (!$this->user->hasPermission('modify', 'extension/module/importcoupon')) {
      $this->error['warning'] = $this->language->get('error_permission');  
    }

    if (!$this->error) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  public function setting() {
    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      
      if(isset($this->request->post['module_importcoupon_number']) &&  $this->request->post['module_importcoupon_number'] <= 10000){  
        $this->request->post['module_importcoupon_number'] = $this->request->post['module_importcoupon_number'];
      } else {
        $this->request->post['module_importcoupon_number'] = '10000';
      }

      if(!isset($this->request->post['module_importcoupon_customergroup'])) {  
        $this->request->post['module_importcoupon_customergroup'] = "";
      }

      if(!isset($this->request->post['module_importcoupon_product'])) {  
        $this->request->post['module_importcoupon_product'] = "";
      }

      if(!isset($this->request->post['module_importcoupon_category'])) {  
        $this->request->post['module_importcoupon_category'] = "";
      }

      $this->request->post['module_importcoupon_product'] = str_replace(",",":",$this->request->post['module_importcoupon_product']);
      $this->request->post['module_importcoupon_category'] = str_replace(",",":",$this->request->post['module_importcoupon_category']);
      $this->request->post['module_importcoupon_customergroup'] = str_replace(",",":",$this->request->post['module_importcoupon_customergroup']);
      
      $this->load->model('setting/setting');
      $this->model_setting_setting->editSetting('module_importcoupon', $this->request->post);
      if(isset($this->request->post['csv']) && $this->request->post['csv']) {  
        $this->load->language('extension/module/importcouponcsv');
      } else {
        $this->load->language('extension/module/importcoupon');
      }
      $json = array();
      $json['success'] = $this->language->get('text_success');
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }
  }
}