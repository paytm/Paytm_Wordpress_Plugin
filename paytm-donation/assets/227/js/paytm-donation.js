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

function isValidPhone(inputText) {
    var pattern = /^\d{10}$/; // Validates a 10-digit phone number

    // Perform the validation
    if (pattern.test(inputText)) {
      // Phone number is valid
      console.log("Valid phone number");
      return true;
    } else {
      // Phone number is invalid
      console.log("Invalid phone number");
      return false;
    }
} 

function paytmDonationJs() {
    jQuery(document).ready(function($) {
        paytmPgLoader();
        var errorMsg = '';
        jQuery('.paytmError').remove();
        jQuery('#paytm-blinkcheckout').on('click', function() {
            jQuery('.paytm-pg-loader').show();
            var allInputs = $('form[name="frmTransaction"]').find(':input');
            serializedata = $('form[name="frmTransaction"]').serializeArray();
            console.log(serializedata);
            allInputs.each(function() {
                errorMsg = '';
                var name = $(this).attr('name');
                var required = ($(this).attr('required') !== undefined) ? true : false;
                if (required && jQuery.trim($(this).val()) === '') {
                    errorMsg = 'Field "' + name + '" is required field!';
                    return false;
                }

                if(name === "Email" && jQuery.trim($(this).val()) != '' && required){
                    if (isValidEmail(jQuery.trim($(this).val())) === false) {
                    errorMsg = "Please Enter Valid Email Address!";
                    return false;
                    } 
                }

                if(name === "Phone" && jQuery.trim($(this).val()) != '' && required){
                    if (isValidPhone(jQuery.trim($(this).val())) === false) {
                    errorMsg = "Please Enter Valid Phone no!";
                    return false;
                    } 
                }
                              
            });

            /*if (errorMsg == "") {
            var donor_email = $.grep(serializedata, function(element, index) {
                    return (element.name === 'Email');
                })[0].value;

               if (isValidEmail(donor_email) === false) {
                    errorMsg = "Please Enter Valid Email Address!";
                }   
            }*/
            if (errorMsg != "") {
                jQuery('.paytmError').remove();
                jQuery('#paytm-blinkcheckout').after('<span class="paytmError" style="display:block;color:red;">' + errorMsg + '</span>');
                jQuery('.paytm-pg-loader').hide();
                return false; 
            }else{
                jQuery('.paytmError').remove();
                var donor_email = $.grep(serializedata, function(element, index) {
                    return (element.name === 'Email');
                })[0].value;
                var donor_amount = $.grep(serializedata, function(element, index) {
                    return (element.name === 'Amount');
                })[0].value;
    
                var donor_name = $.grep(serializedata, function(element, index) {
                    return (element.name === 'Name');
                })[0].value;
                 
                var donor_phone = $.grep(serializedata, function(element, index) {
                    return (element.name === 'Phone');
                })[0].value;

                var nonce_token = $.grep(serializedata, function(element, index) {
                    return (element.name === 'hide_form_field_for_nonce');
                })[0].value;

                var url = jQuery(this).data('action');
                var id = jQuery(this).data('id');
                var pversion = jQuery(this).data('pversion');
                var wpversion = jQuery(this).data('wpversion');
                jQuery.ajax({
                    url: url,
                    method: "POST",
                    data: { "txnAmount": donor_amount, 'email': donor_email, "name": donor_name, 'phone': donor_phone, "id": id ,"token":nonce_token,"serializedata":serializedata},
                    dataType: 'JSON',
                    beforeSend: function() {},
                    success: function(result) {
                        console.log('21313');
                        console.log('============================');
                        console.log(result);
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
                                        if (eventName == 'SESSION_EXPIRED') {
                                            alert('Session Expired. Please try again!');
                                            location.reload();
                                        }
                                    },
                                    transactionStatus: function(data) {
                                       
                                    }
                                }
                            }).then(function() {
                                window.Paytm.CheckoutJS.invoke();
                                jQuery('.paytm-pg-loader').hide();
                            });

                        } else if(result.error == true){
                            alert(result.message);
                            jQuery('.paytm-pg-loader').hide();
                        } else {
                            alert('Something went wrong. Please try again!');
                            jQuery('.paytm-pg-loader').hide();
                        }

                    }
                });
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