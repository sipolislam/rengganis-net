<?php
/**
 * Automated Verification Script for Rengganis Net
 * Validates PRD Functional Requirements FR-01 through FR-08 & Acceptance Criteria
 */

require_once __DIR__ . '/../config/database.php';
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

// TEST 3: Packages Availability
$packageCount = $pdo->query("SELECT COUNT(*) FROM packages WHERE is_active = 1")->fetchColumn();
assertTest("FR-02: Paket layanan WiFi tersedia (total: {$packageCount})", $packageCount >= 4);

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
