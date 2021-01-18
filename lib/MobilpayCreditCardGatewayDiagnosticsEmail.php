<?php
namespace LvdWcMc {
    use \WC_Email;

    class MobilpayCreditCardGatewayDiagnosticsEmail extends WC_Email {
        public function __construct() {
            $this->id = LVD_WCMC_PLUGIN_ID . '_gateway_diagnostics_email';
            $this->title = __('LivePayments - mobilPay Card WooCommerce Payment Gateway - Gateway diagnostics warning e-mail', 'livepayments-mp-wc');
            $this->description = __('An e-mail sent to a configured e-mail address when the plug-in detects that something is wrong with the gateway configuration.', 'livepayments-mp-wc');
            $this->customer_email = false;
            $this->heading = __('mobilPay Card Gateway Configuration Warning',  'livepayments-mp-wc');
            $this->subject = __('mobilPay Card Gateway Configuration Warning',  'livepayments-mp-wc');

            $this->template_html = 'emails/lvdwcmc-gateway-diagnostics-warning.php';
            $this->template_plain = 'emails/plain/lvdwcmc-gateway-diagnostics-warning.php';
            $this->template_base = $this->_getTemplateBaseDir();

            parent::__construct();
        }

        private function _getTemplateBaseDir() {
            $viewsDir = lvdwcmc_get_env()->getViewDir();
            return trailingslashit($viewsDir);
        }

        public function trigger(\stdClass $diagnosticsData) {
            $this->object = $diagnosticsData;
            $this->recipient = $diagnosticsData->sendDiagnosticsWarningToEmail;

            if (!$this->is_enabled() || !$this->get_recipient()) {
                return;
            }

            $this->send($this->get_recipient(), 
                $this->get_subject(), 
                $this->get_content(), 
                $this->get_headers(), 
                $this->get_attachments());
        }

        public function get_content_html() {
            return wc_get_template_html($this->template_html, array(
                'email_heading' => $this->get_heading(),
                'gateway_diagnostic_messages' => $this->object->gatewayDiagnosticMessages,
                'gateway_ok' => $this->object->gatewayOk,
                'email' => $this
            ), '', $this->template_base);
        }

        public function get_content_plain() {
            return wc_get_template_html($this->template_plain, array(
                'email_heading' => $this->get_heading(),
                'gateway_diagnostic_messages' => $this->object->gatewayDiagnosticMessages,
                'gateway_ok' => $this->object->gatewayOk,
                'email' => $this
            ), '', $this->template_base);
        }
    }
}