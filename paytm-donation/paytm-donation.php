<?php
/**
 * Plugin Name: Paytm Payment Donation
 * Plugin URI: https://business.paytm.com/docs/wordpress/
 * Description: This plugin allow you to accept donation payments using Paytm. This plugin will add a simple form that user will fill, when he clicks on submit he will redirected to Paytm website to complete his transaction and on completion his payment, paytm will send that user back to your website along with transactions details. This plugin uses server-to-server verification to add additional security layer for validating transactions. Admin can also see all transaction details with payment status by going to "Paytm Payment Details" from menu in admin.
 * Version: 2.0
 * Author: Paytm
 * Author URI: https://business.paytm.com/payment-gateway
 * Text Domain: Paytm Payments
 */

register_activation_hook(__FILE__, 'paytm_activation');
register_deactivation_hook(__FILE__, 'paytm_deactivation');
require_once(__DIR__.'/includes/PaytmHelper.php');
require_once(__DIR__.'/includes/PaytmChecksum.php');

// do not conflict with WooCommerce Paytm Plugin Callback
if(!isset($_GET["wc-api"])){
	add_action('init', 'paytm_donation_response');
}

add_shortcode( 'paytmcheckout', 'paytm_donation_handler' );

// if(isset($_GET['donation_msg']) && $_GET['donation_msg'] != ""){
// 	//add_action('the_content', 'paytmDonationShowMessage');
// }
add_action('plugins_loaded', 'paytmHelperInit');
add_action('plugins_loaded', 'paytmChecksumInit');
// function paytmDonationShowMessage($content){
// 	return '<div class="box">'.htmlentities(urldecode($_GET['donation_msg'])).'</div>'.$content;
// }
/* Enqueue Javascript File */
function paytmDonation_enqueue_script() {   
    wp_enqueue_script( 'paytmDonation_script', plugin_dir_url( __FILE__ ) . 'assets/'.PaytmConstantsDonation::PLUGIN_VERSION_FOLDER.'/js/paytm-donation.js','','', true);
}
function paytmDonationAdmin_enqueue_script() {   
    wp_enqueue_script( 'paytmDonationAdmin_script', plugin_dir_url( __FILE__ ) . 'assets/'.PaytmConstantsDonation::PLUGIN_VERSION_FOLDER.'/js/admin/paytm-donation-admin.js','','', false);
}
add_action('wp_enqueue_scripts', 'paytmDonation_enqueue_script');
add_action('admin_enqueue_scripts', 'paytmDonationAdmin_enqueue_script');

/* Enqueue Stylesheet */
function paytmDonation_enqueue_style() {
    wp_enqueue_style('paytmDonation', plugin_dir_url( __FILE__ ) . 'assets/'.PaytmConstantsDonation::PLUGIN_VERSION_FOLDER.'/css/paytm-donation.css', array(), '', '');
}
add_action('wp_head', 'paytmDonation_enqueue_style');

function paytmUserField_enqueue_style() {
    wp_enqueue_style('paytmUserField', plugin_dir_url( __FILE__ ) . 'assets/'.PaytmConstantsDonation::PLUGIN_VERSION_FOLDER.'/css/admin/paytm-donation-admin.css', array(), '', '');
}
add_action('admin_enqueue_scripts','paytmUserField_enqueue_style');
 

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
		if(isset($setting['value'])){
			add_option($setting['name'], $setting['value']);
		}
	}
	add_option( 'paytm_donation_details_url', '', '', 'yes' );
	$paytm_enable_address = trim(get_option('paytm_enable_address'));
	$myObj['mytext'][] = "Name";
	$myObj['mytext'][] = "Email";
	$myObj['mytext'][] = "Phone";
	$myObj['mytext'][] = "Amount";
	$myObj['mytype'][] = "text";
	$myObj['mytype'][] = "text";
	$myObj['mytype'][] = "text";
	$myObj['mytype'][] = "text";
	$myObj['myvalue'][] = "";
	$myObj['myvalue'][] = "";
	$myObj['myvalue'][] = "";
	$myObj['myvalue'][] = "100";
	if($paytm_enable_address==1){
		$myObj['mytext'][] = "city";
		$myObj['mytext'][] = "country";
		$myObj['mytext'][] = "state";
		$myObj['mytext'][] = "zip";
		$myObj['mytext'][] = "address";	
		$myObj['mytype'][] = "text";
		$myObj['mytype'][] = "text";
		$myObj['mytype'][] = "text";
		$myObj['mytype'][] = "text";
		$myObj['mytype'][] = "text";	
		$myObj['myvalue'][] = "";
		$myObj['myvalue'][] = "";
		$myObj['myvalue'][] = "";
		$myObj['myvalue'][] = "";
		$myObj['myvalue'][] = "";
	}
	$myJSON = json_encode($myObj);	
	add_option('paytm_user_field', $myJSON);
	$post_date = date( "Y-m-d H:i:s" );
	$post_date_gmt = gmdate( "Y-m-d H:i:s" );

	update_option( $paytm_pages['paytm-page']['option'], _get_page_link($paytm_page_id) );
	unset($paytm_pages['paytm-page']);

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
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

	 $oldTable = $wpdb->prefix . "paytm_donation";
	 $oldLastId = 1;
	 if($wpdb->get_var("SHOW TABLES LIKE '$oldTable'") == $oldTable) {
		 $oldLastOrderId = $wpdb->get_results("SELECT id FROM " . $oldTable." Order By id desc limit 1");
		 $oldLastId =  count($oldLastOrderId) > 0 ? $oldLastOrderId[0]->id +1 : 1;
   }

	 $table_name_paytm_custom = $wpdb->prefix . 'paytm_donation_user_data';
	 $sql_paytm_custom_data = "CREATE TABLE IF NOT EXISTS $table_name_paytm_custom (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`custom_data` TEXT NOT NULL,
		`payment_status` varchar(255),
		`date` datetime,
		PRIMARY KEY (`id`)
	)AUTO_INCREMENT=$oldLastId;";			
 $wpdb->query($sql_paytm_custom_data);	     

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

	/*------- website name code sart -------*/

	    $isWebsiteAddedDonation = get_option('isWebsiteAddedDonation');
        $getPaytmWebsite = get_option('paytm_website');
        $website = isset($getPaytmWebsite)?$getPaytmWebsite:"";
        $websiteOption=array('WEBSTAGING'=>'WEBSTAGING','DEFAULT'=>'DEFAULT');

          
        if ($isWebsiteAddedDonation=="") {
            // Old plugin Data, Need to handle previous Website Name
            add_option("isWebsiteAddedDonation", "yes");
            if (!in_array($website, $websiteOption) and $website!="") {
                $websiteOption[$website]=$website; 
            }	
            $websiteOption['OTHERS'] = 'OTHERS' ;
            add_option('websiteOptionDonation', json_encode($websiteOption));
        }
    $websiteOptionFromDB = json_decode(get_option('websiteOptionDonation'), true);

    $webhookUrl = esc_url(get_site_url() . '/?webhook=yes');
    $paytmDashboardLink = esc_url("https://dashboard.paytm.com/next/apikeys");
    $paytmPaymentStatusLink = esc_url("https://developer.paytm.com/docs/payment-status/"); 
    $dashboardWebhookUrl = esc_url("https://dashboard.paytm.com/next/webhook-url");

	$settings = array(
		array(
			'display'		=>'Environment',
			'type'			=> 'select',
			'name'          => 'paytm_payment_environment',
			'values'		=> array("0" => "Test/Staging", "1" => "Production"),
			'hint'	=> 'Select "Test/Staging" to setup test transactions & "Production" once you are ready to go live.'
		),		
		array(
			'display' => 'Test/Production MID',
			'name'    => 'paytm_merchant_id',
			'value'   => '',
			'type'    => 'text',
			'hint'    => 'Based on the selected Environment Mode, copy the relevant Merchant ID for test or production environment available on <a href="'.$paytmDashboardLink.'" target="_blank">Paytm dashboard</a>.'
		),
		array(
			'display' => 'Test/Production Secret Key',
			'name'    => 'paytm_merchant_key',
			'value'   => '',
			'type'    => 'text',
			'hint'    => 'Based on the selected Environment Mode, copy the Merchant Key for test or production environment available on <a href="'.$paytmDashboardLink.'" target="_blank">Paytm dashboard</a>.'
		),
		array(
			'display' => 'Website(Provided by Paytm)',
			'name'    => 'paytm_website',
			'values'   => $websiteOptionFromDB,
			'type'    => 'select',
			'hint'    => 'Select "WEBSTAGING" for test/staging environment & "DEFAULT" for production environment.'
		),
		array(
			'display' => '',
			'name'    => 'paytm_websiteOther',
			'values'   => '',
			'type'    => 'text',
			'hint'    => '<span class="otherWebsiteName-error-message" style="color:red"></span>'
		),
		array(
			'display' => 'Default Button/Link Text',
			'name'    => 'paytm_content',
			'value'   => PaytmConstantsDonation::PAYTM_PAYMENT_BUTTON_TEXT,
			'type'    => 'text',
			'hint'    => 'The default text to be used for buttons or links if none is provided.'
		),
		array(
            'display' => 'Enable Webhook',
            'type'	  => 'select',
            'name'    => 'is_webhook',
            'hint'    =>  "Enable Paytm Webhook <a href='".$dashboardWebhookUrl."'>here</a> with the URL listed below.<br><span>".$webhookUrl."</span><br/><br/>Instructions and guide to <a href='".$paytmPaymentStatusLink."'>Paytm webhooks</a>",
            'values'  => array("yes" => "Yes","no" => "No"),

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

	add_submenu_page('paytm_options_page', 'Paytm Donation Donation Field Details', 'Edit Fields', 'manage_options', 'wp_paytm_donation_user_field_page', 'wp_paytm_donation_user_field_page');

	require_once(dirname(__FILE__) . '/paytm-donation-user-field.php');	
}


function paytm_options_page() {

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
        $allowedposttags['select']        = $allowed_atts;
        $allowedposttags['option']        = $allowed_atts;



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


$paytmConfig = '<div class="wrap">
				<h1>Paytm Configurations</h1>
				<form method="post" action="options.php">';
			echo wp_kses($paytmConfig, $allowedposttags);

					wp_nonce_field('update-options');
					echo $settingFormHtml;
					  
			echo wp_kses('<table class="form-table">', $allowedposttags);
						$settings = paytm_settings_list();
						foreach($settings as $setting){ 
			  echo wp_kses('<tr valign="top"><th scope="row">'.$setting['display'].'</th><td>', $allowedposttags);
							if ($setting['type']=='radio') { 
					             echo wp_kses($setting['yes'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="1" '.(get_option($setting['name']) == 1 ? 'checked="checked"' : "").' />', $allowedposttags);
								 

							    echo wp_kses($setting['no'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="0" '.(get_option($setting['name']) == 0 ? 'checked="checked"' : "").' />', $allowedposttags);	 
								 
		
							} elseif ($setting['type']=='select') {
								echo '<select name="'.$setting['name'].'" required="required">' ;
								foreach ($setting['values'] as $value=>$name) {

								echo '<option value="'.$value.'" ' .(get_option($setting['name'])==$value? '  selected="selected"' : ''). '>'.$name.'</option>';
									 
								}
								 
								echo  '</select>' ;

							} else { 

								echo wp_kses('<input type="'.$setting['type'].'" name="'.$setting['name'].'" value="'.get_option($setting['name']).'" required="required" />', $allowedposttags);

							}
							echo wp_kses('<p class="description" id="tagline-description">'.$setting['hint'].'</p>', $allowedposttags);
							echo wp_kses('</td></tr>', $allowedposttags); 
						}

						echo  '<tr>
									<td>&nbsp;</td>
									<td colspan="2">
										<input id="savePaytmConfiguration" type="submit" class="button-primary" value="Save Changes" />
										<input id="updatePaytmConfiguration" type="hidden" name="action" value="update" />';
										echo '<input type="hidden" name="page_options" value="';
										foreach ($settings as $setting) {
											echo $setting['name'].',';
										}
									$tableEnd .= '" />
									</td>
								</tr>

								<tr>
								</tr>
							</table>
						</form>'; 
		     echo wp_kses($tableEnd, $allowedposttags); 

		$last_updated = date("d F Y", strtotime(PaytmConstantsDonation::LAST_UPDATED)) .' - '.PaytmConstantsDonation::PLUGIN_VERSION;

		$footer_text = '<div style="text-align: center;"><hr/>';
		$footer_text .= '<strong>'.__('PHP Version').'</strong> '. PHP_VERSION . ' | ';
		$footer_text .= '<strong>'.__('cURL Version').'</strong> '. $curl_version . ' | ';
		$footer_text .= '<strong>'.__('Wordpress Version').'</strong> '. get_bloginfo( 'version' ) . ' | ';
		$footer_text .= '<strong>'.__('Last Updated').'</strong> '. $last_updated. ' | ';
		$footer_text .= '<a href="'.PaytmConstantsDonation::PLUGIN_DOC_URL.'" target="_blank">Developer Docs</a>';
		$footer_text .= '</div>';

		 
		echo wp_kses($footer_text, $allowedposttags); 
		echo isset($_GET["settings-updated"]) ? '<script>alert("Record Updated Successfully!")</script>' : '';
		//dynamic script
		 echo wp_kses('<script type="text/javascript"> paytmDonationJs();</script>', $allowedposttags);
		    PaytmHelperDonation::dbUpgrade_modal();
		echo '<script>
			jQuery(".refresh_history_record").on("click", function() {
			    var ajax_url = "';
			    echo admin_url( 'admin-ajax.php' );
			    echo '?action=refresh_Paytmhistory";
			    $(".refresh_history_record").prop("disabled", true);
			        jQuery.ajax({
			            //  data: data,
			            method: "POST",
			            url: ajax_url,
			            dataType: "JSON",
			            success: function(result) {
			                console.log(result); //should print out the name since we sent it along
			            }
			        });
			        setTimeout(function(){window.location.reload(true);}, 1000);
			     
			});
			var modal2 = document.getElementById("myModal2");';
			$oldLastId = PaytmHelperDonation::checkOldPaytmDonationDb();
		    if ($oldLastId!=''){
		    	echo 'modal2.style.display = "block";';
		    }
			echo 'var modal2 = document.getElementById("myModal2");
			</script>';

	 

}


function paytm_register_settings() {
	$settings = paytm_settings_list();
	foreach ($settings as $setting) {
		if(isset($setting['value'])){	
		 	register_setting($setting['name'], $setting['value']);
		}
		
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
    global $wpdb;
    $customFieldRecord = $wpdb->get_results("SELECT option_value FROM " . $wpdb->prefix . "options where option_name = 'paytm_user_field'");
    $decodeCustomFieldRecord = json_decode(json_encode($customFieldRecord[0]));
	$decodeCustomFieldRecordArray = (json_decode($decodeCustomFieldRecord->option_value));
	$dynamic_html = '';
	foreach($decodeCustomFieldRecordArray->mytext as $key => $value):
		if ($decodeCustomFieldRecordArray->mytype[$key]=='text'){
			$dynamic_html .= 
				'<p>
					<label for="'.$value.'">'.$value.':</label>
					<input type="text" name="'.str_replace(' ', '_', $value).'" maxlength="255" value="'.$decodeCustomFieldRecordArray->myvalue[$key].'"/>
				</p>';
		}
		if ($decodeCustomFieldRecordArray->mytype[$key]=='dropdown'){
			$dynamic_dropdown = explode(',', $decodeCustomFieldRecordArray->myvalue[$key]);
			$dynamic_html .= 
				'<p>
					<label for="'.$value.'">'.$value.':</label>
					<select name="'.str_replace(' ', '_', $value).'" class="dropdown">
						<option value="">Please select</option>';
						foreach($dynamic_dropdown as $dynamic_value):
							$dynamic_html .=  '<option value="'.$dynamic_value.'" >'.$dynamic_value.'</option>';
						endforeach;
						$dynamic_html .='
					</select>
				</p>';
		}
		if ($decodeCustomFieldRecordArray->mytype[$key]=='radio'){
			$dynamic_radio = explode(',', $decodeCustomFieldRecordArray->myvalue[$key]);
			$dynamic_html .= 
				'<p>
					<label for="'.$value.'">'.$value.':</label>';
					foreach($dynamic_radio as $dynamic_radio_value):
						$dynamic_html .=  '<input type="radio" name="'.str_replace(' ', '_', $value).'" value="'.$dynamic_radio_value.'">'.$dynamic_radio_value.'';
					endforeach;
					$dynamic_html .='</p>';
		}				
	endforeach;
	// echo $dynamic_html;

	$current_url = esc_url("//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);	
	$html = PaytmHelperDonation::getCallbackMsgPaytm(); 
	$plugin_data = array();//get_plugin_data( __FILE__ );
	$html .= '<form name="frmTransaction" method="post">
	<div class="paytm-pg-donar-info">'
					.$dynamic_html.
					'</div>

					<p>
						<input type="hidden" name="action" value="paytm_donation_request">
						<input type="submit" value="' . trim(get_option('paytm_content')) .'" id="paytm-blinkcheckout" data-wpversion="'.get_bloginfo( 'version' ).'" data-pversion="'.PaytmConstantsDonation::PLUGIN_VERSION.'" data-action="'.admin_url( 'admin-ajax.php' ).'?action=initiate_blinkCheckout" data-id="'.get_the_ID().'" />
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

		$serializedata = (json_encode($serializedata));
		$decode = json_decode($serializedata);
			unset($decode[count($decode)-1]);//removing action =  paytm_donation_request which is last element
		$serializedata_final = json_encode($decode);

		$table_name_custom = $wpdb->prefix . "paytm_donation_user_data";
		$custom_data = [
			'custom_data' => ($serializedata_final),
			'payment_status' => 'Pending Payment',
			'date' => date('Y-m-d H:i:s'),			
		];
		$result_custom = $wpdb->insert($table_name_custom, $custom_data);
		if(!$result_custom){
			throw new Exception($wpdb->last_error);
		}

		$order_id = $wpdb->insert_id;
		$order_id=PaytmHelperDonation::getPaytmOrderId($order_id);

		if (get_option('paytm_websiteOther') == "") {
                $website = trim(get_option('paytm_website'));
            } else {
                $website = trim(get_option('paytm_websiteOther'));
            }

		/* body parameters */
		$paytmParams["body"] = array(
			"requestType" => "Payment",
			"mid" => trim(get_option('paytm_merchant_id')),
			"websiteName" => $website,
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
	global $wpdb;

	if(! empty($_POST) && isset($_POST['ORDERID'])){
		

		$paytm_merchant_key = trim(get_option('paytm_merchant_key'));
		$paytm_merchant_id = trim(get_option('paytm_merchant_id'));
		if(!empty($_POST['CHECKSUMHASH'])){
			$post_checksum = sanitize_text_field($_POST['CHECKSUMHASH']);
			unset($_POST['CHECKSUMHASH']);	
		}else{
			$post_checksum = "";
		}
		 $transaction_status_url = trim(PaytmHelperDonation::getTransactionStatusURL(get_option('paytm_payment_environment')));
		 if(PaytmChecksum::verifySignature($_POST, $paytm_merchant_key, $post_checksum) === true) {
					$order_id = !empty($_POST['ORDERID'])? PaytmHelperDonation::getOrderId(sanitize_text_field($_POST['ORDERID'])) : 0;

					/* save paytm response in db */
					if(PaytmConstantsDonation::SAVE_PAYTM_RESPONSE && !empty($_POST['STATUS'])){
						$order_data_id = saveTxnResponse1(
											sanitize_text_field($_POST), 
											PaytmHelperDonation::getOrderId(sanitize_text_field($_POST['ORDERID'])));
					}
					/* save paytm response in db */
			if($order_id){

				// Create an array having all required parameters for status query.
				$requestParamList = array("MID" => $paytm_merchant_id, "ORDERID" => sanitize_text_field($_POST['ORDERID']));
				
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
					$responseParamList = sanitize_text_field($_POST);
				}

				/* save paytm response in db */
				if(PaytmConstantsDonation::SAVE_PAYTM_RESPONSE && !empty($responseParamList['STATUS'])){
					saveTxnResponse1($responseParamList, PaytmHelperDonation::getOrderId($responseParamList['ORDERID']), $order_data_id);
				}
				/* save paytm response in db */
			
			

				if($responseParamList['STATUS'] == 'TXN_SUCCESS' && $responseParamList['TXNAMOUNT'] == sanitize_text_field($_POST['TXNAMOUNT'])) {
					$msg = "Thank you for your order. Your transaction has been successful.";
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation_user_data SET payment_status = 'Complete Payment' WHERE  id = %d", sanitize_text_field($_POST['ORDERID'])));
				
				} else  {
					//$msg = "It seems some issue in server to server communication. Kindly connect with administrator.";
					$msg = "Thank You. However, the transaction has been Failed For Reason: " . sanitize_text_field($_POST['RESPMSG']);
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation_user_data SET payment_status = 'Payment failed' WHERE id = %d", sanitize_text_field($_POST['ORDERID'])));
				}

			} else {
				$msg = "Thank You. However, the transaction has been Failed For Reason: " . sanitize_text_field($_POST['RESPMSG']);
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation_user_data SET payment_status = 'Cancelled Payment' WHERE id = %d", sanitize_text_field($_POST['ORDERID'])));
			}
		} else {
			$msg = "Security error!";
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix . "paytm_donation_user_data SET payment_status = 'Payment Error' WHERE  id = %d", sanitize_text_field($_POST['ORDERID'])));
		}
		if (isset($_GET['webhook']) && $_GET['webhook'] =='yes') { 
			 echo wp_kses("Webhook Received", $allowedposttags);
			 exit;
		}
		$redirect_url = get_permalink(get_the_ID());
		PaytmHelperDonation::setCallbackMsgPaytm($msg);
		$redirect_url = add_query_arg( array());
		wp_redirect( $redirect_url,301 );
		exit;
	}


	// Start Auto create table
		if (!(new PaytmHelperDonation)->option_exists("paytm_user_field")) {
		$paytm_enable_address = trim(get_option('paytm_enable_address'));
		$myObj['mytext'][] = "Name";
		$myObj['mytext'][] = "Email";
		$myObj['mytext'][] = "Phone";
		$myObj['mytext'][] = "Amount";
		$myObj['mytype'][] = "text";
		$myObj['mytype'][] = "text";
		$myObj['mytype'][] = "text";
		$myObj['mytype'][] = "text";
		$myObj['myvalue'][] = "";
		$myObj['myvalue'][] = "";
		$myObj['myvalue'][] = "";
		$myObj['myvalue'][] = "100";
		if($paytm_enable_address==1){
			$myObj['mytext'][] = "city";
			$myObj['mytext'][] = "country";
			$myObj['mytext'][] = "state";
			$myObj['mytext'][] = "zip";
			$myObj['mytext'][] = "address";	
			$myObj['mytype'][] = "text";
			$myObj['mytype'][] = "text";
			$myObj['mytype'][] = "text";
			$myObj['mytype'][] = "text";
			$myObj['mytype'][] = "text";	
			$myObj['myvalue'][] = "";
			$myObj['myvalue'][] = "";
			$myObj['myvalue'][] = "";
			$myObj['myvalue'][] = "";
			$myObj['myvalue'][] = "";
		}
		$myJSON = json_encode($myObj);				
	      add_option('paytm_user_field', $myJSON);
	     $post_date = date( "Y-m-d H:i:s" );
	 }
	 $oldTable = $wpdb->prefix . "paytm_donation";
	 $backupTable = $wpdb->prefix . "paytm_donation_backup";
	 $oldLastId = 1;


	 if($wpdb->get_var("SHOW TABLES LIKE '$oldTable'") == $oldTable) {
		 $oldLastOrderId = $wpdb->get_results("SELECT id FROM " . $oldTable." Order By id desc limit 1");
		 $oldLastId =  count($oldLastOrderId) > 0 ? $oldLastOrderId[0]->id +1 : 1;
   }

	 $table_name_paytm_custom = $wpdb->prefix . 'paytm_donation_user_data';
	 $sql_paytm_custom_data = "CREATE TABLE IF NOT EXISTS $table_name_paytm_custom (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`custom_data` TEXT NOT NULL,
		`payment_status` varchar(255),
		`date` datetime,
		PRIMARY KEY (`id`)
	)AUTO_INCREMENT=$oldLastId;";			
 $wpdb->query($sql_paytm_custom_data);

	//End Auto create table

	//Refresh data start
 	$checkBackupable = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $backupTable ) );
 	if($wpdb->get_var("SHOW TABLES LIKE '$oldTable'") == $oldTable) {

	 	if (! $wpdb->get_var( $checkBackupable ) == $backupTable ) {
	    	refresh_Paytmhistory();
		}
}

	//Refresh data end	

}


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


    add_action('wp_ajax_initiate_paytmCustomFieldSave','initiate_paytmCustomFieldSave');
    add_action('wp_ajax_nopriv_initiate_paytmCustomFieldSave','initiate_paytmCustomFieldSave');

    function initiate_paytmCustomFieldSave(){
    echo json_encode($_POST);
	update_option('paytm_user_field', json_encode($_POST));
    wp_die();
    }	

	add_action('wp_ajax_refresh_Paytmhistory','refresh_Paytmhistory');

	function refresh_Paytmhistory(){
		global $wpdb;

		$oldPaytmHistoryData = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "paytm_donation");
		$dataArray = '';
		$table_name_custom = $wpdb->prefix . "paytm_donation_user_data";
		$result_custom = "";	
         
		if(!empty(PaytmHelperDonation::checkOldPaytmDonationDb())){
			
				foreach($oldPaytmHistoryData as $key=>$value):

				$jsonArray = [
					["name" => "name","value" => $value->name],
					["name" => "email","value" => $value->email],
					["name" => "phone","value" => $value->phone],
					["name" => "amount","value" => $value->amount],
					["name" => "city","value" => $value->city],
					["name" => "country","value" => $value->country],
					["name" => "state","value" => $value->state],
					["name" => "zip","value" => $value->zip],
					["name" => "address","value" => $value->address]
				];

				$dataArray = json_encode($jsonArray);
				$id = $value->id;
				$paymentStatus = $value->payment_status;
				$date = $value->date;

				$custom_data = [
					'id' => $id,
					'custom_data' => $dataArray,
					'payment_status' => $paymentStatus,
					'date' => $date,			
				];

				if(PaytmHelperDonation::checkUserDataTable()==true){
					$result_custom = $wpdb->insert($table_name_custom, $custom_data);			
				}
					
			endforeach;

			if(!$result_custom){
			throw new Exception($wpdb->last_error);
			}		
			$table_name = $wpdb->prefix . 'paytm_donation';
			$new_table_name = $wpdb->prefix . 'paytm_donation_backup';
			$wpdb->query( "ALTER TABLE $table_name RENAME TO $new_table_name" );
		}
		 
		
		wp_redirect($_SERVER['HTTP_REFERER']);
		wp_die();
	}

	