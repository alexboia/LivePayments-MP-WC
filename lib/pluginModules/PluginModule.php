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

    use LvdWcMc\Env;
    use LvdWcMc\Plugin;
    use LvdWcMc\PluginViewEngine;
    use LvdWcMc\WordPressAdminAjaxAction;

    abstract class PluginModule {
        /**
         * @var \LvdWcMc\Plugin
         */
        protected $_plugin;

        /**
         * @var \LvdWcMc\Env
         */
        protected $_env;

        /**
         * @var \LvdWcMc\PluginViewEngine
         */
        protected $_viewEngine;

        /**
         * @var \LvdWcMc\MediaIncludes
         */
        protected $_mediaIncludes;

        public function __construct(Plugin $plugin) {
            $this->_plugin = $plugin;
            $this->_env = $plugin->getEnv();
            $this->_viewEngine = $plugin->getViewEngine();
            $this->_mediaIncludes = $plugin->getMediaIncludes();
        }

        abstract public function load();

        protected function _createAdminAjaxAction($actionCode, 
            $callback, 
            $requiresAuthentication = true, 
            $requiredCapability = null) {

            return (new WordPressAdminAjaxAction($actionCode, $callback))
                ->setRequiresAuthentication($requiresAuthentication)
                ->setRequiredCapability($requiredCapability);
        }

        protected function _currentUserCanManageWooCommerce() {
            return current_user_can('manage_woocommerce');
        }

        protected function _currentUserCanManageOptions() {
            return current_user_can('manage_options');
        }

        protected function _getAjaxBaseUrl() {
            return $this->_env->getAjaxBaseUrl();
        }

        protected function _getSettings() {
            return lvdwcmc_get_settings();
        }

        protected function _getDb() {
            return $this->_env->getDb();
        }
    }
}