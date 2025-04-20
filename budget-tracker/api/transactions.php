<?php
/**
 * Transactions API
 * Handles CRUD operations for transactions
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo jsonResponse(false, 'Unauthorized access');
    exit;
}

// Get user ID
$userId = $_SESSION['user_id'];

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get action
    $action = $_GET['action'] ?? 'list';
    
    // Handle different actions
    switch ($action) {
        case 'list':
            getTransactions($userId);
            break;
        case 'recent':
            getRecentTransactions($userId);
            break;
        case 'get':
            getTransaction($userId);
            break;
        case 'expense_categories':
            getExpenseCategoriesData($userId);
            break;
        case 'monthly_summary':
            getMonthlyTransactionData($userId);
            break;
        default:
            echo jsonResponse(false, 'Invalid action');
            break;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get action
    $action = $_POST['action'] ?? '';
    
    // Handle different actions
    switch ($action) {
        case 'add':
            addTransaction($userId);
            break;
        case 'update':
            updateTransaction($userId);
            break;
        case 'delete':
            deleteTransaction($userId);
            break;
        default:
            echo jsonResponse(false, 'Invalid action');
            break;
    }
}

/**
 * Get transactions for a user with optional filtering
 * @param int $userId User ID
 */
function getTransactions($userId) {
    // Get filter parameters
    $fromDate = $_GET['from_date'] ?? '';
    $toDate = $_GET['to_date'] ?? '';
    $type = $_GET['type'] ?? '';
    $category = $_GET['category_id'] ?? '';
    
    // Build WHERE clause
    $where = ['t.user_id = ?'];
    $params = [$userId];
    
    if (!empty($fromDate)) {
        $where[] = 't.transaction_date >= ?';
        $params[] = $fromDate;
    }
    
    if (!empty($toDate)) {
        $where[] = 't.transaction_date <= ?';
        $params[] = $toDate;
    }
    
    if (!empty($type)) {
        $where[] = 'c.type = ?';
        $params[] = $type;
    }
    
    if (!empty($category)) {
        $where[] = 't.category_id = ?';
        $params[] = $category;
    }
    
    // Build query
    $whereClause = implode(' AND ', $where);
    
    $sql = "
        SELECT t.*, c.name as category_name, c.type
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE {$whereClause}
        ORDER BY t.transaction_date DESC, t.id DESC
    ";
    
    // Execute query
    try {
        $transactions = fetchAll($sql, $params);
        
        // Get summary data
        $incomeTotal = 0;
        $expenseTotal = 0;
        
        foreach ($transactions as $transaction) {
            if ($transaction['type'] === 'income') {
                $incomeTotal += $transaction['amount'];
            } else {
                $expenseTotal += $transaction['amount'];
            }
        }
        
        $summary = [
            'total_income' => $incomeTotal,
            'total_expenses' => $expenseTotal,
            'balance' => $incomeTotal - $expenseTotal
        ];
        
        echo jsonResponse(true, '', [
            'transactions' => $transactions,
            'summary' => $summary
        ]);
    } catch (Exception $e) {
        error_log('Failed to get transactions: ' . $e->getMessage());
        echo jsonResponse(false, 'Failed to get transactions');
    }
}

/**
 * Get recent transactions
 * @param int $userId User ID
 */
function getRecentTransactions($userId) {
    $limit = $_GET['limit'] ?? 5;
    
    $sql = "
        SELECT t.*, c.name as category_name, c.type
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
        ORDER BY t.transaction_date DESC, t.id DESC
        LIMIT ?
    ";
    
    try {
        $transactions = fetchAll($sql, [$userId, $limit]);
        echo jsonResponse(true, '', ['transactions' => $transactions]);
    } catch (Exception $e) {
        error_log('Failed to get recent transactions: ' . $e->getMessage());
        echo jsonResponse(false, 'Failed to get recent transactions');
    }
}

/**
 * Get a single transaction
 * @param int $userId User ID
 */
function getTransaction($userId) {
    $id = $_GET['id'] ?? 0;
    
    $sql = "
        SELECT t.*, c.name as category_name, c.type
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.id = ? AND t.user_id = ?
    ";
    
    try {
        $transaction = fetchRow($sql, [$id, $userId]);
        
        if (!$transaction) {
            echo jsonResponse(false, 'Transaction not found');
            return;
        }
        
        echo jsonResponse(true, '', ['transaction' => $transaction]);
    } catch (Exception $e) {
        error_log('Failed to get transaction: ' . $e->getMessage());
        echo jsonResponse(false, 'Failed to get transaction');
    }
}

/**
 * Get expense categories data for charts
 * @param int $userId User ID
 */
function getExpenseCategoriesData($userId) {
    // Get current month start and end dates
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
    
    try {
        $categories = getExpenseCategories($userId, $startDate, $endDate);
        echo jsonResponse(true, '', ['categories' => $categories]);
    } catch (Exception $e) {
        error_log('Failed to get expense categories: ' . $e->getMessage());
        echo jsonResponse(false, 'Failed to get expense categories');
    }
}

/**
 * Get monthly transaction data for charts
 * @param int $userId User ID
 */
function getMonthlyTransactionData($userId) {
    $months = $_GET['months'] ?? 6;
    
    try {
        $monthlyData = getMonthlyData($userId, $months);
        echo jsonResponse(true, '', ['monthly_data' => $monthlyData]);
    } catch (Exception $e) {
        error_log('Failed to get monthly data: ' . $e->getMessage());
        echo jsonResponse(false, 'Failed to get monthly data');
    }
}

/**
 * Add a new transaction
 * @param int $userId User ID
 */
function addTransaction($userId) {
    // Get form data
    $type = $_POST['type'] ?? 'expense';
    $categoryId = $_POST['category_id'] ?? 0;
    $amount = floatval($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $date = $_POST['transaction_date'] ?? date('Y-m-d');
    
    // Validate input
    if (empty($categoryId) || $amount <= 0 || empty($date)) {
        echo jsonResponse(false, 'Invalid input');
        return;
    }
    
    // Verify category belongs to user
    $category = fetchRow(
        "SELECT * FROM categories WHERE id = ? AND user_id = ?",
        [$categoryId, $userId]
    );
    
    if (!$category) {
        echo jsonResponse(false, 'Invalid category');
        return;
    }
    
    // Insert transaction
    try {
        $transactionId = insert('transactions', [
            'user_id' => $userId,
            'category_id' => $categoryId,
            'amount' => $amount,
            'description' => $description,
            'transaction_date' => $date
        ]);
        
        if (!$transactionId) {
            echo jsonResponse(false, 'Failed to add transaction');
            return;
        }
        
        echo jsonResponse(true, 'Transaction added successfully', ['id' => $transactionId]);
    } catch (Exception $e) {
        error_log('Failed to add transaction: ' . $e->getMessage());
        echo jsonResponse(false, 'Failed to add transaction');
    }
}

/**
 * Update an existing transaction
 * @param int $userId User ID
 */
function updateTransaction($userId) {
    // Get form data
    $id = $_POST['id'] ?? 0;
    $type = $_POST['type'] ?? 'expense';
    $categoryId = $_POST['category_id'] ?? 0;
    $amount = floatval($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $date = $_POST['transaction_date'] ?? date('Y-m-d');
    
    // Validate input
    if (empty($id) || empty($categoryId) || $amount <= 0 || empty($date)) {
        echo jsonResponse(false, 'Invalid input');
        return;
    }
    
    // Verify transaction belongs to user
    $transaction = fetchRow(
        "SELECT * FROM transactions WHERE id = ? AND user_id = ?",
        [$id, $userId]
    );
    
    if (!$transaction) {
        echo jsonResponse(false, 'Transaction not found');
        return;
    }
    
    // Verify category belongs to user
    $category = fetchRow(
        "SELECT * FROM categories WHERE id = ? AND user_id = ?",
        [$categoryId, $userId]
    );
    
    if (!$category) {
        echo jsonResponse(false, 'Invalid category');
        return;
    }
    
    // Update transaction
    try {
        $result = update('transactions', [
            'category_id' => $categoryId,
            'amount' => $amount,
            'description' => $description,
            'transaction_date' => $date
        ], ['id' => $id]);
        
        if ($result === 0) {
            echo jsonResponse(false, 'No changes made');
            return;
        }
        
        echo jsonResponse(true, 'Transaction updated successfully');
    } catch (Exception $e) {
        error_log('Failed to update transaction: ' . $e->getMessage());
        echo jsonResponse(false, 'Failed to update transaction');
    }
}

/**
 * Delete a transaction
 * @param int $userId User ID
 */
function deleteTransaction($userId) {
    // Get transaction ID
    $id = $_POST['id'] ?? 0;
    
    // Validate input
    if (empty($id)) {
        echo jsonResponse(false, 'Invalid input');
        return;
    }
    
    // Verify transaction belongs to user
    $transaction = fetchRow(
        "SELECT * FROM transactions WHERE id = ? AND user_id = ?",
        [$id, $userId]
    );
    
    if (!$transaction) {
        echo jsonResponse(false, 'Transaction not found');
        return;
    }
    
    // Delete transaction
    try {
        $result = delete('transactions', ['id' => $id]);
        
        if ($result === 0) {
            echo jsonResponse(false, 'Failed to delete transaction');
            return;
        }
        
        echo jsonResponse(true, 'Transaction deleted successfully');
    } catch (Exception $e) {
        error_log('Failed to delete transaction: ' . $e->getMessage());
        echo jsonResponse(false, 'Failed to delete transaction');
    }
}