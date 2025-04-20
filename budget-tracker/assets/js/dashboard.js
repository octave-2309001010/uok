// dashboard.js - Handle dashboard functionality

document.addEventListener('DOMContentLoaded', function() {
    // Load recent transactions
    loadRecentTransactions();
    
    // Load expense categories chart
    loadExpenseCategoriesChart();
    
    // Load income vs expense chart
    loadIncomeExpenseChart();
    
    /**
     * Load recent transactions from API
     */
    function loadRecentTransactions() {
        // Send AJAX request
        fetch('api/transactions.php?action=recent')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Display transactions
                    displayRecentTransactions(data.transactions);
                } else {
                    console.error('Error loading transactions:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    /**
     * Display recent transactions in the table
     * @param {Array} transactions - Array of transaction objects
     */
    function displayRecentTransactions(transactions) {
        const tableBody = document.getElementById('recent-transactions-body');
        
        // Clear existing rows
        tableBody.innerHTML = '';
        
        // Check if there are transactions
        if (transactions.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td colspan="4" class="text-center">No transactions found</td>
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
                <td>${transaction.description}</td>
                <td>${transaction.category_name}</td>
                <td class="transaction-amount ${amountClass}">${amountSign}$${Math.abs(transaction.amount).toFixed(2)}</td>
            `;
            
            tableBody.appendChild(row);
        });
    }
    
    /**
     * Load expense categories chart
     */
    function loadExpenseCategoriesChart() {
        // Send AJAX request
        fetch('api/transactions.php?action=expense_categories')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create chart
                    createExpensePieChart(data.categories);
                } else {
                    console.error('Error loading expense categories:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    /**
     * Create expense pie chart
     * @param {Array} categories - Array of category objects with name and amount
     */
    function createExpensePieChart(categories) {
        const ctx = document.getElementById('expense-pie-chart');
        
        // Extract data for chart
        const labels = categories.map(category => category.name);
        const amounts = categories.map(category => category.amount);
        
        // Generate random colors
        const backgroundColors = categories.map(() => getRandomColor());
        
        // Create chart
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: amounts,
                    backgroundColor: backgroundColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Load income vs expense chart
     */
    function loadIncomeExpenseChart() {
        // Send AJAX request
        fetch('api/transactions.php?action=monthly_summary')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create chart
                    createIncomeExpenseChart(data.monthly_data);
                } else {
                    console.error('Error loading monthly data:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    /**
     * Create income vs expense chart
     * @param {Array} monthlyData - Array of objects with month, income, and expense
     */
    function createIncomeExpenseChart(monthlyData) {
        const ctx = document.getElementById('income-expense-chart');
        
        // Extract data for chart
        const labels = monthlyData.map(item => item.month);
        const incomeData = monthlyData.map(item => item.income);
        const expenseData = monthlyData.map(item => item.expense);
        
        // Create chart
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Income',
                        data: incomeData,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Expenses',
                        data: expenseData,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.raw || 0;
                                return `${label}: $${value.toFixed(2)}`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Generate random color
     * @return {string} - Random color in rgba format
     */
    function getRandomColor() {
        const r = Math.floor(Math.random() * 255);
        const g = Math.floor(Math.random() * 255);
        const b = Math.floor(Math.random() * 255);
        return `rgba(${r}, ${g}, ${b}, 0.7)`;
    }
});