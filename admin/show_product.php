<?php
session_start();
include "../db/db.php";

header('Content-Type: application/json');

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin'){
    echo json_encode(['success'=>false,'message'=>'غير مسموح']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if($id <= 0){
        echo json_encode(['success'=>false,'message'=>'ID غير صحيح']);
        exit;
    }
    try {
        $stmt = $conn->prepare("UPDATE products SET available = 1 WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['success'=>true]);
    } catch(PDOException $e){
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
} else {
    echo json_encode(['success'=>false,'message'=>'طريقة غير مسموحة']);
}
?>