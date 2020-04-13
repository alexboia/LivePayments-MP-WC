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

function lvd_wcmc_init_autoloader() {
   require_once LVD_WCMC_LIB_DIR . '/Autoloader.php';
   LvdWcMc\Autoloader::init(LVD_WCMC_LIB_DIR, array(
      'LvdWcMc' => array(
         'separator' => '\\',
         'libDir' => LVD_WCMC_LIB_DIR
      ),
      'Mobilpay' => array(
         'separator' => '_',
         'libDir' => LVD_WCMC_LIB_3RDPARTY_DIR . DIRECTORY_SEPARATOR . 'mobilpay'
      )
   ));
}

function lvdwcmc_get_datetime_format() {
   $dateTimeFormat = get_option('date_format') . ' ' . get_option('time_format');
   /**
    * Filters the format used by WC-MobilPayments-Card to format dates
    * 
    * @hook lvdwcmc_datetime_format
    * 
    * @param string $dateTimeFormat The current date time format, initially provided by WC-MobilPayments-Card
    * @return string The actual & final date time format, as returned by the registered filters
    */
   return apply_filters('lvdwcmc_datetime_format', 
         $dateTimeFormat);
}

function lvdwcmc_get_amount_format() {
   $amountFormat = array(
      'decimals' => wc_get_price_decimals(), 
      'decimalSeparator' => wc_get_price_decimal_separator(), 
      'thousandSeparator' => wc_get_price_thousand_separator()
   );

   /** 
    * Filters the format used by WC-MobilPayments-Card to format money amounts
    * 
    * @hook lvdwcmc_amount_format
    * 
    * @param array $amountFormat The current amount format, initially provided by WC-MobilPayments-Card
    * @return array The actual & final amount format, as returned by the registered filters
    */
   return apply_filters('lvdwcmc_amount_format', 
      $amountFormat);
}

function lvdwcmc_get_ajax_response($additionalProps = array()) {
	$response = new stdClass();
	$response->success = false;
	$response->message = null;

	foreach ($additionalProps as $key => $value) {
		$response->$key = $value;
	}

	return $response;
}

function lvdwcmc_append_error($message, $error) {
	if (defined('WP_DEBUG') && WP_DEBUG) {
		if ($error instanceof \Exception) {
			$message .= sprintf(': %s (%s) in file %s line %d', $error->getMessage(), $error->getCode(), $error->getFile(), $error->getLine());
		} else if (!empty($error)) {
			$message .= ': ' . $error;
		}
	}
	return $message;
}

function lvdwcmc_increase_limits($executionTimeMinutes = 10) {
   if (function_exists('set_time_limit')) {
		@set_time_limit($executionTimeMinutes * 60);
	}
	if (function_exists('ini_set')) {
		@ini_set('memory_limit', WP_MAX_MEMORY_LIMIT);
	}
}

function lvdwcmc_send_json(\stdClass $data, $die = true) {
   $data = json_encode($data);
	header('Content-Type: application/json');
	if (extension_loaded('zlib') && function_exists('ini_set')) {
		@ini_set('zlib.output_compression', false);
		@ini_set('zlib.output_compression_level', 0);
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
			header('Content-Encoding: gzip');
			$data = gzencode($data, 8, FORCE_GZIP);
		}
   }

   echo $data;
   if ($die) {
      exit;
   }
}

/**
 * @return \LvdWcMc\Env
 */
function lvdwcmc_env() {
   static $env = null;
   
   if ($env === null) {
      $env = new LvdWcMc\Env();
   }

   return $env;
}

/**
 * @return \LvdWcMc\Plugin
 */
function lvdwcmc_plugin() {
   static $plugin = null;
   if ($plugin === null) {
      $plugin = new LvdWcMc\Plugin(array(
         'mediaIncludes' => array(
            'refPluginsPath' => LVD_WCMC_MAIN,
            'scriptsInFooter' => true
         )
      ));
   }
   return $plugin;
}

function lvd_wcmc_run() {
   lvdwcmc_plugin()->run();
}
