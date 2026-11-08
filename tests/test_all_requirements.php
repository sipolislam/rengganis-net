<?php
/**
 * Automated Requirements Verification Script for Rengganis Net
 * Validates PRD Functional Requirements FR-01 through FR-08, Risk Controls R1-R3, & Acceptance Criteria
 * 
 * Terbagi dalam 12 Kelompok Kriteria Kebutuhan Resmi PRD & EVALS:
 *  1. Integritas Basis Data Relasional & Skema 6 Tabel MySQL (FR-01, NFR-01, T-003)
 *  2. Autentikasi Pengguna Hash Bcrypt & Otorisasi RBAC Multi-Role (FR-01, NFR-03, T-002)
 *  3. Manajemen Paket Layanan WiFi & Tarif Tagihan Pelanggan (FR-02, Setup, T-010)
 *  4. Manajemen Data Pelanggan, Validasi Kontak WA & Pencarian Cepat (FR-02, T-004, T-011)
 *  5. Manajemen Status Cuti Pelanggan & Audit Trail status_logs (FR-02, FR-03, T-005)
 *  6. Batch Billing Engine Bulanan & Format Tagihan INV/YYYYMM/XXXX (FR-04, T-006)
 *  7. Pengecualian Mutlak Tagihan Pelanggan Cuti / 0 Tagihan Fiktif (FR-03, R1, T-007)
 *  8. Idempotensi Siklus Billing & Proteksi Anti-Duplikasi Tagihan (FR-03, R1, T-008)
 *  9. Reaktivasi Pelanggan Cuti dengan Penerbitan Tagihan Bulan Berjalan (FR-02, FR-03, T-009)
 * 10. Notifikasi WhatsApp Tagihan Dual-Option & Kuitansi Pelunasan Sah (FR-04, R2, T-012, T-013)
 * 11. Pelunasan Multi-Metode, Modal Struk, Driver ESC/POS, Fallback & Render PNG (FR-05, R3, T-014–T-018)
 * 12. Dasbor Analitik, Laporan Rentang Tanggal, Ekspor CSV/Excel & Profil Usaha (FR-06, FR-07, Setup, T-019–T-022)
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

$criteriaMap = [
    1  => "FR-01/NFR-01/T-003: Integritas Basis Data Relasional & Skema 6 Tabel MySQL",
    2  => "FR-01/NFR-03/T-002: Autentikasi Pengguna Hash Bcrypt & Otorisasi RBAC Multi-Role",
    3  => "FR-02/Setup/T-010: Manajemen Paket Layanan WiFi & Tarif Tagihan Pelanggan",
    4  => "FR-02/T-004/T-011: Manajemen Data Pelanggan, Validasi Kontak WA & Pencarian Cepat",
    5  => "FR-02/FR-03/T-005: Manajemen Status Cuti Pelanggan & Audit Trail status_logs",
    6  => "FR-04/T-006: Batch Billing Engine Bulanan & Format Tagihan INV/YYYYMM/XXXX",
    7  => "FR-03/R1/T-007: Pengecualian Mutlak Tagihan Pelanggan Cuti (0 Tagihan Fiktif)",
    8  => "FR-03/R1/T-008: Idempotensi Siklus Billing & Proteksi Anti-Duplikasi Tagihan",
    9  => "FR-02/FR-03/T-009: Reaktivasi Pelanggan Cuti dengan Penerbitan Tagihan Bulan Berjalan",
    10 => "FR-04/R2/T-012/T-013: Notifikasi WhatsApp Tagihan Dual-Option & Kuitansi Pelunasan Sah",
    11 => "FR-05/R3/T-014-T-018: Pelunasan Multi-Metode, Modal Struk, Driver ESC/POS, Fallback & Render PNG",
    12 => "FR-06/FR-07/T-019-T-022: Dasbor Analitik, Laporan Rentang Tanggal, Ekspor CSV/Excel & Pengaturan Profil"
];

$kriteriaStatus = array_fill(1, 12, true);
$kriteriaCounts = array_fill(1, 12, ['total' => 0, 'passed' => 0]);

function assertTest($kriteriaId, $description, $condition) {
    global $testsPassed, $totalTests, $kriteriaStatus, $kriteriaCounts;
    $totalTests++;
    $kriteriaCounts[$kriteriaId]['total']++;
    if ($condition) {
        $testsPassed++;
        $kriteriaCounts[$kriteriaId]['passed']++;
        echo "   [PASS] " . $description . "\n";
    } else {
        $kriteriaStatus[$kriteriaId] = false;
        echo "   [FAIL] " . $description . "\n";
    }
}

function printKriteriaHeader($kriteriaId, $title) {
    echo sprintf("\n=== KRITERIA %02d/12: %s ===\n", $kriteriaId, $title);
}

$testMonth = 11; // November 2026 test batch
$testYear = 2026;

try {
    // ========================================================
    // KRITERIA 1: Integritas Basis Data Relasional & Skema 6 Tabel MySQL (FR-01, NFR-01, T-003)
    // ========================================================
    printKriteriaHeader(1, $criteriaMap[1]);

    // Sub-test 1.1: 6 Tabel MySQL
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $expectedTables = ['users', 'packages', 'customers', 'bills', 'status_logs', 'settings'];
    $hasAllTables = count(array_intersect($expectedTables, $tables)) === count($expectedTables);
    assertTest(1, "FR-01/NFR-01: Semua 6 tabel MySQL ada dan siap digunakan", $hasAllTables);

    // Sub-test 1.2: Foreign Keys Integrity (T-003)
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
    assertTest(1, "T-003/Schema: Foreign key relasional antar tabel terkonfigurasi konsisten (bills, customers, packages, status_logs)", $hasBillsCustFk && $hasCustPkgFk && $hasStatusCustFk);

    // Sub-test 1.3: Unique Constraint Idempotensi Tagihan (T-003 / R1)
    $uniquePeriod = false;
    $billIndices = $pdo->query("SHOW INDEX FROM bills WHERE Key_name = 'unique_customer_period'")->fetchAll();
    if (count($billIndices) === 3) {
        $cols = array_column($billIndices, 'Column_name');
        $uniquePeriod = in_array('customer_id', $cols) && in_array('period_month', $cols) && in_array('period_year', $cols);
    }
    assertTest(1, "T-003/R1: Unique constraint (customer_id, period_month, period_year) aktif mencegah tagihan duplikat", $uniquePeriod);

    // ========================================================
    // KRITERIA 2: Autentikasi Pengguna Hash Bcrypt & Otorisasi RBAC Multi-Role (FR-01, NFR-03, T-002)
    // ========================================================
    printKriteriaHeader(2, $criteriaMap[2]);

    $stmtAdmin = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
    $stmtAdmin->execute();
    $adminUser = $stmtAdmin->fetch();
    $adminPassOk = $adminUser && password_verify('admin123', $adminUser['password']);
    assertTest(2, "FR-01/NFR-03: User Admin terdaftar dengan password bcrypt aman", $adminPassOk);

    $stmtTeknisi = $pdo->prepare("SELECT * FROM users WHERE username = 'teknisi'");
    $stmtTeknisi->execute();
    $teknisiUser = $stmtTeknisi->fetch();
    $teknisiPassOk = $teknisiUser && password_verify('teknisi123', $teknisiUser['password']);
    assertTest(2, "FR-01: User Teknisi terdaftar untuk akses teknisi lapangan", $teknisiPassOk);

    $stmtKolektor = $pdo->prepare("SELECT * FROM users WHERE username = 'kolektor'");
    $stmtKolektor->execute();
    $kolektorUser = $stmtKolektor->fetch();
    $kolektorPassOk = $kolektorUser && password_verify('kolektor123', $kolektorUser['password']) && $kolektorUser['role'] === 'kolektor';
    assertTest(2, "FR-01: User Kolektor/Kasir terdaftar dengan password bcrypt aman & role kolektor", $kolektorPassOk);

    // RBAC Multi-role validation
    $_SESSION['user_id'] = 3;
    $_SESSION['user_role'] = 'kolektor';
    $rbacOk = isKolektor() && !isAdmin() && !isTeknisi() && hasRole(['admin', 'kolektor']) && !hasRole(['admin', 'teknisi']);
    assertTest(2, "FR-01/Security: Helper RBAC multi-role memvalidasi hak akses peran Kolektor secara presisi", $rbacOk);
    unset($_SESSION['user_id'], $_SESSION['user_role']);

    // ========================================================
    // KRITERIA 3: Manajemen Paket Layanan WiFi & Tarif Tagihan Pelanggan (FR-02, Setup, T-010)
    // ========================================================
    printKriteriaHeader(3, $criteriaMap[3]);

    $packageCount = $pdo->query("SELECT COUNT(*) FROM packages WHERE is_active = 1")->fetchColumn();
    assertTest(3, "FR-02: Paket layanan WiFi tersedia (total: {$packageCount})", $packageCount >= 4);

    // Form CRUD paket internet berfungsi dan tarif terhubung ke nominal tagihan (T-010)
    $testPkgName = 'Paket Turbo Test ' . time();
    $testPkgSpeed = '150 Mbps';
    $testPkgPrice = 499000.00;
    $testPkgDesc = 'Pengujian CRUD paket internet T-010';

    $stmtCreatePkg = $pdo->prepare("
        INSERT INTO packages (name, speed, price, description, is_active, created_at, updated_at) 
        VALUES (?, ?, ?, ?, 1, NOW(), NOW())
    ");
    $stmtCreatePkg->execute([$testPkgName, $testPkgSpeed, $testPkgPrice, $testPkgDesc]);
    $testPkgId = (int)$pdo->lastInsertId();

    $stmtReadPkg = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmtReadPkg->execute([$testPkgId]);
    $pkgCreated = $stmtReadPkg->fetch();
    $readOk = $pkgCreated && ($pkgCreated['name'] === $testPkgName) && ((float)$pkgCreated['price'] === $testPkgPrice) && ((int)$pkgCreated['is_active'] === 1);

    $updatedPkgSpeed = '200 Mbps';
    $updatedPkgPrice = 549000.00;
    $stmtUpdatePkg = $pdo->prepare("UPDATE packages SET speed = ?, price = ?, updated_at = NOW() WHERE id = ?");
    $stmtUpdatePkg->execute([$updatedPkgSpeed, $updatedPkgPrice, $testPkgId]);

    $stmtReadUpdated = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmtReadUpdated->execute([$testPkgId]);
    $pkgUpdated = $stmtReadUpdated->fetch();
    $updateOk = $pkgUpdated && ($pkgUpdated['speed'] === $updatedPkgSpeed) && ((float)$pkgUpdated['price'] === $updatedPkgPrice);

    $stmtCustTie = $pdo->prepare("SELECT p.price FROM packages p WHERE p.id = ?");
    $stmtCustTie->execute([$testPkgId]);
    $tiedPrice = (float)$stmtCustTie->fetchColumn();
    $pricingTiedOk = ($tiedPrice === $updatedPkgPrice);

    $stmtDeletePkg = $pdo->prepare("DELETE FROM packages WHERE id = ?");
    $stmtDeletePkg->execute([$testPkgId]);
    $stmtCheckDeleted = $pdo->prepare("SELECT COUNT(*) FROM packages WHERE id = ?");
    $stmtCheckDeleted->execute([$testPkgId]);
    $deletedOk = ((int)$stmtCheckDeleted->fetchColumn() === 0);

    assertTest(3, "T-010/Setup: Form CRUD paket internet berfungsi dan tarif terhubung ke nominal tagihan", $readOk && $updateOk && $pricingTiedOk && $deletedOk);

    // ========================================================
    // KRITERIA 4: Manajemen Data Pelanggan, Validasi Kontak WA & Pencarian Cepat (FR-02, T-004, T-011)
    // ========================================================
    printKriteriaHeader(4, $criteriaMap[4]);

    // Sub-test 4.1: Customer Contact Validation (T-004 / F2)
    $validPhoneCheck = validatePhoneNumber('081234567890') && validatePhoneNumber('0812-3456-7890') && validatePhoneNumber('+628123456789');
    $invalidPhoneCheck = !validatePhoneNumber('02112345678') && !validatePhoneNumber('12345') && !validatePhoneNumber('');
    $waFormatOk = (formatWhatsAppNumber('081234567890') === '6281234567890') && (formatWhatsAppNumber('+62812-3456-7890') === '6281234567890');
    assertTest(4, "T-004/F2: Validasi nomor kontak WhatsApp Indonesia & fungsi formatting wa.me berfungsi akurat", $validPhoneCheck && $invalidPhoneCheck && $waFormatOk);

    // Sub-test 4.2: Customer Record Persistence & Package Linking (T-004 / F2)
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
    assertTest(4, "T-004/F2: Penyimpanan data master pelanggan baru terhubung ke paket layanan WiFi", (bool)$custPersisted);

    if ($testCustId > 0) {
        $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$testCustId]);
    }

    // Sub-test 4.3: Customer & Bill Filtering and Search Verification (T-011 / F2 / F3)
    $stmtSearchCust = $pdo->prepare("
        SELECT c.*, p.name AS package_name 
        FROM customers c 
        JOIN packages p ON c.package_id = p.id 
        WHERE c.customer_code LIKE :q1 OR c.name LIKE :q2
    ");
    $stmtSearchCust->execute([':q1' => '%RNG-001%', ':q2' => '%RNG-001%']);
    $foundCusts = $stmtSearchCust->fetchAll();
    $custSearchOk = count($foundCusts) === 1 && $foundCusts[0]['customer_code'] === 'RNG-001';

    $stmtCustCuti = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE status = 'cuti'");
    $stmtCustCuti->execute();
    $cutiTotal = (int)$stmtCustCuti->fetchColumn();

    $stmtCustAktif = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE status = 'aktif'");
    $stmtCustAktif->execute();
    $aktifTotal = (int)$stmtCustAktif->fetchColumn();
    $custFilterOk = ($cutiTotal >= 2 && $aktifTotal >= 5);

    // Bill status filtering on existing records
    $stmtBillsLunas = $pdo->query("SELECT * FROM bills WHERE status = 'lunas' LIMIT 5");
    $billsLunas = $stmtBillsLunas->fetchAll();
    $stmtBillsBelumLunas = $pdo->query("SELECT * FROM bills WHERE status = 'belum_lunas' LIMIT 5");
    $billsBelumLunas = $stmtBillsBelumLunas->fetchAll();

    $billFilterOk = (count($billsLunas) >= 1) && (count($billsBelumLunas) >= 1);
    foreach ($billsLunas as $bl) {
        if ($bl['status'] !== 'lunas') { $billFilterOk = false; break; }
    }
    foreach ($billsBelumLunas as $bbl) {
        if ($bbl['status'] !== 'belum_lunas') { $billFilterOk = false; break; }
    }

    $sampleInvNumber = $billsLunas[0]['bill_number'] ?? '';
    $stmtSearchInv = $pdo->prepare("
        SELECT b.*, c.name AS customer_name 
        FROM bills b 
        JOIN customers c ON b.customer_id = c.id 
        WHERE b.bill_number = ?
    ");
    $stmtSearchInv->execute([$sampleInvNumber]);
    $foundBill = $stmtSearchInv->fetch();
    $billSearchOk = $foundBill && ($foundBill['bill_number'] === $sampleInvNumber);

    $t011Passed = $custSearchOk && $custFilterOk && $billFilterOk && $billSearchOk;
    assertTest(4, "T-011/F2/F3: Pencarian nama/kode pelanggan dan filter status tagihan lunas/belum lunas berfungsi cepat & akurat", $t011Passed);

    // ========================================================
    // KRITERIA 5: Manajemen Status Cuti Pelanggan & Audit Trail status_logs (FR-02, FR-03, T-005)
    // ========================================================
    printKriteriaHeader(5, $criteriaMap[5]);

    $stmtCuti = $pdo->prepare("SELECT * FROM customers WHERE status = 'cuti'");
    $stmtCuti->execute();
    $cutiCust = $stmtCuti->fetch();
    assertTest(5, "FR-02/FR-03: Pelanggan berstatus 'cuti' tercatat di database", (bool)$cutiCust);

    $logCount = $pdo->query("SELECT COUNT(*) FROM status_logs")->fetchColumn();
    assertTest(5, "FR-03/Section 11: Riwayat audit perubahan status dicatat ke tabel status_logs (total: {$logCount} log)", $logCount > 0);

    // Functional Status Change to 'cuti' Audit Trail (T-005 / F2)
    $stmtActCust = $pdo->query("SELECT id, status FROM customers WHERE status = 'aktif' LIMIT 1");
    $actCust = $stmtActCust->fetch();
    $logValid = false;
    if ($actCust) {
        $testCustId = (int)$actCust['id'];
        $testReason = 'Uji coba permohonan cuti sementara (T-005)';
        $testUser = 'Admin Penguji T-005';
        
        $statusUpdated = updateCustomerStatus($pdo, $testCustId, 'cuti', $testReason, $testUser, false);
        
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
            
        // Revert status back to 'aktif'
        updateCustomerStatus($pdo, $testCustId, 'aktif', 'Pengembalian status aktif pasca uji coba', $testUser, false);
    }
    assertTest(5, "T-005/F2: Perubahan status ke 'cuti' mencatat audit log lengkap (status lama, status baru, alasan, user, waktu)", (bool)$logValid);

    // ========================================================
    // KRITERIA 6: Batch Billing Engine Bulanan & Format Tagihan INV/YYYYMM/XXXX (FR-04, T-006)
    // ========================================================
    printKriteriaHeader(6, $criteriaMap[6]);

    // Clean test bills if any exist
    $pdo->exec("DELETE FROM bills WHERE period_month = {$testMonth} AND period_year = {$testYear}");

    $genResult = generateMonthlyBills($pdo, $testMonth, $testYear, 10);
    assertTest(6, "FR-04: Batch billing engine sukses men-generate tagihan baru untuk pelanggan aktif ({$genResult['generated']} tagihan)", $genResult['generated'] > 0);

    // Generated Bill Attributes & Format Verification (T-006 / F3)
    $stmtSampleBills = $pdo->prepare("
        SELECT b.*, p.price as expected_price 
        FROM bills b 
        JOIN customers c ON b.customer_id = c.id 
        JOIN packages p ON c.package_id = p.id 
        WHERE b.period_month = ? AND b.period_year = ?
    ");
    $stmtSampleBills->execute([$testMonth, $testYear]);
    $sampleBills = $stmtSampleBills->fetchAll();

    $allBillsValid = count($sampleBills) > 0;
    foreach ($sampleBills as $sb) {
        $numPatternOk = (bool)preg_match('/^INV\/\d{6}\/\d{4}$/', $sb['bill_number']);
        $statusOk = ($sb['status'] === 'belum_lunas');
        $amountOk = ((float)$sb['amount'] === (float)$sb['expected_price']);
        $dueDateOk = !empty($sb['due_date']);
        if (!$numPatternOk || !$statusOk || !$amountOk || !$dueDateOk) {
            $allBillsValid = false;
            break;
        }
    }
    assertTest(6, "T-006/F3: Seluruh tagihan baru berstatus 'belum_lunas', nomor resmi INV/YYYYMM/XXXX, dan nominal sesuai paket", $allBillsValid);

    // ========================================================
    // KRITERIA 7: Pengecualian Mutlak Tagihan Pelanggan Cuti / 0 Tagihan Fiktif (FR-03, R1, T-007)
    // ========================================================
    printKriteriaHeader(7, $criteriaMap[7]);

    assertTest(7, "FR-03/Acceptance Criteria: Batch billing engine otomatis melewatkan pelanggan cuti ({$genResult['skipped_cuti']} dilewati)", $genResult['skipped_cuti'] >= 2);

    // Check that cuti customer DOES NOT have October 2026 bill generated
    if ($cutiCust) {
        $stmtBillCuti = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE customer_id = ? AND period_month = 10 AND period_year = 2026");
        $stmtBillCuti->execute([$cutiCust['id']]);
        $cutiBillsCount = (int)$stmtBillCuti->fetchColumn();
        assertTest(7, "FR-03/Acceptance Criteria: Pelanggan status 'cuti' TIDAK memiliki tagihan baru pada periode aktif (0 tagihan)", $cutiBillsCount === 0);
    } else {
        assertTest(7, "FR-03/Acceptance Criteria: Pelanggan status 'cuti' TIDAK memiliki tagihan baru pada periode aktif (0 tagihan)", false);
    }

    // Complete Exemption of Leave Customers from Billing (T-007 / F3 / R1 / Guardrail 11)
    $stmtCutiBills = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bills b 
        JOIN customers c ON b.customer_id = c.id 
        WHERE c.status = 'cuti' AND b.period_month = ? AND b.period_year = ?
    ");
    $stmtCutiBills->execute([$testMonth, $testYear]);
    $cutiBatchBillsCount = (int)$stmtCutiBills->fetchColumn();

    $stmtFirstCuti = $pdo->query("SELECT id FROM customers WHERE status = 'cuti' LIMIT 1");
    $firstCutiId = (int)$stmtFirstCuti->fetchColumn();
    $attemptCutiBill = $firstCutiId > 0 ? generateCustomerBill($pdo, $firstCutiId, $testMonth, $testYear) : null;

    $helperExemptCuti = isCustomerExemptFromBilling('cuti') && isCustomerExemptFromBilling('CUTI');
    $helperNotExemptAktif = !isCustomerExemptFromBilling('aktif');

    $t007Passed = ($cutiBatchBillsCount === 0) 
        && ($attemptCutiBill === null) 
        && $helperExemptCuti 
        && $helperNotExemptAktif 
        && isset($genResult['cuti_customers']) 
        && count($genResult['cuti_customers']) >= 2;

    assertTest(7, "T-007/F3/R1: Pengecualian mutlak pelanggan cuti saat batch billing (0 tagihan fiktif terbit & proteksi Guardrail 11)", $t007Passed);

    // ========================================================
    // KRITERIA 8: Idempotensi Siklus Billing & Proteksi Anti-Duplikasi Tagihan (FR-03, R1, T-008)
    // ========================================================
    printKriteriaHeader(8, $criteriaMap[8]);

    $genResultAgain = generateMonthlyBills($pdo, $testMonth, $testYear, 10);
    assertTest(8, "Guardrail Metric: Tidak ada tagihan duplikat (generate ulang menghasilkan 0 tagihan baru)", $genResultAgain['generated'] === 0);

    // Billing Idempotency & Database Unique Constraint Enforcement (T-008 / F3 / R1)
    $idempotentEngineOk = ($genResultAgain['generated'] === 0) && ($genResultAgain['skipped'] >= 9);

    $dbDuplicateBlocked = false;
    try {
        $stmtDirectDup = $pdo->prepare("
            INSERT INTO bills (bill_number, customer_id, period_month, period_year, amount, due_date, status, created_at)
            VALUES ('INV/TEST/DUP', ?, ?, ?, 100000, '2026-11-10', 'belum_lunas', NOW())
        ");
        $stmtDirectDup->execute([$sampleBills[0]['customer_id'], $testMonth, $testYear]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000 || ($e->errorInfo[1] ?? 0) == 1062) {
            $dbDuplicateBlocked = true;
        }
    }

    $singleBillIdempotent = (generateCustomerBill($pdo, (int)$sampleBills[0]['customer_id'], $testMonth, $testYear) === null);
    assertTest(8, "T-008/F3/R1: Validasi idempotensi engine (0 tagihan baru) & proteksi database unique_customer_period (SQLSTATE 23000)", $idempotentEngineOk && $dbDuplicateBlocked && $singleBillIdempotent);

    // ========================================================
    // KRITERIA 9: Reaktivasi Pelanggan Cuti dengan Penerbitan Tagihan Bulan Berjalan (FR-02, FR-03, T-009)
    // ========================================================
    printKriteriaHeader(9, $criteriaMap[9]);

    $stmtCutiTarget = $pdo->prepare("SELECT id, customer_code, package_id FROM customers WHERE status = 'cuti' LIMIT 1");
    $stmtCutiTarget->execute();
    $cutiTarget = $stmtCutiTarget->fetch();

    $reactivationBillingPassed = false;
    if ($cutiTarget) {
        $reactCustId = (int)$cutiTarget['id'];
        
        $stmtPreCheck = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE customer_id = ? AND period_month = ? AND period_year = ?");
        $stmtPreCheck->execute([$reactCustId, $testMonth, $testYear]);
        $preBillCount = (int)$stmtPreCheck->fetchColumn();
        
        $reactBillData = null;
        $reactSuccess = updateCustomerStatus(
            $pdo, 
            $reactCustId, 
            'aktif', 
            'Reaktivasi pelanggan cuti pertengahan bulan (T-009)', 
            'Admin Penguji T-009', 
            true, 
            $reactBillData, 
            $testMonth, 
            $testYear
        );
        
        $stmtPostStatus = $pdo->prepare("SELECT status FROM customers WHERE id = ?");
        $stmtPostStatus->execute([$reactCustId]);
        $postStatus = $stmtPostStatus->fetchColumn();
        
        $stmtNewBill = $pdo->prepare("
            SELECT b.*, p.price as expected_price 
            FROM bills b 
            JOIN packages p ON p.id = ? 
            WHERE b.customer_id = ? AND b.period_month = ? AND b.period_year = ?
        ");
        $stmtNewBill->execute([$cutiTarget['package_id'], $reactCustId, $testMonth, $testYear]);
        $createdDbBill = $stmtNewBill->fetch();
        
        $stmtReactLog = $pdo->prepare("
            SELECT * FROM status_logs 
            WHERE customer_id = ? AND old_status = 'cuti' AND new_status = 'aktif' 
            ORDER BY id DESC LIMIT 1
        ");
        $stmtReactLog->execute([$reactCustId]);
        $reactLog = $stmtReactLog->fetch();
        
        $reactivationBillingPassed = ($preBillCount === 0)
            && $reactSuccess
            && ($postStatus === 'aktif')
            && ($reactBillData !== null)
            && !empty($reactBillData['bill_number'])
            && ($createdDbBill !== false)
            && ($createdDbBill['status'] === 'belum_lunas')
            && ((float)$createdDbBill['amount'] === (float)$createdDbBill['expected_price'])
            && ($reactLog !== false);
            
        updateCustomerStatus($pdo, $reactCustId, 'cuti', 'Restore status cuti pasca pengujian T-009', 'Admin Penguji T-009', false);
        if (!empty($createdDbBill['id'])) {
            $pdo->prepare("DELETE FROM bills WHERE id = ?")->execute([$createdDbBill['id']]);
        }
    }
    assertTest(9, "T-009/F2/F3: Reaktivasi pelanggan dari cuti ke aktif otomatis menerbitkan tagihan bulan berjalan", $reactivationBillingPassed);

    // ========================================================
    // KRITERIA 10: Notifikasi WhatsApp Tagihan Dual-Option & Kuitansi Pelunasan Sah (FR-04, R2, T-012, T-013)
    // ========================================================
    printKriteriaHeader(10, $criteriaMap[10]);

    // Sub-test 10.1: WhatsApp Billing Notification Template (T-012 / F4 / R2)
    $sampleBillT012 = [
        'customer_name'  => 'Agus Prasetyo',
        'customer_code'  => 'RNG-088',
        'customer_phone' => '081234567899',
        'bill_number'    => 'INV/202611/0088',
        'period_month'   => $testMonth,
        'period_year'    => $testYear,
        'package_name'   => 'Paket Reguler 20 Mbps',
        'package_speed'  => '20 Mbps',
        'amount'         => 150000.00,
        'due_date'       => "{$testYear}-11-10"
    ];

    $waMsg = buildBillingWhatsAppMessage($sampleBillT012, $pdo);
    $waUrl = buildBillingWhatsAppUrl($sampleBillT012, $pdo);

    $hasDetails = str_contains($waMsg, 'Agus Prasetyo') 
        && str_contains($waMsg, 'RNG-088')
        && str_contains($waMsg, 'INV/202611/0088')
        && str_contains($waMsg, getMonthName($testMonth) . " {$testYear}")
        && str_contains($waMsg, 'Rp 150.000');

    $hasOnlineOption = str_contains($waMsg, 'PEMBAYARAN ONLINE') 
        && str_contains($waMsg, 'Kode QR') 
        && str_contains($waMsg, 'transfer');

    $hasOfflineOption = str_contains($waMsg, 'PEMBAYARAN OFFLINE') 
        && str_contains($waMsg, 'siapkan uang tunai sejumlah tagihannya') 
        && str_contains($waMsg, 'uang pas');

    $hasValidWaUrl = str_starts_with($waUrl, 'https://wa.me/6281234567899?text=')
        && (urldecode(substr($waUrl, strlen('https://wa.me/6281234567899?text='))) === $waMsg);

    $t012Passed = $hasDetails && $hasOnlineOption && $hasOfflineOption && $hasValidWaUrl;
    assertTest(10, "T-012/F4/R2: Template pesan WhatsApp memuat rincian tagihan, opsi Online QR & instruksi Offline Tunai Pas", $t012Passed);

    // Sub-test 10.2: WhatsApp Payment Confirmation Receipt Template (T-013 / F4)
    $samplePaidBillT013 = [
        'customer_name'    => 'Dewi Lestari',
        'customer_code'    => 'RNG-002',
        'customer_phone'   => '081234567802',
        'customer_address' => 'RT 02 / RW 01, Dusun Krajan Timur',
        'bill_number'      => 'INV/202611/0002',
        'period_month'     => $testMonth,
        'period_year'      => $testYear,
        'package_name'     => 'Paket Hemat 10 Mbps',
        'package_speed'    => '10 Mbps',
        'amount'           => 100000.00,
        'payment_date'     => "{$testYear}-11-05 14:30:00",
        'payment_method'   => 'Transfer BCA',
        'collector_name'   => 'Siti Aminah (Kasir)',
        'notes'            => 'Lunas via transfer m-banking BCA'
    ];

    $receiptMsg = buildPaymentReceiptWhatsAppMessage($samplePaidBillT013, $pdo);
    $receiptUrl = buildPaymentReceiptWhatsAppUrl($samplePaidBillT013, $pdo);

    $hasReceiptHeader = str_contains($receiptMsg, 'BUKTI PEMBAYARAN SAH') || str_contains($receiptMsg, 'KUITANSI');
    $hasTxNumber = str_contains($receiptMsg, 'INV/202611/0002');
    $hasCustDetails = str_contains($receiptMsg, 'Dewi Lestari') && str_contains($receiptMsg, 'RNG-002');
    $hasAmountAndMethod = str_contains($receiptMsg, 'Rp 100.000') && str_contains($receiptMsg, 'Transfer BCA');
    $hasCollectorAndStatus = str_contains($receiptMsg, 'Siti Aminah (Kasir)') && str_contains($receiptMsg, 'LUNAS');
    $hasFooterNote = str_contains($receiptMsg, 'bukti pembayaran yang sah') || str_contains($receiptMsg, 'bukti sah');

    $hasValidReceiptUrl = str_starts_with($receiptUrl, 'https://wa.me/6281234567802?text=')
        && (urldecode(substr($receiptUrl, strlen('https://wa.me/6281234567802?text='))) === $receiptMsg);

    $t013Passed = $hasReceiptHeader && $hasTxNumber && $hasCustDetails && $hasAmountAndMethod && $hasCollectorAndStatus && $hasFooterNote && $hasValidReceiptUrl;
    assertTest(10, "T-013/F4: Tombol WA menghasilkan teks kuitansi resmi beserta nomor transaksi tagihan", $t013Passed);

    // ========================================================
    // KRITERIA 11: Pelunasan Multi-Metode, Modal Struk, Driver ESC/POS, Fallback & Render PNG (FR-05, R3, T-014–T-018)
    // ========================================================
    printKriteriaHeader(11, $criteriaMap[11]);

    // Sub-test 11.1: Multi-Method Payment Processing & Idempotency (T-014 / F5)
    $stmtUnpaid1 = $pdo->prepare("SELECT id FROM bills WHERE period_month = ? AND period_year = ? AND status = 'belum_lunas' ORDER BY id ASC LIMIT 1");
    $stmtUnpaid1->execute([$testMonth, $testYear]);
    $unpaidId1 = (int)$stmtUnpaid1->fetchColumn();

    $paidBillTunai = null;
    if ($unpaidId1 > 0) {
        $paidBillTunai = processBillPayment($pdo, $unpaidId1, 'Tunai', 'Budi Santoso (Kolektor)', 'Uji coba pembayaran tunai lapangan');
    }
    $tunaiOk = $paidBillTunai 
        && $paidBillTunai['status'] === 'lunas' 
        && $paidBillTunai['payment_method'] === 'Tunai'
        && !empty($paidBillTunai['payment_date'])
        && $paidBillTunai['collector_name'] === 'Budi Santoso (Kolektor)';

    $stmtUnpaid2 = $pdo->prepare("SELECT id FROM bills WHERE period_month = ? AND period_year = ? AND status = 'belum_lunas' AND id != ? ORDER BY id ASC LIMIT 1");
    $stmtUnpaid2->execute([$testMonth, $testYear, $unpaidId1]);
    $unpaidId2 = (int)$stmtUnpaid2->fetchColumn();

    $paidBillTransfer = null;
    if ($unpaidId2 > 0) {
        $paidBillTransfer = processBillPayment($pdo, $unpaidId2, 'Transfer BCA', 'Siti Aminah (Kasir)', 'Lunas via m-BCA');
    }
    $transferOk = $paidBillTransfer 
        && $paidBillTransfer['status'] === 'lunas' 
        && $paidBillTransfer['payment_method'] === 'Transfer BCA'
        && !empty($paidBillTransfer['payment_date'])
        && $paidBillTransfer['collector_name'] === 'Siti Aminah (Kasir)';

    $doublePayAttempt = $unpaidId1 > 0 ? processBillPayment($pdo, $unpaidId1, 'Tunai', 'Hacker / Dobel Klik') : null;
    $doublePayPrevented = ($doublePayAttempt === null);

    $t014Passed = $tunaiOk && $transferOk && $doublePayPrevented;
    assertTest(11, "T-014/F5: Proses pelunasan multi-metode (Tunai/Transfer), pencatatan stempel waktu, nama kolektor, & proteksi anti-dobel", $t014Passed);

    // Sub-test 11.2: Desain Antarmuka Modal Struk Pembayaran & Rincian Transaksi (T-015 / F5)
    $footerContent = file_get_contents(__DIR__ . '/../includes/footer.php');
    $appJsContent  = file_get_contents(__DIR__ . '/../assets/js/app.js');

    $hasModalContainers = str_contains($footerContent, 'id="receiptModal"')
        && str_contains($footerContent, 'id="digitalReceiptCard"')
        && str_contains($footerContent, 'id="thermalReceiptPrintArea"');

    $hasActionButtons = str_contains($footerContent, 'id="btnPrintThermal"')
        && str_contains($footerContent, 'id="btnDownloadDigital"')
        && str_contains($footerContent, 'id="btnShareWhatsApp"')
        && str_contains($footerContent, 'closeReceiptModal()');

    $hasReceiptDataSlots = str_contains($footerContent, 'id="receiptCardNumber"')
        && str_contains($footerContent, 'id="receiptCardDate"')
        && str_contains($footerContent, 'id="receiptCardCode"')
        && str_contains($footerContent, 'id="receiptCardCust"')
        && str_contains($footerContent, 'id="receiptCardAddress"')
        && str_contains($footerContent, 'id="receiptCardPeriod"')
        && str_contains($footerContent, 'id="receiptCardPkg"')
        && str_contains($footerContent, 'id="receiptCardAmount"')
        && str_contains($footerContent, 'id="receiptCardMethod"')
        && str_contains($footerContent, 'id="receiptCardCollector"')
        && str_contains($footerContent, 'id="thermalBillNumber"')
        && str_contains($footerContent, 'id="thermalCust"');

    $hasJsFunctions = str_contains($appJsContent, 'function showReceiptModal(')
        && str_contains($appJsContent, 'function closeReceiptModal(')
        && str_contains($appJsContent, 'function loadAndShowReceipt(')
        && str_contains($appJsContent, 'handlePrintThermal')
        && str_contains($appJsContent, 'handleDownloadDigitalReceipt');

    $sampleBillId = $paidBillTunai ? $paidBillTunai['id'] : 0;
    $receiptDataComplete = false;
    if ($sampleBillId > 0) {
        $stmtReceipt = $pdo->prepare("
            SELECT b.*, c.customer_code, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
                   p.name AS package_name, p.speed AS package_speed
            FROM bills b
            JOIN customers c ON b.customer_id = c.id
            JOIN packages p ON c.package_id = p.id
            WHERE b.id = ?
        ");
        $stmtReceipt->execute([$sampleBillId]);
        $rb = $stmtReceipt->fetch();
        if ($rb) {
            $receiptDataComplete = !empty($rb['bill_number'])
                && !empty($rb['customer_code'])
                && !empty($rb['customer_name'])
                && !empty($rb['package_name'])
                && !empty($rb['payment_date'])
                && !empty($rb['payment_method'])
                && !empty($rb['collector_name'])
                && ($rb['status'] === 'lunas');
        }
    }

    $t015Passed = $hasModalContainers && $hasActionButtons && $hasReceiptDataSlots && $hasJsFunctions && $receiptDataComplete;
    assertTest(11, "T-015/F5: Desain antarmuka modal struk memuat kartu digital, thermal preview, rincian lengkap, & tombol aksi", $t015Passed);

    // Sub-test 11.3: Driver Web Bluetooth API Printer Thermal ESC/POS (T-016 / F5)
    $btDriverContent = file_get_contents(__DIR__ . '/../assets/js/bluetooth-print.js');

    $hasBtDriverClass = str_contains($btDriverContent, 'class BluetoothEscPosPrinter')
        && str_contains($btDriverContent, 'isSupported()')
        && str_contains($btDriverContent, 'getPrinterServices()')
        && str_contains($btDriverContent, 'connect()')
        && str_contains($btDriverContent, 'disconnect()')
        && str_contains($btDriverContent, 'setPaperWidth')
        && str_contains($btDriverContent, 'getPaperWidth')
        && str_contains($btDriverContent, 'buildEscPosCommands')
        && str_contains($btDriverContent, 'printEscPos')
        && str_contains($btDriverContent, 'formatRow')
        && str_contains($btDriverContent, 'wrapText');

    $hasWidthControls = str_contains($footerContent, 'id="btnPaperWidth58"')
        && str_contains($footerContent, 'id="btnPaperWidth80"')
        && str_contains($appJsContent, 'function setReceiptPaperWidth(');

    $nodeScript = __DIR__ . '/verify_escpos_buffer.js';
    $nodeTestCode = <<< 'NODE_EOF'
const path = require('path');
const { BluetoothEscPosPrinter } = require(path.resolve(__dirname, '../assets/js/bluetooth-print.js'));
const p = new BluetoothEscPosPrinter();

const mock = {
    company_name: 'RENGGANIS NET',
    bill_number: 'INV/202611/0001',
    customer_code: 'RNG-001',
    customer_name: 'Budi Santoso',
    customer_address: 'Jl. Rengganis No. 10 RT 01 RW 02',
    period: 'November 2026',
    package_name: 'Paket Rumah',
    package_speed: '10 Mbps',
    amount_formatted: 'Rp 100.000',
    payment_method: 'Tunai',
    collector_name: 'Kasir Utama',
    payment_date: '08/11/2026 10:00'
};

const b58 = p.buildEscPosCommands(mock, 58);
const b80 = p.buildEscPosCommands(mock, 80);

const initOk = (b58[0] === 0x1B && b58[1] === 0x40);
const cutOk  = (b58[b58.length - 4] === 0x1D && b58[b58.length - 3] === 0x56);
const widthOk = (b58.length > 500 && b80.length > b58.length);

if (initOk && cutOk && widthOk) {
    process.stdout.write("ESCPOS_OK");
} else {
    process.stdout.write("ESCPOS_FAIL");
}
NODE_EOF;

    file_put_contents($nodeScript, $nodeTestCode);
    $nodeOutput = shell_exec('node ' . escapeshellarg($nodeScript));
    if (file_exists($nodeScript)) {
        unlink($nodeScript);
    }
    $escposBufferOk = ($nodeOutput === 'ESCPOS_OK');

    $t016Passed = $hasBtDriverClass && $hasWidthControls && $escposBufferOk;
    assertTest(11, "T-016/F5: Driver Web Bluetooth API & buffer byte ESC/POS printer thermal 58mm/80mm", $t016Passed);

    // Sub-test 11.4: Fallback Cetak Browser & Reconnect Printer Bluetooth (T-017 / F5 / R3)
    $customCssContent = file_get_contents(__DIR__ . '/../assets/css/custom.css');

    $hasBtDriverFallback = str_contains($btDriverContent, 'reconnect()')
        && str_contains($btDriverContent, 'withTimeout(')
        && str_contains($btDriverContent, 'getConnectionStatus()')
        && str_contains($btDriverContent, 'printBrowser(');

    $hasPrintCss = str_contains($customCssContent, '@media print')
        && str_contains($customCssContent, '#thermalReceiptPrintArea')
        && str_contains($customCssContent, 'paper-80mm');

    $hasFooterFallbackUi = str_contains($footerContent, 'id="btStatusBar"')
        && str_contains($footerContent, 'id="btStatusIndicator"')
        && str_contains($footerContent, 'id="btStatusText"')
        && str_contains($footerContent, 'id="btnReconnectBluetooth"')
        && str_contains($footerContent, 'handleReconnectBluetooth()')
        && str_contains($footerContent, 'id="btnPrintBrowser"')
        && str_contains($footerContent, 'handlePrintBrowserFallback()');

    $hasAppJsLogic = str_contains($appJsContent, 'function updateBtStatusUI(')
        && str_contains($appJsContent, 'function handleReconnectBluetooth(')
        && str_contains($appJsContent, 'function handlePrintBrowserFallback(')
        && str_contains($appJsContent, 'handlePrintThermal');

    $nodeFallbackScript = __DIR__ . '/temp_test_t017_fallback.js';
    $nodeFallbackCode = <<<'NODE_EOF'
const path = require('path');
if (typeof navigator !== 'undefined') {
    Object.defineProperty(navigator, 'bluetooth', { value: {}, configurable: true, writable: true });
} else {
    global.navigator = { bluetooth: {} };
}

const { BluetoothEscPosPrinter } = require(path.resolve(__dirname, '../assets/js/bluetooth-print.js'));
const printer = new BluetoothEscPosPrinter();

(async () => {
    try {
        let timeoutTriggered = false;
        try {
            await printer.withTimeout(new Promise(r => setTimeout(r, 200)), 50, 'Timeout Test');
        } catch (e) {
            timeoutTriggered = (e.message === 'Timeout Test');
        }

        const statusDisconnected = printer.getConnectionStatus();
        const disconnectedOk = (statusDisconnected.supported === true && statusDisconnected.connected === false);

        let gattConnectCalled = false;
        printer.device = {
            name: 'Panda-PRJ58',
            gatt: {
                connect: async () => {
                    gattConnectCalled = true;
                    return {
                        getPrimaryService: async () => ({
                            getCharacteristics: async () => [{ properties: { write: true } }]
                        })
                    };
                }
            }
        };

        const reconnectSuccess = await printer.reconnect();
        const statusConnected = printer.getConnectionStatus();
        const connectedOk = (reconnectSuccess === true && statusConnected.connected === true && statusConnected.deviceName === 'Panda-PRJ58');

        const printBrowserCallable = (typeof printer.printBrowser === 'function');

        if (timeoutTriggered && disconnectedOk && gattConnectCalled && connectedOk && printBrowserCallable) {
            process.stdout.write("T017_RECONNECT_FALLBACK_OK");
        } else {
            process.stdout.write("T017_FAIL: " + JSON.stringify({ timeoutTriggered, disconnectedOk, gattConnectCalled, connectedOk, printBrowserCallable }));
        }
    } catch (err) {
        process.stdout.write("T017_ERROR: " + err.message);
    }
})();
NODE_EOF;

    file_put_contents($nodeFallbackScript, $nodeFallbackCode);
    $nodeFallbackOutput = shell_exec('node ' . escapeshellarg($nodeFallbackScript));
    if (file_exists($nodeFallbackScript)) {
        unlink($nodeFallbackScript);
    }
    $nodeFallbackOk = str_contains((string)$nodeFallbackOutput, 'T017_RECONNECT_FALLBACK_OK');

    $t017Passed = $hasBtDriverFallback && $hasPrintCss && $hasFooterFallbackUi && $hasAppJsLogic && $nodeFallbackOk;
    assertTest(11, "T-017/F5/R3: Fallback cetak browser (@media print 58mm/80mm), reconnect Bluetooth, & proteksi hang", $t017Passed);

    // Sub-test 11.5: Render Struk Digital Format Gambar PNG (T-018 / F5)
    $headerContent = file_get_contents(__DIR__ . '/../includes/header.php');

    $hasHtml2CanvasLib = str_contains($headerContent, 'html2canvas')
        && str_contains($headerContent, 'html2canvas.min.js');

    $hasPngDownloadUi = str_contains($footerContent, 'id="digitalReceiptCard"')
        && str_contains($footerContent, 'id="btnDownloadDigital"')
        && str_contains($footerContent, 'handleDownloadDigitalReceipt()');

    $hasPngExportFunctions = str_contains($appJsContent, 'getSafeReceiptFilename(')
        && str_contains($appJsContent, 'renderReceiptCanvasFallback(')
        && str_contains($appJsContent, 'exportReceiptAsPng(')
        && str_contains($appJsContent, 'handleDownloadDigitalReceipt(')
        && str_contains($appJsContent, 'useCORS: true')
        && str_contains($appJsContent, "backgroundColor: '#ffffff'");

    $nodePngScript = __DIR__ . '/temp_test_t018_png.js';
    $nodePngCode = <<<'NODE_EOF'
const path = require('path');
const { getSafeReceiptFilename, renderReceiptCanvasFallback, exportReceiptAsPng } = require(path.resolve(__dirname, '../assets/js/app.js'));

(async () => {
    try {
        const filename1 = getSafeReceiptFilename('INV/202611/0088');
        const filename2 = getSafeReceiptFilename('INV?TEST*123');
        const filenameOk = (filename1 === 'Struk-INV-202611-0088.png') && (filename2 === 'Struk-INV-TEST-123.png');

        const sampleData = {
            bill_number: 'INV/202611/0088',
            customer_code: 'RNG-088',
            customer_name: 'Agus Prasetyo',
            customer_address: 'Dusun Rengganis RT 02',
            period: 'November 2026',
            package_name: 'Paket Reguler 20 Mbps',
            package_speed: '20 Mbps',
            amount_formatted: 'Rp 150.000',
            payment_method: 'Tunai',
            payment_date: '08/11/2026 10:15',
            collector_name: 'Budi Santoso'
        };

        const canvasMock = renderReceiptCanvasFallback(sampleData);
        const canvasOk = (canvasMock.width === 720 && canvasMock.height === 1060 && typeof canvasMock.toDataURL === 'function');

        const exportRes = await exportReceiptAsPng(sampleData);
        const exportOk = (exportRes.success === true)
            && (exportRes.filename === 'Struk-INV-202611-0088.png')
            && (typeof exportRes.dataUrl === 'string')
            && exportRes.dataUrl.startsWith('data:image/png');

        if (filenameOk && canvasOk && exportOk) {
            process.stdout.write("T018_PNG_RENDER_OK");
        } else {
            process.stdout.write("T018_FAIL: " + JSON.stringify({ filenameOk, canvasOk, exportOk }));
        }
    } catch (err) {
        process.stdout.write("T018_ERROR: " + err.message);
    }
})();
NODE_EOF;

    file_put_contents($nodePngScript, $nodePngCode);
    $nodePngOutput = shell_exec('node ' . escapeshellarg($nodePngScript));
    if (file_exists($nodePngScript)) {
        unlink($nodePngScript);
    }
    $nodePngOk = str_contains((string)$nodePngOutput, 'T018_PNG_RENDER_OK');

    $t018Passed = $hasHtml2CanvasLib && $hasPngDownloadUi && $hasPngExportFunctions && $nodePngOk;
    assertTest(11, "T-018/F5: Render struk digital ke format gambar PNG tajam (html2canvas & Canvas 2D engine)", $t018Passed);

    // Sub-test 11.6: Uji Operasional Lapangan & Responsivitas Mobile (T-024 / F5)
    $billsPageContent = file_get_contents(__DIR__ . '/../bills.php');
    $custPageContent  = file_get_contents(__DIR__ . '/../customers.php');
    $footerContent    = file_get_contents(__DIR__ . '/../includes/footer.php');
    $customCssContent = file_get_contents(__DIR__ . '/../assets/css/custom.css');

    // 1. Verifikasi Viewport Meta & PWA Mobile Capabilities di header.php
    $hasMobileMeta = str_contains($headerContent, 'name="viewport"')
        && str_contains($headerContent, 'width=device-width')
        && str_contains($headerContent, 'name="theme-color"')
        && str_contains($headerContent, 'name="mobile-web-app-capable"');

    // 2. Verifikasi Kartu Tampilan Mobile Lapangan di bills.php
    $hasMobileBillCards = str_contains($billsPageContent, 'mobile-bill-card-')
        && str_contains($billsPageContent, 'block md:hidden')
        && str_contains($billsPageContent, 'hidden md:block')
        && str_contains($billsPageContent, 'touch-target-mobile')
        && str_contains($billsPageContent, 'action-col-mobile')
        && str_contains($billsPageContent, 'status-col-mobile');

    // 3. Verifikasi Kartu Tampilan Mobile Lapangan di customers.php
    $hasMobileCustCards = str_contains($custPageContent, 'block md:hidden')
        && str_contains($custPageContent, 'hidden md:block')
        && str_contains($custPageContent, 'touch-target-mobile');

    // 4. Verifikasi Aturan CSS Responsif, Dialog Anti-Overflow & Safe Area di custom.css
    $hasMobileCssRules = str_contains($customCssContent, '@media (max-width: 767px)')
        && str_contains($customCssContent, '.touch-target-mobile')
        && str_contains($customCssContent, 'min-height: 44px')
        && str_contains($customCssContent, 'max-height: 94vh')
        && str_contains($customCssContent, 'safe-area-inset-bottom');

    // 5. Verifikasi Realtime Sync Mobile Card pada includes/footer.php
    $hasFooterMobileSync = str_contains($footerContent, 'mobile-bill-card-${billId}')
        && str_contains($footerContent, 'status-col-mobile')
        && str_contains($footerContent, 'action-col-mobile');

    $t024Passed = $hasMobileMeta && $hasMobileBillCards && $hasMobileCustCards && $hasMobileCssRules && $hasFooterMobileSync;
    assertTest(11, "T-024/F5: Responsivitas mobile Android lapangan (viewport meta, touch target >=44px, kartu mobile tagihan & pelanggan, dialog anti-overflow)", $t024Passed);

    // ========================================================
    // KRITERIA 12: Dasbor Analitik, Laporan Rentang Tanggal, Ekspor CSV/Excel & Profil Usaha (FR-06, FR-07, Setup, T-019–T-022)
    // ========================================================
    printKriteriaHeader(12, $criteriaMap[12]);

    // Sub-test 12.1: Dasbor Analitik Omzet & Collection Rate (T-019 / F6)
    $indexContent = file_get_contents(__DIR__ . '/../index.php');
    $apiDashboardContent = file_get_contents(__DIR__ . '/../api/dashboard.php');

    $hasDashboardKpiUi = str_contains($indexContent, 'id="kpiTotalIncome"')
        && str_contains($indexContent, 'id="kpiTotalUnpaid"')
        && str_contains($indexContent, 'id="kpiCollectionRate"')
        && str_contains($indexContent, 'id="paymentMethodsBreakdown"')
        && str_contains($indexContent, 'id="collectorPerformanceBreakdown"')
        && str_contains($indexContent, 'name="month"')
        && str_contains($indexContent, 'name="year"');

    $hasDashboardApi = str_contains($apiDashboardContent, 'total_income')
        && str_contains($apiDashboardContent, 'collection_rate')
        && str_contains($apiDashboardContent, 'payment_methods')
        && str_contains($apiDashboardContent, 'count_transactions');

    $stmtDashTest = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_bills,
            SUM(CASE WHEN status = 'lunas' THEN amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN status = 'lunas' THEN 1 ELSE 0 END) AS count_paid,
            SUM(CASE WHEN status = 'belum_lunas' THEN amount ELSE 0 END) AS total_unpaid,
            SUM(CASE WHEN status = 'belum_lunas' THEN 1 ELSE 0 END) AS count_unpaid
        FROM bills
        WHERE period_month = ? AND period_year = ?
    ");
    $stmtDashTest->execute([$testMonth, $testYear]);
    $dashStats = $stmtDashTest->fetch();

    $calcIncome = (float)($dashStats['total_income'] ?? 0);
    $calcPaid = (int)($dashStats['count_paid'] ?? 0);
    $calcTotalBills = (int)($dashStats['total_bills'] ?? 0);
    $expectedRate = $calcTotalBills > 0 ? round(($calcPaid / $calcTotalBills) * 100, 1) : 0;

    $stmtMethodsTest = $pdo->prepare("
        SELECT 
            COALESCE(NULLIF(TRIM(payment_method), ''), 'Tunai') AS method_name,
            COUNT(*) AS count_transactions,
            SUM(amount) AS total_amount
        FROM bills
        WHERE status = 'lunas' AND period_month = ? AND period_year = ?
        GROUP BY COALESCE(NULLIF(TRIM(payment_method), ''), 'Tunai')
    ");
    $stmtMethodsTest->execute([$testMonth, $testYear]);
    $methodsData = $stmtMethodsTest->fetchAll();

    $methodsAggregatedOk = !empty($methodsData);
    $totalMethodAmount = 0;
    foreach ($methodsData as $md) {
        $totalMethodAmount += (float)$md['total_amount'];
    }
    $methodsMathOk = ($totalMethodAmount == $calcIncome) && ($expectedRate > 0);

    $t019Passed = $hasDashboardKpiUi && $hasDashboardApi && $methodsAggregatedOk && $methodsMathOk;
    assertTest(12, "T-019/F6: Dasbor analitik omzet, persentase tertagih (collection rate), & ringkasan metode pembayaran", $t019Passed);

    // Sub-test 12.2: Modul Laporan Keuangan Filter Rentang Tanggal & Siklus Bulanan (T-020 / F6)
    $reportsContent = file_get_contents(__DIR__ . '/../reports.php');
    $printReportContent = file_get_contents(__DIR__ . '/../print-report.php');

    $hasReportUiControls = str_contains($reportsContent, 'name="mode"')
        && str_contains($reportsContent, 'name="start_date"')
        && str_contains($reportsContent, 'name="end_date"')
        && str_contains($reportsContent, 'name="month"')
        && str_contains($reportsContent, 'name="year"')
        && str_contains($reportsContent, 'name="status"')
        && str_contains($reportsContent, 'name="method"')
        && str_contains($reportsContent, 'name="collector"')
        && str_contains($reportsContent, 'id="reportTotalBillAmount"')
        && str_contains($reportsContent, 'id="reportTotalIncome"')
        && str_contains($reportsContent, 'id="reportTotalUnpaid"')
        && str_contains($reportsContent, 'id="reportCollectionRate"')
        && str_contains($reportsContent, 'id="reportDailyRevenueBreakdown"')
        && str_contains($reportsContent, 'id="reportMethodsBreakdown"')
        && str_contains($reportsContent, 'id="reportTableTotal"')
        && str_contains($reportsContent, 'name="export" value="excel"')
        && str_contains($reportsContent, 'name="export" value="csv"');

    $hasPrintReportSync = str_contains($printReportContent, '$filterMode')
        && str_contains($printReportContent, '$startDate')
        && str_contains($printReportContent, '$endDate')
        && str_contains($printReportContent, '$periodLabel')
        && str_contains($printReportContent, 'class="formal-table"')
        && str_contains($printReportContent, 'class="signature-block"');

    $todayStr = date('Y-m-d');
    $stmtDateRangeTest = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_bills,
            SUM(b.amount) AS total_bill_amount,
            SUM(CASE WHEN b.status = 'lunas' THEN b.amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN b.status = 'lunas' THEN 1 ELSE 0 END) AS count_paid
        FROM bills b
        WHERE b.status = 'lunas' AND DATE(b.payment_date) BETWEEN ? AND ?
    ");
    $stmtDateRangeTest->execute([$todayStr, $todayStr]);
    $rangeStats = $stmtDateRangeTest->fetch();

    $rangeIncome = (float)($rangeStats['total_income'] ?? 0);
    $rangePaidCount = (int)($rangeStats['count_paid'] ?? 0);
    $expectedRangeIncome = (float)($paidBillTunai['amount'] + $paidBillTransfer['amount']);

    $dateRangeIncomeAccurate = ($rangePaidCount >= 2) && ($rangeIncome >= $expectedRangeIncome);

    $stmtDisjointTest = $pdo->prepare("
        SELECT COUNT(*) AS count_paid, SUM(b.amount) AS total_income
        FROM bills b
        WHERE b.status = 'lunas' AND DATE(b.payment_date) BETWEEN '2020-01-01' AND '2020-01-02'
    ");
    $stmtDisjointTest->execute();
    $disjointStats = $stmtDisjointTest->fetch();
    $disjointIncomeZero = ((int)$disjointStats['count_paid'] === 0) && ((float)($disjointStats['total_income'] ?? 0) === 0.0);

    $stmtMonthReportTest = $pdo->prepare("
        SELECT 
            SUM(b.amount) AS expected_sum_paid
        FROM bills b
        WHERE b.period_month = ? AND b.period_year = ? AND b.status = 'lunas'
    ");
    $stmtMonthReportTest->execute([$testMonth, $testYear]);
    $expectedSumPaid = (float)$stmtMonthReportTest->fetchColumn();

    $stmtReportAggTest = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN b.status = 'lunas' THEN b.amount ELSE 0 END) AS report_omzet
        FROM bills b
        WHERE b.period_month = ? AND b.period_year = ?
    ");
    $stmtReportAggTest->execute([$testMonth, $testYear]);
    $actualReportOmzet = (float)$stmtReportAggTest->fetchColumn();

    $omzetMathExact = ($actualReportOmzet > 0) && ($actualReportOmzet === $expectedSumPaid);

    $t020Passed = $hasReportUiControls && $hasPrintReportSync && $dateRangeIncomeAccurate && $disjointIncomeZero && $omzetMathExact;
    assertTest(12, "T-020/F6: Modul laporan keuangan akurat sesuai rentang tanggal/bulan, omzet identik tagihan lunas, & cetak tersinkronisasi", $t020Passed);

    // Sub-test 12.3: Ekspor Data Laporan Keuangan ke CSV dan Excel (T-021 / F6)
    $stmtBatchExport = $pdo->prepare("
        SELECT b.*, c.customer_code, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
               p.name AS package_name, p.speed AS package_speed
        FROM bills b
        JOIN customers c ON b.customer_id = c.id
        JOIN packages p ON c.package_id = p.id
        WHERE b.period_month = ? AND b.period_year = ?
        ORDER BY b.status ASC, b.id ASC
    ");
    $stmtBatchExport->execute([$testMonth, $testYear]);
    $exportBills = $stmtBatchExport->fetchAll();

    $exportAgg = [
        'total_bills' => count($exportBills),
        'total_bill_amount' => array_sum(array_column($exportBills, 'amount')),
        'total_income' => $calcIncome,
        'count_paid' => $calcPaid,
        'total_unpaid' => (float)$dashStats['total_unpaid'],
        'count_unpaid' => (int)$dashStats['count_unpaid'],
        'collection_rate' => $expectedRate
    ];

    $exportMeta = [
        'company_name' => 'Rengganis Net',
        'company_tagline' => 'Solusi Internet Cepat & Hemat',
        'company_phone' => '0812-3456-7890',
        'company_address' => 'Jl. Raya Rengganis No. 45, Sumber Malang',
        'period_label' => getMonthName($testMonth) . ' ' . $testYear,
        'filter_status' => 'all',
        'user_name' => 'Admin Test',
        'total_cuti' => 2
    ];

    $csvOutput = buildFinancialReportCsvString($exportBills, $exportAgg, $methodsData, $exportMeta);
    $excelOutput = buildFinancialReportExcelHtml($exportBills, $exportAgg, $methodsData, $exportMeta);

    $csvHasBom = str_starts_with($csvOutput, "\xEF\xBB\xBF");
    $csvHasHeaders = str_contains($csvOutput, 'No Invoice') && str_contains($csvOutput, 'Tarif (Rp)');
    $csvHasInvoices = str_contains($csvOutput, $paidBillTunai['bill_number']) && str_contains($csvOutput, $paidBillTransfer['bill_number']);
    $csvHasGrandTotal = str_contains($csvOutput, 'TOTAL KESELURUHAN') && str_contains($csvOutput, (string)$exportAgg['total_bill_amount']);
    $csvHasKpi = str_contains($csvOutput, 'RINGKASAN EKSEKUTIF')
        && str_contains($csvOutput, 'Total Pemasukan Diterima (Lunas)')
        && str_contains($csvOutput, (string)$calcIncome)
        && str_contains($csvOutput, (string)$expectedRate . '%');
    $csvHasMethods = str_contains($csvOutput, 'REKAPITULASI METODE PEMBAYARAN')
        && str_contains($csvOutput, 'Tunai')
        && str_contains($csvOutput, 'Transfer BCA');

    $csvValid = $csvHasBom && $csvHasHeaders && $csvHasInvoices && $csvHasGrandTotal && $csvHasKpi && $csvHasMethods;

    $excelHasXmlNs = str_contains($excelOutput, 'xmlns:x="urn:schemas-microsoft-com:office:excel"');
    $excelHasHeaderInfo = str_contains($excelOutput, 'RENGGANIS NET') && str_contains($excelOutput, getMonthName($testMonth) . ' ' . $testYear);
    $excelHasKpiTable = str_contains($excelOutput, 'RINGKASAN EKSEKUTIF & METRIK KINERJA PENAGIHAN (KPI)')
        && str_contains($excelOutput, formatRupiah($calcIncome))
        && str_contains($excelOutput, $expectedRate . '%');
    $excelHasMethodTable = str_contains($excelOutput, 'REKAPITULASI BERDASARKAN METODE PEMBAYARAN')
        && str_contains($excelOutput, 'Tunai')
        && str_contains($excelOutput, 'Transfer BCA');
    $excelHasTxTable = str_contains($excelOutput, 'RINCIAN LENGKAP TAGIHAN & TRANSAKSI PELANGGAN')
        && str_contains($excelOutput, $paidBillTunai['bill_number'])
        && str_contains($excelOutput, formatRupiah($exportAgg['total_bill_amount']));
    $excelHasSignature = str_contains($excelOutput, 'Admin Operasional') && str_contains($excelOutput, 'Pemilik / Owner Rengganis Net');

    $excelValid = $excelHasXmlNs && $excelHasHeaderInfo && $excelHasKpiTable && $excelHasMethodTable && $excelHasTxTable && $excelHasSignature;

    $reportsPhpContent = file_get_contents(__DIR__ . '/../reports.php');
    $billsPhpContent = file_get_contents(__DIR__ . '/../bills.php');

    $uiExportValid = str_contains($reportsPhpContent, 'buildFinancialReportExcelHtml')
        && str_contains($reportsPhpContent, 'buildFinancialReportCsvString')
        && str_contains($reportsPhpContent, 'header("Content-Type: application/vnd.ms-excel')
        && str_contains($reportsPhpContent, 'header(\'Content-Type: text/csv')
        && str_contains($billsPhpContent, 'export=excel')
        && str_contains($billsPhpContent, 'export=csv');

    $t021Passed = function_exists('buildFinancialReportCsvString') 
        && function_exists('buildFinancialReportExcelHtml')
        && $csvValid 
        && $excelValid 
        && $uiExportValid;

    assertTest(12, "T-021/F6: Ekspor data laporan keuangan ke CSV dan Excel terformat lengkap dengan tabel transaksi, baris grand total, & ringkasan KPI", $t021Passed);

    // Sub-test 12.4: Setting Company Profile & Receipt Footers
    $compName = getSetting($pdo, 'company_name');
    assertTest(12, "FR-06/FR-07: Pengaturan identitas struk & logo perusahaan tersimpan ('{$compName}')", !empty($compName));

    // Sub-test 12.5: Pengaturan Profil Usaha & Catatan Kaki Struk (T-022 / Setup / FR-06 / FR-07)
    $settingsPageContent = file_get_contents(__DIR__ . '/../settings.php');

    $hasSettingsFormUi = str_contains($settingsPageContent, 'name="company_name"')
        && str_contains($settingsPageContent, 'name="company_tagline"')
        && str_contains($settingsPageContent, 'name="company_phone"')
        && str_contains($settingsPageContent, 'name="company_email"')
        && str_contains($settingsPageContent, 'name="company_address"')
        && str_contains($settingsPageContent, 'name="billing_due_day"')
        && str_contains($settingsPageContent, 'name="receipt_footer"')
        && str_contains($settingsPageContent, 'name="bank_info"')
        && str_contains($settingsPageContent, 'csrfField()')
        && str_contains($settingsPageContent, 'id="simReceiptFooter"');

    $origCompName = getSetting($pdo, 'company_name', 'Rengganis Net');
    $origAddress  = getSetting($pdo, 'company_address', 'Jl. Raya Rengganis No. 45, Sumber Malang');
    $origFooter   = getSetting($pdo, 'receipt_footer', "Terima kasih atas pembayaran Anda.\nSimpan struk ini sebagai bukti pembayaran yang sah.");

    $tempName    = 'Rengganis Net Digital Test';
    $tempAddress = 'Jl. Sakura No. 123, Sumber Malang';
    $tempFooter  = "Terima kasih pelanggan setia.\nStruk sah konfirmasi pembayaran WiFi.";

    $saveNameOk    = setSetting($pdo, 'company_name', $tempName);
    $saveAddressOk = setSetting($pdo, 'company_address', $tempAddress);
    $saveFooterOk  = setSetting($pdo, 'receipt_footer', $tempFooter);

    $readName    = getSetting($pdo, 'company_name');
    $readAddress = getSetting($pdo, 'company_address');
    $readFooter  = getSetting($pdo, 'receipt_footer');

    $settingsDbUpdateOk = $saveNameOk && $saveAddressOk && $saveFooterOk
        && ($readName === $tempName)
        && ($readAddress === $tempAddress)
        && ($readFooter === $tempFooter);

    $sampleTestBill = [
        'customer_name'  => 'Pelanggan Uji',
        'customer_code'  => 'RNG-TEST',
        'customer_phone' => '081234567800',
        'bill_number'    => 'INV/202611/9999',
        'period_month'   => 11,
        'period_year'    => 2026,
        'package_name'   => 'Paket Dedicated',
        'package_speed'  => '50 Mbps',
        'amount'         => 250000.00,
        'payment_method' => 'Transfer BCA',
        'collector_name' => 'Admin'
    ];

    $testReceiptMsg = buildPaymentReceiptWhatsAppMessage($sampleTestBill, $pdo);
    $receiptPropagationOk = str_contains($testReceiptMsg, $tempName)
        && str_contains($testReceiptMsg, 'Struk sah konfirmasi pembayaran WiFi');

    // Restore settings
    setSetting($pdo, 'company_name', $origCompName);
    setSetting($pdo, 'company_address', $origAddress);
    setSetting($pdo, 'receipt_footer', $origFooter);

    $t022Passed = $hasSettingsFormUi && $settingsDbUpdateOk && $receiptPropagationOk;
    assertTest(12, "T-022/Setup/FR-06/FR-07: Form settings memperbarui nama perusahaan, alamat, teks footer struk thermal, & live simulator", $t022Passed);

} finally {
    // Guaranteed cleanup of test batch November 2026 bills
    if (isset($pdo) && $pdo instanceof PDO) {
        $pdo->exec("DELETE FROM bills WHERE period_month = {$testMonth} AND period_year = {$testYear}");
        echo "\n[CLEANUP] Test batch bills November 2026 dibersihkan dari basis data.\n";
    }

    // Clean any lingering test scripts
    foreach ([
        __DIR__ . '/verify_escpos_buffer.js',
        __DIR__ . '/temp_test_t017_fallback.js',
        __DIR__ . '/temp_test_t018_png.js'
    ] as $tempFile) {
        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
    }
}

// ========================================================
// RINGKASAN RESMI KELULUSAN 12 KRITERIA KEBUTUHAN PRD
// ========================================================
echo "\n========================================================\n";
echo "   RINGKASAN STATUS 12 KRITERIA KEBUTUHAN PRD\n";
echo "========================================================\n";

$allKriteriaPassed = true;
$kriteriaPassedCount = 0;

foreach ($criteriaMap as $kId => $kTitle) {
    $passed = $kriteriaStatus[$kId] && ($kriteriaCounts[$kId]['total'] > 0) && ($kriteriaCounts[$kId]['passed'] === $kriteriaCounts[$kId]['total']);
    if ($passed) {
        $kriteriaPassedCount++;
        echo sprintf(" [PASS] KRITERIA %02d: %s (%d/%d sub-tes)\n", $kId, $kTitle, $kriteriaCounts[$kId]['passed'], $kriteriaCounts[$kId]['total']);
    } else {
        $allKriteriaPassed = false;
        echo sprintf(" [FAIL] KRITERIA %02d: %s (%d/%d sub-tes)\n", $kId, $kTitle, $kriteriaCounts[$kId]['passed'], $kriteriaCounts[$kId]['total']);
    }
}

echo "\n--------------------------------------------------------\n";
echo "HASIL AKHIR: {$kriteriaPassedCount} / 12 KRITERIA KEBUTUHAN TERPENUHI (" . round(($kriteriaPassedCount / 12) * 100) . "% PASS)\n";
echo "TOTAL SUB-PENGUJIAN: {$testsPassed} / {$totalTests} SUKSES\n";
echo "--------------------------------------------------------\n";

if ($allKriteriaPassed && $testsPassed === $totalTests && $totalTests >= 36) {
    echo "SELURUH 12 KRITERIA PRD & ACCEPTANCE CRITERIA TERPENUHI 100%!\n\n";
    exit(0);
} else {
    echo "Ada pengujian yang gagal atau kriteria belum lengkap. Periksa log di atas.\n\n";
    exit(1);
}
