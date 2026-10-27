<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->

<?php
/** logout.php - ends the session. POST only, protected by a CSRF token. */
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
csrf_verify();

logout_user();                         // ends the session and starts a fresh empty one
flash('success', 'You have been logged out.');
redirect('login.php');
