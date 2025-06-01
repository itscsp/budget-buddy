<?php
/**
 * Plan Item Template
 * Args: plan
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$plan = $plan_args['plan']; // for plan item
$tx = $tx_args['tx'];       // for transaction item
?>

<li class="bb-plan-item">
    <div class="bb-plan-content">
        <span><?php echo $plan->status === 'done' ? '
            <svg stroke="currentColor" fill="green" stroke-width="0" viewBox="0 0 512 512" height="32px" width="32px" xmlns="http://www.w3.org/2000/svg"><path d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48zm48.19 121.42 24.1 21.06-73.61 84.1-24.1-23.06zM191.93 342.63 121.37 272 144 249.37 214.57 320zm65 .79L185.55 272l22.64-22.62 47.16 47.21 111.13-127.17 24.1 21.06z"></path></svg>
        ' : ''; ?></span>
        <span>₹<?php echo esc_html(number_format($plan->amount ?: 0, 2)); ?>:
            <?php echo esc_html($plan->plan_text); ?>
        </span>
    </div>

    <?php if ($plan->status !== 'done'): ?>
        <form class="bb-plan-status-form">
            <input type="hidden" name="plan_id" value="<?php echo esc_attr($plan->id); ?>" />
            <input type="hidden" name="status" value="<?php echo $plan->status === 'pending' ? 'done' : 'pending'; ?>" />
            <button type="submit" class="bb_btn" title="Mark as Done">
                <svg stroke="currentColor" fill="green" stroke-width="0" viewBox="0 0 512 512" height="32px" width="32px" xmlns="http://www.w3.org/2000/svg">
                    <path d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48zm48.19 121.42 24.1 21.06-73.61 84.1-24.1-23.06zM191.93 342.63 121.37 272 144 249.37 214.57 320zm65 .79L185.55 272l22.64-22.62 47.16 47.21 111.13-127.17 24.1 21.06z"></path>
                </svg>
            </button>
        </form>
    <?php endif; ?>

    <form class="bb-plan-delete-form">
        <input type="hidden" name="bb_delete_plan" value="1" />
        <input type="hidden" name="plan_id" value="<?php echo esc_attr($plan->id); ?>" />
        <button type="submit">
            <svg stroke="currentColor" fill="red" stroke-width="0" viewBox="0 0 512 512" height="32px" width="32px" xmlns="http://www.w3.org/2000/svg">
                <path d="M128 405.429C128 428.846 147.198 448 170.667 448h170.667C364.802 448 384 428.846 384 405.429V160H128v245.429zM416 96h-80l-26.785-32H202.786L176 96H96v32h320V96z"></path>
            </svg>
        </button>
    </form>
</li>