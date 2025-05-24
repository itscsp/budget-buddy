<?php

// Add admin menu page
add_action('admin_menu', 'bb_admin_menu');

function bb_admin_menu() {
    add_menu_page(
        'Budget Buddy',
        'Budget Buddy',
        'manage_options',
        'budget_buddy',
        'bb_render_main_page',
        'dashicons-chart-pie',
        6
    );
}

function bb_render_main_page() {
    ?>
    <div class="wrap">
        <h1>BudgetBuddy Admin</h1>
        <p>Welcome to BudgetBuddy - a personal finance tracking tool for your users.</p>
        
        <div class="card">
            <h2>Add Budget Category</h2>
            <form id="bb-add-category-form" method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="category_name">Category Name</label></th>
                        <td><input type="text" name="category_name" id="category_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="percentage">Percentage (%)</label></th>
                        <td><input type="number" name="percentage" id="percentage" step="0.01" min="0" max="100" class="regular-text" required></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Add Category">
                    <?php wp_nonce_field('bb_add_category_nonce', 'bb_category_nonce'); ?>
                </p>
            </form>
            <div id="bb-category-message"></div>
        </div>

        <div class="card">
            <h2>Existing Categories</h2>
            <?php bb_display_categories(); ?>
        </div>

        <div class="card">
            <h2>Usage Instructions</h2>
            <p>Use the shortcode <code>[budget_buddy]</code> on any page to display the BudgetBuddy tracker interface.</p>
            <p>Only logged-in users will be able to access their personal financial data.</p>
        </div>
    </div>

    <?php
}

// Display existing categories
function bb_display_categories() {
    global $wpdb;
    $user_id = get_current_user_id();
    $categories_table = $wpdb->prefix . 'bb_budget_categories';
    
    $categories = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $categories_table WHERE user_id = %d", $user_id)
    );
    
    ?>
    <table id="bb-categories-table" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Category Name</th>
                <th>Percentage (%)</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($categories) : ?>
                <?php foreach ($categories as $category) : ?>
                    <tr>
                        <td><?php echo esc_html($category->category_name); ?></td>
                        <td><?php echo esc_html($category->percentage); ?></td>
                        <td><?php echo esc_html($category->created_at); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="3">No categories found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}

// AJAX handler for adding a category
add_action('wp_ajax_bb_add_category', 'bb_ajax_add_category');

function bb_ajax_add_category() {
    check_ajax_referer('bb_add_category_nonce', 'bb_category_nonce');
    
    global $wpdb;
    $user_id = get_current_user_id();
    $category_name = sanitize_text_field($_POST['category_name']);
    $percentage = floatval($_POST['percentage']);
    
    if (empty($category_name) || $percentage < 0 || $percentage > 100) {
        wp_send_json_error(['message' => 'Invalid category name or percentage.']);
    }
    
    $categories_table = $wpdb->prefix . 'bb_budget_categories';
    
    $result = $wpdb->insert(
        $categories_table,
        [
            'user_id' => $user_id,
            'category_name' => $category_name,
            'percentage' => $percentage,
            'created_at' => current_time('mysql')
        ],
        ['%d', '%s', '%f', '%s']
    );
    
    if ($result) {
        wp_send_json_success(['message' => 'Category added successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to add category.']);
    }
}

// AJAX handler for getting categories (to refresh table)
add_action('wp_ajax_bb_get_categories', 'bb_ajax_get_categories');

function bb_ajax_get_categories() {
    ob_start();
    bb_display_categories();
    $output = ob_get_clean();
    echo $output;
    wp_die();
}