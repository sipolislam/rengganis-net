<?php
/**
 * API Endpoint: Dashboard Analytics & Metrics (F6 / T-019)
 * Provides JSON aggregation for monthly revenue, collection rate, payment methods, and collector performance.
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
$action = $_GET['action'] ?? 'summary';

try {
    switch ($action) {
        case 'summary':
        case 'metrics':
            $month = isset($_GET['month']) ? max(1, min(12, (int)$_GET['month'])) : (int)date('n');
            $year = isset($_GET['year']) ? max(2020, min(2035, (int)$_GET['year'])) : (int)date('Y');
            $monthName = getMonthName($month);

            // 1. Financial KPIs
            $stmtFinance = $pdo->prepare("
                SELECT 
                    COUNT(*) AS total_bills,
                    SUM(CASE WHEN status = 'lunas' THEN amount ELSE 0 END) AS total_income,
                    SUM(CASE WHEN status = 'lunas' THEN 1 ELSE 0 END) AS count_paid,
                    SUM(CASE WHEN status = 'belum_lunas' THEN amount ELSE 0 END) AS total_unpaid,
                    SUM(CASE WHEN status = 'belum_lunas' THEN 1 ELSE 0 END) AS count_unpaid
                FROM bills
                WHERE period_month = ? AND period_year = ?
            ");
            $stmtFinance->execute([$month, $year]);
            $finStats = $stmtFinance->fetch();

            $totalIncome = (float)($finStats['total_income'] ?? 0);
            $totalUnpaid = (float)($finStats['total_unpaid'] ?? 0);
            $totalPotential = $totalIncome + $totalUnpaid;
            $countPaid = (int)($finStats['count_paid'] ?? 0);
            $countUnpaid = (int)($finStats['count_unpaid'] ?? 0);
            $totalBills = (int)($finStats['total_bills'] ?? 0);

            $collectionRate = $totalBills > 0 ? round(($countPaid / $totalBills) * 100, 1) : 0;
            $amountCollectionRate = $totalPotential > 0 ? round(($totalIncome / $totalPotential) * 100, 1) : 0;

            // 2. Payment Methods Breakdown (F6 / T-019)
            $stmtMethods = $pdo->prepare("
                SELECT 
                    COALESCE(NULLIF(TRIM(payment_method), ''), 'Tunai') AS method_name,
                    COUNT(*) AS count_transactions,
                    SUM(amount) AS total_amount
                FROM bills
                WHERE status = 'lunas' AND period_month = ? AND period_year = ?
                GROUP BY COALESCE(NULLIF(TRIM(payment_method), ''), 'Tunai')
                ORDER BY total_amount DESC
            ");
            $stmtMethods->execute([$month, $year]);
            $rawMethods = $stmtMethods->fetchAll();

            $methodsBreakdown = [];
            foreach ($rawMethods as $m) {
                $mAmount = (float)$m['total_amount'];
                $percentage = $totalIncome > 0 ? round(($mAmount / $totalIncome) * 100, 1) : 0;
                $methodsBreakdown[] = [
                    'method' => $m['method_name'],
                    'count'  => (int)$m['count_transactions'],
                    'amount' => $mAmount,
                    'amount_formatted' => formatRupiah($mAmount),
                    'percentage' => $percentage
                ];
            }

            // 3. Collector / Cashier Performance Breakdown
            $stmtCollectors = $pdo->prepare("
                SELECT 
                    COALESCE(NULLIF(TRIM(collector_name), ''), 'Admin / Kasir') AS collector_name,
                    COUNT(*) AS count_collected,
                    SUM(amount) AS total_amount
                FROM bills
                WHERE status = 'lunas' AND period_month = ? AND period_year = ?
                GROUP BY COALESCE(NULLIF(TRIM(collector_name), ''), 'Admin / Kasir')
                ORDER BY total_amount DESC
            ");
            $stmtCollectors->execute([$month, $year]);
            $rawCollectors = $stmtCollectors->fetchAll();

            $collectorsBreakdown = [];
            foreach ($rawCollectors as $c) {
                $cAmount = (float)$c['total_amount'];
                $percentage = $totalIncome > 0 ? round(($cAmount / $totalIncome) * 100, 1) : 0;
                $collectorsBreakdown[] = [
                    'collector' => $c['collector_name'],
                    'count'     => (int)$c['count_collected'],
                    'amount'    => $cAmount,
                    'amount_formatted' => formatRupiah($cAmount),
                    'percentage' => $percentage
                ];
            }

            // 4. Customer Counts
            $stmtCust = $pdo->query("
                SELECT 
                    COUNT(*) AS total_all,
                    SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) AS total_aktif,
                    SUM(CASE WHEN status = 'cuti' THEN 1 ELSE 0 END) AS total_cuti,
                    SUM(CASE WHEN status = 'isolir' THEN 1 ELSE 0 END) AS total_isolir
                FROM customers
            ");
            $custStats = $stmtCust->fetch();

            echo json_encode([
                'success' => true,
                'period'  => [
                    'month'      => $month,
                    'year'       => $year,
                    'month_name' => $monthName
                ],
                'kpis' => [
                    'total_income'             => $totalIncome,
                    'total_income_formatted'   => formatRupiah($totalIncome),
                    'total_unpaid'             => $totalUnpaid,
                    'total_unpaid_formatted'   => formatRupiah($totalUnpaid),
                    'total_potential'          => $totalPotential,
                    'total_potential_formatted'=> formatRupiah($totalPotential),
                    'count_paid'               => $countPaid,
                    'count_unpaid'             => $countUnpaid,
                    'total_bills'              => $totalBills,
                    'collection_rate'          => $collectionRate,
                    'amount_collection_rate'   => $amountCollectionRate
                ],
                'payment_methods' => $methodsBreakdown,
                'collectors'      => $collectorsBreakdown,
                'customers'       => [
                    'total_all'   => (int)$custStats['total_all'],
                    'total_aktif' => (int)$custStats['total_aktif'],
                    'total_cuti'  => (int)$custStats['total_cuti'],
                    'total_isolir'=> (int)$custStats['total_isolir']
                ]
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}
