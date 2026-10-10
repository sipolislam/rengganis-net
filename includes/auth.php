<?php
/**
 * Authentication and Session Management for Rengganis Net
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'],
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

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk mengakses sistem.';
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['flash_error'] = 'Akses ditolak! Anda tidak memiliki izin Admin untuk tindakan ini.';
        header('Location: index.php');
        exit;
    }
}
