<?php

/**
 * Plugin Name: BudgetBuddy
 * Description: A plugin to manage user income and expense
 * Version: 0.1
 * Author: Chethan S Poojary
 * Author URL: https://chethanspoojary.com/
 */

defined('ABSPATH') || exit;

// Enqueue custom styles and scripts
add_action('wp_enqueue_scripts', 'bb_enqueue_assets');

function bb_enqueue_assets()
{
    $plugin_url = plugin_dir_url(__FILE__);

    // Only load on pages where the shortcode is used
    wp_enqueue_style('bb-style', $plugin_url . 'assets/css/style.css');
    wp_enqueue_script('bb-script', $plugin_url . 'assets/js/script.js', array('jquery'), null, true);

    // Pass data to JavaScript
    wp_localize_script('bb-script', 'bb_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'report_nonce' => wp_create_nonce('bb_report_nonce')
    ));
	
	
}



// Include plugin files
include_once plugin_dir_path(__FILE__) . 'includes/bb-admin-pages.php';
include_once plugin_dir_path(__FILE__) . 'includes/bb-functions.php';

// Activation Hook: Create database table on plugin activation
register_activation_hook(__FILE__, 'bb_install');

function bb_install()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'bb_transactions';
    $plans_table = $wpdb->prefix . 'bb_monthly_plans';


    $sql1 = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        type varchar(10) NOT NULL,
        amount float NOT NULL,
        description text,
        date date NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Create monthly plans table
    $sql2 = "CREATE TABLE $plans_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        plan_text text NOT NULL,
        amount float NOT NULL,
        plan_month date NOT NULL,
        status varchar(10) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
}

function bb_tracker_shortcode($atts)
{
    // Check if user is logged in
    if (!is_user_logged_in()) {
        $login_url = wp_login_url(get_permalink());
        return '<div class="bb_not_logged_in">
                <h2>BudgetBuddy</h2>
                <p>Please <a href="' . $login_url . '">login</a> to access your personal budget tracker.</p>
                </div>';
    }

    // Set a global flag
    global $bb_using_shortcode;
    $bb_using_shortcode = true;

    ob_start();

    include plugin_dir_path(__FILE__) . 'templates/budget-buddy-page.php';

    return ob_get_clean();
}

add_shortcode('budget_buddy', 'bb_tracker_shortcode');


register_activation_hook(__FILE__, 'bb_create_budget_buddy_page');

function bb_create_budget_buddy_page() {
    // Check if the page already exists
    $page = get_page_by_path('budget-buddy');

    if (!$page) {
        // Create the page
        $page_data = array(
            'post_title'     => 'Budget',
            'post_name'      => 'budget',
            'post_content'   => '[budget_buddy]', // Use the shortcode
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_author'    => 1,
        );

        wp_insert_post($page_data);
    }
}


add_filter('body_class', 'bb_add_body_class_if_shortcode_used');
function bb_add_body_class_if_shortcode_used($classes)
{
    global $bb_using_shortcode;

    if (!empty($bb_using_shortcode)) {
        $classes[] = 'budget-buddy-active';
    }

    return $classes;
}


// Register AJAX handlers
add_action('wp_ajax_bb_get_monthly_report', 'bb_ajax_get_monthly_report');

/**
 * AJAX handler for monthly report
 */
function bb_ajax_get_monthly_report()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bb_report_nonce')) {
        wp_send_json_error('Security check failed');
    }

    if (!isset($_POST['month']) || empty($_POST['month'])) {
        wp_send_json_error('Missing month parameter');
    }

    $month = sanitize_text_field($_POST['month']);
    $summary = bb_get_monthly_summary($month);

    wp_send_json_success($summary);
}

// Add this to ensure the AJAX handler is registered properly for both logged-in and non-logged-in users
add_action('wp_ajax_bb_add_transaction', 'bb_handle_ajax_add_transaction');
add_action('wp_ajax_nopriv_bb_add_transaction', 'bb_handle_ajax_add_transaction');

function bb_handle_ajax_add_transaction() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Please log in to add a transaction.']);
    }

    check_ajax_referer('bb_report_nonce', 'report_nonce');

    $type = $_POST['type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $description = $_POST['description'] ?? '';
    $date = $_POST['date'] ?? '';

    $result = bb_add_transaction($type, $amount, $description, $date);

    if ($result) {
        wp_send_json_success(['message' => 'Transaction added successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to add transaction.']);
    }
}



// AJAX handler for deleting transactions
add_action('wp_ajax_bb_delete_transaction', 'bb_ajax_delete_transaction');

function bb_ajax_delete_transaction() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to delete transactions.']);
    }
    
    // Verify nonce
    check_ajax_referer('bb_report_nonce', 'security');
    
    // Get transaction ID
    $transaction_id = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
    
    if (!$transaction_id) {
        wp_send_json_error(['message' => 'Invalid transaction ID.']);
    }
    
    // Delete the transaction
    global $wpdb;
    $table = $wpdb->prefix . 'bb_transactions';
    $user_id = get_current_user_id();
    
    $result = $wpdb->delete(
        $table,
        [
            'id' => $transaction_id,
            'user_id' => $user_id // Security: ensure user can only delete their own transactions
        ],
        ['%d', '%d']
    );
    
    if ($result !== false) {
        wp_send_json_success(['message' => 'Transaction deleted successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete transaction. Please try again.']);
    }
}





// AJAX handler for add plan
add_action('wp_ajax_bb_add_plan', 'bb_ajax_bb_add_plan');



function bb_ajax_bb_add_plan() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to delete transactions.']);
    }
    
    // Verify nonce
    check_ajax_referer('bb_report_nonce', 'security');
    
    // Get transaction ID
    $transaction_id = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
    
    if (!$transaction_id) {
        wp_send_json_error(['message' => 'Invalid transaction ID.']);
    }
    
    // Delete the transaction
    global $wpdb;
    $table = $wpdb->prefix . 'bb_transactions';
    $user_id = get_current_user_id();
    
    $result = $wpdb->delete(
        $table,
        [
            'id' => $transaction_id,
            'user_id' => $user_id // Security: ensure user can only delete their own transactions
        ],
        ['%d', '%d']
    );
    
    if ($result !== false) {
        wp_send_json_success(['message' => 'Transaction deleted successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete transaction. Please try again.']);
    }
}

// ========================
// Register AJAX handlers
// ========================

// Add Transaction
add_action('wp_ajax_bb_add_transaction', 'bb_handle_ajax_add_transaction');
add_action('wp_ajax_nopriv_bb_add_transaction', 'bb_handle_ajax_add_transaction');
function bb_handle_ajax_add_transaction() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Please log in to add a transaction.']);
    }

    check_ajax_referer('bb_report_nonce', 'report_nonce');

    $type = sanitize_text_field($_POST['type'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $description = sanitize_text_field($_POST['description'] ?? '');
    $date = sanitize_text_field($_POST['date'] ?? '');

    $result = bb_add_transaction($type, $amount, $description, $date);

    if ($result) {
        wp_send_json_success(['message' => 'Transaction added successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to add transaction.']);
    }
}


// Delete Transaction
add_action('wp_ajax_bb_delete_transaction', 'bb_ajax_delete_transaction');
function bb_ajax_delete_transaction() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to delete transactions.']);
    }

    check_ajax_referer('bb_report_nonce', 'security');

    $transaction_id = intval($_POST['transaction_id'] ?? 0);
    if (!$transaction_id) {
        wp_send_json_error(['message' => 'Invalid transaction ID.']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bb_transactions';
    $user_id = get_current_user_id();

    $deleted = $wpdb->delete($table, ['id' => $transaction_id, 'user_id' => $user_id], ['%d', '%d']);

    if ($deleted !== false) {
        wp_send_json_success(['message' => 'Transaction deleted successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete transaction.']);
    }
}


// Delete Plan
add_action('wp_ajax_bb_delete_plan', 'bb_ajax_delete_plan');
function bb_ajax_delete_plan() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to delete plans.']);
    }

    check_ajax_referer('bb_report_nonce', 'security');

    $plan_id = intval($_POST['plan_id'] ?? 0);
    if (!$plan_id) {
        wp_send_json_error(['message' => 'Invalid plan ID.']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $deleted = $wpdb->delete($table, ['id' => $plan_id, 'user_id' => $user_id], ['%d', '%d']);

    if ($deleted !== false) {
        wp_send_json_success(['message' => 'Plan deleted successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete plan.']);
    }
}


// Update Plan Status
add_action('wp_ajax_bb_update_plan_status', 'bb_ajax_update_plan_status');
function bb_ajax_update_plan_status() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to update plan status.']);
    }

    check_ajax_referer('bb_report_nonce', 'security');

    $plan_id = intval($_POST['plan_id'] ?? 0);
    $new_status = sanitize_text_field($_POST['status'] ?? '');

    if (!$plan_id || !$new_status) {
        wp_send_json_error(['message' => 'Missing plan ID or status.']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $updated = $wpdb->update(
        $table,
        ['status' => $new_status],
        ['id' => $plan_id, 'user_id' => $user_id],
        ['%s'],
        ['%d', '%d']
    );

    if ($updated !== false) {
        wp_send_json_success(['message' => 'Plan status updated.']);
    } else {
        wp_send_json_error(['message' => 'Failed to update plan status.']);
    }
}
