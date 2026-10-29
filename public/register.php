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

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $contactNumber = trim((string) ($_POST['contact_number'] ?? ''));
        $role = (string) ($_POST['role'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($name === '' || $email === false || $contactNumber === '') {
            throw new InvalidArgumentException('Complete all required account details.');
        }
        if (!in_array($role, ['Student', 'Tutor'], true)) {
            throw new InvalidArgumentException('Choose either Student or Tutor.');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Use a password with at least 8 characters.');
        }
        if ($password !== $confirmation) {
            throw new InvalidArgumentException('The passwords do not match.');
        }

        $repository = new User(Database::connect());
        if ($repository->emailExists($email)) {
            throw new InvalidArgumentException('An account already uses this email address.');
        }

        $repository->register([
            'name' => $name,
            'email' => $email,
            'contact_number' => $contactNumber,
            'role' => $role,
            'password' => $password,
        ]);

        header('Location: login.php?registered=1');
        exit;
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        $error = $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : 'The account could not be created. Please try again.';
    }
}

function registerEscape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PTMS | Create Account</title>
    <link rel="stylesheet" href="styles.css?v=7">
</head>
<body class="auth-page">
<main class="auth-shell">
    <section class="auth-card auth-card-wide">
        <div class="auth-brand">
            <p class="eyebrow">Peer Tutoring Management System</p>
            <h1>Create <span>account.</span></h1>
            <p>Register as a student or tutor. Coordinator access is assigned privately.</p>
        </div>

        <?php if ($error !== null): ?>
            <div class="alert error"><?= registerEscape($error) ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form auth-form-grid">
            <input type="hidden" name="csrf_token" value="<?= registerEscape($_SESSION['csrf_token']) ?>">
            <label>Full name
                <input name="name" maxlength="100" autocomplete="name" required value="<?= registerEscape((string) ($_POST['name'] ?? '')) ?>">
            </label>
            <label>Email address
                <input type="email" name="email" maxlength="150" autocomplete="email" required value="<?= registerEscape((string) ($_POST['email'] ?? '')) ?>">
            </label>
            <label>Contact number
                <input name="contact_number" maxlength="30" autocomplete="tel" required value="<?= registerEscape((string) ($_POST['contact_number'] ?? '')) ?>">
            </label>
            <label>Account type
                <select name="role" required>
                    <option value="Student" <?= ($_POST['role'] ?? '') === 'Student' ? 'selected' : '' ?>>Student</option>
                    <option value="Tutor" <?= ($_POST['role'] ?? '') === 'Tutor' ? 'selected' : '' ?>>Tutor</option>
                </select>
            </label>
            <label>Password
                <input type="password" name="password" minlength="8" autocomplete="new-password" required>
            </label>
            <label>Confirm password
                <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
            </label>
            <div class="auth-form-full"><button class="button auth-submit" type="submit">Create account</button></div>
        </form>
        <p class="auth-switch">Already registered? <a href="login.php">Back to sign in</a></p>
    </section>
</main>
</body>
</html>
