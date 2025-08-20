jQuery(document).ready(function($) {

    // Loop through all localized notices
    Object.keys(window).forEach(function(key) {
        if (key.startsWith('TwaeNoticeData_')) {
            var noticeData = window[key];
            var wrapperSelector = noticeData.review ? noticeData.id + '-feedback-notice-wrapper' : noticeData.id + '_admin_notice';

            // Highlight for debug
            $("." + wrapperSelector + " .notice-dismiss").css("border", "2px solid red");

            // Click handler for both simple and review notices
            $(document).on("click", "." + wrapperSelector + " button.notice-dismiss, ." + wrapperSelector + " a._dismiss_notice", function(e) {
                if (e) e.preventDefault();
                var $wrapper = $(this).closest("." + wrapperSelector);

                if ($wrapper.length) {
                    $.post(noticeData.ajax_url, {
                        action: noticeData.ajax_callback,
                        slug: noticeData.plugin_slug,
                        id: noticeData.id,
                        _nonce: noticeData.wp_nonce
                    }, function() {
                        $wrapper.slideUp("fast");
                    }, "json");
                }
            });
        }
    });

});
