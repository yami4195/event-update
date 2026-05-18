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
$delete = $pdo->prepare('DELETE FROM registrations WHERE event_id = ? AND user_id = ?');
$delete->execute([$eventId, $user['id']]);

if ($delete->rowCount() === 0) {
    jsonResponse(['success' => false, 'message' => 'You are not registered for this event.'], 404);
}

jsonResponse([
    'success' => true,
    'message' => 'Registration cancelled.',
]);
