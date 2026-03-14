<?php
include "../db/db.php";

if (!isset($_GET['id'])) {
    die("No order id.");
}

$orderId = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT o.*, u.full_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.order_id = ?
    LIMIT 1
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found.");
}

$statusMap = [
    'preparing'  => 'جاري التحضير',
    'ready'      => 'جاهز',
    'delivering' => 'جاري التوصيل',
    'delivered'  => 'تم التوصيل',
    'reject'     => 'مرفوض',
];

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إيصال الطلب #<?= $orderId; ?></title>
<style>
body {
    font-family: Tahoma, Arial, sans-serif;
    padding: 20px;
    direction: rtl;
}
h2 {
    text-align: center;
    margin-bottom: 10px;
}
.info p {
    margin: 4px 0;
}
hr {
    margin: 10px 0;
}
</style>
</head>
<body>

<h2>حلويات العالم - إيصال طلب</h2>

<div class="info">
    <p><strong>رقم الطلب:</strong> #<?= $order['order_id']; ?></p>
    <p><strong>الاسم:</strong> <?= htmlspecialchars($order['full_name'] ?? 'غير معروف'); ?></p>
    <p><strong>الهاتف:</strong> <?= htmlspecialchars($order['delivery_phone'] ?? ''); ?></p>
    <p><strong>المدينة:</strong> <?= htmlspecialchars($order['delivery_city'] ?? ''); ?></p>
    <p><strong>العنوان:</strong> <?= htmlspecialchars($order['delivery_address'] ?? ''); ?></p>
    <?php if (!empty($order['delivery_landmark'])): ?>
        <p><strong>معلم قريب:</strong> <?= htmlspecialchars($order['delivery_landmark']); ?></p>
    <?php endif; ?>
    <?php if (!empty($order['delivery_notes'])): ?>
        <p><strong>ملاحظات:</strong> <?= htmlspecialchars($order['delivery_notes']); ?></p>
    <?php endif; ?>
    <hr>
    <p><strong>الإجمالي:</strong> <?= number_format($order['total_price'], 2); ?> د.ل</p>
    <p><strong>طريقة الدفع:</strong> <?= $order['payment_method'] === 'wallet' ? 'محفظة' : 'كاش عند التوصيل'; ?></p>
    <p><strong>حالة الطلب:</strong> <?= $statusMap[$order['status']] ?? $order['status']; ?></p>
    <p><strong>تاريخ الطلب:</strong> <?= $order['order_date']; ?></p>
</div>

<script>
window.print();
</script>
</body>
</html>