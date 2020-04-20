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

    private $_testWcOrdersOtherGateway = array();

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
        $shortcode = new Shortcodes();
        foreach ($this->_testWcOrdersOtherGateway as $orderId => $order) {
            $_GET['order_id'] = $orderId;
            $content = $shortcode->displayMobilpayOrderStatus(array());
            $this->assertEmpty($content);
        }
    }

    public function test_tryRenderShortcode_forInValidOrder_nonExistingOrderId() {
        $orderIds = array();
        $shortcode = new Shortcodes();
        for ($i = 0; $i < 10; $i ++) {
            $orderId = $this->_generateRandomNewOrderId($orderIds);
            $orderIds[] = $orderId;
            
            $_GET['order_id'] = $orderId;
            $content = $shortcode->displayMobilpayOrderStatus(array());
            $this->assertEmpty($content);
        }
    }

    public function test_tryRenderShortcode_forInValidOrder_emptyOrderId() {
        $shortcode = new Shortcodes();
        foreach (array(null, '', 0) as $orderId) {
            $_GET['order_id'] = $orderId;
            $content = $shortcode->displayMobilpayOrderStatus(array());
            $this->assertEmpty($content);
        }
    }

    private function _assertShortdcodeStructure($content, WC_Order $order) {
        $content = trim($content);

        $this->assertNotEmpty($content);

        $this->assertStringStartsWith('<div class="lvdwcmc-mobilpay-return-container order-status-' . $order->get_status() . '">', 
            $content);
        $this->assertStringEndsWith('</div>', 
            $content);

        if ($order->has_status(array('cancelled', 'failed'))) {
            $this->assertContains('<p>' . esc_html__('Your payment could not be processed or has been cancelled.', 'livepayments-mp-wc') . '</p>', 
                $content, 
                true);
        } elseif ($order->has_status('on-hold')) {
            $this->assertContains('<p>' . esc_html__('Your payment is currently being processed.', 'livepayments-mp-wc') . '</p>', 
                $content, 
                true);
            $this->assertContains('<p>' . esc_html__('Order Id', 'livepayments-mp-wc') . ': <strong>' . $order->get_id() . '</strong></p>', 
                $content, 
                true);
            $this->assertContains('<p>' . esc_html__('Detailed order status', 'livepayments-mp-wc') . ': <strong>' . wc_get_order_status_name($order->get_status()) . '</strong></p>', 
                $content, 
                true);
        } else {
            $this->assertContains('<p>' . esc_html__('We have successfully received your payment', 'livepayments-mp-wc') . '</p>', 
                $content, 
                true);
            $this->assertContains('<p>' . esc_html__('Order Id', 'livepayments-mp-wc') . ': <strong>' . $order->get_id() . '</strong></p>', 
                $content, 
                true);
            $this->assertContains('<p>' . esc_html__('Detailed order status', 'livepayments-mp-wc') . ': <strong>' . wc_get_order_status_name($order->get_status()) . '</strong></p>', 
                $content, 
                true);
        }
    }

    private function _initTestData() {
        $faker = self::_getFaker();
        foreach ($this->_getWcOrderStatuses() as $status) {
            $order = $this->_generateRandomWcOrder($status);
            if (!is_wp_error($order)) {
                $this->_testWcOrders[$order->get_id()] = $order;
            } else {
                $this->_writeLine($order->get_error_message());
            }
        }

        foreach ($this->_getWcOrderStatuses() as $status) {
            $order = $this->_generateRandomWcOrder($status, $faker->randomElement(array('stripe', 'bacs')));
            if (!is_wp_error($order)) {
                $this->_testWcOrdersOtherGateway[$order->get_id()] = $order;
            } else {
                $this->_writeLine($order->get_error_message());
            }
        }
    }

    private function _generateRandomNewOrderId($excludeAdditionalIds = array()) {
        $excludeIds = array_merge(
            array_keys($this->_testWcOrdersOtherGateway),
            array_keys($this->_testWcOrders)
        );
        
        if (!empty($excludeAdditionalIds) && is_array($excludeAdditionalIds)) {
            $excludeIds = array_merge($excludeAdditionalIds);
        }

        $faker = self::_getFaker();
        
        $max = !empty($excludeIds) 
            ? max($excludeIds) 
            : 0;

        $orderId = $faker->numberBetween($max + 1, $max + 1000);
        return $orderId;
    }

    private function _cleanupTestData() {
        $this->_truncateAllWcOrderData();
        $this->_testWcOrders = array();
        $this->_testWcOrdersOtherGateway = array();
    }
}