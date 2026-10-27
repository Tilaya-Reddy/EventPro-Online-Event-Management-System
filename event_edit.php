<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->
<?php
/** event_edit.php - change an event (CRUD: Update). Only the owner may edit. */
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    flash('error', 'Event not found.');
    redirect('dashboard.php');
}

// "AND user_id = ?" is the access-control check: you cannot load someone else's event
$st = db()->prepare('SELECT * FROM events WHERE id = ? AND user_id = ?');
$st->execute([$id, $user['id']]);
$event = $st->fetch();
if (!$event) {
    http_response_code(404);
    flash('error', 'Event not found, or you do not have permission to edit it.');
    redirect('dashboard.php');
}

$errors = [];
$values = [
    'title'       => $event['title'],
    'description' => (string) $event['description'],
    'event_date'  => date('Y-m-d\TH:i', strtotime($event['event_date'])),
    'location'    => $event['location'],
    'category'    => $event['category'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Only insist on a future date if the user actually changed the date
    $dateChanged = trim((string) ($_POST['event_date'] ?? '')) !== $values['event_date'];
    [$data, $errors] = validate_event($_POST, $dateChanged);
    $values = array_intersect_key($data, $values);

    if (!$errors) {
        $st = db()->prepare(
            'UPDATE events
                SET title = ?, description = ?, location = ?, category = ?, event_date = ?
              WHERE id = ? AND user_id = ?'
        );
        $st->execute([
            $data['title'], $data['description'] !== '' ? $data['description'] : null,
            $data['location'], $data['category'], $data['sql_date'], $id, $user['id'],
        ]);
        flash('success', 'Event updated.');
        redirect('dashboard.php');
    }
}

$pageTitle    = 'Edit Event';
$formTitle    = 'Edit Event';
$formAction   = 'event_edit.php?id=' . $id;
$submitLabel  = 'Save Changes';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/event_form.php';
require __DIR__ . '/includes/footer.php';
