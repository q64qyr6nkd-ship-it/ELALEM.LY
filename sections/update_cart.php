<?php
session_start();
include "../db/db.php";

header("Content-Type: application/json");

if(!isset($_SESSION['user'])){
    echo json_encode(['success'=>false]);
    exit;
}

$user = $_SESSION['user']['id'];

$cartId = intval($_POST['cart_id'] ?? 0);
$action = $_POST['action'] ?? '';

if(!$cartId || !$action){
    echo json_encode(['success'=>false]);
    exit;
}

// جلب السطر
$stmt = $conn->prepare("SELECT quantity FROM cart WHERE id=? AND user_id=?");
$stmt->execute([$cartId, $user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row){
    echo json_encode(['success'=>false]);
    exit;
}

if($action === "increase"){
    $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id=? AND user_id=?")
         ->execute([$cartId, $user]);

} elseif($action === "decrease"){
    if($row['quantity'] > 1){
        $conn->prepare("UPDATE cart SET quantity = quantity - 1 WHERE id=? AND user_id=?")
             ->execute([$cartId, $user]);
    }

} elseif($action === "remove"){
    $conn->prepare("DELETE FROM cart WHERE id=? AND user_id=?")
         ->execute([$cartId, $user]);
}

echo json_encode(['success'=>true]);
