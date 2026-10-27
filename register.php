<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->
<?php
/** register.php - create a new account (with optional profile picture). */
require_once __DIR__ . '/includes/auth.php';
guest_only();

$errors = [];
$old    = ['full_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['full_name'] = clean_text($_POST['full_name'] ?? '');
    $old['email']     = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password         = (string) ($_POST['password'] ?? '');

    // 1. Validate the text fields
    if ($msg = validate_full_name($old['full_name'])) { $errors['full_name'] = $msg; }
    if ($msg = validate_email($old['email']))         { $errors['email']     = $msg; }
    if ($msg = validate_password($password))          { $errors['password']  = $msg; }

    // 2. Unique email check (the UNIQUE index in the database is the final safety net)
    if (!isset($errors['email'])) {
        $st = db()->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$old['email']]);
        if ($st->fetch()) {
            $errors['email'] = 'That email address is already registered.';
        }
    }

    // 3. Only handle the file once everything else is valid
    $picture = null;
    if (!$errors && has_upload('profile_picture')) {
        [$picture, $uploadError] = save_profile_image($_FILES['profile_picture']);
        if ($uploadError) {
            $errors['profile_picture'] = $uploadError;
        }
    }

    // 4. Save the user
    if (!$errors) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);       // never store plain passwords
            $st = db()->prepare('INSERT INTO users (full_name, email, password_hash, profile_picture) VALUES (?, ?, ?, ?)');
            $st->execute([$old['full_name'], $old['email'], $hash, $picture]);

            flash('success', 'Account created. Please log in.');
            redirect('login.php');
        } catch (PDOException $ex) {
            delete_profile_image($picture);                            // do not leave an orphan file
            if ($ex->getCode() === '23000') {                          // duplicate key (race condition)
                $errors['email'] = 'That email address is already registered.';
            } else {
                error_log($ex->getMessage());
                $errors['general'] = 'Something went wrong. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<section class="card narrow">
    <h1>Create Account</h1>

    <?php if (isset($errors['general'])): ?>
        <div class="alert alert-error"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" action="register.php" enctype="multipart/form-data" data-validate novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="full_name">Full Name <span class="req">*</span></label>
            <input type="text" id="full_name" name="full_name" maxlength="100" required
                   data-rules="required,min:2,max:100" value="<?= e($old['full_name']) ?>" autocomplete="name">
            <small class="field-error" data-for="full_name"><?= e($errors['full_name'] ?? '') ?></small>
        </div>

        <div class="form-group">
            <label for="email">Email <span class="req">*</span></label>
            <input type="email" id="email" name="email" maxlength="150" required
                   data-rules="required,email" value="<?= e($old['email']) ?>" autocomplete="email">
            <small class="field-error" data-for="email"><?= e($errors['email'] ?? '') ?></small>
        </div>

        <div class="form-group">
            <label for="password">Password <span class="req">*</span></label>
            <input type="password" id="password" name="password" maxlength="72" required
                   data-rules="required,password" autocomplete="new-password">
            <small class="hint">At least 8 characters, with a letter and a number.</small>
            <small class="field-error" data-for="password"><?= e($errors['password'] ?? '') ?></small>
        </div>

        <div class="form-group">
            <label for="profile_picture">Profile Picture (optional)</label>
            <input type="file" id="profile_picture" name="profile_picture"
                   accept="image/jpeg,image/png,image/webp" data-preview="#picture-preview">
            <small class="hint">Max 2MB. JPG/PNG/WEBP only.</small>
            <small class="field-error" data-for="profile_picture"><?= e($errors['profile_picture'] ?? '') ?></small>
            <div class="preview-box"><img id="picture-preview" alt="Preview of the selected picture" hidden></div>
        </div>

        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
