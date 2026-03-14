<?php
session_start();
include "../../db/db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../PHPMailer/src/Exception.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';

$error = "";
$success = "";

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $email = trim($_POST['email']);

    // تحقق إن الإيميل موجود
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if($user){

        // إنشاء كود إعادة تعيين
        $code = rand(100000, 999999);
        $expire = date("Y-m-d H:i:s", time() + 10*60); // صالح لـ 10 دقائق

        // حفظ الكود في قاعدة البيانات
        $stmt = $conn->prepare("UPDATE users SET reset_code=?, reset_expire=? WHERE email=?");
        $stmt->execute([$code, $expire, $email]);

        // إرسال البريد
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = "UTF-8";
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'elaleemsweets@gmail.com';
            $mail->Password = 'wnbm csrw ittp adwh'; // app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('elaleemsweets@gmail.com', 'حلويات العالم');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "إعادة تعيين كلمة المرور";
            $mail->Body = "
                <h3>رمز إعادة تعيين كلمة المرور</h3>
                <p>رمزك هو:</p>
                <h2>$code</h2>
                <p>سينتهي في غضون 10 دقائق.</p>
            ";

            $mail->send();

            // تخزين الإيميل للجلسة
            $_SESSION['reset_email'] = $email;

            header("Location: verify_reset_code.php");
            exit;
        } catch (Exception $e){
            $error = "تعذر إرسال البريد. الرجاء المحاولة لاحقاً.";
        }

    } else {
        $error = "البريد الإلكتروني غير مسجل لدينا.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>نسيت كلمة المرور</title>
<link rel="stylesheet" href="../admin_style.css">
</head>
<body>

<div class="auth-container">
    <h1>إعادة تعيين كلمة المرور</h1>

    <?php if($error): ?>
        <p class="error-msg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" class="auth-form">
        <label>البريد الإلكتروني:</label>
        <input type="email" name="email" placeholder="example@gmail.com" required>

        <button type="submit" class="auth-btn">إرسال الكود</button>
    </form>

    <p><a href="login.php">العودة لتسجيل الدخول</a></p>
</div>

</body>
</html>