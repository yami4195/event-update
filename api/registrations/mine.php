<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/events.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = requireLogin();

$pdo = getDb();
$stmt = $pdo->prepare(
    'SELECT e.*, u.name AS organizer_name, u.email AS organizer_email,
            (SELECT COUNT(*) FROM registrations r2 WHERE r2.event_id = e.id) AS registration_count
     FROM registrations r
     INNER JOIN events e ON e.id = r.event_id
     INNER JOIN users u ON u.id = e.organizer_id
     WHERE r.user_id = ?
     ORDER BY e.event_date ASC, e.event_time ASC'
);
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

$events = array_map('formatEventRow', $rows);
$ids = array_map(static fn (array $e): int => (int) $e['id'], $events);

jsonResponse([
    'success'          => true,
    'registeredIds'    => $ids,
    'registeredEvents' => $events,
]);
