<?php
/**
 * Plugin Name: BudgetBuddy
 * Description: A plugin to manage user income and expense
 * Version: 0.1
 * Author: Chethan S Poojary
 * Author URL: https://chethanspoojary.com/
 */

defined('ABSPATH') || exit;

class BudgetBuddy {
    private $plugin_url;
    private $plugin_path;

    public function __construct() {
        $this->plugin_url = plugin_dir_url(__FILE__);
        $this->plugin_path = plugin_dir_path(__FILE__);

        // Include required files
        require_once $this->plugin_path . 'includes/bb-functions.php';
        require_once $this->plugin_path . 'includes/bb-admin-pages.php';

        // Register hooks
        $this->register_hooks();
        $this->register_shortcode();
        $this->register_ajax_handlers();

        // Initialize other classes
        new BudgetBuddyFunctions();
        new BudgetBuddyAdmin();
    }

    private function register_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter('body_class', [$this, 'add_body_class_if_shortcode_used']);
        register_activation_hook(__FILE__, [$this, 'install']);
        register_activation_hook(__FILE__, [$this, 'create_budget_buddy_page']);
    }

    private function register_shortcode() {
        add_shortcode('budget_buddy', [$this, 'tracker_shortcode']);
    }

    private function register_ajax_handlers() {
        // AJAX handlers are managed by BudgetBuddyFunctions
    }

    public function enqueue_assets() {
        wp_enqueue_style('bb-style', $this->plugin_url . 'assets/css/style.css');
        wp_enqueue_script('bb-script', $this->plugin_url . 'assets/js/script.js', ['jquery'], null, true);

        wp_localize_script('bb-script', 'bb_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'report_nonce' => wp_create_nonce('bb_report_nonce'),
            'transaction_nonce' => wp_create_nonce('bb_transaction_nonce')
        ]);
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_budget_buddy') {
            return;
        }

        wp_enqueue_style('bb-admin-style', $this->plugin_url . 'assets/css/style.css');
        wp_enqueue_script('bb-admin-script', $this->plugin_url . 'assets/admin/js/script.js', ['jquery'], null, true);

        wp_localize_script('bb-admin-script', 'bb_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'report_nonce' => wp_create_nonce('bb_report_nonce')
        ]);
    }

    public function install() {
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

    public function tracker_shortcode($atts) {
        if (!is_user_logged_in()) {
            $login_url = wp_login_url(get_permalink());
            return '<div class="bb_not_logged_in">
                    <h2>BudgetBuddy</h2>
                    <p>Please <a href="' . $login_url . '">login</a> to access your personal budget tracker.</p>
                    </div>';
        }

        global $bb_using_shortcode;
        $bb_using_shortcode = true;

        // Include BudgetBuddyPage only when needed
        require_once $this->plugin_path . 'templates/budget-buddy-page.php';
        $page = new BudgetBuddyPage();
        return $page->render();
    }

    public function create_budget_buddy_page() {
        $page = get_page_by_path('budget-buddy');

        if (!$page) {
            $page_data = [
                'post_title'     => 'Budget',
                'post_name'      => 'budget',
                'post_content'   => '[budget_buddy]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'post_author'    => 1,
            ];

            wp_insert_post($page_data);
        }
    }

    public function add_body_class_if_shortcode_used($classes) {
        global $bb_using_shortcode;

        if (!empty($bb_using_shortcode)) {
            $classes[] = 'budget-buddy-active';
        }

        return $classes;
    }
}

// Initialize the plugin
new BudgetBuddy();
?>