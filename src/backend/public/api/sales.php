<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

const PROMPTPAY_NUMBER = '0837735493';

function build_promptpay_qr(float $amount): string {
    $formatted = number_format($amount, 2, '.', '');
    return 'https://promptpay.io/' . PROMPTPAY_NUMBER . '/' . $formatted;
}

try {
    $action = $_GET['action'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'latest_total') {
        $stmt = $pdo->query('SELECT total_amount FROM sales ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch();
        $total = $row ? (float)$row['total_amount'] : 0.00;
        echo json_encode([
            'total_amount' => $total,
            'promptpay_url' => build_promptpay_qr($total),
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'detail') {
        $saleId = (int)($_GET['id'] ?? 0);
        if ($saleId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid sale id']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id, sale_date, total_amount FROM sales WHERE id = ?');
        $stmt->execute([$saleId]);
        $sale = $stmt->fetch();
        if (!$sale) {
            http_response_code(404);
            echo json_encode(['error' => 'Sale not found']);
            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT si.id, si.product_id, p.name, si.quantity, si.price, (si.quantity * si.price) AS line_total
             FROM sale_items si
             JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = ?'
        );
        $stmt->execute([$saleId]);
        $items = $stmt->fetchAll();

        echo json_encode([
            'sale' => $sale,
            'items' => $items,
            'promptpay_url' => build_promptpay_qr((float)$sale['total_amount'])
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload) || !isset($payload['items']) || !is_array($payload['items'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
            exit;
        }

        $items = $payload['items']; // each: {product_id, quantity}
        if (count($items) === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Cart is empty']);
            exit;
        }

        $pdo->beginTransaction();
        try {
            $totalAmount = 0.00;
            $productCache = [];

            $getProductStmt = $pdo->prepare('SELECT id, name, price, stock FROM products WHERE id = ? FOR UPDATE');
            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 0);
                if ($productId <= 0 || $quantity <= 0) {
                    throw new RuntimeException('Invalid item in cart');
                }

                $getProductStmt->execute([$productId]);
                $product = $getProductStmt->fetch();
                if (!$product) {
                    throw new RuntimeException('Product not found: ' . $productId);
                }
                if ((int)$product['stock'] < $quantity) {
                    throw new RuntimeException('Not enough stock for product: ' . $product['name']);
                }

                $lineTotal = $quantity * (float)$product['price'];
                $totalAmount += $lineTotal;
                $productCache[$productId] = $product;
            }

            // Insert sale
            $insertSaleStmt = $pdo->prepare('INSERT INTO sales (sale_date, total_amount) VALUES (NOW(), ?)');
            $insertSaleStmt->execute([number_format($totalAmount, 2, '.', '')]);
            $saleId = (int)$pdo->lastInsertId();

            // Insert items and update stock
            $insertItemStmt = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
            $updateStockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');

            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];
                $product = $productCache[$productId];
                $price = (float)$product['price'];

                $insertItemStmt->execute([$saleId, $productId, $quantity, number_format($price, 2, '.', '')]);
                $updateStockStmt->execute([$quantity, $productId]);
            }

            $pdo->commit();

            echo json_encode([
                'sale_id' => $saleId,
                'total_amount' => (float)number_format($totalAmount, 2, '.', ''),
                'promptpay_url' => build_promptpay_qr((float)$totalAmount),
            ]);
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

