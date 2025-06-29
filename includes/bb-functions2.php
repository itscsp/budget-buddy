<?php

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
 * Get total amount of 'paid' (status: done) plans for a given month
 *
 * @param string $month Format: Y-m
 * @return float
 */
function bb_get_paid_plan_amount($month) {
    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $start_date = date('Y-m-01', strtotime($month));
    $end_date = date('Y-m-t', strtotime($month));

    $total = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount)
        FROM $table
        WHERE user_id = %d AND status = 'done' AND plan_month BETWEEN %s AND %s
    ", $user_id, $start_date, $end_date));

    return floatval($total);
}

/**
 * Get total amount of 'pending' plans for a given month
 *
 * @param string $month Format: Y-m
 * @return float
 */
function bb_get_pending_plan_amount($month) {
    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $start_date = date('Y-m-01', strtotime($month));
    $end_date = date('Y-m-t', strtotime($month));

    $total = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(amount)
        FROM $table
        WHERE user_id = %d AND status = 'pending' AND plan_month BETWEEN %s AND %s
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
    $categories_table = $wpdb->prefix . 'bb_budget_categories';
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

    // Calculate amount spent on each category percentage
    $category_percents = [50, 25, 15, 10];
    $category_spent = [];
    foreach ($category_percents as $percent) {
        $amount = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(t.amount) FROM $table t
            LEFT JOIN $categories_table c ON t.category_id = c.id
            WHERE t.user_id = %d
            AND t.type = 'expense'
            AND t.date BETWEEN %s AND %s
            AND c.percentage = %d",
            $user_id, $start_date, $end_date, $percent
        ));
        $category_spent[$percent] = floatval($amount) ?: 0;
    }

    return [
        'income' => floatval($income) ?: 0,
        'expense' => floatval($expense) ?: 0,
        'loan' => floatval($loan) ?: 0,
        'net' => $net,
        'transaction_count' => intval($transaction_count),
        'month_name' => date('F Y', strtotime($month)),
        'spent_50_percent' => $category_spent[50],
        'spent_25_percent' => $category_spent[25],
        'spent_15_percent' => $category_spent[15],
        'spent_10_percent' => $category_spent[10],
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
