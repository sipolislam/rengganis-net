<?php
/**
 * API Endpoint: Customers for Rengganis Net
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
        case 'update_status':
            // Both Admin and authorized users can update customer status
            $customerId = (int)($input['customer_id'] ?? 0);
            $newStatus = trim($input['new_status'] ?? '');
            $reason = trim($input['reason'] ?? '');

            if ($customerId <= 0) {
                throw new Exception('ID Pelanggan tidak valid');
            }
            if (!in_array($newStatus, ['aktif', 'cuti', 'isolir'])) {
                throw new Exception('Status tidak valid. Harus aktif, cuti, atau isolir.');
            }

            $success = updateCustomerStatus($pdo, $customerId, $newStatus, $reason, $currentUser['name']);
            if (!$success) {
                throw new Exception('Gagal memperbarui status pelanggan.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Status pelanggan berhasil diubah menjadi ' . ucfirst($newStatus),
                'new_status' => $newStatus
            ]);
            break;

        case 'get_detail':
            $customerId = (int)($_GET['id'] ?? 0);
            if ($customerId <= 0) {
                throw new Exception('ID Pelanggan tidak valid');
            }

            $stmt = $pdo->prepare("
                SELECT c.*, p.name AS package_name, p.speed AS package_speed, p.price AS package_price
                FROM customers c
                JOIN packages p ON c.package_id = p.id
                WHERE c.id = ?
            ");
            $stmt->execute([$customerId]);
            $cust = $stmt->fetch();
            if (!$cust) {
                throw new Exception('Pelanggan tidak ditemukan');
            }

            // Billing history
            $stmtBills = $pdo->prepare("
                SELECT * FROM bills 
                WHERE customer_id = ? 
                ORDER BY period_year DESC, period_month DESC 
                LIMIT 12
            ");
            $stmtBills->execute([$customerId]);
            $bills = $stmtBills->fetchAll();

            // Status logs history
            $stmtLogs = $pdo->prepare("
                SELECT * FROM status_logs 
                WHERE customer_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmtLogs->execute([$customerId]);
            $logs = $stmtLogs->fetchAll();

            echo json_encode([
                'success'  => true,
                'customer' => $cust,
                'bills'    => $bills,
                'logs'     => $logs
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
