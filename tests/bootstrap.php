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

require_once 'faker/autoload.php';
require_once 'lib/MobilpayConstants.php';
require_once 'lib/GenericTestHelpers.php';
require_once 'lib/DbTestHelpers.php';
require_once 'lib/IntegerIdGenerator.php';
require_once 'lib/FractionsFakerDataProvider.php';
require_once 'lib/MobilpayTransactionTestHelpers.php';
require_once 'lib/WcOrderHelpers.php';
require_once 'lib/MobilpayCardRequestTestHelpers.php';
require_once 'lib/WordPressHookTester.php';	

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if (is_dir($_tests_dir)) {
	require_once $_tests_dir . '/includes/functions.php';
} else {
	die('Test directory not found');
}

function _get_tests_base_dir() {
	return __DIR__;
}

function _get_own_plugin_base_dir() {
	return dirname(__DIR__);
}

function _get_plugins_root() {
	return dirname(_get_own_plugin_base_dir());
}

function _get_own_plugin_path() {
	return _get_own_plugin_base_dir() . '/lvdwcmc-plugin-main.php';
}

function _get_3rdparty_plugin_base_dir($plugin) {
	return _get_plugins_root() . '/' . $plugin;
}

function _get_woocommerce_plugin_path() {
	return _get_3rdparty_plugin_base_dir('woocommerce') . '/woocommerce.php';
}

function _get_tests_file_path($file) {
	return _get_tests_base_dir() . '/' . $file;
}

function _has_wc_been_installed_before() {
	return file_exists(_get_tests_file_path('.wc-installed'));
}

function _set_wc_installed() {
	file_put_contents(_get_tests_file_path('.wc-installed'), 'yes');
}

function _has_own_plugin_been_installed_before() {
	return file_exists(_get_tests_file_path('.lvdwcmc-installed'));
}

function _set_own_plugin_installed() {
	file_put_contents(_get_tests_file_path('.lvdwcmc-installed'), 'yes');
}

function _disable_mysqli_strict_reporting() {
	$driver = new \mysqli_driver();
	$driver->report_mode =  MYSQLI_REPORT_OFF;
}

function _enable_mysqli_strict_reporting() {
	$driver = new \mysqli_driver();
	$driver->report_mode =  MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
}

function _setup_woocommerce_action_scheduler() {
	$actionSchedulerPackageFile = _get_3rdparty_plugin_base_dir('woocommerce') . '/packages/action-scheduler/action-scheduler.php';
	if (file_exists($actionSchedulerPackageFile)) {
		echo 'Action scheduler found. Need to setup...' . PHP_EOL;
		include $actionSchedulerPackageFile;
		$loggerSchema = new ActionScheduler_LoggerSchema();
		$loggerSchema->register_tables();
		$storeSchema = new ActionScheduler_StoreSchema();
		$storeSchema->register_tables();
	}
}

function _setup_woocommerce_admin() {
	\Automattic\WooCommerce\Admin\Install::create_tables();
	\Automattic\WooCommerce\Admin\Install::create_events();
}

function _manually_install_woocommerce() {
	if (_has_wc_been_installed_before()) {
		// Clean existing install first.
		define('WP_UNINSTALL_PLUGIN', true);
		define('WC_REMOVE_ALL_DATA', true);
		include _get_3rdparty_plugin_base_dir('woocommerce') . '/uninstall.php';
	}

	error_reporting(E_ALL & ~E_NOTICE);
	WC_Install::install();
	_setup_woocommerce_admin();
	_setup_woocommerce_action_scheduler();
	error_reporting(E_ALL);

	if ( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) ) {
		$GLOBALS['wp_roles']->reinit();
	} else {
		$GLOBALS['wp_roles'] = null;
		wp_roles();
	}

	_set_wc_installed();

	require_once 'lib/WcOrderProxy.php';
	require_once 'lib/WcOrderNotesTester.php';
	require_once 'lib/WcOrderProcessingTester.php';
}

function _manually_install_own_plugin() {
	$installer = new \LvdWcMc\Installer();
	if (_has_own_plugin_been_installed_before()) {
		$installer->uninstall();
	}
	
	$activated = $installer->activate();
	if (!$activated) {
		die('Failed to activate plugin. Cannot continue testing.' . PHP_EOL);
	}

	_set_own_plugin_installed();

	require_once 'lib/AlwaysReturnNullMobilpayTransactionFactory.php';
	require_once 'lib/MobilpayTransactionProcessingTester.php';
}

function _manually_load_plugins() {
	require_once _get_woocommerce_plugin_path();
	require_once _get_own_plugin_path();
}

function _manually_install_plugins() {
	_disable_mysqli_strict_reporting();
	_manually_install_woocommerce();
	_manually_install_own_plugin();
	_enable_mysqli_strict_reporting();
}

function _sync_wp_tests_config($testsDir) {
	$thisConfig = _get_tests_base_dir() . '/wp-tests-config.php';
	$runtimeConfig = $testsDir . '/wp-tests-config.php';

	if (is_readable($thisConfig)) {
		echo sprintf('Local wp-tests-config.php found. Overriding %s.%s', 
			$runtimeConfig, 
			PHP_EOL);

		file_put_contents($runtimeConfig, file_get_contents($thisConfig));
	}
}

function _register_setup_actions() {
	tests_add_filter('muplugins_loaded', '_manually_load_plugins');
	tests_add_filter('setup_theme', '_manually_install_plugins');
}

_sync_wp_tests_config($_tests_dir);
_register_setup_actions();

require $_tests_dir . '/includes/bootstrap.php';