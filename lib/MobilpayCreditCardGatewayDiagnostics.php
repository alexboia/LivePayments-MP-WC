<?php
namespace LvdWcMc {
    class MobilpayCreditCardGatewayDiagnostics {
        
        /**
         * @var \LvdWcMc\MobilpayCreditCardGateway
         */
        private $_paymentGateway = null;

        public function __construct() {
            $gateways = WC()->payment_gateways()->get_available_payment_gateways();
            foreach ($gateways as $id => $g) {
                if ($id == MobilpayCreditCardGateway::GATEWAY_ID) {
                    $this->_paymentGateway = $g;
                    break;
                }
            }
        }

        public function getDiagnosticMessages() {
            return $this->_paymentGateway->get_fields_with_warnings();
        }
    }
}