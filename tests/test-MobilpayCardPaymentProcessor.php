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
     * The payment for these transactions have been completed.
     * @var array
     */
    private $_testCompletedOrderData = array();

    /**
     * @var MobilpayTransactionFactory
     */
    private $_mobilpayTransactionFactory;

    /**
     * @var IntegerIdGenerator
     */
    private $_orderIdGenerator;

    public function __construct() {
        $this->_orderIdGenerator = 
            new IntegerIdGenerator();
        $this->_mobilpayTransactionFactory =
            new MobilpayTransactionFactory();
    }

    public function setUp() {
        parent::setUp();
        $this->_clearOrderCache();
        $this->_installTestData();
    }

    private function _installTestData() {
        $db = $this->_getDb();
        $db->startTransaction();

        $this->_installNewOrderData();
        $this->_installInitializedOrderData();
        $this->_installCompletedOrderData();

        $this->_orderIdGenerator->setExcludedIds($this->_getAllGeneratedOrderIds());

        $db->commit();
    }

    private function _installNewOrderData() {
        for ($i = 0; $i < 10; $i ++) {
            $order = $this->_generateRandomWcPendingOrderForOurGateway();
            if (!is_wp_error($order)) {
                $this->_testNewOrderData[$order->get_id()] = $order;
            } else {
                $this->_writeLine($order->get_error_message());
            }
        }
    }

    private function _installInitializedOrderData() {
        for ($i = 0; $i < 10; $i ++) {
            $order = $this->_generateRandomWcPendingOrderForOurGateway();
            if (!is_wp_error($order)) {
                $this->_testInitializedOrderData[$order->get_id()] = $order;
            } else {
                $this->_writeLine($order->get_error_message());
            }
        }

        $this->_createNewTransactionsFromOrders($this->_testInitializedOrderData, 
            MobilpayTransaction::STATUS_NEW);
    }

    private function _installCompletedOrderData() {
        for ($i = 0; $i < 10; $i ++) {
            $order = $this->_generateRandomWcCompletedOrderForOurGateway();
            if (!is_wp_error($order)) {
                $this->_testCompletedOrderData[$order->get_id()] = $order;
            } else {
                $this->_writeLine($order->get_error_message());
            }
        }

        $this->_createNewTransactionsFromOrders($this->_testCompletedOrderData, 
            MobilpayTransaction::STATUS_CONFIRMED);
    }

    private function _generateRandomWcPendingOrderForOurGateway() {
        return $this->_generateAndSaveRandomWcOrder('wc-pending', LVD_WCMC_WOOCOMMERCE_CC_GATEWAY_ID);
    }

    private function _generateRandomWcCompletedOrderForOurGateway() {
        return $this->_generateAndSaveRandomWcOrder('wc-completed', LVD_WCMC_WOOCOMMERCE_CC_GATEWAY_ID);
    }

    private function _createNewTransactionsFromOrders($orders, $status) {
        $db = $this->_getDb();
        $env = $this->_getEnv();

        $db->startTransaction();

        foreach ($orders as $order) {
            $txData = $this->_generateMobilpayTransactionDataFromWcOrder($order, 
                $status);
            $db->insert($env->getPaymentTransactionsTableName(), 
                $txData);
        }

        $db->commit();
    }

    public function tearDown() {
        parent::tearDown();
        $this->_cleanupTestData();
        $this->_clearOrderCache();
    }

    private function _cleanupTestData() {
        $this->_truncateAllWcOrderData();
        $this->_testNewOrderData = array();
        $this->_testInitializedOrderData = array();
        $this->_testCompletedOrderData = array();
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

        $paymentRequest = $this->_generateCardPaymentRequestFromOrder($order);
        $result = $processor->processPaymentInitialized($order, $paymentRequest);
        $this->_assertSuccessfulProcessingResult($result);

        $transaction = $this->_mobilpayTransactionFactory->existingFromOrder($order);
        $this->assertNotNull($transaction);

        return $transaction;
    }

    public function test_tryProcessPaymentInitialized_noTransactionReturnedFromOrder_withHooks() {
        for ($i = 0; $i < 10; $i ++) {
            $hookTester = WordPressHookTester::forActionHook('lvdwcmc_order_payment_initialized', 2);

            $this->_testTryProcessPaymentInitializedNoTransactionReturnedForOrder();
            $this->assertFalse($hookTester->wasCalled());

            $hookTester->unregister();
        }
    }

    public function test_tryProcessPaymentInitialized_noTransactionReturnedFromOrder_withoutHooks() {
        for ($i = 0; $i < 10; $i ++) {
            $this->_testTryProcessPaymentInitializedNoTransactionReturnedForOrder();
        }
    }

    private function _testTryProcessPaymentInitializedNoTransactionReturnedForOrder() {
        $processor = new MobilpayCardPaymentProcessor(
            new AlwaysReturnNullMobilpayTransactionFactory()
        );

        $order = $this->_generateRandomWcPendingOrderForOurGateway();
        if (!is_wp_error($order)) {
            $paymentRequest = $this->_generateCardPaymentRequestFromOrder($order);
            $result = $processor->processPaymentInitialized($order, $paymentRequest);
            $this->_assertFailedWithAppErrorProcessingResult($result);
        } else {
            $this->_writeLine($order->get_error_message());
        }
    }

    public function test_canProcessConfirmedPayment_newTransaction_completePayment_needsProcessing_withHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $hookTester = WordPressHookTester::forActionHook('lvdwcmc_order_payment_confirmed', 3);
            $transaction = $this->_testCanProcessConfirmedPaymentFromNewTransactionWithCompletePayment($order, true);
            
            $this->assertTrue($hookTester->wasCalledWithNumberOfArgs(3));
            $this->assertTrue($hookTester->wasCalledWithNthArg(0, function($calledWithOrder) use ($order) {
                return $calledWithOrder->get_id() == $order->get_id();
            }));
            $this->assertTrue($hookTester->wasCalledWithNthArg(1, $transaction));

            $hookTester->unregister();
        }
    }

    public function test_canProcessConfirmedPayment_newTransaction_completePayment_doesntNeedProcessing_withHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $hookTester = WordPressHookTester::forActionHook('lvdwcmc_order_payment_confirmed', 3);
            $transaction = $this->_testCanProcessConfirmedPaymentFromNewTransactionWithCompletePayment($order, false);
            
            $this->assertTrue($hookTester->wasCalledWithNumberOfArgs(3));
            $this->assertTrue($hookTester->wasCalledWithNthArg(0, function($calledWithOrder) use ($order) {
                return $calledWithOrder->get_id() == $order->get_id();
            }));
            $this->assertTrue($hookTester->wasCalledWithNthArg(1, $transaction));

            $hookTester->unregister();
        }
    }

    public function test_canProcessConfirmedPayment_newTransaction_completePayment_needsProcessing_withoutHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $this->_testCanProcessConfirmedPaymentFromNewTransactionWithCompletePayment($order, true);
        }
    }

    public function test_canProcessConfirmedPayment_newTransaction_completePayment_doesntNeedProcessing_withoutHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $this->_testCanProcessConfirmedPaymentFromNewTransactionWithCompletePayment($order, false);
        }
    }

    private function _testCanProcessConfirmedPaymentFromNewTransactionWithCompletePayment(\WC_Order $order, $needsProcessing) {
        $processor = new MobilpayCardPaymentProcessor();
        $transactionTester = new MobilpayTransactionProcessingTester($order);
        $orderTester = new WcOrderProcessingTester($order);
        $testOrderProxy = WcOrderProxy::overrideNeedsProcessing($order, 
            $needsProcessing);

        $paymentRequest = $this->_generateFullPaymentCompletedCardPaymentRequestFromOrder($order);
        $result = $processor->processConfirmedPaymentResponse($testOrderProxy, $paymentRequest);
        $this->_assertSuccessfulProcessingResult($result);

        $orderTester->refresh();
        $this->assertTrue($orderTester->orderExists());

        if ($needsProcessing) {
            $expectedStatus = 'processing';
        } else {
            $expectedStatus = 'completed';
        }

        $this->assertTrue($orderTester->orderHasStatus($expectedStatus));
        $this->assertTrue($orderTester->currentInternalOrderNotesCountDiffersBy(2));
        $this->assertTrue($orderTester->currentCustomerOrderNotesCountDiffersBy(1));

        $transactionTester->refresh();
        $this->assertTrue($transactionTester->transactionExists());
        $this->assertTrue($transactionTester->transactionIsConfirmed());
        $this->assertTrue($transactionTester->transactionMatchesPaymentResponse($paymentRequest));

        return $transactionTester->getTransaction();
    }

    public function test_tryProcessConfirmedPayment_noTransactionReturnedFromOrder_withHooks() {
        for ($i = 0; $i < 10; $i ++) {
            $hookTester = WordPressHookTester::forActionHook('lvdwcmc_order_payment_confirmed', 3);

            $this->_testTryProcessConfirmedPaymentNoTransactionReturnedFromOrder();
            $this->assertFalse($hookTester->wasCalled());

            $hookTester->unregister();
        }
    }

    public function test_tryProcessConfirmedPayment_noTransactionReturnedFromOrder_withoutHooks() {
        for ($i = 0; $i < 10; $i ++) {
            $this->_testTryProcessConfirmedPaymentNoTransactionReturnedFromOrder();
        }
    }

    private function _testTryProcessConfirmedPaymentNoTransactionReturnedFromOrder() {
        $processor = new MobilpayCardPaymentProcessor(
            new AlwaysReturnNullMobilpayTransactionFactory()
        );

        $order = $this->_generateRandomWcPendingOrderForOurGateway();
        if (!is_wp_error($order)) {
            $request = $this->_generateFullPaymentCompletedCardPaymentRequestFromOrder($order);
            $result = $processor->processConfirmedPaymentResponse($order, $request);
            $this->_assertFailedWithAppErrorProcessingResult($result);
        } else {
            $this->_writeLine($order->get_error_message());
        }
    }

    public function test_canProcessConfirmedPayment_newTransaction_partialPayment_withHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $hookTester = WordPressHookTester::forActionHook('lvdwcmc_order_payment_confirmed', 3);
            $transaction = $this->_testCanProcessConfirmedPaymentFromNewTransactionWithPartialPayment($order);

            $this->assertTrue($hookTester->wasCalledWithNumberOfArgs(3));
            $this->assertTrue($hookTester->wasCalledWithNthArg(0, function($calledWithOrder) use ($order) {
                return $calledWithOrder->get_id() == $order->get_id();
            }));
            $this->assertTrue($hookTester->wasCalledWithNthArg(1, $transaction));

            $hookTester->unregister();
        }
    }

    public function test_canProcessConfirmedPayment_newTransaction_partialPayment_withoutHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $this->_testCanProcessConfirmedPaymentFromNewTransactionWithPartialPayment($order);
        }
    }

    private function _testCanProcessConfirmedPaymentFromNewTransactionWithPartialPayment(\WC_Order $order) {
        $processor = new MobilpayCardPaymentProcessor();
        $transactionTester = new MobilpayTransactionProcessingTester($order);
        $orderTester = new WcOrderProcessingTester($order);

        $paymentRequest = $this->_generatePartialPaymentCompletedCardPaymentRequestFromOrder($order);
        $result = $processor->processConfirmedPaymentResponse($order, $paymentRequest);
        $this->_assertSuccessfulProcessingResult($result);

        $orderTester->refresh();
        $this->assertTrue($orderTester->orderExists());

        $this->assertTrue($orderTester->orderHasStatus('on-hold'));
        if (!$orderTester->orderHadStatus('on-hold')) {
            $this->assertTrue($orderTester->currentInternalOrderNotesCountDiffersBy(2));
            $this->assertTrue($orderTester->currentCustomerOrderNotesCountDiffersBy(1));
        }

        $transactionTester->refresh();
        $this->assertTrue($transactionTester->transactionExists());
        $this->assertTrue($transactionTester->transactionIsConfirmed());
        $this->assertTrue($transactionTester->transactionMatchesPaymentResponse($paymentRequest));

        return $transactionTester->getTransaction();
    }

    public function test_canProcessConfirmedPayment_newTransaction_successivePartialPayments_needsProcessing_withHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $hookTester = WordPressHookTester::forActionHook('lvdwcmc_order_payment_confirmed', 3);
            $transaction = $this->_testCanProcessConfirmedPaymentFromNewTransactionWithPartialPaymentsUntilCompletion($order, true);
            
            $this->assertTrue($hookTester->wasCalledWithNumberOfArgs(3));
            $this->assertTrue($hookTester->wasCalledWithNthArg(0, function($calledWithOrder) use ($order) {
                return $calledWithOrder->get_id() == $order->get_id();
            }));
            $this->assertTrue($hookTester->wasCalledWithNthArg(1, $transaction));

            $hookTester->unregister();
        }
    }

    public function test_canProcessConfirmedPayment_newTransaction_successivePartialPayments_doesntNeedProcessing_withHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $hookTester = WordPressHookTester::forActionHook('lvdwcmc_order_payment_confirmed', 3);
            $transaction = $this->_testCanProcessConfirmedPaymentFromNewTransactionWithPartialPaymentsUntilCompletion($order, false);
            
            $this->assertTrue($hookTester->wasCalledWithNumberOfArgs(3));
            $this->assertTrue($hookTester->wasCalledWithNthArg(0, function($calledWithOrder) use ($order) {
                return $calledWithOrder->get_id() == $order->get_id();
            }));
            $this->assertTrue($hookTester->wasCalledWithNthArg(1, $transaction));

            $hookTester->unregister();
        }
    }

    public function test_canProcessConfirmedPayment_newTransaction_successivePartialPayments_needsProcessing_withoutHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $this->_testCanProcessConfirmedPaymentFromNewTransactionWithPartialPaymentsUntilCompletion($order, true);
        }
    }

    public function test_canProcessConfirmedPayment_newTransaction_successivePartialPayments_doesntNeedProcessing_withoutHooks() {
        foreach ($this->_testInitializedOrderData as $orderId => $order) {
            $this->_testCanProcessConfirmedPaymentFromNewTransactionWithPartialPaymentsUntilCompletion($order, false);
        }
    }

    private function _testCanProcessConfirmedPaymentFromNewTransactionWithPartialPaymentsUntilCompletion(\WC_Order $order, $needsProcessing) {
        $processor = new MobilpayCardPaymentProcessor();
        $transactionTester = new MobilpayTransactionProcessingTester($order);
        $orderTester = new WcOrderProcessingTester($order);

        $paymentRequests = $this->_generatePartialPaymentCardPaymentSplitRequestsFromOrder($order);
        foreach ($paymentRequests as $paymentRequest) {
            $testOrderProxy = WcOrderProxy::overrideNeedsProcessing($orderTester->getOrder(), 
                $needsProcessing);

            $result = $processor->processConfirmedPaymentResponse($testOrderProxy, $paymentRequest);
            $this->_assertSuccessfulProcessingResult($result);

            $orderTester->refresh();
            $this->assertTrue($orderTester->orderExists());

            $expectedStatus = null;
            $transactionTester->refresh();

            if ($transactionTester->isTransactionAmountCompletelyProcessed()) {
                if ($needsProcessing) {
                    $expectedStatus = 'processing';
                } else {
                    $expectedStatus = 'completed';
                }
            } else {
                $expectedStatus = 'on-hold';
            }

            $this->assertTrue($orderTester->orderHasStatus($expectedStatus));
        }

        $this->assertTrue($orderTester->currentInternalOrderNotesCountDiffersBy(4));
        $this->assertTrue($orderTester->currentCustomerOrderNotesCountDiffersBy(2));

        $transactionTester->refresh();
        $this->assertTrue($transactionTester->transactionExists());
        $this->assertTrue($transactionTester->transactionIsConfirmed());
        $this->assertTrue($transactionTester->isTransactionAmountCompletelyProcessed());

        return $transactionTester->getTransaction();
    }

    private function _assertSuccessfulProcessingResult($result) {
        $this->assertEquals(MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK, 
            $result);
    }

    private function _assertFailedWithAppErrorProcessingResult($result) {
        $this->assertEquals(MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_APPLICATION, 
            $result);
    }

    private function _generateNewOrderId() {
        return $this->_orderIdGenerator
            ->generateId();
    }

    private function _getAllGeneratedOrderIds() {
        return array_merge(
            array_keys($this->_testNewOrderData),
            array_keys($this->_testInitializedOrderData),
            array_keys($this->_testCompletedOrderData)
        );
    }
}