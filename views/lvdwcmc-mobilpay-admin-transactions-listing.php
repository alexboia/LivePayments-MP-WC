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

<div class="lvdwcmc-page-container">
    <h1><?php echo esc_html($data->pageTitle); ?></h1>

    <?php if ($data->hasTransactions): ?>
        <table class="wp-list-table widefat fixed striped posts lvdwcmc-tx-listing">
            <thead>
                <tr>
                    <th class="lvdwcmc-order-id-column"><?php echo __('Order Id', LVD_WCMC_TEXT_DOMAIN); ?></th>
                    <th><?php echo __('Date initiated', LVD_WCMC_TEXT_DOMAIN); ?></th>
                    <th><?php echo __('Status', LVD_WCMC_TEXT_DOMAIN); ?></th>
                    <th><?php echo __('Amount', LVD_WCMC_TEXT_DOMAIN); ?></th>
                    <th><?php echo __('Processed amount', LVD_WCMC_TEXT_DOMAIN); ?></th>
                    <th><?php echo __('Actions', LVD_WCMC_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data->transactions as $tx):  ?>
                    <tr>
                        <td class="lvdwcmc-order-id-column">
                            <a href="<?php echo esc_url($tx['tx_admin_details_link']); ?>" target="_blank">
                                <?php echo $tx['tx_title_full']; ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($tx['tx_timestamp_initiated_formatted']); ?></td>
                        <td>
                            <span class="lvdwcmc-tx-status <?php echo esc_attr($tx['tx_status']); ?>"><?php echo esc_html($tx['tx_status_formatted']); ?></span>
                        </td>
                        <td><?php echo esc_html($tx['tx_amount_formatted']); ?></td>
                        <td><?php echo esc_html($tx['tx_processed_amount_formatted']); ?></td>
                        <td>
                            <a href="javascript:void(0)" class="lvdwcmc-tx-action" data-transactionId="<?php echo esc_attr($tx['tx_id']); ?>">View details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="lvdwcmc-admin-notice">
            <?php echo __('There are no transactions matching your criteria', LVD_WCMC_TEXT_DOMAIN); ?>
        </div>
    <?php endif; ?>
</div>
