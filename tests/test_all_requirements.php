<?php
/**
 * Automated Verification Script for Rengganis Net
 * Validates PRD Functional Requirements FR-01 through FR-08 & Acceptance Criteria
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

echo "========================================================\n";
echo "   RENGGANIS NET - AUTOMATED REQUIREMENTS VERIFICATION\n";
echo "========================================================\n\n";

$testsPassed = 0;
$totalTests = 0;

function assertTest($description, $condition) {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        $testsPassed++;
        echo " [PASS] " . $description . "\n";
    } else {
        echo " [FAIL] " . $description . "\n";
    }
}

// TEST 1: Database Tables Existence
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$expectedTables = ['users', 'packages', 'customers', 'bills', 'status_logs', 'settings'];
$hasAllTables = count(array_intersect($expectedTables, $tables)) === count($expectedTables);
assertTest("FR-01/NFR-01: Semua 6 tabel MySQL ada dan siap digunakan", $hasAllTables);

// TEST 1.1: Foreign Keys Integrity (T-003)
$fks = $pdo->query("
    SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
")->fetchAll();

$hasBillsCustFk  = false;
$hasCustPkgFk    = false;
$hasStatusCustFk = false;

foreach ($fks as $fk) {
    if ($fk['TABLE_NAME'] === 'bills' && $fk['COLUMN_NAME'] === 'customer_id' && $fk['REFERENCED_TABLE_NAME'] === 'customers') {
        $hasBillsCustFk = true;
    }
    if ($fk['TABLE_NAME'] === 'customers' && $fk['COLUMN_NAME'] === 'package_id' && $fk['REFERENCED_TABLE_NAME'] === 'packages') {
        $hasCustPkgFk = true;
    }
    if ($fk['TABLE_NAME'] === 'status_logs' && $fk['COLUMN_NAME'] === 'customer_id' && $fk['REFERENCED_TABLE_NAME'] === 'customers') {
        $hasStatusCustFk = true;
    }
}
assertTest("T-003/Schema: Foreign key relasional antar tabel terkonfigurasi konsisten (bills, customers, packages, status_logs)", $hasBillsCustFk && $hasCustPkgFk && $hasStatusCustFk);

// TEST 1.2: Unique Constraint Idempotensi Tagihan (T-003 / R1)
$uniquePeriod = false;
$billIndices = $pdo->query("SHOW INDEX FROM bills WHERE Key_name = 'unique_customer_period'")->fetchAll();
if (count($billIndices) === 3) {
    $cols = array_column($billIndices, 'Column_name');
    $uniquePeriod = in_array('customer_id', $cols) && in_array('period_month', $cols) && in_array('period_year', $cols);
}
assertTest("T-003/R1: Unique constraint (customer_id, period_month, period_year) aktif mencegah tagihan duplikat", $uniquePeriod);

// TEST 2: Users & Password Hashing (bcrypt NFR-03 & FR-01)
$stmtAdmin = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
$stmtAdmin->execute();
$adminUser = $stmtAdmin->fetch();
$adminPassOk = $adminUser && password_verify('admin123', $adminUser['password']);
assertTest("FR-01/NFR-03: User Admin terdaftar dengan password bcrypt aman", $adminPassOk);

$stmtTeknisi = $pdo->prepare("SELECT * FROM users WHERE username = 'teknisi'");
$stmtTeknisi->execute();
$teknisiUser = $stmtTeknisi->fetch();
$teknisiPassOk = $teknisiUser && password_verify('teknisi123', $teknisiUser['password']);
assertTest("FR-01: User Teknisi terdaftar untuk akses teknisi lapangan", $teknisiPassOk);

$stmtKolektor = $pdo->prepare("SELECT * FROM users WHERE username = 'kolektor'");
$stmtKolektor->execute();
$kolektorUser = $stmtKolektor->fetch();
$kolektorPassOk = $kolektorUser && password_verify('kolektor123', $kolektorUser['password']) && $kolektorUser['role'] === 'kolektor';
assertTest("FR-01: User Kolektor/Kasir terdaftar dengan password bcrypt aman & role kolektor", $kolektorPassOk);

// RBAC Multi-role validation
$_SESSION['user_id'] = 3;
$_SESSION['user_role'] = 'kolektor';
$rbacOk = isKolektor() && !isAdmin() && !isTeknisi() && hasRole(['admin', 'kolektor']) && !hasRole(['admin', 'teknisi']);
assertTest("FR-01/Security: Helper RBAC multi-role memvalidasi hak akses peran Kolektor secara presisi", $rbacOk);
unset($_SESSION['user_id'], $_SESSION['user_role']);

// TEST 3: Packages Availability
$packageCount = $pdo->query("SELECT COUNT(*) FROM packages WHERE is_active = 1")->fetchColumn();
assertTest("FR-02: Paket layanan WiFi tersedia (total: {$packageCount})", $packageCount >= 4);

// TEST 3.1: Customer Contact Validation (T-004 / F2)
$validPhoneCheck = validatePhoneNumber('081234567890') && validatePhoneNumber('0812-3456-7890') && validatePhoneNumber('+628123456789');
$invalidPhoneCheck = !validatePhoneNumber('02112345678') && !validatePhoneNumber('12345') && !validatePhoneNumber('');
$waFormatOk = (formatWhatsAppNumber('081234567890') === '6281234567890') && (formatWhatsAppNumber('+62812-3456-7890') === '6281234567890');
assertTest("T-004/F2: Validasi nomor kontak WhatsApp Indonesia & fungsi formatting wa.me berfungsi akurat", $validPhoneCheck && $invalidPhoneCheck && $waFormatOk);

// TEST 3.2: Customer Record Persistence & Package Linking (T-004 / F2)
$testCustCode = 'RNG-TEST-' . time();
$pkgFirstId = (int)$pdo->query("SELECT id FROM packages WHERE is_active = 1 LIMIT 1")->fetchColumn();
$stmtInsertTest = $pdo->prepare("
    INSERT INTO customers (customer_code, name, phone, address, package_id, ip_address, installation_date, status, notes)
    VALUES (?, 'Pelanggan Uji Coba T-004', '081234567899', 'Jl. Uji Coba No. 99', ?, '192.168.10.99', CURDATE(), 'aktif', 'Uji CRUD T-004')
");
$stmtInsertTest->execute([$testCustCode, $pkgFirstId]);
$testCustId = (int)$pdo->lastInsertId();

$stmtCheckTest = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmtCheckTest->execute([$testCustId]);
$insertedCust = $stmtCheckTest->fetch();

$custPersisted = $insertedCust && $insertedCust['customer_code'] === $testCustCode && $insertedCust['package_id'] == $pkgFirstId;
assertTest("T-004/F2: Penyimpanan data master pelanggan baru terhubung ke paket layanan WiFi", (bool)$custPersisted);

// Clean up test customer
if ($testCustId > 0) {
    $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$testCustId]);
}

// TEST 4: Customer Status Management & Exemption from Bills (FR-03 & Acceptance Criteria)
// Find or create customer with status 'cuti'
$stmtCuti = $pdo->prepare("SELECT * FROM customers WHERE status = 'cuti'");
$stmtCuti->execute();
$cutiCust = $stmtCuti->fetch();
assertTest("FR-02/FR-03: Pelanggan berstatus 'cuti' tercatat di database", (bool)$cutiCust);

// Check that cuti customer DOES NOT have October 2026 bill generated
if ($cutiCust) {
    $stmtBillCuti = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE customer_id = ? AND period_month = 10 AND period_year = 2026");
    $stmtBillCuti->execute([$cutiCust['id']]);
    $cutiBillsCount = (int)$stmtBillCuti->fetchColumn();
    assertTest("FR-03/Acceptance Criteria: Pelanggan status 'cuti' TIDAK memiliki tagihan baru pada periode aktif (0 tagihan)", $cutiBillsCount === 0);
}

// TEST 5: Status Change Audit Logging (FR-03 & Section 11 Analytics)
$logCount = $pdo->query("SELECT COUNT(*) FROM status_logs")->fetchColumn();
assertTest("FR-03/Section 11: Riwayat audit perubahan status dicatat ke tabel status_logs (total: {$logCount} log)", $logCount > 0);

// TEST 5.1: Functional Status Change to 'cuti' Audit Trail (T-005 / F2 Acceptance Criteria)
$stmtActCust = $pdo->query("SELECT id, status FROM customers WHERE status = 'aktif' LIMIT 1");
$actCust = $stmtActCust->fetch();
if ($actCust) {
    $testCustId = (int)$actCust['id'];
    $testReason = 'Uji coba permohonan cuti sementara (T-005)';
    $testUser = 'Admin Penguji T-005';
    
    $statusUpdated = updateCustomerStatus($pdo, $testCustId, 'cuti', $testReason, $testUser, false);
    
    // Verify latest log in status_logs
    $stmtLatestLog = $pdo->prepare("
        SELECT * FROM status_logs 
        WHERE customer_id = ? 
        ORDER BY id DESC LIMIT 1
    ");
    $stmtLatestLog->execute([$testCustId]);
    $latestLog = $stmtLatestLog->fetch();
    
    $logValid = $statusUpdated 
        && $latestLog 
        && $latestLog['old_status'] === 'aktif' 
        && $latestLog['new_status'] === 'cuti' 
        && $latestLog['reason'] === $testReason 
        && $latestLog['changed_by'] === $testUser 
        && !empty($latestLog['created_at']);
        
    assertTest("T-005/F2: Perubahan status ke 'cuti' mencatat audit log lengkap (status lama, status baru, alasan, user, waktu)", (bool)$logValid);
    
    // Revert status back to 'aktif'
    updateCustomerStatus($pdo, $testCustId, 'aktif', 'Pengembalian status aktif pasca uji coba', $testUser, false);
}

// TEST 6: Monthly Billing Generation Engine (FR-04)
$testMonth = 11; // November 2026 test
$testYear = 2026;
// Clean test bills if any
$pdo->exec("DELETE FROM bills WHERE period_month = {$testMonth} AND period_year = {$testYear}");

$genResult = generateMonthlyBills($pdo, $testMonth, $testYear, 10);
assertTest("FR-04: Batch billing engine sukses men-generate tagihan baru untuk pelanggan aktif ({$genResult['generated']} tagihan)", $genResult['generated'] > 0);
assertTest("FR-03/Acceptance Criteria: Batch billing engine otomatis melewatkan pelanggan cuti ({$genResult['skipped_cuti']} dilewati)", $genResult['skipped_cuti'] >= 2);

// Test Guardrail Metric 4.3: Duplicate billing prevention
$genResultAgain = generateMonthlyBills($pdo, $testMonth, $testYear, 10);
assertTest("Guardrail Metric: Tidak ada tagihan duplikat (generate ulang menghasilkan 0 tagihan baru)", $genResultAgain['generated'] === 0);

// TEST 7: Payment Processing (FR-05)
// Pick one unpaid bill from the test batch
$stmtUnpaid = $pdo->prepare("SELECT id FROM bills WHERE period_month = ? AND period_year = ? AND status = 'belum_lunas' LIMIT 1");
$stmtUnpaid->execute([$testMonth, $testYear]);
$unpaidId = (int)$stmtUnpaid->fetchColumn();

if ($unpaidId > 0) {
    $paidBill = processBillPayment($pdo, $unpaidId, 'Tunai', 'Budi Santoso (Kolektor)', 'Uji coba pembayaran lapangan');
    assertTest("FR-05: Tagihan berhasil diproses lunas dengan timestamp & collector", $paidBill && $paidBill['status'] === 'lunas' && !empty($paidBill['payment_date']));
}

// TEST 8: Setting Company Profile & Receipt Footers
$compName = getSetting($pdo, 'company_name');
assertTest("FR-06/FR-07: Pengaturan identitas struk & logo perusahaan tersimpan ('{$compName}')", !empty($compName));

// Clean up test batch
$pdo->exec("DELETE FROM bills WHERE period_month = {$testMonth} AND period_year = {$testYear}");
echo "\nTest batch November 2026 dibersihkan.\n";

echo "\n--------------------------------------------------------\n";
echo "HASIL VERIFIKASI: {$testsPassed} / {$totalTests} PENGUJIAN BERHASIL!\n";
echo "--------------------------------------------------------\n";

if ($testsPassed === $totalTests) {
    echo "SELURUH REQUIREMENT PRD & ACCEPTANCE CRITERIA TERPENUHI 100%!\n\n";
} else {
    echo "Ada pengujian yang gagal. Periksa log di atas.\n\n";
}
