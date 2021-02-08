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
            $this->_env = lvdwcmc_get_env();
            $this->_logger = wc_get_logger();

            if (func_num_args() == 1 && (func_get_arg(0) instanceof MobilpayTransactionFactory)) {
                $this->_transactionFactory = func_get_arg(0);
            } else {
                $this->_transactionFactory = new MobilpayTransactionFactory();
            }

            add_filter('woocommerce_order_fully_refunded_status', 
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
                // WC marks the order as refunded when a refund is created in such a way
                //   that the sum of all refunds for that order equals the order amount.
                // However, this is not what we want: we want control over changing the order status,
                //  so that we may set our own comments.
                $this->logDebug('Supressing order refund status update.', $context);
                $status = null;
            }

            return $status;
        }

        public function processPaymentInitialized(\WC_Order $order, \Mobilpay_Payment_Request_Abstract $request) {
            $transaction = $this->_transactionFactory->newFromOrder($order);

            if ($transaction != null) {
                /**
                 * Fires after the payment transaction has been successfully 
                 *  registered and initialized for a given order
                 * 
                 * @hook lvdwcmc_payment_initialized
                 * 
                 * @param \WC_Order $order The target order
                 * @param \LvdWcMc\MobilpayTransaction $transaction The corresponding payment transaction
                 */
                do_action('lvdwcmc_order_payment_initialized', 
                    $order, 
                    $transaction);

                $result = MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK;
            } else {
                $result = MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_APPLICATION;
            }

            return $result;
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

                    /**
                     * Fires when the gateway sends a payment confirmation notification
                     *  and that notification is successfully processed.
                     * 
                     * @hook lvdwcmc_order_payment_confirmed
                     * 
                     * @param \WC_Order $order The target order
                     * @param \LvdWcMc\MobilpayTransaction $transaction The corresponding payment transaction
                     * @param array $args Additional arguments that establish the context of the payment completion notification
                     */
                    do_action('lvdwcmc_order_payment_confirmed', 
                        $order, 
                        $transaction, 
                        array(
                            'transactionId' => $transactionId,
                            'orderAction' => $order->get_status(),
                            'originalAmount' => $originalAmount,
                            'processedAmount' => $processedAmount,
                            'isAmountCompletelyProcessed' => $transaction->isAmountCompletelyProcessed(),
                            'panMasked' => $panMasked
                        ));
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

                    /**
                     * Fires when the gateway sends a payment failure notification
                     *  and that notification is successfully processed.
                     * 
                     * @hook lvdwcmc_order_payment_failed
                     * 
                     * @param \WC_Order $order The target order
                     * @param \LvdWcMc\MobilpayTransaction $transaction The corresponding payment transaction
                     * @param array $args Additional arguments that establish the context of the payment failure notification
                     */
                    do_action('lvdwcmc_order_payment_failed', 
                        $order, 
                        $transaction, 
                        array(
                            'transactionId' => $transactionId,
                            'orderAction' => $order->get_status(),
                            'errorCode' => $errorCode,
                            'errorMessage' => $errorMessage
                        ));
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

                    /**
                     * Fires when the gateway sends a payment cancellation notification
                     *  and that notification is successfully processed.
                     * 
                     * @hook lvdwcmc_order_payment_cancelled
                     * 
                     * @param \WC_Order $order The target order
                     * @param \LvdWcMc\MobilpayTransaction $transaction The corresponding payment transaction
                     * @param array $args Additional arguments that establish the context of the payment cancellation notification
                     */
                    do_action('lvdwcmc_order_payment_cancelled', 
                        $order, 
                        $transaction, 
                        array(
                            'transactionId' => $transactionId,
                            'orderAction' => $order->get_status()
                        ));
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

                    /**
                     * Fires when the gateway sends a payment pending notification
                     *  and that notification is successfully processed.
                     * 
                     * @hook lvdwcmc_order_payment_pending
                     * 
                     * @param \WC_Order $order The target order
                     * @param \LvdWcMc\MobilpayTransaction $transaction The corresponding payment transaction
                     * @param array $args Additional arguments that establish the context of the payment pending notification
                     */
                    do_action('lvdwcmc_order_payment_pending', 
                        $order, 
                        $transaction, 
                        array(
                            'transactionId' => $transactionId,
                            'paymentAction' => $action,
                            'orderAction' => $order->get_status()
                        ));
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

                    /**
                     * Prepare refund data
                     * 
                     * @hook lvdwcmc_refund_data
                     * 
                     * @param array $refundData The current refund data
                     * @param \WC_Order $order The order for which the refund data is computed
                     * @param \LvdWcMc\MobilpayTransaction The transaction based on which the refund data is comptued
                     * 
                     * @return array The actual refund data, as returned by the filters
                     */
                    $refundData = apply_filters('lvdwcmc_refund_data', array(
                        'order_id' => $order->get_id(),
                        'amount' => min($processedAmount, $order->get_remaining_refund_amount()),
                        'reason' => $this->_getPartialRefundReason($transactionId),
                        'line_items' => array(),
                        'restock_items' => false,
                        'refund_payment' => false
                    ), $order, $transaction);

                    //Create partial refund record
                    $refund = wc_create_refund($refundData);
                    if (!is_wp_error($refund)) {
                        $this->logDebug('Created refund record with id = ' . $refund->get_id() . '.', 
                            $context);

                        if ($transaction->isAmountCompletelyProcessed()) {
                            //Do not set status or add notes more than once
                            if (!$order->has_status('refunded')) {
                                $this->logDebug('Setting order status to refunded.', 
                                    $context);

                                //See now, WooCommerce has wc_order_fully_refunded attached 
                                //  to woocommerce_order_status_refunded action hook to ensure that the order
                                //  has all the amount refunded if its status is set to <refunded>.
                                //However, the difference between ->get_total() and ->get_total_refunded() 
                                //  might be a very small negative number, the kind of difference that 
                                //  can occur even if the numbers are equal, but this function 
                                //  thinks there's a difference to be covered, so it generates a useless line.
                                //I just don't have any idea on how to tell it not to, 
                                //  so I'm just going to remove the action hook before setting the order status
                                //  and add it back after I've done my thing.
                                //Good riddance!
                                $wcActionPriority = has_action('woocommerce_order_status_refunded', 'wc_order_fully_refunded');
                                if ($wcActionPriority !== false) {
                                    remove_action('woocommerce_order_status_refunded', 
                                        'wc_order_fully_refunded', 
                                        $wcActionPriority);
                                }

                                $order->update_status('refunded', $this->_getGenericRefundOrderStatusNote());
                                $order->add_order_note($this->_getGenericRefundOrderCustomerNote($transactionId), 1);
                                $order->add_order_note($this->_getGenericRefundOrderAdminNote($transactionId), 0);

                                if ($wcActionPriority !== false) {
                                    add_action('woocommerce_order_status_refunded', 
                                        'wc_order_fully_refunded', 
                                        $wcActionPriority);
                                }
                            } else {
                                $this->logDebug('Order is already set as refunded. Skipping order status update.', 
                                    $context);
                            }
                        }

                        /**
                         * Fires when the gateway sends a payment refund/credit notification
                         *  and that notification is successfully processed.
                         * 
                         * @hook lvdwcmc_order_payment_refund
                         * 
                         * @param \WC_Order $order The target order
                         * @param \LvdWcMc\MobilpayTransaction $transaction The corresponding payment transaction
                         * @param array $args Additional arguments that establish the context of the payment refund/credit notification
                         */
                        do_action('lvdwcmc_order_payment_refund', 
                            $order, 
                            $transaction, 
                            array(
                                'refundId' => $refund->get_id(),
                                'transactionId' => $transactionId,
                                'orderAction' => $order->get_status(),
                                'originalAmount' => $originalAmount,
                                'processedAmount' => $processedAmount,
                                'isAmountCompletelyProcessed' => $transaction->isAmountCompletelyProcessed(),
                                'panMasked' => $panMasked
                            ));
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

        private function _getLoggingContext($order) {
            return array(
                'source' => MobilpayCreditCardGateway::GATEWAY_ID,
                'orderId' => ($order instanceof \WC_Order) 
                    ? $order->get_id() 
                    : intval($order)
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
                '16' => __('Card has a risk (i.e. stolen card)', 'livepayments-mp-wc'), 
                '17' => __('Card number is incorrect', 'livepayments-mp-wc'), 
                '18' => __('Closed card', 'livepayments-mp-wc'), 
                '19' => __('Card is expired', 'livepayments-mp-wc'), 
                '20' => __('Insufficient funds', 'livepayments-mp-wc'), 
                '21' => __('CVV2 code incorrect', 'livepayments-mp-wc'), 
                '22' => __('Issuer is unavailable', 'livepayments-mp-wc'), 
                '32' => __('Amount is incorrect', 'livepayments-mp-wc'), 
                '33' => __('Currency is incorrect', 'livepayments-mp-wc'), 
                '34' => __('Transaction not permitted to cardholder', 'livepayments-mp-wc'), 
                '35' => __('Transaction declined', 'livepayments-mp-wc'), 
                '36' => __('Transaction rejected by antifraud filters', 'livepayments-mp-wc'), 
                '37' => __('Transaction declined (breaking the law)', 'livepayments-mp-wc'), 
                '38' => __('Transaction declined', 'livepayments-mp-wc'), 
                '48' => __('Invalid request', 'livepayments-mp-wc'), 
                '49' => __('Duplicate PREAUTH', 'livepayments-mp-wc'), 
                '50' => __('Duplicate AUTH', 'livepayments-mp-wc'), 
                '51' => __('You can only CANCEL a preauth order', 'livepayments-mp-wc'), 
                '52' => __('You can only CONFIRM a preauth order', 'livepayments-mp-wc'), 
                '53' => __('You can only CREDIT a confirmed order', 'livepayments-mp-wc'), 
                '54' => __('Credit amount is higher than auth amount', 'livepayments-mp-wc'), 
                '55' => __('Capture amount is higher than preauth amount', 'livepayments-mp-wc'), 
                '56' => __('Duplicate request', 'livepayments-mp-wc'), 
                '99' => __('Generic error', 'livepayments-mp-wc')
            );
    
            return isset($standardErrors[$errorCode]) 
                ? $standardErrors[$errorCode] 
                : null;
        }

        private function _getGenericRefundOrderAdminNote($transactionId) {
            return sprintf(__('The amount you paid has been refuned. The order has been marked as refunded as well. Transaction id: %s', 'livepayments-mp-wc'), 
                $transactionId);
        }

        private function _getGenericRefundOrderCustomerNote($transactionId) {
            return sprintf(__('The paid amount has been refuned. The order has been marked as refunded as well. Transaction id: %s', 'livepayments-mp-wc'), 
                $transactionId);
        }

        private function _getGenericRefundOrderStatusNote() {
            return __('The paid amount has been refuned. The order has been marked as refunded as well.', 'livepayments-mp-wc');
        }

        private function _getPartialRefundReason($transactionId) {
            return sprintf(__('Partial refund notification received from MobilPay gateway. Transaction id: %s', 'livepayments-mp-wc'), 
                $transactionId);
        }

        private function _getGenericCancelledOrderAdminNote($transactionId) {
            return sprintf(__('The payment has been cancelled. The order has been cancelled as well. Transaction id: %s', 'livepayments-mp-wc'), 
                $transactionId);
        }

        private function _getGenericCancelledOrderCustomerNote($transactionId) {
            return sprintf(__('Your payment has been cancelled. The order has been cancelled as well. Transaction id: %s', 'livepayments-mp-wc'), 
                $transactionId);
        }

        private function _getGenericCancelledOrderStatusNote() {
            return __('Your payment has been cancelled. The order has been cancelled as well.', 'livepayments-mp-wc');
        }

        private function _getGenericOnHoldOrderAdminNote($transactionId) {
            return sprintf(__('Order payment is currently being processed. The order has been placed on-hold. Transaction id: %s', 'livepayments-mp-wc'),
                $transactionId);
        }

        private function _getGenericOnHoldOrderCustomerNote($transactionId) {
            return sprintf(__('Your payment is currently being processed. Your order has been placed on-hold. Transaction id: %s', 'livepayments-mp-wc'),
                $transactionId);
        }

        public function _getGenericOnHoldOrderStatusNote() {
            return __('Your payment is currently being processed and the order has been placed on-hold', 'livepayments-mp-wc');
        }

        private function _getDifferentAmountsOnHoldOrderAdminNote($transactionId, $originalAmount, $processedAmount) {
            return sprintf(__('The order has been placed on hold as the processed amount is smaller than the total order amount (%s vs. %s). Transaction id: %s', 'livepayments-mp-wc'), 
                $originalAmount, 
                $processedAmount, 
                $transactionId);
        }
    
        private function _getDifferentAmountsOnHoldOrderCustomerNote($transactionId, $originalAmount, $processedAmount) {
            return sprintf(__('The order has been placed on hold as the processed amount is smaller than the total order amount (%s RON vs. %s RON). Transaction id: %s', 'livepayments-mp-wc'), 
                $originalAmount, 
                $processedAmount, 
                $transactionId);
        }

        private function _getDifferentAmountsOnHoldOrderStatusNote() {
            return __('The order has been placed on hold as the processed amount is smaller than the total order amount', 'livepayments-mp-wc');
        }
    
        private function _getFailedPaymentOrderGenericNote($transactionId, $errorCode, $errorMessage) {
            return sprintf(__('Error processing payment: %s (code: %s). Transaction id: %s', 'livepayments-mp-wc'), 
                $errorMessage, 
                $errorCode, 
                $transactionId);
        }
    
        private function _getFailedPaymentOrderStatusNote() {
            return __('The payment has failed. See order notes for additional details', 'livepayments-mp-wc');
        }

        private function _getGenericOrderCompletedCustomerNote($transactionId) {
            return sprintf(__('Your payment has been successfully received. Your order is now completed. Transaction id: %s', 'livepayments-mp-wc'), 
                $transactionId);
        }

        private function _getGenericOrderCompletedAdminNote($transactionId) {
            return sprintf(__('The payment has been successfully received. The order is now completed. Transaction id: %s', 'livepayments-mp-wc'), 
                $transactionId);
        }
    
        private function _getOrderCompletedOrderStatusNote() {
            return __('The payment has been successfully received. The order is now completed', 'livepayments-mp-wc');
        }

        private function _getGenericOrderProcessingCustomerNote($transactionId) {
            return sprintf(__('Your payment has been successfully received. Your order is currently being processed. Transaction id: %s', 'livepayments-mp-wc'), 
                $transactionId);
        }

        private function _getGenericOrderProcessingAdminNote($transactionId) {
            return sprintf(__('The payment has been successfully received. The order is currently being processed. Transaction id: %s', 'livepayments-mp-wc'), 
                $transactionId);
        }
    
        private function _getOrderProcessingStatusNote() {
            return __('The payment has been successfully received. The order is currently being processed.', 'livepayments-mp-wc');
        }

        public function getLogger() {
            return $this->_logger;
        }
    }
}