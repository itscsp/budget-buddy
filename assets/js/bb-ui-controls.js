jQuery(document).ready(function($) {
    // Transaction Modal Controls
    const openBtn = $("#bb_add_action");
    const modal = $("#bb_transation_modal_overlay");
    const closeBtn = $(".bb-transation_modal__close");

    if (openBtn.length && modal.length && closeBtn.length) {
        // Open modal
        openBtn.on("click", function() {
            modal.addClass("active");
        });

        // Close modal via close button
        closeBtn.on("click", function() {
            modal.removeClass("active");
        });

        // Close modal by clicking outside
        modal.on("click", function(e) {
            if (e.target === this) {
                $(this).removeClass("active");
            }
        });
    }

    // Alert auto-hide functionality
    const alerts = $(".bb-alert");
    alerts.each(function() {
        const alert = $(this);
        setTimeout(function() {
            alert.css("opacity", "0");
            setTimeout(function() {
                alert.remove();
            }, 500); // remove after fade
        }, 3000); // hide after 3 seconds
    });

    // Plan Modal Controls
    const planModal = $("#bb_plan_modal_overlay");
    const planMonthInput = $("#plan_month");

    $(".bb-toggle-plan").on("click", function() {
        const selectedMonth = $(this).data("month");
        if (planMonthInput.length) {
            planMonthInput.val(selectedMonth);
        }
        planModal.css("display", "flex");
    });

    // Close plan modals
    $(".bb-plan_modal__close").on("click", function() {
        planModal.css("display", "none");
    });

    // Optional: close modal on outside click
    $(".bb-plan_modal__overlay").on("click", function(e) {
        if (e.target === this) {
            $(this).css("display", "none");
        }
    });

    // Toggle transaction detail view
    $(".bb-expand-btn").on("click", function() {
        const transactionEl = $(this).closest(".bb-transaction");
        const detailsEl = transactionEl.find(".bb-transaction__details");

        if (detailsEl.length) {
            const isVisible = detailsEl.css("display") === "block";
            detailsEl.css("display", isVisible ? "none" : "block");

            // Toggle class
            $(this).removeClass(isVisible ? "Collapse" : "Expand");
            $(this).addClass(isVisible ? "Expand" : "Collapse");
        }
    });

    // Dynamic styling for type select in form
    const typeSelect = $('select[name="type"]');
    if (typeSelect.length) {
        const updateSelectStyle = function() {
            typeSelect.removeClass(
                "bb-form__input--income bb-form__input--expense bb-form__input--loan"
            );
            
            if (typeSelect.val() === "income") {
                typeSelect.addClass("bb-form__input--income");
            } else if (typeSelect.val() === "expense") {
                typeSelect.addClass("bb-form__input--expense");
            } else if (typeSelect.val() === "loan") {
                typeSelect.addClass("bb-form__input--loan");
            }
        };

        typeSelect.on("change", updateSelectStyle);
        updateSelectStyle(); // Initial call to apply style
    }

    // Plan buttons functionality
    $(".bb-month__plan-btn").on("click", function() {
        // Get parent .bb-month
        const thisMonth = $(this).closest(".bb-month");
        const thisPlan = thisMonth.find(".bb-month__plan");

        // Hide all other plans
        $(".bb-month__plan").not(thisPlan).css("display", "none");

        // Toggle current one
        if (thisPlan.length) {
            const isVisible = thisPlan.css("display") === "block";
            thisPlan.css("display", isVisible ? "none" : "block");
        }
    });

    // Radio button active state functionality
    const $radioButtons = $('.budget-class input[type="radio"]');
    
    // Add change event listener
    $radioButtons.on("change", function() {
        // Remove active class from all .budget-class-type divs
        $(".budget-class-type").removeClass("active");
        // Add active class to the parent .budget-class-type div
        $(this).closest(".budget-class-type").addClass("active");
    });

    // Set initial active state for the checked radio button
    const $checkedRadio = $('.budget-class input[type="radio"]:checked');
    if ($checkedRadio.length) {
        $checkedRadio.closest(".budget-class-type").addClass("active");
    }

    // Report Modal Controls
    const reportModal = $("#bb_report_modal_overlay");
    const reportCloseBtn = $(".bb-report_modal__close");

    if (reportCloseBtn.length && reportModal.length) {
        reportCloseBtn.on("click", function() {
            reportModal.removeClass("active");
        });

        reportModal.on("click", function(e) {
            if (e.target === this) {
                $(this).removeClass("active");
            }
        });
    }

    // Print report functionality
    $("#bb-print-report").on("click", function() {
        window.print();
    });
});