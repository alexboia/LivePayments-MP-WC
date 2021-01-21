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

class WordPressHookTester {
    const TEST_FILTER_HOOK_PRIORITY = 10;

    const TEST_ACTION_HOOK_PRIORITY = 10;

    private $_hookName;

    private $_hookType;

    private $_forwardArgIndex = 0;

    private $_wasCalled = false;

    private $_wasCalledWithArgs = array();

    private $_expectedNumberOfArgs = 0;

    private function __construct($hookType, $hookName, $expectedNumberOfArgs, $forwardArgIndex) {
        $this->_hookName = $hookName;
        $this->_hookType = $hookType;
        $this->_forwardArgIndex = $forwardArgIndex;
        $this->_expectedNumberOfArgs = $expectedNumberOfArgs;

        $this->_register();   
    }

    public static function forActionHook($hookName, $expectedNumberOfArgs) {
        return new self('action', $hookName, $expectedNumberOfArgs, 0);
    }

    public static function forFilterHook($hookName, $expectedNumberOfArgs, $forwardArgIndex = 0) {
        return new self('action', $hookName, $expectedNumberOfArgs, $forwardArgIndex);
    }

    private function _register() {
        if ($this->_hookType == 'action') {
            $this->_registerTestActionHook();
        } else if ($this->_hookType == 'filter') {
            $this->_registerTestFilterHook();
        }
    }

    private function _registerTestActionHook() {
        add_action($this->_hookName, 
            array($this, '__handleActionHook'), 
            self::TEST_ACTION_HOOK_PRIORITY, 
            $this->_expectedNumberOfArgs);
    }

    private function _registerTestFilterHook() {
        add_filter($this->_hookName, 
            array($this, '__handleFilterHook'), 
            self::TEST_FILTER_HOOK_PRIORITY, 
            $this->_expectedNumberOfArgs);
    }

    public function __handleActionHook() {
        $this->_wasCalled = true;
        $this->_wasCalledWithArgs = func_get_args(); 
    }

    public function __handleFilterHook() {
        $this->_wasCalled = true;
        $this->_wasCalledWithArgs = func_get_args();   
        return $this->_wasCalledWithArgs[$this->_forwardArgIndex];
    }

    public function wasCalledWithNumberOfArgs($expectedNumberOfArgs) {
        return $this->_wasCalled == true 
            && count($this->_wasCalledWithArgs) == $expectedNumberOfArgs;
    }

    public function unregister() {
        if ($this->_hookType == 'action') {
            $this->_unregisterTestActionHook();
        } else if ($this->_hookType == 'filter') {
            $this->_unregisterTestFilterHook();
        }
    }

    private function _unregisterTestActionHook() {
        remove_action($this->_hookName, 
            array($this, '__handleFilterHook'), 
            self::TEST_ACTION_HOOK_PRIORITY);
    }

    private function _unregisterTestFilterHook() {
        remove_filter($this->_hookName, 
            array($this, ''), 
            self::TEST_FILTER_HOOK_PRIORITY);
    }

    public function wasCalledWithSpecificArgs($expectedArgs) {
        $wasCalledAccordingly = false;

        if ($this->_wasCalled) {
            $expectedCount = count($expectedArgs);
            if (count($this->_wasCalledWithArgs) == $expectedCount) {
                $wasCalledAccordingly = true;
                for ($i = 0; $i < $expectedCount; $i ++) {
                    $actual = $this->_wasCalledWithArgs[$i];
                    $expected = $expectedArgs[$i];
                    if ($actual != $expected) {
                        $wasCalledAccordingly = false;
                        break;
                    }
                }
            }
        }

        return $wasCalledAccordingly;
    }

    public function wasCalled() {
        return $this->_wasCalled;
    }
}