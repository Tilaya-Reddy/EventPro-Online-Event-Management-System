<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->

<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// ---- Secure session start ----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,                                   // cookie dies when browser closes
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),           // only over HTTPS when available
        'httponly' => true,                                // JavaScript cannot read the cookie
        'samesite' => 'Lax',                               // blocks most cross-site requests
    ]);
    session_start();
}

/** Is someone logged in? */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/** Send guests away from protected pages (also enforces the idle timeout). */
function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
        logout_user();
        flash('error', 'You were logged out after a period of inactivity.');
        redirect('login.php');
    }
    $_SESSION['last_activity'] = time();
}

/** Keep logged-in users off the login / register pages. */
function guest_only(): void
{
    if (is_logged_in()) {
        redirect('dashboard.php');
    }
}

/** Fetch the logged-in user's row (cached for the request). */
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (is_logged_in()) {
            $st = db()->prepare('SELECT id, full_name, email, profile_picture, created_at FROM users WHERE id = ?');
            $st->execute([$_SESSION['user_id']]);
            $user = $st->fetch() ?: null;
            if ($user === null) {           // account was deleted while logged in
                logout_user();
            }
        }
    }
    return $user;
}

/** Start an authenticated session. */
function login_user(int $userId): void
{
    session_regenerate_id(true);            // prevents session fixation
    unset($_SESSION['csrf_token']);         // fresh CSRF token for the new session
    $_SESSION['user_id']       = $userId;
    $_SESSION['last_activity'] = time();
}

/**
 * Destroy the current session (log the user out) and start a brand new, empty one.
 * The new session lets us show a flash message on the next page.
 */
function logout_user(): void
{
    $_SESSION = [];
    session_destroy();                       // deletes the old session data on the server
    session_id(session_create_id());         // a new random id...
    session_start();                         // ...so the browser gets a fresh, empty session cookie
    $_SESSION = [];
}
