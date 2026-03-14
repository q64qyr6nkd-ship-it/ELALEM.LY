<?php
include "../db/db.php";
$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT o.*, u.full_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.order_id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

$itemStmt = $conn->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$itemStmt->execute([$id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; width: 80mm; margin: 0 auto; padding: 10px; color: #000; }
        .center { text-align: center; }
        .header { border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
        .details { font-size: 14px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { border-bottom: 1px solid #000; text-align: right; }
        .total-section { border-top: 2px solid #000; margin-top: 10px; padding-top: 5px; }
        .row { display: flex; justify-content: space-between; margin: 3px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="center header">
        <h2>إيصال حجز</h2>
        <p>رقم الحجز: #<?= $order['order_id'] ?></p>
        <p><?= date('Y-m-d H:i') ?></p>
    </div>

    <div class="details">
        <p><strong>العميل:</strong> <?= $order['full_name'] ?></p>
        <p><strong>الهاتف:</strong> <?= $order['delivery_phone'] ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>الصنف</th>
                <th>الكمية</th>
                <th>السعر</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td><?= $item['name'] ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= number_format($item['price'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-section">
        <div class="row"><span>إجمالي المبلغ:</span> <strong><?= number_format($order['total_price'], 2) ?> د.ل</strong></div>
        <div class="row"><span>المبلغ المدفوع:</span> <strong style="text-decoration: underline;"><?= number_format($order['paid_amount'], 2) ?> د.ل</strong></div>
        <div class="row"><span>المتبقي:</span> <strong><?= number_format($order['total_price'] - $order['paid_amount'], 2) ?> د.ل</strong></div>
    </div>

    <div class="center" style="margin-top: 20px; font-size: 12px;">
        <p>شكراً لتعاملكم معنا</p>
        <button class="no-print" onclick="window.print()" style="margin-top:10px; padding:10px;">إعادة طباعة</button>
    </div>
</body>
</html>