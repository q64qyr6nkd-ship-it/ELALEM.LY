<?php
session_start();
include "../../db/db.php";

$successMsg = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_success']);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../PHPMailer/src/Exception.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';

if(!isset($_SESSION['pending_email'])){
    header("Location: register.php");
    exit;
}

$email = $_SESSION['pending_email'];
$otp_method = $_SESSION['otp_method'] ?? 'email'; // 🆕
$message = "";

if($_SERVER['REQUEST_METHOD'] == "POST"){
    
   if(isset($_POST['verify'])){
    $otp = trim($_POST['otp'] ?? '');

    if($otp == ''){
        $message = "⚠️ الرجاء إدخال رمز التحقق.";
    } else {
        $stmt = $conn->prepare("SELECT id, phone, otp_code, otp_expires FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$user){
            $message = "⚠️ المستخدم غير موجود.";
        } elseif(new DateTime() > new DateTime($user['otp_expires'])){
            $message = "⏰ انتهت صلاحية الرمز، أعد الإرسال.";
        } elseif($otp != $user['otp_code']){
            $message = "❌ رمز التحقق غير صحيح.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);

            unset($_SESSION['pending_email']);
            unset($_SESSION['otp_method']);
            header("Location: login.php");
            exit;
        }
    }
}

    if(isset($_POST['resend'])){
        $otp = rand(100000, 999999);
        $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));
        
        // الحصول على رقم الهاتف
        $stmt = $conn->prepare("SELECT phone FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE email = ?");
        $stmt->execute([$otp, $expires, $email]);

        // 🆕 إعادة الإرسال حسب الطريقة
        if($otp_method == 'email'){
            $mail = new PHPMailer(true);
            $mail->CharSet = "UTF-8";
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'elaleemsweets@gmail.com';
            $mail->Password = 'wnbm csrw ittp adwh';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->setFrom('elaleemsweets@gmail.com', 'حلويات العالم');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "رمز تحقق جديد";
            $mail->Body = "<p>رمز التحقق الجديد هو:<h2>$otp</h2><p>ينتهي بعد 10 دقائق.</p>";
            $mail->send();
            $message = "✅ تم إرسال رمز جديد إلى بريدك الإلكتروني.";
            
        } elseif($otp_method == 'sms'){
            sendOtpViaSMS($user['phone'], $otp);
            $message = "✅ تم إرسال رمز جديد إلى هاتفك.";
        }
    }
}

// 🆕 دالة إرسال SMS (نفس الدالة من register.php)
function sendOtpViaSMS($phone, $otp){
    $api_url = "https://api.sms-provider.com/send";
    $api_key = "YOUR_API_KEY";
    
    $message = "رمز التحقق الخاص بك: $otp\nحلويات العالم";
    
    $data = [
        'to' => $phone,
        'message' => $message,
        'api_key' => $api_key
    ];
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code == 200;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد البريد الإلكتروني</title>
    <link rel="stylesheet" href="../admin_style.css">
</head>
<body>
    <div class="auth-container">
        <h1>تأكيد رمز التحقق</h1>

        <?php if($successMsg): ?>
        <p class="success-msg"><?php echo htmlspecialchars($successMsg); ?></p>
        <?php endif; ?>

        <p>تم إرسال رمز تحقق إلى: <strong><?php echo $otp_method == 'email' ? htmlspecialchars($email) : 'هاتفك'; ?></strong></p>

        <?php if($message): ?>
        <p style="color:green;text-align:center;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>رمز التحقق:</label>
            <input type="text" name="otp" placeholder="أدخل الرمز هنا">
            <button type="submit" name="verify">تأكيد</button>
            <button type="submit" name="resend" style="background:#38bdf8;">إعادة إرسال!</button>
        </form>
    </div>
</body>
</html>
