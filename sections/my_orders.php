<?php
session_start();
include "../db/db.php";

if(!isset($_SESSION['user'])){
  header("Location: ../admin/auth/login.php");
  exit;
}

$userId = $_SESSION['user']['id'];

try {
  $stmt = $conn->prepare("
    SELECT order_id, total_price, status, order_date
    FROM orders
    WHERE user_id = ?
    ORDER BY order_id DESC
  ");
  $stmt->execute([$userId]);
  $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e){
  echo "خطأ في جلب الطلبات: " . $e->getMessage();
  $orders = [];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>طلباتي - حلويات العالم</title>

<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/hh.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Amiri&display=swap" rel="stylesheet">
</head>
<body>


<header class="header">
    
    <!-- زر الرجوع -->
    <div class="back-btn" onclick="history.back()">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
  <circle cx="12" cy="12" r="10"></circle>
  <polyline points="13 16 9 12 13 8"></polyline>
</svg>
    </div>

    <div class="app-bar-title-pages">
    حلويات العالم
    <span>ELALEEM SWEETS</span>
</div>

    <!-- الشريط -->
    <nav class="navbar">
        <ul>

                       <li><a href="../index.php"><i class="fa-solid fa-house"></i></a></li>
            <li><a href="../admin/user/wallet.php"><i class="fa-solid fa-wallet"></i></a></li>
            <li><a href="../index.php#sections"><i class="fa-solid fa-layer-group"></i></a></li>
            <li><a href="my_orders.php"><i class="fa-solid fa-box"></i></a></li>

            <!-- السلة -->
            <li class="cart">
                <a href="../sections/cart.php">
                    <span id="cartCount">0</span>
         <svg width="24" height="24" viewBox="0 0 24 24" fill="#047a95">
  <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 
  0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.2 
  6l-.94-2H2v2h2l3.6 7.59-1.35 2.44C5.52 17.37 6.48 
  19 8 19h12v-2H8l1.1-2h7.45c.75 0 1.41-.41 
  1.75-1.03L21 9H5.21z"/>
</svg>
                </a>
            </li>

        </ul>
    </nav>

</header>
<!-- =======================
     الطلبات
======================= -->
<div class="orders-container">

<?php if(!empty($orders)): ?>

    <?php foreach($orders as $o): ?>
        <div class="order-card-modern">

            <!-- رقم الطلب + التاريخ -->
            <div class="order-row-top">
                <span class="order-id">طلب #<?= $o['order_id']; ?></span>
                <span class="order-date"><?= $o['order_date']; ?></span>
            </div>

            <!-- الإجمالي + الحالة (بدون اختفاء) -->
            <div class="order-row-mid">
                <span class="order-total"><?= number_format($o['total_price'], 2); ?> د.ل</span>

                <span id="status_<?= $o['order_id']; ?>" class="order-status">
                    <span class="state <?= $o['status']; ?>">
                        <?php
                        $map = [
                            'preparing'=>'جاري التحضير',
                            'ready'=>'جاهز',
                            'delivering'=>'جاري التوصيل',
                            'delivered'=>'تم التوصيل',
                            'reject'=>'مرفوض'
                        ];
                        echo $map[$o['status']] ?? $o['status'];
                        ?>
                    </span>
                </span>
            </div>

            <a href="order_status.php?id=<?= $o['order_id']; ?>" class="order-details-btn">
                عرض التفاصيل
            </a>

        </div>
    <?php endforeach; ?>

<?php else: ?>
    <p class="no-orders">لا توجد طلبات بعد 🛍️</p>
<?php endif; ?>

</div>


<!-- =======================
     تحديث تلقائي بدون اختفاء
======================= -->
<script src="../js/update.js"></script>

<script>
autoUpdate('../updates.php?type=orders_list', function(data){

    data.data.forEach(row => {

        let wrapper = document.getElementById("status_" + row.order_id);

        if(wrapper){

            let state = wrapper.querySelector(".state");

            // تحديث النص العربي
            let map = {
                preparing: "جاري التحضير",
                ready: "جاهز",
                delivering: "جاري التوصيل",
                delivered: "تم التوصيل",
                reject: "مرفوض"
            };
            state.innerText = map[row.status] ?? row.status;

            // إزالة الألوان القديمة
            state.classList.remove(
                "preparing","ready","delivering","delivered","reject"
            );

            // إضافة اللون الجديد
            state.classList.add(row.status);
        }
    });

});
</script>

<script>
let lastScroll = 0;

window.addEventListener("scroll", () => {
    const header = document.querySelector(".header");
    const currentScroll = window.pageYOffset;

    if (currentScroll > lastScroll && currentScroll > 60) {
        // المستخدم ينزل
        header.classList.add("hide");
    } else {
        // المستخدم يطلع
        header.classList.remove("hide");
    }

    lastScroll = currentScroll;
});
</script>>

</body>
</html>
