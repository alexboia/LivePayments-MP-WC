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
    class Settings {
        const OPT_MONITOR_DIAGNOSTICS = 'monitorDiagnostics';

        const OPT_SEND_DIAGNOSTICS_WARNING_TO_EMAIL = 'sendDiagnosticsWarningToEmail';

        const OPT_CHECKOUT_AUTO_REDIRECT_SECONDS = 'checkoutAutoRedirectSeconds';

        const OPT_SETTINGS_KEY = LVD_WCMC_PLUGIN_ID . '_settings';

        /**
         * @var \LvdWcMc\Settings
         */
        private static $_instance = null;

        /**
         * @var array
         */
        private $_data = null;

        private function __construct() {
            return;
        }

        public function __clone() {
            throw new Exception('Cloning a singleton of type ' . __CLASS__ . ' is not allowed');
        }

        /**
         * @return \LvdWcMc\Settings 
         */
        public static function getInstance() {
            if (self::$_instance == null) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        private function _loadSettingsIfNeeded() {
            if ($this->_data === null) {
                $this->_data = get_option(self::OPT_SETTINGS_KEY, array());
                if (!is_array($this->_data)) {
                    $this->_data = array();
                }
            }
        }

        private function _getOption($key, $default) {
            $this->_loadSettingsIfNeeded();
            $optionValue = isset($this->_data[$key]) 
                ? $this->_data[$key] 
                : $default;

            $this->_data[$key] = $optionValue;
            return $optionValue;
        }

        private function _setOption($key, $value) {
            $this->_loadSettingsIfNeeded();
            $this->_data[$key] = $value;
        }

        public function getMonitorDiagnostics() {
            return $this->_getOption(self::OPT_MONITOR_DIAGNOSTICS, false);
        }

        public function setMonitorDiagnostics($value) {
            $this->_setOption(self::OPT_MONITOR_DIAGNOSTICS, $value);
            return $this;
        }

        public function getSendDiagnosticsWarningToEmail() {
            return $this->_getOption(self::OPT_SEND_DIAGNOSTICS_WARNING_TO_EMAIL, null);
        }

        public function setSendDiagnosticsWarningToEmail($toEmailAddress) {
            $this->_setOption(self::OPT_SEND_DIAGNOSTICS_WARNING_TO_EMAIL, $toEmailAddress);
            return $this;
        }

        public function getCheckoutAutoRedirectSeconds() {
            return $this->_getOption(self::OPT_CHECKOUT_AUTO_REDIRECT_SECONDS, -1);
        }

        public function setCheckoutAutoRedirectSeconds($seconds) {
            $this->_setOption(self::OPT_CHECKOUT_AUTO_REDIRECT_SECONDS, $seconds);
            return $this;
        }

        public function asPlainObject() {
            $data = new \stdClass();
            $data->monitorDiagnostics = $this->getMonitorDiagnostics();
            $data->sendDiagnosticsWarningToEmail = $this->getSendDiagnosticsWarningToEmail();
            $data->checkoutAutoRedirectSeconds = $this->getCheckoutAutoRedirectSeconds();
            return $data;
        }

        public function saveSettings() {
            $this->_loadSettingsIfNeeded();
            update_option(self::OPT_SETTINGS_KEY, $this->_data);
		    return true;
        }

        public function purgeAllSettings() {
            $this->clearSettingsCache();
            return delete_option(self::OPT_SETTINGS_KEY);
        }

        public function clearSettingsCache() {
            $this->_data = null;
        }
    }
}