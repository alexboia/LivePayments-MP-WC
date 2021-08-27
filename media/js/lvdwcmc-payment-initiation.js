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

(function($) {
	"use strict";

	var CHECKOUT_AUTOREDIRECT_TICK_INTERVAL_MILLISECONDS = 1000;

	var _context = null;
	var _autoRedirectRemainingSeconds = 0;
	var _autoRedirectTimer = null;

	var $ctlPaymentForm = null;
	var $ctlAutoRedirectNoticeSecondsCounter = null;

	function getContext() {
		return {
			checkoutAutoRedirectSeconds: window['_checkoutAutoRedirectSeconds'] != undefined 
				? _checkoutAutoRedirectSeconds 
				: -1
		};
	}

	function showLoading() {
		$.blockUI({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});
	}

	function initContext() {
		_context = getContext();
	}

	function initControls() {
		$ctlPaymentForm = $('#lvdwcmc-mobilpay-redirect-form');
		$ctlAutoRedirectNoticeSecondsCounter = $('#lvdwcmc-mobilpay-autoredirect-notice-seconds-counter');
	}

	function initEvents() {
		if (isPaymentPayloadCorrectlyGenerated()) {
			setupPaymentForm();
		} else {
			setupPaymentErrorForm();
		}
	} 

	function isPaymentPayloadCorrectlyGenerated() {
		return $ctlPaymentForm.size() > 0;
	}

	function setupPaymentForm() {
		console.log(_context);
		if (_context.checkoutAutoRedirectSeconds > 0) {
			startAutoRedirectCountdown();
		} else if (_context.checkoutAutoRedirectSeconds == 0) {
			showLoading();
			submitPaymentForm();
		}

		$('#lvdwcmc-submit-mobilpay-payment-form').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			showLoading(); 
		});
	}

	function setupPaymentErrorForm() {
		$('#lvdwcmc-submit-mobilpay-payment-form-reload-on-error').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			showLoading();
			window.location.reload();
		});
	}

	function startAutoRedirectCountdown() {
		_autoRedirectRemainingSeconds = _context.checkoutAutoRedirectSeconds;
		_autoRedirectTimer = window.setInterval(autoRedirectTick, CHECKOUT_AUTOREDIRECT_TICK_INTERVAL_MILLISECONDS);
	}

	function autoRedirectTick() {
		if (_autoRedirectRemainingSeconds > 0) {
			_autoRedirectRemainingSeconds -= 1;
			$ctlAutoRedirectNoticeSecondsCounter.text(_autoRedirectRemainingSeconds);
		} else {
			showLoading();
			submitPaymentForm();
		}
	}

	function submitPaymentForm() {
		$ctlPaymentForm.submit();
	}

	$(document).ready(function() {
		initContext();
		initControls();
		initEvents();
	});
})(jQuery);