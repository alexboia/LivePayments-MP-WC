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

<script id="lvdwcaf-tpl-asset-file-removal" type="text/x-kite">
    <span class="lvdwcmc-payment-asset-file-exists"><?php echo $this->__('The file has already been uploaded.'); ?></span>
    <a href="javascript:void(0);" 
        id="{{assetId}}_file_removal"
        data-asset-id="{{assetId}}"
        class="lvdwcmc-payment-asset-file-removal"><?php echo $this->__('Remove') ?></a>
</script>

<script id="lvdwcaf-tpl-asset-file-upload" type="text/x-kite">
    <a href="javascript:void(0);" 
        id="{{assetId}}_file_selector"
        data-asset-id="{{assetId}}"
        class="lvdwcmc-payment-asset-file-selector"><?php echo $this->__('Chose a file from disk'); ?></a>
</script>

<script type="text/javascript">
    var lvdwcmc_uploadPaymentAssetUrl = '<?php echo esc_js($data->uploadPaymentAssetUrl) ?>';
    var lvdwcmc_uploadPaymentAssetNonce = '<?php echo esc_js($data->uploadPaymentAssetNonce); ?>';

    var lvdwcmc_removePaymentAssetUrl = '<?php echo esc_js($data->removePaymentAssetUrl); ?>';
    var lvdwcmc_removePaymentAssetNonce = '<?php echo esc_js($data->removePaymentAssetNonce); ?>';

    var lvdwcmc_uploadMaxFileSize = '<?php echo esc_js($data->uploadMaxFileSize) ?>';
    var lvdwcmc_uploadChunkSize = '<?php echo esc_js($data->uploadChunkSize); ?>';
    var lvdwcmc_uploadKey = '<?php echo esc_js($data->uploadKey); ?>';
</script>