jQuery(document).ready(function($) {
    // Monthly Report functionality
    $(".bb-month__report-btn").on("click", function() {
        const month = $(this).data("month");
        const reportModal = $("#bb_report_modal_overlay");
        const reportContent = $("#bb-report-content");

        // Show modal with loading state
        reportModal.addClass("active");
        reportContent.html('<div class="bb-report-loading">Loading report data...</div>');

        // Fetch report data via AJAX
        $.ajax({
            url: bb_data.ajax_url,
            type: "POST",
            data: {
                action: "bb_get_monthly_report",
                month: month,
                nonce: bb_data.report_nonce
            },
            success: function(response) {
                if (response.success) {
                    generateReportHTML(response.data, reportContent);
                } else {
                    reportContent.html(`<div class="bb-report-error">Error loading report: ${response.data}</div>`);
                }
            },
            error: function(xhr, status, error) {
                reportContent.html(`<div class="bb-report-error">Error: ${error}</div>`);
                console.error("AJAX Error:", error);
            }
        });
    });
});