<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الإدارة</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="admin-wrapper">

    <h1 class="admin-title">لوحة إدارة حلويات العالم</h1>
    <p class="admin-welcome">أهلاً، <?= $_SESSION['user']['full_name']; ?> 👋</p>

    <div class="admin-grid">

        <a href="admin.php" class="admin-card">
            <i class="fa fa-box-open"></i>
            <h2>إدارة المنتجات</h2>
            <p>إضافة – تعديل – حذف</p>
        </a>

        <a href="admin_orders.php" class="admin-card">
            <i class="fa fa-receipt"></i>
            <h2>إدارة الطلبات</h2>
            <p>عرض – تحديث حالة – تفاصيل</p>
        </a>

        <!-- زر إدارة الحجوزات الجديد -->
        <a href="reservations.php" class="admin-card reservation-btn">
            <i class="fa fa-calendar-check"></i>
            <h2>إدارة الحجوزات</h2>
            <p>متابعة المواعيد – دفع العربون</p>
        </a>
        
        <a href="../admin/user/manage_wallet.php" class="admin-card">
            <i class="fa fa-wallet"></i>
            <h2>إدارة المحفظة</h2>
            <p>شحن – خصم – متابعة الأرصدة</p>
        </a>

        <a href="manage_users.php" class="admin-card">
            <i class="fa fa-users"></i>
            <h2>إدارة المستخدمين</h2>
            <p>عرض – تعديل – حذف</p>
        </a>

        <a href="admin_sales.php" class="admin-card">
            <i class="fa fa-chart-line"></i>
            <h2>التقارير</h2>
            <p>مبيعات – منتجات – شحن</p>
        </a>

    </div>

</div>

</body>
</html>
