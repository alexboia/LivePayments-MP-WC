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

class EnvTests extends WP_UnitTestCase {
    use GenericTestHelpers;

    public function test_canGetInstance() {
        $instance = lvdwcmc_get_env();
		$otherInstance = lvdwcmc_get_env();
		
		$this->assertNotNull($instance);
		$this->assertNotNull($otherInstance);
		
		$this->assertSame($instance, $otherInstance);
    }

    public function test_canReadDbParams() {
		$env = lvdwcmc_get_env();

		$this->assertEquals(DB_HOST, $env->getDbHost());
		$this->assertEquals(DB_USER, $env->getDbUserName());
		$this->assertEquals(DB_PASSWORD, $env->getDbPassword());
		$this->assertEquals(DB_NAME,$env->getDbName());
    }
    
    public function test_canReadDbTableParams() {
		$env = lvdwcmc_get_env();
		$dbTablePrefix = $env->getDbTablePrefix();

		$this->assertEquals($GLOBALS['table_prefix'], $dbTablePrefix);
        $this->assertEquals($dbTablePrefix . 'lvdwcmc_mobilpay_transactions', $env->getPaymentTransactionsTableName());
        $this->assertEquals($dbTablePrefix . 'posts', $env->getPostsTableName());
    }
    
    public function test_canGetVersions() {
		$env = lvdwcmc_get_env();

		$this->assertEquals(PHP_VERSION, $env->getPhpVersion());
		$this->assertEquals(get_bloginfo('version', 'raw'), $env->getWpVersion());
		$this->assertEquals('5.6.2', $env->getRequiredPhpVersion());
		$this->assertEquals('5.0', $env->getRequiredWpVersion());
		$this->assertEquals('0.1.4', $env->getVersion());
    }

    public function test_canGetDbObject() {
		$db = lvdwcmc_get_env()->getDb();
		$otherDb = lvdwcmc_get_env()->getDb();

		$this->assertNotNull($db);
		$this->assertNotNull($otherDb);
		$this->assertSame($db, $otherDb);

		$this->assertInstanceOf('MysqliDb', $db);
    }

    public function test_canGetDirectoriesPath() {
        $env = lvdwcmc_get_env();
        $wpUploadsDirInfo = wp_upload_dir();

        $this->assertEquals(LVD_WCMC_DATA_DIR, $env->getDataDir());
        $this->assertEquals(LVD_WCMC_VIEWS_DIR, $env->getViewDir());

        $this->assertEquals(wp_normalize_path(sprintf('%s/livepayments-mp-wc', $wpUploadsDirInfo['basedir'])), 
            $env->getRootStorageDir());
        $this->assertEquals(wp_normalize_path(sprintf('%s/livepayments-mp-wc/mobilpay-assets', $wpUploadsDirInfo['basedir'])), 
            $env->getPaymentAssetsStorageDir());
    }

    public function test_canGetRemoteAddr() {
        $faker = self::_getFaker();
        $old = isset($_SERVER['REMOTE_ADDR']) 
            ? $_SERVER['REMOTE_ADDR'] 
            : null;

        for ($i = 0; $i < 10; $i++) {
            $ip = $faker->ipv4;
            $_SERVER['REMOTE_ADDR'] = $ip;
            $this->assertEquals($ip, lvdwcmc_get_env()->getRemoteAddress());
        }

        $_SERVER['REMOTE_ADDR'] = $old;
    }

    public function test_canCheckIfHttpGet() {
        $this->_runHttpMethodTests('get');
    }

    public function test_canCheckIfHttpPost() {
        $this->_runHttpMethodTests('post');
    }

    public function test_tryCheckHttpMethod_others() {
        $this->_runHttpMethodTests('put');
        $this->_runHttpMethodTests('delete');
        $this->_runHttpMethodTests('patch');
        $this->_runHttpMethodTests('head');
    }

    private function _runHttpMethodTests($method) {
        $old = isset($_SERVER['REQUEST_METHOD']) 
            ? $_SERVER['REQUEST_METHOD'] 
            : null;

        $_SERVER['REQUEST_METHOD'] = $method;

        switch($method) {
            case 'get':
                $this->assertTrue(lvdwcmc_get_env()->isHttpGet());
                $this->assertFalse(lvdwcmc_get_env()->isHttpPost());
            break;
            case 'post':
                $this->assertFalse(lvdwcmc_get_env()->isHttpGet());
                $this->assertTrue(lvdwcmc_get_env()->isHttpPost());
            break;
            default:
                $this->assertFalse(lvdwcmc_get_env()->isHttpGet());
                $this->assertFalse(lvdwcmc_get_env()->isHttpPost());
            break;
        }

        $_SERVER['REQUEST_METHOD'] = $old;
    }
}