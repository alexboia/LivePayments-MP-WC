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
    class WooCommerceLoggingFormatter {
        private $_source;

        public function __construct($source) {
            $this->_source = $source;
        }

        public function interceptWooCommerceLogEntries() {
            add_filter('woocommerce_format_log_entry', 
                array($this, 'onFormatWooCommerceLogMessage'), 10, 2);
        }

        public function onFormatWooCommerceLogMessage($entry, $args) {
            return $this->_shouldFormatWooCommerceLogMessage($args)
                ? $this->_formatLogMessageEntryWithContextData($entry, $args) 
                : $entry;
        }

        private function _shouldFormatWooCommerceLogMessage($args) {
            return $this->_isLogMessageFromOurOwnSource($args);
        }

        private function _isLogMessageFromOurOwnSource($args) {
            return !empty($args['context']) && (
                empty($args['context']['source']) 
                    || $args['context']['source'] == $this->_source
            );
        }

        private function _formatLogMessageEntryWithContextData($entry, $args) {
            return sprintf('%s%s Additional context: %s', 
                $entry, 
                !$this->_entryEndsWithDot($entry) ? '.' : '',
                $this->_dumpLogMessageContextData($args));
        }

        private function _entryEndsWithDot($entry) {
            return !empty($entry) && $entry[strlen($entry) - 1] === '.';
        }

        private function _dumpLogMessageContextData($args) {
            $filteredContext = $this->_removeSourceFromLogMessageContext($args['context']);
            return !empty($filteredContext) 
                ? print_r($filteredContext, true) 
                : '<None>';
        }

        private function _removeSourceFromLogMessageContext($context) {
            if (isset($context['source'])) {
                unset($context['source']);
            }
            return $context;
        }
    }
}