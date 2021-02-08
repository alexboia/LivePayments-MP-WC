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

<?php if (!$data->gatewayReady): ?>
    <div class="lvdwcmc-gateway-readiness lvdwcmc-gateway-readiness-notready lvdwcmc-readiness-context-<?php echo esc_attr($data->context); ?>">
        <div class="lvdwcmc-gateway-readiness-message">
            <span class="dashicons dashicons-warning"></span> <?php echo $data->message; ?>
        </div>
        <ul>
            <?php foreach ($data->missingRequiredFields as $fieldId => $label): ?>
                <li data-missing-field-id="<?php echo esc_attr($fieldId); ?>"><?php echo esc_html($label); ?></li>
            <?php endforeach; ?>

            <?php foreach ($data->fieldsWithWarnings as $fieldId => $message): ?>
                <li data-missing-field-id="<?php echo esc_attr($fieldId); ?>"><?php echo esc_html($message); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php else: ?>
    <div class="lvdwcmc-gateway-readiness lvdwcmc-gateway-readiness-ready lvdwcmc-readiness-context-<?php echo esc_attr($data->context);?>">
        <div class="lvdwcmc-gateway-readiness-message">
            <span class="dashicons dashicons-yes"></span> <?php echo $data->message; ?>
        </div>
    </div>
<?php endif; ?>