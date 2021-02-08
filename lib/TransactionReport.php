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
    class TransactionReport {
        private $_env;

        private $_transactionFactory;

        private $_formatters;

        public function __construct() {
            $this->_env = lvdwcmc_get_env();
            $this->_transactionFactory = new MobilpayTransactionFactory();
            $this->_formatters = new Formatters();
        }

        public function getTransactionsStatusCounts() {
            $reportData = array();
            $db = $this->_env->getDb();
            
            $rawStatusData = $db
                ->groupBy('tx_status')
                ->get($this->_env->getPaymentTransactionsTableName(), null, 'tx_status, COUNT(tx_status) tx_status_count');
            
            foreach ($this->_getEmptyTransactionStatusData() as $status => $count) {
                $reportData[$status] = array(
                    'label' => $this->_getTransactionStatusLabel($status),
                    'count' => $count
                );
            }

            if (!empty($rawStatusData)) {
                foreach ($rawStatusData as $row) {
                    $reportData[$row['tx_status']] = array(
                        'label' => $this->_getTransactionStatusLabel($row['tx_status']),
                        'count' => intval($row['tx_status_count'])
                    );
                }
            }

            return $reportData;
        }

        public function getLastTransactionDetails() {
            $db = $this->_env->getDb();

            $lastTransactionData = $db
                ->orderBy('tx_timestamp_initiated', 'DESC')
                ->getOne ($this->_env->getPaymentTransactionsTableName());
            
            if (!empty($lastTransactionData)) {
                $transaction = $this->_transactionFactory->existingFromRawTransactionData($lastTransactionData);
                return $this->_formatters->getDisplayableTransactionItemsList($transaction);
            } else {
                return array();
            }
        }

        private function _getEmptyTransactionStatusData() {
            return array(
                MobilpayTransaction::STATUS_NEW => 0,
                MobilpayTransaction::STATUS_CONFIRMED_PENDING => 0,
                MobilpayTransaction::STATUS_PAID_PENDING => 0,
                MobilpayTransaction::STATUS_FAILED => 0,
                MobilpayTransaction::STATUS_CREDIT => 0,
                MobilpayTransaction::STATUS_CONFIRMED => 0,
                MobilpayTransaction::STATUS_CANCELLED => 0
            );
        }

        private function _getTransactionStatusLabel($status) {
            return MobilpayTransaction::getStatusLabel($status);
        }
    }
}