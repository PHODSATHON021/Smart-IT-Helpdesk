<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;
use App\Services\AuthService;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$db = Database::getInstance()->getConnection();
$auth = new AuthService($db);
$auth->requireLogin();
$currentUser = $auth->user();

// อนุญาตให้เฉพาะ Admin และ Technician เข้าใช้งานหน้านี้
if (!in_array($currentUser['role'], ['admin', 'technician'])) {
    header('Location: index.php');
    exit;
}

// 1. ดึงข้อมูลสรุปตามสถานะ (Status)
$stmtStatus = $db->query("
    SELECT status, COUNT(*) as total 
    FROM tickets 
    GROUP BY status
");
$statusData = $stmtStatus->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. ดึงข้อมูลสรุปตามหมวดหมู่ (Category)
$stmtCategory = $db->query("
    SELECT COALESCE(c.name, 'ไม่ระบุ') as category_name, COUNT(t.id) as total 
    FROM tickets t 
    LEFT JOIN categories c ON t.category_id = c.id 
    GROUP BY c.id, c.name
");
$categoryData = $stmtCategory->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. ดึงข้อมูลสรุปตามระดับความสำคัญ (Priority)
$stmtPriority = $db->query("
    SELECT priority, COUNT(*) as total 
    FROM tickets 
    GROUP BY priority
");
$priorityData = $stmtPriority->fetchAll(PDO::FETCH_KEY_PAIR);

// ตัวเลขสรุปการ์ดสถิติรวม
$totalTickets = array_sum($statusData);
$pendingTickets = ($statusData['open'] ?? 0) + ($statusData['pending'] ?? 0);
$inProgressTickets = $statusData['in_progress'] ?? 0;
$closedTickets = ($statusData['closed'] ?? 0) + ($statusData['resolved'] ?? 0);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดสรุปสถิติ - Smart Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .stat-card { border: none; border-radius: 12px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .chart-card { border: none; border-radius: 12px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-primary shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-arrow-left me-2"></i>กลับหน้าหลัก</a>
            <span class="text-white fw-bold"><i class="fa-solid fa-chart-pie me-2"></i>Admin Dashboard & Analytics</span>
        </div>
    </nav>

    <div class="container pb-5">

        <!-- สรุปตัวเลขภาพรวม (Summary Cards) -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm bg-primary text-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small">Ticket ทั้งหมด</div>
                            <h3 class="fw-bold mb-0"><?= number_format($totalTickets) ?></h3>
                        </div>
                        <i class="fa-solid fa-ticket fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm bg-warning text-dark p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">รอดำเนินการ / รอดำเนินงาน</div>
                            <h3 class="fw-bold mb-0"><?= number_format($pendingTickets) ?></h3>
                        </div>
                        <i class="fa-solid fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm bg-info text-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small">กำลังดำเนินการ</div>
                            <h3 class="fw-bold mb-0"><?= number_format($inProgressTickets) ?></h3>
                        </div>
                        <i class="fa-solid fa-spinner fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm bg-success text-white p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small">แก้ไขสำเร็จ / ปิดงานแล้ว</div>
                            <h3 class="fw-bold mb-0"><?= number_format($closedTickets) ?></h3>
                        </div>
                        <i class="fa-solid fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- โซนแสดงกราฟวิเคราะห์ (Charts Area) -->
        <div class="row g-4 mb-4">
            <!-- กราฟวงกลม: สถานะ Ticket -->
            <div class="col-lg-4">
                <div class="card chart-card shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold">
                        <i class="fa-solid fa-chart-doughnut text-primary me-2"></i>สัดส่วนตามสถานะ (Status)
                    </div>
                    <div class="card-body d-flex justify-content-center align-items-center p-4">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- กราฟแท่ง: หมวดหมู่ปัญหา -->
            <div class="col-lg-8">
                <div class="card chart-card shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold">
                        <i class="fa-solid fa-chart-bar text-success me-2"></i>จำนวน Ticket แยกตามหมวดหมู่ (Category)
                    </div>
                    <div class="card-body p-4">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- กราฟแท่ง: ระดับความสำคัญ -->
        <div class="row g-4">
            <div class="col-12">
                <div class="card chart-card shadow-sm">
                    <div class="card-header bg-white py-3 fw-bold">
                        <i class="fa-solid fa-align-left text-danger me-2"></i>จำนวน Ticket แยกตามระดับความสำคัญ (Priority)
                    </div>
                    <div class="card-body p-4">
                        <canvas id="priorityChart" style="max-height: 280px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Data จาก PHP
        const statusData = <?= json_encode($statusData) ?>;
        const categoryData = <?= json_encode($categoryData) ?>;
        const priorityData = <?= json_encode($priorityData) ?>;

        // 1. Chart: Status (Doughnut)
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData).map(k => k.toUpperCase()),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: ['#ffc107', '#0dcaf0', '#198754', '#6c757d', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // 2. Chart: Category (Bar)
        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(categoryData),
                datasets: [{
                    label: 'จำนวน Ticket',
                    data: Object.values(categoryData),
                    backgroundColor: '#0d6efd',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        // 3. Chart: Priority (Horizontal Bar)
        new Chart(document.getElementById('priorityChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(priorityData).map(k => k.toUpperCase()),
                datasets: [{
                    label: 'จำนวน Ticket',
                    data: Object.values(priorityData),
                    backgroundColor: ['#198754', '#ffc107', '#fd7e14', '#dc3545'],
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    </script>
</body>
</html>