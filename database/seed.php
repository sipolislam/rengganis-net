<?php
/**
 * Database Seeder for Rengganis Net
 * Run via CLI: php database/seed.php
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

echo "Memulai seeding database Rengganis Net...\n";

// 1. Seed Settings
$settings = [
    'company_name'     => 'Rengganis Net',
    'company_tagline'  => 'Solusi Internet Cepat & Hemat',
    'company_phone'    => '0812-3456-7890',
    'company_address'  => 'Jl. Raya Rengganis No. 45, Sumber Malang',
    'company_email'    => 'admin@rengganis.net',
    'receipt_footer'   => "Terima kasih atas pembayaran Anda.\nSimpan struk ini sebagai bukti pembayaran yang sah.",
    'bank_info'        => "BCA: 123-456-7890 (a.n Rengganis Net)\nBRI: 0987-01-023456-50-1 (a.n Rengganis Net)\nDANA/Gopay: 0812-3456-7890",
    'billing_due_day'  => '10' // default tanggal jatuh tempo setiap bulan
];

$stmtSetting = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
foreach ($settings as $k => $v) {
    $stmtSetting->execute([$k, $v]);
}
echo "Settings seeded.\n";

// 2. Seed Users (Admin & Teknisi)
$users = [
    [
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_BCRYPT),
        'name'     => 'Admin Utama (Pengelola)',
        'role'     => 'admin',
        'phone'    => '081234567890'
    ],
    [
        'username' => 'teknisi',
        'password' => password_hash('teknisi123', PASSWORD_BCRYPT),
        'name'     => 'Budi Santoso (Teknisi Lapangan)',
        'role'     => 'teknisi',
        'phone'    => '081398765432'
    ],
    [
        'username' => 'kolektor',
        'password' => password_hash('kolektor123', PASSWORD_BCRYPT),
        'name'     => 'Siti Rahma (Kasir/Penagih)',
        'role'     => 'admin',
        'phone'    => '085711223344'
    ]
];

$stmtUser = $pdo->prepare("INSERT INTO users (username, password, name, role, phone) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), role=VALUES(role)");
foreach ($users as $u) {
    $stmtUser->execute([$u['username'], $u['password'], $u['name'], $u['role'], $u['phone']]);
}
echo "Users seeded.\n";

// 3. Seed Packages
$packages = [
    [
        'name'        => 'Paket Hemat 10 Mbps',
        'speed'       => '10 Mbps',
        'price'       => 100000.00,
        'description' => 'Akses lancar untuk browsing & sosmed 2-3 perangkat.'
    ],
    [
        'name'        => 'Paket Reguler 20 Mbps',
        'speed'       => '20 Mbps',
        'price'       => 150000.00,
        'description' => 'Streaming HD mulus & WFH cocok untuk 4-6 perangkat.'
    ],
    [
        'name'        => 'Paket Bisnis 30 Mbps',
        'speed'       => '30 Mbps',
        'price'       => 200000.00,
        'description' => 'Koneksi prioritas stabil untuk UMKM / keluarga besar 8-10 perangkat.'
    ],
    [
        'name'        => 'Paket Ultra 50 Mbps',
        'speed'       => '50 Mbps',
        'price'       => 300000.00,
        'description' => 'Kecepatan tinggi super cepat untuk gaming, streaming 4K & multi-device.'
    ]
];

$stmtPackage = $pdo->prepare("INSERT INTO packages (name, speed, price, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE speed=VALUES(speed), price=VALUES(price)");
// Check if packages exist
$countPkg = $pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
if ($countPkg == 0) {
    foreach ($packages as $pkg) {
        $stmtPackage->execute([$pkg['name'], $pkg['speed'], $pkg['price'], $pkg['description']]);
    }
}
echo "Packages seeded.\n";

// Fetch package IDs
$pkgRows = $pdo->query("SELECT id, name, price FROM packages")->fetchAll();
$pkgMap = [];
foreach ($pkgRows as $p) {
    $pkgMap[] = $p['id'];
}

// 4. Seed Customers (12 sample customers with various statuses)
$customers = [
    [
        'code'    => 'RNG-001',
        'name'    => 'Ahmad Fauzi',
        'phone'   => '081234567801',
        'address' => 'RT 01 / RW 02, Dusun Krajan Barat',
        'pkg_idx' => 1, // 20 Mbps (150.000)
        'ip'      => '192.168.10.11',
        'date'    => '2026-01-15',
        'status'  => 'aktif',
        'notes'   => 'Dekat tiang FO utama no 3'
    ],
    [
        'code'    => 'RNG-002',
        'name'    => 'Dewi Lestari',
        'phone'   => '081234567802',
        'address' => 'RT 02 / RW 02, Dusun Krajan Timur',
        'pkg_idx' => 0, // 10 Mbps (100.000)
        'ip'      => '192.168.10.12',
        'date'    => '2026-02-10',
        'status'  => 'aktif',
        'notes'   => 'Rumah cat hijau pagar besi'
    ],
    [
        'code'    => 'RNG-003',
        'name'    => 'Bambang Supriyanto',
        'phone'   => '081234567803',
        'address' => 'RT 03 / RW 01, Dusun Krajan Tengah',
        'pkg_idx' => 2, // 30 Mbps (200.000)
        'ip'      => '192.168.10.13',
        'date'    => '2026-02-20',
        'status'  => 'aktif',
        'notes'   => 'Toko Kelontong Berkah'
    ],
    [
        'code'    => 'RNG-004',
        'name'    => 'Hendra Wijaya',
        'phone'   => '081234567804',
        'address' => 'RT 01 / RW 03, Dusun Kemuning',
        'pkg_idx' => 1, // 20 Mbps (150.000)
        'ip'      => '192.168.10.14',
        'date'    => '2026-03-05',
        'status'  => 'cuti', // Status Cuti!
        'notes'   => 'Cuti kerja keluar pulau selama 2 bulan (Oktober-November)'
    ],
    [
        'code'    => 'RNG-005',
        'name'    => 'Siti Aisyah',
        'phone'   => '081234567805',
        'address' => 'RT 04 / RW 02, Dusun Krajan Barat',
        'pkg_idx' => 0, // 10 Mbps (100.000)
        'ip'      => '192.168.10.15',
        'date'    => '2026-03-12',
        'status'  => 'aktif',
        'notes'   => 'Belakang musholla Nurul Huda'
    ],
    [
        'code'    => 'RNG-006',
        'name'    => 'Rudi Gunawan',
        'phone'   => '081234567806',
        'address' => 'RT 02 / RW 04, Dusun Sukorejo',
        'pkg_idx' => 3, // 50 Mbps (300.000)
        'ip'      => '192.168.10.16',
        'date'    => '2026-04-01',
        'status'  => 'aktif',
        'notes'   => 'Usaha fotokopi dan cetak'
    ],
    [
        'code'    => 'RNG-007',
        'name'    => 'Nurul Hidayah',
        'phone'   => '081234567807',
        'address' => 'RT 01 / RW 01, Dusun Krajan Selatan',
        'pkg_idx' => 1, // 20 Mbps (150.000)
        'ip'      => '192.168.10.17',
        'date'    => '2026-04-18',
        'status'  => 'aktif',
        'notes'   => 'Dekat pos kamling'
    ],
    [
        'code'    => 'RNG-008',
        'name'    => 'Eko Prasetyo',
        'phone'   => '081234567808',
        'address' => 'RT 03 / RW 03, Dusun Kemuning',
        'pkg_idx' => 0, // 10 Mbps (100.000)
        'ip'      => '192.168.10.18',
        'date'    => '2026-05-02',
        'status'  => 'cuti', // Status Cuti!
        'notes'   => 'Rumah sedang direnovasi, minta stop sementara'
    ],
    [
        'code'    => 'RNG-009',
        'name'    => 'Iwan Setiawan',
        'phone'   => '081234567809',
        'address' => 'RT 02 / RW 01, Dusun Krajan Barat',
        'pkg_idx' => 1, // 20 Mbps (150.000)
        'ip'      => '192.168.10.19',
        'date'    => '2026-05-15',
        'status'  => 'isolir', // Status Isolir!
        'notes'   => 'Menunggak 2 bulan belum dibayar'
    ],
    [
        'code'    => 'RNG-010',
        'name'    => 'Wahyu Pratama',
        'phone'   => '081234567810',
        'address' => 'RT 05 / RW 02, Dusun Krajan Timur',
        'pkg_idx' => 2, // 30 Mbps (200.000)
        'ip'      => '192.168.10.20',
        'date'    => '2026-06-01',
        'status'  => 'aktif',
        'notes'   => 'Depan SDN 1 Rengganis'
    ],
    [
        'code'    => 'RNG-011',
        'name'    => 'Sri Wahyuni',
        'phone'   => '081234567811',
        'address' => 'RT 01 / RW 02, Dusun Sukorejo',
        'pkg_idx' => 0, // 10 Mbps (100.000)
        'ip'      => '192.168.10.21',
        'date'    => '2026-06-20',
        'status'  => 'aktif',
        'notes'   => 'Warung kopi Mba Sri'
    ],
    [
        'code'    => 'RNG-012',
        'name'    => 'Agus Firmansyah',
        'phone'   => '081234567812',
        'address' => 'RT 03 / RW 04, Dusun Krajan Tengah',
        'pkg_idx' => 1, // 20 Mbps (150.000)
        'ip'      => '192.168.10.22',
        'date'    => '2026-07-05',
        'status'  => 'aktif',
        'notes'   => 'Bengkel Motor Agus'
    ]
];

$countCust = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
if ($countCust == 0) {
    $stmtCust = $pdo->prepare("INSERT INTO customers (customer_code, name, phone, address, package_id, ip_address, installation_date, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($customers as $c) {
        $packageId = $pkgMap[$c['pkg_idx'] % count($pkgMap)];
        $stmtCust->execute([
            $c['code'],
            $c['name'],
            $c['phone'],
            $c['address'],
            $packageId,
            $c['ip'],
            $c['date'],
            $c['status'],
            $c['notes']
        ]);
        $custId = $pdo->lastInsertId();

        // If status is cuti or isolir, seed status log
        if ($c['status'] === 'cuti') {
            $stmtLog = $pdo->prepare("INSERT INTO status_logs (customer_id, old_status, new_status, reason, changed_by, created_at) VALUES (?, 'aktif', 'cuti', ?, 'Admin Utama', '2026-09-28 10:00:00')");
            $stmtLog->execute([$custId, $c['notes']]);
        } elseif ($c['status'] === 'isolir') {
            $stmtLog = $pdo->prepare("INSERT INTO status_logs (customer_id, old_status, new_status, reason, changed_by, created_at) VALUES (?, 'aktif', 'isolir', 'Keterlambatan pembayaran lebih dari 15 hari', 'Admin Utama', '2026-10-01 08:30:00')");
            $stmtLog->execute([$custId]);
        }
    }
}
echo "Customers seeded.\n";

// 5. Seed Bills for September and October 2026
$countBills = $pdo->query("SELECT COUNT(*) FROM bills")->fetchColumn();
if ($countBills == 0) {
    $allCusts = $pdo->query("SELECT c.id, c.status, p.price, c.installation_date FROM customers c JOIN packages p ON c.package_id = p.id")->fetchAll();
    
    $stmtBill = $pdo->prepare("INSERT INTO bills (bill_number, customer_id, period_month, period_year, amount, due_date, status, payment_date, payment_method, collector_name, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // September 2026 bills (Month 9) - All who were installed before Sept paid
    $invSeq = 1;
    foreach ($allCusts as $cust) {
        $billNum = sprintf("INV/202609/%04d", $invSeq++);
        $isPaid = ($cust['status'] !== 'isolir'); // Isolir was unpaid
        $stmtBill->execute([
            $billNum,
            $cust['id'],
            9,
            2026,
            $cust['price'],
            '2026-09-10',
            $isPaid ? 'lunas' : 'belum_lunas',
            $isPaid ? '2026-09-06 14:20:00' : null,
            $isPaid ? 'Tunai' : null,
            $isPaid ? 'Admin Utama' : null,
            $isPaid ? 'Pembayaran tepat waktu via kantor' : 'Belum dibayar hingga lewat jatuh tempo',
            '2026-09-01 00:00:00'
        ]);
    }

    // October 2026 bills (Month 10) - Current Month
    // CRITICAL: Customers with status 'cuti' are EXCLUDED (FR-03 requirement)!
    $invSeq = 1;
    foreach ($allCusts as $cust) {
        if ($cust['status'] === 'cuti') {
            // Customer cuti: DO NOT GENERATE BILL!
            continue;
        }

        $billNum = sprintf("INV/202610/%04d", $invSeq++);
        // Some customers have already paid in early October, others pending
        $isPaid = ($invSeq % 2 === 0);
        $payDate = $isPaid ? '2026-10-05 10:15:00' : null;
        $payMethod = $isPaid ? ($invSeq % 4 === 0 ? 'Transfer BCA' : 'Tunai') : null;
        $collector = $isPaid ? 'Siti Rahma (Kasir)' : null;

        $stmtBill->execute([
            $billNum,
            $cust['id'],
            10,
            2026,
            $cust['price'],
            '2026-10-10',
            $isPaid ? 'lunas' : 'belum_lunas',
            $payDate,
            $payMethod,
            $collector,
            $isPaid ? 'Lunas awal bulan' : null,
            '2026-10-01 00:00:00'
        ]);
    }
}
echo "Bills seeded.\n";
echo "Seeding selesai dengan sukses!\n";
