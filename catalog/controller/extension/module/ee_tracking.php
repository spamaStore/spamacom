<?php

class ControllerExtensionModuleEeTracking extends Controller {
    private $mName      = 'module_ee_tracking';
    private $mModel     = 'model_extension_module_ee_tracking';
    private $mPath      = 'extension/module/ee_tracking';
    private $mVersion   = '2.2.1';

    public function listView() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model($this->mPath);
            $json = $this->{$this->mModel}->listView($this->request->post);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function detail() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model($this->mPath);
            $json = $this->{$this->mModel}->detail($this->request->post);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function click() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model($this->mPath);
            $json = $this->{$this->mModel}->click($this->request->post);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function createClick() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model($this->mPath);
            $json = $this->{$this->mModel}->createClick($this->request->post);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function quickAddToCart() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->config->get($this->mName . '_cart_status')) {
            $this->load->model($this->mPath);
            $product_options = $this->{$this->mModel}->getProductRequiredOptions($this->request->post['product_id'], (int)$this->config->get('config_language_id'));
            foreach ($product_options as $product_option) {
                if ($product_option['required']) {
                    if ($this->config->get($this->mName . '_click_status')) {
                        $json = $this->{$this->mModel}->clickAddToCart($this->request->post);
                    }
                    $this->response->addHeader('Content-Type: application/json');
                    return $this->response->setOutput(json_encode($json));
                }
            }
            $json = $this->{$this->mModel}->quickAddToCart($this->request->post);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function addToCart() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->config->get($this->mName . '_cart_status')) {
            if (isset($this->request->post['option'])) {
                $options = array();
                foreach ($this->request->post['option'] as $item) {
                    preg_match('/\d+/', $item['name'], $matches);
                    if (isset($matches[0])) {
                        $options[$matches[0]] = $item['value'];
                    }
                }
                $this->request->post['option'] = $options;
            }
            $this->load->model($this->mPath);
            $product_options = $this->{$this->mModel}->getProductRequiredOptions($this->request->post['product_id'], (int)$this->config->get('config_language_id'));
            foreach ($product_options as $product_option) {
                if ($product_option['required'] && empty($this->request->post['option'][$product_option['product_option_id']])) {
                    $this->response->addHeader('Content-Type: application/json');
                    return $this->response->setOutput(json_encode($json));
                }
            }
            $json = $this->{$this->mModel}->addToCart($this->request->post);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function removeFromCart() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model($this->mPath);
            $json = $this->{$this->mModel}->removeFromCart($this->request->post);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function checkout() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model($this->mPath);
            $json = $this->{$this->mModel}->checkout($this->request->post);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function checkoutOption() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model($this->mPath);
            $json = $this->{$this->mModel}->checkoutOption($this->request->post);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function transaction() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['order_id']) && isset($this->request->post['order_status_id'])) {
            $this->load->model($this->mPath);
            $json = $this->{$this->mModel}->transaction($this->request->post['order_id'], $this->request->post['order_status_id']);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function refund() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model($this->mPath);
            $json = $this->{$this->mModel}->refund($this->request->post);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function promotion() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model($this->mPath);
            $json = $this->{$this->mModel}->promotion($this->request->post);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function promotionClick() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model($this->mPath);
            $json = $this->{$this->mModel}->promotionClick($this->request->post);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function listViewLog() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $json = 'LIST VIEW' . ($this->config->get($this->mName . '_impression_debug') ? ' / mode: debug' : ' / mode: release');
            if ($this->config->get($this->mName . '_extended_log') && isset($this->request->post['url'])) {
                $json .= ' / page: ' . html_entity_decode($this->request->post['url'], ENT_QUOTES, 'UTF-8');
            }
            if (isset($this->request->post['error'])) {
                $json .= ' / error: ' . html_entity_decode($this->request->post['error'], ENT_QUOTES, 'UTF-8');
            }
            $this->load->model($this->mPath);
            $this->{$this->mModel}->addLog($json);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function detailLog() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $json = 'PRODUCT DETAIL VIEW' . ($this->config->get($this->mName . '_detail_debug') ? ' / mode: debug' : ' / mode: release');
            if ($this->config->get($this->mName . '_extended_log') && isset($this->request->post['url'])) {
                $json .= ' / page: ' . html_entity_decode($this->request->post['url'], ENT_QUOTES, 'UTF-8');
            }
            if (isset($this->request->post['error'])) {
                $json .= ' / error: ' . html_entity_decode($this->request->post['error'], ENT_QUOTES, 'UTF-8');
            }
            $this->load->model($this->mPath);
            $this->{$this->mModel}->addLog($json);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function clickLog() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $json = 'PRODUCT CLICK' . ($this->config->get($this->mName . '_click_debug') ? ' / mode: debug' : ' / mode: release');
            if ($this->config->get($this->mName . '_extended_log') && isset($this->request->post['url'])) {
                $json .= ' / page: ' . html_entity_decode($this->request->post['url'], ENT_QUOTES, 'UTF-8');
            }
            if (isset($this->request->post['error'])) {
                $json .= ' / error: ' . html_entity_decode($this->request->post['error'], ENT_QUOTES, 'UTF-8');
            }
            $this->load->model($this->mPath);
            $this->{$this->mModel}->addLog($json);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addToCartLog() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $json = 'ADD TO CART' . ($this->config->get($this->mName . '_cart_debug') ? ' / mode: debug' : ' / mode: release');
            if ($this->config->get($this->mName . '_extended_log') && isset($this->request->post['url'])) {
                $json .= ' / page: ' . html_entity_decode($this->request->post['url'], ENT_QUOTES, 'UTF-8');
            }
            if (isset($this->request->post['error'])) {
                $json .= ' / error: ' . html_entity_decode($this->request->post['error'], ENT_QUOTES, 'UTF-8');
            }
            $this->load->model($this->mPath);
            $this->{$this->mModel}->addLog($json);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function removeFromCartLog() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $json = 'REMOVE FROM CART' . ($this->config->get($this->mName . '_cart_debug') ? ' / mode: debug' : ' / mode: release');
            if ($this->config->get($this->mName . '_extended_log') && isset($this->request->post['url'])) {
                $json .= ' / page: ' . html_entity_decode($this->request->post['url'], ENT_QUOTES, 'UTF-8');
            }
            if (isset($this->request->post['error'])) {
                $json .= ' / error: ' . html_entity_decode($this->request->post['error'], ENT_QUOTES, 'UTF-8');
            }
            $this->load->model($this->mPath);
            $this->{$this->mModel}->addLog($json);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function checkoutLog() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $json = 'CHECKOUT' . ($this->config->get($this->mName . '_checkout_debug') ? ' / mode: debug' : ' / mode: release');
            if ($this->config->get($this->mName . '_extended_log') && isset($this->request->post['url'])) {
                $json .= ' / page: ' . html_entity_decode($this->request->post['url'], ENT_QUOTES, 'UTF-8');
            }
            if (isset($this->request->post['error'])) {
                $json .= ' / error: ' . html_entity_decode($this->request->post['error'], ENT_QUOTES, 'UTF-8');
            }
            $this->load->model($this->mPath);
            $this->{$this->mModel}->addLog($json);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function promotionLog() {
        $json = '';
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $json = 'PROMOTION' . ($this->config->get($this->mName . '_promotion_debug') ? ' / mode: debug' : ' / mode: release');
            if ($this->config->get($this->mName . '_extended_log') && isset($this->request->post['url'])) {
                $json .= ' / page: ' . html_entity_decode($this->request->post['url'], ENT_QUOTES, 'UTF-8');
            }
            if (isset($this->request->post['error'])) {
                $json .= ' / error: ' . html_entity_decode($this->request->post['error'], ENT_QUOTES, 'UTF-8');
            }
            $this->load->model($this->mPath);
            $this->{$this->mModel}->addLog($json);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}