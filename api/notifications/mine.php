<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/notifications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = requireLogin();
$pdo = getDb();
$notifications = fetchNotificationsForUser($pdo, $user['id']);
$unreadCount = count(array_filter($notifications, static fn(array $n): bool => !$n['isRead']));

jsonResponse([
    'success'       => true,
    'notifications' => $notifications,
    'unreadCount'   => $unreadCount,
]);
