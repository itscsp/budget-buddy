jQuery(document).ready(function ($) {
    // Modal handling for transactions
    const $transactionModal = $("#bb_transation_modal_overlay");
    const $transactionOpenBtn = $("#bb_add_action");
    const $transactionCloseBtn = $(".bb-transation_modal__close");

    if ($transactionOpenBtn.length && $transactionModal.length && $transactionCloseBtn.length) {
        $transactionOpenBtn.on("click", () => {
            $transactionModal.addClass("active");
        });

        $transactionCloseBtn.on("click", () => {
            $transactionModal.removeClass("active");
        });

        $transactionModal.on("click", (e) => {
            if (e.target === $transactionModal[0]) {
                $transactionModal.removeClass("active");
            }
        });
    }

    // Fade out alerts
    $(".bb-alert").each(function () {
        setTimeout(() => {
            $(this).fadeTo(500, 0, () => $(this).remove());
        }, 3000);
    });

    // Modal handling for plans
    const $planModal = $("#bb_plan_modal_overlay");
    const $planMonthInput = $("#plan_month");

    $(".bb-toggle-plan").on("click", function () {
        const selectedMonth = $(this).data("month");
        if ($planMonthInput.length) {
            $planMonthInput.val(selectedMonth);
        }
        $planModal.css("display", "flex");
    });

    $(".bb-plan_modal__close").on("click", () => {
        $planModal.css("display", "none");
    });

    $planModal.on("click", function (e) {
        if (e.target === this) {
            $(this).css("display", "none");
        }
    });

    // Modal handling for reports
    const $reportModal = $("#bb_report_modal_overlay");
    const $reportCloseBtn = $(".bb-report_modal__close");

    if ($reportCloseBtn.length && $reportModal.length) {
        $reportCloseBtn.on("click", () => {
            $reportModal.removeClass("active");
        });

        $reportModal.on("click", (e) => {
            if (e.target === $reportModal[0]) {
                $reportModal.removeClass("active");
            }
        });
    }

    // Print report
    $("#bb-print-report").on("click", () => {
        window.print();
    });

    // Toggle transaction details
    $(".bb-expand-btn").on("click", function () {
        const $transactionEl = $(this).closest(".bb-transaction");
        const $detailsEl = $transactionEl.find(".bb-transaction__details");
        const isVisible = $detailsEl.is(":visible");

        $detailsEl.toggle(!isVisible);
        $(this).toggleClass("Expand", !isVisible).toggleClass("Collapse", isVisible);
    });

    // Dynamic styling for type select
    const $typeSelect = $('select[name="type"]');
    if ($typeSelect.length) {
        const updateSelectStyle = () => {
            $typeSelect.removeClass(
                "bb-form__input--income bb-form__input--expense bb-form__input--loan"
            );
            $typeSelect.addClass(`bb-form__input--${$typeSelect.val()}`);
        };

        $typeSelect.on("change", updateSelectStyle);
        updateSelectStyle();
    }

    // Toggle monthly plan visibility
    $(".bb-month__plan-btn").on("click", function () {
        const $month = $(this).closest(".bb-month");
        const $plan = $month.find(".bb-month__plan");
        const isVisible = $plan.is(":visible");

        $(".bb-month__plan").not($plan).hide();
        $plan.toggle(!isVisible);
    });

    // Radio button active state
    const $radioButtons = $('.budget-class input[type="radio"]');
    $radioButtons.on("change", function () {
        $(".budget-class-type").removeClass("active");
        $(this).closest(".budget-class-type").addClass("active");
    });

    const $checkedRadio = $('.budget-class input[type="radio"]:checked');
    if ($checkedRadio.length) {
        $checkedRadio.closest(".budget-class-type").addClass("active");
    }

    // Add transaction
    $("#bb-add-transaction-form").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this);
        const $activeRadio = $('.budget-class input[type="radio"]:checked');

        const formData = new FormData();
        formData.append("action", "bb_add_transaction");
        formData.append("bb_transaction_nonce", bb_data.transaction_nonce);
        formData.append("type", $form.find('[name="type"]').val());
        formData.append("amount", $form.find('[name="amount"]').val());
        formData.append("description", $form.find('[name="description"]').val());
        formData.append("date", $form.find('[name="date"]').val());
        if ($activeRadio.length) {
            formData.append("category_id", $activeRadio.val());
        }

        $.ajax({
            url: bb_data.ajax_url,
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: () => $form.find(".bb-form__submit").prop("disabled", true).val("Adding..."),
            success: (response) => {
                if (response.success) {
                    showAlert(response.data.message, "success");
                    $form[0].reset();
                    location.reload();
                } else {
                    showAlert(response.data.message || "Error adding transaction", "error");
                }
            },
            error: () => {
                showAlert("Error adding transaction. Please try again.", "error");
            },
            complete: () => $form.find(".bb-form__submit").prop("disabled", false).val("Add Transaction")
        });
    });

    // Delete transaction
    $(".delete-transaction-btn").on("click", function (e) {
        e.preventDefault();
        const $button = $(this);
        const transactionId = $button.data("id");

        if (!confirm("Are you sure you want to delete this transaction?")) {
            return;
        }

        const formData = new FormData();
        formData.append("action", "bb_delete_transaction");
        formData.append("transaction_id", transactionId);
        formData.append("security", bb_data.report_nonce);

        $.ajax({
            url: bb_data.ajax_url,
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: () => {
                $button.text("Deleting...").prop("disabled", true);
            },
            success: (response) => {
                if (response.success) {
                    const $transactionEl = $button.closest(".bb-transaction");
                    $transactionEl.fadeTo(300, 0, () => {
                        $transactionEl.remove();
                        showAlert(response.data.message, "success");
                    });
                } else {
                    showAlert(response.data.message || "Error deleting transaction", "error");
                }
            },
            error: () => {
                showAlert("Error deleting transaction. Please try again.", "error");
            },
            complete: () => {
                $button.text("Delete").prop("disabled", false);
            }
        });
    });

    // Add plan
    $("#bb-add-plan-form").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this);

        const formData = new FormData();
        formData.append("action", "bb_add_plan");
        formData.append("security", bb_data.report_nonce);
        formData.append("plan_text", $("#plan_text").val());
        formData.append("amount", $("#plan_amount").val());
        formData.append("plan_month", $("#plan_month").val());

        $.ajax({
            url: bb_data.ajax_url,
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: () => $form.find(".bb-form__submit").prop("disabled", true).val("Saving..."),
            success: (response) => {
                if (response.success) {
                    showAlert(response.data.message, "success");
                    $form[0].reset();
                    $planModal.css("display", "none");
                    location.reload();
                } else {
                    showAlert(response.data.message || "Error adding plan", "error");
                }
            },
            error: () => {
                showAlert("Error adding plan. Please try again.", "error");
            },
            complete: () => $form.find(".bb-form__submit").prop("disabled", false).val("Save Plan")
        });
    });

    // Delete plan
    $(".bb-plan-delete-form").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this);

        if (!confirm("Are you sure you want to delete this plan?")) {
            return;
        }

        const formData = new FormData(this);
        formData.append("action", "bb_delete_plan");
        formData.append("security", bb_data.report_nonce);

        $.ajax({
            url: bb_data.ajax_url,
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.success) {
                    const $planContainer = $form.closest(".bb-plan-item");
                    $planContainer.fadeOut(300, () => {
                        $planContainer.remove();
                        showAlert("Plan deleted successfully.", "success");
                    });
                } else {
                    showAlert(response.data.message || "Failed to delete plan.", "error");
                }
            },
            error: () => {
                showAlert("An error occurred. Please try again.", "error");
            }
        });
    });

    // Update plan status
    $(".bb-plan-status-form").on("submit", function (e) {
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
            success: (response) => {
                if (response.success) {
                    showAlert("Plan status updated.", "success");
                    location.reload();
                } else {
                    showAlert(response.data.message || "Failed to update status.", "error");
                }
            },
            error: () => {
                showAlert("An error occurred while updating status.", "error");
            }
        });
    });

    // Generate monthly report
    $(".bb-month__report-btn").on("click", function () {
        const month = $(this).data("month");
        const $reportContent = $("#bb-report-content");

        $reportModal.addClass("active");
        $reportContent.html('<div class="bb-report-loading">Loading report data...</div>');

        const formData = new FormData();
        formData.append("action", "bb_get_monthly_report");
        formData.append("month", month);
        formData.append("nonce", bb_data.report_nonce);

        $.ajax({
            url: bb_data.ajax_url,
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.success) {
                    generateReportHTML(response.data, $reportContent);
                } else {
                    $reportContent.html(`<div class="bb-report-error">Error loading report: ${response.data}</div>`);
                }
            },
            error: (error) => {
                $reportContent.html(`<div class="bb-report-error">Error: ${error.statusText}</div>`);
            }
        });
    });

    // Helper function to generate report HTML
    function generateReportHTML(data, $container) {
        const formatter = new Intl.NumberFormat("en-IN", {
            style: "currency",
            currency: "INR",
            maximumFractionDigits: 2
        });

        const html = `
            <div class="bb-report-header">
                <div class="bb-report-title">Financial Report: ${data.month_name}</div>
                <div class="bb-report-subtitle">Summary of your financial activity</div>
            </div>
            <div class="bb-report-summary">
                <div class="bb-report-card income">
                    <div class="bb-report-label">Income</div>
                    <div class="bb-report-value positive">${formatter.format(data.income)}</div>
                </div>
                <div class="bb-report-card expense">
                    <div class="bb-report-label">Expenses</div>
                    <div class="bb-report-value negative">${formatter.format(data.expense)}</div>
                </div>
                <div class="bb-report-card loan">
                    <div class="bb-report-label">Loans</div>
                    <div class="bb-report-value">${formatter.format(data.loan)}</div>
                </div>
                <div class="bb-report-card net">
                    <div class="bb-report-label">Net</div>
                    <div class="bb-report-value ${data.net >= 0 ? "positive" : "negative"}">${formatter.format(data.net)}</div>
                </div>
            </div>
            <div class="bb-report-details">
                <h4>Transaction Summary</h4>
                <p>Total transactions: ${data.transaction_count}</p>
                <p>Monthly budget utilization: ${
                    data.expense > 0 && data.income > 0
                        ? Math.round((data.expense / data.income) * 100) + "%"
                        : "N/A"
                }</p>
            </div>
        `;

        $container.html(html);
    }

    // Helper function to show alerts
    function showAlert(message, type) {
        const $alert = $(`<div class="bb-alert ${type === "success" ? "updated" : "error"}"><p>${message}</p></div>`);
        $(".bb-container").prepend($alert);
        setTimeout(() => {
            $alert.fadeTo(500, 0, () => $alert.remove());
        }, 3000);
    }
});