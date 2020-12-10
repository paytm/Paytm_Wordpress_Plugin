<?php
require_once(__DIR__.'/PaytmConstantsDonation.php');
function paytmHelperInit () {
if(!class_exists('PaytmHelperDonation')) :
class PaytmHelperDonation{

	/**
	* include timestap with order id
	*/
	public static function getPaytmOrderId($order_id){
		if($order_id && PaytmConstantsDonation::APPEND_TIMESTAMP){
			return $order_id . '_' . date("YmdHis");
		}else{
			return $order_id;
		}
	}
	/**
	* exclude timestap with order id
	*/
	public static function getOrderId($order_id){		
		if(($pos = strrpos($order_id, '_')) !== false && PaytmConstantsDonation::APPEND_TIMESTAMP) {
			$order_id = substr($order_id, 0, $pos);
		}
		return $order_id;
	}

	/**
	* exclude timestap with order id
	*/
	public static function getTransactionURL($isProduction = 0){		
		if($isProduction == 1){
			return PaytmConstantsDonation::TRANSACTION_URL_PRODUCTION;
		}else{
			return PaytmConstantsDonation::TRANSACTION_URL_STAGING;			
		}
	}
	
	/**
	* Get Initiate URL
	*/
	public static function getInitiateURL($isProduction = 0){		
		if($isProduction == 1){
			return PaytmConstantsDonation::BLINKCHECKOUT_URL_PRODUCTION;
		}else{
			return PaytmConstantsDonation::BLINKCHECKOUT_URL_STAGING;			
		}
	}
	/**
	* exclude timestap with order id
	*/
	public static function getTransactionStatusURL($isProduction = 0){
		if($isProduction == 1){
			return PaytmConstantsDonation::TRANSACTION_STATUS_URL_PRODUCTION;
		}else{
			return PaytmConstantsDonation::TRANSACTION_STATUS_URL_STAGING;			
		}
	}
	/**
	* check and test cURL is working or able to communicate properly with paytm
	*/
	public static function validateCurl($transaction_status_url = ''){		
		if(!empty($transaction_status_url) && function_exists("curl_init")){
			$ch 	= curl_init(trim($transaction_status_url));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			$res 	= curl_exec($ch);
			curl_close($ch);
			return $res !== false;
		}
		return false;
	}

	public static function getcURLversion(){		
		if(function_exists('curl_version')){
			$curl_version = curl_version();
			if(!empty($curl_version['version'])){
				return $curl_version['version'];
			}
		}
		return false;
	}

	public static function executecUrl($apiURL, $requestParamList) {

        $jsonResponse = wp_remote_post($apiURL, array(
            'headers'     => array("Content-Type"=> "application/json"),
            'body'        => json_encode($requestParamList,JSON_UNESCAPED_SLASHES),
        ));

        //$response_code = wp_remote_retrieve_response_code( $jsonResponse );
        $response_body = wp_remote_retrieve_body( $jsonResponse );
        $responseParamList = json_decode($response_body, true);
        return $responseParamList;
	}
	/*
	* Stting up Dynamic Callback Messages
	*/
	public static function setCallbackMsgPaytm($message)
	{
		session_start();
		$_SESSION['callback_response']= $message;
		/* echo $_SESSION['callback_response'];
		die(); */
	}
	/*
	* Stting up Dynamic Callback Messages
	*/
	public static function getCallbackMsgPaytm()
	{
		//session_start();
		$msg='';
		if(isset($_SESSION['callback_response']) && $_SESSION['callback_response']!='')
		{
			$msg= '<div class="box">'.htmlentities($_SESSION['callback_response']).'</div>';
			unset($_SESSION['callback_response']);
		}
		return $msg;
	}

}
endif;
}
?>