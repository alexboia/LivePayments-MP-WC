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
    class PluginDependencyChecker {
        /**
         * @var array
         */
        private $_requiredPluginsSpec = array();

        /**
         * @var array
         */
        private $_missingRequiredPlugins = array();

        public function __construct(array $requiredPluginsSpec) {
            $this->_requiredPluginsSpec = $requiredPluginsSpec;
        }

        public function checkIfDependenciesSatisfied() {
            $this->_reset();
            $areAllDependenciesSatisfied = true;

            if ($this->hasRequiredPlugins()) {
                $areAllDependenciesSatisfied = $this->_doCheckIfDependenciesSatisfied();
            }

            return $areAllDependenciesSatisfied;
        }

        private function _doCheckIfDependenciesSatisfied() {
            $areAllDependenciesSatisfied = true;
            foreach ($this->_requiredPluginsSpec as $plugin => $checker) {
                if (!$checker()) {
                    $this->_missingRequiredPlugins[] = $plugin;
                    $areAllDependenciesSatisfied = false;
                }
            }
            return $areAllDependenciesSatisfied;
        }

        public function hasMissingRequiredPlugins() {
            return count($this->_missingRequiredPlugins) > 0;
        }

        public function getMissingRequiredPlugins() {
            return $this->_missingRequiredPlugins;
        }

        public function hasRequiredPlugins() {
            return count($this->_requiredPluginsSpec) > 0;
        }

        private function _reset() {
            $this->_missingRequiredPlugins = array();
        }
    }
}