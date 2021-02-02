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

trait MobilpayCardRequestTestHelpers {
    use GenericTestHelpers;

    /**
     * @return \Mobilpay_Payment_Request_Card 
     */
    protected function _generateCardPaymentRequestFromOrder(\WC_Order $order) {
        $faker = $this->_getFaker();

        $paymentRequest = new \Mobilpay_Payment_Request_Card();
        $paymentRequest->signature = $this->_generateMobilpayAccountId();
        $paymentRequest->orderId = $faker->uuid;
        
        $paymentRequest->confirmUrl = $faker->url;
        $paymentRequest->returnUrl = $faker->url;

        $paymentRequest->invoice = new \Mobilpay_Payment_Invoice();
        $paymentRequest->invoice->currency = $order->get_currency();
        $paymentRequest->invoice->amount = sprintf('%.2f', $order->get_total());
        $paymentRequest->invoice->details = $faker->text();

        $paymentRequest->params = array(
            '_lvdwcmc_order_id' => $order->get_id(),
            '_lvdwcmc_customer_id' => $order->get_customer_id(),
            '_lvdwcmc_customer_ip' => $order->get_customer_ip_address()
        );
        
        return $paymentRequest;
    }

    /**
     * @return \Mobilpay_Payment_Request_Card 
     */
    protected function _generateFullPaymentCompletedCardPaymentRequestFromOrder(\WC_Order $order) {
        $faker = $this->_getFaker();
        $request = $this->_generateCardPaymentRequestFromOrder($order);

        $request->objPmNotify = new \Mobilpay_Payment_Request_Notify();
        $request->objPmNotify->action = 'confirmed';
        $request->objPmNotify->originalAmount = $request->invoice->amount;
        $request->objPmNotify->processedAmount = $request->invoice->amount;
        $request->objPmNotify->pan_masked = $faker->creditCardNumber;
        $request->objPmNotify->errorCode = 0;
        $request->objPmNotify->errorMessage = '';
        $request->objPmNotify->purchaseId = $faker->uuid;

        return $request;
    }

    /**
     * @return \Mobilpay_Payment_Request_Card 
     */
    protected function _generatePartialPaymentCompletedCardPaymentRequestFromOrder(\WC_Order $order) {
        $faker = $this->_getFaker();
        $request = $this->_generateCardPaymentRequestFromOrder($order);

        $request->objPmNotify = new \Mobilpay_Payment_Request_Notify();
        $request->objPmNotify->action = 'confirmed';
        $request->objPmNotify->originalAmount = $request->invoice->amount;
        $request->objPmNotify->processedAmount = $this->_generatePartialProcessAmount($request);
        $request->objPmNotify->pan_masked = $faker->creditCardNumber;
        $request->objPmNotify->errorCode = 0;
        $request->objPmNotify->errorMessage = '';
        $request->objPmNotify->purchaseId = $faker->uuid;
    }

    private function _generatePartialProcessAmount(\Mobilpay_Payment_Request_Card $request) {
        $minProcessAmount = 0.01;
        $maxProcessAmount = round(0.90 * $request->invoice->amount, 2, 
            PHP_ROUND_HALF_DOWN);

        $faker = $this->_getFaker();
        return $faker->randomFloat(2, $minProcessAmount, $maxProcessAmount);
    }

    private function _generateMobilpayAccountId() {
        return $this->_getFaker()->uuid;
    }
}