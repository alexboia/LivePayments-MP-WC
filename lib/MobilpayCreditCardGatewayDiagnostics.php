<?php
namespace LvdWcMc {
    class MobilpayCreditCardGatewayDiagnostics {
        
        /**
         * @var \LvdWcMc\MobilpayCreditCardGateway
         */
        private $_paymentGateway = null;

        private $_diagnosticMessages = null;

        /**
         * @var \LvdWcMc\Env
         */
        private $_env = null;

        public function __construct() {
            $this->_env = lvdwcmc_get_env();
            $this->_paymentGateway = lvdwcmc_get_mobilpay_credit_card_gateway();
        }

        public function updateGatewaySetupStatus() {
            if ($this->_paymentGateway) {
                $this->_paymentGateway->store_gateway_setup_status();
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
            return $this->_env->getPaymentGatewayWooCommerceSettingsPageUrl(MobilpayCreditCardGateway::GATEWAY_ID);
        }

        public function canSendGatewayDiagnosticsWarningNotification() {
            return $this->isGatewayConfigured() && !$this->isGatewayOk();
        }

        public function sendGatewayDiagnosticsWarningNotification($toAddress) {
            if ($this->canSendGatewayDiagnosticsWarningNotification()) {
                $mailer = $this->_getGatewayDiagnosticsMailer();
                if ($mailer != null) {
                    $mailer->trigger($this->_getGatewayDiagnosticsWarningData($toAddress));
                } else {
                    write_log('Gateway diagnostics mailer not found. No e-mail sent.');
                }
            }
        }

        private function _getGatewayDiagnosticsWarningData($toAddress) {
            $data = new \stdClass();
            $data->sendDiagnosticsWarningToEmail = $toAddress;
            $data->gatewayDiagnosticMessages = $this->getDiagnosticMessages();
            $data->gatewayOk = $this->isGatewayOk();
            return $data;
        }

        /**
         * @return \LvdWcMc\MobilpayCreditCardGatewayDiagnosticsEmail|null 
         */
        private function _getGatewayDiagnosticsMailer() {
            $emails = WC()->mailer()->get_emails();
            if ($emails['LvdWcMc_GatewayDiagnosticsEmail']) {
                return $emails['LvdWcMc_GatewayDiagnosticsEmail'];
            } else {
                return null;
            }
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
            return $this->_paymentGateway != null && 
                $this->_paymentGateway->get_last_stored_gateway_setup_status() === 'yes';
        }

        public function isGatewayOk() {
            return empty($this->getDiagnosticMessages());
        }
    }
}