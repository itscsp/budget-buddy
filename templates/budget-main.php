<?php
/**
 * Main template for Budget Buddy plugin
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include logic file to handle user checks, transients, and data fetching
include plugin_dir_path(__FILE__) . '../includes/budget-logic.php';
?>

<?php
    global $categories;
    $modal_args = array('categories' => $categories);
    $args = $modal_args;
    include plugin_dir_path(__FILE__) . 'budget-transaction-modal.php';
    unset($args);

    include plugin_dir_path(__FILE__) . 'budget-plan-modal.php';
    include plugin_dir_path(__FILE__) . 'budget-report-modal.php';
?>


<section class="bb-container">
    <?php include plugin_dir_path(__FILE__) . 'budget-header.php'; ?>
    <?php include plugin_dir_path(__FILE__) . 'budget-monthly-transactions.php'; ?>
</section>