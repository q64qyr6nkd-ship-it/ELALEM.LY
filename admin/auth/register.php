<?php
session_start();
include "../../db/db.php";

$errors = [];
$full_name = $phone = ""; // تعريف المتغيرات لتجنب الأخطاء في العرض

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $full_name = trim($_POST['full_name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if($full_name === '') $errors[] = "الاسم الكامل مطلوب";
    if($password === '') $errors[] = "كلمة المرور مطلوبة";
    if($phone === '') $errors[] = "رقم الهاتف مطلوب";
    
    if(!empty($password) && strlen($password) < 6) $errors[] = "كلمة المرور قصيرة جدًا (6 أحرف على الأقل)";

    if (!empty($phone)) {
        if (!preg_match('/^09[1234][0-9]{7}$/', $phone)) {
            $errors[] = "رقم الهاتف غير صحيح (091/2/3/4 ويتكون من 10 أرقام)";
        }
    }

    if(empty($errors)){
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        if($stmt->rowCount() > 0){
            $errors[] = "رقم الهاتف هذا مسجل لدينا بالفعل";
        }
    }

    if(empty($errors)){
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $conn->prepare("INSERT INTO users (full_name, phone, password, is_verified, role, created_at)
                                    VALUES (?, ?, ?, 1, 'user', NOW())");
            $stmt->execute([$full_name, $phone, $hashed_password]);
            $user_id = $conn->lastInsertId();
            
            $_SESSION['user'] = [
                'id' => $user_id,
                'full_name' => $full_name,
                'phone' => $phone,
                'role' => 'user',
                'permissions' => []
            ];
            header("Location: ../../index.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "حدث خطأ أثناء حفظ البيانات";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب - حلويات العالم</title>
    <link rel="stylesheet" href="auth_style.css">
</head>
<body class="sweet-theme-body">
    <div class="main-auth-container">
        <div class="auth-glass-card">
            <div class="brand-section">
                <img src="assets/logo.png" alt="حلويات العالم" class="brand-logo">
                <h1 class="auth-title">مرحباً بك</h1>
                <p class="auth-subtitle">أنشئ حسابك لتستمتع بأشهى الحلويات</p>
            </div>

            <?php if(!empty($errors)): ?>
                <div class="alert-box-error">
                    <?php foreach($errors as $err): ?>
                        <p>• <?php echo htmlspecialchars($err); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-custom-form">
                <div class="input-field-group">
                    <label>الاسم الكامل</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name) ?>" placeholder="أدخل اسمك الثلاثي" required>
                </div>

                <div class="input-field-group">
                    <label>رقم الهاتف</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($phone) ?>" placeholder="09XXXXXXXX" maxlength="10" required>
                </div>

                <div class="input-field-group">
                    <label>كلمة المرور</label>
                    <input type="password" name="password" placeholder="أدخل كلمة مرور قوية" required>
                </div>

                <button type="submit" class="submit-action-btn">إنشاء الحساب</button>
            </form>
            
            <div class="auth-links-footer">
                <p>لديك حساب بالفعل؟ <a href="login.php">دخول</a></p>
            </div>
        </div>
    </div>
</body>
</html>