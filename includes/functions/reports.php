<?php
// Reports and deletion/update AJAX
function bb_get_user_balance() {
    global $wpdb;
    $table = $wpdb->prefix . 'bb_transactions';
    if (!is_user_logged_in()) {
        return 0;
    }
    $user_id = get_current_user_id();

    $incomes = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table WHERE user_id = %d AND type = 'income'",
        $user_id
    ));
    $expenses = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table WHERE user_id = %d AND type = 'expense'",
        $user_id
    ));

    return floatval($incomes) - floatval($expenses);
}

function bb_get_monthly_summary($month) {
    global $wpdb;
    $table = $wpdb->prefix . 'bb_transactions';
    $categories_table = $wpdb->prefix . 'bb_budget_categories';
    $user_id = get_current_user_id();

    $start_date = date('Y-m-01', strtotime($month));
    $end_date = date('Y-m-t', strtotime($month));

    $income = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table WHERE user_id = %d AND type = 'income' AND date BETWEEN %s AND %s",
        $user_id, $start_date, $end_date
    ));
    $expense = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table WHERE user_id = %d AND type = 'expense' AND date BETWEEN %s AND %s",
        $user_id, $start_date, $end_date
    ));
    $loan = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table WHERE user_id = %d AND type = 'loan' AND date BETWEEN %s AND %s",
        $user_id, $start_date, $end_date
    ));
    $transaction_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_id = %d AND date BETWEEN %s AND %s",
        $user_id, $start_date, $end_date
    ));

    $net = floatval($income) - floatval($expense) - floatval($loan);

    $category_percents = [50, 25, 15, 10];
    $category_spent = [];
    foreach ($category_percents as $percent) {
        $amount = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(t.amount) FROM $table t
            LEFT JOIN $categories_table c ON t.category_id = c.id
            WHERE t.user_id = %d AND t.type = 'expense' AND t.date BETWEEN %s AND %s AND c.percentage = %d",
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

function bb_ajax_get_monthly_report() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bb_report_nonce')) {
        wp_send_json_error('Security check failed');
    }
    if (empty($_POST['month'])) {
        wp_send_json_error('Missing month parameter');
    }
    $month = sanitize_text_field($_POST['month']);
    $summary = bb_get_monthly_summary($month);
    wp_send_json_success($summary);
}
