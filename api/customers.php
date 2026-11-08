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
            $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!verifyCsrfToken($csrfToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid atau sesi telah kedaluwarsa.']);
                exit;
            }

            $customerId = (int)($input['customer_id'] ?? 0);
            $newStatus = trim($input['new_status'] ?? '');
            $reason = trim($input['reason'] ?? '');

            if ($customerId <= 0) {
                throw new Exception('ID Pelanggan tidak valid');
            }
            if (!in_array($newStatus, ['aktif', 'cuti', 'isolir'])) {
                throw new Exception('Status tidak valid. Harus aktif, cuti, atau isolir.');
            }

            $billInfo = null;
            $success = updateCustomerStatus($pdo, $customerId, $newStatus, $reason, $currentUser['name'], true, $billInfo);
            if (!$success) {
                throw new Exception('Gagal memperbarui status pelanggan.');
            }

            $msg = 'Status pelanggan berhasil diubah menjadi ' . ucfirst($newStatus);
            if ($billInfo) {
                $msg .= ". Tagihan bulan berjalan ({$billInfo['bill_number']}) otomatis diterbitkan.";
            }

            echo json_encode([
                'success'        => true,
                'message'        => $msg,
                'new_status'     => $newStatus,
                'generated_bill' => $billInfo
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

        case 'search':
            // Fast customer search by name, code, phone, address, or IP (T-011 / F2)
            $q = trim($_GET['q'] ?? '');
            $status = trim($_GET['status'] ?? 'all');
            $packageId = (int)($_GET['package_id'] ?? 0);
            $limit = (int)($_GET['limit'] ?? 20);
            if ($limit <= 0 || $limit > 100) {
                $limit = 20;
            }

            $sql = "
                SELECT c.*, p.name AS package_name, p.speed AS package_speed, p.price AS package_price
                FROM customers c
                JOIN packages p ON c.package_id = p.id
                WHERE 1=1
            ";
            $params = [];

            if ($status !== 'all' && in_array($status, ['aktif', 'cuti', 'isolir'])) {
                $sql .= " AND c.status = :status";
                $params[':status'] = $status;
            }

            if ($packageId > 0) {
                $sql .= " AND c.package_id = :package_id";
                $params[':package_id'] = $packageId;
            }

            if (!empty($q)) {
                $sql .= " AND (c.name LIKE :q1 OR c.customer_code LIKE :q2 OR c.phone LIKE :q3 OR c.address LIKE :q4 OR c.ip_address LIKE :q5)";
                $term = "%{$q}%";
                $params[':q1'] = $term;
                $params[':q2'] = $term;
                $params[':q3'] = $term;
                $params[':q4'] = $term;
                $params[':q5'] = $term;
            }

            $sql .= " ORDER BY c.customer_code ASC LIMIT " . (int)$limit;
            $stmtSearch = $pdo->prepare($sql);
            $stmtSearch->execute($params);
            $results = $stmtSearch->fetchAll();

            $formatted = array_map(function($c) {
                return [
                    'id'                => (int)$c['id'],
                    'customer_code'     => $c['customer_code'],
                    'name'              => $c['name'],
                    'phone'             => $c['phone'],
                    'phone_display'     => formatDisplayPhone($c['phone']),
                    'wa_number'         => formatWhatsAppNumber($c['phone']),
                    'address'           => $c['address'],
                    'package_id'        => (int)$c['package_id'],
                    'package_name'      => $c['package_name'],
                    'package_speed'     => $c['package_speed'],
                    'package_price'     => (float)$c['package_price'],
                    'price_formatted'   => formatRupiah($c['package_price']),
                    'ip_address'        => $c['ip_address'],
                    'status'            => $c['status'],
                    'installation_date' => $c['installation_date']
                ];
            }, $results);

            echo json_encode([
                'success' => true,
                'count'   => count($formatted),
                'data'    => $formatted
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
