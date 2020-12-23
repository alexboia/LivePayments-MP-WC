<?php
namespace LvdWcMc {
    class PluginViewEngine {
        /**
         * @var \LvdWcMc\Env
         */
        private $_env;

        public function __construct() {
            $this->_env = lvdwcmc_get_env();
        }

        public function renderView($file, \stdClass $data) {
            ob_start();
            require $this->_env->getViewFilePath($file);
            return ob_get_clean();
        }
    }
}