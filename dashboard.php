<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->

<?php
/** dashboard.php - your events plus upcoming events from everyone. */
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$now  = date('Y-m-d H:i:s');

// Your events: upcoming first (soonest at the top), then past events
$st = db()->prepare(
    'SELECT id, title, description, location, category, event_date, created_at
       FROM events
      WHERE user_id = :uid
      ORDER BY (event_date < :now) ASC, event_date ASC'
);
$st->execute([':uid' => $user['id'], ':now' => $now]);
$myEvents = $st->fetchAll();

// Upcoming events from all users, soonest first
$st = db()->prepare(
    'SELECT ev.id, ev.title, ev.location, ev.category, ev.event_date, ev.user_id, u.full_name AS host
       FROM events ev
       JOIN users u ON u.id = ev.user_id
      WHERE ev.event_date >= :now
      ORDER BY ev.event_date ASC
      LIMIT 8'
);
$st->execute([':now' => $now]);
$upcoming = $st->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="grid-2 grid-wide-left">
    <section class="card">
        <h1>Your Dashboard</h1>
        <p class="muted">Manage events, search users/events, and message others.</p>
        <div class="btn-row">
            <a class="btn" href="event_create.php">+ Create Event</a>
            <a class="btn" href="search.php">Search</a>
            <a class="btn" href="messages.php">Messages</a>
        </div>
        <hr>

        <h2>Your Events</h2>
        <?php if (!$myEvents): ?>
            <p class="muted">No events yet. <a href="event_create.php">Create your first event</a>.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="events">
                    <thead>
                        <tr><th>Date</th><th>Event</th><th>Location</th><th>Category</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($myEvents as $ev): $isPast = $ev['event_date'] < $now; ?>
                        <tr class="<?= $isPast ? 'is-past' : '' ?>">
                            <td>
                                <?= e(date('D j M Y', strtotime($ev['event_date']))) ?><br>
                                <small class="muted"><?= e(date('H:i', strtotime($ev['event_date']))) ?><?= $isPast ? ' &middot; past' : '' ?></small>
                            </td>
                            <td>
                                <strong><?= e($ev['title']) ?></strong>
                                <?php if ($ev['description'] !== null && $ev['description'] !== ''): ?>
                                    <br><small class="muted"><?= e(mb_strimwidth($ev['description'], 0, 80, '…')) ?></small>
                                <?php endif; ?>
                                <br><small class="muted">Created <?= e(format_dt($ev['created_at'])) ?></small>
                            </td>
                            <td><?= e($ev['location']) ?></td>
                            <td><span class="badge"><?= e($ev['category']) ?></span></td>
                            <td class="actions">
                                <a class="btn btn-sm" href="event_edit.php?id=<?= (int) $ev['id'] ?>">Edit</a>
                                <form method="post" action="event_delete.php" data-confirm="Delete this event? This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Upcoming Events</h2>
        <?php if (!$upcoming): ?>
            <p class="muted">Nothing is scheduled yet.</p>
        <?php else: ?>
            <ul class="list">
            <?php foreach ($upcoming as $ev): ?>
                <li class="event-item">
                    <strong><?= e($ev['title']) ?></strong>
                    <span class="badge"><?= e($ev['category']) ?></span>
                    <small class="muted"><?= e(format_dt($ev['event_date'])) ?> &middot; <?= e($ev['location']) ?></small>
                    <small class="muted">Hosted by
                        <a href="<?= (int) $ev['user_id'] === (int) $user['id'] ? 'profile.php' : 'user_view.php?id=' . (int) $ev['user_id'] ?>"><?= e($ev['host']) ?></a>
                    </small>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
