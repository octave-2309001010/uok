// transactions.js - Handle transactions operations

document.addEventListener('DOMContentLoaded', function() {
    // Load transactions
    loadTransactions();
    
    // Load categories for dropdown
    loadCategories();
    
    // Add transaction form handler
    const addTransactionForm = document.getElementById('addTransactionForm');
    
    if (addTransactionForm) {
        addTransactionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Reset error messages
            const errorElements = document.querySelectorAll('.error-message');
            errorElements.forEach(element => {
                element.textContent = '';
            });
            
            // Get form data
            const type = document.getElementById('transaction_type').value;
            const category = document.getElementById('category').value;
            const amount = document.getElementById('amount').value;
            const description = document.getElementById('description').value.trim();
            const date = document.getElementById('transaction_date').value;
            
            // Validate input
            let isValid = true;
            
            if (category === '') {
                document.getElementById('category-error').textContent = 'Please select a category';
                isValid = false;
            }
            
            if (amount === '' || isNaN(amount) || parseFloat(amount) <= 0) {
                document.getElementById('amount-error').textContent = 'Please enter a valid amount';
                isValid = false;
            }
            
            if (date === '') {
                document.getElementById('date-error').textContent = 'Please select a date';
                isValid = false;
            }
            
            // If validation passes, submit the form
            if (isValid) {
                // Create form data object
                const formData = new FormData();
                formData.append('type', type);
                formData.append('category_id', category);
                formData.append('amount', amount);
                formData.append('description', description);
                formData.append('transaction_date', date);
                formData.append('action', 'add');
                
                // Send AJAX request
                fetch('api/transactions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Transaction added successfully
                        // Reset form
                        addTransactionForm.reset();
                        
                        // Show success message
                        const successAlert = document.createElement('div');
                        successAlert.className = 'alert alert-success';
                        successAlert.textContent = 'Transaction added successfully!';
                        
                        // Insert alert before form
                        addTransactionForm.parentNode.insertBefore(successAlert, addTransactionForm);
                        
                        // Remove alert after 3 seconds
                        setTimeout(() => {
                            successAlert.remove();
                        }, 3000);
                        
                        // Reload transactions
                        loadTransactions();
                    } else {
                        // Transaction failed
                        alert('Failed to add transaction: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again later.');
                });
            }
        });
        
        // Transaction type change handler
        document.getElementById('transaction_type').addEventListener('change', function() {
            loadCategories();
        });
    }
    
    /**
     * Load transactions from API
     */
    function loadTransactions() {
        // Get filter values if they exist
        const fromDate = document.getElementById('filter_from_date')?.value || '';
        const toDate = document.getElementById('filter_to_date')?.value || '';
        const filterType = document.getElementById('filter_type')?.value || '';
        const filterCategory = document.getElementById('filter_category')?.value || '';
        
        // Build query string
        let queryString = 'action=list';
        if (fromDate) queryString += `&from_date=${fromDate}`;
        if (toDate) queryString += `&to_date=${toDate}`;
        if (filterType) queryString += `&type=${filterType}`;
        if (filterCategory) queryString += `&category_id=${filterCategory}`;
        
        // Send AJAX request
        fetch(`api/transactions.php?${queryString}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Display transactions
                    displayTransactions(data.transactions);
                    
                    // Update summary
                    updateSummary(data.summary);
                } else {
                    console.error('Error loading transactions:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    /**
     * Display transactions in the table
     * @param {Array} transactions - Array of transaction objects
     */
    function displayTransactions(transactions) {
        const tableBody = document.getElementById('transactions-body');
        
        // Clear existing rows
        tableBody.innerHTML = '';
        
        // Check if there are transactions
        if (transactions.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td colspan="6" class="text-center">No transactions found</td>
            `;
            tableBody.appendChild(row);
            return;
        }
        
        // Add transaction rows
        transactions.forEach(transaction => {
            const row = document.createElement('tr');
            
            // Format date
            const date = new Date(transaction.transaction_date);
            const formattedDate = date.toLocaleDateString();
            
            // Format amount (add + sign for income, - sign for expense)
            let amountClass = transaction.type === 'income' ? 'amount-positive' : 'amount-negative';
            let amountSign = transaction.type === 'income' ? '+' : '-';
            
            row.innerHTML = `
                <td>${formattedDate}</td>
                <td>${transaction.description || 'N/A'}</td>
                <td>${transaction.category_name}</td>
                <td>${transaction.type.charAt(0).toUpperCase() + transaction.type.slice(1)}</td>
                <td class="transaction-amount ${amountClass}">${amountSign}$${Math.abs(transaction.amount).toFixed(2)}</td>
                <td>
                    <button class="btn-edit" data-id="${transaction.id}">Edit</button>
                    <button class="btn-delete" data-id="${transaction.id}">Delete</button>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
        
        // Add event listeners for edit and delete buttons
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', function() {
                const transactionId = this.getAttribute('data-id');
                editTransaction(transactionId);
            });
        });
        
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function() {
                const transactionId = this.getAttribute('data-id');
                deleteTransaction(transactionId);
            });
        });
    }
    
    /**
     * Update summary information
     * @param {Object} summary - Summary data with total income and expenses
     */
    function updateSummary(summary) {
        const totalIncome = document.getElementById('total-income');
        const totalExpenses = document.getElementById('total-expenses');
        const balance = document.getElementById('balance');
        
        if (totalIncome && totalExpenses && balance) {
            totalIncome.textContent = `$${summary.total_income.toFixed(2)}`;
            totalExpenses.textContent = `$${summary.total_expenses.toFixed(2)}`;
            balance.textContent = `$${summary.balance.toFixed(2)}`;
            
            // Add color based on balance
            if (summary.balance < 0) {
                balance.className = 'amount amount-negative';
            } else {
                balance.className = 'amount amount-positive';
            }
        }
    }
    
    /**
     * Load categories for dropdown
     */
    function loadCategories() {
        // Get transaction type
        const type = document.getElementById('transaction_type')?.value || 'expense';
        
        // Send AJAX request
        fetch(`api/categories.php?action=list&type=${type}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Populate category dropdown
                    populateCategoryDropdown(data.categories);
                } else {
                    console.error('Error loading categories:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    /**
     * Populate category dropdown
     * @param {Array} categories - Array of category objects
     */
    function populateCategoryDropdown(categories) {
        const categorySelect = document.getElementById('category');
        
        if (categorySelect) {
            // Clear existing options (except first placeholder)
            const firstOption = categorySelect.options[0];
            categorySelect.innerHTML = '';
            categorySelect.appendChild(firstOption);
            
            // Add new options
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                categorySelect.appendChild(option);
            });
        }
    }
    
    /**
     * Edit transaction
     * @param {number} transactionId - ID of the transaction to edit
     */
    function editTransaction(transactionId) {
        // Fetch transaction details
        fetch(`api/transactions.php?action=get&id=${transactionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const transaction = data.transaction;
                    
                    // Populate edit form
                    document.getElementById('edit_transaction_id').value = transaction.id;
                    document.getElementById('edit_transaction_type').value = transaction.type;
                    
                    // Load categories for the selected type first
                    fetch(`api/categories.php?action=list&type=${transaction.type}`)
                        .then(response => response.json())
                        .then(catData => {
                            if (catData.success) {
                                const editCategorySelect = document.getElementById('edit_category');
                                editCategorySelect.innerHTML = '';
                                
                                catData.categories.forEach(category => {
                                    const option = document.createElement('option');
                                    option.value = category.id;
                                    option.textContent = category.name;
                                    editCategorySelect.appendChild(option);
                                });
                                
                                // Then set the selected category
                                editCategorySelect.value = transaction.category_id;
                            }
                        });
                    
                    document.getElementById('edit_amount').value = transaction.amount;
                    document.getElementById('edit_description').value = transaction.description || '';
                    document.getElementById('edit_transaction_date').value = transaction.transaction_date;
                    
                    // Show modal
                    const editModal = document.getElementById('editTransactionModal');
                    editModal.style.display = 'block';
                } else {
                    alert('Error fetching transaction details: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
            });
    }
    
    /**
     * Delete transaction
     * @param {number} transactionId - ID of the transaction to delete
     */
    function deleteTransaction(transactionId) {
        if (confirm('Are you sure you want to delete this transaction?')) {
            // Create form data
            const formData = new FormData();
            formData.append('id', transactionId);
            formData.append('action', 'delete');
            
            // Send AJAX request
            fetch('api/transactions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaction deleted successfully!');
                    loadTransactions();
                } else {
                    alert('Failed to delete transaction: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
            });
        }
    }
    
    // Filter form handler
    const filterForm = document.getElementById('filterForm');
    
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            loadTransactions();
        });
        
        // Reset filter button
        document.getElementById('resetFilter').addEventListener('click', function() {
            filterForm.reset();
            loadTransactions();
        });
    }
    
    // Edit transaction form handler
    const editTransactionForm = document.getElementById('editTransactionForm');
    
    if (editTransactionForm) {
        editTransactionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const id = document.getElementById('edit_transaction_id').value;
            const type = document.getElementById('edit_transaction_type').value;
            const category = document.getElementById('edit_category').value;
            const amount = document.getElementById('edit_amount').value;
            const description = document.getElementById('edit_description').value.trim();
            const date = document.getElementById('edit_transaction_date').value;
            
            // Validate input
            let isValid = true;
            
            if (category === '') {
                alert('Please select a category');
                isValid = false;
            }
            
            if (amount === '' || isNaN(amount) || parseFloat(amount) <= 0) {
                alert('Please enter a valid amount');
                isValid = false;
            }
            
            if (date === '') {
                alert('Please select a date');
                isValid = false;
            }
            
            // If validation passes, submit the form
            if (isValid) {
                // Create form data object
                const formData = new FormData();
                formData.append('id', id);
                formData.append('type', type);
                formData.append('category_id', category);
                formData.append('amount', amount);
                formData.append('description', description);
                formData.append('transaction_date', date);
                formData.append('action', 'update');
                
                // Send AJAX request
                fetch('api/transactions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        document.getElementById('editTransactionModal').style.display = 'none';
                        
                        // Show success message
                        alert('Transaction updated successfully!');
                        
                        // Reload transactions
                        loadTransactions();
                    } else {
                        // Update failed
                        alert('Failed to update transaction: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again later.');
                });
            }
        });
        
        // Close modal button
        document.querySelector('.close-modal').addEventListener('click', function() {
            document.getElementById('editTransactionModal').style.display = 'none';
        });
        
        // Type change in edit form
        document.getElementById('edit_transaction_type').addEventListener('change', function() {
            const type = this.value;
            
            // Fetch categories for the selected type
            fetch(`api/categories.php?action=list&type=${type}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const editCategorySelect = document.getElementById('edit_category');
                        editCategorySelect.innerHTML = '';
                        
                        data.categories.forEach(category => {
                            const option = document.createElement('option');
                            option.value = category.id;
                            option.textContent = category.name;
                            editCategorySelect.appendChild(option);
                        });
                    }
                });
        });
    }
});