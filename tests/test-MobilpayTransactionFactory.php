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
use \LvdWcMc\MobilpayTransactionFactory;

class MobilpayTransactionFactoryTests extends WP_UnitTestCase {
    use WcOrderHelpers;
    use MobilpayTransactionTestHelpers;
    use WcOrderHelpers;
    use DbTestHelpers;

    /**
     * @var IntegerIdGenerator
     */
    private $_mobilpayTransactionIdGenerator = null;

    /**
     * @var IntegerIdGenerator
     */
    private $_newOrderIdGenerator = null;

    private $_testWcOrdersWTransaction = array();

    private $_testWcOrdersWoTransaction = array();

    public function __construct() {
        $this->_mobilpayTransactionIdGenerator =
            new IntegerIdGenerator();
        $this->_newOrderIdGenerator = 
            new IntegerIdGenerator();
    }

    public function setUp() {
        parent::setUp();
        $this->_dontReportNotices();
        $this->_installTestData();
        $this->_reportAllErrors();
    }

    private function _installTestData() {
        $db = $this->_getDb();
        $env = $this->_getEnv();
        $orderIds = array();

        $db->startTransaction();

        for ($i = 0; $i < 10; $i ++) {
            $order = $this->_generateAndSaveRandomWcOrder();
            if (!is_wp_error($order)) {
                $this->_testWcOrdersWoTransaction[$order->get_id()] = $order;
                $orderIds[] = $order->get_id();
            } else {
                $this->_writeLine($order->get_error_message());
            }
        }

        for ($i = 0; $i < 10; $i ++) {
            $order = $this->_generateAndSaveRandomWcOrder();
            if (!is_wp_error($order)) {
                $txData = $this->_generateMobilpayTransactionDataFromWcOrder($order);

                $txId = $db->insert($env->getPaymentTransactionsTableName(), 
                    $txData);

                $this->_testWcOrdersWTransaction[$order->get_id()] = array(
                    'order' => $order,
                    'txData' => array_merge($txData, array(
                        'tx_id' => $txId
                    ))
                );

                $orderIds[] = $order->get_id();
            } else {
                $this->_writeLine($order->get_error_message());
            }
        }

        $this->_newOrderIdGenerator
            ->setExcludedIds($orderIds);

        $db->commit();
    }

    public function tearDown() {
        parent::tearDown();
        $this->_cleanupTestData();
    }

    private function _cleanupTestData() {
        $this->_truncateAllWcOrderData();
        $this->_testWcOrdersWoTransaction = array();
        $this->_testWcOrdersWTransaction = array();
        $this->_mobilpayTransactionIdGenerator
            ->reset();
        $this->_newOrderIdGenerator
            ->reset();
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
        $orderIds = array();
        $txFactory = new MobilpayTransactionFactory();
        for ($i = 0; $i < 10; $i ++) {
            $orderId = $this->_generateRandomNewOrderId($orderIds);
            $orderIds[] = $orderId;

            $tx = $txFactory->newFromOrder($orderId);
            $this->assertNull($tx);
        }
    }

    public function test_tryCreateNewFromOrder_invalidOrder_emptyOrderId() {
        $emptyOrderIds = array(null, '', 0);
        $txFactory = new MobilpayTransactionFactory();

        foreach ($emptyOrderIds as $emptyOrderId) {
            $tx = $txFactory->newFromOrder($emptyOrderId);
            $this->assertNull($tx);
        }
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
        $orderIds = array();
        $txFactory = new MobilpayTransactionFactory();

        for ($i = 0; $i < 10; $i ++) {
            $orderId = $this->_generateRandomNewOrderId($orderIds);
            $orderIds[] = $orderId;

            $tx = $txFactory->existingFromOrder($orderId);
            $this->assertNull($tx);
        }
    }

    public function test_tryCreateExistingFromOrder_invalidOrder_emptyOrderId() {
        $emptyOrderIds = array(null, '', 0);
        $txFactory = new MobilpayTransactionFactory();

        foreach ($emptyOrderIds as $emptyOrderId) {
            $tx = $txFactory->existingFromOrder($emptyOrderId);
            $this->assertNull($tx);
        }
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
        $emptyTransactionIds = array(null, '', 0);
        $txFactory = new MobilpayTransactionFactory();

        foreach ($emptyTransactionIds as $emptyTransactionId) {
            $tx = $txFactory->fromTransactionId($emptyTransactionId);
            $this->assertNull($tx);
        }
    }

    public function test_canCreateFromExistingTransactionRawData() {
        $txFactory = new MobilpayTransactionFactory();

        foreach ($this->_testWcOrdersWTransaction as $order) {
            $tx = $txFactory->existingFromRawTransactionData($order['txData']);
            $this->assertNotNull($tx);
            $this->_assertMobilpayTransactionMatchesData($tx, $order['txData']);
        }
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

    private function _generateRandomNewOrderId($excludeAdditionalIds = array()) {
       return $this->_newOrderIdGenerator
            ->generateId($excludeAdditionalIds);
    }

    private function _generateRandomMobilpayTransactionId() {
        return $this->_mobilpayTransactionIdGenerator
            ->generateId();
    }
}