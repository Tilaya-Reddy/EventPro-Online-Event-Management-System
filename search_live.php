<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->
<?php
/** search_live.php - JSON endpoint used by the live search box (js/app.js). */
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Protected page: guests get a JSON error instead of a redirect
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in.']);
    exit;
}

$q = clean_text($_GET['q'] ?? '');
$q = mb_substr($q, 0, 100);
if ($q === '') {
    echo json_encode(['users' => [], 'events' => []]);
    exit;
}

$found = search_all($q, (int) $_SESSION['user_id']);

// Only send the fields the page needs
$users = array_map(fn ($u) => [
    'id'     => (int) $u['id'],
    'name'   => $u['full_name'],
    'email'  => $u['email'],
    'avatar' => avatar_url($u),
], $found['users']);

$events = array_map(fn ($ev) => [
    'title'    => $ev['title'],
    'category' => $ev['category'],
    'when'     => format_dt($ev['event_date']),
    'location' => $ev['location'],
    'host'     => $ev['host'],
    'host_url' => (int) $ev['user_id'] === (int) $_SESSION['user_id']
                    ? 'profile.php' : 'user_view.php?id=' . (int) $ev['user_id'],
], $found['events']);

echo json_encode(['users' => $users, 'events' => $events], JSON_UNESCAPED_UNICODE);
