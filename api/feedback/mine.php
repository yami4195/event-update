<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/feedback.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = requireStudent();
$pdo = getDb();

$stmt = $pdo->prepare(
    'SELECT f.id, f.event_id, f.rating, f.comment, f.created_at, e.title AS event_title
     FROM feedback f
     INNER JOIN events e ON e.id = f.event_id
     WHERE f.user_id = ?
     ORDER BY f.created_at DESC'
);
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

$feedback = array_map(static function (array $row): array {
    return [
        'id'         => (int) $row['id'],
        'eventId'    => (int) $row['event_id'],
        'eventTitle' => $row['event_title'],
        'rating'     => (int) $row['rating'],
        'comment'    => $row['comment'] ?? '',
        'createdAt'  => $row['created_at'],
    ];
}, $rows);

$eventIds = array_map(static fn(array $item): int => $item['eventId'], $feedback);

jsonResponse([
    'success'  => true,
    'feedback' => $feedback,
    'eventIds' => $eventIds,
]);
