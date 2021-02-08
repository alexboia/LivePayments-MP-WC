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
    class MobilpayTransaction {
        const STATUS_NEW = 'new';

        const STATUS_CONFIRMED = 'confirmed';

        const STATUS_CONFIRMED_PENDING = 'confirmed_pending';

        const STATUS_PAID_PENDING = 'paid_pending';

        const STATUS_CANCELLED = 'canceled';

        const STATUS_CREDIT = 'credit';

        const STATUS_FAILED = 'failed';

        private static $_labelsForCodes;

        /**
         * @var \LvdWcMc\Env Reference to the environment object
         */
        private $_env;

        private $_id;

        private $_provider;

        private $_orderId;

        private $_orderUserId;

        private $_transactionId;

        private $_providerTransactionId;

        private $_status;

        private $_errorCode;

        private $_errorMessage;

        private $_amount;

        private $_processedAmount;

        private $_currency;

        private $_panMasked;

        private $_timestampInitiated;

        private $_timestampLastUpdated;

        private $_ipAddress;

        public function __construct(array $data, Env $env) {
            $this->_env = $env;

            $this->_id = isset($data['tx_id']) 
                ? intval(($data['tx_id'])) 
                : 0;

            $this->_orderId = $data['tx_order_id'];
            $this->_orderUserId = $data['tx_order_user_id'];
            $this->_provider = $data['tx_provider'];
            $this->_transactionId = $data['tx_transaction_id'];
            $this->_providerTransactionId = $data['tx_provider_transaction_id'];
            $this->_status = $data['tx_status'];
            
            $this->_errorCode = isset($data['tx_error_code']) 
                ? $data['tx_error_code'] 
                : null;
            $this->_errorMessage = isset($data['tx_error_message']) 
                ? $data['tx_error_message']
                : null;
            
            $this->_amount = $data['tx_amount'];
            $this->_processedAmount = $data['tx_processed_amount'];
            $this->_currency = $data['tx_currency'];
            $this->_panMasked = $data['tx_pan_masked'];
            $this->_timestampInitiated = $data['tx_timestamp_initiated'];
            $this->_timestampLastUpdated = $data['tx_timestamp_last_updated'];
            $this->_ipAddress = $data['tx_ip_address'];
        }
        
        private function _getData() {
            return array(
                'tx_id' => $this->_id,
                'tx_order_id' => $this->_orderId,
                'tx_order_user_id' => $this->_orderUserId,
                'tx_provider' => $this->_provider,
                'tx_transaction_id' => $this->_transactionId,
                'tx_provider_transaction_id' => $this->_providerTransactionId,
                'tx_status' => $this->_status,
                'tx_error_code' => $this->_errorCode,
                'tx_error_message' => $this->_errorMessage,
                'tx_amount' => $this->_amount,
                'tx_processed_amount' => $this->_processedAmount,
                'tx_currency' => $this->_currency,
                'tx_pan_masked' => $this->_panMasked,
                'tx_timestamp_initiated' => $this->_timestampInitiated,
                'tx_timestamp_last_updated' => $this->_timestampLastUpdated,
                'tx_ip_address' => $this->_ipAddress
            );
        }

        private function _setLastUpdated() {
            $this->_timestampLastUpdated = date('Y-m-d H:i:s');
        }

        public static function getStatusLabel($status) {
            if (self::$_labelsForCodes === null) {
                self::$_labelsForCodes = array(
                    MobilpayTransaction::STATUS_CANCELLED 
                        => __('Cancelled', 'livepayments-mp-wc'),
                    MobilpayTransaction::STATUS_CONFIRMED 
                        => __('Confirmed. Payment successful', 'livepayments-mp-wc'),
                    MobilpayTransaction::STATUS_CONFIRMED_PENDING 
                        => __('Pending confirmation', 'livepayments-mp-wc'),
                    MobilpayTransaction::STATUS_CREDIT 
                        => __('Credited', 'livepayments-mp-wc'),
                    MobilpayTransaction::STATUS_FAILED 
                        => __('Failed', 'livepayments-mp-wc'),
                    MobilpayTransaction::STATUS_NEW 
                        => __('Started', 'livepayments-mp-wc'),
                    MobilpayTransaction::STATUS_PAID_PENDING 
                        => __('Pending payment', 'livepayments-mp-wc')
                );
            }

            return isset(self::$_labelsForCodes[$status]) 
                ? self::$_labelsForCodes[$status] 
                : '-';
        }

        public function save() {
            $db = $this->_env->getDb();
            if ($this->_id > 0) {
                $db->where('tx_id', $this->_id);
                $db->update($this->_env->getPaymentTransactionsTableName(), $this->_getData());
            } else {
                $this->_id = $db->insert($this->_env->getPaymentTransactionsTableName(), $this->_getData());
            }
        }

        public function isNew() {
            return $this->_status == self::STATUS_NEW;
        }

        public function isAmountCompletelyProcessed() {
            return abs($this->_amount - $this->_processedAmount) <= 1e-5;   
        }

        public function isFailed() {
            return $this->_status == self::STATUS_FAILED;
        }

        public function canBeSetFailed() {
            return $this->_status == self::STATUS_NEW
                || $this->_status == self::STATUS_CONFIRMED_PENDING
                || $this->_status == self::STATUS_PAID_PENDING
                || $this->_status == self::STATUS_FAILED;
        }

        public function setFailed($transactionId, $errorCode, $errorMessage) {
            if ($this->canBeSetFailed()) {
                $this->_status = self::STATUS_FAILED;
                $this->_providerTransactionId = $transactionId;
                $this->_errorCode = $errorCode;
                $this->_errorMessage = $errorMessage;
                $this->_setLastUpdated();
                $this->save();
            }
        }

        public function isPaymentPending() {
            return $this->_status == self::STATUS_PAID_PENDING 
                || $this->_status == self::STATUS_CONFIRMED_PENDING;
        }

        public function canBeSetPaymentPending() {
            return $this->_status == self::STATUS_NEW 
                || $this->_status == self::STATUS_PAID_PENDING;
        }

        public function setPaymentPending($action, $transactionId) {
            if ($this->canBeSetPaymentPending() 
                && ($action == self::STATUS_PAID_PENDING || $action == self::STATUS_CONFIRMED_PENDING)) {
                $this->_status = $action;
                $this->_providerTransactionId = $transactionId;
                $this->_setLastUpdated();
                $this->save();
            }
        }

        public function isConfirmed() {
            return $this->_status == self::STATUS_CONFIRMED;
        }

        public function isPartiallyConfirmed() {
            return $this->isConfirmed() && !$this->isAmountCompletelyProcessed();
        }

        public function canBeSetConfirmed() {
            return $this->_status == self::STATUS_NEW 
                || $this->_status == self::STATUS_PAID_PENDING
                || $this->_status == self::STATUS_CONFIRMED_PENDING
                || $this->_status == self::STATUS_FAILED
                || $this->isPartiallyConfirmed();
        }

        public function setConfirmed($transactionId, $processedAmount, $panMasked) {
            if ($this->canBeSetConfirmed()) {
                if ($this->_status != self::STATUS_CONFIRMED) {
                    $this->_status = self::STATUS_CONFIRMED;
                    $this->_processedAmount = 0;
                }

                $this->_errorCode = null;
                $this->_errorMessage = null;

                $this->_panMasked = $panMasked;
                $this->_providerTransactionId = $transactionId;

                $this->_increaseProcessedAmount($processedAmount);
               
                $this->_setLastUpdated();
                $this->save();
            }
        }

        public function isCancelled() {
            return $this->_status == self::STATUS_CANCELLED;
        }

        public function canBeSetCancelled() {
            return $this->_status == self::STATUS_NEW 
                || $this->_status == self::STATUS_PAID_PENDING
                || $this->_status == self::STATUS_CONFIRMED_PENDING;
        }

        public function setCancelled($transactionId) {
            if ($this->canBeSetCancelled()) {
                $this->_status = self::STATUS_CANCELLED;
                $this->_providerTransactionId = $transactionId;
                $this->_processedAmount = 0;
                $this->_setLastUpdated();
                $this->save();
            }
        }

        public function isCredited() {
            return $this->_status == self::STATUS_CREDIT;
        }

        public function isPartiallyCredited() {
            return $this->isCredited() && !$this->isAmountCompletelyProcessed();
        }

        public function canBeSetCredited() {
            return $this->_status == self::STATUS_NEW
                || $this->_status == self::STATUS_PAID_PENDING
                || $this->_status == self::STATUS_CONFIRMED_PENDING
                || $this->_status == self::STATUS_CONFIRMED
                || $this->isPartiallyCredited();
        }

        public function setCredited($transactionId, $processedAmount, $panMasked) {
            if ($this->canBeSetCredited()) {
                if ($this->_status != self::STATUS_CREDIT) {
                    $this->_status = self::STATUS_CREDIT;
                    $this->_processedAmount = 0;
                }

                $this->_panMasked = $panMasked;
                $this->_providerTransactionId = $transactionId;

                $this->_increaseProcessedAmount($processedAmount);

                $this->_setLastUpdated();
                $this->save();
            }
        }

        private function _increaseProcessedAmount($processedAmount) {
            $this->_processedAmount = min($this->_amount, $this->_processedAmount + $processedAmount);
        }

        public function getId() {
            return $this->_id;
        }

        public function getOrderId() {
            return $this->_orderId;
        }

        public function getOrderUserId() {
            return $this->_orderUserId;
        }

        public function getTransactionId() {
            return $this->_transactionId;
        }

        public function getProviderTransactionId() {
            return $this->_providerTransactionId;
        }

        public function getStatus() {
            return $this->_status;
        }

        public function getErrorCode() {
            return $this->_errorCode;
        }

        public function getErrorMessage() {
            return $this->_errorMessage;
        }

        public function getAmount() {
            return $this->_amount;
        }

        public function getProcessedAmount() {
            return $this->_processedAmount;
        }

        public function getCurrency() {
            return $this->_currency;
        }

        public function getPANMasked() {
            return $this->_panMasked;
        }

        public function getTimestampInitiated() {
            return $this->_timestampInitiated;
        }

        public function getTimestampLastUpdated() {
            return $this->_timestampLastUpdated;
        }

        public function getIpAddress() {
            return $this->_ipAddress;
        }
    }
}