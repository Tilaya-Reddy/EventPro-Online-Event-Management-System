<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->

<?php
/** profile.php - view and update your own name and profile picture. */
require_once __DIR__ . '/includes/auth.php';
require_login();
$user   = current_user();
$errors = [];
$name   = $user['full_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = clean_text($_POST['full_name'] ?? '');

    if ($msg = validate_full_name($name)) {
        $errors['full_name'] = $msg;
    }

    $newPicture = null;
    if (!$errors && has_upload('profile_picture')) {
        [$newPicture, $uploadError] = save_profile_image($_FILES['profile_picture']);
        if ($uploadError) {
            $errors['profile_picture'] = $uploadError;
        }
    }

    if (!$errors) {
        // Keep the old picture unless a new one was uploaded
        $picture = $newPicture ?? $user['profile_picture'];
        $st = db()->prepare('UPDATE users SET full_name = ?, profile_picture = ? WHERE id = ?');
        $st->execute([$name, $picture, $user['id']]);

        if ($newPicture !== null) {
            delete_profile_image($user['profile_picture']);      // remove the replaced file
        }
        flash('success', 'Profile updated.');
        redirect('profile.php');
    }
}

$st = db()->prepare('SELECT title, category, location, event_date, description FROM events WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 5');
$st->execute([$user['id']]);
$recent = $st->fetchAll();

$pageTitle = 'Your Profile';
require __DIR__ . '/includes/header.php';
?>
<div class="grid-2">
    <section class="card">
        <h1>Your Profile</h1>
        <div class="profile-head">
            <?= avatar_html($user, 'avatar avatar-lg') ?>
            <div>
                <strong><?= e($user['full_name']) ?></strong><br>
                <small class="muted"><?= e($user['email']) ?></small><br>
                <small class="muted">Member since <?= e(date('j M Y', strtotime($user['created_at']))) ?></small>
            </div>
        </div>
        <hr>

        <h2>Update Profile</h2>
        <form method="post" action="profile.php" enctype="multipart/form-data" data-validate novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" maxlength="100" required
                       data-rules="required,min:2,max:100" value="<?= e($name) ?>">
                <small class="field-error" data-for="full_name"><?= e($errors['full_name'] ?? '') ?></small>
            </div>
            <div class="form-group">
                <label for="profile_picture">Profile Picture</label>
                <input type="file" id="profile_picture" name="profile_picture"
                       accept="image/jpeg,image/png,image/webp" data-preview="#picture-preview">
                <small class="hint">Max 2MB. JPG/PNG/WEBP only.</small>
                <small class="field-error" data-for="profile_picture"><?= e($errors['profile_picture'] ?? '') ?></small>
                <div class="preview-box"><img id="picture-preview" alt="Preview of the selected picture" hidden></div>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </section>

    <section class="card">
        <h2>Your Recent Events</h2>
        <?php if (!$recent): ?>
            <p class="muted">You have not created any events yet.</p>
        <?php else: ?>
            <ul class="list">
            <?php foreach ($recent as $ev): ?>
                <li class="event-item">
                    <strong><?= e($ev['title']) ?></strong>
                    <span class="badge"><?= e($ev['category']) ?></span>
                    <small class="muted"><?= e(format_dt($ev['event_date'])) ?> &middot; <?= e($ev['location']) ?></small>
                    <?php if ($ev['description']): ?>
                        <small class="muted"><?= e(mb_strimwidth($ev['description'], 0, 110, '…')) ?></small>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
