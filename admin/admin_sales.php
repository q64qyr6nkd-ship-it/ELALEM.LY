<?php
// 🔎 إظهار الأخطاء (للتطوير فقط)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "check_access.php";
requirePermission("manage_reports");
include "../db/db.php";

// =======================
// التاريخ اليوم
$today = date('Y-m-d');

// =======================
// 1️⃣ المنتجات المخفية
$hiddenStmt = $conn->prepare("
    SELECT * FROM products 
    WHERE available = 0 
    ORDER BY id DESC
");
$hiddenStmt->execute();
$hiddenProducts = $hiddenStmt->fetchAll(PDO::FETCH_ASSOC);

// =======================
// 2️⃣ أكثر 10 منتجات مبيعاً
$salesStmt = $conn->prepare("
    SELECT p.name,
           SUM(oi.quantity) AS total_qty,
           SUM(oi.price * oi.quantity) AS total_sales
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON o.order_id = oi.order_id
    WHERE o.status = 'delivered'
      AND o.order_type = 'order'
    GROUP BY p.id
    ORDER BY total_qty DESC
    LIMIT 10
");
$salesStmt->execute();
$topProducts = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// =======================
// 3️⃣ الإحصائيات العامة
$totalOrders     = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalDelivered  = $conn->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetchColumn();
$totalUsers      = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts   = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();

$totalCash = $conn->query("
    SELECT SUM(total_price)
    FROM orders
    WHERE status='delivered' AND payment_method='cash'
")->fetchColumn() ?: 0;

$totalWallet = $conn->query("
    SELECT SUM(total_price)
    FROM orders
    WHERE status='delivered' AND payment_method='wallet'
")->fetchColumn() ?: 0;

$totalRevenue = $totalCash + $totalWallet;

// =======================
// 4️⃣ أرباح اليوم - الطلبات العادية
$dailyOrdersStmt = $conn->prepare("
    SELECT SUM(total_price)
    FROM orders
    WHERE DATE(payment_date) = :today
      AND order_type = 'order'
      AND status = 'delivered'
");
$dailyOrdersStmt->execute([':today' => $today]);
$dailyOrdersTotal = $dailyOrdersStmt->fetchColumn() ?: 0;

// =======================
// 5️⃣ أرباح اليوم - الحجوزات (مدفوعات فعلية)
$dailyPaymentsStmt = $conn->prepare("
    SELECT SUM(amount) 
    FROM payments 
    WHERE DATE(payment_date) = :today
");
$dailyPaymentsStmt->execute([':today' => $today]);
$dailyTotal = $dailyPaymentsStmt->fetchColumn() ?: 0;

// =======================
// 6️⃣ عدد الحجوزات اليوم (التي دفعت اليوم)
$reservationsCountStmt = $conn->prepare("
    SELECT COUNT(DISTINCT order_id)
    FROM payments
    WHERE DATE(payment_date) = :today
");
$reservationsCountStmt->execute([':today' => $today]);
$reservationsCount = $reservationsCountStmt->fetchColumn() ?: 0;

// =======================
// 7️⃣ حفظ ملخص اليوم (مرة واحدة فقط لكل يوم)
$checkExists = $conn->prepare("
    SELECT COUNT(*) FROM daily_reservations_summary WHERE summary_date = :today
");
$checkExists->execute([':today' => $today]);

$upsert = $conn->prepare("
    INSERT INTO daily_reservations_summary
    (summary_date, total_amount, reservations_count, created_at)
    VALUES (:today, :amount, :count, NOW())
    ON DUPLICATE KEY UPDATE
        total_amount = VALUES(total_amount),
        reservations_count = VALUES(reservations_count)
");
$upsert->execute([
    ':today' => $today,
    ':amount' => $dailyTotal,
    ':count' => $reservationsCount
]);


// =======================
// 8️⃣ جلب ملخصات سابقة للجدول
$summaryStmt = $conn->query("SELECT * FROM daily_reservations_summary ORDER BY summary_date DESC");
$dailySummaries = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>لوحة التقارير - حلويات العالم</title>
<link rel="stylesheet" href="admin_style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.stats { display:flex; flex-wrap:wrap; gap:15px; margin-bottom:30px }
.stat-box {
    flex:1 1 200px;
    background:#fff;
    border-radius:8px;
    padding:20px;
    text-align:center;
    box-shadow:0 2px 6px rgba(0,0,0,.1)
}
.stat-box h3 { font-size:15px; margin-bottom:8px }
.stat-box p { font-size:20px; font-weight:bold; color:#800020 }
.table { width:100%; border-collapse:collapse; margin-top:20px }
.table th, .table td { border:1px solid #ddd; padding:8px; text-align:center }
.table th { background:#f4f4f4 }
</style>
</head>
<body>
<a href="admin_dashboard.php" class="back-btn"></a>
<header>📊 لوحة التقارير - حلويات العالم</header>

<div class="container">

<h1>💰 الإحصائيات المالية</h1>
<div class="stats">
    <div class="stat-box"><h3>إجمالي الطلبات</h3><p><?= $totalOrders ?></p></div>
    <div class="stat-box"><h3>الطلبات المكتملة</h3><p><?= $totalDelivered ?></p></div>
    <div class="stat-box"><h3>عدد المستخدمين</h3><p><?= $totalUsers ?></p></div>
    <div class="stat-box"><h3>عدد المنتجات</h3><p><?= $totalProducts ?></p></div>
    <div class="stat-box"><h3>🏦 محفظة</h3><p><?= number_format($totalWallet,2) ?> د.ل</p></div>
</div>

<h1>📅 مبيعات الحجوزات</h1>
<div class="stats">
    <div class="stat-box"><h3>الحجوزات (المدفوع اليوم)</h3><p><?= number_format($dailyTotal,2) ?> د.ل</p></div>
</div>

<h2>🏆 أكثر المنتجات مبيعاً</h2>
<table class="table">
<tr><th>المنتج</th><th>الكمية</th><th>الإجمالي</th></tr>
<?php foreach($topProducts as $p): ?>
<tr>
<td><?= htmlspecialchars($p['name']) ?></td>
<td><?= $p['total_qty'] ?></td>
<td><?= number_format($p['total_sales'],2) ?> د.ل</td>
</tr>
<?php endforeach; ?>
</table>

<h2>📊 الطلبات العادية</h2>
<canvas id="ordersChart"></canvas>

<h2>📊 الحجوزات</h2>
<canvas id="reservationsChart"></canvas>

<h2>📋 ملخص المدفوعات اليومية للحجوزات</h2>
<table class="table">
<tr><th>التاريخ</th><th>عدد الحجوزات</th><th>الإجمالي المدفوع</th></tr>
<?php foreach($dailySummaries as $row): ?>
<tr>
    <td><?= $row['summary_date'] ?></td>
    <td><?= $row['reservations_count'] ?></td>
    <td><?= number_format($row['total_amount'],2) ?> د.ل</td>
</tr>
<?php endforeach; ?>
</table>

</div>

<script>
async function loadChart(canvasId, type){
    const res = await fetch(`sales_chart_data.php?type=${type}&mode=day`);
    const data = await res.json();

    new Chart(document.getElementById(canvasId),{
        type:'bar',
        data:{
            labels:data.labels,
            datasets:[{
                label:type === 'order' ? 'الطلبات' : 'الحجوزات',
                data:data.values
            }]
        }
    });
}

loadChart('ordersChart','order');
loadChart('reservationsChart','reservation');
</script>

</body>
</html>