<?php
/**
 * Budget Buddy REST API
 * Provides authenticated REST endpoints for budget data and transactions
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class BudgetBuddy_REST_API {
    
    private $namespace = 'budget-buddy/v1';
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register all REST API routes
     */
    public function register_routes() {
        // Budget data endpoint
        register_rest_route($this->namespace, '/budget', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_budget_data'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'months' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 6,
                    'description' => 'Number of months to retrieve (default: 6)',
                    'minimum' => 1,
                    'maximum' => 24
                )
            )
        ));
        
        // Get transactions by month
        register_rest_route($this->namespace, '/transactions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_transactions'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'month' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Month in Y-m format (e.g., 2025-07)',
                    'pattern' => '^\d{4}-\d{2}$'
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 50,
                    'minimum' => 1,
                    'maximum' => 200
                ),
                'offset' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 0,
                    'minimum' => 0
                )
            )
        ));
        
        // Add transaction
        register_rest_route($this->namespace, '/transactions', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_transaction'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('income', 'expense', 'loan'),
                    'description' => 'Transaction type'
                ),
                'amount' => array(
                    'required' => true,
                    'type' => 'number',
                    'minimum' => 0.01,
                    'description' => 'Transaction amount'
                ),
                'description' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Transaction description'
                ),
                'date' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Transaction date in Y-m-d format'
                ),
                'category_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'Budget category ID'
                )
            )
        ));
        
        // Update transaction
        register_rest_route($this->namespace, '/transactions/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_transaction'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'type' => array(
                    'required' => false,
                    'type' => 'string',
                    'enum' => array('income', 'expense', 'loan'),
                    'description' => 'Transaction type'
                ),
                'amount' => array(
                    'required' => false,
                    'type' => 'number',
                    'minimum' => 0.01,
                    'description' => 'Transaction amount'
                ),
                'description' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Transaction description'
                ),
                'date' => array(
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Transaction date in Y-m-d format'
                ),
                'category_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'Budget category ID'
                )
            )
        ));
        
        // Delete transaction
        register_rest_route($this->namespace, '/transactions/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_transaction'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        // Get single transaction
        register_rest_route($this->namespace, '/transactions/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_single_transaction'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        // Plans endpoints
        register_rest_route($this->namespace, '/plans', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_plans'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'month' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Month in Y-m format (e.g., 2025-07)',
                    'pattern' => '^\d{4}-\d{2}$'
                )
            )
        ));
        
        // Add plan
        register_rest_route($this->namespace, '/plans', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_plan'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'plan_text' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Plan description'
                ),
                'amount' => array(
                    'required' => true,
                    'type' => 'number',
                    'minimum' => 0.01,
                    'description' => 'Plan amount'
                ),
                'plan_month' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Plan month date in Y-m-d format'
                ),
                'is_recurring' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Whether the plan repeats monthly'
                )
            )
        ));
        
        // Categories endpoint
        register_rest_route($this->namespace, '/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_categories'),
            'permission_callback' => array($this, 'check_authentication')
        ));
        
        // Reports endpoint (monthly summary)
        register_rest_route($this->namespace, '/reports/(?P<month>[0-9]{4}-[0-9]{2})', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_monthly_report'),
            'permission_callback' => array($this, 'check_authentication')
        ));
    }
    
    /**
     * Check if user is authenticated
     */
    public function check_authentication($request) {
        return true;
    }
    
    /**
     * Get budget data in the requested format
     */
    public function get_budget_data($request) {
        $months_count = $request->get_param('months');
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error('unauthorized', 'User not authenticated', array('status' => 401));
        }
        
        $data = array();
        
        // Generate data for the requested number of months
        for ($i = 0; $i < $months_count; $i++) {
            $month_date = date('Y-m', strtotime("-$i months"));
            $month_key = date('F Y', strtotime($month_date . '-01'));
            
            // Get monthly report data
            $report = $this->get_monthly_summary_data($month_date);
            
            // Get transactions for the month
            $transactions = $this->get_transactions_for_month($month_date);
            
            $data[$month_key] = array(
                'report' => $report,
                'transactions' => $transactions
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $data,
            'user_id' => $user_id,
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Get transactions with optional filtering
     */
    public function get_transactions($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $month = $request->get_param('month');
        $limit = $request->get_param('limit');
        $offset = $request->get_param('offset');
        
        $transactions_table = $wpdb->prefix . 'bb_transactions';
        $categories_table = $wpdb->prefix . 'bb_budget_categories';
        
        $where_clause = "WHERE t.user_id = %d";
        $params = array($user_id);
        
        if ($month) {
            $start_date = date('Y-m-01', strtotime($month));
            $end_date = date('Y-m-t', strtotime($month));
            $where_clause .= " AND t.date BETWEEN %s AND %s";
            $params[] = $start_date;
            $params[] = $end_date;
        }
        
        $query = "
            SELECT t.*, c.category_name, c.percentage 
            FROM $transactions_table t 
            LEFT JOIN $categories_table c ON t.category_id = c.id 
            $where_clause 
            ORDER BY t.date DESC, t.id DESC 
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $results = $wpdb->get_results($wpdb->prepare($query, $params));
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) FROM $transactions_table t $where_clause";
        $total_count = $wpdb->get_var($wpdb->prepare($count_query, array_slice($params, 0, -2)));
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $results,
            'pagination' => array(
                'total' => intval($total_count),
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total_count
            )
        ));
    }
    
    /**
     * Add a new transaction
     */
    public function add_transaction($request) {
        $user_id = get_current_user_id();
        
        $type = $request->get_param('type');
        $amount = $request->get_param('amount');
        $description = $request->get_param('description');
        $date = $request->get_param('date');
        $category_id = $request->get_param('category_id');
        
        // Validate category_id if provided
        if ($category_id) {
            global $wpdb;
            $categories_table = $wpdb->prefix . 'bb_budget_categories';
            $category_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $categories_table WHERE id = %d AND user_id = %d",
                $category_id, $user_id
            ));
            
            if (!$category_exists) {
                return new WP_Error('invalid_category', 'Invalid category ID', array('status' => 400));
            }
        }
        
        $result = bb_add_transaction($type, $amount, $description, $date, $category_id);
        
        if ($result) {
            // Get the newly created transaction
            $transaction_id = $wpdb->insert_id;
            $new_transaction = $this->get_transaction_by_id($transaction_id);
            
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Transaction added successfully',
                'data' => $new_transaction
            ));
        } else {
            return new WP_Error('creation_failed', 'Failed to add transaction', array('status' => 500));
        }
    }
    
    /**
     * Update an existing transaction
     */
    public function update_transaction($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $transaction_id = $request->get_param('id');
        
        // Check if transaction exists and belongs to user
        $transaction = $this->get_transaction_by_id($transaction_id);
        if (!$transaction || $transaction->user_id != $user_id) {
            return new WP_Error('not_found', 'Transaction not found', array('status' => 404));
        }
        
        $update_data = array();
        $update_format = array();
        
        if ($request->has_param('type')) {
            $update_data['type'] = sanitize_text_field($request->get_param('type'));
            $update_format[] = '%s';
        }
        
        if ($request->has_param('amount')) {
            $update_data['amount'] = floatval($request->get_param('amount'));
            $update_format[] = '%f';
        }
        
        if ($request->has_param('description')) {
            $update_data['description'] = sanitize_text_field($request->get_param('description'));
            $update_format[] = '%s';
        }
        
        if ($request->has_param('date')) {
            $update_data['date'] = sanitize_text_field($request->get_param('date'));
            $update_format[] = '%s';
        }
        
        if ($request->has_param('category_id')) {
            $category_id = $request->get_param('category_id');
            if ($category_id) {
                $categories_table = $wpdb->prefix . 'bb_budget_categories';
                $category_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $categories_table WHERE id = %d AND user_id = %d",
                    $category_id, $user_id
                ));
                
                if (!$category_exists) {
                    return new WP_Error('invalid_category', 'Invalid category ID', array('status' => 400));
                }
            }
            $update_data['category_id'] = $category_id ? intval($category_id) : null;
            $update_format[] = '%d';
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', 'No data provided for update', array('status' => 400));
        }
        
        $table = $wpdb->prefix . 'bb_transactions';
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $transaction_id, 'user_id' => $user_id),
            $update_format,
            array('%d', '%d')
        );
        
        if ($result !== false) {
            $updated_transaction = $this->get_transaction_by_id($transaction_id);
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Transaction updated successfully',
                'data' => $updated_transaction
            ));
        } else {
            return new WP_Error('update_failed', 'Failed to update transaction', array('status' => 500));
        }
    }
    
    /**
     * Delete a transaction
     */
    public function delete_transaction($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $transaction_id = $request->get_param('id');
        
        $table = $wpdb->prefix . 'bb_transactions';
        $result = $wpdb->delete(
            $table,
            array('id' => $transaction_id, 'user_id' => $user_id),
            array('%d', '%d')
        );
        
        if ($result) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Transaction deleted successfully'
            ));
        } else {
            return new WP_Error('delete_failed', 'Failed to delete transaction or transaction not found', array('status' => 404));
        }
    }
    
    /**
     * Get a single transaction
     */
    public function get_single_transaction($request) {
        $user_id = get_current_user_id();
        $transaction_id = $request->get_param('id');
        
        $transaction = $this->get_transaction_by_id($transaction_id);
        
        if (!$transaction || $transaction->user_id != $user_id) {
            return new WP_Error('not_found', 'Transaction not found', array('status' => 404));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $transaction
        ));
    }
    
    /**
     * Get plans for a month or all plans
     */
    public function get_plans($request) {
        $user_id = get_current_user_id();
        $month = $request->get_param('month');
        
        if ($month) {
            $plans = bb_get_monthly_plans($month);
        } else {
            global $wpdb;
            $table = $wpdb->prefix . 'bb_monthly_plans';
            $plans = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY plan_month DESC, id DESC",
                $user_id
            ));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $plans
        ));
    }
    
    /**
     * Add a new plan
     */
    public function add_plan($request) {
        $plan_text = $request->get_param('plan_text');
        $amount = $request->get_param('amount');
        $plan_month = $request->get_param('plan_month');
        $is_recurring = $request->get_param('is_recurring') ? 1 : 0;
        
        global $wpdb;
        $table = $wpdb->prefix . 'bb_monthly_plans';
        $user_id = get_current_user_id();
        
        $result = $wpdb->insert($table, array(
            'user_id' => $user_id,
            'plan_text' => sanitize_text_field($plan_text),
            'amount' => floatval($amount),
            'plan_month' => sanitize_text_field($plan_month),
            'status' => 'pending',
            'is_recurring' => $is_recurring
        ), array('%d', '%s', '%f', '%s', '%s', '%d'));
        
        if ($result) {
            $plan_id = $wpdb->insert_id;
            $new_plan = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $plan_id
            ));
            
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Plan added successfully',
                'data' => $new_plan
            ));
        } else {
            return new WP_Error('creation_failed', 'Failed to add plan', array('status' => 500));
        }
    }
    
    /**
     * Get budget categories
     */
    public function get_categories($request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $categories_table = $wpdb->prefix . 'bb_budget_categories';
        
        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $categories_table WHERE user_id = %d ORDER BY percentage DESC",
            $user_id
        ));
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $categories
        ));
    }
    
    /**
     * Get monthly report
     */
    public function get_monthly_report($request) {
        $month = $request->get_param('month');
        
        // Use existing function to get monthly summary
        $summary = bb_get_monthly_summary($month);
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $summary
        ));
    }
    
    /**
     * Helper function to get monthly summary data
     */
    private function get_monthly_summary_data($month) {
        return bb_get_monthly_summary($month);
    }
    
    /**
     * Helper function to get transactions for a specific month
     */
    private function get_transactions_for_month($month) {
        global $wpdb;
        $user_id = get_current_user_id();
        
        $transactions_table = $wpdb->prefix . 'bb_transactions';
        $categories_table = $wpdb->prefix . 'bb_budget_categories';
        
        $start_date = date('Y-m-01', strtotime($month));
        $end_date = date('Y-m-t', strtotime($month));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, c.category_name, c.percentage 
             FROM $transactions_table t 
             LEFT JOIN $categories_table c ON t.category_id = c.id 
             WHERE t.user_id = %d 
             AND t.date BETWEEN %s AND %s 
             ORDER BY t.date DESC, t.id DESC",
            $user_id, $start_date, $end_date
        ));
    }
    
    /**
     * Helper function to get transaction by ID with category info
     */
    private function get_transaction_by_id($transaction_id) {
        global $wpdb;
        
        $transactions_table = $wpdb->prefix . 'bb_transactions';
        $categories_table = $wpdb->prefix . 'bb_budget_categories';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, c.category_name, c.percentage 
             FROM $transactions_table t 
             LEFT JOIN $categories_table c ON t.category_id = c.id 
             WHERE t.id = %d",
            $transaction_id
        ));
    }
}

// Initialize the REST API class
new BudgetBuddy_REST_API();
