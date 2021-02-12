<?php
/**
 * Plugin Name: Paytm Payment Donation
 * Plugin URI: https://github.com/Paytm-Payments/
 * Description: This plugin allow you to accept donation payments using Paytm. This plugin will add a simple form that user will fill, when he clicks on submit he will redirected to Paytm website to complete his transaction and on completion his payment, paytm will send that user back to your website along with transactions details. This plugin uses server-to-server verification to add additional security layer for validating transactions. Admin can also see all transaction details with payment status by going to "Paytm Payment Details" from menu in admin.
 * Version: 1.0
 * Author: Paytm
 * Author URI: http://paywithpaytm.com/
 * Text Domain: Paytm Payments
 */

//ini_set('display_errors','On');
register_activation_hook(__FILE__, 'paytm_activation');
register_deactivation_hook(__FILE__, 'paytm_deactivation');
require_once(__DIR__.'/includes/PaytmHelper.php');
require_once(__DIR__.'/includes/PaytmChecksum.php');

// do not conflict with WooCommerce Paytm Plugin Callback
if(!isset($_GET["wc-api"])){
	add_action('init', 'paytm_donation_response');
}

add_shortcode( 'paytmcheckout', 'paytm_donation_handler' );
// add_action('admin_post_nopriv_paytm_donation_request','paytm_donation_handler');
// add_action('admin_post_paytm_donation_request','paytm_donation_handler');


if(isset($_GET['donation_msg']) && $_GET['donation_msg'] != ""){
	//add_action('the_content', 'paytmDonationShowMessage');
}
add_action('plugins_loaded', 'paytmHelperInit');
add_action('plugins_loaded', 'paytmChecksumInit');
function paytmDonationShowMessage($content){
	return '<div class="box">'.htmlentities(urldecode($_GET['donation_msg'])).'</div>'.$content;
}
/* Enqueue Javascript File */
function paytmDonation_enqueue_script() {   
    wp_enqueue_script( 'paytmDonation_script', plugin_dir_url( __FILE__ ) . 'assets/js/paytm-donation.js','','', true);
}
add_action('wp_enqueue_scripts', 'paytmDonation_enqueue_script');
/* Enqueue Stylesheet */
function paytmDonation_enqueue_style() {
    wp_enqueue_style('paytmDonation', plugin_dir_url( __FILE__ ) . 'assets/css/paytm-donation.css', array(), '', '');
}
add_action('wp_head', 'paytmDonation_enqueue_style');

 function getCallbackUrl(){
	if(!empty(PaytmConstantsDonation::CUSTOM_CALLBACK_URL)){
		return PaytmConstantsDonation::CUSTOM_CALLBACK_URL;
	}else{
		get_permalink();
	}	
}
		
function paytm_activation() {
	global $wpdb, $wp_rewrite;
	$settings = paytm_settings_list();
	foreach ($settings as $setting) {
		add_option($setting['name'], $setting['value']);
	}
	add_option( 'paytm_donation_details_url', '', '', 'yes' );
	$post_date = date( "Y-m-d H:i:s" );
	$post_date_gmt = gmdate( "Y-m-d H:i:s" );

	$ebs_pages = array(
		'paytm-page' => array(
			'name' => 'Paytm Transaction Details page',
			'title' => 'Paytm Transaction Details page',
			'tag' => '[paytm_donation_details]',
			'option' => 'paytm_donation_details_url'
		),
	);
	
	$newpages = false;
	
	$paytm_page_id = $wpdb->get_var("SELECT id FROM `" . $wpdb->posts . "` WHERE `post_content` LIKE '%" . $paytm_pages['paytm-page']['tag'] . "%'	AND `post_type` != 'revision'");
	if(empty($paytm_page_id)){
		$paytm_page_id = wp_insert_post( array(
			'post_title'	=>	$paytm_pages['paytm-page']['title'],
			'post_type'		=>	'page',
			'post_name'		=>	$paytm_pages['paytm-page']['name'],
			'comment_status'=> 'closed',
			'ping_status'	=>	'closed',
			'post_content' =>	$paytm_pages['paytm-page']['tag'],
			'post_status'	=>	'publish',
			'post_author'	=>	1,
			'menu_order'	=>	0
		));
		$newpages = true;
	}

	update_option( $paytm_pages['paytm-page']['option'], _get_page_link($paytm_page_id) );
	unset($paytm_pages['paytm-page']);
	
	$table_name = $wpdb->prefix . "paytm_donation";
	$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
				`id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`name` varchar(255),
				`email` varchar(255),
				`phone` varchar(255),
				`address` varchar(255),
				`city` varchar(255),
				`country` varchar(255),
				`state` varchar(255),
				`zip` varchar(255),
				`amount` varchar(255),
				`payment_status` varchar(255),
				`date` datetime
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
	$table_name_paytm = $wpdb->prefix . 'paytm_donation_order_data';
  $sql_paytm = "CREATE TABLE IF NOT EXISTS $table_name_paytm (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`order_id` int(11) NOT NULL,
			`paytm_order_id` VARCHAR(255) NOT NULL,
			`transaction_id` VARCHAR(255) NOT NULL,
			`status` ENUM('0', '1')  DEFAULT '0' NOT NULL,
			`paytm_response` TEXT,
			`date_added` DATETIME NOT NULL,
			`date_modified` DATETIME NOT NULL,
			PRIMARY KEY (`id`)
		);";			
     $wpdb->query($sql_paytm);

	if($newpages){
		wp_cache_delete( 'all_page_ids', 'pages' );
		$wp_rewrite->flush_rules();
	}
}

function paytm_deactivation() {
	$settings = paytm_settings_list();
	foreach ($settings as $setting) {
		delete_option($setting['name']);
	}
	$table_name = $wpdb->prefix . 'paytm_donation_order_data';
	$sql = "DROP TABLE IF EXISTS $table_name";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
}

function paytm_settings_list(){
	$settings = array(
		array(
			'display' => 'Merchant ID',
			'name'    => 'paytm_merchant_id',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant Id Provided by Paytm'
		),
		array(
			'display' => 'Merchant Key',
			'name'    => 'paytm_merchant_key',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant Secret Key Provided by Paytm'
		),
		array(
			'display' => 'Website Name',
			'name'    => 'paytm_website',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Website Name Provided by Paytm'
		),
		array(
			'display'			=>'Environment',
			'type'			=> 'select',
			'name'          => 'paytm_payment_environment',
			'values'		=> array("0" => "Staging", "1" => "Production"),
			'hint'	=> 'Select environment.'
		),
		array(
			'display' => 'Default Amount',
			'name'    => 'paytm_amount',
			'value'   => '100',
			'type'    => 'textbox',
			'hint'    => 'the default donation amount, WITHOUT currency signs -- ie. 100'
		),
		array(
			'display' => 'Default Button/Link Text',
			'name'    => 'paytm_content',
			'value'   => PaytmConstantsDonation::PAYTM_PAYMENT_BUTTON_TEXT,
			'type'    => 'textbox',
			'hint'    => 'the default text to be used for buttons or links if none is provided'
		),
		array(
			'display'			=>'Enable Address Fields',
			'type'			=> 'select',
			'name'          => 'paytm_enable_address',
			'values'		=> array("1" => "yes","0" => "No"),
			'hint'	=> 'Enable/Disable Address Fields'
		)			
	);
	return $settings;
}


if (is_admin()) {
	add_action( 'admin_menu', 'paytm_admin_menu' );
	add_action( 'admin_init', 'paytm_register_settings' );
}


function paytm_admin_menu() {
	add_menu_page('Paytm Donation', 'Paytm Donation', 'manage_options', 'paytm_options_page', 'paytm_options_page', plugin_dir_url(__FILE__).'images/logo.png');

	add_submenu_page('paytm_options_page', 'Paytm Donation Settings', 'Settings', 'manage_options', 'paytm_options_page');

	add_submenu_page('paytm_options_page', 'Paytm Donation Payment Details', 'Payment History', 'manage_options', 'wp_paytm_donation', 'wp_paytm_donation_listings_page');
	
	require_once(dirname(__FILE__) . '/paytm-donation-listings.php');
}


function paytm_options_page() {
	$curl_version = PaytmHelperDonation::getcURLversion();
	$paytm_payment_environment = get_option('paytm_payment_environment');
	$settingFormHtml='';
		if(empty($curl_version)){
			$settingFormHtml= '<div class="paytm_response error-box">'. PaytmConstantsDonation::ERROR_CURL_DISABLED .'</div>';
		}
		// Transaction URL is not working properly or not able to communicate with paytm
		if(!empty(PaytmHelperDonation::getTransactionStatusURL($paytm_payment_environment))){
			$response = (array)wp_remote_get(PaytmHelperDonation::getTransactionStatusURL($paytm_payment_environment));
			if(!empty($response['errors'])){
				$settingFormHtml= '<div class="paytm_response error-box">'. PaytmConstantsDonation::ERROR_CURL_WARNING .'</div>';
			}
		}
	echo	'<div class="wrap">
				<h1>Paytm Configuarations</h1>
				<form method="post" action="options.php">';
					wp_nonce_field('update-options');
					echo $settingFormHtml;
					echo '<table class="form-table">';
						$settings = paytm_settings_list();
						foreach($settings as $setting){
						echo '<tr valign="top"><th scope="row">'.$setting['display'].'</th><td>';

							if ($setting['type']=='radio') {
								echo $setting['yes'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="1" '.(get_option($setting['name']) == 1 ? 'checked="checked"' : "").' />';
								echo $setting['no'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="0" '.(get_option($setting['name']) == 0 ? 'checked="checked"' : "").' />';
		
							} elseif ($setting['type']=='select') {
								echo '<select name="'.$setting['name'].'" required="required">';
								foreach ($setting['values'] as $value=>$name) {
									echo '<option value="'.$value.'" ' .(get_option($setting['name'])==$value? '  selected="selected"' : ''). '>'.$name.'</option>';
								}
								echo '</select>';

							} else {
								echo '<input type="'.$setting['type'].'" name="'.$setting['name'].'" value="'.get_option($setting['name']).'" required="required" />';
							}

							echo '<p class="description" id="tagline-description">'.$setting['hint'].'</p>';
							echo '</td></tr>';
						}

						echo '<tr>
									<td colspan="2" align="center">
										<input type="submit" class="button-primary" value="Save Changes" />
										<input type="hidden" name="action" value="update" />';
										echo '<input type="hidden" name="page_options" value="';
										foreach ($settings as $setting) {
											echo $setting['name'].',';
										}
										echo '" />
									</td>
								</tr>

								<tr>
								</tr>
							</table>
						</form>';
			
		$last_updated = date("d F Y", strtotime(PaytmConstantsDonation::LAST_UPDATED)) .' - '.PaytmConstantsDonation::PLUGIN_VERSION;

		$footer_text = '<div style="text-align: center;"><hr/>';
		$footer_text .= '<strong>'.__('PHP Version').'</strong> '. PHP_VERSION . ' | ';
		$footer_text .= '<strong>'.__('cURL Version').'</strong> '. $curl_version . ' | ';
		$footer_text .= '<strong>'.__('Wordpress Version').'</strong> '. get_bloginfo( 'version' ) . ' | ';
		$footer_text .= '<strong>'.__('Last Updated').'</strong> '. $last_updated. ' | ';
		$footer_text .= '<a href="'.PaytmConstantsDonation::PLUGIN_DOC_URL.'" target="_blank">Developer Docs</a>';
		$footer_text .= '</div>';

		echo $footer_text;

}


function paytm_register_settings() {
	$settings = paytm_settings_list();
	foreach ($settings as $setting) {
		register_setting($setting['name'], $setting['value']);
	}
}

function paytm_donation_handler(){

	if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "paytm_donation_request"){
		return paytm_donation_form();
		//return paytm_donation_form();
	} else {
		return paytm_donation_form();
	}
}
add_action('wp', 'paytmStartSession');
function paytmStartSession() {
    if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}
}
function paytm_donation_form(){
	$current_url = "//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$paytm_enable_address = get_option('paytm_enable_address');
	$paytm_address_id= $paytm_enable_address==0?'hide-address':'';
	$html = PaytmHelperDonation::getCallbackMsgPaytm(); 
	$html .= '<form name="frmTransaction" method="post">
	<div class="paytm-pg-donar-info">
					<p>
						<label for="donor_name">Name:</label>
						<input type="text" name="donor_name" maxlength="255" value=""/>
					</p>
					<p>
						<label for="donor_email">Email:</label>
						<input type="text" name="donor_email" maxlength="255" value=""/>
					</p>
					<p>
						<label for="donor_phone">Phone:</label>
						<input type="text" name="donor_phone" maxlength="15" value=""/>
					</p>
					<p>
						<label for="donor_amount">Amount:</label>
						<input type="text" name="donor_amount" maxlength="10" value="'.trim(get_option('paytm_amount')).'"/>
					</p></div>
					<div class="paytm-pg-donar-address" id='.$paytm_address_id.'>
					<p>
						<label for="donor_address">Address:</label>
						<input type="text" name="donor_address" maxlength="255" value=""/>
					</p>
					<p>
						<label for="donor_city">City:</label>
						<input type="text" name="donor_city" maxlength="255" value=""/>
					</p>
					<p>
						<label for="donor_state">State:</label>
						<input type="text" name="donor_state" maxlength="255" value=""/>
					</p>
					<p>
						<label for="donor_postal_code">Postal Code:</label>
						<input type="text" name="donor_postal_code" maxlength="10" value=""/>
					</p>
					<p>
						<label for="donor_country">Country:</label>
						<input type="text" name="donor_country" maxlength="255" value=""/>
					</p>
					</div>
					<p>
						<input type="hidden" name="action" value="paytm_donation_request">
						<input type="submit" value="' . trim(get_option('paytm_content')) .'" id="paytm-blinkcheckout" data-action="'.admin_url( 'admin-ajax.php' ).'?action=initiate_blinkCheckout" data-id="'.get_the_ID().'" />
					</p>
				</form><script type="application/javascript" crossorigin="anonymous" src="'.PaytmHelperDonation::getInitiateURL(get_option('paytm_payment_environment')).'/merchantpgpui/checkoutjs/merchants/'.trim(get_option('paytm_merchant_id')).'.js"></script>';
	
	return $html;
}
add_action('wp_ajax_initiate_blinkCheckout','initiate_blinkCheckout');
add_action('wp_ajax_nopriv_initiate_blinkCheckout','initiate_blinkCheckout');
function initiate_blinkCheckout()
{
	extract($_REQUEST);
	$paytmParams = array();
	$txntoken = '';

	if(!empty($txnAmount) && (int)$txnAmount > 0)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . "paytm_donation";
		$data = array(
					'name' => sanitize_text_field($name),
					'email' => sanitize_email($email),
					'phone' => sanitize_text_field($phone),
					'address' => sanitize_text_field($address),
					'city' => sanitize_text_field($city),
					'country' => sanitize_text_field($country),
					'state' => sanitize_text_field($state),
					'zip' => sanitize_text_field($postalcode),
					'amount' => sanitize_text_field($txnAmount),
					'payment_status' => 'Pending Payment',
					'date' => date('Y-m-d H:i:s'),
				);

		$result = $wpdb->insert($table_name, $data);

		if(!$result){
			throw new Exception($wpdb->last_error);
		}

		$order_id = $wpdb->insert_id;
		$order_id=PaytmHelperDonation::getPaytmOrderId($order_id);
		/* body parameters */
		$paytmParams["body"] = array(
			"requestType" => "Payment",
			"mid" => trim(get_option('paytm_merchant_id')),
			"websiteName" => trim(get_option('paytm_website')),
			"orderId" => $order_id,
			"callbackUrl" => get_permalink($id),
			"txnAmount" => array(
				"value" => $txnAmount,
				"currency" => "INR",
			),
			"userInfo" => array(
				"custId" => sanitize_email($email),
			),
		);
		
		$checksum = PaytmChecksum::generateSignature(json_encode($paytmParams['body'], JSON_UNESCAPED_SLASHES),trim(get_option('paytm_merchant_key')));
		$paytmParams["head"] = array(
			"signature"	=> $checksum
		);

		$url = trim(PaytmHelperDonation::getInitiateURL(get_option('paytm_payment_environment')))."/theia/api/v1/initiateTransaction?mid=".$paytmParams["body"]['mid']."&orderId=".$paytmParams["body"]['orderId'];
		$res= PaytmHelperDonation::executecUrl($url,$paytmParams);
		if(!empty($res['body']['resultInfo']['resultStatus']) && $res['body']['resultInfo']['resultStatus'] == 'S'){
			$txntoken = $res['body']['txnToken'];
		}

	}

	if(!empty($txntoken)){
		echo json_encode(array('success'=> true,'txnToken' => $txntoken, 'txnAmount' => $txnAmount, 'orderId' =>$paytmParams["body"]['orderId'] ));
	}else{
		echo json_encode(array('success'=> false,'txnToken' => '','data'=>$res));
	}
	die();
}

function paytm_donation_meta_box() {
	$screens = array( 'paytmcheckout' );
	
	foreach ( $screens as $screen ) {
		add_meta_box(  'myplugin_sectionid', __( 'Paytm', 'myplugin_textdomain' ),'paytm_donation_meta_box_callback', $screen, 'normal','high' );
	}
}

add_action( 'add_meta_boxes', 'paytm_donation_meta_box' );

function paytm_donation_meta_box_callback($post) {
	echo "admin";
}

function paytm_donation_response(){
	
	if(! empty($_POST) && isset($_POST['ORDERID'])){

		global $wpdb;

		$paytm_merchant_key = trim(get_option('paytm_merchant_key'));
		$paytm_merchant_id = trim(get_option('paytm_merchant_id'));
		if(!empty($_POST['CHECKSUMHASH'])){
			$post_checksum = $_POST['CHECKSUMHASH'];
			unset($_POST['CHECKSUMHASH']);	
		}else{
			$post_checksum = "";
		}
		 $transaction_status_url = trim(PaytmHelperDonation::getTransactionStatusURL(get_option('paytm_payment_environment')));
		 if(PaytmChecksum::verifySignature($_POST, $paytm_merchant_key, $post_checksum) === true) {
					$order_id = !empty($_POST['ORDERID'])? PaytmHelperDonation::getOrderId($_POST['ORDERID']) : 0;

					/* save paytm response in db */
					if(PaytmConstantsDonation::SAVE_PAYTM_RESPONSE && !empty($_POST['STATUS'])){
						$order_data_id = saveTxnResponse1($_POST, PaytmHelperDonation::getOrderId($_POST['ORDERID']));
					}
					/* save paytm response in db */
			if($order_id){

				// Create an array having all required parameters for status query.
				$requestParamList = array("MID" => $paytm_merchant_id, "ORDERID" => $_POST['ORDERID']);
				
				$StatusCheckSum = PaytmChecksum::generateSignature($requestParamList, $paytm_merchant_key);

				$requestParamList['CHECKSUMHASH'] = $StatusCheckSum;
				
				/* number of retries untill cURL gets success */
				$retry = 1;
				do{
					$responseParamList = PaytmHelperDonation::executecUrl($transaction_status_url, $requestParamList);
					$retry++;
				} while(!$responseParamList['STATUS'] && $retry < PaytmConstantsDonation::MAX_RETRY_COUNT);
				/* number of retries untill cURL gets success */
				if(!isset($responseParamList['STATUS'])){
					$responseParamList = $_POST;
				}

				/* save paytm response in db */
				if(PaytmConstantsDonation::SAVE_PAYTM_RESPONSE && !empty($responseParamList['STATUS'])){
					saveTxnResponse1($responseParamList, PaytmHelperDonation::getOrderId($responseParamList['ORDERID']), $order_data_id);
				}
				/* save paytm response in db */
			
			

				if($responseParamList['STATUS'] == 'TXN_SUCCESS' && $responseParamList['TXNAMOUNT'] == $_POST['TXNAMOUNT']) {
					$msg = "Thank you for your order. Your transaction has been successful.";
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Complete Payment' WHERE  id = %d", sanitize_text_field($_POST['ORDERID'])));
				
				} else  {
					//$msg = "It seems some issue in server to server communication. Kindly connect with administrator.";
					$msg = "Thank You. However, the transaction has been Failed For Reason: " . sanitize_text_field($_POST['RESPMSG']);
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Payment failed' WHERE id = %d", sanitize_text_field($_POST['ORDERID'])));
				}

			} else {
				$msg = "Thank You. However, the transaction has been Failed For Reason: " . sanitize_text_field($_POST['RESPMSG']);
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Cancelled Payment' WHERE id = %d", sanitize_text_field($_POST['ORDERID'])));
			}
		} else {
			$msg = "Security error!";
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Payment Error' WHERE  id = %d", sanitize_text_field($_POST['ORDERID'])));
		}
		

		//$redirect_url = get_site_url() . '/' . get_permalink(get_the_ID());
		$redirect_url = get_permalink(get_the_ID());
		//echo $redirect_url ."<br />";
		PaytmHelperDonation::setCallbackMsgPaytm($msg);

		$redirect_url = add_query_arg( array());
		wp_redirect( $redirect_url,301 );
		exit;
	}
}


/*
* Code to test Curl
*/
if(isset($_GET['paytm_action']) && $_GET['paytm_action'] == "curltest"){
	add_action('the_content', 'curltest_donation');
}

function curltest_donation($content){

	// phpinfo();exit;
	$debug = array();

	if(!function_exists("curl_init")){
		$debug[0]["info"][] = "cURL extension is either not available or disabled. Check phpinfo for more info.";

	// if curl is enable then see if outgoing URLs are blocked or not
	} else {

		// if any specific URL passed to test for
		if(isset($_GET["url"]) && $_GET["url"] != ""){
			$testing_urls = array(esc_url_raw($_GET["url"]));
		
		} else {

			// this site homepage URL
			$server = get_site_url();

			$testing_urls = array(
											$server,
											"https://www.gstatic.com/generate_204",
											PaytmHelperDonation::getTransactionStatusURL(get_option('paytm_payment_environment'))
										);
		}

		// loop over all URLs, maintain debug log for each response received
		foreach($testing_urls as $key=>$url){

			$debug[$key]["info"][] = "Connecting to <b>" . $url . "</b> using cURL";
			
			$response = wp_remote_get($url);

			if ( is_array( $response ) ) {

				$http_code = wp_remote_retrieve_response_code($response);
				$debug[$key]["info"][] = "cURL executed succcessfully.";
				$debug[$key]["info"][] = "HTTP Response Code: <b>". $http_code . "</b>";

				// $debug[$key]["content"] = $res;

			} else {
				$debug[$key]["info"][] = "Connection Failed !!";
				$debug[$key]["info"][] = "Error: <b>" . $response->get_error_message() . "</b>";
				break;
			}
		}
	}

	$content = "<center><h1>cURL Test for Paytm Donation Plugin</h1></center><hr/>";
	foreach($debug as $k=>$v){
		$content .= "<ul>";
		foreach($v["info"] as $info){
			$content .= "<li>".$info."</li>";
		}
		$content .= "</ul>";

		// echo "<div style='display:none;'>" . $v["content"] . "</div>";
		$content .= "<hr/>";
	}

	return $content;
}
/*
* Code to test Curl
*/
/**
	* save response in db
	*/
	function saveTxnResponse1($data  = array(),$order_id, $id = false){
		global $wpdb;
		if(empty($data['STATUS'])) return false;
		
		$status 			= (!empty($data['STATUS']) && $data['STATUS'] =='TXN_SUCCESS') ? 1 : 0;
		$paytm_order_id 	= (!empty($data['ORDERID'])? $data['ORDERID']:'');
		$transaction_id 	= (!empty($data['TXNID'])? $data['TXNID']:'');
		
		if($id !== false){
			$sql =  "UPDATE `" . $wpdb->prefix . "paytm_donation_order_data` SET `order_id` = '" . $order_id . "', `paytm_order_id` = '" . $paytm_order_id . "', `transaction_id` = '" . $transaction_id . "', `status` = '" . (int)$status . "', `paytm_response` = '" . json_encode($data) . "', `date_modified` = NOW() WHERE `id` = '" . (int)$id . "' AND `paytm_order_id` = '" . $paytm_order_id . "'";
			$wpdb->query($sql);
			return $id;
		}else{
			$sql =  "INSERT INTO `" . $wpdb->prefix . "paytm_donation_order_data` SET `order_id` = '" . $order_id . "', `paytm_order_id` = '" . $paytm_order_id . "', `transaction_id` = '" . $transaction_id . "', `status` = '" . (int)$status . "', `paytm_response` = '" . json_encode($data) . "', `date_added` = NOW(), `date_modified` = NOW()";
			$wpdb->query($sql);
			return $wpdb->insert_id;
		}
	}