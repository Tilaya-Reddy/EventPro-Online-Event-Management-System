<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->

<?php
/** event_create.php - add a new event (CRUD: Create). */
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();

$values = ['title' => '', 'description' => '', 'event_date' => '', 'location' => '', 'category' => 'Conference'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$data, $errors] = validate_event($_POST, true);       // new events must be in the future
    $values = array_intersect_key($data, $values);

    if (!$errors) {
        $st = db()->prepare(
            'INSERT INTO events (user_id, title, description, location, category, event_date)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $user['id'], $data['title'], $data['description'] !== '' ? $data['description'] : null,
            $data['location'], $data['category'], $data['sql_date'],
        ]);
        flash('success', 'Event created.');
        redirect('dashboard.php');
    }
}

$pageTitle    = 'Create Event';
$formTitle    = 'Create Event';
$formAction   = 'event_create.php';
$submitLabel  = 'Save Event';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/event_form.php';
require __DIR__ . '/includes/footer.php';
