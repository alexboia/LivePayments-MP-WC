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

 namespace LvdWcMc {
    class MediaIncludes {
        const JS_MOXIE = 'moxiejs';

        const JS_PLUPLOAD = 'plupload';

        const JS_JQUERY = 'jquery';

        const JS_KITE_JS = 'kite-js';

        const JS_JQUERY_BLOCKUI = 'jquery-blockui';

        const JS_TOASTR = 'toastr';

        const JS_URIJS = 'urijs';

        const JS_LVD_ELEMENT_ENABLER_DISABLER = 'lvd-element-enabler-disabler-js';

        const JS_LVDWCMC_COMMON = 'lvdwcmc-common-js';

        const JS_LVDWCMC_GATEWAY_SETTINGS = 'lvdwcmc-mobilpay-cc-gateway-settings-js';

        const JS_LVDWCMC_TRANSACTION_LISTING = 'lvdwcmc-transaction-listing-js';

        const JS_LVDWCMC_PAYMENT_INITIATION = 'lvdwcmc-payment-initiation-js';

        const JS_LVDWCMC_WOOADMIN_DASHBOARD_SECTIONS = 'lvdwcmc-wooadmin-dashboard-sections-js';

        const JS_LVDWCMC_PLUGIN_SETTINGS = 'lvdwcmc-plugin-settings-js';

        const STYLE_TOASTR = 'toastr-css';

        const STYLE_LVDWCMC_COMMON = 'lvdwcmc-common-css';

        const STYLE_LVDWCMC_SETTINGS = 'lvdwcmc-settings-css';

        const STYLE_LVDWCMC_FRONTEND_TRANSACTION_DETAILS = 'lvdwcmc-frontend-transaction-details-css';

        const STYLE_LVDWCMC_ADMIN_TRANSACTION_DETAILS = 'lvdwcmc-admin-transaction-details-css';

        const STYLE_LVDWCMC_ADMIN_TRANSACTION_LISTING = 'lvdwcmc-admin-transaction-listing-css';

        const STYLE_LVDWCMC_DASHBOARD = 'lvdwcmc-dashboard-css';

        const STYLE_LVDWCMC_PLUGIN_DIAGNOSTICS = 'lvdwcmc-plugin-diagnostics';

        const STYLE_LVDWCMC_PLUGIN_SETTINGS = 'lvdwcmc-plugin-settings';

        private $_styles = array(
            self::STYLE_TOASTR => array(
                'path' => 'media/js/3rdParty/toastr/toastr.css',
                'version' => '2.1.1'
            ),
            self::STYLE_LVDWCMC_COMMON => array(
                'path' => 'media/css/lvdwcmc-common.css',
                'version' => LVD_WCMC_VERSION
            ),
            self::STYLE_LVDWCMC_SETTINGS => array(
                'path' => 'media/css/lvdwcmc-cc-gateway-settings.css',
                'version' => LVD_WCMC_VERSION,
                'deps' => array(
                    self::STYLE_TOASTR,
                    self::STYLE_LVDWCMC_COMMON
                )
            ),
            self::STYLE_LVDWCMC_FRONTEND_TRANSACTION_DETAILS => array(
                'path' => 'media/css/lvdwcmc-frontend-transaction-details.css',
                'version' => LVD_WCMC_VERSION
            ),
            self::STYLE_LVDWCMC_ADMIN_TRANSACTION_LISTING => array(
                'alias' => self::STYLE_LVDWCMC_ADMIN_TRANSACTION_DETAILS,
                'deps' => array(
                    self::STYLE_TOASTR,
                    self::STYLE_LVDWCMC_COMMON
                )
            ),
            self::STYLE_LVDWCMC_ADMIN_TRANSACTION_DETAILS => array(
                'path' => 'media/css/lvdwcmc-admin-transaction-details.css',
                'version' => LVD_WCMC_VERSION
            ),
            self::STYLE_LVDWCMC_DASHBOARD => array(
                'path' => 'media/css/lvdwcmc-dashboards.css',
                'version' => LVD_WCMC_VERSION,
                'deps' => array(
                    self::STYLE_LVDWCMC_COMMON
                )
            ),
            self::STYLE_LVDWCMC_PLUGIN_DIAGNOSTICS => array(
                'alias' => self::STYLE_LVDWCMC_COMMON
            ),
            self::STYLE_LVDWCMC_PLUGIN_SETTINGS => array(
                'alias' => self::STYLE_LVDWCMC_COMMON,
                'deps' => array(
                    self::STYLE_TOASTR
                )
            )
        );

        private $_scripts = array(
            self::JS_URIJS => array(
                'path' => 'media/js/3rdParty/urijs/URI.js', 
                'version' => '1.19.2'
            ),
            self::JS_JQUERY_BLOCKUI => array(
                'path' => 'media/js/3rdParty/jquery.blockUI.js', 
                'version' => '2.66',
                'deps' => array(
                    self::JS_JQUERY
                )
            ), 
            self::JS_KITE_JS => array(
                'path' => 'media/js/3rdParty/kite.js', 
                'version' => '1.0'
            ), 
            self::JS_TOASTR => array(
                'path' => 'media/js/3rdParty/toastr/toastr.js', 
                'version' => '2.1.1'
            ), 
            self::JS_LVD_ELEMENT_ENABLER_DISABLER => array(
                'path' => 'media/js/lvd-element-enabler-disabler.js',
                'version' => LVD_WCMC_VERSION,
                'deps' => array(
                    self::JS_JQUERY
                )
            ),
            self::JS_LVDWCMC_COMMON => array(
                'path' => 'media/js/lvdwcmc-common.js',
                'version' => LVD_WCMC_VERSION,
                'deps' => array(
                    self::JS_JQUERY,
                    self::JS_JQUERY_BLOCKUI
                )
            ),
            self::JS_LVDWCMC_GATEWAY_SETTINGS => array(
                'path' => 'media/js/lvdwcmc-mobilpay-cc-gateway-settings.js',
                'version' => LVD_WCMC_VERSION,
                'deps' => array(
                    self::JS_JQUERY,
                    self::JS_MOXIE,
                    self::JS_PLUPLOAD,
                    self::JS_TOASTR,
                    self::JS_URIJS,
                    self::JS_KITE_JS,
                    self::JS_LVDWCMC_COMMON
                )
            ),
            self::JS_LVDWCMC_TRANSACTION_LISTING => array(
                'path' => 'media/js/lvdwcmc-transaction-listing.js',
                'version' => LVD_WCMC_VERSION,
                'deps' => array(
                    self::JS_JQUERY,
                    self::JS_URIJS,
                    self::JS_TOASTR,
                    self::JS_KITE_JS,
                    self::JS_LVDWCMC_COMMON
                )
            ),
            self::JS_LVDWCMC_PAYMENT_INITIATION => array(
                'path' => 'media/js/lvdwcmc-payment-initiation.js',
                'version' => LVD_WCMC_VERSION,
                'deps' => array(
                    self::JS_JQUERY,
                    self::JS_JQUERY_BLOCKUI
                )
            ),
            self::JS_LVDWCMC_WOOADMIN_DASHBOARD_SECTIONS => array(
                'path' => 'media/js/lvdwcmc-woocommerce-admin-dashboard-sections.js',
                'version' => LVD_WCMC_VERSION,
                'deps' => array(
                    self::JS_JQUERY,
                    'wp-hooks',
			        'wp-element',
			        'wp-i18n',
			        'wc-components'
                )
            ),
            self::JS_LVDWCMC_PLUGIN_SETTINGS => array(
                'path' => 'media/js/lvdwcmc-plugin-settings.js',
                'version' => LVD_WCMC_VERSION,
                'deps' => array(
                    self::JS_JQUERY,
                    self::JS_URIJS,
                    self::JS_TOASTR,
                    self::JS_LVD_ELEMENT_ENABLER_DISABLER,
                    self::JS_LVDWCMC_COMMON
                )
            )
        );

        /**
         * Reference path used to compute asset URL
         * @var string
         */
        private $_refPluginsPath;

        /**
         * The media includes manager
         * @var \LivepaymentsCx\MediaIncludesManager
         */
        private $_manager;

        public function __construct($refPluginsPath, $scriptsInFooter) {
            if (empty($refPluginsPath)) {
                throw new \InvalidArgumentException('The $refPluginsPath parameter is required and may not be empty.');
            }

            $this->_manager = new MediaIncludesManager($this->_scripts, 
                $this->_styles, 
                $refPluginsPath, 
                $scriptsInFooter);

            $this->_refPluginsPath = $refPluginsPath;
        }

        private function _includeCommonScriptSettings() {
            wp_localize_script(self::JS_LVDWCMC_COMMON, 'lvdwcmcCommonSettings', array(
                'pluginMediaImgRootDir' => plugins_url('media/img', $this->_refPluginsPath)
            ));
        }

        public function includeScriptGatewaySettings($gatewaySettingsScriptLocalization, $commonScriptLocalization) {
            $this->_manager->enqueueScript(self::JS_LVDWCMC_GATEWAY_SETTINGS);

            if (!empty($commonScriptLocalization)) {
                wp_localize_script(self::JS_LVDWCMC_COMMON,
                    'lvdwcmcCommonScriptL10n', 
                    $commonScriptLocalization);
            }

            if (!empty($gatewaySettingsScriptLocalization)) {
                wp_localize_script(self::JS_LVDWCMC_GATEWAY_SETTINGS, 
                    'lvdwcmcSettingsL10n', 
                    $gatewaySettingsScriptLocalization);
            }

            $this->_includeCommonScriptSettings();
        }

        public function includeScriptTransactionListing($transactionsScriptLocalization, $commonScriptLocalization) {
            $this->_manager->enqueueScript(self::JS_LVDWCMC_TRANSACTION_LISTING);
            
            if (!empty($commonScriptLocalization)) {
                wp_localize_script(self::JS_LVDWCMC_COMMON,
                    'lvdwcmcCommonScriptL10n', 
                    $commonScriptLocalization);
            }

            if (!empty($transactionsScriptLocalization)) {
                wp_localize_script(self::JS_LVDWCMC_TRANSACTION_LISTING, 
                    'lvdwcmcTransactionsListL10n', 
                    $transactionsScriptLocalization);
            }

            $this->_includeCommonScriptSettings();
        }

        public function includeScriptPaymentInitiation() {
            $this->_manager->enqueueScript(self::JS_LVDWCMC_PAYMENT_INITIATION);
        }

        public function includeScriptWooAdminDashboardSections($localization) {
            $this->_manager->enqueueScript(self::JS_LVDWCMC_WOOADMIN_DASHBOARD_SECTIONS);
            if (!empty($localization)) {
                wp_localize_script(self::JS_LVDWCMC_WOOADMIN_DASHBOARD_SECTIONS, 
                    'lvdwcmcWooAdminDashboardSectionsL10n', 
                    $localization);
            }
        }

        public function includeScriptPluginSettings($pluginSettingsScriptLocalization, $commonScriptLocalization) {
            $this->_manager->enqueueScript(self::JS_LVDWCMC_PLUGIN_SETTINGS);

            if (!empty($commonScriptLocalization)) {
                wp_localize_script(self::JS_LVDWCMC_COMMON,
                    'lvdwcmcCommonScriptL10n', 
                    $commonScriptLocalization);
            }

            if (!empty($pluginSettingsScriptLocalization)) {
                wp_localize_script(self::JS_LVDWCMC_PLUGIN_SETTINGS, 
                    'lvdwcmcPluginSettingsL10n', 
                    $pluginSettingsScriptLocalization);
            }

            $this->_includeCommonScriptSettings();
        }

        public function includeStyleCommon() {
            $this->_manager->enqueueStyle(self::STYLE_LVDWCMC_COMMON);
        }

        public function includeStyleSettings() {
            $this->_manager->enqueueStyle(self::STYLE_LVDWCMC_SETTINGS);
        }

        public function includeStyleAdminTransactionListing() {
            wp_enqueue_style('woocommerce_admin_styles');
            $this->_manager->enqueueStyle(self::STYLE_LVDWCMC_ADMIN_TRANSACTION_LISTING);
        }

        public function includeStyleFrontendTransactionDetails() {
            $this->_manager->enqueueStyle(self::STYLE_LVDWCMC_FRONTEND_TRANSACTION_DETAILS);
        }

        public function includeStyleAdminTransactionDetails() {
            $this->_manager->enqueueStyle(self::STYLE_LVDWCMC_ADMIN_TRANSACTION_DETAILS);
        }

        public function includeStyleDashboard() {
            $this->_manager->enqueueStyle(self::STYLE_LVDWCMC_DASHBOARD);
        }

        public function includeStylePluginDiagnostics() {
            $this->_manager->enqueueStyle(self::STYLE_LVDWCMC_PLUGIN_DIAGNOSTICS);
        }

        public function includeStylePluginSettings() {
            $this->_manager->enqueueStyle(self::STYLE_LVDWCMC_PLUGIN_SETTINGS);
        }
    }
}