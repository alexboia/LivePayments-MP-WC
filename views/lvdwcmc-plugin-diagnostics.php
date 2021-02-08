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

<div id="lvdwcmc-plugin-diagnostics-page">
    <h2><?php echo esc_html__('LivePayments - mobilPay Card WooCommerce Payment Gateway - Plugin Diagnostics', 'livepayments-mp-wc'); ?></h2>

    <div class="wrap lvdwcmc-plugin-diagnostics-container">
        <table class="widefat" id="lvdwcmc-system-info" cellspacing="0">
            <thead>
                <tr>
                    <th colspan="2"><h3><?php echo esc_html__('System information', 'livepayments-mp-wc'); ?></h3></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data->systemInfo as $info): ?>
                    <tr>
                        <td><?php echo esc_html($info['label']); ?></td>
                        <td><code><?php echo esc_html($info['value']); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="wrap lvdwcmc-plugin-diagnostics-container">
        <table class="widefat" id="lvdwcmc-gateway-info" cellspacing="0">
            <thead>
                <tr>
                    <th><h3><?php echo esc_html__('Gateway diagnostics', 'livepayments-mp-wc') ?></h3></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data->gatewayConfigured): ?>
                    <?php if (!$data->gatewayOk): ?>
                        <?php foreach ($data->gatewayDiagnosticMessages as $message): ?>
                            <tr><td class="lvdwcmc-gateway-warn"><span class="dashicons dashicons-warning"></span> <?php echo esc_html($message); ?></td></tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td class="lvdwcmc-gateway-ok"><span class="dashicons dashicons-yes"></span> <?php echo esc_html__('Everything appears to be functioning normally.', 'livepayments-mp-wc') ?></td>
                        </tr>
                    <?php endif; ?>
                <?php else: ?>
                    <tr>
                        <td class="lvdwcmc-gateway-warn"><span class="dashicons dashicons-warning"></span> <?php echo esc_html__('The payment gateway requires further configuration until it can be used to accept payments.', 'livepayments-mp-wc') ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td><a class="button-primary debug-report" href="<?php echo esc_attr($data->gatewaySettingsPageUrl); ?>" target="_blank"><?php echo esc_html__('Go to gateway configuration', 'livepayments-mp-wc') ?></a></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>