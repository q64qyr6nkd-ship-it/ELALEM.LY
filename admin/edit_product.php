<?php
include "../db/db.php"; // الاتصال بقاعدة البيانات

if (!isset($_GET['id'])) {
    die("المنتج غير محدد!");
}

$id = $_GET['id'];

// جلب بيانات المنتج من قاعدة البيانات
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("المنتج غير موجود!");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تعديل المنتج - لوحة الإدارة</title>
<link rel="stylesheet" href="admin_style.css">
</head>
<body>

<div class="admin-form">
    <h1>تعديل المنتج</h1>

    <form action="update_product.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">

        <label>اسم المنتج:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>

        <label>السعر:</label>
        <input type="text" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>

        <label>القسم:</label>
        <select name="category" required>
            <option value="eastern"  <?php if($product['category']=='eastern') echo 'selected'; ?>>الحلويات الشرقية</option>
            <option value="moroccan" <?php if($product['category']=='moroccan') echo 'selected'; ?>>الحلويات المغربية</option>
            <option value="nuts"     <?php if($product['category']=='nuts') echo 'selected'; ?>>اللوزيات</option>
            <option value="chocolate"<?php if($product['category']=='chocolate') echo 'selected'; ?>>الشكلاطة</option>
            <option value="cakes"    <?php if($product['category']=='cakes') echo 'selected'; ?>>الكيكات</option>
            <option value="juices"   <?php if($product['category']=='juices') echo 'selected'; ?>>العصائر الطبيعية</option>
            <option value="tort"     <?php if($product['category']=='tort') echo 'selected'; ?>>التورتات</option>
        </select>

        <label>الوصف:</label>
        <textarea name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>

        <label>الصورة الحالية:</label>
        <img class="current-image" src="../images/<?php echo $product['image']; ?>" width="150">

        <label>تغيير الصورة:</label>
        <input type="file" name="image">

        <label>
            <input type="checkbox" name="is_featured"
                <?php echo ($product['is_featured'] == 1 ? 'checked' : ''); ?>>
            منتج مميز ⭐️
        </label>

        <label>
            <input type="checkbox" name="is_best_seller"
                <?php echo ($product['is_best_seller'] == 1 ? 'checked' : ''); ?>>
            من الأكثر مبيعاً 🔥
        </label>

        <button type="submit">حفظ التعديلات</button>
    </form>
</div>


</body>
</html>