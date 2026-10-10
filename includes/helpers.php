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

// Status Badges
function getStatusBadge(string $status): string {
    $status = strtolower($status);
    switch ($status) {
        case 'aktif':
            return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Aktif</span>';
        case 'cuti':
            return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200/80"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Cuti/Libur</span>';
        case 'isolir':
            return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200/80"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Isolir</span>';
        case 'lunas':
            return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80"><svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Lunas</span>';
        case 'belum_lunas':
            return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200/80"><span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>Belum Lunas</span>';
        default:
            return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">' . e($status) . '</span>';
    }
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
    $dueDayPadded = str_pad((string)$dueDay, 2, '0', STR_PAD_LEFT);
    $monthPadded = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
    $dueDate = "{$year}-{$monthPadded}-{$dueDayPadded}";

    // Count customers in 'cuti'
    $stmtCutiCount = $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'cuti'");
    $skippedCuti = (int)$stmtCutiCount->fetchColumn();

    // Select active customers who don't have a bill for this month & year
    $stmt = $pdo->prepare("
        SELECT c.id, c.customer_code, c.name, p.price
        FROM customers c
        JOIN packages p ON c.package_id = p.id
        WHERE c.status = 'aktif'
          AND c.installation_date <= :period_end
          AND c.id NOT IN (
              SELECT customer_id FROM bills 
              WHERE period_month = :m AND period_year = :y
          )
    ");
    $stmt->execute([
        ':period_end' => "{$year}-{$monthPadded}-28",
        ':m'          => $month,
        ':y'          => $year
    ]);
    $eligibleCustomers = $stmt->fetchAll();

    if (empty($eligibleCustomers)) {
        return [
            'generated'    => 0,
            'skipped'      => 0,
            'skipped_cuti' => $skippedCuti
        ];
    }

    // Get current max bill sequence for this period
    $prefix = "INV/{$year}{$monthPadded}/";
    $stmtSeq = $pdo->prepare("SELECT MAX(bill_number) FROM bills WHERE bill_number LIKE :prefix");
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

    $generatedCount = 0;
    $pdo->beginTransaction();
    try {
        foreach ($eligibleCustomers as $cust) {
            $seq++;
            $billNum = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
            $insertStmt->execute([
                $billNum,
                $cust['id'],
                $month,
                $year,
                $cust['price'],
                $dueDate
            ]);
            $generatedCount++;
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    return [
        'generated'    => $generatedCount,
        'skipped'      => 0,
        'skipped_cuti' => $skippedCuti
    ];
}

/**
 * Generate a single bill for a customer (e.g. for mid-month reactivation or new installation)
 */
function generateCustomerBill(PDO $pdo, int $customerId, int $month, int $year, int $dueDay = 10): ?array {
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
    $newId = $pdo->lastInsertId();

    return [
        'id'          => $newId,
        'bill_number' => $billNum,
        'amount'      => $cust['price']
    ];
}

/**
 * Change customer status and log it (FR-03 & Section 11 Analytics)
 */
function updateCustomerStatus(PDO $pdo, int $customerId, string $newStatus, string $reason, string $changedBy, bool $autoBillOnReactivation = true): bool {
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

        // Trade-off 13.2: If reactivated to 'aktif' in mid-month from 'cuti', optionally generate current month's bill
        if ($oldStatus === 'cuti' && $newStatus === 'aktif' && $autoBillOnReactivation) {
            $curMonth = (int)date('n');
            $curYear = (int)date('Y');
            generateCustomerBill($pdo, $customerId, $curMonth, $curYear);
        }

        $pdo->commit();
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
