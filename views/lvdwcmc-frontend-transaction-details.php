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

<h2 class="woocommerce-order-details__title"><?php echo esc_html__('Payment transaction details', 'livepayments-mp-wc'); ?></h2>

<?php 
    /**
     * Fires before the core transaction details 
     *  are rendered in the frontend order details page
     *  (user account area)
     * 
     * @hook lvdwcmc_before_frontend_transaction_details
     * 
     * @param \stdClass $data The view model
     */
    do_action('lvdwcmc_before_frontend_transaction_details', $data);
?>

<table class="woocommerce-table woocommerce-table--mobilpay-transaction-details shop_table order_details transaction_details">
    <tbody>
        <?php if (!empty($data->providerTransactionId)): ?>
            <tr>
                <th scope="row"><?php echo esc_html__('Transaction Id', 'livepayments-mp-wc'); ?>:</th>
                <td><?php echo esc_html($data->providerTransactionId); ?></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($data->status)): ?>
            <tr>
                <th scope="row"><?php echo esc_html__('Transaction status', 'livepayments-mp-wc'); ?>:</th>
                <td><?php echo esc_html($data->status); ?></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($data->panMasked)): ?>
            <tr>
                <th scope="row"><?php echo esc_html__('Card number', 'livepayments-mp-wc'); ?>:</th>
                <td><?php echo esc_html($data->panMasked); ?></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($data->amount)): ?>
            <tr>
                <th scope="row"><?php echo esc_html__('Original amount', 'livepayments-mp-wc'); ?>:</th>
                <td><?php echo esc_html($data->amount); ?> <?php echo $data->currency; ?></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($data->processedAmount)): ?>
            <tr>
                <th scope="row"><?php echo esc_html__('Actually processed amount', 'livepayments-mp-wc'); ?>:</th>
                <td><?php echo esc_html($data->processedAmount); ?> <?php echo $data->currency; ?></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($data->timestampInitiated)): ?>
            <tr>
                <th scope="row"><?php echo esc_html__('Date initiated', 'livepayments-mp-wc'); ?>:</th>
                <td><?php echo esc_html($data->timestampInitiated); ?></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($data->timestampLastUpdated)): ?>
            <tr>
                <th scope="row"><?php echo esc_html__('Date of last activity', 'livepayments-mp-wc'); ?>:</th>
                <td><?php echo esc_html($data->timestampLastUpdated); ?></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($data->errorCode)): ?>
            <tr>
                <th scope="row"><?php echo esc_html__('Transaction error code', 'livepayments-mp-wc'); ?>:</th>
                <td><?php echo esc_html($data->errorCode); ?></td>
            </tr>
            <?php if (!empty($data->errorMessage)): ?>
                <tr>
                    <th scope="row"><?php echo esc_html__('Transaction error message', 'livepayments-mp-wc'); ?>:</th>
                    <td><?php echo esc_html($data->errorMessage); ?></td>
                </tr>
            <?php endif; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php 
    /**
     * Fires after the core transaction details 
     *  are rendered in the frontend order details page
     *  (user account area)
     * 
     * @hook lvdwcmc_after_frontend_transaction_details
     * 
     * @param \stdClass $data The view model
     */
    do_action('lvdwcmc_after_frontend_transaction_details', $data);
?>