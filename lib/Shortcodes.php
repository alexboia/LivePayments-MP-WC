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
    class Shortcodes {
        /**
         * @var \LvdWcMc\Env Reference to the environment object
         */
        private $_env = null;

        public function __construct() {
            $this->_env = lvdwcmc_get_env();
        }

        public function displayMobilpayOrderStatus($attributes) {
			$content = null;
            if (isset($_GET['order_id'])) {
                $orderId = intval($_GET['order_id']);
                if ($orderId > 0 
                    && ($order = wc_get_order($orderId)) instanceof \WC_Order 
                    && MobilpayCreditCardGateway::matchesGatewayId($order->get_payment_method())) {

                    $data = new \stdClass();
                    $data->orderId = $orderId;
                    $data->orderStatus = $order->get_status();
                    $data->order = $order;

					ob_start();
                    require $this->_env->getViewFilePath('lvdwcmc-payment-status.php');
                    $content = ob_get_clean();
                    if (!$content) {
                        $content = null;
                    }
                }
            }
			return $content;
        }
    }
}