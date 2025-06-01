<?php
/**
 * Transaction Modal Template
 * Args: categories
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


$categories = isset($args['categories']) ? $args['categories'] : array();
?>

<div id="bb_transation_modal_overlay" class="bb-transation_modal__overlay bb-modal__overlay">
    <div class="bb-modal">
        <span class="bb-transation_modal__close">
            <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="24px" width="24px" xmlns="http://www.w3.org/2000/svg">
                <path d="M331.3 308.7L278.6 256l52.7-52.7c6.2-6.2 6.2-16.4 0-22.6-6.2-6.2-16.4-6.2-22.6 0L256 233.4l-52.7-52.7c-6.2-6.2-15.6-7.1-22.6 0-7.1 7.1-6 16.6 0 22.6l52.7 52.7-52.7 52.7c-6.7 6.7-6.4 16.3 0 22.6 6.4 6.4 16.4 6.2 22.6 0l52.7-52.7 52.7 52.7c6.2 6.2 16.4 6.2 22.6 0 6.3-6.2 6.3-16.4 0-22.6z"></path>
                <path d="M256 76c48.1 0 93.3 18.7 127.3 52. S436 207.9 436 256s-18.7 93.3-52.7 127.3S304.1 436 256 436c-48.1 0-93.3-18.7-127.3-52.7S76 304.1 76 256s18.7-93.3 52.7-127.3S207.9 76 256 76m0-28C141.1 48 48 141.1 48 256s93.1 208 208 208 208-93.1 208-208S370.9 48 256 48z"></path>
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
                <?php if ($categories) : ?>
                    <?php foreach ($categories as $index => $category) : ?>
                        <div class="budget-class-type">
                            <input
                                type="radio"
                                id="budget-<?php echo esc_attr($category->id); ?>"
                                name="budget-class"
                                value="<?php echo esc_attr($category->id); ?>"
                                <?php checked($index, 0); ?> />
                            <label for="budget-<?php echo esc_attr($category->id); ?>">
                                <?php echo esc_html($category->percentage . '%'); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
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