<?php
// Transactions: AJAX handlers and CRUD

function bb_handle_ajax_add_transaction() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Please log in to add a transaction.']);
    }
    check_ajax_referer('bb_transaction_nonce', 'bb_transaction_nonce');

    $type = $_POST['type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $description = $_POST['description'] ?? '';
    $date = $_POST['date'] ?? '';
    $category_id = $_POST['category_id'] ?? null;

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
    }
    wp_send_json_error(['message' => 'Failed to add transaction.']);
}

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
    if (!empty($category_id) && is_numeric($category_id)) {
        $data['category_id'] = intval($category_id);
    }
    return $wpdb->insert($table, $data);
}

function bb_get_transactions_by_month() {
    global $wpdb;
    $user_id = get_current_user_id();
    $transactions_table = $wpdb->prefix . 'bb_transactions';
    $categories_table = $wpdb->prefix . 'bb_budget_categories';

    $query = $wpdb->prepare(
        "SELECT t.*, c.category_name 
         FROM $transactions_table t 
         LEFT JOIN $categories_table c ON t.category_id = c.id 
         WHERE t.user_id = %d 
         ORDER BY t.date DESC",
        $user_id
    );
    $results = $wpdb->get_results($query);

    $transactions_by_month = [];
    foreach ($results as $tx) {
        $month = date('Y-m', strtotime($tx->date));
        $transactions_by_month[$month] = $transactions_by_month[$month] ?? [];
        $transactions_by_month[$month][] = $tx;
    }
    return $transactions_by_month;
}



function bb_ajax_delete_transaction() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to delete transactions.']);
    }
    check_ajax_referer('bb_report_nonce', 'security');

    $transaction_id = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
    if (!$transaction_id) {
        wp_send_json_error(['message' => 'Invalid transaction ID.']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bb_transactions';
    $user_id = get_current_user_id();

    $result = $wpdb->delete(
        $table,
        [ 'id' => $transaction_id, 'user_id' => $user_id ],
        ['%d', '%d']
    );

    if ($result !== false) {
        wp_send_json_success(['message' => 'Transaction deleted successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete transaction. Please try again.']);
    }
}

