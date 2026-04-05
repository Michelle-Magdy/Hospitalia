<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $st = db()->prepare('SELECT id, username, role FROM hms_users WHERE id = ?');
    $st->execute([(int) $_SESSION['user_id']]);
    $row = $st->fetch();
    return $row ?: null;
}

function require_login(): void {
    if (current_user() === null) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    $u = current_user();
    if ($u === null || $u['role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
}

function is_admin(): bool {
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

function h(?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
