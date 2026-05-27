<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/notifications.php';

requirePost();

$user = requireLogin();
$pdo = getDb();
$stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
$stmt->execute([$user['id']]);

jsonResponse([
    'success' => true,
    'message' => 'All notifications marked as read.',
    'updated' => $stmt->rowCount(),
]);
