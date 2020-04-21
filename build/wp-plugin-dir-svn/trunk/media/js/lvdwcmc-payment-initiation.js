(function($) {
    "use strict";

    $(document).ready(function() {
        $('#submit_mobilpay_payment_form').click(function() {
            $.blockUI({
                message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
            });
        });
    });
})(jQuery);