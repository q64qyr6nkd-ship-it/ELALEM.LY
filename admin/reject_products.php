<?php
session_start();
include "../db/db.php";

header('Content-Type: application/json');

// التحقق من صلاحيات الأدمن
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'غير مسموح']);
    exit;
}

// السماح فقط لطريقة POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة غير مسموحة']);
    exit;
}

// استقبال البيانات
$orderId = intval($_POST['order_id'] ?? 0);
$products = json_decode($_POST['rejected'] ?? "[]", true);

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'رقم الطلب غير صحيح']);
    exit;
}

try {

    // 1) جلب بيانات الطلب (للحساب)
    $stmt = $conn->prepare("
        SELECT user_id, total_price, payment_method, status 
        FROM orders 
        WHERE order_id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'الطلب غير موجود']);
        exit;
    }

    $userId    = $order['user_id'];
    $amount    = $order['total_price'];
    $method    = $order['payment_method'];
    $oldStatus = $order['status'];

    // 2) إخفاء المنتجات المحددة
    if (!empty($products)) {
        $hide = $conn->prepare("UPDATE products SET available = 0 WHERE id = ?");
        foreach ($products as $pid) {
            $hide->execute([$pid]);
        }
    }

    // 3) تغيير حالة الطلب إلى مرفوض
    $updateOrder = $conn->prepare("
        UPDATE orders 
        SET status = 'reject', reject_reason = 'بعض المنتجات غير متوفرة'
        WHERE order_id = ?
    ");
    $updateOrder->execute([$orderId]);

    // 4) إرجاع مبلغ الطلب كامل — إذا كان الدفع من المحفظة
    if ($method === "wallet" && $oldStatus !== "reject") {
        
        // إعادة المبلغ
        $stmt2 = $conn->prepare("
            UPDATE users 
            SET wallet_blance = wallet_blance + ? 
            WHERE id = ?
        ");
        $stmt2->execute([$amount, $userId]);

        // تسجيل عملية الإرجاع
        $stmt3 = $conn->prepare("
            INSERT INTO wallet_transactions 
            (user_id, amount, type, description, order_id, created_at)
            VALUES (?, ?, 'add', 'استرجاع كامل مبلغ الطلب بسبب الرفض', ?, NOW())
        ");
        $stmt3->execute([$userId, $amount, $orderId]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'تم رفض الطلب بالكامل وإرجاع المبلغ (إن وجد) وإخفاء المنتجات.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
}
?>