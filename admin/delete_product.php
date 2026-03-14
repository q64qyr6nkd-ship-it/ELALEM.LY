<?php
include "../db/db.php";

// التأكد من وجود معرف المنتج في الرابط
if(isset($_GET['id'])){
    $id = $_GET['id'];

    // أولاً، الحصول على اسم الصورة لحذفها من المجلد
    $stmt = $conn->prepare("SELECT image FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if($product){
        // حذف الصورة من المجلد
        $imagePath = "../images/" . $product['image'];
        if(file_exists($imagePath)){
            unlink($imagePath);
        }

        // حذف المنتج من قاعدة البيانات
        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->execute([$id]);
    }

    // إعادة التوجيه للوحة الإدارة
    header("Location: admin.php");
    exit();
} else {
    die("المنتج غير موجود");
}
?>