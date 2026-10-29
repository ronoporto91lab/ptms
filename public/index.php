<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/classes/TutoringSession.php';

startSecureSession();
$currentUser = requireLogin();
$canManage = ($currentUser['role'] ?? '') === 'Coordinator';

function escape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirectWithMessage(string $message, string $type = 'success'): never
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    header('Location: index.php');
    exit;
}

function validateSession(array $input): array
{
    $required = ['student_id', 'tutor_id', 'subject_id', 'session_date', 'time_slot', 'location', 'status'];
    foreach ($required as $field) {
        if (trim((string) ($input[$field] ?? '')) === '') {
            throw new InvalidArgumentException('Please complete all required fields.');
        }
    }

    $statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'No-show'];
    if (!in_array($input['status'], $statuses, true)) {
        throw new InvalidArgumentException('Please select a valid status.');
    }

    foreach (['student_id', 'tutor_id', 'subject_id'] as $idField) {
        if (filter_var($input[$idField], FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('Please select a valid student, tutor, and subject.');
        }
    }

    return $input;
}

try {
    $repository = new TutoringSession(Database::connect());
    $tutors = $repository->getTutors();
    $students = $repository->getStudents();
    $subjects = $repository->getSubjects();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$canManage) {
            http_response_code(403);
            throw new RuntimeException('Coordinator access is required for this action.');
        }

        if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('The form expired. Refresh the page and try again.');
        }

        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'add') {
            $repository->addTutoringSession(validateSession($_POST));
            redirectWithMessage('Tutoring session added successfully.');
        }

        if ($action === 'update') {
            $repository->updateTutoringSession((int) $_POST['id'], validateSession($_POST));
            redirectWithMessage('Tutoring session updated successfully.');
        }

        if ($action === 'delete') {
            $repository->deleteTutoringSession((int) $_POST['id']);
            redirectWithMessage('Tutoring session deleted successfully.');
        }

        throw new InvalidArgumentException('Unknown action.');
    }

    $keyword = trim((string) ($_GET['search'] ?? ''));
    $sessions = $keyword === ''
        ? $repository->getTutoringSessions()
        : $repository->searchTutoringSessions($keyword);

    $editId = $canManage ? filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) : null;
    $editing = $editId ? $repository->getTutoringSession((int) $editId) : null;
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $pageError = $exception instanceof InvalidArgumentException
        ? $exception->getMessage()
        : 'The request could not be completed. Please check the database setup and try again.';
    $sessions = $sessions ?? [];
    $editing = $editing ?? null;
    $keyword = $keyword ?? '';
    $tutors = $tutors ?? [];
    $students = $students ?? [];
    $subjects = $subjects ?? [];
}

$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'No-show'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PTMS | Tutoring Sessions</title>
    <link rel="stylesheet" href="styles.css?v=7">
</head>
<body>
<header class="topbar">
    <div class="hero-copy">
        <p class="eyebrow">Peer Tutoring Management System</p>
        <h1>Tutoring <span>Sessions</span></h1>
        <p class="hero-message">Where questions become breakthroughs.</p>
        <div class="hero-chips" aria-label="System highlights">
            <span>Connect</span><span>Learn</span><span>Grow</span>
        </div>
    </div>
    <div class="topbar-actions">
        <div class="user-summary">
            <strong><?= escape($currentUser['name']) ?></strong>
            <span><?= escape($currentUser['role']) ?></span>
        </div>
        <?php if ($canManage): ?><a class="button button-light" href="#session-form">+ New session</a><?php endif; ?>
        <form method="post" action="logout.php">
            <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
            <button class="button button-ghost" type="submit">Log out</button>
        </form>
    </div>
</header>

<main class="container">
    <?php if ($flash): ?>
        <div class="alert <?= escape($flash['type']) ?>"><?= escape($flash['message']) ?></div>
    <?php endif; ?>
    <?php if (isset($pageError)): ?>
        <div class="alert error"><?= escape($pageError) ?></div>
    <?php endif; ?>

    <section class="card search-card">
        <form method="get" class="search-form">
            <label for="search">Search any session field</label>
            <div class="search-row">
                <input id="search" name="search" value="<?= escape($keyword) ?>" placeholder="Student, tutor, subject, date, status...">
                <button class="button" type="submit">Search</button>
                <?php if ($keyword !== ''): ?><a class="button button-muted" href="index.php">Clear</a><?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Records</p>
                <h2><?= $keyword === '' ? 'All sessions' : 'Search results' ?></h2>
            </div>
            <span class="count"><?= count($sessions) ?> record<?= count($sessions) === 1 ? '' : 's' ?></span>
        </div>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Session ID</th><th>Date / Time Slot</th><th>Student</th><th>Tutor</th><th>Subject</th><th>Location</th><th>Status</th><?php if ($canManage): ?><th>Actions</th><?php endif; ?></tr></thead>
                <tbody>
                <?php if (!$sessions): ?>
                    <tr><td colspan="<?= $canManage ? 8 : 7 ?>" class="empty">No tutoring sessions found.</td></tr>
                <?php endif; ?>
                <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td><?= (int) $session['session_id'] ?></td>
                        <td><strong><?= escape(date('M j, Y', strtotime($session['session_date']))) ?></strong><br><small><?= escape($session['time_slot']) ?></small></td>
                        <td><?= escape($session['student_name']) ?></td>
                        <td><?= escape($session['tutor_name']) ?></td>
                        <td><?= escape($session['subject_name']) ?></td>
                        <td><?= escape($session['location']) ?></td>
                        <td><span class="status status-<?= strtolower(str_replace([' ', '-'], '', $session['status'])) ?>"><?= escape($session['status']) ?></span></td>
                        <?php if ($canManage): ?><td class="actions">
                            <a class="text-link" href="?edit=<?= (int) $session['session_id'] ?>#session-form">Edit</a>
                            <form method="post" onsubmit="return confirm('Delete this tutoring session?');">
                                <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $session['session_id'] ?>">
                                <button class="link-danger" type="submit">Delete</button>
                            </form>
                        </td><?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($canManage): ?>
    <section class="card" id="session-form">
        <div class="section-heading">
            <div><p class="eyebrow"><?= $editing ? 'Update record' : 'Create record' ?></p><h2><?= $editing ? 'Edit tutoring session' : 'Add tutoring session' ?></h2></div>
            <?php if ($editing): ?><a class="text-link" href="index.php#session-form">Cancel edit</a><?php endif; ?>
        </div>
        <form method="post" class="record-form">
            <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['session_id'] ?>"><?php endif; ?>

            <label>Student<select name="student_id" required><option value="">Select student</option><?php foreach ($students as $student): ?><option value="<?= (int) $student['user_id'] ?>" <?= (int) ($editing['student_id'] ?? 0) === (int) $student['user_id'] ? 'selected' : '' ?>><?= escape($student['name']) ?></option><?php endforeach; ?></select></label>
            <label>Tutor<select name="tutor_id" required><option value="">Select tutor</option><?php foreach ($tutors as $tutor): ?><option value="<?= (int) $tutor['user_id'] ?>" <?= (int) ($editing['tutor_id'] ?? 0) === (int) $tutor['user_id'] ? 'selected' : '' ?>><?= escape($tutor['name']) ?></option><?php endforeach; ?></select></label>
            <label>Subject<select name="subject_id" required><option value="">Select subject</option><?php foreach ($subjects as $subject): ?><option value="<?= (int) $subject['subject_id'] ?>" <?= (int) ($editing['subject_id'] ?? 0) === (int) $subject['subject_id'] ? 'selected' : '' ?>><?= escape($subject['subject_name']) ?></option><?php endforeach; ?></select></label>
            <label>Session date<input type="date" name="session_date" required value="<?= escape($editing['session_date'] ?? '') ?>"></label>
            <label>Time slot<input name="time_slot" maxlength="50" required placeholder="e.g. 10:00 AM - 11:00 AM" value="<?= escape($editing['time_slot'] ?? '') ?>"></label>
            <label>Location<input name="location" maxlength="150" required value="<?= escape($editing['location'] ?? '') ?>"></label>
            <label>Status<select name="status" required><?php foreach ($statuses as $status): ?><option <?= ($editing['status'] ?? 'Pending') === $status ? 'selected' : '' ?>><?= escape($status) ?></option><?php endforeach; ?></select></label>
            <div class="full-width"><button class="button" type="submit"><?= $editing ? 'Save changes' : 'Add session' ?></button></div>
        </form>
    </section>
    <?php else: ?>
    <section class="card access-note">
        <p class="eyebrow">Read-only access</p>
        <h2>Welcome, <?= escape($currentUser['name']) ?></h2>
        <p>Student and Tutor accounts may view and search sessions. Session management is reserved for the Coordinator.</p>
    </section>
    <?php endif; ?>
</main>
</body>
</html>
