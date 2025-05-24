<?php

/**
 * Plugin Name: BudgetBuddy
 * Description: A plugin to manage user income and expense
 * Version: 1.0.0
 * Author: Chethan S Poojary
 * Author URL: https://chethanspoojary.com/
 */

defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', 'bb_enqueue_assets');

function bb_enqueue_assets() {
    $plugin_url = plugin_dir_url(__FILE__);

    wp_enqueue_style('bb-style', $plugin_url . 'assets/css/style.css');
    wp_enqueue_script('bb-script', $plugin_url . 'assets/js/script.js', array('jquery'), null, true);

    wp_localize_script('bb-script', 'bb_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'report_nonce' => wp_create_nonce('bb_report_nonce'),
        'transaction_nonce' => wp_create_nonce('bb_transaction_nonce')
    ));
}

// Enqueue admin scripts
add_action('admin_enqueue_scripts', 'bb_enqueue_admin_assets');

function bb_enqueue_admin_assets($hook) {
    // Only load on the BudgetBuddy admin page
    if ($hook !== 'toplevel_page_budget_buddy') {
        return;
    }

    $plugin_url = plugin_dir_url(__FILE__);

    // Enqueue admin styles and scripts
    wp_enqueue_style('bb-admin-style', $plugin_url . 'assets/css/style.css');
    wp_enqueue_script('bb-admin-script', $plugin_url . 'assets/admin/js/script.js', array('jquery'), null, true);

    // Pass data to JavaScript
    wp_localize_script('bb-admin-script', 'bb_data', array(
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

    $transactions_table = $wpdb->prefix . 'bb_transactions';
    $plans_table = $wpdb->prefix . 'bb_monthly_plans';
    $categories_table = $wpdb->prefix . 'bb_budget_categories';

    $sql1 = "CREATE TABLE $transactions_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        type varchar(10) NOT NULL,
        amount float NOT NULL,
        description text,
        date date NOT NULL,
        category_id mediumint(9),
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

     $sql3 = "CREATE TABLE $categories_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        category_name varchar(100) NOT NULL,
        percentage float NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);
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



// Add this to ensure the AJAX handler is registered properly for both logged-in and non-logged-in users
add_action('wp_ajax_bb_add_transaction', 'bb_handle_ajax_add_transaction');
add_action('wp_ajax_nopriv_bb_add_transaction', 'bb_handle_ajax_add_transaction');

// AJAX handler for deleting transactions
add_action('wp_ajax_bb_delete_transaction', 'bb_ajax_delete_transaction');

add_action('wp_ajax_bb_add_plan', 'bb_ajax_add_plan');

// Delete Plan
add_action('wp_ajax_bb_delete_plan', 'bb_ajax_delete_plan');

// Update Plan Status
add_action('wp_ajax_bb_update_plan_status', 'bb_ajax_update_plan_status');

