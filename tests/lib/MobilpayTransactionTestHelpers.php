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

use \LvdWcMc\MobilpayTransaction;

trait MobilpayTransactionTestHelpers {
    use GenericTestHelpers;

    protected function _assertMobilpayTransactionMatchesData(MobilpayTransaction $tx, array $data) {
        $this->assertEquals($data['tx_id'], 
            $tx->getId());
        $this->assertEquals($data['tx_transaction_id'], 
            $tx->getTransactionId());
        $this->assertEquals($data['tx_provider_transaction_id'], 
            $tx->getProviderTransactionId());
        $this->assertEquals($data['tx_amount'], 
            $tx->getAmount());
        $this->assertEquals($data['tx_processed_amount'], 
            $tx->getProcessedAmount());
        $this->assertEquals($data['tx_currency'], 
            $tx->getCurrency());
        $this->assertEquals($data['tx_error_code'], 
            $tx->getErrorCode());
        $this->assertEquals($data['tx_error_message'], 
            $tx->getErrorMessage());
        $this->assertEquals($data['tx_pan_masked'], 
            $tx->getPANMasked());
        $this->assertEquals($data['tx_timestamp_initiated'], 
            $tx->getTimestampInitiated());
        $this->assertEquals($data['tx_timestamp_last_updated'], 
            $tx->getTimestampLastUpdated());
    }

    protected function _mobilpayTransactionFromData($txData) {
        return new MobilpayTransaction($txData, $this->_getEnv());
    }

    protected function _generateMobilpayTransaction($override = array()) {
        return $this->_mobilpayTransactionFromData($this->_generateMobilpayTransactionData($override));
    }

    protected function _generateMobilpayTransactionData($override = array()) {
        $faker = self::_getFaker();
        return array_merge(array(
            'tx_id' => $faker->numberBetween(0, PHP_INT_MAX),
            'tx_order_id' => $faker->numberBetween(1, PHP_INT_MAX),
            'tx_order_user_id' => $faker->numberBetween(1, PHP_INT_MAX),
            'tx_provider' => 'mobilpay',
            'tx_transaction_id' => $faker->sha1,
            'tx_status' => $faker->randomElement($this->_getMobilpayTransactionStatuses()),
            'tx_amount' => $faker->randomFloat(4, 100, PHP_FLOAT_MAX),
            'tx_processed_amount' => $faker->randomFloat(4, 0, PHP_FLOAT_MAX),
            'tx_currency' => 'RON',
            'tx_timestamp_initiated' => date('Y-m-d H:i:s'),
            'tx_timestamp_last_updated' => date('Y-m-d H:i:s'),
            'tx_ip_address' => $faker->ipv4,
            'tx_provider_transaction_id' => $faker->sha1,
            'tx_error_code' => $faker->numberBetween(1, 100),
            'tx_error_message' => $faker->sentence(),
            'tx_pan_masked' => $faker->creditCardNumber
        ), $override);
    }

    protected function _generateMobilpayTransactionDataFromWcOrder(WC_Order $order, $status = null) {
        $override = array(
            'tx_order_id' => $order->get_id(),  
            'tx_amount' => $order->get_total(),
            'tx_currency' => $order->get_currency(),
            'tx_order_user_id' => $order->get_user_id(),
            'tx_processed_amount' => 0,
            'tx_ip_address' => $order->get_customer_ip_address(),
            'tx_transaction_id' => $order->get_order_key()
        );

        if ($status == MobilpayTransaction::STATUS_CONFIRMED 
            || $status == MobilpayTransaction::STATUS_CREDIT) {
            $override['tx_processed_amount'] = $order->get_total(); 
        }

        if (!empty($status)) {
            $override['tx_status'] = $status;
        }

        return $this->_generateMobilpayTransactionData($override);
    }

    protected function _getMobilpayTransactionStatuses() {
        return array(
            MobilpayTransaction::STATUS_NEW,
            MobilpayTransaction::STATUS_CANCELLED,
            MobilpayTransaction::STATUS_CONFIRMED,
            MobilpayTransaction::STATUS_CONFIRMED_PENDING,
            MobilpayTransaction::STATUS_CREDIT,
            MobilpayTransaction::STATUS_FAILED,
            MobilpayTransaction::STATUS_NEW,
            MobilpayTransaction::STATUS_PAID_PENDING
        );
    }
}