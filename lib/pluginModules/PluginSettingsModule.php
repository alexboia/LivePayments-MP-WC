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

	use LvdWcMc\Plugin;
	use LvdWcMc\PluginMenu;

	class PluginSettingsModule extends PluginModule {
		const ACTION_SAVE_SETTINGS = 'lvdwcmc_save_settings';

		const MENU_HOOK_ORDER = 10;

		/**
		 * @var \LvdWcMc\WordPressAdminAjaxAction
		 */
		private $_saveSettingsAction;

		public function __construct(Plugin $plugin) {
			parent::__construct($plugin);
			$this->_saveSettingsAction = $this
				->_createAdminAjaxAction(self::ACTION_SAVE_SETTINGS, 
					array($this, 'saveSettings'), 
					true, 
					'manage_options');
		}

		public function load() {
			$this->_registerWebPageAssets();
			$this->_registerMenuHook();

			$this->_saveSettingsAction
				->register();
		}

		private function _registerWebPageAssets() {
			add_action('admin_enqueue_scripts', 
				array($this, 'onAdminEnqueueScripts'), 9998);
			add_action('admin_enqueue_scripts', 
				array($this, 'onAdminEnqueueStyles'), 9998);
		}

		public function onAdminEnqueueScripts() {
			if ($this->_env->isViewingAdminPluginSettingsPage()) {
				$this->_mediaIncludes->includeScriptPluginSettings(
					$this->_plugin->getPluginSettingsScriptTranslations(), 
					$this->_plugin->getCommonScriptTranslations()
				);
			}
		}

		public function onAdminEnqueueStyles() {
			if ($this->_env->isViewingAdminPluginSettingsPage()) {
				$this->_mediaIncludes->includeStylePluginSettings();
			}
		}

		private function _registerMenuHook() {
			add_action('admin_menu', 
				array($this, 'onAddAdminMenuEntries'),
				self::MENU_HOOK_ORDER);
		}

		public function onAddAdminMenuEntries() {
			$callback = array($this, 'showSettingsForm');
			PluginMenu::registerMenuEntryWithCallback(PluginMenu::MAIN_ENTRY, 
				$callback);
			PluginMenu::registerSubMenuEntryWithCallback(PluginMenu::MAIN_ENTRY, 
				PluginMenu::SETTINGS_ENTRY, 
				$callback);
		}

		public function showSettingsForm() {
			if (!$this->_currentUserCanManageOptions()) {
				die;
			}

			$settings = $this->_getSettings();

			$data = new \stdClass();
			$data->ajaxBaseUrl = $this->_getAjaxBaseUrl();
			$data->saveSettingsAction = self::ACTION_SAVE_SETTINGS;
			$data->saveSettingsNonce = $this->_saveSettingsAction
				->generateNonce();

			$data->adminEmailAddress = $this->_getBlogAdminEmailAddress();
			$data->settings = $settings
				->asPlainObject();

			echo $this->_viewEngine->renderView('lvdwcmc-plugin-settings.php', 
				$data);
		}

		public function saveSettings() {
			if (!$this->_env->isHttpPost()) {
				die;
			}

			$settings = $this->_getSettings();
			$response = lvdwcmc_get_ajax_response();

			$monitorDiagnostics = $this->_getMonitorDiagnosticsFromHttpPost();

			if ($monitorDiagnostics) {
				$sendDiagnosticsWarningToEmail = $this->_getSendDiagnosticsWarningToEmailFromHttpPost();
				if ($this->_isValidEmailAddress($sendDiagnosticsWarningToEmail)) {
					$response->message = __('Please fill in a valid e-mail address to which diagnostics warnings will be sent.', 'livepayments-mp-wc');
					return $response;
				}
			} else {
				$sendDiagnosticsWarningToEmail = null;
			}

			$settings->setMonitorDiagnostics($monitorDiagnostics);
			$settings->setSendDiagnosticsWarningToEmail($sendDiagnosticsWarningToEmail);
			$settings->setCheckoutAutoRedirectSeconds($this->_getCheckoutAutoRedirectSecondsFromHttpPost());

			if ($settings->saveSettings()) {
				if ($monitorDiagnostics) {
					$this->_scheduleGatewayDiagnosticsCron();
				} else {
					$this->_unscheduleGatewayDiagnosticsCron();
				}

				$response->success = true;
			} else {
				$response->message = esc_html__('The settings could not be saved. Please try again.', 'livepayments-mp-wc');
			}

			return $response;
		}

		private function _scheduleGatewayDiagnosticsCron() {
			if (wp_next_scheduled('lvdwcmc_auto_gateway_diagnostics') === false) {
				wp_schedule_event(time() + 60, 'daily', 'lvdwcmc_auto_gateway_diagnostics');
			}
		}

		private function _unscheduleGatewayDiagnosticsCron() {
			if (($timestamp = wp_next_scheduled('lvdwcmc_auto_gateway_diagnostics')) !== false) {
				wp_unschedule_event($timestamp, 'lvdwcmc_auto_gateway_diagnostics');
			}
		}

		private function _getMonitorDiagnosticsFromHttpPost() {
			return isset($_POST['monitorDiagnostics'])
				? $_POST['monitorDiagnostics'] === '1'
				: false;
		}

		private function _getSendDiagnosticsWarningToEmailFromHttpPost() {
			return isset($_POST['sendDiagnosticsWarningToEmail'])
				? sanitize_email(strip_tags($_POST['sendDiagnosticsWarningToEmail']))
				: null;
		}

		private function _getCheckoutAutoRedirectSecondsFromHttpPost() {
			return isset($_POST['checkoutAutoRedirectSeconds'])
				? max(intval($_POST['checkoutAutoRedirectSeconds']), 0)
				: 0;
		}

		private function _isValidEmailAddress($email) {
			return empty($email) || !is_email($email);
		}

		private function _getBlogAdminEmailAddress() {
			return get_option('admin_email');
		}
	}
}