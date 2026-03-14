<?php
session_start();
include "../../db/db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../PHPMailer/src/Exception.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';

if(!isset($_SESSION['reset_email'])){
    header("Location: forgot_password.php");
    exit;
}

$email = $_SESSION['reset_email'];
$reset_method = $_SESSION['reset_method'] ?? 'email'; // 🆕
$message = "";

if($_SERVER['REQUEST_METHOD'] == "POST"){
    
    // 🆕 التحقق من الكود
    if(isset($_POST['verify'])){
        $code = trim($_POST['code']);

        $stmt = $conn->prepare("SELECT reset_code, reset_expire FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$user){
            $message = "المستخدم غير موجود.";
        }
        elseif(new DateTime() > new DateTime($user['reset_expire'])){
            $message = "انتهت صلاحية الكود، أرسل كود جديد.";
        }
        elseif($code != $user['reset_code']){
            $message = "الكود غير صحيح.";
        }
        else {
            // نجاح → السماح بتغيير كلمة السر
            $_SESSION['allow_reset'] = true;

            header("Location: new_password.php");
            exit;
        }
    }

    // 🆕 إعادة إرسال الكود
    if(isset($_POST['resend'])){
        $code = rand(100000, 999999);
        $expire = date("Y-m-d H:i:s", time() + 10*60);
        
        // الحصول على رقم الهاتف
        $stmt = $conn->prepare("SELECT phone FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $conn->prepare("UPDATE users SET reset_code=?, reset_expire=? WHERE email=?");
        $stmt->execute([$code, $expire, $email]);

        try {
            if($reset_method == 'email'){
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
                $mail->Subject = "كود إعادة تعيين جديد";
                $mail->Body = "<p>رمز إعادة التعيين الجديد:<h2>$code</h2><p>ينتهي بعد 10 دقائق.</p>";
                $mail->send();

                $message = "✅ تم إرسال كود جديد إلى بريدك الإلكتروني.";

            } elseif($reset_method == 'sms'){
                sendResetCodeViaSMS($user['phone'], $code);
                $message = "✅ تم إرسال كود جديد إلى هاتفك.";
            }
        } catch (Exception $e){
            $message = "فشل إعادة الإرسال: " . $e->getMessage();
        }
    }
}

// 🆕 دالة إرسال SMS
function sendResetCodeViaSMS($phone, $code){
    $api_url = "https://api.sms-provider.com/send";
    $api_key = "YOUR_API_KEY";
    
    $message = "رمز إعادة تعيين كلمة المرور: $code\nحلويات العالم";
    
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>تأكيد الكود</title>
    <link rel="stylesheet" href="../admin_style.css">
</head>
<body>

<div class="auth-container">
    <h1>تأكيد الكود</h1>

    <p>تم إرسال الكود إلى: <strong><?php echo $reset_method == 'email' ? htmlspecialchars($email) : 'هاتفك'; ?></strong></p>

    <?php if($message): ?>
        <p style="color:green;text-align:center;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST" class="auth-form">
        <label>أدخل الكود المرسل:</label>
        <input type="text" name="code" required placeholder="123456">

        <button type="submit" name="verify" class="auth-btn">تأكيد</button>
        <button type="submit" name="resend" style="background:#38bdf8;">إعادة إرسال الكود</button>
    </form>
</div>

</body>
</html>
