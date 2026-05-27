<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/events.php';

requirePost();

$user = requireOrganizer();
$input = readEventRequestData();
$pdo = getDb();
$fields = validateEventFields($pdo, $input);
$imagePath = resolveEventImagePath($input, $_FILES['image'] ?? null);

$stmt = $pdo->prepare(
    'INSERT INTO events (organizer_id, title, description, category_id, event_date, event_time, location, image_path, capacity, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $user['id'],
    $fields['title'],
    $fields['description'],
    $fields['categoryId'],
    $fields['date'],
    $fields['time'],
    $fields['location'],
    $imagePath,
    $fields['capacity'],
    $fields['status'],
]);

$id = (int) $pdo->lastInsertId();
$row = fetchEventById($pdo, $id);

jsonResponse([
    'success' => true,
    'message' => 'Event created successfully.',
    'event'   => formatEventRow($row),
]);
