<?php

// Function to add a transaction (income/expense)
function bb_add_transaction($type, $amount, $description, $date)
{
    global $wpdb;
    $table = $wpdb->prefix . 'bb_transactions';

    // Only allow logged-in users to add transactions
    if (!is_user_logged_in()) {
        return false;
    }

    $user_id = get_current_user_id();

    return $wpdb->insert($table, [
        'user_id' => $user_id,
        'type' => sanitize_text_field($type),
        'amount' => floatval($amount),
        'description' => sanitize_text_field($description),
        'date' => sanitize_text_field($date)
    ]);
}

function bb_get_transactions_by_month()
{
    global $wpdb;
    $table = $wpdb->prefix . 'bb_transactions';

    // Only allow logged-in users to get transactions
    if (!is_user_logged_in()) {
        return [];
    }

    $user_id = get_current_user_id();

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY date DESC",
            $user_id
        )
    );

    $grouped = [];

    foreach ($results as $transaction) {
        $month = date('Y-m', strtotime($transaction->date));
        if (!isset($grouped[$month])) {
            $grouped[$month] = [];
        }
        $grouped[$month][] = $transaction;
    }

    return $grouped;
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


function bb_update_plan_status($id, $new_status)
{
    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $wpdb->update($table, ['status' => $new_status], ['id' => $id, 'user_id' => $user_id]);
}



function bb_handle_transaction_delete($id)
{

    if (!is_user_logged_in()) {
        return; // prevent unauthenticated access
    }

    global $wpdb;

    $table = $wpdb->prefix . 'bb_transactions';
    $user_id = get_current_user_id();

    // Delete transaction if it belongs to the user
    $deleted = $wpdb->delete($table, ['id' => $id, 'user_id' => $user_id]);

    if ($deleted !== false) {
        wp_redirect(home_url('/budget'));
        exit;
    }
}





function bb_handle_plan_delete($id)
{

    if (!is_user_logged_in()) {
        return; // prevent unauthenticated access
    }

    global $wpdb;

    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    // Delete transaction if it belongs to the user
    $deleted = $wpdb->delete($table, ['id' => $id, 'user_id' => $user_id]);

    if ($deleted !== false) {
        wp_redirect(home_url('/budget'));
        exit;
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

