function generateReportHTML(data, container) {
    const incomeFormatted = new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency: "INR",
        maximumFractionDigits: 2
    }).format(data.income);

    const expenseFormatted = new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency: "INR",
        maximumFractionDigits: 2
    }).format(data.expense);

    const loanFormatted = new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency: "INR",
        maximumFractionDigits: 2
    }).format(data.loan);

    const netFormatted = new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency: "INR",
        maximumFractionDigits: 2
    }).format(data.net);

    // Format category spends
    const spent50 = new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR", maximumFractionDigits: 2 }).format(data.spent_50_percent || 0);
    const spent25 = new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR", maximumFractionDigits: 2 }).format(data.spent_25_percent || 0);
    const spent15 = new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR", maximumFractionDigits: 2 }).format(data.spent_15_percent || 0);
    const spent10 = new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR", maximumFractionDigits: 2 }).format(data.spent_10_percent || 0);

    const netClass = data.net >= 0 ? "positive" : "negative";

    const html = `
        <div class="bb-report-header">
            <div class="bb-report-title">Financial Report: ${data.month_name}</div>
            <div class="bb-report-subtitle">Summary of your financial activity</div>
        </div>
        
        <div class="bb-report-summary">
            <div class="bb-report-card income">
                <div class="bb-report-label">Income</div>
                <div class="bb-report-value positive">${incomeFormatted}</div>
            </div>
            
            <div class="bb-report-card expense">
                <div class="bb-report-label">Expenses</div>
                <div class="bb-report-value negative">${expenseFormatted}</div>
            </div>
            
            <div class="bb-report-card loan">
                <div class="bb-report-label">Loans</div>
                <div class="bb-report-value">${loanFormatted}</div>
            </div>
            
            <div class="bb-report-card net">
                <div class="bb-report-label">Net</div>
                <div class="bb-report-value ${netClass}">${netFormatted}</div>
            </div>
        </div>
        <div class="bb-report-simple-text" style="margin-top: 10px;">
            <p><strong style="color:#1e73be">Spent (50%):</strong> <span style="color:#1e73be">${spent50}</span></p>
            <p><strong style="color:#27ae60">Spent (25%):</strong> <span style="color:#27ae60">${spent25}</span></p>
            <p><strong style="color:#e67e22">Spent (15%):</strong> <span style="color:#e67e22">${spent15}</span></p>
            <p><strong style="color:#c0392b">Spent (10%):</strong> <span style="color:#c0392b">${spent10}</span></p>
        </div>
        <div class="bb-report-details">
            <h4>Transaction Summary</h4>
            <p>Total transactions: ${data.transaction_count}</p>
            <p>Monthly budget utilization: ${
                data.expense > 0 && data.income > 0
                ? Math.round((data.expense / data.income) * 100) + "%"
                : "N/A"
            }</p>
        </div>
    `;

    container.html(html);
}