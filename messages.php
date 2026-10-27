<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->

<?php
/** messages.php - inbox: one row per person you have chatted with. */
require_once __DIR__ . '/includes/auth.php';
require_login();
$me = (int) $_SESSION['user_id'];

// For each conversation partner, take the newest message (highest id) between us.
// The WHERE clause only ever looks at messages where *I* am the sender or receiver.
$sql = "SELECT u.id, u.full_name, u.email, u.profile_picture,
               m.body AS last_body, m.created_at AS last_at, m.sender_id AS last_sender,
               (SELECT COUNT(*) FROM messages x
                 WHERE x.sender_id = u.id AND x.receiver_id = ? AND x.is_read = 0) AS unread
          FROM messages m
          JOIN users u ON u.id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
         WHERE m.id IN (
                SELECT MAX(id) FROM messages
                 WHERE sender_id = ? OR receiver_id = ?
                 GROUP BY IF(sender_id = ?, receiver_id, sender_id)
               )
         ORDER BY m.id DESC";
$st = db()->prepare($sql);
$st->execute([$me, $me, $me, $me, $me]);
$conversations = $st->fetchAll();

$pageTitle = 'Messages';
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Messages</h1>
    <p class="muted">Start a message from <a href="search.php">Search</a> &rarr; User &rarr; Message.</p>
</section>

<section class="card">
    <h2>Recent Conversations</h2>
    <?php if (!$conversations): ?>
        <p class="muted">No conversations yet.</p>
    <?php else: ?>
        <ul class="list">
        <?php foreach ($conversations as $c): ?>
            <li class="row">
                <?= avatar_html($c, 'avatar avatar-sm') ?>
                <div class="grow">
                    <strong><?= e($c['full_name']) ?></strong>
                    <?php if ((int) $c['unread'] > 0): ?><span class="pill"><?= (int) $c['unread'] ?> new</span><?php endif; ?>
                    <br>
                    <small class="muted">
                        <?= (int) $c['last_sender'] === $me ? 'You: ' : '' ?><?= e(mb_strimwidth(str_replace("\n", ' ', $c['last_body']), 0, 70, '…')) ?>
                        &middot; <?= e(format_dt($c['last_at'])) ?>
                    </small>
                </div>
                <a class="btn btn-sm" href="conversation.php?user=<?= (int) $c['id'] ?>">Open</a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
