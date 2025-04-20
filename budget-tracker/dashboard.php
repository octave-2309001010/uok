<?php
// Include necessary PHP files
include_once 'includes/config.php';
include_once 'includes/functions.php';
include_once 'includes/db.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get summary data
$currentMonth = date('m');
$currentYear = date('Y');
$income = getTotalIncome($user_id, $currentMonth, $currentYear);
$expenses = getTotalExpenses($user_id, $currentMonth, $currentYear);
$balance = $income - $expenses;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Budget Tracker</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-container">
        <div class="sidebar">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo substr($user['username'], 0, 1); ?>
                </div>
                <div class="user-name">
                    <?php echo $user['username']; ?>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="active"><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="transactions.php">Transactions</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="api/auth.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <h2>Dashboard</h2>
            
            <div class="summary-cards">
                <div class="card income-card">
                    <h3>Income</h3>
                    <p class="amount">$<?php echo number_format($income, 2); ?></p>
                    <p class="period">This Month</p>
                </div>
                
                <div class="card expense-card">
                    <h3>Expenses</h3>
                    <p class="amount">$<?php echo number_format($expenses, 2); ?></p>
                    <p class="period">This Month</p>
                </div>
                
                <div class="card balance-card">
                    <h3>Balance</h3>
                    <p class="amount">$<?php echo number_format($balance, 2); ?></p>
                    <p class="period">This Month</p>
                </div>
            </div>
            
            <div class="charts-container">
                <div class="chart-card">
                    <h3>Expense Categories</h3>
                    <div class="chart" id="expense-pie-chart"></div>
                </div>
                
                <div class="chart-card">
                    <h3>Income vs Expenses</h3>
                    <div class="chart" id="income-expense-chart"></div>
                </div>
            </div>
            
            <div class="recent-transactions">
                <div class="header-with-button">
                    <h3>Recent Transactions</h3>
                    <a href="transactions.php" class="btn-secondary">View All</a>
                </div>
                
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody id="recent-transactions-body">
                        <!-- This will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>