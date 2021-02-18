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

namespace LvdWcMc {
    class Installer {
        /**
         * @var int Code for successful install-related operations
         */
        const INSTALL_OK = 0;

        /**
         * @var int Error code returned when an incompatible PHP version is detected upon installation
         */
        const INCOMPATIBLE_PHP_VERSION = 1;

        /**
         * @var int Error code returned when an incompatible WordPress version is detected upon installation
         */
        const INCOMPATIBLE_WP_VERSION = 2;

        /**
         * @var int Error code returned when MySqli extension is not found
         */
        const SUPPORT_MYSQLI_NOT_FOUND = 3;

        /**
         * @var int Error code returned when OpenSsl extension is not found
         */
        const SUPPORT_OPENSSL_NOT_FOUND = 4;

        /**
         * @var int Generic error code
         */
        const GENERIC_ERROR = PHP_INT_MAX;

        /**
         * @var string WP options key for current plug-in version
         */
        const OPT_VERSION = LVD_WCMC_PLUGIN_ID . '_plugin_version';

        /**
         * @var \LvdWcMc\Env Reference to the environment object
         */
        private $_env;

        /**
         * @var \Exception Reference to the last exception occured whilst running an installer action
         */
        private $_lastError = null;

        public function __construct() {
            $this->_env = lvdwcmc_get_env();
        }
		
		public static function wasInstallationTestSuccessful($testInstallationErrorCode) {
            return $testInstallationErrorCode === self::INSTALL_OK;
        }

        /**
         * Retrieves the current plug-in version (not the currently installed one, 
         *	but the one in the package currently being run)
         * @return string The current plug-in version
         */
        private function _getVersion() {
            return $this->_env->getVersion();
        }

        /**
         * Checks whether or not an update is needed
         * @param string $version The version to be installed
         * @param string $installedVersion The version to be installed
         * @return void
         */
        private function _isUpdatedNeeded($version, $installedVersion) {
            return $version != $installedVersion;
        }

        /**
         * Retrieve the currently installed version (which may be different 
         *	than the one in the package currently being run)
         * @return String The version
         */
        private function _getInstalledVersion() {
            $version = null;
            if (function_exists('get_option')) {
                $version = get_option(self::OPT_VERSION, null);
            }
            return $version;
        }

        /**
         * Carry out the update operation
         * @param String $version The version to be installed
         * @param String $installedVersion The version currently being installed
         * @return Boolean Whether the operation succeeded or not
         */
        private function _update($version, $installedVersion) {
            $this->_reset();
            update_option(self::OPT_VERSION, $version);
            return self::INSTALL_OK;
        }

        /**
         * Checks the current plug-in package version, the currently installed version
         *  and runs the update operation if they differ
         * 
         * @return Integer The operation result
         */
        public function updateIfNeeded() {
            $result = self::INSTALL_OK;
            $version = $this->_getVersion();
            $installedVersion = $this->_getInstalledVersion();

            if ($this->_isUpdatedNeeded($version, $installedVersion)) {
                $result = $this->_update($version, $installedVersion);
            }

            return $result;
        }

        /**
         * Checks whether the plug-in can be installed and returns 
         *  a code that describes the reason it cannot be installed
         *  or Installer::INSTALL_OK if it can.
         * 
         * @return Integer The error code that describes the result of the test.
         */
        public function canBeInstalled() {
            $this->_reset();
            try {
                if (!$this->_isCompatPhpVersion()) {
                    return self::INCOMPATIBLE_PHP_VERSION;
                }
                if (!$this->_isCompatWpVersion()) {
                    return self::INCOMPATIBLE_WP_VERSION;
                }
                if (!$this->_hasMysqli()) {
                    return self::SUPPORT_MYSQLI_NOT_FOUND;
                }
                if (!$this->_hasOpenSsl()) {
                    return self::SUPPORT_OPENSSL_NOT_FOUND;
                }
            } catch (\Exception $e) {
                $this->_lastError = $e;
            }

            return empty($this->_lastError) 
                ? self::INSTALL_OK 
                : self::GENERIC_ERROR;
        }

        /**
         * Activates the plug-in. 
         * If a step of the activation process fails, 
         *  the plug-in attempts to rollback the steps that did successfully execute.
         * The activation process is idempotent, that is, 
         *  it will not perform the same operations twice.
         * 
         * @return bool True if the operation succeeded, false otherwise.
         */
        public function activate() {
            $this->_reset();
            try {
                //Install database tables
                if (!$this->_installSchema()) {
                    return false;
                }

                //Install options, for instance, 
                //  store plug-in version in wp_options table.
                if (!$this->_installSettings()) {
                    //If operation fails, rollback database tables installation
                    $this->_uninstallSchema();
                    return false;
                }

                //Install plug-in assets
                if (!$this->_installAssets()) {
                    //If operation fails, rollback previous steps
                    $this->_uninstallSchema();
                    $this->_uninstallSettings();
                    return false;
                }

                return true;
            } catch (\Exception $exc) {
                $this->_lastError = $exc;
            }

            return false;
        }

        /**
         * Deactivates the plug-in.
         * If a step of the activation process fails, 
         *  the plug-in attempts to rollback the steps 
         *  that did successfully execute.
         * 
         * @return bool True if the operation succeeded, false otherwise. 
         */
        public function deactivate() {
            $this->_reset();
            return true;
        }

        public function uninstall() {
            $this->_reset();
            try {
                if ($this->deactivate()) {
                    $this->_uninstallSchema();
                    $this->_uninstallAssets();
                    $this->_uninstallSettings();
                    return true;
                }
            } catch (\Exception $exc) {
                $this->_lastError = $exc;
            }

            return false;
        }

        /**
         * Ensures all the plug-in's storage directories are created, 
         *  as well as any required assets.
         * If a directory exists, it is not re-created, nor is it purged.
         * If a file asset exists, it is overwritten.
         * 
         * @return bool True if the operation succeeded, false otherwise
         */
        private function _installAssets() {
            $result = false;
            $rootStorageDir = $this->_env->getRootStorageDir();
            $paymentAssetsStorageDir = $this->_env->getPaymentAssetsStorageDir();

            if (!is_dir($rootStorageDir)) {
                mkdir($rootStorageDir);
            }

            if (is_dir($rootStorageDir)) {
                $rootAssets = array(
                    array(
                        'name' => 'index.php',
                        'contents' => $this->_getGuardIndexPhpFileContents(3),
                        'type' => 'file'
                    )
                );

                if (!$this->_installAssetsForDirectory($rootStorageDir, $rootAssets)) {
                    return false;
                }

                if (!is_dir($paymentAssetsStorageDir)) {
                    mkdir($paymentAssetsStorageDir);
                }

                if (is_dir($paymentAssetsStorageDir)) {
                    $paymentAssets = array(
                        array(
                            'name' => 'index.php',
                            'contents' => $this->_getGuardIndexPhpFileContents(4),
                            'type' => 'file'
                        ),
                        array(
                            'name' => '.htaccess',
                            'contents' => $this->_getPaymentAssetsGuardHtaccessFileContents(),
                            'type' => 'file'
                        )
                    );
                    $result = $this->_installAssetsForDirectory($paymentAssetsStorageDir, $paymentAssets);
                }
            }

            return $result;
        }

        private function _installAssetsForDirectory($targetDir, $assetsDesc) {
            foreach ($assetsDesc as $asset) {
                $result = false;
                $assetPath = wp_normalize_path(sprintf('%s/%s', 
                    $targetDir, 
                    $asset['name']));

                if ($asset['type'] == 'file') {
                    $assetHandle = @fopen($assetPath, 'w+');
                    if ($assetHandle) {
                        fwrite($assetHandle, $asset['contents']);
                        fclose($assetHandle);
                        $result = true;
                    }
                } else if ($asset['type'] == 'directory') {
                    if (!is_dir($assetPath)) {
                        @mkdir($assetPath);
                    }
                    $result = is_dir($assetPath);
                }

                if (!$result) {
                    return false;
                }
            }
            return true;
        }

        private function _uninstallAssets() {
            $rootStorageDir = $this->_env->getRootStorageDir();
            $assetsStorageDir = $this->_env->getPaymentAssetsStorageDir();

            if (is_dir($assetsStorageDir) && ($assets = scandir($assetsStorageDir, SCANDIR_SORT_NONE)) !== false) {
                foreach ($assets as $asset) {
                    if ($asset != '.' && $asset != '..') {
                        $fullAssetPath = wp_normalize_path(sprintf('%s/%s', 
                            $assetsStorageDir, 
                            $asset));

                        @unlink($fullAssetPath);
                    }
                }
                @rmdir($assetsStorageDir);
            }

            if (is_dir($rootStorageDir)) {
                @rmdir($rootStorageDir);
            }

            return true;
        }

        private function _installSettings() {
            $version = get_option(self::OPT_VERSION);
            if (empty($version)) {
                update_option(self::OPT_VERSION, $this->_env->getVersion());
            }
            return true;
        }

        /**
         * Deletes the plug-in settings.
         * 
         * @return true True if the operation succeeded, false othwerise
         */
        private function _uninstallSettings() {
            delete_option(self::OPT_VERSION);
            return true;
        }

        private function _installSchema() {
            $result = true;
            $tables = array(
                $this->_getPaymentTransactionsTableDefinition()
            );

            foreach ($tables as $table) {
                $result = $result && $this->_createTable($table);
            }
    
            if (!$result) {
                $this->_uninstallSchema();
            }
    
            return $result;
        }

        private function _uninstallSchema() {
            return $this->_uninstallPaymentTransactionsTable();
        }

        private function _createTable($tableDef) {
            $db = $this->_env->getDb();
            if (!$db) {
                return false;
            }
    
            $charset = $this->_getDefaultCharset();
            $collate = $this->_getDefaultCollate();
    
            if (!empty($charset)) {
                $charset = "DEFAULT CHARACTER SET = '" . $charset . "'";
                $tableDef .= ' ' . $charset . ' ';
            }
            if (!empty($collate)) {
                $collate = "COLLATE = '" . $collate . "'";
                $tableDef .= ' ' . $collate . ' ';
            }
    
            $tableDef .= ' ';
            $tableDef .= 'ENGINE=InnoDB';

            $db->rawQuery($tableDef, null, false);
            $lastError = trim($db->getLastError());
    
            return empty($lastError);
        }

        private function _getPaymentTransactionsTableDefinition() {
            return "CREATE TABLE IF NOT EXISTS `" . $this->_getPaymentTransactionsTableName() . "` (
                `tx_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `tx_order_id` BIGINT(20) NOT NULL,
                `tx_order_user_id` BIGINT(20) NOT NULL,
                `tx_provider` VARCHAR(50) NOT NULL,
                `tx_transaction_id` VARCHAR(255) NOT NULL,
                `tx_provider_transaction_id` VARCHAR(255),
                `tx_status` VARCHAR(255) NOT NULL,
                `tx_error_code` INT(11),
                `tx_error_message` TEXT,
                `tx_amount` DOUBLE NOT NULL DEFAULT 0,
                `tx_processed_amount` DOUBLE NOT NULL DEFAULT 0,
                `tx_currency` VARCHAR(10) NOT NULL DEFAULT 'RON',
                `tx_pan_masked` VARCHAR(150) DEFAULT NULL,
                `tx_timestamp_initiated` DATETIME NOT NULL,
                `tx_timestamp_last_updated` DATETIME,
                `tx_ip_address` VARCHAR(255) NOT NULL,
                    PRIMARY KEY (`tx_id`),
                    UNIQUE INDEX `tx_order_id` (`tx_order_id`),
	                INDEX `tx_timestamp_initiated` (`tx_timestamp_initiated`)
            )";
        }

        private function _uninstallPaymentTransactionsTable() {
            $db = $this->_env->getDb();
            return $db != null 
                ? $db->rawQuery('DROP TABLE IF EXISTS `' . $this->_getPaymentTransactionsTableName() . '`', null, false) 
                : false;
        }

        
        /**
         * Checks whether the current PHP version is compatible 
         * with the plug-in's minimum required PHP version.
         * 
         * @return bool True if compatible, false otherwise
         */
        private function _isCompatPhpVersion() {
            $current = $this->_env->getPhpVersion();
            $required = $this->_env->getRequiredPhpVersion();
            return version_compare($current, $required, '>=');
        }

        /**
         * Checks whether the current WP version is compatible 
         * with the plug-in's minimum required WP version.
         * 
         * @return bool True if compatible, false otherwise
         */
        private function _isCompatWpVersion() {
            $current = $this->_env->getWpVersion();
            $required = $this->_env->getRequiredWpVersion();
            return version_compare($current, $required, '>=');
        }

        private function _hasMysqli() {
            return extension_loaded('mysqli') &&
                class_exists('mysqli_driver') &&
                class_exists('mysqli');
        }

        private function _hasOpenSsl() {
            return extension_loaded('openssl') &&
                function_exists('openssl_pkey_get_public') &&
                function_exists('openssl_get_privatekey');
        }

        private function _getPaymentAssetsGuardHtaccessFileContents() {
            return join("\n", array(
                '<FilesMatch "\.key">',
                    "\t" . 'order allow,deny',
                    "\t" . 'deny from all',
                '</FilesMatch>',
                '<FilesMatch "\.cer">',
                    "\t" . 'order allow,deny',
                    "\t" . 'deny from all',
                '</FilesMatch>'
            ));
        }

        private function _getGuardIndexPhpFileContents($redirectCount) {
            return '<?php header("Location: ' . str_repeat('../', $redirectCount) . 'index.php"); exit;';
        }

        /**
         * Resets the last occurred error
         * @return void 
         */
        private function _reset() {
            $this->_lastError = null;
        }

        private function _getPaymentTransactionsTableName() {
            return $this->_env->getPaymentTransactionsTableName();
        }

        private function _getDefaultCharset() {
            return $this->_env->getDbCharset();
        }
    
        private function _getDefaultCollate() {
            return $this->_env->getDbCollate();
        }

        /**
         * Returns the last occurred exception or null if none found.
         * 
         * @return \Exception The last occurred exception.
         */
        public function getLastError() {
            return $this->_lastError;
        }
    }
}