jQuery(function ($) {

        $(document).on("click", ".twae-migration-notice .notice-dismiss,.twae-migration-notice .twae_hide_migration_notice_editor", function () {
            var mig_val = jQuery('.twae-migration-notice').data('tineline-mig');
          
            if(mig_val=='' || mig_val==undefined){
                  $(this).closest(".twae-migration-notice").fadeOut(300);
                mig_val = 'twe';
            }
        if (typeof twae_migration_obj === 'undefined' || !twae_migration_obj.ajax_url || !twae_migration_obj.hide_migration_nonce) {
            return;
        }
        $.post(twae_migration_obj.ajax_url, {
            action: "twae_hide_migration_notice",
            value:mig_val,
             nonce: twae_migration_obj.hide_migration_nonce 
        });
    });

  
    let hasUnsavedChanges = false;

    function checkUnsaved() {
        if (typeof elementor === "undefined" || !elementor.saver) return false;
        return !elementor.saver.isSaved; 
    }

    // Elementor Editor Events
    if (typeof elementor !== "undefined" && elementor.saver) {

        // Initial check
        hasUnsavedChanges = checkUnsaved();
        updateMigrationButtonState();

        elementor.channels.editor.on("change", function () {
            hasUnsavedChanges = checkUnsaved();
            updateMigrationButtonState();
        });

        elementor.saver.on("after:save", function () {
            hasUnsavedChanges = false;
            updateMigrationButtonState();
        });
    }

    // Update button state
    function updateMigrationButtonState() {
        if (hasUnsavedChanges) {
            $("#twae-run-migration")
                .css({ opacity: 0.5, cursor: "not-allowed" })
                .attr("disabled", true);
        } else {
            $("#twae-run-migration")
                .css({ opacity: 1, cursor: "pointer" })
                .attr("disabled", false);
        }
    }

    // Button Click Handler
    $(document).on("click", "#twae-run-migration", function (e) {

        var $btn = $(this);
        var $notice = $btn.closest(".twae-migration-notice");
        var $result = $notice.find("#twae-migration-result");
        var defaultLabel = $btn.data("default-label") || "Migrate Now!";

        if (hasUnsavedChanges) {
            e.preventDefault();

            elementor.notifications.showToast({
                message: "Please publish your changes before running migration.",
                duration: 4000,
                type: "warning",
            });

            return false;
        }

        $result.empty();
        $btn.prop("disabled", true).text("Running migration...");

        if (typeof twae_migration_obj === 'undefined' || !twae_migration_obj.ajax_url || !twae_migration_obj.nonce) {
            $btn.prop("disabled", false).text(defaultLabel);
            if ($result.length) {
                $result.text("Migration is unavailable. Please reload the page.");
            }
            return;
        }

        $.ajax({
            url: twae_migration_obj.ajax_url,
            type: "POST",
            data: {
                action: "twae_run_migration",
                nonce: twae_migration_obj.nonce
            },
            success: function (response) {

                if (response.success) {
                    $btn.prop("disabled", true).text("Migration Completed");
                    $result.empty();
                    $notice.find(".twae_eventprime_promotion-text > span").last().hide();

                    setTimeout(function () {
                        $notice.slideUp(500);
                    }, 6000);
                    setTimeout(function () {
                        if (typeof elementor !== "undefined") {
                            window.location.reload();
                        }
                    }, 3000);

                } else {
                    $btn.prop("disabled", false).text(defaultLabel);
                    if ($result.length) {
                        $result
                            .empty()
                            .append(
                                jQuery("<span/>", { css: { color: "#cc0000" } }).text(
                                    (response && response.data && response.data.message) ? response.data.message : ""
                                )
                            );
                    }
                }
            },
            error: function () {
                $btn.prop("disabled", false).text(defaultLabel);
                if ($result.length) {
                    $result
                        .empty()
                        .append(jQuery("<span/>", { css: { color: "red" } }).text("AJAX Error"));
                }
            }
        });

    });

});
