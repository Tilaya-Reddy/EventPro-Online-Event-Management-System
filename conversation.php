<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->

<?php

require_once __DIR__ . '/includes/auth.php';
require_login();
$me = (int) $_SESSION['user_id'];

$otherId = filter_var($_GET['user'] ?? null, FILTER_VALIDATE_INT);
if (!$otherId || $otherId === $me) {
    flash('error', 'Choose someone to message from the search page.');
    redirect('messages.php');
}

$st = db()->prepare('SELECT id, full_name, email, profile_picture FROM users WHERE id = ?');
$st->execute([$otherId]);
$other = $st->fetch();
if (!$other) {
    http_response_code(404);
    flash('error', 'That user does not exist.');
    redirect('messages.php');
}

$error = '';
$body  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $body = clean_multiline($_POST['body'] ?? '');

    if ($body === '') {
        $error = 'Type a message before sending.';
    } elseif (mb_strlen($body) > 2000) {
        $error = 'Messages can be up to 2000 characters.';
    } else {
        $st = db()->prepare('INSERT INTO messages (sender_id, receiver_id, body, created_at) VALUES (?, ?, ?, ?)');
        $st->execute([$me, $otherId, $body, date('Y-m-d H:i:s')]);
        redirect('conversation.php?user=' . $otherId);      // stops a refresh from re-sending
    }
}

// Opening the chat marks their messages to me as read
$st = db()->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0');
$st->execute([$otherId, $me]);

// Newest 200 messages between the two of us, shown oldest first
$st = db()->prepare(
    'SELECT * FROM (
        SELECT id, sender_id, body, created_at
          FROM messages
         WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
         ORDER BY id DESC
         LIMIT 200
     ) recent
     ORDER BY id ASC'
);
$st->execute([$me, $otherId, $otherId, $me]);
$messages = $st->fetchAll();

$myName = current_user()['full_name'];

$pageTitle = 'Conversation with ' . $other['full_name'];
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Conversation</h1>
    <div class="profile-head">
        <?= avatar_html($other, 'avatar avatar-sm') ?>
        <div class="grow">
            <strong><?= e($other['full_name']) ?></strong><br>
            <small class="muted"><?= e($other['email']) ?></small>
        </div>
        <a class="btn btn-sm" href="user_view.php?id=<?= (int) $other['id'] ?>">View Profile</a>
    </div>
</section>

<section class="card">
    <h2>Messages</h2>
    <div class="chat" id="chat" aria-live="polite">
        <?php if (!$messages): ?>
            <p class="muted">No messages yet. Say hello!</p>
        <?php endif; ?>
        <?php foreach ($messages as $m): $mine = (int) $m['sender_id'] === $me; ?>
            <div class="bubble <?= $mine ? 'me' : 'them' ?>">
                <div class="bubble-meta">
                    <strong><?= e($mine ? $myName : $other['full_name']) ?></strong> &middot; <?= e(date('Y-m-d H:i', strtotime($m['created_at']))) ?>
                </div>
                <div class="bubble-body"><?= nl2br(e($m['body'])) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <hr>

    <?php if ($error): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="conversation.php?user=<?= (int) $otherId ?>" data-validate novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="body">New Message</label>
            <textarea id="body" name="body" rows="4" maxlength="2000" required
                      data-rules="required,max:2000" data-counter="#body-count"
                      placeholder="Type your message..."></textarea>
            <small class="hint"><span id="body-count">0</span>/2000 &middot; Ctrl+Enter to send</small>
            <small class="field-error" data-for="body"></small>
        </div>
        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Send</button>
            <a class="btn" href="messages.php">Back</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
