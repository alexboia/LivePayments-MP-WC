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

namespace LvdWcMc {
    class Env {
        private $_dbHost;

        private $_dbUserName;

        private $_dbPassword;

        private $_dbTablePrefix;

        private $_dbName;

        private $_dbCollate;

        private $_dbCharset;

        private $_paymentTransactionsTable;

        private $_db = null;

        private $_rootStorageDir;

        private $_paymentAssetsStorageDir;

        private $_dataDir;

        private $_setupDataDir;

        private $_viewsDir;

        private $_version;

        private $_lang;

        private $_isDebugMode;

        private $_phpVersion;

        private $_wpVersion;

        public function __construct() {
            $this->_lang = get_locale();
            $this->_isDebugMode = defined('WP_DEBUG') && WP_DEBUG == true;

            $this->_initVersions();
            $this->_initDbSettings();
            $this->_initStorageAndDataDirs();
        }

        private function _initVersions() {
            $this->_phpVersion = PHP_VERSION;
            $this->_wpVersion = get_bloginfo('version', 'raw');
            $this->_version = LVD_WCMC_VERSION;
        }

        private function _initDbSettings() {
            $this->_dbHost = DB_HOST;
            $this->_dbUserName = DB_USER;
            $this->_dbPassword = DB_PASSWORD;
            $this->_dbName = DB_NAME;
            
            $this->_dbCollate = defined('DB_COLLATE') 
                ? DB_COLLATE 
                : null;

            $this->_dbCharset = defined('DB_CHARSET') 
                ? DB_CHARSET 
                : null;

            $this->_dbTablePrefix = isset($GLOBALS['table_prefix']) 
                ? $GLOBALS['table_prefix']
                : 'wp_';

            $this->_paymentTransactionsTable = $this->_dbTablePrefix 
                . 'lvdwcmc_mobilpay_transactions';
        }

        private function _initStorageAndDataDirs() {
            $wpUploadsDirInfo = wp_upload_dir();

            $this->_rootStorageDir = wp_normalize_path(sprintf('%s/wc-mobilpayments-card', 
                $wpUploadsDirInfo['basedir']));

            $this->_paymentAssetsStorageDir = wp_normalize_path(sprintf('%s/mobilpay-assets', 
                $this->_rootStorageDir));

            $this->_dataDir = LVD_WCMC_DATA_DIR;
            $this->_viewsDir = LVD_WCMC_VIEWS_DIR;

            $this->_setupDataDir = wp_normalize_path(sprintf('%s/setup', 
                $this->_dataDir));
        }

        public function isPluginActive($plugin) {
            if (!function_exists('is_plugin_active')) {
                return in_array($plugin, (array)get_option('active_plugins', array()));
            } else {
                return is_plugin_active($plugin);
            }
        }

        public function isViewingFrontendWcOrder() {
            return is_wc_endpoint_url('view-order');
        }

        public function isEditingWcOrder() {
            if ($this->getCurrentPage() == 'post.php' && !empty($_GET['post'])) {
                $order = wc_get_order(intval($_GET['post']));
                return !empty($order) && ($order instanceof \WC_Order);
            } else {
                return false;
            }
        }

        public function isViewingAdminTransactionListing() {
            return $this->getCurrentPage() == 'admin.php' 
                && isset($_GET['page']) 
                && $_GET['page'] == 'lvdwcmc-card-transactions-listing';
        }

        public function getCurrentPage() {
            return isset($GLOBALS['pagenow']) 
                ? strtolower($GLOBALS['pagenow']) 
                : null;
        }

        public function getTheOrder() {
            return isset($GLOBALS['theorder']) 
                ? $GLOBALS['theorder'] 
                : null;
        }

        public function getDbHost() {
            return $this->_dbHost;
        }

        public function getDbUserName() {
            return $this->_dbUserName;
        }

        public function getDbPassword() {
            return $this->_dbPassword;
        }

        public function getDbTablePrefix() {
            return $this->_dbTablePrefix;
        }

        public function getDbName() {
            return $this->_dbName;
        }

        public function getDbCollate() {
            return $this->_dbCollate;
        }

        public function getDbCharset() {
            return $this->_dbCharset;
        }

        public function getDb() {
            if ($this->_db === null) {
                $this->_db = new \MysqliDb(array(
                    'host' => $this->_dbHost,
                    'username' => $this->_dbUserName, 
                    'password' => $this->_dbPassword,
                    'db'=> $this->_dbName,
                    'port' => 3306,
                    'prefix' => '',
                    'charset' => 'utf8'
                ));

                $driver = new \mysqli_driver();
                $driver->report_mode =  MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
            }
            return $this->_db;
        }

        public function getPaymentTransactionsTableName() {
            return $this->_paymentTransactionsTable;
        }

        public function getPostsTableName() {
            return $this->_dbTablePrefix . 'posts';
        }

        public function getViewFilePath($viewFile) {
            return $this->_viewsDir . DIRECTORY_SEPARATOR . $viewFile;
        }

        public function getRootStorageDir() {
            return $this->_rootStorageDir;
        }

        public function getPaymentAssetsStorageDir() {
            return $this->_paymentAssetsStorageDir;
        }

        public function getDataDir() {
            return $this->_dataDir;
        }

        public function getSetupDataDir() {
            return $this->_setupDataDir;
        }

        public function getPhpVersion() {
            return $this->_phpVersion;
        }

        public function getWpVersion() {
            return $this->_wpVersion;
        }

        public function getVersion() {
            return $this->_version;
        }

        public function getRemoteAddress() {
            return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        }
        public function isHttpPost() {
            return strtolower($_SERVER['REQUEST_METHOD']) === 'post';
        }
    
        public function isHttpGet() {
            return strtolower($_SERVER['REQUEST_METHOD']) === 'get';
        }
    

        public function getRequiredPhpVersion() {
            return '5.6.2';
        }

        public function getRequiredWpVersion() {
            return '5.0';
        }
    }
}