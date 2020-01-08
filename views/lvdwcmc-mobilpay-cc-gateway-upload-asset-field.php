<?php
    defined('LVD_WCMC_LOADED') or die;
?>

<tr valign="top">
    <th scope="row" class="titledesc">
        <label class="lvdwcmc-asset-label lvdwcmc-<?php echo $data->fieldInfo['environment'] ?>-asset-label">
            <?php echo $data->fieldInfo['title']; ?>
            <?php if (!empty($data->fieldInfo['description'])): ?>
                <?php echo $this->get_tooltip_html($data->fieldInfo); ?>
            <?php endif; ?>
        </label>
    </th>
    <td class="forminp" id="<?php echo esc_attr($data->fieldId); ?>_asset_container">
        <?php if ($data->hasAsset): ?>
            <span class="lvdwcmc-payment-asset-file-exists"><?php echo $this->__('The file has already been uploaded.'); ?></span>
            <a href="javascript:void(0);" 
                id="<?php echo esc_attr($data->fieldId); ?>_file_removal"
                data-asset-id="<?php echo esc_attr($data->fieldId); ?>"
                class="lvdwcmc-payment-asset-file-removal"><?php echo $this->__('Remove') ?></a>
        <?php else: ?>
            <a href="javascript:void(0);" 
                id="<?php echo esc_attr($data->fieldId); ?>_file_selector"
                data-asset-id="<?php echo esc_attr($data->fieldId); ?>"
                class="lvdwcmc-payment-asset-file-selector"><?php echo $this->__('Chose a file from disk'); ?></a>
        <?php endif; ?>
    </td>
</tr>