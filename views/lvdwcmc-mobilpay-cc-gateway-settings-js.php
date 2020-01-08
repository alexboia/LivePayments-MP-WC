<?php
    defined('LVD_WCMC_LOADED') or die;
?>

<script id="lvdwcaf-tpl-asset-file-exists" type="text/x-kite">
    <span class="lvdwcmc-payment-asset-file-exists"><?php echo $this->__('The file has already been uploaded.'); ?></span>
    <a href="javascript:void(0);" 
        id="{{assetId}}_file_removal"
        data-asset-id="{{assetId}}"
        class="lvdwcmc-payment-asset-file-removal"><?php echo $this->__('Remove') ?></a>
</script>

<script id="lvdwcaf-tpl-asset-file-missing" type="text/x-kite">
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