<!--
INTERNET PROGRAMMING ASSIGNMENT
NAME: TILAYA REDDY
STUDENT ID: 402504107
-->

<?php
/** login.php - sign in with email + password. */
require_once __DIR__ . '/includes/auth.php';
guest_only();

// A real bcrypt hash of a throwaway password. We verify against it when the email is unknown
// so that "unknown email" and "wrong password" take the same amount of time.
const DUMMY_HASH = '$2y$10$qBX5oGPh9iHsShCHu.AJJOBKK.HxaPmXP8O0DgzTGILpknAoLWAFi';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (($_SESSION['login_lock_until'] ?? 0) > time()) {
        $error = 'Too many failed attempts. Please wait a minute and try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } else {
        $st = db()->prepare('SELECT id, full_name, password_hash FROM users WHERE email = ?');
        $st->execute([$email]);
        $row = $st->fetch();

        $hash = $row ? $row['password_hash'] : DUMMY_HASH;
        if (password_verify($password, $hash) && $row) {
            unset($_SESSION['login_fails'], $_SESSION['login_lock_until']);

            // Upgrade the stored hash if PHP's default algorithm has become stronger
            if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
                $up = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $up->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
            }

            login_user((int) $row['id']);
            flash('success', 'Welcome back, ' . $row['full_name'] . '!');
            redirect('dashboard.php');
        }

        // Failed: count it, lock for 60 seconds after 5 misses (simple brute-force slow-down)
        $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
        if ($_SESSION['login_fails'] >= 5) {
            $_SESSION['login_lock_until'] = time() + 60;
            $_SESSION['login_fails']      = 0;
        }
        $error = 'Incorrect email or password.';   // same message for both cases
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="grid-2">
    <section class="card">
        <h1>Login</h1>
        <?php if ($error): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" data-validate novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required data-rules="required,email"
                       value="<?= e($email) ?>" autocomplete="username">
                <small class="field-error" data-for="email"></small>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required data-rules="required"
                       autocomplete="current-password">
                <small class="field-error" data-for="password"></small>
            </div>
            <div class="btn-row">
                <button type="submit" class="btn btn-primary">Login</button>
                <a class="btn" href="forgot_password.php">Forgot Password</a>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Welcome to <?= e(APP_NAME) ?></h2>
        <p class="muted">A secure platform for creating, managing and exploring events while connecting with other users through private messaging.</p>
        <hr>
        <ul class="plain-list">
            <li>Create and manage your own events.</li>
            <li>Search for users and discover new events.</li>
            <li>Chat privately with other users.</li>
            <li>Built with secure authentication and validated data handling.</li>
        </ul>
        <hr>
        <p class="muted">New here? <a href="register.php">Create a new account here</a></p>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
