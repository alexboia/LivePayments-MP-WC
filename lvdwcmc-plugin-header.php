<?php
/**
 * Copyright (c) 2019-2021 Alexandru Boia
 *
 * Redistribution and use in source and binary forms, with or without modification, 
 * are permitted provided that the following conditions are met:
 * 
 *	1. Redistributions of source code must retain the above copyright notice, 
 *		this list of conditions and the following disclaimer.
 *
 * 	2. Redistributions in binary form must reproduce the above copyright notice, 
 *		this list of conditions and the following disclaimer in the documentation 
 *		and/or other materials provided with the distribution.
 *
 *	3. Neither the name of the copyright holder nor the names of its contributors 
 *		may be used to endorse or promote products derived from this software without 
 *		specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, 
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY 
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES 
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, 
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) 
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED 
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * The LivePayments-MP-WC plug-in header defines all the required constants
 * 
 * @package LivePayments-MP-WC
 */

defined('ABSPATH') or die;

/**
 * Marker constant for establihing that 
 *  LivePayments-MP-WC core has been loaded.
 * All other files must check for the existence 
 *  of this constant  and die if it's not present.
 * 
 * @var boolean
 */
define('LVD_WCMC_LOADED', true);

/**
 * Contains the plug-in identifier, used internally in various places, 
 *  such as an option prefix.
 * 
 * @var string
 */
define('LVD_WCMC_PLUGIN_ID', 'lvd_wc_mc');

/**
 * The current version of LivePayments-MP-WC.
 *  Eg. 0.1.1.
 * 
 * @var string
 */
define('LVD_WCMC_VERSION', '0.1.6');

/**
 * The absolute path to this file - the plug-in header file
 * 
 * @var string
 */
define('LVD_WCMC_HEADER', __FILE__);

/**
 * The absolute path to the plug-in's functions file - lvdwcmc-plugin-functions.php
 * 
 * @var string
 */
define('LVD_WCMC_FUNCTIONS', __DIR__ . '/lvdwcmc-plugin-functions.php');

/**
 * The absolute path to the main plug-in file - lvdwcmc-plugin-main.php
 * 
 * @var string
 */
define('LVD_WCMC_MAIN', __DIR__ . '/lvdwcmc-plugin-main.php');

/**
 * The absolute path to the plug-in's installation directory.
 *  Eg. /whatever/public_html/wp-content/plugins/livepayments-mp-wc.
 * 
 * @var string
 */
define('LVD_WCMC_ROOT_DIR', __DIR__);

/**
 * The absolute path to the plug-in's library - lib - directory.
 *  This is where all the PHP dependencies are stored.
 *  Eg. /whatever/public_html/wp-content/plugins/livepayments-mp-wc/lib.
 * 
 * @var string
 */
define('LVD_WCMC_LIB_DIR', LVD_WCMC_ROOT_DIR . '/lib');

/**
 * The absolute path to the plug-in's 3rd party libraries - lib/3rdParty - directory.
 *  This is where all the 3rdParty PHP dependencies are stored.
 *  Eg. /whatever/public_html/wp-content/plugins/livepayments-mp-wc/lib/3rdParty.
 * 
 * @var string
 */
define('LVD_WCMC_LIB_3RDPARTY_DIR', LVD_WCMC_LIB_DIR . '/3rdParty');

/**
 * The absolute path to the plug-in's views - views - directory.
 *  This is where all the templates are stored.
 *  Eg. /whatever/public_html/wp-content/plugins/livepayments-mp-wc/views.
 * 
 * @var string
 */
define('LVD_WCMC_VIEWS_DIR', LVD_WCMC_ROOT_DIR . '/views');

/**
 * The absolute path to the plug-in's translation files - lang - directory.
 *  This is where all the translation files (.po, .mo, .pot) are stored.
 *  Eg. /whatever/public_html/wp-content/plugins/livepayments-mp-wc/lang.
 * 
 * @var string
 */
define('LVD_WCMC_LANG_DIR', LVD_WCMC_ROOT_DIR . '/lang');

/**
 * The absolute path to the plug-in's own data files - data - directory.
 *  This is where all the data files that are bundled 
 *  (that is, not generated during normal usage) 
 *  with the plug-in are stored.
 *  Eg. /whatever/public_html/wp-content/plugins/livepayments-mp-wc/data.
 * 
 * @var string
 */
define('LVD_WCMC_DATA_DIR', LVD_WCMC_ROOT_DIR . '/data');

/**
 * The plug-in text domain.
 * 
 * @var string
 */
define('LVD_WCMC_TEXT_DOMAIN', 'livepayments-mp-wc');

/**
 * The name of the file upload file that, 
 *  albeit hidden from view, is used to 
 *  upload the track file to the server.
 * 
 * @var string
 */
define('LVD_WCMC_PAYMENT_ASSET_UPLOAD_KEY', 'payment_asset_file');

/**
 * The identifier for the woocommerce mobilPay credit card payment gateway.
 * @see \LvdWcMc\MobilpayCreditCardGateway
 * @var string
 */
define('LVD_WCMC_WOOCOMMERCE_CC_GATEWAY_ID', 'lvd_wc_mc_mobilpay_cc_gateway');

if (!defined('LVD_WCMC_PAYMENT_ASSET_UPLOAD_CHUNK_SIZE')) {
    /**
     * The chunk size is the maxim file chunk, expressed in bytes, 
     *      that can be uploaded in one sitting.
     *  If a file is larger than this size, it will be split in multiple chunks, 
     *      which will be uploaded in sequence.
     * Can be overridden in wp-config.php.
     * 
     * @var int
     */
    define('LVD_WCMC_PAYMENT_ASSET_UPLOAD_CHUNK_SIZE', 102400);
}

if (!defined('LVD_WCMC_PAYMENT_ASSET_UPLOAD_MAX_FILE_SIZE')) {
    /**
     * The maximum size, in bytes, the the plug-in allows for the track file. 
     * That is, track files larger than this are rejected.
     * Defaults to 10485760 or wp_max_upload_size(), whichever is larger.
     * 
     * @var int
     */
    define('LVD_WCMC_PAYMENT_ASSET_UPLOAD_MAX_FILE_SIZE', max(wp_max_upload_size(), 10485760));
}

if (!defined('LVD_WCMC_RECORDS_PER_PAGE')) {
    /**
     * The number of records per page displayed 
     *  by LivePayments-MP-WC in listings that it manages 
     *  (such as payment transactions listing).
     * 
     * @var int
     */
    define('LVD_WCMC_RECORDS_PER_PAGE', 25);
}

if (!defined('LVD_WCMC_SHOW_GATEWAY_READINESS_BANNER')) {
    /**
     * Whether to show the gateway readiness banner in the following sections:
     *  - payment gateway listing (Payment tab);
     *  - our own payment gateway settings form.
     * 
     * @var boolean
     */
    define('LVD_WCMC_SHOW_GATEWAY_READINESS_BANNER', true);
}

if (!defined('LVD_WCMC_VALIDATE_MOBILPAY_URL_AS_LOCAL_PAGE')) {
    /**
     * Whether or not to validate the payment gateway's return URL 
     *  as a valid local WordPress page or post 
     *  when performing gateway diagnostics
     * 
     * @var boolean
     */
    define('LVD_WCMC_VALIDATE_MOBILPAY_URL_AS_LOCAL_PAGE', false);
}