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

use \LvdWcMc\DataExtensions;

class DataExtensionsTests extends WP_UnitTestCase {
    use DataExtensions;
    use GenericTestHelpers;

    public function test_canMergeAdditionalData_nonEmptyToEmptyTarget() {
        $target = new \stdClass();
        $source = $this->_generateSourceData();

        $result = $this->mergeAdditionalData($target, $source);
        foreach ($source as $key => $value) {
            $this->assertEquals($result->{$key}, $value);
        }
    }

    public function test_canMergeAdditionalData_emptyToNonEmptyTarget() {
        $faker = self::_getFaker();
        $one = $faker->randomAscii;
        $two = $faker->randomAscii;

        $target = new \stdClass();
        $target->one = $one;
        $target->two = $two;

        $result = $this->mergeAdditionalData($target, array());
        $this->assertEquals($one, $result->one);
        $this->assertEquals($two, $result->two);
    }

    public function test_canMergeAdditionalData_nonEmptyToNonEmptyTarget_noPropertyOverlap() {
        $faker = self::_getFaker();
        $one = $faker->randomAscii;
        $two = $faker->randomAscii;

        $target = new \stdClass();
        $target->one = $one;
        $target->two = $two;
        $source = $this->_generateSourceData();

        $result = $this->mergeAdditionalData($target, $source);
        foreach ($source as $key => $value) {
            $this->assertEquals($result->{$key}, $value);
        }

        $this->assertEquals($one, $result->one);
        $this->assertEquals($two, $result->two);
    }

    public function test_canMergeAdditionalData_nonEmptyToNonEmptyTarget_withOverlap() {
        $faker = self::_getFaker();
        $one = $faker->randomAscii;
        $two = $faker->randomAscii;

        $target = new \stdClass();
        $target->one = $one;
        $target->two = $two;
        $source = $this->_generateSourceData();

        $result = $this->mergeAdditionalData($target, array_merge($source, array(
            'one' => $faker->randomNumber(),
            'two' => $faker->randomNumber()
        )));

        foreach ($source as $key => $value) {
            $this->assertEquals($result->{$key}, $value);
        }

        $this->assertEquals($one, $result->one);
        $this->assertEquals($two, $result->two);
    }

    private function _generateSourceData($count = null) {
        $data = array();
        $faker = self::_getFaker();

        if ($count === null) {
            $count = $faker->numberBetween(1, 100);
        }

        for ($i = 0; $i < $count; $i ++) {
            $data[$faker->asciify('var******')] = $faker->text();
        }

        return $data;
    }
}