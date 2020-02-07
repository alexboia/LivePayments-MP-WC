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
    class MobilpayCardPaymentProcessor {
        use LoggingExtensions;

        /**
         * @var \LvdWcMc\Env Reference to the environment object
         */
        private $_env;

        /**
         * @var WC_Logger The logger instance used by this processor
         */
        private $_logger = null;

        public function __construct(Env $env) {
            $this->_env = $env;
            $this->_logger = wc_get_logger();
        }

        public function processConfirmedPaymentResponse(\WC_Order $customerOrder, \Mobilpay_Payment_Request_Abstract $request) {
            $context = array(
                'source' => __METHOD__,
                'orderId' => $customerOrder->get_id()
            );

            $this->logDebug('Begin processing confirmed payment response for order...', 
                $context);

            //If the order has already been processed, 
            //  return successful result
            if (!$this->_isGatewayResponseProcessableForOrder($customerOrder)) {
                $this->logDebug('The order has already been processed', $context);
                return MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK;
            }

            $transactionId = $request->objPmNotify->purchaseId;
            $originalAmount = $request->objPmNotify->originalAmount;
            $processedAmount = !empty($request->objPmNotify->processedAmount) 
                ? $request->objPmNotify->processedAmount 
                : 0;

            $this->logDebug(sprintf('Processed amount is %s. Original amount is %s', $processedAmount, $originalAmount), 
                $context);
    
            if ($processedAmount >= $originalAmount) {
                $this->logDebug('Processed amount OK. Completing order...', $context);

                if (!$customerOrder->needs_processing()) {
                    $customerOrder->update_status('completed', $this->_getOrderCompletedOrderStatusNote());
                    $customerOrder->add_order_note($this->_getGenericOrderCompletedCustomerNote($transactionId), 1);
                    $customerOrder->add_order_note($this->_getGenericOrderCompletedAdminNote($transactionId), 0);
                } else {
                    $customerOrder->update_status('processing', $this->_getOrderProcessingStatusNote());
                    $customerOrder->add_order_note($this->_getGenericOrderProcessingCustomerNote($transactionId), 1);
                    $customerOrder->add_order_note($this->_getGenericOrderProcessingAdminNote($transactionId), 0);
                }

                wc_reduce_stock_levels($customerOrder->get_id());
            } else {
                $this->logDebug('Processed amount lower than original amount. Placing order on hold...', $context);

                $customerOrder->update_status('on-hold', $this->_getDifferentAmountsOnHoldOrderStatusNote());

                $customerOrder->add_order_note($this->_getDifferentAmountsOnHoldOrderCustomerNote($transactionId, 
                    $originalAmount, 
                    $processedAmount), 1);
                $customerOrder->add_order_note($this->_getDifferentAmountsOnHoldOrderAdminNote($transactionId, 
                    $originalAmount, 
                    $processedAmount), 0);
            }

            $this->logDebug('Done processing confirmed payment response for order', 
                $context);
    
            return MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK;
        }

        public function processFailedPaymentResponse(\WC_Order $customerOrder, \Mobilpay_Payment_Request_Abstract $request) {
            $context = array(
                'source' => __METHOD__,
                'orderId' => $customerOrder->get_id()
            );

            $this->logDebug('Begin processing failed payment response for order...', 
                $context);

            $transactionId = $request->objPmNotify->purchaseId;
            $customerOrder->update_status('failed', $this->_getFailedPaymentOrderStatusNote());
    
            $errorCode = $request->objPmNotify->errorCode;
            $errorMessage = $request->objPmNotify->errorMessage;

            if (empty($errorMessage)) {
                $errorMessage = $this->_getMobilpayPaymentMessageError($errorCode);
            }
    
            $customerOrder->add_order_note($this->_getFailedPaymentOrderGenericNote($transactionId, 
                $errorCode, 
                $errorMessage), 1);
    
            $customerOrder->add_order_note($this->_getFailedPaymentOrderGenericNote($transactionId, 
                $errorCode, 
                $errorMessage), 0);

            $this->logDebug('Done processing failed payment response for order', 
                $context);
    
            return MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK;
        }

        public function processPaymentCancelledResponse(\WC_Order $customerOrder, \Mobilpay_Payment_Request_Abstract $request) {
            $context = array(
                'source' => __METHOD__,
                'orderId' => $customerOrder->get_id()
            );

            $this->logDebug('Begin processing canceled payment response for order...', 
                $context);

            $transactionId = $request->objPmNotify->purchaseId;

            $customerOrder->update_status('cancelled', $this->_getGenericCancelledOrderStatusNote());
            $customerOrder->add_order_note($this->_getGenericCancelledOrderCustomerNote($transactionId), 1);
            $customerOrder->add_order_note($this->_getGenericCancelledOrderAdminNote($transactionId), 0);

            $this->logDebug('Done processing canceled payment response for order', 
                $context);

            return MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK;
        }

        public function processPendingPaymentResponse(\WC_Order $customerOrder, \Mobilpay_Payment_Request_Abstract $request) {
            $context = array(
                'source' => __METHOD__,
                'orderId' => $customerOrder->get_id()
            );

            $this->logDebug('Begin processing pending payment response for order...', 
                $context);
            
            $transactionId = $request->objPmNotify->purchaseId;

            $customerOrder->update_status('on-hold', $this->_getGenericOnHoldOrderStatusNote());
            $customerOrder->add_order_note($this->_getGenericOnHoldOrderCustomerNote($transactionId), 1);
            $customerOrder->add_order_note($this->_getGenericOnHoldOrderAdminNote($transactionId), 0);

            $this->logDebug('Done processing pending payment response for order', 
                $context);

            return MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK;
        }

        public  function processCreditPaymentResponse(\WC_Order $customerOrder, \Mobilpay_Payment_Request_Abstract $request) {
            $context = array(
                'source' => __METHOD__,
                'orderId' => $customerOrder->get_id()
            );

            $this->logDebug('Begin processing credit payment response for order...', 
                $context);

            $transactionId = $request->objPmNotify->purchaseId;

            $customerOrder->update_status('refunded', $this->_getGenericRefundOrderStatusNote());
            $customerOrder->add_order_note($this->_getGenericRefundOrderCustomerNote($transactionId), 1);
            $customerOrder->add_order_note($this->_getGenericRefundOrderAdminNote($transactionId), 0);

            $this->logDebug('Done processing credit payment response for order', 
                $context);

            return MobilpayCreditCardGateway::GATEWAY_PROCESS_RESPONSE_ERR_OK;
        }

        private function _getMobilpayPaymentMessageError($errorCode) {
            $standardErrors = array(
                '16' => $this->__('Card has a risk (i.e. stolen card)'), 
                '17' => $this->__('Card number is incorrect'), 
                '18' => $this->__('Closed card'), 
                '19' => $this->__('Card is expired'), 
                '20' => $this->__('Insufficient funds'), 
                '21' => $this->__('CVV2 code incorrect'), 
                '22' => $this->__('Issuer is unavailable'), 
                '32' => $this->__('Amount is incorrect'), 
                '33' => $this->__('Currency is incorrect'), 
                '34' => $this->__('Transaction not permitted to cardholder'), 
                '35' => $this->__('Transaction declined'), 
                '36' => $this->__('Transaction rejected by antifraud filters'), 
                '37' => $this->__('Transaction declined (breaking the law)'), 
                '38' => $this->__('Transaction declined'), 
                '48' => $this->__('Invalid request'), 
                '49' => $this->__('Duplicate PREAUTH'), 
                '50' => $this->__('Duplicate AUTH'), 
                '51' => $this->__('You can only CANCEL a preauth order'), 
                '52' => $this->__('You can only CONFIRM a preauth order'), 
                '53' => $this->__('You can only CREDIT a confirmed order'), 
                '54' => $this->__('Credit amount is higher than auth amount'), 
                '55' => $this->__('Capture amount is higher than preauth amount'), 
                '56' => $this->__('Duplicate request'), 
                '99' => $this->__('Generic error')
            );
    
            return isset($standardErrors[$errorCode]) 
                ? $standardErrors[$errorCode] 
                : null;
        }

        private function _isGatewayResponseProcessableForOrder(\WC_Order $customerOrder) {
            $status = $customerOrder->get_status();
            return $status != 'completed' 
                && $status != 'processing';
        }

        public function sendErrorResponse($type, $code, $message) {
            header('Content-type: application/xml');
            echo '<?xml version="1.0" encoding="utf-8"?>';
            echo '<crc error_type="' . $type . '" error_code="' . $code . '">' . $message . '</crc>';
            exit;
        }
    
        public function sendSuccessResponse($crc) {
            header('Content-type: application/xml');
            echo '<?xml version="1.0" encoding="utf-8"?>';
            echo '<crc>' . $crc . '</crc>';
            exit;
        }

        private function __($text) {
            return __($text, lvdwcmc_plugin()->getTextDomain());
        }

        private function _getGenericRefundOrderAdminNote($transactionId) {
            return sprintf($this->__('The amount you paid has been refuned. The order has been marked as refunded as well. Transaction id: %s'), 
                $transactionId);
        }

        private function _getGenericRefundOrderCustomerNote($transactionId) {
            return sprintf($this->__('The paid amount has been refuned. The order has been marked as refunded as well. Transaction id: %s'), 
                $transactionId);
        }

        private function _getGenericRefundOrderStatusNote() {
            return $this->__('The paid amount has been refuned. The order has been marked as refunded as well.');
        }

        private function _getGenericCancelledOrderAdminNote($transactionId) {
            return sprintf($this->__('The payment has been cancelled. The order has been cancelled as well. Transaction id: %s'), 
                $transactionId);
        }

        private function _getGenericCancelledOrderCustomerNote($transactionId) {
            return sprintf($this->__('Your payment has been cancelled. The order has been cancelled as well. Transaction id: %s'), 
                $transactionId);
        }

        private function _getGenericCancelledOrderStatusNote() {
            return $this->__('Your payment has been cancelled. The order has been cancelled as well.');
        }

        private function _getGenericOnHoldOrderAdminNote($transactionId) {
            return sprintf($this->__('Order payment is currently being processed. The order has been placed on-hold. Transaction id: %s'),
                $transactionId);
        }

        private function _getGenericOnHoldOrderCustomerNote($transactionId) {
            return sprintf($this->__('Your payment is currently being processed. Your order has been placed on-hold. Transaction id: %s'),
                $transactionId);
        }

        public function _getGenericOnHoldOrderStatusNote() {
            return $this->__('Your payment is currently being processed and the order has been placed on-hold');
        }

        private function _getDifferentAmountsOnHoldOrderAdminNote($transactionId, $originalAmount, $processedAmount) {
            return sprintf($this->__('The order has been placed on hold as the processed amount is smaller than the total order amount (%s vs. %s). Transaction id: %s'), 
                $originalAmount, 
                $processedAmount, 
                $transactionId);
        }
    
        private function _getDifferentAmountsOnHoldOrderCustomerNote($transactionId, $originalAmount, $processedAmount) {
            return sprintf($this->__('The order has been placed on hold as the processed amount is smaller than the total order amount (%s RON vs. %s RON). Transaction id: %s'), 
                $originalAmount, 
                $processedAmount, 
                $transactionId);
        }

        private function _getDifferentAmountsOnHoldOrderStatusNote() {
            return $this->__('The order has been placed on hold as the processed amount is smaller than the total order amount');
        }
    
        private function _getFailedPaymentOrderGenericNote($transactionId, $errorCode, $errorMessage) {
            return sprintf($this->__('Error processing payment: %s (code: %s). Transaction id: %s'), 
                $errorMessage, 
                $errorCode, 
                $transactionId);
        }
    
        private function _getFailedPaymentOrderStatusNote() {
            return $this->__('The payment has failed. See order notes for additional details');
        }

        private function _getGenericOrderCompletedCustomerNote($transactionId) {
            return sprintf($this->__('Your payment has been successfully received. Your order is now completed. Transaction id: %s'), $transactionId);
        }

        private function _getGenericOrderCompletedAdminNote($transactionId) {
            return sprintf($this->__('The payment has been successfully received. The order is now completed. Transaction id: %s'), $transactionId);
        }
    
        private function _getOrderCompletedOrderStatusNote() {
            return $this->__('The payment has been successfully received. The order is now completed');
        }

        private function _getGenericOrderProcessingCustomerNote($transactionId) {
            return sprintf($this->__('Your payment has been successfully received. Your order is currently being processed. Transaction id: %s'), 
                $transactionId);
        }

        private function _getGenericOrderProcessingAdminNote($transactionId) {
            return sprintf($this->__('The payment has been successfully received. The order is currently being processed. Transaction id: %s'), 
                $transactionId);
        }
    
        private function _getOrderProcessingStatusNote() {
            return $this->__('The payment has been successfully received. The order is currently being processed.');
        }

        public function getLogger() {
            return $this->_logger;
        }
    }
}