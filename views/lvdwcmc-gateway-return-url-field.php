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
        <label for="mobilpay_return_url">
            <?php echo esc_html($data->fieldInfo['title']); ?> 
            <?php if (!empty($data->fieldInfo['description'])): ?>
                <?php echo $this->get_tooltip_html($data->fieldInfo); ?>
            <?php endif; ?>
        </label>
    </th>
    <td class="forminp">
        <fieldset>
            <legend class="screen-reader-text"><span><?php echo esc_html($data->fieldInfo['title']); ?></span></legend>
            <input class="input-text regular-input" 
                type="text" 
                name="mobilpay_return_url" 
                id="mobilpay_return_url" 
                value="<?php echo esc_attr($data->returnUrl);  ?>" 
                placeholder="">
            <a href="javascript:void(0);" 
                id="mobilpay_return_url_generate"
                class="lvdwcmc-return-url-generate"><?php echo esc_html__('Generate it for me', 'livepayments-mp-wc') ?></a>
        </fieldset>
    </td>
</tr>