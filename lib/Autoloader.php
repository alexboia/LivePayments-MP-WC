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
    class Autoloader {
        private static $_defaultLibDir;

        private static $_initialized = false;

        private static $_prefixConfig = null;

        public static function init($defaultLibDir, $prefixConfig) {
            if (empty($defaultLibDir)) {
                throw new \InvalidArgumentException('The $libDir parameter is required and may not be empty.');
            }

            if (empty($prefixConfig) || !is_array($prefixConfig)) {
                throw new \InvalidArgumentException('The $prefixConfig parameter is required and may not be empty.');
            }

            if (!self::$_initialized) {
                self::$_defaultLibDir = $defaultLibDir;
                self::$_prefixConfig = $prefixConfig;
                self::$_initialized = true;
                spl_autoload_register(array(__CLASS__, 'autoload'));
            }
        }

        private static function autoload($className) {
            $classPath = null;

            foreach (self::$_prefixConfig as $prefix => $config) {
                $fullPrefix = $prefix . $config['separator'];
                if (strpos($className, $fullPrefix) === 0) {
                    $classPath = str_replace($fullPrefix, '', $className);
                    $classPath = self::_getRelativePath($classPath, $config['separator']);
                    $classPath = $config['libDir'] . '/' . $classPath . '.php';
                    break;
                }
            }

            if (empty($classPath)) {
                $classPath = self::$_defaultLibDir . '/3rdParty/' . $className . '.php';
            }

            if (!empty($classPath) && file_exists($classPath)) {
                require_once $classPath;
            }
        }

        private static function _getRelativePath($className, $separator) {
            $classPath = array();
			$pathParts = array_filter(explode($separator, $className), function($el) {
				return !empty($el);
			});
            $className = array_pop($pathParts);
            foreach ($pathParts as $namePart) {
                if (!empty($namePart)) {
                    $namePart[0] = strtolower($namePart[0]);
                    $classPath[] = $namePart;
                }
            }
            $classPath[] = $className;
            return implode('/', $classPath);
        }
    }
}