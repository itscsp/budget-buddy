<?php
// REST API endpoints for BudgetBuddy
defined('ABSPATH') || exit;

/**
 * Permission callbacks
 */
function bb_api_permissions_read() {
    return is_user_logged_in() && current_user_can('read');
}

function bb_api_permissions_write() {
    return is_user_logged_in();
}

/**
 * Register REST routes
 */
add_action('rest_api_init', function () {
    register_rest_route('bb/v1', '/transactions', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'bb_api_get_transactions',
            'permission_callback' => 'bb_api_permissions_read',
            'args'                => [
                'year'  => [
                    'description' => 'Filter transactions by 4-digit year.',
                    'type'        => 'integer',
                    'required'    => false,
                ],
                'month' => [
                    'description' => 'Filter transactions by month (1-12).',
                    'type'        => 'integer',
                    'required'    => false,
                ],
            ],
        ],
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'bb_api_create_transaction',
            'permission_callback' => 'bb_api_permissions_write',
            'args'                => [
                'type'        => [ 'type' => 'string', 'required' => true ],
                'amount'      => [ 'type' => 'number', 'required' => true ],
                'description' => [ 'type' => 'string', 'required' => false ],
                'date'        => [ 'type' => 'string', 'required' => true, 'description' => 'Date in Y-m-d format' ],
                'category_id' => [ 'type' => 'integer', 'required' => false ],
            ],
        ],
    ]);

    // Optional: Update and Delete endpoints for completeness
    register_rest_route('bb/v1', '/transactions/(?P<id>\\d+)', [
        [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => 'bb_api_update_transaction',
            'permission_callback' => 'bb_api_permissions_write',
            'args'                => [
                'type'        => [ 'type' => 'string', 'required' => false ],
                'amount'      => [ 'type' => 'number', 'required' => false ],
                'description' => [ 'type' => 'string', 'required' => false ],
                'date'        => [ 'type' => 'string', 'required' => false ],
                'category_id' => [ 'type' => 'integer', 'required' => false ],
            ],
        ],
        [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => 'bb_api_delete_transaction',
            'permission_callback' => 'bb_api_permissions_write',
        ],
    ]);
});

/**
 * GET /bb/v1/transactions
 * Returns transactions grouped as: { 'YYYY': { 'MM': [ {id, type, amount, date, description, category} ] } }
 */
function bb_api_get_transactions(WP_REST_Request $request) {
    if (!is_user_logged_in()) {
        return new WP_REST_Response([ 'message' => 'Authentication required.' ], 401);
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $transactions_table = $wpdb->prefix . 'bb_transactions';
    $categories_table   = $wpdb->prefix . 'bb_budget_categories';

    $where  = [ 't.user_id = %d' ];
    $params = [ $user_id ];

    $year  = $request->get_param('year');
    $month = $request->get_param('month');

    if (!empty($year) && is_numeric($year)) {
        $where[]  = 'YEAR(t.date) = %d';
        $params[] = intval($year);
    }
    if (!empty($month) && is_numeric($month)) {
        $where[]  = 'MONTH(t.date) = %d';
        $params[] = intval($month);
    }

    $sql = "SELECT t.id, t.type, t.amount, t.description, t.date, c.category_name AS category
            FROM {$transactions_table} t
            LEFT JOIN {$categories_table} c ON t.category_id = c.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY t.date DESC, t.id DESC";

    $prepared = $wpdb->prepare($sql, $params);
    $rows = $wpdb->get_results($prepared);

    $grouped = [];
    foreach ($rows as $row) {
        $y = date('Y', strtotime($row->date));
        $m = date('m', strtotime($row->date));
        if (!isset($grouped[$y])) {
            $grouped[$y] = [];
        }
        if (!isset($grouped[$y][$m])) {
            $grouped[$y][$m] = [];
        }
        $grouped[$y][$m][] = [
            'id'          => intval($row->id),
            'type'        => $row->type,
            'amount'      => floatval($row->amount),
            'date'        => $row->date,
            'description' => $row->description,
            'category'    => $row->category ? $row->category : null,
        ];
    }

    return new WP_REST_Response($grouped, 200);
}

/**
 * POST /bb/v1/transactions
 * Create a transaction for the current user.
 */
function bb_api_create_transaction(WP_REST_Request $request) {
    if (!is_user_logged_in()) {
        return new WP_REST_Response([ 'message' => 'Authentication required.' ], 401);
    }

    $type        = sanitize_text_field($request->get_param('type'));
    $amount      = floatval($request->get_param('amount'));
    $description = sanitize_text_field($request->get_param('description'));
    $date        = sanitize_text_field($request->get_param('date'));
    $category_id = $request->get_param('category_id');

    if (empty($type) || empty($date) || !is_numeric($amount)) {
        return new WP_REST_Response([ 'message' => 'type, amount, and date are required.' ], 400);
    }
    // Basic date format check (Y-m-d)
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return new WP_REST_Response([ 'message' => 'Invalid date format. Use Y-m-d.' ], 400);
    }

    // If category is provided, ensure it belongs to the user
    if (!empty($category_id)) {
        global $wpdb;
        $categories_table = $wpdb->prefix . 'bb_budget_categories';
        $user_id = get_current_user_id();
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$categories_table} WHERE id = %d AND user_id = %d",
                intval($category_id),
                $user_id
            )
        );
        if (!$exists) {
            return new WP_REST_Response([ 'message' => 'Invalid category.' ], 400);
        }
    }

    // Use existing helper to insert
    $result = bb_add_transaction($type, $amount, $description, $date, $category_id);
    if (!$result) {
        return new WP_REST_Response([ 'message' => 'Failed to create transaction.' ], 500);
    }

    global $wpdb;
    $insert_id = intval($wpdb->insert_id);

    // Return the created record
    $response_data = [
        'id'          => $insert_id,
        'type'        => $type,
        'amount'      => $amount,
        'date'        => $date,
        'description' => $description,
        'category_id' => !empty($category_id) ? intval($category_id) : null,
    ];

    return new WP_REST_Response($response_data, 201);
}

/**
 * PATCH/PUT /bb/v1/transactions/{id}
 */
function bb_api_update_transaction(WP_REST_Request $request) {
    if (!is_user_logged_in()) {
        return new WP_REST_Response([ 'message' => 'Authentication required.' ], 401);
    }
    global $wpdb;
    $table  = $wpdb->prefix . 'bb_transactions';
    $user_id = get_current_user_id();
    $id     = intval($request['id']);

    // Ensure the transaction belongs to the user
    $owner = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$table} WHERE id = %d", $id));
    if (!$owner || intval($owner) !== $user_id) {
        return new WP_REST_Response([ 'message' => 'Not found.' ], 404);
    }

    $data  = [];
    $types = [];
    $maybe = function($key, $type = 'string') use ($request, &$data, &$types) {
        if (!is_null($request->get_param($key))) {
            $val = $request->get_param($key);
            if ($type === 'string') $val = sanitize_text_field($val);
            if ($type === 'number') $val = floatval($val);
            if ($type === 'int') $val = intval($val);
            $data[$key] = $val;
            $types[] = $type === 'number' ? '%f' : ($type === 'int' ? '%d' : '%s');
        }
    };

    $maybe('type', 'string');
    $maybe('amount', 'number');
    $maybe('description', 'string');
    $maybe('date', 'string');
    $maybe('category_id', 'int');

    if (empty($data)) {
        return new WP_REST_Response([ 'message' => 'No fields to update.' ], 400);
    }

    $updated = $wpdb->update($table, $data, [ 'id' => $id, 'user_id' => $user_id ], $types, [ '%d', '%d' ]);
    if ($updated === false) {
        return new WP_REST_Response([ 'message' => 'Failed to update transaction.' ], 500);
    }

    return new WP_REST_Response([ 'message' => 'Updated', 'id' => $id ], 200);
}

/**
 * DELETE /bb/v1/transactions/{id}
 */
function bb_api_delete_transaction(WP_REST_Request $request) {
    if (!is_user_logged_in()) {
        return new WP_REST_Response([ 'message' => 'Authentication required.' ], 401);
    }
    global $wpdb;
    $table  = $wpdb->prefix . 'bb_transactions';
    $user_id = get_current_user_id();
    $id     = intval($request['id']);

    $deleted = $wpdb->delete($table, [ 'id' => $id, 'user_id' => $user_id ], [ '%d', '%d' ]);
    if ($deleted === false) {
        return new WP_REST_Response([ 'message' => 'Failed to delete transaction.' ], 500);
    }
    if ($deleted === 0) {
        return new WP_REST_Response([ 'message' => 'Not found.' ], 404);
    }
    return new WP_REST_Response([ 'message' => 'Deleted', 'id' => $id ], 200);
}
