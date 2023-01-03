=== Paytm - Donation Plugin === 
Contributors: integrationdevpaytm
Tags: paytm, paytm plugin, paytm donation, payment, paytm payment, paytm wordpress plugin, wordpress donation, paytm official
Requires PHP: 5.6
Requires at least: 4.9
Tested up to: 6.1.1
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
This plugin allow you to accept donation payments using Paytm. This plugin will add a simple form that user will fill, when he clicks on submit he will redirected to Paytm website to complete his transaction and on completion his payment, paytm will send that user back to your website along with transactions details. This plugin uses server-to-server verification to add additional security layer for validating transactions. Admin can also see all transaction details with payment status by going to "Paytm Payment Details" from menu in admin.

== Installation ==

* Download Paytm Donation plugin 
* Upload this all plugin files in "wp-content/plugins/" directory
* Install and activate the plugin from Wordpress admin panel
* Select "Paytm Settings" from menu list and update Paytm configuration values provided by Paytm team
* Create a new post or page with and put shortcode [paytmcheckout] there
* Your Wordpress Donation plugin is now setup. You can now accept donation payment through Paytm.

== Compatibilities and Dependencies ==

* Wordpress v3.9.2 or higher
* PHP v5.6.0 or higher
* Php-curl

== Changelog ==

= 2.0 =
* User now can able to customise fields to show on frontend
* Security Fix
* Export option added
* User now can filter transaction details from payment history

= 1.0 =
* Stable release