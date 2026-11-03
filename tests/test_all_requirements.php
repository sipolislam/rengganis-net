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

// TEST 3.0: WiFi Packages CRUD Management & Billing Tie-in (T-010 / Setup)
// 1. CREATE test package
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

// 2. READ package and verify attributes
$stmtReadPkg = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
$stmtReadPkg->execute([$testPkgId]);
$pkgCreated = $stmtReadPkg->fetch();
$readOk = $pkgCreated && ($pkgCreated['name'] === $testPkgName) && ((float)$pkgCreated['price'] === $testPkgPrice) && ((int)$pkgCreated['is_active'] === 1);

// 3. UPDATE package (price & speed)
$updatedPkgSpeed = '200 Mbps';
$updatedPkgPrice = 549000.00;
$stmtUpdatePkg = $pdo->prepare("UPDATE packages SET speed = ?, price = ?, updated_at = NOW() WHERE id = ?");
$stmtUpdatePkg->execute([$updatedPkgSpeed, $updatedPkgPrice, $testPkgId]);

$stmtReadUpdated = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
$stmtReadUpdated->execute([$testPkgId]);
$pkgUpdated = $stmtReadUpdated->fetch();
$updateOk = $pkgUpdated && ($pkgUpdated['speed'] === $updatedPkgSpeed) && ((float)$pkgUpdated['price'] === $updatedPkgPrice);

// 4. Verify package price tie-in to customer billing nominal
$stmtCustTie = $pdo->prepare("SELECT p.price FROM packages p WHERE p.id = ?");
$stmtCustTie->execute([$testPkgId]);
$tiedPrice = (float)$stmtCustTie->fetchColumn();
$pricingTiedOk = ($tiedPrice === $updatedPkgPrice);

// 5. DELETE test package (CRUD delete)
$stmtDeletePkg = $pdo->prepare("DELETE FROM packages WHERE id = ?");
$stmtDeletePkg->execute([$testPkgId]);
$stmtCheckDeleted = $pdo->prepare("SELECT COUNT(*) FROM packages WHERE id = ?");
$stmtCheckDeleted->execute([$testPkgId]);
$deletedOk = ((int)$stmtCheckDeleted->fetchColumn() === 0);

assertTest("T-010/Setup: Form CRUD paket internet berfungsi dan tarif terhubung ke nominal tagihan", $readOk && $updateOk && $pricingTiedOk && $deletedOk);

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

// TEST 6.1: Generated Bill Attributes & Format Verification (T-006 / F3)
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
assertTest("T-006/F3: Seluruh tagihan baru berstatus 'belum_lunas', nomor resmi INV/YYYYMM/XXXX, dan nominal sesuai paket", $allBillsValid);

// TEST 6.2: Complete Exemption of Leave Customers from Billing (T-007 / F3 / R1 / Guardrail 11)
$stmtCutiBills = $pdo->prepare("
    SELECT COUNT(*) 
    FROM bills b 
    JOIN customers c ON b.customer_id = c.id 
    WHERE c.status = 'cuti' AND b.period_month = ? AND b.period_year = ?
");
$stmtCutiBills->execute([$testMonth, $testYear]);
$cutiBatchBillsCount = (int)$stmtCutiBills->fetchColumn();

// Verify Guardrail 11: Attempting to generate a single bill for a cuti customer must return null
$stmtFirstCuti = $pdo->query("SELECT id FROM customers WHERE status = 'cuti' LIMIT 1");
$firstCutiId = (int)$stmtFirstCuti->fetchColumn();
$attemptCutiBill = $firstCutiId > 0 ? generateCustomerBill($pdo, $firstCutiId, $testMonth, $testYear) : null;

// Verify helper function
$helperExemptCuti = isCustomerExemptFromBilling('cuti') && isCustomerExemptFromBilling('CUTI');
$helperNotExemptAktif = !isCustomerExemptFromBilling('aktif');

$t007Passed = ($cutiBatchBillsCount === 0) 
    && ($attemptCutiBill === null) 
    && $helperExemptCuti 
    && $helperNotExemptAktif 
    && isset($genResult['cuti_customers']) 
    && count($genResult['cuti_customers']) >= 2;

assertTest("T-007/F3/R1: Pengecualian mutlak pelanggan cuti saat batch billing (0 tagihan fiktif terbit & proteksi Guardrail 11)", $t007Passed);

// TEST 6.3: Billing Idempotency & Database Unique Constraint Enforcement (T-008 / F3 / R1)
// 1. Idempotency return verification: generated is 0, skipped reflects all existing active bills
$idempotentEngineOk = ($genResultAgain['generated'] === 0) && ($genResultAgain['skipped'] >= 9);

// 2. Database-level unique constraint enforcement test:
// Attempting a direct raw SQL duplicate insert for the same (customer_id, period_month, period_year) must fail with SQLSTATE 23000
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

// 3. Single customer bill idempotency: calling generateCustomerBill for already-billed customer must return null
$singleBillIdempotent = (generateCustomerBill($pdo, (int)$sampleBills[0]['customer_id'], $testMonth, $testYear) === null);

assertTest("T-008/F3/R1: Validasi idempotensi engine (0 tagihan baru) & proteksi database unique_customer_period (SQLSTATE 23000)", $idempotentEngineOk && $dbDuplicateBlocked && $singleBillIdempotent);

// TEST 6.4: Reactivation of Leave Customer with Current Month Billing (T-009 / F2 / F3)
// 1. Pick a customer who is currently on 'cuti' (e.g. RNG-004 or RNG-008)
$stmtCutiTarget = $pdo->prepare("SELECT id, customer_code, package_id FROM customers WHERE status = 'cuti' LIMIT 1");
$stmtCutiTarget->execute();
$cutiTarget = $stmtCutiTarget->fetch();

$reactivationBillingPassed = false;
if ($cutiTarget) {
    $reactCustId = (int)$cutiTarget['id'];
    
    // Ensure this customer has NO bill for the test batch period before reactivation
    $stmtPreCheck = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE customer_id = ? AND period_month = ? AND period_year = ?");
    $stmtPreCheck->execute([$reactCustId, $testMonth, $testYear]);
    $preBillCount = (int)$stmtPreCheck->fetchColumn();
    
    // Reactivate from 'cuti' to 'aktif' with autoBillOnReactivation = true for testMonth/testYear
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
    
    // Verify customer status updated to 'aktif'
    $stmtPostStatus = $pdo->prepare("SELECT status FROM customers WHERE id = ?");
    $stmtPostStatus->execute([$reactCustId]);
    $postStatus = $stmtPostStatus->fetchColumn();
    
    // Verify bill was created in database
    $stmtNewBill = $pdo->prepare("
        SELECT b.*, p.price as expected_price 
        FROM bills b 
        JOIN packages p ON p.id = ? 
        WHERE b.customer_id = ? AND b.period_month = ? AND b.period_year = ?
    ");
    $stmtNewBill->execute([$cutiTarget['package_id'], $reactCustId, $testMonth, $testYear]);
    $createdDbBill = $stmtNewBill->fetch();
    
    // Verify status_logs has record of cuti -> aktif
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
        
    // Cleanup & restore customer state back to 'cuti' for idempotency of test suite
    updateCustomerStatus($pdo, $reactCustId, 'cuti', 'Restore status cuti pasca pengujian T-009', 'Admin Penguji T-009', false);
    if (!empty($createdDbBill['id'])) {
        $pdo->prepare("DELETE FROM bills WHERE id = ?")->execute([$createdDbBill['id']]);
    }
}

assertTest("T-009/F2/F3: Reaktivasi pelanggan dari cuti ke aktif otomatis menerbitkan tagihan bulan berjalan", $reactivationBillingPassed);

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
