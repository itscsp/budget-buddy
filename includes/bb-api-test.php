<?php
/**
 * Budget Buddy REST API Test File
 * This file provides simple tests to verify API endpoints
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Simple API tester that doesn't initialize early
 */
function bb_init_api_tester() {
    // Only initialize in admin for administrators
    if (!is_admin() || !current_user_can('administrator')) {
        return;
    }
    
    // Add AJAX handlers
    add_action('wp_ajax_bb_test_api', 'bb_run_api_tests');
    
    // Add admin menu
    add_action('admin_menu', 'bb_add_api_test_menu');
}

/**
 * Run API tests via AJAX
 */
function bb_run_api_tests() {
    if (!current_user_can('administrator')) {
        wp_send_json_error('Unauthorized access');
        return;
    }
    
    $tests = array();
    $namespace = 'budget-buddy/v1';
    
    // Test 1: Check if routes are registered
    $tests['routes_registered'] = bb_test_routes_registration($namespace);
    
    // Test 2: Test budget endpoint
    $tests['budget_endpoint'] = bb_test_budget_endpoint($namespace);
    
    // Test 3: Test transactions endpoint  
    $tests['transactions_endpoint'] = bb_test_transactions_endpoint($namespace);
    
    // Test 4: Test categories endpoint
    $tests['categories_endpoint'] = bb_test_categories_endpoint($namespace);
    
    wp_send_json_success(array(
        'message' => 'API tests completed',
        'results' => $tests,
        'base_url' => get_rest_url(null, $namespace)
    ));
}

/**
 * Test if routes are properly registered
 */
function bb_test_routes_registration($namespace) {
    try {
        $routes = rest_get_server()->get_routes();
        $namespace_routes = array();
        
        foreach ($routes as $route => $data) {
            if (strpos($route, '/' . $namespace) === 0) {
                $namespace_routes[] = $route;
            }
        }
        
        $expected_routes = array(
            '/' . $namespace . '/budget',
            '/' . $namespace . '/transactions',
            '/' . $namespace . '/plans',
            '/' . $namespace . '/categories'
        );
        
        $registered_routes = array();
        foreach ($expected_routes as $expected) {
            $registered_routes[$expected] = in_array($expected, $namespace_routes);
        }
        
        return array(
            'status' => 'success',
            'total_routes' => count($namespace_routes),
            'registered_routes' => $registered_routes,
            'all_namespace_routes' => $namespace_routes
        );
        
    } catch (Exception $e) {
        return array('status' => 'error', 'message' => $e->getMessage());
    }
}

/**
 * Test budget endpoint
 */
function bb_test_budget_endpoint($namespace) {
    try {
        // Use wp_remote_get instead of internal REST request
        $url = get_rest_url(null, $namespace . '/budget?months=1');
        $response = wp_remote_get($url, array(
            'cookies' => $_COOKIE,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return array(
            'status' => 'success',
            'endpoint' => '/budget',
            'method' => 'GET',
            'response_code' => $response_code,
            'has_data' => isset($data['data']),
            'response_structure' => is_array($data)
        );
        
    } catch (Exception $e) {
        return array('status' => 'error', 'message' => $e->getMessage());
    }
}

/**
 * Test transactions endpoint
 */
function bb_test_transactions_endpoint($namespace) {
    try {
        $url = get_rest_url(null, $namespace . '/transactions?limit=5');
        $response = wp_remote_get($url, array(
            'cookies' => $_COOKIE,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return array(
            'status' => 'success',
            'endpoint' => '/transactions',
            'method' => 'GET',
            'response_code' => $response_code,
            'has_pagination' => isset($data['pagination']),
            'response_structure' => is_array($data)
        );
        
    } catch (Exception $e) {
        return array('status' => 'error', 'message' => $e->getMessage());
    }
}

/**
 * Test categories endpoint
 */
function bb_test_categories_endpoint($namespace) {
    try {
        $url = get_rest_url(null, $namespace . '/categories');
        $response = wp_remote_get($url, array(
            'cookies' => $_COOKIE,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return array(
            'status' => 'success',
            'endpoint' => '/categories',
            'method' => 'GET',
            'response_code' => $response_code,
            'has_categories' => isset($data['data']) && is_array($data['data']),
            'response_structure' => is_array($data)
        );
        
    } catch (Exception $e) {
        return array('status' => 'error', 'message' => $e->getMessage());
    }
}

/**
 * Add admin menu for API testing
 */
function bb_add_api_test_menu() {
    if (current_user_can('administrator')) {
        add_submenu_page(
            'budget_buddy',
            'API Test',
            'API Test',
            'manage_options',
            'budget-buddy-api-test',
            'bb_api_test_page'
        );
    }
}

/**
 * API test page
 */
function bb_api_test_page() {
    ?>
    <div class="wrap">
        <h1>Budget Buddy API Test</h1>
        <p>Test the REST API endpoints to ensure they're working correctly.</p>
        
        <div class="notice notice-info">
            <p><strong>API Base URL:</strong> <code><?php echo esc_html(get_rest_url(null, 'budget-buddy/v1')); ?></code></p>
        </div>
        
        <button id="run-api-tests" class="button button-primary">Run API Tests</button>
        
        <div id="test-results" style="margin-top: 20px; display: none;">
            <h2>Test Results</h2>
            <div id="results-content"></div>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#run-api-tests').on('click', function() {
            var button = $(this);
            button.prop('disabled', true).text('Running tests...');
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'bb_test_api'
                },
                success: function(response) {
                    $('#test-results').show();
                    if (response.success) {
                        var html = '<div class="notice notice-success"><p>Tests completed successfully!</p></div>';
                        html += '<h3>Base URL: ' + response.data.base_url + '</h3>';
                        html += '<h3>Test Results:</h3>';
                        
                        $.each(response.data.results, function(testName, result) {
                            var statusClass = result.status === 'success' ? 'notice-success' : 'notice-error';
                            html += '<div class="notice ' + statusClass + '">';
                            html += '<h4>' + testName.replace(/_/g, ' ').toUpperCase() + '</h4>';
                            html += '<pre>' + JSON.stringify(result, null, 2) + '</pre>';
                            html += '</div>';
                        });
                        
                        $('#results-content').html(html);
                    } else {
                        $('#results-content').html('<div class="notice notice-error"><p>Tests failed: ' + response.data + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#results-content').html('<div class="notice notice-error"><p>AJAX Error: ' + error + '</p></div>');
                    $('#test-results').show();
                },
                complete: function() {
                    button.prop('disabled', false).text('Run API Tests');
                }
            });
        });
    });
    </script>
    <?php
}

// Initialize when WordPress is ready
add_action('wp_loaded', 'bb_init_api_tester');
