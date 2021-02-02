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

class MobilpayTransactionProcessingTester {
    private $_id;

    /**
     * @var MobilpayTransaction
     */
    private $_mobilpayTransaction;

    /**
     * @var MobilpayTransactionFactory
     */
    private $_mobilpayTransactionFactory;

    private $_initialStatus;

    private $_initialProcessedAmount;

    public function __construct($descriptor) {
        $this->_mobilpayTransactionFactory = 
            new MobilpayTransactionFactory();

        if ($descriptor instanceof MobilpayTransaction) {
            $mobilpayTransaction = $descriptor;
        } else if ($descriptor instanceof \WC_Order) {
            $mobilpayTransaction = $this->_mobilpayTransactionFactory->existingFromOrder($descriptor->get_id());
        } else {
            $mobilpayTransaction = $this->_mobilpayTransactionFactory->existingFromOrder($descriptor);
        }

        if (!empty($mobilpayTransaction)) {
            $this->_id = $mobilpayTransaction->getId();
            $this->_mobilpayTransaction = $mobilpayTransaction;
            $this->_initialStatus = $mobilpayTransaction->getStatus();
            $this->_initialProcessedAmount = $mobilpayTransaction->getProcessedAmount();            
        }
    }

    public function refresh() {
        if ($this->transactionExists()) {
            $this->_initialStatus = $this->_mobilpayTransaction->getStatus();
            $this->_initialProcessedAmount = $this->_mobilpayTransaction->getProcessedAmount();
            $this->_mobilpayTransaction = $this->_mobilpayTransactionFactory->fromTransactionId($this->_id);
        }
    }

    public function transactionExists() {
        return $this->_mobilpayTransaction != null;
    }

    public function transactionMatchesPaymentResponse(\Mobilpay_Payment_Request_Card $request) {
        return $this->transactionExists() 
            && ($request->objPmNotify->pan_masked 
                == $this->_mobilpayTransaction->getPANMasked())
            && ($request->objPmNotify->purchaseId 
                == $this->_mobilpayTransaction->getProviderTransactionId())
            && ($request->objPmNotify->processedAmount + $this->_initialProcessedAmount 
                == $this->_mobilpayTransaction->getProcessedAmount());
    }

    public function transactionIsConfirmed() {
        return $this->transactionExists()
            && $this->_mobilpayTransaction->getStatus() == MobilpayTransaction::STATUS_CONFIRMED
            && $this->transactionHasNoError();
    }

    public function transactionHasNoError() {
        return $this->transactionExists() 
            && empty($this->_mobilpayTransaction->getErrorCode())
            && empty($this->_mobilpayTransaction->getErrorMessage());
    }

    public function isTransactionAmountCompletelyProcessed() {
        return $this->transactionExists() 
            && $this->_mobilpayTransaction->isAmountCompletelyProcessed();
    }

    public function getTransaction() {
        return $this->_mobilpayTransaction;
    }
}