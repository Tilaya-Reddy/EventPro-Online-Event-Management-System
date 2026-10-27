<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->
<?php

$navUser = current_user();
$unreadCount = 0;
if ($navUser) {
    $st = db()->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0');
    $st->execute([$navUser['id']]);
    $unreadCount = (int) $st->fetchColumn();
}
$currentPage = basename($_SERVER['SCRIPT_NAME']);

/** Print one navigation link and mark the current page. */
function nav_link(string $file, string $label, string $current, string $extra = ''): void
{
    $active = ($file === $current) ? ' aria-current="page" class="active"' : '';
    echo '<a href="' . e($file) . '"' . $active . '>' . e($label) . $extra . '</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($pageTitle ?? 'Welcome') . ' | ' . APP_NAME) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="index.php"><?= e(APP_NAME) ?></a>

        <?php if ($navUser): ?>
            <nav class="main-nav" aria-label="Main">
                <?php
                nav_link('dashboard.php', 'Dashboard', $currentPage);
                nav_link('event_create.php', 'Create Event', $currentPage);
                nav_link('search.php', 'Search', $currentPage);
                nav_link('messages.php', 'Messages', $currentPage,
                    $unreadCount > 0 ? ' <span class="pill">' . $unreadCount . '</span>' : '');
                ?>
            </nav>
            <div class="nav-user">
                <a class="nav-profile" href="profile.php">
                    <?= avatar_html($navUser, 'avatar avatar-sm') ?>
                    <span><?= e($navUser['full_name']) ?></span>
                </a>
                <form method="post" action="logout.php">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                </form>
            </div>
        <?php else: ?>
            <div class="nav-user">
                <a class="btn btn-sm" href="login.php">Login</a>
                <a class="btn btn-sm" href="register.php">Register</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<main class="container">
<?php foreach (get_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>" role="status"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
