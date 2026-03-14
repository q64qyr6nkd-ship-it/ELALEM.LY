<?php
session_start();
include "../db/db.php";

header("Content-Type: application/json");

if(!isset($_SESSION['user'])){
    echo json_encode(['success'=>false, 'message'=>'يجب تسجيل الدخول']);
    exit;
}

$userId = $_SESSION['user']['id'];

$product_id = intval($_POST['product_id'] ?? 0);
$qty = intval($_POST['quantity'] ?? 1);

if($product_id <= 0 || $qty <= 0){
    echo json_encode(['success'=>false]);
    exit;
}

// تحقق من المنتج
$stmt = $conn->prepare("SELECT id FROM products WHERE id=?");
$stmt->execute([$product_id]);

if(!$stmt->fetch()){
    echo json_encode(['success'=>false,'message'=>'المنتج غير موجود']);
    exit;
}

// تحقق إذا كان موجود مسبقاً
$stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND product_id=?");
$stmt->execute([$userId, $product_id]);
$exists = $stmt->fetch(PDO::FETCH_ASSOC);

if($exists){
    $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE id=?");
    $stmt->execute([$qty, $exists['id']]);
} else {
    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)");
    $stmt->execute([$userId, $product_id, $qty]);
}

echo json_encode(['success'=>true]);
