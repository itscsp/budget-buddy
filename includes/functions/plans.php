<?php
// Plans: CRUD and AJAX for monthly plans

function bb_add_monthly_plan($plan_month, $plan_text, $amount) {
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

function bb_get_monthly_plans($month) {
    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();
    $start_date = date('Y-m-01', strtotime($month));
    $end_date = date('Y-m-t', strtotime($month));

    // Copy recurring plans forward if not present
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

    return $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table WHERE user_id = %d AND plan_month BETWEEN %s AND %s", $user_id, $start_date, $end_date)
    );
}

function bb_ajax_add_plan() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to add a plan.']);
    }
    check_ajax_referer('bb_report_nonce', 'security');

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

    if ($plan_id > 0) {
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
            if (!$is_recurring) {
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
    }
    wp_send_json_error(['message' => 'Failed to add plan.']);
}

function bb_update_plan_status($id, $new_status) {
    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();
    $wpdb->update($table, ['status' => $new_status], ['id' => $id, 'user_id' => $user_id]);
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

function bb_get_monthly_plan_total($month) {
    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $start_date = date('Y-m-01', strtotime($month));
    $end_date = date('Y-m-t', strtotime($month));

    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table WHERE user_id = %d AND plan_month BETWEEN %s AND %s",
        $user_id, $start_date, $end_date
    ));
    return floatval($total);
}

function bb_get_paid_plan_amount($month) {
    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $start_date = date('Y-m-01', strtotime($month));
    $end_date = date('Y-m-t', strtotime($month));

    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table WHERE user_id = %d AND status = 'done' AND plan_month BETWEEN %s AND %s",
        $user_id, $start_date, $end_date
    ));
    return floatval($total);
}

function bb_get_pending_plan_amount($month) {
    global $wpdb;
    $table = $wpdb->prefix . 'bb_monthly_plans';
    $user_id = get_current_user_id();

    $start_date = date('Y-m-01', strtotime($month));
    $end_date = date('Y-m-t', strtotime($month));

    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM $table WHERE user_id = %d AND status = 'pending' AND plan_month BETWEEN %s AND %s",
        $user_id, $start_date, $end_date
    ));
    return floatval($total);
}
