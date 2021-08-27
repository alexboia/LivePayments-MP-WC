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

    use LvdWcMc\MobilpayCreditCardGatewayDiagnosticsEmail;
    use LvdWcMc\MobilpayCreditCardGatewayDiagnostics;
    use LvdWcMc\Plugin;
    use LvdWcMc\PluginMenu;
    use LvdWcMc\SystemInfoPropertiesProvider;

    class GatewayDiagnosticsModule extends PluginModule {
        const MENU_HOOK_ORDER = 30;

        /**
         * @var \LvdWcMc\SystemInfoPropertiesProvider
         */
        private $_systemInfoPropertiesProvider;

        public function __construct(Plugin $plugin) {
            parent::__construct($plugin);
            $this->_systemInfoPropertiesProvider = 
                new SystemInfoPropertiesProvider();
        }

        public function load() {
            $this->_registerMenuHook();
            $this->_registerWebPageAssets();
            $this->_storeInitialGatewaySetupStatusIfDoesNotExist();
            $this->_setupGatewayDiagnosticsWarningEmail();
            $this->_registerAutoGatewayDiagnosticsWpCron();
        }

        private function _storeInitialGatewaySetupStatusIfDoesNotExist() {
            $this->_getMobilpayCreditCardGatewayDiagnostics()
                ->storeInitialGatewaySetupStatusIfDoesNotExist();
        }

        private function _setupGatewayDiagnosticsWarningEmail() {
            add_filter('woocommerce_email_classes', 
                array($this, 'registerGatewayDiagnosticsWarningEmail'), 
                10, 1);
        }

        public static function registerGatewayDiagnosticsWarningEmail($emails) {
            $emails['LvdWcMc_GatewayDiagnosticsEmail'] = new MobilpayCreditCardGatewayDiagnosticsEmail();
            return $emails;
        }

        private function _registerAutoGatewayDiagnosticsWpCron() {
            add_action('lvdwcmc_auto_gateway_diagnostics', 
                array($this, 'runAutoGatewayDiagnosticsCron'));
        }

        public function runAutoGatewayDiagnosticsCron() {
            $gatewayDiagnostics = $this->_getMobilpayCreditCardGatewayDiagnostics();
            if ($gatewayDiagnostics->canSendGatewayDiagnosticsWarningNotification()) {
                write_log('Gateway configured but not ok. Sending diagnostics warning e-mail...');
                $gatewayDiagnostics->sendGatewayDiagnosticsWarningNotification($this->_getSendDiagnosticsWarningEmail());
            } else {
                write_log('Gateway either not configured or no warnings found. Nothing to be done.');
            }
        }

        private function _getSendDiagnosticsWarningEmail() {
            return $this
                ->_getSettings()
                ->getSendDiagnosticsWarningToEmail();
        }

        private function _registerWebPageAssets() {
            add_action('admin_enqueue_scripts', 
                array($this, 'onAdminEnqueueStyles'), 9998);
        }

        public function onAdminEnqueueStyles() {
            if ($this->_env->isViewingAdminPluginDiagnosticsPage()) {
                $this->_mediaIncludes
                    ->includeStylePluginDiagnostics();
            }
        }

        private function _registerMenuHook() {
            add_action('admin_menu', 
                array($this, 'onAddAdminMenuEntries'), 
                self::MENU_HOOK_ORDER);
        }

        public function onAddAdminMenuEntries() {
            PluginMenu::registerSubMenuEntryWithCallback(PluginMenu::MAIN_ENTRY, 
                PluginMenu::DIAGNOSTICS_ENTRY, 
                array($this, 'showDiagnosticsPage'));
        }

        public function showDiagnosticsPage() {
            $data = new \stdClass();
            $gatewayDiagnostics = $this->_getMobilpayCreditCardGatewayDiagnostics();

            $data->systemInfo = $this->_getSystemInfoProperties();
            $data->gatewaySettingsPageUrl = $gatewayDiagnostics->getGatewaySettingsPageUrl();
            $data->gatewayConfigured = $gatewayDiagnostics->isGatewayConfigured();
            $data->gatewayDiagnosticMessages = $gatewayDiagnostics->getDiagnosticMessages();
            $data->gatewayOk = $gatewayDiagnostics->isGatewayOk();

            echo $this->_viewEngine->renderView('lvdwcmc-plugin-diagnostics.php', 
                $data);
        }

        private function _getSystemInfoProperties() {
            return $this->_systemInfoPropertiesProvider
                ->getSystemInfoProperties();
        }

        private function _getMobilpayCreditCardGatewayDiagnostics() {
            return new MobilpayCreditCardGatewayDiagnostics();
        }
    }
}