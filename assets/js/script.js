document.addEventListener('DOMContentLoaded', () => {
    const openBtn = document.getElementById('bb_add_action');
    const modal = document.getElementById('bb_modal_overlay');
    const closeBtn = document.querySelector('.bb_close');

    if (openBtn && modal && closeBtn) {
        openBtn.addEventListener('click', () => {
            modal.classList.add('active');
        });

        closeBtn.addEventListener('click', () => {
            modal.classList.remove('active');
        });

        // Close on clicking outside the modal
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    }

    // Handle expanding/collapsing transaction details
    document.querySelectorAll('.bb_expand_action').forEach(function (td) {
        td.addEventListener('click', function () {
            const btn = td.querySelector('.bb_expand_btn');  // Select the button inside the td
            const targetId = td.getAttribute('data-target');
            const row = document.getElementById(targetId);
            
            if (row) {
                row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
                // Change the button text accordingly
                btn.textContent = row.style.display === 'table-row' ? 'Collapse' : 'Expand';
            }
        });
    });

    // Style the transaction type select dropdown
    const typeSelect = document.querySelector('select[name="type"]');
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            // Remove any existing classes
            this.classList.remove('income-select', 'expense-select', 'loan-select');
            
            // Add appropriate class based on selection
            if (this.value === 'income') {
                this.classList.add('income-select');
            } else if (this.value === 'expense') {
                this.classList.add('expense-select');
            } else if (this.value === 'loan') {
                this.classList.add('loan-select');
            }
        });
        
        // Trigger change event to apply initial styling
        typeSelect.dispatchEvent(new Event('change'));
    }
});