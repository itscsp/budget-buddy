<?php
/**
 * Plugin Name: BudgetBuddy
 * Description: A plugin to manage user income and expense
 * Version: 1.1.1
 * Author: Chethan S Poojary
 * Author URL: https://chethanspoojary.com/
 */

defined('ABSPATH') || exit;

define('BUDGET_VERSION', '1.1.1');

// Shared plugin constants
if (!defined('BUDGET_BUDDY_FILE')) {
    define('BUDGET_BUDDY_FILE', __FILE__);
    define('BUDGET_BUDDY_DIR', plugin_dir_path(__FILE__));
    define('BUDGET_BUDDY_URL', plugin_dir_url(__FILE__));
}

// Core includes
require_once BUDGET_BUDDY_DIR . 'includes/bb-admin-pages.php';
// Modularized function groups
require_once BUDGET_BUDDY_DIR . 'includes/functions/transactions.php';
require_once BUDGET_BUDDY_DIR . 'includes/functions/plans.php';
require_once BUDGET_BUDDY_DIR . 'includes/functions/reports.php';
require_once BUDGET_BUDDY_DIR . 'includes/functions/api.php';
require_once BUDGET_BUDDY_DIR . 'plugin-update-checker/plugin-update-checker.php';
require_once BUDGET_BUDDY_DIR . 'includes/class-budget-buddy.php';

// Activation: create tables and page on first install
register_activation_hook(BUDGET_BUDDY_FILE, function () {
    if (class_exists('BudgetBuddy')) {
        $bb = new BudgetBuddy();
        if (method_exists($bb, 'install')) {
            $bb->install();
        }
        if (method_exists($bb, 'create_budget_buddy_page')) {
            $bb->create_budget_buddy_page();
        }
    }
});

// Initialize plugin
new BudgetBuddy();