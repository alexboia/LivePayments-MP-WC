<?php
namespace LvdWcMc {
    class MobilpayCreditCardGatewayDiagnostics {
        
        /**
         * @var \LvdWcMc\MobilpayCreditCardGateway
         */
        private $_paymentGateway = null;

        public function __construct() {
            $gateways = WC()
                ->payment_gateways()
                ->payment_gateways;

            foreach ($gateways as $g) {
                if ($g->id == MobilpayCreditCardGateway::GATEWAY_ID) {
                    $this->_paymentGateway = $g;
                    break;
                }
            }
        }

        public function getGatewaySettingsPageUrl() {
            return admin_url('admin.php?page=wc-settings&tab=checkout&section=' . MobilpayCreditCardGateway::GATEWAY_ID);
        }

        public function logDiagnosticsSupported() {

        }

        public function countLogItems() {

        }

        public function getLatestLogTail() {

        }

        public function getLatestLogLinkViewUrl() {

        }

        /**
         * Returns a list of diagnistic messages as an associative array.
         * Keys are gateway option field IDs.
         * Values are diagnostic warning messages.
         * 
         * This returns messages only for gateway option fields that have been filled in.
         * 
         * @return array The array of diagnositc messages
         */
        public function getDiagnosticMessages() {
            return $this->isGatewayConfigured() 
                ? $this->_paymentGateway->get_fields_with_warnings() 
                : array();
        }

        /**
         * @return bool True if the gateway has been completely configured, false otherwise
         */
        public function isGatewayConfigured() {
            return !$this->_paymentGateway->needs_setup();
        }
    }
}