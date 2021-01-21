<p align="center">
    <img align="center" width="800" height="324" src="https://raw.githubusercontent.com/alexboia/LivePayments-MP-WC/master/logo.png?v=3" style="margin-bottom: 20px; margin-right: 20px;" />
</p>

# LivePayments - mobilPay Card WooCommerce Payment Gateway
LivePayments is a Credit & Debit Card WooCommerce Payment Gateway that uses the Romanian mobilPay payment processor.
This plugin is meant to be used by merchants in Romania.

## Contents

1. [Is it good for you?](#lvdwcmc-isitgoodforyou)
2. [Supported transaction statuses (actions)](#lvdwcmc-transaction-statuses)
3. [Downloading the plug-in](#lvdwcmc-get-it)
4. [Installation](#lvdwcmc-installation)
5. [Setting up and configuring your plug-in](#lvdwcmc-setup)
6. [Retrieving mobilPay™ security assets](#lvdwcmc-mobilpay-security-assets)
7. [Gateway readiness](#lvdwcmc-gateway-readiness)
8. [Gateway diagnostics](#lvdwcmc-gateway-diagnostics)
9. [Screenshots](#lvdwcmc-screenshots)
10. [Requirements](#lvdwcmc-requirements)
11. [Credits](#lvdwcmc-credits)
12. [License](#lvdwcmc-license)

## Is it good for you?
<a name="lvdwcmc-isitgoodforyou"></a>  

### End users
This plug-in is good for you if you are any kind of merchant running a WooCommerce-powered store and want accept credit or debit card payment using the romanian mobilPay™ payment processing gateway.  

### WooCommerce developers
If you are a WordPress solution developer and  working on implementing a WooCommerce-based store powered by this payment processing gateway, then this is the only such plug-in that allows you to customize your implementation, due to a wealth of action and filter hooks.

## Features
<a name="lvdwcmc-features"></a>  

- easy to setup and run using a friendly configuration interface, which does not require of you to perform any kind of FTP operation: just install the plug-in, fetch your assets from mobilPay and configure your plug-in.
- customizable and extensible via a wealth of action and filter hooks;
- supports all mobilPay™ transaction statuses, including partial refunds and partially completed payments;
- extremely detailed reporting on transaction history and lifecycle;
- dashboard widget for a quick outlook on overall transaction statuses;
- detailed transaction details are reported for each order, for both admin staff and your clients;
- multi-language support (romanian translation included);
- detailed journaling.

## Supported transaction statuses (actions)
<a name="lvdwcmc-transaction-statuses"></a>  

| Type | Description | Notes |
| --- | --- | --- |
| confirmed | The amount has been transfered and is entering the settlement process. | If the amount reported as confirmed is the same as the amount initially paid, the order may be fulfilled. Otherwise, it is placed on hold. |
| confirmed_pending | The transaction's fraud risk is being assessed. The amount is being transfered. If everything checks out, the transaction moves to `confirmed` state. | The order is placed on hold. |
| paid_pending | The transaction's fraud risk is being assessed. No transfer has occured at this stage, but the amount is being reserved on the client's card. | The order is placed on hold. |
| canceled | The amount reserved on the client's card is being released. | The order is marked as cancelled. |
| credit | Amount refunded to client (partially or in full). | The refunded amount is recorded in the order state. If the entire amount has been refunded, the order is marked as refunded. |

## Downloading the plug-in
<a name="lvdwcmc-get-it"></a>

You can get the plug-in:

- either from the WordPress plug-in directory: [https://wordpress.org/plugins/wc-mobilpayments-card/](https://wordpress.org/plugins/wc-mobilpayments-card/);
- or from the Releases section of the project's page: [https://github.com/alexboia/LivePayments-MP-WC/releases](https://github.com/alexboia/LivePayments-MP-WC/releases).

## Installation
<a name="lvdwcmc-installation"></a>  

1. Using your favourite FTP client, upload the plugin files to the `/wp-content/plugins/livepayments-mp-wc` directory, or install the plugin through the WordPress plugins screen directly (recommended).
2. Activate the plugin through the 'Plugins' screen in WordPress.

## Setting up and configuring your plug-in
<a name="lvdwcmc-setup"></a>  

1. Retrieve your security assets from mobilPay™'s management interface ([see below](#lvdwcmc-mobilpay-security-assets)).
2. Use the `WooCommerce -> Settings -> Payments -> mobilPay™ Card Gateway` sidebar menu item to access the plug-in configuration page.
3. Fill in the required fields as instructed in the table below.

| Option | How to fill in | Notes |
| --- | --- | --- |
| Enable / Disable  | Check this box to enable mobilPay™ debit or credit card payments | - |
| mobilPay™ Sandbox / Test Mode  | Check this box to conduct safe testing using mobilPay™'s sandbox (without having to perform real transactions) | - |
| Title | Any value you like, but consider telling the customer the name of the payment provider. Default: `MobilPay`. | Payment title the customer will be seing during the check-out process |
| Description  | Any value you like, may be left empty, but it is good practice to use this field to communicate more about the payment provider / payment process. | Payment description the customer will be seing during the check-out process |
| Seller Account ID  | Your merchant account identifier, as provided by mobilPay™, in the following format: `XXXX-XXXX-XXXX-XXXX-XXXX`. | [See below](#lvdwcmc-mobilpay-security-assets) |
| Return URL  | A page where your client will be redirected after completing the payment process. Absolute URL's must be used, but you can very well click `Geneate it for me` to let the plug-in handle everything | [See below](#lvdwcmc-returnurls) |
| --- | --- | --- |
| mobilPay™ digital certificate for the live environment | Use this field to upload the `live.XXXX-XXXX-XXXX-XXXX-XXXX.public.cer` public certificate file obtained from mobilPay™. | [See below](#lvdwcmc-mobilpay-security-assets) |
| The private key for the live environment | Use this field to upload the `live.XXXX-XXXX-XXXX-XXXX-XXXXprivate.key` private key file obtained from mobilPay™. | [See below](#lvdwcmc-mobilpay-security-assets) |
| --- | --- | --- |
| mobilPay™ digital certificate for the sandbox environment | Use this field to upload the `sandbox.XXXX-XXXX-XXXX-XXXX-XXXX.public.cer` public certificate file obtained from mobilPay™. | [See below](#lvdwcmc-mobilpay-security-assets) |
| The private key for the sandbox environment | Use this field to upload the `sandbox.XXXX-XXXX-XXXX-XXXX-XXXXprivate.key` private key file obtained from mobilPay™. | [See below](#lvdwcmc-mobilpay-security-assets) |

## Retrieving mobilPay™ security assets
<a name="lvdwcmc-mobilpay-security-assets"></a>    

To retrieve the seller account ID (`XXXX-XXXX-XXXX-XXXX-XXXX`), the live public certificate file (`live.XXXX-XXXX-XXXX-XXXX-XXXX.public.cer`) and the live private key file (`live.XXXX-XXXX-XXXX-XXXX-XXXXprivate.key`):

1. Going to `https://admin.mobilpay.ro`;
2. Navigating to `Admin -> Seller accounts -> Modify` (for the seller you want to retrieve the assets) `-> Security Settings tab`.

To retrieve the sandbox public certificate file (`sandbox.XXXX-XXXX-XXXX-XXXX-XXXX.public.cer`) and the sandbox private key file (`sandbox.XXXX-XXXX-XXXX-XXXX-XXXXprivate.key`), you must first synchronize the sandbox environment (if you have not done so yet or if you did, but your merchant account has been modified):

1. Go to `https://admin.mobilpay.ro`;
2. Navigate to `Admin -> Seller accounts -> Modify` (for the seller you want to sync) `-> General Information tab`;
3. Click the `Synchronization applications (to Sandbox)` button.

Then you can go on to the sandbox environment and download your sandbox assets:

1. Go to `https://admin.mobilpay.ro`;
2. Navigate to `Implementation -> Test Implementation`;
3. Then, in the sandbox environment, navigate to `Admin -> Seller accounts -> Modify` (for the seller you want to sync) `-> Security Settings tab`.

## Return URLs
<a name="lvdwcmc-returnurls"></a>  

A return URL is the absolute URL (starting with the `http://` or `https://` thingy) to a page where your customer is being redirected after completing the payment process.  
The payment gateway sends the customer here, but it is your job to set up such page to, at the very least, thank him for his business.

`LivePayments-MP-WC` offers a shortcode that you can embed into one of your WordPress pages (post type=`page`, created via `Pages -> Add new`): `[lvdwcmc_display_mobilpay_order_status]`.  
After that, you can copy the absolute URL (ex. `http://uberstorethebestintown.com/thank-you-dude`) of that page and place it in the `Return URL field` [mentioned above](#lvdwcmc-setup).

However, I only mention this manual and rather unfriendly procedure because you might already have a page defined for this purpose, and just want to embed the status in it. But, if you do not, then let the plug-in handle this for you and click the `Geneate it for me` [mentioned above](#lvdwcmc-setup), which will generate this page for you, with the following attributes:

- post author: current user;
- post content: `[lvdwcmc_display_mobilpay_order_status]`;
- post title: `Thank you for your order` (translated to your current language, if such a translation exists);
- post slug: `lvdwcmc-thank-you`;
- post status: `publish`;
- post type: `page`;
- comment status: `closed`;
- ping status: `closed`.

## Gateway Readiness
<a name="lvdwcmc-gateway-readiness"></a>

If the mobilPay™ card payment gateway requires further configuration, the plug-in displays a banner in which you are informed what information is needed for the gateway to be able to process payments.
The banner is shown in the following places:
- payment gateway listing (Payment tab);
- our own payment gateway settings form.

Below is a sample of how such a banner would look like:
![Sample gateway readiness banner](/screenshots/lvdwcmc-sample-gateway-notready-banner.png?raw=true)

To disable this feature, simply set the following constant in your `wp-config.php` file:

```php
define('LVD_WCMC_SHOW_GATEWAY_READINESS_BANNER', false);
```

## Gateway Diagnostics
<a name="lvdwcmc-gateway-diagnostics"></a>    

Similar to the Stargate, the built-in mobilPay™ card payment gateway now offers a diagnostic feature which notifies you whether and when something is wrong. 
The following parameters are monitored:

- Whether or not your return URL is still a valid URL; 
- Whether or not your return URL points to a valid local WordPress page (this bit is optional actually, see more details below);
- Whether or not your payment assets are still valid: makes sure that payment asset file is not empty and that its content is what it is supposed to be (i.e. valid public key certificate data or private key data).

These checks are only being performed if the payment gateway has been configured (i.e. all the necessary information has been filled in and saved).
The diagnostic messages are presented as follows:

- In the WooCommerce payment gateway listing page;
- In the the plug-in's payment gateway configuration page;
- In a newly created, dedicated, plug-in diagnostic page (`Livepayments-MP-WC > Plugin Diagnostics` menu);
- Via e-mail, if activated in the plug-in settings page (`Livepayments-MP-WC > Plugin Settings` menu), delivered to an e-mail address of your chosing (defaults to the site administrator's e-mail address): the plug-in will scan the payment gateway once a day and deliver an e-mail warning notification if issues are found.

### Return URL validation

When validationg the return URL, there is also an optional check to be performed, whether or not that URL corresponds to an existing local WordPress page or post.
This has been intentionally left inactive by default because, if left active by default, it might interefere with valid configurations which have either:
- an external return URL or 
- a valid non-WordPress return URL.

In a situation like this, the diagnostics feature will issue false warnings. 
To enable it, simply define the following constant in `wp-config.php`:

```php
define('LVD_WCMC_VALIDATE_MOBILPAY_URL_AS_LOCAL_PAGE', true);
```

### Customizing the warning e-mail template

The e-mail sent when some issues are found for the mobilPay™ card payment gateway is fully integrated in WooCommerce's e-mail system. 
As such, not only does it use the standard e-mail templates, but is also customizable: simply go to `WooCommerce > Settings > Emails` and look for `	LivePayments - mobilPay Card WooCommerce Payment Gateway - Gateway diagnostics warning e-mail`.

## Screenshots
<a name="lvdwcmc-screenshots"></a>    

#### The settings screen

![The settings screen](/screenshots/lvdwcmc-settings.png?raw=true)

#### Admin order page - transaction status information

![The order page - transaction status information](/screenshots/lvdwcmc-order-page.png?raw=true)

#### Thank you page content

![Thank you page content](/screenshots/lvdwcmc-thank-you-page.png?raw=true)

#### Admin transaction history

![Admin transaction history](/screenshots/lvdwcmc-tx-history.png?raw=true)

#### Admin transaction history - view details

![Admin transaction history - view details](/screenshots/lvdwcmc-tx-details.png?raw=true)

#### Admin dashboard widget

![Admin dashboard widget](/screenshots/lvdwcmc-tx-dashboard-widget.png?raw=true)

#### Frontend order page - transaction status information

![Frontend order page - transaction status information](/screenshots/lvdwcmc-frontend-order-page.png?raw=true)

#### Plugin Settings page

![Plugin Settings page](/screenshots/lvdwcmc-plugin-settings.png?raw=true)

#### Plugin Diagnostics page

![Plugin Diagnostics page](/screenshots/lvdwcmc-plugin-diagnostics.png?raw=true)

## Requirements
<a name="lvdwcmc-requirements"></a>  

### For running the plug-in itself

1. PHP version 5.6.2 or greater;
2. MySQL version 5.7 or greater;
3. WordPress 5.0 or greater;
4. WooCommerce 3.2.0 or greater;
5. openssl extension;
6. mysqli extension;
7. mbstring - not strictly required, but recommended;
8. zlib - not strictly required, but recommended.

### For development

All of the above, with the following amendments:

1. PHP version 5.4.0 or greater is required;
2. xdebug extension is recommended;
3. phpunit version 5.x installed and available in your $PATH, for running the tests;
4. wp (wp-cli) version 2.x installed and available in your $PATH, for initializing the test environment, if needed
5. phpcompatinfo version 5.x installed and available in your $PATH, for generating the compatibility information files
6. cygwin, for Windows users, such as myself, for setting up the development environment, running unit tests and the build scripts, with the following requirements itself:
   - wget command;
   - curl command;
   - gettext libraries;
   - php core engine and the above-mentioned php extensions;
   - mysql command line client;
   - subversion command line client;
   - zip command.

## Credits
<a name="lvdwcmc-credits"></a>  

1. [PHP-MySQLi-Database-Class](https://github.com/joshcam/PHP-MySQLi-Database-Class) - small mysqli wrapper for PHP. I used it instead of the builtin wpdb class.
2. [MimeReader](http://social-library.org/) - PHP mime sniffer written by Shane Thompson.
3. [Payment gateway integration libary provided by mobilPay](https://github.com/mobilpay/PHP_CARD).
4. [URI.js](https://github.com/medialize/URI.js) - JavaScript URI builder and parser.
5. [Toastr](https://github.com/CodeSeven/toastr) - Javascript library for non-blocking notifications.
6. [blockUI](https://github.com/malsup/blockui/) - jQuery modal view plug-in.
7. [kite](http://code.google.com/p/kite/) - super small and simple JavaScript template engine.

## License
<a name="lvdwcmc-license"></a> 

The source code is published under the terms of the [BSD New License](https://opensource.org/licenses/BSD-3-Clause) licence.

## Donate

I put some of my free time into developing and maintaining this plugin.
If helped you in your projects and you are happy with it, you can...

[![ko-fi](https://www.ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/Q5Q01KGLM)