<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->
<?php

require_once __DIR__ . '/includes/auth.php';
require_login();
$me = current_user();

$q = clean_text($_GET['q'] ?? '');
if (mb_strlen($q) > 100) {
    $q = mb_substr($q, 0, 100);
}
$results = $q !== '' ? search_all($q, (int) $me['id']) : null;

$pageTitle = 'Search';
require __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h1>Search</h1>
    <form method="get" action="search.php" class="search-form" role="search">
        <label for="live-search" class="sr-only">Search users or events</label>
        <input type="search" id="live-search" name="q" maxlength="100" placeholder="Search users or events..."
               value="<?= e($q) ?>" autocomplete="off">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
    <p class="hint">Search by user name/email or event name/location/category.</p>
</section>

<div id="search-status" class="muted" aria-live="polite"></div>
<div id="search-results" class="grid-2">
<?php if ($results !== null): ?>
    <section class="card">
        <h2>Users</h2>
        <?php if (!$results['users']): ?>
            <p class="muted">No users found.</p>
        <?php else: ?>
            <ul class="list">
            <?php foreach ($results['users'] as $u): ?>
                <li class="row">
                    <?= avatar_html($u, 'avatar avatar-sm') ?>
                    <div class="grow"><strong><?= e($u['full_name']) ?></strong><br><small class="muted"><?= e($u['email']) ?></small></div>
                    <a class="btn btn-sm" href="user_view.php?id=<?= (int) $u['id'] ?>">View</a>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <section class="card">
        <h2>Events</h2>
        <?php if (!$results['events']): ?>
            <p class="muted">No events found.</p>
        <?php else: ?>
            <ul class="list">
            <?php foreach ($results['events'] as $ev): ?>
                <li class="event-item">
                    <strong><?= e($ev['title']) ?></strong> <span class="badge"><?= e($ev['category']) ?></span>
                    <small class="muted"><?= e(format_dt($ev['event_date'])) ?> &middot; <?= e($ev['location']) ?></small>
                    <small class="muted">Hosted by
                        <a href="<?= (int) $ev['user_id'] === (int) $me['id'] ? 'profile.php' : 'user_view.php?id=' . (int) $ev['user_id'] ?>"><?= e($ev['host']) ?></a>
                    </small>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
