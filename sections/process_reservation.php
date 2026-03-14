<?php
session_start();
include "../db/db.php";

if(!isset($_SESSION['user'])){
    header("Location: ../admin/auth/login.php");
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
        header("Location: ../sections/cart.php?error=empty");
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

    // ✅ استخدام Transaction لضمان تنفيذ كل شيء معاً
    $conn->beginTransaction();

    // إدخال الطلب
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, products, total_price, status, order_type, payment_method, order_date) 
        VALUES (?, ?, ?, 'pending', 'reservation', 'reservation', NOW())
    ");
    $stmt->execute([$userId, $productsJson, $totalPrice]);
    $orderId = $conn->lastInsertId();

    // إدخال المنتجات
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

    // ✅ حذف السلة بشكل صريح وواضح
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=?");
    $stmt->execute([$userId]);

    // ✅ التأكيد النهائي للعمليات
    $conn->commit();

    // ✅ حفظ في الـ Session أنه تم الحذف
    $_SESSION['cart_cleared'] = true;

    // التوجيه
    header("Location: checkout.php?id=" . $orderId . "&type=reservation");
    exit;

} catch(PDOException $e) {
    // ✅ التراجع عن كل شيء في حالة الخطأ
    if($conn->inTransaction()){
        $conn->rollBack();
    }
    die("خطأ أثناء معالجة الحجز: " . $e->getMessage());
}
?>