<?php
session_start();
include "../db/db.php";

if(!isset($_SESSION['user'])){
    header("Location: ../admin/auth/login.php");
    exit;
}

$userId = $_SESSION['user']['id'];

// جلب السلة من قاعدة البيانات
$stmt = $conn->prepare("
    SELECT c.id AS cart_id, p.id AS product_id, p.name, p.price, p.image, c.quantity
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($cart as $item){
    $total += $item["price"] * $item["quantity"];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Amiri&display=swap" rel="stylesheet">
<title>السلة</title>
<link rel="stylesheet" href="../css/hh.css">
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
            <li class="cart">
                <a href="../sections/cart.php">
                    <span id="cartCount">0</span>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#047a95">
                      <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 
                      2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 
                      2 2 2 2-.9 2-2-.9-2-2-2zM7.2 6l-.94-2H2v2h2l3.6 
                      7.59-1.35 2.44C5.52 17.37 6.48 19 8 19h12v-2H8l1.1-2h7.45c.75 
                      0 1.41-.41 1.75-1.03L21 9H5.21z"/>
                    </svg>
                </a>
            </li>
        </ul>
    </nav>

</header>

<section class="cart-section">
<h1>سلة المشتريات</h1>

<div id="cart-container">

<?php if (!empty($cart)): ?>
<table class="cart-table">
    <thead>
        <tr>
            <th>المنتج</th>
            <th>الكمية</th>
            <th>الحذف</th>
            

    </thead>

    <tbody>
    <?php foreach($cart as $item): ?>
      <tr data-id="<?= $item['cart_id'] ?>">

    <!-- الهيدر: صورة + اسم + سعر -->
    <td class="cart-item-header">
        <img src="../images/<?= $item['image'] ?>" alt="product">
        <div class="cart-item-info">
            <div class="name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="price"><?= $item['price'] ?> د.ل</div>
        </div>
    </td>

    <!-- أدوات الكمية + الإجمالي -->
    <td class="quantity-box">

        <div class="quantity-controls">
            <button class="update-btn decrease">-</button>
            <span class="qty"><?= $item['quantity'] ?></span>
            <button class="update-btn increase">+</button>
        </div>

        <div class="item-total">
            <?= $item['price'] * $item['quantity'] ?> د.ل
        </div>

    </td>

    <!-- زر الحذف -->
    <td>
        <button class="remove-btn">حذف</button>
    </td>

</tr>
    <?php endforeach; ?>
    </tbody>
</table>

    <h3>الإجمالي الكلي: <span id="total"><?= $total ?></span> د.ل</h3>

<div class="payment-buttons">

    <!-- الدفع عند الاستلام -->
    <button id="confirm-order" class="pay-btn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <path d="M3 7h13v10H3V7zm13 4h3l3 3v3h-6v-6zM5 17a2 2 0 11.001-3.999A2 2 0 015 17zm12 0a2 2 0 110-3.999A2 2 0 0117 17z"
                  stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        الدفع عند الاستلام
    </button>

    <!-- الدفع من المحفظة -->
    <button id="pay-wallet" class="pay-btn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <path d="M3 7h18v13H3V7zm0-3h18v3H3V4zm12 8h5v4h-5v-4z"
                  stroke="#fff" stroke-width="2" stroke-linejoin="round"/>
        </svg>
        الدفع من المحفظة
    </button>

    <!-- الاستلام من المحل -->
    <button id="pickup-order" class="pay-btn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <path d="M3 9l9-6 9 6v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9zm4 12v-6h10v6"
                  stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        الاستلام من المحل
    </button>

    
    <!-- زر الحجز المبدئي باللون الأحمر الهافت -->
<button id="reserve-order" class="pay-btn" style="background-color: #e66767; border: none;">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
        <!-- أيقونة تقويم مع علامة إضافة -->
        <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" 
              stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M12 14v4m-2-2h4" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
    </svg>
    حجز مبدئي (ليوم آخر)
</button>
    
</div>

<?php else: ?>
    <p>السلة فارغة.</p>
<?php endif; ?>

</div>
</section>


<script>
// 🔄 تحديث السحب Ajax (بدون refresh)
function refreshCart(){
    fetch("cart.php")
    .then(res => res.text())
    .then(html => {
        let parser = new DOMParser();
        let doc = parser.parseFromString(html, "text/html");
        document.getElementById("cart-container").innerHTML =
            doc.getElementById("cart-container").innerHTML;
        attachEvents(); // إعادة تفعيل الأحداث بعد التحديث
    });
}

// 🎯 زر الزيادة / النقصان / الحذف
function attachEvents(){

document.querySelectorAll(".update-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        let tr = btn.closest("tr");
        let id = tr.dataset.id;
        let action = btn.classList.contains("increase") ? "increase" : "decrease";

        fetch("update_cart.php", {
            method: "POST",
            headers: {"Content-Type":"application/x-www-form-urlencoded"},
            body: "cart_id="+id+"&action="+action
        })
        .then(res => res.json())
        .then(d => { if(d.success) refreshCart(); });
    });
});

document.querySelectorAll(".remove-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        let id = btn.closest("tr").dataset.id;

        fetch("update_cart.php", {
            method:"POST",
            headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body:"cart_id="+id+"&action=remove"
        })
        .then(res=>res.json())
        .then(d => { if(d.success) refreshCart(); });
    });
});

// زر الدفع عند الاستلام
document.getElementById("confirm-order")?.addEventListener("click", () => {
    fetch("confirm_order.php", {method:"POST"})
    .then(r=>r.json())
    .then(d => {
        if(d.success) window.location="checkout.php";
        else alert(d.message);
    });
});

// زر الدفع بالمحفظة
document.getElementById("pay-wallet")?.addEventListener("click", () => {
    fetch("pay_with_wallet.php",{method:"POST"})
    .then(r=>r.json())
    .then(d=>{
        if(d.success) window.location="checkout.php";
        else alert(d.message);
    });
});

// زر الاستلام من المحل
document.getElementById("pickup-order")?.addEventListener("click", () => {
    window.location = "pickup_options.php";
});


}

attachEvents();
    
    // زر الحجز المبدئي
document.getElementById("reserve-order")?.addEventListener("click", () => {
    // سنقوم بتوجيه المستخدم مباشرة لصفحة المعالجة مع علامة تخبرنا أنه "حجز"
    window.location = "process_reservation.php"; 
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
</script>

</body>
</html>
