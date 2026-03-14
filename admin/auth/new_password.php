<?php
session_start();
include "../../db/db.php";

if(!isset($_SESSION['reset_email'])){
    header("Location: forgot_password.php");
    exit;
}

$error = "";

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirm_password'];
    $email = $_SESSION['reset_email'];

    if($pass1 !== $pass2){
        $error = "كلمتا المرور غير متطابقتين!";
    } else {
        $hash = password_hash($pass1, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password=?, reset_code=NULL, reset_expire=NULL WHERE email=?");
        $stmt->execute([$hash, $email]);

        unset($_SESSION['reset_email']);

        header("Location: login.php?reset=done");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta charset="UTF-8">
<title>كلمة مرور جديدة</title>
<link rel="stylesheet" href="../admin_style.css">
</head>
<body>

<div class="auth-container">
    <h1>إنشاء كلمة مرور جديدة</h1>

    <?php if($error): ?>
        <p class="error-msg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" class="auth-form">
        <label>كلمة مرور جديدة:</label>
        <input type="password" name="password" required>

        <label>تأكيد كلمة المرور:</label>
        <input type="password" name="confirm_password" required>

        <button type="submit" class="auth-btn">حفظ الكلمة الجديدة</button>
    </form>

</div>

</body>
</html>
