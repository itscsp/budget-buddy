<?php
/**
 * BudgetBuddy Page Template
 * Handles the rendering of the BudgetBuddy frontend interface
 */

defined('ABSPATH') || exit;

class BudgetBuddyPage {
    private $wpdb;
    private $user_id;
    private $categories_table;
    private $plugin_path;
    private $functions;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->user_id = get_current_user_id();
        $this->categories_table = $wpdb->prefix . 'bb_budget_categories';
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->functions = new BudgetBuddyFunctions();
    }

    public function render() {
        // Ensure user is logged in
        if (!is_user_logged_in()) {
            return;
        }

        // Handle form submissions
        $this->handle_form_submissions();

        // Prepare data
        $balance = $this->get_user_balance();
        $balance_class = $balance >= 0 ? 'positive' : 'negative';
        $categories = $this->get_user_categories();
        $transactions_by_month = $this->get_transactions_by_month();
        $flash_message = $this->get_flash_message();

        // Start output buffering
        ob_start();
        ?>
        <!-- Display transient message (if exists) -->
        <?php if ($flash_message): ?>
            <div class="updated bb-alert"><p><?php echo esc_html($flash_message); ?></p></div>
        <?php endif; ?>

        <!-- Modal Overlay and Popup for Transactions -->
        <div id="bb_transation_modal_overlay" class="bb-transation_modal__overlay bb-modal__overlay">
            <div class="bb-modal">
                <span class="bb-transation_modal__close">
                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="24px" width="24px" xmlns="http://www.w3.org/2000/svg">
                        <path d="M331.3 308.7L278.6 256l52.7-52.7c6.2-6.2 6.2-16.4 0-22.6-6.2-6.2-16.4-6.2-22.6 0L256 233.4l-52.7-52.7c-6.2-6.2-15.6-7.1-22.6 0-7.1 7.1-6 16.6 0 22.6l52.7 52.7-52.7 52.7c-6.7 6.7-6.4 16.3 0 22.6 6.4 6.4 16.4 6.2 22.6 0l52.7-52.7 52.7 52.7c6.2 6.2 16.4 6.2 22.6 0 6.3-6.2 6.3-16.4 0-22.6z"></path>
                        <path d="M256 76c48.1 0 93.3 18.7 127.3 52.7S436 207.9 436 256s-18.7 93.3-52.7 127.3S304.1 436 256 436c-48.1 0-93.3-18.7-127.3-52.7S76 304.1 76 256s18.7-93.3 52.7-127.3S207.9 76 256 76m0-28C141.1 48 48 141.1 48 256s93.1 208 208 208 208-93.1 208-208S370.9 48 256 48z"></path>
                    </svg>
                </span>
                <h4 class="bb-modal__heading">Add Income or Expense</h4>
                <form class="bb-form" id="bb-add-transaction-form">
                    <input type="hidden" name="bb_form_submitted" value="1" />

                    <label for="bb-type">Type:</label>
                    <select id="bb-type" name="type" class="bb-form__input">
                        <option value="expense">Expense</option>
                        <option value="loan">Loan</option>
                        <option value="income">Income</option>
                    </select>
                    <fieldset class="budget-class">
                        <legend>Choose budget group:</legend>
                        <?php if ($categories): ?>
                            <?php foreach ($categories as $index => $category): ?>
                                <div class="budget-class-type">
                                    <input 
                                        type="radio" 
                                        id="budget-<?php echo esc_attr($category->id); ?>" 
                                        name="budget-class" 
                                        value="<?php echo esc_attr($category->id); ?>" 
                                        <?php checked($index, 0); ?>
                                    />
                                    <label for="budget-<?php echo esc_attr($category->id); ?>">
                                        <?php echo esc_html($category->percentage . '%'); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="budget-class-type">
                                <p>No budget categories found. Please <a href="<?php echo admin_url('admin.php?page=budget_buddy'); ?>">add categories</a> in the admin panel.</p>
                            </div>
                        <?php endif; ?>
                    </fieldset>

                    <label for="bb-amount">Amount:</label>
                    <input id="bb-amount" type="number" step="0.01" name="amount" required class="bb-form__input" />

                    <label for="bb-description">Description:</label>
                    <input id="bb-description" type="text" name="description" class="bb-form__input" />

                    <label for="bb-date">Date:</label>
                    <input id="bb-date" type="date" name="date" required value="<?php echo date('Y-m-d'); ?>" class="bb-form__input" />

                    <input type="submit" value="Add Transaction" class="button button-primary bb-form__submit" />
                </form>
            </div>
        </div>

        <!-- Plan Modal -->
        <div id="bb_plan_modal_overlay" class="bb-plan_modal__overlay bb-modal__overlay" style="display: none;">
            <div class="bb-modal">
                <span class="bb-plan_modal__close">
                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="24px" width="24px" xmlns="http://www.w3.org/2000/svg">
                        <path d="M331.3 308.7L278.6 256l52.7-52.7c6.2-6.2 6.2-16.4 0-22.6-6.2-6.2-16.4-6.2-22.6 0L256 233.4l-52.7-52.7c-6.2-6.2-15.6-7.1-22.6 0-7.1 7.1-6 16.6 0 22.6l52.7 52.7-52.7 52.7c-6.7 6.7-6.4 16.3 0 22.6 6.4 6.4 16.4 6.2 22.6 0l52.7-52.7 52.7 52.7c6.2 6.2 16.4 6.2 22.6 0 6.3-6.2 6.3-16.4 0-22.6z"></path>
                        <path d="M256 76c48.1 0 93.3 18.7 127.3 52.7S436 207.9 436 256s-18.7 93.3-52.7 127.3S304.1 436 256 436c-48.1 0-93.3-18.7-127.3-52.7S76 304.1 76 256s18.7-93.3 52.7-127.3S207.9 76 256 76m0-28C141.1 48 48 141.1 48 256s93.1 208 208 208 208-93.1 208-208S370.9 48 256 48z"></path>
                    </svg>
                </span>
                <h4 class="bb-modal__heading">Add Monthly Plan</h4>
                <form class="bb-form" id="bb-add-plan-form">
                    <input type="hidden" name="plan_month" id="plan_month" value="" />

                    <label for="plan_amount">Planned Amount:</label>
                    <input id="plan_amount" type="number" step="0.01" name="amount" required class="bb-form__input" />

                    <label for="plan_text">Plan Text:</label>
                    <input id="plan_text" type="text" name="plan_text" required class="bb-form__input" />

                    <input type="submit" value="Save Plan" class="button button-primary bb-form__submit" />
                </form>
            </div>
        </div>

        <!-- Report Modal -->
        <div id="bb_report_modal_overlay" class="bb-report_modal__overlay bb-modal__overlay">
            <div class="bb-modal">
                <span class="bb-report_modal__close">
                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="32px" width="32px" xmlns="http://www.w3.org/2000/svg">
                        <path d="M331.3 308.7L278.6 256l52.7-52.7c6.2-6.2 6.2-16.4 0-22.6-6.2-6.2-16.4-6.2-22.6 0L256 233.4l-52.7-52.7c-6.2-6.2-15.6-7.1-22.6 0-7.1 7.1-6 16.6 0 22.6l52.7 52.7-52.7 52.7c-6.7 6.7-6.4 16.3 0 22.6 6.4 6.4 16.4 6.2 22.6 0l52.7-52.7 52.7 52.7c6.2 6.2 16.4 6.2 22.6 0 6.3-6.2 6.3-16.4 0-22.6z"></path>
                        <path d="M256 76c48.1 0 93.3 18.7 127.3 52.7S436 207.9 436 256s-18.7 93.3-52.7 127.3S304.1 436 256 436c-48.1 0-93.3-18.7-127.3-52.7S76 304.1 76 256s18.7-93.3 52.7-127.3S207.9 76 256 76m0-28C141.1 48 48 141.1 48 256s93.1 208 208 208 208-93.1 208-208S370.9 48 256 48z"></path>
                    </svg>
                </span>
                <h4 class="bb-modal__heading">Monthly Financial Report</h4>
                <div id="bb-report-content" class="bb-report-content">
                    <div class="bb-report-loading">Loading report data...</div>
                </div>
                <div class="bb-report-actions">
                    <button id="bb-print-report" class="bb-btn">Print Report</button>
                </div>
            </div>
        </div>

        <section class="bb-container">
            <div class="bb-header">
                <h1 class="bb-header__title">Budget</h1>
                <button id="bb_add_action" class="bb-btn bb-btn--add">Add</button>
            </div>

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
                                <div class="bb-month">
                                    <div class="bb-month__header">
                                        <p class="bb-month__title"><?php echo date("F Y", strtotime($month)); ?></p>
                                        <button class="bb-month__plan-btn">Plan</button>
                                        <button class="bb-month__report-btn" data-month="<?php echo $month; ?>">Report</button>
                                    </div>
                                    <?php
                                    $plan_month = date('Y-m', strtotime($month));
                                    $plans = $this->get_monthly_plans($plan_month);
                                    $first_day_of_month = date('Y-m-01', strtotime($month));
                                    ?>

                                    <div class="bb-month__plan" style="display: none;">
                                        <div class="bb-month__plan-label">
                                            <span>Plan Your Month</span>
                                            <button class="bb-toggle-plan" data-month="<?php echo $first_day_of_month; ?>">Add Plan</button>
                                        </div>

                                        <?php foreach ($plans as $plan): ?>
                                            <li class="bb-plan-item">
                                                <div class="bb-plan-content">
                                                    <span><?php echo $plan->status === 'done' ? '
                                                    <svg stroke="currentColor" fill="green" stroke-width="0" viewBox="0 0 512 512" height="32px" width="32px"  xmlns="http://www.w3.org/2000/svg"><path d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48zm48.19 121.42 24.1 21.06-73.61 84.1-24.1-23.06zM191.93 342.63 121.37 272 144 249.37 214.57 320zm65 .79L185.55 272l22.64-22.62 47.16 47.21 111.13-127.17 24.1 21.06z"></path></svg>
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

                                                <!-- Delete form -->
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
                                        <?php endforeach; ?>

                                        <div class="bb-month__plan-summary">
                                            <strong>Plan Budget:</strong>
                                            ₹<?php
                                                $monthly_plan_total = $this->get_monthly_plan_total($plan_month);
                                                echo number_format($monthly_plan_total ?: 0, 2);
                                            ?>
                                        </div>

                                        <hr>
                                    </div>

                                    <?php foreach ($transactions as $index => $tx): ?>
                                        <div class="bb-transaction bb-transaction--<?php echo esc_attr($tx->type); ?>">
                                            <div class="bb-transaction__main">
                                                <date class="bb-transaction__date"><?php echo esc_html(date('d', strtotime($tx->date))); ?></date>
                                                <div class="bb-transaction__amount-wrapper">
                                                    <p class="bb-transaction__amount"><?php echo esc_attr($tx->type === 'income' ? '+' : '-'); ?> ₹<?php echo esc_html(number_format($tx->amount, 2)); ?></p>
                                                    <button class="bb-expand-btn"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="32px" width="32px" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M256 294.1L383 167c9.4-9.4 24.6-9.4 33.9 0s9.3 24.6 0 34L273 345c-9.1 9.1-23.7 9.3-33.1.7L95 201.1c-4.7-4.7-7-10.9-7-17s2.3-12.3 7-17c9.4-9.4 24.6-9.4 33.9 0l127.1 127z"></path>
                                                    </svg></button>
                                                </div>
                                            </div>

                                            <div id="bb-transaction-details-<?php echo esc_attr($tx->id); ?>" class="bb-transaction__details" style="display: none;">
                                                <strong>Type:</strong> <?php echo ucfirst(esc_html($tx->type)); ?><br>
                                                <strong>Description:</strong> <?php echo esc_html($tx->description); ?><br>
                                                <strong>Category:</strong> <?php echo esc_html($tx->category_name ?: 'Uncategorized'); ?><br>
                                                <strong>Date:</strong> <?php echo esc_html(date('F j, Y', strtotime($tx->date))); ?><br><br>

                                                <!-- Delete form -->
                                                <form class="bb-delete-form">
                                                    <input type="hidden" name="transaction_id" value="<?php echo esc_attr($tx->id); ?>" />
                                                    <button type="button" class="bb-delete-btn delete-transaction-btn" data-id="<?php echo esc_attr($tx->id); ?>">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    private function handle_form_submissions() {
        if (is_user_logged_in()) {
            if (isset($_POST['bb_update_plan_status'])) {
                $this->functions->update_plan_status($_POST['plan_id'], $_POST['new_status']);
                $this->set_flash_message('Plan updated!');
                wp_redirect($_SERVER['REQUEST_URI']);
                exit;
            }

            if (isset($_POST['bb_delete_plan'])) {
                $this->functions->ajax_delete_plan($_POST['plan_id']);
                $this->set_flash_message('Plan deleted!');
                wp_redirect($_SERVER['REQUEST_URI']);
                exit;
            }
        }
    }

    private function get_flash_message() {
        $flash_message = get_transient('bb_flash_message_' . $this->user_id);
        if ($flash_message) {
            delete_transient('bb_flash_message_' . $this->user_id);
        }
        return $flash_message;
    }

    private function set_flash_message($message) {
        set_transient('bb_flash_message_' . $this->user_id, $message, 30);
    }

    private function get_user_balance() {
        return $this->functions->get_user_balance();
    }

    private function get_user_categories() {
        return $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM $this->categories_table WHERE user_id = %d", $this->user_id)
        );
    }

    private function get_transactions_by_month() {
        return $this->functions->get_transactions_by_month();
    }

    private function get_monthly_plans($plan_month) {
        return $this->functions->get_monthly_plans($plan_month);
    }

    private function get_monthly_plan_total($plan_month) {
        return $this->functions->get_monthly_plan_total($plan_month);
    }
}
?>