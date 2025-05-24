<?php
/**
 * BudgetBuddy Functions
 * Core functionality for handling budget-related operations
 */

defined('ABSPATH') || exit;

class BudgetBuddyFunctions {
    private $wpdb;
    private $user_id;
    private $transactions_table;
    private $plans_table;
    private $categories_table;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->user_id = get_current_user_id();
        $this->transactions_table = $wpdb->prefix . 'bb_transactions';
        $this->plans_table = $wpdb->prefix . 'bb_monthly_plans';
        $this->categories_table = $wpdb->prefix . 'bb_budget_categories';

        // Register AJAX handlers
        add_action('wp_ajax_bb_add_transaction', [$this, 'handle_ajax_add_transaction']);
        add_action('wp_ajax_bb_delete_transaction', [$this, 'ajax_delete_transaction']);
        add_action('wp_ajax_bb_add_plan', [$this, 'ajax_add_plan']);
        add_action('wp_ajax_bb_delete_plan', [$this, 'ajax_delete_plan']);
        add_action('wp_ajax_bb_get_monthly_report', [$this, 'ajax_get_monthly_report']);
        add_action('wp_ajax_bb_update_plan_status', [$this, 'ajax_update_plan_status']);
    }

    public function get_user_balance() {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT type, amount FROM {$this->transactions_table} WHERE user_id = %d",
                $this->user_id
            )
        );

        $balance = 0;
        foreach ($results as $row) {
            if ($row->type === 'income') {
                $balance += $row->amount;
            } elseif (in_array($row->type, ['expense', 'loan'])) {
                $balance -= $row->amount;
            }
        }

        return $balance;
    }

    public function get_transactions_by_month() {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT t.*, c.category_name 
                FROM {$this->transactions_table} t 
                LEFT JOIN {$this->categories_table} c ON t.category_id = c.id 
                WHERE t.user_id = %d 
                ORDER BY t.date DESC",
                $this->user_id
            )
        );

        $transactions_by_month = [];
        foreach ($results as $row) {
            $month = date('Y-m', strtotime($row->date));
            $transactions_by_month[$month][] = $row;
        }

        return $transactions_by_month;
    }

    public function get_monthly_plans($plan_month) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->plans_table} WHERE user_id = %d AND plan_month = %s",
                $this->user_id,
                $plan_month
            )
        );
    }

    public function get_monthly_plan_total($plan_month) {
        $results = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(amount) FROM {$this->plans_table} WHERE user_id = %d AND plan_month = %s",
                $this->user_id,
                $plan_month
            )
        );

        return $results ? floatval($results) : 0;
    }

    public function handle_ajax_add_transaction() {
        check_ajax_referer('bb_transaction_nonce', 'bb_transaction_nonce');

        $type = sanitize_text_field($_POST['type'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $description = sanitize_text_field($_POST['description'] ?? '');
        $date = sanitize_text_field($_POST['date'] ?? date('Y-m-d'));
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;

        if (!in_array($type, ['income', 'expense', 'loan']) || $amount <= 0) {
            wp_send_json_error(['message' => 'Invalid transaction type or amount.']);
        }

        $result = $this->wpdb->insert(
            $this->transactions_table,
            [
                'user_id' => $this->user_id,
                'type' => $type,
                'amount' => $amount,
                'description' => $description,
                'date' => $date,
                'category_id' => $category_id
            ],
            ['%d', '%s', '%f', '%s', '%s', '%d']
        );

        if ($result) {
            wp_send_json_success(['message' => 'Transaction added successfully!']);
        } else {
            wp_send_json_error(['message' => 'Failed to add transaction.']);
        }
    }

    public function ajax_delete_transaction() {
        check_ajax_referer('bb_report_nonce', 'security');

        $transaction_id = intval($_POST['transaction_id'] ?? 0);

        if ($transaction_id <= 0) {
            wp_send_json_error(['message' => 'Invalid transaction ID.']);
        }

        $result = $this->wpdb->delete(
            $this->transactions_table,
            ['id' => $transaction_id, 'user_id' => $this->user_id],
            ['%d', '%d']
        );

        if ($result) {
            wp_send_json_success(['message' => 'Transaction deleted successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete transaction or transaction not found.']);
        }
    }

    public function ajax_add_plan() {
        check_ajax_referer('bb_report_nonce', 'security');

        $plan_text = sanitize_text_field($_POST['plan_text'] ?? '');
        $amount = floatval($_POST['plan_amount'] ?? 0);
        $plan_month = sanitize_text_field($_POST['plan_month'] ?? date('Y-m-01'));

        if (empty($plan_text) || $amount <= 0) {
            wp_send_json_error(['message' => 'Invalid plan text or amount.']);
        }

        $result = $this->wpdb->insert(
            $this->plans_table,
            [
                'user_id' => $this->user_id,
                'plan_text' => $plan_text,
                'amount' => $amount,
                'plan_month' => $plan_month,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%f', '%s', '%s', '%s']
        );

        if ($result) {
            wp_send_json_success(['message' => 'Plan added successfully!']);
        } else {
            wp_send_json_error(['message' => 'Failed to add plan.']);
        }
    }

    public function ajax_delete_plan() {
        check_ajax_referer('bb_report_nonce', 'security');

        $plan_id = intval($_POST['plan_id'] ?? 0);

        if ($plan_id <= 0) {
            wp_send_json_error(['message' => 'Invalid plan ID.']);
        }

        $result = $this->wpdb->delete(
            $this->plans_table,
            ['id' => $plan_id, 'user_id' => $this->user_id],
            ['%d', '%d']
        );

        if ($result) {
            wp_send_json_success(['message' => 'Plan deleted successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete plan or plan not found.']);
        }
    }

    public function ajax_update_plan_status() {
        check_ajax_referer('bb_report_nonce', 'security');

        $plan_id = intval($_POST['plan_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['status'] ?? '');

        if ($plan_id <= 0 || !in_array($new_status, ['pending', 'done'])) {
            wp_send_json_error(['message' => 'Invalid plan ID or status.']);
        }

        $result = $this->wpdb->update(
            $this->plans_table,
            ['status' => $new_status],
            ['id' => $plan_id, 'user_id' => $this->user_id],
            ['%s'],
            ['%d', '%d']
        );

        if ($result !== false) {
            wp_send_json_success(['message' => 'Plan status updated successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to update plan status.']);
        }
    }

    public function ajax_get_monthly_report() {
        check_ajax_referer('bb_report_nonce', 'nonce');

        // Sanitize and normalize month input
        $month = isset($_POST['month']) ? sanitize_text_field($_POST['month']) : date('Y-m');
        // Convert to YYYY-MM format if necessary
        $month_start = date('Y-m-01', strtotime($month));
        $month_end = date('Y-m-t', strtotime($month_start));

        // Debug: Log the query parameters
        error_log("BudgetBuddy: Fetching report for user {$this->user_id}, month {$month_start} to {$month_end}");

        $transactions = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT type, amount 
                FROM {$this->transactions_table} 
                WHERE user_id = %d 
                AND date >= %s 
                AND date <= %s",
                $this->user_id,
                $month_start,
                $month_end
            )
        );

        // Debug: Log the number of transactions found
        error_log("BudgetBuddy: Found " . count($transactions) . " transactions");

        $income = 0;
        $expense = 0;
        $loan = 0;
        $transaction_count = count($transactions);

        foreach ($transactions as $tx) {
            if ($tx->type === 'income') {
                $income += floatval($tx->amount);
            } elseif ($tx->type === 'expense') {
                $expense += floatval($tx->amount);
            } elseif ($tx->type === 'loan') {
                $loan += floatval($tx->amount);
            }
        }

        $net = $income - $expense - $loan;

        $data = [
            'month_name' => date('F Y', strtotime($month_start)),
            'income' => $income,
            'expense' => $expense,
            'loan' => $loan,
            'net' => $net,
            'transaction_count' => $transaction_count
        ];

        wp_send_json_success($data);
    }
}
?>