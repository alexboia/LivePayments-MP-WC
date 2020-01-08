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

        public static function matchesGatewayId($gatewayId) {
            return $gatewayId == self::GATEWAY_ID;
        }

        public function __construct() {
            $this->id = self::GATEWAY_ID;
            $this->plugin_id = LVD_WCMC_PLUGIN_ID;

            $this->method_title = $this->__('MobilPay Card Gateway');
            $this->method_description = $this->__('MobilPay Payment Gateway pentru WooCommerce');
            
            $this->title = $this->__('MobilPay Card Gateway');

            $this->supports = array(
                'products', 
                'refunds'
            );

            $this->_env = lvdwcmc_plugin()->getEnv();
            $this->_mediaIncludes = lvdwcmc_plugin()->getMediaIncludes();

            $this->_apiDescriptor = strtolower(str_replace('\\', '_', __CLASS__));
            $this->_mobilpayNotifyUrl = WC()->api_request_url($this->_apiDescriptor);
            $this->_mobilpayAssetsDir = $this->_env->getPaymentAssetsStorageDir();

            $this->_mobilpayAssetUploadApiDescriptor = strtolower(self::GATEWAY_ID . '_payment_asset_upload');
            $this->_mobilpayAssetUploadUrl = WC()->api_request_url($this->_mobilpayAssetUploadApiDescriptor);

            $this->_mobilpayAssetRemoveApiDescriptor = strtolower(self::GATEWAY_ID . '_payment_asset_remove');
            $this->_mobilpayAssetRemoveUrl = WC()->api_request_url($this->_mobilpayAssetRemoveApiDescriptor);

            add_action('woocommerce_api_' . $this->_apiDescriptor, array($this, 'process_gateway_response'));
            add_action('woocommerce_api_' . $this->_mobilpayAssetUploadApiDescriptor, array($this, 'process_payment_asset_upload'));
            add_action('woocommerce_api_' . $this->_mobilpayAssetRemoveApiDescriptor, array($this, 'process_payment_asset_remove'));

            add_action('woocommerce_receipt_' . $this->id, array($this, 'show_payment_initiation'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_form_scripts'));
            
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options'));

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
            $this->_mobilpayReturnUrl = $this->get_option('mobilapy_return_url');
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => $this->__('Enable / Disable'),
                    'label' => $this->__('Enable this payment gateway'),
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                'mobilpay_environment' => array(
                    'title' => $this->__('MobilPay Sandbox / Test Mode'),
                    'label' => $this->__('Enable Test Mode'),
                    'type' => 'checkbox',
                    'description' => $this->__('Place the payment gateway in test mode.'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => $this->__('Title'),
                    'type' => 'text',
                    'desc_tip' => $this->__('Payment title the customer will see during the checkout process.'),
                    'default' => $this->__('MobilPay')
                ),
                'description' => array(
                    'title' => $this->__('Description'),
                    'type' => 'textarea',
                    'desc_tip' => $this->__('Payment description the customer will see during the checkout process.'),
                    'css' => 'max-width:350px;'
                ),
                'mobilpay_account_id' => array(
                    'title'	=> $this->__('Seller Account ID'),
                    'type'	=> 'text',
                    'description' => $this->__('This is Account ID provided by MobilPay when you signed up for an account. Unique key for your seller account for the payment process.')
                ),
                'mobilapy_return_url' => array(
                    'title'	=> $this->__('Return URL'),
                    'type'	=> 'text',
                    'description' => $this->__('You must create a new page and in the content field enter the shortcode [lvdwcmc_thank_you] so that the user can see the message that is returned by the Mobilpay server regarding their transaction. Or any content you want to thank for buying.'),
                    'desc_tip' => true
                ),
                'mobilpay_live_public_cert' => array(
                    'title' => $this->__('mobilPay™ digital certificate for the live environment'),
                    'description' => $this->__('The public key used for securing communication with the mobilPay™ gateway in the live environment.'),
                    'type' => 'mobilpay_asset_upload',
                    'environment' => self::GATEWAY_MODE_LIVE,
                    'desc_tip' => true,
                    'allowed_files_hints' => 'allowed file types: .cer',
                    '_file_format' => 'live.%s.public.cer'
                ),
                'mobilpay_live_private_key' => array(
                    'title' => $this->__('The private key for the live environment'),
                    'description' => $this->__('The private key used for securing communication with the mobilPay™ gateway in the live environment.'),
                    'type' => 'mobilpay_asset_upload',
                    'environment' => self::GATEWAY_MODE_LIVE,
                    'desc_tip' => true,
                    'allowed_files_hints' => 'allowed file types: .key',
                    '_file_format' => 'live.%s.private.key'
                ),
                'mobilpay_sandbox_public_cert' => array(
                    'title' => $this->__('mobilPay™ digital certificate for the sandbox environment'),
                    'description' => $this->__('The public key used for securing communication with the mobilPay™ gateway in the sandbox environment (used when "MobilPay Sandbox / Test Mode"  is checked).'),
                    'type' => 'mobilpay_asset_upload',
                    'environment' => self::GATEWAY_MODE_SANDBOX,
                    'desc_tip' => true,
                    'allowed_files_hints' => 'allowed file types: .cer',
                    '_file_format' => 'sandbox.%s.public.cer'
                ),
                'mobilpay_sandbox_private_key' => array(
                    'title' => $this->__('The private key for the sandbox environment'),
                    'description' => $this->__('The private key used for securing communication with the mobilPay™ gateway in the sandbox environment (used when "MobilPay Sandbox / Test Mode"  is checked).'),
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

            $data->uploadMaxFileSize = LVD_WCMC_PAYMENT_ASSET_UPLOAD_MAX_FILE_SIZE;
            $data->uploadChunkSize = LVD_WCMC_PAYMENT_ASSET_UPLOAD_CHUNK_SIZE;
            $data->uploadKey = LVD_WCMC_PAYMENT_ASSET_UPLOAD_KEY;

            require $this->_env->getViewFilePath('lvdwcmc-mobilpay-cc-gateway-settings-js.php');
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
            require $this->_env->getViewFilePath('lvdwcmc-mobilpay-cc-gateway-upload-asset-field.php');
            return ob_get_clean();
        }

        public function generate_mobilpay_asset_upload_html($fieldId, $fieldInfo) {
            return $this->_renderPaymentAssetUploadField($fieldId, $fieldInfo);
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

            $result = new \stdClass();
            $result->success = !file_exists($destination);
            $result->message = $result->success
                ? $this->__('Payment asset file successfully removed.')
                : $this->__('Payment asset file could not be removed.');

            lvdwcmc_send_json($result);
        }

        public function needs_setup() {
            return empty($this->_mobilpayAccountId) 
                || empty($this->_mobilpayReturnUrl)
                || !$this->_hasMobilpayAssets();
        }

        private function _getMobilpayPaymentMessageError($errorCode) {
            $standardErrors = array(
                '16' => $this->__('Card has a risk (i.e. stolen card)'), 
                '17' => $this->__('Card number is incorrect'), 
                '18' => $this->__('Closed card'), 
                '19' => $this->__('Card is expired'), 
                '20' => $this->__('Insufficient funds'), 
                '21' => $this->__('CVV2 code incorrect'), 
                '22' => $this->__('Issuer is unavailable'), 
                '32' => $this->__('Amount is incorrect'), 
                '33' => $this->__('Currency is incorrect'), 
                '34' => $this->__('Transaction not permitted to cardholder'), 
                '35' => $this->__('Transaction declined'), 
                '36' => $this->__('Transaction rejected by antifraud filters'), 
                '37' => $this->__('Transaction declined (breaking the law)'), 
                '38' => $this->__('Transaction declined'), 
                '48' => $this->__('Invalid request'), 
                '49' => $this->__('Duplicate PREAUTH'), 
                '50' => $this->__('Duplicate AUTH'), 
                '51' => $this->__('You can only CANCEL a preauth order'), 
                '52' => $this->__('You can only CONFIRM a preauth order'), 
                '53' => $this->__('You can only CREDIT a confirmed order'), 
                '54' => $this->__('Credit amount is higher than auth amount'), 
                '55' => $this->__('Capture amount is higher than preauth amount'), 
                '56' => $this->__('Duplicate request'), 
                '99' => $this->__('Generic error')
            );
    
            return isset($standardErrors[$errorCode]) 
                ? $standardErrors[$errorCode] 
                : null;
        }

        private function _isDecryptedRequestValid($paymentRequest) {
            return $paymentRequest instanceof \Mobilpay_Payment_Request_Card
                && isset($paymentRequest->params['_lvdwcmc_order_id']) 
                && isset($paymentRequest->params['_lvdwcmc_customer_id'])
                && isset($paymentRequest->params['_lvdwcmc_customer_ip']);
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
                'errPluploadTooLarge' => $this->__('The selected file is too large. Maximum allowed size is 10MB'), 
                'errPluploadFileType' => $this->__('The selected file type is not valid.'), 
                'errPluploadIoError' => $this->__('The file could not be read'), 
                'errPluploadSecurityError' => $this->__('The file could not be read'), 
                'errPluploadInitError' => $this->__('The uploader could not be initialized'), 
                'errPluploadHttp' =>  $this->__('The file could not be uploaded'), 
                'errServerUploadFileType' =>  $this->__('The selected file type is not valid.'), 
                'errServerUploadTooLarge' =>  $this->__('The selected file is too large. Maximum allowed size is 10MB'), 
                'errServerUploadNoFile' =>  $this->__('No file was uploaded'), 
                'errServerUploadInternal' =>  $this->__('The file could not be uploaded due to a possible internal server issue'), 
                'errServerUploadFail' =>  $this->__('The file could not be uploaded'),
                'warnRemoveAssetFile' => $this->__('Remove asset file? This action cannot be undone and you will have to re-upload the asset again!'),
                'errAssetFileCannotBeRemoved' => $this->__('The asset file could not be removed'),
                'errAssetFileCannotBeRemovedNetwork' => $this->__('The asset file could not be removed due to a possible network issue'),
                'assetUploadOk' => $this->__('The file has been successfully uploaded'),
                'assetRemovalOk' => $this->__('The file has been successfulyl removed')
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

        private function _hasMobilpayAssets() {
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

        private function __($text) {
            return __($text, lvdwcmc_plugin()->getTextDomain());
        }
    }
}