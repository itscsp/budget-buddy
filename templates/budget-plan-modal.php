<?php
/**
 * Plan Modal Template
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div id="bb_plan_modal_overlay" class="bb-plan_modal__overlay bb-modal__overlay" style="display: none;">
    <div class="bb-modal">
        <span class="bb-plan_modal__close">
            <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="24px" width="24px" xmlns="http://www.w3.org/2000/svg">
                <path d="M331.3 308.7L278.6 256l52.7-52.7c6.2-6.2 6.2-16.4 0-22.6-6.2-6.2-16.4-6.2-22.6 0L256 233.4l-52.7-52.7c-6.2-6.2-15.6-7.1-22.6 0-7.1 7.1-6 16.6 0 22.6l52.7 52.7-52.7 52.7c-6.7 6.7-6.4 16.3 0 22.6 6.4 6.4 16.4 6.2 22.6 0l52.7-52.7 52.7 52.7c6.2 6.2 16.4 6.2 22.6 0 6.3-6.2 6.3-16.4 0-22.6z"></path>
                <path d="M256 76c48.1 0 93.3 18.7 127.3 52.7S436 207.9 436 256s-18.7 93.3-52.7 127.3S304.1 436 256 436c-48.1 0-93.3-18.7-127.3-52.7S76 304.1 76 256s18.7-93.3 52.7-127.3S207.9 76 256 76m0-28C141.1 48 48 141.1 48 256s93.1 208 208 208 208-93.1 208-208S370.9 48 256 48z"></path>
            </svg>
        </span>
        <h4 class="bb-modal__heading">Add Monthly Plan</h4>
        <form class="bb-form" id="bb-add-plan-form">
            <input type="hidden" name="plan_month" id="plan_month" value="" />
            <input type="hidden" name="plan_id" id="plan_id" value="" />

            <div id="bb-plan-month-display" style="margin-bottom: 12px; font-weight: bold; font-size: 1.1em;"></div>

            <label for="plan_amount">Planned Amount:</label>
            <input id="plan_amount" type="number" step="0.01" name="amount" required class="bb-form__input" />

            <label for="plan_text">Plan Text:</label>
            <input id="plan_text" type="text" name="plan_text" required class="bb-form__input" />

            <div class="bb-form__checkbox-group">
                <input type="checkbox" id="is_recurring" name="is_recurring" class="bb-form__checkbox" />
                <label for="is_recurring">Repeat this plan every month</label>
            </div>

            <input type="submit" value="Save Plan" class="button button-primary bb-form__submit" />
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var planMonthInput = document.getElementById('plan_month');
    var planMonthDisplay = document.getElementById('bb-plan-month-display');
    if (planMonthInput && planMonthDisplay) {
        // Try to format the month as "Month YYYY" if possible
        var val = planMonthInput.value;
        if (val && /^\d{4}-\d{2}-\d{2}$/.test(val)) {
            var d = new Date(val);
            var monthName = d.toLocaleString('default', { month: 'long' });
            planMonthDisplay.textContent = 'Plan Month: ' + monthName + ' ' + d.getFullYear();
        } else if (val) {
            planMonthDisplay.textContent = 'Plan Month: ' + val;
        } else {
            planMonthDisplay.textContent = '';
        }
    }
});
</script>