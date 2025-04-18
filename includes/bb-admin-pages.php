<?php

// Add admin menu page
add_action('admin_menu', 'bb_admin_menu');

function bb_admin_menu() {
    add_menu_page(
        'BudgetBuddy',
        'BudgetBuddy',
        'manage_options',
        'budget_buddy',
        'bb_render_main_page',
        'dashicons-chart-pie',
        6
    );
}

function bb_render_main_page() {
    include plugin_dir_path(__FILE__) . 'bb-user-ui.php';
}

// Create the admin UI page
function bb_admin_ui() {
    ?>
    <div class="wrap">
        <h1>BudgetBuddy Admin</h1>
        <p>Welcome to BudgetBuddy - a personal finance tracking tool for your users.</p>
        <div class="card">
            <h2>Usage Instructions</h2>
            <p>Use the shortcode <code>[budget_buddy]</code> on any page to display the BudgetBuddy tracker interface.</p>
            <p>Only logged-in users will be able to access their personal financial data.</p>
        </div>
    </div>
    <?php
}