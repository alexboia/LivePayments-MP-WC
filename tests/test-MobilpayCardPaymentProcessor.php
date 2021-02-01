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
     * The payment for these transactions have been initialized, 
     *  but no reply has been received from the gateway yet
     * @var array
     */
    private $_testInitializedOrderData = array();

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

    private function _installInitializedOrderData() {
        for ($i = 0; $i < 10; $i ++) {
            $order = $this->_generateRandomWcPendingOrderForOurGateway();
            $this->_testInitializedOrderData[$order->get_id()] = $order;
        }

        $this->_createNewTransactionsFromOrders($this->_testInitializedOrderData);
    }

    private function _generateRandomWcPendingOrderForOurGateway() {
        return $this->_generateAndSaveRandomWcOrder('wc-pending', LVD_WCMC_WOOCOMMERCE_CC_GATEWAY_ID);
    }

    private function _createNewTransactionsFromOrders($orders) {
        $db = $this->_getDb();
        $env = $this->_getEnv();

        $db->startTransaction();

        foreach ($orders as $order) {
            $txData = $this->_generateMobilpayTransactionDataFromWcOrder($order, 
                MobilpayTransaction::STATUS_NEW);
            $db->insert($env->getPaymentTransactionsTableName(), 
                $txData);
        }

        $db->commit();
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

        $result = $processor->processPaymentInitialized($order, $this->_generateCardPaymentRequestFromOrder($order));
        $this->assertEquals(MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK, $result);

        $transaction = $transactionFactory->existingFromOrder($order);
        $this->assertNotNull($transaction);

        return $transaction;
    }

    public function test_canProcessConfirmedPayment_newTransaction_completePayment_needsProcessing_withHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $hookTester = WordPressHookTester::forActionHook('lvdwcmc_order_payment_confirmed', 3);
            $transaction = $this->_testCanProcessConfirmedPaymentFromNewTransaction($order, true);
            $this->assertTrue($hookTester->wasCalledWithNumberOfArgs(3));

            $this->assertTrue($hookTester->wasCalledWithNthArg(0, $order));
            $this->assertTrue($hookTester->wasCalledWithNthArg(1, $transaction));
        }
    }

    public function test_canProcessConfirmedPayment_newTransaction_completePayment_doesntNeedProcessing_withHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $hookTester = WordPressHookTester::forActionHook('lvdwcmc_order_payment_confirmed', 3);
            $transaction = $this->_testCanProcessConfirmedPaymentFromNewTransaction($order, false);
            
            $this->assertTrue($hookTester->wasCalledWithNumberOfArgs(3));
            $this->assertTrue($hookTester->wasCalledWithNthArg(0, $order));
            $this->assertTrue($hookTester->wasCalledWithNthArg(1, $transaction));
        }
    }

    public function test_canProcessConfirmedPayment_newTransaction_completePayment_needsProcessing_withoutHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $this->_testCanProcessConfirmedPaymentFromNewTransaction($order, true);
        }
    }

    public function test_canProcessConfirmedPayment_newTransaction_completePayment_doesntNeedProcessing_withoutHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $this->_testCanProcessConfirmedPaymentFromNewTransaction($order, false);
        }
    }

    private function _testCanProcessConfirmedPaymentFromNewTransaction(\WC_Order $order, $needsProcessing) {
        $processor = new MobilpayCardPaymentProcessor();
        $transactionFactory = new MobilpayTransactionFactory();

        $testOrder = new WcOrderProxy($order, array(
            'needs_processing' => $needsProcessing
        ));

        $request = $this->_generateFullPaymentCompletedCardPaymentRequestFromOrder($order);
        $result = $processor->processConfirmedPaymentResponse($testOrder, $request);

        $this->assertEquals(MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK, 
            $result);

        $countCustomerNotes = $this->_countCustomerOrderNotes($order->get_id());
        $countInternalNotes = $this->_countInternalOrderNotes($order->get_id());

        $readOrder = wc_get_order($order->get_id());
        $this->assertNotNull($readOrder);

        if ($needsProcessing) {
            $expectedStatus = 'completed';
        } else {
            $expectedStatus = 'processing';
        }

        $this->assertEquals($expectedStatus, 
            $readOrder->get_status());

        $this->assertEquals($countCustomerNotes + 1, 
            $this->_countCustomerOrderNotes($order->get_id()));
        $this->assertEquals($countInternalNotes + 1, 
            $this->_countInternalOrderNotes($order->get_id()));

        $transaction = $transactionFactory->existingFromOrder($order->get_id());
        $this->assertNotNull($transaction);

        $this->assertEquals(MobilpayTransaction::STATUS_CONFIRMED, 
            $transaction->getStatus());
        $this->assertEquals($request->objPmNotify->pan_masked, 
            $transaction->getPANMasked());
        $this->assertEquals($request->objPmNotify->purchaseId, 
            $transaction->getProviderTransactionId());
        $this->assertEquals($request->objPmNotify->processedAmount, 
            $transaction->getProcessedAmount());

        $this->assertNull($transaction->getErrorCode());
        $this->assertNull($transaction->getErrorMessage());

        return $transaction;
    }

    private function _countInternalOrderNotes($orderId) {
        $notes =  wc_get_order_notes(array(
            'order_id' => $orderId,
            'type' => 'internal'
        ));

        return !empty($notes) 
            ? count($notes) 
            : 0;
    }

    private function _countCustomerOrderNotes($orderId) {
        $notes =  wc_get_order_notes(array(
            'order_id' => $orderId,
            'type' => 'customer'
        ));

        return !empty($notes) 
            ? count($notes) 
            : 0;
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