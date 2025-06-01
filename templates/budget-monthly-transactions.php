<?php
/**
 * Monthly Transactions Template
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$transactions_by_month = bb_get_transactions_by_month();
?>

<div class="bb-history">
    <div class="bb-history__table">
        <div class="bb-history__table-head">
            <h3 class="bb-history__heading">Monthly Transaction History</h3>
        </div>
        <div class="bb-history__body">
            <?php if (empty($transactions_by_month)): ?>
                <div class="bb-no-data">No transactions found. Add your first transaction!</div>
            <?php else: ?>
                <?php foreach ($transactions_by_month as $month => $transactions): ?>
                    <?php
                    $plan_month = date('Y-m', strtotime($month));
                    $plans = bb_get_monthly_plans($plan_month);
                    $first_day_of_month = date('Y-m-01', strtotime($month));
                    ?>
                   <?php
                    $args = array(
                        'month' => $month,
                        'transactions' => $transactions,
                        'plans' => $plans,
                        'first_day_of_month' => $first_day_of_month
                    );
                    include plugin_dir_path(__FILE__) . 'budget-month.php';
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>