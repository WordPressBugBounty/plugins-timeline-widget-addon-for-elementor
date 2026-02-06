jQuery(function ($) {

        $(document).on("click", ".twae-migration-notice .notice-dismiss,.twae-migration-notice .twae_hide_migration_notice_editor", function () {
            var mig_val = jQuery('.twae-migration-notice').data('tineline-mig');
          
            if(mig_val=='' || mig_val==undefined){
                  $(this).closest(".twae-migration-notice").fadeOut(300);
                var mig_val = 'twe';
            }
            console.log("Nonce sent:", twae_migration_obj.hide_migration_nonce);
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

        if (hasUnsavedChanges) {
            e.preventDefault();

            elementor.notifications.showToast({
                message: "Please publish your changes before running migration.",
                duration: 4000,
                type: "warning",
            });

            return false;
        }

        $("#twae-migration-result").html("Running migration...");

        $.ajax({
            url: twae_migration_obj.ajax_url,
            type: "POST",
            data: {
                action: "twae_run_migration",
                nonce: twae_migration_obj.nonce
            },
            success: function (response) {

                if (response.success) {
                    $("#twae-migration-result").html("<strong>" + response.data.message + "</strong>");
                    $(".twae_eventprime_promotion-text").hide();

                    setTimeout(function () {
                        $(".twae-migration-notice").slideUp(500);
                    }, 6000);
                      setTimeout(function () {
                    if (typeof elementor !== "undefined") {
                            window.location.reload();
                        }
                        }, 3000);

                    $("#twae-run-migration")
                        .prop("disabled", true)
                        .text("Migration Completed");

                } else {
                    $("#twae-migration-result").html(
                        "<span style='color:#cc0000;'>" + response.data.message + "</span>"
                    );
                }
            },
            error: function () {
                $("#twae-migration-result").html("<span style='color:red;'>AJAX Error</span>");
            }
        });

    });

});
