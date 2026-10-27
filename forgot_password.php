<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->
<?php

require_once __DIR__ . '/includes/auth.php';
guest_only();

$submitted = false;
$resetLink = null;
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));

    if ($msg = validate_email($email)) {
        $error = $msg;
    } else {
        $st = db()->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        $user = $st->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));                 // unguessable token sent to the user
            $hash  = hash('sha256', $token);                    // only the hash is stored

            db()->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);
            db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)')
                ->execute([$user['id'], $hash, date('Y-m-d H:i:s', time() + RESET_TOKEN_LIFETIME)]);

            $resetLink = 'reset_password.php?token=' . $token;
        }
        $submitted = true;
    }
}

$pageTitle = 'Forgot Password';
require __DIR__ . '/includes/header.php';
?>
<section class="card narrow">
    <h1>Forgot Password</h1>
    <p class="muted">Demo mode: the reset link is shown on-screen.</p>

    <?php if ($error): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($submitted): ?>
        <?php if ($resetLink): ?>
            <div class="alert alert-success">
                Reset link created. It expires in <?= (int) (RESET_TOKEN_LIFETIME / 60) ?> minutes:<br>
                <a href="<?= e($resetLink) ?>">Reset my password</a>
            </div>
        <?php else: ?>
            <div class="alert alert-success">If that email address is registered, a reset link has been generated.</div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="post" action="forgot_password.php" data-validate novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required data-rules="required,email" autocomplete="email">
            <small class="field-error" data-for="email"></small>
        </div>
        <button type="submit" class="btn btn-primary">Generate Reset Link</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
