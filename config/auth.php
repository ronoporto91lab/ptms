<?php
declare(strict_types=1);

function currentUser(): ?array
{
    $user = $_SESSION['user'] ?? null;

    return is_array($user) ? $user : null;
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['user_id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
    ];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function requireLogin(): array
{
    $user = currentUser();
    if ($user === null) {
        header('Location: login.php');
        exit;
    }

    return $user;
}

function requireCoordinator(): array
{
    $user = requireLogin();
    if (($user['role'] ?? '') !== 'Coordinator') {
        http_response_code(403);
        exit('Access denied. Coordinator access is required.');
    }

    return $user;
}

function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parameters = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $parameters['path'], '',
            (bool) $parameters['secure'], (bool) $parameters['httponly']);
    }

    session_destroy();
}
