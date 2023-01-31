<?php
class ControllerExtensionPaymentGCCPay extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/gccpay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_gccpay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['merchant_id'])) {
			$data['error_merchant_id'] = $this->error['merchant_id'];
		} else {
			$data['error_merchant_id'] = '';
		}
		
		if (isset($this->error['client_id'])) {
		    $data['error_client_id'] = $this->error['client_id'];
		} else {
		    $data['error_client_id'] = '';
		}
		
		if (isset($this->error['merchant_key'])) {
			$data['error_merchant_key'] = $this->error['merchant_key'];
		} else {
			$data['error_merchant_key'] = '';
		}

		if (isset($this->error['merchant_secret'])) {
		    $data['error_merchant_secret'] = $this->error['merchant_secret'];
		} else {
		    $data['error_merchant_secret'] = '';
		}
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/gccpay', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/gccpay', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_gccpay_merchant_id'])) {
		    $data['payment_gccpay_merchant_id'] = $this->request->post['payment_gccpay_merchant_id'];
		} else {
		    $data['payment_gccpay_merchant_id'] = $this->config->get('payment_gccpay_merchant_id');
		}
		
		if (isset($this->request->post['payment_gccpay_client_id'])) {
		    $data['payment_gccpay_client_id'] = $this->request->post['payment_gccpay_client_id'];
		} else {
		    $data['payment_gccpay_client_id'] = $this->config->get('payment_gccpay_client_id');
		}
		
		if (isset($this->request->post['payment_gccpay_merchant_key'])) {
		    $data['payment_gccpay_merchant_key'] = $this->request->post['payment_gccpay_merchant_key'];
		} else {
		    $data['payment_gccpay_merchant_key'] = $this->config->get('payment_gccpay_merchant_key');
		}
		
		if (isset($this->request->post['payment_gccpay_merchant_secret'])) {
		    $data['payment_gccpay_merchant_secret'] = $this->request->post['payment_gccpay_merchant_secret'];
		} else {
		    $data['payment_gccpay_merchant_secret'] = $this->config->get('payment_gccpay_merchant_secret');
		}
		
		if (isset($this->request->post['payment_gccpay_total'])) {
			$data['payment_gccpay_total'] = $this->request->post['payment_gccpay_total'];
		} else {
			$data['payment_gccpay_total'] = $this->config->get('payment_gccpay_total');
		}

		if (isset($this->request->post['payment_gccpay_order_status_id'])) {
			$data['payment_gccpay_order_status_id'] = $this->request->post['payment_gccpay_order_status_id'];
		} else {
			$data['payment_gccpay_order_status_id'] = $this->config->get('payment_gccpay_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_gccpay_geo_zone_id'])) {
			$data['payment_gccpay_geo_zone_id'] = $this->request->post['payment_gccpay_geo_zone_id'];
		} else {
			$data['payment_gccpay_geo_zone_id'] = $this->config->get('payment_gccpay_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_gccpay_test'])) {
			$data['payment_gccpay_test'] = $this->request->post['payment_gccpay_test'];
		} else {
			$data['payment_gccpay_test'] = $this->config->get('payment_gccpay_test');
		}

		if (isset($this->request->post['payment_gccpay_status'])) {
			$data['payment_gccpay_status'] = $this->request->post['payment_gccpay_status'];
		} else {
			$data['payment_gccpay_status'] = $this->config->get('payment_gccpay_status');
		}

		if (isset($this->request->post['payment_gccpay_sort_order'])) {
			$data['payment_gccpay_sort_order'] = $this->request->post['payment_gccpay_sort_order'];
		} else {
			$data['payment_gccpay_sort_order'] = $this->config->get('payment_gccpay_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/gccpay', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/gccpay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->request->post['payment_gccpay_merchant_id']) {
			$this->error['merchant_id'] = $this->language->get('error_merchant_id');
		}

		if (!$this->request->post['payment_gccpay_client_id']) {
		    $this->error['client_id'] = $this->language->get('error_client_id');
		}
		
		if (!$this->request->post['payment_gccpay_merchant_key']) {
		    $this->error['merchant_key'] = $this->language->get('error_merchant_key');
		}
		
		if (!$this->request->post['payment_gccpay_merchant_secret']) {
		    $this->error['merchant_secret'] = $this->language->get('error_merchant_secret');
		}
		return !$this->error;
	}
	
	
	public function install() {
	    if ($this->user->hasPermission('modify', 'marketplace/extension')) {
	        $this->load->model('extension/payment/gccpay');
	        
	        $this->model_extension_payment_gccpay->install();
	    }
	}
	
	public function uninstall() {
	    if ($this->user->hasPermission('modify', 'marketplace/extension')) {
	        $this->load->model('extension/payment/gccpay');
	        
	        $this->model_extension_payment_gccpay->uninstall();
	    }
	}
}