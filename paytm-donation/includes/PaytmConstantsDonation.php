<?php

class PaytmConstantsDonation{
	CONST TRANSACTION_URL_PRODUCTION			       = "https://secure.paytmpayments.com/order/process";
	CONST TRANSACTION_STATUS_URL_PRODUCTION		       = "https://secure.paytmpayments.com/order/status";

	CONST TRANSACTION_URL_PRODUCTION_PPBL			   = "https://securepg.paytm.in/order/process";
	CONST TRANSACTION_STATUS_URL_PRODUCTION_PPBL	   = "https://securepg.paytm.in/order/status";	

	CONST TRANSACTION_URL_STAGING				        = "https://securestage.paytmpayments.com/order/process";
	CONST TRANSACTION_STATUS_URL_STAGING		        = "https://securestage.paytmpayments.com/order/status";

	CONST BLINKCHECKOUT_URL_STAGING				        = "https://securestage.paytmpayments.com";
	CONST BLINKCHECKOUT_URL_PRODUCTION			        = "https://secure.paytmpayments.com";
	CONST BLINKCHECKOUT_URL_PRODUCTION_PPBL				= "https://securepg.paytm.in";

	CONST PPBL =  false;

	CONST SAVE_PAYTM_RESPONSE 					= true;
	CONST CHANNEL_ID							= "WEB";
	CONST APPEND_TIMESTAMP						= true;
	CONST X_REQUEST_ID							= "PLUGIN_WORDPRESS_";
	CONST PLUGIN_VERSION_FOLDER					= "233";

	CONST MAX_RETRY_COUNT						= 3;
	CONST CONNECT_TIMEOUT						= 10;
	CONST TIMEOUT								= 10;

	CONST LAST_UPDATED							= "20250303";
	CONST PLUGIN_VERSION						= "2.3.3";
	CONST PLUGIN_DOC_URL						= "https://paytmpayments.com/docs/wordpress/";

	CONST CUSTOM_CALLBACK_URL					= "";


	CONST ID									= "paytm";
	CONST METHOD_TITLE							= "Paytm Payments";
	CONST METHOD_DESCRIPTION					= "The best payment gateway provider in India for e-payment through credit card, debit card & netbanking.";

	CONST TITLE									= "Paytm";
	CONST DESCRIPTION							= "The best payment gateway provider in India for e-payment through credit card, debit card & netbanking.";
    CONST PAYTM_PAYMENT_BUTTON_TEXT             =  "Pay With Paytm PG";
	 
	
	CONST FRONT_MESSAGE							= "Thank you for your order, please click the button below to pay with paytm.";
	CONST NOT_FOUND_TXN_URL					        = "Something went wrong. Kindly contact with us.";
	CONST PAYTM_PAY_BUTTON					        = "Pay via Paytm";
	CONST CANCEL_ORDER_BUTTON				        = "Cancel order & Restore cart";
	CONST POPUP_LOADER_TEXT					        = "Thank you for your order. We are now redirecting you to paytm to make payment.";

	CONST TRANSACTION_ID					        = "<b>Transaction ID:</b> %s";
	CONST PAYTM_ORDER_ID					        = "<b>Paytm Order ID:</b> %s";

	CONST REASON							= " Reason: %s";
	CONST FETCH_BUTTON						= "Fetch Status";

	//Success
	CONST SUCCESS_ORDER_MESSAGE				        = "Thank you for your order. Your payment has been successfully received.";
	CONST RESPONSE_SUCCESS						= "Updated <b>STATUS</b> has been fetched";
	CONST RESPONSE_STATUS_SUCCESS			       		= " and Transaction Status has been updated <b>PENDING</b> to <b>%s</b>";
	CONST RESPONSE_ERROR						= "Something went wrong. Please again'";

	//Error
	CONST PENDING_ORDER_MESSAGE					= "Your payment has been pending!";
	CONST ERROR_ORDER_MESSAGE					= "Your payment has been failed!";
	CONST ERROR_SERVER_COMMUNICATION		        	= "It seems some issue in server to server communication. Kindly connect with us.";
	CONST ERROR_CHECKSUM_MISMATCH			        	= "Security Error. Checksum Mismatched!";
	CONST ERROR_AMOUNT_MISMATCH					= "Security Error. Amount Mismatched!";
	CONST ERROR_INVALID_ORDER					= "No order found to process. Kindly contact with us.";
	CONST ERROR_CURL_DISABLED					= "cURL is not enabled properly. Please verify.";
	CONST ERROR_CURL_WARNING					= "Your server is unable to connect with us. Please contact to Paytm Support.";



}

?>
