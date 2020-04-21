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
    class MediaIncludes {
        const JS_MOXIE = 'moxiejs';

        const JS_PLUPLOAD = 'plupload';

        const JS_JQUERY = 'jquery';

        const JS_KITE_JS = 'kite-js';

        const JS_JQUERY_BLOCKUI = 'jquery-blockui';

        const JS_TOASTR = 'toastr';

        const JS_URIJS = 'urijs';

        const JS_LVDWCMC_COMMON = 'lvdwcmc-common-js';

        const JS_LVDWCMC_SETTINGS = 'lvdwcmc-mobilpay-cc-gateway-settings-js';

        const JS_LVDWCMC_TRANSACTION_LISTING = 'lvdwcmc-transaction-listing-js';

        const JS_LVDWCMC_PAYMENT_INITIATION = 'lvdwcmc-payment-initiation-js';

        const STYLE_TOASTR = 'toastr-css';

        const STYLE_LVDWCMC_COMMON = 'lvdwcmc-common-css';

        const STYLE_LVDWCMC_SETTINGS = 'lvdwcmc-settings-css';

        const STYLE_LVDWCMC_FRONTEND_TRANSACTION_DETAILS = 'lvdwcmc-frontend-transaction-details-css';

        const STYLE_LVDWCMC_ADMIN_TRANSACTION_DETAILS = 'lvdwcmc-admin-transaction-details-css';

        const STYLE_LVDWCMC_ADMIN_TRANSACTION_LISTING = 'lvdwcmc-admin-transaction-listing-css';

        const STYLE_LVDWCMC_DASHBOARD = 'lvdwcmc-dashboard-css';

        private $_refPluginsPath;

        private $_scriptsInFooter;

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
            self::JS_LVDWCMC_COMMON => array(
                'path' => 'media/js/lvdwcmc-common.js',
                'version' => LVD_WCMC_VERSION,
                'deps' => array(
                    self::JS_JQUERY,
                    self::JS_JQUERY_BLOCKUI
                )
            ),
            self::JS_LVDWCMC_SETTINGS => array(
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
            )
        );

        public function __construct($refPluginsPath, $scriptsInFooter) {
            if (empty($refPluginsPath)) {
                throw new \InvalidArgumentException('The $refPluginsPath parameter is required and may not be empty.');
            }

            $this->_refPluginsPath = $refPluginsPath;
            $this->_scriptsInFooter = $scriptsInFooter;
        }

        private  function _hasScript($handle) {
            return !empty($this->_scripts[$handle]);
        }
    
        private function _hasStyle($handle) {
            return !empty($this->_styles[$handle]);
        }

        private function _getActualElement($handle, array &$collection) {
            $script = null;
            $actual = null;
    
            if (isset($collection[$handle])) {
                $script = $collection[$handle];
                if (!empty($script['alias'])) {
                    $handle = $script['alias'];
                    $actual = isset($collection[$handle]) 
                        ? $collection[$handle]
                        : null;
                }
    
                if (!empty($actual)) {
                    $deps = isset($script['deps']) 
                        ? $script['deps'] 
                        : null;
                    if (!empty($deps)) {
                        $actual['deps'] = $deps;
                    }
                } else {
                    $actual = $script;
                }
            }
    
            return $actual;
        }
    
        private function _getActualScriptToInclude($handle) {
            return $this->_getActualElement($handle, $this->_scripts);
        }
    
        private function _getActualStyleToInclude($handle) {
            return $this->_getActualElement($handle, $this->_styles);
        }

        private function _ensureScriptDependencies(array $deps) {
            foreach ($deps as $depHandle) {
                if ($this->_hasScript($depHandle)) {
                    $this->_enqueueScript($depHandle);
                }
            }
        }
    
        private  function _ensureStyleDependencies(array $deps) {
            foreach ($deps as $depHandle) {
                if ($this->_hasStyle($depHandle)) {
                    $this->_enqueueStyle($depHandle);
                }
            }
        }

        private function _enqueueScript($handle) {
            if (empty($handle)) {
                return;
            }
            if (isset($this->_scripts[$handle])) {
                if (!wp_script_is($handle, 'registered')) {
                    $script = $this->_getActualScriptToInclude($handle);

                    $deps = isset($script['deps']) && is_array($script['deps']) 
                        ? $script['deps'] 
                        : array();

                    if (!empty($deps)) {
                        $this->_ensureScriptDependencies($deps);
                    }
    
                    wp_enqueue_script($handle, 
                        plugins_url($script['path'], $this->_refPluginsPath), 
                        $deps, 
                        $script['version'], 
                        $this->_scriptsInFooter);

                    if (isset($script['inline-setup'])) {
                        wp_add_inline_script($handle, $script['inline-setup']);
                    }
                } else {
                    wp_enqueue_script($handle);
                }
            } else {
                wp_enqueue_script($handle);
            }
        }

        private function _enqueueStyle($handle) {
            if (empty($handle)) {
                return;
            }
            if (isset($this->_styles[$handle])) {
                $style = $this->_getActualStyleToInclude($handle);

                if (!isset($style['media']) || !$style['media']) {
                    $style['media'] = 'all';
                }

                $deps = isset($style['deps']) && is_array($style['deps']) 
                    ? $style['deps'] 
                    : array();

                if (!empty($deps)) {
                    $this->_ensureStyleDependencies($deps);
                }

                wp_enqueue_style($handle, 
                    plugins_url($style['path'], $this->_refPluginsPath), 
                    $deps, 
                    $style['version'], 
                    $style['media']);
            } else {
                wp_enqueue_style($handle);
            }
        }

        public function _includeCommonScriptSettings() {
            wp_localize_script(self::JS_LVDWCMC_COMMON, 'lvdwcmcCommonSettings', array(
                'pluginMediaImgRootDir' => plugins_url('media/img', $this->_refPluginsPath)
            ));
        }

        public function includeScriptSettings($settingsScriptLocalization, $commonScriptLocalization) {
            $this->_enqueueScript(self::JS_LVDWCMC_SETTINGS);

            if (!empty($commonScriptLocalization)) {
                wp_localize_script(self::JS_LVDWCMC_COMMON,
                    'lvdwcmcCommonScriptL10n', 
                    $commonScriptLocalization);
            }

            if (!empty($settingsScriptLocalization)) {
                wp_localize_script(self::JS_LVDWCMC_SETTINGS, 
                    'lvdwcmcSettingsL10n', 
                    $settingsScriptLocalization);
            }

            $this->_includeCommonScriptSettings();
        }

        public function includeScriptTransactionListing($transactionsScriptLocalization, $commonScriptLocalization) {
            $this->_enqueueScript(self::JS_LVDWCMC_TRANSACTION_LISTING);
            
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
            $this->_enqueueScript(self::JS_LVDWCMC_PAYMENT_INITIATION);
        }

        public function includeStyleCommon() {
            $this->_enqueueStyle(self::STYLE_LVDWCMC_COMMON);
        }

        public function includeStyleSettings() {
            $this->_enqueueStyle(self::STYLE_LVDWCMC_SETTINGS);
        }

        public function includeStyleAdminTransactionListing() {
            wp_enqueue_style('woocommerce_admin_styles');
            $this->_enqueueStyle(self::STYLE_LVDWCMC_ADMIN_TRANSACTION_LISTING);
        }

        public function includeStyleFrontendTransactionDetails() {
            $this->_enqueueStyle(self::STYLE_LVDWCMC_FRONTEND_TRANSACTION_DETAILS);
        }

        public function includeStyleAdminTransactionDetails() {
            $this->_enqueueStyle(self::STYLE_LVDWCMC_ADMIN_TRANSACTION_DETAILS);
        }

        public function includeStyleDashboard() {
            $this->_enqueueStyle(self::STYLE_LVDWCMC_DASHBOARD);
        }
    }
}