<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $action = $_GET['action'] ?? '';

    // Ensure users table exists and bootstrap default user for first-time setup
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `username` VARCHAR(100) NOT NULL UNIQUE,
      `password_hash` VARCHAR(255) NOT NULL,
      `role` ENUM('staff','admin') NOT NULL DEFAULT 'staff',
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if ($action === 'status' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $isAuth = isset($_SESSION['user']);
        echo json_encode(['authenticated' => $isAuth, 'user' => $isAuth ? $_SESSION['user'] : null]);
        exit;
    }

    if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        $username = trim((string)($payload['username'] ?? ''));
        $password = (string)($payload['password'] ?? '');
        if ($username === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password required']);
            exit;
        }

        // Bootstrap default user if table is empty (store plain password by request)
        $count = (int)$pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
        if ($count === 0) {
            $plain = 'password';
            $ins = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
            $ins->execute(['staff', $plain, 'staff']);
        }

        $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        $isValid = false;
        if ($user) {
            // allow both plaintext match and legacy password_hash() match
            $isValid = ($password === $user['password_hash']) || password_verify($password, (string)$user['password_hash']);
        }
        if (!$user || !$isValid) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            exit;
        }
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        echo json_encode(['ok' => true, 'user' => $user]);
        exit;
    }

    if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}


