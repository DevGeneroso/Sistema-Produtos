<?php
session_start();
require_once '../config/database.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json');

// Get products with pagination and filtering
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        // Build query with filters
        $params = [];
        $whereClause = "";
        
        if (isset($_GET['name']) && !empty($_GET['name'])) {
            $whereClause .= (empty($whereClause) ? "WHERE " : " AND ") . "name LIKE ?";
            $params[] = '%' . $_GET['name'] . '%';
        }
        
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $whereClause .= (empty($whereClause) ? "WHERE " : " AND ") . "status = ?";
            $params[] = $_GET['status'];
        }
        
        // Count total records for pagination
        $countQuery = "SELECT COUNT(*) as total FROM products " . $whereClause;
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetch()['total'];
        
        // Get products with pagination
        $query = "SELECT * FROM products " . $whereClause . " ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($query);
        
        // Add pagination parameters
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        echo json_encode([
            'products' => $products,
            'pagination' => [
                'total' => $totalRecords,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($totalRecords / $limit)
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
    }
}

// Create new product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['code']) || empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Código e nome do produto são obrigatórios']);
            exit;
        }
        
        // Check if code already exists
        $checkStmt = $pdo->prepare("SELECT id FROM products WHERE code = ?");
        $checkStmt->execute([$data['code']]);
        if ($checkStmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Código do produto já existe']);
            exit;
        }
        
        // Set default values
        $price = isset($data['price']) ? $data['price'] : 0.00;
        $quantity = isset($data['quantity']) ? $data['quantity'] : 0;
        $status = isset($data['status']) ? $data['status'] : 1;
        $description = isset($data['description']) ? $data['description'] : '';
        
        $stmt = $pdo->prepare("INSERT INTO products (code, name, description, price, quantity, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['code'],
            $data['name'],
            $description,
            $price,
            $quantity,
            $status
        ]);
        
        $productId = $pdo->lastInsertId();
        
        // Get the newly created product
        $getStmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $getStmt->execute([$productId]);
        $product = $getStmt->fetch();
        
        echo json_encode(['success' => true, 'product' => $product]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao criar produto: ' . $e->getMessage()]);
    }
}

// Update product
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do produto é obrigatório']);
            exit;
        }
        
        // Validate required fields
        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome do produto é obrigatório']);
            exit;
        }
        
        // Get current product data
        $getStmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $getStmt->execute([$data['id']]);
        $product = $getStmt->fetch();
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Produto não encontrado']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, status = ? WHERE id = ?");
        $stmt->execute([
            $data['name'],
            isset($data['description']) ? $data['description'] : $product['description'],
            isset($data['price']) ? $data['price'] : $product['price'],
            isset($data['quantity']) ? $data['quantity'] : $product['quantity'],
            isset($data['status']) ? $data['status'] : $product['status'],
            $data['id']
        ]);
        
        // Get the updated product
        $getStmt->execute([$data['id']]);
        $updatedProduct = $getStmt->fetch();
        
        echo json_encode(['success' => true, 'product' => $updatedProduct]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao atualizar produto: ' . $e->getMessage()]);
    }
}

// Delete product
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do produto é obrigatório']);
            exit;
        }
        
        // Get product data to check business rules
        $getStmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $getStmt->execute([$data['id']]);
        $product = $getStmt->fetch();
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Produto não encontrado']);
            exit;
        }
        
        // Check business rules
        if ($product['status'] == 1) {
            http_response_code(400);
            echo json_encode(['error' => 'Produtos ativos não podem ser excluídos']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao excluir produto: ' . $e->getMessage()]);
    }
}
