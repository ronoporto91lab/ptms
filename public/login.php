<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/classes/User.php';

startSecureSession();

if (currentUser() !== null) {
    header('Location: index.php');
    exit;
}

$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('The form expired. Refresh the page and try again.');
        }

        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');
        if ($email === false || $password === '') {
            throw new InvalidArgumentException('Enter a valid email address and password.');
        }

        $repository = new User(Database::connect());
        $user = $repository->findByEmail($email);
        if ($user === null || !password_verify($password, (string) $user['password'])) {
            throw new InvalidArgumentException('The email or password is incorrect.');
        }

        loginUser($user);
        header('Location: index.php');
        exit;
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        $error = $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : 'Login could not be completed. Please try again.';
    }
}

function loginEscape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PTMS | Coordinator Login</title>
    <link rel="stylesheet" href="styles.css?v=7">
</head>
<body class="auth-page">
<main class="auth-shell">
    <section class="auth-card">
        <div class="auth-brand">
            <p class="eyebrow">Peer Tutoring Management System</p>
            <h1>Welcome <span>back.</span></h1>
            <p>Sign in as the program coordinator to manage tutoring sessions.</p>
        </div>

        <?php if (isset($_GET['logged_out'])): ?>
            <div class="alert">You have been logged out safely.</div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert">Account created. You can now sign in.</div>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <div class="alert error"><?= loginEscape($error) ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= loginEscape($_SESSION['csrf_token']) ?>">
            <label>Email address
                <input type="email" name="email" autocomplete="username" required
                       value="<?= loginEscape((string) ($_POST['email'] ?? '')) ?>">
            </label>
            <label>Password
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <button class="button auth-submit" type="submit">Sign in</button>
        </form>
        <p class="auth-switch">New to PTMS? <a href="register.php">Create an account</a></p>
    </section>
</main>
</body>
</html>
