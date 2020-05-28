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
    class ApiServer  {
        private $_env;

        private $_report;

        public function __construct() {
            $this->_env = lvdwcmc_env();
            $this->_report = new TransactionReport();
        }

        public function listen() {
            register_rest_route('livepayments-mp-wc', 
                '/reports/transctions-status-counts', 
                array(
                    'methods' => 'GET',
                    'callback' => array($this, 'handleRequestTransactionsStatusCounts'),
            ));

            register_rest_route('livepayments-mp-wc', 
                '/reports/last-transaction-details', 
                array(
                    'methods' => 'GET',
                    'callback' => array($this, 'handleRequestLastTransactionDetails'),
            ));
        }

        public function handleRequestTransactionsStatusCounts(\WP_REST_Request $request) {
            $data = new \stdClass();
            $data->data = $this->_report->getTransactionsStatusCounts();
            $data->success = true;
            return new \WP_REST_Response($data, 200);
        }

        public function handleRequestLastTransactionDetails(\WP_REST_Request $request) {
            $data = new \stdClass();
            $data->data = $this->_report->getLastTransactionDetails();
            return new \WP_REST_Response($data, 200);
        }
    }
}