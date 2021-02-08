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
    class Formatters {
        public function getDisplayableTransactionItemsList(MobilpayTransaction $transaction) {
            $items = array();
            $displayableData = $this->getDisplayableTransactionDetails($transaction);
            $items[] = array(
                'label' => esc_html__('Transaction Id', 'livepayments-mp-wc'),
                'id' => 'providerTransactionId',
                'value' => $displayableData->providerTransactionId
            );
            $items[] = array(
                'label' => esc_html__('Transaction status', 'livepayments-mp-wc'),
                'id' => 'status',
                'value' => $displayableData->status
            );
            $items[] = array(
                'label' => esc_html__('Card number', 'livepayments-mp-wc'),
                'id' => 'panMasked',
                'value' => $displayableData->panMasked
            );
            $items[] = array(
                'label' => esc_html__('Original amount', 'livepayments-mp-wc'),
                'id' => 'amount',
                'value' => $displayableData->amount . ' ' . $displayableData->currency
            );
            $items[] = array(
                'label' => esc_html__('Actually processed amount', 'livepayments-mp-wc'),
                'id' => 'processedAmount',
                'value' => $displayableData->processedAmount . ' ' . $displayableData->currency
            );
            $items[] = array(
                'label' => esc_html__('Date initiated', 'livepayments-mp-wc'),
                'id' => 'timestampInitiated',
                'value' => $displayableData->timestampInitiated
            );
            $items[] = array(
                'label' => esc_html__('Date of last activity', 'livepayments-mp-wc'),
                'id' => 'timestampLastUpdated',
                'value' => $displayableData->timestampLastUpdated
            );

            if (!empty($displayableData->errorCode)) {
                $items[] = array(
                    'label' => esc_html__('Transaction error code', 'livepayments-mp-wc'),
                    'id' => 'errorCode',
                    'value' => $displayableData->errorCode
                );
                $items[] = array(
                    'label' => esc_html__('Transaction error message', 'livepayments-mp-wc'),
                    'id' => 'errorMessage',
                    'value' => $displayableData->errorMessage
                );
            }
            
            if (!empty($displayableData->clientIpAddress)) {
                $items[] = array(
                    'label' => esc_html__('Client IP Address', 'livepayments-mp-wc'),
                    'id' => 'clientIpAddress',
                    'value' => $displayableData->clientIpAddress
                );
            }

            return $items;
        }

        public function getDisplayableTransactionDetails(MobilpayTransaction $transaction) {
            $data = new \stdClass();
            $data->providerTransactionId = $transaction->getProviderTransactionId();
            $data->status = $this->_getTransactionStatusLabel($transaction->getStatus());
            $data->panMasked = $transaction->getPANMasked();
            
            $data->amount = $this->formatTransactionAmount($transaction->getAmount());
            $data->processedAmount = $this->formatTransactionAmount($transaction->getProcessedAmount());
            $data->currency = $transaction->getCurrency();
            
            $data->timestampInitiated = $this->formatTransactionTimestamp($transaction->getTimestampInitiated());
            $data->timestampLastUpdated = $this->formatTransactionTimestamp($transaction->getTimestampLastUpdated());
            $data->errorCode = $transaction->getErrorCode();
            $data->errorMessage = $transaction->getErrorMessage();

            if ($this->_canManageWooCommerce()) {
                $data->clientIpAddress = $transaction->getIpAddress();
            } else {
                $data->clientIpAddress = null;
            }

            /**
             * Filters the transaction details view model, used both 
             *  in the backend and in the frontend to display transaction information.
             * Properties may be overwritten.
             * 
             * @hook lvdwcmc_get_displayable_transaction_details
             * 
             * @param \stdClass $data The initial view model, as provided by LivePayments-MP-WC
             * @param \LvdWcMc\MobilpayTransaction $transaction The source transaction
             * @return \stdClass The actual view model, as returned by the registered filters
             */
            return apply_filters('lvdwcmc_get_displayable_transaction_details', 
                $data, 
                $transaction);
        }

        public function formatTransactionAmount($amount) {
            $amountFormat = $this->_getAmountFormat();
            return number_format($amount, 
                $amountFormat['decimals'], 
                $amountFormat['decimalSeparator'], 
                $amountFormat['thousandSeparator']);
        }

        public function formatTransactionTimestamp($strTimestamp) {
            $timestamp = date_create_from_format('Y-m-d H:i:s', $strTimestamp);
            return !empty($timestamp) 
                ? $timestamp->format($this->_getDateTimeFormat()) 
                : null;
        }

        private function _getTransactionStatusLabel($status) {
            return MobilpayTransaction::getStatusLabel($status);
        }

        private function _canManageWooCommerce() {
            return current_user_can('manage_woocommerce');
        }

        private function _getAmountFormat() {
            return lvdwcmc_get_amount_format();
        }

        private function _getDateTimeFormat() {
            return lvdwcmc_get_datetime_format();
        }
    }
}