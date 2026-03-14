<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();

include "../db/db.php";

if (!isset($_POST['order_id'], $_POST['status'])) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit;
}

$orderId   = intval($_POST['order_id']);
$newStatus = $_POST['status'];

try {

    // 1) جلب بيانات الطلب
    $stmt = $conn->prepare("
        SELECT user_id, total_price, payment_method, status 
        FROM orders 
        WHERE order_id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(["success" => false, "message" => "Order not found"]);
        exit;
    }

    $userId    = $order['user_id'];
    $amount    = $order['total_price'];
    $method    = $order['payment_method'];
    $oldStatus = $order['status'];

    // 2) تحديث الحالة
    $stmt2 = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt2->execute([$newStatus, $orderId]);

    // 3) تنفيذ الاسترجاع إذا الشروط مستوفية
    if ($method === "wallet" && $newStatus === "reject" && $oldStatus !== "reject") {

        // أ) إعادة المبلغ للمحفظة
        $stmt3 = $conn->prepare("
            UPDATE users SET wallet_blance = wallet_blance + ? WHERE id = ?
        ");
        $stmt3->execute([$amount, $userId]);

        // ب) تسجيل العملية في سجل المحفظة
        $stmt4 = $conn->prepare("
            INSERT INTO wallet_transactions (user_id, amount, type, description, order_id, created_at)
            VALUES (?, ?, 'add', 'استرجاع مبلغ الطلب المرفوض', ?, NOW())
        ");
        $stmt4->execute([$userId, $amount, $orderId]);
    }

    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>