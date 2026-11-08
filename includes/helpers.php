<?php
/**
 * Utility & Business Logic Helpers for Rengganis Net
 */

require_once __DIR__ . '/../config/database.php';

// Format Rupiah
function formatRupiah($amount): string {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

// Indonesian Month Name
function getMonthName(int $month): string {
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $months[$month] ?? '';
}

// Format Date to Indonesian
function formatDate(?string $datetime, bool $withTime = false): string {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    if (!$time) return '-';
    
    $d = date('j', $time);
    $m = getMonthName((int)date('n', $time));
    $y = date('Y', $time);
    
    if ($withTime) {
        $his = date('H:i', $time);
        return "$d $m $y, $his WIB";
    }
    return "$d $m $y";
}

// Contact & Phone Validation Helpers (F2 / T-004)
function validatePhoneNumber(?string $phone): bool {
    if (empty($phone)) {
        return false;
    }
    $clean = preg_replace('/[^\d+]/', '', trim($phone));
    return (bool)preg_match('/^(?:\+?62|0)8[1-9][0-9]{7,11}$/', $clean);
}

function formatWhatsAppNumber(?string $phone): string {
    if (empty($phone)) {
        return '';
    }
    $digits = preg_replace('/\D/', '', $phone);
    if (str_starts_with($digits, '0')) {
        return '62' . substr($digits, 1);
    }
    return $digits;
}

function formatDisplayPhone(?string $phone): string {
    if (empty($phone)) {
        return '-';
    }
    $digits = preg_replace('/\D/', '', $phone);
    if (str_starts_with($digits, '62')) {
        $digits = '0' . substr($digits, 2);
    }
    if (strlen($digits) >= 10) {
        $part1 = substr($digits, 0, 4);
        $part2 = substr($digits, 4, 4);
        $part3 = substr($digits, 8);
        return "{$part1}-{$part2}-{$part3}";
    }
    return $phone;
}

// Flash messages
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash_' . $type] = $message;
}

function getFlash(string $type): ?string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

// CSRF Protection
function generateCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(generateCsrfToken()) . '">';
}

function validateCsrfOrDie(?string $redirectUrl = null): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!verifyCsrfToken($token)) {
        if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid atau sesi telah kedaluwarsa.']);
            exit;
        }
        setFlash('error', 'Token keamanan CSRF tidak valid atau sesi telah kedaluwarsa. Silakan coba lagi.');
        if ($redirectUrl) {
            header('Location: ' . $redirectUrl);
            exit;
        }
        http_response_code(403);
        if (file_exists(__DIR__ . '/403.php')) {
            $message = 'Token keamanan CSRF tidak valid atau sesi telah kedaluwarsa. Silakan muat ulang halaman.';
            require __DIR__ . '/403.php';
        } else {
            echo '403 Forbidden: Invalid CSRF Token.';
        }
        exit;
    }
}

// Sanitize output
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Get Setting
function getSetting(PDO $pdo, string $key, string $default = ''): string {
    static $settingsCache = null;
    if ($settingsCache === null) {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settingsCache = [];
        while ($row = $stmt->fetch()) {
            $settingsCache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settingsCache[$key] ?? $default;
}

// Status Badges (Neo-Skeuomorphic with Glowing LEDs)
function getStatusBadge(string $status): string {
    $status = strtolower($status);
    switch ($status) {
        case 'aktif':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-gradient-to-b from-emerald-50 to-emerald-100/80 text-emerald-800 border border-emerald-300 shadow-[0_2px_4px_rgba(16,185,129,0.15)]"><span class="led-pulse active-green"></span>AKTIF</span>';
        case 'cuti':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-gradient-to-b from-amber-50 to-amber-100/80 text-amber-800 border border-amber-300 shadow-[0_2px_4px_rgba(245,158,11,0.15)]"><span class="led-pulse active-amber"></span>CUTI / LIBUR</span>';
        case 'isolir':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-gradient-to-b from-rose-50 to-rose-100/80 text-rose-800 border border-rose-300 shadow-[0_2px_4px_rgba(244,63,94,0.15)]"><span class="led-pulse active-rose"></span>ISOLIR</span>';
        case 'lunas':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-gradient-to-b from-emerald-50 to-emerald-100/80 text-emerald-800 border border-emerald-300 shadow-[0_2px_4px_rgba(16,185,129,0.15)]"><i class="fa-solid fa-circle-check text-emerald-600"></i>LUNAS</span>';
        case 'belum_lunas':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-gradient-to-b from-rose-50 to-rose-100/80 text-rose-800 border border-rose-300 shadow-[0_2px_4px_rgba(244,63,94,0.15)]"><span class="led-pulse active-rose"></span>BELUM LUNAS</span>';
        default:
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-800">' . e($status) . '</span>';
    }
}

/**
 * Check if a customer status is exempt from monthly billing (FR-03 / R1 / Guardrail 11).
 * Customers with status = 'cuti' are strictly exempt with 0 fictional bills.
 *
 * @param string $status
 * @return bool True if customer is exempt from billing
 */
function isCustomerExemptFromBilling(string $status): bool {
    return in_array(strtolower(trim($status)), ['cuti'], true);
}

/**
 * Generate monthly bills for all active customers.
 * EXCLUDES customers with status = 'cuti' or 'isolir' (as per FR-03).
 *
 * @param PDO $pdo
 * @param int $month
 * @param int $year
 * @param int $dueDay
 * @return array ['generated' => int, 'skipped' => int, 'skipped_cuti' => int]
 */
function generateMonthlyBills(PDO $pdo, int $month, int $year, int $dueDay = 10): array {
    if ($dueDay === 10) {
        $settingDue = (int)getSetting($pdo, 'billing_due_day', '10');
        if ($settingDue >= 1 && $settingDue <= 28) {
            $dueDay = $settingDue;
        }
    }

    $monthPadded = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
    $dueDayPadded = str_pad((string)$dueDay, 2, '0', STR_PAD_LEFT);
    $dueDate = "{$year}-{$monthPadded}-{$dueDayPadded}";

    // Query customers on leave ('cuti') for exemption tracking (FR-03 / R1)
    $stmtCuti = $pdo->query("SELECT customer_code FROM customers WHERE status = 'cuti'");
    $cutiCodes = $stmtCuti->fetchAll(PDO::FETCH_COLUMN);
    $skippedCuti = count($cutiCodes);

    // Count customers in 'isolir'
    $stmtIsolirCount = $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'isolir'");
    $skippedIsolir = (int)$stmtIsolirCount->fetchColumn();

    // Hitung akhir bulan target
    $lastDayOfMonth = date('Y-m-t', strtotime("{$year}-{$monthPadded}-01"));

    // Select active customers who don't have a bill for this month & year
    // Hard filter: status = 'aktif' strictly prevents any leave customer from being selected
    $stmt = $pdo->prepare("
        SELECT c.id, c.customer_code, c.name, c.status, p.price
        FROM customers c
        JOIN packages p ON c.package_id = p.id
        WHERE c.status = 'aktif'
          AND c.installation_date <= :period_end
          AND c.id NOT IN (
              SELECT customer_id FROM bills 
              WHERE period_month = :m AND period_year = :y
          )
        ORDER BY c.id ASC
    ");
    $stmt->execute([
        ':period_end' => $lastDayOfMonth,
        ':m'          => $month,
        ':y'          => $year
    ]);
    $eligibleCustomers = $stmt->fetchAll();

    // Calculate active customers already billed for this month & year (idempotency tracking / R1)
    $stmtAlreadyBilled = $pdo->prepare("
        SELECT COUNT(DISTINCT b.customer_id) 
        FROM bills b 
        JOIN customers c ON b.customer_id = c.id 
        WHERE b.period_month = :m AND b.period_year = :y AND c.status = 'aktif'
    ");
    $stmtAlreadyBilled->execute([':m' => $month, ':y' => $year]);
    $skippedAlreadyBilled = (int)$stmtAlreadyBilled->fetchColumn();

    if (empty($eligibleCustomers)) {
        return [
            'generated'      => 0,
            'skipped'        => $skippedAlreadyBilled,
            'skipped_cuti'   => $skippedCuti,
            'skipped_isolir' => $skippedIsolir,
            'cuti_customers' => $cutiCodes
        ];
    }

    $generatedCount = 0;
    $pdo->beginTransaction();
    try {
        // Query nomor invoice urut terakhir dalam periode berjalan dengan transaction lock
        $prefix = "INV/{$year}{$monthPadded}/";
        $stmtSeq = $pdo->prepare("SELECT MAX(bill_number) FROM bills WHERE bill_number LIKE :prefix FOR UPDATE");
        $stmtSeq->execute([':prefix' => $prefix . '%']);
        $maxBill = $stmtSeq->fetchColumn();
        $seq = 0;
        if ($maxBill) {
            $parts = explode('/', $maxBill);
            $seq = (int)end($parts);
        }

        $insertStmt = $pdo->prepare("
            INSERT INTO bills (bill_number, customer_id, period_month, period_year, amount, due_date, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'belum_lunas', NOW())
        ");

        foreach ($eligibleCustomers as $cust) {
            // Defense-in-depth: Never generate bill for cuti or exempt customer (Guardrail 11)
            if (isset($cust['status']) && isCustomerExemptFromBilling($cust['status'])) {
                continue;
            }

            $seq++;
            $billNum = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
            try {
                $insertStmt->execute([
                    $billNum,
                    $cust['id'],
                    $month,
                    $year,
                    $cust['price'],
                    $dueDate
                ]);
                $generatedCount++;
            } catch (PDOException $e) {
                // Database unique_customer_period duplicate trap: skip gracefully without duplicate
                if ($e->getCode() == 23000 || ($e->errorInfo[1] ?? 0) == 1062) {
                    $skippedAlreadyBilled++;
                    continue;
                }
                throw $e;
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    return [
        'generated'      => $generatedCount,
        'skipped'        => $skippedAlreadyBilled,
        'skipped_cuti'   => $skippedCuti,
        'skipped_isolir' => $skippedIsolir,
        'cuti_customers' => $cutiCodes
    ];
}

/**
 * Generate a single bill for a customer (e.g. for mid-month reactivation or new installation)
 */
function generateCustomerBill(PDO $pdo, int $customerId, int $month, int $year, int $dueDay = 10): ?array {
    if ($dueDay === 10) {
        $settingDue = (int)getSetting($pdo, 'billing_due_day', '10');
        if ($settingDue >= 1 && $settingDue <= 28) {
            $dueDay = $settingDue;
        }
    }

    $monthPadded = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
    $dueDayPadded = str_pad((string)$dueDay, 2, '0', STR_PAD_LEFT);
    $dueDate = "{$year}-{$monthPadded}-{$dueDayPadded}";

    // Check if bill exists
    $stmtCheck = $pdo->prepare("SELECT id FROM bills WHERE customer_id = ? AND period_month = ? AND period_year = ?");
    $stmtCheck->execute([$customerId, $month, $year]);
    if ($stmtCheck->fetch()) {
        return null; // Already exists
    }

    // Customer details
    $stmtCust = $pdo->prepare("SELECT c.*, p.price FROM customers c JOIN packages p ON c.package_id = p.id WHERE c.id = ?");
    $stmtCust->execute([$customerId]);
    $cust = $stmtCust->fetch();
    if (!$cust) return null;

    // Hard Limit Guardrail 11 / FR-03 / R1: Never generate bill for a customer on leave ('cuti')
    if (isCustomerExemptFromBilling($cust['status'])) {
        return null;
    }

    $prefix = "INV/{$year}{$monthPadded}/";
    $stmtSeq = $pdo->prepare("SELECT MAX(bill_number) FROM bills WHERE bill_number LIKE :prefix");
    $stmtSeq->execute([':prefix' => $prefix . '%']);
    $maxBill = $stmtSeq->fetchColumn();
    $seq = 0;
    if ($maxBill) {
        $parts = explode('/', $maxBill);
        $seq = (int)end($parts);
    }
    $seq++;
    $billNum = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

    try {
        $insertStmt = $pdo->prepare("
            INSERT INTO bills (bill_number, customer_id, period_month, period_year, amount, due_date, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'belum_lunas', NOW())
        ");
        $insertStmt->execute([
            $billNum,
            $customerId,
            $month,
            $year,
            $cust['price'],
            $dueDate
        ]);
        $newId = (int)$pdo->lastInsertId();

        return [
            'id'          => $newId,
            'bill_number' => $billNum,
            'amount'      => $cust['price']
        ];
    } catch (PDOException $e) {
        // Trap duplicate constraint violation (idempotent: return null without inserting duplicate)
        if ($e->getCode() == 23000 || ($e->errorInfo[1] ?? 0) == 1062) {
            return null;
        }
        throw $e;
    }
}

/**
 * Change customer status and log it (FR-03 & Section 11 Analytics).
 * If reactivated from 'cuti' to 'aktif', automatically generates a bill for the current period (F2 / T-009).
 *
 * @param PDO $pdo
 * @param int $customerId
 * @param string $newStatus
 * @param string $reason
 * @param string $changedBy
 * @param bool $autoBillOnReactivation
 * @param array|null &$generatedBillInfo Output parameter holding created bill data if generated
 * @param int|null $targetMonth Optional target month for reactivation billing (defaults to current month)
 * @param int|null $targetYear Optional target year for reactivation billing (defaults to current year)
 * @return bool True on success
 */
function updateCustomerStatus(
    PDO $pdo, 
    int $customerId, 
    string $newStatus, 
    string $reason, 
    string $changedBy, 
    bool $autoBillOnReactivation = true,
    ?array &$generatedBillInfo = null,
    ?int $targetMonth = null,
    ?int $targetYear = null
): bool {
    $stmtCur = $pdo->prepare("SELECT status FROM customers WHERE id = ?");
    $stmtCur->execute([$customerId]);
    $oldStatus = $stmtCur->fetchColumn();
    if (!$oldStatus) return false;

    if ($oldStatus === $newStatus) return true; // No change

    $pdo->beginTransaction();
    try {
        // Update customer
        $stmtUp = $pdo->prepare("UPDATE customers SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmtUp->execute([$newStatus, $customerId]);

        // Insert log
        $stmtLog = $pdo->prepare("INSERT INTO status_logs (customer_id, old_status, new_status, reason, changed_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmtLog->execute([$customerId, $oldStatus, $newStatus, $reason ?: null, $changedBy]);

        // F2 / T-009 Acceptance Criteria: If reactivated to 'aktif' in mid-month from 'cuti', automatically generate current month's bill
        $createdBill = null;
        if ($oldStatus === 'cuti' && $newStatus === 'aktif' && $autoBillOnReactivation) {
            $curMonth = $targetMonth ?: (int)date('n');
            $curYear = $targetYear ?: (int)date('Y');
            $createdBill = generateCustomerBill($pdo, $customerId, $curMonth, $curYear);
        }

        $pdo->commit();

        if (func_num_args() >= 7) {
            $generatedBillInfo = $createdBill;
        }

        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Process a bill payment and mark as Lunas (T-014 / F5).
 * Validates bill existence, prevents double-payment via atomic transaction,
 * records payment method, payment timestamp, collector name, and optional notes.
 */
function processBillPayment(PDO $pdo, int $billId, string $method, string $collectorName, ?string $notes = null): ?array {
    $stmt = $pdo->prepare("
        SELECT b.*, c.customer_code, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
               p.name AS package_name, p.speed AS package_speed
        FROM bills b
        JOIN customers c ON b.customer_id = c.id
        JOIN packages p ON c.package_id = p.id
        WHERE b.id = ?
    ");
    $stmt->execute([$billId]);
    $bill = $stmt->fetch();
    if (!$bill) return null;

    // Idempotency: prevent double payment if already paid
    if ($bill['status'] === 'lunas') {
        return null;
    }

    $validMethods = [
        'Tunai', 'Transfer BCA', 'Transfer BRI', 'Transfer Mandiri', 'Transfer BNI',
        'DANA / Gopay', 'DANA', 'GoPay', 'OVO', 'QRIS', 'Lainnya'
    ];
    $method = trim($method);
    if (empty($method)) {
        $method = 'Tunai';
    }

    $collectorName = trim($collectorName);
    if (empty($collectorName)) {
        $collectorName = 'Admin Kasir';
    }
    $notes = !empty($notes) ? trim($notes) : null;
    $now = date('Y-m-d H:i:s');

    $pdo->beginTransaction();
    try {
        $stmtUp = $pdo->prepare("
            UPDATE bills 
            SET status = 'lunas', payment_date = ?, payment_method = ?, collector_name = ?, notes = ?, updated_at = NOW()
            WHERE id = ? AND status = 'belum_lunas'
        ");
        $stmtUp->execute([$now, $method, $collectorName, $notes, $billId]);
        if ($stmtUp->rowCount() === 0) {
            $pdo->rollBack();
            return null; // Concurrently updated
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    // Refresh updated bill
    $bill['status'] = 'lunas';
    $bill['payment_date'] = $now;
    $bill['payment_method'] = $method;
    $bill['collector_name'] = $collectorName;
    $bill['notes'] = $notes;

    return $bill;
}


/**
 * Generate formatted WhatsApp notification message for a bill (F4 / R2).
 * Contains customer details, bill amount, period, due date, and structured dual-channel payment options:
 * 1. Online payment via QR code / bank transfer
 * 2. Offline payment with instruction to prepare exact cash ("siapkan uang tunai sejumlah tagihannya" / uang pas)
 *
 * @param array $bill Bill data (with customer and package details)
 * @param PDO|null $pdo Database connection to load settings
 * @return string Formatted plain text WhatsApp message
 */
function buildBillingWhatsAppMessage(array $bill, ?PDO $pdo = null): string {
    if (!$pdo) {
        $pdo = getDB();
    }

    $companyName = getSetting($pdo, 'company_name', 'Rengganis Net');
    $companyTagline = getSetting($pdo, 'company_tagline', 'Solusi Internet Cepat & Hemat');
    $companyPhone = getSetting($pdo, 'company_phone', '0812-3456-7890');
    $bankInfo = getSetting($pdo, 'bank_info', "BCA: 123-456-7890 (a.n Rengganis Net)\nBRI: 0987-01-023456-50-1 (a.n Rengganis Net)\nDANA/Gopay: 0812-3456-7890");

    $customerName = trim($bill['customer_name'] ?? $bill['name'] ?? 'Pelanggan');
    $customerCode = trim($bill['customer_code'] ?? '-');
    $billNumber = trim($bill['bill_number'] ?? '-');
    $periodMonth = (int)($bill['period_month'] ?? date('n'));
    $periodYear = (int)($bill['period_year'] ?? date('Y'));
    $periodLabel = getMonthName($periodMonth) . ' ' . $periodYear;
    $packageName = trim($bill['package_name'] ?? 'Paket WiFi');
    $packageSpeed = trim($bill['package_speed'] ?? '');
    $pkgFull = $packageSpeed ? "{$packageName} ({$packageSpeed})" : $packageName;
    $amountFormatted = formatRupiah((float)($bill['amount'] ?? 0));
    $dueDate = !empty($bill['due_date']) ? formatDate($bill['due_date']) : '-';

    $msg = "*TAGIHAN INTERNET WIFI - {$companyName}*\n";
    $msg .= "---------------------------------------------\n";
    $msg .= "Yth. Pelanggan *{$customerName}* ({$customerCode}),\n\n";
    $msg .= "Berikut rincian tagihan internet WiFi bulanan Anda:\n";
    $msg .= "📄 No. Invoice : *{$billNumber}*\n";
    $msg .= "📦 Paket : *{$pkgFull}*\n";
    $msg .= "🗓️ Periode : *{$periodLabel}*\n";
    $msg .= "💰 Total Tagihan : *{$amountFormatted}*\n";
    $msg .= "⏰ Batas Jatuh Tempo : *{$dueDate}*\n\n";
    $msg .= "*PILIHAN METODE PEMBAYARAN:*\n\n";
    $bankLines = preg_split('/\r\n|\r|\n/', trim($bankInfo));
    $bankIndented = implode("\n   ", array_map('trim', $bankLines));

    $msg .= "1️⃣ *PEMBAYARAN ONLINE (KODE QR / TRANSFER):*\n";
    $msg .= "   Silakan transfer ke rekening resmi atau scan kode QR:\n";
    $msg .= "   " . $bankIndented . "\n";
    $msg .= "   📲 Tautan Pembayaran / Kode QR: https://rengganis.net/pay?inv=" . urlencode($billNumber) . "\n";
    $msg .= "   *(Mohon kirimkan foto/tangkapan layar bukti transfer setelah pembayaran)*\n\n";
    $msg .= "2️⃣ *PEMBAYARAN OFFLINE (PETUGAS KOLEKTOR):*\n";
    $msg .= "   Jika memilih offline, mohon siapkan uang tunai sejumlah tagihannya (*{$amountFormatted}*) dengan uang pas saat petugas kasir/kolektor kami berkunjung ke lokasi Anda.\n\n";
    $msg .= "Terima kasih atas kerja samanya.\n";
    $msg .= "_{$companyName} - {$companyTagline}_\n";
    $msg .= "📞 Bantuan & Layanan: {$companyPhone}";

    return $msg;
}

/**
 * Generate full wa.me URL for billing notification (F4 / R2).
 *
 * @param array $bill Bill data (with customer phone)
 * @param PDO|null $pdo Database connection
 * @return string WhatsApp click-to-chat URL
 */
function buildBillingWhatsAppUrl(array $bill, ?PDO $pdo = null): string {
    $phone = $bill['customer_phone'] ?? $bill['phone'] ?? '';
    $waPhone = formatWhatsAppNumber($phone);
    $message = buildBillingWhatsAppMessage($bill, $pdo);

    if (empty($waPhone)) {
        return '#';
    }

    return "https://wa.me/{$waPhone}?text=" . rawurlencode($message);
}


/**
 * Generate formatted WhatsApp payment receipt / confirmation message for a paid bill (F4 / T-013).
 * Contains official transaction reference number (bill_number), customer details, payment method,
 * payment timestamp, collector name, and receipt footer.
 *
 * @param array $bill Paid bill data
 * @param PDO|null $pdo Database connection
 * @return string Formatted plain text receipt message
 */
function buildPaymentReceiptWhatsAppMessage(array $bill, ?PDO $pdo = null): string {
    if (!$pdo) {
        $pdo = getDB();
    }

    $companyName = getSetting($pdo, 'company_name', 'Rengganis Net');
    $companyTagline = getSetting($pdo, 'company_tagline', 'Solusi Internet Cepat & Hemat');
    $companyPhone = getSetting($pdo, 'company_phone', '0812-3456-7890');
    $receiptFooter = getSetting($pdo, 'receipt_footer', "Terima kasih atas pembayaran Anda.\nSimpan struk ini sebagai bukti pembayaran yang sah.");

    $customerName = trim($bill['customer_name'] ?? $bill['name'] ?? 'Pelanggan');
    $customerCode = trim($bill['customer_code'] ?? '-');
    $customerAddress = trim($bill['customer_address'] ?? $bill['address'] ?? '-');
    $billNumber = trim($bill['bill_number'] ?? '-');
    $periodMonth = (int)($bill['period_month'] ?? date('n'));
    $periodYear = (int)($bill['period_year'] ?? date('Y'));
    $periodLabel = getMonthName($periodMonth) . ' ' . $periodYear;
    $packageName = trim($bill['package_name'] ?? 'Paket WiFi');
    $packageSpeed = trim($bill['package_speed'] ?? '');
    $pkgFull = $packageSpeed ? "{$packageName} ({$packageSpeed})" : $packageName;
    $amountFormatted = formatRupiah((float)($bill['amount'] ?? 0));
    $paymentDate = !empty($bill['payment_date']) ? formatDate($bill['payment_date'], true) : date('d/m/Y H:i');
    $paymentMethod = trim($bill['payment_method'] ?? 'Tunai');
    $collectorName = trim($bill['collector_name'] ?? 'Admin Kasir');
    $notes = trim($bill['notes'] ?? '');

    $msg = "*BUKTI PEMBAYARAN SAH (KUITANSI DIGITAL)*\n";
    $msg .= "*{$companyName}* - {$companyTagline}\n";
    $msg .= "---------------------------------------------\n";
    $msg .= "Terima kasih! Pembayaran tagihan internet WiFi Anda telah kami terima dan diverifikasi.\n\n";
    $msg .= "📄 No. Transaksi / Kuitansi : *{$billNumber}*\n";
    $msg .= "👤 Pelanggan : *{$customerName}* ({$customerCode})\n";
    if ($customerAddress !== '-') {
        $msg .= "📍 Alamat : {$customerAddress}\n";
    }
    $msg .= "📦 Paket Layanan : *{$pkgFull}*\n";
    $msg .= "🗓️ Periode Tagihan : *{$periodLabel}*\n";
    $msg .= "💰 Total Pelunasan : *{$amountFormatted}*\n";
    $msg .= "💳 Metode Pembayaran : *{$paymentMethod}*\n";
    $msg .= "📅 Waktu Transaksi : *{$paymentDate}*\n";
    $msg .= "👨‍💼 Petugas Kasir / Kolektor : *{$collectorName}*\n";
    if (!empty($notes)) {
        $msg .= "📝 Catatan : {$notes}\n";
    }
    $msg .= "✅ Status Pembayaran : *LUNAS (SAH)*\n\n";
    $msg .= "---------------------------------------------\n";
    $msg .= trim($receiptFooter) . "\n";
    $msg .= "📞 Bantuan & Layanan: {$companyPhone}";

    return $msg;
}

/**
 * Generate full wa.me URL for payment receipt confirmation (F4 / T-013).
 *
 * @param array $bill Paid bill data
 * @param PDO|null $pdo Database connection
 * @return string WhatsApp click-to-chat URL
 */
function buildPaymentReceiptWhatsAppUrl(array $bill, ?PDO $pdo = null): string {
    $phone = $bill['customer_phone'] ?? $bill['phone'] ?? '';
    $waPhone = formatWhatsAppNumber($phone);
    $message = buildPaymentReceiptWhatsAppMessage($bill, $pdo);

    if (empty($waPhone)) {
        return '#';
    }

    return "https://wa.me/{$waPhone}?text=" . rawurlencode($message);
}

/**
 * Generate formatted CSV export string for financial reports (F6 / T-021).
 * Includes UTF-8 BOM, report metadata, detailed transactions table with columns,
 * grand total row, and executive KPI summary block.
 *
 * @param array $bills Array of bill rows
 * @param array $aggregates Aggregated KPI stats (total_bills, total_bill_amount, total_income, count_paid, total_unpaid, count_unpaid, collection_rate)
 * @param array $methodRows Payment method breakdown rows (method, count, total)
 * @param array $meta Report metadata (company_name, period_label, filter_status, user_name, total_cuti)
 * @return string Complete CSV content
 */
function buildFinancialReportCsvString(array $bills, array $aggregates = [], array $methodRows = [], array $meta = []): string {
    $fp = fopen('php://temp', 'r+');
    
    // Write UTF-8 BOM for Microsoft Excel compatibility
    fputs($fp, "\xEF\xBB\xBF");

    $writeRow = function(array $fields) use ($fp): void {
        fputcsv($fp, $fields, ',', '"', "\\");
    };

    $companyName = $meta['company_name'] ?? 'Rengganis Net';
    $periodLabel = $meta['period_label'] ?? '-';
    $filterStatus = strtoupper(str_replace('_', ' ', $meta['filter_status'] ?? 'all'));
    $userName = $meta['user_name'] ?? 'Admin';
    $exportDate = date('d/m/Y H:i') . ' WIB';

    // 1. Report Metadata Header
    $writeRow(['# LAPORAN REKAPITULASI KEUANGAN - ' . strtoupper($companyName)]);
    $writeRow(['# Periode', $periodLabel]);
    $writeRow(['# Filter Status', $filterStatus]);
    $writeRow(['# Tanggal Unduh', $exportDate]);
    $writeRow(['# Petugas / Admin', $userName]);
    $writeRow([]);

    // 2. Table Column Headers
    $writeRow([
        'No',
        'No Invoice',
        'Kode Pelanggan',
        'Nama Pelanggan',
        'No Telp',
        'Alamat',
        'Paket WiFi',
        'Tarif (Rp)',
        'Status',
        'Tanggal Bayar',
        'Metode Bayar',
        'Kolektor'
    ]);

    // 3. Transactions Data Rows
    $no = 1;
    $grandTotal = 0.0;
    foreach ($bills as $b) {
        $amount = (float)($b['amount'] ?? 0);
        $grandTotal += $amount;
        $isLunas = ($b['status'] ?? '') === 'lunas';
        $payDate = !empty($b['payment_date']) ? date('d/m/Y H:i', strtotime($b['payment_date'])) : '-';

        $writeRow([
            $no++,
            $b['bill_number'] ?? '',
            $b['customer_code'] ?? '',
            $b['customer_name'] ?? '',
            $b['customer_phone'] ?? '',
            $b['customer_address'] ?? '',
            $b['package_name'] ?? '',
            $amount,
            $isLunas ? 'LUNAS' : 'BELUM LUNAS',
            $payDate,
            $b['payment_method'] ?? '-',
            $b['collector_name'] ?? '-'
        ]);
    }

    // 4. Grand Total Summary Row
    $writeRow([
        'TOTAL KESELURUHAN (' . count($bills) . ' Transaksi)',
        '',
        '',
        '',
        '',
        '',
        '',
        $grandTotal,
        '',
        '',
        '',
        ''
    ]);

    // 5. Executive KPI Summary Block
    $writeRow([]);
    $writeRow(['=== RINGKASAN EKSEKUTIF & METRIK KINERJA (KPI) ===']);
    $writeRow(['Indikator Kinerja', 'Kuantitas', 'Total Nominal (Rp)', 'Keterangan / Status']);
    
    $totalBills = (int)($aggregates['total_bills'] ?? count($bills));
    $totalBillAmount = (float)($aggregates['total_bill_amount'] ?? $grandTotal);
    $countPaid = (int)($aggregates['count_paid'] ?? 0);
    $totalIncome = (float)($aggregates['total_income'] ?? 0);
    $countUnpaid = (int)($aggregates['count_unpaid'] ?? 0);
    $totalUnpaid = (float)($aggregates['total_unpaid'] ?? 0);
    $collectionRate = isset($aggregates['collection_rate']) 
        ? $aggregates['collection_rate'] 
        : ($totalBills > 0 ? round(($countPaid / $totalBills) * 100, 1) : 0);
    $totalCuti = (int)($meta['total_cuti'] ?? 0);

    $writeRow(['Total Tagihan Terdaftar', $totalBills . ' Invoice', $totalBillAmount, 'Total tagihan dalam filter']);
    $writeRow(['Total Pemasukan Diterima (Lunas)', $countPaid . ' Terbayar', $totalIncome, 'Dana riil kas masuk']);
    $writeRow(['Sisa Tertunggak (Piutang)', $countUnpaid . ' Tertunda', $totalUnpaid, 'Prioritas penagihan lapangan']);
    $writeRow(['Tingkat Kolektibilitas (Collection Rate)', $collectionRate . '%', '-', ($collectionRate >= 80 ? 'Efisiensi Sangat Baik' : 'Perlu Peningkatan Penagihan')]);
    $writeRow(['Pelanggan Berstatus Cuti (FR-03)', $totalCuti . ' User', '-', 'Otomatis bebas tagihan tanpa denda']);

    // 6. Payment Method Breakdown Block
    if (!empty($methodRows)) {
        $writeRow([]);
        $writeRow(['=== REKAPITULASI METODE PEMBAYARAN ===']);
        $writeRow(['Metode Pembayaran', 'Jumlah Transaksi', 'Total Nominal (Rp)', 'Kontribusi (%)']);
        foreach ($methodRows as $m) {
            $mTotal = (float)($m['total'] ?? 0);
            $pct = $totalIncome > 0 ? round(($mTotal / $totalIncome) * 100, 1) : 0;
            $writeRow([
                $m['method'] ?? 'Tunai',
                ($m['count'] ?? 0) . ' Transaksi',
                $mTotal,
                $pct . '%'
            ]);
        }
        $writeRow(['TOTAL KAS MASUK', $countPaid . ' Transaksi', $totalIncome, '100%']);
    }

    rewind($fp);
    $csv = stream_get_contents($fp);
    fclose($fp);
    return $csv;
}

/**
 * Generate formatted Microsoft Excel HTML spreadsheet for financial reports (F6 / T-021).
 * Includes XML namespace, styled tables, executive KPI metrics, payment methods breakdown,
 * detailed transaction rows with grand total, and formal authorization block.
 *
 * @param array $bills Array of bill rows
 * @param array $aggregates Aggregated KPI stats
 * @param array $methodRows Payment method breakdown rows
 * @param array $meta Report metadata
 * @return string Full HTML Excel spreadsheet
 */
function buildFinancialReportExcelHtml(array $bills, array $aggregates = [], array $methodRows = [], array $meta = []): string {
    $companyName = $meta['company_name'] ?? 'Rengganis Net';
    $companyTagline = $meta['company_tagline'] ?? 'Solusi Internet Cepat & Hemat';
    $companyPhone = $meta['company_phone'] ?? '0812-3456-7890';
    $companyAddress = $meta['company_address'] ?? 'Jl. Raya Rengganis No. 45, Sumber Malang';
    $periodLabel = $meta['period_label'] ?? '-';
    $userName = $meta['user_name'] ?? 'Admin';
    $exportDate = date('d/m/Y H:i') . ' WIB';
    $totalCuti = (int)($meta['total_cuti'] ?? 0);

    $totalBills = (int)($aggregates['total_bills'] ?? count($bills));
    $totalBillAmount = (float)($aggregates['total_bill_amount'] ?? 0);
    $countPaid = (int)($aggregates['count_paid'] ?? 0);
    $totalIncome = (float)($aggregates['total_income'] ?? 0);
    $countUnpaid = (int)($aggregates['count_unpaid'] ?? 0);
    $totalUnpaid = (float)($aggregates['total_unpaid'] ?? 0);
    $collectionRate = isset($aggregates['collection_rate']) 
        ? $aggregates['collection_rate'] 
        : ($totalBills > 0 ? round(($countPaid / $totalBills) * 100, 1) : 0);

    ob_start();
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="utf-8">';
    echo '<style>
        body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; color: #0f172a; }
        .title { font-size: 16pt; font-weight: bold; color: #1e3a8a; }
        .sub-title { font-size: 11pt; color: #475569; }
        .section-header { font-size: 12pt; font-weight: bold; background-color: #1e3a8a; color: #ffffff; padding: 6px; }
        .table-header { font-weight: bold; background-color: #e2e8f0; color: #0f172a; text-align: center; border: 1px solid #94a3b8; }
        .cell { border: 1px solid #cbd5e1; padding: 5px; }
        .cell-center { border: 1px solid #cbd5e1; padding: 5px; text-align: center; }
        .cell-right { border: 1px solid #cbd5e1; padding: 5px; text-align: right; }
        .kpi-label { font-weight: bold; background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px; }
        .kpi-val { font-weight: bold; border: 1px solid #cbd5e1; padding: 6px; text-align: right; }
        .kpi-sub { border: 1px solid #cbd5e1; padding: 6px; color: #64748b; font-size: 10pt; }
        .status-lunas { background-color: #dcfce7; color: #166534; font-weight: bold; text-align: center; border: 1px solid #cbd5e1; }
        .status-unpaid { background-color: #ffe4e6; color: #9f1239; font-weight: bold; text-align: center; border: 1px solid #cbd5e1; }
        .total-row { font-weight: bold; background-color: #f8fafc; border-top: 2px solid #0f172a; border-bottom: 2px solid #0f172a; padding: 6px; }
    </style></head><body>';

    // Header Perusahaan
    echo '<table>';
    echo '<tr><td colspan="10" class="title">' . htmlspecialchars(strtoupper($companyName)) . '</td></tr>';
    echo '<tr><td colspan="10" class="sub-title">' . htmlspecialchars($companyTagline) . ' | Telp/WA: ' . htmlspecialchars($companyPhone) . '</td></tr>';
    echo '<tr><td colspan="10" class="sub-title">Alamat: ' . htmlspecialchars($companyAddress) . '</td></tr>';
    echo '<tr><td colspan="10" style="font-weight: bold; padding-top: 10px; font-size: 13pt;">LAPORAN REKAPITULASI PENAGIHAN & PENDAPATAN</td></tr>';
    echo '<tr><td colspan="10" style="color: #475569;">Periode: ' . htmlspecialchars($periodLabel) . ' | Tanggal Unduh: ' . $exportDate . ' | Petugas: ' . htmlspecialchars($userName) . '</td></tr>';
    echo '<tr><td colspan="10"></td></tr>';
    echo '</table>';

    // TABEL 1: RINGKASAN EKSEKUTIF / KPI
    echo '<table border="1">';
    echo '<tr><td colspan="4" class="section-header">1. RINGKASAN EKSEKUTIF & METRIK KINERJA PENAGIHAN (KPI)</td></tr>';
    echo '<tr class="table-header"><td>INDIKATOR KINERJA</td><td>KUANTITAS</td><td>TOTAL NOMINAL (RP)</td><td>KETERANGAN / STATUS</td></tr>';
    echo '<tr><td class="kpi-label">Total Tagihan Terdaftar</td><td class="kpi-val">' . $totalBills . ' Invoice</td><td class="kpi-val">' . formatRupiah($totalBillAmount) . '</td><td class="kpi-sub">Total tagihan dalam filter</td></tr>';
    echo '<tr><td class="kpi-label">Total Pemasukan Diterima (Lunas)</td><td class="kpi-val" style="color: #166534;">' . $countPaid . ' Terbayar</td><td class="kpi-val" style="color: #166534;">' . formatRupiah($totalIncome) . '</td><td class="kpi-sub" style="background-color: #f0fdf4;">Dana riil kas masuk</td></tr>';
    echo '<tr><td class="kpi-label">Sisa Tertunggak (Piutang)</td><td class="kpi-val" style="color: #9f1239;">' . $countUnpaid . ' Tertunda</td><td class="kpi-val" style="color: #9f1239;">' . formatRupiah($totalUnpaid) . '</td><td class="kpi-sub" style="background-color: #fff1f2;">Prioritas penagihan lapangan</td></tr>';
    echo '<tr><td class="kpi-label">Tingkat Kolektibilitas (Collection Rate)</td><td class="kpi-val" style="color: #1d4ed8;">' . $collectionRate . '%</td><td class="kpi-val">-</td><td class="kpi-sub">' . ($collectionRate >= 80 ? 'Efisiensi Sangat Baik' : 'Perlu Peningkatan Penagihan') . '</td></tr>';
    echo '<tr><td class="kpi-label">Pelanggan Berstatus Cuti (FR-03)</td><td class="kpi-val">' . $totalCuti . ' User</td><td class="kpi-val">-</td><td class="kpi-sub">Otomatis bebas tagihan tanpa akumulasi denda</td></tr>';
    echo '</table>';

    echo '<br>';

    // TABEL 2: REKAP METODE PEMBAYARAN
    echo '<table border="1">';
    echo '<tr><td colspan="4" class="section-header">2. REKAPITULASI BERDASARKAN METODE PEMBAYARAN</td></tr>';
    echo '<tr class="table-header"><td>METODE PEMBAYARAN</td><td>JUMLAH TRANSAKSI</td><td>TOTAL NOMINAL MASUK (RP)</td><td>KONTRIBUSI (%)</td></tr>';
    foreach ($methodRows as $m) {
        $mTotal = (float)($m['total'] ?? 0);
        $pct = $totalIncome > 0 ? round(($mTotal / $totalIncome) * 100, 1) : 0;
        echo '<tr>';
        echo '<td class="cell" style="font-weight: bold;">' . htmlspecialchars($m['method'] ?? 'Tunai') . '</td>';
        echo '<td class="cell-center">' . ($m['count'] ?? 0) . ' Transaksi</td>';
        echo '<td class="cell-right" style="font-weight: bold;">' . formatRupiah($mTotal) . '</td>';
        echo '<td class="cell-center">' . $pct . '%</td>';
        echo '</tr>';
    }
    echo '<tr class="total-row"><td>TOTAL KAS MASUK</td><td style="text-align: center;">' . $countPaid . ' Transaksi</td><td style="text-align: right;">' . formatRupiah($totalIncome) . '</td><td style="text-align: center;">100%</td></tr>';
    echo '</table>';

    echo '<br>';

    // TABEL 3: RINCIAN LENGKAP INVOICE & PELANGGAN
    echo '<table border="1">';
    echo '<tr><td colspan="12" class="section-header">3. RINCIAN LENGKAP TAGIHAN & TRANSAKSI PELANGGAN (' . count($bills) . ' DATA)</td></tr>';
    echo '<tr class="table-header">';
    echo '<td>NO</td><td>NO INVOICE</td><td>ID PELANGGAN</td><td>NAMA PELANGGAN</td><td>NO TELEPON / WA</td><td>ALAMAT LENGKAP</td><td>PAKET WIFI</td><td>TARIF (RP)</td><td>STATUS</td><td>TANGGAL BAYAR</td><td>METODE BAYAR</td><td>PETUGAS / KASIR</td>';
    echo '</tr>';

    $no = 1;
    $grandTotal = 0.0;
    foreach ($bills as $b) {
        $amount = (float)($b['amount'] ?? 0);
        $grandTotal += $amount;
        $isLunas = ($b['status'] ?? '') === 'lunas';
        echo '<tr>';
        echo '<td class="cell-center">' . $no++ . '</td>';
        echo '<td class="cell" style="font-family: Consolas, monospace; font-weight: bold;">' . htmlspecialchars($b['bill_number'] ?? '') . '</td>';
        echo '<td class="cell" style="font-family: Consolas, monospace;">' . htmlspecialchars($b['customer_code'] ?? '') . '</td>';
        echo '<td class="cell" style="font-weight: bold;">' . htmlspecialchars($b['customer_name'] ?? '') . '</td>';
        echo '<td class="cell" style="mso-number-format:\'@\';">' . htmlspecialchars($b['customer_phone'] ?? '') . '</td>';
        echo '<td class="cell">' . htmlspecialchars($b['customer_address'] ?? '') . '</td>';
        echo '<td class="cell">' . htmlspecialchars($b['package_name'] ?? '') . '</td>';
        echo '<td class="cell-right" style="font-weight: bold;">' . formatRupiah($amount) . '</td>';
        echo '<td class="' . ($isLunas ? 'status-lunas' : 'status-unpaid') . '">' . ($isLunas ? 'LUNAS' : 'BELUM LUNAS') . '</td>';
        echo '<td class="cell-center">' . (!empty($b['payment_date']) ? date('d/m/Y H:i', strtotime($b['payment_date'])) : '-') . '</td>';
        echo '<td class="cell">' . htmlspecialchars($b['payment_method'] ?: '-') . '</td>';
        echo '<td class="cell">' . htmlspecialchars($b['collector_name'] ?: '-') . '</td>';
        echo '</tr>';
    }

    echo '<tr class="total-row">';
    echo '<td colspan="7" style="text-align: right; padding: 8px;">TOTAL KESELURUHAN (' . count($bills) . ' TRANSAKSI):</td>';
    echo '<td style="text-align: right; padding: 8px; font-size: 12pt; color: #1e3a8a;">' . formatRupiah($grandTotal) . '</td>';
    echo '<td colspan="4"></td>';
    echo '</tr>';
    echo '</table>';

    // Lembar Pengesahan
    echo '<br><br>';
    echo '<table>';
    echo '<tr>';
    echo '<td colspan="4" style="text-align: center;">Dibuat Oleh,<br><strong>Admin Operasional</strong><br><br><br><br><u>( ' . htmlspecialchars($userName) . ' )</u></td>';
    echo '<td colspan="4"></td>';
    echo '<td colspan="4" style="text-align: center;">Sumber Malang, ' . formatDate(date('Y-m-d')) . '<br>Mengetahui & Menyetujui,<br><strong>Pemilik / Owner Rengganis Net</strong><br><br><br><br><u>( Pemilik Usaha )</u></td>';
    echo '</tr>';
    echo '</table>';

    echo '</body></html>';
    return ob_get_clean();
}
