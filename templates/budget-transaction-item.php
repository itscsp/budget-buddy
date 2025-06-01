<?php
/**
 * Transaction Item Template
 * Args: tx
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Only set $tx if $tx_args is available
if (isset($tx_args)) {
    $tx = $tx_args['tx'];
} else {
    $tx = null;
}
if (!$tx) {
    return; // Nothing to show
}
?>
<div class="bb-transaction bb-transaction--<?php echo esc_attr($tx->type); ?>">
    <div class="bb-transaction__main">
        <date class="bb-transaction__date"><?php echo esc_html(date('d', strtotime($tx->date))); ?></date>
        <div class="bb-transaction__amount-wrapper">
            <p class="bb-transaction__amount"><?php echo esc_attr($tx->type === 'income' ? '+' : '-'); ?> ₹<?php echo esc_html(number_format($tx->amount, 2)); ?></p>
            <button class="bb-expand-btn"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="32px" width="32px" xmlns="http://www.w3.org/2000/svg">
                    <path d="M256 294.1L383 167c9.4-9.4 24.6-9.4 33.9 0s9.3 24.6 0 34L273 345c-9.1 9.1-23.7 9.3-33.1.7L95 201.1c-4.7-4.7-7-10.9-7-17s2.3-12.3 7-17c9.4-9.4 24.6-9.4 33.9 0l127.1 127z"></path>
                </svg></button>
        </div>
    </div>

    <div id="bb-transaction-details-<?php echo esc_attr($tx->id); ?>" class="bb-transaction__details" style="display: none;">
        <strong>Type:</strong> <?php echo ucfirst(esc_html($tx->type)); ?><br>
        <strong>Description:</strong> <?php echo esc_html($tx->description); ?><br>
        <strong>Category:</strong> <?php echo esc_html($tx->category_name ?: 'Uncategorized'); ?><br>
        <strong>Date:</strong> <?php echo esc_html(date('F j, Y', strtotime($tx->date))); ?><br><br>

        <form class="bb-delete-form">
            <input type="hidden" name="transaction_id" value="<?php echo esc_attr($tx->id); ?>" />
            <button type="button" class="bb-delete-btn delete-transaction-btn" data-id="<?php echo esc_attr($tx->id); ?>">Delete</button>
        </form>
    </div>
</div>