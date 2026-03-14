<?php
include "../db/db.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $id          = $_POST['id'];
    $name        = $_POST['name'];
    $price       = $_POST['price'];
    $category    = $_POST['category'];
    $description = $_POST['description'];

    // الحقول الجديدة
    $is_featured    = isset($_POST['is_featured']) ? 1 : 0;
    $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;

    // تحقق إذا تم رفع صورة جديدة
    if (isset($_FILES['image']) && $_FILES['image']['name'] != "") {
        $imageName = time() . "_" . $_FILES['image']['name'];
        $target    = "../images/" . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            // تحديث مع تغيير الصورة
            $stmt = $conn->prepare("
                UPDATE products 
                SET name = ?, price = ?, category = ?, description = ?, image = ?, 
                    is_featured = ?, is_best_seller = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name,
                $price,
                $category,
                $description,
                $imageName,
                $is_featured,
                $is_best_seller,
                $id
            ]);
        }
    } else {
        // تحديث بدون تغيير الصورة
        $stmt = $conn->prepare("
            UPDATE products 
            SET name = ?, price = ?, category = ?, description = ?, 
                is_featured = ?, is_best_seller = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $name,
            $price,
            $category,
            $description,
            $is_featured,
            $is_best_seller,
            $id
        ]);
    }

    // إعادة التوجيه للوحة الإدارة
    header("Location: admin.php");
    exit();
} else {
    die("الوصول غير مسموح");
}
?>