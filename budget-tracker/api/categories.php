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
        // Get all categories for the user
        $categories = fetchAll(
            "SELECT * FROM categories WHERE user_id = ? ORDER BY name ASC",
            [$user_id]
        );
        echo json_encode($categories);
        break;

    case 'POST':
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($data['name']) || empty(trim($data['name']))) {
            http_response_code(400);
            echo json_encode(['error' => 'Category name is required']);
            exit;
        }

        $name = trim($data['name']);
        $type = isset($data['type']) ? trim($data['type']) : 'expense'; // Default to expense
        
        // Validate type
        if (!in_array($type, ['income', 'expense'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Category type must be either "income" or "expense"']);
            exit;
        }
        
        // Check if category already exists
        $existingCategory = fetchRow(
            "SELECT * FROM categories WHERE user_id = ? AND name = ?",
            [$user_id, $name]
        );
        
        if ($existingCategory) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'Category already exists']);
            exit;
        }
        
        // Create new category
        $result = execute(
            "INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)",
            [$user_id, $name, $type]
        );
        
        if ($result) {
            $categoryId = getLastInsertId();
            $newCategory = [
                'id' => $categoryId,
                'user_id' => $user_id,
                'name' => $name,
                'type' => $type
            ];
            http_response_code(201); // Created
            echo json_encode($newCategory);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create category']);
        }
        break;

    case 'PUT':
        // Get the category ID from the URL
        $url_parts = parse_url($_SERVER['REQUEST_URI']);
        parse_str($url_parts['query'] ?? '', $query_params);
        
        if (!isset($query_params['id']) || !is_numeric($query_params['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Category ID is required']);
            exit;
        }
        
        $category_id = (int)$query_params['id'];
        
        // Check if category exists and belongs to the user
        $existingCategory = fetchRow(
            "SELECT * FROM categories WHERE id = ? AND user_id = ?",
            [$category_id, $user_id]
        );
        
        if (!$existingCategory) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            exit;
        }
        
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($data['name']) || empty(trim($data['name']))) {
            http_response_code(400);
            echo json_encode(['error' => 'Category name is required']);
            exit;
        }
        
        $name = trim($data['name']);
        $type = isset($data['type']) ? trim($data['type']) : $existingCategory['type']; // Keep existing type if not provided
        
        // Validate type
        if (!in_array($type, ['income', 'expense'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Category type must be either "income" or "expense"']);
            exit;
        }
        
        // Check if another category with the same name exists
        $duplicateCategory = fetchRow(
            "SELECT * FROM categories WHERE user_id = ? AND name = ? AND id != ?",
            [$user_id, $name, $category_id]
        );
        
        if ($duplicateCategory) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'Another category with this name already exists']);
            exit;
        }
        
        // Update category
        $result = execute(
            "UPDATE categories SET name = ?, type = ? WHERE id = ? AND user_id = ?",
            [$name, $type, $category_id, $user_id]
        );
        
        if ($result) {
            $updatedCategory = [
                'id' => $category_id,
                'user_id' => $user_id,
                'name' => $name,
                'type' => $type
            ];
            echo json_encode($updatedCategory);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update category']);
        }
        break;

    case 'DELETE':
        // Get the category ID from the URL
        $url_parts = parse_url($_SERVER['REQUEST_URI']);
        parse_str($url_parts['query'] ?? '', $query_params);
        
        if (!isset($query_params['id']) || !is_numeric($query_params['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Category ID is required']);
            exit;
        }
        
        $category_id = (int)$query_params['id'];
        
        // Check if category exists and belongs to the user
        $existingCategory = fetchRow(
            "SELECT * FROM categories WHERE id = ? AND user_id = ?",
            [$category_id, $user_id]
        );
        
        if (!$existingCategory) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            exit;
        }
        
        // Check if category is used in transactions
        $usedInTransactions = fetchRow(
            "SELECT COUNT(*) as count FROM transactions WHERE category_id = ?",
            [$category_id]
        );
        
        if ($usedInTransactions && $usedInTransactions['count'] > 0) {
            http_response_code(409); // Conflict
            echo json_encode([
                'error' => 'Cannot delete category that is used in transactions',
                'count' => $usedInTransactions['count']
            ]);
            exit;
        }
        
        // Delete category
        $result = execute(
            "DELETE FROM categories WHERE id = ? AND user_id = ?",
            [$category_id, $user_id]
        );
        
        if ($result) {
            echo json_encode(['message' => 'Category deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete category']);
        }
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>