<?php

declare(strict_types=1);

function createNotification(PDO $pdo, int $userId, string $message): int
{
    $message = trim($message);
    if ($message === '') {
        return 0;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, message) VALUES (?, ?)'
    );
    $stmt->execute([$userId, $message]);

    return (int) $pdo->lastInsertId();
}

function fetchNotificationsForUser(PDO $pdo, int $userId, int $limit = 50): array
{
    $stmt = $pdo->prepare(
        'SELECT id, user_id, message, is_read, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT ' . max(1, min($limit, 100))
    );
    $stmt->execute([$userId]);

    return array_map('formatNotificationRow', $stmt->fetchAll());
}

function formatNotificationRow(array $row): array
{
    return [
        'id'        => (int) $row['id'],
        'userId'    => (int) $row['user_id'],
        'message'   => $row['message'],
        'isRead'    => (bool) $row['is_read'],
        'createdAt' => $row['created_at'],
    ];
}

function markNotificationRead(PDO $pdo, int $userId, int $notificationId): bool
{
    $stmt = $pdo->prepare(
        'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
    );
    $stmt->execute([$notificationId, $userId]);

    return $stmt->rowCount() > 0;
}
