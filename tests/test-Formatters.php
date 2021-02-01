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

use \LvdWcMc\Formatters;
use \LvdWcMc\MobilpayTransaction;
use LvdWcMc\MobilpayTransactionFactory;
use ParagonIE\Sodium\Core\Curve25519\Ge\P2;

class FormattersTests extends WP_UnitTestCase {
    use MobilpayTransactionTestHelpers;
    use DbTestHelpers;

    private static $_roleKey;

    private static $_initialRoleData;

    private static $_canManageWooCommercePerRoles = array(
        'administrator' => true,
        'shop_manager' => true,
        'author' => false,
        'subscriber' => false
    );

    private static $_testUsers = array();

    private $_txData = array();

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        self::$_roleKey = wp_roles()->role_key;
        self::$_initialRoleData = get_option(self::$_roleKey, array());

        foreach (self::$_canManageWooCommercePerRoles as $roleName => $canManageWooCommerce) {
            $role = get_role($roleName);
            if ($role == null) {
                add_role($roleName, $roleName);
            }

            if ($canManageWooCommerce) {
                if (!$role->has_cap('manage_woocommerce')) {
                    $role->add_cap('manage_woocommerce');
                }
            }

            $userId = self::factory()->user->create(array(
                'role' => $roleName
            ));

            self::$_testUsers[$roleName] = $userId;
        }
    }

    public static function tearDownAfterClass() {
        parent::tearDownAfterClass();

        update_option(self::$_roleKey, 
            self::$_initialRoleData);

        self::$_initialRoleData = array();
        self::$_testUsers = array();
    }

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function test_canGetDisplayableTransactionDetails() {
        $formatters = new Formatters();
        for ($i = 0; $i < 10; $i ++) {
            $transaction = $this->_generateMobilpayTransaction();
            foreach (self::$_testUsers as $roleName => $userId) {
                $canManageWooCommerce = self::$_canManageWooCommercePerRoles[$roleName];
                wp_set_current_user($userId);

                $data = $formatters->getDisplayableTransactionDetails($transaction);

                $this->assertNotNull($data);
                $this->_assertDisplayableTransactionDataMatchesTransaction($formatters, 
                    $transaction, 
                    $data, 
                    $canManageWooCommerce);
            }
        }
    }

    public function test_canGetDisplayableTransactionItemsList() {
        $formatters = new Formatters();
        for ($i = 0; $i < 10; $i ++) {
            $transaction = $this->_generateMobilpayTransaction();
            foreach (self::$_testUsers as $roleName => $userId) {
                $canManageWooCommerce = self::$_canManageWooCommercePerRoles[$roleName];
                wp_set_current_user($userId);

                $items = $formatters->getDisplayableTransactionItemsList($transaction);

                $this->assertNotNull($items);
                $this->assertNotEmpty($items);
                $this->_assertDisplayableTransactionItemsListMatchesTransaction($formatters, 
                    $transaction, 
                    $items, 
                    $canManageWooCommerce);
            }
        }
    }

    public function test_canFormatAmount() {
        $this->_runAmountFormatTests(0);
        $this->_runAmountFormatTests(wc_get_price_decimals());
        $this->_runAmountFormatTests(wc_get_price_decimals() + 1);
        $this->_runAmountFormatTests(max(0, wc_get_price_decimals() - 1));
    }

    public function test_canFormatTimestamp() {
        $faker = $this->_getFaker();
        $this->_runTimestampFormatTests(time());
        for ($i = 0; $i < 10; $i ++) {
            $this->_runTimestampFormatTests($faker->unixTime);
        }
    }

    private function _runAmountFormatTests($decimalsToGenerate) {
        $faker = $this->_getFaker();
        $formatters = new Formatters();
        for ($i = 0; $i < 10; $i ++) {
            $amount = $faker->randomFloat($decimalsToGenerate);
            $formattedAmount = $formatters->formatTransactionAmount($amount);
            $formattedAmountExpected = number_format($amount, 
                wc_get_price_decimals(), 
                wc_get_price_decimal_separator(), 
                wc_get_price_thousand_separator());

            $this->assertEquals($formattedAmountExpected, $formattedAmount);
        }
    }

    private function _runTimestampFormatTests($nTimestamp) {
        $timestamp = date('Y-m-d H:i:s', $nTimestamp);
        $expectedFormat = date(get_option('date_format') . ' ' . get_option('time_format'), $nTimestamp);

        $formatters = new Formatters();
        $this->assertEquals($expectedFormat, $formatters->formatTransactionTimestamp($timestamp));
    }

    private function _assertDisplayableTransactionDataMatchesTransaction(Formatters $formatters, 
        MobilpayTransaction $tx, 
        stdClass $data, 
        $canManageWooCommerce) {

        $this->assertEquals($tx->getProviderTransactionId(), 
            $data->providerTransactionId); 
        $this->assertEquals(MobilpayTransaction::getStatusLabel($tx->getStatus()), 
            $data->status);
        $this->assertEquals($tx->getPANMasked(), 
            $data->panMasked);

        $this->assertEquals($formatters->formatTransactionAmount($tx->getAmount()), 
            $data->amount);
        $this->assertEquals($formatters->formatTransactionAmount($tx->getProcessedAmount()), 
            $data->processedAmount);
        $this->assertEquals($tx->getCurrency(), 
            $data->currency);

        $this->assertEquals($formatters->formatTransactionTimestamp($tx->getTimestampInitiated()), 
            $data->timestampInitiated);
        $this->assertEquals($formatters->formatTransactionTimestamp($tx->getTimestampLastUpdated()), 
            $data->timestampLastUpdated);

        $this->assertEquals($tx->getErrorCode(), 
            $data->errorCode);
        $this->assertEquals($tx->getErrorMessage(), 
            $data->errorMessage);

        if ($canManageWooCommerce) {
            $this->assertEquals($tx->getIpAddress(), $data->clientIpAddress);
        } else {
            $this->assertEmpty($data->clientIpAddress);
        }
    }

    private function _assertDisplayableTransactionItemsListMatchesTransaction(Formatters $formatters, 
        MobilpayTransaction $tx, 
        array $itemsList, 
        $canManageWooCommerce) {

        $this->_asserItemsListValue($itemsList, 
            'providerTransactionId', 
            $tx->getProviderTransactionId());
        $this->_asserItemsListValue($itemsList, 
            'status', 
            MobilpayTransaction::getStatusLabel($tx->getStatus()));
        $this->_asserItemsListValue($itemsList, 
            'panMasked', 
            $tx->getPANMasked());

        $this->_asserItemsListValue($itemsList, 
            'amount', 
            $formatters->formatTransactionAmount($tx->getAmount()) . ' ' . $tx->getCurrency());
        $this->_asserItemsListValue($itemsList, 
            'processedAmount', 
            $formatters->formatTransactionAmount($tx->getProcessedAmount()) . ' ' . $tx->getCurrency());

        $this->_asserItemsListValue($itemsList, 
            'timestampInitiated', 
            $formatters->formatTransactionTimestamp($tx->getTimestampInitiated()));
        $this->_asserItemsListValue($itemsList, 
            'timestampLastUpdated', 
            $formatters->formatTransactionTimestamp($tx->getTimestampLastUpdated()));

        if (!empty($tx->getErrorCode())) {
            $this->_asserItemsListValue($itemsList, 
                'errorCode', 
                $tx->getErrorCode());
            $this->_asserItemsListValue($itemsList, 
                'errorMessage', 
                $tx->getErrorMessage());
        } else {
            $this->_assertItemNotInItemsList($itemsList, 'errorCode');
            $this->_assertItemNotInItemsList($itemsList, 'errorMessage');
        }

        if ($canManageWooCommerce) {
            $this->_asserItemsListValue($itemsList, 
                'clientIpAddress', 
                $tx->getIpAddress());
        } else {
            $this->_assertItemNotInItemsList($itemsList, 'clientIpAddress');
        }
    }

    private function _asserItemsListValue($itemsList, $itemId, $expectedValue) {
        $found = false;
        foreach ($itemsList as $item) {
            if ($item['id'] == $itemId) {
                $this->assertNotEmpty($item['label']);
                $this->assertEquals($expectedValue, $item['value']);
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->fail('Item with id=' . $itemId . ', was expected, but not found.');
        }
    }

    private function _assertItemNotInItemsList($itemsList, $itemId) {
        $found = false;
        foreach ($itemsList as $item) {
            if ($item['id'] == $itemId) {
                $this->fail('Item with id=' . $itemId . ' was expected missing, but was found.');
                break;
            }
        }
    }
}