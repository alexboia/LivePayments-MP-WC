/**
 * Copyright (c) 2019-2020 Alexandru Boia
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

(function($) {
    "use strict";

    var _context = null;
    var _isShowingProgress = false;
    var _tplTransactionDetails = null;

    function _getContext() {
        return {
            ajaxBaseUrl: window['lvdwcmc_ajaxBaseUrl'],
            transactionDetailsAction: window['lvdwcmc_transactionDetailsAction'],
            transactionDetailsNonce: window['lvdwcmc_transactionDetailsNonce']
        }
    }

    function _getAjaxGetTransactionDetailsUrl(transactionId) {
        return URI(_context.ajaxBaseUrl)
            .addSearch('action', _context.transactionDetailsAction)
            .addSearch('lvdwcmc_nonce', _context.transactionDetailsNonce)
            .addSearch('transaction_id', transactionId)
            .toString();
    }

    function _toastMessage(success, message) {
        toastr[success ? 'success' : 'error'](message);
    }

    function _showProgress() {
        if (!_isShowingProgress) {
            lvdwcmc.showPleaseWait();
            _isShowingProgress = true;
        }
    }

    function _hideProgress() {
        if (_isShowingProgress) {
            lvdwcmc.hidePleaseWait();
            _isShowingProgress = false;
        }
    }

    function _renerDetailsHtml(transaction) {
        if (_tplTransactionDetails == null) {
            _tplTransactionDetails = kite('#lvdwcmc-tpl-transaction-details');
        }
        return _tplTransactionDetails({
            transaction: transaction
        });
    }

    function _loadDetails(transactionId) {
        _showProgress();
        $.ajax(_getAjaxGetTransactionDetailsUrl(transactionId), {
            type: 'GET',
            dataType: 'json',
        }).done(function(data, status, xhr) {
            _hideProgress();
            if (data && data.success && data.transaction != null) {
                var $html = $(_renerDetailsHtml(data.transaction));
                var blockUICss = $.blockUI.defaults.css;

                $.blockUI({
                    message: $html,
                    css: {
                        width: '680px',
                        height: 'auto',
                        top: '100px',
                        left: 'calc(50% - 340px)',
                        border: '0px none',
                        boxShadow: '0 5px 15px rgba(0, 0, 0, 0.7)'
                    }
                });
            } else {
                _toastMessage(false, 'Could not load transaction details data');
            }
        }).fail(function() {
            _hideProgress();
            _toastMessage(false, 'Could not load transaction details data');
        });
    }

    function _initListeners() {
        $('#lvdwcmc-tx-listing').on('click', 'a.lvdwcmc-tx-action', function(e) {
            var transactionId = parseInt($(this).attr('data-transactionId'));
            if (!isNaN(transactionId) && transactionId > 0) {
                _loadDetails(transactionId);
            }
        });

        $('body').on('click', '#lvdwcmc-admin-transaction-details-close', function() {
            $.unblockUI();
        });
    }

    function _initState() {
        _context = _getContext();
    }

    $(document).ready(function() {
        _initState();
        _initListeners();
    });
})(jQuery);