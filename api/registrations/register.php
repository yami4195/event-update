<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/events.php';

requirePost();

$user = requireStudent();
$body = readJsonBody();
$eventId = (int) ($body['eventId'] ?? 0);

if ($eventId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid event id.'], 422);
}

$pdo = getDb();
$event = fetchEventById($pdo, $eventId);

if (!$event || $event['status'] !== 'published') {
    jsonResponse(['success' => false, 'message' => 'Event not available for registration.'], 404);
}

$eventStart = strtotime($event['event_date'] . ' ' . $event['event_time']);
if ($eventStart !== false && $eventStart < time()) {
    jsonResponse(['success' => false, 'message' => 'This event has already passed.'], 422);
}

$exists = $pdo->prepare(
    'SELECT id FROM registrations WHERE event_id = ? AND user_id = ? LIMIT 1'
);
$exists->execute([$eventId, $user['id']]);
if ($exists->fetch()) {
    jsonResponse(['success' => false, 'message' => 'You are already registered for this event.'], 409);
}

if ($event['capacity'] !== null) {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE event_id = ?');
    $countStmt->execute([$eventId]);
    $count = (int) $countStmt->fetchColumn();
    if ($count >= (int) $event['capacity']) {
        jsonResponse(['success' => false, 'message' => 'This event is full.'], 422);
    }
}

$insert = $pdo->prepare('INSERT INTO registrations (event_id, user_id) VALUES (?, ?)');
$insert->execute([$eventId, $user['id']]);

jsonResponse([
    'success' => true,
    'message' => 'Registration successful.',
]);
