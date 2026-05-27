<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/notifications.php';

requirePost();

$user = requireLogin();
$body = readJsonBody();
$notificationId = (int) ($body['notificationId'] ?? 0);

if ($notificationId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid notification id.'], 422);
}

$pdo = getDb();
$updated = markNotificationRead($pdo, $user['id'], $notificationId);

if (!$updated) {
    jsonResponse(['success' => false, 'message' => 'Notification not found.'], 404);
}

jsonResponse([
    'success' => true,
    'message' => 'Notification marked as read.',
]);
