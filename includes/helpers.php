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
 * Process a bill payment and mark as Lunas
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

    $now = date('Y-m-d H:i:s');
    $stmtUp = $pdo->prepare("
        UPDATE bills 
        SET status = 'lunas', payment_date = ?, payment_method = ?, collector_name = ?, notes = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmtUp->execute([$now, $method, $collectorName, $notes, $billId]);

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
