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

    use LvdWcMc\PluginModules\AdminTransactionDetailsModule;
    use LvdWcMc\PluginModules\GatewayDiagnosticsModule;
    use LvdWcMc\PluginModules\GatewaySetupModule;
    use LvdWcMc\PluginModules\OrderTransactionSupportModule;
    use LvdWcMc\PluginModules\PluginSettingsModule;
    use LvdWcMc\PluginModules\WebPageAssetsExtensionPointsProviderModule;
    use LvdWcMc\PluginModules\WooCommerceAdminDashboardReportingWidgetsModule;
    use LvdWcMc\PluginModules\WordPressDashboardReportingWidgetsModule;

    class Plugin {
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
         * @var \LvdWcMc\WooCommerceLoggingFormatter
         */
        private $_loggingFormatter = null;

        /**
         * @var \LvdWcMc\PluginDependencyChecker
         */
        private $_pluginDependencyChecker = null;

        /**
         * @var \LvdWcMc\PluginViewEngine
         */
        private $_viewEngine = null;

        /**
         * @var \LvdWcMc\Settings
         */
        private $_settings = null;

        /**
         * @var \LvdWcMc\PluginModules\PluginModule[]
         */
        private $_pluginModules = array();

        public function __construct(array $options) {
            $this->_env = lvdwcmc_get_env();
            $this->_installer = new Installer();
            $this->_shortcodes = new Shortcodes();
            $this->_transactionFactory = new MobilpayTransactionFactory();
            $this->_report = new TransactionReport();
            $this->_apiServer = new ApiServer();
            $this->_formatters = new Formatters();
            $this->_settings = lvdwcmc_get_settings();

            $this->_loggingFormatter = new WooCommerceLoggingFormatter(LVD_WCMC_WOOCOMMERCE_CC_GATEWAY_ID);
            $this->_viewEngine = new PluginViewEngine();

            $this->_pluginDependencyChecker = 
                new PluginDependencyChecker(array(
                    'woocommerce/woocommerce.php' => function() {
                        return defined('WC_PLUGIN_FILE') 
                            && class_exists('WC_Payment_Gateway') 
                            && class_exists('WooCommerce')
                            && function_exists('WC');
                    }
                ));

			$options = $this->_ensureDefaultOptions($options);
            $this->_mediaIncludes = new MediaIncludes(
                $options['mediaIncludes']['refPluginsPath'], 
                $options['mediaIncludes']['scriptsInFooter']
            );

            $this->_initModules();
        }
		
		private function _ensureDefaultOptions(array $options) {
            if (!isset($options['mediaIncludes']) || !is_array($options['mediaIncludes'])) {
                $options['mediaIncludes'] = array(
                    'refPluginsPath' => LPWOOTRK_PLUGIN_MAIN,
                    'scriptsInFooter' => true
                );
            }
            
            return $options;
        }

        private function _initModules() {
            $this->_pluginModules = array(
                new GatewaySetupModule($this),
                new PluginSettingsModule($this),
                new GatewayDiagnosticsModule($this),
                new AdminTransactionDetailsModule($this),
                new OrderTransactionSupportModule($this),
                new WordPressDashboardReportingWidgetsModule($this),
                new WooCommerceAdminDashboardReportingWidgetsModule($this),
                new WebPageAssetsExtensionPointsProviderModule($this)
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

            $testInstallationErrorCode = $this->_installer->canBeInstalled();
            if (!$this->_wasInstallationTestSuccessful($testInstallationErrorCode)) {
                $message = $this->_getInstallationErrorMessage($testInstallationErrorCode);
                $this->_abortPluginInstallation($message);
            } else {
                if (!$this->_installer->activate()) {
                    $message = __('Could not activate plug-in: activation failure.', 'livepayments-mp-wc');
                    $this->_displayActivationErrrorMessage($message);
                }
            }
        }

        private function _wasInstallationTestSuccessful($testInstallationErrorCode) {
            return Installer::wasInstallationTestSuccessful($testInstallationErrorCode);
        }

        private function _getInstallationErrorMessage($installationErrorCode) {
			$this->_loadTextDomain();
            $errors = $this->_getInstallationErrorTranslations();
            return isset($errors[$installationErrorCode]) 
                ? $errors[$installationErrorCode] 
                : __('Could not activate plug-in: requirements not met.', 'livepayments-mp-wc');
        }
		
		private function _getInstallationErrorTranslations() {
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

        private function _displayActivationErrrorMessage($message) {
            $displayMessage = lvdwcmc_append_error($message, 
                $this->_installer->getLastError());
				
			$displayTitle = __('Activation error', 
                'livepayments-mp-wc');
                
            wp_die($displayMessage, $displayTitle);
        }

        private function _abortPluginInstallation($message) {
            deactivate_plugins(plugin_basename(LVD_WCMC_MAIN));
            $this->_displayActivationErrrorMessage($message);
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

        private static function _currentUserCanActivatePlugins() {
            return current_user_can('activate_plugins');
        }

        public function onPluginsLoaded() {
            if ($this->_checkIfDependenciesSatisfied()) {
                $this->_setupLogging();
                $this->_setupPluginModules();
            } else {
                $this->_registerMissingPluginsWarning();
            }
        }

        private function _setupLogging() {
            $this->_loggingFormatter->interceptWooCommerceLogEntries();
        }

        private function _setupPluginModules() {
            foreach ($this->_pluginModules as $module) {
                $module->load();
            }
        }

        private function _registerMissingPluginsWarning() {
            add_action('admin_notices', array($this, 'onAdminNoticesRenderMissingPluginsWarning'));
        }
		
		public function onAdminNoticesRenderMissingPluginsWarning() {
            $data = new \stdClass();
            $data->missingPlugins = $this->_pluginDependencyChecker
                ->getMissingRequiredPlugins();
            echo $this->_viewEngine->renderView('lvdwcmc-admin-notices-missing-required-plugins.php', 
                $data);
        }

        public function onPluginsRestApiInit() {
            if ($this->_checkIfDependenciesSatisfied()) {
                $this->_apiServer->listen();
            }
        }

        public function onPluginsInit() {
            $this->_loadTextDomain();
            $this->_installer->updateIfNeeded();
        }

        public function getGatewaySettingsScriptTranslations() {
            return TranslatedScriptMessages::getGatewaySettingsScriptTranslations();
        }

        public function getWooAdminDashboardSectionsScriptTranslations() {
            return TranslatedScriptMessages::getWooAdminDashboardSectionsScriptTranslations();
        }

        public function getTransactionsListingScriptTranslations() {
            return TranslatedScriptMessages::getTransactionsListingScriptTranslations();
        }

        public function getPluginSettingsScriptTranslations() {
            return TranslatedScriptMessages::getPluginSettingsScriptTranslations();
        }

        public function getCommonScriptTranslations() {
            return TranslatedScriptMessages::getCommonScriptTranslations();
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

        public function getViewEngine() {
            return $this->_viewEngine;
        }

        public function getFormatters() {
            return $this->_formatters;
        }

        public function getTransactionFactory() {
            return $this->_transactionFactory;
        }

        public function getShortcodes() {
            return $this->_shortcodes;
        }

        public function getReport() {
            return $this->_report;
        }

        public function getSettings() {
            return $this->_settings;
        }

        public function getAjaxBaseUrl() {
            return get_admin_url(null, 'admin-ajax.php', 'admin');
        }

        private function _loadTextDomain() {
            load_plugin_textdomain(LVD_WCMC_TEXT_DOMAIN, 
				false, 
				plugin_basename(LVD_WCMC_LANG_DIR));
        }

        private function _checkIfDependenciesSatisfied() {
            return $this->_pluginDependencyChecker
                ->checkIfDependenciesSatisfied();
        }
    }
}