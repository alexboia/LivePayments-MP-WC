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
use LvdWcMc\MobilpayCardPaymentProcessor;
use LvdWcMc\MobilpayCreditCardGateway;
use LvdWcMc\MobilpayTransactionFactory;

class MobilpayCardPaymentProcessorTests extends WP_UnitTestCase {
    use MobilpayTransactionTestHelpers;
    use WcOrderHelpers;
    use DbTestHelpers;
    use MobilpayCardRequestTestHelpers;

    /**
     * The payment for these transactions have not been initialized yet. 
     * Thus, they don't have an associated transaction yet.
     * 
     * @var array
     */
    private $_testNewOrderData = array();

    /**
     * @var IntegerIdGenerator
     */
    private $_orderIdGenerator;

    public function __construct() {
        $this->_orderIdGenerator = 
            new IntegerIdGenerator();
    }

    public function setUp() {
        parent::setUp();
        $this->_installTestData();
    }

    private function _installTestData() {
        $db = $this->_getDb();
        $db->startTransaction();

        $this->_installNewOrderData();

        $this->_orderIdGenerator
            ->setExcludedIds($this->_getAllGeneratedOrderIds());

        $db->commit();
    }

    private function _installNewOrderData() {
        for ($i = 0; $i < 10; $i ++) {
            $order = $this->_generateRandomWcPendingOrderForOurGateway();
            $this->_testNewOrderData[$order->get_id()] = $order;
        }
    }

    private function _generateRandomWcPendingOrderForOurGateway() {
        return $this->_generateRandomWcOrder('wc-pending', LVD_WCMC_WOOCOMMERCE_CC_GATEWAY_ID);
    }

    public function tearDown() {
        parent::tearDown();
        $this->_cleanupTestData();
    }

    private function _cleanupTestData() {
        $this->_truncateAllWcOrderData();
        $this->_testNewOrderData = array();
    }

    public function test_canProcessPaymentInitialized_existingOrder_withHooks() {
        foreach ($this->_testNewOrderData as $orderId => $order) {
            $hookTester = WordPressHookTester::forActionHook('lvdwcmc_order_payment_initialized', 2);
            $transaction = $this->_testCanProcessPaymentInitializedForExistingOrder($order);

            $this->assertTrue($hookTester->wasCalled());
            $this->assertTrue($hookTester->wasCalledWithSpecificArgs(array(
                $order, 
                $transaction
            )));

            $hookTester->unregister();
        }
    }

    public function test_canProcessPaymentInitialized_existingOrder_withoutHooks() {
        foreach ($this->_testNewOrderData as $orderId => $order) {
            $this->_testCanProcessPaymentInitializedForExistingOrder($order);
        }
    }

    private function _testCanProcessPaymentInitializedForExistingOrder(\WC_Order $order) {
        $processor = new MobilpayCardPaymentProcessor();
        $transactionFactory = new MobilpayTransactionFactory();

        $result = $processor->processPaymentInitialized($order, $this->_createCardPaymentRequestFromOrder($order));
        $this->assertEquals(MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK, $result);

        $transaction = $transactionFactory->existingFromOrder($order);
        $this->assertNotNull($transaction);

        return $transaction;
    }

    private function _generateNewOrderId() {
        return $this->_orderIdGenerator
            ->generateId();
    }

    private function _getAllGeneratedOrderIds() {
        return array_merge(
            array_keys($this->_testNewOrderData)
        );
    }
}