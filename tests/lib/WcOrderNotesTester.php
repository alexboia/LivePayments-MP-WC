<?php
class WcOrderNotesTester {
    /**
     * @var \WC_Order
     */
    private $_order;

    private $_initialInternalOrderNotesCount;

    private $_initialCustomerOrderNotesCount;

    public function __construct(\WC_Order $order) {   
        $this->_order = $order;
        $this->_initialInternalOrderNotesCount = $this->_countInternalOrderNotes();
        $this->_initialCustomerOrderNotesCount = $this->_countCustomerOrderNotes();
    }

    public function currentInternalOrderNotesCountDiffersBy($diff) {
        return ($this->_countInternalOrderNotes() - $this->_initialInternalOrderNotesCount) 
            == $diff;
    }

    public function currentCustomerOrderNotesCountDiffersBy($diff) {
        return ($this->_countCustomerOrderNotes() - $this->_initialCustomerOrderNotesCount)
            == $diff;
    }

    private function _countInternalOrderNotes() {
        $notes =  wc_get_order_notes(array(
            'order_id' => $this->_order->get_id(),
            'type' => 'internal'
        ));

        return !empty($notes) 
            ? count($notes) 
            : 0;
    }

    private function _countCustomerOrderNotes() {
        $notes =  wc_get_order_notes(array(
            'order_id' => $this->_order->get_id(),
            'type' => 'customer'
        ));

        return !empty($notes) 
            ? count($notes) 
            : 0;
    }
}