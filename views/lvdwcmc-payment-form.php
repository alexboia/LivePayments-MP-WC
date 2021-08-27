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

<style type="text/css">
	#lvdwcmc-submit-mobilpay-payment-form-reload-on-error {
		margin-bottom: 10px;
	}

	#lvdwcmc-mobilpay-autoredirect-notice {
		display: block;
		line-height: 19px;
		padding: 11px 15px;
		font-size: 14px;
		text-align: left;
		margin-bottom: 15px;
		color: #856404;
		background-color: #fff3cd;
		border: 1px solid #ffeeba;
	}

	#lvdwcmc-mobilpay-autoredirect-notice-seconds-counter {
		font-weight: bold;
		margin-right: 3px;
		margin-left: 3px;
	}
</style>

<?php 
	/** 
	 * Fires before the payment form is rendered, 
	 * 	but after the default stylesheet is rendered
	 * 
	 * @hook lvdwcmc_payment_form_before_begin
	 * 
	 * @param \stdClass $data The payment data view model
	 */
	do_action('lvdwcmc_payment_form_before_begin', $data);
?>

<?php if ($data->success): ?>
	<script type="text/javascript">
		var _checkoutAutoRedirectSeconds = <?php echo esc_js($data->settings->checkoutAutoRedirectSeconds); ?>;
	</script>

	<form method="post" id="lvdwcmc-mobilpay-redirect-form" action="<?php echo $data->paymentUrl; ?>">
		<input type="hidden" name="env_key" value="<?php echo esc_attr($data->envKey); ?>"/>
		<input type="hidden" name="data" value="<?php echo esc_attr($data->encData); ?>"/>	

		<?php 
			/**
			 * Fires right after the payment form data payload 
			 * 	hidden HTML fields have been rendered
			 * 
			 * @hook lvdwcmc_payment_form_after_payment_data_payload_fields
			 * 
			 * @param \stdClass $data The payment data view model
			 */
			do_action('lvdwcmc_payment_form_after_payment_data_payload_fields', $data);
		?>

		<?php if ($data->settings->checkoutAutoRedirectSeconds > 0): ?>
			<div id="lvdwcmc-mobilpay-autoredirect-notice">
				<span id="lvdwcmc-mobilpay-autoredirect-notice-text"><?php echo esc_html__('You will be automatically redirected to the payment page in', 'livepayments-mp-wc'); ?></span>
				<span id="lvdwcmc-mobilpay-autoredirect-notice-seconds-counter"><?php echo esc_html($data->settings->checkoutAutoRedirectSeconds); ?></span>
				<span id="lvdwcmc-mobilpay-autoredirect-notice-seconds-suffix"><?php echo esc_html__('seconds', 'livepayments-mp-wc'); ?></span>
			</div>
		<?php endif; ?>

		<?php 
			/**
			 * Fires rght before the payment form submit button 
			 * 	is rendered
			 * 
			 * @hook lvdwcmc_payment_form_before_payment_submit_button
			 * 
			 * @param \stdClass $data The payment data view model
			 */
			do_action('lvdwcmc_payment_form_before_payment_submit_button', $data);
		?>

		<input type="submit" 
			name="lvdwcmc-submit-mobilpay-payment-form" 
			id="lvdwcmc-submit-mobilpay-payment-form" 
			class="lvdwcmc-submit-mobilpay-payment-form"
			value="<?php echo esc_attr__('Pay via mobilPay&trade;', 'livepayments-mp-wc') ?>" />

		<?php 
			/**
			 * Fires rght before the payment form submit button 
			 * 	has been rendered
			 * 
			 * @hook lvdwcmc_payment_form_after_payment_submit_button
			 * 
			 * @param \stdClass $data The payment data view model
			 */
			do_action('lvdwcmc_payment_form_after_payment_submit_button', $data);
		?>
	</form>
<?php else: ?>
	<ul class="woocommerce-error" role="alert">
		<li><?php echo esc_attr__('The payment could not be initialized. This is usually due to an issue with the store itself, so please contact its administrator.', 'livepayments-mp-wc'); ?></li>
	</ul>

	<?php 
		/**
		 * Fires right before the payment error screen 
		 * 	refresh button is rendered
		 * 
		 * @hook lvdwcmc_payment_form_before_payment_error_refresh_button
		 * 
		 * @param \stdClass $data The payment data view model
		 */
		do_action('lvdwcmc_payment_form_before_payment_error_refresh_button', $data);
	?>

	<input type="button" 
		name="lvdwcmc-submit-mobilpay-payment-form-reload-on-error" 
		id="lvdwcmc-submit-mobilpay-payment-form-reload-on-error" 
		class="lvdwcmc-submit-mobilpay-payment-form-reload-on-error"
		value="<?php echo esc_attr__('Retry', 'livepayments-mp-wc') ?>" >

	<?php 
		/**
		 * Fires right before the payment error screen 
		 * 	refresh button has been rendered
		 * 
		 * @hook lvdwcmc_payment_form_after_payment_error_refresh_button
		 * 
		 * @param \stdClass $data The payment data view model
		 */
		do_action('lvdwcmc_payment_form_after_payment_error_refresh_button', $data);
	?>
<?php endif; ?>

<?php 
	/** 
	 * Fires after the payment form has bee rendered
	 * 
	 * @hook lvdwcmc_payment_form_before_after_end
	 * 
	 * @param \stdClass $data The payment data view model
	 */
	do_action('lvdwcmc_payment_form_before_after_end', $data);
?>