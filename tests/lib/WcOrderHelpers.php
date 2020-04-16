<?php

use LvdWcMc\MobilpayCreditCardGateway;

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

trait WcOrderHelpers {
    use GenericTestHelpers;

    protected function _generateRandomWcOrder($status = null) {
        $order = null;
        $faker = self::_getFaker();
        $customerId = wc_create_new_customer($faker->email, 
            $faker->userName, 
            $faker->password);

        if (!is_wp_error($customerId)) {
            $orderArgs = array(
                'status' => !empty($status) ? $status : 'wc-pending',
                'customer_id' => $customerId,
                'created_via' => 'unitTests'
            );

            $order = wc_create_order($orderArgs);
            if (!is_wp_error($order)) {
                if ($order->get_id()) {
                    $order->set_total($faker->randomFloat(2, 1, PHP_FLOAT_MAX));
                    $order->set_order_key(wc_generate_order_key());
                    $order->set_payment_method(MobilpayCreditCardGateway::GATEWAY_ID);
                    $order->save();
                } else {
                    $order = new WP_Error('lvdwcmc-cant-save-new-order', 'Failed to save created order');
                }   
            }
        } else {
            $order = new WP_Error($customerId->get_error_code(), 
                $customerId->get_error_message(), 
                $customerId->get_error_data());
        }

        return $order;
    }

    protected function _truncateAllWcOrderData() {
        $db = $this->_getDb();
        $env = $this->_getEnv();

        $this->_truncateTables($db, 
            $env->getPaymentTransactionsTableName(),
            $env->getDbTablePrefix() . 'woocommerce_order_itemmeta',
            $env->getDbTablePrefix() . 'woocommerce_order_items',
            $env->getDbTablePrefix() . 'postmeta',
            $env->getDbTablePrefix() . 'posts',
            $env->getDbTablePrefix() . 'usermeta',
            $env->getDbTablePrefix() . 'users'
        );
    }

    protected function _getWcOrderStatuses() {
        return array_keys(wc_get_order_statuses());
    }
}