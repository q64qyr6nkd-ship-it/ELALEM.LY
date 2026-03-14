<?php
session_start();
include "../db/db.php";
include "check_access.php";
requirePermission("manage_users"); // فقط من عنده هذه الصلاحية

if($_SERVER['REQUEST_METHOD'] !== "POST"){
    header("Location: manage_users.php");
    exit;
}

$userId = intval($_POST['user_id'] ?? 0);
$permissions = $_POST['permissions'] ?? [];

if($userId <= 0){
    header("Location: manage_users.php?error=invalid_user");
    exit;
}

// تأكد أن permissions array
if(!is_array($permissions)){
    $permissions = [];
}

// قائمة الصلاحيات المسموح بها فقط
$allowedPermissions = [
    "manage_products",
    "manage_orders",
    "manage_wallet",
    "manage_reports",
    "manage_users",
    "manage_reservations"
];

$permissions = array_intersect($permissions, $allowedPermissions);
$json = json_encode(array_values($permissions), JSON_UNESCAPED_UNICODE);

$stmt = $conn->prepare("UPDATE users SET permissions=? WHERE id=?");
$stmt->execute([$json, $userId]);

// ⚡ تحديث الجلسة تلقائيًا
if(isset($_SESSION['user']) && $_SESSION['user']['id'] == $userId){
    $_SESSION['user']['permissions'] = $permissions;
}

header("Location: manage_users.php?success=1");
exit;
?>