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

<tr valign="top">
    <th scope="row" class="titledesc">
        <label class="lvdwcmc-asset-label lvdwcmc-<?php echo esc_attr($data->fieldInfo['environment']); ?>-asset-label">
            <?php echo esc_html($data->fieldInfo['title']); ?>
            <?php if (!empty($data->fieldInfo['description'])): ?>
                <?php echo $this->get_tooltip_html($data->fieldInfo); ?>
            <?php endif; ?>
        </label>
    </th>
    <td class="forminp" id="<?php echo esc_attr($data->fieldId); ?>_asset_container">
        <?php if ($data->hasAsset): ?>
            <span class="lvdwcmc-payment-asset-file-exists"><?php echo esc_html__('The file has already been uploaded.', 'livepayments-mp-wc'); ?></span>
            <a href="javascript:void(0);" 
                id="<?php echo esc_attr($data->fieldId); ?>_file_removal"
                data-asset-id="<?php echo esc_attr($data->fieldId); ?>"
                class="lvdwcmc-payment-asset-file-removal"><?php echo esc_html__('Remove', 'livepayments-mp-wc'); ?></a>
        <?php else: ?>
            <a href="javascript:void(0);" 
                id="<?php echo esc_attr($data->fieldId); ?>_file_selector"
                data-asset-id="<?php echo esc_attr($data->fieldId); ?>"
                class="lvdwcmc-payment-asset-file-selector"><?php echo esc_html__('Chose a file from disk', 'livepayments-mp-wc'); ?></a>
        <?php endif; ?>
    </td>
</tr>