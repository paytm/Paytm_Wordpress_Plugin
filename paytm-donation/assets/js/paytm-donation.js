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

function paytmDonationJs() {
    jQuery(document).ready(function() {
        paytmPgLoader();
        jQuery('#paytm-blinkcheckout').on('click', function() {
            jQuery('.paytm-pg-loader').show();
            var donor_amount = jQuery('input[name=donor_amount]').val();
            var donor_name = jQuery('input[name=donor_name]').val();
            var donor_email = jQuery('input[name=donor_email]').val();
            var donor_phone = jQuery('input[name=donor_phone]').val();
            var donor_address = jQuery('input[name=donor_address]').val();
            var donor_city = jQuery('input[name=donor_city]').val();
            var donor_state = jQuery('input[name=donor_state]').val();
            var donor_country = jQuery('input[name=donor_country]').val();
            var donor_postal_code = jQuery('input[name=donor_postal_code]').val();
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
                jQuery.ajax({
                    url: url,
                    method: "POST",
                    data: { "txnAmount": donor_amount, 'email': donor_email, "name": donor_name, 'phone': donor_phone, "address": donor_address, 'city': donor_city, "state": donor_state, 'country': donor_country, "postalcode": donor_postal_code, "id": id },
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