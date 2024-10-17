<?php
require_once(__DIR__.'/PaytmConstantsDonation.php');
function paytmHelperInit() 
{
    if(!class_exists('PaytmHelperDonation')) :
        class PaytmHelperDonation 
        {

            /**
             * include timestap with order id
            **/
            public static function getPaytmOrderId($order_id)
            {
                if ($order_id && PaytmConstantsDonation::APPEND_TIMESTAMP) {
                    return $order_id . '_' . date("YmdHis");
                } else {
                    return $order_id;
                }
            }
            /**
             * exclude timestap with order id
            **/
            public static function getOrderId($order_id)
            {
                if (($pos = strrpos($order_id, '_')) !== false && PaytmConstantsDonation::APPEND_TIMESTAMP) {
                    $order_id = substr($order_id, 0, $pos);
                }
                return $order_id;
            }

            /**
             * exclude timestap with order id
            **/
            public static function getTransactionURL($isProduction = 0)
            {
                $url = isset($url) ? $url : '';
                if ($isProduction == 1) {
                    if(PaytmConstantsDonation::PPBL==false){
                        return PaytmConstantsDonation::TRANSACTION_URL_PRODUCTION . $url;
                    }                    
                    $midLength = strlen(preg_replace("/[^A-Za-z]/", "", get_option('paytm_merchant_id')));
                    if($midLength == 6){
                        return PaytmConstantsDonation::TRANSACTION_URL_PRODUCTION . $url;
                    }
                    if($midLength == 7){
                        return PaytmConstantsDonation::TRANSACTION_URL_PRODUCTION_PPBL . $url;
                    } 
                } else {
                    return PaytmConstantsDonation::TRANSACTION_URL_STAGING;
                }
            }

             /**
              * Get Initiate URL
             **/
            public static function getInitiateURL($isProduction = 0) 
            {
                $url = isset($url) ? $url : '';
                if ($isProduction == 1) {
                    if(PaytmConstantsDonation::PPBL==false){
                        return PaytmConstantsDonation::BLINKCHECKOUT_URL_PRODUCTION . $url;
                    }
                    $midLength = strlen(preg_replace("/[^A-Za-z]/", "", get_option('paytm_merchant_id')));
                    if($midLength == 6){
                        return PaytmConstantsDonation::BLINKCHECKOUT_URL_PRODUCTION . $url;
                    }
                    if($midLength == 7){
                        return PaytmConstantsDonation::BLINKCHECKOUT_URL_PRODUCTION_PPBL . $url;
                    } 
                } else {
                    return PaytmConstantsDonation::BLINKCHECKOUT_URL_STAGING;
                }
            }
            /**
             * exclude timestap with order id
            **/

            public static function getTransactionStatusURL($isProduction = 0) 
            {
                $url = isset($url) ? $url : '';
                if ($isProduction == 1) {
                    if(PaytmConstantsDonation::PPBL==false){
                        return PaytmConstantsDonation::TRANSACTION_STATUS_URL_PRODUCTION . $url;
                    }                      
                    $midLength = strlen(preg_replace("/[^A-Za-z]/", "", get_option('paytm_merchant_id')));
                    if($midLength == 6){
                        return PaytmConstantsDonation::TRANSACTION_STATUS_URL_PRODUCTION . $url;
                    }
                    if($midLength == 7){
                        return PaytmConstantsDonation::TRANSACTION_STATUS_URL_PRODUCTION_PPBL . $url;
                    } 
                } else {
                    return PaytmConstantsDonation::TRANSACTION_STATUS_URL_STAGING;
                }
            }
            /**
             * check and test cURL is working or able to communicate properly with paytm
            */
            public static function validateCurl($transaction_status_url = '') 
            {
                if (!empty($transaction_status_url) && function_exists("curl_init")) {
                    $ch = curl_init(trim($transaction_status_url));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
                    $res = curl_exec($ch);
                    curl_close($ch);
                    return $res !== false;
                }
                return false;
            }

            public static function getcURLversion()
            {
                if (function_exists('curl_version')) {
                    $curl_version = curl_version();
                    if (!empty($curl_version['version'])) {
                        return $curl_version['version'];
                    }
                }
                return false;
            }

            public static function executecUrl($apiURL, $requestParamList) 
            {

                $jsonResponse = wp_remote_post( $apiURL, array(
                    'headers' => array("Content-Type"=> "application/json"),
                    'body' => json_encode($requestParamList, JSON_UNESCAPED_SLASHES),
                    'sslverify'=>false
                ));

        //$response_code = wp_remote_retrieve_response_code( $jsonResponse );
                $response_body = wp_remote_retrieve_body($jsonResponse);
                $responseParamList = json_decode($response_body, true);
                return $responseParamList;
            }
        /** 
        * Stting up Dynamic Callback Messages
        **/
            public static function setCallbackMsgPaytm($message)
            {
                session_start();
                $_SESSION['callback_response']= $message;
            }
        /**
         * Stting up Dynamic Callback Messages
        **/
            public static function getCallbackMsgPaytm()
            {
                $msg='';
                if (isset($_SESSION['callback_response']) && $_SESSION['callback_response']!='') {
                    $msg= '<div class="box">'.htmlentities($_SESSION['callback_response']).'</div>';
                    unset($_SESSION['callback_response']);
                }
                return $msg;
            }

            public static function checkOldPaytmDonationDb()
            {
                global $wpdb;
                $oldTable = $wpdb->prefix . "paytm_donation";
                $oldLastId = '';
                if ($wpdb->get_var("SHOW TABLES LIKE '$oldTable'") == $oldTable) {
                    $oldLastOrderId = $wpdb->get_results("SELECT id FROM " . $oldTable." Order By id desc limit 1");
                    $oldLastId =  count($oldLastOrderId) > 0 ? $oldLastOrderId[0]->id +1 : '';
                }
                return $oldLastId;
            }


            public static function checkUserDataTable() {
                global $wpdb;
                $checkUserDataTable = $wpdb->prefix . "paytm_donation_user_data";
                $userDataTable = false;

                if ($wpdb->get_var("SHOW TABLES LIKE '$checkUserDataTable'") == $checkUserDataTable) {
                    $userDataTable = true;
                }
                return $userDataTable;
            }
            public static function dbUpgrade_modal()
            {
            //Echoing HTML safely start
                global $allowedposttags;
                $allowed_atts = array(
                'align'      => array(),
                'class'      => array(),
                'type'       => array(),
                'id'         => array(),
                'dir'        => array(),
                'lang'       => array(),
                'style'      => array(),
                'xml:lang'   => array(),
                'src'        => array(),
                'alt'        => array(),
                'href'       => array(),
                'rel'        => array(),
                'rev'        => array(),
                'target'     => array(),
                'novalidate' => array(),
                'type'       => array(),
                'value'      => array(),
                'name'       => array(),
                'tabindex'   => array(),
                'action'     => array(),
                'method'     => array(),
                'for'        => array(),
                'width'      => array(),
                'height'     => array(),
                'data'       => array(),
                'title'      => array(),
                );
                $allowedposttags['form']     = $allowed_atts;
                $allowedposttags['label']    = $allowed_atts;
                $allowedposttags['input']    = $allowed_atts;
                $allowedposttags['textarea'] = $allowed_atts;
                $allowedposttags['iframe']   = $allowed_atts;
                $allowedposttags['script']   = $allowed_atts;
                $allowedposttags['style']    = $allowed_atts;
                $allowedposttags['strong']   = $allowed_atts;
                $allowedposttags['small']    = $allowed_atts;
                $allowedposttags['table']    = $allowed_atts;
                $allowedposttags['span']     = $allowed_atts;
                $allowedposttags['abbr']     = $allowed_atts;
                $allowedposttags['code']     = $allowed_atts;
                $allowedposttags['pre']      = $allowed_atts;
                $allowedposttags['div']      = $allowed_atts;
                $allowedposttags['img']      = $allowed_atts;
                $allowedposttags['h1']       = $allowed_atts;
                $allowedposttags['h2']       = $allowed_atts;
                $allowedposttags['h3']       = $allowed_atts;
                $allowedposttags['h4']       = $allowed_atts;
                $allowedposttags['h5']       = $allowed_atts;
                $allowedposttags['h6']       = $allowed_atts;
                $allowedposttags['ol']       = $allowed_atts;
                $allowedposttags['ul']       = $allowed_atts;
                $allowedposttags['li']       = $allowed_atts;
                $allowedposttags['em']       = $allowed_atts;
                $allowedposttags['hr']       = $allowed_atts;
                $allowedposttags['br']       = $allowed_atts;
                $allowedposttags['tr']       = $allowed_atts;
                $allowedposttags['td']       = $allowed_atts;
                $allowedposttags['p']        = $allowed_atts;
                $allowedposttags['a']        = $allowed_atts;
                $allowedposttags['b']        = $allowed_atts;
                $allowedposttags['i']        = $allowed_atts;
                //Echoing HTML safely end		
                $databaseUpgradePop = '<div id="myModal2" class="modal">
			     <div class="modal-content">
			                    <div id="paytm_refresh_data"> 
			                        <h3>Database Upgrade Required!</h2>
			                        <p>Paytm has done certain updates in database for this version. Kindly upgrade your database.</p>
			                        <button class="refresh_history_record button-secondary" >Upgrade Now&nbsp; </button>
			                    </div>
			    </div>
			  </div>';
                echo wp_kses($databaseUpgradePop, $allowedposttags);
            }
            public function option_exists($name, $site_wide=false)
            {
                global $wpdb; 
                $table_name = $site_wide ? $wpdb->base_prefix . 'options' : $wpdb->prefix . 'options';
                $name = esc_sql($name); 
                $query = $wpdb->prepare("SELECT * FROM $table_name WHERE option_name = %s LIMIT 1", $name);
                return $result = $wpdb->get_results($query);
                //return $wpdb->query($wpdb->prepare("SELECT * FROM ". ($site_wide ? $wpdb->base_prefix : $wpdb->prefix). "options WHERE option_name ='$name' LIMIT 1"));
            }

            public static function checkValidInput($serializedata){
                $email_pattern = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
                $error_message = "";
                foreach($serializedata as $form_data){
                    $frm_val = $form_data['value'];
                    $frm_name = $form_data['name'];
                    if($frm_val != strip_tags($frm_val)){
                       $error_message = "Pleave enter a valid ".$frm_name;
                    }

                    if($frm_name == 'Email' && !preg_match($email_pattern, $frm_val)){
                        $error_message = "Please enter a valid email address";
                    }


                }
                return $error_message;
                
            }
        }
    endif;
}
?>