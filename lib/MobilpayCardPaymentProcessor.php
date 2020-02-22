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

namespace LvdWcMc {
    class MobilpayCardPaymentProcessor implements MobilpayCardPaymentProcessorInterface {
        use LoggingExtensions;

        /**
         * @var \LvdWcMc\Env Reference to the environment object
         */
        private $_env;

        /**
         * @var WC_Logger The logger instance used by this processor
         */
        private $_logger = null;

        /**
         * @var \LvdWcMc\MobilpayTransactionFactory Reference to the transaction factory
         */
        private $_transactionFactory = null;

        public function __construct() {
            $this->_env = lvdwcmc_env();
            $this->_logger = wc_get_logger();
            $this->_transactionFactory = new MobilpayTransactionFactory();

            add_action('woocommerce_order_fully_refunded_status', 
                array($this, 'onOrderFullyRefundedGetRefundedStatus'), 
                PHP_INT_MAX, 
                3);
        }

        public function onOrderFullyRefundedGetRefundedStatus($status, $orderId, $refundId) {
            $context = $this->_getLoggingContext($orderId);
            $transaction = $this->_transactionFactory->existingFromOrder($orderId);

            if ($transaction != null 
                && $transaction->isCredited() 
                && $transaction->isAmountCompletelyProcessed()) {
                $this->logDebug('Supressing order refund status update.', $context);
                $status = null;
            }

            return $status;
        }

        public function processOrderInitialized(\WC_Order $order, \Mobilpay_Payment_Request_Abstract $request) {
            return ($this->_transactionFactory->newFromOrder($order) != null)
                ? MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK
                : MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_APPLICATION;
        }

        public function processConfirmedPaymentResponse(\WC_Order $order, \Mobilpay_Payment_Request_Abstract $request) {
            $context = $this->_getLoggingContext($order);

            $this->logDebug('Begin processing confirmed payment response for order...', 
                $context);

            $originalAmount = $this->_getOriginalAmount($request);
            $processedAmount = $this->_getProcessedAmount($request);

            if ($processedAmount <= 0) {
                $processedAmount = $originalAmount;
            }

            $panMasked = $this->_getPANMasked($request);
            $transactionId = $this->_getTransactionId($request);
            $processResult = MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_APPLICATION;

            $transaction = $this->_transactionFactory->existingFromOrder($order);
            if ($transaction != null) {
                $this->logDebug('Found local transaction data from current order. Processing order...', 
                    $context);

                $this->logDebug(sprintf('Processed amount is %s. Original amount is %s', $processedAmount, $originalAmount), 
                    $context);

                if ($transaction->canBeSetConfirmed()) {
                    $transaction->setConfirmed($transactionId, $processedAmount, $panMasked);
                    if ($transaction->isAmountCompletelyProcessed()) {
                        $this->logDebug('Processed amount OK. Completing order...', 
                            $context);

                        if (!$order->needs_processing()) {
                            $order->update_status('completed', $this->_getOrderCompletedOrderStatusNote());
                            $order->add_order_note($this->_getGenericOrderCompletedCustomerNote($transactionId), 1);
                            $order->add_order_note($this->_getGenericOrderCompletedAdminNote($transactionId), 0);
                        } else {
                            $order->update_status('processing', $this->_getOrderProcessingStatusNote());
                            $order->add_order_note($this->_getGenericOrderProcessingCustomerNote($transactionId), 1);
                            $order->add_order_note($this->_getGenericOrderProcessingAdminNote($transactionId), 0);
                        }
        
                        wc_reduce_stock_levels($order->get_id());
                    } else {
                        $this->logDebug('Processed amount lower than original amount. Placing order on hold...', 
                            $context);

                        //Do not set status or add notes more than once
                        if (!$order->has_status('on-hold')) {
                            $order->update_status('on-hold', $this->_getDifferentAmountsOnHoldOrderStatusNote());
                            $order->add_order_note($this->_getDifferentAmountsOnHoldOrderCustomerNote($transactionId, 
                                $originalAmount, 
                                $processedAmount), 1);
                            $order->add_order_note($this->_getDifferentAmountsOnHoldOrderAdminNote($transactionId, 
                                $originalAmount, 
                                $processedAmount), 0);
                        }
                    }
                } else {
                    $this->logDebug('The local transaction could not be set as completed.', 
                        $context);
                }

                $processResult = MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK;
            } else {
                $this->logDebug('Could not find local transaction data from current order', 
                    $context);
            }

            return $processResult;
        }

        public function processFailedPaymentResponse(\WC_Order $order, \Mobilpay_Payment_Request_Abstract $request) {
            $context = $this->_getLoggingContext($order);

            $this->logDebug('Begin processing failed payment response for order...', 
                $context);

            $errorCode = $this->_getErrorCode($request);
            $errorMessage = $this->_getErrorMessage($request);

            if (empty($errorMessage)) {
                $errorMessage = $this->_getMobilpayPaymentMessageError($errorCode);
            }

            $transactionId = $this->_getTransactionId($request);
            $processResult = MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_APPLICATION;

            $transaction = $this->_transactionFactory->existingFromOrder($order);
            if ($transaction != null) {
                $this->logDebug('Found local transaction data from current order. Processing order...', 
                    $context);

                if ($transaction->canBeSetFailed()) {
                    $transaction->setFailed($transactionId, $errorCode, $errorMessage);
                    $order->update_status('failed', $this->_getFailedPaymentOrderStatusNote());
    
                    $order->add_order_note($this->_getFailedPaymentOrderGenericNote($transactionId, 
                        $errorCode, 
                        $errorMessage), 1);
            
                    $order->add_order_note($this->_getFailedPaymentOrderGenericNote($transactionId, 
                        $errorCode, 
                        $errorMessage), 0);
                } else {
                    $this->logDebug('The local transaction could not be set as failed.', 
                        $context);
                }

                $processResult = MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK;
            } else {
                $this->logDebug('Could not find local transaction data from current order', 
                    $context);
            }

            $this->logDebug('Done processing failed payment response for order', 
                $context);
    
            return $processResult;
        }

        public function processPaymentCancelledResponse(\WC_Order $order, \Mobilpay_Payment_Request_Abstract $request) {
            $context = $this->_getLoggingContext($order);

            $this->logDebug('Begin processing canceled payment response for order...', 
                $context);

            $transactionId = $this->_getTransactionId($request);
            $processResult = MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_APPLICATION;

            $transaction = $this->_transactionFactory->existingFromOrder($order);
            if ($transaction != null) {
                $this->logDebug('Found local transaction data from current order. Processing order...', 
                    $context);

                if ($transaction->canBeSetCancelled()) {
                    $transaction->setCancelled($transactionId);
                    $order->update_status('cancelled', $this->_getGenericCancelledOrderStatusNote());

                    $order->add_order_note($this->_getGenericCancelledOrderCustomerNote($transactionId), 1);
                    $order->add_order_note($this->_getGenericCancelledOrderAdminNote($transactionId), 0);
                } else {
                    $this->logDebug('The local transaction could not be set as cancelled.', 
                        $context);
                }

                $processResult = MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK;
            } else {
                $this->logDebug('Could not find local transaction data from current order', 
                    $context);
            }

            $this->logDebug('Done processing canceled payment response for order', 
                $context);

            return $processResult;
        }

        public function processPendingPaymentResponse(\WC_Order $order, \Mobilpay_Payment_Request_Abstract $request) {
            $context = $this->_getLoggingContext($order);

            $this->logDebug('Begin processing pending payment response for order...', 
                $context);

            $action = $this->_getAction($request);
            $transactionId = $this->_getTransactionId($request);
            $processResult = MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_APPLICATION;

            $transaction = $this->_transactionFactory->existingFromOrder($order);
            if ($transaction != null) {
                $this->logDebug('Found local transaction data from current order. Processing order...', 
                    $context);

                if ($transaction->canBeSetPaymentPending()) {
                    $transaction->setPaymentPending($action, $transactionId);
                    $order->update_status('on-hold', $this->_getGenericOnHoldOrderStatusNote());
                    
                    $order->add_order_note($this->_getGenericOnHoldOrderCustomerNote($transactionId), 1);
                    $order->add_order_note($this->_getGenericOnHoldOrderAdminNote($transactionId), 0);
                } else {
                    $this->logDebug('The local transaction could not be set as payment pending.', 
                        $context);
                }

                $processResult = MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK;
            } else {
                $this->logDebug('Could not find local transaction data from current order', 
                    $context);
            }

            $this->logDebug('Done processing pending payment response for order', 
                $context);

            return $processResult;
        }

        public function processCreditPaymentResponse(\WC_Order $order, \Mobilpay_Payment_Request_Abstract $request) {
            $context = $this->_getLoggingContext($order);

            $this->logDebug('Begin processing credit payment response for order...', 
                $context);

            $originalAmount = $this->_getOriginalAmount($request);
            $processedAmount = $this->_getProcessedAmount($request);

            if ($processedAmount <= 0) {
                $processedAmount = $originalAmount;
            }

            //Extract transaction id from payment request
            $panMasked = $this->_getPANMasked($request);
            $transactionId = $this->_getTransactionId($request);
            $processResult = MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_APPLICATION;

            $transaction = $this->_transactionFactory->existingFromOrder($order);
            if ($transaction != null) {
                $this->logDebug('Found local transaction data from current order. Processing order...', 
                    $context);

                if ($transaction->canBeSetCredited()) {
                    $transaction->setCredited($transactionId, $processedAmount, $panMasked);

                    //Create partial refund record
                    $refund = wc_create_refund(array(
                        'order_id' => $order->get_id(),
                        'amount' => $processedAmount,
                        'reason' => $this->_getPartialRefundReason($transactionId),
                        'line_items' => array(),
                        'restock_items' => false,
                        'refund_payment' => false
                    ));

                    if (!is_wp_error($refund)) {
                        if ($transaction->isAmountCompletelyProcessed()) {
                            //Do not set status or add notes more than once
                            if (!$order->has_status('refunded')) {
                                $order->update_status('refunded', $this->_getGenericRefundOrderStatusNote());
                                $order->add_order_note($this->_getGenericRefundOrderCustomerNote($transactionId), 1);
                                $order->add_order_note($this->_getGenericRefundOrderAdminNote($transactionId), 0);
                            }
                        }
                    }
                } else {
                    $this->logDebug('The local transaction could not be set as credited.', 
                        $context);
                }

                $processResult = MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK;
            } else {
                $this->logDebug('Could not find local transaction data from current order', 
                    $context);
            }

            $this->logDebug('Done processing credit payment response for order', 
                $context);

            return $processResult;
        }

        private function _getLoggingContext(\WC_Order $order) {
            return array(
                'source' => MobilpayCreditCardGateway::GATEWAY_ID,
                'orderId' => $order->get_id()
            );
        }

        private function _getTransactionId(\Mobilpay_Payment_Request_Abstract $request) {
            return $request->objPmNotify->purchaseId;
        }

        private function _getAction(\Mobilpay_Payment_Request_Abstract $request) {
            return $request->objPmNotify->action;
        }

        private function _getOriginalAmount(\Mobilpay_Payment_Request_Abstract $request) {
            return floatval($request->objPmNotify->originalAmount);
        }

        private function _getProcessedAmount(\Mobilpay_Payment_Request_Abstract $request) {
            return !empty($request->objPmNotify->processedAmount) && is_numeric($request->objPmNotify->processedAmount)
                ? floatval($request->objPmNotify->processedAmount)
                : 0;
        }

        private function _getPANMasked(\Mobilpay_Payment_Request_Abstract $request) {
            return $request->objPmNotify->pan_masked;
        }

        private function _getErrorCode(\Mobilpay_Payment_Request_Abstract $request) {
            return $request->objPmNotify->errorCode;
        }

        private function _getErrorMessage(\Mobilpay_Payment_Request_Abstract $request) {
            return $request->objPmNotify->errorMessage;
        }

        private function _getMobilpayPaymentMessageError($errorCode) {
            $standardErrors = array(
                '16' => __('Card has a risk (i.e. stolen card)', 'wc-mobilpayments-card'), 
                '17' => __('Card number is incorrect', 'wc-mobilpayments-card'), 
                '18' => __('Closed card', 'wc-mobilpayments-card'), 
                '19' => __('Card is expired', 'wc-mobilpayments-card'), 
                '20' => __('Insufficient funds', 'wc-mobilpayments-card'), 
                '21' => __('CVV2 code incorrect', 'wc-mobilpayments-card'), 
                '22' => __('Issuer is unavailable', 'wc-mobilpayments-card'), 
                '32' => __('Amount is incorrect', 'wc-mobilpayments-card'), 
                '33' => __('Currency is incorrect', 'wc-mobilpayments-card'), 
                '34' => __('Transaction not permitted to cardholder', 'wc-mobilpayments-card'), 
                '35' => __('Transaction declined', 'wc-mobilpayments-card'), 
                '36' => __('Transaction rejected by antifraud filters', 'wc-mobilpayments-card'), 
                '37' => __('Transaction declined (breaking the law)', 'wc-mobilpayments-card'), 
                '38' => __('Transaction declined', 'wc-mobilpayments-card'), 
                '48' => __('Invalid request', 'wc-mobilpayments-card'), 
                '49' => __('Duplicate PREAUTH', 'wc-mobilpayments-card'), 
                '50' => __('Duplicate AUTH', 'wc-mobilpayments-card'), 
                '51' => __('You can only CANCEL a preauth order', 'wc-mobilpayments-card'), 
                '52' => __('You can only CONFIRM a preauth order', 'wc-mobilpayments-card'), 
                '53' => __('You can only CREDIT a confirmed order', 'wc-mobilpayments-card'), 
                '54' => __('Credit amount is higher than auth amount', 'wc-mobilpayments-card'), 
                '55' => __('Capture amount is higher than preauth amount', 'wc-mobilpayments-card'), 
                '56' => __('Duplicate request', 'wc-mobilpayments-card'), 
                '99' => __('Generic error', 'wc-mobilpayments-card')
            );
    
            return isset($standardErrors[$errorCode]) 
                ? $standardErrors[$errorCode] 
                : null;
        }

        private function _getGenericRefundOrderAdminNote($transactionId) {
            return sprintf(__('The amount you paid has been refuned. The order has been marked as refunded as well. Transaction id: %s', 'wc-mobilpayments-card'), 
                $transactionId);
        }

        private function _getGenericRefundOrderCustomerNote($transactionId) {
            return sprintf(__('The paid amount has been refuned. The order has been marked as refunded as well. Transaction id: %s', 'wc-mobilpayments-card'), 
                $transactionId);
        }

        private function _getGenericRefundOrderStatusNote() {
            return __('The paid amount has been refuned. The order has been marked as refunded as well.', 'wc-mobilpayments-card');
        }

        private function _getPartialRefundReason($transactionId) {
            return sprintf(__('Partial refund notification received from MobilPay gateway. Transaction id: %s', 'wc-mobilpayments-card'), 
                $transactionId);
        }

        private function _getGenericCancelledOrderAdminNote($transactionId) {
            return sprintf(__('The payment has been cancelled. The order has been cancelled as well. Transaction id: %s', 'wc-mobilpayments-card'), 
                $transactionId);
        }

        private function _getGenericCancelledOrderCustomerNote($transactionId) {
            return sprintf(__('Your payment has been cancelled. The order has been cancelled as well. Transaction id: %s', 'wc-mobilpayments-card'), 
                $transactionId);
        }

        private function _getGenericCancelledOrderStatusNote() {
            return __('Your payment has been cancelled. The order has been cancelled as well.', 'wc-mobilpayments-card');
        }

        private function _getGenericOnHoldOrderAdminNote($transactionId) {
            return sprintf(__('Order payment is currently being processed. The order has been placed on-hold. Transaction id: %s', 'wc-mobilpayments-card'),
                $transactionId);
        }

        private function _getGenericOnHoldOrderCustomerNote($transactionId) {
            return sprintf(__('Your payment is currently being processed. Your order has been placed on-hold. Transaction id: %s', 'wc-mobilpayments-card'),
                $transactionId);
        }

        public function _getGenericOnHoldOrderStatusNote() {
            return __('Your payment is currently being processed and the order has been placed on-hold', 'wc-mobilpayments-card');
        }

        private function _getDifferentAmountsOnHoldOrderAdminNote($transactionId, $originalAmount, $processedAmount) {
            return sprintf(__('The order has been placed on hold as the processed amount is smaller than the total order amount (%s vs. %s). Transaction id: %s', 'wc-mobilpayments-card'), 
                $originalAmount, 
                $processedAmount, 
                $transactionId);
        }
    
        private function _getDifferentAmountsOnHoldOrderCustomerNote($transactionId, $originalAmount, $processedAmount) {
            return sprintf(__('The order has been placed on hold as the processed amount is smaller than the total order amount (%s RON vs. %s RON). Transaction id: %s', 'wc-mobilpayments-card'), 
                $originalAmount, 
                $processedAmount, 
                $transactionId);
        }

        private function _getDifferentAmountsOnHoldOrderStatusNote() {
            return __('The order has been placed on hold as the processed amount is smaller than the total order amount', 'wc-mobilpayments-card');
        }
    
        private function _getFailedPaymentOrderGenericNote($transactionId, $errorCode, $errorMessage) {
            return sprintf(__('Error processing payment: %s (code: %s). Transaction id: %s', 'wc-mobilpayments-card'), 
                $errorMessage, 
                $errorCode, 
                $transactionId);
        }
    
        private function _getFailedPaymentOrderStatusNote() {
            return __('The payment has failed. See order notes for additional details', 'wc-mobilpayments-card');
        }

        private function _getGenericOrderCompletedCustomerNote($transactionId) {
            return sprintf(__('Your payment has been successfully received. Your order is now completed. Transaction id: %s', 'wc-mobilpayments-card'), 
                $transactionId);
        }

        private function _getGenericOrderCompletedAdminNote($transactionId) {
            return sprintf(__('The payment has been successfully received. The order is now completed. Transaction id: %s', 'wc-mobilpayments-card'), 
                $transactionId);
        }
    
        private function _getOrderCompletedOrderStatusNote() {
            return __('The payment has been successfully received. The order is now completed', 'wc-mobilpayments-card');
        }

        private function _getGenericOrderProcessingCustomerNote($transactionId) {
            return sprintf(__('Your payment has been successfully received. Your order is currently being processed. Transaction id: %s', 'wc-mobilpayments-card'), 
                $transactionId);
        }

        private function _getGenericOrderProcessingAdminNote($transactionId) {
            return sprintf(__('The payment has been successfully received. The order is currently being processed. Transaction id: %s', 'wc-mobilpayments-card'), 
                $transactionId);
        }
    
        private function _getOrderProcessingStatusNote() {
            return __('The payment has been successfully received. The order is currently being processed.', 'wc-mobilpayments-card');
        }

        public function getLogger() {
            return $this->_logger;
        }
    }
}