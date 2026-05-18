<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/events.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$scope = $_GET['scope'] ?? 'public';
$category = trim((string) ($_GET['category'] ?? 'all'));
$when = $_GET['when'] ?? 'all';

$pdo = getDb();
$params = [];
$sql = 'SELECT e.*, u.name AS organizer_name, u.email AS organizer_email,
               (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registration_count
        FROM events e
        INNER JOIN users u ON u.id = e.organizer_id
        WHERE 1=1';

if ($scope === 'mine') {
    $user = requireOrganizer();
    $sql .= ' AND e.organizer_id = ?';
    $params[] = $user['id'];
} else {
    $sql .= " AND e.status = 'published'";
}

if ($category !== '' && $category !== 'all') {
    $sql .= ' AND e.category = ?';
    $params[] = $category;
}

if ($when === 'upcoming') {
    $sql .= ' AND TIMESTAMP(e.event_date, e.event_time) >= NOW()';
} elseif ($when === 'past') {
    $sql .= ' AND TIMESTAMP(e.event_date, e.event_time) < NOW()';
}

$sql .= ' ORDER BY e.event_date ASC, e.event_time ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$events = array_map('formatEventRow', $rows);

jsonResponse([
    'success' => true,
    'events'  => $events,
]);
