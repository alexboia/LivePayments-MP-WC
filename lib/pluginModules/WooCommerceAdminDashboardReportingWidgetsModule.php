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

    class WooCommerceAdminDashboardReportingWidgetsModule extends PluginModule {
        public function __construct(Plugin $plugin) {
            parent::__construct($plugin);
        }

        public function load() {
            $this->_registerWebPageAssets();
        }

        private function _registerWebPageAssets() {
            add_action('admin_enqueue_scripts', 
                array($this, 'onAdminEnqueueStylesForWooAdminDashboard'), 0);
            add_action('admin_enqueue_scripts', 
                array($this, 'onAdminEnqueueScriptsForWooAdminDashboard'), 0);
        }

        public function onAdminEnqueueStylesForWooAdminDashboard() {
            if ($this->_env->isViewingWooAdminDashboard()) {
                $this->_mediaIncludes
                    ->includeStyleDashboard();
                $this->_mediaIncludes
                    ->includeStyleAdminTransactionDetails();
            }
        }

        public function onAdminEnqueueScriptsForWooAdminDashboard() {
            if ($this->_env->isViewingWooAdminDashboard()) {
                $this->_mediaIncludes
                    ->includeScriptWooAdminDashboardSections(
                        $this->_plugin->getWooAdminDashboardSectionsScriptTranslations()
                    );
            }
        }
    }
}