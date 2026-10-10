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
                throw new Exception('Hanya admin yang dapat men-generate tagihan.');
            }

            $month = (int)($input['month'] ?? date('n'));
            $year = (int)($input['year'] ?? date('Y'));
            $dueDay = (int)getSetting($pdo, 'billing_due_day', '10');

            $res = generateMonthlyBills($pdo, $month, $year, $dueDay);

            echo json_encode([
                'success' => true,
                'message' => "Berhasil men-generate {$res['generated']} tagihan baru untuk periode " . getMonthName($month) . " {$year}. ({$res['skipped_cuti']} pelanggan berstatus Cuti dilewati tanpa denda).",
                'data'    => $res
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
