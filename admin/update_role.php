<?php
session_start();
include "check_access.php";
requirePermission("manage_users"); // فقط من عنده هذه الصلاحية

include "../db/db.php";

if($_SERVER['REQUEST_METHOD'] !== "POST"){
    header("Location: manage_users.php");
    exit;
}

$userId = intval($_POST['user_id'] ?? 0);
$newRole = $_POST['role'] ?? '';

if($userId <= 0 || !in_array($newRole, ["admin", "customer"])){
    header("Location: manage_users.php?error=invalid_role");
    exit;
}

$stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
$stmt->execute([$newRole, $userId]);

header("Location: manage_users.php?role_updated=1");
exit;