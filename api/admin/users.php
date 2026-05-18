<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/admin.php';

$pdo = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAdmin();

    $stmt = $pdo->query(
        'SELECT id, name, email, role, status, created_at
         FROM users
         ORDER BY created_at DESC'
    );
    $users = array_map(static function (array $row): array {
        return [
            'id'        => (int) $row['id'],
            'name'      => $row['name'],
            'email'     => $row['email'],
            'role'      => $row['role'],
            'status'    => $row['status'],
            'createdAt' => $row['created_at'],
        ];
    }, $stmt->fetchAll());

    jsonResponse(['success' => true, 'users' => $users]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAdmin();
    $body = readJsonBody();
    $userId = (int) ($body['userId'] ?? 0);
    $status = trim((string) ($body['status'] ?? ''));

    if ($userId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid user id.'], 422);
    }

    if (!in_array($status, ['active', 'pending', 'suspended'], true)) {
        jsonResponse(['success' => false, 'message' => 'Invalid status.'], 422);
    }

    $current = $pdo->prepare('SELECT id, role FROM users WHERE id = ? LIMIT 1');
    $current->execute([$userId]);
    $user = $current->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
    }

    if ($user['role'] === 'admin') {
        jsonResponse(['success' => false, 'message' => 'Cannot change admin account status.'], 403);
    }

    $update = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
    $update->execute([$status, $userId]);

    jsonResponse([
        'success' => true,
        'message' => 'User status updated.',
    ]);
}

jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
