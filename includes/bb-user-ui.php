<?php
// Handle form submission
if (isset($_POST['bb_form_submitted']) && is_user_logged_in()) {
    bb_add_transaction($_POST['type'], $_POST['amount'], $_POST['description'], $_POST['date']);
    echo "<div class='updated'><p>Transaction added!</p></div>";
}

// Get user balance
$balance = bb_get_user_balance();
$balance_class = $balance >= 0 ? 'positive' : 'negative';
?>

<!-- Modal Overlay and Popup -->
<div id="bb_modal_overlay" class="bb_modal_overlay">
    <div class="bb_modal">
        <span class="bb_close">&times;</span>
        <h4 class="bb_heading">Add Income or Expense</h4>
        <form method="post">
            <input type="hidden" name="bb_form_submitted" value="1" />
            <label>Type:</label>
            <select name="type">
                <option value="expense">Expense</option>
                <option value="loan">Loan</option>
                <option value="income">Income</option>
            </select>

            <label>Amount:</label>
            <input type="number" step="0.01" name="amount" required />

            <label>Description:</label>
            <input type="text" name="description" />

            <label>Date:</label>
            <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>" />

            <input type="submit" value="Add Transaction" class="button button-primary" />
        </form>
    </div>
</div>

<section class="bb_container">
    <div class="bb_header">
        <h1>BudgetBuddy</h1>
        <div class="bb_balance_summary">
            <span>Current Balance: </span>
            <span class="bb_balance <?php echo $balance_class; ?>">
                <?php echo number_format($balance, 2); ?>
            </span>
        </div>
        <button id="bb_add_action" class="bb_btn">
            Add
        </button>
    </div>
    <div>
        <h3>Monthly Transaction History</h3>
        <table class="bb_table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $transactions_by_month = bb_get_transactions_by_month();
                
                if (empty($transactions_by_month)) {
                    echo '<tr><td colspan="2" class="bb_no_data">No transactions found. Add your first transaction!</td></tr>';
                } else {
                    foreach ($transactions_by_month as $month => $transactions):
                    ?>
                        <tr>
                            <td colspan="2" class="bb_month_header">
                                <?php echo date("F Y", strtotime($month)); ?>
                            </td>
                        </tr>
                        <?php foreach ($transactions as $index => $tx):
                            $row_class = $tx->type;
                            $row_id = "desc_row_{$month}_{$index}";
                        ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td class="bb_table_td"><?php echo esc_html(date('d', strtotime($tx->date))); ?></td>
                                <td class="bb_table_td bb_table_data bb_expand_action" data-target="<?php echo esc_attr($row_id); ?>"> 
                                    <span>
                                        <?php echo esc_html(number_format($tx->amount, 2)); ?>
                                    </span>
                                    <button class="bb_expand_btn">Expand</button>
                                </td>
                            </tr>
                            <tr id="<?php echo esc_attr($row_id); ?>" class="bb_description_row" style="display: none;">
                                <td colspan="2" class="bb_table_td">
                                    <strong>Type:</strong> <?php echo ucfirst(esc_html($tx->type)); ?><br>
                                    <strong>Description:</strong> <?php echo esc_html($tx->description); ?><br>
                                    <strong>Date:</strong> <?php echo esc_html(date('F j, Y', strtotime($tx->date))); ?><br><br>
                                    <!-- Delete form -->
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
                                        <input type="hidden" name="bb_delete_transaction" value="1" />
                                        <input type="hidden" name="transaction_id" value="<?php echo esc_attr($tx->id); ?>" />
                                        <button type="submit" class="bb_delete_btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach;
                }
                ?>
            </tbody>
        </table>
    </div>
</section>