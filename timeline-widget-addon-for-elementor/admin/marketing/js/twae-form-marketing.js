
(function($) {
    $(document).on('click', '.twae-dismiss-notice, .twae-dismiss-cross, .twae-tec-notice .notice-dismiss', function(e) {

        e.preventDefault();
        var $el = $(this);
        let noticeType = $el.data('notice');
        let nonce = $el.data('nonce');

        if (noticeType === undefined) {
            noticeType = $('.twae-tec-notice').data('notice');
            nonce = $('.twae-tec-notice').data('nonce');
        }

        $.post(ajaxurl, {

            action: 'twae_mkt_dismiss_notice',
            notice_type: noticeType,
            nonce: nonce

        }, function(response) {

            if (response.success) {

                if (noticeType === 'cool_form') {
                    $el.closest('.cool-form-wrp').fadeOut();
                } else if (noticeType === 'tec_notice') {
                    $el.closest('.twae-tec-notice').fadeOut();
                }
            }
        });

    });

    function installPlugin(btn, slugg) {

        let button = $(btn);
        var $wrapper = button.closest('.cool-form-wrp');
        
        button.next('.twae-error-message').remove();

        const slug = getPluginSlug(slugg);
        const allowedSlugs = [
            'extensions-for-elementor-form',
            'conditional-fields-for-elementor-form',
            'country-code-field-for-elementor-form',
            'loop-grid-extender-for-elementor-pro',
            'events-widgets-for-elementor-and-the-events-calendar',
            'conditional-fields-for-elementor-form-pro',
        ];
        if (!slug || allowedSlugs.indexOf(slug) === -1) return;
        // Get the nonce from the button data attribute
        let nonce = button.data('nonce');

        button.text('Installing...').prop('disabled', true);
        disableAllOtherPluginButtonsTemporarily(slug);

        $.post(ajaxurl, {

                action: 'twae_install_plugin',
                slug: slug,
                _wpnonce: nonce
            },

            function(response) {

                const pluginSlug = slug;
                const responseString = JSON.stringify(response);
                const responseContainsPlugin = responseString.includes(pluginSlug);

                if (responseContainsPlugin) {

                    button.text('Activated')
                        .removeClass('e-btn e-info e-btn-1 elementor-button-success')
                        .addClass('elementor-disabled')
                        .prop('disabled', true);

                    disableOtherPluginButtons(slug);

                    let successMessage = 'Save & reload the page to start using the feature.';

                    if (slug === 'events-widgets-for-elementor-and-the-events-calendar') {

                        successMessage = 'Events Widget is now active! Design your Events page with Elementor to access powerful new features.';
                        $('.twae_ect-notice-widget').text(successMessage);

                    } else {

                        $wrapper.find('.elementor-control-notice-success').remove();

                        const $notice = $('<div/>', {
                            class: 'elementor-control-notice elementor-control-notice-success'
                        });
                        const $content = $('<div/>', { class: 'elementor-control-notice-content' }).text(successMessage);
                        $notice.append($content);
                        $wrapper.find('.elementor-control-notice-main-actions').after($notice);
                    }

                } else if (!responseContainsPlugin) {

                    $wrapper.find('.elementor-control-notice-success').remove();

                    const $notice = $('<div/>', {
                        class: 'elementor-control-notice elementor-control-notice-success'
                    });
                    const $content = $('<div/>', { class: 'elementor-control-notice-content' })
                        .text('The plugin is installed but not yet activated. Please go to the Plugins menu and activate it.');
                    $notice.append($content);
                    $wrapper.find('.elementor-control-notice-main-actions').after($notice);
                } else {

                    let errorMessage = 'Please try again or download plugin manually from WordPress.org';
                    $wrapper.find('.elementor-button-warning').remove();
                    if (slug === 'events-widget') {
                        //
                        $('.twae_ect-notice-widget').text(errorMessage)
                    } else {

                        const $notice = $('<div/>', {
                            class: 'elementor-control-notice elementor-button-warning'
                        });
                        const $content = $('<div/>', { class: 'elementor-control-notice-content' }).text(errorMessage);
                        $notice.append($content);
                        $wrapper.find('.elementor-control-notice-main-actions').after($notice);
                    }
                }
            });
    }

    function getPluginSlug(plugin) {

        if (!plugin) {
            return '';
        }

        const slugs = {
            'cool-form-lite': 'extensions-for-elementor-form',
            'conditional': 'conditional-fields-for-elementor-form',
            'country-code': 'country-code-field-for-elementor-form',
            'loop-grid': 'loop-grid-extender-for-elementor-pro',
            'events-widget': 'events-widgets-for-elementor-and-the-events-calendar',
            'conditional-pro': 'conditional-fields-for-elementor-form-pro',
        };
        return slugs[plugin] || '';
    }

    function disableAllOtherPluginButtonsTemporarily(activeSlug) {
        const relatedSlugs = [
            'extensions-for-elementor-form',
            'conditional-fields-for-elementor-form',
            'country-code-field-for-elementor-form'
        ];

        $('.twae-install-plugin').each(function() {
            const $btn = $(this);
            const btnSlug = getPluginSlug($btn.data('plugin'));

            if (btnSlug && btnSlug !== activeSlug && relatedSlugs.includes(btnSlug)) {
                $btn.prop('disabled', true);
            }
        });
    }


    function disableOtherPluginButtons(activatedSlug) {
        const relatedSlugs = [
            'extensions-for-elementor-form',
            'conditional-fields-for-elementor-form',
            'country-code-field-for-elementor-form'
        ];

        if (!relatedSlugs.includes(activatedSlug)) return;

        $('.twae-install-plugin').each(function() {
            const $btn = $(this);
            const btnSlug = getPluginSlug($btn.data('plugin'));

            if (btnSlug && btnSlug !== activatedSlug && relatedSlugs.includes(btnSlug)) {
                $btn.text('Already Installed')
                    .addClass('elementor-disabled')
                    .prop('disabled', true)
                    .removeClass('e-btn e-info e-btn-1 elementor-button-success');

                $btn.closest('.cool-form-wrp').hide();

                // Hide associated switcher controls
                if (btnSlug === 'country-code-field-for-elementor-form') {
                    $('[data-setting="ctwae-mkt-country-conditions"]').closest('.elementor-control').hide();
                }
                if (btnSlug === 'conditional-fields-for-elementor-form') {
                    $('[data-setting="ctwae-mkt-conditional-conditions"]').closest('.elementor-control').hide();
                }
            }
        });
    }
    $(document).on('click', '.twae-install-plugin', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const pluginKey = $btn.data('plugin');
        const slug = getPluginSlug(pluginKey);

        if (!slug) {
            return;
        }

        installPlugin($btn, pluginKey);
    });

})(jQuery);