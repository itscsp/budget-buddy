<?php
/**
 * Plugin Name: BudgetBuddy
 * Description: A plugin to manage user income and expense
 * Version: 1.0.6
 * Author: Chethan S Poojary
 * Author URL: https://chethanspoojary.com/
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'includes/bb-admin-pages.php';
require_once plugin_dir_path(__FILE__) . 'includes/bb-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/bb-functions2.php';
require_once plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if (!class_exists('BudgetBuddy')) {
    class BudgetBuddy {

        public function __construct() {
            // Hooks
            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
            add_action('plugins_loaded', [$this, 'run_plugin']);
            register_activation_hook(__FILE__, [$this, 'install']);
            register_activation_hook(__FILE__, [$this, 'create_budget_buddy_page']);
            add_shortcode('budget_buddy', [$this, 'tracker_shortcode']);
            add_filter('body_class', [$this, 'add_body_class_if_shortcode_used']);

            // AJAX
            add_action('wp_ajax_bb_get_monthly_report', 'bb_ajax_get_monthly_report');
            add_action('wp_ajax_bb_add_transaction', 'bb_handle_ajax_add_transaction');
            add_action('wp_ajax_nopriv_bb_add_transaction', 'bb_handle_ajax_add_transaction');
            add_action('wp_ajax_bb_delete_transaction', 'bb_ajax_delete_transaction');
            add_action('wp_ajax_bb_add_plan', 'bb_ajax_add_plan');
            add_action('wp_ajax_bb_delete_plan', 'bb_ajax_delete_plan');
            add_action('wp_ajax_bb_update_plan_status', 'bb_ajax_update_plan_status');
        }

        public function enqueue_assets() {
            $plugin_url = plugin_dir_url(__FILE__);

            wp_enqueue_style('bb-style', $plugin_url . 'assets/css/style.css');
            wp_enqueue_script('jquery');
            wp_enqueue_script('budget-buddy-ui-controls', $plugin_url . 'assets/js/bb-ui-controls.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('bb-monthly-report', $plugin_url . 'assets/js/bb-monthly-report.js', ['jquery', 'bb-report-utils'], '1.0.0', true);
            wp_enqueue_script('bb-transaction-operations', $plugin_url . 'assets/js/bb-transaction-operations.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('bb-plan-operations', $plugin_url . 'assets/js/bb-plan-operations.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('bb-report-utils', $plugin_url . 'assets/js/bb-report-utils.js', ['jquery'], '1.0.0', true);

            // Localize scripts
            $localize = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'report_nonce' => wp_create_nonce('bb_report_nonce'),
                'transaction_nonce' => wp_create_nonce('bb_transaction_nonce')
            ];
            wp_localize_script('bb-monthly-report', 'bb_data', $localize);
            wp_localize_script('bb-transaction-operations', 'bb_data', $localize);
            wp_localize_script('bb-plan-operations', 'bb_data', $localize);
        }

        public function enqueue_admin_assets($hook) {
            if ($hook !== 'toplevel_page_budget_buddy') {
                return;
            }
            $plugin_url = plugin_dir_url(__FILE__);
            wp_enqueue_style('bb-admin-style', $plugin_url . 'assets/css/style.css');
            wp_enqueue_script('bb-admin-script', $plugin_url . 'assets/admin/js/script.js', ['jquery'], null, true);
            wp_localize_script('bb-admin-script', 'bb_data', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'report_nonce' => wp_create_nonce('bb_report_nonce')
            ]);
        }

        public function run_plugin() {
            if (is_admin()) {
                PucFactory::buildUpdateChecker(
                    'https://raw.githubusercontent.com/itscsp/budget-buddy/main/manifest.json',
                    __FILE__,
                    'budget-buddy'
                );
            }
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
            ob_start();
            include plugin_dir_path(__FILE__) . 'templates/budget-main.php';
            return ob_get_clean();
        }

        public function create_budget_buddy_page() {
            $page = get_page_by_path('budget-buddy');
            if (!$page) {
                $page_data = [
                    'post_title'   => 'Budget',
                    'post_name'    => 'budget',
                    'post_content' => '[budget_buddy]',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_author'  => 1,
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

    // Initialize plugin
    new BudgetBuddy();
}