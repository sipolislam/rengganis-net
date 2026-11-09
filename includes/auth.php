<?php
/**
 * Authentication and Session Management for Rengganis Net
 * Multi-role RBAC: Admin, Kolektor, Teknisi
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';

initAppSession();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'       => (int)$_SESSION['user_id'],
        'username' => $_SESSION['user_username'] ?? '',
        'name'     => $_SESSION['user_name'] ?? '',
        'role'     => $_SESSION['user_role'] ?? 'teknisi',
        'phone'    => $_SESSION['user_phone'] ?? ''
    ];
}

function isAdmin(): bool {
    return isLoggedIn() && (($_SESSION['user_role'] ?? '') === 'admin');
}

function isTeknisi(): bool {
    return isLoggedIn() && (($_SESSION['user_role'] ?? '') === 'teknisi');
}

function isKolektor(): bool {
    return isLoggedIn() && (($_SESSION['user_role'] ?? '') === 'kolektor');
}

function hasRole(string|array $roles): bool {
    if (!isLoggedIn()) {
        return false;
    }
    $userRole = $_SESSION['user_role'] ?? '';
    if (is_array($roles)) {
        return in_array($userRole, $roles, true);
    }
    return $userRole === $roles;
}

function denyAccess(string $message = 'Akses ditolak! Anda tidak memiliki hak akses yang mencukupi untuk tindakan ini (403 Forbidden).'): void {
    http_response_code(403);
    $isJson = (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
        || (!empty($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/api/'))
        || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    
    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    $_SESSION['flash_error'] = $message;
    require_once __DIR__ . '/403.php';
    exit;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk mengakses sistem.';
        header('Location: login.php');
        exit;
    }
}

function requireRole(string|array $roles): void {
    requireLogin();
    if (!hasRole($roles)) {
        $allowed = is_array($roles) ? implode('/', $roles) : $roles;
        denyAccess("Akses ditolak! Menu ini hanya dapat diakses oleh peran {$allowed} (403 Forbidden).");
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        denyAccess('Akses ditolak! Menu Pengaturan Sistem dan Manajemen User hanya dapat diakses oleh Admin Pengelola (403 Forbidden).');
    }
}

