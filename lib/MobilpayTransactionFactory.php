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
    class MobilpayTransactionFactory {
        /**
         * @var \LvdWcMc\Env Reference to the environment object
         */
        private $_env;

        public function __construct() {
            $this->_env = lvdwcmc_get_env();
        }

        private function _getTransactionData(\WC_Order $order) {
            return array(
                'tx_order_id' => $order->get_id(),
                'tx_order_user_id' => $order->get_user_id(),
                'tx_provider' => 'mobilpay',
                'tx_transaction_id' => $order->get_order_key(),
                'tx_status' => MobilpayTransaction::STATUS_NEW,
                'tx_amount' => $order->get_total(),
                'tx_processed_amount' => 0,
                'tx_currency' => $order->get_currency(),
                'tx_timestamp_initiated' => date('Y-m-d H:i:s'),
                'tx_timestamp_last_updated' => date('Y-m-d H:i:s'),
                'tx_ip_address' => $order->get_customer_ip_address(),
                'tx_provider_transaction_id' => null,
                'tx_error_code' => null,
                'tx_error_message' => null,
                'tx_pan_masked' => null
            );
        }

        private function _fromOrder($order, $createIfNotExists) {
            if (!($order instanceof \WC_Order)) {
                if (is_numeric($order)) {
                    $order = wc_get_order($order);
                } else {
                    throw new \InvalidArgumentException('Invalid order provided');
                }
            }
            
            if ($order instanceof \WC_Order) {
                $db = $this->_getDb();
                $db->where('tx_order_id', $order->get_id());

                $data = $db->getOne($this->_env->getPaymentTransactionsTableName());
                if (!is_array($data)) {   
                    if ($createIfNotExists) {
                        /**
                         * Prepare order transaction data - maybe some plugin wants to alter it.
                         * 
                         * @hook lvdwcmc_initial_transaction_data_from_order
                         * 
                         * @param array $transactionData The current transaction data
                         * @param \WC_Order $order The order for which the transaction data must be computed
                         * 
                         * @return array The actual transaction data, as returned by the filters
                         */
                        $data = apply_filters('lvdwcmc_initial_transaction_data_from_order', 
                            $this->_getTransactionData($order), 
                            $order);

                        $internalTxId = $db->insert($this->_env->getPaymentTransactionsTableName(), $data);
                        if (is_numeric($internalTxId) && $internalTxId > 0) {
                            $data = array_merge($data, array(
                                'tx_id' => $internalTxId
                            ));
                        } else {
                            $data = null;
                        }
                    } else {
                        $data = null;
                    }
                }
            } else {
                $data = null;
            }

            return $data != null 
                ? new MobilpayTransaction($data, $this->_env)
                : null;
        }

        /**
         * @return \LvdWcMc\MobilpayTransaction The corresponding transaction or null if not found
         */
        public function newFromOrder($order) {
            if (empty($order)) {
                return null;
            }
            return $this->_fromOrder($order, true);
        }

        /**
         * @return \LvdWcMc\MobilpayTransaction The corresponding transaction or null if not found
         */
        public function existingFromOrder($order) {
            if (empty($order)) {
                return null;
            }
            return $this->_fromOrder($order, false);
        }

        /**
         * @return \LvdWcMc\MobilpayTransaction The corresponding transaction or null if not found
         */
        public function fromTransactionId($transactionId) {
            if (empty($transactionId)) {
                return null;
            }

            $db = $this->_getDb();
            $db->where('tx_id', $transactionId);

            $data = $db->getOne($this->_env->getPaymentTransactionsTableName());

            return is_array($data) 
                ? new MobilpayTransaction($data, $this->_env) 
                : null;
        }

        /**
         * @param array $data The data from which to create the transaction instance
         * @return \LvdWcMc\MobilpayTransaction The corresponding transaction or null if data is empty
         */
        public function existingFromRawTransactionData(array $data) {
            return !empty($data) 
                ? new MobilpayTransaction($data, $this->_env) 
                : null;
        }

        private function _getDb() {
            return $this->_env->getDb();
        }
    }
 }