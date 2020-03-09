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
    class Plugin {
        const ACTION_GET_ADMIN_TRANSACTION_DETAILS = 'lvdwcmc_get_admin_transaction_details';

        const NONCE_GET_ADMIN_TRANSACTION_DETAILS = 'lvdwcmc_get_admin_transaction_details_nonce';

        /**
         * @var \LvdWcMc\Env Reference to the environment object
         */
        private $_env;

        /**
         * @var \LvdWcMc\MediaIncludes Reference to the media includes manager
         */
        private $_mediaIncludes;

        /**
         * @var \LvdWcMc\Installer Reference to the installer object
         */
        private $_installer;

        /**
         * @var \LvdWcMc\Shortcodes Reference to the shortcodes manager object
         */
        private $_shortcodes;

        /**
         * @var string The identifier of the plug-in text domain
         */
        private $_textDomain = LVD_WCMC_TEXT_DOMAIN;

        /**
         * @var \LvdWcMc\MobilpayTransactionFactory Reference to the transaction factory
         */
        private $_transactionFactory = null;

        /**
         * @var array The list of required plugins for our plug-in to work
         */
        private $_requiredPlugins = array(
            'woocommerce/woocommerce.php'
        );

        public function __construct(array $options) {
            if (!isset($options['mediaIncludes']) || !is_array($options['mediaIncludes'])) {
                $options['mediaIncludes'] = array(
                    'refPluginsPath' => LVD_WCMC_MAIN,
                    'scriptsInFooter' => true
                );
            }

            $this->_env = lvdwcmc_env();
            $this->_installer = new Installer();
            $this->_shortcodes = new Shortcodes();
            $this->_transactionFactory = new MobilpayTransactionFactory();

            $this->_mediaIncludes = new MediaIncludes(
                $options['mediaIncludes']['refPluginsPath'], 
                $options['mediaIncludes']['scriptsInFooter']
            );
        }

        public function run() {
            register_activation_hook(LVD_WCMC_MAIN, array($this, 'onActivatePlugin'));
            register_deactivation_hook(LVD_WCMC_MAIN, array($this, 'onDeactivatePlugin'));
            register_uninstall_hook(LVD_WCMC_MAIN, array($this, 'onUninstallPlugin'));

            add_action('plugins_loaded', array($this, 'onPluginsLoaded'));
            add_action('init', array($this, 'onPluginsInit'));
            
            add_action('wp_enqueue_scripts', array($this, 'onFrontendEnqueueStyles'), 9999);
            add_action('wp_enqueue_scripts', array($this, 'onFrontendEnqueueScripts'), 9999);
            add_action('admin_enqueue_scripts', array($this, 'onAdminEnqueueStyles'), 9999);
            add_action('admin_enqueue_scripts', array($this, 'onAdminEnqueueScripts'), 9999);

            add_action('add_meta_boxes', array($this, 'onRegisterMetaboxes'), 10, 2);
            add_action('admin_menu', array($this, 'onAddAdminMenuEntries'));
            add_action('wp_dashboard_setup', array($this, 'onDashboardWidgetsSetup'));

            $this->_addAjaxAction(self::ACTION_GET_ADMIN_TRANSACTION_DETAILS, 
                array($this, 'ajaxGetAdminTransactionDetails'),
                false);

            add_shortcode('lvdwcmc_display_mobilpay_order_status', 
                array($this->_shortcodes, 'displayMobilpayOrderStatus'));
        }

        public function onActivatePlugin() {
            if (!current_user_can('activate_plugins')) {
                return;
            }

            $test = $this->_installer->canBeInstalled();
            if ($test !== Installer::INSTALL_OK) {
                $errors = $this->_getInstallationErrorTranslations();
                $message = isset($errors[$test]) 
                    ? $errors[$test] 
                    : __('Could not activate plug-in: requirements not met.', 'wc-mobilpayments-card');

                deactivate_plugins(plugin_basename(LVD_WCMC_MAIN));
                wp_die(lvdwcmc_append_error($message, $this->_installer->getLastError()),  __('Activation error', 'wc-mobilpayments-card'));
            } else {
                if (!$this->_installer->activate()) {
                    wp_die(lvdwcmc_append_error(
                        __('Could not activate plug-in: activation failure.', 'wc-mobilpayments-card'), 
                        $this->_installer->getLastError()), 
                        __('Activation error', 'wc-mobilpayments-card'));
                }
            }
        }

        public function onDeactivatePlugin() {
            if (!current_user_can('activate_plugins')) {
                return;
            }
            if (!$this->_installer->deactivate()) {
                wp_die(lvdwcmc_append_error('Could not deactivate plug-in', $this->_installer->getLastError()), 
                    'Deactivation error');
            }
        }

        public function onUninstallPlugin() {
            if (!$this->_installer->uninstall()) {
                wp_die(lvdwcmc_append_error('Could not uninstall plug-in', $this->_installer->getLastError()), 
                    'Uninstall error');
            }
        }

        public function onPluginsLoaded() {
            foreach ($this->_requiredPlugins as $plugin) {
                if (!$this->_env->isPluginActive($plugin)) {
                    wp_die('Missing required plug-in: "' . $plugin . '".', 'Missing dependency');
                }
            }

            add_filter('woocommerce_payment_gateways', 
                array($this, 'onWooCommercePaymentGatewaysRequested'), 10, 1);
            add_filter('woocommerce_order_details_after_order_table', 
                array($this, 'addTransactionDetailsOnAccountOrderDetails'), -1, 1);
            add_filter('woocommerce_format_log_entry', 
                array($this, 'onFormatWooCommerceLogMessage'), 10, 2);
        }

        public function onPluginsInit() {
            $this->_loadTextDomain();
            $this->_installer->updateIfNeeded();
        }

        public function onWooCommercePaymentGatewaysRequested($methods) {
            $methods[] = '\LvdWcMc\MobilpayCreditCardGateway';
            return $methods;
        }

        public function onAddAdminMenuEntries() {
            if ($this->_canManageWooCommerce()) {
                add_submenu_page('woocommerce', 
                    __('mobilPay&trade; Card Transactions', 'wc-mobilpayments-card'), 
                    __('mobilPay&trade; Card Transactions', 'wc-mobilpayments-card'), 
                    'manage_woocommerce', 
                    'lvdwcmc-card-transactions-listing',
                    array($this, 'showAdminTransactionsListing'));
            }
        }

        public function onFrontendEnqueueStyles() {
            if ($this->_env->isViewingFrontendWcOrder()) {
                $this->_mediaIncludes->includeStyleFrontendTransactionDetails();
            }

            /**
             * Triggered after all the core-plug-in frontend styles 
             *  have been enqueued.
             * 
             * @hook lvdwcmc_frontend_enqueue_styles
             * 
             * @param \LvdWcMc\MediaIncludes $mediaIncludes Reference to the media includes manager
             */
            do_action('lvdwcmc_frontend_enqueue_styles', 
                $this->_mediaIncludes);
        }

        public function onFrontendEnqueueScripts() {
            if ($this->_env->isAtWcOrderPayEndpoint()) {
                $this->_mediaIncludes->includeScriptPaymentInitiation();
            }

            /**
             * Triggered after all the core-plug-in frontend scripts 
             *  have been enqueued.
             * 
             * @hook lvdwcmc_frontend_enqueue_scripts
             * 
             * @param \LvdWcMc\MediaIncludes $mediaIncludes Reference to the media includes manager
             */
            do_action('lvdwcmc_frontend_enqueue_scripts', 
                $this->_mediaIncludes);
        }

        public function onAdminEnqueueStyles() {
            if ($this->_env->isEditingWcOrder()) {
                $this->_mediaIncludes->includeStyleAdminTransactionDetails();
            }

            if ($this->_env->isViewingAdminTransactionListing()) {
                $this->_mediaIncludes->includeStyleAdminTransactionListing();
            }

            /**
             * Triggered after all the core-plug-in admin styles 
             *  have been enqueued.
             * 
             * @hook lvdwcmc_admin_enqueue_styles
             * 
             * @param \LvdWcMc\MediaIncludes $mediaIncludes Reference to the media includes manager
             */
            do_action('lvdwcmc_admin_enqueue_styles', 
                $this->_mediaIncludes);
        }

        public function onAdminEnqueueScripts() {
            if ($this->_env->isViewingWpDashboard()) {
                $this->_mediaIncludes->includeStyleDashboard();
            }

            if ($this->_env->isViewingAdminTransactionListing()) {
                $this->_mediaIncludes->includeScriptTransactionListing();
                $this->_mediaIncludes->localizeTransactionListingScript($this->_getTransactionsListingScriptTranslations());
            }

            /**
             * Triggered after all the core-plug-in admin scripts 
             *  have been enqueued.
             * 
             * @hook lvdwcmc_admin_enqueue_scripts
             * 
             * @param \LvdWcMc\MediaIncludes $mediaIncludes Reference to the media includes manager
             */
            do_action('lvdwcmc_admin_enqueue_scripts', 
                $this->_mediaIncludes);
        }

        public function addTransactionDetailsOnAccountOrderDetails(\WC_Order $order) {
            if ($this->_env->isViewingFrontendWcOrder()) {
                $data = $this->_getDisplayableTransactionDetailsFromOrder($order);
                if ($data != null) {
                    require $this->_env->getViewFilePath('lvdwcmc-frontend-transaction-details.php');
                }
            }
        }

        public function onRegisterMetaboxes($postType, $post) {
            /**
             * Filters whether or not to add the payment details metabox 
             *  to the admin order details screen.
             * 
             * @hook lvdwcmc_register_order_payment_details_metabox
             * 
             * @param boolean $registerMetabox Whether or not to register the metabox, initially provided by WC-MobilPayments-Card
             * @param array $args Additional arguments that establish the context of the operation
             * @return boolean Whether or not to register the metabox, as established by the registered filters
             */
            $registerMetabox = apply_filters('lvdwcmc_register_order_payment_details_metabox', 
                ($postType === 'shop_order'),
                array(
                    'postType' => $postType,
                    'post' => $post
                ));

            if ($registerMetabox) {
                add_meta_box('lvdwcmc-transaction-details-metabox', 
                    __('Payment transaction details', 'wc-mobilpayments-card'), 
                    array($this, 'addTransactionDetailsOnAdminOrderDetails'), 
                    'shop_order', 
                    'side', 
                    'default');
            }
        }

        public function addTransactionDetailsOnAdminOrderDetails() {
            $order = $this->_env->getTheOrder();
            if (!empty($order) && ($order instanceof \WC_Order)) {
                $data = $this->_getDisplayableTransactionDetailsFromOrder($order);
                if ($data != null) {
                    require $this->_env->getViewFilePath('lvdwcmc-admin-transaction-details.php');
                }
            }
        }

        public function showAdminTransactionsListing() {
            if (!$this->_canManageWooCommerce()) {
                die;
            }

            $db = $this->_env->getDb();
            $currentPage = max(isset($_GET['page_num']) 
                ? intval($_GET['page_num']) 
                : 1, 1);

            $totalRecords = $db->getValue($this->_env->getPaymentTransactionsTableName(), 'COUNT(tx_id)');
            $totalPages = ceil($totalRecords / LVD_WCMC_RECORDS_PER_PAGE);

            $numRows = array(
                ($currentPage - 1) * LVD_WCMC_RECORDS_PER_PAGE, //offset
                LVD_WCMC_RECORDS_PER_PAGE //limit
            );

            $db->join($this->_env->getPostsTableName() . ' wp', 
                'wp.ID = tx.tx_order_id', 
                'LEFT');

            $db->orderBy('tx.tx_timestamp_last_updated', 'DESC');
            $db->orderBy('tx.tx_timestamp_initiated', 'DESC');

            $transactions = $db->get($this->_env->getPaymentTransactionsTableName() . ' tx', 
                $numRows, 
                'tx.*, wp.post_title tx_title') ;

            foreach ($transactions as &$tx) {
                $tx['tx_title_full'] = '#' 
                    . $tx['tx_order_id'] . ' ' 
                    . $tx['tx_title'];

                $tx['tx_admin_details_link'] = get_edit_post_link($tx['tx_order_id']);

                $tx['tx_timestamp_initiated_formatted'] = 
                    $this->_formatTransactionTimestamp($tx['tx_timestamp_initiated']);
                $tx['tx_timestamp_last_updated_formatted'] = 
                    $this->_formatTransactionTimestamp($tx['tx_timestamp_last_updated']);
                $tx['tx_amount_formatted'] = 
                    $this->_formatTransactionAmount($tx['tx_amount']) . ' ' . $tx['tx_currency'];
                $tx['tx_processed_amount_formatted'] = 
                    $this->_formatTransactionAmount($tx['tx_processed_amount']) . ' ' . $tx['tx_currency'];
                $tx['tx_status_formatted'] = 
                    $this->_getTransactionStatusLabel($tx['tx_status']);
            }

            $data = new \stdClass();
            $data->pageTitle = get_admin_page_title();
            $data->transactions = $transactions;
            $data->hasTransactions = !empty($transactions);

            $data->totalPages = $totalPages;
            $data->totalRecords = $totalRecords;
            $data->currentPage = $currentPage;

            $data->transactionDetailsNonce = $this->_createGetTransactionDetailsNonce();
            $data->transactionDetailsAction = self::ACTION_GET_ADMIN_TRANSACTION_DETAILS;
            $data->ajaxBaseUrl = $this->_getAjaxBaseUrl();

            require $this->_env->getViewFilePath('lvdwcmc-admin-transactions-listing.php');
        }

        public function ajaxGetAdminTransactionDetails() {
            if (!$this->_canManageWooCommerce()) {
                die;
            }

            $transactionId = isset($_GET['transaction_id']) 
                ? intval($_GET['transaction_id']) 
                : 0;

            //Verify input data and the nonce
            if ($transactionId < 0 || !$this->_verifyNonceGetTransactionDetails()) {
                die;
            }

            $response = lvdwcmc_get_ajax_response();
            $transaction = $this->_transactionFactory->fromTransactionId($transactionId);

            if ($transaction != null) {
                $response->transaction = $this->_getDisplayableTransactionDetails($transaction);
                $response->success = true;
            } else {
                $response->transaction = null;
            }

            lvdwcmc_send_json($response);
        }

        public function onDashboardWidgetsSetup() {
            if ($this->_canManageWooCommerce()) {
                wp_add_dashboard_widget('lvdwcmc-transactions-status', 
                    __('mobilPay&trade; Card Transaction Status', 'wc-mobilpayments-card'), 
                    array($this, 'renderTransactionsStatusWidget'), 
                        null,
                        null);
            }
        }

        public function renderTransactionsStatusWidget() {
            $statusData = array();
            $db = $this->_env->getDb();
            
            $rawStatusData = $db
                ->groupBy('tx_status')
                ->get($this->_env->getPaymentTransactionsTableName(), null, 'tx_status, COUNT(tx_status) tx_status_count');
            
            foreach ($this->_getEmptyTransactionStatusData() as $status => $count) {
                $statusData[$status] = array(
                    'label' => $this->_getTransactionStatusLabel($status),
                    'count' => $count
                );
            }

            if (!empty($rawStatusData)) {
                foreach ($rawStatusData as $row) {
                    $statusData[$row['tx_status']] = array(
                        'label' => $this->_getTransactionStatusLabel($row['tx_status']),
                        'count' => intval($row['tx_status_count'])
                    );
                }
            }

            $data = new \stdClass();
            $data->status = $statusData;
            $data->success = true;

            require $this->_env->getViewFilePath('lvdwcmc-dashboard-transactions-status.php');
        }

        public function onFormatWooCommerceLogMessage($entry, $args) {
            return $this->_shouldFormatWooCommerceLogMessage($args)
                ? $entry . ' Additional context: ' . var_export($args['context'], true) 
                : $entry;
        }

        private function _shouldFormatWooCommerceLogMessage($args) {
            return !empty($args['context']) && (
                empty($args['context']['source']) 
                || $args['context']['source'] == MobilpayCreditCardGateway::GATEWAY_ID
            );
        }

        public function isActive() {
            return $this->_env->isPluginActive('wc-mobilpayments-card/wc-mobilpayments-card-plugin-main.php');
        }

        public function getEnv() {
            return $this->_env;
        }

        public function getTextDomain() {
            return $this->_textDomain;
        }

        public function getMediaIncludes() {
            return $this->_mediaIncludes;
        }

        private function _getEmptyTransactionStatusData() {
            return array(
                MobilpayTransaction::STATUS_NEW => 0,
                MobilpayTransaction::STATUS_CONFIRMED_PENDING => 0,
                MobilpayTransaction::STATUS_PAID_PENDING => 0,
                MobilpayTransaction::STATUS_FAILED => 0,
                MobilpayTransaction::STATUS_CREDIT => 0,
                MobilpayTransaction::STATUS_CONFIRMED => 0,
                MobilpayTransaction::STATUS_CANCELLED => 0
            );
        }

        private function _getDisplayableTransactionDetailsFromOrder(\WC_Order $order) {
            $transaction = $this->_transactionFactory->existingFromOrder($order);
            if ($transaction != null) {
                return $this->_getDisplayableTransactionDetails($transaction);
            } else {
                return null;
            }
        }

        private function _getDisplayableTransactionDetails(MobilpayTransaction $transaction) {
            $data = new \stdClass();
            $data->providerTransactionId = $transaction->getProviderTransactionId();
            $data->status = $this->_getTransactionStatusLabel($transaction->getStatus());
            $data->panMasked = $transaction->getPANMasked();
            
            $data->amount = $this->_formatTransactionAmount($transaction->getAmount());
            $data->processedAmount = $this->_formatTransactionAmount($transaction->getProcessedAmount());
            $data->currency = $transaction->getCurrency();
            
            $data->timestampInitiated = $this->_formatTransactionTimestamp($transaction->getTimestampInitiated());
            $data->timestampLastUpdated = $this->_formatTransactionTimestamp($transaction->getTimestampLastUpdated());
            $data->errorCode = $transaction->getErrorCode();
            $data->errorMessage = $transaction->getErrorMessage();

            if ($this->_canManageWooCommerce()) {
                $data->clientIpAddress = $transaction->getIpAddress();
            } else {
                $data->clientIpAddress = null;
            }

            /**
             * Filters the transaction details view model, used both 
             *  in the backend and in the frontend to display transaction information.
             * Properties may be overwritten.
             * 
             * @hook lvdwcmc_get_displayable_transaction_details
             * 
             * @param \stdClass $data The initial view model, as provided by WC-MobilPayments-Card
             * @param \LvdWcMc\MobilpayTransaction $transaction The source transaction
             * @return \stdClass The actual view model, as returned by the registered filters
             */
            return apply_filters('lvdwcmc_get_displayable_transaction_details', 
                $data, 
                $transaction);
        }

        private function _formatTransactionAmount($amount) {
            $amountFormat = $this->_getAmountFormat();
            return number_format($amount, 
                $amountFormat['decimals'], 
                $amountFormat['decimalSeparator'], 
                $amountFormat['thousandSeparator']);
        }

        private function _formatTransactionTimestamp($strTimestamp) {
            $timestamp = date_create_from_format('Y-m-d H:i:s', $strTimestamp);
            return !empty($timestamp) 
                ? $timestamp->format($this->_getDateTimeFormat()) 
                : null;
        }

        private function _addAjaxAction($action, $callBack, $noPriv = false) {
            add_action('wp_ajax_' . $action, $callBack);
            if ($noPriv) {
                add_action('wp_ajax_nopriv_' . $action, $callBack);
            }
        }

        private function _canManageWooCommerce() {
            return current_user_can('manage_woocommerce');
        }

        private function _createGetTransactionDetailsNonce() {
            return wp_create_nonce(self::NONCE_GET_ADMIN_TRANSACTION_DETAILS);
        }

        private function _verifyNonceGetTransactionDetails() {
            return check_ajax_referer(self::NONCE_GET_ADMIN_TRANSACTION_DETAILS, 
                'lvdwcmc_nonce', 
                false);
        }

        private function _getAjaxBaseUrl() {
            return get_admin_url(null, 'admin-ajax.php', 'admin');
        }

        private function _loadTextDomain() {
            load_plugin_textdomain($this->_textDomain, false, plugin_basename(LVD_WCMC_LANG_DIR));
        }

        private function _getTransactionsListingScriptTranslations() {
            return array(
                'errCannotLoadTransactionDetails' 
                    => __('Could not load transaction details data', 'wc-mobilpayments-card'),
                'errCannotLoadTransactionDetailsNetwork' 
                    => __('Could not load transaction details data due to a possible network issue', 'wc-mobilpayments-card')
            );
        }

        private function _getInstallationErrorTranslations() {
            $this->_loadTextDomain();
            return array(
                Installer::INCOMPATIBLE_PHP_VERSION 
                    => sprintf(__('Minimum required PHP version is %s.', 'wc-mobilpayments-card'), $this->_env->getRequiredPhpVersion()),
                Installer::INCOMPATIBLE_WP_VERSION 
                    => sprintf(__('Minimum required WordPress version is %s.', 'wc-mobilpayments-card'), $this->_env->getRequiredWpVersion()),
                Installer::SUPPORT_MYSQLI_NOT_FOUND 
                    => __('Mysqli extension was not found on your system or is not fully compatible.', 'wc-mobilpayments-card'),
                Installer::SUPPORT_OPENSSL_NOT_FOUND 
                    => __('Openssl extension was not found on your system or is not fully compatible.', 'wc-mobilpayments-card'),
                Installer::GENERIC_ERROR 
                    => __('The installation failed.', 'wc-mobilpayments-card')
            );
        }

        private function _getTransactionStatusLabel($status) {
            $labelsForCodes = array(
                MobilpayTransaction::STATUS_CANCELLED 
                    => __('Cancelled', 'wc-mobilpayments-card'),
                MobilpayTransaction::STATUS_CONFIRMED 
                    => __('Confirmed. Payment successful', 'wc-mobilpayments-card'),
                MobilpayTransaction::STATUS_CONFIRMED_PENDING 
                    => __('Pending confirmation', 'wc-mobilpayments-card'),
                MobilpayTransaction::STATUS_CREDIT 
                    => __('Credited', 'wc-mobilpayments-card'),
                MobilpayTransaction::STATUS_FAILED 
                    => __('Failed', 'wc-mobilpayments-card'),
                MobilpayTransaction::STATUS_NEW 
                    => __('Started', 'wc-mobilpayments-card'),
                MobilpayTransaction::STATUS_PAID_PENDING 
                    => __('Pending payment', 'wc-mobilpayments-card')
            );

            return isset($labelsForCodes[$status]) 
                ? $labelsForCodes[$status] 
                : '-';
        }

        private function _getAmountFormat() {
            return lvdwcmc_get_amount_format();
        }

        private function _getDateTimeFormat() {
            return lvdwcmc_get_datetime_format();
        }
    }
}