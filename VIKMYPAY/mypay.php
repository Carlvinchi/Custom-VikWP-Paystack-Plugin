<?php

defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.payment.payment');


abstract class AbstractMyPayPayment extends JPayment{
	
	
	protected function buildAdminParameters() {
        $logo_img = VIKMYPAY_URI . 'vikbooking/mypay.png';
        return array(	
            'logo' => array(
                'label' => __('','vikbooking'),
                'type' => 'custom',
                'html' => '<img src="'.$logo_img.'"/>'
            ),
            
            'paystack_api_secret' => array(
                'label' => __('API Secret','vikbooking'),
                'type' => 'text'
            ),
            'paystack_public_key' => array(
                'label' => __('API Public Key','vikbooking'),
                'type' => 'text'
            ),
            'fx_api_endpoint' => array(
                'label' => __('Exchange API URL','vikbooking'),
                'type' => 'text'
            ),
            'exchange_api_key' => array(
                'label' => __('Exchange API Key','vikbooking'),
                'type' => 'text'
            ),
            
        );

        /*  How to retrieve form details  */
       // $merchant_id = $this->getParam('merchantid'); 
       

    }
	
	public function __construct($alias, $order, $params = array()) {
		parent::__construct($alias, $order, $params);
	}
	
	protected function beginTransaction() {
        // Think about non fx version
        /*       FX API integration  to get exchange rate   */

        $fx_api_endpoint = $this->getParam('fx_api_endpoint');

        $exchange_api_key = $this->getParam('exchange_api_key');

        // Initialize CURL:
        $ch = curl_init($fx_api_endpoint.'?access_key='.$exchange_api_key.'&base=EUR&symbols=GHS');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
        // Store the data:
        $json = curl_exec($ch);
        curl_close($ch);
 
        // Decode JSON response:
        $exchangeRates = json_decode($json, true);
 
        
        // Access the exchange rate values, e.g. GBP:
        $rate = $exchangeRates['rates']['GHS'];

        $api_secret = $this->getParam('paystack_api_secret');

        $public_key = $this->getParam('paystack_public_key');

        $trx_ref = "vikbooking_".mt_rand(1000000000,9999999999);

        $amount_to_charge = ($this->get('total_to_pay')*$rate)*100;

        $email = $this->get('custmail');

        $notify_url = $this->get('notify_url');

        $pay_url = VIKMYPAY_URI . 'pay/money.php';

        
        $form='<form action="'.$pay_url.'" method="post">';
        // put here all the required fields of your gateway
    
        $form.='<input type="hidden" id="amount" name="amount" value="'.$amount_to_charge.'"/>';
        $form.='<input type="hidden" id="email" name="email" value="'.$email.'"/>';
        $form.='<input type="hidden" id="ref" name="ref" value="'.$trx_ref.'"/>';
        $form.='<input type="hidden" id="key" name="key" value="'.$api_secret.'"/>';
        $form.='<input type="hidden" id="notify" name="notify" value="'.$notify_url.'"/>';
        // print a button to submit the payment form
        $form.='<input type="submit" name="_submit" value="Pay Now!" />';
        $form.='</form>';
        
        echo $form;
 

	}
	
	protected function validateTransaction(JPaymentStatus &$status) {

        $transaction_id = $_GET["trxref"];

		/*       FX API integration  to get exchange rate   */

        $fx_api_endpoint = $this->getParam('fx_api_endpoint');

        $exchange_api_key = $this->getParam('exchange_api_key');

        // Initialize CURL:
        $ch = curl_init($fx_api_endpoint.'?access_key='.$exchange_api_key.'&base=EUR&symbols=GHS');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
        // Store the data:
        $json = curl_exec($ch);
        curl_close($ch);
 
        // Decode JSON response:
        $exchangeRates = json_decode($json, true);
 
        
        // Access the exchange rate values, e.g. GBP:
        $rate = $exchangeRates['rates']['GHS'];

        $api_secret = $this->getParam('paystack_api_secret');

        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/$transaction_id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer ".$api_secret,
            "Cache-Control: no-cache",
            ),
        ));
        
        $result = curl_exec($curl);
    
        $err = curl_error($curl);

        curl_close($curl);

        $res = json_decode($result,true);
        
        $paymentStatus = $res["data"]["status"];
        
        $log = '';
	
        /** In case of error the log will be sent via email to the admin */
        
        $response = $paymentStatus;
        
        if($response == 'success') {
            $status->verified(1); 
            
            $amount_paid = ($res["data"]["amount"]/100)/$rate;

            $status->paid($amount_paid);

        } else {
            $status->appendLog( "Transaction Error!\n".$res);
        }
        //stop iteration
        return true;


	}

	protected function complete($res = 0) {
		

        $app = JFactory::getApplication();

        if ($res)
        {
            $url = $this->get('return_url');

            // display successful message
            $app->enqueueMessage(__('Thank you! Payment successfully received.', 'vikmypay'));
        }
        else
        {
            $url = $this->get('error_url');

            // display error message
            $app->enqueueMessage(__('It was not possible to verify the payment. Please, try again.', 'vikmypay'));
        }

        JFactory::getApplication()->redirect($url);
        exit;
	}
}
?>