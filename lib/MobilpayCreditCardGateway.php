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

            $this->_apiDescriptor = strtolower(__NAMESPACE__ . '_' . __CLASS__);
            $this->_mobilpayNotifyUrl = WC()->api_request_url($this->_apiDescriptor);
            $this->_mobilpayAssetsDir = $this->_env->getPaymentAssetsStorageDir();

            add_action('woocommerce_api_' . $this->_apiDescriptor, array( $this, 'process_gateway_response' ) );
            add_action('woocommerce_receipt_' . $this->id, array($this, 'show_payment_initiation'));

            if (is_admin()) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            $this->init_form_fields();
            $this->init_settings();
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
                    'title' => $this->__('Activare / Dezactivare'),
                    'label' => $this->__('Activeaza acest gateway de plata'),
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                'mobilpay_environment' => array(
                    'title' => $this->__('MobilPay Sandbox'),
                    'label' => $this->__('Activeaza modul sandbox'),
                    'type' => 'checkbox',
                    'description' => $this->__('Foloseste gateway-ul de plata in modul sandbox'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => $this->__('Titlu'),
                    'type' => 'text',
                    'desc_tip' => $this->__('Acest titlu va fi afisat clientului in timpul procesului de check-out.'),
                    'default' => $this->__('MobilPay')
                ),
                'description' => array(
                    'title' => $this->__('Descriere'),
                    'type' => 'textarea',
                    'desc_tip' => $this->__('Aceasta descriere va fi afisata clientului in timpul procesului de check-out.'),
                    'css' => 'max-width:350px;'
                ),
                'mobilpay_account_id' => array(
                    'title'	=> $this->__('ID Cont Comerciant'),
                    'type'	=> 'text',
                    'description' => $this->__('Acesta este ID-ul unic al contului tau de comerciant, atribuit la inrolarea in sistemul nostru.')
                ),
                'mobilapy_return_url' => array(
                    'title'	=> $this->__('URL pagina confirmare plata'),
                    'type'	=> 'text',
                    'description' => $this->__('URL-ul unei pagini din site-ul tau wordpress, la care clientul tau va fi redirectionat dupa finalizarea procesului de plata. Acest camp si existenta acestei pagini sunt obligatoriu.')
                ),
                'mobilpay_live_public_cert' => array(
                    'title' => $this->__('Certificat digital mobilPay / mediu live'),
                    'description' => $this->__('Cheie publica folosita pentru securizarea comunicarii catre mobilPay, in mediul live'),
                    'type' => 'mobilpay_asset_upload',
                    'kind' => 'public_key_certificate',
                    'environment' => 'live',
                    'desc_tip' => true
                ),
                'mobilpay_sandbox_public_cert' => array(
                    'title' => $this->__('Certificat digital mobilPay / sandbox'),
                    'description' => $this->__('Cheie publica folosita pentru securizarea comunicarii catre mobilPay, in mediul de testare'),
                    'type' => 'mobilpay_asset_upload',
                    'kind' => 'public_key_certificate',
                    'environment' => 'sandbox',
                    'desc_tip' => true
                ),
                'mobilpay_live_private_key' => array(
                    'title' => $this->__('Certificat cont comerciant / mediu live'),
                    'description' => $this->__('Cheia privata folosita pentru securizarea comunicarii dinspre mobilPay, in mediul live'),
                    'type' => 'mobilpay_asset_upload',
                    'kind' => 'private_key',
                    'environment' => 'live',
                    'desc_tip' => true
                ),
                'mobilpay_sandbox_private_key' => array(
                    'title' => $this->__('Certificat cont comerciant / sandbox'),
                    'description' => $this->__('Cheia privata folosita pentru securizarea comunicarii dinspre mobilPay, in mediul de testare'),
                    'type' => 'mobilpay_asset_upload',
                    'kind' => 'private_key',
                    'environment' => 'sandbox',
                    'desc_tip' => true
                )
            );
        }

        public function generate_mobilpay_asset_upload_html($fieldId, $fieldInfo) {
            ob_start();
            require $this->_env->getViewFilePath('lvdwcmc-upload-asset-field.php');
            return ob_get_clean();
        }

        public function needs_setup() {
            return empty($this->_mobilpayAccountId) 
                || empty($this->_mobilpayReturnUrl)
                || !$this->_hasMobilpayAssets();
        }

        private function _hasMobilpayAssets() {
            if (empty($this->_mobilpayAccountId)) {
                return false;
            }

            $files = array(
                sprintf('live.%s.public.cer', $this->_mobilpayAccountId),
                sprintf('sandbox.%s.public.cer', $this->_mobilpayAccountId),
                sprintf('live.%s.private.key', $this->_mobilpayAccountId),
                sprintf('sandbox.%s.private.key', $this->_mobilpayAccountId)
            );

            foreach ($files as $file) {
                if (!is_readable($this->_getPaymentAssetFilePath($file))) {
                    return false;
                }
            }

            return true;
        }

        private function _getPaymentAssetFilePath($file) {
            return wp_normalize_path(sprintf('%s/%s', 
                $this->_mobilpayAssetsDir, 
                $file));
        }

        private function __($text) {
            return __($text, lvdwcmc_plugin()->getTextDomain());
        }
    }
}