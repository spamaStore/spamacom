<?php
class ControllerExtensionPaymentPaytabsexpress extends Controller {
	public function index() {
		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
//   print_r($order_info);exit;
		$merchant_id = $this->config->get('payment_paytabsexpress_merchant_id');
		$merchant_secret = $this->config->get('payment_paytabsexpress_merchant_secret_key');
		$return_url = $this->url->link('extension/payment/paytabsexpress/callback');
		

		$out_trade_no = trim($order_info['order_id']);
		$subject = trim($this->config->get('config_name'));
		$total_amount = trim($this->currency->format($order_info['total'], $order_info['currency_code'],'',false));
		$body = '';//trim($_POST['WIDbody']);
		$random_number = rand();
	
		$js = '<script type="text/javascript">
		jQuery(document).ready(function($){
			  $(function(){$(\'head\').append(\'<script  type="text/javascript" id="context" src="https://www.paytabs.com/express/express_checkout_v3.js">\<\/script>\');});
		
			$(function(){$(\'head\').append(\'<link rel="stylesheet" type="text/css" href="https://www.paytabs.com/theme/express_checkout/css/express.css">\');});
			$("#pt_loader").show();
			//$("iframe[name=\'PT_express_checkout_loader\']").remove();
		';


		
		
		$js.=' });</script>';
		$js.='<script>
		function initPaytabs(){
			var random_var = "'.$random_number.'";
			$("#pt_loader").hide();
			Paytabs("#express_checkout").expresscheckout({
				settings:{
				merchant_id: "'.$merchant_id.'",
				secret_key: "'.$merchant_secret.'",
				amount : "'.$total_amount.'",
				currency : "'.$order_info['currency_code'].'",
				title : "'.$order_info['firstname'].' '.$order_info['lastname'].'",
				product_names: "Product1,Product2,Product3",
				order_id: "'.$order_info['order_id'].'",
				url_redirect: "'.$return_url.'",
				display_customer_info:1,
				display_billing_fields:1,
				display_shipping_fields:1,
				language: "en",
				redirect_on_reject: 1,
				
				},customer_info:{
					first_name: "'.$order_info['firstname'].'",
					last_name: "'.$order_info['lastname'].'",
					phone_number: "'.$order_info['telephone'].'",
					email_address: "'.$order_info['email'].'", 
					country_code: "973"
				},
				billing_address:{
					full_address: "'.$order_info['payment_address_1'].' '.$order_info['payment_address_2'].'",
					city: "'.$order_info['payment_city'].'",
					state: "'.$order_info['payment_zone'].'",
					country: "'.$order_info['payment_iso_code_3'].'",
					postal_code: "'.$order_info['payment_postcode'].'"
				},shipping_address:{
					shipping_first_name: "'.$order_info['shipping_firstname'].'",
					shipping_last_name: "'.$order_info['shipping_lastname'].'",
					full_address_shipping:"'.$order_info['shipping_address_1'].' '.$order_info['shipping_address_2'].'",
					city_shipping: "'.$order_info['shipping_city'].'",
					state_shipping: "'.$order_info['shipping_zone'].'",
					country_shipping: "'.$order_info['shipping_iso_code_3'].'",
					postal_code_shipping: "'.$order_info['shipping_postcode'].'",
					}
				});
			}';
		
		$js.= '
		setTimeout(function(){ initPaytabs(); }, 2500);
		</script>';

		$data['js'] = $js;
		return $this->load->view('extension/payment/paytabsexpress', $data);
	}

	public function callback() {
		$this->load->model('extension/payment/paytabsexpress');
		$result = $this->model_extension_payment_paytabsexpress->verify_payment($_POST['transaction_id']);
		
		if(in_array($result['response_code'],[100,481,482])) {//check successed
			//TODO: Check here if the result is tempered
			$this->log->write('PayTabs Express checkout successed');
			$order_id = $result['order_id'];
			$this->load->model('checkout/order');
			$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_paytabsexpress_order_status_id'),$result['result']);
			$this->response->redirect($this->url->link('checkout/success'));
		}else {
			$this->log->write('PayTabs Express checkout check failed');
			//Redirect to failed method
			$this->response->redirect($this->url->link('checkout/failure'));
		}
	}
}
