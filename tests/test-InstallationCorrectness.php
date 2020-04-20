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

use \LvdWcMc\Installer;

class InstallationCorrectnessTests extends WP_UnitTestCase {
    use GenericTestHelpers;

    public function test_correctVersionNumber() {
        $expectedVersion = $this->_getEnv()->getVersion();
        $actualVersion = get_option(Installer::OPT_VERSION);

        $this->assertEquals(LVD_WCMC_VERSION, 
            $expectedVersion);

        $this->assertEquals($expectedVersion, 
            $actualVersion);
    }

    public function test_dbTablesArePresent() {
        $env = $this->_getEnv();

        $checkTables = array(
            $env->getPaymentTransactionsTableName()
        );

        $db = $env->getMetaDb();

        $db->where('TABLE_SCHEMA', $env->getDbName())
            ->where('TABLE_NAME', $checkTables, 'IN');

        $checkedTables = $db->get('TABLES', null, 'TABLE_NAME');

        $this->assertEquals(count($checkTables), 
            count($checkedTables));

        foreach ($checkedTables as $table) {
            $this->assertContains($table['TABLE_NAME'], $checkTables, '', true);
        }
    }

    public function test_storageDirectoriesArePresent() {
        $env = $this->_getEnv();

        $checkDirs = array(
            $env->getRootStorageDir() => array(
                'index.php'
            ),
            $env->getPaymentAssetsStorageDir() => array(
                'index.php',
                '.htaccess'
            )
        );

        foreach ($checkDirs as $dir => $checkAssets) {
            $this->assertDirectoryIsReadable($dir);

            foreach ($checkAssets as $asset) {
                $checkAssetFile = $dir . DIRECTORY_SEPARATOR . $asset;
                $this->assertFileIsReadable($checkAssetFile);
            }
        }
    }
}