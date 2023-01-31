<?php
class ControllerExtensionPaymentGCCPay extends Controller {
	public function index() {
		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		if(!isset($this->session->data['order_id'])) {
			return false;
		}

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$config = array (
			'merchant_id'     => $this->config->get('payment_gccpay_merchant_id'),
			'client_id'       => $this->config->get('payment_gccpay_client_id'),
		    'merchant_key'    => $this->config->get('payment_gccpay_merchant_key'),
		    'merchant_secret' => $this->config->get('payment_gccpay_merchant_secret'),
		    'notify_url'           => $this->url->link("extension/payment/gccpay/callback") ,//HTTPS_SERVER . "payment_callback/gccpay",
			'return_url'           => $this->url->link('checkout/success'),
			'gateway_url'          => $this->config->get('payment_gccpay_test') == "live" ? "https://gateway.gcc-pay.com/api_v1" : "https://sandbox.gcc-pay.com/api_v1",
		    'gccpay_url'      => $this->config->get('payment_gccpay_test') == "live" ? "https://gateway.gcc-pay.com/" : "https://sandbox.gcc-pay.com/",
		    
		);
		$out_trade_no = trim($order_info['order_id']);
		$subject = trim($this->config->get('config_name'));
		/// 这里货币需要处理 alfa@gccpay
		$total_amount = round($order_info['total'],16,2);//trim($this->currency->format($order_info['total'], 'CNY', '', false));
		if ($this->customer->isLogged()) {
		    $customer_id = $this->customer->getId();
		} else {
		    $customer_id = 0;
		}
		$payRequestBuilder = array(
		    'merchantOrderId'         => $out_trade_no."_".time(),
		    'amount'      => $total_amount,
		    'currency' => $order_info['currency_code'],
		    'name' => "User: ".$customer_id.",Order:".$out_trade_no,
		);
		
		$this->load->model('extension/payment/gccpay');

		$response = $this->model_extension_payment_gccpay->pagePay($payRequestBuilder,$config);
		$transInfo = [
		    "order_id"=>$out_trade_no,
		    "amounts" => $total_amount,
		    "gccpayid" => $response["id"],
		    "ticket" => $response["ticket"],
		    "merchantOrderId" => $payRequestBuilder["merchantOrderId"],
		    "currency" => $payRequestBuilder["currency"],
		];
		$this->model_extension_payment_gccpay->AddTransactionLog($transInfo);
		$data['action'] = $config['gccpay_url'];
		$data['form_params'] = ["orderId"=>$response["id"],"ticket"=>$response["ticket"],"returnURL"=>$config["return_url"]];

		return $this->load->view('extension/payment/gccpay', $data);
	}

	public function callback() {
	    $this->log->write('gccpay pay notify orderId:'.$_GET["_orderId"]);
		$gccpayorderid = $_GET["_orderId"];
		$this->load->model('extension/payment/gccpay');
		$config = array (
		    'merchant_id'     => $this->config->get('payment_gccpay_merchant_id'),
		    'client_id'       => $this->config->get('payment_gccpay_client_id'),
		    'merchant_key'    => $this->config->get('payment_gccpay_merchant_key'),
		    'merchant_secret' => $this->config->get('payment_gccpay_merchant_secret'),
		    'notify_url'           => $this->url->link("extension/payment/gccpay/callback") ,//HTTPS_SERVER . "payment_callback/gccpay",
		    'return_url'           => $this->url->link('checkout/success'),
		    'gateway_url'          => $this->config->get('payment_gccpay_test') == "live" ? "https://gateway.gcc-pay.com/api_v1" : "https://sandbox.gcc-pay.com/api_v1",
		);
		$response = $this->model_extension_payment_gccpay->checkOrder($gccpayorderid,$config);
		$transInfo = [
		    "gccpayid" => $response["id"],
		    "status" => $response["status"]
		];
		$this->model_extension_payment_gccpay->UpdateTransactionLog($transInfo);
		
		$gccpayTransInfo = $this->model_extension_payment_gccpay->getTransInfo($gccpayorderid);
        $this->log->write("gccpayTransInfo:".json_encode($gccpayTransInfo));
		if ($gccpayTransInfo['status'] == 'paid') {
		    $this->load->model('checkout/order');
		    $this->model_checkout_order->addOrderHistory($gccpayTransInfo["order_id"], $this->config->get('payment_gccpay_order_status_id'));
		    echo "COMPLETED::".$gccpayTransInfo["gccpayid"];
		}
	}
}