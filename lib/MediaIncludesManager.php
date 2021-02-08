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
    class MediaIncludesManager {
        private $_refPluginsPath;

        private $_scriptsInFooter;

        private $_styles = array();

        private $_scripts = array();

        public function __construct(array $scripts,
            array $styles, 
            $refPluginsPath, 
            $scriptsInFooter) {

            if (empty($refPluginsPath)) {
                throw new \InvalidArgumentException('The $refPluginsPath parameter is required and may not be empty.');
            }

            $this->_refPluginsPath = $refPluginsPath;
            $this->_scriptsInFooter = $scriptsInFooter;

            $this->_scripts = $scripts;
            $this->_styles = $styles;
        }

        private function _hasScript($handle) {
            return !empty($this->_scripts[$handle]);
        }
    
        private function _hasStyle($handle) {
            return !empty($this->_styles[$handle]);
        }

        private function _getActualElement($handle, array &$collection) {
            $script = null;
            $actual = null;
    
            if (isset($collection[$handle])) {
                $script = $collection[$handle];
                if (!empty($script['alias'])) {
                    $handle = $script['alias'];
                    $actual = isset($collection[$handle]) 
                        ? $collection[$handle]
                        : null;
                }
    
                if (!empty($actual)) {
                    $deps = isset($script['deps']) 
                        ? $script['deps'] 
                        : null;
                    if (!empty($deps)) {
                        $actual['deps'] = $deps;
                    }
                } else {
                    $actual = $script;
                }
            }
    
            return $actual;
        }

        private function _getActualScriptToInclude($handle) {
            return $this->_getActualElement($handle, $this->_scripts);
        }
    
        private function _getActualStyleToInclude($handle) {
            return $this->_getActualElement($handle, $this->_styles);
        }

        private function _ensureScriptDependencies(array $deps) {
            foreach ($deps as $depHandle) {
                if ($this->_hasScript($depHandle)) {
                    $this->enqueueScript($depHandle);
                }
            }
        }
    
        private function _ensureStyleDependencies(array $deps) {
            foreach ($deps as $depHandle) {
                if ($this->_hasStyle($depHandle)) {
                    $this->enqueueStyle($depHandle);
                }
            }
        }

        public function enqueueScript($handle) {
            if (empty($handle)) {
                return;
            }

            if (isset($this->_scripts[$handle])) {
                if (!wp_script_is($handle, 'registered')) {
                    $script = $this->_getActualScriptToInclude($handle);

                    $deps = isset($script['deps']) && is_array($script['deps']) 
                        ? $script['deps'] 
                        : array();

                    if (!empty($deps)) {
                        $this->_ensureScriptDependencies($deps);
                    }
    
                    wp_enqueue_script($handle, 
                        plugins_url($script['path'], $this->_refPluginsPath), 
                        $deps, 
                        $script['version'], 
                        $this->_scriptsInFooter);

                    if (isset($script['inline-setup'])) {
                        wp_add_inline_script($handle, $script['inline-setup']);
                    }
                } else {
                    wp_enqueue_script($handle);
                }
            } else {
                wp_enqueue_script($handle);
            }
        }

        public function enqueueStyle($handle) {
            if (empty($handle)) {
                return;
            }

            if (isset($this->_styles[$handle])) {
                $style = $this->_getActualStyleToInclude($handle);

                if (!isset($style['media']) || !$style['media']) {
                    $style['media'] = 'all';
                }

                $deps = isset($style['deps']) && is_array($style['deps']) 
                    ? $style['deps'] 
                    : array();

                if (!empty($deps)) {
                    $this->_ensureStyleDependencies($deps);
                }

                wp_enqueue_style($handle, 
                    plugins_url($style['path'], $this->_refPluginsPath), 
                    $deps, 
                    $style['version'], 
                    $style['media']);
            } else {
                wp_enqueue_style($handle);
            }
        }
    }
}