<?php 
include "check_access.php";
requirePermission("manage_products");

include "../db/db.php";

// البحث
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// جلب الأقسام
try {
    $stmt = $conn->prepare("SELECT DISTINCT category FROM products ORDER BY category");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    echo "<p>خطأ في جلب الأقسام: " . $e->getMessage() . "</p>";
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>لوحة إدارة حلويات العالم</title>
<link rel="stylesheet" href="admin_style.css">
</head>
<body>
<a href="admin_dashboard.php" class="back-btn" title="الرجوع للوحة التحكم"></a>
<div class="container">

<h1>لوحة إدارة حلويات العالم</h1>

<div class="top-bar">
    
    <a href="add_product.php" class="add-button">إضافة منتج جديد</a>

    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="ابحث عن منتج..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">بحث</button>
    </form>

</div>

<?php if(!empty($categories)): ?>
    <?php foreach($categories as $category): ?>

        <?php
        if($search !== '') {
            $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND name LIKE ?");
            $stmt->execute([$category, "%$search%"]);
        } else {
            $stmt = $conn->prepare("SELECT * FROM products WHERE category = ?");
            $stmt->execute([$category]);
        }
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="category-section">
            <h2><?php echo htmlspecialchars($category); ?></h2>

            <?php if(!empty($products)): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>الصورة</th>
                            <th>الاسم</th>
                            <th>السعر</th>
                            <th>القسم</th>
                            <th>الوصف</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach($products as $product): ?>
                        <tr>
                            <td><img src="../images/<?php echo $product['image']; ?>" width="60"></td>
                            <td><?php echo $product['name']; ?></td>
                            <td><?php echo $product['price']; ?> د.ل</td>
                            <td><?php echo $product['category']; ?></td>
                            <td><?php echo $product['description']; ?></td>

                            <td>
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>">تعديل</a> |

                                <?php if($product['available'] == 1): ?>
                                    <button 
                                        class="toggle-btn" 
                                        data-id="<?php echo $product['id']; ?>" 
                                        data-action="hide"
                                        style="color:red; cursor:pointer;">
                                        إخفاء
                                    </button> |
                                <?php else: ?>
                                    <button 
                                        class="toggle-btn" 
                                        data-id="<?php echo $product['id']; ?>" 
                                        data-action="show"
                                        style="color:green; cursor:pointer;">
                                        إظهار
                                    </button> |
                                <?php endif; ?>

                                <a href="delete_product.php?id=<?php echo $product['id']; ?>" onclick="return confirm('هل تريد حذف هذا المنتج؟');">حذف</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            <?php else: ?>
                <p>لا توجد منتجات في هذا القسم.</p>
                <?php endif; ?>
        </div>

    <?php endforeach; ?>

<?php else: ?>
    <p>لا توجد أقسام</p>
<?php endif; ?>


<!-- جدول المنتجات المخفية -->
<h2 style="margin-top:40px;">المنتجات المخفية</h2>

<?php
try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE available = 0 ORDER BY category, id DESC");
    $stmt->execute();
    $hiddenProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "خطأ: " . $e->getMessage();
}
?>

<?php if(!empty($hiddenProducts)): ?>
<table class="admin-table">
    <thead>
        <tr>
            <th>صورة</th>
            <th>الاسم</th>
            <th>السعر</th>
            <th>القسم</th>
            <th>الوصف</th>
            <th>إجراء</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach($hiddenProducts as $product): ?>
        <tr>
            <td><img src="../images/<?php echo $product['image']; ?>" width="60"></td>
            <td><?php echo $product['name']; ?></td>
            <td><?php echo $product['price']; ?></td>
            <td><?php echo $product['category']; ?></td>
            <td><?php echo $product['description']; ?></td>

            <td>
                <button 
                    class="toggle-btn" 
                    data-id="<?php echo $product['id']; ?>" 
                    data-action="show"
                    style="color:green; cursor:pointer;">
                    إظهار
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php else: ?>
<p>لا توجد منتجات مخفية.</p>
<?php endif; ?>


</div>

<script>
document.querySelectorAll('.toggle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        let id = btn.dataset.id;
        let action = btn.dataset.action;

        fetch('toggle_product_api.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'id='+id+'&action='+action
        })
        .then(r => r.json())
        .then(data => {
            if(data.success){
                location.reload();
            } else {
                alert("خطأ: " + data.message);
            }
        });
    });
});


document.querySelectorAll('.toggle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        let id = btn.dataset.id;
        let action = btn.dataset.action;

        fetch('toggle_product_api.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'id='+id+'&action='+action
        })
        .then(r => r.json())
        .then(data => {
            if(data.success){
                location.reload();
            } else {
                alert("خطأ: " + data.message);
            }
        });
    });
});
</script>

</body>
</html>