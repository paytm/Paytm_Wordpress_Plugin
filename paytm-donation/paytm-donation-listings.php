<?php
class CSVExport
{
    /**
     * Constructor
    **/
    public function __construct()
    {
        if (isset($_GET['export'])) {
            $total_records = 500;
            global $wpdb;
                if(isset($_GET['view-full-data']) && $_GET['view-full-data'] == '1'){
                    $query = $wpdb->prepare("SELECT pdud.*,pdod.transaction_id FROM " . $wpdb->prefix . "paytm_donation_user_data as pdud LEFT JOIN " . $wpdb->prefix . "paytm_donation_order_data as pdod on pdud.id = pdod.order_id ORDER BY date DESC"); // No need for ARRAY_A here
                }
                else{
                    $query = $wpdb->prepare("SELECT pdud.*,pdod.transaction_id FROM " . $wpdb->prefix . "paytm_donation_user_data as pdud LEFT JOIN " . $wpdb->prefix . "paytm_donation_order_data as pdod on pdud.id = pdod.order_id ORDER BY date DESC LIMIT %d, %d",
                    0,
                    $total_records
                );
                }
                $donationEntries = $wpdb->get_results($query);

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
    <div id="paytm-filter" >
    <div class="alignleft actions">
    <input type="hidden" name="page" value="wp_paytm_donation">
    <div id="paytm-filter-select" class="dataTables_filter">
    <select name="payment_status" id="payment_status" class="postform">
    <option value="" selected="selected">All Payment Status</option>
    <option class="level-0" value="Success" >Success</option>
    <option class="level-0" value="Failed">Failed</option>
    <option class="level-0" value="Pending">Pending</option>
    </select>
    </div>
</div>	

    <?php
    global $wpdb;
    $total_records = 500;
    $records_per_page = 10;
    $page = isset($_GET['cpage']) ? abs((int) sanitize_text_field($_GET['cpage'])) : 1;
    $str = '';
   
        if(isset($_GET['view-full-data']) && $_GET['view-full-data'] == '1'){
            $query = $wpdb->prepare(
                "SELECT pdud.*, pdod.transaction_id , pdod.paytm_response
                FROM {$wpdb->prefix}paytm_donation_user_data as pdud 
                LEFT JOIN {$wpdb->prefix}paytm_donation_order_data as pdod 
                ON pdud.id = pdod.order_id 
                ORDER BY date DESC"
            );
        }
        else{
            $query = $wpdb->prepare(
                "SELECT pdud.*, pdod.transaction_id , pdod.paytm_response
                FROM {$wpdb->prefix}paytm_donation_user_data as pdud 
                LEFT JOIN {$wpdb->prefix}paytm_donation_order_data as pdod 
                ON pdud.id = pdod.order_id 
                ORDER BY date DESC LIMIT %d, %d",
                0,
                $total_records
            );
        }

        $donationEntries = $wpdb->get_results($query);
        $total = $wpdb->get_var("SELECT COUNT(id)  FROM " . $wpdb->prefix . "paytm_donation_user_data");
    


?>
<?php if (count($donationEntries) > 0) {     ?>
   <div class="paytm-view-full-data"> <label for="view-full-data"><input type="checkbox" id="view-full-data" name="view-full-data" value="1" <?php echo isset($_GET['view-full-data']) && $_GET['view-full-data'] == '1' ? 'checked' : ''; ?>  >View Full Data <br />
<span style="font-size: 12px;">Note: By Default, only <?php echo esc_html($total_records); ?> records are shown. Check this option to view all records.</span></label>
</div>
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
            <!-- <th>Email</th> -->
            <!-- <th>Phone</th> -->
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

                    $decodeData = json_decode($row['custom_data']);?>
                    <th><?php echo esc_html($row['id']); ?></th>
                    <th><?php echo esc_html($decodeData[0]->value); ?></th>
                    <!-- <th><?php echo esc_html($decodeData[1]->value); ?></th> -->
                    <!-- <th><?php echo esc_html($decodeData[2]->value); ?></th> -->
                    <th><?php echo esc_html($decodeData[3]->value); ?></th>

                    <?php 
                    $status_class = '';
                    $status_text = 'NA';
                    
                    switch ($row['payment_status']) {
                        case 'Complete Payment':
                            $status_class = 'label-success';
                            $status_text = 'Success';
                            break;
                        case 'Pending Payment':
                            $status_class = 'label-warning';
                            $status_text = 'Pending';
                            break;
                        case 'Payment failed':
                            $status_class = 'label-danger';
                            $status_text = 'Failed';
                            break;
                    }
                    ?>
                    <th><span class="label <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></th>
                        
                        <th><?php echo esc_html($row['transaction_id'] ?: 'NA'); ?></th>
                        <th><?php echo esc_html($row['date']); ?></th>
                          
                          <td><button class="btnPrimary" onclick="displayFullDetails(<?php echo esc_js($row['id']); ?>)" id="myBtn">Full Details</button></td>
                          </tr>
                    <?php } } else { ?>
                    <tr>
                    <th colspan="12">No Record's Found.</th>
                    </tr>
                    <?php } ?>
                    </tbody>
    </table>	
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
    dynamic_content +='<tr><td>Date:</td><td>'+res['date']+'</td></tr>';
    const formattedJson = JSON.stringify(JSON.parse(res['paytm_response']), null, 2);
    dynamic_content +='<tr><td>Payment Data</td><td class="paytm-response"><pre>'+formattedJson+'</pre></td></tr></table>';

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
    var ajax_url = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=refresh_Paytmhistory";
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
jQuery(document).ready(function($) {
    var table = jQuery('#paytm-table').DataTable(
        {
            "order": [[0, "desc"]],
            "autoWidth": true,
            "pageLength": 20
        }
    );  // Initialize DataTable
    var payment_status = $('#payment_status');

    // Apply filter when dropdown changes
    payment_status.on('change', function() {
        var selectedRole = $(this).val();
        table.column(3).search(selectedRole).draw();
    });
    $('#paytm-filter-select').insertAfter('#paytm-table_filter');
    
    // Add confirmation dialog for checkbox
    $('#view-full-data').on('change', function(e) {
        var checkbox = $(this);
        if (checkbox.is(':checked')) {
            if (confirm('Are you sure you want to view all records? This may take longer to load.')) {
                // Get current URL and append view-full-data parameter
                var currentUrl = window.location.href;
                var separator = currentUrl.indexOf('?') !== -1 ? '&' : '?';
                window.location.href = currentUrl + separator + 'view-full-data=1';
            } else {
                e.preventDefault();
                checkbox.prop('checked', false);
            }
        }
        else{
            var currentUrl = window.location.href;
            var separator = currentUrl.indexOf('?') !== -1 ? '&' : '?';
            window.location.href = currentUrl + separator + 'view-full-data=0';
        }
    });
    
    // Export button click handler
    $('.paytm-export').on('click', function(e) {
        e.preventDefault();
        
        var exportUrl = $(this).attr('href');
        var isViewAll = $('#view-full-data').is(':checked');
        
        // Add view-full-data parameter if checked
        if (isViewAll) {
            var separator = exportUrl.indexOf('?') !== -1 ? '&' : '?';
            exportUrl += separator + 'view-full-data=1';
        }
        
        // Show confirmation dialog
        if (confirm('Are you sure you want to export the data?')) {
            window.open(exportUrl, '_blank');
        }
    });
});

<?php if ($oldLastId!='') {?>
    modal2.style.display = "block";
<?php } ?>

</script>		


<?php } 

?>
