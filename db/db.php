<?php
date_default_timezone_set('Africa/Tripoli');
$host = "sql112.infinityfree.com";          // السيرفر الخارجي
$user = "if0_40673713";                     // اسم المستخدم
$password = "QcYMvOdGrq";                   // كلمة المرور
$dbname = "if0_40673713_mydb";             // اسم قاعدة البيانات

try {
    // إنشاء اتصال PDO مع دعم الترميز utf8mb4
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    
    // ضبط وضع الأخطاء
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // تأكد من أن الاتصال يستخدم UTF-8 للبيانات
    $conn->exec("SET NAMES 'utf8mb4'");
    // ضبط توقيت MySQL لتوقيت ليبيا
$conn->exec("SET time_zone = '+02:00'");
} catch(PDOException $e) {
    // رسالة خطأ واضحة إذا فشل الاتصال
    echo "<p>فشل الاتصال بقاعدة البيانات: " . $e->getMessage() . "</p>";
    exit();
}
?>