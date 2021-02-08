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
    class WordPressAdminAjaxAction {
        private $_actionCode;

        private $_nonceUrlParam;

        private $_callback;

        private $_nonceActionCode;

        private $_requiresAuthentication = true;

        private $_requiredCapability = null;

        public function __construct($actionCode, $callback, $nonceUrlParam = 'lvdwcmc_nonce') {
            $this->_actionCode = $actionCode;
            $this->_callback = $callback;
            $this->_nonceUrlParam = $nonceUrlParam;
            $this->_nonceActionCode = $actionCode . '_nonce';
        }

        public function setRequiresAuthentication($requiresAuthentication) {
            $this->_requiresAuthentication = $requiresAuthentication;
            return $this;
        }

        public function setRequiredCapability($requiredPermission) {
            $this->_requiredCapability = $requiredPermission;
            return $this;
        }

        public function generateNonce() {
            return wp_create_nonce($this->_nonceActionCode);
        }

        public function isNonceValid() {
            return check_ajax_referer($this->_nonceActionCode, 
                $this->_nonceUrlParam, 
                false);
        }

        public function register() {
            $callback = array($this, 'executeAndSendJsonThenExit');

            add_action('wp_ajax_' . $this->_actionCode,
                $callback);

            if (!$this->_requiresAuthentication) {
                add_action('wp_ajax_nopriv_' . $this->_actionCode, 
                    $callback);
            }

            return $this;
        }

        public function execute() {
            if (!$this->isNonceValid() 
                || !$this->_currentUserCanExecute()) {
                die;
            }

            return call_user_func($this->_callback);
        }

        public function executeAndSendJsonThenExit() {
            $result = $this->execute();
            $this->_sendJsonAndExit($result);
        }

        private function _currentUserCanExecute() {
            return empty($this->_requiredCapability) 
                || current_user_can($this->_requiredCapability);
        }

        private function _sendJsonAndExit($data) {
            lvdwcmc_send_json($data, true);
        }
    }
}