<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->

<?php
/** event_delete.php - remove an event (CRUD: Delete). POST + CSRF token + owner check. */
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
csrf_verify();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id) {
    $st = db()->prepare('DELETE FROM events WHERE id = ? AND user_id = ?');   // owner only
    $st->execute([$id, $_SESSION['user_id']]);
    if ($st->rowCount() === 1) {
        flash('success', 'Event deleted.');
        redirect('dashboard.php');
    }
}
flash('error', 'Event not found, or you do not have permission to delete it.');
redirect('dashboard.php');
