<?php
session_start();
include "../db/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً.']);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    // جلب السلة
    $stmt = $conn->prepare("
        SELECT c.product_id, c.quantity, p.price 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        echo json_encode(['success' => false, 'message' => 'السلة فارغة.']);
        exit;
    }

    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // رصيد المحفظة
    $balance = $conn->prepare("SELECT wallet_blance FROM users WHERE id = ?");
    $balance->execute([$userId]);
    $wallet = $balance->fetchColumn();

    if ($wallet < $total) {
        echo json_encode(['success' => false, 'message' => 'رصيد المحفظة غير كافٍ.']);
        exit;
    }

    $conn->beginTransaction();

    // إنشاء الطلب
    $orderStmt = $conn->prepare("
        INSERT INTO orders (user_id, total_price, status, payment_method, order_date) 
        VALUES (?, ?, 'preparing', 'wallet', NOW())
    ");
    $orderStmt->execute([$userId, $total]);
    $orderId = $conn->lastInsertId();

    // إدخال المنتجات
    $itemStmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price) 
        VALUES (?, ?, ?, ?)
    ");

    foreach ($cartItems as $item) {
        $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
    }

    // خصم الرصيد
    $updateBalance = $conn->prepare("
        UPDATE users SET wallet_blance = wallet_blance - ? WHERE id = ?
    ");
    $updateBalance->execute([$total, $userId]);

    
    // تحديث المبلغ المدفوع في الطلب
$updatePaid = $conn->prepare("
    UPDATE orders SET paid_amount = paid_amount + ? WHERE order_id = ?
");
$updatePaid->execute([$total, $orderId]);
    
    // حذف السلة
    $conn->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);

    // 🔥 تسجيل العملية في سجل المحفظة
    $log = $conn->prepare("
        INSERT INTO wallet_transactions (user_id, amount, type, description, order_id, created_at)
        VALUES (?, ?, 'debit', ?, ?, NOW())
    ");

    $desc = "خصم قيمة الطلبية رقم #" . $orderId;
    $log->execute([$userId, $total, $desc, $orderId]);

    $conn->commit();

    echo json_encode(['success' => true, 'order_id' => $orderId, 'message' => 'تم الدفع بنجاح!']);

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'خطأ: '.$e->getMessage()]);
}
?>
