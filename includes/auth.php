<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';

function admin_login(string $username, string $password): bool
{
    ensure_session();
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && hash_equals($user['password_hash'], hash('sha256', $password))) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];
        return true;
    }

    return false;
}

function is_admin_authenticated(): bool
{
    ensure_session();
    return isset($_SESSION['admin_id']);
}

function require_admin_auth(): void
{
    if (!is_admin_authenticated()) {
        header('Location: ' . ADMIN_URL . '/index.php?error=login_required');
        exit;
    }
}

function admin_logout(): void
{
    ensure_session();
    session_destroy();
}

