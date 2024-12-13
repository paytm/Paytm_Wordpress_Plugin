<?php
function wp_paytm_donation_user_field_page() 
{
    global $wpdb;
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
            $customFieldRecord = $wpdb->get_results("SELECT option_value FROM " . $wpdb->prefix . "options where option_name = 'paytm_user_field'");
            $decodeCustomFieldRecord = json_decode(json_encode($customFieldRecord[0]));
    
            $decodeCustomFieldRecordArray = (json_decode($decodeCustomFieldRecord->option_value));
         
            $fieldType = ['text','dropdown','radio'];

            $requiredType = ["yes","no"];

            //script is dynamically added here
            echo wp_kses('<script type="text/javascript"> paytmDonationJs();</script>', $allowedposttags);
            PaytmHelperDonation::dbUpgrade_modal();
            ?>

<form id="customFieldForm" method="post">
<div class="container1">
  <h1>Manage Donation Form</h1>
  <p>Customize your donation form by using below custom fields, please read instructions given below to prevent from errors. </p>
    <button class="add_form_field button-secondary">Add New Field &nbsp; 
      <span class="plusIcon">+ </span>
    </button><br><br>
    <div class="userFields">
        <label class="input-head" for="">Field Name</label>
        <label class="input-head"  for="">Required Option</label>
        <label class="input-head" for="">Field Type</label>
        <label class="input-head" for="">Field Value</label>
    </div>
    
    <?php $i=0; foreach($decodeCustomFieldRecordArray->mytext as $key => $value): ?>
    <div class="userFields">
        <?php $readonly = ''; if ($value=='Name' || $value=='Email' || $value=='Phone' || $value=='Amount'){
            $readonly = 'readonly';
         } ?>
        <input type="text" name="mytext[]" Placeholder="Field Name" value="<?php echo esc_attr($value);?>" <?php echo esc_attr($readonly);?>>

        <select name="is_required[]" <?php if($i<=3){ echo 'style="pointer-events: none;"';}?> >
            <?php foreach($requiredType as $fieldTypeValue):?>
                <?php if($i<=3){?>
                    <option value="yes">yes</option>
                    <?php }else{ ?>
                <option value="<?php echo esc_attr($fieldTypeValue);?>" <?php echo esc_attr(($decodeCustomFieldRecordArray->is_required[$key] == $fieldTypeValue) ? 'selected' : ''); ?>><?php echo esc_attr($fieldTypeValue);?></option>
                <?php };?>
            <?php endforeach;?>
        </select>  

        <select name="mytype[]" <?php if($i<=3){ echo 'style="pointer-events: none;"';}?> >
            <option value="">Select</option>
            <?php foreach($fieldType as $fieldTypeValue):?>
                <option value="<?php echo esc_attr($fieldTypeValue);?>" <?php echo ($decodeCustomFieldRecordArray->mytype[$key] == $fieldTypeValue) ? 'selected' : ''; ?> ><?php echo esc_attr($fieldTypeValue);?></option>
            <?php endforeach;?>
        </select>
        <input type="text" name="myvalue[]" Placeholder="Comma Seperated Value" value="<?php echo esc_attr($decodeCustomFieldRecordArray->myvalue[$key]);?>">
        <?php if ($value!=='Name' && $value!=='Email' && $value!=='Phone' && $value!== 'Amount') {?>
        <a href="#" class="paytmDelete">Delete</a>
        <?php } ?>
    </div>
    <?php $i++; endforeach; ?>
</div>
 
    <?php $post_paytmCustomField = get_queried_object_id(); 

    //$nonce_field = wp_nonce_field(plugin_basename(__FILE__),'hide_form_field_for_admin_nonce');

    echo '<input type="button" value="Save Changes" class="button-primary" id="paytm-paytmCustomFieldSave" data-action="'.esc_attr(admin_url('admin-ajax.php').'?action=initiate_paytmCustomFieldSave&nonce='.wp_create_nonce( 'hide_form_field_for_admin_nonce' )).'" data-id="'.esc_attr($post_paytmCustomField).'" />';
    ?>
</form>
<div class="Instructions">
   <h2>Instructions:</h2>
    <p>- Name, Email, Phone, Amount are basic fields which are used in transactions.</p>
    <p>- You can add options like input fields, dropdowns and radio buttons.</p>
    <p>- For e.g. You can add an extra input by clicking add new field and give a field name, then select its type and give default value.</p>
    <p><strong>Note:</strong> If you select dropdown or radio then please add its value comma seperated e.g. (Noida, Pune, Nainital)</p>
</div>
<script>
jQuery(document).ready(function($) {
    var wrapper = $(".container1");
    var add_button = $(".add_form_field");

    var x = 1;
    jQuery(add_button).click(function(e) {
        e.preventDefault();
       /* if (x < max_fields) {
            x++;*/
            $(wrapper).append('<div class="userFields"><input type="text" name="mytext[]" Placeholder="Field Name" />&#8198;&#8198;<select name="is_required[]"><option value=yes>yes</option><option value=no>No</option></select>&#8198;&#8198;<select name="mytype[]" ><option value="">Select</option><option value="text">text</option><option value="dropdown">dropdown</option><option value="radio">radio</option></select>&#8198;&#8198;<input type="text" name="myvalue[]" Placeholder="Comma Seperated Value">&#8198;&#8198;<a href="#" class="paytmDelete">Delete</a></div>'); //add input box
        /*} else {
            alert('You Reached the limits')
        }*/
    });

    jQuery(wrapper).on("click", ".paytmDelete", function(e) {
        e.preventDefault();
        $(this).parent('div').remove();
        x--;
    })
});

jQuery('#paytm-paytmCustomFieldSave').on('click', function() {
    var data = jQuery('#customFieldForm').serializeArray();
    dataObj = {};
    fieldName = false;
    fieldRequired = false;
    fieldType = false;
    fieldValue = false;    
    jQuery(data).each(function(i, field){
      dataObj[field.name] = field.value;
      console.log(dataObj);
      getReminder = i % 4;
      position = i;

      if(getReminder == 0 && field.value==''){
        fieldName = true;
      }

      if(getReminder == 1 && (field.value=='dropdown')){
        position++;
        if(data[position]['value']==''){
            fieldValue = true;
        }
      }
      if(getReminder == 1 && (field.value=='')){
        fieldRequired = true;
      }

      if(getReminder == 2 && (field.value=='dropdown' || field.value=='radio')){
        position++;
        if(data[position]['value']==''){
            fieldValue = true;
        }
      }
      if(getReminder == 2 && (field.value=='')){
        fieldType = true;
      }
  
    }); 

    if(fieldName==true){
        alert('Field Name Cannot be empty');
        return false;
    } 

    if(fieldValue==true){
        alert('Field Value Cannot be empty for type dropdown and radio');
        return false;
    } 

    if(fieldType==true){
        alert('Field Type Cannot be empty');
        return false;
    }
    if(fieldRequired==true){
        alert('Field Required Cannot be empty');
        return false;
    }          

    var ajax_url = "<?php echo esc_attr(admin_url('admin-ajax.php')); ?>";
    var url = jQuery(this).data('action');
    var id = jQuery(this).data('id');
    jQuery.ajax({
         data:data,
         method: "POST",
         url: url,
         dataType: 'JSON',
         success: function(result) {
            if (result.success == true) {
                alert("Record Saved Successfully!");
                location.reload();
            }else if(result.error == true){
                alert(result.message);
                location.reload();
            } else {
                alert('Something went wrong. Please try again!');
                location.reload();
            }
        }
    });

});

jQuery('.refresh_history_record').on('click', function() {
    var ajax_url = "<?php echo esc_attr(admin_url('admin-ajax.php')); ?>?action=refresh_Paytmhistory";
    jQuery('.refresh_history_record').prop('disabled', true);
   
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

var modal2 = document.getElementById("myModal2");
    <?php 
    $oldLastId = PaytmHelperDonation::checkOldPaytmDonationDb();
    if ($oldLastId!='') {?>
        modal2.style.display = "block";
    <?php } ?>

</script>
    <?php 
}
?>