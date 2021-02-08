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

<script type="text/javascript">
    var lvdwcmc_ajaxBaseUrl = '<?php echo esc_js($data->ajaxBaseUrl); ?>';
    var lvdwcmc_transactionDetailsAction = '<?php echo esc_js($data->transactionDetailsAction); ?>';
    var lvdwcmc_transactionDetailsNonce = '<?php echo esc_js($data->transactionDetailsNonce); ?>';
</script>

<div class="lvdwcmc-page-container">
    <h1><?php echo esc_html($data->pageTitle); ?></h1>

    <?php
        /**
         * Fires before the core transactions listing 
         *  is rendered on the admin transactions 
         *  listing page.
         * 
         * @hook lvdwcmc_before_admin_transactions_listing
         * 
         * @param \stdClass $data The view model
         */
        do_action('lvdwcmc_before_admin_transactions_listing', $data);
    ?>

    <?php if ($data->hasTransactions): ?>
        <table id="lvdwcmc-tx-listing" class="wp-list-table widefat fixed striped posts lvdwcmc-tx-listing">
            <thead>
                <tr>
                    <th class="lvdwcmc-order-id-column"><?php echo esc_html__('Order Id', 'livepayments-mp-wc'); ?></th>
                    <th><?php echo esc_html__('Date initiated', 'livepayments-mp-wc'); ?></th>
                    <th><?php echo esc_html__('Status', 'livepayments-mp-wc'); ?></th>
                    <th><?php echo esc_html__('Amount', 'livepayments-mp-wc'); ?></th>
                    <th><?php echo esc_html__('Processed amount', 'livepayments-mp-wc'); ?></th>

                    <?php foreach ($data->additionalColumns as $column): ?>
                        <th <?php echo !empty($column['class']) ? 'class="' . esc_attr($column['class']) . '"' : '' ?>><?php echo esc_html($column['header']); ?></th>
                    <?php endforeach; ?>

                    <th><?php echo esc_html__('Actions', 'livepayments-mp-wc'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data->transactions as $key => $tx):  ?>
                    <tr>
                        <td class="lvdwcmc-order-id-column">
                            <a href="<?php echo esc_url($tx['tx_admin_details_link']); ?>" target="_blank">
                                <?php echo esc_html($tx['tx_title_full']); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($tx['tx_timestamp_initiated_formatted']); ?></td>
                        <td>
                            <span class="lvdwcmc-tx-status <?php echo esc_attr($tx['tx_status']); ?>"><?php echo esc_html($tx['tx_status_formatted']); ?></span>
                        </td>
                        <td><?php echo esc_html($tx['tx_amount_formatted']); ?></td>
                        <td><?php echo esc_html($tx['tx_processed_amount_formatted']); ?></td>

                        <?php foreach ($data->additionalColumns as $column): ?>
                            <td <?php echo !empty($column['class']) ? 'class="' . esc_attr($column['class']) . '"' : '' ?>>
                                <?php if (isset($column['provider'])): ?>
                                    <?php if (is_callable($column['provider'])): ?>
                                        <?php echo esc_html(call_user_func($column['provider'], $key, $tx, $data)); ?>
                                    <?php else: ?>
                                        <?php 
                                            echo !empty($tx[$column['provider']]) 
                                                ? esc_html($tx[$column['provider']]) 
                                                : '<span class="lvdwcmc-novalue">-</span>'; 
                                        ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="lvdwcmc-novalue">-</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>

                        <td>
                            <a href="javascript:void(0)" class="lvdwcmc-tx-action" data-transactionId="<?php echo esc_attr($tx['tx_id']); ?>"><?php echo esc_html__('Details', 'livepayments-mp-wc'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="lvdwcmc-pagination-links">
            <?php echo paginate_links($data->paginateLinksArgs); ?>
        </div>
    <?php else: ?>
        <div class="lvdwcmc-admin-notice">
            <?php echo esc_html__('There are no transactions matching your criteria', 'livepayments-mp-wc'); ?>
        </div>
    <?php endif; ?>

    <?php
        /**
         * Fires before the core transactions listing 
         *  is rendered on the admin transactions 
         *  listing page.
         * 
         * @hook lvdwcmc_after_admin_transactions_listing
         * 
         * @param \stdClass $data The view model
         */
        do_action('lvdwcmc_after_admin_transactions_listing', $data);
    ?>
</div>

<script id="lvdwcmc-tpl-transaction-details" type="text/x-kite">
    <div class="lvdwcmc-admin-transaction-details-wnd">
        <div class="lvdwcmc-admin-transaction-details-wnd-header">
            <h3><?php echo esc_html__('Transaction details', 'livepayments-mp-wc'); ?></h3>
        </div>
        <div class="lvdwcmc-admin-transaction-details-wnd-content">

            <?php 
                /**
                 * Fires before core details table of the JS admin listing 
                 *   transaction details template
                 *
                 * @hook lvdwcmc_before_admin_transactions_listing_details_template
                 */
                do_action('lvdwcmc_before_admin_transactions_listing_details_template');
            ?>

            <table class="lvdwcmc-admin-transaction-details-list">
                <tbody>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Transaction Id', 'livepayments-mp-wc'); ?>:</th>
                        <td>
                            {{? !!transaction.providerTransactionId }}
                                {{transaction.providerTransactionId}}
                            {{^?}}
                                -
                            {{/?}}
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Transaction status', 'livepayments-mp-wc'); ?>:</th>
                        <td>{{transaction.status}}</td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Card number', 'livepayments-mp-wc'); ?>:</th>
                        <td>
                            {{? !!transaction.panMasked}}
                                {{transaction.panMasked}}
                            {{^?}}
                                -
                            {{/?}}
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Original amount', 'livepayments-mp-wc'); ?>:</th>
                        <td>{{transaction.amount}} {{transaction.currency}}</td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Actually processed amount', 'livepayments-mp-wc'); ?>:</th>
                        <td>{{transaction.processedAmount}} {{transaction.currency}}</td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Date initiated', 'livepayments-mp-wc'); ?>:</th>
                        <td>{{transaction.timestampInitiated}}</td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Date of last activity', 'livepayments-mp-wc'); ?>:</th>
                        <td>{{transaction.timestampLastUpdated}}</td>
                    </tr>
                    {{? transaction.errorCode > 0 }}
                        <tr>
                            <th scope="row"><?php echo esc_html__('Transaction error code', 'livepayments-mp-wc'); ?>:</th>
                            <td>{{transaction.errorCode}}</td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Transaction error message', 'livepayments-mp-wc'); ?>:</th>
                            <td>{{transaction.errorMessage}}</td>
                        </tr>
                    {{/?}}
                    {{? transaction.clientIpAddress }}
                        <tr>
                            <th scope="row"><?php echo esc_html__('Client IP Address', 'livepayments-mp-wc'); ?>:</th>
                            <td>{{transaction.clientIpAddress}}</td>
                        </tr>
                    {{/?}}
                </tbody>
            </table>

            <?php 
                /**
                 * Fires after core details table of the JS admin listing 
                 *   transaction details template
                 *
                 * @hook lvdwcmc_after_admin_transactions_listing_details_template
                 */
                do_action('lvdwcmc_after_admin_transactions_listing_details_template');
            ?>

        </div>
        <div class="lvdwcmc-admin-transaction-details-wnd-footer">
            <a href="javascript:void(0)" 
                id="lvdwcmc-admin-transaction-details-close" 
                class="lvdwcmc-generic-close-btn"><?php echo esc_html__('Close', 'livepayments-mp-wc'); ?></a>
            <div class="lvdwcmc-clear"></div>
        </div>
    </div>
</script>