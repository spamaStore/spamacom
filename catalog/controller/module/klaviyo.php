<?php 
class ControllerModuleKlaviyo extends Controller { 
  private $error = array(); 

  public function orders() {
   
    $json = array();

    $this->load->model('module/klaviyo');

    if (!$this->config->get('module_klaviyo_api_key')) {
      $this->renderJsonFailure(array('The Klaviyo module has not been set up.'));
      return;
    }

    $api_key = $this->getValue($this->request->get, 'api_key');
    $since = $this->getValue($this->request->get, 'since');
    $page = $this->getValue($this->request->get, 'page');
    $count = $this->getValue($this->request->get, 'count');

    if (!$api_key || $api_key != $this->config->get('module_klaviyo_api_key')) {
      $this->renderJsonFailure(array('You must specify a valid API key.'));
      return;
    }

    if (is_null($since) || is_null($page) || is_null($count)) {
      $this->renderJsonFailure(array('You must specify the "since," "page" and "count" parameters.'));
      return;
    }
      
    $data = array(
      'since' => date('Y-m-d H:i:s', (int) $since),
      'page' => $page,
      'count' => $count
    );
    
    $orders = $this->model_module_klaviyo->getRecentOrders($data);
    $json = array(
      'since' => $since,
      'page' => $page,
      'count' => $count,
      'orders' => $orders
    );

    $this->renderJsonSuccess($json);
  }

  public function carts() {
    $json = array();

    $this->load->model('module/klaviyo');

    if (!$this->config->get('module_klaviyo_api_key')) {
      $this->renderJsonFailure(array('The Klaviyo module has not been set up.'));
      return;
    }

    $api_key = $this->getValue($this->request->get, 'api_key');
    $since = $this->getValue($this->request->get, 'since');
    $page = $this->getValue($this->request->get, 'page');
    $count = $this->getValue($this->request->get, 'count');

    if (!$api_key || $api_key != $this->config->get('module_klaviyo_api_key')) {
      $this->renderJsonFailure(array('You must specify a valid API key.'));
      return;
    }

    if (is_null($page) || is_null($count)) {
      $this->renderJsonFailure(array('You must specify the "page" and "count" parameters.'));
      return;
    }

    $data = array(
      'since' => is_null($since) ? NULL : gmdate('Y-m-d H:i:s', (int) $since),
      'page' => $page,
      'count' => $count
    );
    
    $carts = $this->model_module_klaviyo->getRecentCarts($data);
    $json = array(
      'since' => $since,
      'page' => $page,
      'count' => $count,
      'carts' => $carts
    );

    $this->renderJsonSuccess($json);
  }

  protected function getValue($arr, $key) {
    if (isset($arr[$key])) {
      return $arr[$key];
    } else {
      return NULL;
    }
  }

  protected function renderJsonFailure($errors) {
    $json = array(
      'success' => FALSE,
      'errors' => $errors,
      'data' => array()
    );
    $this->response->addHeader('Content-type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  protected function renderJsonSuccess($data) {
    $json = array(
      'success' => TRUE,
      'errors' => array(),
      'data' => $data
    );
    $this->response->addHeader('Content-type: application/json');
    $this->response->setOutput(json_encode($json));
  }
}
?>