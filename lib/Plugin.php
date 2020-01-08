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
         * @var string The identifier of the plug-in text domain
         */
        private $_textDomain = LVD_WCMC_TEXT_DOMAIN;

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

            $this->_env = new Env();
            $this->_installer = new Installer($this->_env);

            $this->_mediaIncludes = new MediaIncludes(
                $options['mediaIncludes']['refPluginsPath'], 
                $options['mediaIncludes']['scriptsInFooter']
            );
        }

        private function _loadTextDomain() {
            load_plugin_textdomain($this->_textDomain, false, plugin_basename(LVD_WCMC_LANG_DIR));
        }

        private function __($text) {
            return esc_html__($text, LVD_WCMC_TEXT_DOMAIN);
        }

        private function _getInstallationErrorTranslations() {
            $this->_loadTextDomain();
            return array(
                Installer::INCOMPATIBLE_PHP_VERSION 
                    => sprintf($this->__('Minimum required PHP version is %s.'), $this->_env->getRequiredPhpVersion()),
                Installer::INCOMPATIBLE_WP_VERSION 
                    => sprintf($this->__('Minimum required WordPress version is %s.'), $this->_env->getRequiredWpVersion()),
                Installer::SUPPORT_MYSQLI_NOT_FOUND 
                    => $this->__('Mysqli extension was not found on your system or is not fully compatible.'),
                Installer::SUPPORT_OPENSSL_NOT_FOUND 
                    => $this->__('Openssl extension was not found on your system or is not fully compatible.'),
                Installer::GENERIC_ERROR 
                    => $this->__('The installation failed.')
            );
        }

        public function run() {
            register_activation_hook(LVD_WCMC_MAIN, array($this, 'onActivatePlugin'));
            register_deactivation_hook(LVD_WCMC_MAIN, array($this, 'onDeactivatePlugin'));
            register_uninstall_hook(LVD_WCMC_MAIN, array($this, 'onUninstallPlugin'));

            add_action('plugins_loaded', array($this, 'onPluginsLoaded'));
            add_action('init', array($this, 'onPluginsInit'));
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
                    : $this->__('Could not activate plug-in: requirements not met.');

                deactivate_plugins(plugin_basename(LVD_WCMC_MAIN));
                wp_die(lvdwcmc_append_error($message, $this->_installer->getLastError()),  $this->__('Activation error'));
            } else {
                if (!$this->_installer->activate()) {
                    wp_die(lvdwcmc_append_error($this->__('Could not activate plug-in: activation failure.'), $this->_installer->getLastError()), $this->__('Activation error'));
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

            add_filter('woocommerce_payment_gateways', array($this, 'onWooCommercePaymentGatewaysRequested'), 10, 1);
        }

        public function onPluginsInit() {
            $this->_loadTextDomain();
            $this->_installer->updateIfNeeded();
        }

        public function onWooCommercePaymentGatewaysRequested($methods) {
            $methods[] = '\LvdWcMc\MobilpayCreditCardGateway';
            return $methods;
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
    }
}