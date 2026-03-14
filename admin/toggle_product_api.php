<?php
session_start();

include "check_access.php";
requirePermission("manage_products");

include "../db/db.php";

header('Content-Type: application/json');

// التحقق من POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة غير مسموحة']);
    exit;
}

// التحقق من البيانات
if (!isset($_POST['id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات ناقصة']);
    exit;
}

$id = intval($_POST['id']);
$action = $_POST['action'];

try {

    if ($action === "hide") {

        $stmt = $conn->prepare("UPDATE products SET available = 0 WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'تم إخفاء المنتج']);
        exit;

    } elseif ($action === "show") {

        $stmt = $conn->prepare("UPDATE products SET available = 1 WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'تم إظهار المنتج']);
        exit;

    } else {
        echo json_encode(['success' => false, 'message' => 'عملية غير صحيحة']);
        exit;
    }

} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
    exit;
}