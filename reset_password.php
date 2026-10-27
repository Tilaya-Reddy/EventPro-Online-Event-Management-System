<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->
<?php
/** reset_password.php - step 2: choose a new password using the emailed token. */
require_once __DIR__ . '/includes/auth.php';
guest_only();

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$reset = null;

// The token must look right AND exist AND not be expired
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = db()->prepare('SELECT id, user_id FROM password_resets WHERE token_hash = ? AND expires_at > ?');
    $st->execute([hash('sha256', $token), date('Y-m-d H:i:s')]);
    $reset = $st->fetch() ?: null;
}

$error = '';
if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm_password'] ?? '');

    if ($msg = validate_password($password)) {
        $error = $msg;
    } elseif ($password !== $confirm) {
        $error = 'The two passwords do not match.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();                   // update password + delete token together
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
        $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$reset['user_id']]);
        $pdo->commit();

        flash('success', 'Password updated. You can now log in.');
        redirect('login.php');
    }
}

$pageTitle = 'Reset Password';
require __DIR__ . '/includes/header.php';
?>
<section class="card narrow">
    <h1>Reset Password</h1>

<?php if (!$reset): ?>
    <div class="alert alert-error">This reset link is invalid or has expired.</div>
    <a class="btn" href="forgot_password.php">Request a new link</a>
<?php else: ?>
    <?php if ($error): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="reset_password.php" data-validate novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" maxlength="72" required
                   data-rules="required,password" autocomplete="new-password">
            <small class="hint">At least 8 characters, with a letter and a number.</small>
            <small class="field-error" data-for="password"></small>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" maxlength="72" required
                   data-rules="required,match:password" autocomplete="new-password">
            <small class="field-error" data-for="confirm_password"></small>
        </div>
        <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
<?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
