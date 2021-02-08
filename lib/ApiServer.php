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
    class ApiServer  {
        use LoggingExtensions;
        
        /**
         * @var \LvdWcMc\Env Reference to the environment object
         */
        private $_env;

        /**
         * @var \LvdWcMc\TransactionReport Reference to the transaction report manager
         */
        private $_report;

        /**
         * @var WC_Logger The logger instance used by this API server
         */
        private $_logger = null;

        public function __construct() {
            $this->_env = lvdwcmc_get_env();
            $this->_report = new TransactionReport();
        }

        public function listen() {
            register_rest_route('livepayments-mp-wc', 
                '/reports/transctions-status-counts', 
                array(
                    'methods' => 'GET',
                    'callback' => array($this, 'handleRequestTransactionsStatusCounts'),
                    'permission_callback' => function() {
                        return $this->_currentUserCanManageWooCommerce();
                    }
            ));

            register_rest_route('livepayments-mp-wc', 
                '/reports/last-transaction-details', 
                array(
                    'methods' => 'GET',
                    'callback' => array($this, 'handleRequestLastTransactionDetails'),
                    'permission_callback' => function() {
                        return $this->_currentUserCanManageWooCommerce();
                    }
            ));
        }

        public function handleRequestTransactionsStatusCounts(\WP_REST_Request $request) {
            if (!$this->_currentUserCanManageWooCommerce()) {
                return new \WP_REST_Response(null, 403);
            }

            $data = new \stdClass();
            $this->logDebug('Begin processing transactions status counts report', 
                $this->_getLoggingContext());

            try {
                $data->data = $this->_report->getTransactionsStatusCounts();
                $data->success = true;
            } catch (\Exception $exc) {
                $data->data = null;
                $data->success = false;
                $this->logException('Error computing transaction status counts report', 
                    $exc, 
                    $this->_getLoggingContext());
            }

            return new \WP_REST_Response($data, 200);
        }

        public function handleRequestLastTransactionDetails(\WP_REST_Request $request) {
            if (!$this->_currentUserCanManageWooCommerce()) {
                return new \WP_REST_Response(null, 403);
            }

            $data = new \stdClass();
            $this->logDebug('Begin processing last transaction details report', 
                $this->_getLoggingContext());
            
            try {
                $data->data = $this->_report->getLastTransactionDetails();
                $data->success = true;
            } catch (\Exception $exc) {
                $data->data = null;
                $data->success = false;
                $this->logException('Error computing last transaction details report', 
                    $exc, 
                    $this->_getLoggingContext());
            }

            return new \WP_REST_Response($data, 200);
        }

        private function _currentUserCanManageWooCommerce() {
            return current_user_can('manage_woocommerce');
        }

        public function getLogger() {
            if ($this->_logger === null) {
                $this->_logger = wc_get_logger();
            }
            return $this->_logger;
        }

        private function _getLoggingContext() {
            return array(
                'source' => MobilpayCreditCardGateway::GATEWAY_ID,
                'location' => 'api-server'
            );
        }
    }
}