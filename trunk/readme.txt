=== Woocommerce Pesapal Standard Payment Gateway ===
Contributors: jonathan_mitnick
Donate link: 
Tags: woocommerce, pesapal, payment-gateway
Requires at least: 3.3
Tested up to: 3.9.1
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends Woocommerce with a Pesapal payment gateawy.

== Description ==

Extends woocommerce payment gateway's to enable usage of pesapal to purchase goods. Payment can then be made via mobile money, visa/mastercard , bank transfer or from one's pesapal e-wallet.

== Installation ==

1. Upload `woocommerce-pesapal-standard.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the Pesapal settings page under Woocommerce/Settings/Checkout/Pesapal
4.Copy and Paste your consumer secret and your api key from pesapal.com.
5.Copy and paste your demo consumer secret and your api key from demo.pesapal.com.

== Frequently asked questions ==

= How to enable Instant Payment Notifications =

Add the following url to as your IPN notification url in your pesapal merchant dashboard : 
http://www.your-site.com/?wc-api=WC_Pesapal_Standard_Gateway

== Screenshots ==

1. The various payment options offered by Pesapal are displayed on the order-pay page on checkout. These include m-pesa, airtel money, yu cash, equity % co-operative banks and the pesapal e-wallet.

== Changelog ==

= 1.0 =
* Introduction of Instant Payment Notification integration.

== Upgrade notice ==

= 1.0 =
Version 1.0 integrates with Instant Payment Notifications from the Payment Gateway.
