<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();

include "../db/db.php";

if (!isset($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "Missing order ID"]);
    exit;
}

$orderId = intval($_GET['id']);

try {

    // 1️⃣ جلب بيانات الطلب + اسم الزبون
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
        echo json_encode(["success" => false, "message" => "Order not found"]);
        exit;
    }

    // 2️⃣ جلب منتجات الطلب
    $itemStmt = $conn->prepare("
        SELECT 
            oi.product_id,
            oi.quantity,
            oi.price,
            p.name,
            p.image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $itemStmt->execute([$orderId]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3️⃣ إرسال البيانات
    echo json_encode([
        "success" => true,
        "order"   => $order,   // فيه reservation_date + notes تلقائياً
        "items"   => $items
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error",
        "error"   => $e->getMessage()
    ]);
}