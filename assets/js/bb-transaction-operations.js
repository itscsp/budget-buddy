jQuery(document).ready(function($) {
    // Delete Transaction
    $(".delete-transaction-btn").on("click", function(e) {
        e.preventDefault();

        const button = $(this);
        const transactionId = button.data("id");

        if (confirm("Are you sure you want to delete this transaction?")) {
            // Show loading state
            button.text("Deleting...").prop("disabled", true);

            // Send AJAX request
            $.ajax({
                url: bb_data.ajax_url,
                type: "POST",
                data: {
                    action: "bb_delete_transaction",
                    transaction_id: transactionId,
                    security: bb_data.report_nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Find and remove the transaction element from the DOM
                        const transactionElement = button.closest(".bb-transaction");
                        transactionElement.css("opacity", "0");
                        setTimeout(function() {
                            transactionElement.remove();
                            // Display success message
                            const alertDiv = $('<div class="updated bb-alert"><p>' + response.data.message + '</p></div>');
                            $(".bb-container").prepend(alertDiv);

                            // Auto-remove the alert after 3 seconds
                            setTimeout(function() {
                                alertDiv.css("opacity", "0");
                                setTimeout(function() {
                                    alertDiv.remove();
                                }, 500);
                            }, 3000);
                        }, 300);
                    } else {
                        alert(response.data.message || "Error deleting transaction");
                        button.text("Delete").prop("disabled", false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error:", error);
                    alert("Error deleting transaction. Please try again.");
                    button.text("Delete").prop("disabled", false);
                }
            });
        }
    });

    // Add Transaction
    $("#bb-add-transaction-form").on("submit", function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find("input[type='submit']");
        const $activeRadio = $('.budget-class input[type="radio"]:checked');
        
        // Disable button and show loading state
        submitBtn.prop("disabled", true).val("Adding...");

        $.ajax({
            url: bb_data.ajax_url,
            type: "POST",
            data: {
                action: "bb_add_transaction",
                bb_transaction_nonce: bb_data.transaction_nonce,
                type: form.find('[name="type"]').val(),
                amount: form.find('[name="amount"]').val(),
                description: form.find('[name="description"]').val(),
                date: form.find('[name="date"]').val(),
                category_id: $activeRadio.val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    form[0].reset(); // Reset the form
                    window.location.reload(); // Reload page to show new transaction
                } else {
                    alert(response.data.message || "Error adding transaction");
                    submitBtn.prop("disabled", false).val("Add Transaction");
                }
            },
            error: function(xhr, status, error) {
                console.error("Error:", error);
                alert("An error occurred. Please try again.");
                submitBtn.prop("disabled", false).val("Add Transaction");
            }
        });
    });
});