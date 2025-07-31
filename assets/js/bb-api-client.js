/**
 * Budget Buddy API Client Example
 * This JavaScript client demonstrates how to interact with the Budget Buddy REST API
 */

class BudgetBuddyAPIClient {
    constructor(baseUrl) {
        this.baseUrl = baseUrl.replace(/\/$/, '') + '/wp-json/budget-buddy/v1';
        this.headers = {
            'Content-Type': 'application/json',
            'X-WP-Nonce': this.getNonce() // For WordPress nonce verification
        };
    }

    /**
     * Get WordPress nonce if available
     */
    getNonce() {
        // Try to get nonce from WordPress
        if (typeof wpApiSettings !== 'undefined' && wpApiSettings.nonce) {
            return wpApiSettings.nonce;
        }
        // Try to get from meta tag
        const nonce = document.querySelector('meta[name="wp-nonce"]');
        return nonce ? nonce.getAttribute('content') : '';
    }

    /**
     * Make API request
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const config = {
            headers: this.headers,
            credentials: 'include', // Include cookies for authentication
            ...options
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('API Request failed:', error);
            throw error;
        }
    }

    /**
     * Get budget data for multiple months
     */
    async getBudgetData(months = 6) {
        return this.request(`/budget?months=${months}`);
    }

    /**
     * Get transactions with optional filtering
     */
    async getTransactions(filters = {}) {
        const params = new URLSearchParams();
        
        if (filters.month) params.append('month', filters.month);
        if (filters.limit) params.append('limit', filters.limit);
        if (filters.offset) params.append('offset', filters.offset);

        const queryString = params.toString();
        return this.request(`/transactions${queryString ? '?' + queryString : ''}`);
    }

    /**
     * Add a new transaction
     */
    async addTransaction(transactionData) {
        return this.request('/transactions', {
            method: 'POST',
            body: JSON.stringify(transactionData)
        });
    }

    /**
     * Update an existing transaction
     */
    async updateTransaction(id, updateData) {
        return this.request(`/transactions/${id}`, {
            method: 'PUT',
            body: JSON.stringify(updateData)
        });
    }

    /**
     * Delete a transaction
     */
    async deleteTransaction(id) {
        return this.request(`/transactions/${id}`, {
            method: 'DELETE'
        });
    }

    /**
     * Get a single transaction
     */
    async getTransaction(id) {
        return this.request(`/transactions/${id}`);
    }

    /**
     * Get plans for a specific month or all plans
     */
    async getPlans(month = null) {
        const query = month ? `?month=${month}` : '';
        return this.request(`/plans${query}`);
    }

    /**
     * Add a new plan
     */
    async addPlan(planData) {
        return this.request('/plans', {
            method: 'POST',
            body: JSON.stringify(planData)
        });
    }

    /**
     * Get budget categories
     */
    async getCategories() {
        return this.request('/categories');
    }

    /**
     * Get monthly report
     */
    async getMonthlyReport(month) {
        return this.request(`/reports/${month}`);
    }
}

// Usage Examples
class BudgetBuddyApp {
    constructor() {
        this.api = new BudgetBuddyAPIClient(window.location.origin);
        this.init();
    }

    async init() {
        try {
            await this.loadDashboard();
        } catch (error) {
            this.handleError(error);
        }
    }

    /**
     * Load dashboard with budget data
     */
    async loadDashboard() {
        console.log('Loading dashboard...');
        
        // Get budget data for last 6 months
        const budgetData = await this.api.getBudgetData(6);
        console.log('Budget Data:', budgetData);

        // Get categories for dropdown
        const categories = await this.api.getCategories();
        console.log('Categories:', categories);

        // Get recent transactions
        const recentTransactions = await this.api.getTransactions({ limit: 10 });
        console.log('Recent Transactions:', recentTransactions);

        this.renderDashboard(budgetData, categories, recentTransactions);
    }

    /**
     * Add a new income transaction
     */
    async addIncome(amount, description, date) {
        try {
            const result = await this.api.addTransaction({
                type: 'income',
                amount: parseFloat(amount),
                description: description,
                date: date
            });

            console.log('Income added:', result);
            this.showSuccess('Income added successfully!');
            await this.refreshData();
        } catch (error) {
            this.handleError(error);
        }
    }

    /**
     * Add a new expense transaction
     */
    async addExpense(amount, description, date, categoryId = null) {
        try {
            const transactionData = {
                type: 'expense',
                amount: parseFloat(amount),
                description: description,
                date: date
            };

            if (categoryId) {
                transactionData.category_id = parseInt(categoryId);
            }

            const result = await this.api.addTransaction(transactionData);
            console.log('Expense added:', result);
            this.showSuccess('Expense added successfully!');
            await this.refreshData();
        } catch (error) {
            this.handleError(error);
        }
    }

    /**
     * Update an existing transaction
     */
    async updateTransaction(id, updateData) {
        try {
            const result = await this.api.updateTransaction(id, updateData);
            console.log('Transaction updated:', result);
            this.showSuccess('Transaction updated successfully!');
            await this.refreshData();
        } catch (error) {
            this.handleError(error);
        }
    }

    /**
     * Delete a transaction
     */
    async deleteTransaction(id) {
        if (!confirm('Are you sure you want to delete this transaction?')) {
            return;
        }

        try {
            const result = await this.api.deleteTransaction(id);
            console.log('Transaction deleted:', result);
            this.showSuccess('Transaction deleted successfully!');
            await this.refreshData();
        } catch (error) {
            this.handleError(error);
        }
    }

    /**
     * Get monthly report
     */
    async showMonthlyReport(month) {
        try {
            const report = await this.api.getMonthlyReport(month);
            console.log('Monthly Report:', report);
            this.renderReport(report);
        } catch (error) {
            this.handleError(error);
        }
    }

    /**
     * Add a monthly plan
     */
    async addPlan(planText, amount, month, isRecurring = false) {
        try {
            const result = await this.api.addPlan({
                plan_text: planText,
                amount: parseFloat(amount),
                plan_month: month,
                is_recurring: isRecurring
            });

            console.log('Plan added:', result);
            this.showSuccess('Plan added successfully!');
            await this.refreshData();
        } catch (error) {
            this.handleError(error);
        }
    }

    /**
     * Refresh dashboard data
     */
    async refreshData() {
        await this.loadDashboard();
    }

    /**
     * Render dashboard UI
     */
    renderDashboard(budgetData, categories, transactions) {
        // This would render your dashboard UI
        // Implementation depends on your frontend framework
        console.log('Rendering dashboard with:', { budgetData, categories, transactions });
    }

    /**
     * Render monthly report
     */
    renderReport(report) {
        // Render the monthly report
        console.log('Rendering report:', report);
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        console.log('Success:', message);
        // Implement your success notification UI
    }

    /**
     * Handle API errors
     */
    handleError(error) {
        console.error('Error:', error);
        // Implement your error notification UI
    }
}

// Initialize the app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.budgetBuddyApp = new BudgetBuddyApp();
});

// Example usage with async/await
async function exampleUsage() {
    const api = new BudgetBuddyAPIClient(window.location.origin);

    try {
        // Get budget overview
        const budget = await api.getBudgetData(3);
        console.log('3-month budget:', budget);

        // Add a new expense
        const newExpense = await api.addTransaction({
            type: 'expense',
            amount: 1500,
            description: 'Groceries',
            date: '2025-01-15',
            category_id: 1
        });
        console.log('New expense:', newExpense);

        // Get transactions for current month
        const currentMonth = new Date().toISOString().slice(0, 7); // YYYY-MM
        const monthlyTransactions = await api.getTransactions({ month: currentMonth });
        console.log('This month transactions:', monthlyTransactions);

        // Get monthly report
        const report = await api.getMonthlyReport(currentMonth);
        console.log('Monthly report:', report);

    } catch (error) {
        console.error('Example failed:', error);
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { BudgetBuddyAPIClient, BudgetBuddyApp };
}
