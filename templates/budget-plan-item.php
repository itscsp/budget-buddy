<?php
/**
 * Plan Item Template
 * Args: plan
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$plan = $plan_args['plan']; // for plan item
?>

<li class="bb-plan-item">
    <div class="bb-plan-content">
       <span class="status-text <?php echo $plan->status === 'done' ? 'text-green' : 'text-red'?>">
            <?php if ($plan->status === 'done') : ?>
                PAID
            <?php else : ?>
                <form class="bb-plan-status-form">
                    <input type="hidden" name="plan_id" value="<?php echo esc_attr($plan->id); ?>" />
                    <input type="hidden" name="status" value="<?php echo $plan->status === 'pending' ? 'done' : 'pending'; ?>" />
                    <button type="submit" class="status-pending" title="Make it Paid">PENDING</button>
                </form>
            <?php endif; ?>
        </span>
        <hr>
        <span>
            <?php echo esc_html($plan->plan_text); ?>
        </span>
    </div>

    <div class="bb-plan-action">
        <div>
             ₹<?php echo esc_html(number_format($plan->amount ?: 0, 2)); ?>
            </div>
        <hr>
        <div>
             <form class="bb-plan-delete-form">
                <input type="hidden" name="bb_delete_plan" value="1" />
                <input type="hidden" name="plan_id" value="<?php echo esc_attr($plan->id); ?>" />
                <button type="submit">
                    <svg stroke="currentColor" fill="red" stroke-width="0" viewBox="0 0 512 512" height="24px" width="24px" xmlns="http://www.w3.org/2000/svg">
                        <path d="M128 405.429C128 428.846 147.198 448 170.667 448h170.667C364.802 448 384 428.846 384 405.429V160H128v245.429zM416 96h-80l-26.785-32H202.786L176 96H96v32h320V96z"></path>
                    </svg>
                </button>
            </form>
        </div>
       
    </div>

</li>