<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/events.php';

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

$stmt = $pdo->prepare(
    'SELECT u.name, u.email, r.registered_at
     FROM registrations r
     INNER JOIN users u ON u.id = r.user_id
     WHERE r.event_id = ?
     ORDER BY r.registered_at DESC'
);
$stmt->execute([$eventId]);
$rows = $stmt->fetchAll();

$registrations = array_map(static function (array $row): array {
    return [
        'name'             => $row['name'],
        'email'            => $row['email'],
        'registrationDate' => substr((string) $row['registered_at'], 0, 10),
    ];
}, $rows);

jsonResponse([
    'success'       => true,
    'eventId'       => $eventId,
    'eventTitle'    => $event['title'],
    'registrations' => $registrations,
]);
