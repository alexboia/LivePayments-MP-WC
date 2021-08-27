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

	var CHECKOUT_AUTOREDIRECT_DISABLED = -1;
	var CHECKOUT_AUTOREDIRECT_INSTANT = 0;
	var CHECKOUT_AUTOREDIRECT_INITIAL = 5;

	var _context = null;
	var _intitalElementValues = null;

	var $ctlSettingsForm = null;
	var $ctlMonitorDiagnostics = null;
	var $ctlAutoRedirectEnable = null;
	var $ctlAutoRedirectInstant = null;
	var $ctlAutoRedirectSeconds = null;
	var $ctlSendDiagnosticsWarningToEmail = null;
	var $ctlButtonSubmitSettings = null;

	function _showProgress() {
		lvdwcmc.showPleaseWait();
	}

	function _hideProgress() {
		lvdwcmc.hidePleaseWait();
	}

	function _getContextFromInlineData() {
		return {
			ajaxBaseUrl: window['lvdwcmc_ajaxBaseUrl'],
			saveSettingsAction: window['lvdwcmc_saveSettingsAction'],
			saveSettingsNonce: window['lvdwcmc_saveSettingsNonce'],
			adminEmailAddress: window['lvdwcmc_adminEmailAddress']
		}
	}

	function _toastMessage(success, message) {
		toastr[success ? 'success' : 'error'](message);
	}

	function _getFormSaveUrl() {
		return URI(_context.ajaxBaseUrl)
			.addSearch('action', _context.saveSettingsAction)
			.addSearch('lvdwcmc_nonce', _context.saveSettingsNonce)
			.toString();
	}

	function _handleMonitorDiagnosticsChanged() {
		var isChecked = $ctlMonitorDiagnostics.is(':checked');
		if (isChecked) {
			_monitorDiagnosticsHasBeenEnabled();
		} else {
			_monitorDiagnosticsHasBeenDisabled();
		}
	}

	function _monitorDiagnosticsHasBeenEnabled() {
		$ctlSendDiagnosticsWarningToEmail.enableElement();
		$ctlSendDiagnosticsWarningToEmail.val(_getPrefillValueForSendDiagnosticsWarningToEmailField());
	}

	function _getPrefillValueForSendDiagnosticsWarningToEmailField() {
		var prefillValue = $ctlSendDiagnosticsWarningToEmail.data('savedValue');
		if (!prefillValue) {
			prefillValue = _context.adminEmailAddress;
		}

		return prefillValue;
	}

	function _monitorDiagnosticsHasBeenDisabled() {
		$ctlSendDiagnosticsWarningToEmail.disableElement();
		$ctlSendDiagnosticsWarningToEmail.val('');
	}

	function _handleAutoRedirectEnabledChanged() {
		var isChecked = $ctlAutoRedirectEnable.is(':checked');
		if (isChecked) {
			_autoRedirectHasBeenEnabled();	
		} else {
			_autoRedirectHasBeenDisabled();
		}
	}

	function _autoRedirectHasBeenEnabled() {
		$ctlAutoRedirectSeconds.enableElement();
		$ctlAutoRedirectSeconds.val(CHECKOUT_AUTOREDIRECT_INITIAL);

		$ctlAutoRedirectInstant.enableElement();
		$ctlAutoRedirectInstant.prop('checked', false);
	}

	function _autoRedirectHasBeenDisabled() {
		$ctlAutoRedirectSeconds.disableElement();
		$ctlAutoRedirectSeconds.val(CHECKOUT_AUTOREDIRECT_DISABLED);

		$ctlAutoRedirectInstant.disableElement();
		$ctlAutoRedirectInstant.prop('checked', false);
	}

	function _handleAutoRedirectInstantChanged() {
		var isChecked = $ctlAutoRedirectInstant.is(':checked');
		if (isChecked) {
			_instantAutoRedirectHasBeenEnabled();
		} else {
			_instantAutoRedirectHasBeenDisabled();
		}
	}

	function _instantAutoRedirectHasBeenEnabled() {
		$ctlAutoRedirectSeconds.disableElement();
		$ctlAutoRedirectSeconds.val(CHECKOUT_AUTOREDIRECT_INSTANT);
	}

	function _instantAutoRedirectHasBeenDisabled() {
		$ctlAutoRedirectSeconds.enableElement();
		$ctlAutoRedirectSeconds.val(CHECKOUT_AUTOREDIRECT_INITIAL);
	}

	function _handleAutoRedirectSecondsChanged() {
		var value = parseInt($ctlAutoRedirectSeconds.val());
		if (isNaN(value)) {
			value = CHECKOUT_AUTOREDIRECT_INITIAL;
		} else {
			value = Math.max(CHECKOUT_AUTOREDIRECT_DISABLED, value);
		}

		if (value == CHECKOUT_AUTOREDIRECT_DISABLED) {
			$ctlAutoRedirectEnable.prop('checked', false)
				.trigger('change');
		} else if (value == CHECKOUT_AUTOREDIRECT_INSTANT) {
			console.log('Instant');
			$ctlAutoRedirectInstant.prop('checked', true)
				.trigger('change');
		}
	}

	function _saveSettings() {
		_showProgress();
		$.ajax(_getFormSaveUrl(), {
			type: 'POST',
			dataType: 'json',
			cache: false,
			data: _getSettingsInputData()
		}).done(function(data, status, xhr) {
			_hideProgress();
			if (data && data.success) {
				_toastMessage(true, lvdwcmcPluginSettingsL10n.msgSaveOk);
				_storeInitialControlValues();
			} else {
				_toastMessage(false, data.message || lvdwcmcPluginSettingsL10n.errSaveFailGeneric);
			}
		}).fail(function(xhr, status, error) {
			_hideProgress();
			_toastMessage(false, lvdwcmcPluginSettingsL10n.errSaveFailNetwork);
		});
	}

	function _getSettingsInputData() {
		return $ctlSettingsForm.serialize();
	}

	function _initState() {
		_context = _getContextFromInlineData();
	}

	function _initListeners() {
		$ctlMonitorDiagnostics.on('change', _handleMonitorDiagnosticsChanged);
		$ctlAutoRedirectEnable.on('change', _handleAutoRedirectEnabledChanged);
		$ctlAutoRedirectInstant.on('change', _handleAutoRedirectInstantChanged);
		$ctlAutoRedirectSeconds.on('change', _handleAutoRedirectSecondsChanged);
		$ctlButtonSubmitSettings.on('click', _saveSettings);
	}

	function _initControls() {
		$ctlSettingsForm = $('#lvdwcmc-settings-form');
		$ctlAutoRedirectEnable = $('#lvdwcmc-checkout-auto-redirect-enable');
		$ctlAutoRedirectInstant = $('#lvdwcmc-checkout-auto-redirect-instant');
		$ctlAutoRedirectSeconds = $('#lvdwcmc-checkout-auto-redirect-seconds');
		$ctlMonitorDiagnostics = $('#lvdwcmc-monitor-diagnostics');
		$ctlSendDiagnosticsWarningToEmail = $('#lvdwcmc-send-diagnsotics-warning-to-email');
		$ctlButtonSubmitSettings = $('.lvdwcmc-form-submit-btn');
	}

	function _initToastMessages() {
		toastr.options = $.extend(toastr.options, {
			target: 'body',
			positionClass: 'toast-bottom-right',
			timeOut: 4000
		});
	}

	function _storeInitialControlValues() {
		$ctlSendDiagnosticsWarningToEmail.data('savedValue', 
			$ctlSendDiagnosticsWarningToEmail.val());
	}

	$(document).ready(function() {
		_initState();
		_initControls();
		_storeInitialControlValues();
		_initListeners();
		_initToastMessages();
	});
})(jQuery);