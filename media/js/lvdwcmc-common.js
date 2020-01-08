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
                '<img class="lvdwcmc-please-wait-spinner" src="/wp-content/plugins/wc-mobilpayments-card/media/img/lvdwcmc-wait.svg" alt="Please wait..." />',
                '<p class="lvdwcmc-please-wait-txt">Please wait...</p>'
            ],
            css: {
                border: 'none', 
                padding: '15px', 
                backgroundColor: '#000', 
                opacity: .5, 
                color: '#fff' 
            },

            onBlock: disableWindowScroll,
            onUnblock: enableWindowScroll
        });
    }

    function hidePleaseWait() {
        $.unblockUI();
    }

    if (window.lvdwcmc == undefined) {
        window.lvdwcmc = {};
    }

    window.lvdwcmc = $.extend(window.lvdwcmc, {
        disableWindowScroll: disableWindowScroll,
        enableWindowScroll: enableWindowScroll,
        showPleaseWait: showPleaseWait,
        hidePleaseWait: hidePleaseWait
    });
})(jQuery);