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

 define('LVD_WCMC_LOADED', true);
 define('LVD_WCMC_PLUGIN_ID', 'lvd_wc_mc');
 define('LVD_WCMC_VERSION', '0.1.0');
 define('LVD_WCMC_HEADER', __FILE__);
 define('LVD_WCMC_FUNCTIONS', __DIR__ . DIRECTORY_SEPARATOR . 'wc-mobilpayments-card-plugin-functions.php');
 define('LVD_WCMC_MAIN', __DIR__ . DIRECTORY_SEPARATOR .  'wc-mobilpayments-card-plugin-main.php');
 define('LVD_WCMC_ROOT_DIR', __DIR__);
 define('LVD_WCMC_LIB_DIR', LVD_WCMC_ROOT_DIR . DIRECTORY_SEPARATOR . 'lib');
 define('LVD_WCMC_VIEWS_DIR', LVD_WCMC_ROOT_DIR . DIRECTORY_SEPARATOR . 'views');
 define('LVD_WCMC_LANG_DIR', LVD_WCMC_ROOT_DIR . DIRECTORY_SEPARATOR . 'lang');
 define('LVD_WCMC_DATA_DIR', LVD_WCMC_ROOT_DIR . DIRECTORY_SEPARATOR . 'data');
 define('LVD_WCMC_TEXT_DOMAIN', 'wc-mobilpayments-card');