<?php

declare(strict_types=1);

function isValidRating(int $rating): bool
{
    return $rating >= 1 && $rating <= 5;
}

function formatFeedbackRow(array $row): array
{
    return [
        'id'        => (int) $row['id'],
        'userId'    => (int) $row['user_id'],
        'eventId'   => (int) $row['event_id'],
        'userName'  => $row['user_name'] ?? '',
        'rating'    => (int) $row['rating'],
        'comment'   => $row['comment'] ?? '',
        'createdAt' => $row['created_at'],
    ];
}

function fetchFeedbackForEvent(PDO $pdo, int $eventId): array
{
    $stmt = $pdo->prepare(
        'SELECT f.*, u.name AS user_name
         FROM feedback f
         INNER JOIN users u ON u.id = f.user_id
         WHERE f.event_id = ?
         ORDER BY f.created_at DESC'
    );
    $stmt->execute([$eventId]);

    return array_map('formatFeedbackRow', $stmt->fetchAll());
}
