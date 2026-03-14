<?php
session_start();
include "../../db/db.php";

$error = "";
$phone = "";

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($phone === '' || $password === '') {
        $error = "الرجاء إدخال رقم الهاتف وكلمة المرور";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
            $stmt->execute([$phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if($user && password_verify($password, $user['password'])){
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'phone' => $user['phone'],
                    'role' => $user['role'],
                    'permissions' => is_array(json_decode($user['permissions'] ?? '', true)) ? json_decode($user['permissions'], true) : []
                ];
                header("Location: ../../index.php");
                exit();
            } else {
                $error = "رقم الهاتف أو كلمة المرور غير صحيحة";
            }
        } catch(PDOException $e){
            $error = "حدث خطأ في النظام، حاول لاحقاً";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - حلويات العالم</title>
    <link rel="stylesheet" href="auth_style.css">
</head>
<body class="sweet-theme-body">
    <div class="main-auth-container">
        <div class="auth-glass-card">
            <div class="brand-section">
                <img src="assets/logo.png" alt="حلويات العالم" class="brand-logo">
                <h1 class="auth-title">مرحباً بك</h1>
                <p class="auth-subtitle">سجل دخولك لتكمل رحلتك مع مذاقنا الخاص</p>
            </div>

            <?php if(!empty($error)): ?>
                <div class="alert-box-error">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-custom-form">
                <div class="input-field-group">
                    <label>رقم الهاتف</label>
                    <input type="text" name="phone" placeholder="09XXXXXXXX" required value="<?php echo htmlspecialchars($phone) ?>">
                </div>

                <div class="input-field-group">
                    <label>كلمة المرور</label>
                    <input type="password" name="password" placeholder="أدخل كلمة المرور" required>
                </div>

                <button type="submit" class="submit-action-btn">دخول</button>
            </form>

            <div class="auth-links-footer">
                <p>ليس لديك حساب؟ <a href="register.php">سجل الآن</a></p>
            </div>
        </div>
    </div>
</body>
</html>