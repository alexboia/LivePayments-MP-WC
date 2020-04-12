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

use LvdWcMc\MobilpayTransaction;

class MobilpayTransactionTests extends WP_UnitTestCase {
    use GenericTestHelpers;
    use DbTestHelpers;

    private $_testMobilpayTransactions = array();

    public function setUp() {
        parent::setUp();
        $this->_installTestData();
    }

    public function tearDown() { 
        parent::tearDown();
        $this->_clearTestData();
    }

    public function test_canCheckStatus() {
        foreach ($this->_getStatusCheckTestsCases() as $status => $checker) {
            $tx = $this->_generateRandomMobilpayTransaction(array(
                'tx_status' => $status
            ));
            $checker($tx);
        }
    }

    public function test_canGetDataProperties() {
        for ($i = 0; $i < 10; $i ++) {
            $data = $this->_generateRandomMobilpayTransactionData();
            $tx = new MobilpayTransaction($data, $this->_getEnv());
            $this->_assertMobilpayTransactionMatchesData($tx, $data);
        }
    }

    public function test_canCheckIfAmountCompletelyProcessed() {
        $faker = self::_getFaker();

        for ($i = 0; $i < 10; $i ++) {
            //Processed amount is 0
            $amount = $faker->numberBetween(1, PHP_INT_MAX);
            $tx = $this->_generateRandomMobilpayTransaction(array(
                'tx_amount' => $amount,
                'tx_processed_amount' => 0
            ));
            $this->assertFalse($tx->isAmountCompletelyProcessed());

            //Processed amount is greater than 0 but lower than initial amount
            $tx = $this->_generateRandomMobilpayTransaction(array(
                'tx_amount' => $amount,
                'tx_processed_amount' => round($amount * $faker->randomFloat(2, 0.01, 0.99))
            ));
            $this->assertFalse($tx->isAmountCompletelyProcessed());

            //Processed amount is equal to the initial amount
            $tx = $this->_generateRandomMobilpayTransaction(array(
                'tx_amount' => $amount,
                'tx_processed_amount' => $amount
            ));
            $this->assertTrue($tx->isAmountCompletelyProcessed());
        }
    }

    public function test_canSave_existing() {
        foreach ($this->_testMobilpayTransactions as $txId => $txData) {
            $newTxData = $this->_generateRandomMobilpayTransactionData(array(
                'tx_id' => $txId
            ));

            $tx = new MobilpayTransaction($newTxData, $this->_getEnv());
            $tx->save();

            $this->_assertMobilpayTransactionDbDataMatchesExpectedData($tx->getId(), $newTxData);
        }
    }

    public function test_canSave_new() {
        for ($i = 0; $i < 10; $i ++) {
            $txData = $this->_generateRandomMobilpayTransactionData(array(
                'tx_id' => 0
            ));

            $tx = new MobilpayTransaction($txData, $this->_getEnv());
            $tx->save();

            $this->assertTrue($tx->getId() > 0);
            $this->_assertMobilpayTransactionDbDataMatchesExpectedData($tx->getId(), array_merge($txData, array(
                'tx_id' => $tx->getId()
            )));
        }
    }

    public function test_canSetFailed_validStatus() {
        $faker = self::_getFaker();
        $statuses = array(
            MobilpayTransaction::STATUS_NEW,
            MobilpayTransaction::STATUS_CONFIRMED_PENDING,
            MobilpayTransaction::STATUS_PAID_PENDING,
            MobilpayTransaction::STATUS_FAILED
        );

        foreach ($statuses as $status) {
            $txData = $this->_generateRandomMobilpayTransactionData(array(
                'tx_id' => 0,
                'tx_status' => $status,
                'tx_error_code' => null,
                'tx_error_message' => null
            ));

            $tx = new MobilpayTransaction($txData, $this->_getEnv());

            $providerTxId = $faker->sha1;
            $errorCode = $faker->numberBetween(1, 100);
            $errorMessage = $faker->sentence();

            $this->assertTrue($tx->canBeSetFailed());
            $tx->setFailed($providerTxId, $errorCode, $errorMessage);

            $this->assertEquals($providerTxId, $tx->getProviderTransactionId());
            $this->assertEquals(MobilpayTransaction::STATUS_FAILED, $tx->getStatus());
            $this->assertEquals($errorCode, $tx->getErrorCode());
            $this->assertEquals($errorMessage, $tx->getErrorMessage());

            $txId = $tx->getId();
            $this->_assertMobilpayTransactionDbDataMatchesExpectedData($txId, array(
                'tx_id' => $txId,
                'tx_provider_transaction_id' => $providerTxId,
                'tx_status' => MobilpayTransaction::STATUS_FAILED,
                'tx_error_code' => $errorCode,
                'tx_error_message' => $errorMessage
            ));
        }
    }

    public function test_trySetFailed_invalidStatus() {
        $faker = self::_getFaker();
        $statuses = array(
            MobilpayTransaction::STATUS_CONFIRMED,
            MobilpayTransaction::STATUS_CANCELLED,
            MobilpayTransaction::STATUS_CREDIT
        );

        foreach ($statuses as $status) {
            $txData = $this->_generateRandomMobilpayTransactionData(array(
                'tx_id' => $this->_generateMobilpayTransactionId(),
                'tx_status' => $status,
                'tx_error_code' => null,
                'tx_error_message' => null
            ));

            $tx = new MobilpayTransaction($txData, $this->_getEnv());

            $providerTxId = $faker->sha1;
            $errorCode = $faker->numberBetween(1, 100);
            $errorMessage = $faker->sentence();

            $this->assertFalse($tx->canBeSetFailed());
            $tx->setFailed($providerTxId, $errorCode, $errorMessage);

            $this->_assertMobilpayTransactionMatchesData($tx, $txData);
            $this->_assertMobilpayTransactionNotInDb($tx->getId());
        }
    }

    public function test_canSetPaymentPending_validStatus() {
        $faker = self::_getFaker();
        $statuses = array(
            MobilpayTransaction::STATUS_NEW,
            MobilpayTransaction::STATUS_PAID_PENDING
        );

        foreach ($statuses as $status) {
            $txData = $this->_generateRandomMobilpayTransactionData(array(
                'tx_id' => 0,
                'tx_status' => $status,
                'tx_error_code' => null,
                'tx_error_message' => null
            ));

            $tx = new MobilpayTransaction($txData, $this->_getEnv());
            $this->assertTrue($tx->canBeSetPaymentPending());

            $providerTxId = $faker->sha1;
            if ($status == MobilpayTransaction::STATUS_NEW) {
                $newStatus = MobilpayTransaction::STATUS_PAID_PENDING;
            } else {
                $newStatus = MobilpayTransaction::STATUS_CONFIRMED_PENDING;
            }

            $tx->setPaymentPending($newStatus, $providerTxId);
            $this->assertEquals($providerTxId, $tx->getProviderTransactionId());
            $this->assertEquals($newStatus, $tx->getStatus());            

            $txId = $tx->getId();
            $this->_assertMobilpayTransactionDbDataMatchesExpectedData($txId, array(
                'tx_id' => $txId,
                'tx_provider_transaction_id' => $providerTxId,
                'tx_status' => $newStatus
            ));
        }
    }

    public function test_trySetPaymentPending_invalidStatus() {
        $faker = self::_getFaker();
        $statuses = array(
            MobilpayTransaction::STATUS_CONFIRMED_PENDING,
            MobilpayTransaction::STATUS_CONFIRMED,
            MobilpayTransaction::STATUS_FAILED,
            MobilpayTransaction::STATUS_CREDIT,
            MobilpayTransaction::STATUS_CANCELLED
        );

        foreach ($statuses as $status) {
            $txData = $this->_generateRandomMobilpayTransactionData(array(
                'tx_id' => $this->_generateMobilpayTransactionId(),
                'tx_status' => $status,
                'tx_error_code' => null,
                'tx_error_message' => null
            ));

            $tx = new MobilpayTransaction($txData, $this->_getEnv());
            $this->assertFalse($tx->canBeSetPaymentPending());

            $providerTxId = $faker->sha1;
            $newStatus = $faker->randomElement(array(
                MobilpayTransaction::STATUS_PAID_PENDING,
                MobilpayTransaction::STATUS_CONFIRMED_PENDING
            ));

            $tx->setPaymentPending($newStatus, $providerTxId);

            $this->_assertMobilpayTransactionMatchesData($tx, $txData);
            $this->_assertMobilpayTransactionNotInDb($tx->getId());
        }
    }

    private function _assertMobilpayTransactionNotInDb($txId) {
        $db = $this->_getDb();
        $db->where('tx_id', $txId);
        $dbData = $db->getOne($this->_getEnv()->getPaymentTransactionsTableName());
        $this->assertEmpty($dbData);
    }

    private function _assertMobilpayTransactionDbDataMatchesExpectedData($txId, array $data) {
        $db = $this->_getDb();
        $db->where('tx_id', $txId);
        $dbData = $db->getOne($this->_getEnv()->getPaymentTransactionsTableName());

        $this->assertNotEmpty($dbData);
        foreach ($data as $key => $value) {
            $this->assertArrayHasKey($key, $dbData);
            $this->assertEquals($value, $dbData[$key]);
        }
    }

    private function _assertMobilpayTransactionMatchesData(MobilpayTransaction $tx, array $data) {
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

    private function _installTestData() {
        $db = $this->_getDb();
        $table = $this->_getEnv()->getPaymentTransactionsTableName();

        $db->startTransaction();
        
        for ($i = 0; $i < 10; $i ++) {
            $txData = $this->_generateRandomMobilpayTransactionData(array(
                'tx_id' => 0
            ));

            $txId = $db->insert($table, $txData);
            $this->_testMobilpayTransactions[$txId] = $txData;
        }

        $db->commit();
    }

    private function _clearTestData() {
        $this->_truncateTables($this->_getDb(), $this->_getEnv()->getPaymentTransactionsTableName());
        $this->_testMobilpayTransactions = array();
    }

    private function _getStatusCheckTestsCases() {
        return array(
            MobilpayTransaction::STATUS_NEW => function($tx) { 
                $this->assertTrue($tx->isNew()); 
            },
            MobilpayTransaction::STATUS_FAILED => function($tx) { 
                $this->assertTrue($tx->isFailed()); 
            },
            MobilpayTransaction::STATUS_PAID_PENDING => function($tx) { 
                $this->assertTrue($tx->isPaymentPending()); 
            },
            MobilpayTransaction::STATUS_CONFIRMED_PENDING => function($tx) { 
                $this->assertTrue($tx->isPaymentPending()); 
            },
            MobilpayTransaction::STATUS_CONFIRMED => function($tx) { 
                $this->assertTrue($tx->isConfirmed()); 
            },
            MobilpayTransaction::STATUS_CREDIT => function($tx) { 
                $this->assertTrue($tx->isCredited()); 
            }
        );
    }

    private function _generateRandomMobilpayTransaction($override = array()) {
        return new MobilpayTransaction($this->_generateRandomMobilpayTransactionData($override), $this->_getEnv());
    }

    private function _generateRandomMobilpayTransactionData($override = array()) {
        $faker = self::_getFaker();
        return array_merge(array(
            'tx_id' => $faker->numerify(0, PHP_INT_MAX),
            'tx_order_id' => $faker->numberBetween(1, PHP_INT_MAX),
            'tx_order_user_id' => $faker->numberBetween(1, PHP_INT_MAX),
            'tx_provider' => 'mobilpay',
            'tx_transaction_id' => $faker->sha1,
            'tx_status' => $faker->randomElement($this->_getMobilpayTransactionStatuses()),
            'tx_amount' => $faker->numberBetween(1, PHP_INT_MAX),
            'tx_processed_amount' => $faker->numberBetween(0, PHP_INT_MAX),
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

    private function _getMobilpayTransactionStatuses() {
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

    private function _generateMobilpayTransactionId($excludeAdditionalIds = array()) {
        $excludeIds = array_keys($this->_testMobilpayTransactions);
        if (!empty($excludeAdditionalIds) && is_array($excludeAdditionalIds)) {
            $excludeIds = array_merge($excludeAdditionalIds);
        }

        $faker = self::_getFaker();
        
        $max = !empty($excludeIds) 
            ? max($excludeIds) 
            : 0;

        $transactionId = $faker->numberBetween($max + 1, $max + 1000);
        return $transactionId;
    }
}