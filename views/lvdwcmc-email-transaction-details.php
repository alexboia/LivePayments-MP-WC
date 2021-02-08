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
    defined('LVD_WCMC_LOADED') or die;
?>

<section class="woocommerce-mobilpay-transaction-details" style="margin-bottom: 40px;">
    <h2 class="woocommerce-mobilpay-transaction-details-heading"><?php echo esc_html__('Card payment transaction details', 'livepayments-mp-wc'); ?></h2>

    <?php 
        /**
         * Fires before the core payment transaction details 
         *  are rendered in the e-mail notification send to the user
         *  when the order status changes.
         * 
         * @hook lvdwcmc_before_email_transaction_details
         * 
         * @param \stdClass $data The view model that contains the data required to render any additional details
         */
        do_action('lvdwcmc_before_email_transaction_details', $data);
    ?>

    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
        <tr>
            <td class="td" style="text-align:left; font-weight: bold;"><?php echo esc_html__('Transaction Id', 'livepayments-mp-wc') ?></td>
            <td class="td" style="text-align:left"><?php echo esc_html($data->mobilpayTransactionId); ?></td>
        </tr>
        <tr>
            <td class="td" style="text-align:left; font-weight: bold;"><?php echo esc_html__('Card number', 'livepayments-mp-wc') ?></td>
            <td class="td" style="text-align:left"><?php echo esc_html($data->panMasked); ?></td>
        </tr>
        <?php if (!empty($data->clientIpAddress)): ?>
            <tr>
                <td class="td" style="text-align:left; font-weight: bold;"><?php echo esc_html__('Client IP address', 'livepayments-mp-wc') ?></td>
                <td class="td" style="text-align:left"><?php echo esc_html($data->clientIpAddress); ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <?php 
        /**
         * Fires after the core payment transaction details 
         *  are rendered in the e-mail notification send to the user
         *  when the order status changes.
         * 
         * @hook lvdwcmc_after_email_transaction_details
         * 
         * @param \stdClass $data The view model that contains the data required to render any additional details
         */
        do_action('lvdwcmc_after_email_transaction_details', $data);
    ?>
</section>