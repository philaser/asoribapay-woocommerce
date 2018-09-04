=== AsoribaPay payment gateway for Woocommerce ===
Tags: woocommerce, asoriba, asoribapay, payment, paypal
Requires at least: 4.6
Tested up to: 4.9
Stable tag: trunk
Requires PHP: 5.6.35
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AsoribaPay payment gateway for woocommerce allows you to recieve payments to your merchant account through woocommerce

== Description ==

AsoribaPay is an  innovative payment gateway designed with the developer in mind.
A comprehensive API has been provided to make payment integration in your Wordpress site.
This plugin allows you to accept payments to your AsoribaPay merchant account through your woocommerce site without coding a single thing.

Major features of AsoribaPay include:

    *   Accept Visa and mastercard payments, on both local(Ghana) and international cards.
    *   Accept mobile payments including MTN mobile Money, Vodafone Cash, Tigo cash and Airtel money.
    *   Seamless and well documented API to get you started on recieving payments.

PS: You'll need an [AsoribaPay API Key](https://payment.asoriba.com/) to use this plugin.
== Installation ==

This section describes how to install the plugin and get it working.


1. Get your API Key from your merchant Dashboard
1. Upload the plugin files to the `/wp-content/plugins/woocommerce-asoribapay-gateway` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the 'AsoribaPay payment gateway for Woocommerce' plugin through the 'Plugins' screen in WordPress
1. Go to the Woocommerce->Settings->Payments screen  and enable 'AsoribaPay'. Click set up to configure the plugin
1. Place your API Key in the API Key field and click Save changes to get Started!

== Test Mode ==

From version 0.5, We have added a test/Sandbox mode for payment testing purposes.

1. First go to Go to the Woocommerce->Settings->Payments screen  and enable 'Test mode'. Click on Save Changes to save
1. Go to your websites Checkout Page , select AsoribaPay as your Payment Gateway and pay for the order.
1. On the Payment Page, the card number should be **4111 1111 1111 1111** , the expiry date should be **any** future date and the CVC should be **005**


* NOTE: **Please do not forget to disable Test mode before making payments live.**
== Frequently Asked Questions ==

= How do i get my API Key? =

You will first need a Merchant account on [AsoribaPay](https://payment.asoriba.com/user/sign_up).
You will then find your API Key in your Dashboard

= I keep getting errors when making payments! =

please contact us on support.asoriba.com(http://support.asoriba.com).


== Changelog ==

= 0.6 =
*Added: Tokenization option

= 0.5 =
* Added: Test/Sandbox mode functionality

= 0.4 =
* Added: Payment Image icon fuctionality

= 0.3 =
* Welcome to the AsoribaPay payment gateway for Woocommerce.
* Changes will be posted her after every update.
