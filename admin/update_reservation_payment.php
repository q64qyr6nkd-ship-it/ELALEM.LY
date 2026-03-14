<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "غير مصرح"]);
    exit;
}

include "../db/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "طلب غير صالح"]);
    exit;
}

$orderId = intval($_POST['order_id'] ?? 0);
$amount  = floatval($_POST['amount'] ?? 0);
$newDate = $_POST['new_date'] ?? null;
$action  = $_POST['action'] ?? 'update';
$paymentDate = $_POST['payment_date'] ?? date('Y-m-d H:i:s');

if ($orderId <= 0) {
    echo json_encode(["success" => false, "message" => "رقم طلب غير صحيح"]);
    exit;
}

try {

    /* =======================
       إلغاء الحجز
    ======================= */
    if ($action === 'cancel') {
        $stmt = $conn->prepare("
            UPDATE orders 
            SET status = 'reject'
            WHERE order_id = ?
        ");
        $stmt->execute([$orderId]);

        echo json_encode(["success" => true, "message" => "تم إلغاء الحجز"]);
        exit;
    }

    /* =======================
       جلب بيانات الطلب
    ======================= */
    $stmt = $conn->prepare("
        SELECT paid_amount, total_price, reservation_date 
        FROM orders 
        WHERE order_id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(["success" => false, "message" => "الطلب غير موجود"]);
        exit;
    }

    $totalPaidNow = $order['paid_amount'] + $amount;

    if ($totalPaidNow > $order['total_price']) {
        echo json_encode(["success" => false, "message" => "المبلغ أكبر من الإجمالي"]);
        exit;
    }

    $finalDate = !empty($newDate) ? $newDate : $order['reservation_date'];
    $newStatus = 'preparing';

    
    // تسجيل الدفعة
$payStmt = $conn->prepare("
    INSERT INTO payments (order_id, amount, payment_date, created_by)
    VALUES (?, ?, ?, ?)
");
$payStmt->execute([
    $orderId,
    $amount,
    $paymentDate,
    $_SESSION['user']['id'] ?? null
]);
    
    
    /* =======================
       تحديث الطلب
    ======================= */
    $updateStmt = $conn->prepare("
        UPDATE orders 
        SET paid_amount = ?, 
            reservation_date = ?, 
            status = ?, 
            payment_date = ?
        WHERE order_id = ?
    ");

    $updateStmt->execute([
        $totalPaidNow,
        $finalDate,
        $newStatus,
        $paymentDate,
        $orderId
    ]);

    echo json_encode([
        "success" => true,
        "message" => "تم تحديث الحجز بنجاح",
        "total_paid" => $totalPaidNow
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "خطأ قاعدة بيانات",
        "error" => $e->getMessage()
    ]);
}