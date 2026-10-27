<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->

<?php

// ---- Database ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'eventpro');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---- Application ----
define('APP_NAME', 'EventPro');
define('SESSION_TIMEOUT', 1800);            // log out after 30 minutes of inactivity
define('RESET_TOKEN_LIFETIME', 1800);       // password reset links last 30 minutes

// ---- File uploads (profile pictures) ----
define('UPLOAD_DIR', __DIR__ . '/../uploads/avatars/');   // file-system path
define('UPLOAD_URL', 'uploads/avatars/');                 // browser path
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024);              // 2 MB

// Make every date/time use the same timezone
date_default_timezone_set('Africa/Johannesburg');

// Never show raw PHP errors to visitors (log them instead)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
