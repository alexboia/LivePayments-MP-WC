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

class WcOrderProcessingTester {
    private $_id;
    
    /**
     * @var WC_Order
     */
    private $_order;

    private $_initialStatus;

    /**
     * @var WcOrderNotesTester
     */
    private $_orderNotesTester;

    public function __construct($descriptor) {
        if ($descriptor instanceof \WC_Order) {
            $order = $descriptor;
        } else {
            $order = wc_get_order($descriptor);
        }

        if (!empty($order)) {
            $this->_id = $order->get_id();
            $this->_order = $order;
            $this->_initialStatus = $order->get_status();
            $this->_orderNotesTester = new WcOrderNotesTester($order);
        }
    }

    public function refresh() {
        if ($this->orderExists()) {
            wp_cache_flush();
            wc_delete_shop_order_transients($this->_id);
            $this->_initialStatus = $this->_order->get_status();
            $this->_order = wc_get_order($this->_id);
        }
    }

    public function orderExists() {
        return !empty($this->_order);
    }

    public function orderHadStatus($status) {
        return $this->orderExists() && $this->_initialStatus == $status;
    }

    public function orderHasStatus($status) {
        return $this->orderExists() && $this->_order->has_status($status);
    }

    public function currentInternalOrderNotesCountDiffersBy($diff) {
        return $this->orderExists() && $this->_orderNotesTester->currentInternalOrderNotesCountDiffersBy($diff);
    }

    public function currentCustomerOrderNotesCountDiffersBy($diff) {
        return $this->orderExists() && $this->_orderNotesTester->currentCustomerOrderNotesCountDiffersBy($diff);
    }

    public function getOrder() {
        return $this->_order;
    }

    public function getOrderStatus() {
        return $this->orderExists() ? $this->_order->get_status() : '';
    }
}