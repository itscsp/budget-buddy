<?php
/**
 * Budget Header Template
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$plugin_url = plugin_dir_url(dirname(__FILE__));
?>

<div class="bb-header">
    <h1 class="bb-header__title" style="display:inline-flex;align-items:center;gap:10px;">
        Budget
        <span id="bb_show_budget_image" title="View Budget 2025-26" style="cursor:pointer;display:inline-flex;align-items:center;">
            <img src="<?php echo $plugin_url . 'assets/img/budget.png'; ?>" alt="View Budget 2025-26" style="height:22px;width:22px;vertical-align:middle;" />
        </span>
    </h1>
    <button id="bb_add_action" class="bb-btn bb-btn--add">Add</button>
</div>

<!-- Budget Image Modal -->
<div id="bb-budget-image-modal" class="bb-modal__overlay" style="display:none;align-items:center;justify-content:center;z-index:9999;background:rgba(0,0,0,0.7);position:fixed;top:0;left:0;width:100vw;height:100vh;">
    <div style="position:relative;background:transparent;padding:0;border-radius:8px;max-width:90vw;max-height:90vh;box-shadow:none;">
        <button id="bb-close-budget-image" style="position:absolute;top:5px;right:5px;font-size:22px;background:none;border:none;cursor:pointer;color:#fff;">&times;</button>
        <img src="<?php echo get_site_url(); ?>/wp-content/uploads/2025/05/budget-2025-26.png" alt="Budget 2025-26" style="max-width:80vw;max-height:80vh;display:block;margin:0 auto;box-shadow:0 2px 16px rgba(0,0,0,0.3);border-radius:8px;" />
        </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var openBtn = document.getElementById('bb_show_budget_image');
    var modal = document.getElementById('bb-budget-image-modal');
    var closeBtn = document.getElementById('bb-close-budget-image');
    if (openBtn && modal && closeBtn) {
        openBtn.addEventListener('click', function() {
            modal.style.display = 'flex';
        });
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
});
</script>