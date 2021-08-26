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

    function disableWindowScroll() {
        $('body').addClass('lvdwcmc-stop-scrolling');
    }

    function enableWindowScroll() {
        $('body').removeClass('lvdwcmc-stop-scrolling');
    }

    function showPleaseWait() {
        $.blockUI({
            message: [
                '<img class="lvdwcmc-please-wait-spinner" src="' + lvdwcmcCommonSettings.pluginMediaImgRootDir + '/lvdwcmc-wait.svg" alt="' + lvdwcmcCommonScriptL10n.lblLoading + '" />',
                '<p class="lvdwcmc-please-wait-txt">' + lvdwcmcCommonScriptL10n.lblLoading + '</p>'
            ],
            css: {
                border: 'none', 
                padding: '15px', 
                backgroundColor: '#000', 
                opacity: .5, 
                color: '#fff' 
            },

            overlayCSS: {
                backgroundImage: 'none'
            },

            onBlock: disableWindowScroll
        });
    }

    function hidePleaseWait() {
        $.unblockUI({
            onUnblock: enableWindowScroll
        });
    }

    function scrollToTop() {
        $('body,html').scrollTop(0);
    }

    if (window.lvdwcmc == undefined) {
        window.lvdwcmc = {};
    }

    window.lvdwcmc = $.extend(window.lvdwcmc, {
        disableWindowScroll: disableWindowScroll,
        enableWindowScroll: enableWindowScroll,
        showPleaseWait: showPleaseWait,
        hidePleaseWait: hidePleaseWait,
        scrollToTop: scrollToTop
    });
})(jQuery);