<p align="center">
    <img align="center" width="800" height="324" src="https://raw.githubusercontent.com/alexboia/LivePayments-MP-WC/master/logo.png?v=3" style="margin-bottom: 20px; margin-right: 20px;" />
</p>

# LivePayments - mobilPay Card WooCommerce Payment Gateway
LivePayments is a Credit & Debit Card WooCommerce Payment Gateway that uses the Romanian mobilPay payment processor.
This plugin is meant to be used by merchants in Romania.

## Contents

1. [Is it good for you?](#lvdwcmc-isitgoodforyou)
2. [Supported transaction statuses (actions)](#lvdwcmc-transaction-statuses)
3. [Installation](#lvdwcmc-installation)
4. [Setting up and configuring your plug-in](#lvdwcmc-setup)
5. [Retrieving mobilPay™ security assets](#lvdwcmc-mobilpay-security-assets)
6. [Screenshots](#lvdwcmc-screenshots)
7. [Credits](#lvdwcmc-credits)
8. [License](#lvdwcmc-license)

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

## Requirements

### For running the plug-in itself

1. PHP version 5.6.2 or greater;
2. MySQL version 5.7 or greater;
3. WordPress 5.0 or greater;
4. openssl extension;
5. mysqli extension;
6. mbstring - not strictly required, but recommended;
7. zlib - not strictly required, but recommended.

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
3. [Payment gateway integration libary provide by mobilPay](https://github.com/mobilpay/PHP_CARD).
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