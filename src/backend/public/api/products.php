<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->query('SELECT id, name, price, stock FROM products ORDER BY name ASC');
        $products = $stmt->fetchAll();
        echo json_encode(['data' => $products]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON body']);
            exit;
        }

        $name = trim((string)($payload['name'] ?? ''));
        $price = (float)($payload['price'] ?? 0);
        $stock = (int)($payload['stock'] ?? 0);

        if ($name === '' || $price <= 0 || $stock < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Name, price (>0) and stock (>=0) are required']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO products (name, price, stock) VALUES (?, ?, ?)');
        $stmt->execute([$name, number_format($price, 2, '.', ''), $stock]);
        $id = (int)$pdo->lastInsertId();
        echo json_encode(['id' => $id, 'name' => $name, 'price' => (float)number_format($price, 2, '.', ''), 'stock' => $stock]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON body']);
            exit;
        }
        $id = (int)($payload['id'] ?? 0);
        $name = trim((string)($payload['name'] ?? ''));
        $price = (float)($payload['price'] ?? -1);
        $stock = (int)($payload['stock'] ?? -1);
        if ($id <= 0 || $name === '' || $price < 0 || $stock < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid fields']);
            exit;
        }
        $stmt = $pdo->prepare('UPDATE products SET name = ?, price = ?, stock = ? WHERE id = ?');
        $stmt->execute([$name, number_format($price, 2, '.', ''), $stock, $id]);
        echo json_encode(['id' => $id, 'name' => $name, 'price' => (float)number_format($price, 2, '.', ''), 'stock' => $stock]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $payload = json_decode(file_get_contents('php://input'), true);
        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid id']);
            exit;
        }
        try {
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$id]);
        } catch (Throwable $e) {
            // likely foreign key constraint
            http_response_code(409);
            echo json_encode(['error' => 'Cannot delete product that has sales history']);
            exit;
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

