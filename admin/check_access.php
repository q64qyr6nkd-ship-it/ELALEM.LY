<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requirePermission($requiredPermission) {

    // التحقق من تسجيل الدخول
    if (!isset($_SESSION['user'])) {
        echo "<h3 style='color:red;text-align:center'>❌ يجب تسجيل الدخول</h3>";
        exit;
    }

    // التحقق إن الصلاحيات موجودة
    if (!isset($_SESSION['user']['permissions']) || empty($_SESSION['user']['permissions'])) {
        echo "<h3 style='color:red;text-align:center'>❌ ليس لديك صلاحية لدخول هذه الصفحة</h3>";
        exit;
    }

    // فك JSON إذا كانت الصلاحيات نص
    $permissions = $_SESSION['user']['permissions'];
    if (is_string($permissions)) {
        $permissions = json_decode($permissions, true) ?? [];
    }

    // التحقق من الصلاحية المطلوبة
    if (!in_array($requiredPermission, $permissions)) {
        echo "<h3 style='color:red;text-align:center'>❌ ليس لديك صلاحية لدخول هذه الصفحة</h3>";
        exit;
    }
}
?>