<?php
class CSVExport
{
    /**
     * Constructor
    **/
    public function __construct()
    {
        if (isset($_GET['export'])) {

            global $wpdb;
            if (isset($_GET['filter_action'])) {
                $filter1 = '';
                $filter2 = '';
                $params = array();
                
                if (!empty($_GET['payment_status'])) {
                    $payment_status = sanitize_text_field($_GET['payment_status']);
                    $filter1 = "AND payment_status = %s";
                    $params[] = $payment_status;
                }

                if (!empty($_GET['query'])) {
                    $string = trim(sanitize_text_field($_GET['query']));
                    $string = '%' . $string . '%';
                    $filter2 = "AND (custom_data LIKE %s)";
                    $params[] = $string;
                }
                $query = "SELECT pdud.*,pdod.transaction_id FROM " . $wpdb->prefix . "paytm_donation_user_data as pdud LEFT JOIN " . $wpdb->prefix . "paytm_donation_order_data as pdod on pdud.id = pdod.order_id WHERE 1 " . $filter1 . $filter2 . "  ORDER BY date DESC";

                if (!empty($params)) {
                    $query = $wpdb->prepare($query, $params);
                }

                $donationEntries = $wpdb->get_results($query); // No need for ARRAY_A here
            } else {
                $donationEntries = $wpdb->get_results("SELECT pdud.*,pdod.transaction_id FROM " . $wpdb->prefix . "paytm_donation_user_data as pdud LEFT JOIN " . $wpdb->prefix . "paytm_donation_order_data as pdod on pdud.id = pdod.order_id ORDER BY date DESC"); // No need for ARRAY_A here
            }

            $exportArr = [];

            $headers = ["OrderId","Name","Email","Phone","Donation","Payment Status","Transaction ID","Date","More Details"];
            $filename = "paytm_donation_".time().".csv";

            foreach ($donationEntries as $key => $value) {
                 $decodeData = json_decode($value->custom_data); 
                 $donationEntriesFormat[$key][0] =$value->id;
                 $donationEntriesFormat[$key][1] = ($decodeData)[0]->value;
                 $donationEntriesFormat[$key][2] = ($decodeData)[1]->value;
                 $donationEntriesFormat[$key][3] = ($decodeData)[2]->value;
                 $donationEntriesFormat[$key][4] = ($decodeData)[3]->value;
                 $donationEntriesFormat[$key][5] =$value->payment_status;
                 $donationEntriesFormat[$key][6] =$value->transaction_id;
                 $donationEntriesFormat[$key][7] =$value->date;

                 $j =4;
                 $donationEntriesFormat[$key][8]='';
                for ($i=5; $i<=count($decodeData); $i++) {
                    /* ---  Getting data from 4th position and then incrementing it ----- */
                    $donationEntriesFormat[$key][8] .= $decodeData[$j]->name.' : '.$decodeData[$j]->value."\n";
                    $j++;
                }
            }

            $csv = $this->csv_download($donationEntriesFormat, $headers, $filename);
            exit;
        }
    }

    public function csv_download($array, $headers,$filename = "export.csv") 
    {
        $f = fopen('php://memory', 'w'); 
        fputcsv($f, $headers);
        foreach ($array as $key=> $line) { 
            fputcsv($f, $line); 
        }
        fseek($f, 0);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Description: File Transfer');
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        header('Expires: 0');
        header('Pragma: public');
        fpassthru($f);
        exit();
    }
}

// Instantiate a singleton of this plugin
$csvExport = new CSVExport();

ob_start();

function wp_paytm_donation_listings_page() {
 echo '<script type="text/javascript"> paytmDonationJs();</script>'; //dynamic script
?>
<!-- The Modal -->
<div id="myModal" class="modal">
    <!-- Modal content -->
    <div class="modal-content">
    <span class="close">&times;</span>
    <div id="paytm_dynamic_content">
    </div>
    </div>
</div>
<?php //require_once(__DIR__.'/includes/dbUpgrade_modal.php'); ?> <!-- dynamic script-->

<div id="myModal2" class="modal">
<!-- Modal content -->
<div class="modal-content">
<div id="paytm_refresh_data"> 
<p>To use the plugin, please upgrade the Database.</p>
<button class="refresh_history_record button-secondary" >Upgrade Now&nbsp; </button>
</div>
</div>
</div>

<div>
    <h1>Paytm Payment Details</h1>
    <form id="posts-filter" method="get">
    <div class="alignleft actions">
    <input type="hidden" name="page" value="wp_paytm_donation">
    <input type="text" name="query" value="<?=isset($_GET['query'])?sanitize_text_field($_GET['query']):""?>" placeholder="search">
    <select name="payment_status" id="payment_status" class="postform">
    <option value="0" selected="selected">All Payment Status</option>
    <option class="level-0" value="Complete Payment" <?=(isset($_GET['payment_status']) && $_GET['payment_status']=="Complete Payment")?"selected":""?>>Success</option>
    <option class="level-0" value="Payment failed" <?=(isset($_GET['payment_status']) && $_GET['payment_status']=="Payment failed")?"selected":""?>>Failed</option>
    <option class="level-0" value="Pending Payment" <?=(isset($_GET['payment_status']) && $_GET['payment_status']=="Pending Payment")?"selected":""?>>Pending</option>
    </select>
    <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Search">
    
</form>	

    <?php
    global $wpdb;
    $records_per_page = 10;
    $page = isset($_GET['cpage']) ? abs((int) sanitize_text_field($_GET['cpage'])) : 1;
    $str = '';
    $offset = ( $page * $records_per_page ) - $records_per_page;
    if (isset($_GET['filter_action'])) {
        $filter1 = '';
        $filter2 = '';
        $params = array();

        if (!empty($_GET['payment_status'])) {
            $payment_status = sanitize_text_field($_GET['payment_status']);
            $filter1 = "AND payment_status = %s";
            $params[] = $payment_status;
            $str .= "&filter_action=true&payment_status=" . urlencode($payment_status);
        }

        if (!empty($_GET['query'])) {
            $string = trim(sanitize_text_field($_GET['query']));
            $string = '%' . $string . '%';
            $filter2 = "AND (custom_data LIKE %s)";
            $params[] = $string;
            $str .= "&filter_action=true&query=" . urlencode($string);
        }

        $query =  "SELECT pdud.*,pdod.transaction_id FROM " . $wpdb->prefix . "paytm_donation_user_data as pdud LEFT JOIN " . $wpdb->prefix . "paytm_donation_order_data as pdod on pdud.id = pdod.order_id WHERE 1 " . $filter1 . $filter2 . "  ORDER BY date DESC LIMIT " . intval($offset) . ", " . intval($records_per_page);
        //$query ="SELECT pdud.*,pdod.transaction_id FROM " . $wpdb->prefix . "paytm_donation_user_data as pdud LEFT JOIN " . $wpdb->prefix . "paytm_donation_order_data as pdod on pdud.id = pdod.order_id ORDER BY date DESC LIMIT " . intval($offset) . ", " . intval($records_per_page);

        /* echo $query;
        die(); */
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        $donationEntries = $wpdb->get_results($query, ARRAY_A);

        $total_query =  "SELECT COUNT(id)  FROM " . $wpdb->prefix . "paytm_donation_user_data WHERE 1 " . $filter1 . $filter2;
        if (!empty($params)) {
            $total_query = $wpdb->prepare($total_query, $params);
        }
        $total = $wpdb->get_var($total_query);
    } else {
        //$query = "SELECT * FROM " . $wpdb->prefix . "paytm_donation_user_data ORDER BY date DESC LIMIT " . intval($offset) . ", " . intval($records_per_page);
        $query ="SELECT pdud.*,pdod.transaction_id FROM " . $wpdb->prefix . "paytm_donation_user_data as pdud LEFT JOIN " . $wpdb->prefix . "paytm_donation_order_data as pdod on pdud.id = pdod.order_id ORDER BY date DESC LIMIT " . intval($offset) . ", " . intval($records_per_page);
        /* echo $query;
        die(); */
        $donationEntries = $wpdb->get_results($query);
        $total = $wpdb->get_var("SELECT COUNT(id)  FROM " . $wpdb->prefix . "paytm_donation_user_data");
    }


?>
<?php if (count($donationEntries) > 0) {     ?>
<a href="<?php echo esc_url(admin_url().''.'/admin.php?page=wp_paytm_donation&export=true'.$str); ?>" class="paytm-export">Export</a>
<?php } ?>
</div>
<?php

$oldLastId = PaytmHelperDonation::checkOldPaytmDonationDb();
if ($oldLastId!='') {?>
    <button class="refresh_history_record button-secondary" >Refresh History Record &nbsp; 
     </button>
<?php } ?>
<div class="table-responsive">
    <table class="wp-list-table widefat fixed striped table-view-list posts" id="paytm-table">
            <thead>
            <tr>
            <th>Order Id</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Donation</th>
            <th>Payment Status</th>
            <th>Transaction ID</th>
            <th>Date</th>
            <th>View Details</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if (count( $donationEntries) > 0) { ?>
                <?php foreach ($donationEntries as $row) { ?>
                    <tr>
                    <?php 
                    if(is_object($row)){
                            $row = (array) $row;
                    }
                    $decodeData = json_decode($row['custom_data']);?>
                    <th><?php echo sanitize_text_field($row['id']); ?></th>
                    <th><?php echo sanitize_text_field(($decodeData)[0]->value); ?></th>
                    <th><?php echo sanitize_text_field(($decodeData)[1]->value); ?></th>
                    <th><?php echo sanitize_text_field(($decodeData)[2]->value); ?></th>
                    <th><?php echo sanitize_text_field(($decodeData)[3]->value); ?></th>

                    <?php if ($row['payment_status'] == "Complete Payment") { ?>

                            <th><span class="label label-success">Success</span></th>
 
                    <?php } else if ($row['payment_status'] == "Pending Payment") { ?>
                        <th><span class="label label-warning">Pending</span></th>

                    <?php } else if ($row['payment_status'] == "Payment failed") { ?>
                        <th><span class="label label-danger">Failed</span></th>
                    <?php } else { ?>
                        <th><span class="label label-default">NA</span></th>
                    <?php } ?>
                        
                        <th><?php echo $row['transaction_id']?$row['transaction_id']:"NA"; ?></th>
                        <th><?php echo $row['date'] ?></th>
                          
                          <td><button class="btnPrimary" onclick="displayFullDetails(<?php echo sanitize_text_field($row['id']);?>)" id="myBtn">Full Details</button></td>
                          </tr>
                    <?php } } else { ?>
                    <tr>
                    <th colspan="12">No Record's Found.</th>
                    </tr>
                    <?php } ?>
                    </tbody>
    </table>	
</div>
<?php
    $pagination = paginate_links(array(
                'base' => add_query_arg('cpage', '%#%' ),
                'format' => '',
                'prev_text' => __('Previous'),
                'next_text' => __('Next'),
                'total' => ceil($total / $records_per_page),
                'current' => $page
        )
    );
?>
<div class="donation-pagination">
    <?php echo sanitize_text_field($pagination); ?>
    </div>
</div>

<script>
// Get the modal
var modal = document.getElementById("myModal");
var modal2 = document.getElementById("myModal2");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal 
function displayFullDetails(order_id) {
   // var txnData = <?php /* echo $donationEntries['paytm_response'];  */?>;
    var decodeData = <?php echo (json_encode($donationEntries)); ?>;
    // console.log(decodeData);
    let res = decodeData.find(({id}) => id == order_id);
 
    //--- popup table content ----//	
    var dynamic_content ='<table class="wp-list-table widefat fixed striped table-view-list posts" border="1" width="70%" align="center" cellpadding="6"><p><caption><strong>PAYTM DONATION DETAILS</strong></p></caption>';
    dynamic_content+='<tr><td>Order Id:</td><td>'+order_id+'</td></tr>';
    for (var i = 0; i < JSON.parse(res['custom_data']).length; i++){
        dynamic_content += '<tr><td>'+JSON.parse(res['custom_data'])[i]['name'].replace(/_/g, ' ')+': </td>'+'<td>'+JSON.parse(res['custom_data'])[i]['value']+'</td></tr>';
    }
    dynamic_content +='<tr><td>Payment Status:</td><td>'+res['payment_status']+'</td></tr>';
    dynamic_content +='<tr><td>Date:</td><td>'+res['date']+'</td></tr></table>';

    document.getElementById('paytm_dynamic_content').innerHTML = dynamic_content;

  modal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
  modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
  }
}

jQuery('.refresh_history_record').on('click', function() {
    var ajax_url = "<?php echo admin_url('admin-ajax.php'); ?>?action=refresh_Paytmhistory";
    $('.refresh_history_record').prop('disabled', true);
 
    jQuery.ajax({
        //  data: data,
        method: "POST",
        url: ajax_url,
        dataType: 'JSON',
        success: function(result) {
            console.log(result); //should print out the name since we sent it along
        }
    });
    setTimeout(function(){window.location.reload(true);}, 1000);
 
});

<?php if ($oldLastId!='') {?>
    modal2.style.display = "block";
<?php } ?>

</script>		


<?php } 

?>
