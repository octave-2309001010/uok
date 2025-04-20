<?php
require_once 'db.php';
require_once 'auth.php';

// Get authenticated user ID
$user_id = authenticateUser();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Get report type from query parameter
$report_type = $_GET['type'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default to first day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t');      // Default to last day of current month

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
    exit;
}

switch ($report_type) {
    case 'summary':
        // Get income summary
        $income = fetchRow(
            "SELECT COALESCE(SUM(t.amount), 0) as total
             FROM transactions t
             JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND c.type = 'income' AND t.date BETWEEN ? AND ?",
            [$user_id, $start_date, $end_date]
        );
        
        // Get expense summary
        $expense = fetchRow(
            "SELECT COALESCE(SUM(t.amount), 0) as total
             FROM transactions t
             JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND c.type = 'expense' AND t.date BETWEEN ? AND ?",
            [$user_id, $start_date, $end_date]
        );
        
        // Calculate balance
        $balance = $income['total'] - $expense['total'];
        
        // Return summary data
        echo json_encode([
            'income' => (float)$income['total'],
            'expense' => (float)$expense['total'],
            'balance' => (float)$balance,
            'period' => [
                'start_date' => $start_date,
                'end_date' => $end_date
            ]
        ]);
        break;
        
    case 'category':
        // Get expense breakdown by category
        $expensesByCategory = fetchAll(
            "SELECT c.id, c.name, COALESCE(SUM(t.amount), 0) as total,
                    (COALESCE(SUM(t.amount), 0) / 
                     (SELECT COALESCE(SUM(t2.amount), 1) FROM transactions t2 
                      JOIN categories c2 ON t2.category_id = c2.id 
                      WHERE t2.user_id = ? AND c2.type = 'expense' AND t2.date BETWEEN ? AND ?)) * 100 as percentage
             FROM categories c
             LEFT JOIN transactions t ON c.id = t.category_id AND t.date BETWEEN ? AND ?
             WHERE c.user_id = ? AND c.type = 'expense'
             GROUP BY c.id, c.name
             ORDER BY total DESC",
            [$user_id, $start_date, $end_date, $start_date, $end_date, $user_id]
        );
        
        // Get income breakdown by category
        $incomesByCategory = fetchAll(
            "SELECT c.id, c.name, COALESCE(SUM(t.amount), 0) as total,
                    (COALESCE(SUM(t.amount), 0) / 
                     (SELECT COALESCE(SUM(t2.amount), 1) FROM transactions t2 
                      JOIN categories c2 ON t2.category_id = c2.id 
                      WHERE t2.user_id = ? AND c2.type = 'income' AND t2.date BETWEEN ? AND ?)) * 100 as percentage
             FROM categories c
             LEFT JOIN transactions t ON c.id = t.category_id AND t.date BETWEEN ? AND ?
             WHERE c.user_id = ? AND c.type = 'income'
             GROUP BY c.id, c.name
             ORDER BY total DESC",
            [$user_id, $start_date, $end_date, $start_date, $end_date, $user_id]
        );
        
        echo json_encode([
            'expenses' => $expensesByCategory,
            'income' => $incomesByCategory,
            'period' => [
                'start_date' => $start_date,
                'end_date' => $end_date
            ]
        ]);
        break;
        
    case 'trend':
        // Get date range type (daily, weekly, monthly)
        $range_type = $_GET['range_type'] ?? 'monthly';
        
        // Define date format and group by clause based on range type
        switch ($range_type) {
            case 'daily':
                $date_format = '%Y-%m-%d';
                $group_by = "DATE_FORMAT(t.date, '$date_format')";
                break;
            case 'weekly':
                $date_format = '%x-W%v'; // ISO year and week number
                $group_by = "DATE_FORMAT(t.date, '$date_format')";
                break;
            case 'monthly':
            default:
                $date_format = '%Y-%m';
                $group_by = "DATE_FORMAT(t.date, '$date_format')";
                break;
        }
        
        // Get income trend
        $incomeTrend = fetchAll(
            "SELECT $group_by as period, COALESCE(SUM(t.amount), 0) as total
             FROM transactions t
             JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND c.type = 'income' AND t.date BETWEEN ? AND ?
             GROUP BY period
             ORDER BY MIN(t.date)",
            [$user_id, $start_date, $end_date]
        );
        
        // Get expense trend
        $expenseTrend = fetchAll(
            "SELECT $group_by as period, COALESCE(SUM(t.amount), 0) as total
             FROM transactions t
             JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND c.type = 'expense' AND t.date BETWEEN ? AND ?
             GROUP BY period
             ORDER BY MIN(t.date)",
            [$user_id, $start_date, $end_date]
        );
        
        echo json_encode([
            'income' => $incomeTrend,
            'expense' => $expenseTrend,
            'period' => [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'range_type' => $range_type
            ]
        ]);
        break;
        
    case 'budget':
        // Get monthly budget data
        $yearMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
        
        if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid month format. Use YYYY-MM']);
            exit;
        }
        
        // Extract year and month
        list($year, $month) = explode('-', $yearMonth);
        
        // Calculate start and end dates for the month
        $startOfMonth = "$year-$month-01";
        $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
        
        // Get budget limits for categories
        $budgets = fetchAll(
            "SELECT c.id, c.name, b.amount as budget_amount
             FROM categories c
             LEFT JOIN budgets b ON c.id = b.category_id AND b.year = ? AND b.month = ?
             WHERE c.user_id = ? AND c.type = 'expense'
             ORDER BY c.name",
            [$year, $month, $user_id]
        );
        
        // Get actual spending for each category
        foreach ($budgets as &$budget) {
            $actual = fetchRow(
                "SELECT COALESCE(SUM(amount), 0) as total
                 FROM transactions
                 WHERE user_id = ? AND category_id = ? AND date BETWEEN ? AND ?",
                [$user_id, $budget['id'], $startOfMonth, $endOfMonth]
            );
            
            $budget['actual_amount'] = (float)$actual['total'];
            $budget['budget_amount'] = (float)$budget['budget_amount'];
            
            // Calculate remaining and percentage
            if ($budget['budget_amount'] > 0) {
                $budget['remaining'] = $budget['budget_amount'] - $budget['actual_amount'];
                $budget['percentage'] = ($budget['actual_amount'] / $budget['budget_amount']) * 100;
            } else {
                $budget['remaining'] = 0;
                $budget['percentage'] = 0;
            }
        }
        
        echo json_encode([
            'budgets' => $budgets,
            'period' => [
                'year' => (int)$year,
                'month' => (int)$month,
                'start_date' => $startOfMonth,
                'end_date' => $endOfMonth
            ]
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid report type. Available types: summary, category, trend, budget']);
        break;
}
?>