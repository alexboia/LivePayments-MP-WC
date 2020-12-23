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

namespace LvdWcMc\PluginModules {

    use LvdWcMc\Plugin;
    use LvdWcMc\PluginMenu;

    class PluginSettingsModule extends PluginModule {
        const ACTION_SAVE_SETTINGS = 'lvdwcmc_save_settings';

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
            $this->_registerMenuHook();

            $this->_saveSettingsAction
                ->register();
        }

        private function _registerMenuHook() {
            add_action('admin_menu', array($this, 'onAddAdminMenuEntries'));
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
            $data = new \stdClass();
            $data->settings = new \stdClass();
            $data->settings->monitorGatewayStatus = true;

            echo $this->_viewEngine->renderView('lvdwcmc-plugin-settings.php', $data);
        }

        public function saveSettings() {
            
        }
    }
}