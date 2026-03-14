<?php
include "../db/db.php";

$message = ""; // متغير لعرض الرسائل للمستخدم

if (isset($_POST['submit'])) {
    // 1. تنظيف البيانات المدخلة (Security Sanitization)
    $name        = htmlspecialchars(trim($_POST['name']));
    $price       = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $category    = htmlspecialchars($_POST['category']);
    $description = htmlspecialchars(trim($_POST['description']));

    // 2. معالجة ملف الصورة
    $image_file = $_FILES['image'];
    $image_name = $image_file['name'];
    $tmp_name   = $image_file['tmp_name'];
    $image_size = $image_file['size'];
    $error      = $image_file['error'];

    // الحصول على امتداد الملف والتحقق منه
    $img_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
    $allowed_exts = array("jpg", "jpeg", "png", "webp");

    if (in_array($img_ext, $allowed_exts)) {
        if ($error === 0) {
            // التحقق من حجم الملف (اختياري: مثلاً نمنع الملفات الأكبر من 5 ميجابايت قبل الضغط)
            if ($image_size <= 5000000) {
                
                // توليد اسم فريد تماماً للصورة لمنع التكرار
                $new_img_name = uniqid("IMG-", true) . '.' . $img_ext;
                $target_path = "../images/" . $new_img_name;

                // --- عملية ضغط الصورة وتحسين الأداء (Optimization) ---
                // نقوم بإنشاء مورد للصورة بناءً على نوعها ثم حفظها بضغط معين
                if ($img_ext == 'jpg' || $img_ext == 'jpeg') {
                    $source_img = imagecreatefromjpeg($tmp_name);
                    imagejpeg($source_img, $target_path, 75); // ضغط بجودة 75%
                } elseif ($img_ext == 'png') {
                    $source_img = imagecreatefrompng($tmp_name);
                    // ضغط PNG (مستوى 6) مع الحفاظ على الشفافية
                    imagealphablending($source_img, false);
                    imagesavealpha($source_img, true);
                    imagepng($source_img, $target_path, 6);
                } elseif ($img_ext == 'webp') {
                    $source_img = imagecreatefromwebp($tmp_name);
                    imagewebp($source_img, $target_path, 75);
                }

                // حذف المورد من الذاكرة لتوفير موارد السيرفر
                imagedestroy($source_img);

                // 3. حفظ البيانات في قاعدة البيانات باستخدام Prepared Statements
                try {
                    $stmt = $conn->prepare("INSERT INTO products (name, price, description, category, image) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $price, $description, $category, $new_img_name]);
                    $message = "<p class='success'>✅ تم إضافة المنتج بنجاح!</p>";
                } catch (Exception $e) {
                    $message = "<p class='error'>❌ حدث خطأ في قاعدة البيانات: " . $e->getMessage() . "</p>";
                }

            } else {
                $message = "<p class='error'>⚠️ حجم الصورة كبير جداً، يرجى اختيار صورة أقل من 5MB.</p>";
            }
        } else {
            $message = "<p class='error'>⚠️ حدث خطأ أثناء رفع الصورة.</p>";
        }
    } else {
        $message = "<p class='error'>⚠️ عذراً، يسمح فقط برفع الصور من نوع (JPG, PNG, WEBP).</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة منتج جديد - لوحة الإدارة</title>
    <link rel="stylesheet" href="admin_style.css">
   
</head>
<body>

<div class="admin-form">
    <h1>إضافة منتج جديد</h1>

    <!-- عرض رسائل الحالة -->
    <?php echo $message; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <label>اسم المنتج:</label>
        <input type="text" name="name" placeholder="مثلاً: كعك باللوز" required>

        <label>السعر:</label>
        <input type="number" step="0.01" name="price" placeholder="0.00" required>

        <label>القسم:</label>
        <select name="category" required>
            <option value="">-- اختر القسم --</option>
            <option value="eastern">الحلويات الشرقية</option>
            <option value="moroccan">الحلويات المغربية</option>
            <option value="nuts">اللوزيات</option>
            <option value="chocolate">الشكلاطة</option>
            <option value="cakes">الكيكات</option>
            <option value="juices">العصائر الطبيعية</option>
            <option value="tort">التورتات</option>
        </select>

        <label>الوصف:</label>
        <textarea name="description" rows="4" placeholder="اكتب وصفاً جذاباً للمنتج..." required></textarea>

        <label>صورة المنتج (JPG, PNG, WEBP):</label>
        <input type="file" name="image" accept="image/*" required>

        <button type="submit" name="submit">إضافة المنتج</button>
    </form>

    <div style="margin-top: 20px; text-align: center;">
        <a href="admin.php" class="back-button">⬅️ رجوع للوحة الإدارة</a>
    </div>
</div>

</body>
</html>