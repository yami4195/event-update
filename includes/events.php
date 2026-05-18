<?php

declare(strict_types=1);

require_once __DIR__ . '/upload.php';

const EVENT_CATEGORIES = [
    'Seminar / Academic Talk',
    'Training',
    'Club Programs',
    'Sport Events',
    'Cultural Events',
    'Hackathons',
];

const EVENT_STATUSES = ['draft', 'published', 'cancelled'];

function requireOrganizer(): array
{
    $user = requireLogin();
    if ($user['role'] !== 'organizer') {
        jsonResponse(['success' => false, 'message' => 'Organizer access required.'], 403);
    }
    return $user;
}

function requireStudent(): array
{
    $user = requireLogin();
    if ($user['role'] !== 'student') {
        jsonResponse(['success' => false, 'message' => 'Student access required.'], 403);
    }
    return $user;
}

function isValidCategory(string $category): bool
{
    return in_array($category, EVENT_CATEGORIES, true);
}

function isValidEventStatus(string $status): bool
{
    return in_array($status, EVENT_STATUSES, true);
}

function formatEventTime(string $time): string
{
    return strlen($time) >= 5 ? substr($time, 0, 5) : $time;
}

function resolveEventImageUrl(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    if (str_starts_with($path, '/')) {
        return $path;
    }

    return BASE_URL . '/' . ltrim($path, '/');
}

function formatEventRow(array $row): array
{
    $capacity = $row['capacity'] !== null ? (int) $row['capacity'] : null;
    $registrationCount = (int) ($row['registration_count'] ?? 0);

    return [
        'id'                => (int) $row['id'],
        'title'             => $row['title'],
        'description'       => $row['description'],
        'category'          => $row['category'],
        'date'              => $row['event_date'],
        'time'              => formatEventTime($row['event_time']),
        'location'          => $row['location'],
        'imageUrl'          => resolveEventImageUrl($row['image_path'] ?: null),
        'capacity'          => $capacity,
        'status'            => $row['status'],
        'organizerId'       => (int) $row['organizer_id'],
        'organizerName'     => $row['organizer_name'] ?? '',
        'organizerEmail'    => $row['organizer_email'] ?? '',
        'registrationCount' => $registrationCount,
        'spotsLeft'         => $capacity !== null ? max(0, $capacity - $registrationCount) : null,
        'isFull'            => $capacity !== null && $registrationCount >= $capacity,
    ];
}

function fetchEventById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT e.*, u.name AS organizer_name, u.email AS organizer_email,
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registration_count
         FROM events e
         INNER JOIN users u ON u.id = e.organizer_id
         WHERE e.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function assertOrganizerOwnsEvent(array $user, array $event): void
{
    if ((int) $event['organizer_id'] !== (int) $user['id']) {
        jsonResponse(['success' => false, 'message' => 'You can only manage your own events.'], 403);
    }
}

function readEventRequestData(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    return readJsonBody();
}

function parseCapacity(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    $capacity = (int) $value;
    if ($capacity < 1) {
        jsonResponse(['success' => false, 'message' => 'Capacity must be at least 1.'], 422);
    }

    return $capacity;
}

function resolveEventImagePath(array $input, ?array $file, ?string $existingPath = null): ?string
{
    if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            return saveUploadedEventImage($file);
        } catch (Throwable $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    $imageUrl = trim((string) ($input['imageUrl'] ?? ''));
    if ($imageUrl !== '') {
        return $imageUrl;
    }

    if ($existingPath !== null && ($input['keepImage'] ?? '') === '1') {
        return $existingPath;
    }

    return $existingPath;
}

function validateEventFields(array $input): array
{
    $title = trim((string) ($input['title'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $category = trim((string) ($input['category'] ?? ''));
    $date = trim((string) ($input['date'] ?? ''));
    $time = trim((string) ($input['time'] ?? ''));
    $location = trim((string) ($input['location'] ?? ''));
    $status = trim((string) ($input['status'] ?? 'published'));

    if ($title === '' || $description === '' || $location === '') {
        jsonResponse(['success' => false, 'message' => 'Please fill all required fields.'], 422);
    }

    if (!isValidCategory($category)) {
        jsonResponse(['success' => false, 'message' => 'Invalid category.'], 422);
    }

    if ($date === '' || $time === '') {
        jsonResponse(['success' => false, 'message' => 'Date and time are required.'], 422);
    }

    if (!isValidEventStatus($status)) {
        jsonResponse(['success' => false, 'message' => 'Invalid event status.'], 422);
    }

    $eventStart = strtotime($date . ' ' . $time);
    if ($eventStart !== false && $eventStart < time()) {
        jsonResponse(['success' => false, 'message' => 'Event date and time must be in the future.'], 422);
    }

    return [
        'title'       => $title,
        'description' => $description,
        'category'    => $category,
        'date'        => $date,
        'time'        => $time,
        'location'    => $location,
        'status'      => $status,
        'capacity'    => parseCapacity($input['capacity'] ?? null),
    ];
}
