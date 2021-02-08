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

    class WordPressDashboardReportingWidgetsModule extends PluginModule {
        /**
         * @var \LvdWcMc\TransactionReport
         */
        private $_report;

        public function __construct(Plugin $plugin) {
            parent::__construct($plugin);
            $this->_report = $plugin
                ->getReport();
        }

        public function load() {
            $this->_registerDashboardWidgets();
            $this->_registerWebPageAssets();
        }

        private function _registerWebPageAssets() {
            add_action('admin_enqueue_scripts', 
                array($this, 'onAdminEnqueueStyles'), 9998);
        }

        public function onAdminEnqueueStyles() {
            if ($this->_env->isViewingWpDashboard()) {
                $this->_mediaIncludes->includeStyleDashboard();
            }
        }

        private function _registerDashboardWidgets() {
            add_action('wp_dashboard_setup', 
                array($this, 'onDashboardWidgetsSetup'));
        }

        public function onDashboardWidgetsSetup() {           
            if ($this->_shouldAddDashboardReportingWidget()) {
                $this->_registerDashboardReportingWidget();
            }
        }

        private function _shouldAddDashboardReportingWidget() {
            /**
             * Filters whether or not to add the transactions 
             *  status widget to the WP admin dashboard
             * 
             * @hook lvdwcmc_add_status_dashboard_widget
             * 
             * @param boolean $addDashboardWidget Whether to add the widget or not, initially provided by LivePayments-MP-WC
             * @return boolean Whether to add the widget or not, as returned by the registered filters
             */
            return apply_filters('lvdwcmc_add_status_dashboard_widget', 
                $this->_currentUserCanManageWooCommerce());
        }

        private function _registerDashboardReportingWidget() {
            wp_add_dashboard_widget('lvdwcmc-transactions-status', 
                __('LivePayments Card Transaction Status', 'livepayments-mp-wc'), 
                array($this, 'renderTransactionsStatusWidget'), 
                    null,
                    null);
        }

        public function renderTransactionsStatusWidget() {
            $data = new \stdClass();
            $data->status = $this->_report->getTransactionsStatusCounts();
            $data->success = true;

            /**
             * Filters the view model of the admin dashboard transaction status widget
             *  thus allowing additional data to be added to it.
             * 
             * @hook lvdwcmc_get_dashboard_widget_status_data
             * 
             * @param \stdClass $data The view model, initially provided by LivePayments-MP-WC
             * @return \stdClass The view model, as returned by the registered filters
             */
            $data = apply_filters('lvdwcmc_get_dashboard_widget_status_data', 
                $data);

            echo $this->_viewEngine->renderView('lvdwcmc-dashboard-transactions-status.php', 
                $data);
        }
    }
}