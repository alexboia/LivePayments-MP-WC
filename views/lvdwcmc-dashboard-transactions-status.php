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
<?php if ($data->success): ?>
    <?php 
        /**
         * Fires before the core content of the admin 
         *  dashboard status widget is rendered
         * 
         * @hook lvdwcmc_before_admin_dasboard_transaction_status
         * 
         * @param \stdClass $data The view model
         */
        do_action('lvdwcmc_before_admin_dasboard_transaction_status', $data);
    ?>
    
    <ul class="lvdwcmc-dashboard-transaction-status">
        <?php foreach ($data->status as $status => $data): ?>
            <li class="<?php echo esc_attr($status); ?>">
                <span class="lvdwcmc-status-count"><?php echo esc_html($data['count']); ?></span>
                <h5 class="lvdwcmc-status-label"><?php echo esc_html($data['label']); ?></h5>
                <div class="lvdwcmc-clear"></div>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php 
        /**
         * Fires after the core content of the admin 
         *  dashboard status widget is rendered
         * 
         * @hook lvdwcmc_after_admin_dasboard_transaction_status
         * 
         * @param \stdClass $data The view model
         */
        do_action('lvdwcmc_after_admin_dasboard_transaction_status', $data);
    ?>
<?php endif; ?>