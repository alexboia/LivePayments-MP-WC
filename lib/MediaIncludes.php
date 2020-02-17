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

        const JS_LVDWCMC_COMMON = 'lvdwcmc-common-js';

        const JS_LVDWCMC_SETTINGS = 'lvdwcmc-mobilpay-cc-gateway-settings-js';

        const STYLE_TOASTR = 'toastr-css';

        const STYLE_LVDWCMC_COMMON = 'lvdwcmc-common-css';

        const STYLE_LVDWCMC_SETTINGS = 'lvdwcmc-settings-css';

        const STYLE_LVDWCMC_FRONTEND_TRANSACTION_DETAILS = 'lvdwcmc-frontend-transaction-details-css';

        const STYLE_LVDWCMC_ADMIN_TRANSACTION_DETAILS = 'lvdwcmc-admin-transaction-details-css';

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
                'path' => 'media/css/lvdwcmc-mobilpay-cc-gateway-settings.css',
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
            self::STYLE_LVDWCMC_ADMIN_TRANSACTION_DETAILS => array(
                'path' => 'media/css/lvdwcmc-admin-transaction-details.css',
                'version' => LVD_WCMC_VERSION
            )
        );

        private $_scripts = array(
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
                    self::JS_LVDWCMC_COMMON,
                    self::JS_JQUERY,
                    self::JS_MOXIE,
                    self::JS_PLUPLOAD,
                    self::JS_TOASTR
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

        private function _enqueueScript($handle) {
            if (empty($handle)) {
                return;
            }
            if (isset($this->_scripts[$handle])) {
                if (!wp_script_is($handle, 'registered')) {
                    $script = $this->_scripts[$handle];
                    $deps = isset($script['deps']) && is_array($script['deps']) 
                        ? $script['deps'] 
                        : array();
    
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
                $style = $this->_styles[$handle];
                $deps = isset($style['deps']) && is_array($style['deps']) 
                    ? $style['deps'] 
                    : array();

                if (!isset($style['media']) || !$style['media']) {
                    $style['media'] = 'all';
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

        public function includeScriptCommon() {
            $this->_enqueueScript(self::JS_JQUERY);
            $this->_enqueueScript(self::JS_JQUERY_BLOCKUI);
            $this->_enqueueScript(self::JS_LVDWCMC_COMMON);
        }

        public function includeScriptSettings() {
            $this->_enqueueScript(self::JS_JQUERY);
            $this->_enqueueScript(self::JS_MOXIE);
            $this->_enqueueScript(self::JS_PLUPLOAD);
            $this->_enqueueScript(self::JS_TOASTR);
            $this->_enqueueScript(self::JS_JQUERY_BLOCKUI);
            $this->_enqueueScript(self::JS_KITE_JS);
            $this->_enqueueScript(self::JS_LVDWCMC_COMMON);
            $this->_enqueueScript(self::JS_LVDWCMC_SETTINGS);
        }

        public function includeStyleCommon() {
            $this->_enqueueStyle(self::STYLE_LVDWCMC_COMMON);
        }

        public function includeStyleSettings() {
            $this->_enqueueStyle(self::STYLE_TOASTR);
            $this->_enqueueStyle(self::STYLE_LVDWCMC_COMMON);
            $this->_enqueueStyle(self::STYLE_LVDWCMC_SETTINGS);
        }

        public function includeStyleAdminTransactionListing() {
            wp_enqueue_style('woocommerce_admin_styles');
            $this->_enqueueStyle(self::STYLE_LVDWCMC_COMMON);
        }

        public function includeStyleFrontendTransactionDetails() {
            $this->_enqueueStyle(self::STYLE_LVDWCMC_FRONTEND_TRANSACTION_DETAILS);
        }

        public function includeStyleAdminTransactionDetails() {
            $this->_enqueueStyle(self::STYLE_LVDWCMC_ADMIN_TRANSACTION_DETAILS);
        }

        public function localizeSettingsScript($translations) {
            wp_localize_script(self::JS_LVDWCMC_SETTINGS, 
                'lvdwcmcSettingsL10n', 
    			$translations);
        }
    }
}