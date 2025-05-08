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

// Modificar a consulta GET para filtrar produtos por usuário
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        // Build query with filters
        $params = [$_SESSION['user_id']]; // Adicionar user_id como primeiro parâmetro
        $whereClause = "WHERE user_id = ?"; // Filtrar por usuário atual
        
        if (isset($_GET['name']) && !empty($_GET['name'])) {
            $whereClause .= " AND name LIKE ?";
            $params[] = '%' . $_GET['name'] . '%';
        }
        
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $whereClause .= " AND status = ?";
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

// Modificar a criação de produtos para associar ao usuário atual
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['code']) || empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Código e nome do produto são obrigatórios']);
            exit;
        }
        
        // Check if code already exists for this user
        $checkStmt = $pdo->prepare("SELECT id FROM products WHERE code = ? AND user_id = ?");
        $checkStmt->execute([$data['code'], $_SESSION['user_id']]);
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
        
        $stmt = $pdo->prepare("INSERT INTO products (code, name, description, price, quantity, status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['code'],
            $data['name'],
            $description,
            $price,
            $quantity,
            $status,
            $_SESSION['user_id'] // Associar ao usuário atual
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

// Modificar a atualização de produtos para verificar propriedade
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
        
        // Get current product data and verify ownership
        $getStmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
        $getStmt->execute([$data['id'], $_SESSION['user_id']]);
        $product = $getStmt->fetch();
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Produto não encontrado ou não pertence ao usuário atual']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([
            $data['name'],
            isset($data['description']) ? $data['description'] : $product['description'],
            isset($data['price']) ? $data['price'] : $product['price'],
            isset($data['quantity']) ? $data['quantity'] : $product['quantity'],
            isset($data['status']) ? $data['status'] : $product['status'],
            $data['id'],
            $_SESSION['user_id'] // Garantir que apenas o proprietário pode atualizar
        ]);
        
        // Get the updated product
        $getStmt->execute([$data['id'], $_SESSION['user_id']]);
        $updatedProduct = $getStmt->fetch();
        
        echo json_encode(['success' => true, 'product' => $updatedProduct]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao atualizar produto: ' . $e->getMessage()]);
    }
}

// Modificar a exclusão de produtos para verificar propriedade
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do produto é obrigatório']);
            exit;
        }
        
        // Get product data to check business rules and ownership
        $getStmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
        $getStmt->execute([$data['id'], $_SESSION['user_id']]);
        $product = $getStmt->fetch();
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Produto não encontrado ou não pertence ao usuário atual']);
            exit;
        }
        
        // Check business rules
        if ($product['status'] == 1) {
            http_response_code(400);
            echo json_encode(['error' => 'Produtos ativos não podem ser excluídos']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['id'], $_SESSION['user_id']]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao excluir produto: ' . $e->getMessage()]);
    }
}
