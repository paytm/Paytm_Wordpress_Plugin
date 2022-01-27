<?php

class CSVExport
{
	/**
	* Constructor
	*/
	public function __construct()
	{
		if(isset($_GET['export']))
		{

			global $wpdb;
			if(isset($_GET['filter_action'])){
				if(!empty($_GET['payment_status'])){
					$filter1 = "and payment_status = '".$_GET['payment_status']."'";
				}else{
					$filter1 = "";
				}
				if(!empty($_GET['query'])){
					$string = trim($_GET['query']);
					$filter2 = "and ( name like '%".$string."%' or email like '%".$string."%' or phone like '%".$string."%' or address like '%".$string."%' or city like '%".$string."%' or state like '%".$string."%' or zip like '%".$string."%' or country like '%".$string."%' )";
				}else{
					$filter2 = "";
				}
				$donationEntries = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "paytm_donation where 1 ".$filter1.$filter2."  order by date desc", ARRAY_A);
			}else{
				$donationEntries = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "paytm_donation order by date desc", ARRAY_A);
			}
			$exportArr = [];

			$headers = ["OrderId","Name","Email","Phone","Address","City","State","Country","Zipcode","Donation","Payment Status","Date"];
			$filename = "paytm_donation_".time().".csv";		
			$csv = $this->csv_download($donationEntries,$headers,$filename);
			exit;
		}
	}

	public function csv_download($array, $headers,$filename = "export.csv") {
        $f = fopen('php://memory', 'w'); 
        fputcsv($f, $headers);
        foreach ($array as $line) { 
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
?>
<style type="text/css">
	.paytm-export{

    display: inline-block;
    text-decoration: none;
    font-size: 13px;
    line-height: 2.15384615;
    min-height: 30px;
    margin: 0;
    padding: 0 10px;
    cursor: pointer;
    border-width: 1px;
    border-style: solid;
    -webkit-appearance: none;
    border-radius: 3px;
    white-space: nowrap;
    box-sizing: border-box;
        color: #0071a1;
    border-color: #0071a1;
    background: #f3f5f6;
    vertical-align: top;
    margin: 0 8px 0 0;
}
.wp-core-ui select {
    min-height: 31px;
    margin-top: -5px;
}
table#paytm-table {
    margin-top: 10px;
    overflow-x: auto;
}
.table-responsive {
    width: 97%;
    
}

.label-danger {
    background-color: #d9534f;
}
.label-success {
    background-color: #5cb85c;
}
.label-warning {
    background-color: #f0ad4e;
}
.label {
    display: inline;
    padding: .2em .6em .3em;
    font-size: 75%;
    font-weight: 700;
    line-height: 1;
    color: #fff;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: .25em;
}
</style>
<?php
ob_start();

function wp_paytm_donation_listings_page() {
?>

<div>
	<h1>Paytm Payment Details</h1>
	<form id="posts-filter" method="get">
		<div class="alignleft actions">
			<input type="hidden" name="page" value="wp_paytm_donation">
			<input type="text" name="query" value="<?=isset($_GET['query'])?$_GET['query']:""?>" placeholder="search">
			<select name="payment_status" id="payment_status" class="postform">
				<option value="0" selected="selected">All Payment Status</option>
				<option class="level-0" value="Payment failed" <?=($_GET['payment_status']=="Payment failed")?"selected":""?>>Payment failed</option>
				<option class="level-0" value="Complete Payment" <?=($_GET['payment_status']=="Complete Payment")?"selected":""?>>Complete Payment</option>
				<option class="level-0" value="Pending Payment" <?=($_GET['payment_status']=="Pending Payment")?"selected":""?>>Pending Payment</option>
			</select>
			<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">
		</div>
	</form>	
	<?php
			global $wpdb;
			$records_per_page = 10;
			$page = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
			$str = '';
			$offset = ( $page * $records_per_page ) - $records_per_page;
			if(isset($_GET['filter_action'])){
				if(!empty($_GET['payment_status'])){
					$filter1 = "and payment_status = '".$_GET['payment_status']."'";
					$str .= "&filter_action=true&payment_status=".$_GET['payment_status'];
				}else{
					$filter1 = "";
				}
				if(!empty($_GET['query'])){
					$string = trim($_GET['query']);
					$filter2 = "and ( name like '%".$string."%' or email like '%".$string."%' or phone like '%".$string."%' or address like '%".$string."%' or city like '%".$string."%' or state like '%".$string."%' or zip like '%".$string."%' or country like '%".$string."%' )";
					$str .= "&filter_action=true&query=".$string;
				}else{
					$filter2 = "";
				}
				$donationEntries = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "paytm_donation where 1 ".$filter1.$filter2."  order by date desc limit ".$offset. " , ".$records_per_page);
				$total = $wpdb->get_var("SELECT COUNT(id)  FROM " . $wpdb->prefix . "paytm_donation where 1 ".$filter1.$filter2."");
			}else{
				$donationEntries = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "paytm_donation order by date desc limit ".$offset. " , ".$records_per_page);
				$total = $wpdb->get_var("SELECT COUNT(id)  FROM " . $wpdb->prefix . "paytm_donation");
			}			
		?>

	<a href="<?php echo admin_url(); ?>/admin.php?page=wp_paytm_donation&export=true<?php echo $str; ?>" class="paytm-export">EXPORT</a>

	<div class="table-responsive">
		<table class="wp-list-table widefat fixed striped table-view-list posts" id="paytm-table">
			<thead>
				<tr>
					<th>Order Id</th>
					<th>Name</th>
					<th>Email</th>
					<th>Phone</th>
					<th>Address</th>
					<th>City</th>
					<th>State</th>
					<th>Country</th>
					<th>Zipcode</th>
					<th>Donation</th>
					<th>Payment Status</th>
					<th>Date</th>
				</tr>
			</thead>
			<tbody>
				<?php if (count($donationEntries) > 0) { ?>
					<?php foreach ($donationEntries as $row) { ?>
						<tr>
							<th><?php echo $row->id ?></th>
							<th><?php echo $row->name ?></th>
							<th><?php echo $row->email ?></th>
							<th><?php echo $row->phone ?></th>
							<th><?php echo $row->address ?></th>
							<th><?php echo $row->city ?></th>
							<th><?php echo $row->state ?></th>
							<th><?php echo $row->country ?></th>
							<th><?php echo $row->zip ?></th>
							<th><?php echo $row->amount ?></th>

							<?php if ($row->payment_status=="Complete Payment") { ?>
							    
								<th><span class="label label-success">Success</span></th>
							    
							<?php }else if($row->payment_status=="Pending Payment"){ ?>
								<th><span class="label label-warning">Pending</span></th>

							<?php }else if($row->payment_status=="Payment failed"){ ?>
								<th><span class="label label-danger">Failed</span></th>
							<?php }else{ ?>
								<th><span class="label label-default">NA</span></th>

							<?php } ?>

							


							<th><?php echo $row->date ?></th>
						</tr>
					<?php } }else { ?>
						<tr>
							<th colspan="12">No Record's Found.</th>
						</tr>
					<?php } ?>	
			</tbody>
		</table>	
		</div>
		<?php
		$pagination = paginate_links( array(
				'base' => add_query_arg( 'cpage', '%#%' ),
				'format' => '',
				'prev_text' => __('Previous'),
				'next_text' => __('Next'),
				'total' => ceil($total / $records_per_page),
				'current' => $page
		));
		?>		
		<div class="donation-pagination">
			<?php echo $pagination; ?>
		</div>
	</div>

	
	

<?php } ?>
