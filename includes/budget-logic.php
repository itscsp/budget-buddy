<?php
/**
 * Budget Buddy Logic
 * Handles user authentication, plan updates, transients, and data fetching
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Ensure user is logged in
if (!is_user_logged_in()) {
    return;
}

// Fetch categories for the current user
global $wpdb;
$user_id = get_current_user_id();
$categories_table = $wpdb->prefix . 'bb_budget_categories';

// Handle updating a plan's status
if (isset($_POST['bb_update_plan_status']) && is_user_logged_in()) {
    bb_update_plan_status($_POST['plan_id'], $_POST['new_status']);
    set_transient('bb_flash_message_' . get_current_user_id(), 'Plan updated!', 30);
    wp_redirect($_SERVER['REQUEST_URI']);
    exit;
}

// Handle plan deletion
if (isset($_POST['bb_delete_plan']) && is_user_logged_in()) {
    bb_handle_plan_delete($_POST['plan_id']);
    set_transient('bb_flash_message_' . get_current_user_id(), 'Plan deleted!', 30);
    wp_redirect($_SERVER['REQUEST_URI']);
    exit;
}

// Display transient message (if exists)
$bb_flash_message = get_transient('bb_flash_message_' . get_current_user_id());
if ($bb_flash_message) {
    echo '<div class="updated bb-alert"><p>' . esc_html($bb_flash_message) . '</p></div>';
    delete_transient('bb_flash_message_' . get_current_user_id());
}

// Get balance
$balance = bb_get_user_balance();
$balance_class = $balance >= 0 ? 'positive' : 'negative';

global $categories;
$categories = $wpdb->get_results(
    $wpdb->prepare("SELECT * FROM $categories_table WHERE user_id = %d", $user_id)
);
?>