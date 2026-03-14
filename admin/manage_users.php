
<?php
session_start();
include "check_access.php";
requirePermission("manage_users"); // فقط من عنده هذه الصلاحية

include "../db/db.php";

$search = $_GET['phone'] ?? "";

if($search != ""){
    // البحث برقم الهاتف لأي مستخدم (حتى لو كان عادي)
    $stmt = $conn->prepare("SELECT * FROM users WHERE phone LIKE ?");
    $stmt->execute(["%$search%"]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // بدون بحث: نعرض فقط الأدمن أو من لديه أي صلاحيات
    $users = $conn->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    // فلترة المستخدمين: فقط الأدمن أو من لديه أي صلاحيات
    $users = array_filter($users, function($u){
        $perms = json_decode($u['permissions'], true);
        return $u['role'] === 'admin' || ($perms && count($perms) > 0);
    });
}

// فصل المستخدمين أصحاب الصلاحيات عن بدون صلاحيات
$hasPermissions = [];
$noPermissions = [];

foreach($users as $u){
    $perms = json_decode($u['permissions'], true);
    if($perms && count($perms) > 0){
        $hasPermissions[] = $u;
    } else {
        $noPermissions[] = $u;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة المستخدمين</title>
<link rel="stylesheet" href="admin_style.css">
<style>
/* تحسين تنسيق checkboxes */
.perm-box {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.perm-item {
    display: flex;
    align-items: center;
    gap: 5px;
}
.save-btn {
    margin-top: 10px;
    padding: 5px 10px;
}
.role-btn.promote { background: #2ecc71; color: #fff; }
.role-btn.demote { background: #e74c3c; color: #fff; }
</style>
</head>

<body>

<a href="admin_dashboard.php" class="back-btn" title="الرجوع للوحة التحكم"></a>    

<h1 class="page-title">إدارة المستخدمين</h1>

<form method="GET" class="admin-search">
    <input type="text" name="phone" placeholder="ابحث برقم الهاتف..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">بحث</button>
</form>

<?php if(isset($_GET['success'])): ?>
    <p class="success-msg">✔️ تم تحديث الصلاحيات بنجاح</p>
<?php endif; ?>
<?php if(isset($_GET['role_updated'])): ?>
    <p class="success-msg">✔️ تم تعديل دور المستخدم</p>
<?php endif; ?>

<h2 class="section-title">المستخدمون أصحاب الصلاحيات</h2>

<?php if(count($hasPermissions) > 0): ?>
<table class="users-table">
<tr>
    <th>الاسم</th>
    <th>الهاتف</th>
    <th>الدور</th>
    <th>الصلاحيات</th>
</tr>

<?php foreach($hasPermissions as $u): ?>
<tr>
    <td><?= htmlspecialchars($u['full_name']); ?></td>
    <td><?= htmlspecialchars($u['phone']); ?></td>

    <td>
        <?= ($u['role']==='admin') ? "أدمن" : "مستخدم"; ?>
        <form method="POST" action="update_role.php">
            <input type="hidden" name="user_id" value="<?= $u['id']; ?>">
            <input type="hidden" name="role" value="<?= ($u['role']==='admin')?'customer':'admin'; ?>">
            <button class="role-btn <?= ($u['role']==='admin')?'demote':'promote'; ?>">
                <?= ($u['role']==='admin') ? "إرجاع لمستخدم" : "ترقية لأدمن"; ?>
            </button>
        </form>
    </td>

    <td>
        <?php $current = json_decode($u['permissions'], true) ?? []; ?>
        <form method="POST" action="update_permissions.php" class="perm-box">
            <input type="hidden" name="user_id" value="<?= $u['id']; ?>">

            <?php
            $allPermissions = [
                "manage_products" => "المنتجات",
                "manage_orders" => "الطلبات",
                "manage_wallet" => "المحفظة",
                "manage_reports" => "التقارير",
                "manage_users" => "المستخدمين",
                "manage_reservations" => "الحجوزات"
            ];
            foreach($allPermissions as $key => $label):
            ?>
            <label class="perm-item">
                <input type="checkbox" name="permissions[]" value="<?= $key ?>" <?= in_array($key,$current) ? "checked" : "" ?>>
                <span><?= $label ?></span>
            </label>
            <?php endforeach; ?>

            <br><button type="submit" class="save-btn">حفظ</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p class="empty-msg">لا يوجد مستخدمون لديهم صلاحيات.</p>
<?php endif; ?>

<h2 class="section-title">مستخدمون بدون صلاحيات</h2>

<?php if(count($noPermissions) > 0): ?>
<table class="users-table">
<tr>
    <th>الاسم</th>
    <th>الهاتف</th><th>الدور</th>
    <th>إضافة صلاحيات</th>
</tr>

<?php foreach($noPermissions as $u): ?>
<tr>
    <td><?= htmlspecialchars($u['full_name']); ?></td>
    <td><?= htmlspecialchars($u['phone']); ?></td>

    <td>
        <?= ($u['role']==='admin') ? "أدمن" : "مستخدم"; ?>
        <form method="POST" action="update_role.php">
            <input type="hidden" name="user_id" value="<?= $u['id']; ?>">
            <input type="hidden" name="role" value="<?= ($u['role']==='admin')?'customer':'admin'; ?>">
            <button class="role-btn <?= ($u['role']==='admin')?'demote':'promote'; ?>">
                <?= ($u['role']==='admin') ? "إرجاع لمستخدم" : "ترقية لأدمن"; ?>
            </button>
        </form>
    </td>

    <td>
        <form method="POST" action="update_permissions.php" class="perm-box">
            <input type="hidden" name="user_id" value="<?= $u['id']; ?>">

            <?php foreach($allPermissions as $key => $label): ?>
            <label class="perm-item">
                <input type="checkbox" name="permissions[]" value="<?= $key ?>">
                <span><?= $label ?></span>
            </label>
            <?php endforeach; ?>

            <br><button type="submit" class="save-btn">حفظ</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p class="empty-msg">لا يوجد مستخدمون بدون صلاحيات.</p>
<?php endif; ?>

</body>
</html>