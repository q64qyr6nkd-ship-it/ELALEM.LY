<?php
session_start();
include "../db/db.php";

header('Content-Type: application/json');

if(!isset($_SESSION['user'])){
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    $stmt = $conn->prepare("SELECT c.quantity, p.id AS product_id, p.name, p.price 
                            FROM cart c 
                            JOIN products p ON c.product_id = p.id 
                            WHERE c.user_id=?");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(!$cartItems){
        echo json_encode(['success' => false, 'message' => 'السلة فارغة']);
        exit;
    }

    $products = [];
    $totalPrice = 0;
    foreach($cartItems as $item){
        $products[] = [
            'product_id' => $item['product_id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $item['quantity']
        ];
        $totalPrice += $item['price'] * $item['quantity'];
    }

    $productsJson = json_encode($products, JSON_UNESCAPED_UNICODE);

    // 🔥 إدخال الطلب مع طريقة الدفع = "cash"
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, products, total_price, status, payment_method, order_date) 
        VALUES (?, ?, ?, 'preparing', 'cash', NOW())
    ");
    $success = $stmt->execute([$userId, $productsJson, $totalPrice]);
$orderId = $conn->lastInsertId();

$insertItem = $conn->prepare("
    INSERT INTO order_items (order_id, product_id, quantity, price)
    VALUES (?, ?, ?, ?)
");

foreach ($products as $item) {
    $insertItem->execute([
        $orderId,
        $item['product_id'],
        $item['quantity'],
        $item['price']
    ]);
}
    if ($success) {
        // حذف السلة
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=?");
        $stmt->execute([$userId]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل إنشاء الطلب']);
    }

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ أثناء تأكيد الطلب: '.$e->getMessage()]);
}
?>