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
	var lvdwcmc_saveSettingsNonce = '<?php echo esc_js($data->saveSettingsNonce); ?>';
	var lvdwcmc_saveSettingsAction = '<?php echo esc_js($data->saveSettingsAction); ?>';
	var lvdwcmc_adminEmailAddress = '<?php echo esc_js($data->adminEmailAddress); ?>';
</script>

<div id="lvdwcmc-settings-page">
	<form id="lvdwcmc-settings-form" method="post">
		<h2><?php echo __('LivePayments - mobilPay Card WooCommerce Payment Gateway - Plugin Settings', 'livepayments-mp-wc'); ?></h2>

		<div class="wrap lvdwmc-settings-container">
			<div id="lvdwcmc-settings-save-result" 
				class="updated settings-error lvdwcmc-settings-save-result" 
				style="display:none"></div>

			<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<th><h3><?php echo esc_html__('Checkout payment workflow options', 'livepayments-mp-wc'); ?></h3></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="lvdwcmc-checkout-auto-redirect-enable"><?php echo esc_html__('Automatically redirect customer to payment page', 'livepayments-mp-wc'); ?></label>
									</th>
									<td>
										<input type="checkbox" 
											name="checkoutAutoRedirect" 
											id="lvdwcmc-checkout-auto-redirect-enable" 
											value="1" 
											<?php echo $data->settings->checkoutAutoRedirectSeconds >= 0 ? 'checked="checked"' : ''; ?>
										/>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="lvdwcmc-checkout-auto-redirect-seconds"><?php echo esc_html__('Automatically redirect customer to payment page after this number of seconds', 'livepayments-mp-wc') ?></label>
									</th>
									<td>
										<input type="text" 
											name="checkoutAutoRedirectSeconds"
											id="lvdwcmc-checkout-auto-redirect-seconds"
											class="input-text regular-input"
											value="<?php echo esc_attr($data->settings->checkoutAutoRedirectSeconds); ?>"
											<?php echo $data->settings->checkoutAutoRedirectSeconds <= 0 ? 'disabled="disabled"' : ''; ?> 
										/>

										<span class="lvdwcmc-checkout-auto-redirect-instant-container">
											<input type="checkbox" 
												name="checkoutAutoRedirectInstant" 
												id="lvdwcmc-checkout-auto-redirect-instant" 
												value="1" 
												<?php echo $data->settings->checkoutAutoRedirectSeconds == 0 ? 'checked="checked"' : ''; ?>
											/>
											<label for="lvdwcmc-checkout-auto-redirect-instant"><?php echo esc_html__('Do not wait, redirect immediately', 'livepayments-mp-wc'); ?></label>
										</span>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<p class="submit">
								<input type="button" 
									id="lvdwcmc-submit-settings-workflow" 
									name="lvdwcmc-submit-settings-workflow" 
									class="button button-primary lvdwcmc-form-submit-btn" 
									value="<?php echo esc_html__('Save settings', 'livepayments-mp-wc'); ?>" 
								/>
							</p>
						</td>
					</tr>
				</tbody>
			</table>


			<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<th><h3><?php echo esc_html__('Diagnostics options', 'livepayments-mp-wc'); ?></h3></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="lvdwcmc-monitor-diagnostics"><?php echo esc_html__('Monitor gateway diagnostics', 'livepayments-mp-wc'); ?>:</label>
									</th>
									<td>
										<input type="checkbox" 
											name="monitorDiagnostics" 
											id="lvdwcmc-monitor-diagnostics" 
											value="1" 
											<?php echo $data->settings->monitorDiagnostics ? 'checked="checked"' : ''; ?> 
										/> 
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="lvdwcmc-send-diagnsotics-warning-to-email"><?php echo esc_html__('Send gateway diagnostics warnings to this address', 'livepayments-mp-wc'); ?>:</label>
									</th>
									<td>
										<input type="text" 
											name="sendDiagnosticsWarningToEmail" 
											id="lvdwcmc-send-diagnsotics-warning-to-email"
											class="input-text regular-input"
											value="<?php echo esc_attr($data->settings->sendDiagnosticsWarningToEmail); ?>" 
											<?php echo !$data->settings->monitorDiagnostics ? 'disabled="disabled"' : ''; ?> 
										/> 
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<p class="submit">
								<input type="button" 
									id="lvdwcmc-submit-settings" 
									name="lvdwcmc-submit-settings" 
									class="button button-primary lvdwcmc-form-submit-btn" 
									value="<?php echo esc_html__('Save settings', 'livepayments-mp-wc'); ?>" 
								/>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</form>
</div>