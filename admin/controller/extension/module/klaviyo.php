<?php 
class ControllerExtensionModuleKlaviyo extends Controller { 
  private $error = array(); 

  public function install() {
    $this->load->model('extension/module/klaviyo');
    $this->model_extension_module_klaviyo->createTables();

  }

  public function uninstall() {
    $this->load->model('extension/module/klaviyo');
    $this->model_extension_module_klaviyo->dropTables();
  }

  public function index() {   
    $this->language->load('extension/module/klaviyo');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('setting/setting');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_setting_setting->editSetting('module_klaviyo', $this->request->post);

      $this->session->data['success'] = $this->language->get('text_success');

      $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
    }

    if (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
    }
    
    if (isset($this->error['api_key'])) {
      $data['error_api_key'] = $this->error['api_key'];
    } else {
      $data['error_api_key'] = '';
    }

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_extension'),
      'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('extension/module/klaviyo', 'user_token=' . $this->session->data['user_token'], true)
    );

    $data['action'] = $this->url->link('extension/module/klaviyo', 'user_token=' . $this->session->data['user_token'], true);

    $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

    if (isset($this->request->post['module_klaviyo_api_key'])) {
      $data['module_klaviyo_api_key'] = $this->request->post['module_klaviyo_api_key'];
    } else {
      $data['module_klaviyo_api_key'] = $this->config->get('module_klaviyo_api_key');
    }

    if (isset($this->request->post['module_klaviyo_status'])) {
      $data['module_klaviyo_status'] = $this->request->post['module_klaviyo_status'];
    } else {
      $data['module_klaviyo_status'] = $this->config->get('module_klaviyo_status');
    }

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/klaviyo', $data));

  }
  
  protected function validate() {
    if (!$this->user->hasPermission('modify', 'extension/module/klaviyo')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    if (!$this->request->post['module_klaviyo_api_key']) {
      $this->error['api_key'] = $this->language->get('error_api_key');
    }

    return !$this->error; 
  }
}
?>