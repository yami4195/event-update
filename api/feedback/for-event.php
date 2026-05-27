<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/events.php';
require_once dirname(__DIR__, 2) . '/includes/feedback.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = requireOrganizer();
$eventId = (int) ($_GET['event_id'] ?? 0);

if ($eventId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid event id.'], 422);
}

$pdo = getDb();
$event = fetchEventById($pdo, $eventId);

if (!$event) {
    jsonResponse(['success' => false, 'message' => 'Event not found.'], 404);
}

assertOrganizerOwnsEvent($user, $event);

$feedback = fetchFeedbackForEvent($pdo, $eventId);
$count = count($feedback);
$averageRating = $count > 0
    ? round(array_sum(array_column($feedback, 'rating')) / $count, 1)
    : null;

jsonResponse([
    'success'       => true,
    'eventId'       => $eventId,
    'eventTitle'    => $event['title'],
    'feedback'      => $feedback,
    'count'         => $count,
    'averageRating' => $averageRating,
]);
