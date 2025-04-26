document.addEventListener("DOMContentLoaded", () => {
  const openBtn = document.getElementById("bb_add_action");
  const modal = document.getElementById("bb_transation_modal_overlay");
  const closeBtn = document.querySelector(".bb-transation_modal__close");

  if (openBtn && modal && closeBtn) {
    // Open modal
    openBtn.addEventListener("click", () => {
      modal.classList.add("active");
    });

    // Close modal via close button
    closeBtn.addEventListener("click", () => {
      modal.classList.remove("active");
    });

    // Close modal by clicking outside
    modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        modal.classList.remove("active");
      }
    });
  }

  const alerts = document.querySelectorAll(".bb-alert");
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0";
      setTimeout(() => alert.remove(), 500); // remove after fade
    }, 3000); // hide after 3 seconds
  });

  const planModal = document.getElementById("bb_plan_modal_overlay");
  const planMonthInput = document.getElementById("plan_month");

  document.querySelectorAll(".bb-toggle-plan").forEach((button) => {
    button.addEventListener("click", function () {
      const selectedMonth = button.getAttribute("data-month");
      if (planMonthInput) {
        planMonthInput.value = selectedMonth;
      }
      planModal.style.display = "flex";
    });
  });

  // Close modals
  document.querySelectorAll(".bb-plan_modal__close").forEach((closeBtn) => {
    closeBtn.addEventListener("click", function () {
      planModal.style.display = "none";
    });
  });

  // Optional: close modal on outside click
  document.querySelectorAll(".bb-plan_modal__overlay").forEach((overlay) => {
    overlay.addEventListener("click", function (e) {
      if (e.target === this) {
        this.style.display = "none";
      }
    });
  });

  // Toggle transaction detail view
  document.querySelectorAll(".bb-expand-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const transactionEl = this.closest(".bb-transaction");
      const detailsEl = transactionEl.querySelector(".bb-transaction__details");

      if (detailsEl) {
        const isVisible = detailsEl.style.display === "block";

        detailsEl.style.display = isVisible ? "none" : "block";

        // Toggle class
        this.classList.remove(isVisible ? "Collapse" : "Expand");
        this.classList.add(isVisible ? "Expand" : "Collapse");
      }
    });
  });

  // Dynamic styling for type select in form
  const typeSelect = document.querySelector('select[name="type"]');
  if (typeSelect) {
    const updateSelectStyle = () => {
      typeSelect.classList.remove(
        "bb-form__input--income",
        "bb-form__input--expense",
        "bb-form__input--loan"
      );
      if (typeSelect.value === "income") {
        typeSelect.classList.add("bb-form__input--income");
      } else if (typeSelect.value === "expense") {
        typeSelect.classList.add("bb-form__input--expense");
      } else if (typeSelect.value === "loan") {
        typeSelect.classList.add("bb-form__input--loan");
      }
    };

    typeSelect.addEventListener("change", updateSelectStyle);
    updateSelectStyle(); // Initial call to apply style
  }

  const planButtons = document.querySelectorAll(".bb-month__plan-btn");

  planButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      // Get parent .bb-month
      const thisMonth = btn.closest(".bb-month");
      const thisPlan = thisMonth.querySelector(".bb-month__plan");

      // Hide all other plans
      document.querySelectorAll(".bb-month__plan").forEach((plan) => {
        if (plan !== thisPlan) {
          plan.style.display = "none";
        }
      });

      // Toggle current one
      if (thisPlan) {
        const isVisible = thisPlan.style.display === "block";
        thisPlan.style.display = isVisible ? "none" : "block";
      }
    });
  });
});


// Monthly Report functionality
document.addEventListener("DOMContentLoaded", () => {
  // Handle Report Button Click
  document.querySelectorAll(".bb-month__report-btn").forEach((btn) => {
    btn.addEventListener("click", function() {
      const month = this.getAttribute("data-month");
      const reportModal = document.getElementById("bb_report_modal_overlay");
      const reportContent = document.getElementById("bb-report-content");
      
      // Show modal with loading state
      reportModal.classList.add("active");
      reportContent.innerHTML = '<div class="bb-report-loading">Loading report data...</div>';
      
      // Fetch report data via AJAX
      const formData = new FormData();
      formData.append('action', 'bb_get_monthly_report');
      formData.append('month', month);
      formData.append('nonce', bb_data.report_nonce);
      
      fetch(bb_data.ajax_url, {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          generateReportHTML(data.data, reportContent);
        } else {
          reportContent.innerHTML = `<div class="bb-report-error">Error loading report: ${data.data}</div>`;
        }
      })
      .catch(error => {
        reportContent.innerHTML = `<div class="bb-report-error">Error: ${error.message}</div>`;
      });
    });
  });
  
  // Close report modal
  const reportModal = document.getElementById("bb_report_modal_overlay");
  const reportCloseBtn = document.querySelector(".bb-report_modal__close");
  
  if (reportCloseBtn && reportModal) {
    reportCloseBtn.addEventListener("click", () => {
      reportModal.classList.remove("active");
    });
    
    reportModal.addEventListener("click", (e) => {
      if (e.target === reportModal) {
        reportModal.classList.remove("active");
      }
    });
  }
  
  // Print report functionality
  const printBtn = document.getElementById("bb-print-report");
  if (printBtn) {
    printBtn.addEventListener("click", () => {
      window.print();
    });
  }
});

// Generate Report HTML
function generateReportHTML(data, container) {
  const incomeFormatted = new Intl.NumberFormat('en-IN', { 
    style: 'currency', 
    currency: 'INR',
    maximumFractionDigits: 2
  }).format(data.income);
  
  const expenseFormatted = new Intl.NumberFormat('en-IN', { 
    style: 'currency', 
    currency: 'INR',
    maximumFractionDigits: 2
  }).format(data.expense);
  
  const loanFormatted = new Intl.NumberFormat('en-IN', { 
    style: 'currency', 
    currency: 'INR', 
    maximumFractionDigits: 2
  }).format(data.loan);
  
  const netFormatted = new Intl.NumberFormat('en-IN', { 
    style: 'currency', 
    currency: 'INR',
    maximumFractionDigits: 2
  }).format(data.net);
  
  const netClass = data.net >= 0 ? 'positive' : 'negative';
  
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
    
    <div class="bb-report-details">
      <h4>Transaction Summary</h4>
      <p>Total transactions: ${data.transaction_count}</p>
      <p>Monthly budget utilization: ${data.expense > 0 && data.income > 0 ? 
        Math.round((data.expense / data.income) * 100) + '%' : 'N/A'}</p>
    </div>
  `;
  
  container.innerHTML = html;
}

document.addEventListener("DOMContentLoaded", () => {
  jQuery(function ($) {
    $('#bb-add-transation-form').on('submit', function (e) {
      e.preventDefault();

      const form = $(this);
      const formData = {
        action: 'bb_add_transaction',
        report_nonce: bb_data.report_nonce,  // Changed from nonce to report_nonce
        type: form.find('[name="type"]').val(),
        amount: form.find('[name="amount"]').val(),
        description: form.find('[name="description"]').val(),
        date: form.find('[name="date"]').val(),
      };

      $.post(bb_data.ajax_url, formData, function (response) {  // Use bb_data instead of bb_ajax_obj
        if (response.success) {
          alert(response.data.message);
          form[0].reset(); // reset the form
          window.location.reload(); // Reload page to show new transaction
        } else {
          alert(response.data.message || 'Error adding transaction');
        }
      });
    });
	  
	  
	 // Delete transation
	 $('.delete-transaction-btn').on('click', function(e) {
  e.preventDefault();

  const button = this; // More readable than e.target
  const transactionId = button.getAttribute('data-id');

  if (confirm('Are you sure you want to delete this transaction?')) {
    // Prepare the data
    const formData = new FormData();
    formData.append('action', 'bb_delete_transaction');
    formData.append('transaction_id', transactionId);
    formData.append('security', bb_data.report_nonce);

    // Show loading state
    button.textContent = 'Deleting...';
    button.disabled = true;

    // Send AJAX request
    fetch(bb_data.ajax_url, {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Find and remove the transaction element from the DOM
        const transactionElement = button.closest('.bb-transaction');
        transactionElement.style.opacity = '0';
        setTimeout(() => {
          transactionElement.remove();
          // Display success message
          const alertDiv = document.createElement('div');
          alertDiv.className = 'updated bb-alert';
          alertDiv.innerHTML = `<p>${data.data.message}</p>`;
          document.querySelector('.bb-container').prepend(alertDiv);
          
          // Auto-remove the alert after 3 seconds
          setTimeout(() => {
            alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 500);
          }, 3000);
        }, 300);
      } else {
        alert(data.data.message || 'Error deleting transaction');
        button.textContent = 'Delete';
        button.disabled = false;
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error deleting transaction. Please try again.');
      button.textContent = 'Delete';
      button.disabled = false;
    });
  }
});

	  $('#bb-add-plan-form').on('submit', function (e) {
        e.preventDefault();

        const data = {
            action: 'bb_add_plan',
            security: bb_data.report_nonce,
            plan_text: $('#plan_text').val(),
            amount: $('#plan_amount').val(),
            plan_month: $('#plan_month').val(),
        };

        console.log('Data:',data)
        $.post(bb_data.ajax_url, data, function (response) {
            if (response.success) {
                alert(response.data.message);
                location.reload(); // or update UI dynamically
            } else {
                alert(response.data.message);
            }
        });
    });

      $('.bb-plan-delete-form').on('submit', function(e) {
          e.preventDefault();
  
          if (!confirm('Are you sure you want to delete this plan?')) {
              return;
          }
  
          var $form = $(this);
          var formData = new FormData(this);
          formData.append('action', 'bb_delete_plan');
          formData.append('security', bb_data.report_nonce); // localized nonce
  
          $.ajax({
              url: bb_data.ajax_url,
              method: 'POST',
              data: formData,
              processData: false,
              contentType: false,
              success: function(response) {
                  if (response.success) {
                      alert('Plan deleted successfully.');
  
                      // Remove the plan container
                      var $planContainer = $form.closest('.bb-plan-item');
                      if ($planContainer.length) {
                          $planContainer.remove();
                      }
                  } else {
                      alert(response.data.message || 'Failed to delete plan.');
                  }
              },
              error: function(xhr, status, error) {
                  console.error('AJAX Error:', error);
                  alert('An error occurred. Please try again.');
              }
          });
      });

        $('.bb-plan-status-form').on('submit', function(e) {
            e.preventDefault();
    
            const $form = $(this);
            const formData = new FormData(this);
    
            formData.append('action', 'bb_update_plan_status');
            formData.append('security', bb_data.report_nonce); // Make sure this nonce is localized
    
            $.ajax({
                url: bb_data.ajax_url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Plan status updated.');
                        // Optional: Reload the page or update the DOM accordingly
                        location.reload();
                    } else {
                        alert(response.data.message || 'Failed to update status.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('An error occurred while updating status.');
                }
            });
        });
    });
    
    
  

  });




