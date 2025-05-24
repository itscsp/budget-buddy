<?php

// Function to add a transaction (income/expense)
function bb_handle_ajax_add_transaction() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Please log in to add a transaction.']);
    }

    // Check nonce (use bb_transaction_nonce to match the form)
    check_ajax_referer('bb_transaction_nonce', 'bb_transaction_nonce');

    $type = $_POST['type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $description = $_POST['description'] ?? '';
    $date = $_POST['date'] ?? '';
    $category_id = $_POST['category_id'] ?? null; // Expect category_id from form

    // Validate category_id
    if (!empty($category_id)) {
        global $wpdb;
        $user_id = get_current_user_id();
        $categories_table = $wpdb->prefix . 'bb_budget_categories';
        $category_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $categories_table WHERE id = %d AND user_id = %d",
                intval($category_id),
                $user_id
            )
        );

        if (!$category_exists) {
            wp_send_json_error(['message' => 'Invalid category selected.']);
        }
    }

    $result = bb_add_transaction($type, $amount, $description, $date, $category_id);

    if ($result) {
        wp_send_json_success(['message' => 'Transaction added successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to add transaction.']);
    }
}

// Function to add a transaction (income/expense)
function bb_add_transaction($type, $amount, $description, $date, $category_id = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'bb_transactions';

    if (!is_user_logged_in()) {
        return false;
    }

    $user_id = get_current_user_id();

    $data = [
        'user_id'     => $user_id,
        'type'        => sanitize_text_field($type),
        'amount'      => floatval($amount),
        'description' => sanitize_text_field($description),
        'date'        => sanitize_text_field($date),
    ];

    // Only include category_id if it's provided and numeric
    if (!empty($category_id) && is_numeric($category_id)) {
        $data['category_id'] = intval($category_id);
    }

    return $wpdb->insert($table, $data);
}


function bb_get_transactions_by_month()
{
   global $wpdb;
    $user_id = get_current_user_id();
    $transactions_table = $wpdb->prefix . 'bb_transactions';
    $categories_table = $wpdb->prefix . 'bb_budget_categories';

    // Query transactions with a LEFT JOIN to include category name
    $query = $wpdb->prepare(
        "SELECT t.*, c.category_name 
         FROM $transactions_table t 
         LEFT JOIN $categories_table c ON t.category_id = c.id 
         WHERE t.user_id = %d 
         ORDER BY t.date DESC",
        $user_id
    );

    $results = $wpdb->get_results($query);

    // Group transactions by month
    $transactions_by_month = [];
    foreach ($results as $tx) {
        $month = date('Y-m', strtotime($tx->date));
        if (!isset($transactions_by_month[$month])) {
            $transactions_by_month[$month] = [];
        }
        $transactions_by_month[$month][] = $tx;
    }

    return $transactions_by_month;
}

// Function to get user's total balance
function bb_get_user_balance()
{
    global $wpdb;
    $table = $wpdb->prefix . 'bb_transactions';

    if (!is_user_logged_in()) {
        return 0;
    }

    $user_id = get_current_user_id();

    $incomes = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(amount) FROM $table WHERE user_id = %d AND type = 'income'",
            $user_id
        )
    );

    $expenses = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(amount) FROM $table WHERE user_id = %d AND type = 'expense'",
            $user_id
        )
    );

    return floatval($incomes) - floatval($expenses);
}

function bb_add_monthly_plan($plan_month, $plan_text, $amount)
{
    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $wpdb->insert($table, [
        'user_id' => $user_id,
        'plan_text' => sanitize_text_field($plan_text),
        'amount' => floatval($amount),
        'plan_month' => $plan_month,
        'status' => 'pending',
    ]);
}

function bb_get_monthly_plans($month)
{
    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();
    $start_date = date('Y-m-01', strtotime($month));
    $end_date = date('Y-m-t', strtotime($month));

    return $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table WHERE user_id = %d AND plan_month BETWEEN %s AND %s", $user_id, $start_date, $end_date)
    );
}


function bb_ajax_add_plan() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to add a plan.']);
    }

    // Verify nonce
    check_ajax_referer('bb_report_nonce', 'security');

    // Sanitize inputs
    $plan_text  = sanitize_text_field($_POST['plan_text'] ?? '');
    $amount     = floatval($_POST['amount'] ?? 0);
    $plan_month = sanitize_text_field($_POST['plan_month'] ?? '');

    if (empty($plan_text) || empty($amount) || empty($plan_month)) {
        wp_send_json_error(['message' => 'All fields are required.']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $inserted = $wpdb->insert($table, [
        'user_id'    => $user_id,
        'plan_text'  => $plan_text,
        'amount'     => $amount,
        'plan_month' => $plan_month,
        'status'     => 'pending',
    ], ['%d', '%s', '%f', '%s', '%s']);

    if ($inserted) {
        wp_send_json_success(['message' => 'Plan added successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to add plan.']);
    }
}



function bb_ajax_update_plan_status() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to update plan status.']);
    }

    check_ajax_referer('bb_report_nonce', 'security');

    $plan_id = intval($_POST['plan_id'] ?? 0);
    $new_status = sanitize_text_field($_POST['status'] ?? '');

    if (!$plan_id || !$new_status) {
        wp_send_json_error(['message' => 'Missing plan ID or status.']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $updated = $wpdb->update(
        $table,
        ['status' => $new_status],
        ['id' => $plan_id, 'user_id' => $user_id],
        ['%s'],
        ['%d', '%d']
    );

    if ($updated !== false) {
        wp_send_json_success(['message' => 'Plan status updated.']);
    } else {
        wp_send_json_error(['message' => 'Failed to update plan status.']);
    }
}

function bb_ajax_delete_transaction() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to delete transactions.']);
    }
    
    // Verify nonce
    check_ajax_referer('bb_report_nonce', 'security');
    
    // Get transaction ID
    $transaction_id = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
    
    if (!$transaction_id) {
        wp_send_json_error(['message' => 'Invalid transaction ID.']);
    }
    
    // Delete the transaction
    global $wpdb;
    $table = $wpdb->prefix . 'bb_transactions';
    $user_id = get_current_user_id();
    
    $result = $wpdb->delete(
        $table,
        [
            'id' => $transaction_id,
            'user_id' => $user_id // Security: ensure user can only delete their own transactions
        ],
        ['%d', '%d']
    );
    
    if ($result !== false) {
        wp_send_json_success(['message' => 'Transaction deleted successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete transaction. Please try again.']);
    }
}

function bb_ajax_delete_plan() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to delete plans.']);
    }

    check_ajax_referer('bb_report_nonce', 'security');

    $plan_id = intval($_POST['plan_id'] ?? 0);
    if (!$plan_id) {
        wp_send_json_error(['message' => 'Invalid plan ID.']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $deleted = $wpdb->delete($table, ['id' => $plan_id, 'user_id' => $user_id], ['%d', '%d']);

    if ($deleted !== false) {
        wp_send_json_success(['message' => 'Plan deleted successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete plan.']);
    }
}



function bb_get_monthly_plan_total($month)
{
    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $start_date = date('Y-m-01', strtotime($month));
    $end_date = date('Y-m-t', strtotime($month));

    $total = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount)
        FROM $table
        WHERE user_id = %d AND plan_month BETWEEN %s AND %s
    ", $user_id, $start_date, $end_date));

    return floatval($total);
}


/**
 * Get monthly summary data (income, expenses, loans)
 * 
 * @param string $month The month in Y-m format
 * @return array Summary data
 */
function bb_get_monthly_summary($month) {
    global $wpdb;
    $table = $wpdb->prefix . 'bb_transactions';
    $user_id = get_current_user_id();
    
    $start_date = date('Y-m-01', strtotime($month));
    $end_date = date('Y-m-t', strtotime($month));
    
    // Get income total
    $income = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table 
        WHERE user_id = %d 
        AND type = 'income' 
        AND date BETWEEN %s AND %s",
        $user_id, $start_date, $end_date
    ));
    
    // Get expense total
    $expense = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table 
        WHERE user_id = %d 
        AND type = 'expense' 
        AND date BETWEEN %s AND %s",
        $user_id, $start_date, $end_date
    ));
    
    // Get loan total
    $loan = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table 
        WHERE user_id = %d 
        AND type = 'loan' 
        AND date BETWEEN %s AND %s",
        $user_id, $start_date, $end_date
    ));
    
    // Get total transactions count
    $transaction_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table 
        WHERE user_id = %d 
        AND date BETWEEN %s AND %s",
        $user_id, $start_date, $end_date
    ));
    
    // Calculate net savings (income - expense - loan)
    $net = floatval($income) - floatval($expense) - floatval($loan);
    
    return [
        'income' => floatval($income) ?: 0,
        'expense' => floatval($expense) ?: 0,
        'loan' => floatval($loan) ?: 0,
        'net' => $net,
        'transaction_count' => intval($transaction_count),
        'month_name' => date('F Y', strtotime($month))
    ];
}


/**
 * AJAX handler for monthly report
 */
function bb_ajax_get_monthly_report()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bb_report_nonce')) {
        wp_send_json_error('Security check failed');
    }

    if (!isset($_POST['month']) || empty($_POST['month'])) {
        wp_send_json_error('Missing month parameter');
    }

    $month = sanitize_text_field($_POST['month']);
    $summary = bb_get_monthly_summary($month);

    wp_send_json_success($summary);
}

function bb_update_plan_status($id, $new_status)
{
    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $wpdb->update($table, ['status' => $new_status], ['id' => $id, 'user_id' => $user_id]);
}
