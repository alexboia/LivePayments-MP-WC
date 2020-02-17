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

    defined('LVD_WCMC_LOADED') or die;
?>

<h2 class="woocommerce-order-details__title"><?php echo __('Payment transaction details', LVD_WCMC_TEXT_DOMAIN); ?></h2>
<table class="woocommerce-table woocommerce-table--mobilpay-transaction-details shop_table order_details transaction_details">
    <tbody>
        <tr>
            <th scope="row"><?php echo __('Transaction Id', LVD_WCMC_TEXT_DOMAIN); ?>:</th>
            <td><?php echo $data->providerTransactionId; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php echo __('Transaction status', LVD_WCMC_TEXT_DOMAIN); ?>:</th>
            <td><?php echo $data->status; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php echo __('Card number', LVD_WCMC_TEXT_DOMAIN); ?>:</th>
            <td><?php echo $data->panMasked; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php echo __('Original amount', LVD_WCMC_TEXT_DOMAIN); ?>:</th>
            <td><?php echo $data->amount; ?> <?php echo $data->currency; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php echo __('Actually processed amount', LVD_WCMC_TEXT_DOMAIN); ?>:</th>
            <td><?php echo $data->processedAmount; ?> <?php echo $data->currency; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php echo __('Date initiated', LVD_WCMC_TEXT_DOMAIN); ?>:</th>
            <td><?php echo $data->timestampInitiated; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php echo __('Date of last activity', LVD_WCMC_TEXT_DOMAIN); ?>:</th>
            <td><?php echo $data->timestampLastUpdated; ?></td>
        </tr>
        <?php if (!empty($data->errorCode)): ?>
            <tr>
                <th scope="row"><?php echo __('Transaction error code', LVD_WCMC_TEXT_DOMAIN); ?>:</th>
                <td><?php echo $data->errorCode; ?></td>
            </tr>
            <tr>
                <th scope="row"><?php echo __('Transaction error message', LVD_WCMC_TEXT_DOMAIN); ?>:</th>
                <td><?php echo $data->errorMessage; ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>