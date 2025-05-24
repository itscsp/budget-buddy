<?php
/**
 * BudgetBuddy Admin Pages
 * Handles the admin interface for managing budget categories
 */

defined('ABSPATH') || exit;

class BudgetBuddyAdmin {
    private $wpdb;
    private $user_id;
    private $categories_table;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->user_id = get_current_user_id();
        $this->categories_table = $wpdb->prefix . 'bb_budget_categories';

        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('wp_ajax_bb_add_category', [$this, 'ajax_add_category']);
        add_action('wp_ajax_bb_get_categories', [$this, 'ajax_get_categories']);
    }

    public function register_admin_menu() {
        add_menu_page(
            'BudgetBuddy',
            'BudgetBuddy',
            'manage_options',
            'budget_buddy',
            [$this, 'render_main_page'],
            'dashicons-chart-pie',
            6
        );
    }

    public function render_main_page() {
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
                <?php $this->display_categories(); ?>
            </div>

            <div class="card">
                <h2>Usage Instructions</h2>
                <p>Use the shortcode <code>[budget_buddy]</code> on any page to display the BudgetBuddy tracker interface.</p>
                <p>Only logged-in users will be able to access their personal financial data.</p>
            </div>
        </div>
        <?php
    }

    public function display_categories() {
        $categories = $this->get_categories();
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
                <?php if ($categories): ?>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo esc_html($category->category_name); ?></td>
                            <td><?php echo esc_html($category->percentage); ?></td>
                            <td><?php echo esc_html($category->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No categories found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    public function ajax_add_category() {
        check_ajax_referer('bb_add_category_nonce', 'bb_category_nonce');

        $category_name = sanitize_text_field($_POST['category_name'] ?? '');
        $percentage = floatval($_POST['percentage'] ?? 0);

        if (empty($category_name) || $percentage < 0 || $percentage > 100) {
            wp_send_json_error(['message' => 'Invalid category name or percentage.']);
        }

        $result = $this->wpdb->insert(
            $this->categories_table,
            [
                'user_id' => $this->user_id,
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

    public function ajax_get_categories() {
        ob_start();
        $this->display_categories();
        $output = ob_get_clean();
        echo $output;
        wp_die();
    }

    private function get_categories() {
        return $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM $this->categories_table WHERE user_id = %d", $this->user_id)
        );
    }
}
?>