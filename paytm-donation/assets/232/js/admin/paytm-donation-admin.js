function paytmPgLoader() {
    var loaderContent = '<div id="paytm-pg-spinner" class="paytm-pg-loader"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div><div class="bounce4"></div><div class="bounce5"></div></div><div class="paytm-overlay paytm-pg-loader"></div>';
    jQuery('body').append(loaderContent);
}

function isValidEmail(inputText) {
    var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
    if (inputText.match(mailformat)) {
        return true;
    } else {
        return false;
    }
}

/* --- website Name ------*/

jQuery(document).ready(function(){ 

   var otherWebvalue = jQuery('input[name=paytm_websiteOther]').val();
   console.log(otherWebvalue);
   var data = jQuery('select[name=paytm_website]').val();
   if (otherWebvalue ==""){
    jQuery("input[name=paytm_websiteOther]").css("display","none");    
   }else{
      jQuery("input[name=paytm_websiteOther]").val(otherWebvalue);
   } 

});

 
    jQuery(document).ready(function(){ 
        jQuery('select[name=paytm_website]').change(function() {
        websiteNameValiation(false); 
        var data = jQuery('select[name=paytm_website]').val();  
        if(data == "OTHERS"){ 

            jQuery("input[name=paytm_websiteOther]").css("display","block");
            jQuery("input[name=paytm_websiteOther]").attr('placeholder', 'Enter website name');

        }else  {
             jQuery("input[name=paytm_websiteOther]").css("display","none");
             jQuery("input[name=paytm_websiteOther]").val(""); 
        }
      });
    });
  
    jQuery("input[name=paytm_websiteOther]").on("keyup", function(event) {
        var value =jQuery("input[name=paytm_websiteOther]").val();
        var check = isAlphaNumeric(value);
        
        if(!check){
            websiteNameValiation(true);
        }else{
           websiteNameValiation(false);
        }
    });
  
    function websiteNameValiation(showMessage = false){
        if(showMessage){
            //jQuery(".otherWebsiteName-error-message").text("Please enter a valid website name");
            jQuery(".otherWebsiteName-error-message").html("Please enter a valid website name provided by <a href='https://dashboard.paytm.com' target='_blank'>Paytm</a>");
            jQuery('#savePaytmConfiguration').prop('disabled', true);
            jQuery("input[name=paytm_websiteOther]").scrollIntoView(true);

        }else{
            jQuery(".otherWebsiteName-error-message").text("");
            jQuery('#savePaytmConfiguration').prop('disabled', false);
        }
    }
    function isAlphaNumeric(str) {
      var code, i, len;
      for (i = 0, len = str.length; i < len; i++) {
        code = str.charCodeAt(i);
        if (!(code > 47 && code < 58) && // numeric (0-9)
            !(code > 64 && code < 91) && // upper alpha (A-Z)
            !(code > 96 && code < 123)) { // lower alpha (a-z)
          return false;
        }
      }
      return true;
    };
/*  --------- website name code end here -------------*/

function paytmDonationJs() {
    jQuery(document).ready(function($) {
        paytmPgLoader();
        jQuery('#paytm-blinkcheckout').on('click', function() {
            jQuery('.paytm-pg-loader').show();
            serializedata = $('form[name="frmTransaction"]').serializeArray();
            
            var donor_amount = $.grep(serializedata, function(element, index) {
                return (element.name === 'Amount');
             })[0].value;

             var donor_name = $.grep(serializedata, function(element, index) {
                return (element.name === 'Name');
             })[0].value;
             
             var donor_email = $.grep(serializedata, function(element, index) {
                return (element.name === 'Email');
             })[0].value;
             
             var donor_phone = $.grep(serializedata, function(element, index) {
                return (element.name === 'Phone');
             })[0].value;     
            var errorMsg = '';
            jQuery('.paytmError').remove();
            if (jQuery.trim(donor_name) == '') {
                errorMsg = "Please Enter Name!";
            } else if (isValidEmail(donor_email) === false) {
                errorMsg = "Please Enter Valid Email Address!";
            } else if (jQuery.trim(donor_amount) == '' || Number(donor_amount) < 1) {
                errorMsg = "Please Enter Amount!";
            } else {
                var url = jQuery(this).data('action');
                var id = jQuery(this).data('id');
                var pversion = jQuery(this).data('pversion');
                var wpversion = jQuery(this).data('wpversion');
                jQuery.ajax({
                    url: url,
                    method: "POST",
                    data: { "txnAmount": donor_amount, 'email': donor_email, "name": donor_name, 'phone': donor_phone, "id": id ,"serializedata":serializedata},
                    dataType: 'JSON',
                    beforeSend: function() {},
                    success: function(result) {
                        if (result.success == true) {
                            window.Paytm.CheckoutJS.init({
                                "flow": "DEFAULT",
                                "data": {
                                    "orderId": result.orderId,
                                    "token": result.txnToken,
                                    "tokenType": "TXN_TOKEN",
                                    "amount": result.txnAmount,
                                },
                                "integration": {
                                    "platform": "Wordpress Donation",
                                    "version": wpversion+"|"+pversion
                                },
                                handler: {
                                    notifyMerchant: function notifyMerchant(eventName, data) {
                                        /* console.log("notify merchant about the payment state"); */
                                        if (eventName == 'SESSION_EXPIRED') {
                                            alert('Session Expired. Please try again!');
                                            location.reload();
                                        }
                                    },
                                    transactionStatus: function(data) {
                                        /* console.log("payment status ", data); */
                                    }
                                }
                            }).then(function() {
                                window.Paytm.CheckoutJS.invoke();
                                jQuery('.paytm-pg-loader').hide();
                            });

                        } else {
                            alert('Something went wrong. Please try again!');
                            jQuery('.paytm-pg-loader').hide();
                        }

                    }
                });
            }
            if (errorMsg != "") {
                jQuery('#paytm-blinkcheckout').after('<span class="paytmError" style="display:block;color:red;">' + errorMsg + '</span>');
                jQuery('.paytm-pg-loader').hide();
            }
            return false;
        });
    });
}
if (typeof jQuery == 'undefined') {
    var headTag = document.getElementsByTagName("head")[0];
    var jqTag = document.createElement('script');
    jqTag.type = 'text/javascript';
    jqTag.src = 'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js';
    jqTag.onload = paytmDonationJs;
    headTag.appendChild(jqTag);
} else {
    paytmDonationJs();
}