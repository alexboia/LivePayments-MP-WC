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

use \LvdWcMc\Shortcodes;

class ShortcodesTests extends WP_UnitTestCase {
    use DbTestHelpers;
    use WcOrderHelpers;

    private $_testWcOrders = array();

    public function setUp() {
        parent::setUp();
        $this->_dontReportNotices();
        $this->_initTestData();
        $this->_reportAllErrors();
    }

    public function tearDown() {
        parent::tearDown();
        $this->_cleanupTestData();
    }

    public function test_canRenderShortcode_forValidOrder_forOurGateway() {
        $shortcode = new Shortcodes();
        foreach ($this->_testWcOrders as $orderId => $order) {
            $_GET['order_id'] = $orderId;
            $content = $shortcode->displayMobilpayOrderStatus(array());
            $this->_assertShortdcodeStructure($content, $order);
        }
    }

    public function test_tryRenderShortcode_forValidOrder_forOtherGateways() {

    }

    public function test_tryRenderShortcode_forInValidOrder_nonExistingOrderId() {
        
    }

    public function test_tryRenderShortcode_forInValidOrder_emptyOrderId() {
        
    }

    private function _assertShortdcodeStructure($content, WC_Order $order) {
        $this->assertNotEmpty($content);
    }

    private function _initTestData() {
        foreach ($this->_getWcOrderStatuses() as $status) {
            $order = $this->_generateRandomWcOrder($status);
            if (!is_wp_error($order)) {
                $this->_testWcOrders[$order->get_id()] = $order;
            } else {
                $this->_writeLine($order->get_error_message());
            }
        }
    }

    private function _cleanupTestData() {
        $this->_truncateAllWcOrderData();
        $this->_testWcOrders = array();
    }
}