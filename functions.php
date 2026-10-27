<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->
<?php

const EVENT_CATEGORIES = ['Conference', 'Education', 'Workshop', 'Networking', 'Social', 'Music', 'Sports', 'Other'];

/* ------------------------------------------------------------------
 * Output + navigation
 * ------------------------------------------------------------------ */

/** Escape text before printing it in HTML (XSS protection). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Store a one-time message to show on the next page. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

/** Read and clear all pending flash messages. */
function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Human friendly date, e.g. "Fri 13 Feb 2026, 20:22". */
function format_dt(string $sqlDate): string
{
    return date('D j M Y, H:i', strtotime($sqlDate));
}

/* ------------------------------------------------------------------
 * CSRF protection (stops other websites submitting our forms)
 * ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Call at the top of every POST handler. */
function csrf_verify(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(419);
        exit('Your form expired or was invalid. Go back, refresh the page and try again.');
    }
}

/* ------------------------------------------------------------------
 * Input cleaning + validation
 * ------------------------------------------------------------------ */

/** Single-line text: strip control characters, collapse spaces, trim. */
function clean_text($value): string
{
    $value = is_string($value) ? $value : '';
    if (!mb_check_encoding($value, 'UTF-8')) {
        return '';
    }
    $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value);
    return trim(preg_replace('/\s+/u', ' ', $value));
}

/** Multi-line text (descriptions, messages): keep line breaks, strip other control characters. */
function clean_multiline($value): string
{
    $value = is_string($value) ? $value : '';
    if (!mb_check_encoding($value, 'UTF-8')) {
        return '';
    }
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    return trim($value);
}

function validate_full_name(string $name): ?string
{
    $len = mb_strlen($name);
    if ($len < 2 || $len > 100) {
        return 'Name must be between 2 and 100 characters.';
    }
    if (!preg_match("/^[\p{L}\p{M}0-9 '.\-()]+$/u", $name)) {
        return 'Name may only contain letters, numbers, spaces and . - \' ( )';
    }
    return null;
}

function validate_email(string $email): ?string
{
    if ($email === '' || mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Enter a valid email address.';
    }
    return null;
}

function validate_password(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (strlen($password) > 72) {                 // bcrypt only uses the first 72 bytes
        return 'Password must be 72 characters or fewer.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'Password must contain at least one letter and one number.';
    }
    return null;
}

/**
 * Validate the event form. Returns [cleanData, errors].
 * $requireFuture: new events (and changed dates) must be in the future.
 */
function validate_event(array $input, bool $requireFuture): array
{
    $data = [
        'title'       => clean_text($input['title'] ?? ''),
        'description' => clean_multiline($input['description'] ?? ''),
        'event_date'  => trim((string) ($input['event_date'] ?? '')),
        'location'    => clean_text($input['location'] ?? ''),
        'category'    => clean_text($input['category'] ?? ''),
        'sql_date'    => null,
    ];
    $errors = [];

    $len = mb_strlen($data['title']);
    if ($len < 3 || $len > 150) {
        $errors['title'] = 'Event name must be between 3 and 150 characters.';
    }
    if (mb_strlen($data['description']) > 2000) {
        $errors['description'] = 'Description must be 2000 characters or fewer.';
    }
    $len = mb_strlen($data['location']);
    if ($len < 2 || $len > 150) {
        $errors['location'] = 'Location must be between 2 and 150 characters.';
    }
    if (!in_array($data['category'], EVENT_CATEGORIES, true)) {
        $errors['category'] = 'Choose a category from the list.';
    }

    // <input type="datetime-local"> sends "2026-02-13T20:22"
    $dt = false;
    foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s'] as $format) {
        $try = DateTime::createFromFormat($format, $data['event_date']);
        $err = DateTime::getLastErrors();
        if ($try && (!$err || ($err['warning_count'] === 0 && $err['error_count'] === 0))) {
            $dt = $try;
            break;
        }
    }
    if (!$dt) {
        $errors['event_date'] = 'Enter a valid date and time.';
    } elseif ($requireFuture && $dt < new DateTime('-1 minute')) {
        $errors['event_date'] = 'The event date must be in the future.';
    } else {
        $data['sql_date'] = $dt->format('Y-m-d H:i:00');
    }

    return [$data, $errors];
}

/* ------------------------------------------------------------------
 * Profile pictures
 * ------------------------------------------------------------------ */

/** True if the visitor actually picked a file in this <input type="file">. */
function has_upload(string $field): bool
{
    return isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE;
}

/**
 * Validate and store an uploaded profile picture.
 * Returns [newFileName|null, errorMessage|null].
 * Checks: upload status, size, real MIME type, real image content, safe random name.
 */
function save_profile_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return [null, 'Image is too large. Maximum size is 2 MB.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'The upload failed. Please try again.'];
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return [null, 'Image is too large. Maximum size is 2 MB.'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return [null, 'Invalid upload.'];
    }

    // Look at the file's real content, not the name the browser claims
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        return [null, 'Only JPG, PNG or WEBP images are allowed.'];
    }
    $info = @getimagesize($file['tmp_name']);        // false if it is not a real image
    if ($info === false || $info[0] > 5000 || $info[1] > 5000) {
        return [null, 'That file is not a valid image (max 5000 x 5000 pixels).'];
    }

    // Random name: the user's file name is never used
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
        return [null, 'Could not save the image. Please try again.'];
    }
    chmod(UPLOAD_DIR . $name, 0644);
    return [$name, null];
}

/** Only names in our own format are ever touched on disk. */
function is_valid_image_name(?string $name): bool
{
    return $name !== null && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name) === 1;
}

function delete_profile_image(?string $name): void
{
    if (is_valid_image_name($name) && is_file(UPLOAD_DIR . $name)) {
        @unlink(UPLOAD_DIR . $name);
    }
}

/** URL of a user's picture, or null when they have none. */
function avatar_url(array $user): ?string
{
    return is_valid_image_name($user['profile_picture'] ?? null) ? UPLOAD_URL . $user['profile_picture'] : null;
}

/** HTML for an avatar: the picture, or a circle with the user's initial. */
function avatar_html(array $user, string $class = 'avatar'): string
{
    $url = avatar_url($user);
    if ($url !== null) {
        return '<img class="' . e($class) . '" src="' . e($url) . '" alt="Profile picture of ' . e($user['full_name']) . '">';
    }
    $initial = mb_strtoupper(mb_substr($user['full_name'] ?? '?', 0, 1));
    return '<span class="' . e($class) . ' avatar-fallback" aria-hidden="true">' . e($initial) . '</span>';
}

/* ------------------------------------------------------------------
 * Search (used by search.php and search_live.php)
 * ------------------------------------------------------------------ */

/** Search users (by name/email) and events (by name/location/category). */
function search_all(string $q, int $meId): array
{
    // Escape LIKE wildcards so a user typing "%" or "_" searches for the literal character
    $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
    $pdo  = db();

    $st = $pdo->prepare(
        "SELECT id, full_name, email, profile_picture
           FROM users
          WHERE id <> :me
            AND (full_name LIKE :q1 ESCAPE '!' OR email LIKE :q2 ESCAPE '!')
          ORDER BY full_name ASC
          LIMIT 20"
    );
    $st->execute([':me' => $meId, ':q1' => $like, ':q2' => $like]);
    $users = $st->fetchAll();

    // Upcoming events first (soonest first), then past events
    $st = $pdo->prepare(
        "SELECT ev.id, ev.title, ev.location, ev.category, ev.event_date, ev.user_id, u.full_name AS host
           FROM events ev
           JOIN users u ON u.id = ev.user_id
          WHERE ev.title LIKE :q1 ESCAPE '!' OR ev.location LIKE :q2 ESCAPE '!' OR ev.category LIKE :q3 ESCAPE '!'
          ORDER BY (ev.event_date < :now) ASC, ev.event_date ASC
          LIMIT 20"
    );
    $st->execute([':q1' => $like, ':q2' => $like, ':q3' => $like, ':now' => date('Y-m-d H:i:s')]);
    $events = $st->fetchAll();

    return ['users' => $users, 'events' => $events];
}
