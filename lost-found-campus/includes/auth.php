<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function current_user(bool $refresh = false): ?array
{
    static $cache = false;
    if ($refresh) { $cache = false; }
    if ($cache === false) {
        $cache = null;
        if (!empty($_SESSION['user_id'])) {
            $st = db()->prepare('SELECT id, full_name, email, role FROM users WHERE id = :id');
            $st->execute([':id' => (int)$_SESSION['user_id']]);
            $cache = $st->fetch() ?: null;
            if (!$cache) { unset($_SESSION['user_id']); }
        }
    }
    return $cache;
}
function is_admin(): bool { $u = current_user(); return $u !== null && $u['role'] === 'admin'; }

function require_login(): array
{
    $u = current_user();
    if (!$u) { flash('error', 'Please log in to continue.'); redirect('login.php'); }
    return $u;
}
function require_admin(): array
{
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') { flash('error', 'Admin login required.'); redirect('admin/login.php'); }
    return $u;
}

/** Verifies credentials. Returns an error message, or null on success (session is started). */
function attempt_login(string $email, string $password, bool $adminOnly = false): ?string
{
    $now = time();
    $fails = $_SESSION['login_fails'] ?? ['n' => 0, 'until' => 0];
    if ($fails['until'] > $now) {
        return 'Too many failed attempts. Wait ' . ($fails['until'] - $now) . ' seconds and try again.';
    }
    $st = db()->prepare('SELECT id, password_hash, role FROM users WHERE email = :e');
    $st->execute([':e' => strtolower($email)]);
    $u = $st->fetch();

    if (!$u || !password_verify($password, $u['password_hash'])) {
        $fails['n']++;
        if ($fails['n'] >= 5) { $fails = ['n' => 0, 'until' => $now + 60]; }
        $_SESSION['login_fails'] = $fails;
        return 'Email or password is incorrect.';
    }
    if ($adminOnly && $u['role'] !== 'admin') { return 'This account does not have admin access.'; }

    unset($_SESSION['login_fails']);
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['id'];
    current_user(true);
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
            ->execute([':h' => password_hash($password, PASSWORD_DEFAULT), ':id' => $u['id']]);
    }
    return null;
}
