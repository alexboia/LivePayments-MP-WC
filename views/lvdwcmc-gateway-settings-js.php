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

<script id="lvdwcmc-tpl-asset-file-removal" type="text/x-kite">
    <span class="lvdwcmc-payment-asset-file-exists"><?php echo esc_html__('The file has already been uploaded.', 'livepayments-mp-wc'); ?></span>
    <a href="javascript:void(0);" 
        id="{{assetId}}_file_removal"
        data-asset-id="{{assetId}}"
        class="lvdwcmc-payment-asset-file-removal"><?php echo esc_html__('Remove', 'livepayments-mp-wc'); ?></a>
</script>

<script id="lvdwcmc-tpl-asset-file-upload" type="text/x-kite">
    <a href="javascript:void(0);" 
        id="{{assetId}}_file_selector"
        data-asset-id="{{assetId}}"
        class="lvdwcmc-payment-asset-file-selector"><?php echo esc_html__('Chose a file from disk', 'livepayments-mp-wc'); ?></a>
</script>

<script type="text/javascript">
    var lvdwcmc_uploadPaymentAssetUrl = '<?php echo esc_js($data->uploadPaymentAssetUrl) ?>';
    var lvdwcmc_uploadPaymentAssetNonce = '<?php echo esc_js($data->uploadPaymentAssetNonce); ?>';

    var lvdwcmc_removePaymentAssetUrl = '<?php echo esc_js($data->removePaymentAssetUrl); ?>';
    var lvdwcmc_removePaymentAssetNonce = '<?php echo esc_js($data->removePaymentAssetNonce); ?>';

    var lvdwcmc_returnUrlGenerationUrl = '<?php echo esc_js($data->returnUrlGenerationUrl); ?>';
    var lvdwcmc_returnUrlGenerationNonce = '<?php echo esc_js($data->returnUrlGenerationNonce); ?>';

    var lvdwcmc_uploadMaxFileSize = '<?php echo esc_js($data->uploadMaxFileSize) ?>';
    var lvdwcmc_uploadChunkSize = '<?php echo esc_js($data->uploadChunkSize); ?>';
    var lvdwcmc_uploadKey = '<?php echo esc_js($data->uploadKey); ?>';
</script>

<?php 
    /**
     * Insert inline gateway JS settings. The plug-in does not automatically wrap those in script tags, 
     *  so user action hook callback must provide those if required.
     * Triggered after the core gateway JS settings have been inserted.
     * 
     * @hook lvdwcmc_insert_inline_js_settings
     * @param \stdClass $data The view model that contains the data required to render the settings
     */
    do_action('lvdwcmc_insert_inline_js_settings', $data); 
?>