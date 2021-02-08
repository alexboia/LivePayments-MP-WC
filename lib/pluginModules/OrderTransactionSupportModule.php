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

namespace LvdWcMc\PluginModules {

    use LvdWcMc\MobilpayTransaction;
    use LvdWcMc\Plugin;

    class OrderTransactionSupportModule extends PluginModule {
        /**
         * @var \LvdWcMc\Shortcodes
         */
        private $_shortcodes;

        /**
         * @var \LvdWcMc\Formatters
         */
        private $_formatters;

        /**
         * @var \LvdWcMc\MobilpayTransactionFactory
         */
        private $_transactionFactory;

        public function __construct(Plugin $plugin) {
            parent::__construct($plugin);

            $this->_formatters = $plugin
                ->getFormatters();
            $this->_shortcodes = $plugin
                ->getShortcodes();
            $this->_transactionFactory = $plugin
                ->getTransactionFactory();
        }

        public function load() {
            $this->_registereWebPageAssets();
            $this->_registerOrderStatusShortCode();
            $this->_registerFrontendTransactionDetails();
            $this->_registerAdminOrderAndTransactionMetaBoxes();
        }

        private function _registereWebPageAssets() {
            add_action('wp_enqueue_scripts', 
                array($this, 'onFrontendEnqueueStyles'), 9998);
            add_action('wp_enqueue_scripts', 
                array($this, 'onFrontendEnqueueScripts'), 9998);

            add_action('admin_enqueue_scripts', 
                array($this, 'onAdminEnqueueStyles'), 9998);
        }

        public function onAdminEnqueueStyles() {
            if ($this->_env->isEditingWcOrder()) {
                $this->_mediaIncludes->includeStyleAdminTransactionDetails();
            }
        }

        public function onFrontendEnqueueStyles() {
            if ($this->_env->isViewingFrontendWcOrder()) {
                $this->_mediaIncludes->includeStyleFrontendTransactionDetails();
            }
        }

        public function onFrontendEnqueueScripts() {
            if ($this->_env->isAtWcOrderPayEndpoint()) {
                $this->_mediaIncludes->includeScriptPaymentInitiation();
            }
        }

        private function _registerFrontendTransactionDetails() {
            add_filter('woocommerce_order_details_after_order_table', 
                array($this, 'addTransactionDetailsOnAccountOrderDetails'), -1, 1);
        }

        private function _registerOrderStatusShortCode() {
            add_shortcode('lvdwcmc_display_mobilpay_order_status', 
                array($this->_shortcodes, 'displayMobilpayOrderStatus'));
        }

        public function addTransactionDetailsOnAccountOrderDetails(\WC_Order $order) {
            if ($this->_env->isViewingFrontendWcOrder()) {
                $data = $this->_getDisplayableTransactionDetailsFromOrder($order);
                if ($data != null) {
                    echo $this->_viewEngine->renderView('lvdwcmc-frontend-transaction-details.php', 
                        $data);
                }
            }
        }

        private function _registerAdminOrderAndTransactionMetaBoxes() {
            add_action('add_meta_boxes', 
                array($this, 'onRegisterAdminOrderAndTransactionMetaBoxes'), 10, 2);
        }

        public function onRegisterAdminOrderAndTransactionMetaBoxes($postType, $post) {
            if ($this->_shouldRegisterOrderAndTransactionDetailsMetabox($postType, $post)) {
                $this->_registerOrderAndTransactionDetailsMetabox();
            }
        }

        private function _shouldRegisterOrderAndTransactionDetailsMetabox($postType, $post) {
            $registerMetabox = ($postType === 'shop_order');
            $additionalHookArgs = array(
                'postType' => $postType,
                'post' => $post
            );

             /**
             * Filters whether or not to add the payment details metabox 
             *  to the admin order details screen.
             * 
             * @hook lvdwcmc_register_order_payment_details_metabox
             * 
             * @param boolean $registerMetabox Whether or not to register the metabox, initially provided by LivePayments-MP-WC
             * @param array $args Additional arguments that establish the context of the operation
             * @return boolean Whether or not to register the metabox, as established by the registered filters
             */
            return apply_filters('lvdwcmc_register_order_payment_details_metabox', 
                $registerMetabox,
                $additionalHookArgs);
        }

        private function _registerOrderAndTransactionDetailsMetabox() {
            add_meta_box('lvdwcmc-transaction-details-metabox', 
                __('Payment transaction details', 'livepayments-mp-wc'), 
                array($this, 'addTransactionDetailsOnAdminOrderDetails'), 
                'shop_order', 
                'side', 
                'default');
        }

        public function addTransactionDetailsOnAdminOrderDetails() {
            $order = $this->_env->getTheOrder();
            if (!empty($order) && ($order instanceof \WC_Order)) {
                $data = $this->_getDisplayableTransactionDetailsFromOrder($order);
                if ($data != null) {
                    echo $this->_viewEngine->renderView('lvdwcmc-admin-transaction-details.php', 
                        $data);
                }
            }
        }

        private function _getDisplayableTransactionDetailsFromOrder(\WC_Order $order) {
            $transaction = $this->_transactionFactory
                ->existingFromOrder($order);

            if ($transaction != null) {
                return $this->_getDisplayableTransactionDetails($transaction);
            } else {
                return null;
            }
        }

        private function _getDisplayableTransactionDetails(MobilpayTransaction $transaction) {
            return $this->_formatters
                ->getDisplayableTransactionDetails($transaction);
        }
    }
}