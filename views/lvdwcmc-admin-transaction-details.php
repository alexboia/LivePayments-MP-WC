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

<table class="lvdwcmc-admin-transaction-details-list">
    <tbody>
        <tr>
            <th scope="row"><?php echo esc_html__('Transaction Id', 'wc-mobilpayments-card'); ?>:</th>
            <td><?php echo esc_html($data->providerTransactionId); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php echo esc_html__('Transaction status', 'wc-mobilpayments-card'); ?>:</th>
            <td><?php echo esc_html($data->status); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php echo esc_html__('Card number', 'wc-mobilpayments-card'); ?>:</th>
            <td><?php echo esc_html($data->panMasked); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php echo esc_html__('Original amount', 'wc-mobilpayments-card'); ?>:</th>
            <td><?php echo esc_html($data->amount); ?> <?php echo $data->currency; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php echo esc_html__('Actually processed amount', 'wc-mobilpayments-card'); ?>:</th>
            <td><?php echo esc_html($data->processedAmount); ?> <?php echo $data->currency; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php echo esc_html__('Date initiated', 'wc-mobilpayments-card'); ?>:</th>
            <td><?php echo esc_html($data->timestampInitiated); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php echo esc_html__('Date of last activity', 'wc-mobilpayments-card'); ?>:</th>
            <td><?php echo esc_html($data->timestampLastUpdated); ?></td>
        </tr>
        <?php if (!empty($data->errorCode)): ?>
            <tr>
                <th scope="row"><?php echo esc_html__('Transaction error code', 'wc-mobilpayments-card'); ?>:</th>
                <td><?php echo esc_html($data->errorCode); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Transaction error message', 'wc-mobilpayments-card'); ?>:</th>
                <td><?php echo esc_html($data->errorMessage); ?></td>
            </tr>
        <?php endif; ?>
        <?php if (!empty($data->clientIpAddress)): ?>
            <tr>
                <th scope="row"><?php echo esc_html__('Client IP Address', 'wc-mobilpayments-card'); ?>:</th>
                <td><?php echo esc_html($data->clientIpAddress); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>