<?php

// Function to add a transaction (income/expense)
function bb_add_transaction($type, $amount, $description, $date) {
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

function bb_get_transactions_by_month() {
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
        $month = date('Y-m', strtotime($transaction->date)); // e.g., "2025-04"
        if (!isset($grouped[$month])) {
            $grouped[$month] = [];
        }
        $grouped[$month][] = $transaction;
    }
    
    return $grouped;
}

// Function to get user's total balance
function bb_get_user_balance() {
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
            "SELECT SUM(amount) FROM $table WHERE user_id = %d AND (type = 'expense' OR type = 'loan')",
            $user_id
        )
    );
    
    return floatval($incomes) - floatval($expenses);
}