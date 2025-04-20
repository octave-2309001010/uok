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

// Process based on request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get query parameters for filtering and pagination
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        
        // Build query
        $query = "SELECT t.*, c.name as category_name, c.type as category_type 
                 FROM transactions t
                 JOIN categories c ON t.category_id = c.id
                 WHERE t.user_id = ?";
        $params = [$user_id];
        
        // Add filters if provided
        if ($category_id) {
            $query .= " AND t.category_id = ?";
            $params[] = $category_id;
        }
        
        if ($start_date) {
            $query .= " AND t.date >= ?";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $query .= " AND t.date <= ?";
            $params[] = $end_date;
        }
        
        // Add ordering and pagination
        $query .= " ORDER BY t.date DESC, t.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // Get transactions
        $transactions = fetchAll($query, $params);
        
        // Get total count for pagination info
        $countQuery = "SELECT COUNT(*) as total FROM transactions t WHERE t.user_id = ?";
        $countParams = [$user_id];
        
        if ($category_id) {
            $countQuery .= " AND t.category_id = ?";
            $countParams[] = $category_id;
        }
        
        if ($start_date) {
            $countQuery .= " AND t.date >= ?";
            $countParams[] = $start_date;
        }
        
        if ($end_date) {
            $countQuery .= " AND t.date <= ?";
            $countParams[] = $end_date;
        }
        
        $totalCount = fetchRow($countQuery, $countParams);
        
        // Return transactions with pagination info
        echo json_encode([
            'data' => $transactions,
            'pagination' => [
                'total' => (int)$totalCount['total'],
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($totalCount['total'] / $limit)
            ]
        ]);
        break;

    case 'POST':
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($data['amount']) || !isset($data['category_id']) || !isset($data['date'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Amount, category, and date are required']);
            exit;
        }
        
        // Validate amount
        $amount = filter_var($data['amount'], FILTER_VALIDATE_FLOAT);
        if ($amount === false || $amount <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Amount must be a positive number']);
            exit;
        }
        
        // Validate category
        $category_id = filter_var($data['category_id'], FILTER_VALIDATE_INT);
        if (!$category_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid category']);
            exit;
        }
        
        // Check if category exists and belongs to user
        $category = fetchRow(
            "SELECT * FROM categories WHERE id = ? AND user_id = ?",
            [$category_id, $user_id]
        );
        
        if (!$category) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            exit;
        }
        
        // Validate date
        $date = $data['date'];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(400);
            echo json_encode(['error' => 'Date must be in YYYY-MM-DD format']);
            exit;
        }
        
        // Get optional fields
        $description = isset($data['description']) ? trim($data['description']) : '';
        
        // Create transaction
        $result = execute(
            "INSERT INTO transactions (user_id, category_id, amount, description, date) 
             VALUES (?, ?, ?, ?, ?)",
            [$user_id, $category_id, $amount, $description, $date]
        );
        
        if ($result) {
            $transaction_id = getLastInsertId();
            
            // Get created transaction with category info
            $transaction = fetchRow(
                "SELECT t.*, c.name as category_name, c.type as category_type 
                 FROM transactions t
                 JOIN categories c ON t.category_id = c.id
                 WHERE t.id = ?",
                [$transaction_id]
            );
            
            http_response_code(201); // Created
            echo json_encode($transaction);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create transaction']);
        }
        break;

    case 'PUT':
        // Get the transaction ID from the URL
        $url_parts = parse_url($_SERVER['REQUEST_URI']);
        parse_str($url_parts['query'] ?? '', $query_params);
        
        if (!isset($query_params['id']) || !is_numeric($query_params['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Transaction ID is required']);
            exit;
        }
        
        $transaction_id = (int)$query_params['id'];
        
        // Check if transaction exists and belongs to the user
        $existingTransaction = fetchRow(
            "SELECT * FROM transactions WHERE id = ? AND user_id = ?",
            [$transaction_id, $user_id]
        );
        
        if (!$existingTransaction) {
            http_response_code(404);
            echo json_encode(['error' => 'Transaction not found']);
            exit;
        }
        
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Prepare update data with existing values as defaults
        $category_id = isset($data['category_id']) ? (int)$data['category_id'] : $existingTransaction['category_id'];
        $amount = isset($data['amount']) ? filter_var($data['amount'], FILTER_VALIDATE_FLOAT) : $existingTransaction['amount'];
        $date = isset($data['date']) ? $data['date'] : $existingTransaction['date'];
        $description = isset($data['description']) ? trim($data['description']) : $existingTransaction['description'];
        
        // Validate amount
        if ($amount === false || $amount <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Amount must be a positive number']);
            exit;
        }
        
        // Validate category if provided
        if (isset($data['category_id'])) {
            $category = fetchRow(
                "SELECT * FROM categories WHERE id = ? AND user_id = ?",
                [$category_id, $user_id]
            );
            
            if (!$category) {
                http_response_code(404);
                echo json_encode(['error' => 'Category not found']);
                exit;
            }
        }
        
        // Validate date if provided
        if (isset($data['date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(400);
            echo json_encode(['error' => 'Date must be in YYYY-MM-DD format']);
            exit;
        }
        
        // Update transaction
        $result = execute(
            "UPDATE transactions SET category_id = ?, amount = ?, description = ?, date = ? WHERE id = ? AND user_id = ?",
            [$category_id, $amount, $description, $date, $transaction_id, $user_id]
        );
        
        if ($result) {
            // Get updated transaction with category info
            $transaction = fetchRow(
                "SELECT t.*, c.name as category_name, c.type as category_type 
                 FROM transactions t
                 JOIN categories c ON t.category_id = c.id
                 WHERE t.id = ?",
                [$transaction_id]
            );
            
            echo json_encode($transaction);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update transaction']);
        }
        break;

    case 'DELETE':
        // Get the transaction ID from the URL
        $url_parts = parse_url($_SERVER['REQUEST_URI']);
        parse_str($url_parts['query'] ?? '', $query_params);
        
        if (!isset($query_params['id']) || !is_numeric($query_params['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Transaction ID is required']);
            exit;
        }
        
        $transaction_id = (int)$query_params['id'];
        
        // Check if transaction exists and belongs to the user
        $existingTransaction = fetchRow(
            "SELECT * FROM transactions WHERE id = ? AND user_id = ?",
            [$transaction_id, $user_id]
        );
        
        if (!$existingTransaction) {
            http_response_code(404);
            echo json_encode(['error' => 'Transaction not found']);
            exit;
        }
        
        // Delete transaction
        $result = execute(
            "DELETE FROM transactions WHERE id = ? AND user_id = ?",
            [$transaction_id, $user_id]
        );
        
        if ($result) {
            echo json_encode(['message' => 'Transaction deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete transaction']);
        }
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>