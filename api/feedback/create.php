<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/events.php';
require_once dirname(__DIR__, 2) . '/includes/feedback.php';

requirePost();

$user = requireStudent();
$body = readJsonBody();
$eventId = (int) ($body['eventId'] ?? 0);
$rating = (int) ($body['rating'] ?? 0);
$comment = trim((string) ($body['comment'] ?? ''));

if ($eventId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid event id.'], 422);
}

if (!isValidRating($rating)) {
    jsonResponse(['success' => false, 'message' => 'Rating must be between 1 and 5.'], 422);
}

$pdo = getDb();
$event = fetchEventById($pdo, $eventId);

if (!$event) {
    jsonResponse(['success' => false, 'message' => 'Event not found.'], 404);
}

$registered = $pdo->prepare(
    'SELECT id FROM registrations WHERE event_id = ? AND user_id = ? LIMIT 1'
);
$registered->execute([$eventId, $user['id']]);
if (!$registered->fetch()) {
    jsonResponse(['success' => false, 'message' => 'You must register for this event before leaving feedback.'], 403);
}

$eventStart = strtotime($event['event_date'] . ' ' . $event['event_time']);
if ($event['status'] === 'cancelled') {
    jsonResponse(['success' => false, 'message' => 'Cancelled events cannot receive feedback.'], 422);
}
if ($eventStart !== false && $eventStart >= time()) {
    jsonResponse(['success' => false, 'message' => 'Feedback is available after the event takes place.'], 422);
}

try {
    $insert = $pdo->prepare(
        'INSERT INTO feedback (user_id, event_id, rating, comment) VALUES (?, ?, ?, ?)'
    );
    $insert->execute([$user['id'], $eventId, $rating, $comment !== '' ? $comment : null]);
} catch (PDOException $e) {
    if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
        jsonResponse(['success' => false, 'message' => 'You have already submitted feedback for this event.'], 409);
    }
    throw $e;
}

jsonResponse([
    'success' => true,
    'message' => 'Thank you for your feedback.',
]);
