<?php
/**
 * Admin authentication - session-based login
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getAdminId(): ?int {
    return isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
}

function adminLogin(string $username, string $password): bool {
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }
    $_SESSION['admin_id'] = (int)$row['id'];
    $_SESSION['admin_username'] = $username;
    return true;
}

function adminLogout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
