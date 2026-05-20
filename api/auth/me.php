<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = null;

if (!empty($_SESSION['user_id'])) {
    $pdo = getDb();
    $stmt = $pdo->prepare(
        'SELECT id, name, email, role, status FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->execute([(int) $_SESSION['user_id']]);
    $row = $stmt->fetch();

    if ($row && $row['status'] === 'active') {
        loginUserSession($row);
        $user = publicUser($row);
    } else {
        clearUserSession();
    }
}

jsonResponse([
    'success' => true,
    'user'    => $user,
]);
