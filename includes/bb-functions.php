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

    // Check for recurring plans from previous months that should be copied
    $previous_recurring_plans = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table 
            WHERE user_id = %d 
            AND is_recurring = 1 
            AND plan_month < %s 
            AND NOT EXISTS (
                SELECT 1 FROM $table t2 
                WHERE t2.user_id = $table.user_id 
                AND t2.plan_text = $table.plan_text 
                AND t2.amount = $table.amount 
                AND t2.plan_month = %s
            )",
            $user_id,
            $start_date,
            $start_date
        )
    );

    // Create new instances of recurring plans
    foreach ($previous_recurring_plans as $plan) {
        $wpdb->insert(
            $table,
            [
                'user_id'      => $user_id,
                'plan_text'    => $plan->plan_text,
                'amount'       => $plan->amount,
                'plan_month'   => $start_date,
                'status'       => 'pending',
                'is_recurring' => 1
            ],
            ['%d', '%s', '%f', '%s', '%s', '%d']
        );
    }

    // Get all plans for the current month
    return $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table WHERE user_id = %d AND plan_month BETWEEN %s AND %s", $user_id, $start_date, $end_date)
    );
}


// Debug function to check recurring plans
function bb_debug_check_recurring_plans() {
    if (!current_user_can('administrator')) {
        return 'Only administrators can access this function.';
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    // Get all recurring plans
    $recurring_plans = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND is_recurring = 1 ORDER BY plan_month DESC",
            $user_id
        )
    );

    $output = "=== Recurring Plans ===\n";
    foreach ($recurring_plans as $plan) {
        $output .= sprintf(
            "Plan: %s\nAmount: %s\nMonth: %s\nStatus: %s\n\n",
            $plan->plan_text,
            $plan->amount,
            $plan->plan_month,
            $plan->status
        );
    }

    return $output;
}

function bb_ajax_add_plan() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to add a plan.']);
    }

    // Verify nonce
    check_ajax_referer('bb_report_nonce', 'security');

    // Sanitize inputs
    $plan_text    = sanitize_text_field($_POST['plan_text'] ?? '');
    $amount       = floatval($_POST['amount'] ?? 0);
    $plan_month   = sanitize_text_field($_POST['plan_month'] ?? '');
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $plan_id      = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;

    if (empty($plan_text) || empty($amount) || empty($plan_month)) {
        wp_send_json_error(['message' => 'All fields are required.']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    // If this is an update to an existing plan
    if ($plan_id > 0) {
        // Update the existing plan's recurring status
        $updated = $wpdb->update(
            $table,
            [
                'is_recurring' => $is_recurring,
                'plan_text'   => $plan_text,
                'amount'      => $amount
            ],
            ['id' => $plan_id, 'user_id' => $user_id],
            ['%d', '%s', '%f'],
            ['%d', '%d']
        );

        if ($updated) {
            // If recurring is turned off, prevent future copies
            if (!$is_recurring) {
                // Update any future instances of this plan to not be recurring
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table 
                    SET is_recurring = 0 
                    WHERE user_id = %d 
                    AND plan_text = %s 
                    AND amount = %f 
                    AND plan_month > %s",
                    $user_id,
                    $plan_text,
                    $amount,
                    $plan_month
                ));
            }
            wp_send_json_success(['message' => 'Plan updated successfully.']);
            return;
        }
    }

    // Insert new plan
    $inserted = $wpdb->insert($table, [
        'user_id'      => $user_id,
        'plan_text'    => $plan_text,
        'amount'       => $amount,
        'plan_month'   => $plan_month,
        'status'       => 'pending',
        'is_recurring' => $is_recurring
    ], ['%d', '%s', '%f', '%s', '%s', '%d']);

    if ($inserted) {
        wp_send_json_success(['message' => 'Plan added successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to add plan.']);
    }
}

