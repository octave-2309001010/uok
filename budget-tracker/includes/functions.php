<?php
/**
 * Helper functions for the Budget Tracker application
 */

/**
 * Sanitize input data
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    
    // Convert special characters to HTML entities
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate JSON response
 * @param bool $success Whether the request was successful
 * @param string $message Message to include in the response
 * @param array $data Additional data to include in the response
 * @return string JSON encoded response
 */
function jsonResponse($success, $message = '', $data = []) {
    $response = [
        'success' => $success,
        'message' => $message,
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    header('Content-Type: application/json');
    return json_encode($response);
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to another page
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: {$url}");
    exit;
}

/**
 * Get user by ID
 * @param int $user_id User ID
 * @return array|false User data or false if not found
 */
function getUserById($user_id) {
    return fetchRow("SELECT * FROM users WHERE id = ?", [$user_id]);
}

/**
 * Get user by email
 * @param string $email User email
 * @return array|false User data or false if not found
 */
function getUserByEmail($email) {
    return fetchRow("SELECT * FROM users WHERE email = ?", [$email]);
}

/**
 * Get user by username
 * @param string $username Username
 * @return array|false User data or false if not found
 */
function getUserByUsername($username) {
    return fetchRow("SELECT * FROM users WHERE username = ?", [$username]);
}

/**
 * Get total income for a user in a specific month and year
 * @param int $user_id User ID
 * @param int $month Month (1-12)
 * @param int $year Year (e.g., 2025)
 * @return float Total income
 */
function getTotalIncome($user_id, $month, $year) {
    $startDate = "{$year}-{$month}-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $result = fetchRow("
        SELECT COALESCE(SUM(amount), 0) as total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND c.type = 'income'
        AND t.transaction_date BETWEEN ? AND ?
    ", [$user_id, $startDate, $endDate]);
    
    return (float) $result['total'];
}

/**
 * Get total expenses for a user in a specific month and year
 * @param int $user_id User ID
 * @param int $month Month (1-12)
 * @param int $year Year (e.g., 2025)
 * @return float Total expenses
 */
function getTotalExpenses($user_id, $month, $year) {
    $startDate = "{$year}-{$month}-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $result = fetchRow("
        SELECT COALESCE(SUM(amount), 0) as total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND c.type = 'expense'
        AND t.transaction_date BETWEEN ? AND ?
    ", [$user_id, $startDate, $endDate]);
    
    return (float) $result['total'];
}

/**
 * Get monthly summary for income and expenses
 * @param int $user_id User ID
 * @param int $numMonths Number of months to include (default 6)
 * @return array Monthly summary data
 */
function getMonthlyData($user_id, $numMonths = 6) {
    $result = [];
    
    // Get the current month and year
    $currentMonth = (int) date('m');
    $currentYear = (int) date('Y');
    
    // Loop through the last N months
    for ($i = 0; $i < $numMonths; $i++) {
        $month = $currentMonth - $i;
        $year = $currentYear;
        
        // Adjust year if month is negative
        while ($month <= 0) {
            $month += 12;
            $year--;
        }
        
        // Get month name
        $monthName = date('M', mktime(0, 0, 0, $month, 1, $year));
        
        // Get income and expenses for this month
        $income = getTotalIncome($user_id, $month, $year);
        $expenses = getTotalExpenses($user_id, $month, $year);
        
        // Add to result
        $result[] = [
            'month' => $monthName . ' ' . $year,
            'income' => $income,
            'expense' => $expenses
        ];
    }
    
    // Reverse the array to get chronological order
    return array_reverse($result);
}

/**
 * Get expense categories with totals
 * @param int $user_id User ID
 * @param string $startDate Start date (Y-m-d)
 * @param string $endDate End date (Y-m-d)
 * @return array Category data with totals
 */
function getExpenseCategories($user_id, $startDate = null, $endDate = null) {
    // If dates not provided, use current month
    if (!$startDate) {
        $startDate = date('Y-m-01');
    }
    
    if (!$endDate) {
        $endDate = date('Y-m-t');
    }
    
    $sql = "
        SELECT c.name, COALESCE(SUM(t.amount), 0) as amount
        FROM categories c
        LEFT JOIN transactions t ON c.id = t.category_id AND t.transaction_date BETWEEN ? AND ?
        WHERE c.user_id = ? AND c.type = 'expense'
        GROUP BY c.id, c.name
        HAVING amount > 0
        ORDER BY amount DESC
    ";
    
    return fetchAll($sql, [$startDate, $endDate, $user_id]);
}

/**
 * Generate a random color in hexadecimal format
 * @return string Hexadecimal color code
 */
function getRandomColor() {
    return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
}

/**
 * Format amount with sign (+ for income, - for expense)
 * @param float $amount Amount
 * @param string $type Transaction type (income or expense)
 * @return string Formatted amount
 */
function formatAmount($amount, $type) {
    $sign = ($type === 'income') ? '+' : '-';
    return $sign . '$' . number_format(abs($amount), 2);
}

/**
 * Generate default categories for a new user
 * @param int $user_id User ID
 * @return bool True on success, false on failure
 */
function generateDefaultCategories($user_id) {
    $incomeCategories = ['Salary', 'Freelance', 'Investments', 'Gifts', 'Other Income'];
    $expenseCategories = ['Food', 'Housing', 'Transportation', 'Entertainment', 'Shopping', 'Utilities', 'Healthcare', 'Education', 'Travel', 'Other Expenses'];
    
    try {
        // Add income categories
        foreach ($incomeCategories as $category) {
            insert('categories', [
                'user_id' => $user_id,
                'name' => $category,
                'type' => 'income'
            ]);
        }
        
        // Add expense categories
        foreach ($expenseCategories as $category) {
            insert('categories', [
                'user_id' => $user_id,
                'name' => $category,
                'type' => 'expense'
            ]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log('Failed to generate default categories: ' . $e->getMessage());
        return false;
    }
}