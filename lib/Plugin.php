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
         * @var \LvdWcMc\MobilpayTransactionFactory Reference to the transaction factory
         */
        private $_transactionFactory = null;

        /**
         * @var array The list of required plugins for our plug-in to work
         */
        private $_requiredPlugins = null;

        /**
         * @var \LvdWcMc\TransactionReport
         */
        private $_report = null;

        /**
         * @var \LvdWcMc\Formatters
         */
        private $_formatters = null;

        /**
         * @var \LvdWcMc\ApiServer
         */
        private $_apiServer = null;

        /**
         * @var array The list of required plugins that are missing
         */
        private $_missingPlugins = array();

        public function __construct(array $options) {
            if (!isset($options['mediaIncludes']) || !is_array($options['mediaIncludes'])) {
                $options['mediaIncludes'] = array(
                    'refPluginsPath' => LVD_WCMC_MAIN,
                    'scriptsInFooter' => true
                );
            }

            $this->_requiredPlugins = array(
                'woocommerce/woocommerce.php' => function() {
                    return defined('WC_PLUGIN_FILE') 
                        && class_exists('WC_Payment_Gateway') 
                        && class_exists('WooCommerce')
                        && function_exists('WC');
                }
            );

            $this->_env = lvdwcmc_get_env();
            $this->_installer = new Installer();
            $this->_shortcodes = new Shortcodes();
            $this->_transactionFactory = new MobilpayTransactionFactory();
            $this->_report = new TransactionReport();
            $this->_apiServer = new ApiServer();
            $this->_formatters = new Formatters();

            $this->_mediaIncludes = new MediaIncludes(
                $options['mediaIncludes']['refPluginsPath'], 
                $options['mediaIncludes']['scriptsInFooter']
            );
        }

        public function run() {
            register_activation_hook(LVD_WCMC_MAIN, array($this, 'onActivatePlugin'));
            register_deactivation_hook(LVD_WCMC_MAIN, array($this, 'onDeactivatePlugin'));
            register_uninstall_hook(LVD_WCMC_MAIN, array(__CLASS__, 'onUninstallPlugin'));

            add_action('plugins_loaded', array($this, 'onPluginsLoaded'));
            add_action('rest_api_init', array($this, 'onPluginsRestApiInit'));
            add_action('init', array($this, 'onPluginsInit'));
        }

        public function onActivatePlugin() {
            if (!self::_currentUserCanActivatePlugins()) {
                write_log('Attempted to activate plug-in without appropriate access permissions.');
                return;
            }

            $test = $this->_installer->canBeInstalled();
            if ($test !== Installer::INSTALL_OK) {
                $errors = $this->_getInstallationErrorTranslations();
                $message = isset($errors[$test]) 
                    ? $errors[$test] 
                    : __('Could not activate plug-in: requirements not met.', 'livepayments-mp-wc');

                deactivate_plugins(plugin_basename(LVD_WCMC_MAIN));
                wp_die(lvdwcmc_append_error($message, $this->_installer->getLastError()),  __('Activation error', 'livepayments-mp-wc'));
            } else {
                if (!$this->_installer->activate()) {
                    wp_die(lvdwcmc_append_error(
                        __('Could not activate plug-in: activation failure.', 'livepayments-mp-wc'), 
                        $this->_installer->getLastError()), 
                        __('Activation error', 'livepayments-mp-wc'));
                }
            }
        }

        public function onDeactivatePlugin() {
            if (!self::_currentUserCanActivatePlugins()) {
                write_log('Attempted to deactivate plug-in without appropriate access permissions.');
                return;
            }

            if (!$this->_installer->deactivate()) {
                wp_die(lvdwcmc_append_error('Could not deactivate plug-in', $this->_installer->getLastError()), 
                    'Deactivation error');
            }
        }

        public static function onUninstallPlugin() {
            if (!self::_currentUserCanActivatePlugins()) {
                write_log('Attempted to uninstall plug-in without appropriate access permissions.');
                return;
            }
            
            $installer = lvdwcmc_plugin()->getInstaller();
            if (!$installer->uninstall()) {
                wp_die(lvdwcmc_append_error('Could not uninstall plug-in', $installer->getLastError()), 
                    'Uninstall error');
            }
        }

        public function onAdminNoticesRenderMissingPluginsWarning() {
            $data = new \stdClass();
            $data->missingPlugins = $this->_missingPlugins;
            require $this->_env->getViewFilePath('lvdwcmc-admin-notices-missing-required-plugins.php');
        }

        public function onPluginsLoaded() {
            if ($this->_checkMisingPlugins()) {
                add_action('wp_enqueue_scripts', array($this, 'onFrontendEnqueueStyles'), 9999);
                add_action('wp_enqueue_scripts', array($this, 'onFrontendEnqueueScripts'), 9999);
                add_action('admin_enqueue_scripts', array($this, 'onAdminEnqueueStyles'), 9999);
                add_action('admin_enqueue_scripts', array($this, 'onAdminEnqueueScripts'), 9999);
                add_action('admin_enqueue_scripts', array($this, 'onAdminEnqueueStylesForWooAdminDashboard'), 0);
                add_action('admin_enqueue_scripts', array($this, 'onAdminEnqueueScriptsForWooAdminDashboard'), 0);

                add_action('add_meta_boxes', array($this, 'onRegisterMetaboxes'), 10, 2);
                add_action('admin_menu', array($this, 'onAddAdminMenuEntries'));
                add_action('wp_dashboard_setup', array($this, 'onDashboardWidgetsSetup'));

                $this->_addAjaxAction(self::ACTION_GET_ADMIN_TRANSACTION_DETAILS, 
                    array($this, 'ajaxGetAdminTransactionDetails'),
                    false);

                add_shortcode('lvdwcmc_display_mobilpay_order_status', 
                    array($this->_shortcodes, 'displayMobilpayOrderStatus'));

                add_filter('woocommerce_payment_gateways', 
                    array($this, 'onWooCommercePaymentGatewaysRequested'), 10, 1);
                add_filter('woocommerce_order_details_after_order_table', 
                    array($this, 'addTransactionDetailsOnAccountOrderDetails'), -1, 1);
                add_filter('woocommerce_format_log_entry', 
                    array($this, 'onFormatWooCommerceLogMessage'), 10, 2);
            } else {
                add_action('admin_notices', array($this, 'onAdminNoticesRenderMissingPluginsWarning'));
            }
        }

        public function onPluginsRestApiInit() {
            if ($this->_checkMisingPlugins()) {
                $this->_apiServer->listen();
            }
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
            if (self::_currentUserCanManageWooCommerce()) {
                add_submenu_page('woocommerce', 
                    __('LivePayments Card Transactions', 'livepayments-mp-wc'), 
                    __('LivePayments Card Transactions', 'livepayments-mp-wc'), 
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

        public function onAdminEnqueueStylesForWooAdminDashboard() {
            if ($this->_env->isViewingWooAdminDashboard()) {
                $this->_mediaIncludes->includeStyleDashboard();
                $this->_mediaIncludes->includeStyleAdminTransactionDetails();
            }
        }

        public function onAdminEnqueueScriptsForWooAdminDashboard() {
            if ($this->_env->isViewingWooAdminDashboard()) {
                $this->_mediaIncludes->includeScriptWooAdminDashboardSections($this->getWooAdminDashboardSectionsScriptTranslations());
            }
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
                $this->_mediaIncludes->includeScriptTransactionListing(
                    $this->getTransactionsListingScriptTranslations(), 
                    $this->getCommonScriptTranslations());
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
             * @param boolean $registerMetabox Whether or not to register the metabox, initially provided by LivePayments-MP-WC
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
                    __('Payment transaction details', 'livepayments-mp-wc'), 
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
            if (!self::_currentUserCanManageWooCommerce()) {
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

            foreach ($transactions as $key => $tx) {
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

                /**
                 * Filters a transaction listing item, represented as an array, 
                 *  after the formatted data has been added to it.
                 * The view model is a plain stdClass and contains 
                 *  any data required to correctly render the template.
                 * 
                 * @hook lvdwcmc_get_admin_transansactions_listing_item
                 * 
                 * @param array $tx The view model, initially provided by LivePayments-MP-WC
                 * @param array $args Additional arguments to establish the context of the operation
                 * 
                 * @return array The view model, as returned by the registered filters
                 */
                $transactions[$key] = apply_filters('lvdwcmc_get_admin_transansactions_listing_item', $tx, array(
                    'pageNum' => $currentPage,
                    'totalRecords' => $totalRecords,
                    'totalPages' => $totalPages
                ));
            }

            $data = new \stdClass();
            $data->additionalColumns = array();
            $data->pageTitle = get_admin_page_title();
            $data->transactions = $transactions;
            $data->hasTransactions = !empty($transactions);

            $data->totalPages = $totalPages;
            $data->totalRecords = $totalRecords;
            $data->currentPage = $currentPage;

            $data->transactionDetailsNonce = $this->_createGetTransactionDetailsNonce();
            $data->transactionDetailsAction = self::ACTION_GET_ADMIN_TRANSACTION_DETAILS;
            $data->ajaxBaseUrl = $this->_getAjaxBaseUrl();
            
            $data->paginateLinksArgs = array(
                'base' => add_query_arg('page_num', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;', 'livepayments-mp-wc'),
                'next_text' => __('&raquo;', 'livepayments-mp-wc'),
                'total' => $data->totalPages,
                'current' => $data->currentPage
            );

            /**
             * Filters the view model of the admin transactions listing page, 
             *  thus allowing additional data to be added to it.
             * 
             * @hook lvdwcmc_get_admin_transansactions_listing_data
             * 
             * @param \stdClass $data The view model, initially provided by LivePayments-MP-WC
             * @param array $args Additional arguments to establish the context of the operation
             * 
             * @return \stdClass The view model, as returned by the registered filters
             */
            $data = apply_filters('lvdwcmc_get_admin_transansactions_listing_data', $data, array(
                'pageNum' => $currentPage,
                'totalRecords' => $totalRecords,
                'totalPages' => $totalPages
            ));

            require $this->_env->getViewFilePath('lvdwcmc-admin-transactions-listing.php');
        }

        public function ajaxGetAdminTransactionDetails() {
            if (!self::_currentUserCanManageWooCommerce()) {
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
            /**
             * Filters whether or not to add the transactions 
             *  status widget to the WP admin dashboard
             * 
             * @hook lvdwcmc_add_status_dashboard_widget
             * 
             * @param boolean $addDashboardWidget Whether to add the widget or not, initially provided by LivePayments-MP-WC
             * @return boolean Whether to add the widget or not, as returned by the registered filters
             */
            $addDashboardWidget = apply_filters('lvdwcmc_add_status_dashboard_widget', 
                self::_currentUserCanManageWooCommerce());

            if ($addDashboardWidget) {
                wp_add_dashboard_widget('lvdwcmc-transactions-status', 
                    __('LivePayments Card Transaction Status', 'livepayments-mp-wc'), 
                    array($this, 'renderTransactionsStatusWidget'), 
                        null,
                        null);
            }
        }

        public function renderTransactionsStatusWidget() {
            $data = new \stdClass();
            $data->status = $this->_report->getTransactionsStatusCounts();
            $data->success = true;

            /**
             * Filters the view model of the admin dashboard transaction status widget
             *  thus allowing additional data to be added to it.
             * 
             * @hook lvdwcmc_get_dashboard_widget_status_data
             * 
             * @param \stdClass $data The view model, initially provided by LivePayments-MP-WC
             * @return \stdClass The view model, as returned by the registered filters
             */
            $data = apply_filters('lvdwcmc_get_dashboard_widget_status_data', 
                $data);

            require $this->_env->getViewFilePath('lvdwcmc-dashboard-transactions-status.php');
        }

        public function onFormatWooCommerceLogMessage($entry, $args) {
            //TODO: proper dump of context data
            return $this->_shouldFormatWooCommerceLogMessage($args)
                ? $entry . ' Additional context: ' . print_r($args['context'], true) 
                : $entry;
        }

        public function getSettingsScriptTranslations() {
            return array(
                'errPluploadTooLarge' 
                    => __('The selected file is too large. Maximum allowed size is 10MB', 'livepayments-mp-wc'), 
                'errPluploadFileType' 
                    => __('The selected file type is not valid.', 'livepayments-mp-wc'), 
                'errPluploadIoError' 
                    => __('The file could not be read', 'livepayments-mp-wc'), 
                'errPluploadSecurityError' 
                    => __('The file could not be read', 'livepayments-mp-wc'), 
                'errPluploadInitError' 
                    => __('The uploader could not be initialized', 'livepayments-mp-wc'), 
                'errPluploadHttp' 
                    => __('The file could not be uploaded', 'livepayments-mp-wc'), 
                'errServerUploadFileType' 
                    => __('The selected file type is not valid.', 'livepayments-mp-wc'), 
                'errServerUploadTooLarge' 
                    => __('The selected file is too large. Maximum allowed size is 10MB', 'livepayments-mp-wc'), 
                'errServerUploadNoFile' 
                    => __('No file was uploaded', 'livepayments-mp-wc'), 
                'errServerUploadInternal' 
                    => __('The file could not be uploaded due to a possible internal server issue', 'livepayments-mp-wc'), 
                'errServerUploadFail' 
                    => __('The file could not be uploaded', 'livepayments-mp-wc'),
                'warnRemoveAssetFile' 
                    => __('Remove asset file? This action cannot be undone and you will have to re-upload the asset again!', 'livepayments-mp-wc'),
                'errAssetFileCannotBeRemoved' 
                    => __('The asset file could not be removed', 'livepayments-mp-wc'),
                'errAssetFileCannotBeRemovedNetwork' 
                    => __('The asset file could not be removed due to a possible network issue', 'livepayments-mp-wc'),
                'assetUploadOk' 
                    => __('The file has been successfully uploaded', 'livepayments-mp-wc'),
                'assetRemovalOk' 
                    => __('The file has been successfulyl removed', 'livepayments-mp-wc'),
                'returnURLGenerationOk'
                    => __('The return URL has been successfully generated.','livepayments-mp-wc'),
                'errReturnURLCannotBeGenerated'
                    => __('The return URL could not generated.', 'livepayments-mp-wc'),
                'errReturnURLCannotBeGeneratedNetwork'
                    => __('The return URL could not be generated due to a possible network issue', 'livepayments-mp-wc')
            );
        }

        public function getWooAdminDashboardSectionsScriptTranslations() {
            return array(
                'lblReloadPageBtn' => __('Reload page', 'livepayments-mp-wc'),
                'lblSectionTitle' => __('LivePayments for Mobilpay - Transaction Reporting', 'livepayments-mp-wc'),
                'lblTitleTransactionsStatusCounts' => __('Transactions Status Counts', 'livepayments-mp-wc'),
                'lblTitleLastTransactionDetails' => __('Last Transaction', 'livepayments-mp-wc'),
                'warnDataNotFoundTitle' => __('Data not found!', 'livepayments-mp-wc'),
                'warnDataNotFoundLastTransactionDetails' => __('No transactions data found', 'livepayments-mp-wc'),
                'warnDataNotFoundTransactionsStatusCounts' => __('No transactions status counts data found', 'livepayments-mp-wc'),
                'errDataLoadingErrorTitle' => __('Error loading data', 'livepayments-mp-wc'),
                'errDataLoadingErrorLastTransactionDetails' => __('The last transaction details data could not be loaded due to an internal server issue. Please try again.', 'livepayments-mp-wc'),
                'errDataLoadingErrorTransactionsStatusCounts' => __('The transactions status counts data could not be loaded due to an internal server issue. Please try again.', 'livepayments-mp-wc'),
            );
        }

        public function getTransactionsListingScriptTranslations() {
            return array(
                'errCannotLoadTransactionDetails' 
                    => __('Could not load transaction details data', 'livepayments-mp-wc'),
                'errCannotLoadTransactionDetailsNetwork' 
                    => __('Could not load transaction details data due to a possible network issue', 'livepayments-mp-wc')
            );
        }

        public function getCommonScriptTranslations() {
            return array(
                'lblLoading' => __('Please wait...', 'livepayments-mp-wc')
            );
        }

        public function getInstaller() {
            return $this->_installer;
        }

        public function isActive() {
            return $this->_env->isPluginActive('livepayments-mp-wc/lvdwcmc-plugin-main.php');
        }

        public function getEnv() {
            return $this->_env;
        }

        public function getTextDomain() {
            return LVD_WCMC_TEXT_DOMAIN;
        }

        public function getMediaIncludes() {
            return $this->_mediaIncludes;
        }

        private static function _currentUserCanManageWooCommerce() {
            return current_user_can('manage_woocommerce');
        }

        private static function _currentUserCanActivatePlugins() {
            return current_user_can('activate_plugins');
        }  

        private function _shouldFormatWooCommerceLogMessage($args) {
            return !empty($args['context']) && (
                empty($args['context']['source']) 
                || $args['context']['source'] == MobilpayCreditCardGateway::GATEWAY_ID
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
            return $this->_formatters->getDisplayableTransactionDetails($transaction);
        }

        private function _formatTransactionAmount($amount) {
            return $this->_formatters->formatTransactionAmount($amount);
        }

        private function _formatTransactionTimestamp($strTimestamp) {
            return $this->_formatters->formatTransactionTimestamp($strTimestamp);
        }

        private function _addAjaxAction($action, $callBack, $noPriv = false) {
            add_action('wp_ajax_' . $action, $callBack);
            if ($noPriv) {
                add_action('wp_ajax_nopriv_' . $action, $callBack);
            }
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
            load_plugin_textdomain(LVD_WCMC_TEXT_DOMAIN, false, plugin_basename(LVD_WCMC_LANG_DIR));
        }

        private function _getInstallationErrorTranslations() {
            $this->_loadTextDomain();
            return array(
                Installer::INCOMPATIBLE_PHP_VERSION 
                    => sprintf(__('Minimum required PHP version is %s.', 'livepayments-mp-wc'), $this->_env->getRequiredPhpVersion()),
                Installer::INCOMPATIBLE_WP_VERSION 
                    => sprintf(__('Minimum required WordPress version is %s.', 'livepayments-mp-wc'), $this->_env->getRequiredWpVersion()),
                Installer::SUPPORT_MYSQLI_NOT_FOUND 
                    => __('Mysqli extension was not found on your system or is not fully compatible.', 'livepayments-mp-wc'),
                Installer::SUPPORT_OPENSSL_NOT_FOUND 
                    => __('Openssl extension was not found on your system or is not fully compatible.', 'livepayments-mp-wc'),
                Installer::GENERIC_ERROR 
                    => __('The installation failed.', 'livepayments-mp-wc')
            );
        }

        private function _getTransactionStatusLabel($status) {
            return MobilpayTransaction::getStatusLabel($status);
        }

        private function _checkMisingPlugins() {
            $this->_missingPlugins = array();
            foreach ($this->_requiredPlugins as $plugin => $checker) {
                if (!$checker()) {
                    $this->_missingPlugins[] = $plugin;
                }
            }
            return !$this->_hasMissingRequiredPlugins();
        }

        private function _hasMissingRequiredPlugins() {
            return !empty($this->_missingPlugins);
        }
    }
}