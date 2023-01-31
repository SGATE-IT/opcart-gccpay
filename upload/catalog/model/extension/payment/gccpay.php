<?php
class ModelExtensionPaymentGCCPay extends Model {
	private $logFileName = "gccpay.log";
	private $gateway_url = "https://sandbox.gcc-pay.com/api_v1";
	private $merchant_id;
	private $client_id;
	private $merchant_key;
	private $merchant_secret;	
	private $notifyUrl;
	private $returnUrl;

	private $apiParas = array();

	public function getMethod($address, $total) {
		$this->load->language('extension/payment/gccpay');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_gccpay_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('payment_gccpay_total') > 0 && $this->config->get('payment_gccpay_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('payment_gccpay_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'gccpay',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_gccpay_sort_order')
			);
		}

		return $method_data;
	}

	private function setParams($gccpay_config){
	    $this->gateway_url = $gccpay_config['gateway_url'];
	    $this->merchant_id = $gccpay_config['merchant_id'];
	    $this->client_id = $gccpay_config['client_id'];
	    $this->merchant_key = $gccpay_config['merchant_key'];
	    $this->merchant_secret = $gccpay_config['merchant_secret'];
	    $this->notifyUrl = $gccpay_config['notify_url'];
	    $this->returnUrl = $gccpay_config['return_url'];

	    if (empty($this->merchant_id)||trim($this->merchant_id)=="") {
			throw new Exception("MerchantID should not be NULL!");
		}
		if (empty($this->client_id)||trim($this->client_id)=="") {
			throw new Exception("ClientID should not be NULL!");
		}
		if (empty($this->merchant_key)||trim($this->merchant_key)=="") {
			throw new Exception("MerchantKey should not be NULL!");
		}
		if (empty($this->merchant_secret)||trim($this->merchant_secret)=="") {
			throw new Exception("MerchantSecret should not be NULL!");
		}
		if (empty($this->gateway_url)||trim($this->gateway_url)=="") {
			throw new Exception("gateway_url should not be NULL!");
		}
	}

	function pagePay($builder,$config) {
		$this->setParams($config);
		
		$session_request = array();
		$session_request["merchantOrderId"] = $builder["merchantOrderId"];
		$session_request["amount"] = $builder["amount"];
		$session_request["currency"] = $builder["currency"];
		$session_request["name"] = $builder["name"];
		$session_request["notificationURL"] = $this->notifyUrl;
		$session_request["expiredAt"] = strftime('%Y-%m-%dT%H:%M:%S.000Z',time()+3600*24);
		
		$uri = "/merchants/" . $this->merchant_id . "/orders" ;
		$response_json = $this->submitToGCCPay($uri,"merchant.addOrder","post",$session_request);
		
		return $response_json;
	}

	function checkOrder($gccpayorderid,$config)
	{
	    $this->setParams($config);
	    $uri = "/orders/" . $gccpayorderid;
	    $orderinfo =  $this->submitToGCCPay($uri,"order.detail");
	    return $orderinfo;
	}
	/**
	 *
	 * @param string $uri
	 * @param string $method
	 * @param string $post
	 * @param array $params
	 * @return array[]
	 */
	private function submitToGCCPay($uri="",$method="",$post="get",$params=[])
	{
	    $signArr = [];
	    $signArr["uri"] = $uri;
	    $signArr["key"] = $this->merchant_key;
	    $signArr["timestamp"] = time();
	    $signArr["signMethod"] = "HmacSHA256";
	    $signArr["signVersion"] = 1;
	    $signArr["method"] = $method;
	    
	    ksort($signArr);
	    $signStr = http_build_query($signArr);
	    $sign = base64_encode(hash_hmac('sha256',$signStr, $this->merchant_secret, true));
	    
	    $headers = [];
	    $headers[] = "Content-Type:application/json";
	    $headers[] = "x-auth-signature: " . $sign;
	    $headers[] = "x-auth-key:". $this->merchant_key;
	    $headers[] = "x-auth-timestamp:". $signArr["timestamp"];
	    $headers[] = "x-auth-sign-method: HmacSHA256";
	    $headers[] = "x-auth-sign-version: 1";
	    

	    $url = $this->gateway_url.$uri;
	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
	    
	    $data = json_encode($params);
	    $log = new Log($this->logFileName);
	    $log->write("opencart-gccpay:request[url/type/params]=>:".$url."/".$post."/".$data);
	    
	    if(strtolower($post) == "post")
	    {
	        curl_setopt($curl, CURLOPT_POST, true);
	        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	    }
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    $result = curl_exec($curl);
	    curl_close($curl);
	    $log->write("opencart-gccpay:response=>".$result);
	    	    
	    $ret = json_decode($result,true);
	    return $ret;
	}
	
	public function AddTransactionLog($transInfo)
	{
	    $this->db->query("INSERT INTO `" . DB_PREFIX . "gccpay_transaction` SET `order_id` = '" . (int)$transInfo['order_id'] . "', `amounts` = " . (float)$transInfo['amounts'] . ", `gccpayid` = '" . $this->db->escape($transInfo['gccpayid']) . "', `ticket` = '" . $this->db->escape($transInfo['ticket']) . "', `merchantOrderId` = '" . $this->db->escape($transInfo['merchantOrderId']) . "', `currency` = '" . $this->db->escape($transInfo['currency']) . "', `created_at` = NOW(),`updated_at` = NOW()");
	    
	}
	public function UpdateTransactionLog($transInfo)
	{
	    $this->db->query("UPDATE `" . DB_PREFIX . "gccpay_transaction` SET `status` = '" . $this->db->escape($transInfo['status']) . "', `updated_at` = NOW()  WHERE `gccpayid` = '" . $this->db->escape($transInfo['gccpayid']) . "'");
	}
	public function getTransInfo($transId)
	{
	    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "gccpay_transaction` WHERE `gccpayid` = '" . $this->db->escape($transId) . "'");
	    
	    return $query->row;
	}
}
