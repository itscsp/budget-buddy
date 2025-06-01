jQuery(document).ready(function($) {
    // Add Plan
    $("#bb-add-plan-form").on("submit", function(e) {
        e.preventDefault();

        const form = $(this);
        const submitButton = form.find("input[type='submit']");
        
        // Disable button and show loading state
        submitButton.prop("disabled", true).val("Saving...");

        $.ajax({
            url: bb_data.ajax_url,
            type: "POST",
            data: {
                action: "bb_add_plan",
                security: bb_data.report_nonce,
                plan_text: $("#plan_text").val(),
                amount: $("#plan_amount").val(),
                plan_month: $("#plan_month").val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload(); // or update UI dynamically
                } else {
                    alert(response.data.message || "Error adding plan");
                    submitButton.prop("disabled", false).val("Save Plan");
                }
            },
            error: function(xhr, status, error) {
                console.error("Error:", error);
                alert("An error occurred while adding the plan. Please try again.");
                submitButton.prop("disabled", false).val("Save Plan");
            }
        });
    });

    // Delete Plan
    $(".bb-plan-delete-form").on("submit", function(e) {
        e.preventDefault();

        if (!confirm("Are you sure you want to delete this plan?")) {
            return;
        }

        const $form = $(this);
        const formData = new FormData(this);
        formData.append("action", "bb_delete_plan");
        formData.append("security", bb_data.report_nonce);

        $.ajax({
            url: bb_data.ajax_url,
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert("Plan deleted successfully.");

                    // Remove the plan container
                    const $planContainer = $form.closest(".bb-plan-item");
                    if ($planContainer.length) {
                        $planContainer.remove();
                    }
                } else {
                    alert(response.data.message || "Failed to delete plan.");
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                alert("An error occurred. Please try again.");
            }
        });
    });

    // Update Plan Status
    $(".bb-plan-status-form").on("submit", function(e) {
        e.preventDefault();

        const $form = $(this);
        const formData = new FormData(this);

        formData.append("action", "bb_update_plan_status");
        formData.append("security", bb_data.report_nonce);

        $.ajax({
            url: bb_data.ajax_url,
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert("Plan status updated.");
                    location.reload();
                } else {
                    alert(response.data.message || "Failed to update status.");
                }
            },
            error: function(xhr, status, error) {
                console.error("Error:", error);
                alert("An error occurred while updating status.");
            }
        });
    });
});