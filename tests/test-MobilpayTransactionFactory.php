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

use \LvdWcMc\MobilpayTransaction;
use \LvdWcMc\MobilpayTransactionFactory;

class MobilpayTransactionFactoryTests extends WP_UnitTestCase {
    use MobilpayTransactionTestHelpers;
    use DbTestHelpers;

    private $_maxTransactionId = 0;

    private $_testWcOrdersWTransaction = array();

    private $_testWcOrdersWoTransaction = array();

    public function setUp() {
        parent::setUp();
        $this->_dontReportNotices();
        $this->_initTestData();
        $this->_reportAll();
    }

    public function tearDown() {
        parent::tearDown();
        $this->_cleanupTestData();
    }

    public function test_canCreateNewFromOrder_validOrder_fromId() {
        $txFactory = new MobilpayTransactionFactory();
        foreach ($this->_testWcOrdersWoTransaction as $orderId => $order) {
            $tx = $txFactory->newFromOrder($orderId);
            $this->_assertNewTransactionMatchesOrder($tx, $order, MobilpayTransaction::STATUS_NEW);
        }
    }

    public function test_canCreateNewFromOrder_validOrder_fromOrderObject() {
        $txFactory = new MobilpayTransactionFactory();
        foreach ($this->_testWcOrdersWoTransaction as $order) {
            $tx = $txFactory->newFromOrder($order);
            $this->_assertNewTransactionMatchesOrder($tx, $order, MobilpayTransaction::STATUS_NEW);
        }
    }

    public function test_tryCreateNewFromOrder_invalidOrder_nonExistingOrderId() {
        $txFactory = new MobilpayTransactionFactory();
        for ($i = 0; $i < 10; $i ++) {
            $orderId = $this->_generateRandomNewOrderId();
            $tx = $txFactory->newFromOrder($orderId);
            $this->assertNull($tx);
        }
    }

    public function test_tryCreateNewFromOrder_invalidOrder_emptyOrderId() {
        $txFactory = new MobilpayTransactionFactory();

        $tx = $txFactory->newFromOrder(0);
        $this->assertNull($tx);

        $tx = $txFactory->newFromOrder(null);
        $this->assertNull($tx);

        $tx = $txFactory->newFromOrder('');
        $this->assertNull($tx);
    }

    public function test_canCreateExistingFromOrder_validOrder_fromId() {
        $txFactory = new MobilpayTransactionFactory();
        foreach ($this->_testWcOrdersWTransaction as $orderId => $order) {
            $tx = $txFactory->newFromOrder($orderId);
            $this->_assertNewTransactionMatchesOrder($tx, $order['order'], $order['txData']['tx_status']);
            $this->_assertMobilpayTransactionMatchesData($tx, $order['txData']);
        }
    }

    public function test_canCreateExistingFromOrder_validOrder_fromOrderObject() {
        $txFactory = new MobilpayTransactionFactory();
        foreach ($this->_testWcOrdersWTransaction as $order) {
            $tx = $txFactory->newFromOrder($order['order']);
            $this->_assertNewTransactionMatchesOrder($tx, $order['order'], $order['txData']['tx_status']);
            $this->_assertMobilpayTransactionMatchesData($tx, $order['txData']);
        }
    }

    public function test_tryCreateExistingFromOrder_invalidOrder_existingOrderWithNoTransactio_orderId() {
        $txFactory = new MobilpayTransactionFactory();
        foreach ($this->_testWcOrdersWoTransaction as $orderId => $order) {
            $tx = $txFactory->existingFromOrder($orderId);
            $this->assertNull($tx);
        }
    }

    public function test_tryCreateExistingFromOrder_invalidOrder_existingOrderWithNoTransactio_orderObject() {
        $txFactory = new MobilpayTransactionFactory();
        foreach ($this->_testWcOrdersWoTransaction as $order) {
            $tx = $txFactory->existingFromOrder($order);
            $this->assertNull($tx);
        }
    }

    public function test_tryCreateExistingFromOrder_invalidOrder_nonExistingOrderId() {
        $txFactory = new MobilpayTransactionFactory();
        for ($i = 0; $i < 10; $i ++) {
            $orderId = $this->_generateRandomNewOrderId();
            $tx = $txFactory->existingFromOrder($orderId);
            $this->assertNull($tx);
        }
    }

    public function test_tryCreateExistingFromOrder_invalidOrder_emptyOrderId() {
        $txFactory = new MobilpayTransactionFactory();

        $tx = $txFactory->existingFromOrder(0);
        $this->assertNull($tx);

        $tx = $txFactory->existingFromOrder(null);
        $this->assertNull($tx);

        $tx = $txFactory->existingFromOrder('');
        $this->assertNull($tx);
    }

    public function test_canCreateFromTransactionId_validTransactionId() {
        $txFactory = new MobilpayTransactionFactory();
        foreach ($this->_testWcOrdersWTransaction as $order) {
            $tx = $txFactory->fromTransactionId($order['txData']['tx_id']);
            $this->_assertMobilpayTransactionMatchesData($tx, $order['txData']);
        }
    }

    public function test_tryCreateFromTransactionId_invalidTransactionId() {
        $txFactory = new MobilpayTransactionFactory();
        for ($i = 0; $i < 10; $i ++) {
            $tx = $txFactory->fromTransactionId($this->_generateRandomMobilpayTransactionId());
            $this->assertNull($tx);
        }
    }

    public function test_tryCreateFromTransactionId_invalidTransactionId_emptyId() {
        $txFactory = new MobilpayTransactionFactory();

        $tx = $txFactory->fromTransactionId(0);
        $this->assertNull($tx);

        $tx = $txFactory->fromTransactionId(null);
        $this->assertNull($tx);

        $tx = $txFactory->fromTransactionId('');
        $this->assertNull($tx);
    }

    private function _assertNewTransactionMatchesOrder(MobilpayTransaction $tx, WC_Order $order, $expectedStatus) {
        $this->assertEquals($order->get_id(), 
            $tx->getOrderId());
        $this->assertEquals($order->get_order_key(), 
            $tx->getTransactionId());
        $this->assertEquals($expectedStatus, 
            $tx->getStatus());
        $this->assertEquals($order->get_total(), 
            $tx->getAmount(), 0.001);
        $this->assertEquals($order->get_currency(), 
            $tx->getCurrency());
        $this->assertEquals($order->get_customer_ip_address(), 
            $tx->getIpAddress());
        $this->assertEquals($order->get_user_id(), 
            $tx->getOrderUserId());
    }
    
    private function _initTestData() {
        $db = $this->_getDb();
        $env = $this->_getEnv();
        $faker = self::_getFaker();

        $db->startTransaction();

        for ($i = 0; $i < 20; $i ++) {
            $customerId = wc_create_new_customer($faker->email, 
                $faker->userName, 
                $faker->password);

            if (!is_wp_error($customerId)) {
                $orderArgs = array(
                    'status' => 'wc-pending',
                    'customer_id' => $customerId,
                    'created_via' => 'unitTests'
                );
    
                $order = wc_create_order($orderArgs);
                if (is_wp_error($order)) {
                    echo PHP_EOL . $order->get_error_message() . PHP_EOL;
                }
    
                if ($order->get_id()) {
                    $order->set_total($faker->randomFloat(2, 1, PHP_FLOAT_MAX));
                    $order->set_order_key(wc_generate_order_key());
                    $order->save();

                    if ($i < 10) {
                        $txData = $this->_generateRandomMobilpayTransactionData(array(
                            'tx_order_id' => $order->get_id(),
                            'tx_amount' => $order->get_total(),
                            'tx_currency' => $order->get_currency(),
                            'tx_order_user_id' => $order->get_user_id(),
                            'tx_processed_amount' => 0,
                            'tx_ip_address' => $order->get_customer_ip_address(),
                            'tx_transaction_id' => $order->get_order_key()
                        ));

                        $txId = $db->insert($env->getPaymentTransactionsTableName(), 
                            $txData);

                        $this->_testWcOrdersWTransaction[$order->get_id()] = array(
                            'order' => $order,
                            'txData' => array_merge($txData, array(
                                'tx_id' => $txId
                            ))
                        );

                        $this->_maxTransactionId = max($this->_maxTransactionId, $txId);
                    } else {
                        $this->_testWcOrdersWoTransaction[$order->get_id()] = $order;
                    }
                } else {
                    echo PHP_EOL . 'Failed to create order' . PHP_EOL;
                }   
            } else {
                echo PHP_EOL . $customerId->get_error_message() . PHP_EOL;
            }
        }

        $db->commit();
    }

    private function _cleanupTestData() {
        $db = $this->_getDb();
        $env = $this->_getEnv();

        $this->_truncateTables($db, 
            $env->getPaymentTransactionsTableName(),
            $env->getDbTablePrefix() . 'woocommerce_order_itemmeta',
            $env->getDbTablePrefix() . 'woocommerce_order_items',
            $env->getDbTablePrefix() . 'postmeta',
            $env->getDbTablePrefix() . 'posts',
            $env->getDbTablePrefix() . 'usermeta',
            $env->getDbTablePrefix() . 'users'
        );

        $this->_testWcOrdersWoTransaction = array();
        $this->_testWcOrdersWTransaction = array();
        $this->_maxTransactionId = 0;
    }

    private function _generateRandomNewOrderId($excludeAdditionalIds = array()) {
        $excludeIds = array_keys($this->_testWcOrdersWoTransaction);
        if (!empty($excludeAdditionalIds) && is_array($excludeAdditionalIds)) {
            $excludeIds = array_merge($excludeAdditionalIds);
        }

        $faker = self::_getFaker();
        
        $max = !empty($excludeIds) 
            ? max($excludeIds) 
            : 0;

        $orderId = $faker->numberBetween($max + 1, $max + 1000);
        return $orderId;
    }

    private function _generateRandomMobilpayTransactionId() {
        $faker = self::_getFaker();      
        $transactionId = $faker->numberBetween($this->_maxTransactionId + 1, $this->_maxTransactionId + 1000);
        return $transactionId;
    }

    private function _getOrderStatuses() {
        return array_keys(wc_get_order_statuses());
    }
}