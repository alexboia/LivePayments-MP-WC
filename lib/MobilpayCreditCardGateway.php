<?php
/**
 * Copyright (c) 2019-2021 Alexandru Boia
 *
 * Redistribution and use in source and binary forms, with or without modification, 
 * are permitted provided that the following conditions are met:
 * 
 *	1. Redistributions of source code must retain the abdove copyright notice, 
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
		use DataExtensions;
		use LoggingExtensions;

		const GATEWAY_PROCESS_RESPONSE_ERR_OK = 0x0000;

		const GATEWAY_PROCESS_RESPONSE_ERR_APPLICATION = 0x1000;

		const GATEWAY_ID = LVD_WCMC_WOOCOMMERCE_CC_GATEWAY_ID;

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

		/**
		 * @var \LvdWcMc\Settings
		 */
		private $_settings;

		private $_paymentAssetFileTemplates;

		public static function matchesGatewayId($gatewayId) {
			return $gatewayId == self::GATEWAY_ID;
		}

		public function __construct() {
			$this->id = self::GATEWAY_ID;
			$this->plugin_id = LVD_WCMC_PLUGIN_ID;
			
			$this->_logger = wc_get_logger();
			$this->_env = lvdwcmc_plugin()->getEnv();
			$this->_mediaIncludes = lvdwcmc_plugin()->getMediaIncludes();
			$this->_processor = new MobilpayCardPaymentProcessor();
			$this->_transactionFactory = new MobilpayTransactionFactory();
			$this->_settings = lvdwcmc_plugin()->getSettings();

			$this->method_title = __('LivePayments Card Gateway via mobilPay', 'livepayments-mp-wc');
			$this->method_description = __('LivePayments - mobilPay Payment Gateway for WooCommerce', 'livepayments-mp-wc');

			/**
			 * Filters the absolute URL for the payment gateway icon.
			 * Default value is the absolute URL to media/img/mobilpay.png.
			 * 
			 * @hook lvdwcmc_payment_gateway_icon
			 * @param string $url The current URL, initially provided by LivePayments-MP-WC
			 * @return string The actual and final URL, as returned by the registered filters
			 */
			$this->icon = apply_filters('lvdwcmc_payment_gateway_icon', $this->_env->getPublicAssetUrl('media/img/mobilpay.png'));
			
			$this->title = __('mobilPay&trade; Card Gateway', 'livepayments-mp-wc');

			$this->supports = array(
				'products'
			);

			$this->_apiDescriptor = strtolower(str_replace('\\', '_', __CLASS__));
			$this->_mobilpayNotifyUrl = WC()->api_request_url($this->_apiDescriptor);
			$this->_mobilpayAssetsDir = $this->_getPaymentAssetsDir();
			$this->_paymentAssetFileTemplates = $this->_getPaymentAssetFileTemplates();

			$this->_mobilpayAssetUploadApiDescriptor = strtolower(self::GATEWAY_ID . '_payment_asset_upload');
			$this->_mobilpayAssetUploadUrl = WC()->api_request_url($this->_mobilpayAssetUploadApiDescriptor);

			$this->_mobilpayAssetRemoveApiDescriptor = strtolower(self::GATEWAY_ID . '_payment_asset_remove');
			$this->_mobilpayAssetRemoveUrl = WC()->api_request_url($this->_mobilpayAssetRemoveApiDescriptor);

			$this->_returnUrlGenerationApiDescriptor = strtolower(self::GATEWAY_ID . '_generate_return_url');
			$this->_returnUrlGenerationUrl = WC()->api_request_url($this->_returnUrlGenerationApiDescriptor);

			add_action('woocommerce_api_' . $this->_apiDescriptor, array($this, 'process_gateway_response'), 10, 1);
			add_action('woocommerce_receipt_' . $this->id, array($this, 'show_payment_initiation'), 10, 1);
			add_action('woocommerce_email_after_order_table', array($this, 'add_transaction_details_to_email'), 10, 4);
			add_action('woocommerce_settings_payment_gateways_options', array($this, 'add_gateway_readiness_banner'));

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

			/**
			 * Enqueue styles for the gateway settings page. 
			 * Triggered after the core plug-in gateway settings page styles have been enqueued.
			 * 
			 * @hook lvdwcmc_enqueue_gateway_settings_form_styles
			 * 
			 * @param \LvdWcMc\MediaIncludes $mediaIncludes Reference to the media includes manager
			 */
			do_action('lvdwcmc_enqueue_gateway_settings_form_styles', 
				$this->_mediaIncludes);

			$plugin = lvdwcmc_plugin();
			$this->_mediaIncludes->includeScriptGatewaySettings(
				$plugin->getGatewaySettingsScriptTranslations(), 
				$plugin->getCommonScriptTranslations());

			/**
			 * Enqueue scripts for the gateway settings page. 
			 * Triggered after the core plug-in gateway settings page scripts have been enqueued.
			 * 
			 * @hook lvdwcmc_enqueue_gateway_settings_form_scripts
			 * 
			 * @param \LvdWcMc\MediaIncludes $mediaIncludes Reference to the media includes manager
			 */
			do_action('lvdwcmc_enqueue_gateway_settings_form_scripts', 
				$this->_mediaIncludes);
		}

		public function process_admin_options() {
			if (!$this->_canManageWcSettings()) {
				return false;
			}

			$renameOk = true;
			$processResult = parent::process_admin_options();
			$mobilpayAccountId = $this->get_option('mobilpay_account_id');

			/**
			 * Fires after the core admin option processing has been performed 
			 *  (including the options being saved),
			 *  but before the gateway peforms its custom processing.
			 * 
			 * @hook lvdwcmc_before_process_admin_options
			 * 
			 * @param boolean $processResult The intermediary result of the options processing operation
			 * @param array $settings The new settings values
			 * @param array $errors The current list of errors that occured during processing
			 * @param \LvdWcMc\MobilpayCreditCardGateway $gateway The gateway instance
			 */
			do_action('lvdwcmc_process_admin_options', 
				$processResult, 
				$this->settings, 
				$this->errors,
				$this);

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

			if ($processResult) {
				$this->init_settings();
			}

			//Update final processing result
			$processResult = $processResult && $renameOk;

			//Setup status changed, store it
			if ($processResult) {
				$this->store_gateway_setup_status();
			}

			/**
			 * Fires after the gateway has performed its custom processing
			 * 
			 * @hook lvdwcmc_after_process_admin_options
			 * 
			 * @param boolean $processResult The result of options processing operation
			 * @param array $settings The new settings values
			 * @param array $errors The current list of errors that occurred during processing
			 * @param \LvdWcMc\MobilpayCreditCardGateway $gateway The gateway instance
			 */
			do_action('lvdwcmc_process_admin_options_result', 
				$processResult,
				$this->errors,
				$this->settings,
				$this);

			return $processResult;
		}

		public function init_settings() {
			parent::init_settings();

			$this->title = $this->get_option('title');
			$this->description = $this->get_option('description');

			$this->_mobilpayEnvironment = $this->get_option('mobilpay_environment');
			$this->_mobilpayAccountId = trim($this->get_option('mobilpay_account_id'));
			$this->_mobilpayReturnUrl = trim($this->get_option('mobilpay_return_url'));

			/**
			 * Fires after the the gateway has read the options 
			 *  and done initializing itself with the values it needs
			 * 
			 * @hook lvdwcmc_admin_options_init
			 * 
			 * @param array $settings The current settings values
			 * @param \LvdWcMc\MobilpayCreditCardGateway $gateway The gateway instance
			 */
			do_action('lvdwcmc_admin_options_init', 
				$this->settings,
				$this);
		}

		public function init_form_fields() {
			$additionalFields = array();

			$coreFields = array(
				'enabled' => array(
					'title' => __('Enable / Disable', 'livepayments-mp-wc'),
					'label' => __('Enable this payment gateway', 'livepayments-mp-wc'),
					'type' => 'checkbox',
					'default' => 'no'
				),
				'mobilpay_environment' => array(
					'title' => __('mobilPay&trade; Sandbox / Test Mode', 'livepayments-mp-wc'),
					'label' => __('Enable Test Mode', 'livepayments-mp-wc'),
					'type' => 'checkbox',
					'description' => __('Place the payment gateway in test mode.', 'livepayments-mp-wc'),
					'default' => 'no'
				),
				'title' => array(
					'title' => __('Title', 'livepayments-mp-wc'),
					'type' => 'text',
					'desc_tip' => __('Payment title the customer will see during the checkout process.', 'livepayments-mp-wc'),
					'default' => __('LivePayments via mobilPay', 'livepayments-mp-wc')
				),
				'description' => array(
					'title' => __('Description', 'livepayments-mp-wc'),
					'type' => 'textarea',
					'desc_tip' => __('Payment description the customer will see during the checkout process.', 'livepayments-mp-wc'),
					'css' => 'max-width:350px;'
				),
				'mobilpay_account_id' => array(
					'title'	=> __('Seller Account ID', 'livepayments-mp-wc'),
					'type'	=> 'text',
					'description' => __('This is Account ID provided by MobilPay when you signed up for an account. Unique key for your seller account for the payment process.', 'livepayments-mp-wc')
				),
				'mobilpay_return_url' => array(
					'title'	=> __('Return URL', 'livepayments-mp-wc'),
					'type'	=> 'return_url',
					'description' => __('You must create a new page and in the content field enter the shortcode [lvdwcmc_display_mobilpay_order_status] so that the user can see the message that is returned by the Mobilpay server regarding their transaction. Or any content you want to thank for buying.', 'livepayments-mp-wc'),
					'desc_tip' => true
				),
				'mobilpay_live_public_cert' => array(
					'title' => __('mobilPay&trade; digital certificate for the live environment', 'livepayments-mp-wc'),
					'description' => __('The public key used for securing communication with the mobilPay&trade; gateway in the live environment.', 'livepayments-mp-wc'),
					'type' => 'mobilpay_asset_upload',
					'environment' => self::GATEWAY_MODE_LIVE,
					'desc_tip' => true,
					'allowed_files_hints' => sprintf(__('allowed file types: %s', 'livepayments-mp-wc'), '.cer'),
					'_kind' => 'public_key_certificate',
					'_file_format' => $this->_paymentAssetFileTemplates['public_key_certificate'],
					'_is_live_mode' => true
				),
				'mobilpay_live_private_key' => array(
					'title' => __('The private key for the live environment', 'livepayments-mp-wc'),
					'description' => __('The private key used for securing communication with the mobilPay&trade; gateway in the live environment.', 'livepayments-mp-wc'),
					'type' => 'mobilpay_asset_upload',
					'environment' => self::GATEWAY_MODE_LIVE,
					'desc_tip' => true,
					'allowed_files_hints' => sprintf(__('allowed file types: %s', 'livepayments-mp-wc'), '.key'),
					'_kind' => 'private_key_file',
					'_file_format' => $this->_paymentAssetFileTemplates['private_key_file'],
					'_is_live_mode' => true
				),
				'mobilpay_sandbox_public_cert' => array(
					'title' => __('mobilPay&trade; digital certificate for the sandbox environment', 'livepayments-mp-wc'),
					'description' => __('The public key used for securing communication with the mobilPay&trade; gateway in the sandbox environment (used when "MobilPay Sandbox / Test Mode"  is checked).', 'livepayments-mp-wc'),
					'type' => 'mobilpay_asset_upload',
					'environment' => self::GATEWAY_MODE_SANDBOX,
					'desc_tip' => true,
					'allowed_files_hints' => sprintf(__('allowed file types: %s', 'livepayments-mp-wc'), '.cer'),
					'_kind' => 'public_key_certificate',
					'_file_format' => $this->_paymentAssetFileTemplates['public_key_certificate'],
					'_is_live_mode' => false
				),
				'mobilpay_sandbox_private_key' => array(
					'title' => __('The private key for the sandbox environment', 'livepayments-mp-wc'),
					'description' => __('The private key used for securing communication with the mobilPay&trade; gateway in the sandbox environment (used when "MobilPay Sandbox / Test Mode"  is checked).', 'livepayments-mp-wc'),
					'type' => 'mobilpay_asset_upload',
					'environment' => self::GATEWAY_MODE_SANDBOX,
					'desc_tip' => true,
					'allowed_files_hints' => sprintf(__('allowed file types: %s', 'livepayments-mp-wc'), '.key'),
					'_kind' => 'private_key_file',
					'_file_format' => $this->_paymentAssetFileTemplates['private_key_file'],
					'_is_live_mode' => false
				)
			);

			/**
			 * Filters the list of additional fields added to the gateway settings form.
			 * Any additional field will be overridden by a core field with the same key.
			 * 
			 * @hook lvdwcmc_additional_gateway_settings_fields
			 * 
			 * @param array $additionalFields The current list of additional fields, initially provided by LivePayments-MP-WC
			 * @param array $coreFields The list of core fields provided by LivePayments-MP-WC
			 * @return array The actual list of additional fields, as returned by the registered filters
			 */
			$additionalFields = apply_filters('lvdwcmc_additional_gateway_settings_fields', 
				$additionalFields, 
				$coreFields);

			//We do not allow overriding of the core fields, 
			//  only specifying additional ones, so any additional field 
			//  will be overwritten by a core field with the same key
			$this->form_fields = array_merge($additionalFields,
				$coreFields);
		}

		public function generate_text_html($fieldId, $fieldInfo) {
			if (isset($fieldInfo['_generate_html']) && is_callable($fieldInfo['_generate_html'])) {
				$content = call_user_func($fieldInfo['_generate_html'], 
					$fieldId, 
					$fieldInfo, 
					$this->settings);
			} else {
				$content = parent::generate_text_html($fieldId, $fieldInfo);
			}
			return $content;
		}

		public function validate_text_field($fieldId, $fieldInfo) {
			if (isset($fieldInfo['_validate_field']) && is_callable($fieldInfo['_validate_field'])) {
				$value = call_user_func($fieldInfo['_validate_field'], 
					$fieldId, 
					$fieldInfo);
			} else {
				$value = parent::validate_text_field($fieldId, $fieldInfo);
			}
			return $value;
		}

		private function _renderGatewayReadinessBanner($context, $displayMessageIfGatewayReady) {
			if ($this->_gatewayReadinessBannerEnabled()) {
				$message = '';
				$missingRequiredFields = $this->get_missing_required_fields();
				$fieldsWithWarnings = empty($missingRequiredFields) 
					? $this->get_fields_with_warnings() 
					: array();

				$gatewayReady = empty($missingRequiredFields) 
					&& empty($fieldsWithWarnings);

				if (!$gatewayReady) {
					$message = sprintf(__('The %s payment gateway requires further configuration until it can be used to accept payments. The following fields are missing or require your attention:', 'livepayments-mp-wc'), 
						$this->method_title);
				} else if ($displayMessageIfGatewayReady) {
					$message = sprintf(__('The %s payment gateway is configured and ready to use.', 'livepayments-mp-wc'), 
						$this->method_title);
				} else {
					return '';
				}

				$data = new \stdClass();
				$data->message = $message;
				$data->missingRequiredFields = $missingRequiredFields;
				$data->fieldsWithWarnings = $fieldsWithWarnings;
				$data->gatewayReady = $gatewayReady;
				$data->context = $context;

				ob_start();
				require $this->_env->getViewFilePath('lvdwcmc-gateway-readiness-banner.php');
				return ob_get_clean();
			}
		}

		private function _renderAdminOptionsJSSettings() {
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

			/**
			 * Filters the view model of the gateway js settings, 
			 *  thus allowing any additional data to be added to it.
			 * The view model is a plain stdClass and contains any data 
			 *  required to inject the gateway settings, 
			 *  required for the JS scripts on this page.
			 * Additional data is provided by user filters as an associative array 
			 *  and then added to the view model as properties, but only the corresponding keys 
			 *  that do not overlap the existing ones.
			 * 
			 * @hook lvdwcmc_get_inline_js_settings_data
			 * 
			 * @param array $additionalData The initial set of additional data fields, as provided by LivePayments-MP-WC
			 * @param array $settings The current settings form values
			 * @return array The actual set of additional data fields, as returned by the registered filters
			 */
			$additionalData = apply_filters('lvdwcmc_get_inline_js_settings_data', 
				array(), 
				$this->settings);

			$data = $this->mergeAdditionalData($data, 
				$additionalData);

			ob_start();
			require $this->_env->getViewFilePath('lvdwcmc-gateway-settings-js.php');
			return ob_get_clean();
		}

		public function add_gateway_readiness_banner() {
			echo $this->_renderGatewayReadinessBanner('gateway-options-listing', false);
		}

		public function store_gateway_setup_status() {
			$optionKey = $this->_getSetupCompletedKey();
			if (!$this->needs_setup()) {
				update_option($optionKey, 'yes', true);
			} else {
				update_option($optionKey, 'no', true);
			}
		}

		public function get_last_stored_gateway_setup_status() {
			$optionKey = $this->_getSetupCompletedKey();
			return get_option($optionKey, null);
		}

		private function _getSetupCompletedKey() {
			return LVD_WCMC_PLUGIN_ID . '_setup_completed';
		}

		public function admin_options() {
			/**
			 * Executed before the admin options form is rendered 
			 *  (also before the gateway readiness banner is displayed).
			 * 
			 * @hook lvdwcmc_before_gateway_admin_options
			 */
			do_action('lvdwcmc_before_gateway_admin_options', 
				$this);

			echo $this->_renderGatewayReadinessBanner('gateway-settings-form', true);
			parent::admin_options();
			echo $this->_renderAdminOptionsJSSettings();

			/**
			 * Executed after the admin options form is rendered.
			 * 
			 * @hook lvdwcmc_after_gateway_admin_options
			 */
			do_action('lvdwcmc_after_gateway_admin_options', 
				$this);
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

			$uploader->setCustomValidator(function($destFilePathForValidation) use ($fieldInfo) {
				$result = true;
				$contents = file_get_contents($destFilePathForValidation);

				if (isset($fieldInfo['_kind'])) {
					if ($fieldInfo['_kind'] == 'public_key_certificate') {
						$result = $this->_isValidPublicKeyCertificateFile($contents);
					} else if ($fieldInfo['_kind'] == 'private_key_file') {
						$result = $this->_isValidPrivateKeyFile($contents);
					}
				}

				return $result;
			});

			//Process upload and store result
			$result = new \stdClass();
			$result->status = $uploader->receive();
			$result->ready = $uploader->isReady();

			//Setup status changed, store it
			if ($uploader->isReady()) {
				$this->store_gateway_setup_status();
			}

			/**
			 * Fires after a payment asset upload step has been completed.
			 * The asset is uploaded in chunks, so this is triggered for every uploaded chunk.
			 * When all the chunks have been successfully uploaded, the $ready parameter is set to true.
			 * 
			 * @hook lvdwcmc_payment_asset_uploaded
			 * 
			 * @param array $uploadedAssetInfo The information pertaining to the uploaded asset
			 * @param array $uploadProcessInfo The information pertaining to the current upload process step
			 * @param boolean $status Whether or not the current step of the upload process has been successful
			 * @param boolean $ready Whether or not the entire upload is now successfully completed
			 */
			do_action('lvdwcmc_payment_asset_uploaded', 
				array(
					'assetId' => $assetId,
					'fieldInfo' => $fieldInfo
				), 
				array(
					'chunk' => $chunk, 
					'chunks' => $chunks,
					'destination' => $destination
				), 
				$result->status, 
				$result->ready);

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
				? __('Payment asset file successfully removed.', 'livepayments-mp-wc')
				: __('Payment asset file could not be removed.', 'livepayments-mp-wc');

			//Setup status changed, store it
			$this->store_gateway_setup_status();

			/**
			 * Fires after a payment asset has been removed.
			 * 
			 * @hook lvdwcmc_payment_asset_removed
			 * 
			 * @param array $uploadedAssetInfo The information pertaining to the uploaded asset
			 * @param boolean $success Whether or not the operation is successfully completed
			 * @param string $message The message pertaining to the operation status
			 */
			do_action('lvdwcmc_payment_asset_removed',
				array(
					'assetId' => $assetId,
					'fieldInfo' => $fieldInfo
				),
				$result->success,
				$result->message);

			lvdwcmc_send_json($result);
		}

		public function generate_return_url() {
			if (!$this->_canManageWcSettings()) {
				http_response_code(401);
				die;
			}

			/**
			 * Filters the slug used to create the return page
			 * 
			 * @hook lvdwcmc_generate_return_url_slug
			 * 
			 * @param string $slug The current slug, initially provided by LivePayments-MP-WC
			 * @param string The actual & final page slug, as returned by the registered filters
			 */
			$slug = apply_filters('lvdwcmc_generate_return_url_slug', 'lvdwcmc-thank-you');

			$page = get_page_by_path($slug, OBJECT, 'page');
			if ($page == null) {
				/**
				 * Filters the information used to create the return page. 
				 * This is the data passed along to wp_insert_post().
				 * 
				 * @hook lvdwcmc_generate_return_url_data
				 * 
				 * @param array $pagePostInfo The curent page info, initially provided by LivePayments-MP-WC
				 * @return array The actual & final page post info, as returned by the registered filters
				 */
				$pagePostInfo = apply_filters('lvdwcmc_generate_return_url_page_info', array(
					'post_author' => get_current_user_id(),
					'post_content' => '[lvdwcmc_display_mobilpay_order_status]',
					'post_title' => __('Thank you for your order', 'livepayments-mp-wc'),
					'post_name' => $slug,
					'post_status' => 'publish',
					'post_type' => 'page',
					'comment_status' => 'closed',
					'ping_status' => 'closed'
				));

				$pageId = wp_insert_post($pagePostInfo, true);
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

		public function get_missing_required_fields() {
			$missingRequiredFields = array();

			if (empty($this->_mobilpayAccountId)) {
				$missingRequiredFields['mobilpay_account_id'] = 
					$this->form_fields['mobilpay_account_id']['title'];
			}

			if (empty($this->_mobilpayReturnUrl)) {
				$missingRequiredFields['mobilpay_return_url'] =
					$this->form_fields['mobilpay_return_url']['title'];
			}

			foreach ($this->_getPaymentAssetFields() as $fieldId => $fieldInfo) {
				if (!is_readable($this->_getPaymentAssetFilePathFromFieldInfo($fieldInfo, $this->_mobilpayAccountId))) {
					$missingRequiredFields[$fieldId] = $fieldInfo['title'];
				}
			}

			/**
			 * Filters the list of required fields for the gateway 
			 *  to be considered ready for processing payments.
			 * 
			 * The array of required fields must have the following structure:
			 *  - key => field ID, as defined in the $form_fields property;
			 *  - label => a label for the field, usually the label defined 
			 *      in the $form_fields property for this field.
			 * 
			 * Implementors of this hook will also need to implement 
			 *  the lvdwcmc_gateway_needs_setup to ensure that 
			 *  their results are consistent
			 * 
			 * @hook lvdwcmc_gateway_get_missing_required_fields
			 * @see lvdwcmc_gateway_needs_setup
			 * 
			 * @param array $missingRequiredFields The list of required fields that are missing, initially determined by LivePayments-MP-WC
			 * @param array $settings The current settings values
			 * @param \LvdWcMc\MobilpayCreditCardGateway $gateway The gateway instance
			 * 
			 * @return array The list of required fields that are missing
			 */
			return apply_filters('lvdwcmc_gateway_get_missing_required_fields', $missingRequiredFields, 
				$this->settings, 
				$this);
		}

		public function get_fields_with_warnings() {
			$fieldsWithWarnings = array();
			
			if (!empty($this->_mobilpayReturnUrl)) {
			   $message = $this->_validateMobilpayReturnUrl();
			   if (!empty($message)) {
					$fieldsWithWarnings['mobilpay_return_url'] = $message;
			   }
			}

			if (!empty($this->_mobilpayAccountId)) {
				foreach ($this->_getPaymentAssetFields() as $fieldId => $fieldInfo) {
					$message = $this->_validateMobilpayAsset($fieldInfo);
					if (!empty($message)) {
						$fieldsWithWarnings[$fieldId] = $message;
					}
				}
			}

			return $fieldsWithWarnings;
		}

		private function _validateMobilpayReturnUrl() {
			$message = null;

			if (!empty($this->_mobilpayReturnUrl)) {
				$title = $this->form_fields['mobilpay_return_url']['title'];
				if (parse_url($this->_mobilpayReturnUrl) !== false) {
					if ($this->_validateMobilpayReturnUrlAsLocalPage()) {
						$postId = url_to_postid($this->_mobilpayReturnUrl);
						if (!is_int($postId) || $postId <= 0) {
							$message = sprintf(__('The value for the field %s is a valid URL, but no longer corresponds to an existing local page', 'livepayments-mp-wc'), 
								$title);
						}
					}
				} else {
					$message = sprintf(__('The value for the field %s is no longer a valid URL', 'livepayments-mp-wc'), 
						$title);
				}
			}

			return $message;
		}

		private function _validateMobilpayAsset(array $fieldInfo) {
			$message = null;
			$path = $this->_getPaymentAssetFilePathFromFieldInfo($fieldInfo, $this->_mobilpayAccountId);
			
			if (is_readable($path)) {
				$contents = file_get_contents($path);
				if (!empty($contents)) {
					if ($fieldInfo['_kind'] == 'public_key_certificate') {
						if (!$this->_isValidPublicKeyCertificateFile($contents)) {
							$message = sprintf(__('The payment asset file %s is not a valid public key certificate', 'livepayments-mp-wc'), 
								$fieldInfo['title']);
						}
					} else if ($fieldInfo['_kind'] == 'private_key_file') {
						if (!$this->_isValidPrivateKeyFile($contents)) {
							$message = sprintf(__('The payment asset file %s is not a valid private key', 'livepayments-mp-wc'), 
								$fieldInfo['title']);
						}
					}
				} else {
					$message = sprintf(__('The payment asset file %s is empty', 'livepayments-mp-wc'), 
						$fieldInfo['title']);
				}
			} else {
				$message = sprintf(__('The payment asset file %s was not found', 'livepayments-mp-wc'), 
					$fieldInfo['title']);
			}

			return $message;
		}

		private function _isValidPublicKeyCertificateFile($fileContents) {
			$result = true;
			if (function_exists('openssl_pkey_get_public') && function_exists('openssl_free_key')) {
				$publicKey = openssl_pkey_get_public($fileContents);
				if ($publicKey !== false && is_resource($publicKey)) {
					openssl_free_key($publicKey);
				} else {
					$result = false;
				}
			}
			return $result;
		}

		private function _isValidPrivateKeyFile($fileContents) {
			$result = true;
			if (function_exists('openssl_pkey_get_private') && function_exists('openssl_free_key')) {
				$privateKey = openssl_pkey_get_private($fileContents);
				if ($privateKey !== false && is_resource($privateKey)) {
					openssl_free_key($privateKey);
				} else {
					$result = false;
				}
			}
			return $result;
		}

		public function needs_setup() {
			//Used by WC when toggling gateway on or off via AJAX 
			//  (see WC_Ajax::toggle_gateway_enabled())
			$needsSetup = empty($this->_mobilpayAccountId) 
				|| empty($this->_mobilpayReturnUrl)
				|| !$this->_hasPaymentAssets();

			/**
			 * Filters whether or not the gateway needs setup.
			 * This is invoked by WC, when toggling gateway on or off via AJAX.
			 * 
			 * Implementors of this hook will also need to implement 
			 *  the lvdwcmc_gateway_get_missing_required_fields to ensure that 
			 *  their results are consistent.
			 * 
			 * @hook lvdwcmc_gateway_needs_setup
			 * @see \WC_Ajax::toggle_gateway_enabled()
			 * @see lvdwcmc_gateway_get_missing_required_fields
			 * 
			 * @param boolean $needsSetup Whether or not setup is needed, initially determined by default by LivePayments-MP-WC
			 * @param array $settings The current settings values
			 * @param \LvdWcMc\MobilpayCreditCardGateway $gateway The gateway instance
			 * 
			 * @return boolean Whether or not setup is neded, as returned by the registered filters
			 */
			return apply_filters('lvdwcmc_gateway_needs_setup', $needsSetup, 
				$this->settings, 
				$this);
		}

		public function is_available() {
			//Used by WC to determine what payment gateways are available on checkout 
			//  (see WC_Payment_Gateways::get_available_payment_gateways())
			$isAvailable = !$this->needs_setup() && parent::is_available();

			/**
			 * Filters the gateway availability status.
			 * This is invoked when WC tries to determine what payment gateways are available for checkout.
			 *
			 * @hook lvdwcmc_gateway_is_available
			 * @see \WC_Payment_Gateways::get_available_payment_gateways().
			 * 
			 * @param boolean $isAvailable The current availability status, initially computed by LivePayments-MP-WC
			 * @param array $settings The current settings values
			 * @param \LvdWcMc\MobilpayCreditCardGateway $gateway The gateway instance
			 * 
			 * @return boolean The actual & final availability status, as returned by the registered filters
			 */
			return apply_filters('lvdwcmc_gateway_is_available', $isAvailable, 
				$this->settings, 
				$this);
		}

		public function process_payment($orderId) {
			$result = array();
			$context = array(
				'orderId' => $orderId,
				'source' => self::GATEWAY_ID
			);

			$this->logDebug('Begin processing payment for order', 
				$context);

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

					/**
					 * Fires after the order has been completed, during payment processing, 
					 *  before redirecting to the checkout receipt page, when the order 
					 *  has a total greater than zero.
					 * 
					 * @hook lvdwcmc_payment_before_checkout
					 * @see \WC_Payment_Gateway::process_payment()
					 * 
					 * @param \WC_Order $order The order affected by the operation
					 * @param array $result The operation result, as specified by \WC_Payment_Gateway::process_payment()
					 */
					do_action('lvdwcmc_order_before_checkout_payment', 
						$order, 
						$result);
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

					/**
					 * Fires after the order has been completed, during payment processing, 
					 *  when the order has a total of zero.
					 * 
					 * @hook lvdwcmc_payment_initialized
					 * @see \WC_Payment_Gateway::process_payment()
					 * 
					 * @param \WC_Order $order The order affected by the operation
					 * @param array $result The operation result, as specified by \WC_Payment_Gateway::process_payment()
					 */
					do_action('lvdwcmc_order_before_checkout_thank_you', 
						$order, 
						$result);
				}

				$this->logDebug('Done processing payment for order', $context);
			} catch (\Exception $exc) {
				wc_add_notice(__('Error initiating MobilPay card payment.', 'livepayments-mp-wc'), 'error');
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
					$mobilpayRequest = $this->_createPaymentRequest($order);
					$this->_processor->processPaymentInitialized($order, $mobilpayRequest);

					$data->paymentUrl = $this->_getGatewayEndpointUrl();
					$data->envKey = $mobilpayRequest->getEnvKey();
					$data->encData = $mobilpayRequest->getEncData();
					$data->settings = $this->_settings->asPlainObject();
					$data->success = true;

					$this->logDebug('Successfully constructed payment form data', $context);
				} catch (\Exception $exc) {
					$data->success = false;
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

					/**
					 * Filters any additional data to be added to the view model of the 
					 *  transaction details template used for the order e-mail notification
					 *  send to the user when the order status changes.
					 * The view model is a plain stdClass and contains any data required 
					 *  to correctly render the template.
					 * Additional data is provided by user filters as an associative array 
					 *  and then added to the view model as properties, but only the corresponding keys 
					 *  that do not overlap the existing ones.
					 * 
					 * @hook lvdwcmc_get_email_transaction_details_data
					 * 
					 * @param array $additionalData The initial set of additional data fields, as provided by LivePayments-MP-WC
					 * @param \WC_Order $order The target order
					 * @param \LvdWcMc\MobilpayTransaction $transaction The corresponding payment transaction
					 * @param array $args Additional arguments that establish the context in which the e-mail is being sent
					 * @return array The actual set of additional data fields, as returned by the registered filters
					 */
					$additionalData = apply_filters('lvdwcmc_get_email_transaction_details_data', 
						array(), 
						$order, 
						$transaction, 
						array(
							'sendToAdmin' => $sent_to_admin,
							'plainText' => $plain_text,
							'email' => $email
						));

					$data = $this->mergeAdditionalData($data, 
						$additionalData);

					require $this->_env->getViewFilePath('lvdwcmc-email-transaction-details.php');
				}
			}
		}

		private function _canAddTransactionDetailsToEmail(\WC_Order $order, $sent_to_admin, $plain_text, $email) {
			$canAdd = self::matchesGatewayId($order->get_payment_method()) 
				&& $order->has_status(array('completed', 'processing'))
				&& $email instanceof \WC_Email 
				&& ($email->id == 'customer_completed_order' || $email->id == 'customer_processing_order')
				&& !$plain_text;

			/**
			 * Filters whether or not to add the transaction details 
			 *  to the e-mail notification send to the user when 
			 *  the order status changes
			 * 
			 * @hook lvdwcmc_add_email_transaction_details
			 * 
			 * @param boolean $canAdd The current value of whether or not the transaction details are added to the e-mail notification
			 * @param \WC_Order $order The target order
			 * @param array $args Additional arguments that establish the context in which the e-mail is being sent
			 * @return boolean Whether or not to add to add the transaction details, as established by the registered filters
			 */
			return apply_filters('lvdwcmc_add_email_transaction_details', 
				$canAdd,
				$order, 
				array(
					'sendToAdmin' => $sent_to_admin,
					'plainText' => $plain_text,
					'email' => $email
				));
		}

		private function _createPaymentRequest(\WC_Order $order) {
			$requestInfo = $this->_getPaymentRequestInfo($order);

			$paymentRequest = new \Mobilpay_Payment_Request_Card();
			$paymentRequest->signature = $this->_mobilpayAccountId;
			$paymentRequest->orderId = $requestInfo['orderId'];
	
			$paymentRequest->confirmUrl = $this->_mobilpayNotifyUrl;
			$paymentRequest->returnUrl = add_query_arg(
				'order_id', 
				$order->get_id(), 
				$this->_mobilpayReturnUrl
			);

			$paymentRequest->invoice = new \Mobilpay_Payment_Invoice();
			$paymentRequest->invoice->currency = $requestInfo['invoice']['currency'];
			$paymentRequest->invoice->amount = $requestInfo['invoice']['amount'];
			$paymentRequest->invoice->details = $requestInfo['invoice']['details'];

			if (!empty($requestInfo['billing']) && is_array($requestInfo['billing'])) {
				$billingAddress = $this->_createPaymentAddressFromRequestInfo($requestInfo['billing']);
			} else {
				$billingAddress = null;
			}

			if (!empty($requestInfo['shipping']) && is_array($requestInfo['shipping'])) {
				$shippingAddress = $this->_createPaymentAddressFromRequestInfo($requestInfo['shipping']);
			} else {
				$shippingAddress = $billingAddress;
			}
			
			if ($billingAddress != null) {
				$paymentRequest->invoice->setBillingAddress($billingAddress);
			}

			if ($shippingAddress != null) {
				$paymentRequest->invoice->setShippingAddress($shippingAddress);
			}
	
			$paymentRequest->params = array(
				'_lvdwcmc_order_id' => $order->get_id(),
				'_lvdwcmc_customer_id' => $order->get_customer_id(),
				'_lvdwcmc_customer_ip' => $order->get_customer_ip_address()
			);

			$paymentRequest->encrypt($this->_getX509CertificateFilePath());
			return $paymentRequest;
		}

		private function _getPaymentRequestInfo(\WC_Order $order) {
			/**
			 * Filters the information fed into the payment 
			 *  request sent to the payment gateway
			 * 
			 * @hook lvdwcmc_get_payment_request_info
			 * 
			 * @param array $requestInfo The current payment request information, initially provided by LivePayments-MP-WC
			 * @param \WC_Order $order The order for which the payment request needs to be generated
			 * @return array The actual & final payment request information, as returned by the registered filters
			 */
			return apply_filters('lvdwcmc_get_payment_request_info', 
				array(
					'orderId' => md5(uniqid(rand())),
					'invoice' => array(
						'currency' => $order->get_currency(),
						'amount' => sprintf('%.2f', $order->get_total()),
						'details' => sprintf(__('Payment for order #%s.', 'livepayments-mp-wc'), $order->get_order_key())
					),
					'billing' => array(
						'type' => 'person',
						'firstName' => $order->get_billing_first_name(),
						'lastName' => $order->get_billing_last_name(),
						'email' => $order->get_billing_email(),
						'fiscalNumber' => '',
						'address' => $order->get_formatted_billing_address(),
						'mobilePhone' => $order->get_billing_phone()
					),
					'shipping' => array(
						'type' => 'person',
						'firstName' => $order->get_shipping_first_name(),
						'lastName' => $order->get_shipping_last_name(),
						'email' => $order->get_billing_email(),
						'fiscalNumber' => '',
						'address' => $order->get_formatted_shipping_address(),
						'mobilePhone' => $order->get_billing_phone()
					)
				), 
				$order);
		}

		private function _createPaymentAddressFromRequestInfo(array $info) {
			$address = new \Mobilpay_Payment_Address();
			$address->type = isset($info['type']) 
				? $info['type'] 
				: null;
			$address->firstName = isset($info['firstName']) 
				? $info['firstName'] 
				: null;
			$address->lastName = isset($info['lastName']) 
				? $info['lastName'] 
				: null;
			$address->email = isset($info['email']) 
				? $info['email'] 
				: null;
			$address->fiscalNumber = isset($info['fiscalNumber']) 
				? $info['fiscalNumber'] 
				: null;
			$address->address = isset($info['address']) 
				? $info['address'] 
				: null;
			$address->mobilePhone = isset($info['mobilePhone']) 
				? $info['mobilePhone'] 
				: null;
			return $address;
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
						$paymentAssetFields[$fieldId] = $fieldInfo;
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
			$replace = $this->_getPaymentAssetFileReplaceData();

			$fileName = str_ireplace(array_keys($replace), 
				array_values($replace), 
				$this->_paymentAssetFileTemplates['public_key_certificate']);

			return $this->_getPaymentAssetFilePath($fileName);
		}
	
		private function _getPrivateKeyFilePath() {
			$replace = $this->_getPaymentAssetFileReplaceData();
			
			$fileName = str_ireplace(array_keys($replace), 
				array_values($replace), 
				$this->_paymentAssetFileTemplates['private_key_file']);

			return $this->_getPaymentAssetFilePath($fileName);
		}

		private function _getPaymentAssetFileReplaceData() {
			return array(
				'%env%' => $this->_getPaymentAssetFileDescriptor(),
				'%account%' => $this->_mobilpayAccountId
			);
		}

		private function _getPaymentAssetFileDescriptor() {
			$isLiveMode = func_num_args() == 1 
				? func_get_arg(0) === true 
				: $this->_isLiveMode();

			$descriptor = $isLiveMode 
				? 'live' 
				: 'sandbox';

			/**
			 * Filters the payment asset file environment descriptor 
			 * (i.e. a string that describes whether the asset file 
			 * is used for the live environment or the sandbox environment).
			 * 
			 * @hook lvdwcmc_get_payment_assets_file_descriptor
			 * 
			 * @param string $descripor The current descriptor, initially provided by LivePayments-MP-WC
			 * @param boolean $isLiveMode Whether the descriptor belongs to the live environment (true) or the sandbox environment (false)
			 * @return string The actual & final descriptor, as returned by the registered filters
			 */
			return apply_filters('lvdwcmc_get_payment_assets_file_descriptor', 
				$descriptor, 
				$isLiveMode);
		}

		private function _getPaymentAssetFilePathFromFieldInfo(array $fieldInfo, $accountId) {
			$replace = array(
				'%env%' => $this->_getPaymentAssetFileDescriptor($fieldInfo['_is_live_mode']),
				'%account%' => !empty($accountId) 
					? $accountId 
					: '__TEMP__'
			);

			$destinationFileName = str_ireplace(array_keys($replace), 
				array_values($replace), 
				$fieldInfo['_file_format']);

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

		private function _getPaymentAssetsDir() {
			$assetsDir = $this->_env->getPaymentAssetsStorageDir();

			/**
			 * Filters the directory where the payment asset files are stored
			 * 
			 * @hook lvdwcmc_get_payment_assets_storage_dir
			 * 
			 * @param string $assetsDir The current asset directory, initially provided by LivePayments-MP-WC
			 * @return string The actual & final asset directory, as returned by the registered filters
			 */
			return apply_filters('lvdwcmc_get_payment_assets_storage_dir', 
				$assetsDir);
		}

		/**
		 * Retrieves the templates used for the file names of the payment assets, as an associative array:
		 *  - the public_key_certificate key stores the template for the public key certificate file
		 *  - the private_key_file key stores the template for the private key file
		 * 
		 * @return array The file name templates
		 */
		private function _getPaymentAssetFileTemplates() {
			$fileNameTemplates = array(
				'public_key_certificate' => '%env%.%account%.public.cer',
				'private_key_file' => '%env%.%account%.private.key'
			);

			/**
			 * Filters the file name templates for the payment assets. 
			 * 
			 * The templates are represented as an associative array:
			 *  - the public_key_certificate key stores the template for the public key certificate file;
			 *  - the private_key_file key stores the template for the private key file.
			 * 
			 * Each of the templates supports the following placeholders:
			 *  - %account% for the mobilpay account id;
			 *  - %env% for the environment in which the asset must be used (sandbox or live).
			 * 
			 * @hook lvdwcmc_get_payment_assets_file_templates
			 * 
			 * @param array $fileNameTemplates The current file name templates, intially provided by LivePayments-MP-WC
			 * @return array The actual & final templates, as returned by the registered filters
			 */
			return apply_filters('lvdwcmc_get_payment_assets_file_templates', 
				$fileNameTemplates);
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

		private function _gatewayReadinessBannerEnabled() {
			return defined('LVD_WCMC_SHOW_GATEWAY_READINESS_BANNER') 
				&& constant('LVD_WCMC_SHOW_GATEWAY_READINESS_BANNER') === true;
		}

		private function _validateMobilpayReturnUrlAsLocalPage() {
			return defined('LVD_WCMC_VALIDATE_MOBILPAY_URL_AS_LOCAL_PAGE') 
				&& constant('LVD_WCMC_VALIDATE_MOBILPAY_URL_AS_LOCAL_PAGE') === true;
		}

		private function _isLiveMode() {
			return $this->_mobilpayEnvironment == 'no';
		}

		public function getLogger() {
			return $this->_logger;
		}
	}
}