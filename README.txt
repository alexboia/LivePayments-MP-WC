=== LivePayments - mobilPay Card WooCommerce Payment Gateway ===
Contributors: alexandruboia
Donate link: https://ko-fi.com/alexandruboia
Tags: payment, card, mobilpay, creditcard, woocommerce
Requires at least: 5.0
Tested up to: 5.8.0
Stable tag: 0.1.6
Requires PHP: 5.6.2
License: BSD New License
License URI: https://opensource.org/licenses/BSD-3-Clause

LivePayments is a Credit & Debit Card WooCommerce Payment Gateway that uses the Romanian mobilPay payment processor.

== Description ==

Is this plug-in a good fit for you?
------------------------------------
For end users:
This plug-in is good for you if you are any kind of merchant running a WooCommerce-powered store and want accept credit or debit card payment using the romanian mobilPay™ payment processing gateway.  

For WooCommerce developers:
If you are a WordPress solution developer and  working on implementing a WooCommerce-based store powered by this payment processing gateway, then this is the only such plug-in that allows you to customize your implementation, due to a wealth of action and filter hooks.

Features
--------
- easy to setup and run using a friendly configuration interface, which does not require of you to perform any kind of FTP operation: just install the plug-in, fetch your assets from mobilPay and configure your plug-in.
- customizable and extensible via a wealth of action and filter hooks;
- supports all mobilPay™ transaction statuses, including partial refunds and partially completed payments;
- extremely detailed reporting on transaction history and lifecycle;
- dashboard widget for a quick outlook on overall transaction statuses;
- detailed transaction details are reported for each order, for both admin staff and your clients;
- multi-language support (romanian translation included);
- advanced diagnosis and monitoring of plugin configuration;
- detailed journaling.

Supported transaction statuses (actions)
----------------------------------------
- confirmed (the amount has been transfered and is entering the settlement process) - if the amount reported as confirmed is the same as the amount initially paid, the order may be fulfilled. Otherwise, it is placed on hold.
- confirmed_pending (the transaction's fraud risk is being assessed. The amount is being transfered. If everything checks out, the transaction moves to confirmed state.) - the order is placed on hold.
- paid_pending (the transaction's fraud risk is being assessed. No transfer has occured at this stage, but the amount is being reserved on the client's card.) - the order is placed on hold.
- canceled (the amount reserved on the client's card is being released.) - the order is marked as cancelled.
- credit (amount refunded to client - partially or in full) - the refunded amount is recorded in the order state. If the entire amount has been refunded, the order is marked as refunded.

Supported languages
-------------------
Available in English and Romanian.

== Frequently Asked Questions ==

= Does it support other payment processing gateways? =
No, the plug-in only supports the mobilPay payment processing gateway.

= Does it support other payment methods? =
No, the plug-in only supports credit or debit card payments.

= How can I contribute? =
Head over to the plug-in's GitHub page (https://github.com/alexboia/LivePayments-MP-WC) and let's talk!

== Screenshots ==

1. Frontend order details page - Payment transaction details
2. Admin order details page - Payment transaction details
3. Admin plug-in settings
4. Thank you page - Payment transaction status
5. Admin dashboard widget
6. Admin transaction history - details
7. Admin transaction history - listing
8. Plugin settings page (Livepayments-MP-WC > Plugin Settings menu)
9. Plugin diagnosis page (Livepayments-MP-WC > Plugin Diagnostics menu)

== Installation ==

1. Using your favourite FTP client, upload the plugin files to the /wp-content/plugins/livepayments-mp-wc directory, or install the plugin through the WordPress plugins screen directly (recommended).
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Retrieve your security assets from mobilPay™'s management interface (see here: https://github.com/alexboia/LivePayments-MP-WC#retrieving-mobilpay-security-assets).
4. Use the WooCommerce -> Settings -> Payments -> mobilPay™ Card Gateway sidebar menu item to access the plug-in configuration page.
5. Fill in the required fields as instructed here: https://github.com/alexboia/LivePayments-MP-WC#setting-up-and-configuring-your-plug-in.

== Changelog ==

= 0.1.6 =
* Fixed "WooCommerce 5.6 Analytics -> Overview not working #16"
* Fixed "The plugin's loading indicator overlaps with WooCommerce's loading indicator #14"

= 0.1.5 =
* Fixed two issues: activation error + missing romanian translations for settings page.

= 0.1.4 =
* Fixed some minor display issues;
* Added support for diagnosis and monitoring of the plugin payment gateway's configuration.

= 0.1.3 =
* Improved usability: gateway readiness displayd in the WooCommerce payment management tab;
* Extended action and filter hooks support;
* Bug fixes.

= 0.1.2 =
* Add reporting widget to WooCommerce Admin dashboard;
* Correct handling of payment initiation errors in the payment receipt screen;
* Correct handling of situations where some plug-in requirements are not fulfilled.

= 0.1.1 =
Fixed minor usability issues.

= 0.1.0 =
First officially distributed version.

== Upgrade Notice ==

= 0.1.6 =
Update to version 0.1.6 for compatibility updates with WordPress 5.8.0 and WooCommerce 5.6.0.

= 0.1.5 =
Update to version 0.1.5 for fixed activation error + missing romanian translations for settings page.

= 0.1.4 =
Update to version 0.1.4 for improved usability and new features.

= 0.1.3 =
Update to version 0.1.3 for improved usability and new features.

= 0.1.2 =
Update to version 0.1.2 for improved usability and new features.

= 0.1.1 =
Updated to version 0.1.1 for improved usability.

= 0.1.0 =
Use this version as the first officially distributed version.