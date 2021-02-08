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
    #submit_mobilpay_payment_form_reload_on_error {
        margin-bottom: 10px;
    }
</style>

<?php if ($data->success): ?>
    <form method="post" id="lvdWcMcMobilpayRedirect" action="<?php echo $data->paymentUrl; ?>">
        <input type="hidden" name="env_key" value="<?php echo esc_attr($data->envKey); ?>"/>
        <input type="hidden" name="data" value="<?php echo esc_attr($data->encData); ?>"/>	

        <input type="submit" name="submit_mobilpay_payment_form" 
            id="submit_mobilpay_payment_form" 
            value="<?php echo esc_attr__('Pay via mobilPay&trade;', 'livepayments-mp-wc') ?>" />
    </form>
<?php else: ?>
    <ul class="woocommerce-error" role="alert">
        <li><?php echo esc_attr__('The payment could not be initialized. This is usually due to an issue with the store itself, so please contact its administrator.', 'livepayments-mp-wc'); ?></li>
    </ul>

    <input type="button" name="submit_mobilpay_payment_form_reload_on_error" 
        id="submit_mobilpay_payment_form_reload_on_error" 
        value="<?php echo esc_attr__('Retry', 'livepayments-mp-wc') ?>" >
<?php endif; ?>