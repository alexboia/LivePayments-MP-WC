<?php
namespace LvdWcMc {
    trait LoggingExtensions {
        public function logDebug($message, $context = array()) {
            $this->getLogger()->debug($message, $context);
        }

        public function logException($message, \Exception $exc, $context = array()) {
            $context = array_merge($context, array(
                'error_message' => $exc->getMessage(),
                'error_type' => get_class($exc),
                'error_location' => sprintf('%s:%s', $exc->getFile(), $exc->getLine()),
                'error_info' => $exc->getTraceAsString()
            ));
            $this->getLogger()->error($message, $context);
        }

        /**
         * @return \WC_Logger
         */
        abstract public function getLogger();
    }
}