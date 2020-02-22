<?php
/**
 * Copyright (c) 2019-2020 Alexandru Boia
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

namespace LvdWcMc {
    class MobilpayCreditCardGateway extends \WC_Payment_Gateway {
        use LoggingExtensions;

        const GATEWAY_PROCESS_RESPONSE_ERR_OK = 0x0000;

        const GATEWAY_PROCESS_RESPONSE_ERR_APPLICATION = 0x1000;

        const GATEWAY_ID = 'lvd_wc_mc_mobilpay_cc_gateway';

        const GATEWAY_MODE_LIVE = 'live';

        const GATEWAY_MODE_SANDBOX = 'sandbox';

        /**
         * @var \LvdWcMc\Env Reference to the environment object
         */
        private $_env;

        /**
         * @var \LvdWcMc\MobilpayCardPaymentProcessor Reference to the payment processor
         */
        private $_processor;

        /**
         * @var \LvdWcMc\MobilpayTransactionFactory Reference to the transaction factory
         */
        private $_transactionFactory = null;

        /**
         * @var \LvdWcMc\MediaIncludes Reference to the media includes manager
         */
        private $_mediaIncludes = null;

        /**
         * @var string The IPN url to which mobilpay will post the payment response
         */
        private $_mobilpayNotifyUrl = null;

        /**
         * @var string The API descriptor is the identifier based on which the above IPN url is formed (as WC api request URL)
         */
        private $_apiDescriptor = null;

        /**
         * @var string The current environment (production/sandbox)
         */
        private $_mobilpayEnvironment = null;

        /**
         * @var string The merchant account identifier, that the user retrieves from the mobilpay administration panel
         */
        private $_mobilpayAccountId = null;

        /**
         * @var string The configured return URL, to which the paying customer is returned after paying
         */
        private $_mobilpayReturnUrl = null;

        /**
         * @var string Where the mobilpay assets (certificate and private key) are stored
         */
        private $_mobilpayAssetsDir = null;

        private $_mobilpayAssetUploadApiDescriptor = null;

        private $_mobilpayAssetUploadUrl = null;

        private $_mobilpayAssetRemoveApiDescriptor = null;

        private $_mobilpayAssetRemoveUrl = null;

        private $_returnUrlGenerationApiDescriptor = null;

        private $_returnUrlGenerationUrl = null;

        /**
         * @var WC_Logger The logger instance used by this gateway
         */
        private $_logger = null;

        public static function matchesGatewayId($gatewayId) {
            return $gatewayId == self::GATEWAY_ID;
        }

        public function __construct() {
            $this->id = self::GATEWAY_ID;
            $this->plugin_id = LVD_WCMC_PLUGIN_ID;
            $this->_logger = wc_get_logger();

            $this->method_title = __('mobilPay&trade; Card Gateway', 'wc-mobilpayments-card');
            $this->method_description = __('mobilPay&trade; Payment Gateway for WooCommerce', 'wc-mobilpayments-card');
            
            $this->title = __('mobilPay&trade; Card Gateway', 'wc-mobilpayments-card');

            $this->supports = array(
                'products', 
                'refunds'
            );

            $this->_env = lvdwcmc_plugin()->getEnv();
            $this->_mediaIncludes = lvdwcmc_plugin()->getMediaIncludes();
            $this->_processor = new MobilpayCardPaymentProcessor();
            $this->_transactionFactory = new MobilpayTransactionFactory();

            $this->_apiDescriptor = strtolower(str_replace('\\', '_', __CLASS__));
            $this->_mobilpayNotifyUrl = WC()->api_request_url($this->_apiDescriptor);
            $this->_mobilpayAssetsDir = $this->_env->getPaymentAssetsStorageDir();

            $this->_mobilpayAssetUploadApiDescriptor = strtolower(self::GATEWAY_ID . '_payment_asset_upload');
            $this->_mobilpayAssetUploadUrl = WC()->api_request_url($this->_mobilpayAssetUploadApiDescriptor);

            $this->_mobilpayAssetRemoveApiDescriptor = strtolower(self::GATEWAY_ID . '_payment_asset_remove');
            $this->_mobilpayAssetRemoveUrl = WC()->api_request_url($this->_mobilpayAssetRemoveApiDescriptor);

            $this->_returnUrlGenerationApiDescriptor = strtolower(self::GATEWAY_ID . '_generate_return_url');
            $this->_returnUrlGenerationUrl = WC()->api_request_url($this->_returnUrlGenerationApiDescriptor);

            add_action('woocommerce_api_' . $this->_apiDescriptor, array($this, 'process_gateway_response'), 10, 1);
            add_action('woocommerce_receipt_' . $this->id, array($this, 'show_payment_initiation'), 10, 1);
            add_action('woocommerce_email_after_order_table', array($this, 'add_transaction_details_to_email'), 10, 4);

            if (is_admin()) {
                add_action('admin_enqueue_scripts', array($this, 'enqueue_form_scripts'));
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options'));
            }

            add_action('woocommerce_api_' . $this->_mobilpayAssetUploadApiDescriptor, array($this, 'process_payment_asset_upload'));
            add_action('woocommerce_api_' . $this->_mobilpayAssetRemoveApiDescriptor, array($this, 'process_payment_asset_remove'));
            add_action('woocommerce_api_' . $this->_returnUrlGenerationApiDescriptor, array($this, 'generate_return_url'));

            $this->init_form_fields();
            $this->init_settings();
        }

        public function enqueue_form_scripts() {
            $this->_mediaIncludes
                ->includeStyleSettings();
            $this->_mediaIncludes
                ->includeScriptSettings();
            $this->_mediaIncludes
                ->localizeSettingsScript($this->_getSettingsScriptTranslations());
        }

        public function process_admin_options() {
            if (!$this->_canManageWcSettings()) {
                return false;
            }

            $renameOk = true;
            $result = parent::process_admin_options();
            $mobilpayAccountId = $this->get_option('mobilpay_account_id');

            if ($this->_mobilpayAccountId != $mobilpayAccountId) {
                foreach ($this->_getPaymentAssetFields() as $fieldId => $fieldInfo) {
                    $oldFilePath = $this->_getPaymentAssetFilePathFromFieldInfo($fieldInfo, 
                        $this->_mobilpayAccountId);
                    $newFilePath = $this->_getPaymentAssetFilePathFromFieldInfo($fieldInfo, 
                        $mobilpayAccountId);

                    if (file_exists($oldFilePath)) {
                        if (!@rename($oldFilePath, $newFilePath)) {
                            $renameOk = false;
                        }
                    }
                }
            }

            if ($result) {
                $this->init_settings();
            }

            return $result && $renameOk;
        }

        public function init_settings() {
            parent::init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            $this->_mobilpayEnvironment = $this->get_option('mobilpay_environment');
            $this->_mobilpayAccountId = $this->get_option('mobilpay_account_id');
            $this->_mobilpayReturnUrl = $this->get_option('mobilpay_return_url');
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable / Disable', 'wc-mobilpayments-card'),
                    'label' => __('Enable this payment gateway', 'wc-mobilpayments-card'),
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                'mobilpay_environment' => array(
                    'title' => __('mobilPay&trade; Sandbox / Test Mode', 'wc-mobilpayments-card'),
                    'label' => __('Enable Test Mode', 'wc-mobilpayments-card'),
                    'type' => 'checkbox',
                    'description' => __('Place the payment gateway in test mode.', 'wc-mobilpayments-card'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => __('Title', 'wc-mobilpayments-card'),
                    'type' => 'text',
                    'desc_tip' => __('Payment title the customer will see during the checkout process.', 'wc-mobilpayments-card'),
                    'default' => __('MobilPay', 'wc-mobilpayments-card')
                ),
                'description' => array(
                    'title' => __('Description', 'wc-mobilpayments-card'),
                    'type' => 'textarea',
                    'desc_tip' => __('Payment description the customer will see during the checkout process.', 'wc-mobilpayments-card'),
                    'css' => 'max-width:350px;'
                ),
                'mobilpay_account_id' => array(
                    'title'	=> __('Seller Account ID', 'wc-mobilpayments-card'),
                    'type'	=> 'text',
                    'description' => __('This is Account ID provided by MobilPay when you signed up for an account. Unique key for your seller account for the payment process.', 'wc-mobilpayments-card')
                ),
                'mobilpay_return_url' => array(
                    'title'	=> __('Return URL', 'wc-mobilpayments-card'),
                    'type'	=> 'return_url',
                    'description' => __('You must create a new page and in the content field enter the shortcode [lvdwcmc_display_mobilpay_order_status] so that the user can see the message that is returned by the Mobilpay server regarding their transaction. Or any content you want to thank for buying.', 'wc-mobilpayments-card'),
                    'desc_tip' => true
                ),
                'mobilpay_live_public_cert' => array(
                    'title' => __('mobilPay&trade; digital certificate for the live environment', 'wc-mobilpayments-card'),
                    'description' => __('The public key used for securing communication with the mobilPay&trade; gateway in the live environment.', 'wc-mobilpayments-card'),
                    'type' => 'mobilpay_asset_upload',
                    'environment' => self::GATEWAY_MODE_LIVE,
                    'desc_tip' => true,
                    'allowed_files_hints' => 'allowed file types: .cer',
                    '_file_format' => 'live.%s.public.cer'
                ),
                'mobilpay_live_private_key' => array(
                    'title' => __('The private key for the live environment', 'wc-mobilpayments-card'),
                    'description' => __('The private key used for securing communication with the mobilPay&trade; gateway in the live environment.', 'wc-mobilpayments-card'),
                    'type' => 'mobilpay_asset_upload',
                    'environment' => self::GATEWAY_MODE_LIVE,
                    'desc_tip' => true,
                    'allowed_files_hints' => 'allowed file types: .key',
                    '_file_format' => 'live.%s.private.key'
                ),
                'mobilpay_sandbox_public_cert' => array(
                    'title' => __('mobilPay&trade; digital certificate for the sandbox environment', 'wc-mobilpayments-card'),
                    'description' => __('The public key used for securing communication with the mobilPay&trade; gateway in the sandbox environment (used when "MobilPay Sandbox / Test Mode"  is checked).', 'wc-mobilpayments-card'),
                    'type' => 'mobilpay_asset_upload',
                    'environment' => self::GATEWAY_MODE_SANDBOX,
                    'desc_tip' => true,
                    'allowed_files_hints' => 'allowed file types: .cer',
                    '_file_format' => 'sandbox.%s.public.cer'
                ),
                'mobilpay_sandbox_private_key' => array(
                    'title' => __('The private key for the sandbox environment', 'wc-mobilpayments-card'),
                    'description' => __('The private key used for securing communication with the mobilPay&trade; gateway in the sandbox environment (used when "MobilPay Sandbox / Test Mode"  is checked).', 'wc-mobilpayments-card'),
                    'type' => 'mobilpay_asset_upload',
                    'environment' => self::GATEWAY_MODE_SANDBOX,
                    'desc_tip' => true,
                    'allowed_files_hints' => 'allowed file types: .key',
                    '_file_format' => 'sandbox.%s.private.key'
                )
            );
        }

        private function _renderAdminOptionsJSSettings() {
            ob_start();
            $data = new \stdClass();
            $data->uploadPaymentAssetUrl = $this->_mobilpayAssetUploadUrl;
            $data->uploadPaymentAssetNonce = wp_create_nonce($this->_mobilpayAssetUploadApiDescriptor);

            $data->removePaymentAssetUrl = $this->_mobilpayAssetRemoveUrl;
            $data->removePaymentAssetNonce = wp_create_nonce($this->_mobilpayAssetRemoveApiDescriptor);

            $data->returnUrlGenerationUrl = $this->_returnUrlGenerationUrl;
            $data->returnUrlGenerationNonce = wp_create_nonce($this->_returnUrlGenerationApiDescriptor);

            $data->uploadMaxFileSize = LVD_WCMC_PAYMENT_ASSET_UPLOAD_MAX_FILE_SIZE;
            $data->uploadChunkSize = LVD_WCMC_PAYMENT_ASSET_UPLOAD_CHUNK_SIZE;
            $data->uploadKey = LVD_WCMC_PAYMENT_ASSET_UPLOAD_KEY;

            require $this->_env->getViewFilePath('lvdwcmc-gateway-settings-js.php');
            return ob_get_clean();
        }

        public function admin_options() {
            parent::admin_options();
            echo $this->_renderAdminOptionsJSSettings();
        }

        public function get_tooltip_html($fieldInfo) {
            if (true === $fieldInfo['desc_tip']) {
                $tip = $fieldInfo['description'] . (!empty($fieldInfo['allowed_files_hints']) 
                    ? sprintf(' (%s)', $fieldInfo['allowed_files_hints']) 
                    : '');
            } elseif (!empty( $fieldInfo['desc_tip'])) {
                $tip = $fieldInfo['desc_tip'];
            } else {
                $tip = '';
            }
    
            return $tip ? wc_help_tip($tip, true) : '';
        }

        private function _renderPaymentAssetUploadField($fieldId, $fieldInfo) {
            $assetFilePath = $this->_getPaymentAssetFilePathFromFieldInfo($fieldInfo, $this->_mobilpayAccountId);

            $data = new \stdClass();
            $data->hasAsset = is_readable($assetFilePath);
            $data->fieldId = $fieldId;
            $data->fieldInfo = $fieldInfo;

            ob_start();
            require $this->_env->getViewFilePath('lvdwcmc-gateway-upload-asset-field.php');
            return ob_get_clean();
        }

        public function generate_mobilpay_asset_upload_html($fieldId, $fieldInfo) {
            return $this->_renderPaymentAssetUploadField($fieldId, $fieldInfo);
        }

        private function _renderReturnUrlField($fieldId, $fieldInfo) {
            $data = new \stdClass();
            $data->fieldId = $fieldId;
            $data->fieldInfo = $fieldInfo;
            $data->returnUrl = $this->_mobilpayReturnUrl;

            ob_start();
            require $this->_env->getViewFilePath('lvdwcmc-gateway-return-url-field.php');
            return ob_get_clean();
        }

        public function generate_return_url_html($fieldId, $fieldInfo) {
            return $this->_renderReturnUrlField($fieldId, $fieldInfo);
        }

        public function validate_return_url_field($key, $value) {
            $returnUrl = null;
            if (isset($_POST['mobilpay_return_url'])) {
                $returnUrl = filter_var($_POST['mobilpay_return_url'], FILTER_VALIDATE_URL) 
                    ? $_POST['mobilpay_return_url'] 
                    : null;
            }
            return $returnUrl;
        }

        public function process_payment_asset_upload() {
            if (!$this->_canManageWcSettings() || !$this->_validatePaymentAssetUploadNonce()) {
                http_response_code(401);
                die;
            }

            $assetId = $this->_getAssetIdFromRequest();
            $fieldInfo = $this->_getFieldInfo($assetId);

            if (!$this->_isPaymentAssetField($fieldInfo)) {
                http_response_code(404);
                die;
            }

            if (LVD_WCMC_PAYMENT_ASSET_UPLOAD_CHUNK_SIZE > 0) {
                $chunk = isset($_REQUEST['chunk']) 
                    ? intval($_REQUEST['chunk']) 
                    : 0;
                $chunks = isset($_REQUEST['chunks']) 
                    ? intval($_REQUEST['chunks']) 
                    : 0;
            } else {
                $chunk = $chunks = 0;
            }
            
            $destination = $this->_getPaymentAssetFilePathFromFieldInfo($fieldInfo, 
                $this->_mobilpayAccountId);

            $uploader = new Uploader(LVD_WCMC_PAYMENT_ASSET_UPLOAD_KEY, $destination, array(
                'chunk' => $chunk, 
                'chunks' => $chunks, 
                'chunkSize' => LVD_WCMC_PAYMENT_ASSET_UPLOAD_CHUNK_SIZE, 
                'maxFileSize' => LVD_WCMC_PAYMENT_ASSET_UPLOAD_MAX_FILE_SIZE, 
                'allowedFileTypes' => array()));

            $result = new \stdClass();
            $result->status = $uploader->receive();
            $result->ready = $uploader->isReady();

            lvdwcmc_send_json($result);
        }

        public function process_payment_asset_remove() {
            if (!$this->_canManageWcSettings() || !$this->_validatePaymentAssetRemoveNonce()) {
                http_response_code(401);
                die;
            }

            $assetId = $this->_getAssetIdFromRequest();
            $fieldInfo = $this->_getFieldInfo($assetId);

            if (!$this->_isPaymentAssetField($fieldInfo)) {
                http_response_code(404);
                die;
            }

            $destination = $this->_getPaymentAssetFilePathFromFieldInfo($fieldInfo, 
                $this->_mobilpayAccountId);

            if (file_exists($destination)) {
                @unlink($destination);
            }

            $result = lvdwcmc_get_ajax_response();
            $result->success = !file_exists($destination);
            $result->message = $result->success
                ? __('Payment asset file successfully removed.', 'wc-mobilpayments-card')
                : __('Payment asset file could not be removed.', 'wc-mobilpayments-card');

            lvdwcmc_send_json($result);
        }

        public function generate_return_url() {
            if (!$this->_canManageWcSettings()) {
                http_response_code(401);
                die;
            }

            $slug = 'lvdwcmc-thank-you';
            $page = get_page_by_path($slug, OBJECT, 'page');

            if ($page == null) {
                $pageId = wp_insert_post(array(
                    'post_author' => get_current_user_id(),
                    'post_content' => '[lvdwcmc_display_mobilpay_order_status]',
                    'post_title' => __('Thank you for your order', 'wc-mobilpayments-card'),
                    'post_name' => $slug,
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed'
                ), true);

                if (!is_wp_error($pageId)) {
                    $page = get_post($pageId, OBJECT, 'raw');
                }
            }

            $result = lvdwcmc_get_ajax_response(array(
                'returnPageUrl' => null
            ));

            if ($page instanceof \WP_Post) {
                $result->success = true;
                $result->returnPageUrl = get_permalink($page);
            }

            lvdwcmc_send_json($result);
        }

        public function needs_setup() {
            //Used by WC when toggling gateway on or off via AJAX 
            //  (see WC_Ajax::toggle_gateway_enabled())
            return empty($this->_mobilpayAccountId) 
                || empty($this->_mobilpayReturnUrl)
                || !$this->_hasPaymentAssets();
        }

        public function is_available() {
            //Used by WC to determine what payment gateways are available on checkout 
            //  (see WC_Payment_Gateways::get_available_payment_gateways())
            return !$this->needs_setup() && parent::is_available();
        }

        public function process_payment($orderId) {
            $context = array(
                'orderId' => $orderId,
                'source' => self::GATEWAY_ID
            );

            $this->logDebug('Begin processing payment for order', $context);

            try {
                $order = wc_get_order($orderId);
                //If the order total is greater than 0,
                //  redirect to the payment page;
                //Otherwise, redirect to the "thank you" page  
                if ($order->get_total() > 0) {
                    $this->logDebug('Order total is greater than 0. Will redirect to payment page', 
                        $context);

                    //Empty cart and create redirection info result
                    $this->_emptyCart();

                    $result = array(
                        'result' => 'success', 
                        'redirect' => 
                            add_query_arg(
                                'key', 
                                $order->get_order_key(), 
                                $order->get_checkout_payment_url(true)
                            )
                    );
                } else {
                    $this->logDebug('Order total is 0. Will complete order and redirect to thank-you page', 
                        $context);

                    //Mark order complete, empty cart and 
                    //  create redirection info result
                    $order->payment_complete();
                    $this->_emptyCart();

                    $result = array(
                        'result' => 'success',
                        'redirect' => $order->get_checkout_order_received_url()
                    );
                }

                $this->logDebug('Done processing payment for order', $context);
            } catch (\Exception $exc) {
                wc_add_notice(__('Error initiating MobilPay card payment.', 'wc-mobilpayments-card'), 'error');
                $this->logException('Error initiating MobilPay card payment', 
                    $exc, 
                    $context);
            }

            return $result;
        }

        public function show_payment_initiation($orderId) {
            $context = array(
                'orderId' => $orderId,
                'source' => self::GATEWAY_ID
            );

            $this->logDebug('Constructing payment form data', $context);

            $data = new \stdClass();
            $order = wc_get_order($orderId);

            if ($order instanceof \WC_Order) {
                try {
                    $mobilpayRequest = $this->_createMobilpayRequest($order);
                    $this->_processor->processOrderInitialized($order, $mobilpayRequest);

                    $data->paymentUrl = $this->_getGatewayEndpointUrl();
                    $data->envKey = $mobilpayRequest->getEnvKey();
                    $data->encData = $mobilpayRequest->getEncData();
                    $data->success = true;

                    $this->logDebug('Successfully constructed payment form data', $context);
                } catch (\Exception $exc) {
                    $this->logException('Failed to construct payment form data', 
                        $exc, 
                        $context);
                }
            } else {
                $this->logDebug('Failed to construct payment form data: order not found', $context);
                $data->success = false;
            }

            require $this->_env->getViewFilePath('lvdwcmc-payment-form.php');
        }

        public function process_gateway_response() {
            $paymentRequest = null;
            $processErrorMessage = null;
            $processErrorCode =  self::GATEWAY_PROCESS_RESPONSE_ERR_OK;

            $context = array(
                'source' => self::GATEWAY_ID,
                'remoteAddress' => $this->_env->getRemoteAddress()
            );

            $this->logDebug('Begin processing gateway callback response.', $context);

            if (!$this->_env->isHttpPost()) {
                $this->logDebug('Invalid HTTP method received from gateway.', $context);
                $this->_sendInvalidGatewayHttpAction(); //will exit
            }

            if (empty($_POST['env_key']) || empty($_POST['data'])) {
                $this->logDebug('Invalid POST data received from gateway.', $context);
                $this->_sendInvalidGatewayPOSTData(); //will exit
            }

            try {
                $paymentRequest = $this->_getPaymentRequestFromPOSTData();
                if (!$this->_isDecryptedRequestValid($paymentRequest)) {
                    $this->logDebug('Invalid payment request data received from gateway.', $context);
                    $this->_sendFailedDecodingGatewayData(); //will exit
                }

                $orderId = isset($paymentRequest->params['_lvdwcmc_order_id']) 
                    ? intval($paymentRequest->params['_lvdwcmc_order_id']) 
                    : 0;

                //Update context with the order id
                $context = array_merge($context, array(
                    'orderId' => $orderId
                ));

                $order = wc_get_order($orderId);
                if (!($order instanceof \WC_Order)) {
                    $this->logDebug('Order not found for ID in payment request', $context);
                    $this->_sendFailedDecodingGatewayData(); //will exit
                }

                $result = $this->_processGatewayAction($order, 
                    $paymentRequest, 
                    $context);

                $processErrorMessage = $result['processErrorMessage'];
                $processErrorCode = $result['processErrorCode'];
            } catch (\Exception $exc) {
                $this->logException('Error processing gateway callback.', 
                    $exc, 
                    $context);

                $processErrorCode = self::GATEWAY_PROCESS_RESPONSE_ERR_APPLICATION;
                $processErrorMessage = sprintf('Internal error occured: %s (#%s)', 
                    $exc->getMessage(), 
                    $exc->getCode());
            }

            if ($processErrorCode != self::GATEWAY_PROCESS_RESPONSE_ERR_OK) {
                $this->logDebug('Sending error response to gateway.', $context);
                $this->_sendErrorResponse(\Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT, 
                    $processErrorCode,
                    $processErrorMessage);
            } else {
                $this->logDebug('Sending success response to gateway.', $context);
                $this->_sendSuccessResponse($paymentRequest->objPmNotify->getCrc());
            }
        }

        public function add_transaction_details_to_email(\WC_Order $order, $sent_to_admin, $plain_text, $email) {
            if ($this->_canAddTransactionDetailsToEmail($order, $sent_to_admin, $plain_text, $email)) {

                $transaction = $this->_transactionFactory->existingFromOrder($order);
                if ($transaction != null) {

                    $data = new \stdClass();
                    $data->mobilpayTransactionId = $transaction->getProviderTransactionId();
                    $data->panMasked = $transaction->getPANMasked();

                    if ($sent_to_admin) {
                        $data->clientIpAddress = $transaction->getIpAddress();
                    } else {
                        $data->clientIpAddress = null;
                    }

                    $data->success = true;

                    require $this->_env->getViewFilePath('lvdwcmc-email-transaction-details.php');
                }
            }
        }

        private function _canAddTransactionDetailsToEmail(\WC_Order $order, $sent_to_admin, $plain_text, $email) {
            return self::matchesGatewayId($order->get_payment_method()) 
                && $order->has_status(array('completed', 'processing'))
                && $email instanceof \WC_Email 
                && ($email->id == 'customer_completed_order' || $email->id == 'customer_processing_order')
                && !$plain_text;
        }

        private function _createMobilpayRequest(\WC_Order $order) {
            $cardPaymentRequest = new \Mobilpay_Payment_Request_Card();
            $cardPaymentRequest->signature = $this->_mobilpayAccountId;
            $cardPaymentRequest->orderId = md5(uniqid(rand()));
    
            $cardPaymentRequest->confirmUrl = $this->_mobilpayNotifyUrl;
            $cardPaymentRequest->returnUrl = trim($this->_mobilpayReturnUrl) . '?order_id=' . $order->get_id();
    
            $cardPaymentRequest->invoice = new \Mobilpay_Payment_Invoice();
            $cardPaymentRequest->invoice->currency = 
                $order->get_currency();
            $cardPaymentRequest->invoice->amount = 
                sprintf('%.2f', $order->get_total());
            $cardPaymentRequest->invoice->details = 
                sprintf(__('Payment for order #%s.', 'wc-mobilpayments-card'), $order->get_order_key());
            
            $billingAndShipping = new \Mobilpay_Payment_Address();
            $billingAndShipping->type = 'person';
            $billingAndShipping->firstName = $order->get_billing_first_name();
            $billingAndShipping->lastName = $order->get_billing_last_name();
            $billingAndShipping->email = $order->get_billing_email();
            $billingAndShipping->fiscalNumber = 'N/A';
            $billingAndShipping->address = $order->get_formatted_billing_address();
            $billingAndShipping->mobilePhone = $order->get_billing_phone();
            
            $cardPaymentRequest->invoice
                ->setBillingAddress($billingAndShipping);
            $cardPaymentRequest->invoice
                ->setShippingAddress($billingAndShipping);
    
            $cardPaymentRequest->params = array(
                '_lvdwcmc_order_id' => $order->get_id(),
                '_lvdwcmc_customer_id' => $order->get_customer_id(),
                '_lvdwcmc_customer_ip' => $order->get_customer_ip_address()
            );

            $cardPaymentRequest->encrypt($this->_getX509CertificateFilePath());
            return $cardPaymentRequest;
        }

        private function _processGatewayAction(\WC_Order $order, \Mobilpay_Payment_Request_Abstract $paymentRequest, $context) {
            $processErrorMessage = null;
            $processResult = self::GATEWAY_PROCESS_RESPONSE_ERR_OK;

            $gatewayErrorCode = $paymentRequest->objPmNotify->errorCode;

            $this->logDebug(sprintf('Received error code="%s" from gateway', $gatewayErrorCode), 
                $context);

            if ($gatewayErrorCode == 0) {
                $gatewayAction = $paymentRequest->objPmNotify->action;

                $this->logDebug(sprintf('Received callback action="%s"  from gateway', $gatewayAction), 
                    $context);

                switch ($gatewayAction) {
                    case 'confirmed':
                        $processResult = $this->_processor->processConfirmedPaymentResponse($order, $paymentRequest);
                    break;
                    case 'confirmed_pending':
                    case 'paid_pending':
                        $processResult = $this->_processor->processPendingPaymentResponse($order, $paymentRequest);
                    break;
                    case 'canceled':
                        $processResult = $this->_processor->processPaymentCancelledResponse($order, $paymentRequest);
                    break;
                    case 'credit':
                        $processResult = $this->_processor->processCreditPaymentResponse($order, $paymentRequest);
                    break;
                }

                if ($processResult != self::GATEWAY_PROCESS_RESPONSE_ERR_OK) {
                    $this->logDebug(sprintf('Order processing failed with error code="%s"', $processResult), 
                        $context);
                } else {
                    $this->logDebug('Order processing succeeded', 
                        $context);
                }
            } else {
                $processResult = $this->_processor->processFailedPaymentResponse($order, $paymentRequest);
            }

            return array(
                'processErrorCode' => $processResult,
                'processErrorMessage' => $processErrorMessage
            );
        }

        private function _getPaymentRequestFromPOSTData() {
            return \Mobilpay_Payment_Request_Abstract::factoryFromEncrypted($_POST['env_key'], $_POST['data'], 
                $this->_getPrivateKeyFilePath());
        }

        private function _isDecryptedRequestValid($paymentRequest) {
            return $paymentRequest instanceof \Mobilpay_Payment_Request_Card
                && isset($paymentRequest->params['_lvdwcmc_order_id']) 
                && isset($paymentRequest->params['_lvdwcmc_customer_id'])
                && isset($paymentRequest->params['_lvdwcmc_customer_ip']);
        }

        private function _sendInvalidGatewayHttpAction() {
            $this->_sendErrorResponse(
                \Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT,
                \Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_ACTION,
                    'Invalid request received');
        }
        
        private function _sendInvalidGatewayPOSTData() {
            $this->_sendErrorResponse(
                \Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT, 
                \Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_PARAMETERS, 
                    'Invalid request parameters received');
        }

        private function _sendFailedDecodingGatewayData() {
            $this->_sendErrorResponse(
                \Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT, 
                \Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_FAILED_DECODING_DATA, 
                    'Invalid request parameters received');
        }

        private function _sendErrorResponse($type, $code, $message) {
            header('Content-type: application/xml');
            echo '<?xml version="1.0" encoding="utf-8"?>';
            echo '<crc error_type="' . $type . '" error_code="' . $code . '">' . $message . '</crc>';
            exit;
        }
    
        private function _sendSuccessResponse($crc) {
            header('Content-type: application/xml');
            echo '<?xml version="1.0" encoding="utf-8"?>';
            echo '<crc>' . $crc . '</crc>';
            exit;
        }

        private function _getSettingsScriptTranslations() {
            return array(
                'errPluploadTooLarge' 
                    => __('The selected file is too large. Maximum allowed size is 10MB', 'wc-mobilpayments-card'), 
                'errPluploadFileType' 
                    => __('The selected file type is not valid.', 'wc-mobilpayments-card'), 
                'errPluploadIoError' 
                    => __('The file could not be read', 'wc-mobilpayments-card'), 
                'errPluploadSecurityError' 
                    => __('The file could not be read', 'wc-mobilpayments-card'), 
                'errPluploadInitError' 
                    => __('The uploader could not be initialized', 'wc-mobilpayments-card'), 
                'errPluploadHttp' 
                    => __('The file could not be uploaded', 'wc-mobilpayments-card'), 
                'errServerUploadFileType' 
                    => __('The selected file type is not valid.', 'wc-mobilpayments-card'), 
                'errServerUploadTooLarge' 
                    => __('The selected file is too large. Maximum allowed size is 10MB', 'wc-mobilpayments-card'), 
                'errServerUploadNoFile' 
                    => __('No file was uploaded', 'wc-mobilpayments-card'), 
                'errServerUploadInternal' 
                    => __('The file could not be uploaded due to a possible internal server issue', 'wc-mobilpayments-card'), 
                'errServerUploadFail' 
                    => __('The file could not be uploaded', 'wc-mobilpayments-card'),
                'warnRemoveAssetFile' 
                    => __('Remove asset file? This action cannot be undone and you will have to re-upload the asset again!', 'wc-mobilpayments-card'),
                'errAssetFileCannotBeRemoved' 
                    => __('The asset file could not be removed', 'wc-mobilpayments-card'),
                'errAssetFileCannotBeRemovedNetwork' 
                    => __('The asset file could not be removed due to a possible network issue', 'wc-mobilpayments-card'),
                'assetUploadOk' 
                    => __('The file has been successfully uploaded', 'wc-mobilpayments-card'),
                'assetRemovalOk' 
                    => __('The file has been successfulyl removed', 'wc-mobilpayments-card'),
                'returnURLGenerationOk'
                    => __('The return URL has been successfully generated.','wc-mobilpayments-card'),
                'errReturnURLCannotBeGenerated'
                    => __('The return URL could not generated.', 'wc-mobilpayments-card'),
                'errReturnURLCannotBeGeneratedNetwork'
                    => __('The return URL could not be generated due to a possible network issue', 'wc-mobilpayments-card')
            );
        }

        private function _validatePaymentAssetUploadNonce() {
            return check_ajax_referer($this->_mobilpayAssetUploadApiDescriptor, 
                'payment_asset_upload_nonce', 
                false);
        }

        private function _validatePaymentAssetRemoveNonce() {
            return check_ajax_referer($this->_mobilpayAssetRemoveApiDescriptor, 
                'payment_asset_remove_nonce', 
                false);
        }

        private function _emptyCart() {
            WC()->cart->empty_cart();
        }

        private function _getPaymentAssetFields() {
            static $paymentAssetFields = null;
            if ($paymentAssetFields === null) {
                $paymentAssetFields = array();
                foreach ($this->form_fields as $fieldId => $fieldInfo) {
                    if ($this->_isPaymentAssetField($fieldInfo)) {
                        $paymentAssetFields[$fieldId] = &$fieldInfo;
                    }
                }
            }
            return $paymentAssetFields;
        }

        private function _hasPaymentAssets() {
            if (empty($this->_mobilpayAccountId)) {
                return false;
            }

            foreach ($this->_getPaymentAssetFields() as $fieldId => $fieldInfo) {
                if (!is_readable($this->_getPaymentAssetFilePathFromFieldInfo($fieldInfo, $this->_mobilpayAccountId))) {
                    return false;
                }
            }

            return true;
        }

        private function _getGatewayEndpointUrl() {
            return $this->_isLiveMode() 
                ? 'https://secure.mobilpay.ro' 
                : 'http://sandboxsecure.mobilpay.ro';
        }

        private function _getX509CertificateFilePath() {
            return $this->_getPaymentAssetFilePath(sprintf('%s.%s.public.cer', 
                $this->_getPaymentAssetFilePrefix(), 
                $this->_mobilpayAccountId));
        }
    
        private function _getPrivateKeyFilePath() {
            return $this->_getPaymentAssetFilePath(sprintf('%s.%s.private.key', 
                $this->_getPaymentAssetFilePrefix(), 
                $this->_mobilpayAccountId));
        }

        private function _getPaymentAssetFilePrefix() {
            return $this->_isLiveMode() ? 'live' : 'sandbox';
        }

        private function _getPaymentAssetFilePathFromFieldInfo(array $fieldInfo, $accountId) {
            $destinationFileName = sprintf($fieldInfo['_file_format'], !empty($accountId) 
                ? $accountId 
                : '__TEMP__');

            return $this->_getPaymentAssetFilePath($destinationFileName);
        }

        private function _getAssetIdFromRequest() {
            return isset($_POST['assetId']) 
                ? sanitize_text_field($_POST['assetId']) 
                : null;
        }

        private function _getPaymentAssetFilePath($file) {
            return wp_normalize_path(sprintf('%s/%s', 
                $this->_mobilpayAssetsDir, 
                $file));
        }

        private function _isPaymentAssetField(array $fieldInfo) {
            return !empty($fieldInfo) 
                && !empty($fieldInfo['type']) 
                && $fieldInfo['type'] == 'mobilpay_asset_upload';
        }

        private function _getFieldInfo($fieldId) {
            return isset($this->form_fields[$fieldId]) 
                ? $this->form_fields[$fieldId] 
                : null;
        }

        private function _canManageWcSettings() {
            return current_user_can('manage_woocommerce');
        }

        private function _isLiveMode() {
            return $this->_mobilpayEnvironment == 'no';
        }

        public function getLogger() {
            return $this->_logger;
        }
    }
}