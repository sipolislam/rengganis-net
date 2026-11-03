<?php
/**
 * API Endpoint: Packages Management for Rengganis Net (T-010 / Setup)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();
$currentUser = getCurrentUser();
$action = $_GET['action'] ?? '';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? $_POST;

try {
    switch ($action) {
        case 'list':
            $activeOnly = !empty($_GET['active_only']);
            $sql = "
                SELECT p.*, COUNT(c.id) AS subscriber_count
                FROM packages p
                LEFT JOIN customers c ON p.id = c.package_id
            ";
            if ($activeOnly) {
                $sql .= " WHERE p.is_active = 1 ";
            }
            $sql .= " GROUP BY p.id ORDER BY p.price ASC";
            $packages = $pdo->query($sql)->fetchAll();

            echo json_encode([
                'success' => true,
                'data'    => $packages
            ]);
            break;

        case 'get_detail':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID Paket tidak valid.');
            }

            $stmt = $pdo->prepare("
                SELECT p.*, COUNT(c.id) AS subscriber_count
                FROM packages p
                LEFT JOIN customers c ON p.id = c.package_id
                WHERE p.id = ?
                GROUP BY p.id
            ");
            $stmt->execute([$id]);
            $package = $stmt->fetch();
            if (!$package) {
                throw new Exception('Paket tidak ditemukan.');
            }

            echo json_encode([
                'success' => true,
                'data'    => $package
            ]);
            break;

        case 'toggle_active':
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Akses ditolak! Hanya Admin yang dapat mengubah status paket (403 Forbidden).']);
                exit;
            }

            $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!verifyCsrfToken($csrfToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid atau sesi telah kedaluwarsa.']);
                exit;
            }

            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID Paket tidak valid.');
            }

            $stmt = $pdo->prepare("UPDATE packages SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);

            $stmtCheck = $pdo->prepare("SELECT is_active, name FROM packages WHERE id = ?");
            $stmtCheck->execute([$id]);
            $updated = $stmtCheck->fetch();

            $statusLabel = ((int)$updated['is_active'] === 1) ? 'diaktifkan' : 'dinonaktifkan';
            echo json_encode([
                'success'   => true,
                'message'   => "Paket '{$updated['name']}' berhasil {$statusLabel}.",
                'is_active' => (int)$updated['is_active']
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
