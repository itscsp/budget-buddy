<?php
/**
 * Month Template
 * Args: month, transactions, plans, first_day_of_month
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$month = $args['month'];
$transactions = $args['transactions'];
$plans = $args['plans'];
$first_day_of_month = $args['first_day_of_month'];
?>

<div class="bb-month">
    <div class="bb-month__header">
        <p class="bb-month__title"><?php echo date("F Y", strtotime($month)); ?></p>
        <button class="bb-month__plan-btn">Plan</button>
        <button class="bb-month__report-btn" data-month="<?php echo esc_attr($month); ?>">Report</button>
    </div>

    <div class="bb-month__plan" style="display: none;">
        <div class="bb-month__plan-label">
            <span>Plan Your Month</span>
            <button class="bb-toggle-plan" data-month="<?php echo esc_attr($first_day_of_month); ?>">Add Plan</button>
        </div>

        <?php foreach ($plans as $plan): ?>
            <?php
            $plan_args = array('plan' => $plan);
            include plugin_dir_path(__FILE__) . 'budget-plan-item.php';
            ?>
        <?php endforeach; ?>

        <div class="bb-month__plan-summary">
            <strong>Plan Budget:</strong>
            ₹<?php
                $monthly_plan_total = bb_get_monthly_plan_total(date('Y-m', strtotime($month)));
                echo number_format($monthly_plan_total ?: 0, 2);
            ?>
        </div>
        <hr>
    </div>

    <?php foreach ($transactions as $index => $tx): ?>
        <?php
        $tx_args = array('tx' => $tx);
        include plugin_dir_path(__FILE__) . 'budget-transaction-item.php';
        ?>
    <?php endforeach; ?>
</div>