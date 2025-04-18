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

function bb_enqueue_assets() {
    $plugin_url = plugin_dir_url(__FILE__);

    // Only load on pages where the shortcode is used
    wp_enqueue_style('bb-style', $plugin_url . 'assets/css/style.css');
    wp_enqueue_script('bb-script', $plugin_url . 'assets/js/script.js', array('jquery'), null, true);
}

add_action('init', 'bb_handle_transaction_delete');

function bb_handle_transaction_delete() {
    if (isset($_POST['bb_delete_transaction']) && !empty($_POST['transaction_id'])) {
        if (!is_user_logged_in()) {
            return; // prevent unauthenticated access
        }

        global $wpdb;

        $table = $wpdb->prefix . 'bb_transactions';
        $id = intval($_POST['transaction_id']);
        $user_id = get_current_user_id();

        // Delete transaction if it belongs to the user
        $deleted = $wpdb->delete($table, ['id' => $id, 'user_id' => $user_id]);

        if ($deleted !== false) {
            wp_redirect(home_url('/budget'));
            exit;
        }
    }
}


// Include plugin files
include_once plugin_dir_path(__FILE__) . 'includes/bb-admin-pages.php';
include_once plugin_dir_path(__FILE__) . 'includes/bb-functions.php';

// Activation Hook: Create database table on plugin activation
register_activation_hook(__FILE__, 'bb_install');

function bb_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bb_transactions';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        type varchar(10) NOT NULL,
        amount float NOT NULL,
        description text,
        date date NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}




function bb_tracker_shortcode($atts) {
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

    // Include the HTML UI
    include plugin_dir_path(__FILE__) . 'includes/bb-user-ui.php';

    return ob_get_clean();
}

add_shortcode('budget_buddy', 'bb_tracker_shortcode');

add_filter('body_class', 'bb_add_body_class_if_shortcode_used');
function bb_add_body_class_if_shortcode_used($classes) {
    global $bb_using_shortcode;

    if (!empty($bb_using_shortcode)) {
        $classes[] = 'budget-buddy-active';
    }

    return $classes;
}
