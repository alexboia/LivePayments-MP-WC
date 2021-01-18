<?php
namespace LvdWcMc {
    class MobilpayCreditCardGatewayDiagnostics {
        
        /**
         * @var \LvdWcMc\MobilpayCreditCardGateway
         */
        private $_paymentGateway = null;

        private $_diagnosticMessages = null;

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

        public function storeInitialGatewaySetupStatusIfDoesNotExist() {
            if ($this->_paymentGateway) {
                if ($this->_paymentGateway->get_last_stored_gateway_setup_status() === null) {
                    $this->_paymentGateway->store_gateway_setup_status();
                }
            }
        }

        public function getGatewaySettingsPageUrl() {
            return admin_url('admin.php?page=wc-settings&tab=checkout&section=' . MobilpayCreditCardGateway::GATEWAY_ID);
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
            if ($this->_diagnosticMessages === null) {
                $this->_diagnosticMessages = $this->isGatewayConfigured() 
                    ? $this->_paymentGateway->get_fields_with_warnings() 
                    : array();
            }
            return $this->_diagnosticMessages;
        }

        /**
         * @return bool True if the gateway has been completely configured, false otherwise
         */
        public function isGatewayConfigured() {
            return $this->_paymentGateway->get_last_stored_gateway_setup_status() === 'yes';
        }

        public function isGatewayOk() {
            return empty($this->getDiagnosticMessages());
        }
    }
}