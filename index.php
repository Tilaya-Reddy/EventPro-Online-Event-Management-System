<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->

<?php
// Entry point: send visitors to the right place.
require_once __DIR__ . '/includes/auth.php';
redirect(is_logged_in() ? 'dashboard.php' : 'login.php');

//http://localhost/402504107-IPR-ASSIGNMENT/index.php
