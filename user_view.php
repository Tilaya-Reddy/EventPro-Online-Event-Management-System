<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->
<?php
/** user_view.php - VIEW-ONLY profile of another user. Nothing here can be edited. */
require_once __DIR__ . '/includes/auth.php';
require_login();
$me = current_user();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id && $id === (int) $me['id']) {
    redirect('profile.php');                    // your own profile is the editable one
}

$other = null;
if ($id) {
    // Select only the public columns (never the password hash)
    $st = db()->prepare('SELECT id, full_name, email, profile_picture, created_at FROM users WHERE id = ?');
    $st->execute([$id]);
    $other = $st->fetch() ?: null;
}
if (!$other) {
    http_response_code(404);
    flash('error', 'That user does not exist.');
    redirect('search.php');
}

$now = date('Y-m-d H:i:s');
$st = db()->prepare(
    'SELECT title, description, location, category, event_date
       FROM events WHERE user_id = :uid
      ORDER BY (event_date < :now) ASC, event_date ASC'
);
$st->execute([':uid' => $other['id'], ':now' => $now]);
$events = $st->fetchAll();

$pageTitle = $other['full_name'];
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>User Profile</h1>
    <div class="profile-head">
        <?= avatar_html($other, 'avatar avatar-lg') ?>
        <div class="grow">
            <strong><?= e($other['full_name']) ?></strong><br>
            <small class="muted"><?= e($other['email']) ?></small><br>
            <small class="muted">Member since <?= e(date('j M Y', strtotime($other['created_at']))) ?></small>
        </div>
        <a class="btn btn-primary" href="conversation.php?user=<?= (int) $other['id'] ?>">Message</a>
    </div>
</section>

<section class="card">
    <h2><?= e($other['full_name']) ?>'s Events (View Only)</h2>
    <?php if (!$events): ?>
        <p class="muted">No events available.</p>
    <?php else: ?>
        <ul class="list">
        <?php foreach ($events as $ev): ?>
            <li class="event-item <?= $ev['event_date'] < $now ? 'is-past' : '' ?>">
                <strong><?= e($ev['title']) ?></strong>
                <span class="badge"><?= e($ev['category']) ?></span>
                <small class="muted"><?= e(format_dt($ev['event_date'])) ?> &middot; <?= e($ev['location']) ?><?= $ev['event_date'] < $now ? ' &middot; past' : '' ?></small>
                <?php if ($ev['description']): ?>
                    <small class="muted"><?= e(mb_strimwidth($ev['description'], 0, 160, '…')) ?></small>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
