<?php
/**
 * API Endpoint: Bills & Payments for Rengganis Net
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

// Helper to get raw JSON payload
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? $_POST;

try {
    switch ($action) {
        case 'pay':
            // Multi-role Authorization: Only Admin and Kolektor/Kasir can record payments (F5 / docs/SECURITY.md)
            if (!hasRole(['admin', 'kolektor'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Akses ditolak! Teknisi tidak memiliki wewenang untuk memproses pelunasan tagihan (403 Forbidden).']);
                exit;
            }

            // CSRF validation
            $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!verifyCsrfToken($csrfToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid atau sesi telah kedaluwarsa.']);
                exit;
            }

            $billId = (int)($input['bill_id'] ?? 0);
            $method = trim($input['payment_method'] ?? 'Tunai');
            $collector = trim($input['collector_name'] ?? $currentUser['name']);
            $notes = trim($input['notes'] ?? '');

            if ($billId <= 0) {
                throw new Exception('ID Tagihan tidak valid');
            }

            // Check if already paid
            $stmtCheck = $pdo->prepare("SELECT status FROM bills WHERE id = ?");
            $stmtCheck->execute([$billId]);
            $currentStatus = $stmtCheck->fetchColumn();
            if ($currentStatus === 'lunas') {
                throw new Exception('Tagihan ini sudah berstatus LUNAS');
            }

            $paidBill = processBillPayment($pdo, $billId, $method, $collector, $notes);
            if (!$paidBill) {
                throw new Exception('Gagal memproses pembayaran tagihan');
            }

            // Prepare complete receipt data
            $receiptData = [
                'bill_id'          => $paidBill['id'],
                'bill_number'      => $paidBill['bill_number'],
                'customer_code'    => $paidBill['customer_code'],
                'customer_name'    => $paidBill['customer_name'],
                'customer_phone'   => $paidBill['customer_phone'],
                'customer_address' => $paidBill['customer_address'],
                'period'           => getMonthName((int)$paidBill['period_month']) . ' ' . $paidBill['period_year'],
                'period_month'     => (int)$paidBill['period_month'],
                'period_year'      => (int)$paidBill['period_year'],
                'package_name'     => $paidBill['package_name'],
                'package_speed'    => $paidBill['package_speed'],
                'amount'           => (float)$paidBill['amount'],
                'amount_formatted' => formatRupiah($paidBill['amount']),
                'status'           => 'LUNAS',
                'date'             => date('d/m/Y H:i'),
                'payment_date'     => formatDate($paidBill['payment_date'], true),
                'payment_method'   => $paidBill['payment_method'],
                'collector_name'   => $paidBill['collector_name'],
                'notes'            => $paidBill['notes'],
                'company_name'     => getSetting($pdo, 'company_name', 'Rengganis Net'),
                'company_tagline'  => getSetting($pdo, 'company_tagline', 'Solusi Internet Cepat & Hemat'),
                'company_phone'    => getSetting($pdo, 'company_phone', '0812-3456-7890'),
                'company_address'  => getSetting($pdo, 'company_address', 'Jl. Raya Rengganis No. 45'),
                'receipt_footer'   => getSetting($pdo, 'receipt_footer', "Terima kasih atas pembayaran Anda.\nSimpan struk ini sebagai bukti sah.")
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Pembayaran berhasil disimpan.',
                'data'    => $receiptData
            ]);
            break;

        case 'get_receipt':
            $billId = (int)($_GET['id'] ?? 0);
            if ($billId <= 0) {
                throw new Exception('ID Tagihan tidak valid');
            }

            $stmt = $pdo->prepare("
                SELECT b.*, c.customer_code, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
                       p.name AS package_name, p.speed AS package_speed
                FROM bills b
                JOIN customers c ON b.customer_id = c.id
                JOIN packages p ON c.package_id = p.id
                WHERE b.id = ?
            ");
            $stmt->execute([$billId]);
            $b = $stmt->fetch();
            if (!$b) {
                throw new Exception('Tagihan tidak ditemukan');
            }

            $receiptData = [
                'bill_id'          => $b['id'],
                'bill_number'      => $b['bill_number'],
                'customer_code'    => $b['customer_code'],
                'customer_name'    => $b['customer_name'],
                'customer_phone'   => $b['customer_phone'],
                'customer_address' => $b['customer_address'],
                'period'           => getMonthName((int)$b['period_month']) . ' ' . $b['period_year'],
                'period_month'     => (int)$b['period_month'],
                'period_year'      => (int)$b['period_year'],
                'package_name'     => $b['package_name'],
                'package_speed'    => $b['package_speed'],
                'amount'           => (float)$b['amount'],
                'amount_formatted' => formatRupiah($b['amount']),
                'status'           => strtoupper($b['status']),
                'date'             => date('d/m/Y H:i'),
                'payment_date'     => $b['payment_date'] ? formatDate($b['payment_date'], true) : '-',
                'payment_method'   => $b['payment_method'] ?? 'Tunai',
                'collector_name'   => $b['collector_name'] ?? 'Admin',
                'notes'            => $b['notes'],
                'company_name'     => getSetting($pdo, 'company_name', 'Rengganis Net'),
                'company_tagline'  => getSetting($pdo, 'company_tagline', 'Solusi Internet Cepat & Hemat'),
                'company_phone'    => getSetting($pdo, 'company_phone', '0812-3456-7890'),
                'company_address'  => getSetting($pdo, 'company_address', 'Jl. Raya Rengganis No. 45'),
                'receipt_footer'   => getSetting($pdo, 'receipt_footer', "Terima kasih atas pembayaran Anda.\nSimpan struk ini sebagai bukti sah.")
            ];

            echo json_encode([
                'success' => true,
                'data'    => $receiptData
            ]);
            break;

        case 'generate_monthly':
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Akses ditolak! Hanya Admin Pengelola yang dapat men-generate tagihan (403 Forbidden).']);
                exit;
            }

            $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!verifyCsrfToken($csrfToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid atau sesi telah kedaluwarsa.']);
                exit;
            }

            $month = (int)($input['month'] ?? date('n'));
            $year = (int)($input['year'] ?? date('Y'));
            if ($month < 1 || $month > 12 || $year < 2020 || $year > 2100) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Parameter periode bulan (1-12) atau tahun tidak valid.']);
                exit;
            }
            $dueDay = (int)getSetting($pdo, 'billing_due_day', '10');

            $res = generateMonthlyBills($pdo, $month, $year, $dueDay);

            $periodLabel = getMonthName($month) . " {$year}";
            if ($res['generated'] === 0) {
                $message = "Seluruh tagihan periode {$periodLabel} sudah terbit sebelumnya ({$res['skipped']} tagihan aktif sudah ada / Idempoten). 0 tagihan ganda dibuat.";
                if ($res['skipped_cuti'] > 0) {
                    $message .= " ({$res['skipped_cuti']} pelanggan Cuti tetap dilewati).";
                }
            } else {
                $cutiInfo = $res['skipped_cuti'] > 0 ? " ({$res['skipped_cuti']} pelanggan berstatus Cuti dilewati tanpa denda fiktif)." : "";
                $message = "Berhasil men-generate {$res['generated']} tagihan baru untuk periode {$periodLabel}.{$cutiInfo}";
            }

            echo json_encode([
                'success' => true,
                'message' => $message,
                'data'    => $res
            ]);
            break;

        case 'search':
            // Fast bill searching and status filtering (T-011 / F3)
            $q = trim($_GET['q'] ?? '');
            $month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
            $year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
            $status = trim($_GET['status'] ?? 'all');
            $limit = (int)($_GET['limit'] ?? 20);
            if ($limit <= 0 || $limit > 100) {
                $limit = 20;
            }

            $sql = "
                SELECT b.*, c.customer_code, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
                       p.name AS package_name, p.speed AS package_speed
                FROM bills b
                JOIN customers c ON b.customer_id = c.id
                JOIN packages p ON c.package_id = p.id
                WHERE 1=1
            ";
            $params = [];

            if ($month >= 1 && $month <= 12) {
                $sql .= " AND b.period_month = :month";
                $params[':month'] = $month;
            }
            if ($year >= 2020 && $year <= 2100) {
                $sql .= " AND b.period_year = :year";
                $params[':year'] = $year;
            }
            if ($status !== 'all' && in_array($status, ['lunas', 'belum_lunas'])) {
                $sql .= " AND b.status = :status";
                $params[':status'] = $status;
            }
            if (!empty($q)) {
                $sql .= " AND (b.bill_number LIKE :q1 OR c.name LIKE :q2 OR c.customer_code LIKE :q3 OR c.phone LIKE :q4)";
                $term = "%{$q}%";
                $params[':q1'] = $term;
                $params[':q2'] = $term;
                $params[':q3'] = $term;
                $params[':q4'] = $term;
            }

            $sql .= " ORDER BY b.period_year DESC, b.period_month DESC, b.id DESC LIMIT " . (int)$limit;
            $stmtSearch = $pdo->prepare($sql);
            $stmtSearch->execute($params);
            $results = $stmtSearch->fetchAll();

            $formatted = array_map(function($b) {
                return [
                    'id'               => (int)$b['id'],
                    'bill_number'      => $b['bill_number'],
                    'customer_id'      => (int)$b['customer_id'],
                    'customer_code'    => $b['customer_code'],
                    'customer_name'    => $b['customer_name'],
                    'customer_phone'   => $b['customer_phone'],
                    'customer_address' => $b['customer_address'],
                    'period_month'     => (int)$b['period_month'],
                    'period_year'      => (int)$b['period_year'],
                    'period_label'     => getMonthName((int)$b['period_month']) . ' ' . $b['period_year'],
                    'package_name'     => $b['package_name'],
                    'package_speed'    => $b['package_speed'],
                    'amount'           => (float)$b['amount'],
                    'amount_formatted' => formatRupiah($b['amount']),
                    'status'           => $b['status'],
                    'due_date'         => $b['due_date'],
                    'payment_date'     => $b['payment_date'],
                    'payment_method'   => $b['payment_method'],
                    'collector_name'   => $b['collector_name']
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
